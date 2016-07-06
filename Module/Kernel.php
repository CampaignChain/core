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
use CampaignChain\CoreBundle\Util\CommandUtil;
use CampaignChain\CoreBundle\Util\SystemUtil;
use CampaignChain\CoreBundle\Util\VariableUtil;
use CampaignChain\CoreBundle\Wizard\Install\Driver\YamlConfig;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;

class Kernel
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CommandUtil
     */
    private $command;

    /**
     * @var KernelConfig
     */
    private $kernelConfig;

    /**
     * @var array
     */
    private $configFiles;

    /**
     * Kernel constructor.
     * @param CommandUtil $command
     * @param Logger      $logger
     */
    public function __construct(CommandUtil $command, Logger $logger)
    {
        $this->command = $command;
        $this->logger = $logger;
        $this->configFiles = SystemUtil::getConfigFiles();
        $this->kernelConfig = new KernelConfig();
    }

    public function register(
        array $types = [
            'classes' => true,
            'configs' => true,
            'routings' => true,
            'security' => true,
        ]
    )
    {
        if (!$this->kernelConfig) {
            return;
        }

        if (isset($types['classes']) && $types['classes']) {
            $this->registerClasses();
        }
        if (isset($types['configs']) && $types['configs']) {
            $this->registerConfigs();
        }
        if (isset($types['routings']) && $types['routings']) {
            $this->registerRoutings();
        }
        if (isset($types['security']) && $types['security']) {
            $this->registerSecurity();
        }
    }

    /**
     * @param Bundle[] $bundles
     */
    public function parseBundlesForKernelConfig(array $bundles)
    {
        dump($this->configFiles);
        exit;
        foreach ($bundles as $bundle) {
            $extra = $bundle->getExtra();

            if (!$extra || !isset($extra['campaignchain'])) {
                continue;
            }
            if (isset($extra['campaignchain']['kernel'])) {
                $this->kernelConfig->addClasses($extra['campaignchain']['kernel']['classes']);
                $bundle->setClass($extra['campaignchain']['kernel']['classes'][0]);
            }

            if (isset($extra['campaignchain']['kernel']['routing'])) {
                $this->kernelConfig->addRouting($extra['campaignchain']['kernel']['routing']);
            }

            // Register the bundle's config.yml file.
            $configFile = $this->rootDir.$bundle->getPath().DIRECTORY_SEPARATOR.
                'Resources'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.
                'config.yml';
            $this->registerConfigurationFile($configFile);

            // Register the bundle's security.yml file.
            $securityFile = $this->rootDir.$bundle->getPath().DIRECTORY_SEPARATOR.
                'Resources'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.
                'security.yml';
            $this->registerConfigurationFile($securityFile, 'security');
        }
    }

    /**
     * @param string $configFile
     * @param string $type
     */
    private function registerConfigurationFile($configFile, $type = 'config')
    {
        if (!file_exists($configFile)) {
            return;
        }

        $symfonyRoot = $this->rootDir;
        // Make sure that even on Windows, the directory separator
        // is "/".
        if (DIRECTORY_SEPARATOR == '\\') {
            $symfonyRoot = str_replace(DIRECTORY_SEPARATOR, '/', $this->rootDir);
            $configFile = str_replace(DIRECTORY_SEPARATOR, '/', $configFile);
        }

        switch ($type) {
            case 'config':
                $configFile = '../../../'.
                    str_replace($symfonyRoot, '', $configFile);
                $this->kernelConfig->addConfig($configFile);
                break;
            case 'security':
                $this->kernelConfig->addSecurity($configFile);
                break;
        }
    }

    /**
     * Register bundles
     */
    protected function registerClasses()
    {
        $campaignchainBundlesContent = file_get_contents($this->configFiles['bundles']);
        $symfonyBundlesContent = file_get_contents($this->configFiles['kernel_symfony']);

        $hasNewBundles = false;

        $classes = $this->kernelConfig->getClasses();

        if (!count($classes)) {
            return;
        }

        foreach ($classes as $class) {
            // Check if the bundle is already registered in the kernel.
            if (
                strpos($campaignchainBundlesContent, $class) === false &&
                strpos($symfonyBundlesContent, $class) === false
            ) {
                $hasNewBundles = true;

                // Add the bundle class path to the CampaignChain bundles registry file.
                $campaignchainContentBundle = "\$bundles[] = new ".$class."();";

                $campaignchainBundlesContent .= "\xA".$campaignchainContentBundle;
            }
        }

        if (!$hasNewBundles) {
            return;
        }

        $fs = new Filesystem();
        $fs->dumpFile($this->configFiles['bundles'], $campaignchainBundlesContent);

    }

    /**
     * Register bundle's config.yml files
     */
    protected function registerConfigs()
    {
        $yamlConfig = new YamlConfig('', $this->configFiles['config']);
        $parameters = $yamlConfig->read();

        $hasNewConfigs = false;

        $configs = $this->kernelConfig->getConfigs();

        if (!count($configs)) {
            return;
        }

        foreach ($configs as $config) {
            // Check if the config is already being imported.
            if ($this->recursiveArraySearch($config, $parameters['imports']) === false) {
                $hasNewConfigs = true;

                // Add the config to the imports
                $parameters['imports'][]['resource'] = $config;
            }
        }

        if (!$hasNewConfigs) {
            return;
        }

        $yamlConfig = new YamlConfig('', $this->configFiles['config']);
        $yamlConfig->write($parameters);
        $yamlConfig->clean();
    }

    /**
     * Register bundle's security.yml files
     */
    protected function registerSecurity()
    {
        /*
         * Re-create the security.yml file to avoid duplicates in merged array
         * that occur upon multiple parsing.
         */
        $fs = new Filesystem();
        if(!$fs->exists($this->configFiles['security'])){
            $fs->copy(
                $this->configFiles['security_dist'],
                $this->configFiles['security'],
                true
            );
        }

        $yamlConfig = new YamlConfig('', $this->configFiles['security_dist']);
        $appParameters = $yamlConfig->read();

        // Read content of all security.yml files and merge the arrays.
        $securityFiles = $this->kernelConfig->getSecurities();
        if (!count($securityFiles)) {
            return;
        }

        foreach ($securityFiles as $securityFile) {
            $yamlConfig = new YamlConfig('', $securityFile);
            $bundleParameters = $yamlConfig->read();
            $appParameters = VariableUtil::arrayMerge($bundleParameters, $appParameters);
        }

        $yamlConfig = new YamlConfig('', $this->configFiles['security']);
        $yamlConfig->write($appParameters, 5);
        $yamlConfig->clean();
    }

    /**
     * Register bundle's  routing.yml files
     */
    protected function registerRoutings()
    {
        $yamlConfig = new YamlConfig('', $this->configFiles['routing']);
        $parameters = $yamlConfig->read();

        $hasNewRoutings = false;

        $routings = $this->kernelConfig->getRoutings();

        if (!count($routings)) {
            return false;
        }

        foreach ($routings as $routing) {
            // Check if the routing is already defined.
            if (isset($parameters[$routing['name']])) {
                continue;
            }

            $hasNewRoutings = true;

            // Add the config to the imports
            $parameters[$routing['name']] = array(
                'resource' => $routing['resource'],
                'prefix' => $routing['prefix'],
            );
        }

        if (!$hasNewRoutings) {
            return;
        }

        $yamlConfig = new YamlConfig('', $this->configFiles['routing']);
        $yamlConfig->write($parameters);
        $yamlConfig->clean();
    }

    private function recursiveArraySearch($needle, $haystack) {
        if (!is_array($haystack) || !count($haystack)) {
            return false;
        }

        foreach ($haystack as $key => $value) {
            $currentKey = $key;
            if ($needle === $value || (is_array($value) && $this->recursiveArraySearch($needle, $value) !== false)) {
                return $currentKey;
            }
        }

        return false;
    }
}