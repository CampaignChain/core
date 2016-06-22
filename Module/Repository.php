<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    private $devMode;

    private $repositories;

    private $distributionVersion;

    /**
     * @var SystemService
     */
    private $systemService;

    public function __construct(SystemService $systemService, $devMode = false)
    {
        $this->devMode = $devMode;
        $this->systemService = $systemService;
    }

    public function loadRepositories()
    {
        $system = $this->systemService->getActiveSystem();

        if($this->devMode){
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