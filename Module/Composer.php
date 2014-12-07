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

use Symfony\Component\Process\Process;

class Composer
{
    private $root;
    private $logger;

    public function __construct($kernelRootDir, $logger)
    {
        $this->root = $kernelRootDir.
                        DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
        $this->logger = $logger;
    }

    public function requirePackages(array $packages)
    {
        if(!count($packages)){
            return false;
        }

        $packagesArg = '';

        foreach($packages as $package){
            $packagesArg .= $package['name'].':'.$package['version'].' ';
        }

        /*
         * TODO: Enhance for multiple repositories.
         *
         * Check whether the package and repository actually exist and that
         * the package exists in the repository.
         */

        ob_start();
        $currentDir = getcwd();
        chdir($this->root);
        system('composer require '.$packagesArg, $output);
        chdir($currentDir);
        $this->logger->info(ob_get_clean());

        // TODO: Check if new package is in lock file.
    }
}