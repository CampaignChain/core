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
 * @ORM\MappedSuperclass
 */
class Medium extends Meta
{
    /**
     * Types of media.
     */
    const TYPE_CHANNEL = 'channel';
    const TYPE_LOCATION = 'location';

    /**
     * Status constants.
     */
    const STATUS_UNPUBLISHED = 'unpublished';
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=20)
     */
    protected $status = self::STATUS_ACTIVE;

    /**
     * Set name
     *
     * @param string $name
     * @return Medium
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Medium
     */
    public function setStatus($status)
    {
        if (!in_array($status, array(
            self::STATUS_UNPUBLISHED,
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
        ))) {
            throw new \InvalidArgumentException("Invalid status in ".get_class($this).".");
        }

        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
}
