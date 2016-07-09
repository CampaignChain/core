<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class Builder extends ContainerAware
{
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
        $menu = $factory->createItem('root');

        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('ROLE_SUPER_ADMIN')) {
            $menu->addChild('Users', [
                'route' => 'campaignchain_core_user',
            ]);
        }

        $menu->addChild('Channels', [
            'route' => 'campaignchain_core_channel',
        ]);
        $menu->addChild('Locations', [
            'route' => 'campaignchain_core_location',
        ]);

        $menu->addChild('Modules', [
            'route' => 'campaignchain_core_module',
        ]);

        $system = $this->container->get('campaignchain.core.system')->getActiveSystem();
        $systemNavigation = $system->getNavigation();

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