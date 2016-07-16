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

namespace CampaignChain\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class OperationModule extends Module
{
    /**
     * @ORM\OneToMany(targetEntity="Operation", mappedBy="operationModule", cascade={"persist"})
     */
    protected $operations;

    /**
     * Add operations
     *
     * @param \CampaignChain\CoreBundle\Entity\Operation $operations
     * @return OperationModule
     */
    public function addOperation(\CampaignChain\CoreBundle\Entity\Operation $operations)
    {
        $this->operations[] = $operations;

        return $this;
    }

    /**
     * Remove operations
     *
     * @param \CampaignChain\CoreBundle\Entity\Operation $operations
     */
    public function removeOperation(\CampaignChain\CoreBundle\Entity\Operation $operations)
    {
        $this->operations->removeElement($operations);
    }

    /**
     * Get operations
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOperations()
    {
        return $this->operations;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->operations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function ownsLocation()
    {
        if(isset($this->params['owns_location'])){
            return $this->params['owns_location'];
        } else {
            return false;
        }
    }
}
