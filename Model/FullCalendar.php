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
    const FORMAT_CALENDAR_DATE = 'Y-m-d\TH:i:s';

    protected $em;
    protected $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function getEvents(){
        $calendarEvents = array();

        $encoders = array(new JsonEncoder());
        $normalizers = array(new GetSetMethodNormalizer());
        $serializer = new Serializer($normalizers, $encoders);

        // Retrieve all campaigns
        $repository = $this->em->getRepository('CampaignChainCoreBundle:Campaign');
        $campaigns = $repository->findAll();

        if(!count($campaigns)) {
            $docUrl = $this->container->get('templating.helper.assets')
                ->getUrl(
                    'bundles/campaignchaindochtml/user/get_started.html#create-a-campaign',
                    null
                );

            $this->container->get('session')->getFlashBag()->add(
                'warning',
                'No campaigns defined yet. To learn how to create one, please <a href="#" onclick="popupwindow(\''.$docUrl.'\',\'\',900,600)">consult the documentation</a>.'
            );

            return false;
        }

        $campaignEvents = array();

        $datetimeUtil = $this->container->get('campaignchain.core.util.datetime');
        $userNow = $datetimeUtil->getUserNow();

        foreach($campaigns as $campaign){
            $campaignEvent['title'] = $campaign->getName();
            $campaignEvent['start'] = $campaign->getStartDate()->format(self::FORMAT_CALENDAR_DATE);
            $campaignEvent['end'] = $campaign->getEndDate()->format(self::FORMAT_CALENDAR_DATE);
            $campaignEvent['allDay'] = true;
            $campaignEvent['id'] = $campaign->getId();

            if($campaign->getStartDate() < $userNow && $campaign->getEndDate() > $userNow){
                $campaignEvents['ongoing'][] = $campaignEvent;
            } elseif($campaign->getStartDate() < $userNow && $campaign->getEndDate() < $userNow){
                $campaignEvents['done'][] = $campaignEvent;
            } elseif($campaign->getStartDate() > $userNow && $campaign->getEndDate() > $userNow){
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
                'startEditable' => false,
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
            foreach($activities as $activity){
                $activityEvent['title'] = $activity->getName();
                // TODO: Use hook instead.
                $activityEvent['start'] = $activity->getStartDate()->format(self::FORMAT_CALENDAR_DATE);
                $activityEvent['id'] = $activity->getId();
                $activityEvent['type'] = 'activity';
                // Get icons path
                $channelService = $this->container->get('campaignchain.core.channel');
                $icons = $channelService->getIcons($activity->getChannel());
                $activityEvent['icon_path_16px'] = $icons['16px'];

                $activityEvents[] = $activityEvent;
            }

            $calendarEvents['activity']['data'] = $serializer->serialize($activityEvents, 'json');
            $calendarEvents['activity']['options'] = array(
                'className' => 'campaignchain-activity',
                'durationEditable' => false,
            );
        }

        // Retrieve all milestones
        $repository = $this->em->getRepository('CampaignChainCoreBundle:Milestone');
        $milestones = $repository->findAll();

        if(count($milestones)){
            foreach($milestones as $milestone){
                $milestoneEvent['title'] = $milestone->getName();
                $milestoneEvent['start'] = $milestone->getStartDate()->format(self::FORMAT_CALENDAR_DATE);
                $milestoneEvent['id'] = $milestone->getId();
                // Get icons path
                $milestoneService = $this->container->get('campaignchain.core.milestone');
                $icons = $milestoneService->getIcons($milestone);
                $milestoneEvent['icon_path_16px'] = $icons['16px'];

                $milestoneEvents[] = $milestoneEvent;
            }

            $calendarEvents['milestone']['data'] = $serializer->serialize($milestoneEvents, 'json');
            $calendarEvents['milestone']['options'] = array(
                'className' => 'campaignchain-milestone',
                'durationEditable' => false,
            );
        }

        return $calendarEvents;
    }
}