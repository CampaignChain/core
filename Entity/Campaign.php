<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="campaignchain_campaign")
 */
class Campaign extends Action
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="Activity", mappedBy="campaign", cascade={"persist"})
     */
    protected $activities;

    /**
     * @ORM\OneToMany(targetEntity="ReportAnalyticsActivityFact", mappedBy="campaign")
     */
    protected $activityFacts;

    /**
     * @ORM\OneToMany(targetEntity="ReportAnalyticsChannelFact", mappedBy="campaign")
     */
    protected $channelFacts;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function __toString()
    {
        return (string) $this->getId();
    }

    /**
     * Add activities
     *
     * @param \CampaignChain\CoreBundle\Entity\Activity $activities
     * @return Campaign
     */
    public function addActivity(\CampaignChain\CoreBundle\Entity\Activity $activities)
    {
        $this->activities[] = $activities;

        return $this;
    }

    /**
     * Remove activities
     *
     * @param \CampaignChain\CoreBundle\Entity\Activity $activities
     */
    public function removeActivity(\CampaignChain\CoreBundle\Entity\Activity $activities)
    {
        $this->activities->removeElement($activities);
    }

    /**
     * Get activities
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getActivities()
    {
        return $this->activities;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->activities = new \Doctrine\Common\Collections\ArrayCollection();
        $this->activityFacts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->channelFacts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->milestones = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add activityFacts
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityFact $activityFacts
     * @return Campaign
     */
    public function addActivityFact(\CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityFact $activityFacts)
    {
        $this->activityFacts[] = $activityFacts;

        return $this;
    }

    /**
     * Remove activityFacts
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityFact $activityFacts
     */
    public function removeActivityFact(\CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityFact $activityFacts)
    {
        $this->activityFacts->removeElement($activityFacts);
    }

    /**
     * Get activityFacts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getActivityFacts()
    {
        return $this->activityFacts;
    }

    /**
     * Add channelFacts
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportAnalyticsChannelFact $channelFacts
     * @return Campaign
     */
    public function addChannelFact(\CampaignChain\CoreBundle\Entity\ReportAnalyticsChannelFact $channelFacts)
    {
        $this->channelFacts[] = $channelFacts;

        return $this;
    }

    /**
     * Remove channelFacts
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportAnalyticsChannelFact $channelFacts
     */
    public function removeChannelFact(\CampaignChain\CoreBundle\Entity\ReportAnalyticsChannelFact $channelFacts)
    {
        $this->channelFacts->removeElement($channelFacts);
    }

    /**
     * Get channelFacts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getChannelFacts()
    {
        return $this->channelFacts;
    }

    /**
     * Add milestones
     *
     * @param \CampaignChain\CoreBundle\Entity\Milestone $milestones
     * @return Campaign
     */
    public function addMilestone(\CampaignChain\CoreBundle\Entity\Milestone $milestones)
    {
        $this->milestones[] = $milestones;

        return $this;
    }

    /**
     * Remove milestones
     *
     * @param \CampaignChain\CoreBundle\Entity\Milestone $milestones
     */
    public function removeMilestone(\CampaignChain\CoreBundle\Entity\Milestone $milestones)
    {
        $this->milestones->removeElement($milestones);
    }

    /**
     * Get milestones
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMilestones()
    {
        return $this->milestones;
    }

    /**
     * Set timezone
     *
     * @param string $timezone
     * @return Campaign
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Get timezone
     *
     * @return string 
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * Set campaignModule
     *
     * @param \CampaignChain\CoreBundle\Entity\CampaignModule $campaignModule
     * @return Campaign
     */
    public function setCampaignModule(\CampaignChain\CoreBundle\Entity\CampaignModule $campaignModule = null)
    {
        $this->campaignModule = $campaignModule;

        return $this;
    }

    /**
     * Get campaignModule
     *
     * @return \CampaignChain\CoreBundle\Entity\CampaignModule
     */
    public function getCampaignModule()
    {
        return $this->campaignModule;
    }

    /**
     * Convenience method that masquerades getCampaignModule()
     *
     * @return \CampaignChain\CoreBundle\Entity\CampaignModule
     */
    public function getModule()
    {
        return $this->campaignModule;
    }

    /**
     * Set hasRelativeDates
     *
     * @param boolean $hasRelativeDates
     * @return Activity
     */
    public function setHasRelativeDates($hasRelativeDates)
    {
        $this->hasRelativeDates = $hasRelativeDates;

        return $this;
    }

    /**
     * Get hasRelativeDates
     *
     * @return boolean
     */
    public function getHasRelativeDates()
    {
        return $this->hasRelativeDates;
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
