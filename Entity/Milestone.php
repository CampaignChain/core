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
 * @ORM\Entity(repositoryClass="MilestoneRepository")
 * @ORM\Table(name="campaignchain_milestone")
 */
class Milestone extends Action implements AssignableInterface
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Campaign", cascade={"persist"}, inversedBy="milestones")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $campaign;

    /**
     * @ORM\ManyToOne(targetEntity="MilestoneModule", inversedBy="milestones")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $milestoneModule;

    /**
     * @ORM\ManyToOne(targetEntity="CampaignChain\CoreBundle\Entity\User", inversedBy="milestones")
     * @ORM\JoinColumn(name="assignee", referencedColumnName="id")
     */
    protected $assignee;

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
     * Get time in JavaScript timestamp format
     *
     * @return \DateTime
     */
    public function getJavascriptTimestamp()
    {
        $date = new \DateTime($this->startDate->format('Y-m-d H:i:s'));
        $javascriptTimestamp = $date->getTimestamp()*1000;
        return $javascriptTimestamp;
    }

    /**
     * Set campaign
     *
     * @param \CampaignChain\CoreBundle\Entity\Campaign $campaign
     * @return Milestone
     */
    public function setCampaign(\CampaignChain\CoreBundle\Entity\Campaign $campaign = null)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * Get campaign
     *
     * @return \CampaignChain\CoreBundle\Entity\Campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * Set milestoneModule
     *
     * @param \CampaignChain\CoreBundle\Entity\MilestoneModule $milestoneModule
     * @return Milestone
     */
    public function setMilestoneModule(\CampaignChain\CoreBundle\Entity\MilestoneModule $milestoneModule = null)
    {
        $this->milestoneModule = $milestoneModule;

        return $this;
    }

    /**
     * Get milestoneModule
     *
     * @return \CampaignChain\CoreBundle\Entity\MilestoneModule
     */
    public function getMilestoneModule()
    {
        return $this->milestoneModule;
    }

    /**
     * Convenience method that masquerades getMilestoneModule()
     *
     * @return \CampaignChain\CoreBundle\Entity\MilestoneModule
     */
    public function getModule()
    {
        return $this->milestoneModule;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
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
}
