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

namespace CampaignChain\CoreBundle\Exception;

class ErrorCode
{
    const PHP_EXCEPTION = 1001;
    const OPERATION_NOT_EXECUTABLE_IN_LOCATION = 1002;
    const ACTIVITY_NOT_EXECUTABLE_IN_LOCATION = 1003;
    const MILESTONE_NOT_EXECUTABLE_IN_LOCATION = 1004;
    const CONNECTION_TO_REST_API_FAILED = 1005;
    const CAMPAIGN_CONCURRENT_EDIT_START_DATE = 1006;
    const CAMPAIGN_CONCURRENT_EDIT_END_DATE = 1007;
    const CAMPAIGN_TIMESPAN_INSUFFICIENT = 1008;

    static function getMessageByCode($code)
    {
        $messages = array(
            1001 => 'An error in the PHP code occurred.',
            1002 => 'The Operation cannot be executed in the Location.',
            1003 => 'The Activity cannot be executed in the Location.',
            1004 => 'The Milestone cannot be executed in the Location.',
            1005 => 'The connection to the REST API failed.',
            1006 => 'While you edited the campaign, someone else added or changed an Activity or Milestone which now has an earlier start date than the campaign.',
            1007 => 'While you edited the campaign, someone else added or changed an Activity or Milestone which now has a later start date than the campaign end date.',
            1008 => 'The timespan is too short.',
        );

        if(isset($messages[$code])){
            return $messages[$code];
        } else {
            throw new \Exception('Error code "'.$code.'" does not exist.');
        }
    }
}
