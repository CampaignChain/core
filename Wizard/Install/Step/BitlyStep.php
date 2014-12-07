<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Wizard\Install\Step;

use Sensio\Bundle\DistributionBundle\Configurator\Step\StepInterface;
use Symfony\Component\Validator\Constraints as Assert;
use CampaignChain\CoreBundle\Wizard\Install\Form\BitlyStepType;
use CampaignChain\CoreBundle\Wizard\Install\Driver\YamlConfig;

class BitlyStep implements StepInterface
{
    /**
     * @Assert\NotBlank
     */
    public $access_token;

    private $context;

    public function setContext(array $context){
        $this->context = $context;
    }

    protected function getConfigFilePath()
    {
        return '..'.DIRECTORY_SEPARATOR.
        'vendor'.DIRECTORY_SEPARATOR.'campaignchain'.DIRECTORY_SEPARATOR.
        'core'.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.
        'config'.DIRECTORY_SEPARATOR.'config.yml';
    }

    public function setParameters(array $parameters)
    {
        $yamlConfig = new YamlConfig($this->context['kernel_dir'], $this->getConfigFilePath());
        $parameters = $yamlConfig->read();

        if (array_key_exists('hpatoio_bitly', $parameters) &&
            array_key_exists('access_token', $parameters['hpatoio_bitly'])) {
            $this->access_token = $parameters['hpatoio_bitly']['access_token'];

            if ('insert_here_your_bitly_access_token' == $this->access_token) {
                $this->access_token = '';
            }
        } else {
            $this->access_token = '';
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
        return array('hpatoio_bitly' => array('access_token' => $data->access_token));
    }

    /**
     * @see StepInterface
     */
    public function getTemplate()
    {
        return 'CampaignChainCoreBundle:Wizard/Install/Step:bitly.html.twig';
    }

    public function execute($parameters){
        $yamlConfig = new YamlConfig($this->context['kernel_dir'], $this->getConfigFilePath());
        $yamlConfig->write($parameters);
        $yamlConfig->clean();
    }
}
