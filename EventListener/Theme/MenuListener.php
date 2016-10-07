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

namespace CampaignChain\CoreBundle\EventListener\Theme;

use Avanzu\AdminThemeBundle\Event\KnpMenuEvent;

class MenuListener
{
    public function onSetupMenu(KnpMenuEvent $event)
    {
        $menu = $event->getMenu();

        /*
         * Plan
         */
        $menu->addChild('Plan', [
                'label' => 'PLAN',
                'childOptions' => $event->getChildOptions()
            ]
        )
            ->setLabelAttribute('icon', 'fa fa-calendar')
            ->setChildrenAttribute('class', 'treeview-menu');
        $menu->getChild('Plan')->addChild('PlanCampaigns', [
                'route' => 'campaignchain_core_plan',
                'label' => 'Campaigns',
                'childOptions' => $event->getChildOptions()
            ]
        )
            ->setLabelAttribute('icon', 'fa fa-circle-o');
        $menu->getChild('Plan')->addChild('PlanActivities', [
                'route' => 'campaignchain_core_plan_activities',
                'label' => 'Activities',
                'childOptions' => $event->getChildOptions()
            ]
        )->setLabelAttribute('icon', 'fa fa-circle-o');
        $menu->getChild('Plan')->addChild('PlanCampaignTemplates', [
                'route' => 'campaignchain_core_plan_templates',
                'label' => 'Templates',
                'childOptions' => $event->getChildOptions()
            ]
        )->setLabelAttribute('icon', 'fa fa-circle-o');

        /*
         * Execute
         */
        $menu->addChild('Execute', [
                'label' => 'EXECUTE',
                'route' => 'campaignchain_core_execute',
                'childOptions' => $event->getChildOptions()
            ]
        )
            ->setLabelAttribute('icon', 'fa fa-dashboard');

        /*
         * Monitor
         */
        $menu->addChild('Monitor', [
                'label' => 'MONITOR',
                'route' => 'campaignchain_core_report',
                'childOptions' => $event->getChildOptions()
            ]
        )
            ->setLabelAttribute('icon', 'fa fa-bar-chart');
    }
}