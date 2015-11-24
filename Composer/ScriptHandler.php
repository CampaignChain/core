<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Composer;

use Symfony\Component\Filesystem\Filesystem;
use Composer\Script\CommandEvent;
use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler as SensioScriptHandler;

class ScriptHandler extends SensioScriptHandler
{
    /**
     * Asks if the new directory structure should be used, installs the structure if needed.
     *
     * @param CommandEvent $event
     */
    public static function initApp(CommandEvent $event)
    {
        $campaignchainBundlesKernel = 'app'.DIRECTORY_SEPARATOR.'campaignchain_bundles.php';
        $symfonyConfigDir = 'app'.DIRECTORY_SEPARATOR.'config';
        $campaignchainBundlesConfig = $symfonyConfigDir.
            DIRECTORY_SEPARATOR.'campaignchain'.
            DIRECTORY_SEPARATOR.'config_bundles.yml';
        $routingFile = $symfonyConfigDir.DIRECTORY_SEPARATOR.'routing.yml';
        $campaignchainBundlesSecurity = $symfonyConfigDir.
            DIRECTORY_SEPARATOR.'campaignchain'.
            DIRECTORY_SEPARATOR.'security.yml';

        $fs = new Filesystem();

        if(!$fs->exists($campaignchainBundlesKernel)){
            $fs->copy($campaignchainBundlesKernel.'.dist', $campaignchainBundlesKernel, true);
        }
        if(!$fs->exists($campaignchainBundlesConfig)){
            $fs->copy($campaignchainBundlesConfig.'.dist', $campaignchainBundlesConfig, true);
        }
        if(!$fs->exists($routingFile)){
            $fs->copy($routingFile.'.dist', $routingFile, true);
        }
        if(!$fs->exists($campaignchainBundlesSecurity)){
            $fs->copy($campaignchainBundlesSecurity.'.dist', $campaignchainBundlesSecurity, true);
        }
    }

    public static function registerModules(CommandEvent $event)
    {
        $options = self::getOptions($event);
        $consoleDir = self::getConsoleDir($event, 'register modules');

        if (null === $consoleDir) {
            return;
        }

        self::executeCommand($event, $consoleDir, 'campaignchain:module:update --class-only', $options['process-timeout']);
        self::executeCommand($event, $consoleDir, 'campaignchain:module:update --config-only', $options['process-timeout']);
        self::executeCommand($event, $consoleDir, 'campaignchain:module:update --routing-only', $options['process-timeout']);
    }
}
