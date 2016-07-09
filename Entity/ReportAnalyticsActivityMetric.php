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
 * @ORM\Table(name="campaignchain_report_analytics_activity_metric")
 */
class ReportAnalyticsActivityMetric
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="ReportAnalyticsActivityFact", mappedBy="metric")
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
     * @return ReportAnalyticsActivityMetric
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Add fact.
     *
     * @param ReportAnalyticsActivityMetric $fact
     *
     * @return ReportAnalyticsActivityMetric
     */
    public function addFact(ReportAnalyticsActivityMetric $fact)
    {
        $this->facts->add($fact);

        return $this;
    }

    /**
     * Remove fact.
     *
     * @param ReportAnalyticsActivityFact $fact
     */
    public function removeFact(ReportAnalyticsActivityFact $fact)
    {
        $this->facts->removeElement($fact);
    }

    /**
     * Get Facts.
     *
     * @return ArrayCollection
     */
    public function getFacts()
    {
        return $this->facts;
    }

    /**
     * Get bundle.
     *
     * @return string
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * Set bundle.
     *
     * @param string $bundle
     *
     * @return ReportAnalyticsActivityMetric
     */
    public function setBundle($bundle)
    {
        $this->bundle = $bundle;

        return $this;
    }
}
