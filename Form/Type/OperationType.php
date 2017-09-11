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

use CampaignChain\CoreBundle\Entity\Location;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class OperationType extends AbstractType
{
    protected $view = 'default';
    protected $em;
    protected $container;
    protected $location;
    protected $activityModule;

    public function __construct(ManagerRegistry $managerRegistry, ContainerInterface $container)
    {
        $this->em = $managerRegistry->getManager();
        $this->container = $container;
    }

    public function setView($view){
        $this->view = $view;
    }

    public function setLocation(Location $location){
        $this->location = $location;
    }

    public function setActivityModule($activityModule)
    {
        $this->activityModule = $activityModule;
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $this->setOptions($options);

        if($this->location){
            $view->vars['location'] = $this->location;
        } elseif(isset($options['data'])) {
            $view->vars['location'] = $options['data']->getOperation()->getActivity()->getLocation();
        }
        if(!isset($options['data']) || !$view->vars['location']){
            $view->vars['activity_module'] = $this->activityModule;
        }
    }

    public function setOptions($options)
    {
        if(isset($options['view'])){
            $this->setView($options['view']);
        }
        if(isset($options['location'])){
            $this->setLocation($options['location']);
        }
        if(isset($options['activity_module'])){
            $this->setActivityModule($options['activity_module']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'view' => null,
            'location' => null,
            'activity_module' => null,
        ));
    }
}