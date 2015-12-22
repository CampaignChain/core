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
 * @ORM\Table(name="campaignchain_scheduler")
 */
class Scheduler extends Meta
{
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
     * @ORM\OneToMany(targetEntity="Job", mappedBy="scheduler")
     */
    protected $jobs;

    /**
     * @ORM\Column(type="smallint")
     */
    protected $periodInterval;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $duration;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $periodStart;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $periodEnd;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $executionStart;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $executionEnd;

    /**
     * @ORM\Column(type="string", length=20)
     */
    protected $status = self::STATUS_RUNNING;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $message;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->jobs = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Add jobs
     *
     * @param \CampaignChain\CoreBundle\Entity\Job $jobs
     * @return Scheduler
     */
    public function addJob(\CampaignChain\CoreBundle\Entity\Job $jobs)
    {
        $this->jobs[] = $jobs;

        return $this;
    }

    /**
     * Remove jobs
     *
     * @param \CampaignChain\CoreBundle\Entity\Job $jobs
     */
    public function removeJob(\CampaignChain\CoreBundle\Entity\Job $jobs)
    {
        $this->jobs->removeElement($jobs);
    }

    /**
     * Get jobs
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getJobs()
    {
        return $this->jobs;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Scheduler
     */
    public function setStatus($status)
    {
        if (!in_array($status, array(
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
     * Set duration
     *
     * @param integer $duration
     * @return Scheduler
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
     * Set periodStart
     *
     * @param \DateTime $periodStart
     * @return Scheduler
     */
    public function setPeriodStart($periodStart)
    {
        $this->periodStart = $periodStart;

        return $this;
    }

    /**
     * Get periodStart
     *
     * @return \DateTime 
     */
    public function getPeriodStart()
    {
        return $this->periodStart;
    }

    /**
     * Set periodEnd
     *
     * @param \DateTime $periodEnd
     * @return Scheduler
     */
    public function setPeriodEnd($periodEnd)
    {
        $this->periodEnd = $periodEnd;

        return $this;
    }

    /**
     * Get periodEnd
     *
     * @return \DateTime 
     */
    public function getPeriodEnd()
    {
        return $this->periodEnd;
    }

    /**
     * Set executionStart
     *
     * @param \DateTime $executionStart
     * @return Scheduler
     */
    public function setExecutionStart($executionStart)
    {
        $this->executionStart = $executionStart;

        return $this;
    }

    /**
     * Get executionStart
     *
     * @return \DateTime 
     */
    public function getExecutionStart()
    {
        return $this->executionStart;
    }

    /**
     * Set executionEnd
     *
     * @param \DateTime $executionEnd
     * @return Scheduler
     */
    public function setExecutionEnd($executionEnd)
    {
        $this->executionEnd = $executionEnd;

        return $this;
    }

    /**
     * Get executionEnd
     *
     * @return \DateTime 
     */
    public function getExecutionEnd()
    {
        return $this->executionEnd;
    }

    /**
     * Set periodInterval
     *
     * @param integer $periodInterval
     * @return Scheduler
     */
    public function setPeriodInterval($periodInterval)
    {
        $this->periodInterval = $periodInterval;

        return $this;
    }

    /**
     * Get periodInterval
     *
     * @return integer 
     */
    public function getPeriodInterval()
    {
        return $this->periodInterval;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return Scheduler
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
}
