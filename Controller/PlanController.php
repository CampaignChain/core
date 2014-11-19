<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Controller;

use Guzzle\Common\FromConfigInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use CampaignChain\CoreBundle\Entity\Activity;
use CampaignChain\CoreBundle\Entity\Operation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

class PlanController extends Controller
{
    public function ganttAction(){
        return $this->render(
            'CampaignChainCoreBundle:Plan:gantt.html.twig',
            array(
                'page_title' => 'Timeline',
                'gantt_tasks' => $this->get('campaignchain.core.model.dhtmlxgantt')->getTasks(),
                'gantt_toolbar_status' => 'default',
                'campaignchain_style' => $this->container->getParameter('campaignchain_core')['style'],
            ));
    }

    public function ganttFullScreenAction(){
        return $this->render(
            'CampaignChainCoreBundle:GANTT:fullscreen.html.twig',
            array(
                'page_title' => 'Timeline',
                'gantt_tasks' => $this->get('campaignchain.core.model.dhtmlxgantt')->getTasks(),
                'gantt_toolbar_status' => 'fullscreen',
                'campaignchain_style' => $this->container->getParameter('campaignchain_core')['style'],
            ));
    }

    public function calendarAction(){
        return $this->render(
            'CampaignChainCoreBundle:Plan:calendar.html.twig',
            array(
                'page_title' => 'Calendar',
                'events' => $this->get('campaignchain.core.model.fullcalendar')->getEvents(),
            ));
    }
}