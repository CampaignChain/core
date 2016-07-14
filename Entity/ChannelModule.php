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
 * @ORM\Entity(repositoryClass="CampaignChain\CoreBundle\Repository\ChannelRepository")
 */
class ChannelModule extends Module
{
    /**
     * @ORM\OneToMany(targetEntity="Channel", mappedBy="channelModule")
     */
    protected $channels;

    /**
     * @ORM\ManyToMany(targetEntity="ActivityModule", mappedBy="channelModules")
     **/
    protected $activityModules;

    /**
     * @ORM\ManyToMany(targetEntity="LocationModule", mappedBy="channelModules")
     **/
    protected $locationModules;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->channels = new \Doctrine\Common\Collections\ArrayCollection();
        $this->activityModules = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add channels
     *
     * @param \CampaignChain\CoreBundle\Entity\Channel $channels
     * @return ChannelModule
     */
    public function addChannel(\CampaignChain\CoreBundle\Entity\Channel $channels)
    {
        $this->channels[] = $channels;

        return $this;
    }

    /**
     * Remove channels
     *
     * @param \CampaignChain\CoreBundle\Entity\Channel $channels
     */
    public function removeChannel(\CampaignChain\CoreBundle\Entity\Channel $channels)
    {
        $this->channels->removeElement($channels);
    }

    /**
     * Get channels
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getChannels()
    {
        return $this->channels;
    }

    /**
     * Add activityModules
     *
     * @param \CampaignChain\CoreBundle\Entity\ActivityModule $activityModules
     * @return ChannelModule
     */
    public function addActivityModule(\CampaignChain\CoreBundle\Entity\ActivityModule $activityModules)
    {
        $this->activityModules[] = $activityModules;

        return $this;
    }

    /**
     * Remove activityModules
     *
     * @param \CampaignChain\CoreBundle\Entity\ActivityModule $activityModules
     */
    public function removeActivityModule(\CampaignChain\CoreBundle\Entity\ActivityModule $activityModules)
    {
        $this->activityModules->removeElement($activityModules);
    }

    /**
     * Get activityModules
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getActivityModules()
    {
        return $this->activityModules;
    }


    /**
     * Add locationModules
     *
     * @param \CampaignChain\CoreBundle\Entity\LocationModule $locationModules
     * @return ChannelModule
     */
    public function addLocationModule(\CampaignChain\CoreBundle\Entity\LocationModule $locationModules)
    {
        $this->locationModules[] = $locationModules;

        return $this;
    }

    /**
     * Remove locationModules
     *
     * @param \CampaignChain\CoreBundle\Entity\LocationModule $locationModules
     */
    public function removeLocationModule(\CampaignChain\CoreBundle\Entity\LocationModule $locationModules)
    {
        $this->locationModules->removeElement($locationModules);
    }

    /**
     * Get locationModules
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLocationModules()
    {
        return $this->locationModules;
    }
}
