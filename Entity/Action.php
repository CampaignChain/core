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
 * @ORM\MappedSuperclass
 */
class Action extends Meta
{
    /**
     * Types of actions.
     */
    const TYPE_CAMPAIGN     = 'campaign';
    const TYPE_MILESTONE    = 'milestone';
    const TYPE_ACTIVITY     = 'activity';
    const TYPE_OPERATION    = 'operation';

    /**
     * Status constants.
     */
    const STATUS_OPEN                   = 'open';
    const STATUS_PAUSED                 = 'paused';
    const STATUS_CLOSED                 = 'closed';
    const STATUS_INTERACTION_REQUIRED   = 'interaction required';
    const STATUS_BACKGROUND_PROCESS     = 'background process';

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
     * A string defining the interval range as a relative date format with a
     * value in the future. For example, if the report operation is supposed
     * to run every hour, the interval would be "1 hour".
     *
     * Relative date formats are defined here:
     * http://php.net/manual/en/datetime.formats.relative.php
     *
     * TODO: Make sure that provided interval has a future value (not pointing
     * to the past).
     *
     * @ORM\Column(name="`interval`", type="string", length=100, nullable=true)
     */
    protected $interval;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $intervalStartDate;

    /**
     * The date when the Action will be run the next time. It will be
     * increased by the scheduler.
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $intervalNextRun;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $intervalEndDate;

    /**
     * The number of times an Action is supposed to be repeated.
     *
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $intervalEndOccurrence;

    /**
     * @ORM\Column(type="string", length=20)
     */
    protected $status = self::STATUS_OPEN;

    /**
     * @ORM\ManyToOne(targetEntity="Hook", inversedBy="activities")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $triggerHook;

    /**
     * Set name
     *
     * @param string $name
     * @return Action
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
     * @return Action
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
     * Set intervalStartDate
     *
     * @param \DateTime $intervalStartDate
     * @return Action
     */
    public function setIntervalStartDate($intervalStartDate)
    {
        $this->intervalStartDate = $intervalStartDate;

        return $this;
    }

    /**
     * Get intervalStartDate
     *
     * @return \DateTime
     */
    public function getIntervalStartDate()
    {
        return $this->intervalStartDate;
    }

    /**
     * Set interval
     *
     * @param string $interval
     * @return Action
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;

        return $this;
    }

    /**
     * Get interval
     *
     * @return string
     */
    public function getInterval()
    {
        return $this->interval;
    }

    public function getIntervalHumanReadable()
    {
        if (strpos($this->interval,'days') !== false) {
            $intervalParts = explode(' ',$this->interval);
            $days = str_replace('+', '', $intervalParts[0]);
            if($days == '1'){
                return 'Every day';
            } else {
                return "Every ".$days." days";
            }
        } elseif (strpos($this->interval,'weeks') !== false) {
            $intervalParts = explode(' ',$this->interval);
            $dayOfWeek = NULL;
            if(strpos($this->interval,'Next') !== false){
                $weeks = str_replace('+', '', $intervalParts[2]);
                $dayOfWeek = $intervalParts[1];
            } else {
                $weeks = str_replace('+', '', $intervalParts[0]);
            }
            if($weeks == '1' && $dayOfWeek) {
                return 'Every ' . $dayOfWeek;
            } elseif($weeks == '1'){
                return 'Every week';
            } elseif($dayOfWeek) {
                return $dayOfWeek.' every '.$weeks.' weeks';
            } else {
                return 'Every '.$weeks.' weeks';
            }
        } elseif (strpos($this->interval,'month') !== false) {
            $intervalParts = explode(' ',$this->interval);

            if (strpos($this->interval,'hours') !== false) {
                // Day of month
                $days = str_replace('+', '', $intervalParts[5])/24;
                $months = str_replace('+', '', $intervalParts[7]) + 1;
                if($days == '1'){
                    if($months == '1'){
                        return 'Day 1 of every month';
                    } else {
                        return 'Day 1 of a month every '.$months.' months';
                    }
                } else {
                    if($months == '1'){
                        return 'Day '.$days.' of every month';
                    } else {
                        return 'Day '.$days.' of a month every '.$months.' months';
                    }
                }

                $dataMonthly['repeat_by'] = 'day_of_month';


            } else {
                // Day of week
                $months = str_replace('+', '', $intervalParts[5]);
                $occurrence = $intervalParts[0];
                $dayOfWeek = $intervalParts[1];
                if($months == '1'){
                    return 'Every '.$occurrence.' '.$dayOfWeek.' of a month';
                } else {
                    return 'Every '.$occurrence.' '.$dayOfWeek.' every '.$months.' months';
                }
            }
        } elseif (strpos($this->interval,'years') !== false) {
            $intervalParts = explode(' ',$this->interval);
            $years = str_replace('+', '', $intervalParts[0]);
            if($years == '1'){
                return 'Every year';
            } else {
                return "Every ".$years." years";
            }
        }
    }

    /**
     * Set intervalNextRun
     *
     * @param \DateTime $intervalNextRun
     * @return Action
     */
    public function setIntervalNextRun($intervalNextRun)
    {
        $this->intervalNextRun = $intervalNextRun;

        return $this;
    }

    /**
     * Get intervalNextRun
     *
     * @return \DateTime
     */
    public function getIntervalNextRun()
    {
        return $this->intervalNextRun;
    }

    /**
     * Set intervalEndDate
     *
     * @param \DateTime $intervalEndDate
     * @return Action
     */
    public function setIntervalEndDate($intervalEndDate)
    {
        $this->intervalEndDate = $intervalEndDate;

        return $this;
    }

    /**
     * Get intervalEndDate
     *
     * @return \DateTime
     */
    public function getIntervalEndDate()
    {
        return $this->intervalEndDate;
    }

    /**
     * @param mixed $intervalEndOccurrence
     */
    public function setIntervalEndOccurrence($intervalEndOccurrence)
    {
        $this->intervalEndOccurrence = $intervalEndOccurrence;
    }

    /**
     * @return mixed
     */
    public function getIntervalEndOccurrence()
    {
        return $this->intervalEndOccurrence;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return Action
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
     * @return Action
     */
    public function setStatus($status)
    {
        if (!in_array($status, array(
            self::STATUS_OPEN,
            self::STATUS_PAUSED,
            self::STATUS_CLOSED,
            self::STATUS_INTERACTION_REQUIRED,
            self::STATUS_BACKGROUND_PROCESS,
        ))) {
            throw new \InvalidArgumentException("Invalid status in ".get_class($this).".");
        }

        // If end date is in the past, status is automatically "closed" if status is not "paused".
        if(
        ($status != self::STATUS_BACKGROUND_PROCESS && $status != self::STATUS_CLOSED && $status != self::STATUS_PAUSED && $this->endDate && $this->endDate < new \DateTime('now'))
        ||
        ($status != self::STATUS_BACKGROUND_PROCESS && $status != self::STATUS_CLOSED && $status != self::STATUS_PAUSED && !$this->endDate && $this->startDate && $this->startDate < new \DateTime('now'))){
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
     * @return Action
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

    static function getStatuses()
    {
        return array(
            self::STATUS_BACKGROUND_PROCESS,
            self::STATUS_CLOSED,
            self::STATUS_INTERACTION_REQUIRED,
            self::STATUS_OPEN,
            self::STATUS_PAUSED,
            self::STATUS_PAUSED,
        );
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
