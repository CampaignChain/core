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
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LocationType extends HookListenerType
{
    private $helpText = false;

    public function setHelpText($helpText){
        $this->helpText = $helpText;
    }

    /*
     * @todo Take default image from location module if no image path provided.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if($this->view == 'read_only'){
            $readOnly = true;
        } else {
            $readOnly = false;
        }

        $builder->add('name', 'text', array(
            'label' => 'Name',
//            'data' => $options['data']->getName(),
            'read_only' => $readOnly,
        ));

        if($this->view != 'hide_url'){
            $builder->add('URL', 'url', array(
                'label' => 'URL',
//                'data' => $options['data']->getUrl(),
                'read_only' => $readOnly,
            ));
        }

        if($this->view == 'checkbox'){
            $builder->add('selected', 'checkbox', array(
                'label'     => 'Add "'.$options['data']->getName().'" as location',
                'required'  => true,
                'data' => true,
                'mapped' => false,
                'attr' => array(
                    'align_with_widget' => true,
                ),
            ));
        }

        $hookListener = $this->getHookListener($builder);

        // Embed hook forms.
        $builder->addEventSubscriber($hookListener);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CampaignChain\CoreBundle\Entity\Location',
        ));
    }

    public function getName()
    {
        return 'campaignchain_location';
    }
}