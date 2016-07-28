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
     * Constructor
     */
    public function __construct()
    {
        $this->activities = new \Doctrine\Common\Collections\ArrayCollection();
        $this->channelModules = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
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
}
