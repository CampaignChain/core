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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ModuleController extends Controller
{

    const BLOCKUI_WAIT_MESSAGE = 'Downloading and registering packages.<br/>This might take a while.</br>Please do not close the browser window.';

    public function indexAction(Request $request)
    {
        $this->processSelectedModules($request);

        $installerService = $this->get('campaignchain.core.module.installer');
        $modules = $installerService->getAll();

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
            $updates = count($installerService->getUpdates());
            $installs = count($installerService->getInstalls());
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

        $installerService = $this->get('campaignchain.core.module.installer');
        $modules = $installerService->getInstalls();

        if(
            $modules == Repository::STATUS_NO_REPOSITORIES ||
            $modules == Repository::STATUS_NO_MODULES
        ){
            $updates = 0;
            $installs = 0;
        } else {
            $updates = count($installerService->getUpdates());
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

            // Have composer download the required packages including the modules.
            $composerService->installPackages($requiredPackages);

            // Register the modules with CampaignChain.
            $moduleInstaller = $this->get('campaignchain.core.module.installer');
            $moduleInstaller->install();

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