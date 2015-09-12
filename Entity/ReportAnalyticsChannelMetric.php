<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="campaignchain_report_analytics_channel_metric")
 */
class ReportAnalyticsChannelMetric
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="ReportAnalyticsChannelMetric", mappedBy="metric")
     */
    protected $fact;

    /**
     * @ORM\Column(type="string")
     */
    protected $name;

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
     * @return Variable
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
     * @param \CampaignChain\CoreBundle\Entity\ReportAnalyticsChannelMetric $fact
     * @return ReportAnalyticsChannelMetric
     */
    public function addFact(\CampaignChain\CoreBundle\Entity\ReportAnalyticsChannelMetric $fact)
    {
        $this->fact[] = $fact;

        return $this;
    }

    /**
     * Remove fact
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportAnalyticsChannelMetric $fact
     */
    public function removeFact(\CampaignChain\CoreBundle\Entity\ReportAnalyticsChannelMetric $fact)
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
}
