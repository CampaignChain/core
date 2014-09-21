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
 * @ORM\Entity
 * @ORM\Table(name="campaignchain_report_analytics_activity_fact")
 */
class ReportAnalyticsActivityFact
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Activity")
     */
    protected $activity;

    /**
     * @ORM\ManyToOne(targetEntity="Campaign")
     */
    protected $campaign;

    /**
     * @ORM\ManyToOne(targetEntity="ReportAnalyticsActivityMetric")
     */
    protected $metric;

    /**
     * @ORM\Column(type="integer")
     */
    protected $value;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $time;

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
     * Set value
     *
     * @param integer $value
     * @return Statistics
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return integer 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get time in JavaScript timestamp format
     *
     * @return \DateTime
     */
    public function getJavascriptTimestamp()
    {
        $date = new \DateTime($this->time->format('Y-m-d H:i:s'));
        $javascriptTimestamp = $date->getTimestamp()*1000;
        return $javascriptTimestamp;
    }

    /**
     * Set activity
     *
     * @param \CampaignChain\CoreBundle\Entity\Activity $activity
     * @return Statistics
     */
    public function setActivity(\CampaignChain\CoreBundle\Entity\Activity $activity = null)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity
     *
     * @return \CampaignChain\CoreBundle\Entity\Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * Set campaign
     *
     * @param \CampaignChain\CoreBundle\Entity\Campaign $campaign
     * @return Statistics
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
     * Set time
     *
     * @param \DateTime $time
     * @return ReportData
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get time
     *
     * @return \DateTime 
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set metric
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityMetric $metric
     * @return ReportAnalyticsActivityFact
     */
    public function setMetric(\CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityMetric $metric = null)
    {
        $this->metric = $metric;

        return $this;
    }

    /**
     * Get metric
     *
     * @return \CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityMetric
     */
    public function getMetric()
    {
        return $this->metric;
    }
}
