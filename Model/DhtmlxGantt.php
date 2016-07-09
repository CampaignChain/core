<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Model;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class DhtmlxGantt
{
    const FORMAT_TIMELINE_DATE = 'd-m-Y H:i P'; // Javascript Date() format: 'D M d Y H:i:s \G\M\T P (T)' // Original: 'd-m-Y H:i'

    protected $em;
    protected $container;
    protected $serializer;

    public function __construct(EntityManager $em, ContainerInterface $container, SerializerInterface $serializer)
    {
        $this->em = $em;
        $this->container = $container;
        $this->serializer = $serializer;
    }

    /**
     * Get JSON encoded data for DHTMLXGantt chart.
     *
     * @return string|\Symfony\Component\Serializer\Encoder\scalar
     */
    public function getTasks($bundleName = null, $moduleIdentifier = null, $campaignId = null){
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

        if(isset($campaignId)){
            $qb->andWhere('c.id = :campaignId')
                ->setParameter('campaignId', $campaignId);
        }

        $qb->orderBy('c.startDate', 'DESC');

        $query = $qb->getQuery();
        $campaigns = $query->getResult();

        //$ganttTask = $campaigns;

        // Create GANTT data
        $ganttDataId = 1;
        $ganttLinkId = 1;

        foreach($campaigns as $campaign){
            $campaign_data['text'] = $campaign->getName();
            // Define the trigger hook's identifier.
            if($campaign->getTriggerHook()){
                //$campaign_data['trigger_identifier'] = str_replace('-', '_', $campaign->getTriggerHook()->getIdentifier());
                // Retrieve the start and end date from the trigger hook.
                $hookService = $this->container->get($campaign->getTriggerHook()->getServices()['entity']);
                $hook = $hookService->getHook($campaign);
                $campaign_data['start_date'] = $hook->getStartDate()->format(self::FORMAT_TIMELINE_DATE);
                if($hook->getEndDate()){
                    $campaign_data['end_date'] = $hook->getEndDate()->format(self::FORMAT_TIMELINE_DATE);
                } else {
                    $campaign_data['end_date'] = $campaign_data['start_date'];
                }
                // Provide the hook's start and end date form field names.
                //$campaign_data['start_date_identifier'] = $hookService->getStartDateIdentifier();
                //$campaign_data['end_date_identifier'] = $hookService->getEndDateIdentifier();
            }
            $campaignId = $campaign->getId();
//            $campaign_data['id'] = (string) $ganttDataId;
            $campaign_data['id'] = (string) $campaign->getId().'_campaign';
            $campaign_data['campaignchain_id'] = (string) $campaign->getId();
            $campaign_data['type'] = 'campaign';
            $campaign_data['route_edit_api'] = $campaign->getCampaignModule()->getRoutes()['edit_api'];
            $campaignService = $this->container->get('campaignchain.core.campaign');
            $campaign_data['tpl_teaser'] = $campaignService->tplTeaser(
                $campaign->getCampaignModule(),
                array(
                    'only_icon' => true,
                    'size' => 24,
                )
            );
            $ganttDataId++;
//            $campaign['entity'] = array(
//                'id' => $campaignId,
//                'name' => 'Campaign',
//            );
            $ganttCampaignData[] = $campaign_data;

            // Get activities of campaign
            $qb = $this->em->createQueryBuilder();
            $qb->select('a')
                ->from('CampaignChain\CoreBundle\Entity\Activity', 'a')
                ->where('a.campaign = :campaignId')
                ->andWhere('a.parent IS NULL')
                ->setParameter('campaignId', $campaignId)
                ->orderBy('a.startDate', 'ASC');
            $query = $qb->getQuery();
            $activities = $query->getResult();

            if(is_array($activities) && count($activities)){
                foreach($activities as $activity){
                    $activity_data['text'] = $activity->getName();

                    // Define the trigger hook's identifier.
                    //$activity_data['trigger_identifier'] = str_replace('-', '_', $activity->getTriggerHook()->getIdentifier());
                    // Retrieve the start and end date from the trigger hook.
                    $hookService = $this->container->get($activity->getTriggerHook()->getServices()['entity']);
                    $hook = $hookService->getHook($activity);
                    $activity_data['start_date'] = $hook->getStartDate()->format(self::FORMAT_TIMELINE_DATE);
                    if($hook->getEndDate()){
                        $activity_data['end_date'] = $hook->getEndDate()->format(self::FORMAT_TIMELINE_DATE);
                    } else {
                        $activity_data['end_date'] = $activity_data['start_date'];
                    }
                    // Provide the hook's start and end date form field names.
                    //$activity_data['start_date_identifier'] = $hookService->getStartDateIdentifier();
                    //$activity_data['end_date_identifier'] = $hookService->getEndDateIdentifier();

//                    $activity_data['start_date'] = $activity_data['end_date'] = $activity->getDue()->format(self::FORMAT_TIMELINE_DATE);
//                    $activity_data['id'] = $ganttDataId;
                    $activity_data['id'] = (string) $activity->getId().'_activity';
                    $ganttDataId++;
                    $activity_data['campaignchain_id'] = (string) $activity->getId();
                    $activity_data['parent'] = $campaign_data['id'];
                    $activity_data['type'] = 'activity';
//                    $activity_data['form_root_name'] = $activity->getActivityModule()->getFormRootName();
                    $activity_data['route_edit_api'] = $activity->getActivityModule()->getRoutes()['edit_api'];
                    // Get activity icons path
                    $activityService = $this->container->get('campaignchain.core.activity');
                    $activity_data['tpl_teaser'] = $activityService->tplTeaser($activity, array('only_icon' => true));

                    $ganttActivityData[] = $activity_data;
                    $ganttActivityLinks[] = array(
                        'id' => $ganttLinkId,
                        'source' => $campaign_data['id'],
                        'target' => $activity_data['id'],
                        'type' => '1',
                    );
                    $ganttLinkId++;

                    // TODO: Retrieve GANTT data for operations of an activity if the activity does not equal the operation.
                }
            }

            // Get milestones of campaign
            $qb = $this->em->createQueryBuilder();
            $qb->select('m')
                ->from('CampaignChain\CoreBundle\Entity\Milestone', 'm')
                ->where('m.campaign = :campaignId')
                ->setParameter('campaignId', $campaignId)
                ->orderBy('m.startDate', 'ASC');
            $query = $qb->getQuery();
            $milestones = $query->getResult();

            if(is_array($milestones) && count($milestones)){
                foreach($milestones as $milestone){
                    $milestone_data['text'] = $milestone->getName();
                    // Define the trigger hook's identifier.
                    //$milestone_data['trigger_identifier'] = str_replace('-', '_', $milestone->getTriggerHook()->getIdentifier());
                    // Retrieve the start and end date from the trigger hook.
                    $hookService = $this->container->get($milestone->getTriggerHook()->getServices()['entity']);
                    $hook = $hookService->getHook($milestone);
                    $milestone_data['start_date'] = $hook->getStartDate()->format(self::FORMAT_TIMELINE_DATE);
                    if($hook->getEndDate()){
                        $milestone_data['end_date'] = $hook->getEndDate()->format(self::FORMAT_TIMELINE_DATE);
                    } else {
                        $milestone_data['end_date'] = $milestone_data['start_date'];
                    }
                    // Provide the hook's start and end date form field names.
                    //$milestone_data['start_date_identifier'] = $hookService->getStartDateIdentifier();
                    //$milestone_data['end_date_identifier'] = $hookService->getEndDateIdentifier();
//                    $milestone_data['id'] = $ganttDataId;
                    $milestone_data['id'] = (string) $milestone->getId().'_milestone';
                    $ganttDataId++;
                    $milestone_data['campaignchain_id'] = (string) $milestone->getId();
                    $milestone_data['parent'] = $campaign_data['id'];
                    $milestone_data['type'] = 'milestone';
                    $milestone_data['route_edit_api'] = $milestone->getMilestoneModule()->getRoutes()['edit_api'];
                    // Get icons path
                    $milestoneService = $this->container->get('campaignchain.core.milestone');
                    $icons = $milestoneService->getIcons($milestone);
                    $milestone_data['icon_path_16px'] = $icons['16px'];
                    $milestone_data['icon_path_24px'] = $icons['24px'];

                    $ganttMilestoneData[] = $milestone_data;
                    $ganttMilestoneLinks[] = array(
                        'id' => $ganttLinkId,
                        'source' => $campaign_data['id'],
                        'target' => $milestone_data['id'],
                        'type' => '1',
                    );
                    $ganttLinkId++;
                }
            }
        }

        $ganttTasks = array();

        if(isset($ganttCampaignData) && is_array($ganttCampaignData)){
            $hasMilestones = false;
            $hasActivities = false;

            $ganttTasks['data'] = $ganttCampaignData;
            if(isset($ganttMilestoneData) && is_array($ganttMilestoneData)){
                $ganttTasks['data'] = array_merge($ganttTasks['data'], $ganttMilestoneData);
                $hasMilestones = true;
            }
            if(isset($ganttActivityData) && is_array($ganttActivityData)){
                $ganttTasks['data'] = array_merge($ganttTasks['data'], $ganttActivityData);
                $hasActivities = true;
            }
            if($hasMilestones && $hasActivities){
                $ganttTasks['links'] = array_merge($ganttActivityLinks, $ganttMilestoneLinks);
            } elseif($hasMilestones){
                $ganttTasks['links'] = $ganttMilestoneLinks;
            } elseif($hasActivities){
                $ganttTasks['links'] = $ganttActivityLinks;
            }
        } else {
            $this->container->get('session')->getFlashBag()->add(
                'warning',
                'No campaigns available yet. Please create one.'
            );

            header('Location: '.
                $this->container->get('router')->generate('campaignchain_core_campaign_new')
            );
            exit;
        }

        return $this->serializer->serialize($ganttTasks, 'json');
    }
}