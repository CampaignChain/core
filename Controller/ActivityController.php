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

namespace CampaignChain\CoreBundle\Controller;

use CampaignChain\CoreBundle\Entity\Activity;
use CampaignChain\CoreBundle\Entity\Medium;
use CampaignChain\CoreBundle\EntityService\ActivityService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Response;
use CampaignChain\CoreBundle\Entity\Action;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class ActivityController extends Controller
{
    // TODO: FORMAT_DATEINTERVAL should be moved to DateTimeUtil class.
    const FORMAT_DATEINTERVAL = 'Years: %Y, months: %m, days: %d, hours: %h, minutes: %i, seconds: %s';

    public function indexAction(){
        $activityService = $this->get('campaignchain.core.activity');
        $activities = $activityService->getAllActiveActivities();

        return $this->render(
            'CampaignChainCoreBundle:Activity:index.html.twig',
            array(
                'page_title' => 'Activities Table',
                'activities' => $activities
            ));
    }

    public function newAction(Request $request)
    {
        $formSingle = $this->createFormBuilder()
            ->add('campaign', 'entity', array(
                'label' => 'Campaign',
                'class' => 'CampaignChainCoreBundle:Campaign',
                'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('campaign')
                            ->where('campaign.status != :statusClosed')
                            ->andWhere('campaign.status != :statusBackgroundProcess')
                            ->setParameter('statusClosed', Action::STATUS_CLOSED)
                            ->setParameter('statusBackgroundProcess', Action::STATUS_BACKGROUND_PROCESS)
                            ->orderBy('campaign.startDate', 'ASC');
                    },
                'choice_label' => 'name',
                'placeholder' => 'Select a Campaign',
                'empty_data' => null,
                'attr' => array(
                    'selected' => $this->get('session')->get('campaignchain.campaign'),
                )
            ));

        $formSingle
        // TODO: Only show channels that actually have min. 1 related activity module.
            ->add('location', 'entity', array(
                'label' => 'Location',
                'class' => 'CampaignChainCoreBundle:Location',
                'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('location')
                                    ->where('location.status != :status_unpublished AND location.status != :status_inactive')
                                    ->andWhere('location.parent IS NULL')
                                    ->andWhere( // Skip Locations that don't provide Activities.
                                        'EXISTS ('
                                            .'SELECT channelModule.id FROM '
                                            .'CampaignChain\CoreBundle\Entity\Channel channel, '
                                            .'CampaignChain\CoreBundle\Entity\ChannelModule channelModule '
                                            .'WHERE '
                                            .'location.channel = channel.id AND '
                                            .'channel.channelModule = channelModule.id AND '
                                            .'SIZE(channelModule.activityModules) != 0'
                                        .')'


                                    )
                                    ->orderBy('location.name', 'ASC')
                                    ->setParameter('status_unpublished', Medium::STATUS_UNPUBLISHED)
                                    ->setParameter('status_inactive', Medium::STATUS_INACTIVE);
                    },
                'choice_label' => 'name',
                'placeholder' => 'Select a Location',
                'empty_data' => null,
                'attr' => array(
                    'show_image' => true,
                )
            ))
            ->add('activity', 'text', array(
                'label' => 'Activity',
                'mapped' => false,
                'attr' => array('placeholder' => 'Select an activity')
            ));

        $formSingle = $formSingle->getForm();
        $formSingle->handleRequest($request);

        // See if we also have multi-location Activities.
        $repository = $this->getDoctrine()->getRepository('CampaignChainCoreBundle:ActivityModule');
        $qb = $repository->createQueryBuilder('am')
            ->leftJoin('am.channelModules', 'cm')
            ->where('cm IS NULL')
            ->orderBy('am.displayName', 'ASC')
            ->getQuery();
        $multiLocationActivities = $qb->getResult();

        if($multiLocationActivities) {

            $formMultiple = $this->createFormBuilder();

            $formMultiple
                ->add('campaign_multi', 'entity', array(
                    'label' => 'Campaign',
                    'class' => 'CampaignChainCoreBundle:Campaign',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('campaign')
                            ->where('campaign.status != :statusClosed')
                            ->andWhere('campaign.status != :statusBackgroundProcess')
                            ->setParameter('statusClosed', Action::STATUS_CLOSED)
                            ->setParameter('statusBackgroundProcess', Action::STATUS_BACKGROUND_PROCESS)
                            ->orderBy('campaign.startDate', 'ASC');
                    },
                    'choice_label' => 'name',
                    'placeholder' => 'Select a Campaign',
                    'empty_data' => null,
                    'attr' => array(
                        'selected' => $this->get('session')->get('campaignchain.campaign'),
                    )
                ));

            $formMultiple
                ->add('activity_multi', 'entity', array(
                    'label' => 'Activity',
                    'class' => 'CampaignChainCoreBundle:ActivityModule',
                    'choices' => $multiLocationActivities,
                    'choice_label' => 'displayName',
                    'placeholder' => 'Select an Activity',
                    'empty_data' => null,
                    'attr' => array(
                        'selected' => $this->get('session')->get('campaignchain.campaign'),
                    )
                ));

            $formMultiple = $formMultiple->getForm();
            $formMultiple->handleRequest($request);
        }

        if ($formSingle->isValid()) {
            $campaign = $formSingle->getData()['campaign'];
            $location = $formSingle->get('location')->getData();
            $activityModuleId = $formSingle->get('activity')->getData();
        } elseif($multiLocationActivities && $formMultiple->isValid()) {
            $campaign = $formMultiple->getData()['campaign_multi'];
            $location = null;
            $activityModuleId = $formMultiple->get('activity_multi')->getData();
        } elseif(
            $formSingle->isSubmitted() ||
            ($multiLocationActivities && $formMultiple->isSubmitted())
        ) {
            throw new \Exception('None of the forms is valid.');
        }

        if($formSingle->isValid() || ($multiLocationActivities && $formMultiple->isValid())) {
            // Get the activity module's activity.
            $activityService = $this->get('campaignchain.core.activity');
            $activityModule = $activityService->getActivityModule($activityModuleId);

            // Start wizard
            $wizard = $this->get('campaignchain.core.activity.wizard');
            $wizard->start(
                $campaign,
                $activityModule,
                $location
            );

            $routes = $activityModule->getRoutes();
            $routeNew = (string) $routes['new'];

            // TODO: Investigate why we have to cast it as a string to make it work?
            return $this->redirect(
                $this->generateUrl($routeNew)
            );
        }

        $formMultipleView = null;
        if($multiLocationActivities){
            $formMultipleView = $formMultiple->createView();
        }

        return $this->render(
            'CampaignChainCoreBundle:Activity:new.html.twig',
            array(
                'page_title' => 'Create New Activity',
                'form_single' => $formSingle->createView(),
                'form_multiple' => $formMultipleView,
                'form_submit_label' => 'Next',
                'dependent_select_parent' => 'form_location',
                'dependent_select_child' => 'form_activity',
                'dependent_select_route' => 'campaignchain_core_location_list_activities_api',
            ));
    }

    public function editAction(Request $request, $id)
    {
        $activityService = $this->get('campaignchain.core.activity');

        $routeType = 'edit';

        // If closed activity, then redirect to read view.
        $activity = $activityService->getActivity($id);
        if($activity->getStatus() == Action::STATUS_CLOSED){
            $routeType = 'read';
        }

        $activityModule = $activityService->getActivityModuleByActivity($id);
        $routes = $activityModule->getRoutes();

        return $this->redirect(
            $this->generateUrl(
                $routes[$routeType],
                array(
                    'id' => $id,
                )
            )
        );
    }

    public function readAction(Request $request, $id)
    {
        return $this->editAction($request, $id);
    }

    public function editModalAction(Request $request, $id)
    {
        /** @var ActivityService $activityService */
        $activityService = $this->get('campaignchain.core.activity');
        /** @var Activity $activity */
        $activity = $activityService->getActivity($id);
        $routes = $activity->getActivityModule()->getRoutes();

        if($activity->getStatus() == Action::STATUS_CLOSED){
            return $this->redirect(
                $this->generateUrl(
                    $routes['read_modal'],
                    array(
                        'id' => $id,
                    )
                )
            );
        } else {
            return $this->redirect(
                $this->generateUrl(
                    $routes['edit_modal'],
                    array(
                        'id' => $id,
                    )
                )
            );
        }
    }

    public function readModalAction(Request $request, $id)
    {
        return $this->editModalAction($request, $id);
    }

    public function removeAction(Request $request, $id)
    {
        $activityService = $this->get('campaignchain.core.activity');

        try{
            $activityService->removeActivity($id);
            $this->addFlash('success', 'Activity deleted successfully');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Activity could not be deleted');
        }
        return $this->redirectToRoute('campaignchain_core_activities');
    }

    /**
     * Move an Activity to a new start date.
     *
     * @ApiDoc(
     *  section = "Core",
     *  views = { "private" },
     *  requirements={
     *      {
     *          "name"="id",
     *          "description" = "Campaign ID",
     *          "requirement"="\d+"
     *      },
     *     {
     *          "name"="start_date",
     *          "description" = "Start date in ISO8601 format",
     *          "requirement"="/(\d{4})-(\d{2})-(\d{2})T(\d{2})\:(\d{2})\:(\d{2})[+-](\d{2})\:(\d{2})/"
     *      }
     *  }
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function moveApiAction(Request $request)
    {
        $serializer = $this->get('campaignchain.core.serializer.default');

        $responseData = array();

        $id = $request->request->get('id');
        $newDue = new \DateTime($request->request->get('start_date'));

        $activityService = $this->get('campaignchain.core.activity');
        $activity = $activityService->getActivity($id);

        $responseData['id'] = $activity->getId();

        $oldActivityStartDate = clone $activity->getStartDate();
        $responseData['old_start_date'] = $oldActivityStartDate->format(\DateTime::ISO8601);
//        $oldActivityEndDate = clone $activity->getEndDate();
        // TODO: Check whether start = end date.
        $responseData['old_end_date'] = $responseData['old_start_date'];


        // Calculate time difference.
        $interval = $activity->getStartDate()->diff($newDue);
        $responseData['interval']['object'] = json_encode($interval, true);
        $responseData['interval']['string'] = $interval->format(self::FORMAT_DATEINTERVAL);

        // TODO: Also move operations.
        $activity = $activityService->moveActivity($activity, $interval);

        // Set new dates.
        $responseData['new_start_date'] = $activity->getStartDate()->format(\DateTime::ISO8601);
        // TODO: Check whether start = end date.
        $responseData['new_end_date'] = $responseData['new_start_date'];

        $em = $this->getDoctrine()->getManager();
        $em->persist($activity);
        $em->flush();

        return new Response($serializer->serialize($responseData, 'json'));
    }
}