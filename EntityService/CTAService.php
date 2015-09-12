<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EntityService;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CampaignChain\CoreBundle\Util\ParserUtil;
use CampaignChain\CoreBundle\Entity\CTA;

class CTAService
{
    const FORMAT_HTML = 'html';
    const FORMAT_TXT = 'txt';
    const TRACKING_ID_NAME = 'campaignchain-id';

    protected $em;
    protected $container;

    /**
     * @param EntityManager $em
     * @param ContainerInterface $container
     */
    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function processCTAs($content, $operation, $format = self::FORMAT_TXT)
    {
        // TODO:
        // - Manage links to Locations that will be created by CampaignChain once
        //   an Operation gets executed

        $ctaParserData = new CTAParserData();

        // Replace the URLs with the tracking URLs in the message.
        $originalUrls = array();
        // Find all URLs
        $originalUrls = ParserUtil::extractURLsFromText($content);

        if(count($originalUrls)){
            $trackingUrls = array();
            foreach($originalUrls as $originalUrl){
                // If a shortened URL, then expand it.
                if(ParserUtil::isShortUrl($originalUrl)){
                    $headers = get_headers($originalUrl, 1);
                    $expandedUrl = $headers['Location'];
                    $shortUrl = $originalUrl;
                } else {
                    $expandedUrl = $originalUrl;
                    $shortUrl = null;
                }

                // Check if the URL is a Location.
                $locationService = $this->container->get('campaignchain.core.location');
                $location = $locationService->findLocationByUrl($expandedUrl, $operation);

                // If at this point we have a valid Location mapped to the URL,
                // then proceed with turning it into a trackable URL.
                if($location){
                    // Append the CampaignChain Tracking ID to the URL.
                    $trackingId = $this->generateTrackingId();
                    $trackingUrl = ParserUtil::addUrlParam(
                        $expandedUrl,
                        self::TRACKING_ID_NAME,
                        $trackingId);

                    // Shorten Tracking URL, but only if not on localhost.
                    $trackingUrlParts = parse_url($trackingUrl);

                    if( $trackingUrlParts['host'] != 'localhost' &&
                        $trackingUrlParts['host'] != '127.0.0.1'
                    ){
                        $bitlyService = $this->container->get('hpatoio_bitly.client');
                        $response = $bitlyService->Shorten(array("longUrl" => $trackingUrl));

                        $shortenedUrl = $response['url'];
                    } else {
                        $shortenedUrl = $trackingUrl;
                    }

                    // Save the URL as a CTA
                    $cta = new CTA();
                    $cta->setTrackingId($trackingId);
                    $cta->setOperation($operation);
                    $cta->setUrl($expandedUrl);
                    $cta->setShortUrl($shortUrl);
                    $cta->setLocation($location);
                    $location->addCta($cta);
                    $this->em->persist($cta);

                    $ctaParserData->addUrlTracked($shortenedUrl);
                    $replaceUrls[$originalUrl][] = $shortenedUrl;
                } else {
                    // It was not possible to map the URL to a Location, hence
                    // keep it as is in the text.
                    $ctaParserData->addUrlNotTracked($originalUrl);
                    $replaceUrls[$originalUrl][] = $originalUrl;
                }
            }
            $this->em->flush();

            $newText = ParserUtil::replaceURLsInText($content, $replaceUrls);

            $ctaParserData->setContent($newText);

            return $ctaParserData;
        }

        $ctaParserData->setContent($content);

        return $ctaParserData;
    }

    /*
     * Generates a Tracking ID
     *
     * This method also makes sure that the ID is unique, i.e. that it does
     * not yet exist for another CTA.
     *
     * @return string
     */
    public function generateTrackingId()
    {
        $trackingId = md5(uniqid(mt_rand(), true));

        // Check with DB, whether already exists. If yes, then generate new one and check again.
        $cta = $this->em->getRepository('CampaignChainCoreBundle:CTA')->findOneByTrackingId($trackingId);

        if($cta){
            return $this->generateTrackingId();
        } else {
            return $trackingId;
        }
    }
}