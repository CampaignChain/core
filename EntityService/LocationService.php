<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EntityService;

use Doctrine\ORM\EntityManager;
use CampaignChain\CoreBundle\Entity\Channel;
use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\CoreBundle\Entity\Operation;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LocationService
{
    protected $em;
    protected $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
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
}