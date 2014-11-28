<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Wizard\Install\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class AdminStepType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('user', 'text', array(
                'data' => 'admin',
                'read_only'  => true,
                'mapped' => false,
            ))
            ->add('first_name', 'text')
            ->add('last_name', 'text')
            ->add('password', 'repeated', array(
            'required'        => false,
            'type'            => 'password',
            'first_name'      => 'password',
            'second_name'     => 'password_again',
            'invalid_message' => 'The password fields must match.',
            ))
            ->add('email', 'email')
            ->add('support', 'checkbox', array(
                'label'     => 'Free: 30 days product support',
                'required'  => false,
                'data'     => true,
                'attr' => array(
                    'help_text' => 'Get one month of free engineering support and important product updates from CampaignChain. ',
                ),
            ));
    }

    public function getName()
    {
        return 'campaignchain_core_install_step_admin';
    }
}