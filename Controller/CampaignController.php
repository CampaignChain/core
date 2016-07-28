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

use CampaignChain\CoreBundle\Util\DateTimeUtil;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use CampaignChain\CoreBundle\Entity\Campaign;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityRepository;

class CampaignController extends Controller
{
    const FORMAT_DATEINTERVAL = 'Years: %Y, months: %m, days: %d, hours: %h, minutes: %i, seconds: %s';

    public function indexAction()
    {

        $repository_campaigns = $this->getDoctrine()->getRepository('CampaignChainCoreBundle:Campaign')->getCampaigns();

        return $this->render(
            'CampaignChainCoreBundle:Campaign:index.html.twig',
            array(
                'page_title' => 'Campaigns',
                'repository_campaigns' => $repository_campaigns
            ));
    }

    public function newAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('campaign_module', 'entity', array(
                'label' => 'Type',
                'class' => 'CampaignChainCoreBundle:CampaignModule',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('cm')
                        ->orderBy('cm.displayName', 'ASC');
                },
                'property' => 'displayName',
                'empty_value' => 'Select the type of campaign',
                'empty_data' => null,
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            // Get the activity module's activity.
            $campaignService = $this->get('campaignchain.core.campaign');
            $campaignModule = $campaignService->getCampaignModule($form->get('campaign_module')->getData());

            $routes = $campaignModule->getRoutes();

            if ($this->getRequest()->isXmlHttpRequest()) {
                return new JsonResponse(array(
                    'step' => 1,
                    'next_step' => $routes['new'],
                ));
            } else {
                return $this->redirectToRoute($routes['new']);
            }
        }

        return $this->render(
            $this->getRequest()->isXmlHttpRequest() ? 'CampaignChainCoreBundle:Base:new_modal.html.twig' : 'CampaignChainCoreBundle:Base:new.html.twig',
            array(
                'page_title' => 'Create New Campaign',
                'form' => $form->createView(),
                'form_submit_label' => 'Next',
            ));
    }

    public function editAction(Request $request, $id)
    {
        // TODO: If a campaign is ongoing, only the end date can be changed.
        // TODO: If a campaign is done, it cannot be edited.
        $campaignService = $this->get('campaignchain.core.campaign');
        $campaignModule = $campaignService->getCampaignModuleByCampaign($id);
        $routes = $campaignModule->getRoutes();

        return $this->redirectToRoute($routes['edit'], array('id' => $id));
    }

    public function editModalAction(Request $request, $id)
    {
        // TODO: If a campaign is ongoing, only the end date can be changed.
        // TODO: If a campaign is done, it cannot be edited.
        $campaignService = $this->get('campaignchain.core.campaign');
        $campaignModule = $campaignService->getCampaignModuleByCampaign($id);
        $routes = $campaignModule->getRoutes();

        return $this->redirectToRoute($routes['edit_modal'], array('id' => $id));

    }

    public function moveApiAction(Request $request)
    {
        $serializer = $this->get('campaignchain.core.serializer.default');

        $responseData = array();

        $id = $request->request->get('id');
        $newStartDate = new \DateTime($request->request->get('start_date'));
        $newStartDate = DateTimeUtil::roundMinutes($newStartDate);

        $campaignService = $this->get('campaignchain.core.campaign');
        $campaign = $campaignService->getCampaign($id);

        // Preserve old campaign data for response.
        $responseData['campaign']['id'] = $campaign->getId();
        $oldCampaignStartDate = clone $campaign->getStartDate();
        $responseData['campaign']['old_start_date'] = $oldCampaignStartDate->format(\DateTime::ISO8601);
        $responseData['campaign']['old_end_date'] = $campaign->getEndDate()->format(\DateTime::ISO8601);

        // Move campaign's start date.
        $campaign = $campaignService->moveCampaign($campaign, $newStartDate);

        // Add new campaign dates to response.
        $responseData['campaign']['new_start_date'] = $campaign->getStartDate()->format(\DateTime::ISO8601);
        $responseData['campaign']['new_end_date'] = $campaign->getEndDate()->format(\DateTime::ISO8601);

        return new Response($serializer->serialize($responseData, 'json'));
    }
}