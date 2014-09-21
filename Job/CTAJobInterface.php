<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Job;
use CampaignChain\CoreBundle\Entity\Location;

/**
 * Interface JobInterface
 *
 * This interface is supposed to be implemented by a Job service.
 *
 * @author Sandro Groganz <sandro@campaignchain.com>
 * @package CampaignChain\CoreBundle\Job
 */
interface CTAJobInterface
{
    /*
     * @return Location
     */
    public function execute(Location $location);
}