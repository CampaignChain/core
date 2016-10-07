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

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class Builder extends ContainerAware
{
    public function mainNav(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('Home');

        // Plan
        $menu->addChild('Plan');
        $menu['Plan']->addChild('Campaigns', array(
                'route' => 'campaignchain_core_plan'
            ))->setAttribute('class', 'header');
        $menu['Plan']->addChild('Activities', array(
            'route' => 'campaignchain_core_plan_activities'
        ));
        $menu['Plan']->addChild('Templates', array(
            'route' => 'campaignchain_core_plan_templates'
        ));

        // Execute
        $menu->addChild('Execute', array(
            'route' => 'campaignchain_core_execute'
        ));

        // Monitor
        $menu->addChild('Monitor', array(
            'route' => 'campaignchain_core_report'
        ));

        // New
        $menu->addChild('New')->setAttributes(
            array(
                'class' => 'btn btn-default',
                'id' => 'menu_new'
                ));
        $menu['New']->addChild('Activity', array(
            'route' => 'campaignchain_core_activities_new'
        ));
        $menu['New']->addChild('Milestone', array(
            'route' => 'campaignchain_core_milestone_new'
        ));
        $menu['New']->addChild('Campaign', array(
            'route' => 'campaignchain_core_campaign_new'
        ));
        $menu['New']->addChild('Location', array(
            'route' => 'campaignchain_core_channel_new'
        ));

        return $menu;
    }
    
    public function executeListTab(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $menu->addChild('Campaigns', array('route' => 'campaignchain_core_campaign'));
        $menu->addChild('Activities', array('route' => 'campaignchain_core_activities'));
        $menu->addChild('Milestones', array('route' => 'campaignchain_core_milestone'));

        return $menu;
    }

    public function modulesTab(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $menu->addChild('All', array(
            'route' => 'campaignchain_core_module'
        ))
            ->setAttribute('class', 'campaignchain-modules-all');
        $menu->addChild('New', array(
            'route' => 'campaignchain_core_module_new'
        ));

        return $menu;
    }

    public function profileListTab(FactoryInterface $factory, array $options)
    {
        $request = $this->container->get('request_stack')->getMasterRequest();

        $menu = $factory->createItem('root');

        $menu->addChild('Settings', array('route' => 'campaignchain_core_profile_edit'));
        $menu->addChild('Password', array('route' => 'campaignchain_core_profile_change_password'));

        return $menu;
    }

    public function userListTab(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $menu->addChild('List', array('route' => 'campaignchain_core_user'));

        return $menu;
    }

    public function userEditTab(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $menu->addChild('Back to List', array('route' => 'campaignchain_core_user'));
        $menu->addChild('Edit User', array(
            'route' => 'campaignchain_core_user_edit',
            'routeParameters' => array(
                'id' => $this->container->get('request')->get('id')
            )));
        $menu->addChild('Change Password', array(
            'route' => 'campaignchain_core_user_change_password',
            'routeParameters' => array(
                'id' => $this->container->get('request')->get('id')
            )));

        return $menu;
    }


    public function settingsMenu(FactoryInterface $factory, array $options)
    {
        $system = $this->container->get('campaignchain.core.system')->getActiveSystem();
        $systemNavigation = $system->getNavigation();

        $menu = $factory->createItem('root');

        if(!isset($systemNavigation['users']) || $systemNavigation['users']) {
            $securityContext = $this->container->get('security.authorization_checker');
            if ($securityContext->isGranted('ROLE_SUPER_ADMIN')) {
                $menu->addChild('Users', [
                    'route' => 'campaignchain_core_user',
                ]);
            }
        }

        if(!isset($systemNavigation['channels']) || $systemNavigation['channels']) {
            $menu->addChild('Channels', [
                'route' => 'campaignchain_core_channel',
            ]);
        }

        if(!isset($systemNavigation['locations']) || $systemNavigation['locations']) {
            $menu->addChild('Locations', [
                'route' => 'campaignchain_core_location',
            ]);
        }

        if(!isset($systemNavigation['modules']) || $systemNavigation['modules']) {
            $menu->addChild('Modules', [
                'route' => 'campaignchain_core_module',
            ]);
        }

        if ($systemNavigation) {
            foreach ($systemNavigation['settings'] as $systemSetting) {
                list($label, $route) = $systemSetting;

                $menu->addChild($label, [
                    'route' => $route,
                ]);
            }
        }

        return $menu;
    }
}