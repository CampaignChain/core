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
 * Call to Action (CTA)
 *
 * @ORM\Entity
 * @ORM\Table(name="campaignchain_cta")
 */
class CTA extends Meta
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
    * @ORM\ManyToOne(targetEntity="Operation")
    * @ORM\JoinColumn(name="operation_id", referencedColumnName="id", nullable=false)
    */
    protected $operation;

    /**
     * @ORM\ManyToOne(targetEntity="Location", cascade={"persist"})
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id", nullable=false)
     */
    protected $location;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $url;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    protected $shortUrl;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $trackingId;

    /**
     * @ORM\OneToMany(targetEntity="ReportCTA", mappedBy="CTA")
     */
    protected $reports;

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
     * Set trackingId
     *
     * @param guid $trackingId
     * @return CTA
     */
    public function setTrackingId($trackingId)
    {
        $this->trackingId = $trackingId;

        return $this;
    }

    /**
     * Get trackingId
     *
     * @return string
     */
    public function getTrackingId()
    {
        return $this->trackingId;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return CTA
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set operation
     *
     * @param \CampaignChain\CoreBundle\Entity\Operation $operation
     * @return CTA
     */
    public function setOperation(\CampaignChain\CoreBundle\Entity\Operation $operation)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Get operation
     *
     * @return \CampaignChain\CoreBundle\Entity\Operation
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Set shortUrl
     *
     * @param string $shortUrl
     * @return CTA
     */
    public function setShortUrl($shortUrl)
    {
        $this->shortUrl = $shortUrl;

        return $this;
    }

    /**
     * Get shortUrl
     *
     * @return string 
     */
    public function getShortUrl()
    {
        return $this->shortUrl;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->reports = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add reports
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportCTA $reports
     * @return CTA
     */
    public function addReport(\CampaignChain\CoreBundle\Entity\ReportCTA $reports)
    {
        $this->reports[] = $reports;

        return $this;
    }

    /**
     * Remove reports
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportCTA $reports
     */
    public function removeReport(\CampaignChain\CoreBundle\Entity\ReportCTA $reports)
    {
        $this->reports->removeElement($reports);
    }

    /**
     * Get reports
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getReports()
    {
        return $this->reports;
    }

    /**
     * Set location
     *
     * @param \CampaignChain\CoreBundle\Entity\Location $location
     * @return CTA
     */
    public function setLocation(\CampaignChain\CoreBundle\Entity\Location $location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return \CampaignChain\CoreBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }
}
