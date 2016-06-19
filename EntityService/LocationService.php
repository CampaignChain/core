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
use CampaignChain\CoreBundle\Entity\Channel;
use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\CoreBundle\Entity\Operation;
use CampaignChain\CoreBundle\Entity\Activity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CampaignChain\CoreBundle\Util\ParserUtil;
use CampaignChain\CoreBundle\Twig\CampaignChainCoreExtension;
use Doctrine\Common\Collections\ArrayCollection;
use CampaignChain\CoreBundle\EntityService\ActivityService;
use CampaignChain\CoreBundle\EntityService\ChannelService;

class LocationService
{
    protected $em;
    protected $container;
    protected $activityService;

    public function __construct(EntityManager $em, ContainerInterface $container, ActivityService $activityService)
    {
        $this->em = $em;
        $this->container = $container;
        $this->activityService = $activityService;
    }

    public function getLocation($id){
        $location = $this->em
            ->getRepository('CampaignChainCoreBundle:Location')
            ->find($id);

        if (!$location) {
            throw new \Exception(
                'No location found for id '.$id
            );
        }

        return $location;
    }

    public function getLocationModule($moduleIdentifier, $locationIdentifier){
        // Get module.
        $bundle = $this->em
            ->getRepository('CampaignChainCoreBundle:Bundle')
            ->findOneByName($moduleIdentifier);
        // Get the module's location config.
        $locationModule = $this->em
            ->getRepository('CampaignChainCoreBundle:LocationModule')
            ->findOneBy(array(
                    'bundle' => $bundle,
                    'identifier' => $locationIdentifier,
                )
            );

        return $locationModule;
    }

    /**
     * Finds a Location by the URL and automatically creates the Location if it
     * does not exist and if the respective Location module supports auto
     * generation of Locations.
     *
     * @todo Introduce an identifier_alias (e.g. username)?
     *
     * @param $url
     * @param $operation Operation
     * @return bool|Location|null|object
     */
    public function findLocationByUrl($url, $operation)
    {
        $url = ParserUtil::sanitizeUrl($url);

        // Check if the URL is in CampaignChain as a Location.
        $location = $this->em
            ->getRepository('CampaignChainCoreBundle:Location')
            ->findOneBy(array('url' => $url));

        /*
         * If not a Location, then see if the URL is inside a connected
         * Channel's top-level Locations. To do so, check if one of these
         * criteria apply:
         * - The Location URL matches the beginning of the CTA URL
         * - The Location identifier is included in the CTA URL as well
         *   as the domain
         */
        if(!$location){
            // Check if URL exists.

            try {
                // TODO: This is a performance bottleneck!
                $expandedUrlHeaders = get_headers($url);
            } catch (\Exception $e) {
                return false;
            }
            $status = $expandedUrlHeaders[0];

            if(strpos($status,"200")) {
                $urlParts = parse_url($url);

                $repository = $this->em
                    ->getRepository('CampaignChainCoreBundle:Location');

                $query = $repository->createQueryBuilder('location')
                    ->where(
                        "(:url LIKE CONCAT('%', location.url, '%')) OR ".
                        "(".
                        "location.identifier IS NOT NULL AND ".
                        ":url LIKE CONCAT('%', location.identifier, '%') AND ".
                        "location.url LIKE :host".
                        ")"
                    )
                    ->andWhere('location.parent IS NULL')
                    ->setParameter('url', $url)
                    ->setParameter('host', $urlParts['host'].'%')
                    ->getQuery();

                $matchingLocations = $query->getResult();

                // If there is at least 1 main location match, then see for
                // each of them whether the related Location Module supports
                // auto-generation of Locations.
                if($matchingLocations){
                    $location = null;

                    foreach($matchingLocations as $matchingLocation){
                        $ctaServiceName = $matchingLocation->getLocationModule()
                            ->getServices()['job_cta'];
                        if($ctaServiceName){
                            // Create the new Location if that
                            // has not been done yet.
                            if(!$location){
                                $location = new Location();
                                $location->setUrl($url);
                                $location->setOperation($operation);
                                $location->setChannel($matchingLocation->getChannel());
                                $location->setParent($matchingLocation);
                            }
                            // Update the Location module to be the current
                            // one.
                            $location->setLocationModule(
                                $matchingLocation->getLocationModule()
                            );

                            // Let the module's service process the new
                            // Location.
                            $ctaService = $this->container->get($ctaServiceName);
                            $location = $ctaService->execute($location);


                            // If the service does not return false, this
                            // means that the URL qualified to be handled by
                            // this module and we can exit the loop,
                            // because there's no need to try with the other
                            // main locations that might have been matching
                            // the URL.
                            if($location){
                                return $location;
                            }
                        }
                    }
                }
            }
        } else {
            return $location;
        }

        return false;
    }

