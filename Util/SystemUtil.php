<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Util;

use Symfony\Component\Filesystem\Filesystem;

class SystemUtil
{
    static function getInstallFilePath()
    {
        return __DIR__.'/../../../../app/config/campaignchain/.install';
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
}