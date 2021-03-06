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
use CampaignChain\CoreBundle\Entity\Module;
use CampaignChain\CoreBundle\EntityService\ModuleService;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class MenuListener
{
    /**
     * @var AuthorizationChecker
     */
    protected $authorizationChecker;

    /**
     * @var ModuleService
     */
    protected $moduleService;

    public function __construct(
        AuthorizationChecker $authorizationChecker,
        ModuleService $moduleService
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->moduleService = $moduleService;
    }

    public function onSetupMenu(KnpMenuEvent $event)
    {
        /*
         * Get all dynamic routes from modules.
         */
        // Get all module routes for creating a new Campaign.
        $modules = $this->moduleService->getModulesByType(Module::REPOSITORY_CAMPAIGN);
        /** @var Module $module */
        foreach($modules as $module){
            $extraRoutes['CreateCampaign'][] = $module->getRoutes()['new'];
            $extraRoutes['PlanCampaigns'][] = $module->getRoutes()['edit'];
            $extraRoutes['PlanCampaigns'][] = $module->getRoutes()['plan_detail'];
            if(isset($module->getRoutes()['copy'])){
                $extraRoutes['PlanCampaigns'][] = $module->getRoutes()['copy'];
            }
        }
        $extraRoutes['CreateCampaign'][] = 'campaignchain_core_campaign_new';
        $extraRoutes['PlanCampaigns'][] = 'campaignchain_core_campaign';
        $extraRoutes['PlanCampaigns'][] = 'campaignchain_core_plan_campaigns';
        $extraRoutes['PlanCampaigns'][] = 'campaignchain_core_plan_templates';

        // Get all module routes for creating a new Activity.
        $modules = $this->moduleService->getModulesByType(Module::REPOSITORY_ACTIVITY);
        /** @var Module $module */
        foreach($modules as $module){
            $extraRoutes['CreateActivity'][] = $module->getRoutes()['new'];
            if(isset($module->getRoutes()['edit'])) {
                $extraRoutes['PlanActivities'][] = $module->getRoutes()['edit'];
            }
            if(isset($module->getRoutes()['read'])) {
                $extraRoutes['PlanActivities'][] = $module->getRoutes()['read'];
            }
        }
        $extraRoutes['CreateActivity'][] = 'campaignchain_core_activities_new';
        $extraRoutes['PlanActivities'][] = 'campaignchain_core_plan_activities';
        $extraRoutes['PlanActivities'][] = 'campaignchain_core_activities';

        // Get all module routes for creating a new Milestone.
        $modules = $this->moduleService->getModulesByType(Module::REPOSITORY_MILESTONE);
        /** @var Module $module */
        foreach($modules as $module){
            $extraRoutes['CreateMilestone'][] = $module->getRoutes()['new'];
            if(isset($module->getRoutes()['edit'])) {
                $extraRoutes['PlanMilestones'][] = $module->getRoutes()['edit'];
            }
            if(isset($module->getRoutes()['read'])) {
                $extraRoutes['PlanMilestones'][] = $module->getRoutes()['read'];
            }
        }
        $extraRoutes['CreateMilestone'][] = 'campaignchain_core_milestone_new';
        $extraRoutes['PlanMilestones'][] = 'campaignchain_core_plan_milestones';
        $extraRoutes['PlanMilestones'][] = 'campaignchain_core_milestone';

        // Get all module routes for reports.
        $modules = $this->moduleService->getModulesByType(Module::REPOSITORY_REPORT);
        /** @var Module $module */
        foreach($modules as $module){
            if(isset($module->getRoutes()['index'])) {
                $extraRoutes['Monitor'][] = $module->getRoutes()['index'];
            }
        }
        $extraRoutes['Monitor'][] = 'campaignchain_core_report';

        /*
         * Build menu.
         */
        $menu = $event->getMenu();

        if($this->authorizationChecker->isGranted('ROLE_USER')) {
            /*
             * Connect Channels.
             */
            $menu->addChild('Connect', [
                    'label' => 'CONNECT',
                    'childOptions' => $event->getChildOptions()
                ]
            )
                ->setLabelAttribute('icon', 'fa fa-exchange')
                ->setChildrenAttribute('class', 'treeview-menu');
            $menu->getChild('Connect')->addChild('ConnectLocations', [
                    'route' => 'campaignchain_core_location',
                    'label' => 'Locations',
                    'childOptions' => $event->getChildOptions()
                ]
            )
                ->setLabelAttribute('icon', 'fa fa-circle-o')
                ->setAttribute('data-step', '1');
            $menu->getChild('Connect')
                ->addChild('ConnectChannels', [
                    'route' => 'campaignchain_core_channel',
                    'label' => 'Channels',
                    'childOptions' => $event->getChildOptions()
                    ]
                )
                ->setLabelAttribute('icon', 'fa fa-circle-o');
            $menu->getChild('Connect')
                ->addChild('ConnectTracking', [
                        'route' => 'campaignchain_core_channel_tracking',
                        'label' => 'Tracking',
                        'childOptions' => $event->getChildOptions()
                    ]
                )
                ->setLabelAttribute('icon', 'fa fa-circle-o');

            /*
             * Create Actions.
             */
            $menu->addChild('Create', [
                    'label' => 'CREATE',
                    'childOptions' => $event->getChildOptions(),
                    ]
                )
                ->setLabelAttribute('icon', 'fa fa-plus-square')
                ->setChildrenAttribute('class', 'treeview-menu');

            $menu->getChild('Create')->addChild('CreateCampaign', [
                    'label' => 'Campaign',
                    'route' => 'campaignchain_core_campaign_new',
                    'childOptions' => $event->getChildOptions()
                    ]
                )
                ->setLabelAttribute('icon', 'fa fa-circle-o')
                ->setAttribute('data-step', '2')
                ->setExtra('routes', $extraRoutes['CreateCampaign']);

            $menu->getChild('Create')->addChild('CreateActivity', [
                    'route' => 'campaignchain_core_activities_new',
                    'label' => 'Activity',
                    'childOptions' => $event->getChildOptions()
                    ]
                )
                ->setLabelAttribute('icon', 'fa fa-circle-o')
                ->setAttribute('data-step', '3')
                ->setExtra('routes', $extraRoutes['CreateActivity']);

            $menu->getChild('Create')->addChild('CreateMilestone', [
                    'route' => 'campaignchain_core_milestone_new',
                    'label' => 'Milestone',
                    'childOptions' => $event->getChildOptions()
                    ]
                )
                ->setLabelAttribute('icon', 'fa fa-circle-o')
                ->setExtra('routes', $extraRoutes['CreateMilestone']);

            /*
             * Plan
             */
            $menu->addChild('Plan', [
                    'label' => 'PLAN',
                    'childOptions' => $event->getChildOptions(),
                ]
            )
                ->setLabelAttribute('icon', 'fa fa-calendar')
                ->setChildrenAttribute('class', 'treeview-menu');
            $menu->getChild('Plan')->addChild('PlanCampaigns', [
                    'label' => 'Campaigns',
                    'route' => 'campaignchain_core_plan_campaigns',
                    'childOptions' => $event->getChildOptions()
                ]
            )
                ->setLabelAttribute('icon', 'fa fa-circle-o')
                ->setAttribute('data-step', '4')
                ->setExtra('routes', $extraRoutes['PlanCampaigns']);

            $menu->getChild('Plan')->addChild('PlanActivities', [
                    'route' => 'campaignchain_core_plan_activities',
                    'label' => 'Activities',
                    'childOptions' => $event->getChildOptions()
                ]
            )
                ->setLabelAttribute('icon', 'fa fa-circle-o')
                ->setExtra('routes', $extraRoutes['PlanActivities']);

            $menu->getChild('Plan')->addChild('PlanMilestones', [
                    'route' => 'campaignchain_core_plan_milestones',
                    'label' => 'Milestones',
                    'childOptions' => $event->getChildOptions()
                ]
            )
                ->setLabelAttribute('icon', 'fa fa-circle-o')
                ->setExtra('routes', $extraRoutes['PlanMilestones']);

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
                ->setLabelAttribute('icon', 'fa fa-bar-chart')
                ->setAttribute('data-step', '5')
                ->setExtra('routes', $extraRoutes['Monitor']);
        }
    }
}