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

use CampaignChain\CoreBundle\Entity\Bundle;
use Doctrine\Common\Persistence\ManagerRegistry;

class BundleConfig
{
    /**
     * @var BundleLocator
     */
    private $bundleLocatorService;

    /**
     * @var Registry
     */
    private $em;

    /**
     * BundleConfig constructor.
     * @param ManagerRegistry $managerRegistry
     * @param BundleLocator $bundleLocatorService
     */
    public function __construct(BundleLocator $bundleLocatorService, ManagerRegistry $managerRegistry)
    {
        $this->bundleLocatorService = $bundleLocatorService;
        $this->em = $managerRegistry->getManager();
    }

    /**
     * Return only new or need to be updated Bundles
     * skipVersion == false
     *
     * @return Bundle[]
     */
    public function getNewBundles()
    {
        $bundles = $this->bundleLocatorService->getAvailableBundles();
        $newBundles = [];

        foreach ($bundles as $bundle) {
            // Check whether this bundle has already been installed
            switch ($this->isRegisteredBundle($bundle)) {
                case Installer::STATUS_REGISTERED_NO:
                    $bundle->setStatus(Installer::STATUS_REGISTERED_NO);
                    $newBundles[] = $bundle;
                    break;

                case Installer::STATUS_REGISTERED_OLDER:
                    // Get the existing bundle.
                    /** @var Bundle $registeredBundle */
                    $registeredBundle = $this->em
                        ->getRepository('CampaignChainCoreBundle:Bundle')
                        ->findOneByName($bundle->getName());
                    // Update the existing bundle's data.
                    $registeredBundle->setType($bundle->getType());
                    $registeredBundle->setDescription($bundle->getDescription());
                    $registeredBundle->setLicense($bundle->getLicense());
                    $registeredBundle->setAuthors($bundle->getAuthors());
                    $registeredBundle->setHomepage($bundle->getHomepage());
                    $registeredBundle->setVersion($bundle->getVersion());
                    $registeredBundle->setExtra($bundle->getExtra());

                    $registeredBundle->setStatus(Installer::STATUS_REGISTERED_OLDER);
                    $newBundles[] = $registeredBundle;
                    break;

                case Installer::STATUS_REGISTERED_SAME:
                    $bundle->setStatus(Installer::STATUS_REGISTERED_SAME);
                    break;
            }
        }

        return $newBundles;
    }
    
    /**
     * @param Bundle $newBundle
     * @return string
     */
    public function isRegisteredBundle(Bundle $newBundle)
    {
        /** @var Bundle $registeredBundle */
        $registeredBundle = $this->em
            ->getRepository('CampaignChainCoreBundle:Bundle')
            ->findOneByName($newBundle->getName());

        if (!$registeredBundle){
            // This case covers development of modules.
            return Installer::STATUS_REGISTERED_NO;
        }

        /*
         * Checking for dev-* ensures that the status is being registered
         * properly not just for dev-master, but also for branches (e.g.
         * dev-campaignchain-42).
         */
        if (
            substr( $registeredBundle->getVersion(), 0, 4 ) === "dev-" &&
            substr( $newBundle->getVersion(), 0, 4 ) === "dev-"
        ) {
            return Installer::STATUS_REGISTERED_OLDER;
        }

        // Bundle with same version is already registered.
        if(version_compare($registeredBundle->getVersion(), $newBundle->getVersion(), '==')){
            return Installer::STATUS_REGISTERED_SAME;
        }

        // Bundle with older version is already registered.
        return Installer::STATUS_REGISTERED_OLDER;
    }
}