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

use Doctrine\ORM\EntityManager;
use Guzzle\Http\Client;

class Repository
{
    const STATUS_NO_REPOSITORIES = 'No repositories defined';
    const STATUS_NO_MODULES = 'No modules available';

    private $em;

    private $repositories;

    private $distributionVersion;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function loadRepositories()
    {
        $system = $this->em->getRepository('CampaignChainCoreBundle:System')->find(1);
        $this->repositories = $system->getModules()['repositories'];
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

        foreach($this->repositories as $repository){
            // Retrieve compatible modules.
            $client = new Client($repository);
            $request = $client->get('distribution/ce/'.$this->distributionVersion.'.json');
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