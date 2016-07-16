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
use Guzzle\Http\Client;

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
     * @var SystemService
     */
    private $systemService;

    public function __construct(SystemService $systemService, $env = 'prod')
    {
        $this->env = $env;
        $this->systemService = $systemService;
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
    public function getModules()
    {
        $modules = array();

        $system = $this->systemService->getActiveSystem();

        foreach($this->repositories as $repository){
            // Retrieve compatible modules.
            $client = new Client($repository);
            $request = $client->get('d/'.$system->getPackage().'/'.$this->distributionVersion.'.json');
            $response = $request->send();
            $compatibleModules = json_decode($response->getBody(true));
            // TODO: What to do if same module exists in different repositories?
            $modules = array_merge($modules, $compatibleModules->results);
        }

        if(count($modules) === 0){
            return self::STATUS_NO_MODULES;
        }

        return $modules;
    }

    public function isModule($name, $repository)
    {

    }
}