<?php
/**
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EntityService;

use CampaignChain\CoreBundle\Entity\Channel;
use CampaignChain\CoreBundle\Entity\Location;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ChannelService
{
    protected $em;
    protected $container;
    protected $activityService;
    protected $locationService;

    public function __construct(
        EntityManager $em,
        ContainerInterface $container,
        ActivityService $activityService,
        LocationService $locationService
    ) {
        $this->em = $em;
        $this->container = $container;
        $this->activityService = $activityService;
        $this->locationService = $locationService;
    }

    public function getChannelByLocation($locationId)
    {
        $location = $this->em
            ->getRepository('CampaignChainCoreBundle:Location')
            ->find($locationId);

        if (!$location) {
            throw new \Exception(
                'No Location found for id '.$locationId
            );
        }

        $channel = $location->getChannel();

        if (!$channel) {
            throw new \Exception(
                'This is not a Channel Location'
            );
        }

        return $channel;
    }

    public function generateTrackingId()
    {
        $trackingId = md5(uniqid(mt_rand(), true));

        // Check with DB, whether already exists. If yes, then generate new one and check again.
        $cta = $this->em->getRepository('CampaignChainCoreBundle:Channel')->findOneByTrackingId($trackingId);

        if ($cta) {
            return $this->generateTrackingId();
        } else {
            return $trackingId;
        }
    }

    /*
     * Generates a Tracking ID
     *
     * This method also makes sure that the ID is unique, i.e. that it does
     * not yet exist for another Channel.
     *
     * @return string
     */

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
     * If there are open activities the location is deactivated.
     *
     * @param $id
     *
     * @throws \Exception
     */
    public function removeChannel($id)
    {
        $channel = $this->em
            ->getRepository('CampaignChainCoreBundle:Channel')
            ->find($id);

        if (!$channel) {
            throw new \Exception(
                'No channel found for id '.$id
            );
        }
        //
        $locations = $channel->getLocations();

        $openActivities = new ArrayCollection();
        $closedActivities = new ArrayCollection();
        foreach ($locations as $location) {
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
            foreach ($locations as $location) {
                $this->locationService->removeLocation($location);
            }
            $this->em->remove($channel);
            $this->em->flush();
        }
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    public function isRemovable(Channel $channel)
    {
        foreach ($channel->getLocations() as $location) {
            if (!$this->locationService->isRemovable($location)) {
                return false;
            }
        }

        return true;
    }

    public function toggleStatusChannel($id)
    {
        $channel = $this->getChannel($id);

        $toggle = (($channel->getStatus() == Location::STATUS_ACTIVE) ? $channel->setStatus(
            Location::STATUS_INACTIVE
        ) : $channel->setStatus(Location::STATUS_ACTIVE));
        foreach ($channel->getLocations() as $location) {
            $location->setStatus($channel->getStatus());
        }
        $this->em->persist($channel);
        $this->em->flush();
    }

    public function getChannel($id)
    {
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
}
