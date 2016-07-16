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
