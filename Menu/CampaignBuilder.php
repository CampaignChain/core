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

class CampaignBuilder extends ContainerAware
{
    public function planListTab(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $menu->addChild('Timeline', array('route' => 'campaignchain_campaign_scheduled_plan_timeline'));
        $menu->addChild('Calendar', array('route' => 'campaignchain_campaign_scheduled_plan_calendar'));

        return $menu;
    }

    public function detailsTab(FactoryInterface $factory, array $options)
    {
        $id = $this->container->get('request')->get('id');
        /** @var CampaignService $campaignService */
        $campaignService = $this->container->get('campaignchain.core.campaign');
        /** @var Campaign $campaign */
        $campaign = $campaignService->getCampaign($id);
        $routes = $campaign->getCampaignModule()->getRoutes();

        $menu = $factory->createItem('root');

        $menu->addChild(
            'Edit',
            array(
                'label' => '.icon-pencil-square Edit',
                'route' => $routes['edit'],
                'routeParameters' => array(
                    'id' => $id
                )
            )
        );
        $menu->addChild(
            'Timeline',
            array(
                'route' => $routes['plan_detail'],
                'label' => '.icon-clock-o Timeline',
                'routeParameters' => array(
                    'id' => $id
                )
            )
        );


        $menuCopy = $menu->addChild(
            'Copy',
            array(
                'label' => '.icon-files-o Copy as',
                'routeParameters' => array(
                    'id' => $id
                )
            )
        );
        /** @var ModuleService $moduleService */
        $moduleService = $this->container->get('campaignchain.core.module');
        $copyAsCampaignModules = $moduleService->getCopyAsCampaignModules($id);
        if(count($copyAsCampaignModules)){
            /** @var Module $module */
            foreach ($copyAsCampaignModules as $module){
                $menuCopy->addChild(
                    $module->getDisplayName(),
                    array(
                        'label' => $module->getDisplayName(),
                        'route' => $module->getRoutes()['copy'],
                        'routeParameters' => array(
                            'id' => $id
                        )
                    )
                );
            }
        }

        return $menu;
    }
}