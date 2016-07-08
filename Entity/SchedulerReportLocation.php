<?php
/**
 *
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace CampaignChain\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="CampaignChain\CoreBundle\Repository\SchedulerReportLocationRepository")
 */
class SchedulerReportLocation extends SchedulerReport
{
    /**
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="scheduledReports")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $location;

    /**
     * Set location
     * @param Location $location
     * @return $this
     */
    public function setLocation(Location $location = null)
    {
        $this->location = $location;

        $this->setStartDate(new \DateTime('now', new \DateTimeZone('UTC')));

        return $this;
    }

    /**
     * Get operation
     *
     * @return \CampaignChain\CoreBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }
}
