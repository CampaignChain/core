<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ActivityType extends HookListenerType
{
    private $campaign;
    private $operationForms;

    public function setOperationForms(array $operationForms){
        $this->operationForms = $operationForms;
    }

    public function setCampaign($campaign){
        $this->campaign = $campaign;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'label' => 'Activity Name',
                'attr' => array('placeholder' => 'What should be the name of the Activity?')
            ));

        if(is_array($this->operationForms) && count($this->operationForms)){
            foreach($this->operationForms as $form){
                $builder
                    ->add($form['identifier'], $form['form'], array(
                        'mapped' => false,
                        'label' => $form['label'],
                    ));
            }
        }

        $hookListener = $this->getHookListener($builder);
        $hookListener->setCampaign($this->campaign);

        // Embed hook forms.
        $builder->addEventSubscriber($hookListener);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CampaignChain\CoreBundle\Entity\Activity',
        ));
    }

    public function getName()
    {
        return 'campaignchain_core_activity';
    }
}