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
use Composer\Script\CommandEvent;
use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler as SensioScriptHandler;

class ScriptHandler extends SensioScriptHandler
{
    public static function enableInstallMode(CommandEvent $event)
    {
        SystemUtil::enableInstallMode();

        $event->getIO()->write('CampaignChain: Enabled install mode.');
    }

    /**
     * Asks if the new directory structure should be used, installs the structure if needed.
     *
     * @param CommandEvent $event
     */
    public static function initConfig(CommandEvent $event)
    {
        SystemUtil::initConfig();

        $event->getIO()->write('CampaignChain: Created configuration files.');
    }

    public static function registerModules(CommandEvent $event)
    {
        $options = self::getOptions($event);
        $consoleDir = self::getConsoleDir($event, 'register modules');

        if (null === $consoleDir) {
            return;
        }

        self::executeCommand($event, $consoleDir, 'campaignchain:kernel:update --class-only --config-only --routing-only', $options['process-timeout']);
        $event->getIO()->write('CampaignChain: Registered modules.');
    }
}
