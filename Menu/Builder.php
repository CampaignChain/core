<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
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

    public function profileListTab(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $menu->addChild('Settings', array('route' => 'campaignchain_core_profile_edit'));
        $menu->addChild('Password', array('route' => 'fos_user_change_password'));

        return $menu;
    }

    public function userListTab(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $menu->addChild('List', array('route' => 'campaignchain_core_user'));

        return $menu;
    }
}