<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Block;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Validator\ErrorElement;

use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\BaseBlockService;

/**
 *
 * @author     Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class ActivityUpcomingListGroupBlockService extends BaseBlockService
{
    protected $container;

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'List of Activities';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultSettings(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'title'    => 'Upcoming Activities',
            'limit'      => 5,
            'sort'    => 'DESC',
            'template' => 'CampaignChainCoreBundle:Block:activity_upcoming_listgroup.html.twig',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
        $formMapper->add('settings', 'sonata_type_immutable_array', array(
            'keys' => array(
                array('limit', 'int', array('required' => false)),
                array('sort', 'text', array('required' => false)),
            )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function validateBlock(ErrorElement $errorElement, BlockInterface $block)
    {
        $errorElement
            ->with('settings[limit]')
                ->assertNotNull(array())
                ->assertNotBlank()
            ->end()
            ->with('settings[sort]')
                ->assertNotNull(array())
                ->assertNotBlank()
                ->assertMaxLength(array('limit' => 4))
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        // merge settings
        $settings = $blockContext->getSettings();

        $activityService = $this->container->get('campaignchain.core.activity');
        $activities = $activityService->getUpcomingActivities(array('limit' => $settings['limit']));

        return $this->renderResponse($blockContext->getTemplate(), array(
            'activities'     => $activities,
            'block'     => $blockContext->getBlock(),
            'settings'  => $settings
        ), $response);
    }
}
