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

use CampaignChain\CoreBundle\Util\SystemUtil;
use CampaignChain\CoreBundle\Util\CommandUtil;
use CampaignChain\CoreBundle\Wizard\StepInterface;
use Symfony\Component\Validator\Constraints as Assert;
use CampaignChain\CoreBundle\Wizard\Install\Form\AdminStepType;

class AdminStep implements StepInterface
{
    /**
     * @Assert\NotBlank
     */
    public $first_name;

    /**
     * @Assert\NotBlank
     */
    public $last_name;

    /**
     * @Assert\NotBlank
     */
    public $password;

    /**
     * @Assert\NotBlank
     * @Assert\Email
     */
    public $email;

    /**
     * @Assert\NotBlank
     */
    public $user;

    /**
     * @Assert\NotBlank
     */
    public $timezone = 'UTC';

    private $context;

    private $command;

    public function __construct(array $parameters = array()){}

    public function setContext(array $context){
        $this->context = $context;
    }

    public function setServices(CommandUtil $command){
        $this->command = $command;
    }

    public function setParameters(array $parameters)
    {
        if(!isset($parameters['first_name'])){
            $this->first_name = null;
        } else {
            $this->first_name = $parameters['first_name'];
        }
        if(!isset($parameters['last_name'])){
            $this->last_name = null;
        } else {
            $this->last_name = $parameters['last_name'];
        }
        if(!isset($parameters['first_name'])){
            $this->password = null;
        } else {
            $this->password = $parameters['password'];
        }

        if(!isset($parameters['email'])){
            $this->email = null;
        } else {
            $this->email = $parameters['email'];
        }

        if (!isset($parameters['timezone'])) {
            $this->timezone = 'UTC';
        } else {
            $this->timezone = $parameters['timezone'];
        }
    }

    /**
     * @see StepInterface
     */
    public function getFormType()
    {
        return AdminStepType::class;
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
     * @param StepInterface $data
     * @return array
     */
    public function update(StepInterface $data)
    {
        return array(
            'firstName' => $data->first_name,
            'lastName' => $data->last_name,
            'email' => $data->email,
            'password' => $data->password,
            'username' => $data->user,
            'timezone' => $data->timezone,
        );
    }

    /**
     * @see StepInterface
     */
    public function getTemplate()
    {
        return 'CampaignChainCoreBundle:Wizard/Install/Step:admin.html.twig';
    }

    public function execute($parameters)
    {
        $this->command->createAdminUser($parameters);

        SystemUtil::disableInstallMode();
    }
}
