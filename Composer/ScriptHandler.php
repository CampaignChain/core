<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Composer;

use Symfony\Component\Filesystem\Filesystem;
use Composer\Script\CommandEvent;

class ScriptHandler
{
    /**
     * Asks if the new directory structure should be used, installs the structure if needed.
     *
     * @param CommandEvent $event
     */
    public static function initConfig(CommandEvent $event)
    {
        $symfonyConfigDir = 'app'.DIRECTORY_SEPARATOR.'config';
        $campaignchainBundlesFile = $symfonyConfigDir.DIRECTORY_SEPARATOR.'campaignchain_bundles.yml';
        $routingFile = $symfonyConfigDir.DIRECTORY_SEPARATOR.'routing.yml';

        $fs = new Filesystem();
        if(!$fs->exists($campaignchainBundlesFile)){
            $fs->copy($campaignchainBundlesFile.'.dist', $campaignchainBundlesFile, true);
        }
        if(!$fs->exists($routingFile)){
            $fs->copy($routingFile.'.dist', $routingFile, true);
        }
    }
}
