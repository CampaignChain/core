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

use Sensio\Bundle\DistributionBundle\Configurator\Step\SecretStep;
use CampaignChain\CoreBundle\Wizard\Install\Driver\YamlConfig;

class SfSecretStep extends SecretStep
{
    private $context;

    public function setContext(array $context){
        $this->context = $context;
    }

    public function setParameters(array $parameters)
    {
        $yamlConfig = new YamlConfig($this->context['kernel_dir'], 'config'.DIRECTORY_SEPARATOR.'parameters.yml');
        $parameters = $yamlConfig->read()['parameters'];

        parent::setParameters($parameters);
    }

    public function getTemplate()
    {
        return 'CampaignChainCoreBundle:Wizard/Install/Step:secret.html.twig';
    }

    public function execute($parameters){
        $yamlConfig = new YamlConfig($this->context['kernel_dir'], 'config'.DIRECTORY_SEPARATOR.'parameters.yml');
        $yamlConfig->write(array('parameters' => $parameters));
        $yamlConfig->clean();
    }
}
