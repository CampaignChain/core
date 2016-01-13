<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Wizard\Install\Step;

use Sensio\Bundle\DistributionBundle\Configurator\Step\StepInterface;
use CampaignChain\CoreBundle\Wizard\Install\Validator\Constraints as InstallAssert;
use Symfony\Component\Validator\Constraints as Assert;
use CampaignChain\CoreBundle\Wizard\Install\Form\BitlyStepType;
use CampaignChain\CoreBundle\Wizard\Install\Driver\YamlConfig;
use Symfony\Component\Filesystem\Filesystem;
use CampaignChain\CoreBundle\Util\CommandUtil;

/**
 * @Assert\GroupSequence({"BitlyStep", "CheckToken"})
 */
class BitlyStep implements StepInterface
{
    /**
     * @Assert\NotBlank
     * @InstallAssert\IsValidBitlyToken(groups={"CheckToken"})
     */
    public $access_token;

    private $context;

    private $command;

    public function setContext(array $context){
        $this->context = $context;
    }

    public function setServices(CommandUtil $command){
        $this->command = $command;
    }

    public function setParameters(array $parameters)
    {
        if(!isset($parameters['access_token'])){
            $this->access_token = null;
        } else {
            $this->access_token = $parameters['access_token'];
        }
    }

    /**
     * @see StepInterface
     */
    public function getFormType()
    {
        return new BitlyStepType();
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
        return array('bitly_access_token' => $data->access_token);
    }

    /**
     * @see StepInterface
     */
    public function getTemplate()
    {
        return 'CampaignChainCoreBundle:Wizard/Install/Step:bitly.html.twig';
    }

    public function execute($parameters){
        $this->command->createBitlyAccessToken($parameters);
    }
}
