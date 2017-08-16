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

use CampaignChain\CoreBundle\Entity\Theme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

class ThemeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'label' => 'Organization Name',
            ))
            ->add('logo', 'file', array(
                'label' => 'Logo',
                'data_class' => null,
                'data' => Theme::STORAGE_PATH.'logo.png',
                'required' => false,
                'attr' => array(
                    'help_text' => 'Upload a PNG file with maximum width 260px, maximum height 48px.'
                )
            ))
            ->add('favicon', 'file', array(
                'label' => 'Favicon',
                'data_class' => null,
                'data' => Theme::STORAGE_PATH.'favicon.ico',
                'required' => false,
                'attr' => array(
                    'help_text' => 'Upload an .ico file with either 16px or 32px width and height.'
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CampaignChain\CoreBundle\Entity\Theme',
        ));
    }

    public function getBlockPrefix()
    {
        return 'campaignchain_core_theme';
    }
}
