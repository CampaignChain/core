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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;

class MilestoneType extends HookListenerType
{
    private $campaign;

    public function setCampaign($campaign){
        $this->campaign = $campaign;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'attr' => array('placeholder' => 'What should be the name of the Milestone?')
            ));

        // Campaign cannot be changed when editing a milestone.
        $builder
            ->add('campaign', 'text', array(
                'data' => $this->campaign->getName(),
                'read_only'  => true,
                'mapped' => false,
            ));

        $hookListener = $this->getHookListener($builder);
        $hookListener->setCampaign($this->campaign);

        // Embed hook forms.
        $builder->addEventSubscriber($hookListener);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CampaignChain\CoreBundle\Entity\Milestone',
        ));
    }

    public function getName()
    {
        return 'campaignchain_core_milestone';
    }
}