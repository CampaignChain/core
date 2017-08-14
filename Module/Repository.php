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

use CampaignChain\CoreBundle\EntityService\SystemService;
use GuzzleHttp\Client;

/**
 * Class Repository
 * @package CampaignChain\CoreBundle\Module
 */
class Repository
{
    const STATUS_NO_REPOSITORIES = 'No repositories defined';
    const STATUS_NO_MODULES = 'No modules available';


    /**
     * @var bool
     */
    private $env;

    private $repositories;

    private $distributionVersion;

    /**
     * @var Package
     */
    private $packageService;

    /**
     * @param Package $packageService
     * @var SystemService
     */
    private $systemService;

    public function __construct(
        SystemService $systemService,
        Package $packageService,
        $env = 'prod'
    )
    {
        $this->systemService = $systemService;
        $this->packageService = $packageService;
        $this->env = $env;
    }

    public function loadRepositories()
    {
        $system = $this->systemService->getActiveSystem();

        if($this->env == 'dev'){
            if(isset($system->getModules()['repositories-dev'])){
                $this->repositories = $system->getModules()['repositories-dev'];
            } else {
                $this->repositories = $system->getModules()['repositories'];
            }
        } else {
            $this->repositories = $system->getModules()['repositories'];
        }

        $this->distributionVersion = $system->getVersion();

        return !is_null($this->repositories) &&
                is_array($this->repositories) &&
                count($this->repositories);
    }

    /**
     * Retrieve compatible modules from repositories.
     *
     * @return array
     */
    public function getModulesFromRepository()
    {
        $modules = array();

        $system = $this->systemService->getActiveSystem();

        foreach($this->repositories as $repository){
            // Retrieve compatible modules.
            $client = new Client([
                'base_uri' => $repository,
            ]);
            $response = $client->get('d/'.$system->getPackage().'/'.$this->distributionVersion.'.json');
            $compatibleModules = json_decode($response->getBody());
            // TODO: What to do if same module exists in different repositories?
            $modules = array_merge($modules, $compatibleModules->results);
        }

        if(count($modules) === 0){
            return self::STATUS_NO_MODULES;
        }

        return $modules;
    }

    /**
     * Get module versions from CampaignChain.
     *
     * @return array|string
     */
    public function getAll()
    {
        if (!$this->loadRepositories()) {
            return Repository::STATUS_NO_REPOSITORIES;
        }

        $modules = $this->getModulesFromRepository();

        // Is a higher version of an already installed package available?
        foreach ($modules as $key => $module) {
            $version = $this->packageService->getVersion($module->name);

            if (!$version) {
                // Not installed at all.
                unset($modules[$key]);
            } elseif (version_compare($version, $module->version, '<')) {
                // Older version installed.
                $modules[$key]->hasUpdate = true;
                $modules[$key]->versionInstalled = $version;
            } else {
                $modules[$key]->hasUpdate = false;
                $modules[$key]->versionInstalled = $version;
            }
        }

        return $modules;
    }

    /**
     * Get modules that needs to be installed.
     *
     * @return array|string
     */
    public function getInstalls()
    {
        if (!$this->loadRepositories()) {
            return Repository::STATUS_NO_REPOSITORIES;
        }

        $modules = $this->getModulesFromRepository();

        // Is the package already installed? If yes, is a higher version available?
        foreach ($modules as $key => $module) {
            $version = $this->packageService->getVersion($module->name);
            // Not installed yet.
            if ($version) {
                // Older version installed.
                unset($modules[$key]);
            }
        }

        return $modules;
    }

    /**
     * Get updates from CampaignChain.
     *
     * @return array|string
     */
    public function getUpdates()
    {
        if (!$this->loadRepositories()) {
            return Repository::STATUS_NO_REPOSITORIES;
        }

        $modules = $this->getModulesFromRepository();

        // Is a higher version of an already installed package available?
        foreach ($modules as $key => $module) {
            $version = $this->packageService->getVersion($module->name);

            if (!$version) {
                // Not installed at all.
                unset($modules[$key]);
            } elseif (version_compare($version, $module->version, '<')) {
                // Older version installed.
                $modules[$key]->versionInstalled = $version;
            } else {
                unset($modules[$key]);
            }
        }

        return $modules;
    }
}