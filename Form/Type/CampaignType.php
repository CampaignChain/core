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

use CampaignChain\CoreBundle\Util\DateTimeUtil;
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
            ->add('description', 'textarea', array(
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