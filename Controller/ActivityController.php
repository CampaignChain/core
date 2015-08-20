<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Controller;

use CampaignChain\CoreBundle\Entity\Medium;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use CampaignChain\CoreBundle\Entity\Activity;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use CampaignChain\CoreBundle\Entity\Action;

class ActivityController extends Controller
{
    // TODO: FORMAT_DATEINTERVAL should be moved to DateTimeUtil class.
    const FORMAT_DATEINTERVAL = 'Years: %Y, months: %m, days: %d, hours: %h, minutes: %i, seconds: %s';

    public function indexAction(){
        $activityService = $this->get('campaignchain.core.activity');
        $activities = $activityService->getAllActivities();

        return $this->render(
            'CampaignChainCoreBundle:Activity:index.html.twig',
            array(
                'page_title' => 'Activities',
                'activities' => $activities
            ));
    }

    public function newAction(Request $request)
    {
        $form = $this->createFormBuilder()
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
                'property' => 'name',
                'empty_value' => 'Select a Campaign',
                'empty_data' => null,
                'attr' => array(
                    'selected' => $this->get('session')->get('campaignchain.campaign'),
                )
            ))
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
                'property' => 'name',
                'empty_value' => 'Select a Location',
                'empty_data' => null,
                'attr' => array(
                    'show_image' => true,
                )
            ))
            ->add('activity', 'text', array(
                'label' => 'Activity',
                'mapped' => false,
                'attr' => array('placeholder' => 'Select an activity')
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $campaign = $form->getData()['campaign'];

            $activity = new Activity();

            // Get the activity module's activity.
            $activityService = $this->get('campaignchain.core.activity');
            $activityModule = $activityService->getActivityModule($form->get('activity')->getData());

            // Start wizard
            $wizard = $this->get('campaignchain.core.activity.wizard');
            $wizard->start(
                $campaign,
                $form->get('location')->getData(),
                $activity,
                $activityModule
            );

            $routes = $activityModule->getRoutes();
            $routeNew = (string) $routes['new'];

            // TODO: Investigate why we have to cast it as a string to make it work?
            return $this->redirect(
                $this->generateUrl($routeNew)
            );
        }

        return $this->render(
            'CampaignChainCoreBundle:Base:new_dependent_select.html.twig',
            array(
                'page_title' => 'Create New Activity',
                'form' => $form->createView(),
                'form_submit_label' => 'Next',
                'dependent_select_parent' => 'location',
                'dependent_select_child' => 'activity',
                'dependent_select_route' => 'campaignchain_core_location_list_activities_api',
            ));
    }

    public function editAction(Request $request, $id)
    {
        $activityService = $this->get('campaignchain.core.activity');

        $routeType = 'edit';

        // If closed activity, then redirect to read view.
        $activity = $activityService->getActivity($id);
        if($activity->getStatus() == 'closed'){
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
        // TODO: If an activity is done, it cannot be edited.
        $activityService = $this->get('campaignchain.core.activity');
        $activityModule = $activityService->getActivityModuleByActivity($id);
        $routes = $activityModule->getRoutes();

        return $this->redirect(
            $this->generateUrl(
                $routes['edit_modal'],
                array(
                    'id' => $id,
                )
            )
        );
    }

    public function moveApiAction(Request $request)
    {
        $encoders = array(new JsonEncoder());
        $normalizers = array(new GetSetMethodNormalizer());
        $serializer = new Serializer($normalizers, $encoders);

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

        $repository = $this->getDoctrine()->getManager();
        $repository->persist($activity);
        $repository->flush();

        $response = new Response($serializer->serialize($responseData, 'json'));
        return $response->setStatusCode(Response::HTTP_OK);
    }
}