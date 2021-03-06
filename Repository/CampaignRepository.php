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

namespace CampaignChain\CoreBundle\Repository;

use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\Activity;
use CampaignChain\CoreBundle\Entity\Campaign;
use CampaignChain\CoreBundle\Entity\Job;
use CampaignChain\CoreBundle\Entity\Milestone;
use DateTime;
use Doctrine\ORM\EntityRepository;

/**
 * Class CampaignRepository
 * @package CampaignChain\CoreBundle\Repository
 */
class CampaignRepository extends EntityRepository
{

    /**
     * get a list of all campaigns
     *
     * @return array
     */
    public function getCampaigns() {

        return $this->createQueryBuilder('campaign')
            ->where('campaign.status != :statusBackgroundProcess')
            ->setParameter('statusBackgroundProcess', Action::STATUS_BACKGROUND_PROCESS)
            ->orderBy('campaign.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getCampaignTemplates() {

        return $this->createQueryBuilder('campaign')
            ->where('campaign.startDate = :relativeStartDate')
            ->andWhere('campaign.interval IS NULL')
            ->andWhere('campaign.status != :statusClosed')
            ->andWhere('campaign.status != :statusBackgroundProcess')
            ->setParameter('relativeStartDate', Campaign::RELATIVE_START_DATE)
            ->setParameter('statusClosed', Action::STATUS_CLOSED)
            ->setParameter('statusBackgroundProcess', Action::STATUS_BACKGROUND_PROCESS)
            ->orderBy('campaign.name', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $moduleIdentifier
     * @param $bundleName
     * @return array
     */
    public function getCampaignsByModule($moduleIdentifier, $bundleName) {

        return $this->createQueryBuilder('campaign')
            ->select('campaign')
            ->Join('campaign.campaignModule', 'module', 'WITH', 'module.identifier = :moduleIdentifier')
            ->Join('module.bundle', 'bundle', 'WITH', 'bundle.name = :bundleName')
            ->setParameter('moduleIdentifier', $moduleIdentifier)
            ->setParameter('bundleName', $bundleName)
            ->orderBy('campaign.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param DateTime $periodStart
     * @param DateTime $periodEnd
     * @return Campaign[]
     */
    public function getScheduledCampaign(DateTime $periodStart, DateTime $periodEnd)
    {
        return $this->createQueryBuilder('c')
            ->select('c')
            // We only want campaigns with status "open":
            ->where('c.status != :status')
            // We don't want campaigns to already be processed by another scheduler, that's why we check all Job entities:
            ->andWhere(
                "NOT EXISTS (SELECT j.id FROM CampaignChain\CoreBundle\Entity\Job j WHERE j.status = :jobStatus AND c.id = j.actionId AND j.actionType = :actionType)"
            )
            // Get all campaigns where the start date is within the execution period
            // or get all campaigns where the start date is outside the period, but the end date - if not NULL - is within the period.
            ->andWhere(
                '(c.startDate IS NOT NULL AND c.startDate >= :periodStart AND c.startDate <= :periodEnd)'.
                ' OR '.
                '(c.endDate IS NOT NULL AND c.startDate <= :periodStart AND c.endDate >= :periodStart AND c.endDate <= :periodEnd)'.
                ' OR '.
                '(c.intervalStartDate IS NOT NULL AND c.intervalStartDate >= :periodStart AND c.intervalStartDate <= :periodEnd)'.
                ' OR '.
                '(c.intervalEndDate IS NOT NULL AND c.intervalEndDate >= :periodStart AND c.intervalEndDate <= :periodEnd)'.
                ' OR '.
                '(c.intervalNextRun IS NOT NULL AND c.intervalNextRun >= :periodStart AND c.intervalNextRun <= :periodEnd)'
            )
            ->setParameter('status', ACTION::STATUS_CLOSED)
            ->setParameter('jobStatus', JOB::STATUS_OPEN)
            ->setParameter('actionType', Action::TYPE_CAMPAIGN)
            ->setParameter('periodEnd', $periodEnd->format('Y-m-d H:i:s'))
            ->setParameter('periodStart', $periodStart->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult();
    }

    /**
     * Get the first Action, i.e. Activity or Milestone.
     *
     * @param Campaign $campaign
     * @return Activity|Milestone|null
     */
    public function getFirstAction(Campaign $campaign)
    {
        return $this->getEdgeAction($campaign, 'first');
    }

    /**
     * Get the last Action, i.e. Activity or Milestone.
     *
     * @param Campaign $campaign
     * @return Activity|Milestone|null
     */
    public function getLastAction(Campaign $campaign)
    {
        $lastAction = $this->getEdgeAction($campaign, 'last');

        // If there is a last action, make sure it does not equal the first action.
        if($lastAction){
            $firstAction = $this->getFirstAction($campaign);

            if($lastAction->getId() == $firstAction->getId()){
                return null;
            } else {
                return $lastAction;
            }
        }
    }

    /**
     * Retrieve the first or last Action, i.e. an Activity or Milestone.
     *
     * @param Campaign $campaign
     * @param string $position first|last
     * @return Activity|Milestone|null
     */
    private function getEdgeAction(Campaign $campaign, $position)
    {
        if($position != 'first' && $position != 'last'){
            throw new \Exception('Position must be either "first" or "last"');
        }
        if($position == 'first'){
            $order = 'ASC';
        } else {
            $order = 'DESC';
        }

        // Get first Activity
        /** @var Activity $activity */
        $activity = $this->createQueryBuilder('c')
            ->select('a')
            ->from('CampaignChain\CoreBundle\Entity\Activity', 'a')
            ->where('a.campaign = :campaign')
            ->setParameter('campaign', $campaign)
            ->orderBy('a.startDate', $order)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // Get first Milestone
        /** @var Milestone $milestone */
        $milestone = $this->createQueryBuilder('c')
            ->select('m')
            ->from('CampaignChain\CoreBundle\Entity\Milestone', 'm')
            ->where('m.campaign = :campaign')
            ->setParameter('campaign', $campaign)
            ->orderBy('m.startDate', $order)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // Does Activity or Milestone exist?
        if($activity == null && $milestone == null){
            return null;
        }
        if($milestone == null){
            return $activity;
        }
        if($activity == null){
            return $milestone;
        }

        // Does Activity or Milestone come first/last?
        if($position == 'first') {
            if ($activity->getStartDate() < $milestone->getStartDate()) {
                return $activity;
            } else {
                return $milestone;
            }
        } else {
            if ($activity->getStartDate() > $milestone->getStartDate()) {
                return $activity;
            } else {
                return $milestone;
            }
        }
    }
}