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

namespace CampaignChain\CoreBundle\EntityService;

use CampaignChain\CoreBundle\Entity\Hook;

/**
 * Interface HookServiceDefaultInterface
 *
 * @author Sandro Groganz <sandro@campaignchain.com>
 * @package CampaignChain\CoreBundle\EntityService
 */
interface HookServiceTriggerInterface extends HookServiceDefaultInterface
{
    /**
     * @param $entity
     * @param $mode
     * @return object The hook object.
     */
    public function getHook($entity, $mode = Hook::MODE_DEFAULT);

    /**
     * @return string The hook's start date field attribute name as specified in the respective form type.
     */
    public function getStartDateIdentifier();

    /**
     * @return string The hook's end date field attribute name as specified in the respective form type.
     */
    public function getEndDateIdentifier();
}