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
