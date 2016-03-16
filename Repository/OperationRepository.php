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
use CampaignChain\CoreBundle\Entity\Operation;
use DateTime;
use Doctrine\ORM\EntityRepository;

class OperationRepository extends EntityRepository
{

    /**
     * @param DateTime $periodStart
     * @param DateTime $periodEnd
     * @return Operation[]
     */
    public function getScheduledOperation(DateTime $periodStart, DateTime $periodEnd)
    {
        return $this->createQueryBuilder('o')
            ->select('o')
            ->join('o.activity', 'a')
            ->join('a.campaign', 'c')
            // We only want operations with status "open":
            ->where('o.status != :status')
            // The parent activity of the operation should also have the status "open":
            ->andWhere('a.status != :status')
            // The campaign within which the operation and parent activity reside must also have the status "open":
            ->andWhere('c.status != :status')
            // We don't want operations to already be processed by another scheduler, that's why we check all Job entities:
            ->andWhere(
                "NOT EXISTS (SELECT j.id FROM CampaignChain\CoreBundle\Entity\Job j WHERE j.status = :jobStatus AND o.id = j.actionId AND j.actionType = :actionType)"
            )
            // Get all operations where the start date is within the execution period
            // or get all operations where the start date is outside the period, but the end date - if not NULL - is within the period.
            ->andWhere(
                '(o.startDate IS NOT NULL AND o.startDate >= :periodStart AND o.startDate <= :periodEnd)'.
                ' OR '.
                '(o.endDate IS NOT NULL AND o.startDate <= :periodStart AND o.endDate >= :periodStart AND o.endDate <= :periodEnd)'.
                ' OR '.
                '('.
                '(o.intervalStartDate IS NULL OR o.intervalStartDate <= :periodEnd)'.
                ' AND '.
                '(o.intervalEndDate IS NULL OR o.intervalEndDate <= :periodEnd)'.
                ' AND '.
                'o.intervalNextRun IS NOT NULL AND o.intervalNextRun >= :periodStart AND o.intervalNextRun <= :periodEnd'.
                ')'
            )
            ->setParameter('status', ACTION::STATUS_CLOSED)
            ->setParameter('jobStatus', JOB::STATUS_OPEN)
            ->setParameter('actionType', Action::TYPE_OPERATION)
            ->setParameter('periodStart', $periodStart->format('Y-m-d H:i:s'))
            ->setParameter('periodEnd', $periodEnd->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult();
    }
}