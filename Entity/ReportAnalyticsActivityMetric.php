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
     * @ORM\OneToMany(targetEntity="ReportAnalyticsActivityMetric", mappedBy="metric")
     */
    protected $fact;

    /**
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @ORM\Column(type="string")
     */
    protected $bundle;

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
     * Set name
     *
     * @param string $name
     * @return ReportAnalyticsActivityMetric
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
     * Constructor
     */
    public function __construct()
    {
        $this->reportData = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add fact
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityMetric $fact
     * @return ReportAnalyticsActivityMetric
     */
    public function addFact(\CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityMetric $fact)
    {
        $this->fact[] = $fact;

        return $this;
    }

    /**
     * Remove fact
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityMetric $fact
     */
    public function removeFact(\CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityMetric $fact)
    {
        $this->fact->removeElement($fact);
    }

    /**
     * Get fact
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getFact()
    {
        return $this->fact;
    }

    /**
     * Set bundle
     *
     * @param string $bundle
     * @return ReportAnalyticsActivityMetric
     */
    public function setBundle($bundle)
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * Get bundle
     *
     * @return string
     */
    public function getBundle()
    {
        return $this->bundle;
    }
}
