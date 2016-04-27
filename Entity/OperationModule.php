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
