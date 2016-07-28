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