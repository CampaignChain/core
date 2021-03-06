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
use CampaignChain\CoreBundle\Entity\Job;
use CampaignChain\CoreBundle\Entity\Location;
use DateTime;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class ActivityRepository extends EntityRepository
{
    /**
     * @param DateTime $periodStart
     * @param DateTime $periodEnd
     * @return Activity[]
     */
    public function getScheduledActivity(DateTime $periodStart, DateTime $periodEnd)
    {
        return $this->createQueryBuilder('a')
            ->select('a')
            ->join('a.campaign','c')
            // We only want activities with status "open":
            ->where('a.status != :status')
            // The campaign within which the activity resides must also have the status "open":
            ->andWhere('c.status != :status')
            // We don't want activities to already be processed by another scheduler, that's why we check all Job entities:
            ->andWhere(
                "NOT EXISTS (SELECT j.id FROM CampaignChain\CoreBundle\Entity\Job j WHERE j.status = :jobStatus AND a.id = j.actionId AND j.actionType = :actionType)"
            )
            // Get all activities where the start date is within the execution period
            // or get all activities where the start date is outside the period, but the end date - if not NULL - is within the period.
            ->andWhere(
                '(a.startDate IS NOT NULL AND a.startDate >= :periodStart AND a.startDate <= :periodEnd)'.
                ' OR '.
                '(a.endDate IS NOT NULL AND a.startDate <= :periodStart AND a.endDate >= :periodStart AND a.endDate <= :periodEnd)'.
                ' OR '.
                '('.
                '(a.intervalStartDate IS NULL OR a.intervalStartDate <= :periodEnd)'.
                ' AND '.
                '(a.intervalEndDate IS NULL OR a.intervalEndDate <= :periodEnd)'.
                ' AND '.
                'a.intervalNextRun IS NOT NULL AND a.intervalNextRun >= :periodStart AND a.intervalNextRun <= :periodEnd'.
                ')'
            )
            ->setParameter('status', ACTION::STATUS_CLOSED)
            ->setParameter('jobStatus', JOB::STATUS_OPEN)
            ->setParameter('actionType', Action::TYPE_ACTIVITY)
            ->setParameter('periodEnd', $periodEnd->format('Y-m-d H:i:s'))
            ->setParameter('periodStart', $periodStart->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult();
    }

    public function getClosestScheduledActivity(Activity $activity, $interval)
    {
        $startInterval = clone $activity->getStartDate();
        $startInterval->modify($interval);

        $qb = $this->createQueryBuilder('a');
        $qb->select('a');

        if(substr($interval, 0, 1) == "-"){
            $qb->where('a.startDate < :startDate')
                ->andWhere('a.startDate > :startInterval');
        } else {
            $qb->where('a.startDate > :startDate')
                ->andWhere('a.startDate < :startInterval');
        }

        $qb->setParameter('startInterval', $startInterval)
            ->andWhere('a.location = :location')
            ->andWhere('a.campaign = :campaign')
            ->andWhere('a.status != :closed')
            ->setParameter('startDate', $activity->getStartDate())
            ->setParameter('location', $activity->getLocation())
            ->setParameter('campaign', $activity->getCampaign())
            ->setParameter('closed', Action::STATUS_CLOSED)
            ->orderBy('a.startDate', 'DESC')
            ->setMaxResults(1);

        $query = $qb->getQuery();
        return $query->getOneOrNullResult();
    }
}