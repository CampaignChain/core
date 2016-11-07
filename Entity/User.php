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

use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="campaignchain_user")
 * @ORM\HasLifecycleCallbacks
 *
 * @ORM\EntityListeners({"CampaignChain\CoreBundle\EventListener\UserAvatarListener"})
 */
class User extends BaseUser
{
    private static $ROLE_NAMES = [
        'ROLE_USER' => 'User',
        'ROLE_ADMIN' => 'Admin',
        'ROLE_SUPER_ADMIN' => 'Super Admin'
    ];

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToMany(targetEntity="CampaignChain\CoreBundle\Entity\Group")
     * @ORM\JoinTable(name="campaignchain_user_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    protected $groups;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    protected $language = 'en_US';

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    protected $locale = 'en_US';

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    protected $timezone = 'UTC';

    /**
     * @ORM\Column(type="string", length=3, nullable=true)
     */
    protected $currency = 'USD';

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $dateFormat = 'yyyy-MM-dd';

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $timeFormat = 'HH:mm';

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $createdDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $modifiedDate;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $firstName;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $lastName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $avatarImage;

    /**
     * @ORM\OneToMany(targetEntity="CampaignChain\CoreBundle\Entity\Campaign", mappedBy="user")
     */
    protected $campaigns;

    /**
     * @ORM\OneToMany(targetEntity="CampaignChain\CoreBundle\Entity\Activity", mappedBy="user")
     */
    protected $activities;

    /**
     * @ORM\OneToMany(targetEntity="CampaignChain\CoreBundle\Entity\Milestone", mappedBy="user")
     */
    protected $milestones;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->campaigns = new ArrayCollection();
        $this->activities = new ArrayCollection();
        $this->milestones = new ArrayCollection();
    }

    /**
     * Set language
     *
     * @param string $language
     * @return User
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return string 
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return User
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string 
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set timezone
     *
     * @param string $timezone
     * @return User
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Get timezone
     *
     * @return string 
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * Set currency
     *
     * @param string $currency
     * @return User
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string 
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set dateFormat
     *
     * @param string $dateFormat
     * @return User
     */
    public function setDateFormat($dateFormat)
    {
        $this->dateFormat = $dateFormat;

        return $this;
    }

    /**
     * Get dateFormat
     *
     * @return string 
     */
    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    /**
     * Set timeFormat
     *
     * @param string $timeFormat
     * @return User
     */
    public function setTimeFormat($timeFormat)
    {
        $this->timeFormat = $timeFormat;

        return $this;
    }

    /**
     * Get timeFormat
     *
     * @return string 
     */
    public function getTimeFormat()
    {
        return $this->timeFormat;
    }

    /**
     * Set createdDate
     *
     * @param \DateTime $createdDate
     * @return User
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    /**
     * Get createdDate
     *
     * @return \DateTime
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * Set modifiedDate
     *
     * @param \DateTime $modifiedDate
     * @return User
     */
    public function setModifiedDate($modifiedDate)
    {
        $this->modifiedDate = $modifiedDate;

        return $this;
    }

    /**
     * Get modifiedDate
     *
     * @return \DateTime
     */
    public function getModifiedDate()
    {
        return $this->modifiedDate;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param mixed $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    public function getName()
    {
        return $this->firstName.' '.$this->lastName;
    }

    public function getNameAndUsername()
    {
        return $this->getName().' ('.$this->username.')';
    }

    /**
     * @return string|null
     */
    public function getAvatarImage()
    {
        if($this->avatarImage) {
            return $this->avatarImage;
        } else {
            return 'default_user.png';
        }
    }

    /**
     * @param string|null $avatarImage
     *
     * @return self
     */
    public function setAvatarImage($avatarImage)
    {
        $this->avatarImage = $avatarImage;
        return $this;
    }

    public function getHumanRole()
    {
        return join(', ', array_map(function($role) {
            if (isset(self::$ROLE_NAMES[$role])) {
                return self::$ROLE_NAMES[$role];
            }
        }, $this->getRoles()));
    }

    /**
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function timestamps()
    {
        if ($this->getCreatedDate() == null) {
            $this->setCreatedDate(new \DateTime('now', new \DateTimeZone('UTC')));
        } else {
            $this->setModifiedDate(new \DateTime('now', new \DateTimeZone('UTC')));
        }
    }

    /**
     * @return mixed
     */
    public function getCampaigns()
    {
        return $this->campaigns;
    }

    /**
     * @param mixed $campaigns
     */
    public function setCampaigns($campaigns)
    {
        $this->campaigns = $campaigns;
    }

    /**
     * @param Campaign $campaign
     */
    public function addCampaign(Campaign $campaign)
    {
        $this->campaigns[] = $campaign;
    }

    /**
     * @param Campaign $campaign
     */
    public function removeCampaign(Campaign $campaign)
    {
        $this->campaigns->removeElement($campaign);
    }

    /**
     * @return mixed
     */
    public function getActivities()
    {
        return $this->activities;
    }

    /**
     * @param mixed $activities
     */
    public function setActivities($activities)
    {
        $this->activities = $activities;
    }

    /**
     * @param Activity $activity
     */
    public function addActivity(Activity $activity)
    {
        $this->activities[] = $activity;
    }

    /**
     * @param Activity $activity
     */
    public function removeActivity(Activity $activity)
    {
        $this->activities->removeElement($activity);
    }

    /**
     * @return mixed
     */
    public function getMilestones()
    {
        return $this->milestones;
    }

    /**
     * @param mixed $milestones
     */
    public function setMilestones($milestones)
    {
        $this->milestones = $milestones;
    }

    /**
     * @param Milestone $milestone
     */
    public function addMilestone(Milestone $milestone)
    {
        $this->milestones[] = $milestone;
    }

    /**
     * @param Milestone $milestone
     */
    public function removeMilestone(Milestone $milestone)
    {
        $this->milestones->removeElement($milestone);
    }

    /*
     * Check if the user has ever logged in
     * @return boolean
     */
    public function isFirstLogin()
    {
        return (null === $this->getLastLogin());
    }
}
