<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Job;

/**
 * Interface JobReportInterface
 *
 * This interface is supposed to be implemented by a report job.
 *
 * @author Sandro Groganz <sandro@campaignchain.com>
 * @package CampaignChain\CoreBundle\Job
 */
interface JobReportInterface extends JobActionInterface
{
     /**
     * @param $action The object of an action (Operation, Activity or Milestone).
     * @return const One of the status constants.
     */
    public function schedule($action, $facts = null);
}