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
use Doctrine\ORM\EntityRepository;

class MilestoneType extends HookListenerType
{
    private $campaign;

    public function setCampaign($campaign){
        $this->campaign = $campaign;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'attr' => array('placeholder' => 'What should be the name of the Milestone?')
            ));

        // Campaign cannot be changed when editing a milestone.
        if($options['data']->getId()){
            $builder
                ->add('campaign', 'text', array(
                    'data' => $options['data']->getCampaign()->getName(),
                    'read_only'  => true,
                    'mapped' => false,
                ));
        } else {
//            $campaignService = $this->get('campaignchain.core.campaign');
//            $campaigns = $campaignService->getAllCampaigns();

            $builder
                ->add('campaign', 'entity', array(
                    'label' => 'Campaign',
                    'class' => 'CampaignChainCoreBundle:Campaign',
                    'query_builder' => function(EntityRepository $er) {
                            return $er->createQueryBuilder('campaign')
                                ->where('campaign.endDate > :now')
                                ->orderBy('campaign.startDate', 'ASC')
                                ->setParameter('now', new \DateTime('now'));
                        },
                    'property' => 'name',
                    'empty_value' => 'Select a Campaign',
                    'empty_data' => null,
                ));
        }

        $hookListener = $this->getHookListener($builder);
        if($options['data']->getId()){
            $hookListener->setCampaign($options['data']->getCampaign());
        }

        // Embed hook forms.
        $builder->addEventSubscriber($hookListener);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CampaignChain\CoreBundle\Entity\Milestone',
        ));
    }


    public function getName()
    {
        return 'campaignchain_core_milestone';
    }
}