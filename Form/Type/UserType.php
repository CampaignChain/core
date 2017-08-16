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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

class UserType extends AbstractType
{
    private $formats = array();

    public function __construct($formats){
        $this->formats = $formats['formats'];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Create the sample date and time data to be displayed
        // in the select form fields
        $now = new \DateTime();
        // Set user's timezone
        //$now->setTimezone(new \DateTimeZone($options['data']->getTimezone()));
        // Format output according to user's locale
        $localeFormat = new \IntlDateFormatter(
            $options['data']->getLocale(),
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            (new \DateTimeZone($options['data']->getTimezone()))->getName()
        );

        if(
            is_array($this->formats['date']) && count($this->formats['date'])
            &&
            is_array($this->formats['time']) && count($this->formats['time'])
        ){
            foreach($this->formats['date'] as $dateFormat){
                // Display format with current date to user
                $localeFormat->setPattern($dateFormat);
                $dateFormats[$dateFormat] = $localeFormat->format($now);
            }
            foreach($this->formats['time'] as $timeFormat){
                // Display format with current date to user
                $localeFormat->setPattern($timeFormat);
                $timeFormats[$timeFormat] = $localeFormat->format($now);
            }
        }

        $builder
            ->add('username', 'text', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                )
            ))
            ->add('email', 'email', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Email(),
                )
            ))
            ->add('firstName', 'text', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                )
            ))
            ->add('lastName', 'text', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                )
            ))
            ->add('timezone', 'timezone')
            ->add('avatarImage', new AvatarUploadType(), array(
                'label' => 'Profile image'
            ))
        ;

        if ($options['new']) {
            $builder->add('password', 'repeated', array(
                'required'        =>  true,
                'type'            => 'password',
                'first_name'      => 'password',
                'second_name'     => 'password_again',
                'invalid_message' => 'The password fields must match.',
            ));
        }

//        $builder
//            ->add('language', 'language', array(
//                'data' => 'en_US',
//                'disabled' => true
//            ))
//            ->add('locale', 'locale', array(
//                'data' => 'en_US',
//                'disabled' => true,
//            ))
//            ->add('currency', 'currency', array(
//                'data' => 'USD',
//                'disabled' => true
//            ))
//            ->add('dateFormat', 'choice', array(
//                'data' => 'yyyy-MM-dd',
//                'disabled' => true,
//                'label' => 'Date Format',
//                'choices'   => $dateFormats,
//                'multiple'  => false,
//             ))
//            ->add('timeFormat', 'choice', array(
//                'data' => 'HH:mm:ss',
//                'disabled' => true,
//                'label' => 'Time Format',
//                'choices'   => $timeFormats,
//                'multiple'  => false,
//            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CampaignChain\CoreBundle\Entity\User',
            'new' => false,
            'constraints' => [
                new UniqueEntity([
                    'fields' => ['email'],
                    'errorPath' => 'email',
                    'message' => 'E-Mail already exists'
                ]),
                new UniqueEntity([
                    'fields' => ['username'],
                    'errorPath' => 'username',
                    'message' => 'Username already exists'
                ])
            ],
        ));
    }

    public function getBlockPrefix()
    {
        return 'campaignchain_core_user';
    }
}
