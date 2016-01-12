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
 * @ORM\Table(name="campaignchain_activity")
 */
class Activity extends Action implements AssignableInterface
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="Activity", mappedBy="parent")
     */
    protected $children;

    /**
     * @ORM\ManyToOne(targetEntity="Activity", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     */
    protected $parent;

    /**
     * @ORM\ManyToOne(targetEntity="Campaign", inversedBy="activities")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $campaign;

    /**
     * @ORM\ManyToOne(targetEntity="Channel", inversedBy="activities")
     * @ORM\JoinColumn(name="channel_id", referencedColumnName="id", nullable=false)
     */
    protected $channel;

    /**
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="activities")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id", nullable=false)
     */
    protected $location;

    /**
     * @ORM\OneToMany(targetEntity="Operation", mappedBy="activity",cascade={"persist", "remove"})
     */
    protected $operations;

    /**
     * @ORM\OneToMany(targetEntity="ReportAnalyticsActivityFact", mappedBy="activity")
     */
    protected $fact;

    /**
     * @ORM\ManyToOne(targetEntity="ActivityModule", inversedBy="activities")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $activityModule;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $equalsOperation = true;

    /**
     * @ORM\ManyToOne(targetEntity="CampaignChain\CoreBundle\Entity\User", inversedBy="activities")
     * @ORM\JoinColumn(name="assignee", referencedColumnName="id")
     */
    protected $assignee;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->operations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->fact = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set equalsOperation
     *
     * @param boolean $equalsOperation
     * @return Activity
     */
    public function setEqualsOperation($equalsOperation)
    {
        $this->equalsOperation = $equalsOperation;

        return $this;
    }

    /**
     * Get equalsOperation
     *
     * @return boolean
     */
    public function getEqualsOperation()
    {
        return $this->equalsOperation;
    }

    /**
     * Set campaign
     *
     * @param \CampaignChain\CoreBundle\Entity\Campaign $campaign
     * @return Activity
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
     * Set channel
     *
     * @param \CampaignChain\CoreBundle\Entity\Channel $channel
     * @return Activity
     */
    public function setChannel(\CampaignChain\CoreBundle\Entity\Channel $channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Get channel
     *
     * @return \CampaignChain\CoreBundle\Entity\Channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Set location
     *
     * @param \CampaignChain\CoreBundle\Entity\Location $location
     * @return Activity
     */
    public function setLocation(\CampaignChain\CoreBundle\Entity\Location $location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return \CampaignChain\CoreBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Add operations
     *
     * @param \CampaignChain\CoreBundle\Entity\Operation $operations
     * @return Activity
     */
    public function addOperation(\CampaignChain\CoreBundle\Entity\Operation $operations)
    {
        $this->operations[] = $operations;

        return $this;
    }

    /**
     * Remove operations
     *
     * @param \CampaignChain\CoreBundle\Entity\Operation $operations
     */
    public function removeOperation(\CampaignChain\CoreBundle\Entity\Operation $operations)
    {
        $this->operations->removeElement($operations);
    }

    /**
     * Get operations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOperations()
    {
        return $this->operations;
    }

    /**
     * Add fact
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityFact $fact
     * @return Activity
     */
    public function addFact(\CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityFact $fact)
    {
        $this->fact[] = $fact;

        return $this;
    }

    /**
     * Remove fact
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityFact $fact
     */
    public function removeFact(\CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityFact $fact)
    {
        $this->fact->removeElement($fact);
    }

    /**
     * Get fact
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFact()
    {
        return $this->fact;
    }

    /**
     * Set activityModule
     *
     * @param \CampaignChain\CoreBundle\Entity\ActivityModule $activityModule
     * @return Activity
     */
    public function setActivityModule(\CampaignChain\CoreBundle\Entity\ActivityModule $activityModule = null)
    {
        $this->activityModule = $activityModule;

        return $this;
    }

    /**
     * Get activityModule
     *
     * @return \CampaignChain\CoreBundle\Entity\ActivityModule
     */
    public function getActivityModule()
    {
        return $this->activityModule;
    }

    /**
     * Convenience method that masquerades getActivityModule()
     *
     * @return \CampaignChain\CoreBundle\Entity\ActivityModule
     */
    public function getModule()
    {
        return $this->activityModule;
    }

    /**
     * If the Activity equals the Operation, then set the status of the Activity to the same value.
     *
     * @param string $status
     * @return Activity
     */
    public function setStatus($status, $calledFromOperation = false)
    {
        parent::setStatus($status);

        // Change the Operation as well only if this method has not been called by an Operation instance to avoid recursion.
        if(!$calledFromOperation && $this->getEqualsOperation() && count($this->getOperations())) {
            $this->getOperations()[0]->setStatus($this->status, true);
        }

        return $this;
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

    /**
     * Add children
     *
     * @param \CampaignChain\CoreBundle\Entity\Activity $children
     * @return Activity
     */
    public function addChild(\CampaignChain\CoreBundle\Entity\Activity $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \CampaignChain\CoreBundle\Entity\Activity $children
     */
    public function removeChild(\CampaignChain\CoreBundle\Entity\Activity $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent
     *
     * @param \CampaignChain\CoreBundle\Entity\Activity $parent
     * @return Activity
     */
    public function setParent(\CampaignChain\CoreBundle\Entity\Activity $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \CampaignChain\CoreBundle\Entity\Activity
     */
    public function getParent()
    {
        return $this->parent;
    }
}
