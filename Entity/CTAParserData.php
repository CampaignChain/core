<?php
/**
 *
 * This file is part of the CampaignChain package.
 *
 *  (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *
 */

namespace CampaignChain\CoreBundle\Entity;

use CampaignChain\CoreBundle\Util\ParserUtil;

/**
 * Class CTAParserData
 * @package CampaignChain\CoreBundle\Entity
 */
class CTAParserData
{
    const URL_TYPE_ORIGINAL = 'originalUrl';
    const URL_TYPE_EXPANDED = 'expandedUrl';
    const URL_TYPE_TRACKED = 'trackingUrl';
    const URL_TYPE_SHORTENED_TRACKED = 'shortenedTrackingUrl';

    /**
     * @var string content
     */
    protected $content;

    /**
     * @var array list of tracked CTAs
     */
    protected $trackedCTAs = array();

    /**
     * @var array list of shortened CTAs
     */
    protected $untrackedCTAs = array();

    /**
     * @var array list of untracked urls
     */
    protected $untrackedUrls = array();
    /**
     * @var array map of urls and how to replace them
     */
    protected $replacementUrls = array();

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Return the content with urls replaced. Various formats are available.
     *
     * @param mixed $type
     * @return string
     */
    public function getContent($type = self::URL_TYPE_SHORTENED_TRACKED)
    {
        if (self::URL_TYPE_ORIGINAL === $type) {
            return $this->content;
        } else {
            return ParserUtil::replaceURLsInText($this->content, $this->getReplacementUrls($type));
        }
    }

    /**
     * Keep a record of a tracked CTA and queue it for replacement
     *
     * @param CTA $cta
     */
    public function addTrackedCTA(CTA $cta)
    {
        $this->trackedCTAs[] = $cta;

        $this->queueForReplacement(
            $cta->getOriginalUrl(),
            $cta->getExpandedUrl(),
            $cta->getTrackingUrl(),
            $cta->getShortenedTrackingUrl()
        );
    }

    /**
     * Keep a record of a untracked CTA and queue it for replacement
     *
     * @param CTA $cta
     */
    public function addUntrackedCTA(CTA $cta)
    {
        $this->untrackedCTAs[] = $cta;

        if($cta->getShortenedExpandedUrl()) {
            $trackingUrl = $cta->getOriginalUrl();
            $shortenedUrl = $cta->getShortenedExpandedUrl();
        } elseif($cta->getShortenedUniqueExpandedUrl()){
            $trackingUrl = $cta->getUniqueExpandedUrl();
            $shortenedUrl = $cta->getShortenedUniqueExpandedUrl();
        } else {
            $trackingUrl = $cta->getOriginalUrl();
            $shortenedUrl = $cta->getOriginalUrl();
        }

        $this->queueForReplacement(
            $cta->getOriginalUrl(),
            $cta->getExpandedUrl(),
            $trackingUrl,
            $shortenedUrl
        );
    }

    /**
     * Queue urls for replacement
     *
     * @param string $originalUrl
     * @param string $expandedUrl
     * @param string $trackingUrl
     * @param string $shortenedTrackingUrl
     */
    public function queueForReplacement($originalUrl, $expandedUrl, $trackingUrl, $shortenedTrackingUrl)
    {
        $this->replacementUrls[$originalUrl][] = array(
            self::URL_TYPE_EXPANDED => $expandedUrl,
            self::URL_TYPE_TRACKED => $trackingUrl,
            self::URL_TYPE_SHORTENED_TRACKED => $shortenedTrackingUrl
        );
    }

    /**
     * Filter replacement urls so only one format is left.
     *
     * @param mixed $type
     * @return array map of filtered replacement urls
     */
    public function getReplacementUrls($type)
    {
        $replacementUrls = array_map(function ($a1) use ($type) {
            return array_map(function ($a2) use ($type) {
                return $a2[$type];
            }, $a1);
        }, $this->replacementUrls);

        return $replacementUrls;
    }

    /**
     * @return array list of tracked CTA objects
     */
    public function getTrackedCTAs()
    {
        return $this->trackedCTAs;
    }

    /**
     * @return array list of untracked CTA objects
     */
    public function getUntrackedCTAs()
    {
        return $this->untrackedCTAs;
    }
}