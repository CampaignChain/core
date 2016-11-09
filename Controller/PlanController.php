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

namespace CampaignChain\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;

class PlanController extends Controller
{
    const BUNDLE_NAME = 'campaignchain/campaign-scheduled';
    const MODULE_IDENTIFIER = 'campaignchain-scheduled';

    public function campaignsAction(Request $request)
    {
        $repository = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:CampaignModule');

        $query = $repository->createQueryBuilder('cm')
            ->orderBy('cm.displayName', 'ASC')
            ->getQuery();

        $campaignModules = $query->getResult();

        return $this->render(
            'CampaignChainCoreBundle:Plan/Timeline/Campaign:index.html.twig',
            array(
                'page_title' => 'Open Campaigns',
                'gantt_tasks' => $this->get('campaignchain.core.model.dhtmlxgantt')->getOngoingUpcomingCampaigns(),
                'gantt_toolbar_status' => 'default',
                'gantt_show_buttons' => true,
                'path_embedded' => $this->generateUrl('campaignchain_campaign_scheduled_plan_timeline'),
                'path_fullscreen' =>  $this->generateUrl('campaignchain_campaign_scheduled_plan_timeline_fullscreen'),
            ));
    }

    public function activitiesAction(){
        return $this->render(
            'CampaignChainCoreBundle:Activity:calendar.html.twig',
            array(
                'page_title' => 'Activities Calendar',
                'events' => $this->get('campaignchain.core.model.fullcalendar')->getEvents(
                    array(
                        'only_activities' => true
                    )
                ),
            ));
    }

    public function milestonesAction(){
        return $this->render(
            'CampaignChainCoreBundle:Milestone:calendar.html.twig',
            array(
                'page_title' => 'Milestones Calendar',
                'events' => $this->get('campaignchain.core.model.fullcalendar')->getEvents(
                    array(
                        'only_milestones' => true
                    )
                ),
            ));
    }

    public function templatesAction()
    {

        $repository_campaigns = $this->getDoctrine()->getRepository('CampaignChainCoreBundle:Campaign')->getCampaignTemplates();

        return $this->render(
            'CampaignChainCoreBundle:Plan/Table/Campaign:index.html.twig',
            array(
                'page_title' => 'Plan Templates',
                'repository_campaigns' => $repository_campaigns
            ));
    }
}