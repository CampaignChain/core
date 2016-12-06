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

use CampaignChain\CoreBundle\Entity\Link;
use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\CoreBundle\Entity\Operation;
use Doctrine\Common\Persistence\ManagerRegistry;
use CampaignChain\CoreBundle\Entity\CTAParserData;
use CampaignChain\CoreBundle\Entity\CTA;
use CampaignChain\CoreBundle\Service\UrlShortener\UrlShortenerServiceInterface;
use CampaignChain\CoreBundle\Util\ParserUtil;
use Doctrine\ORM\NoResultException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;

class CTAService
{
    const FORMAT_HTML = 'html';
    const FORMAT_TXT = 'txt';

    protected $trackingIdName;
    protected $em;
    protected $urlShortener;
    protected $locationService;
    protected $trackingJsMode;
    protected $baseUrl;
    protected $uniqueParamName;

    /**
     * CTAService constructor.
     * @param ManagerRegistry $managerRegistry
     * @param UrlShortenerServiceInterface $urlShortener
     * @param LocationService $locationService
     */
    public function __construct(
        $trackingIdName,
        ManagerRegistry $managerRegistry,
        UrlShortenerServiceInterface $urlShortener,
        LocationService $locationService,
        $trackingJsMode,
        $baseUrl,
        $uniqueParamName
    ) {
        $this->trackingIdName = $trackingIdName;
        $this->em = $managerRegistry->getManager();
        $this->urlShortener = $urlShortener;
        $this->locationService = $locationService;
        $this->trackingJsMode = $trackingJsMode;
        $this->baseUrl = $baseUrl;
        $this->uniqueParamName = $uniqueParamName;
    }

    /**
     * Stores all Calls-to-Action (i.e. URLs) included in a text.
     *
     * URLs that point to a connected Location will be tracked by adding a
     * tracking ID and then shortening the URL.
     *
     * URLs that do not point to a connected Location will be stored without
     * shortening them. This will allow to map a Location that might be added
     * later.
     *
     * @param $content
     * @param $operation
     * @param array $options    'shorten_all':
     *                              Shortens not only the URLs that point to a
     *                              connected Location, but also those that don't.
     *                          'shorten_all_unique':
     *                              If true, ensure that if the Operation is being
     *                              executed multiple times (e.g. a Tweet in a
     *                              repeating campaign), each shortened URL is
     *                              unique (e.g. to avoid duplicate message error).
     *                              If false (which is the default), either a new
     *                              shortened URL will be created (if a connected
     *                              Location exists), or an existing one returned.
     *                              Overrides 'shorten_all'.
     *                          'graceful_url_exists':
     *                              Gracefully handles URL check if there's a
     *                              timeout.
     * @return CTAParserData
     *
     * @todo Manage links to Locations that will be created by CampaignChain once an Operation gets executed
     */
    public function processCTAs($content, Operation $operation, array $options = array())
    {
        /*
         * Set default options.
         */
        $resolver = new OptionsResolver();
        $resolver->setDefaults(array(
            'format' => self::FORMAT_TXT,
            'shorten_all' => false,
            'shorten_all_unique' => false,
            'graceful_url_exists' => true,
        ));

        // Option 'shorten_all_unique' overrides 'shorten_all'.
        $resolver->setDefault('shorten_all', function (Options $options) {
            if (true === $options['shorten_all_unique']) {
                return false;
            }
        });

        $options = $resolver->resolve($options);

        $ctaParserData = new CTAParserData();
        $ctaParserData->setContent($content);

        // extract URLs from content
        $contentUrls = $this->extractUrls($content);

        // no urls? nothing to do!
        if (empty($contentUrls)) {
            return $ctaParserData;
        }

        // process each url
        foreach ($contentUrls as $url) {
            $location = $this->locationService->findLocationByUrl(
                $this->expandUrl($url), $operation, null, array(
                    'graceful_url_exists' => $options['graceful_url_exists']
                )
            );

            if(!$location){
                $location = null;
            }

            $cta = $this->createCTA($url, $operation, $location);
            if($location) {
                $location->addCta($cta);
            }

            // Deal with options.
            if(!$options['shorten_all_unique']){
                /*
                 * A tracked CTA does exist and we don't want to use
                 * unique shortened URLs, so let's use the existing
                 * shortened URL.
                 */
                if(!$location && $options['shorten_all']){
                    $cta->setShortenedExpandedUrl(
                        $this->getShortenedUrl($cta->getExpandedUrl())
                    );
                }
            } else {
                if($location) {
                    $uniqueUrl = $this->getUniqueUrl(
                        $cta->getTrackingUrl(),
                        $this->countShortenedUrls($cta->getOriginalUrl(), $operation, $location)
                    );
                    $cta->setTrackingUrl($uniqueUrl);
                    $cta->setShortenedTrackingUrl(
                        $this->getShortenedUrl($uniqueUrl)
                    );
                } else {
                    $uniqueUrl = $this->getUniqueUrl(
                        $cta->getExpandedUrl(),
                        $this->countShortenedUrls($cta->getOriginalUrl(), $operation, null)
                    );
                    $cta->setUniqueExpandedUrl($uniqueUrl);
                    $cta->setShortenedUniqueExpandedUrl(
                        $this->getShortenedUrl($uniqueUrl)
                    );
                }
            }

            if($location) {
                $ctaParserData->addTrackedCTA($cta);
            } else {
                $ctaParserData->addUntrackedCTA($cta);
            }

            $this->em->persist($cta);
        }

        $this->em->flush();

        return $ctaParserData;
    }

