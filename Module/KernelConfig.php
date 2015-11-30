<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Module;

class KernelConfig
{
    private $classes = array();

    private $configs = array();

    private $routings = array();

    private $securities = array();

    public function addClasses($classes)
    {
        $this->classes = array_merge($this->classes, $classes);
    }

    public function getClasses()
    {
        return $this->classes;
    }

    public function addConfig($config)
    {
        $this->isRelativeToAppConfig($config);
        $this->configs[] = $config;
    }

    public function getConfigs()
    {
        return $this->configs;
    }

    public function addRouting($routing)
    {
        $this->routings[] = $routing;
    }

    public function getRoutings()
    {
        return $this->routings;
    }

    public function addSecurity($security)
    {
        $this->securities[] = $security;
    }

    public function getSecurities()
    {
        return $this->securities;
    }

    /**
     * Ensure that the config file path is relative to
     * app/config/config.yml.
     *
     * @param $filePath
     * @return bool
     * @throws \Exception
     */
    protected function isRelativeToAppConfig($filePath)
    {
        if(
            strpos($filePath, '../../../')
            === false
        ){
            throw new \Exception(
                'File path must be relative to app/config/config.yml and thus '.
                'start with "../../../".'
            );
        }

        return true;
    }
}