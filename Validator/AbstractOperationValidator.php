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
 * Class AbstractOperationValidator
 * @package CampaignChain\CoreBundle\Validator
 */
abstract class AbstractOperationValidator
{
    /**
     * Implement this method to identify whether the Activity should be
     * checked before executing it.
     *
     * For example, Twitter does not allow duplicate posts within roughly
     * a 24 hours time frame.
     *
     * @return bool
     */
    public function mustValidate($content, \DateTime $startDate)
    {
        return false;
    }

    /**
     * Checks whether an Operation can be executed.
     *
     * For example, Twitter does not allow to post identical tweets within
     * roughly 24 hours. This method would check whether an identical Tweet was
     * already posted.
     *
     * If an Operation can be executed, then return:
     *
     * array(
     *     'status' => true,
     * );
     *
     * If an Operation cannot be executed, then return:
     *
     * array(
     *     'status' => false,
     *     'message' => 'Your text here',
     * );
     *
     * @param object $content
     * @return array
     */
    public function isExecutableByLocation($content, \DateTime $startDate)
    {
        return array(
            'status' => true,
        );
    }

    /**
     * An Activity implements this method to allow a Campaign to check whether
     * the Activity can be executed within the realm of the Campaign.
     *
     * For example, a repeating campaign will not work properly if a Twitter
     * status message without a link is supposed to be published every day. In
     * that case, Twitter might deny posting of the message due to duplicate
     * content.
     *
     * @param $content
     * @param \DateTime $startDate
     * @return array
     */
    public function isExecutableByCampaign($content, \DateTime $startDate)
    {
        return array(
            'status' => true,
        );
    }

    /**
     * This is a helper method to quickly implement Activity-specific
     * checks as per an interval.
     *
     * For example, the same Tweet cannot be published within 24 hours.
     *
     * @param $content
     * @param \DateTime $startDate
     * @param $interval
     * @param $errMsg
     * @return array
     */
    public function isExecutableByCampaignByInterval($content, \DateTime $startDate, $interval, $errMsg)
    {
        /** @var Campaign $campaign */
        $campaign = $content->getOperation()->getActivity()->getCampaign();

        if($campaign->getInterval()){
            $campaignIntervalDate = new \DateTime();
            $campaignIntervalDate->modify($campaign->getInterval());
            $maxDuplicateIntervalDate = new \DateTime();
            $maxDuplicateIntervalDate->modify($interval);

            if($maxDuplicateIntervalDate > $campaignIntervalDate){
                return array(
                    'status' => false,
                    'message' => $errMsg,
                );
            }
        }

        return $this->isExecutableByCampaign(
            $content, $content->getOperation()->getActivity()->getStartDate()
        );
    }

    /**
     * Allows the Scheduler to check whether an Activity that is due now can
     * actually be executed.
     *
     * @param $content
     * @param \DateTime $startDate
     * @return array
     */
    public function isExecutableByScheduler($content, \DateTime $startDate)
    {
        return array(
            'status' => true,
        );
    }
}