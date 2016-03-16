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
use CampaignChain\CoreBundle\Entity\Scheduler;
use Doctrine\ORM\EntityRepository;

class JobRepository extends EntityRepository
{
    /**
     * @param Scheduler $scheduler
     * @return Job[]
     */
    public function getOpenJobsForScheduler(Scheduler $scheduler)
    {
        return $this->createQueryBuilder('j')
            ->select('j')
            ->where('j.scheduler = :scheduler')
            ->andWhere('j.status = :status')
            ->setParameter('scheduler', $scheduler)
            ->setParameter('status', Job::STATUS_OPEN)
            ->orderBy('j.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Scheduler $scheduler
     * @return Job[]
     */
    public function getProcessedJobsForScheduler(Scheduler $scheduler)
    {
        return $this->createQueryBuilder('j')
            ->select('j')
            ->where('j.scheduler = :scheduler')
            ->andWhere('j.status != :status')
            ->setParameter('scheduler', $scheduler)
            ->setParameter('status', Job::STATUS_OPEN)
            ->orderBy('j.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}