<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Model;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FullCalendar
{
    const FORMAT_CALENDAR_DATE = \DateTime::ISO8601; //'Y-m-d\TH:i:s';

    protected $em;
    protected $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function getEvents($bundleName = null, $moduleIdentifier = null, $campaignId = null){
        $calendarEvents = array();

        $encoders = array(new JsonEncoder());
        $normalizers = array(new GetSetMethodNormalizer());
        $serializer = new Serializer($normalizers, $encoders);

        $qb = $this->em->createQueryBuilder();
        $qb->select('c')
            ->from('CampaignChain\CoreBundle\Entity\Campaign', 'c');

        if(isset($bundleName) && isset($moduleIdentifier)){
            $qb->from('CampaignChain\CoreBundle\Entity\Module', 'm')
                ->from('CampaignChain\CoreBundle\Entity\Bundle', 'b')
                ->where('b.name = :bundleName')
                ->andWhere('m.identifier = :moduleIdentifier')
                ->andWhere('m.id = c.campaignModule')
                ->setParameter('bundleName', $bundleName)
                ->setParameter('moduleIdentifier', $moduleIdentifier);
        }

        if(isset($campaign)){
            $qb->andWhere('c.id = :campaignId')
                ->setParameter('campaignId', $campaignId);
        }

        $qb->orderBy('c.startDate', 'DESC');

        $query = $qb->getQuery();
        $campaigns = $query->getResult();

        if(!count($campaigns)) {
            $this->container->get('session')->getFlashBag()->add(
                'warning',
                'No campaigns available yet. Please create one.'
            );

            header('Location: '.
                $this->container->get('router')->generate('campaignchain_core_campaign_new')
            );
            exit;
        }

        $datetimeUtil = $this->container->get('campaignchain.core.util.datetime');
        $userNow = $datetimeUtil->getUserNow();

        $campaignEvents = array();

        foreach($campaigns as $campaign){
            $campaignEvent['title'] = $campaign->getName();

            // Retrieve the start and end date from the trigger hook.
            $hookService = $this->container->get($campaign->getTriggerHook()->getServices()['entity']);
            $hook = $hookService->getHook($campaign);
            $campaignEvent['start'] = $hook->getStartDate()->format(self::FORMAT_CALENDAR_DATE);
            if($hook->getEndDate()){
                $campaignEvent['end'] = $hook->getEndDate()->format(self::FORMAT_CALENDAR_DATE);
            } else {
                $campaignEvent['end'] = $campaignEvent['start'];
            }
            // Provide the hook's start and end date form field names.
            //$campaignEvent['start_date_identifier'] = $hookService->getStartDateIdentifier();
            //$campaignEvent['end_date_identifier'] = $hookService->getEndDateIdentifier();

            $campaignEvent['allDay'] = true;
            $campaignEvent['type'] = 'campaign';
            $campaignEvent['campaignchain_id'] = $campaign->getId();
            $campaignEvent['route_edit_api'] = $campaign->getCampaignModule()->getRoutes()['edit_api'];
            //$campaignEvent['trigger_identifier'] = str_replace('-', '_', $campaign->getTriggerHook()->getIdentifier());

            if($hook->getStartDate() < $userNow && $hook->getEndDate() > $userNow){
                $campaignEvents['ongoing'][] = $campaignEvent;
            } elseif($hook->getStartDate() < $userNow && $hook->getEndDate() < $userNow){
                $campaignEvents['done'][] = $campaignEvent;
            } elseif($hook->getStartDate() > $userNow && $hook->getEndDate() > $userNow){
                $campaignEvents['upcoming'][] = $campaignEvent;
            }
        }

        if(isset($campaignEvents['ongoing'])){
            $calendarEvents['campaign_ongoing']['data'] = $serializer->serialize($campaignEvents['ongoing'], 'json');
            $calendarEvents['campaign_ongoing']['options'] = array(
                'className' => 'campaignchain-calendar-ongoing campaignchain-calendar-campaign',
                'startEditable' => false,
            );
        }
        if(isset($campaignEvents['done'])){
            $calendarEvents['campaign_done']['data'] = $serializer->serialize($campaignEvents['done'], 'json');
            $calendarEvents['campaign_done']['options'] = array(
                'className' => 'campaignchain-calendar-done campaignchain-calendar-campaign',
                'editable' => false,
            );
        }
        if(isset($campaignEvents['upcoming'])){
            $calendarEvents['campaign_upcoming']['data'] = $serializer->serialize($campaignEvents['upcoming'], 'json');
            $calendarEvents['campaign_upcoming']['options'] = array(
                'className' => 'campaignchain-calendar-upcoming campaignchain-calendar-campaign',
                'startEditable' => false,
            );
        }

        // Retrieve all activities
        $repository = $this->em->getRepository('CampaignChainCoreBundle:Activity');
        $activities = $repository->findAll();

        if(count($activities)){

            $activityEvents = array();

            foreach($activities as $activity){
                $activityEvent['title'] = $activity->getName();

                // Retrieve the start and end date from the trigger hook.
                $hookService = $this->container->get($activity->getTriggerHook()->getServices()['entity']);
                $hook = $hookService->getHook($activity);
                $activityEvent['start'] = $hook->getStartDate()->format(self::FORMAT_CALENDAR_DATE);
                // Provide the hook's start and end date form field names.
                //$activityEvent['start_date_identifier'] = $hookService->getStartDateIdentifier();
                //$activityEvent['end_date_identifier'] = $hookService->getEndDateIdentifier();

                $activityEvent['campaignchain_id'] = $activity->getId();
                $activityEvent['type'] = 'activity';
                $activityEvent['route_edit_api'] = $activity->getActivityModule()->getRoutes()['edit_api'];
                //$activityEvent['trigger_identifier'] = str_replace('-', '_', $activity->getTriggerHook()->getIdentifier());
                // Get activity icons path
                $activityService = $this->container->get('campaignchain.core.activity');
                $activityEvent['location_tpl'] = $activityService->tplTeaser($activity, array('only_icon' => true));

                if($hook->getStartDate() < $userNow){
                    $activityEvents['done'][] = $activityEvent;
                } else {
                    $activityEvents['upcoming'][] = $activityEvent;
                }
            }

            if(isset($activityEvents['done'])){
                $calendarEvents['activity_done']['data'] = $serializer->serialize($activityEvents['done'], 'json');
                $calendarEvents['activity_done']['options'] = array(
                    'className' => 'campaignchain-activity campaignchain-activity-done',
                    'editable' => false,
                );
            }
            if(isset($activityEvents['upcoming'])){
                $calendarEvents['activity_upcoming']['data'] = $serializer->serialize($activityEvents['upcoming'], 'json');
                $calendarEvents['activity_upcoming']['options'] = array(
                    'className' => 'campaignchain-activity',
                    'durationEditable' => false,
                );
            }
        }

        // Retrieve all milestones
        $repository = $this->em->getRepository('CampaignChainCoreBundle:Milestone');
        $milestones = $repository->findAll();

        if(count($milestones)){

            $milestoneEvents = array();

            foreach($milestones as $milestone){
                $milestoneEvent['title'] = $milestone->getName();

                // Retrieve the start and end date from the trigger hook.
                $hookService = $this->container->get($milestone->getTriggerHook()->getServices()['entity']);
                $hook = $hookService->getHook($milestone);
                $milestoneEvent['start'] = $hook->getStartDate()->format(self::FORMAT_CALENDAR_DATE);
                // Provide the hook's start and end date form field names.
                //$milestoneEvent['start_date_identifier'] = $hookService->getStartDateIdentifier();
                //$milestoneEvent['end_date_identifier'] = $hookService->getEndDateIdentifier();

                $milestoneEvent['type'] = 'milestone';
                $milestoneEvent['campaignchain_id'] = $milestone->getId();
                $milestoneEvent['route_edit_api'] = $milestone->getMilestoneModule()->getRoutes()['edit_api'];
                //$milestoneEvent['trigger_identifier'] = str_replace('-', '_', $milestone->getTriggerHook()->getIdentifier());
                // Get icons path
                $milestoneService = $this->container->get('campaignchain.core.milestone');
                $icons = $milestoneService->getIcons($milestone);
                $milestoneEvent['icon_path_16px'] = $icons['16px'];

                if($hook->getStartDate() < $userNow){
                    $milestoneEvents['done'][] = $milestoneEvent;
                } else {
                    $milestoneEvents['upcoming'][] = $milestoneEvent;
                }
            }

            if(isset($milestoneEvents['done'])){
                $calendarEvents['milestone_done']['data'] = $serializer->serialize($milestoneEvents['done'], 'json');
                $calendarEvents['milestone_done']['options'] = array(
                    'className' => 'campaignchain-milestone campaignchain-milestone-done',
                    'editable' => false,
                );
            }
            if(isset($milestoneEvents['upcoming'])){
                $calendarEvents['milestone_upcoming']['data'] = $serializer->serialize($milestoneEvents['upcoming'], 'json');
                $calendarEvents['milestone_upcoming']['options'] = array(
                    'className' => 'campaignchain-milestone',
                    'durationEditable' => false,
                );
            }
        }

        return $calendarEvents;
    }
}