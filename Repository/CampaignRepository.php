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
use CampaignChain\CoreBundle\Entity\Campaign;
use CampaignChain\CoreBundle\Entity\Job;
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

    /**
     * @param $moduleIdentifier
     * @return array
     */
    public function getCampaignsByModule($moduleIdentifier) {

        return $this->createQueryBuilder('campaign')
            ->select('campaign')
            ->leftJoin('campaign.campaignModule', 'module', 'WITH', 'module.identifier = :moduleIdentifier')
            ->setParameter('moduleIdentifier', $moduleIdentifier)
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
                '('.
                '(c.intervalStartDate IS NULL OR c.intervalStartDate <= :periodEnd)'.
                ' AND '.
                '(c.intervalEndDate IS NULL OR c.intervalEndDate <= :periodEnd)'.
                ' AND '.
                'c.intervalNextRun IS NOT NULL AND c.intervalNextRun >= :periodStart AND c.intervalNextRun <= :periodEnd'.
                ')'
            )
            ->setParameter('status', ACTION::STATUS_CLOSED)
            ->setParameter('jobStatus', JOB::STATUS_OPEN)
            ->setParameter('actionType', Action::TYPE_CAMPAIGN)
            ->setParameter('periodEnd', $periodEnd->format('Y-m-d H:i:s'))
            ->setParameter('periodStart', $periodStart->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult();
    }
}