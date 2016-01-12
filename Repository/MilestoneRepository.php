<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Repository;

use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\Job;
use CampaignChain\CoreBundle\Entity\Milestone;
use DateTime;
use Doctrine\ORM\EntityRepository;

class MilestoneRepository extends EntityRepository
{

    /**
     * @param DateTime $periodStart
     * @param DateTime $periodEnd
     * @return Milestone[]
     */
    public function getScheduledMilestone(DateTime $periodStart, DateTime $periodEnd)
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
}