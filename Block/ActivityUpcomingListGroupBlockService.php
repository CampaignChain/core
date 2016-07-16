<?php
/*
 * Copyright 2016 CampaignChain, Inc. <info@campaignchain.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CampaignChain\CoreBundle\Block;

use CampaignChain\CoreBundle\EntityService\ActivityService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Validator\ErrorElement;

use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\BaseBlockService;

class ActivityUpcomingListGroupBlockService extends BaseBlockService
{
    protected $service;

    public function setService(ActivityService $service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'List of upcoming Activities';
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

        $activities = $this->service->getUpcomingActivities(array('limit' => $settings['limit']));

        return $this->renderResponse($blockContext->getTemplate(), array(
            'activities'     => $activities,
            'block'     => $blockContext->getBlock(),
            'settings'  => $settings
        ), $response);
    }
}
