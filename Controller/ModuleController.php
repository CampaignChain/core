<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Controller;

use CampaignChain\CoreBundle\Module\Repository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ModuleController extends Controller
{
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
            $composerService->requirePackages($requiredPackages);

            // Register the modules with CampaignChain.
            $moduleInstaller = $this->get('campaignchain.core.module.installer');
            $moduleInstaller->install();
        }
    }
}