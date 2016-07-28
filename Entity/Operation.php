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

namespace CampaignChain\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="CampaignChain\CoreBundle\Repository\OperationRepository")
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
     * @ORM\ManyToOne(targetEntity="OperationModule", inversedBy="operations")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $operationModule;

    /**
     * This column has to be nullable=true, because the module installer will
     * add the Activity after it already persisted the Operation.
     *
     * @ORM\ManyToOne(targetEntity="Activity", inversedBy="operations")
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
    protected $facts;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->locations = new ArrayCollection();
        $this->outboundCTAs = new ArrayCollection();
        $this->scheduledReports = new ArrayCollection();
        $this->facts = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get operationModule.
     *
     * @return OperationModule
     */
    public function getOperationModule()
    {
        return $this->operationModule;
    }

    /**
     * Set operationModule.
     *
     * @param OperationModule $operationModule
     *
     * @return Operation
     */
    public function setOperationModule(OperationModule $operationModule = null)
    {
        $this->operationModule = $operationModule;

        return $this;
    }

    /**
     * Convenience method that masquerades getOperationModule().
     *
     * @return OperationModule
     */
    public function getModule()
    {
        return $this->operationModule;
    }

    /**
     * If the Activity equals the Operation, then set the status of the Activity to the same value.
     *
     * @param string $status
     * @param bool   $calledFromActivity
     *
     * @return Operation
     */
    public function setStatus($status, $calledFromActivity = false)
    {
        parent::setStatus($status);

        // Change the Activity as well only if this method has not been called by an Activity instance to avoid recursion.
        if (!$calledFromActivity && $this->getActivity() && $this->getActivity()->getEqualsOperation()) {
            $this->getActivity()->setStatus($this->status, true);
        }

        return $this;
    }

    /**
     * Get activity.
     *
     * @return Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * Set activity.
     *
     * @param Activity $activity
     *
     * @return Operation
     */
    public function setActivity(Activity $activity = null)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Add location.
     *
     * @param Location $location
     *
     * @return Operation
     */
    public function addLocation(Location $location)
    {
        $this->locations->add($location);

        return $this;
    }

    /**
     * Remove location.
     *
     * @param Location $location
     */
    public function removeLocation(Location $location)
    {
        $this->locations->removeElement($location);
    }

    /**
     * Get locations.
     *
     * @return ArrayCollection
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * Add outboundCTA.
     *
     * @param CTA $outboundCTA
     *
     * @return Operation
     */
    public function addOutboundCTA(CTA $outboundCTA)
    {
        $this->outboundCTAs->add($outboundCTA);

        return $this;
    }

    /**
     * Remove outboundCTA.
     *
     * @param CTA $outboundCTA
     */
    public function removeOutboundCTA(CTA $outboundCTA)
    {
        $this->outboundCTAs->removeElement($outboundCTA);
    }

    /**
     * Get outboundCTAs.
     *
     * @return ArrayCollection
     */
    public function getOutboundCTAs()
    {
        return $this->outboundCTAs;
    }

    /**
     * Add scheduledReport.
     *
     * @param SchedulerReportOperation $schedulerReportOperation
     *
     * @return Operation
     *
     * @internal param SchedulerReportOperation $scheduledReport
     */
    public function addScheduledReport(SchedulerReportOperation $schedulerReportOperation)
    {
        $this->scheduledReports->add($schedulerReportOperation);

        return $this;
    }

    /**
     * Remove scheduledReport.
     *
     * @param SchedulerReportOperation $schedulerReportOperation
     *
     * @internal param SchedulerReportOperation $schedulerReportOperdation
     */
    public function removeScheduledReport(SchedulerReportOperation $schedulerReportOperation)
    {
        $this->scheduledReports->removeElement($schedulerReportOperation);
    }

    /**
     * Get scheduledReports.
     *
     * @return ArrayCollection
     */
    public function getScheduledReports()
    {
        return $this->scheduledReports;
    }

    /**
     * Add fact.
     *
     * @param ReportAnalyticsActivityFact $fact
     *
     * @return Operation
     */
    public function addFact(ReportAnalyticsActivityFact $fact)
    {
        $this->facts->add($fact);

        return $this;
    }

    /**
     * Remove fact.
     *
     * @param ReportAnalyticsActivityFact $fact
     */
    public function removeFact(ReportAnalyticsActivityFact $fact)
    {
        $this->facts->removeElement($fact);
    }

    /**
     * Get fact.
     *
     * @return ArrayCollection
     */
    public function getFacts()
    {
        return $this->facts;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }
}
