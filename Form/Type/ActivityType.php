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
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ActivityType extends HookListenerType
{
    private $campaign;
    private $operationForms;
    private $showNameField = true;

    public function setOperationForms(array $operationForms){
        $this->operationForms = $operationForms;
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
        if(is_array($this->operationForms) && count($this->operationForms)){
            foreach($this->operationForms as $form){
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

        $hookListener = $this->getHookListener($builder);
        $hookListener->setCampaign($this->campaign);

        // Embed hook forms.
        $builder->addEventSubscriber($hookListener);
        $builder->add('assignee', 'campaignchain_hook_campaignchain_assignee');
    }

    public function configureOptions(OptionsResolver $resolver)
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