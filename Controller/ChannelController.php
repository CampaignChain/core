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
use CampaignChain\CoreBundle\Entity\Channel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityRepository;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use GK\JavascriptPacker;

class ChannelController extends Controller
{
    public function indexChannelModulesAction(){
        $channel_modules = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:ChannelModule')
            ->getAllChannelModules();
        //dump($channel_modules);exit;
        if(!count($channel_modules)){
            $system = $this->get('campaignchain.core.system')->getActiveSystem();
            $this->get('session')->getFlashBag()->add(
                'warning',
                'No channels defined yet. To learn how to create one, please <a href="#" onclick="popupwindow(\''.
                $system->getDocsURL().'/user/get_started.html#connect-to-a-channel'.
                '\',\'\',900,600)">consult the documentation</a>.'
            );
        }

        return $this->render(
            'CampaignChainCoreBundle:Channel:index_channel_modules.html.twig',
            array(
                'page_title' => 'Channels',
                'channel_modules' => $channel_modules
            ));
    }

    public function indexTrackingAction(){
        $repository_channels = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:Channel')
            ->getAllChannels();

        if(!count($repository_channels)){
            $system = $this->get('campaignchain.core.system')->getActiveSystem();
            $this->get('session')->getFlashBag()->add(
                'warning',
                'No channels defined yet. To learn how to create one, please <a href="#" onclick="popupwindow(\''.
                $system->getDocsURL().'/user/get_started.html#connect-to-a-channel'.
                '\',\'\',900,600)">consult the documentation</a>.'
            );
        }

        return $this->render(
            'CampaignChainCoreBundle:Channel:index_tracking.html.twig',
            array(
                'page_title' => 'Tracking',
                'repository_channels' => $repository_channels
            ));
    }

    public function indexAccountsAction(){
        $repository_channels = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:Channel')
            ->getAllChannels();

        if(!count($repository_channels)){
            $system = $this->get('campaignchain.core.system')->getActiveSystem();
            $this->get('session')->getFlashBag()->add(
                'warning',
                'No channels defined yet. To learn how to create one, please <a href="#" onclick="popupwindow(\''.
                $system->getDocsURL().'/user/get_started.html#connect-to-a-channel'.
                '\',\'\',900,600)">consult the documentation</a>.'
            );
        }

        return $this->render(
            'CampaignChainCoreBundle:Channel:index_accounts.html.twig',
            array(
                'page_title' => 'Channels',
                'repository_channels' => $repository_channels
            ));
    }

    public function newAction(Request $request, $id)
    {
        $channel = new Channel();
        $module = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:ChannelModule')
            ->find($id);

        $wizard = $this->get('campaignchain.core.channel.wizard');
        $wizard->start($channel, $module);

        return $this->redirect(
            $this->generateUrl(
                $module->getRoutes()['new']
            )
        );
    }

    public function apiListActivitiesAction(Request $request, $id){
        $location = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:Location')
            ->find($id);

        if (!$location) {
            throw new \Exception(
                'No channel found for id '.$id
            );
        }

        // Get the modules of type "activity" that are related to the channel.
        $activityModules = $location->getChannel()->getChannelModule()->getActivityModules();

        $response = array();

        // TODO: Check whether there are any activity modules.
//        if($activityModules->count()){
            foreach($activityModules as $activityModule){
                $response[] = array(
                    'id' => $activityModule->getId(),
                    'display_name' => $activityModule->getDisplayName(),
                    'name' => $activityModule->getIdentifier(),
                );
            }
//        }

        $serializer = $this->get('campaignchain.core.serializer.default');

        return new Response($serializer->serialize($response, 'json'));
    }

    private function getTrackingSnippet(Channel $channel)
    {
        $trackingSnippet = $this->renderView(
            'CampaignChainCoreBundle:Channel:_cta_tracking_snippet.js.twig',
            array(
                'channel' => $channel,
                'tracking_js_init' => $this->getParameter('campaignchain_core.tracking.js_init'),
                'tracking_js_route' => $this->getParameter('campaignchain.tracking.js_route')
            )
        );

        $packer = new JavascriptPacker($trackingSnippet, 'None');
        return $packer->pack();
    }

    public function ctaTrackingAction(Request $request, $id){
        $channelService = $this->get('campaignchain.core.channel');
        $channel = $channelService->getChannel($id);

        return $this->render(
            'CampaignChainCoreBundle:Channel:cta_tracking.html.twig',
            array(
                'page_title' => 'Enable CTA Tracking',
                'channel' => $channel,
                'tracking_snippet' => $this->getTrackingSnippet($channel),
            ));
    }

    /**
     * Checks whether a Channel has the tracking code included.
     *
     * @ApiDoc(
     *  section = "Core",
     *  views = { "private" },
     *  requirements={
     *      {
     *          "name"="id",
     *          "requirement"="\d+"
     *      }
     *  }
     * )
     *
     * @param Request $request
     * @param $id Channel ID
     * @return Response
     * @throws \Exception
     */
    public function apiTestCtaTrackingAction(Request $request, $id){
        $response = array();

        $channelService = $this->get('campaignchain.core.channel');
        $channel = $channelService->getChannel($id);
        $locations = $channelService->getRootLocations($channel);

        if(count($locations)){
            $trackingStatus = true;

            foreach($locations as $location){
                $html = file_get_contents($location->getUrl());
                if (strpos($html, $this->getTrackingSnippet($channel)) === false) {
                    $trackingStatus = false;
                    $response['root_location'][] = $location->getUrl();
                }
            }
        } else {
            // TODO: Throw exception if no location defined for channel.
            $trackingStatus = false;
        }

        $response['ok'] = $trackingStatus;

        $serializer = $this->get('campaignchain.core.serializer.default');

        return new Response($serializer->serialize($response, 'json'));
    }

    public function removeAction(Request $request, $id)
    {
        $channelService = $this->get('campaignchain.core.channel');
        try{
            $channelService->removeChannel($id);
            $this->addFlash('success', 'Channel deleted successfully');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Channel could not be deleted');
        }
        return $this->redirectToRoute('campaignchain_core_channel');
    }
}