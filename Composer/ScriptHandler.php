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

namespace CampaignChain\CoreBundle\Composer;

use CampaignChain\CoreBundle\Util\SystemUtil;
use Composer\Script\Event;
use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler as SensioScriptHandler;

class ScriptHandler extends SensioScriptHandler
{
    /**
     * Creates the configuration files for CampaignChain's kernel.
     *
     * @param Event $event
     */
    public static function initKernel(Event $event)
    {
        SystemUtil::initKernel();

        $event->getIO()->write('CampaignChain: Created configuration files.');
    }

    public static function enableInstallMode(Event $event)
    {
        if(!file_exists(SystemUtil::getInstallDoneFilePath())) {
            SystemUtil::enableInstallMode();
        }

        $event->getIO()->write('CampaignChain: Enabled install mode.');
    }
}
