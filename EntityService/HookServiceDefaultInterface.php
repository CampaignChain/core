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
use CampaignChain\CoreBundle\Entity\Meta;

/**
 * Interface HookServiceDefaultInterface
 *
 * @author Sandro Groganz <sandro@campaignchain.com>
 * @package CampaignChain\CoreBundle\EntityService
 */
abstract class HookServiceDefaultInterface
{
    /**
     * @var Meta
     */
    protected $entity;

    /**
     * @var string
     */
    protected $errorCodes = array();

    /**
     * @param $entity   An Action (Campaign, Activity, Milestone) or Medium
     *                  (Channel, Location).
     * @return object   The hook object.
     */
    abstract public function getHook($entity);

    /**
     * @param $entity   An Action (Campaign, Activity, Milestone) or Medium
     *                  (Channel, Location).
     * @param $hook
     * @return bool     True, if the hook was processed successfully, false if not.
     */
    public function processHook($entity, $hook)
    {
        return true;
    }

    /**
     * @param $entity   An Action (Campaign, Activity, Milestone) or Medium
     *                  (Channel, Location).
     */
    protected function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @throws \Exception
     * @return object The entity object.
     */
    public function getEntity()
    {
        if($this->entity === null){
            throw new \Exception('Please execute processHook() first.');
        }

        return $this->entity;
    }

    protected function addErrorCode($errorCode)
    {
        $this->errorCodes[] = $errorCode;
    }

    public function getErrorCodes()
    {
        return $this->errorCodes;
    }

    public function hasErrors()
    {
        return count($this->errorCodes);
    }
}