    /*
     * Generates a unique, unused CTA tracking id
     *
     * @return string
     */
    protected function generateTrackingId()
    {
        $ctaRepository = $this->em->getRepository('CampaignChainCoreBundle:CTA');

        // loop until there is a unused id
        while (true) {
            $trackingId = md5(uniqid(mt_rand(), true));

            if (!$ctaRepository->findOneByTrackingId($trackingId)) {
                return $trackingId;
            }
        }
    }

    /**
     * Expand if url is already a short url
     *
     * @param $url
     * @return mixed
     */
    protected function expandUrl($url)
    {
        // skip if no short url
        if (!ParserUtil::isShortUrl($url)) {
            return $url;
        }

        $header_location = get_headers($url, 1)['Location'];

        return $header_location ?: $url;
    }

    /**
     * Append the CampaignChain Tracking ID to the URL.
     *
     * @param $url
     * @param $trackingId
     * @return mixed|string
     */
    protected function generateTrackingUrl($url, $trackingId)
    {
        $trackingUrl = ParserUtil::addUrlParam($url, $this->trackingIdName, $trackingId);
        
        // Pass the base URL if tracking script runs in dev or dev-stay mode.
        if($this->trackingJsMode == 'dev' || $this->trackingJsMode == 'dev-stay'){
            $trackingUrl = ParserUtil::addUrlParam($trackingUrl, 'cctapi', urlencode($this->baseUrl));
        }

        return $trackingUrl;
    }

    /**
     * Modifies a URL so that it results into a unique short URL.
     *
     * If a URL has no fragment, we add a unique ID as one. For example:
     * http://www.example.com/#2
     *
     * If a URL fragment exists, we add a query parameter instead. For example:
     * http:/www.example.com/?ccshortly=2#fragment
     *
     * @param string $url
     * @param integer $uniqueId
     * @return string
     */
    public function getUniqueUrl($url, $uniqueId)
    {
        // If the shortened URL is supposed to be unique, then we modify the URL.
        $urlParts = parse_url($url);
        if(!isset($urlParts['fragment'])){
            //
            $url .= '#'.$uniqueId;
        } else {
            //
            $url = ParserUtil::addUrlParam($url, $this->uniqueParamName, $uniqueId);
        }

        return $url;
    }

    /**
     * Use shortener service to shorten url
     *
     * @param $url
     * @param integer $uniqueId
     * @return mixed
     */
    protected function getShortenedUrl($url, $uniqueId = null)
    {
        // skip if localhost
        if (in_array(parse_url($url, PHP_URL_HOST), array('localhost', '127.0.0.1'))) {
            return $url;
        }

        $link = new Link();
        $link->setLongUrl($url);

        $this->urlShortener->shorten($link);

        return $link->getShortUrl();
    }

    /**
     * @param $content
     * @return mixed
     */
    protected function extractUrls($content)
    {
        return ParserUtil::extractURLsFromText($content);
    }

    /**
     * @param $url
     * @param Operation $operation
     * @param Location|null $location
     * @return CTA
     */
    protected function createCTA($url, Operation $operation, Location $location = null)
    {
        $cta = new CTA();
        $cta->setOperation($operation);
        $cta->setOriginalUrl($url);
        $cta->setExpandedUrl($this->expandUrl($url));
        if($location){
            $cta->setLocation($location);
            $cta->setTrackingId($this->generateTrackingId());
            $cta->setTrackingUrl($this->generateTrackingUrl(
                $cta->getExpandedUrl(), $cta->getTrackingId())
            );
            $cta->setShortenedTrackingUrl(
                $this->getShortenedUrl($cta->getTrackingUrl())
            );
        }

        return $cta;
    }

    /**
     * Counts the number of shortened URLs available for the same URL.
     *
     * @param $url
     * @param Operation $operation
     * @param Location $location
     * @return mixed
     */
    protected function countShortenedUrls($url, Operation $operation, Location $location = null)
    {
        if($operation->getActivity()->getCampaign()->getInterval()){
            $isParentCampaign = false;
            $campaign = $operation->getActivity()->getCampaign();
        } elseif(
            $operation->getActivity()->getCampaign()->getParent() &&
            $operation->getActivity()->getCampaign()->getParent()->getInterval()
        ){
            $isParentCampaign = true;
            $campaign = $operation->getActivity()->getCampaign()->getParent();
        }

        $qb = $this->em
            ->getRepository('CampaignChainCoreBundle:CTA')
            ->createQueryBuilder('cta')
            ->from('CampaignChainCoreBundle:Campaign', 'c')
            ->from('CampaignChainCoreBundle:Activity', 'a')
            ->from('CampaignChainCoreBundle:Operation', 'o')
            ->where('cta.originalUrl = :originalUrl')
            ->setParameter('originalUrl', $url)
            ->andWhere('a.location = :location')
            ->setParameter('location', $operation->getActivity()->getLocation())
            ->andWhere('cta.operation = o.id')
            ->andWhere('o.activity = a.id');
        if(!$isParentCampaign) {
            $qb->andWhere('a.campaign = :campaign');
        } else {
            $qb->andWhere('a.campaign = c.id')
                ->andWhere('c.parent = :campaign');
        }
        $qb->setParameter('campaign', $campaign);

        if($location) {
            $qb->andWhere('cta.location = :location')
                ->setParameter('location', $location);
        } else {
            $qb->andWhere('cta.location IS NULL');
        }

        $results = $qb->getQuery()->getResult();

        return count($results);
    }
}