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
