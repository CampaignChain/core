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

use CampaignChain\CoreBundle\Entity\Bundle;
use Doctrine\ORM\EntityManager;

class BundleConfig
{
    /**
     * @var BundleLocator
     */
    private $bundleLocatorService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * BundleConfig constructor.
     * @param EntityManager $entityManager
     * @param BundleLocator $bundleLocatorService
     */
    public function __construct(BundleLocator $bundleLocatorService, EntityManager $entityManager)
    {
        $this->bundleLocatorService = $bundleLocatorService;
        $this->entityManager = $entityManager;
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
                    $registeredBundle = $this->entityManager
                        ->getRepository('CampaignChainCoreBundle:Bundle')
                        ->findOneByName($bundle->getName());
                    // Update the existing bundle's data.
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
        $registeredBundle = $this->entityManager
            ->getRepository('CampaignChainCoreBundle:Bundle')
            ->findOneByName($newBundle->getName());

        if (!$registeredBundle){
            // This case covers development of modules.
            return Installer::STATUS_REGISTERED_NO;
        }

        if ($registeredBundle->getVersion() == 'dev-master' && $newBundle->getVersion() == 'dev-master') {
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