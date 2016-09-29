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
 * @ORM\Entity(repositoryClass="CampaignChain\CoreBundle\Repository\JobRepository")
 * @ORM\Table(name="campaignchain_job")
 */
class Job extends Meta
{
    const STATUS_OPEN = 'open';
    const STATUS_RUNNING = 'running';
    const STATUS_CLOSED = 'closed';
    const STATUS_ERROR = 'error';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $pid;

    /**
     * @ORM\ManyToOne(targetEntity="Scheduler", inversedBy="jobs")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $scheduler;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $startDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $endDate;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $duration;

    /**
     * @ORM\Column(type="integer")
     */
    protected $actionId;

    /**
     * @ORM\Column(type="string")
     */
    protected $actionType;

    /**
     * @ORM\Column(type="string", length=20)
     */
    protected $status = self::STATUS_OPEN;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $message;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $errorCode;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $jobType;

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
     * Set name
     *
     * @param string $name
     * @return Job
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Job
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return Job
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set actionId
     *
     * @param integer $actionId
     * @return Job
     */
    public function setActionId($actionId)
    {
        $this->actionId = $actionId;

        return $this;
    }

    /**
     * Get actionId
     *
     * @return integer 
     */
    public function getActionId()
    {
        return $this->actionId;
    }

    /**
     * Set actionType
     *
     * @param string $actionType
     * @return Job
     */
    public function setActionType($actionType)
    {
        $this->actionType = $actionType;

        return $this;
    }

    /**
     * Get actionType
     *
     * @return string 
     */
    public function getActionType()
    {
        return $this->actionType;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Job
     */
    public function setStatus($status)
    {
        if (!in_array($status, array(
            self::STATUS_OPEN,
            self::STATUS_RUNNING,
            self::STATUS_CLOSED,
            self::STATUS_ERROR,
        ))) {
            throw new \InvalidArgumentException("Invalid status in ".get_class($this).".");
        }

        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set scheduler
     *
     * @param \CampaignChain\CoreBundle\Entity\Scheduler $scheduler
     * @return Job
     */
    public function setScheduler(\CampaignChain\CoreBundle\Entity\Scheduler $scheduler = null)
    {
        $this->scheduler = $scheduler;

        return $this;
    }

    /**
     * Get scheduler
     *
     * @return \CampaignChain\CoreBundle\Entity\Scheduler
     */
    public function getScheduler()
    {
        return $this->scheduler;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     * @return Job
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return integer 
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return Job
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set errorCode
     *
     * @param integer $errorCode
     * @return Job
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    /**
     * Get errorCode
     *
     * @return integer
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Set pid
     *
     * @param integer $pid
     * @return Job
     */
    public function setPid($pid)
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * Get pid
     *
     * @return integer 
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set jobType
     *
     * @param string $jobType
     * @return Job
     */
    public function setJobType($jobType)
    {
        $this->jobType = $jobType;

        return $this;
    }

    /**
     * Get jobType
     *
     * @return string
     */
    public function getJobType()
    {
        return $this->jobType;
    }
}
