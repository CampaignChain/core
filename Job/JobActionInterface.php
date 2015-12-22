<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Job;

/**
 * Interface JobInterface
 *
 * This interface is supposed to be implemented by a Job service.
 *
 * @author Sandro Groganz <sandro@campaignchain.com>
 * @package CampaignChain\CoreBundle\Job
 */
interface JobActionInterface
{
    const STATUS_OK = 'ok';
    const STATUS_WARNING = 'warning';
    const STATUS_ERROR = 'error';

    /**
     * @param $actionId The ID of an action (Operation, Activity, Milestone, or Campaign).
     * @return const One of the status constants.
     */
    public function execute($actionId);

    /**
     * @return string A message returned by the Job.
     */
    public function getMessage();
}