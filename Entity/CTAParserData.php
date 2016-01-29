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

/**
 * Class CTAParserData
 * @package CampaignChain\CoreBundle\Entity
 */
class CTAParserData
{

    /**
     * @var array list of tracked urls
     */
    protected $trackedUrls = array();

    /**
     * @var array list of untracked urls
     */
    protected $untrackedUrls = array();

    /**
     * @var array list of urls and their replacements
     */
    protected $replacementUrls = array();

    /**
     * @var string original content
     */
    protected $content;

    /**
     * @param $originalUrl unchanged url from content
     * @param $trackedUrl (shortened) url with tracking id
     */
    public function addTrackedUrl($originalUrl, $trackedUrl)
    {
        $this->trackedUrls[] = $trackedUrl;

        // queue for replacement
        $this->addReplacementUrl($originalUrl, $trackedUrl);
    }


    /**
     * @return array
     */
    public function getTrackedUrls()
    {
        return $this->trackedUrls;
    }

    /**
     * @param $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param $url original url
     */
    public function addUntrackedUrl($url)
    {
        $this->untrackedUrls[] = $url;

        // queue for replacement
        // even if the url was not changed it must be queued to keep the order
        $this->addReplacementUrl($url, $url);
    }

    /**
     * @return array
     */
    public function getUntrackedUrls()
    {
        return $this->untrackedUrls;
    }

    /**
     * @param $originalUrl original url from the content
     * @param $replacementUrl url to replace with (usually the tracked url)
     */
    public function addReplacementUrl($originalUrl, $replacementUrl)
    {
        $this->replacementUrls[$originalUrl][] = $replacementUrl;
    }

    /**
     * @return array
     */
    public function getReplacementUrls()
    {
        return $this->replacementUrls;
    }
}