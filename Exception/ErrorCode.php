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

    static function getMessageByCode($code)
    {
        $messages = array(
            1001 => 'An error in the PHP code occurred.',
            1002 => 'The Operation cannot be executed in the Location.',
            1003 => 'The Activity cannot be executed in the Location.',
            1004 => 'The Milestone cannot be executed in the Location.',
            1005 => 'The connection to the REST API failed.'
        );

        if(isset($messages[$code])){
            return $messages[$code];
        } else {
            throw new \Exception('Error code "'.$code.'" does not exist.');
        }
    }
}