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

use CampaignChain\Hook\ImageBundle\Entity\Image;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="CampaignChain\CoreBundle\Repository\ActivityRepository")
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
     * @ORM\JoinColumn(name="channel_id", referencedColumnName="id", nullable=true)
     */
    protected $channel;

    /**
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="activities")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id", nullable=true)
     */
    protected $location;

    /**
     * @ORM\OneToMany(targetEntity="Operation", mappedBy="activity",cascade={"persist", "remove"})
     */
    protected $operations;

    /**
     * @ORM\OneToMany(targetEntity="ReportAnalyticsActivityFact", mappedBy="activity")
     */
    protected $facts;

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
     * @ORM\OneToMany(targetEntity="CampaignChain\Hook\ImageBundle\Entity\Image", mappedBy="activity", cascade={"persist"}, orphanRemoval=true)
     */
    protected $images;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->operations = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->facts = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get campaign.
     *
     * @return Campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * Set campaign.
     *
     * @param Campaign $campaign
     *
     * @return Activity
     */
    public function setCampaign(Campaign $campaign = null)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * Get channel.
     *
     * @return Channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Set channel.
     *
     * @param Channel $channel
     *
     * @return Activity
     */
    public function setChannel(Channel $channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Get location.
     *
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set location.
     *
     * @param Location $location
     *
     * @return Activity
     */
    public function setLocation(Location $location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Add operations.
     *
     * @param Operation $operations
     *
     * @return Activity
     */
    public function addOperation(Operation $operations)
    {
        $this->operations[] = $operations;

        return $this;
    }

    /**
     * Remove operations.
     *
     * @param Operation $operation
     */
    public function removeOperation(Operation $operation)
    {
        $this->operations->removeElement($operation);
    }

    /**
     * Add fact.
     *
     * @param ReportAnalyticsActivityFact $fact
     *
     * @return Activity
     */
    public function addFact(ReportAnalyticsActivityFact $fact)
    {
        $this->facts->add($fact);

        return $this;
    }

    /**
     * Remove fact.
     *
     * @param ReportAnalyticsActivityFact $fact
     */
    public function removeFact(ReportAnalyticsActivityFact $fact)
    {
        $this->facts->removeElement($fact);
    }

    /**
     * Get fact.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFacts()
    {
        return $this->facts;
    }

    /**
     * Get activityModule.
     *
     * @return ActivityModule
     */
    public function getActivityModule()
    {
        return $this->activityModule;
    }

    /**
     * Set activityModule.
     *
     * @param ActivityModule $activityModule
     *
     * @return Activity
     */
    public function setActivityModule(ActivityModule $activityModule = null)
    {
        $this->activityModule = $activityModule;

        return $this;
    }

    /**
     * Convenience method that masquerades getActivityModule().
     *
     * @return ActivityModule
     */
    public function getModule()
    {
        return $this->activityModule;
    }

    /**
     * If the Activity equals the Operation, then set the status of the Activity to the same value.
     *
     * @param string $status
     * @param bool   $calledFromOperation
     *
     * @return Activity
     */
    public function setStatus($status, $calledFromOperation = false)
    {
        parent::setStatus($status);

        // Change the Operation as well only if this method has not been called by an Operation instance to avoid recursion.
        if (!$calledFromOperation && $this->getEqualsOperation() && count($this->getOperations())) {
            $this->getOperations()[0]->setStatus($this->status, true);
        }

        return $this;
    }

    /**
     * Get equalsOperation.
     *
     * @return bool
     */
    public function getEqualsOperation()
    {
        return $this->equalsOperation;
    }

    /**
     * Set equalsOperation.
     *
     * @param bool $equalsOperation
     *
     * @return Activity
     */
    public function setEqualsOperation($equalsOperation)
    {
        $this->equalsOperation = $equalsOperation;

        return $this;
    }

    /**
     * Get operations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOperations()
    {
        return $this->operations;
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
     * Add children.
     *
     * @param Activity $children
     *
     * @return Activity
     */
    public function addChild(Activity $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children.
     *
     * @param Activity $children
     */
    public function removeChild(Activity $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Get parent.
     *
     * @return Activity
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set parent.
     *
     * @param Activity $parent
     *
     * @return Activity
     */
    public function setParent(Activity $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Image[]|ArrayCollection
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param Image[] $images
     */
    public function setImages($images)
    {
        $this->images = $images;
    }

    /**
     * @param Image $image
     */
    public function addImage(Image $image)
    {
        $this->images[] = $image;
    }

    /**
     * @param Image $image
     */
    public function removeImage(Image $image)
    {
        $this->images->removeElement($image);
    }
}
