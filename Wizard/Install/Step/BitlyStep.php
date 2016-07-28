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
