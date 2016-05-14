<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
        return self::getRootDir().'app/config/campaignchain/.install';
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
        $symfonyConfigDir = self::getRootDir().'app'.DIRECTORY_SEPARATOR.
            'config';

        $configFiles['bundles'] = self::getRootDir().'app'.DIRECTORY_SEPARATOR.
            'campaignchain_bundles.php';
        $configFiles['config'] = $symfonyConfigDir.DIRECTORY_SEPARATOR.
            'campaignchain'.DIRECTORY_SEPARATOR.
            'config_bundles.yml';
        $configFiles['routing'] = $symfonyConfigDir.DIRECTORY_SEPARATOR.
            'routing.yml';
        $configFiles['security'] = $symfonyConfigDir.DIRECTORY_SEPARATOR.
            'campaignchain'.DIRECTORY_SEPARATOR.
            'security.yml';

        return $configFiles;
    }

    /**
     * Creates the CampaignChain app configuration files based on the default
     * files or overwrites existing ones with the default.
     */
    public static function initApp()
    {
        $configFiles = self::getConfigFiles();

        $fs = new Filesystem();

        if(!$fs->exists($configFiles['bundles'])){
            $fs->copy($configFiles['bundles'].'.dist', $configFiles['bundles'], true);
        }
        if(!$fs->exists($configFiles['config'])){
            $fs->copy($configFiles['config'].'.dist', $configFiles['config'], true);
        }
        if(!$fs->exists($configFiles['routing'])){
            $fs->copy($configFiles['routing'].'.dist', $configFiles['routing'], true);
        }
        if(!$fs->exists($configFiles['security'])){
            $fs->copy($configFiles['security'].'.dist', $configFiles['security'], true);
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