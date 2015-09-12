<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
