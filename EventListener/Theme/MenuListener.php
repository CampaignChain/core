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
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class MenuListener
{
    /**
     * @var AuthorizationChecker
     */
    protected $authorizationChecker;

    public function __construct(AuthorizationChecker $authorizationChecker) {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function onSetupMenu(KnpMenuEvent $event)
    {
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
                    'route' => 'campaignchain_core_campaign_new',
                    'label' => 'Campaign',
                    'childOptions' => $event->getChildOptions()
                    ]
                )
                ->setLabelAttribute('icon', 'fa fa-circle-o')
                ->setAttribute('data-step', '2');
            $menu->getChild('Create')->addChild('CreateActivity', [
                    'route' => 'campaignchain_core_activities_new',
                    'label' => 'Activity',
                    'childOptions' => $event->getChildOptions()
                    ]
                )
                ->setLabelAttribute('icon', 'fa fa-circle-o')
                ->setAttribute('data-step', '3');
            $menu->getChild('Create')->addChild('CreateMilestone', [
                    'route' => 'campaignchain_core_milestone_new',
                    'label' => 'Milestone',
                    'childOptions' => $event->getChildOptions()
                    ]
                )
                ->setLabelAttribute('icon', 'fa fa-circle-o');

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
                    'route' => 'campaignchain_core_plan',
                    'label' => 'Campaigns',
                    'childOptions' => $event->getChildOptions()
                ]
            )
                ->setLabelAttribute('icon', 'fa fa-circle-o')
                ->setAttribute('data-step', '4')
                ->addChild('PlanCampaignTemplates', [
                        'route' => 'campaignchain_core_plan_templates',
                        'label' => 'Templates',
                        'childOptions' => $event->getChildOptions()
                    ]
                )->setLabelAttribute('icon', 'fa fa-circle-o');
            $menu->getChild('Plan')->addChild('PlanActivities', [
                    'route' => 'campaignchain_core_plan_activities',
                    'label' => 'Activities',
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
                ->setLabelAttribute('icon', 'fa fa-bar-chart')
                ->setAttribute('data-step', '5');
        }
    }
}