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

/**
 * @ORM\MappedSuperclass
 */
class Action
{
    /**
     * Types of actions.
     */
    const TYPE_CAMPAIGN = 'campaign';
    const TYPE_MILESTONE = 'milestone';
    const TYPE_ACTIVITY = 'activity';
    const TYPE_OPERATION = 'operation';

    /**
     * Status constants.
     */
    const STATUS_OPEN = 'open';
    const STATUS_PAUSED = 'paused';
    const STATUS_CLOSED = 'closed';

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
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
     * @ORM\Column(type="string", length=20)
     */
    protected $status = self::STATUS_OPEN;

    /**
     * @ORM\ManyToOne(targetEntity="Hook")
     */
    protected $triggerHook;

    /**
     * Set name
     *
     * @param string $name
     * @return BaseTask
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
     * @return BaseTask
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
     * @return BaseTask
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
     * Set status
     *
     * @param string $status
     * @return BaseTask
     */
    public function setStatus($status)
    {
        if (!in_array($status, array(
            self::STATUS_OPEN,
            self::STATUS_PAUSED,
            self::STATUS_CLOSED,
        ))) {
            throw new \InvalidArgumentException("Invalid status in ".get_class($this).".");
        }

        // If end date is in the past, status is automatically "closed".
        if(
        ($status != self::STATUS_CLOSED && $this->endDate && $this->endDate < new \DateTime('now')) ||
        ($status != self::STATUS_CLOSED && !$this->endDate && $this->startDate && $this->startDate < new \DateTime('now'))){
            // TODO: Warning that status is different from what has been provided.
            $status = self::STATUS_CLOSED;
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
     * Set triggerHook
     *
     * @param \CampaignChain\CoreBundle\Entity\Hook $triggerHook
     * @return BaseTask
     */
    public function setTriggerHook(\CampaignChain\CoreBundle\Entity\Hook $triggerHook = null)
    {
        $this->triggerHook = $triggerHook;

        return $this;
    }

    /**
     * Get triggerHook
     *
     * @return \CampaignChain\CoreBundle\Entity\Hook
     */
    public function getTriggerHook()
    {
        return $this->triggerHook;
    }

    /**
     * Identifies the type of action.
     *
     * @param $action
     * @return string
     */
    public function getType(){
        $class = get_class($this);

        if(strpos($class, 'CoreBundle\Entity\Operation') !== false){
            return self::TYPE_OPERATION;
        }
        if(strpos($class, 'CoreBundle\Entity\Activity') !== false){
            return self::TYPE_ACTIVITY;
        }
        if(strpos($class, 'CoreBundle\Entity\Milestone') !== false){
            return self::TYPE_MILESTONE;
        }
        if(strpos($class, 'CoreBundle\Entity\Campaign') !== false){
            return self::TYPE_CAMPAIGN;
        }

        return false;
    }

    static function getRepositoryName($actionType)
    {
        switch($actionType){
            case self::TYPE_OPERATION:
                $repositoryName = 'CampaignChainCoreBundle:Operation';
                break;
            case self::TYPE_ACTIVITY:
                $repositoryName = 'CampaignChainCoreBundle:Activity';
                break;
            case self::TYPE_MILESTONE:
                $repositoryName = 'CampaignChainCoreBundle:Milestone';
                break;
            case self::TYPE_CAMPAIGN:
                $repositoryName = 'CampaignChainCoreBundle:Campaign';
                break;
            default:
                throw new \Exception('Action type "'.$actionType.'" does not exist.');
                break;
        }

        return $repositoryName;
    }
}
