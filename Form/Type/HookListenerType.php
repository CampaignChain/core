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

namespace CampaignChain\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class HookListenerType extends AbstractType
{
    protected $view = 'default';
    protected $bundleName;
    protected $moduleIdentifier;
    protected $hooksOptions = array();

    protected $em;
    protected $container;

    public function __construct(ManagerRegistry $managerRegistry, ContainerInterface $container)
    {
        $this->em = $managerRegistry->getManager();
        $this->container = $container;
    }

    public function setBundleName($bundleName){
        $this->bundleName = $bundleName;
    }

    public function setModuleIdentifier($moduleIdentifier){
        $this->moduleIdentifier = $moduleIdentifier;
    }

    public function setView($view){
        $this->view = $view;
    }

    public function setHooksOptions(array $hooksOptions){
        $this->hooksOptions = $hooksOptions;
    }

    public function setDefaultOptions($options)
    {
        if(isset($options['view'])){
            $this->setView($options['view']);
        }
        if(isset($options['bundle_name'])){
            $this->setBundleName($options['bundle_name']);
        }
        if(isset($options['module_identifier'])){
            $this->setModuleIdentifier($options['module_identifier']);
        }
        if(isset($options['hooks_options'])){
            $this->setHooksOptions($options['hooks_options']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'view' => 'default',
            'bundle_name' => null,
            'module_identifier' => null,
            'hooks_options' => null,
        ));
    }

    public function getHookListener($builder)
    {
        $hookListener = $this->container->get('campaignchain.core.listener.hook');
        // Initialize the hooks for this campaign.
        $hookListener->init(
            $builder,
            $this->bundleName,
            $this->moduleIdentifier
        );
        $hookListener->setView($this->view);
        $hookListener->setHooksOptions($this->hooksOptions);

        return $hookListener;
    }
}