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

use Doctrine\ORM\Mapping as ORM;

/**
 * A report operation collects data from a Channel, e.g. the number of retweets
 * of a Twitter status.
 *
 * @ORM\Entity
 * @ORM\Table(name="campaignchain_scheduler_report")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap( {
 *      "operation" = "SchedulerReportOperation",
 *      "activity" = "SchedulerReportActivity",
 *      "milestone" = "SchedulerReportMilestone",
 *      "location" = "SchedulerReportLocation"
 * } )
 * @ORM\HasLifecycleCallbacks
 *
 * TODO: Ensure that minimum and only 1 Action is specified each for the start
 *       and end date.
 */
abstract class SchedulerReport extends Meta
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * The date when the report operation is supposed to start.
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $startDate;

    /**
     * The date when the report operation was run the last time.
     *
     * TODO: Cannot be earlier than startDate.
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $prevRun;

    /**
     * A string defining the interval range as a relative date format with a
     * value in the future. For example, if the report operation is supposed
     * to run every hour, the interval would be "1 hour".
     *
     * Relative date formats are defined here:
     * http://php.net/manual/en/datetime.formats.relative.php
     *
     * TODO: Make sure that provided interval has a future value (not pointing
     * to the past).
     *
     * TODO: If no interval provided, then endDate must be defined.
     *
     * TODO: Initial interval date cannot be after endDate.
     *
     * @ORM\Column(name="`interval`", type="string", length=100, nullable=true)
     */
    protected $interval;

    /**
     * The date when the report operation will be run the next time. It will be
     * increased by the scheduler.
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $nextRun;

    /**
     * @ORM\ManyToOne(targetEntity="Campaign")
     * @ORM\JoinColumn(name="end_campaign_id", referencedColumnName="id", nullable=true)
     */
    protected $endCampaign;

    /**
     * @ORM\ManyToOne(targetEntity="Milestone")
     * @ORM\JoinColumn(name="end_milestone_id", referencedColumnName="id", nullable=true)
     */
    protected $endMilestone;

    /**
     * @ORM\ManyToOne(targetEntity="Activity")
     * @ORM\JoinColumn(name="end_activity_id", referencedColumnName="id", nullable=true)
     */
    protected $endActivity;

    /**
     * The report operation will not run past this point in time.
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $endDate;

    /**
     * A string defining the a relative time that the end date of the defined
     * end Action should be extended to. For example, Facebook stats for a
     * status updated could be delayed by up to 1 week.
     *
     * Note that the prolongation will not be applied to the next run date, but
     * to the end date of the specified Action's end date.
     *
     * Relative date formats are defined here:
     * http://php.net/manual/en/datetime.formats.relative.php
     *
     * TODO: Make sure that provided interval has a future value (not pointing
     * to the past).
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $prolongation;

    /**
     * A string defining the interval range of the scheduler running with in the
     * prolonged period of time.
     *
     * TODO: Make sure that the provided interval is within the prolonged period.
     *
     * TODO: Same additional todos as for $interval.
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     *
     * @see $interval
     */
    protected $prolongationInterval;

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
     * Set prevRun.
     *
     * @param \DateTime $prevRun
     *
     * @return SchedulerReport
     */
    public function setPrevRun($prevRun)
    {
        $this->prevRun = $prevRun;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function persistNextRun()
    {
        if ($this->getInterval() != null) {
            $interval = \DateInterval::createFromDateString($this->getInterval());
            $nextRun = clone $this->getStartDate();
            $this->setNextRun($nextRun->add($interval));
        }
    }

    /**
     * Get interval.
     *
     * @return string
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Set interval.
     *
     * @param string $interval
     *
     * @return SchedulerReport
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;

        return $this;
    }

    /**
     * Get startDate.
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set startDate.
     *
     * @param \DateTime $startDate
     *
     * @return $this
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get nextRun.
     *
     * @return \DateTime
     */
    public function getNextRun()
    {
        return $this->nextRun;
    }

    /**
     * Set nextRun.
     *
     * @param \DateTime $nextRun
     *
     * @return $this
     */
    public function setNextRun($nextRun)
    {
        $this->nextRun = $nextRun;

        return $this;
    }

    public function setEndAction($endAction)
    {
        $class = get_class($endAction);

        if (strpos($class, 'CoreBundle\Entity\Activity') !== false) {
            $this->setEndActivity($endAction);
            $this->setEndDate($endAction->getEndDate());
        } elseif (strpos($class, 'CoreBundle\Entity\Milestone') !== false) {
            $this->setEndMilestone($endAction);
            $this->setEndDate($endAction->getEndDate());
        } elseif (strpos($class, 'CoreBundle\Entity\Campaign') !== false) {
            $this->setEndCampaign($endAction);
            $this->setEndDate($endAction->getEndDate());
        } elseif (strpos($class, 'CoreBundle\Entity\Location') !== false) {
        } else {
            throw new \Exception(
                "End Action is instance of '".$class."'. Must be either instance of "
                .'Campaign, Milestone or Activity.'
            );
        }
    }

    public function getEndAction()
    {
        if ($this->endActivity != null) {
            return $this->endActivity->getEndDate();
        } elseif ($this->endMilestone != null) {
            return $this->endMilestone->getEndDate();
        } elseif ($this->endCampaign != null) {
            return $this->endCampaign->getEndDate();
        }

        throw new \Exception('No end Action defined.');
    }

    /**
     * Get endDate.
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set endDate.
     *
     * @param \DateTime $endDate
     *
     * @return $this
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get prolongation.
     *
     * @return string
     */
    public function getProlongation()
    {
        return $this->prolongation;
    }

    /**
     * Set prolongation.
     *
     * @param string $prolongation
     *
     * @return SchedulerReport
     */
    public function setProlongation($prolongation)
    {
        $this->prolongation = $prolongation;

        return $this;
    }

    /**
     * Get prolongationInterval.
     *
     * @return string
     */
    public function getProlongationInterval()
    {
        return $this->prolongationInterval;
    }

    /**
     * Set prolongationInterval.
     *
     * @param string $prolongationInterval
     *
     * @return SchedulerReport
     */
    public function setProlongationInterval($prolongationInterval)
    {
        $this->prolongationInterval = $prolongationInterval;

        return $this;
    }

    /**
     * Get endCampaign.
     *
     * @return Campaign
     */
    protected function getEndCampaign()
    {
        return $this->endCampaign;
    }

    /**
     * Set endCampaign.
     *
     * @param Campaign $endCampaign
     *
     * @return SchedulerReport
     */
    protected function setEndCampaign(Campaign $endCampaign = null)
    {
        $this->endCampaign = $endCampaign;

        return $this;
    }

    /**
     * Get endMilestone.
     *
     * @return Milestone
     */
    protected function getEndMilestone()
    {
        return $this->endMilestone;
    }

    /**
     * Set endMilestone.
     *
     * @param Milestone $endMilestone
     *
     * @return SchedulerReport
     */
    protected function setEndMilestone(Milestone $endMilestone = null)
    {
        $this->endMilestone = $endMilestone;

        return $this;
    }

    /**
     * Get endActivity.
     *
     * @return Activity
     */
    protected function getEndActivity()
    {
        return $this->endActivity;
    }

    /**
     * Set endActivity.
     *
     * @param Activity $endActivity
     *
     * @return SchedulerReport
     */
    protected function setEndActivity(Activity $endActivity = null)
    {
        $this->endActivity = $endActivity;

        return $this;
    }
}
