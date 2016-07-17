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
use Doctrine\ORM\EntityManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

class BundleLocator
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
     * Locator constructor.
     * @param $rootDir
     * @param Package $package
     * @param EntityManager $entityManager
     */
    public function __construct($rootDir, Package $package)
    {
        $this->rootDir = $rootDir.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
        $this->packageService = $package;
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
        $finder->files()->contains('"type": "campaignchain-')
            ->in($this->rootDir.DIRECTORY_SEPARATOR)
            ->exclude('app')
            ->exclude('bin')
            ->exclude('component')
            ->exclude('web')
            ->name('composer.json');

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
}