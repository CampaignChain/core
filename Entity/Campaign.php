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
 * @ORM\Entity(repositoryClass="CampaignChain\CoreBundle\Repository\CampaignRepository")
 * @ORM\Table(name="campaignchain_campaign")
 */
class Campaign extends Action implements AssignableInterface
{
    const RELATIVE_START_DATE = '2012-01-01 00:00:00';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="Campaign", mappedBy="parent")
     */
    protected $children;

    /**
     * @ORM\ManyToOne(targetEntity="Campaign", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     */
    protected $parent;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\OneToMany(targetEntity="Activity", mappedBy="campaign", cascade={"persist"})
     */
    protected $activities;

    /**
     * @ORM\OneToMany(targetEntity="ReportAnalyticsActivityFact", mappedBy="campaign")
     */
    protected $activityFacts;

    /**
     * @ORM\OneToMany(targetEntity="Milestone", mappedBy="campaign", cascade={"persist"})
     */
    protected $milestones;

    /**
     * @ORM\ManyToOne(targetEntity="CampaignModule", inversedBy="campaigns")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $campaignModule;

    /**
     * @ORM\Column(type="string", length=40)
     */
    protected $timezone = 'UTC';

    /**
     * @ORM\Column(type="boolean")
     */
    protected $hasRelativeDates = false;

    /**
     * @ORM\ManyToOne(targetEntity="CampaignChain\CoreBundle\Entity\User", inversedBy="campaigns")
     * @ORM\JoinColumn(name="assignee", referencedColumnName="id")
     */
    protected $assignee;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->activities = new ArrayCollection();
        $this->activityFacts = new ArrayCollection();
        $this->milestones = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    public function __toString()
    {
        return (string) $this->getId();
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
     * Add children
     *
     * @param \CampaignChain\CoreBundle\Entity\Campaign $children
     * @return Campaign
     */
    public function addChild(Campaign $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \CampaignChain\CoreBundle\Entity\Campaign $children
     */
    public function removeChild(Campaign $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent
     *
     * @param \CampaignChain\CoreBundle\Entity\Campaign $parent
     * @return Campaign
     */
    public function setParent(Campaign $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \CampaignChain\CoreBundle\Entity\Campaign
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Campaign
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Add activity.
     *
     * @param Activity $activity
     *
     * @return Campaign
     */
    public function addActivity(Activity $activity)
    {
        $this->activities->add($activity);

        return $this;
    }

    /**
     * Remove activity.
     *
     * @param Activity $activity
     */
    public function removeActivity(Activity $activity)
    {
        $this->activities->removeElement($activity);
    }

    /**
     * Get activities.
     *
     * @return ArrayCollection
     */
    public function getActivities()
    {
        return $this->activities;
    }

    /**
     * Add activityFact.
     *
     * @param ReportAnalyticsActivityFact $activityFact
     *
     * @return Campaign
     */
    public function addActivityFact(ReportAnalyticsActivityFact $activityFact)
    {
        $this->activityFacts->add($activityFact);

        return $this;
    }

    /**
     * Remove activityFact.
     *
     * @param ReportAnalyticsActivityFact $activityFact
     */
    public function removeActivityFact(ReportAnalyticsActivityFact $activityFact)
    {
        $this->activityFacts->removeElement($activityFact);
    }

    /**
     * Get activityFacts.
     *
     * @return ArrayCollection
     */
    public function getActivityFacts()
    {
        return $this->activityFacts;
    }

    /**
     * Add milestone.
     *
     * @param Milestone $milestone
     *
     * @return Campaign
     */
    public function addMilestone(Milestone $milestone)
    {
        $this->milestones->add($milestone);

        return $this;
    }

    /**
     * Remove milestone.
     *
     * @param Milestone $milestone
     */
    public function removeMilestone(Milestone $milestone)
    {
        $this->milestones->removeElement($milestone);
    }

    /**
     * Get milestones.
     *
     * @return ArrayCollection
     */
    public function getMilestones()
    {
        return $this->milestones;
    }

    /**
     * Get timezone.
     *
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * Set timezone.
     *
     * @param string $timezone
     *
     * @return Campaign
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Convenience method that masquerades getCampaignModule().
     *
     * @return CampaignModule
     */
    public function getModule()
    {
        return $this->getCampaignModule();
    }

    /**
     * Get campaignModule.
     *
     * @return CampaignModule
     */
    public function getCampaignModule()
    {
        return $this->campaignModule;
    }

    /**
     * Set campaignModule.
     *
     * @param CampaignModule $campaignModule
     *
     * @return Campaign
     */
    public function setCampaignModule(CampaignModule $campaignModule = null)
    {
        $this->campaignModule = $campaignModule;

        return $this;
    }

    /**
     * Get hasRelativeDates.
     *
     * @return bool
     */
    public function getHasRelativeDates()
    {
        return $this->hasRelativeDates;
    }

    /**
     * Set hasRelativeDates.
     *
     * @param bool $hasRelativeDates
     *
     * @return Campaign
     */
    public function setHasRelativeDates($hasRelativeDates)
    {
        $this->hasRelativeDates = $hasRelativeDates;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAssignee()
    {
        return $this->assignee;
    }

    /**
     * @param mixed $assignee
     */
    public function setAssignee($assignee)
    {
        $this->assignee = $assignee;
    }

//    public function __clone()
//    {
//        if ($this->id) {
//            $this->id = null;
//
//            $activities = $this->getActivities();
//            foreach($activities as $activity){
//                $clonedActivity = clone $activity;
//                $this->activities->add($clonedActivity);
//            }
//
//            $milestones = $this->getMilestones();
//            foreach($milestones as $milestone){
//                $clonedMilestone = clone $milestone;
//                $this->milestones->add($clonedMilestone);
//            }
//        }
//    }
}
