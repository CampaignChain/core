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

use Symfony\Component\Process\Process;

class Composer
{
    private $root;
    private $commandUtil;
    private $logger;

    public function __construct($kernelRootDir, $commandUtil, $logger)
    {
        $this->root = $kernelRootDir.
            DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
        $this->commandUtil = $commandUtil;
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
        $command = 'composer require '.$packagesArg;
        $this->logger->info('Output of: '.$command.' '.$packagesArg);
        $this->logger->info($this->commandUtil->shell($command));

        $command = 'composer update -n --optimize-autoloader';
        $this->logger->info('Output of: '.$command.' '.$packagesArg);
        $this->logger->info($this->commandUtil->shell($command));

        // TODO: Check if new package is in lock file.
    }
}
