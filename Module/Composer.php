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
