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

namespace CampaignChain\CoreBundle\Validator;

use CampaignChain\CoreBundle\Entity\Campaign;

/**
 * Class AbstractCampaignValidator
 * @package CampaignChain\CoreBundle\Validator
 */
abstract class AbstractCampaignValidator
{
    /**
     * Implement this method to identify whether the Campaign should be
     * checked before executing it.
     *
     * @return bool
     */
    public function mustValidate(Campaign $campaign)
    {
        return false;
    }

    /**
     * Checks whether the Activities belonging to a Campaign and marked with
     * mustValidate are executable.
     *
     * @param Campaign $campaign
     * @return array
     */
    public function hasExecutableActivities(Campaign $campaign)
    {
        return array(
            'status' => true,
        );
    }

    /**
     * Allows the Scheduler to check whether an ongoing Campaign can
     * actually be executed.
     *
     * @param Campaign $campaign
     * @return array
     */
    public function isExecutableByScheduler(Campaign $campaign)
    {
        return array(
            'status' => true,
        );
    }
}