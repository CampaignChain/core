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
 * @ORM\Table(name="campaignchain_report_analytics_location_fact")
 */
class ReportAnalyticsLocationFact
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Location")
     */
    protected $location;

    /**
     * @ORM\ManyToOne(targetEntity="ReportAnalyticsLocationMetric", inversedBy="facts")
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
     * @param $value
     *
     * @return ReportAnalyticsLocationFact
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
     * @return ReportAnalyticsLocationFact
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get metric.
     *
     * @return ReportAnalyticsLocationMetric
     */
    public function getMetric()
    {
        return $this->metric;
    }

    /**
     * Set metric.
     *
     * @param ReportAnalyticsLocationMetric $metric
     *
     * @return ReportAnalyticsLocationFact
     */
    public function setMetric(ReportAnalyticsLocationMetric $metric = null)
    {
        $this->metric = $metric;

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
     * @return ReportAnalyticsLocationFact
     */
    public function setLocation(Location $location = null)
    {
        $this->location = $location;

        return $this;
    }
}
