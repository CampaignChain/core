<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="campaignchain_operation")
 */
class Operation extends Action
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="OperationModule")
     */
    protected $operationModule;

    /**
     * This column has to be nullable=true, because the module installer will
     * add the Activity after it already persisted the Operation.
     *
     * @ORM\ManyToOne(targetEntity="Activity")
     * @ORM\JoinColumn(name="activity_id", referencedColumnName="id", nullable=true)
     */
    protected $activity;

    /**
     * @ORM\OneToMany(targetEntity="Location", mappedBy="operation", cascade={"persist"})
     */
    protected $locations;

    /**
     * @ORM\OneToMany(targetEntity="CTA", mappedBy="operation")
     */
    protected $outboundCTAs;

    /**
     * @ORM\OneToMany(targetEntity="SchedulerReportOperation", mappedBy="operation")
     */
    protected $scheduledReports;

    /**
     * @ORM\OneToMany(targetEntity="ReportAnalyticsActivityFact", mappedBy="operation")
     */
    protected $fact;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set operationModule
     *
     * @param \CampaignChain\CoreBundle\Entity\OperationModule $operationModule
     * @return Operation
     */
    public function setOperationModule(\CampaignChain\CoreBundle\Entity\OperationModule $operationModule = null)
    {
        $this->operationModule = $operationModule;

        return $this;
    }

    /**
     * Get operationModule
     *
     * @return \CampaignChain\CoreBundle\Entity\OperationModule
     */
    public function getOperationModule()
    {
        return $this->operationModule;
    }

    /**
     * Convenience method that masquerades getOperationModule()
     *
     * @return \CampaignChain\CoreBundle\Entity\OperationModule
     */
    public function getModule()
    {
        return $this->operationModule;
    }

    /**
     * Set activity
     *
     * @param \CampaignChain\CoreBundle\Entity\Activity $activity
     * @return Operation
     */
    public function setActivity(\CampaignChain\CoreBundle\Entity\Activity $activity = null)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity
     *
     * @return \CampaignChain\CoreBundle\Entity\Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * If the Activity equals the Operation, then set the status of the Activity to the same value.
     *
     * @param string $status
     * @param bool $calledFromActivity
     * @return $this|BaseTask
     */
    public function setStatus($status, $calledFromActivity = false)
    {
        parent::setStatus($status);

        // Change the Activity as well only if this method has not been called by an Activity instance to avoid recursion.
        if(!$calledFromActivity && $this->getActivity() && $this->getActivity()->getEqualsOperation()){
            $this->getActivity()->setStatus($this->status, true);
        }

        return $this;
    }

    /**
     * Add locations
     *
     * @param \CampaignChain\CoreBundle\Entity\Location $locations
     * @return Operation
     */
    public function addLocation(\CampaignChain\CoreBundle\Entity\Location $locations)
    {
        $this->locations[] = $locations;

        return $this;
    }

    /**
     * Remove locations
     *
     * @param \CampaignChain\CoreBundle\Entity\Location $locations
     */
    public function removeLocation(\CampaignChain\CoreBundle\Entity\Location $locations)
    {
        $this->locations->removeElement($locations);
    }

    /**
     * Get locations
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->locations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->outboundCTAs = new \Doctrine\Common\Collections\ArrayCollection();
        $this->scheduledReports = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add outboundCTAs
     *
     * @param \CampaignChain\CoreBundle\Entity\CTA $outboundCTAs
     * @return Operation
     */
    public function addOutboundCTA(\CampaignChain\CoreBundle\Entity\CTA $outboundCTAs)
    {
        $this->outboundCTAs[] = $outboundCTAs;

        return $this;
    }

    /**
     * Remove outboundCTAs
     *
     * @param \CampaignChain\CoreBundle\Entity\CTA $outboundCTAs
     */
    public function removeOutboundCTA(\CampaignChain\CoreBundle\Entity\CTA $outboundCTAs)
    {
        $this->outboundCTAs->removeElement($outboundCTAs);
    }

    /**
     * Get outboundCTAs
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOutboundCTAs()
    {
        return $this->outboundCTAs;
    }

    /**
     * Add scheduledReports
     *
     * @param \CampaignChain\CoreBundle\Entity\SchedulerReportOperation $scheduledReports
     * @return OperationModule
     */
    public function addScheduledReport(\CampaignChain\CoreBundle\Entity\SchedulerReportOperation $scheduledReports)
    {
        $this->scheduledReports[] = $scheduledReports;

        return $this;
    }

    /**
     * Remove scheduledReports
     *
     * @param \CampaignChain\CoreBundle\Entity\SchedulerReportOperation $scheduledReports
     */
    public function removeScheduledReport(\CampaignChain\CoreBundle\Entity\SchedulerReportOperation $scheduledReports)
    {
        $this->scheduledReports->removeElement($scheduledReports);
    }

    /**
     * Get scheduledReports
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getScheduledReports()
    {
        return $this->scheduledReports;
    }

    /**
     * Add fact
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityFact $fact
     * @return Operation
     */
    public function addFact(\CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityFact $fact)
    {
        $this->fact[] = $fact;

        return $this;
    }

    /**
     * Remove fact
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityFact $fact
     */
    public function removeFact(\CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityFact $fact)
    {
        $this->fact->removeElement($fact);
    }

    /**
     * Get fact
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFact()
    {
        return $this->fact;
    }
}
