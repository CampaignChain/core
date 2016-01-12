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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use CampaignChain\CoreBundle\Entity\Channel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

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
            ->select('location', 'channel')
            ->join('location.channel','channel')
            ->where('location.operation IS NULL')
            ->andWhere('channel.status = ?1')
            ->orderBy('location.name', 'ASC')
            ->getQuery();
        $query->setParameters(array(1 => Channel::STATUS_ACTIVE));

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

        $encoders = array(new JsonEncoder());
        $normalizers = array(new GetSetMethodNormalizer());

        $serializer = new Serializer($normalizers, $encoders);

        return new Response($serializer->serialize($response, 'json'));
    }
    public function  removeAction(Request $request, $id)
    {
        $locationService = $this->get('campaignchain.core.location');
        $locationService->removeLocation($id);
        return $this->redirectToRoute('campaignchain_core_location');
    }
    public function toggleStatusAction(Request $request, $id)
    {
        $locationService = $this->get('campaignchain.core.location');
        $locationService->toggleStatus($id);
        return $this->redirectToRoute('campaignchain_core_location');
    }
}