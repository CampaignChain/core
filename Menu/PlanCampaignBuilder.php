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

class PlanCampaignBuilder extends ContainerAware
{
    public function navbar(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $menu->addChild('New', array(
                'label' => '.icon-plus New Campaign',
                'route' => 'campaignchain_core_campaign_new')
        );

        $menu->addChild(
            'Open',
            array(
                'label' => '.icon-clock-o Open Campaigns',
                'route' => 'campaignchain_core_plan_campaigns'
            )
        );

        $menu->addChild(
            'All',
            array(
                'label' => '.icon-table All Campaigns',
                'route' => 'campaignchain_core_campaign'
            )
        );

        $menu->addChild(
            'Templates',
            array(
                'label' => '.icon-file-o Campaign Templates',
                'route' => 'campaignchain_core_plan_templates'
            )
        );

//        $menuTimelines = $menu->addChild(
//            'Timelines',
//            array(
//                'label' => '.icon-clock-o Timelines',
//            )
//        )
//            ->addChild('TimelinesAllOpen', array(
//                'label' => 'Open Campaigns',
//                'route' => 'campaignchain_core_plan_campaigns'
//            ));
//
//        /** @var ModuleService $moduleService */
//        $moduleService = $this->container->get('campaignchain.core.module');
//        $modules = $moduleService->getModulesByType('campaign');
//        if(count($modules)){
//            /** @var Module $module */
//            foreach ($modules as $module){
//                $moduleRoutes = $module->getRoutes();
//                if(is_array($moduleRoutes) && isset($moduleRoutes['plan'])) {
//                    $menuTimelines->addChild(
//                        $module->getDisplayName(),
//                        array(
//                            'label' => $module->getDisplayName(),
//                            'route' => $module->getRoutes()['plan']
//                        )
//                    );
//                }
//            }
//        }

        return $menu;
    }
}