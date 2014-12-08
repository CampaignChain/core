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

    public function installPackages(array $packages)
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
        $currentDir = getcwd();
        chdir($this->root);

        ob_start();
        $command = 'composer require '.$packagesArg;
        system($command, $output);
        $this->logger->info('Output of: '.$command.' '.$packagesArg);
        $this->logger->info(ob_get_clean());

        ob_start();
        $command = 'composer update -n';
        system($command, $output);
        $this->logger->info('Output of: '.$command.' '.$packagesArg);
        $this->logger->info(ob_get_clean());

        chdir($currentDir);
        // TODO: Check if new package is in lock file.
    }
}