    public function existsInCampaign(
        $moduleIdentifier, $locationIdentifier, $identifier, $campaign = null
    )
    {
        $locationModule = $this->getLocationModule($moduleIdentifier, $locationIdentifier);

        $qb = $this->em->createQueryBuilder();
        $qb->select('l')
            ->from('CampaignChain\CoreBundle\Entity\Location', 'l')
            ->from('CampaignChain\CoreBundle\Entity\Operation', 'o')
            ->from('CampaignChain\CoreBundle\Entity\Activity', 'a')
            ->where('l.locationModule = :locationModule')
            ->andWhere('l.identifier = :identifier')
            ->andWhere('l.operation = o.id')
            ->andWhere('o.activity = a.id');
        if($campaign != null){
            $qb->andWhere('a.campaign = :campaign')
                ->setParameter('campaign', $campaign);
        }
        $qb->setParameter('locationModule', $locationModule)
            ->setParameter('identifier', $identifier)
            ->orderBy('a.startDate', 'ASC');
        $query = $qb->getQuery();
        $locations = $query->getResult();

        return is_array($locations) && count($locations) >= 1;
    }

    public function existsInAllCampaigns(
        $moduleIdentifier, $locationIdentifier, $identifier
    )
    {
        return $this->existsInCampaign(
            $moduleIdentifier, $locationIdentifier, $identifier
        );
    }

    public function getLocationByOperation($operation)
    {
        $location = $this->em
            ->getRepository('CampaignChainCoreBundle:Location')
            ->findOneByOperation($operation);

        if (!$location) {
            throw new \Exception(
                'No location found for Operation with ID '.$operation->getId()
            );
        }

        return $location;
    }

    public function tplTeaser($location, $options = array())
    {
        $twigExt = new CampaignChainCoreExtension($this->em, $this->container);

        return $twigExt->tplTeaser($location, $options);

        return $icon;
    }
    /**
     * This method deletes a location if there are no closed activities.
     * If there are open activities the location is deactivated
     *
     * @param $id
     * @throws \Exception
     */
    public function removeLocation($id)
    {
        /** @var Location $location */
        $location = $this->em
            ->getRepository('CampaignChainCoreBundle:Location')
            ->find($id);

        if (!$location) {
            throw new \Exception(
                'No location found for id ' . $id
            );
        }
        $removableActivities = new ArrayCollection();
        $notRemovableActivities = new ArrayCollection();

        $accessToken = $this->em
            ->getRepository('CampaignChainSecurityAuthenticationClientOAuthBundle:Token')
            ->findOneBy(['location' => $location]);

        if ($accessToken) {
            $this->em->remove($accessToken);
            $this->em->flush();
        }


        foreach ($location->getActivities() as $activity) {
            if ($this->isRemovable($location)) {
                $removableActivities->add($activity);
            } else {
                $notRemovableActivities->add($activity);
            }
        }

        foreach ($removableActivities as $activity) {
            $this->activityService->removeActivity($activity);
        }

        //Hack to find the beloning entities which hae to be delted first
        $bundleName = explode('/', $location->getLocationModule()->getBundle()->getName());
        $classPrefix = 'CampaignChain\\'.implode('\\',array_map(function($e) {return ucfirst($e);},explode('-',$bundleName[1]))).'Bundle';

        $entitiesToDelete = [];
        foreach ($this->em->getMetadataFactory()->getAllMetadata() as $metadataClass) {
            if (strpos(strtolower($metadataClass->getName()), strtolower($classPrefix)) === 0) {
                $entitiesToDelete[] = $metadataClass;
            }
        };

        foreach ($entitiesToDelete as $repo) {
            $entities = $this->em->getRepository($repo->getName())->findBy(['location' => $location->getId()]);
            foreach ($entities as $entityToDelete) {
                $this->em->remove($entityToDelete);
            }
        }
        $this->em->flush();

        $this->em->remove($location);
        $this->em->flush();

        $channel = $location->getChannel();

        if($channel->getLocations()->isEmpty()){
            $this->em->remove($channel);
            $this->em->flush();
        }
    }

    /**
     * @param Location $location
     * @return bool
     */
    public function isRemovable(Location $location){

        $ctas= $this->em
            ->getRepository('CampaignChainCoreBundle:CTA')
            ->createQueryBuilder('cta')
            ->select('cta', 'reports')
            ->join('cta.reports', 'reports')
            ->where('cta.location = :location')
            ->setParameter('location', $location)
            ->getQuery()
            ->getResult();

        if (!empty($ctas)) {
            return false;
        }

        foreach ($location->getActivities() as $activity) {
            if (!$this->activityService->isRemovable($activity)) {
                return false;
            }
        }

        return true;
    }

    public function toggleStatus($id){
        /** @var Location $location */
        $location = $this->getLocation($id);

        /*
         * If a Location's status switches from inactive to active, and the
         * Channel is inactive, then the Channel becomes active as well.
         */
        if(
            $location->getStatus() == Location::STATUS_INACTIVE &&
            $location->getChannel()->getStatus() == Channel::STATUS_INACTIVE
        ) {
            $location->getChannel()->setStatus(Channel::STATUS_ACTIVE);
        }

        $toggle = (($location->getStatus()==Location::STATUS_ACTIVE) ? $location->setStatus(Location::STATUS_INACTIVE) : $location->setStatus(Location::STATUS_ACTIVE));
        $this->em->persist($location);
        $this->em->flush();
    }

}