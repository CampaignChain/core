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
class MilestoneModule extends Module
{
    /**
     * @ORM\OneToMany(targetEntity="Milestone", mappedBy="milestoneModule")
     */
    protected $milestones;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->milestones = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add milestones
     *
     * @param \CampaignChain\CoreBundle\Entity\Milestone $milestones
     * @return MilestoneModule
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
}
