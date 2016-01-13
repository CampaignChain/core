<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="CampaignChain\CoreBundle\Repository\SchedulerReportOperationRepository")
 */
class SchedulerReportOperation extends SchedulerReport
{
    /**
     * @ORM\ManyToOne(targetEntity="Operation", inversedBy="scheduledReports")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $operation;

    /**
     * Set operation
     *
     * @param \CampaignChain\CoreBundle\Entity\Operation $operation
     * @return Operation
     */
    public function setOperation(\CampaignChain\CoreBundle\Entity\Operation $operation = null)
    {
        $this->operation = $operation;

        $this->setStartDate($this->operation->getStartDate());

        return $this;
    }

    /**
     * Get operation
     *
     * @return \CampaignChain\CoreBundle\Entity\Operation
     */
    public function getOperation()
    {
        return $this->operation;
    }
}
