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
use CampaignChain\CoreBundle\Entity\Module;

/**
 * @ORM\Entity
 */
class ActivityModule extends Module
{
    /**
     * @ORM\OneToMany(targetEntity="Activity", mappedBy="activityModule")
     */
    protected $activities;

    /**
     * @ORM\ManyToMany(targetEntity="ChannelModule", inversedBy="activityModules")
     * @ORM\JoinTable(name="campaignchain_module_activity_channel",
     *   joinColumns={@ORM\JoinColumn(name="activitymodule_id", referencedColumnName="id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="channelmodule_id", referencedColumnName="id")}
     *   )
     **/
    protected $channelModules;

    /**
     * Add activities
     *
     * @param \CampaignChain\CoreBundle\Entity\Activity $activities
     * @return ActivityModule
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
     * Add channels
     *
     * @param \CampaignChain\CoreBundle\Entity\ChannelModule $channels
     * @return ActivityModule
     */
    public function addChannelModule(\CampaignChain\CoreBundle\Entity\ChannelModule $channelModule)
    {
        $this->channelModules[] = $channelModule;

        return $this;
    }

    /**
     * Remove channels
     *
     * @param \CampaignChain\CoreBundle\Entity\ChannelModule $channels
     */
    public function removeChannelModule(\CampaignChain\CoreBundle\Entity\ChannelModule $channelModule)
    {
        $this->channelModules->removeElement($channelModule);
    }

    /**
     * Get channels
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getChannelModules()
    {
        return $this->channelModules;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->activities = new \Doctrine\Common\Collections\ArrayCollection();
        $this->channelModules = new \Doctrine\Common\Collections\ArrayCollection();
    }
}
