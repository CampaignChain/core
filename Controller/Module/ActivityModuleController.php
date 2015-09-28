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

class ActivityModuleController extends Controller
{
    protected $parameters;

    public function setParameters($parameters){
        $this->parameters = $parameters;
    }

    public function newAction(Request $request)
    {
        /*
         * Get context from user's choice.
         */
        $wizard = $this->get('campaignchain.core.activity.wizard');
        $campaign = $wizard->getCampaign();
        $activity = $wizard->getActivity();
        $activity->setEqualsOperation($this->parameters['equals_operation']);
        $locationService = $this->get('campaignchain.core.location');
        $location = $locationService->getLocation($wizard->getLocation());

        /*
         * Configure the Activity's form type.
         */
        $activityFormType = $this->get('campaignchain.core.form.type.activity');
        $activityFormType->setBundleName($this->parameters['activity_bundle_name']);
        $activityFormType->setModuleIdentifier(
            $this->parameters['activity_module_identifier']
        );
        $activityFormType->setCampaign($campaign);

        /*
         * Set the Operation forms for this Activity.
         */
        if(!is_array($this->parameters['operation_forms'])){
            throw new \Exception('No operation forms defined');
        } else {
            foreach($this->parameters['operation_forms'] as $operationForm){
                $operationFormType = new $operationForm(
                    $this->getDoctrine()->getManager(),
                    $this->get('service_container')
                );

                $operationFormType->setLocation($location);

                $operationForms[] = array(
                    'identifier' => $this->parameters['operation_module_identifier'],
                    'form' => $operationFormType
                );
            }
        }
        $activityFormType->setOperationForms($operationForms);

        $form = $this->createForm($activityFormType, $activity);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $activity = $wizard->end();

            // Get the operation module.
            $operationService = $this->get('campaignchain.core.operation');
            $operationModule = $operationService->getOperationModule(
                $this->parameters['operation_bundle_name'],
                $this->parameters['operation_module_identifier']
            );

            if($this->parameters['equals_operation']) {
                // The activity equals the operation. Thus, we create a new operation with the same data.
                $operation = new Operation();
                $operation->setName($activity->getName());
                $operation->setActivity($activity);
                $activity->addOperation($operation);
                $operationModule->addOperation($operation);
                $operation->setOperationModule($operationModule);

                // The Operation creates a Location, i.e. the Operation
                // will be accessible through a URL after publishing.

                // Get the location module.
                $locationModule = $locationService->getLocationModule(
                    $this->parameters['location_bundle_name'],
                    $this->parameters['location_module_identifier']
                );

                $statusLocation = new Location();
                $statusLocation->setLocationModule($locationModule);
                $statusLocation->setParent($activity->getLocation());
                $statusLocation->setName($activity->getName());
                $statusLocation->setStatus(Medium::STATUS_UNPUBLISHED);
                $statusLocation->setOperation($operation);
                $operation->addLocation($statusLocation);

                // Get the status data from request.
                $status = $form->get($this->parameters['operation_module_identifier'])->getData();
                // Link the status with the operation.
                $status->setOperation($operation);
            } else {
                throw new \Exception('Multiple Operations for one Activity not implemented yet.');
            }

            $repository = $this->getDoctrine()->getManager();

            // Make sure that data stays intact by using transactions.
            try {
                $repository->getConnection()->beginTransaction();

                $repository->persist($activity);
                $repository->persist($status);

                // We need the activity ID for storing the hooks. Hence we must flush here.
                $repository->flush();

                $hookService = $this->get('campaignchain.core.hook');
                $activity = $hookService->processHooks($this->parameters['activity_bundle_name'], $this->parameters['activity_module_identifier'], $activity, $form, true);
                $repository->flush();

                $repository->getConnection()->commit();
            } catch (\Exception $e) {
                $repository->getConnection()->rollback();
                throw $e;
            }

            $this->get('session')->getFlashBag()->add(
                'success',
                'Your new activity <a href="'.$this->generateUrl('campaignchain_core_activity_edit', array('id' => $activity->getId())).'">'.$activity->getName().'</a> was created successfully.'
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

        $campaignService = $this->get('campaignchain.core.campaign');
        $campaign = $campaignService->getCampaign($campaign);

        return $this->render(
            'CampaignChainCoreBundle:Operation:new.html.twig',
            array(
                'page_title' => 'New Activity',
                'activity' => $activity,
                'campaign' => $campaign,
                'campaign_module' => $campaign->getCampaignModule(),
                'channel_module' => $wizard->getChannelModule(),
                'channel_module_bundle' => $wizard->getChannelModuleBundle(),
                'location' => $wizard->getLocation(),
                'form' => $form->createView(),
                'form_submit_label' => 'Save',
                'form_cancel_route' => 'campaignchain_core_activities_new'
            ));

    }
}