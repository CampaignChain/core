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
 * @ORM\Entity
 */
class LocationModule extends Module
{
    /**
     * @ORM\OneToMany(targetEntity="Location", mappedBy="locationModule")
     */
    protected $locations;

    /**
     * @ORM\ManyToMany(targetEntity="ChannelModule", inversedBy="locationModules")
     * @ORM\JoinTable(name="campaignchain_module_location_channel",
     *   joinColumns={@ORM\JoinColumn(name="locationmodule_id", referencedColumnName="id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="channelmodule_id", referencedColumnName="id")}
     *   )
     **/
    protected $channelModules;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->locations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->channelModules = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add locations
     *
     * @param \CampaignChain\CoreBundle\Entity\Location $locations
     * @return LocationModule
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
