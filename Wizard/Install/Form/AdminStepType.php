<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Wizard\Install\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class AdminStepType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('user', 'text', array(
                'data' => 'admin',
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
            ->add('email', 'email');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'campaignchain_core_install_step_admin';
    }
}