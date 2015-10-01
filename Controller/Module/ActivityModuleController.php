<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Controller\Module;

use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\CoreBundle\Entity\Medium;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use CampaignChain\CoreBundle\Entity\Operation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

class ActivityModuleController extends Controller
{
    protected $parameters;

    protected $handler;

    protected $campaign;

    protected $activity;

    protected $location;

    protected $operations = array();

    private $locationBundleName;
    private $locationModuleIdentifier;
    private $operationBundleName;
    private $operationModuleIdentifier;
    private $operationFormType;

    public function setParameters($parameters){
        $this->parameters = $parameters;

        if(!isset($this->parameters['controller_handler'])){
            throw new \Exception('No controller handler defined in services.yml.');
        }
        $this->handler = $this->get($this->parameters['controller_handler']);

        $this->locationBundleName = $this->parameters['location']['bundle_name'];
        $this->locationModuleIdentifier = $this->parameters['location']['module_identifier'];
        $this->operationBundleName = $this->parameters['operations'][0]['bundle_name'];
        $this->operationModuleIdentifier = $this->parameters['operations'][0]['module_identifier'];
        $this->operationFormType = $this->parameters['operations'][0]['form_type'];
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
        /*
         * Get context from user's choice.
         */
        $wizard = $this->get('campaignchain.core.activity.wizard');
        $campaignService = $this->get('campaignchain.core.campaign');
        $this->campaign = $campaignService->getCampaign($wizard->getCampaign());
        $this->activity = $wizard->getActivity();
        $this->activity->setEqualsOperation($this->parameters['equals_operation']);
        $locationService = $this->get('campaignchain.core.location');
        $this->location = $locationService->getLocation($wizard->getLocation());

        $form = $this->createForm(
            $this->getActivityFormType('new'), $this->activity
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->activity = $wizard->end();
            // Allow a module's handler to modify the Activity data.
            $this->activity = $this->handler->processActivity(
                $this->activity,
                $form->get($this->operationModuleIdentifier)->getData()
            );

            // Get the operation module.
            $operationService = $this->get('campaignchain.core.operation');
            $operationModule = $operationService->getOperationModule(
                $this->operationBundleName,
                $this->operationModuleIdentifier
            );

            if($this->parameters['equals_operation']) {
                // The activity equals the operation. Thus, we create a new operation with the same data.
                $operation = new Operation();
                $operation->setName($this->activity->getName());
                $operation->setActivity($this->activity);
                $this->activity->addOperation($operation);
                $operationModule->addOperation($operation);
                $operation->setOperationModule($operationModule);

                // The Operation creates a Location, i.e. the Operation
                // will be accessible through a URL after publishing.

                // Get the location module.
                $locationModule = $locationService->getLocationModule(
                    $this->locationBundleName,
                    $this->locationModuleIdentifier
                );

                $operationLocation = new Location();
                $operationLocation->setLocationModule($locationModule);
                $operationLocation->setParent($this->activity->getLocation());
                $operationLocation->setName($this->activity->getName());
                $operationLocation->setStatus(Medium::STATUS_UNPUBLISHED);
                $operationLocation->setOperation($operation);
                $operation->addLocation($operationLocation);
                // Allow a module's handler to modify the Operation's Location.
                $operationLocation = $this->handler->processOperationLocation(
                    $operationLocation,
                    $form->get($this->operationModuleIdentifier)->getData()
                );

                // Process the Operation details.
                $operationDetails = $this->handler->processOperationDetails(
                    $operation,
                    $form->get($this->operationModuleIdentifier)->getData()
                );

                // Link the Operation details with the operation.
                $operationDetails->setOperation($operation);
            } else {
                throw new \Exception(
                    'Multiple Operations for one Activity not implemented yet.'
                );
            }

            $repository = $this->getDoctrine()->getManager();

            // Make sure that data stays intact by using transactions.
            try {
                $repository->getConnection()->beginTransaction();

                $repository->persist($this->activity);
                $repository->persist($operationDetails);

                // We need the activity ID for storing the hooks. Hence we must
                // flush here.
                $repository->flush();

                $hookService = $this->get('campaignchain.core.hook');
                $this->activity = $hookService->processHooks(
                    $this->parameters['bundle_name'],
                    $this->parameters['module_identifier'],
                    $this->activity,
                    $form,
                    true
                );
                $repository->flush();

                $repository->getConnection()->commit();
            } catch (\Exception $e) {
                $repository->getConnection()->rollback();
                throw $e;
            }

            $this->handler->postPersistNewAction($operation);

            $this->get('session')->getFlashBag()->add(
                'success',
                'Your new activity <a href="'.$this->generateUrl('campaignchain_core_activity_edit', array('id' => $this->activity->getId())).'">'.$this->activity->getName().'</a> was created successfully.'
            );

            // Status Update to be sent immediately?
            // TODO: This is an intermediary hardcoded hack and should be instead handled by the scheduler.
            if ($form->get('campaignchain_hook_campaignchain_due')->has('execution_choice') && $form->get('campaignchain_hook_campaignchain_due')->get('execution_choice')->getData() == 'now') {
                $job = $this->get($this->parameters['operation_job']);
                $job->execute($operation->getId());
                // TODO: Add different flashbag which includes link to posted message on Facebook
            }

            return $this->redirect($this->generateUrl('campaignchain_core_activities'));
        }

        return $this->render(
            'CampaignChainCoreBundle:Operation:new.html.twig',
            array(
                'page_title' => 'New Activity',
                'activity' => $this->activity,
                'campaign' => $this->campaign,
                'campaign_module' => $this->campaign->getCampaignModule(),
                'channel_module' => $wizard->getChannelModule(),
                'channel_module_bundle' => $wizard->getChannelModuleBundle(),
                'location' => $this->location,
                'form' => $form->createView(),
                'form_submit_label' => 'Save',
                'form_cancel_route' => 'campaignchain_core_activities_new'
            ));

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

        $this->handler->preFormCreateEditAction($this->operations[0]);

        $form = $this->createForm(
            $this->getActivityFormType('edit'), $this->activity
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            $repository = $this->getDoctrine()->getManager();

            // Make sure that data stays intact by using transactions.
            try {
                $repository->getConnection()->beginTransaction();

                if($this->handler->hasOperationForm('edit')) {
                    // Get the status data from request.
                    $operationDetails =
                        $form->get($this->operationModuleIdentifier)
                            ->getData();

                    if ($this->parameters['equals_operation']) {
                        // The activity equals the operation. Thus, we update the operation with the same data.
                        $this->operations[0]->setName($this->activity->getName());
                        $repository->persist($this->operations[0]);
                    } else {
                        throw new \Exception(
                            'Multiple Operations for one Activity not implemented yet.'
                        );
                    }

                    $repository->persist($operationDetails);
                }

                $hookService = $this->get('campaignchain.core.hook');
                $this->activity = $hookService->processHooks(
                    $this->parameters['bundle_name'],
                    $this->parameters['module_identifier'],
                    $this->activity,
                    $form
                );
                $repository->persist($this->activity);

                $repository->flush();

    //            // Status Update should be sent immediately
    //            if ($form->get('actions')->get('send')->isClicked()) {
    //                $job = $this->get('campaignchain.job.operation.twitter.update_status');
    //                $job->execute($operation);
    //
    //                // TODO: If this previously was a scheduled activity, then reset the schedule
    //            }
                $repository->getConnection()->commit();
            } catch (\Exception $e) {
                $repository->getConnection()->rollback();
                throw $e;
            }

            $this->get('session')->getFlashBag()->add(
                'success',
                'Your activity <a href="'.$this->generateUrl('campaignchain_core_activity_edit', array('id' => $this->activity->getId())).'">'.$this->activity->getName().'</a> was edited successfully.'
            );

            if ($form->get('campaignchain_hook_campaignchain_due')->has('execution_choice') && $form->get('campaignchain_hook_campaignchain_due')->get('execution_choice')->getData() == 'now') {
                $job = $this->get($this->parameters['operation_job']);
                $job->execute($this->operations[0]->getId());
                // TODO: Add different flashbag which includes link to posted message on Facebook
            }

            return $this->redirect($this->generateUrl('campaignchain_core_activities'));
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

        $handlerRenderOptions = $this->handler->getRenderOptionsEditAction(
            $this->operations[0]
        );

        return $this->renderWithHandlerOptions($defaultRenderOptions, $handlerRenderOptions);
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

        $this->handler->preFormCreateEditModalAction($this->operations[0]);

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

        $handlerRenderOptions = $this->handler->getRenderOptionsEditModalAction(
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
        $this->activity->setName($data['name']);

        if($this->parameters['equals_operation']) {
            // Get the one operation.
            $operation = $activityService->getOperation($id);
            // The activity equals the operation. Thus, we update the operation
            // with the same data.
            $operation->setName($data['name']);
            $this->operations[0] = $operation;

            if($this->handler->hasOperationForm('editModal')){
                $operationDetails = $this->handler->processOperationDetails(
                    $this->operations[0],
                    $data[$this->operationModuleIdentifier]
                );
            }
        } else {
            throw new \Exception(
                'Multiple Operations for one Activity not implemented yet.'
            );
        }

        $repository = $this->getDoctrine()->getManager();
        $repository->persist($this->activity);
        $repository->persist($this->operations[0]);
        if($this->handler->hasOperationForm('editModal')) {
            $repository->persist($operationDetails);
        }

        $hookService = $this->get('campaignchain.core.hook');
        $this->activity = $hookService->processHooks(
            $this->parameters['bundle_name'],
            $this->parameters['module_identifier'],
            $this->activity,
            $data
        );

        $repository->flush();

        $responseData['start_date'] =
        $responseData['end_date'] =
            $this->activity->getStartDate()->format(\DateTime::ISO8601);

        $encoders = array(new JsonEncoder());
        $normalizers = array(new GetSetMethodNormalizer());
        $serializer = new Serializer($normalizers, $encoders);

        $response = new Response($serializer->serialize($responseData, 'json'));
        return $response->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Symfony controller action for viewing the data of a CampaignChain Activity.
     *
     * @param Request $request
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function readAction(Request $request, $id)
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
            return $this->handler->readOperationDetail($this->operations[0]);
        } else {
            throw new \Exception('No read handler defined.');
        }
    }

    /**
     * Configure an Activity's form type.
     *
     * @return object
     */
    private function getActivityFormType($view)
    {
        $activityFormType = $this->get('campaignchain.core.form.type.activity');
        $activityFormType->setBundleName($this->parameters['bundle_name']);
        $activityFormType->setModuleIdentifier(
            $this->parameters['module_identifier']
        );
        if($this->handler->hasOperationForm($view)) {
            $activityFormType->setOperationForms(
                $this->getOperationFormTypes()
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
    private function getOperationFormTypes()
    {
        if($this->parameters['equals_operation']) {
            $operationForm = $this->operationFormType;
            $operationFormType = new $operationForm(
                $this->getDoctrine()->getManager(),
                $this->get('service_container')
            );

            if($this->location) {
                $operationFormType->setLocation($this->location);
            }

            if($this->handler){
                if(isset($this->operations[0])){
                    $operation = $this->operations[0];
                } else {
                    $operation = null;
                }
                $operationFormType->setOperationDetail(
                    $this->handler->getOperationDetail($this->location, $operation)
                );
            }

            $operationForms[] = array(
                'identifier' => $this->operationModuleIdentifier,
                'form' => $operationFormType
            );
        } else {
            throw new \Exception(
                'Multiple Operations for one Activity not implemented yet.'
            );
        }

        return $operationForms;
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
        if($handler){
            if(isset($handler['template'])){
                $default['template'] = $handler['template'];
            }
            if(isset($handler['vars'])) {
                $default['vars'] = $default['vars'] + $handler['vars'];
            }
        }

        return $this->render($default['template'], $default['vars']);
    }
}