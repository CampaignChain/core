<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

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

            ))
        ;

        if ($options['new']) {
            $builder->add('password', 'repeated', array(
                'required'        => false,
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
        ));
    }

    public function getName()
    {
        return 'campaignchain_core_user';
    }
}
