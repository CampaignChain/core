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

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CampaignType extends HookListenerType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'attr' => array(
                    'placeholder' => 'Give your campaign a name',
                )
            ))
            ->add('timezone', 'timezone');

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
        return 'campaignchain_core_campaign';
    }
}