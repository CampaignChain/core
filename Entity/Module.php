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
 * @ORM\Table(name="campaignchain_module")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap( { "activity" = "ActivityModule", "campaign" = "CampaignModule", "channel" = "ChannelModule", "location" = "LocationModule", "milestone" = "MilestoneModule", "operation" = "OperationModule", "report" = "ReportModule", "security" = "SecurityModule" } )
 */

abstract class Module extends Meta
{
    const REPOSITORY_CAMPAIGN = 'CampaignModule';
    const REPOSITORY_MILESTONE = 'MilestoneModule';
    const REPOSITORY_ACTIVITY = 'ActivityModule';
    const REPOSITORY_OPERATION = 'OperationModule';
    const REPOSITORY_CHANNEL = 'ChannelModule';
    const REPOSITORY_LOCATION = 'LocationModule';
    const REPOSITORY_SECURITY = 'SecurityModule';
    const REPOSITORY_REPORT = 'ReportModule';

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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $trackingAlias;

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
     * Set trackingAlias
     *
     * @param string $trackingAlias
     * @return Module
     */
    public function setTrackingAlias($trackingAlias)
    {
        $this->trackingAlias = $trackingAlias;

        return $this;
    }

    /**
     * Get trackingAlias
     *
     * @return string
     */
    public function getTrackingAlias()
    {
        return $this->trackingAlias;
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
