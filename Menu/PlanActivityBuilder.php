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

namespace CampaignChain\CoreBundle\Menu;

use CampaignChain\CoreBundle\Entity\Campaign;
use CampaignChain\CoreBundle\Entity\Module;
use CampaignChain\CoreBundle\EntityService\CampaignService;
use CampaignChain\CoreBundle\EntityService\ModuleService;
use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class PlanActivityBuilder extends ContainerAware
{
    public function navbar(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $menu->addChild('New', array(
                'label' => '.icon-plus New Activity',
                'route' => 'campaignchain_core_activities_new')
        );

        $menu->addChild(
            'Open',
            array(
                'label' => '.icon-calendar Open Activities',
                'route' => 'campaignchain_core_plan_activities'
            )
        );

        $menu->addChild(
            'All',
            array(
                'label' => '.icon-table All Activities',
                'route' => 'campaignchain_core_activities'
            )
        );

        return $menu;
    }
}