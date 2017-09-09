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
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class HookType extends AbstractType
{
    protected $campaign;
    protected $view;
    protected $hooksOptions = array();

    public function setCampaign($campaign){
        $this->campaign = $campaign;
    }

    public function setView($view){
        $this->view = $view;
    }

    public function setHooksOptions(array $hooksOptions){
        $this->hooksOptions = $hooksOptions;
    }

    public function setOptions($options)
    {
        if(isset($options['view'])){
            $this->setView($options['view']);
        }
        if(isset($options['campaign'])){
            $this->setCampaign($options['campaign']);
        }
        if(isset($options['hooks_options'])){
            $this->setHooksOptions($options['hooks_options']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'view' => 'default',
            'campaign' => null,
            'hooks_options' => null,
        ));
    }
}