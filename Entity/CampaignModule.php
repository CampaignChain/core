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
use CampaignChain\CoreBundle\Entity\Module;

/**
 * @ORM\Entity
 */
class CampaignModule extends Module
{
    /**
     * @ORM\OneToMany(targetEntity="Campaign", mappedBy="campaignModule")
     */
    protected $campaigns;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->campaigns = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add campaigns
     *
     * @param \CampaignChain\CoreBundle\Entity\Campaign $campaigns
     * @return CampaignModule
     */
    public function addCampaign(\CampaignChain\CoreBundle\Entity\Campaign $campaigns)
    {
        $this->campaigns[] = $campaigns;

        return $this;
    }

    /**
     * Remove campaigns
     *
     * @param \CampaignChain\CoreBundle\Entity\Campaign $campaigns
     */
    public function removeCampaign(\CampaignChain\CoreBundle\Entity\Campaign $campaigns)
    {
        $this->campaigns->removeElement($campaigns);
    }

    /**
     * Get campaigns
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCampaigns()
    {
        return $this->campaigns;
    }
}
