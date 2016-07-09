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

use CampaignChain\CoreBundle\Util\SystemUtil;
use Sensio\Bundle\DistributionBundle\Configurator\Step\StepInterface;
use CampaignChain\CoreBundle\Util\CommandUtil;
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
        return new AdminStepType();
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
