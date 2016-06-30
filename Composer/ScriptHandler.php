<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Composer;

use CampaignChain\CoreBundle\Util\SystemUtil;
use Symfony\Component\Filesystem\Filesystem;
use Composer\Script\CommandEvent;
use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler as SensioScriptHandler;

class ScriptHandler extends SensioScriptHandler
{
    public static function enableInstallMode(CommandEvent $event)
    {
        SystemUtil::enableInstallMode();

        $event->getIO()->write('Enabled CampaignChain install mode.');
    }

    /**
     * Asks if the new directory structure should be used, installs the structure if needed.
     *
     * @param CommandEvent $event
     */
    public static function initApp(CommandEvent $event)
    {
        SystemUtil::initApp();
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

    public static function initPlatformsh(CommandEvent $event)
    {
        $isPlatformsh = getenv("PLATFORM_RELATIONSHIPS");
        if (!$isPlatformsh) {
            return;
        }

        $paramsFile = SystemUtil::getRootDir().'app/config/parameters.yml';

        $paramsContent = file_get_contents($paramsFile);
        $paramsContent .= "\$imports:";
        $paramsContent .= "\$\xA- { resource: parameters_platformsh.php }";

        $fs = new Filesystem();
        $fs->dumpFile($paramsFile, $paramsContent);

        $event->getIO()->write('Initialized Platform.sh configuration.');
    }
}
