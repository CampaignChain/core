<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
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
        /*
         * Ensure that the config file path is relative to
         * app/config/config.yml.
         */
        if(
            strpos($config, '..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR)
            === false
        ){
            throw new \Exception(
                'File path must be relative to app/config/config.yml and thus '.
                'start with "..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'".'
            );
        }
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
}