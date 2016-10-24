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

use CampaignChain\CoreBundle\Util\DateTimeUtil;
use CampaignChain\TextareaCountFormTypeBundle\Form\Type\TextareaCountType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CampaignType extends HookListenerType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var DateTimeUtil $dateTimeUtil */
        $dateTimeUtil = $this->container->get('campaignchain.core.util.datetime');
        $builder
            ->add('name', 'text', array(
                'attr' => array(
                    'placeholder' => 'Give your campaign a name',
                )
            ))
            ->add('description', TextareaCountType::class, array(
                'label' => 'Description',
                'required' => false,
                'attr' => array(
                    'placeholder' => 'What is the campaign about?',
                    'maxlength' => 1000,
                ),
            ))
            ->add('timezone', 'timezone', array(
                'label' => 'Timezone of Audience',
                'data' => $dateTimeUtil->getUserTimezone(),
            ));

        $hookListener = $this->getHookListener($builder);

        // Embed hook forms.
        $builder->addEventSubscriber($hookListener);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CampaignChain\CoreBundle\Entity\Campaign',
        ));
    }


    public function getName()
    {
        if ($this->view == 'rest') {
            return 'campaign';
        } else {
            return 'campaignchain_core_campaign';
        }
    }
}