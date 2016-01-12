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

use CampaignChain\CoreBundle\Entity\Job;
use CampaignChain\CoreBundle\Entity\SchedulerReportOperation;
use \Doctrine\ORM\EntityRepository;

class SchedulerReportOperationRepository extends EntityRepository
{
    /**
     * @param \DateTime $periodStart
     * @param \DateTime $periodEnd
     * @return SchedulerReportOperation[]
     */
    public function getScheduledReportJobsForSchedulerCommand(\DateTime $periodStart, \DateTime $periodEnd)
    {
        return $this->createQueryBuilder('sr')
            ->select('sr')
            ->where('sr.nextRun >= :periodStart AND sr.nextRun <= :periodEnd')
            // We don't want reports to already be processed by another scheduler, that's why we check all Job entities:
            ->andWhere(
                "NOT EXISTS (SELECT j.id FROM CampaignChain\CoreBundle\Entity\Job j WHERE j.status = :jobStatus AND sr.operation = j.actionId AND j.actionType = :reportType)"
            )
            ->setParameter('reportType', 'operation')
            ->setParameter('jobStatus', JOB::STATUS_OPEN)
            ->setParameter('periodEnd', $periodEnd->format('Y-m-d H:i:s'))
            ->setParameter('periodStart', $periodStart->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult();
    }
}