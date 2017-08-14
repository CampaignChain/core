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

namespace CampaignChain\CoreBundle\Controller;

use CampaignChain\CoreBundle\Module\Repository;
use CampaignChain\CoreBundle\Util\CommandUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ModuleController extends Controller
{

    const BLOCKUI_WAIT_MESSAGE = 'Downloading and registering packages.<br/>This might take a while.</br>Please do not close the browser window.';

    public function indexAction(Request $request)
    {
        $this->processSelectedModules($request);

        $moduleRepositoryService = $this->get('campaignchain.core.module.repository');
        $modules = $moduleRepositoryService->getAll();

        if(
            $modules == Repository::STATUS_NO_REPOSITORIES ||
            $modules == Repository::STATUS_NO_MODULES
        ){
            $updates = 0;
            $installs = 0;
            if($modules == Repository::STATUS_NO_REPOSITORIES){
                $this->get('session')->getFlashBag()->add(
                    'warning',
                    'No repositories defined yet.'
                );
            }
        } else {
            $updates = count($moduleRepositoryService->getUpdates());
            $installs = count($moduleRepositoryService->getInstalls());
        }

        if($updates == 0) {
            $this->get('session')->getFlashBag()->add(
                'info',
                'No updates available.'
            );
        }

        return $this->render(
            'CampaignChainCoreBundle:System/Module:index.html.twig', array(
            'page_title' => 'Modules',
            'modules' => $modules,
            'updates' => $updates,
            'installs' => $installs,
            'blockui_wait_message' => self::BLOCKUI_WAIT_MESSAGE,
        ));
    }

    public function newAction(Request $request)
    {
        $this->processSelectedModules($request);

        $moduleRepositoryService = $this->get('campaignchain.core.module.repository');
        $modules = $moduleRepositoryService->getInstalls();

        if(
            $modules == Repository::STATUS_NO_REPOSITORIES ||
            $modules == Repository::STATUS_NO_MODULES
        ){
            $updates = 0;
            $installs = 0;
        } else {
            $updates = count($moduleRepositoryService->getUpdates());
            $installs = count($modules);
        }

        return $this->render(
            'CampaignChainCoreBundle:System/Module:new.html.twig', array(
            'page_title' => 'Modules',
            'modules' => $modules,
            'updates' => $updates,
            'installs' => $installs,
            'blockui_wait_message' => self::BLOCKUI_WAIT_MESSAGE,
        ));
    }

    protected function processSelectedModules($request)
    {
        $selectedModules = $request->get('modules');
        $selectedVersions = $request->get('versions');
        if(is_array($selectedModules) && count($selectedModules)){
            $composerService = $this->get('campaignchain.core.module.composer');
            foreach($selectedModules as $key => $selectedModule){
                $requiredPackages[] = array(
                    'name' => $selectedModule,
                    'version' => $selectedVersions[$key],
                );
            }

            /** @var LoggerInterface $logger */
            $logger = $this->get('logger');

            /** @var CommandUtil $command */
            $command = $this->get('campaignchain.core.util.command');
            
            // Have composer download the required packages including the modules.
            $composerService->installPackages($requiredPackages);

            $logger->info('Installed new packages', $requiredPackages);

            // Load schemas of entities into database
            $output = $command->schemaUpdate();

            $logger->info('Output of campaignchain:schema:update');
            $logger->info($output);

            // Register the modules with CampaignChain.
            $moduleInstaller = $this->get('campaignchain.core.module.installer');
            $moduleInstaller->install();

            // Load schemas of entities into database
            $output = $command->clearCache(false);
            $logger->info('Output of cache:clear --no-warmup');
            $logger->info($output);

            // Install assets to web/ directory and dump assetic files.
            $output = $command->assetsInstallWeb();
            $logger->info('Output of assets:install web');
            $logger->info($output);

            // bin/console assetic:dump --no-debug
            $output = $command->asseticDump();
            $logger->info('Output of assetic:dump --no-debug');
            $logger->info($output);

            // Load schemas of entities into database
            $output = $command->schemaUpdate();
            $logger->info('Output of campaignchain:schema:update');
            $logger->info($output);

            // Install or update bower JavaScript libraries.
            $output = $command->bowerInstall();
            $logger->info('Output of sp:bower:install');
            $logger->info($output);

            // Run the update routines which might come with modified or new modules.


            /*
             * This is a hack to avoid that an error about a missing bundle for a
             * route will be shown after installing the modules.
             *
             * By redirecting without calling the Symfony router component, we can
             * avoid the above issue.
             *
             * TODO: Fix this in a proper way :)
             */
            header('Location: /modules/');
            exit;
        }
    }
}