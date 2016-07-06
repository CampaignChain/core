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
     * @ORM\ManyToOne(targetEntity="Operation", inversedBy="fact")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $operation;

    /**
     * @ORM\ManyToOne(targetEntity="Activity", inversedBy="facts")
     */
    protected $activity;

    /**
     * @ORM\ManyToOne(targetEntity="Campaign", inversedBy="activityFacts")
     */
    protected $campaign;

    /**
     * @ORM\ManyToOne(targetEntity="ReportAnalyticsActivityMetric", inversedBy="facts")
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get value.
     *
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set value.
     *
     * @param int $value
     *
     * @return ReportAnalyticsActivityFact
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get time in JavaScript timestamp format.
     *
     * @return \DateTime
     */
    public function getJavascriptTimestamp()
    {
        $date = new \DateTime($this->time->format('Y-m-d H:i:s'));
        $javascriptTimestamp = $date->getTimestamp() * 1000;

        return $javascriptTimestamp;
    }

    /**
     * Get operation.
     *
     * @return Operation
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Set operation.
     *
     * @param Operation $operation
     *
     * @return ReportAnalyticsActivityFact
     */
    public function setOperation(Operation $operation = null)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Get activity.
     *
     * @return Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * Set activity.
     *
     * @param Activity $activity
     *
     * @return ReportAnalyticsActivityFact
     */
    public function setActivity(Activity $activity = null)
    {
        $this->activity = $activity;

        return $this;
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
     * @return ReportAnalyticsActivityFact
     */
    public function setCampaign(Campaign $campaign = null)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * Get time.
     *
     * @return \DateTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set time.
     *
     * @param \DateTime $time
     *
     * @return ReportAnalyticsActivityFact
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get metric.
     *
     * @return ReportAnalyticsActivityMetric
     */
    public function getMetric()
    {
        return $this->metric;
    }

    /**
     * Set metric.
     *
     * @param ReportAnalyticsActivityMetric $metric
     *
     * @return ReportAnalyticsActivityFact
     */
    public function setMetric(ReportAnalyticsActivityMetric $metric = null)
    {
        $this->metric = $metric;

        return $this;
    }
}
