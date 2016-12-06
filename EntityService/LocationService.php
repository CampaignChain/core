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

namespace CampaignChain\CoreBundle\EntityService;

use CampaignChain\CoreBundle\Entity\ChannelModule;
use CampaignChain\CoreBundle\Entity\LocationModule;
use Doctrine\Common\Persistence\ManagerRegistry;
use CampaignChain\CoreBundle\Entity\Channel;
use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\CoreBundle\Entity\Operation;
use CampaignChain\CoreBundle\Entity\Activity;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CampaignChain\CoreBundle\Util\ParserUtil;
use CampaignChain\CoreBundle\Twig\CampaignChainCoreExtension;
use Doctrine\Common\Collections\ArrayCollection;
use CampaignChain\CoreBundle\EntityService\ActivityService;
use CampaignChain\CoreBundle\EntityService\ChannelService;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocationService
{
    const DEFAULT_LOCATION_BUNDLE_NAME = 'campaignchain/location-website';
    const DEFAULT_LOCATION_MODULE_IDENTIFIER = 'campaignchain-website-page';

    protected $em;
    protected $container;
    protected $activityService;

    /**
     * @var CampaignChainCoreExtension
     */
    protected $twigExt;

    public function __construct(ManagerRegistry $managerRegistry, ContainerInterface $container, ActivityService $activityService)
    {
        $this->em = $managerRegistry->getManager();
        $this->container = $container;
        $this->activityService = $activityService;

        $this->twigExt = $this->container->get('campaignchain.core.twig.campaignchain_core_extension');
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

    public function getLocationModuleByTrackingAlias(ChannelModule $channelModule, $alias)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('lm')
            ->from('CampaignChain\CoreBundle\Entity\LocationModule', 'lm')
            ->join('lm.channelModules', 'cm')
            ->where('lm.trackingAlias = :trackingAlias')
            ->andWhere('cm.id = :channelModule')
            ->setParameter('trackingAlias', $alias)
            ->setParameter('channelModule', $channelModule->getId());
        $query = $qb->getQuery();
        /** @var LocationModule $locationModule */
        $locationModule = $query->getSingleResult();

        if (!$locationModule) {
            throw new \Exception(
                'No location module found for tracking alias "'.$alias.'" '.
                'in Channel "'.$channelModule->getBundle()->getName().'/'.
                $channelModule->getIdentifier().'"."'
            );
        }

        return $locationModule;
    }

    /**
     * Finds a Location by the URL and automatically creates the Location if it
     * does not exist and if the respective Location module supports auto
     * generation of Locations.
     **
     * @param URL $url
     * @param Operation $operation
     * @param string $alias Tracking alias
     * @param array $options    'graceful_url_exists':
     *                              Gracefully handles URL check if there's a
     *                              timeout.
     * @return bool|Location|null|object
     */
    public function findLocationByUrl($url, Operation $operation, $alias = null, array $options = array())
    {
        /*
         * Set default options.
         */
        $resolver = new OptionsResolver();
        $resolver->setDefaults(array(
            'graceful_url_exists' => true,
        ));

        $options = $resolver->resolve($options);

        $url = ParserUtil::sanitizeUrl($url);

        /*
         * If not a Location, then see if the URL is inside a connected
         * Channel's top-level Locations. To do so, check if one of these
         * criteria apply:
         * - The Location URL matches the beginning of the CTA URL
         * - The Location identifier is included in the CTA URL as well
         *   as the domain
         */
        if(ParserUtil::urlExists($url, $options['graceful_url_exists'])) {
            $urlParts = parse_url($url);

            if($urlParts['scheme'] == 'http'){
                $urlAltScheme = str_replace('http://', 'https://', $url);
            } elseif($urlParts['scheme'] == 'https'){
                $urlAltScheme = str_replace('https://', 'http://', $url);
            }

            $repository = $this->em
                ->getRepository('CampaignChainCoreBundle:Location');

            $query = $repository->createQueryBuilder('location')
                ->where(
                    "(:url LIKE CONCAT('%', location.url, '%')) OR ".
                    "(:url_alt_scheme LIKE CONCAT('%', location.url, '%')) OR ".
                    "(".
                    "location.identifier IS NOT NULL AND ".
                    ":url LIKE CONCAT('%', location.identifier, '%') AND ".
                    "location.url LIKE :host".
                    ")"
                )
                ->andWhere('location.parent IS NULL')
                ->setParameter('url', $url)
                ->setParameter('url_alt_scheme', $urlAltScheme)
                ->setParameter('host', $urlParts['host'].'%')
                ->getQuery();

            try {
                /** @var Location $matchingLocation */
                $matchingLocation = $query->getOneOrNullResult();
            } catch (NonUniqueResultException $e){
                throw new \Exception('Internal Error: Found two matching Locations, but there should be only one in file '.__FILE__.' on line '.__LINE__);
            }

            if($matchingLocation){
                /*
                 * Create a new Location based on the tracking alias
                 * within the matching Location's Channel.
                 *
                 * If no tracking alias was provided, we take the
                 * default LocationModule to create a new Location.
                 */
                if(!$alias){
                    // Done if the URL is exactly the same in the matching Location.
                    if($matchingLocation->getUrl() == $url || $matchingLocation->getUrl() == $urlAltScheme){
                        return $matchingLocation;
                    }

                    // Let's move on to create a new Location with the default module.
                    $locationService = $this->container->get('campaignchain.core.location');
                    /** @var LocationModule $locationModule */
                    $locationModule = $locationService->getLocationModule(
                        self::DEFAULT_LOCATION_BUNDLE_NAME,
                        self::DEFAULT_LOCATION_MODULE_IDENTIFIER
                    );

                    if(!$locationModule){
                        throw new \Exception(
                            'No Location module found for bundle "'.
                            $matchingLocation->getChannel()->getBundle()->getName().' and module '.
                            $matchingLocation->getChannel()->getChannelModule()->getIdentifier().'"'
                        );
                    }
                } else {
                    /*
                     * Get the Location module within the matching Location's
                     * Channel and with the given tracking alias.
                     */
                    /** @var LocationModule $locationModule */
                    $locationModule = $this->getLocationModuleByTrackingAlias(
                        $matchingLocation->getChannel()->getChannelModule(),
                        $alias
                    );

                    // Done if the matching Location also matches the alias and URL.
                    if(
                        $matchingLocation->getLocationModule() == $locationModule &&
                        ($matchingLocation->getUrl() == $url || $matchingLocation->getUrl() == $urlAltScheme)
                    ){
                        return $matchingLocation;
                    }

                    /*
                     * See if there is already another Location that matches the
                     * aliase's Location module and the URL.
                     */
                    $repository = $this->em
                        ->getRepository('CampaignChainCoreBundle:Location');

                    $query = $repository->createQueryBuilder('location')
                        ->where('location.locationModule = :locationModule')
                        ->andWhere('(location.url = :url OR location.url = :url_alt_scheme')
                        ->setParameter('locationModule', $locationModule)
                        ->setParameter('url', $url)
                        ->setParameter('url_alt_scheme', $urlAltScheme)
                        ->getQuery();

                    /** @var Location $location */
                    $location = $query->getOneOrNullResult();

                    // We found an existing Location, we're done.
                    if($location){
                        return $location;
                    }

                    if(!$locationModule){
                        throw new \Exception(
                            'Cannot map tracking alias "'.$alias.'" to a "'.
                            'Location module that belongs to Channel module "'.
                            $matchingLocation->getChannel()->getBundle()->getName().'/'.
                            $matchingLocation->getChannel()->getChannelModule()->getIdentifier().'"'
                        );
                    }
                }

                /*
                 * If the matching Location provides auto-generation of Locations,
                 * then let's create a new child Location.
                 */
                $ctaServiceName = $locationModule->getServices()['job_cta'];
                if($ctaServiceName){
                    // Create the new Location
                    $location = new Location();
                    $location->setUrl($url);
                    $location->setOperation($operation);
                    $location->setChannel($matchingLocation->getChannel());
                    $location->setParent($matchingLocation);

                    // Update the Location module to be the current
                    // one.
                    $location->setLocationModule(
                        $locationModule
                    );

                    // Let the module's service process the new
                    // Location.
                    $ctaService = $this->container->get($ctaServiceName);
                    return $ctaService->execute($location);
                } else {
                    throw new \Exception(
                        'No CTA Job service defined for Location module '.
                        'of bundle "'.$locationModule->getBundle()->getName().'" '.
                        'and module "'.$locationModule->getIdentifier().'"'
                    );
                }
            } else {
                return false;
            }
        }

        throw new \Exception(
            'The URL '.$url.' does not exist.'
        );
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
        return $this->twigExt->tplTeaser($location, $options);
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
        $location = $this->getLocation($id);
        $removableActivities = new ArrayCollection();
        $notRemovableActivities = new ArrayCollection();

        $accessToken = $this->em
            ->getRepository('CampaignChainSecurityAuthenticationClientOAuthBundle:Token')
            ->findOneBy(['location' => $location]);

        try {
            $this->em->getConnection()->beginTransaction();

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

            //Hack to find the belonging entities which has to be deleted first
            $bundleName = explode('/', $location->getLocationModule()->getBundle()->getName());
            $classPrefix = 'CampaignChain\\' . implode('\\', array_map(function ($e) {
                    return ucfirst($e);
                }, explode('-', $bundleName[1]))) . 'Bundle';

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

            if ($channel->getLocations()->isEmpty()) {
                $this->em->remove($channel);
                $this->em->flush();
            }

            $this->em->getConnection()->commit();
        } catch(\Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
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