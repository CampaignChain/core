<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
