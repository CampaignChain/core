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
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="campaignchain_report_analytics_location_metric")
 */
class ReportAnalyticsLocationMetric
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="ReportAnalyticsLocationFact", mappedBy="metric")
     */
    protected $facts;

    /**
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @ORM\Column(type="string")
     */
    protected $bundle;

    public function getBundle()
    {
        return $this->bundle;
    }

    public function setBundle($bundle)
    {
        $this->bundle = $bundle;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->facts = new ArrayCollection();
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
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return ReportAnalyticsLocationMetric
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Add fact.
     *
     * @param ReportAnalyticsLocationFact $fact
     *
     * @return ReportAnalyticsLocationMetric
     */
    public function addFact(ReportAnalyticsLocationFact $fact)
    {
        $this->facts->add($fact);

        return $this;
    }

    /**
     * Remove fact.
     *
     * @param ReportAnalyticsLocationFact $fact
     */
    public function removeFact(ReportAnalyticsLocationFact $fact)
    {
        $this->facts->removeElement($fact);
    }

    /**
     * Get facts.
     *
     * @return ArrayCollection
     */
    public function getFacts()
    {
        return $this->facts;
    }
}
