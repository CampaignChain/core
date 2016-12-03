<?php
/*
 * Copyright 2016 CampaignChain, Inc. <info@campaignchain.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CampaignChain\CoreBundle\Controller\Module;

use CampaignChain\CoreBundle\Entity\Activity;
use CampaignChain\CoreBundle\Entity\Campaign;
use CampaignChain\CoreBundle\Entity\Channel;
use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\CoreBundle\Entity\Medium;
use CampaignChain\CoreBundle\Entity\Module;
use CampaignChain\CoreBundle\EntityService\ActivityService;
use CampaignChain\CoreBundle\Exception\ExternalApiException;
use CampaignChain\CoreBundle\Validator\AbstractOperationValidator;
use CampaignChain\CoreBundle\Wizard\ActivityWizard;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use CampaignChain\CoreBundle\Entity\Operation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivityModuleController extends Controller
{
    protected $parameters;

    /**
     * @var AbstractActivityHandler
     */
    protected $handler;

    /**
     * @var AbstractOperationValidator
     */
    protected $validator = array();

    /**
     * @var Campaign
     */
    protected $campaign;

    /**
     * @var Activity
     */
    protected $activity;

    /**
     * @var Location
     */
    protected $location;

    /**
     * @var Channel
     */
    protected $channel;

    protected $operations = array();

    protected $view = 'default';

    private $activityBundleName;
    private $activityModuleIdentifier;
    private $locationBundleName;
    private $locationModuleIdentifier;
    private $contentBundleName;
    private $contentModuleIdentifier;
    private $contentFormType;
    private $contentModuleFormName;

    private $logger;

    public function getLogger()
    {
        return $this->has('monolog.logger.external') ? $this->get('monolog.logger.external') : $this->get('monolog.logger');
    }

    public function setParameters($parameters){
        $this->parameters = $parameters;

        if(!isset($this->parameters['handler'])){
            throw new \Exception('No Activity handler defined in services.yml.');
        }

        /** @var AbstractActivityHandler handler */
        $this->handler = $this->get($this->parameters['handler']);

        if(isset($this->parameters['validator'])){
            $this->validators['activity'] = $this->get($this->parameters['validator']);
        }

        $this->activityBundleName = $this->parameters['bundle_name'];
        $this->activityModuleIdentifier = $this->parameters['module_identifier'];

        if(!isset($this->parameters['equals_operation'])){
            $this->parameters['equals_operation'] = false;
        }

        if(isset($this->parameters['location'])) {
            $this->locationBundleName = $this->parameters['location']['bundle_name'];
            $this->locationModuleIdentifier = $this->parameters['location']['module_identifier'];
        }

        if($this->parameters['equals_operation']) {
            $this->contentBundleName = $this->parameters['operations'][0]['bundle_name'];
            $this->contentModuleIdentifier = $this->parameters['operations'][0]['module_identifier'];
            $this->contentModuleFormName = str_replace('-', '_', $this->contentModuleIdentifier);
            $this->contentFormType = $this->parameters['operations'][0]['form_type'];
            if(isset($this->parameters['operations'][0]['validator'])){
                $this->validators['operations'][0] = $this->get(
                    $this->parameters['operations'][0]['validator']
                );
            }
        } else {
            $this->contentFormType = $this->parameters['content_form_type'];
        }
    }

    public function setActivityContext(Campaign $campaign, Location $location = null){
        $this->campaign = $campaign;
        $this->location = $location;

        /** @var AbstractActivityHandler location */
        $this->location = $this->handler->processActivityLocation($this->location);

        if($this->location) {
            $this->channel = $this->location->getChannel();
        }
    }

    /**
     * Symfony controller action for creating a new CampaignChain Activity.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Exception
     */
    public function newAction(Request $request)
    {
        $operation = null;

        /*
         * Set Activity's context from user's choice.
         */
        /** @var ActivityWizard $wizard */
        $wizard = $this->get('campaignchain.core.activity.wizard');
        if (!$wizard->getCampaign()) {
            dump($wizard);exit;
            return $this->redirectToRoute('campaignchain_core_activities_new');
        }

        $campaignService = $this->get('campaignchain.core.campaign');
        $campaign = $campaignService->getCampaign($wizard->getCampaign());
        $locationService = $this->get('campaignchain.core.location');
        if($wizard->getLocation()) {
            $location = $locationService->getLocation($wizard->getLocation());
        } else {
            $location = null;
        }

        $this->setActivityContext($campaign, $location);

        $activity = $wizard->getNewActivity();
        $activity->setEqualsOperation($this->parameters['equals_operation']);

        $form = $this->createForm(
            $this->getActivityFormType('new'), $activity
        );

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();

        if ($form->isValid()) {
            try {
                $em->getConnection()->beginTransaction();

                $activity = $this->createActivity($activity, $form);

                $this->addFlash(
                    'success',
                    'Your new activity <a href="' . $this->generateUrl('campaignchain_core_activity_edit', array('id' => $activity->getId())) . '">' . $activity->getName() . '</a> was created successfully.'
                );

                $wizard->end();

                $em->getConnection()->commit();

                return $this->redirect($this->generateUrl('campaignchain_core_activities'));
            } catch(\Exception $e) {
                $em->getConnection()->rollback();

                if($this->get('kernel')->getEnvironment() == 'dev'){
                    $message = $e->getMessage().' '.$e->getFile().' '.$e->getLine().'<br/>'.$e->getTraceAsString();
                } else {
                    $message = $e->getMessage();
                }
                $this->addFlash(
                    'warning',
                    $message
                );

                $this->getLogger()->error($e->getMessage(), array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace(),
                ));
            }
        }

        if($location){
            $channelModule = $wizard->getChannelModule();
            $channelModuleBundle = $wizard->getChannelModuleBundle();
        } else {
            $channelModule = null;
            $channelModuleBundle = null;
        }

        /*
         * Define default rendering options and then apply those defined by the
         * module's handler if applicable.
         */
        $defaultRenderOptions = array(
            'template' => 'CampaignChainCoreBundle:Operation:new.html.twig',
            'vars' => array(
                'page_title' => 'New Activity',
                'activity' => $activity,
                'campaign' => $this->campaign,
                'campaign_module' => $this->campaign->getCampaignModule(),
                'channel_module' => $channelModule,
                'channel_module_bundle' => $channelModuleBundle,
                'location' => $this->location,
                'form' => $form->createView(),
                'form_submit_label' => 'Save',
                'form_cancel_route' => 'campaignchain_core_activities_new'
            )
        );

        $handlerRenderOptions = $this->handler->getNewRenderOptions();

        return $this->renderWithHandlerOptions($defaultRenderOptions, $handlerRenderOptions);
    }

    public function createActivity(Activity $activity, Form $form)
    {
        // Apply context of Activity.
        if(!$activity->getCampaign()) {
            $activity->setCampaign($this->campaign);
        } elseif(!$this->campaign){
            $this->campaign = $activity->getCampaign();
        }

        if(!$activity->getChannel()) {
            $activity->setChannel($this->channel);
        } elseif(!$this->channel){
            $this->channel = $activity->getChannel();
        }

        if(!$activity->getLocation()) {
            $activity->setLocation($this->location);
        } elseif(!$this->location){
            $this->location = $activity->getLocation();
        }

        // The Module's content.
        $content = null;

        // If Activity module is not set, then do it.
        if(!$activity->getActivityModule()){
            $moduleService = $this->get('campaignchain.core.module');
            $activity->setActivityModule(
                $moduleService->getModule(
                    $this->activityBundleName,
                    $this->activityModuleIdentifier
                )
            );
        }

        // Does the Activity module relate to at least one Channel module?
        $hasChannel = $activity->getActivityModule()->getChannelModules()->count();

        // The Module's content.
        $content = null;
        $operation = new Operation();

        if($this->parameters['equals_operation']) {
            if($hasChannel) {
                if (!$activity->getChannel()) {
                    $activity->setChannel($this->channel);
                } elseif (!$this->channel) {
                    $this->channel = $activity->getChannel();
                }

                if (!$activity->getLocation()) {
                    $activity->setLocation($this->location);
                } elseif (!$this->location) {
                    $this->location = $activity->getLocation();
                }
            }

            // Allow the module to change some data based on its custom input.
            if($form->has($this->contentModuleFormName)) {
                $this->handler->postFormSubmitNewEvent(
                    $activity,
                    $form->get($this->contentModuleFormName)->getData()
                );

                // Allow a module's handler to modify the Activity data.
                $activity = $this->handler->processActivity(
                    $activity,
                    $form->get($this->contentModuleFormName)->getData()
                );
            }

            // Get the operation module.
            $operationService = $this->get('campaignchain.core.operation');
            $operation = $operationService->newOperationByActivity(
                $activity,
                $this->contentBundleName,
                $this->contentModuleIdentifier
            );

            // Will the Operation create a Location, i.e. the Operation
            // will be accessible through a URL after publishing?
            if($operation->getOperationModule()->ownsLocation()) {
                // Get the location module.
                $locationService = $this->get('campaignchain.core.location');
                $locationModule = $locationService->getLocationModule(
                    $this->locationBundleName,
                    $this->locationModuleIdentifier
                );

                $contentLocation = new Location();
                $contentLocation->setLocationModule($locationModule);
                $contentLocation->setParent($activity->getLocation());
                $contentLocation->setName($activity->getName());
                $contentLocation->setStatus(Medium::STATUS_UNPUBLISHED);
                $contentLocation->setOperation($operation);
                $operation->addLocation($contentLocation);

                if ($form->has($this->contentModuleFormName)) {
                    // Allow a module's handler to modify the Operation's Location.
                    $contentLocation = $this->handler->processContentLocation(
                        $contentLocation,
                        $form->get($this->contentModuleFormName)->getData()
                    );
                }
            }

            if($form->has($this->contentModuleFormName)) {
                // Process the Operation's content.
                $content = $this->handler->processContent(
                    $operation,
                    $form->get($this->contentModuleFormName)->getData()
                );
            }

            if($content) {
                // Link the Operation details with the operation.
                $content->setOperation($operation);
            }
        } elseif(count($this->parameters['operations']) > 1) {
            $content = $this->handler->processMultiOperationMultiContent(
                $activity, $form, $this->parameters['operations']
            );
        }

        $em = $this->getDoctrine()->getManager();

        // Make sure that data stays intact by using transactions.
        try {
            $em->getConnection()->beginTransaction();

            $em->persist($activity);

            if(!$content) {
                $content = $this->handler->processSingleContentMultiOperation(
                    $activity, $form
                );
            }

            if($content) {
                $em->persist($content);
            }

            // We need the activity ID for storing the hooks. Hence we must
            // flush here.
            $em->flush();

            $hookService = $this->get('campaignchain.core.hook');
            /** @var Activity $activity */
            $activity = $hookService->processHooks(
                $this->parameters['bundle_name'],
                $this->parameters['module_identifier'],
                $activity,
                $form,
                true
            );

            // Check if the content can be executed.
            if(isset($this->validators['operations']) && $content){
                $isExecutable = $this->validators['operations'][0]->isExecutableByLocation($content, $activity->getStartDate());
                if(!$isExecutable['status']) {
                    throw new \Exception($isExecutable['message']);
                }

                $activity->setMustValidate(
                    $this->validators['operations'][0]->mustValidate($content, $activity->getStartDate())
                );
            }

            $em->flush();

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw $e;
        }

        // The module tries to execute the job immediately.
        $this->handler->postPersistNewEvent($operation, $content);

        return $activity;
    }

    /**
     * Symfony controller action for editing a CampaignChain Activity.
     *
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Exception
     */
    public function editAction(Request $request, $id)
    {
        $activityService = $this->get('campaignchain.core.activity');
        $this->activity = $activityService->getActivity($id);
        $this->campaign = $this->activity->getCampaign();
        $this->location = $this->activity->getLocation();

        if($this->parameters['equals_operation']) {
            // Get the one operation.
            $this->operations[0] = $activityService->getOperation($id);
        } else {
            throw new \Exception(
                'Multiple Operations for one Activity not implemented yet.'
            );
        }

        $content = $this->handler->preFormSubmitEditEvent($this->operations[0]);

        $form = $this->createForm(
            $this->getActivityFormType('edit'), $this->activity
        );

        $form->handleRequest($request);

        if ($form->isValid()) {

            try {
                $this->activity = $this->editActivity($this->activity, $form, $content);

                $this->addFlash(
                    'success',
                    'Your activity <a href="'.$this->generateUrl('campaignchain_core_activity_edit', array('id' => $this->activity->getId())).'">'.$this->activity->getName().'</a> was edited successfully.'
                );

                return $this->redirect($this->generateUrl('campaignchain_core_activities'));
            } catch(\Exception $e) {
                $this->addFlash(
                    'warning',
                    $e->getMessage()
                );

                $this->getLogger()->error($e->getMessage(), array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace(),
                ));
            }
        }

        /*
         * Define default rendering options and then apply those defined by the
         * module's handler if applicable.
         */
        $defaultRenderOptions = array(
            'template' => 'CampaignChainCoreBundle:Operation:new.html.twig',
            'vars' => array(
                'page_title' => 'Edit Activity',
                'activity' => $this->activity,
                'form' => $form->createView(),
                'form_submit_label' => 'Save',
                'form_cancel_route' => 'campaignchain_core_activities'
            )
        );

        $handlerRenderOptions = $this->handler->getEditRenderOptions(
            $this->operations[0]
        );

        return $this->renderWithHandlerOptions($defaultRenderOptions, $handlerRenderOptions);
    }

    public function editActivity(Activity $activity, Form $form, $content)
    {
        /** @var ActivityService $activityService */
        $activityService = $this->get('campaignchain.core.activity');
        /** @var Operation $operation */
        $operation = $activityService->getOperation($activity->getId());

        $em = $this->getDoctrine()->getManager();

        // Make sure that data stays intact by using transactions.
        try {
            $em->getConnection()->beginTransaction();

            if($this->handler->hasContent('edit')) {
                // Get the content data from request.
                $content = $this->handler->processContent(
                    $operation,
                    $form->get($this->contentModuleFormName)->getData()
                );

                if ($this->parameters['equals_operation']) {
                    // The activity equals the operation. Thus, we update the operation with the same data.
                    $operation->setName($activity->getName());
                    $em->persist($operation);
                } else {
                    throw new \Exception(
                        'Multiple Operations for one Activity not implemented yet.'
                    );
                }

                $em->persist($content);
            }

            $hookService = $this->get('campaignchain.core.hook');
            /** @var Activity $activity */
            $activity = $hookService->processHooks(
                $this->activityBundleName,
                $this->activityModuleIdentifier,
                $activity,
                $form
            );

            // Check if the content can be executed.
            if(isset($this->validators['operations'])) {
                $isExecutable = $this->validators['operations'][0]->isExecutableByLocation($content, $activity->getStartDate());
                if (!$isExecutable['status']) {
                    throw new \Exception($isExecutable['message']);
                }

                $activity->setMustValidate(
                    $this->validators['operations'][0]->mustValidate($content, $activity->getStartDate())
                );
            }

            $em->persist($activity);

            $em->flush();

            // The module tries to execute the job immediately.
            $this->handler->postPersistEditEvent($this->operations[0], $content);

            $em->getConnection()->commit();

            return $activity;
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Symfony controller action for editing a CampaignChain Activity in a
     * pop-up window.
     *
     * @param Request $request
     * @param $id
     * @return Response
     * @throws \Exception
     */
    public function editModalAction(Request $request, $id)
    {
        $activityService = $this->get('campaignchain.core.activity');
        $this->activity = $activityService->getActivity($id);
        $this->location = $this->activity->getLocation();
        $this->campaign = $this->activity->getCampaign();

        if($this->parameters['equals_operation']) {
            // Get the one operation.
            $this->operations[0] = $activityService->getOperation($id);
        } else {
            throw new \Exception(
                'Multiple Operations for one Activity not implemented yet.'
            );
        }

        $activityFormType = $this->getActivityFormType('editModal');
        $activityFormType->setView('default');

        $this->handler->preFormSubmitEditModalEvent($this->operations[0]);

        $form = $this->createForm($activityFormType, $this->activity);

        $form->handleRequest($request);

        /*
         * Define default rendering options and then apply those defined by the
         * module's handler if applicable.
         */
        $defaultRenderOptions = array(
            'template' => 'CampaignChainCoreBundle:Base:new_modal.html.twig',
            'vars' => array(
                'page_title' => 'Edit Activity',
                'form' => $form->createView()
            )
        );

        $handlerRenderOptions = $this->handler->getEditModalRenderOptions(
            $this->operations[0]
        );

        return $this->renderWithHandlerOptions($defaultRenderOptions, $handlerRenderOptions);
    }

    /**
     * Symfony controller action that takes the data posted by the editModalAction
     * and persists it.
     *
     * @param Request $request
     * @param $id
     * @return Response
     * @throws \Exception
     */
    public function editApiAction(Request $request, $id)
    {
        $responseData = array();

        $data = $request->get('campaignchain_core_activity');

        $activityService = $this->get('campaignchain.core.activity');
        $this->activity = $activityService->getActivity($id);
        // Remember original dates.
        $responseData['start_date'] =
        $responseData['end_date'] =
            $this->activity->getStartDate()->format(\DateTime::ISO8601);
        $this->activity->setName($data['name']);

        $em = $this->getDoctrine()->getManager();

        // Make sure that data stays intact by using transactions.
        try {
            $em->getConnection()->beginTransaction();

            if($this->parameters['equals_operation']) {
                /** @var Operation $operation */
                $operation = $activityService->getOperation($id);
                // The activity equals the operation. Thus, we update the operation
                // with the same data.
                $operation->setName($data['name']);
                $this->operations[0] = $operation;

                if($this->handler->hasContent('editModal')){
                    $content = $this->handler->processContent(
                        $this->operations[0],
                        $data[$this->contentModuleFormName]
                    );
                } else {
                    $content = null;
                }
            } else {
                throw new \Exception(
                    'Multiple Operations for one Activity not implemented yet.'
                );
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($this->activity);
            $em->persist($this->operations[0]);
            if($this->handler->hasContent('editModal')) {
                $em->persist($content);
            }

            $hookService = $this->get('campaignchain.core.hook');
            $this->activity = $hookService->processHooks(
                $this->parameters['bundle_name'],
                $this->parameters['module_identifier'],
                $this->activity,
                $data
            );

            $em->flush();

            // The module tries to execute the job immediately.
            $this->handler->postPersistEditEvent($operation, $content);

            $responseData['start_date'] =
            $responseData['end_date'] =
                $this->activity->getStartDate()->format(\DateTime::ISO8601);
            $responseData['success'] = true;

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();

            $this->addFlash(
                'warning',
                $e->getMessage().' '.$e->getFile().' '.$e->getLine().' '.$e->getTraceAsString()
            );

            $this->getLogger()->error($e->getMessage(), array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ));

            $responseData['message'] = $e->getMessage();
            $responseData['success'] = false;
        }

        $serializer = $this->get('campaignchain.core.serializer.default');
        
        return new Response($serializer->serialize($responseData, 'json'));
    }

    /**
     * Symfony controller action for viewing the data of a CampaignChain Activity.
     *
     * @param Request $request
     * @param $id
     * @param bool $isModal Modal view yes or no?
     * @return mixed
     * @throws \Exception
     */
    public function readAction(Request $request, $id, $isModal = false)
    {
        $activityService = $this->get('campaignchain.core.activity');
        $this->activity = $activityService->getActivity($id);

        if($this->parameters['equals_operation']) {
            // Get the one operation.
            $this->operations[0] = $activityService->getOperation($id);
        } else {
            throw new \Exception(
                'Multiple Operations for one Activity not implemented yet.'
            );
        }

        if($this->handler){
            return $this->handler->readAction($this->operations[0], $isModal);
        } else {
            throw new \Exception('No read handler defined.');
        }
    }

    /**
     * Symfony controller action for viewing the data of a CampaignChain Activity
     * in a modal.
     *
     * @param Request $request
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function readModalAction(Request $request, $id)
    {
        return $this->readAction($request, $id, true);
    }

    /**
     * Configure an Activity's form type.
     *
     * @return object
     */
    public function getActivityFormType($view = 'default')
    {
        $this->view = $view;

        $activityFormType = $this->get('campaignchain.core.form.type.activity');
        $activityFormType->setView($this->view);
        $activityFormType->setBundleName($this->parameters['bundle_name']);
        $activityFormType->setModuleIdentifier(
            $this->parameters['module_identifier']
        );
        if (isset($this->parameters['hooks_options'])) {
            $activityFormType->setHooksOptions($this->parameters['hooks_options']);
        }
        if($this->handler->hasContent($this->view)) {
            $activityFormType->setContentForms(
                $this->getContentFormTypes()
            );
        }
        $activityFormType->setCampaign($this->campaign);

        return $activityFormType;
    }

    /**
     * Set the Operation forms for this Activity.
     *
     * @return array
     * @throws \Exception
     */
    private function getContentFormTypes()
    {
        foreach($this->parameters['operations'] as $operationParams){
            $operationForms[] = $this->getContentFormType($operationParams);
        }

        return $operationForms;
    }

    private function getContentFormType($params)
    {
        $contentFormType = new $params['form_type'](
            $this->getDoctrine(),
            $this->get('service_container')
        );

        if($this->location) {
            $contentFormType->setLocation($this->location);
        }

        if($this->handler){
            if(isset($this->operations[0])){
                $content = $this->handler->getContent(
                    $this->location, $this->operations[0]
                );
            } else {
                $content = $this->handler->createContent($this->location, $this->campaign);
            }
            $contentFormType->setContent($content);
        }

        $formName = str_replace('-', '_', $params['module_identifier']);

        return array(
            'identifier' => $formName,
            'form' => $contentFormType
        );
    }

    /**
     * Applies handler's template render options to default ones.
     *
     * @param $default
     * @param $handler
     * @return array
     */
    private function renderWithHandlerOptions($default, $handler)
    {
        if(
            $handler && is_array($handler) && count($handler) &&
            $default && is_array($default) && count($default)
        ){
            if(isset($handler['template'])){
                $default['template'] = $handler['template'];
            }
            if(isset($handler['vars'])) {
                $default['vars'] = $default['vars'] + $handler['vars'];
            }
        }

        return $this->render($default['template'], $default['vars']);
    }

    /**
     * This method checks whether the given Location is within a Channel
     * that the module's Activity is related to.
     *
     * @param $id The Location ID.
     * @return bool
     */
    public function isValidLocation($id)
    {
        $qb = $this->getDoctrine()->getEntityManager()->createQueryBuilder();
        $qb->select('b.name, am.identifier');
        $qb->from('CampaignChain\CoreBundle\Entity\Activity', 'a');
        $qb->from('CampaignChain\CoreBundle\Entity\ActivityModule', 'am');
        $qb->from('CampaignChain\CoreBundle\Entity\Bundle', 'b');
        $qb->from('CampaignChain\CoreBundle\Entity\Module', 'm');
        $qb->from('CampaignChain\CoreBundle\Entity\Location', 'l');
        $qb->from('CampaignChain\CoreBundle\Entity\Channel', 'c');
        $qb->innerJoin('am.channelModules', 'cm');
        $qb->where('cm.id = c.channelModule');
        $qb->andWhere('l.id = :location');
        $qb->andWhere('l.channel = c.id');
        $qb->andWhere('a.activityModule = am.id');
        $qb->andWhere('am.bundle = b.id');
        $qb->setParameter('location', $id);
        $qb->groupBy('b.name');
        $query = $qb->getQuery();
        $result = $query->getResult();

        if(
            !is_array($result) || !count($result) ||
            $result[0]['name'] != $this->activityBundleName ||
            $result[0]['identifier'] != $this->activityModuleIdentifier
        ){
            return false;
        } else {
            return true;
        }
    }
}