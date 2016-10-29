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

namespace CampaignChain\CoreBundle\Util;

class SchedulerUtil
{
    protected $schedulerInterval;

    public function __construct($schedulerInterval)
    {
        $this->schedulerInterval = $schedulerInterval;
    }

    /**
     * If the post is within the scheduler's interval, then this means that
     * it is supposed to be published now.
     *
     * @param \DateTime $moment
     * @return bool
     */
    public function isDueNow(\DateTime $moment)
    {
        $startInterval = new \DateTime();
        $startInterval->modify('-'.$this->schedulerInterval.' mins');
        if($moment > $startInterval && $moment <= new \DateTime()){
            return true;
        } else {
            return false;
        }
    }
}