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

use CampaignChain\CoreBundle\Entity\Link;
use Doctrine\ORM\EntityManager;
use CampaignChain\CoreBundle\Entity\CTAParserData;
use CampaignChain\CoreBundle\Entity\CTA;
use CampaignChain\CoreBundle\Service\UrlShortener\UrlShortenerServiceInterface;
use CampaignChain\CoreBundle\Util\ParserUtil;


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

    /**
     * CTAService constructor.
     * @param EntityManager $em
     * @param UrlShortenerServiceInterface $urlShortener
     * @param LocationService $locationService
     */
    public function __construct(
        $trackingIdName,
        EntityManager $em,
        UrlShortenerServiceInterface $urlShortener,
        LocationService $locationService,
        $trackingJsMode,
        $baseUrl
    ) {
        $this->trackingIdName = $trackingIdName;
        $this->em = $em;
        $this->urlShortener = $urlShortener;
        $this->locationService = $locationService;
        $this->trackingJsMode = $trackingJsMode;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Replace the URLs with the tracking URLs in the message.
     *
     * TODO: Manage links to Locations that will be created by CampaignChain once an Operation gets executed
     *
     * @param $content
     * @param $operation
     * @param string $format
     * @return CTAParserData
     */
    public function processCTAs($content, $operation, $format = self::FORMAT_TXT)
    {

        $ctaParserData = new CTAParserData();
        $ctaParserData->setContent($content);

        // extract urls from content
        $contentUrls = $this->extractUrls($content);

        // no urls? nothing to do!
        if (empty($contentUrls)) {
            return $ctaParserData;
        }

        // process each url
        foreach ($contentUrls as $url) {

            // expand if necessary
            $expandedUrl = $this->expandUrl($url);

            // Mapped to location? Generate tracking url and create CTA
            if ($location = $this->locationService->findLocationByUrl($expandedUrl, $operation)) {

                // create cta
                $cta = new CTA();
                $cta->setTrackingId($this->generateTrackingId());
                $cta->setOperation($operation);

                $cta->setOriginalUrl($url);
                $cta->setExpandedUrl($expandedUrl);

                // generate tracking and short url
                $cta->setTrackingUrl(
                    $this->generateTrackingUrl($expandedUrl, $cta->getTrackingId())
                );

                $cta->setShortenedTrackingUrl(
                    $this->getShortenedUrl($cta->getTrackingUrl())
                );

                $cta->setLocation($location);

                // add to location and persist
                $location->addCta($cta);
                $this->em->persist($cta);

                $ctaParserData->addTrackedCTA($cta);

            // otherwise keep the original url
            } else {
                $ctaParserData->addUntrackedUrl($url);
            }
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
     * Use shortener service to shorten url
     *
     * @param $url
     * @return mixed
     */
    protected function getShortenedUrl($url)
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
}