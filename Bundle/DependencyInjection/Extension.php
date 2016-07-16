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

namespace CampaignChain\CoreBundle\Bundle\DependencyInjection;

use CampaignChain\CoreBundle\Util\VariableUtil;
use Symfony\Component\HttpKernel\DependencyInjection\Extension as SymfonyExtension;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
abstract class Extension extends SymfonyExtension
{
    public function setParameters(ContainerBuilder $container, $config){
        $configStrings = VariableUtil::arrayConcatenate($config);
        foreach ($configStrings as $name => $node) {
            $container->setParameter($this->getAlias().'.'.$name, $node);
        }
    }

    public function getAlias()
    {
        $className = get_class($this);

        if (substr($className, -9) != 'Extension') {
            throw new BadMethodCallException('This extension does not follow the naming convention; you must overwrite the getAlias() method.');
        }

        $classBaseName = substr(strrchr($className, '\\'), 1, -9);

        $alias = str_replace('campaign_chain', 'campaignchain', Container::underscore($classBaseName));

        return $alias;
    }
}
