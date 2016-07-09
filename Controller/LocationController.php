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

use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\CoreBundle\EntityService\LocationService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use CampaignChain\CoreBundle\Entity\Channel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LocationController extends Controller
{
    public function indexAction(){
        $repository = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:Location');

        //$query = $repository->createQueryBuilder('location')
          //  ->where('location.operation IS NULL')
            //->orderBy('location.name', 'ASC')
            //->getQuery();

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
                'locations' => $locations
            ));
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
    public function  removeAction(Request $request, $id)
    {
        $locationService = $this->get('campaignchain.core.location');
        try{
            $locationService->removeLocation($id);
            $this->addFlash('success', 'Location deleted successfully');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Location could not be deleted');
        }

        return $this->redirectToRoute('campaignchain_core_location');
    }
    public function toggleStatusAction(Request $request, $id)
    {
        /** @var LocationService $locationService */
        $locationService = $this->get('campaignchain.core.location');
        $locationService->toggleStatus($id);
        return $this->redirectToRoute('campaignchain_core_location');
    }
}