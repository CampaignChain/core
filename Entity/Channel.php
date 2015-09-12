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
 * @ORM\Table(name="campaignchain_channel")
 */
class Channel extends Medium
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="ChannelModule")
     */
    protected $channelModule;

    /**
     * @ORM\OneToMany(targetEntity="Activity", mappedBy="channel")
     */
    protected $activities;

    /**
     * @ORM\OneToMany(targetEntity="Location", mappedBy="channel", cascade={"persist"})
     */
    protected $locations;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $trackingId;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->activities = new \Doctrine\Common\Collections\ArrayCollection();
        $this->locations = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set channelModule
     *
     * @param \CampaignChain\CoreBundle\Entity\ChannelModule $channelModule
     * @return Channel
     */
    public function setChannelModule(\CampaignChain\CoreBundle\Entity\ChannelModule $channelModule = null)
    {
        $this->channelModule = $channelModule;

        return $this;
    }

    /**
     * Get channelModule
     *
     * @return \CampaignChain\CoreBundle\Entity\ChannelModule
     */
    public function getChannelModule()
    {
        return $this->channelModule;
    }

    /**
     * Convenience method that masquerades getChannelModule()
     *
     * @return \CampaignChain\CoreBundle\Entity\ChannelModule
     */
    public function getModule()
    {
        return $this->channelModule;
    }

    /**
     * Add activities
     *
     * @param \CampaignChain\CoreBundle\Entity\Activity $activities
     * @return Channel
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
     * Add locations
     *
     * @param \CampaignChain\CoreBundle\Entity\Location $locations
     * @return Channel
     */
    public function addLocation(\CampaignChain\CoreBundle\Entity\Location $location)
    {
        $this->locations[] = $location;

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
     * Set trackingId
     *
     * @param guid $trackingId
     * @return Channel
     */
    public function setTrackingId($trackingId)
    {
        $this->trackingId = $trackingId;

        return $this;
    }

    /**
     * Get trackingId
     *
     * @return string
     */
    public function getTrackingId()
    {
        return $this->trackingId;
    }
}
