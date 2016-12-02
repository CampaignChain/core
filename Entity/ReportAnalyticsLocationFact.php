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
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="facts")
     * @ORM\JoinColumn(onDelete="CASCADE")
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
