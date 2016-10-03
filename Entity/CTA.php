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
use CampaignChain\CoreBundle\Util\ParserUtil;

/**
 * Call to Action (CTA)
 *
 * @ORM\Entity
 * @ORM\Table(name="campaignchain_cta")
 *
 * @todo Ensure the tracking ID is unique across parent CTAs.
 * @todo Either shortened expanded or shortened tracking URL must be provided.
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
    * @ORM\ManyToOne(targetEntity="Operation", inversedBy="outboundCTAs")
    * @ORM\JoinColumn(name="operation_id", referencedColumnName="id", nullable=false)
    */
    protected $operation;

    /**
     * @ORM\ManyToOne(targetEntity="Location", cascade={"persist"}, inversedBy="ctas")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id", nullable=true)
     */
    protected $location;

    /**
     * Original URL entered by customer
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $originalUrl;

    /**
     * Expanded URL.
     *
     * Will be identical with original URL, if no shortener service was used for
     * original URL.
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $expandedUrl;

    /**
     * Expanded URL shortened.
     *
     * Only defined if original URL does not point to a connected Location.
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    protected $shortenedExpandedUrl;

    /**
     * Unique version of expanded URL, which has an additional fragement or
     * query parameter to ensure the shortened version of it is unique.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $uniqueExpandedUrl;

    /**
     * Shortened version of unique expanded URL.
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    protected $shortenedUniqueExpandedUrl;

    /**
     * Tracking URL.
     *
     * Only defined if original URL points to a connected Location.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $trackingUrl;

    /**
     * Tracking URL shortened.
     *
     * Only defined if original URL points to a connected Location.
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    protected $shortenedTrackingUrl;

    /**
     * Tracking ID.
     *
     * Unique random string.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $trackingId;

    /**
     * @ORM\OneToMany(targetEntity="ReportCTA", mappedBy="CTA")
     */
    protected $reports;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->reports = new ArrayCollection();
    }

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
    public function setOriginalUrl($url)
    {
        $this->originalUrl = ParserUtil::sanitizeUrl($url);

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getOriginalUrl()
    {
        return $this->originalUrl;
    }

    /**
     * @return mixed
     */
    public function getExpandedUrl()
    {
        return $this->expandedUrl;
    }

    /**
     * @param mixed $expandedUrl
     * @return CTA
     */
    public function setExpandedUrl($expandedUrl)
    {
        $this->expandedUrl = $expandedUrl;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getShortenedExpandedUrl()
    {
        return $this->shortenedExpandedUrl;
    }

    /**
     * @param mixed $shortenedExpandedUrl
     * @return CTA
     */
    public function setShortenedExpandedUrl($shortenedExpandedUrl)
    {
        $this->shortenedExpandedUrl = $shortenedExpandedUrl;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUniqueExpandedUrl()
    {
        return $this->uniqueExpandedUrl;
    }

    /**
     * @param mixed $uniqueExpandedUrl
     * @return CTA
     */
    public function setUniqueExpandedUrl($uniqueExpandedUrl)
    {
        $this->uniqueExpandedUrl = $uniqueExpandedUrl;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getShortenedUniqueExpandedUrl()
    {
        return $this->shortenedUniqueExpandedUrl;
    }

    /**
     * @param mixed $shortenedUniqueExpandedUrl
     * @return CTA
     */
    public function setShortenedUniqueExpandedUrl($shortenedUniqueExpandedUrl)
    {
        $this->shortenedUniqueExpandedUrl = $shortenedUniqueExpandedUrl;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTrackingUrl()
    {
        return $this->trackingUrl;
    }

    /**
     * @param mixed $trackingUrl
     * @return CTA
     */
    public function setTrackingUrl($trackingUrl)
    {
        $this->trackingUrl = $trackingUrl;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getShortenedTrackingUrl()
    {
        return $this->shortenedTrackingUrl;
    }

    /**
     * @param mixed $shortenedTrackingUrl
     * @return CTA
     */
    public function setShortenedTrackingUrl($shortenedTrackingUrl)
    {
        $this->shortenedTrackingUrl = $shortenedTrackingUrl;
        return $this;
    }

    /**
     * Set operation
     *
     * @param \CampaignChain\CoreBundle\Entity\Operation $operation
     * @return CTA
     */
    public function setOperation(Operation $operation)
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
     * Add reports
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportCTA $reports
     * @return CTA
     */
    public function addReport(ReportCTA $reports)
    {
        $this->reports[] = $reports;

        return $this;
    }

    /**
     * Remove reports
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportCTA $reports
     */
    public function removeReport(ReportCTA $reports)
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
    public function setLocation(Location $location)
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
