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

use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\CoreBundle\Entity\Medium;
use CampaignChain\CoreBundle\EntityService\LocationService;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use CampaignChain\CoreBundle\Entity\Channel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class LocationController extends Controller
{
    public function indexAction(){
        $channelModules = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:ChannelModule')
            ->getActiveChannelModules();

        $repository = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:Location');
        $query = $repository->createQueryBuilder('location')
            ->select('location', 'channel', 'locationModule')
            ->join('location.channel','channel')
            ->join('location.locationModule', 'locationModule')
            ->where('location.operation IS NULL')
            ->orderBy('location.name', 'ASC')
            ->getQuery();

        $locations = $query->getResult();

        return $this->render(
            'CampaignChainCoreBundle:Location:index.html.twig',
            array(
                'page_title' => 'Locations',
                'locations' => $locations,
                'channel_modules' => $channelModules,
            ));
    }

    /**
     * Get the Activity modules that are available for a Location.
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
     * @param $id Location ID
     * @return Response
     * @throws \Exception
     */
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

    public function  removeAction(Request $request, $id)
    {
        $locationService = $this->get('campaignchain.core.location');
        try{
            $locationService->removeLocation($id);
            $this->addFlash('success', 'Location deleted successfully');
        } catch (\Exception $e) {
            /** @var Logger $logger */
            $logger = $this->get('logger');
            $logger->critical($e->getMessage());
            $this->addFlash('warning', 'Location could not be deleted');
        }

        return $this->redirectToRoute('campaignchain_core_location');
    }
}