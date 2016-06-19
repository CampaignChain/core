<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EntityService;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CampaignChain\CoreBundle\Util\ParserUtil;
use CampaignChain\CoreBundle\Entity\CTA;
use Doctrine\Common\Collections\ArrayCollection;
use CampaignChain\CoreBundle\Entity\Activity;
use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\CoreBundle\Entity\Channel;

class ChannelService
{
    protected $em;
    protected $container;
    protected $activityService;
    protected $locationService;


    public function __construct(EntityManager $em, ContainerInterface $container, ActivityService $activityService, LocationService $locationService)
    {
        $this->em = $em;
        $this->container = $container;
        $this->activityService = $activityService;
        $this->locationService = $locationService;
    }

    public function getChannel($id){
        $channel = $this->em
            ->getRepository('CampaignChainCoreBundle:Channel')
            ->find($id);

        if (!$channel) {
            throw new \Exception(
                'No Channel found for id '.$id
            );
        }

        return $channel;
    }

    public function getChannelByLocation($locationId){
        $location = $this->em
            ->getRepository('CampaignChainCoreBundle:Location')
            ->find($locationId);

        if (!$location) {
            throw new \Exception(
                'No Location found for id '.$locationId
            );
        }

        $channel = $location->getChannel();

        if(!$channel){
            throw new \Exception(
                'This is not a Channel Location'
            );
        }

        return $channel;
    }

    /*
     * Generates a Tracking ID
     *
     * This method also makes sure that the ID is unique, i.e. that it does
     * not yet exist for another Channel.
     *
     * @return string
     */
    public function generateTrackingId()
    {
        $trackingId = md5(uniqid(mt_rand(), true));

        // Check with DB, whether already exists. If yes, then generate new one and check again.
        $cta = $this->em->getRepository('CampaignChainCoreBundle:Channel')->findOneByTrackingId($trackingId);

        if($cta){
            return $this->generateTrackingId();
        } else {
            return $trackingId;
        }
    }

    public function getRootLocations($channel)
    {
        $repository = $this->em->getRepository('CampaignChainCoreBundle:Location');

        $query = $repository->createQueryBuilder('l')
            ->where('l.channel = :channel')
            ->andWhere('l.parent IS NULL')
            ->orderBy('l.name', 'ASC')
            ->setParameter('channel', $channel)
            ->getQuery();

        return $query->getResult();
    }
    /**
     * This method deletes a channel if there are no closed activities.
     * If there are open activities the location is deactivated
     *
     * @param $id
     * @throws \Exception
     */
    public function removeChannel($id)
    {
        $channel = $this->em
            ->getRepository('CampaignChainCoreBundle:Channel')
            ->find($id);

        if (!$channel) {
            throw new \Exception(
                'No channel found for id ' . $id
            );
        }
        //
        $locations = $channel->getLocations();

        $openActivities = new ArrayCollection();
        $closedActivities = new ArrayCollection();
        foreach ($locations as $location){
        foreach ($location->getActivities() as $activity) {
            if ($activity->getStatus() == 'closed') {
                $closedActivities->add($activity);
            } else {
                $openActivities->add($activity);
            }
        }
        }

        if (!$closedActivities->isEmpty()) {
            //deaktivieren
        } else {
            foreach ($openActivities as $activity) {
                $this->activityService->removeActivity($activity);
            }
            foreach($locations as $location){
                $this->locationService->removeLocation($location);
            }
            $this->em->remove($channel);
            $this->em->flush();


        }
    }

    /**
     * @param Channel $channel
     * @return bool
     */
    public function isRemovable(Channel $channel){

        $schedulerReportsChannels = $this->em
            ->getRepository('CampaignChainCoreBundle:ReportAnalyticsChannelFact')
            ->findBy(array('channel' => $channel));

        if (!empty($schedulerReportsChannels)) {
            return false;
        }

        foreach ($channel->getLocations() as $location) {
            if (!$this->locationService->isRemovable($location)) {
                return false;
            }
        }
        
        return true;
    }

    public function toggleStatusChannel($id){
        $channel = $this->getChannel($id);

        $toggle = (($channel->getStatus()==Location::STATUS_ACTIVE) ? $channel->setStatus(Location::STATUS_INACTIVE) : $channel->setStatus(Location::STATUS_ACTIVE));
        foreach ($channel->getLocations() as $location) {
            $location->setStatus($channel->getStatus());
        }
        $this->em->persist($channel);
        $this->em->flush();
    }

}