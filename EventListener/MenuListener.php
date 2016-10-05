<?php

namespace CampaignChain\CoreBundle\EventListener;

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