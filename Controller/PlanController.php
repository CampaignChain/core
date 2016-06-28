<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;

class PlanController extends Controller
{
    const BUNDLE_NAME = 'campaignchain/campaign-scheduled';
    const MODULE_IDENTIFIER = 'campaignchain-scheduled';

    public function indexAction(Request $request)
    {
        $repository = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:CampaignModule');

        $query = $repository->createQueryBuilder('cm')
            ->orderBy('cm.displayName', 'ASC')
            ->getQuery();

        $campaignModules = $query->getResult();

        return $this->render(
            'CampaignChainCoreBundle:Plan:index.html.twig',
            array(
                'page_title' => 'Plan Campaigns',
                'gantt_tasks' => $this->get('campaignchain.core.model.dhtmlxgantt')->getOngoingUpcomingCampaigns(
                    self::BUNDLE_NAME, self::MODULE_IDENTIFIER
                ),
                'gantt_toolbar_status' => 'default',
                'path_embedded' => $this->generateUrl('campaignchain_campaign_scheduled_plan_timeline'),
                'path_fullscreen' =>  $this->generateUrl('campaignchain_campaign_scheduled_plan_timeline_fullscreen'),
                'gantt_toolbar_timescale_hours' => false,
            ));
    }
}