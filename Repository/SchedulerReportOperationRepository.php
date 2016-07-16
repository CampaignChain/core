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