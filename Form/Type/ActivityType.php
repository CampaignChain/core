<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
        if(is_array($this->contentForms) && count($this->contentForms)){
            foreach($this->contentForms as $form){
                $form['form']->setActivityModule($options['data']->getActivityModule());
                $builder
                    ->add($form['identifier'], $form['form'], array(
                        'mapped' => false,
                        'label' => false,
                        'attr' => array(
                            'widget_col' => 12,
                        ),
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
                'property' => 'id'
                ))
                ->add('campaign', 'entity', array(
                    'class' => 'CampaignChain\CoreBundle\Entity\Campaign',
                    'property' => 'id'
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
                        'property' => 'id'
                    ));

                    $event->setData($activity);
                });
        }

        $hookListener = $this->getHookListener($builder);
        $hookListener->setCampaign($this->campaign);

        // Embed hook forms.
        $builder->addEventSubscriber($hookListener);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CampaignChain\CoreBundle\Entity\Activity',
        ));
    }

    public function getName()
    {
        if($this->view == 'rest'){
            return 'activity';
        }

        return 'campaignchain_core_activity';
    }
}