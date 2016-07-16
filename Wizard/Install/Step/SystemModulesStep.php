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

namespace CampaignChain\CoreBundle\Wizard\Install\Step;

use CampaignChain\CoreBundle\Module\Installer;
use CampaignChain\CoreBundle\Util\CommandUtil;
use CampaignChain\CoreBundle\Wizard\Install\Form\SystemModulesStepType;
use Sensio\Bundle\DistributionBundle\Configurator\Step\StepInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SystemModulesStep implements StepInterface
{
    private $context;

    private $command;
    private $modulesInstaller;

    public function setContext(array $context){
        $this->context = $context;
    }

    public function setServices(CommandUtil $command, Installer $modulesInstaller){
        $this->command = $command;
        $this->modulesInstaller = $modulesInstaller;
    }

    public function setParameters(array $parameters)
    {
    }

    /**
     * @see StepInterface
     */
    public function getFormType()
    {
        return new SystemModulesStepType();
    }

    /**
     * @see StepInterface
     */
    public function checkRequirements()
    {
        return array();
    }

    /**
     * checkOptionalSettings
     */
    public function checkOptionalSettings()
    {
        return array();
    }

    /**
     * @see StepInterface
     */
    public function update(StepInterface $data)
    {
        return array();
    }

    /**
     * @see StepInterface
     */
    public function getTemplate()
    {
        return 'CampaignChainCoreBundle:Wizard/Install/Step:system_modules.html.twig';
    }

    public function execute($parameters){
        // Load schemas of entities into database
        $this->command->doctrineSchemaUpdate();

        // Install CampaignChain modules
        $this->modulesInstaller->install();
    }
}
