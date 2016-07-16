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

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LocationType extends HookListenerType
{
    private $helpText = false;

    public function setHelpText($helpText){
        $this->helpText = $helpText;
    }

    /*
     * @todo Take default image from location module if no image path provided.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if($this->view == 'read_only'){
            $readOnly = true;
        } else {
            $readOnly = false;
        }

        $builder->add('name', 'text', array(
            'label' => 'Name',
//            'data' => $options['data']->getName(),
            'read_only' => $readOnly,
        ));

        if($this->view != 'hide_url'){
            $builder->add('URL', 'url', array(
                'label' => 'URL',
//                'data' => $options['data']->getUrl(),
                'read_only' => $readOnly,
            ));
        }

        if($this->view == 'checkbox'){
            $builder->add('selected', 'checkbox', array(
                'label'     => 'Add "'.$options['data']->getName().'" as location',
                'required'  => true,
                'data' => true,
                'mapped' => false,
                'attr' => array(
                    'align_with_widget' => true,
                ),
            ));
        }

        $hookListener = $this->getHookListener($builder);

        // Embed hook forms.
        $builder->addEventSubscriber($hookListener);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CampaignChain\CoreBundle\Entity\Location',
        ));
    }

    public function getName()
    {
        return 'campaignchain_location';
    }
}