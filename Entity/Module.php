<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="campaignchain_module")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap( { "activity" = "ActivityModule", "campaign" = "CampaignModule", "channel" = "ChannelModule", "location" = "LocationModule", "milestone" = "MilestoneModule", "operation" = "OperationModule", "report" = "ReportModule", "security" = "SecurityModule" } )
 */

abstract class Module extends Meta
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Bundle")
     */
    protected $bundle;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $identifier;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $displayName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $description;

    /**
     * @ORM\Column(type="array")
     */
    protected $routes;

    /**
     * @ORM\Column(type="array")
     */
    protected $services;

    /**
     * @ORM\Column(type="array")
     */
    protected $hooks;

    /**
     * @ORM\Column(type="array")
     */
    protected $params;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set identifier
     *
     * @param string $identifier
     * @return Module
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get identifier
     *
     * @return string 
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set displayName
     *
     * @param string $displayName
     * @return Module
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Get displayName
     *
     * @return string 
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Module
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set routes
     *
     * @param array $routes
     * @return Module
     */
    public function setRoutes($routes)
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * Get routes
     *
     * @return array 
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Set hooks
     *
     * @param array $hooks
     * @return Module
     */
    public function setHooks($hooks)
    {
        $this->hooks = $hooks;

        return $this;
    }

    /**
     * Get hooks
     *
     * @return array 
     */
    public function getHooks()
    {
        return $this->hooks;
    }

    /**
     * Set bundle
     *
     * @param \CampaignChain\CoreBundle\Entity\Bundle $bundle
     * @return Module
     */
    public function setBundle(\CampaignChain\CoreBundle\Entity\Bundle $bundle = null)
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * Get bundle
     *
     * @return \CampaignChain\CoreBundle\Entity\Bundle
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * Set services
     *
     * @param array $services
     * @return Module
     */
    public function setServices($services)
    {
        $this->services = $services;

        return $this;
    }

    /**
     * Get services
     *
     * @return array 
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Set params
     *
     * @param array $params
     * @return Module
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get params
     *
     * @return array 
     */
    public function getParams()
    {
        return $this->params;
    }
}
