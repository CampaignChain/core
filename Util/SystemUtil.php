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

namespace CampaignChain\CoreBundle\Util;

use Symfony\Component\Filesystem\Filesystem;

class SystemUtil
{
    static function getRootDir()
    {
        return __DIR__.'/../../../../';
    }

    static function getInstallFilePath()
    {
        return self::getRootDir().'app/campaignchain/.install';
    }

    /**
     * Checks whether system is in install mode.
     */
    static function redirectInstallMode()
    {
        // Only apply if in request context.
        if(isset($_SERVER['REQUEST_URI'])) {
            if (
                self::isInstallMode() &&
                false === strpos($_SERVER['REQUEST_URI'], '/install/')
            ) {
                // System is not installed yet and user wants to access
                // a secured page. Hence, redirect to Installation Wizard.
                header('Location: /campaignchain/install.php');
                exit;
            } elseif (
                // System is installed and user wants to access the Installation
                // Wizard. Hence, redirect to login page.
                !self::isInstallMode() &&
                0 === strpos($_SERVER['REQUEST_URI'], '/install/')
            ) {
                header('Location: /');
                exit;
            }
        }
    }

    static function enableInstallMode()
    {
        $fs = new Filesystem();

        $fs->dumpFile(self::getInstallFilePath(), '');
    }

    static function disableInstallMode()
    {
        $fs = new Filesystem();
        $fs->remove(self::getInstallFilePath());
    }

    static function isInstallMode()
    {
        return file_exists(self::getInstallFilePath());
    }

    /**
     * Returns an array with the absolute path of all configuration files
     * of the CampaignChain app.
     *
     * @return array
     */
    public static function getConfigFiles()
    {
        $configFiles = array();
        $symfonyConfigDir =
            self::getRootDir().'app'.DIRECTORY_SEPARATOR.
            'config';
        $campaignchainConfigDir =
            self::getRootDir().'app'.DIRECTORY_SEPARATOR.
            'campaignchain'.DIRECTORY_SEPARATOR.
            'config';

        $configFiles['kernel_symfony'] =
            self::getRootDir().'app'.DIRECTORY_SEPARATOR.'AppKernel.php';
        
        $configFiles['bundles_dist'] = self::getRootDir().'app'.DIRECTORY_SEPARATOR.
            'AppKernel_campaignchain.php';
        $configFiles['bundles'] = self::getRootDir().'app'.DIRECTORY_SEPARATOR.
            'campaignchain'.DIRECTORY_SEPARATOR.
            'AppKernel.php';
        
        $configFiles['config_dist'] = $symfonyConfigDir.DIRECTORY_SEPARATOR.
            'config_campaignchain_imports.yml.dist';
        $configFiles['config'] = $campaignchainConfigDir.DIRECTORY_SEPARATOR.
            'imports.yml';
        
        $configFiles['routing_dist'] = $symfonyConfigDir.DIRECTORY_SEPARATOR.
            'routing_campaignchain.yml.dist';
        $configFiles['routing'] = $campaignchainConfigDir.DIRECTORY_SEPARATOR.
            'routing.yml';
        
        $configFiles['security_dist'] = $symfonyConfigDir.DIRECTORY_SEPARATOR.
            'security_campaignchain.yml.dist';
        $configFiles['security'] = $campaignchainConfigDir.DIRECTORY_SEPARATOR.
            'security.yml';

        return $configFiles;
    }

    /**
     * Creates the CampaignChain app configuration files based on the default
     * files or overwrites existing ones with the default.
     */
    public static function initConfig()
    {
        $configFiles = self::getConfigFiles();

        $fs = new Filesystem();

        if(!$fs->exists($configFiles['bundles'])){
            $fs->copy($configFiles['bundles_dist'], $configFiles['bundles'], true);
        }
        if(!$fs->exists($configFiles['config'])){
            $fs->copy($configFiles['config_dist'], $configFiles['config'], true);
        }
        if(!$fs->exists($configFiles['routing'])){
            $fs->copy($configFiles['routing_dist'], $configFiles['routing'], true);
        }
        if(!$fs->exists($configFiles['security'])){
            $fs->copy($configFiles['security_dist'], $configFiles['security'], true);
        }
    }

    /**
     * Puts the CampaignChain app into same status as after a fresh
     * <code>
     * composer create-project
     * </code>
     * installation.
     */
    public static function resetApp()
    {
        // Set install mode.
        SystemUtil::enableInstallMode();

        $currentDir = getcwd();
        chdir(self::getRootDir());
        ob_start();
        // Drop all tables
        $command = 'php app/console doctrine:schema:drop --force --full-database';
        system($command, $output);
        // Run composer post install command.
        $command = 'composer run-script post-install-cmd';
        system($command, $output);
        ob_get_clean();
        chdir($currentDir);
    }
}