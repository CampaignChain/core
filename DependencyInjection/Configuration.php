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

namespace CampaignChain\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('campaignchain_core');

        $rootNode
            ->children()
                ->enumNode('env')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->values(array('prod', 'dev', 'demo', 'test'))
                ->end()
                ->arrayNode('tracking')
                    ->children()
                        ->scalarNode('id_name')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('cctid')
                        ->end()
                        ->scalarNode('js_route')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('/tracking.js')
                        ->end()
                        ->enumNode('js_mode')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->values(array('prod', 'dev', 'dev-stay'))
                        ->end()
                        ->scalarNode('js_class')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('CCTracking')
                        ->end()
                        ->scalarNode('js_init')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('cc')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('upload_storage')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('path')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('%kernel.root_dir%/../web/storage')
                        ->end()
                        ->scalarNode('url_prefix')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue("/storage")
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
