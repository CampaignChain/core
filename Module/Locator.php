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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

class Locator
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var Bundle[]
     */
    private $availableBundles;

    /**
     * @var Package
     * campaignchain.core.module.package
     */
    private $packageService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Locator constructor.
     * @param $rootDir
     * @param Package $package
     * @param EntityManager $entityManager
     */
    public function __construct($rootDir, Package $package, EntityManager $entityManager)
    {
        $this->rootDir = $rootDir.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
        $this->packageService = $package;
        $this->entityManager = $entityManager;
    }

    /**
     * @return Bundle[]
     */
    public function getAvailableBundles()
    {
        if ($this->availableBundles) {
            return $this->availableBundles;
        }

        $finder = new Finder();
        // Find all the CampaignChain module configuration files.
        $finder->files()
            ->in($this->rootDir.DIRECTORY_SEPARATOR)
            ->exclude('app')
            ->exclude('bin')
            ->exclude('component')
            ->exclude('web')
            ->name('campaignchain.yml');

        $bundles = [];

        foreach ($finder as $moduleConfig) {
            $bundleComposer = $this->rootDir.DIRECTORY_SEPARATOR.str_replace(
                    'campaignchain.yml',
                    'composer.json',
                    $moduleConfig->getRelativePathname()
                );
            if ($bundle = $this->getBundle($bundleComposer)) {
                $bundles[] = $bundle;
            }
        }

        $this->availableBundles = $bundles;

        return $bundles;
    }

    /**
     * Return only new or need to be updated Bundles
     * skipVersion == false
     *
     * @return Bundle[]
     */
    public function getNewBundles()
    {
        $bundles = $this->getAvailableBundles();
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
     * @param string $bundleComposer
     *
     * @return bool|Bundle
     */
    private function getBundle($bundleComposer)
    {
        if (!file_exists($bundleComposer)) {
            return false;
        }

        $bundleComposerData = file_get_contents($bundleComposer);

        $normalizer = new GetSetMethodNormalizer();
        $normalizer->setIgnoredAttributes(array(
            'require',
            'keywords',
        ));
        $encoder = new JsonEncoder();
        $serializer = new Serializer(array($normalizer), array($encoder));
        $bundle = $serializer->deserialize($bundleComposerData,'CampaignChain\CoreBundle\Entity\Bundle','json');

        // Set the version of the installed bundle.
        $version = $this->packageService->getVersion($bundle->getName());

        /*
         * If version does not exist, this means two things:
         *
         * 1) Either, it is a package in require-dev of composer.json, but
         * CampaignChain is not in dev mode. Then we don't add this package.
         *
         * 2) Or it is a bundle in Symfony's src/ directory. Then we want to
         * add it.
         */
        if(!$version){
            // Check if bundle is in src/ dir.
            $bundlePath = str_replace($this->rootDir.DIRECTORY_SEPARATOR, '', $bundleComposer);
            if(strpos($bundlePath, 'src'.DIRECTORY_SEPARATOR) !== 0){
                // Not in src/ dir, so don't add this bundle.
                return false;
            } else {
                $version = 'dev-master';
            }
        }

        $bundle->setVersion($version);

        // Set relative path of bundle.
        $bundle->setPath(
        // Remove the root directory to get the relative path
            str_replace($this->rootDir.DIRECTORY_SEPARATOR, '',
                // Remove the composer file from the path
                str_replace(DIRECTORY_SEPARATOR.'composer.json', '', $bundleComposer)
            )
        );

        return $bundle;
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