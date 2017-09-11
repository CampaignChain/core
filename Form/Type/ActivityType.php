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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityType extends HookListenerType
{
    private $campaign;
    private $contentForms;
    private $showNameField = true;

    public function setContentForms(array $contentForms){
        $this->contentForms = $contentForms;
    }

    public function setCampaign($campaign){
        $this->campaign = $campaign;
    }

    public function showNameField($showNameField)
    {
        $this->showNameField = $showNameField;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->setOptions($options);

        if(is_array($this->contentForms) && count($this->contentForms)){
            foreach($this->contentForms as $form){
                $builder
                    ->add($form['identifier'], $form['options']['class'], array(
                        'activity_module' => $options['data']->getActivityModule(),
                        'mapped' => false,
                        'label' => false,
                        'attr' => array(
                            'widget_col' => 12,
                        ),
                        'data' => $form['options']['data'],
                        'location' => $form['options']['location'],
                    ));
            }
        }

        if($this->showNameField){
            $builder
                ->add('name', 'text', array(
                    'label' => 'Activity Name',
                    'attr' => array('placeholder' => 'What should be the name of the Activity?')
                ));
        } else {
            $builder
                ->add('name', 'hidden');
        }

        if($this->view == 'rest'){
            $builder
                ->add('location', 'entity', array(
                'class' => 'CampaignChain\CoreBundle\Entity\Location',
                'choice_label' => 'id'
                ))
                ->add('campaign', 'entity', array(
                    'class' => 'CampaignChain\CoreBundle\Entity\Campaign',
                    'choice_label' => 'id'
                ))
                ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                    $activity = $event->getData();
                    $form = $event->getForm();

                    if (!$activity) {
                        return;
                    }

                    // Assign Channel based on given Location ID.
                    $channelService = $this->container->get('campaignchain.core.channel');
                    $channel = $channelService->getChannelByLocation($activity['location']);
                    $activity['channel'] = $channel->getId();

                    $form->add('channel', 'entity', array(
                        'class' => 'CampaignChain\CoreBundle\Entity\Channel',
                        'choice_label' => 'id'
                    ));

                    $event->setData($activity);
                });
        }

        $hookListener = $this->getHookListener($builder);
        $hookListener->setCampaign($this->campaign);

        // Embed hook forms.
        $builder->addEventSubscriber($hookListener);
    }

    public function setOptions($options)
    {
        parent::setOptions($options);

        if(isset($options['campaign'])){
            $this->setCampaign($options['campaign']);
        }
        if(isset($options['content_forms'])){
            $this->setContentForms($options['content_forms']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'CampaignChain\CoreBundle\Entity\Activity',
            'campaign' => null,
            'content_forms' => null,
        ));
    }

    public function getBlockPrefix()
    {
        if($this->view == 'rest'){
            return 'activity';
        }

        return 'campaignchain_core_activity';
    }
}