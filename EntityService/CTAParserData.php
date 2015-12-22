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

class CTAParserData
{
    /**
     * @var array
     */
    protected $urlsTracked = array();

    /**
     * @var array
     */
    protected $urlsNotTracked = array();

    /**
     * @var string
     */
    protected $content;

    /**
     * @param array $urlsTracked
     */
    public function addUrlTracked($urlTracked)
    {
        $this->urlsTracked[] = $urlTracked;
    }

    /**
     * @return array
     */
    public function getUrlsTracked()
    {
        return $this->urlsTracked;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param array $urlsNotTracked
     */
    public function addUrlNotTracked($urlNotTracked)
    {
        $this->urlsNotTracked[] = $urlNotTracked;
    }

    /**
     * @return array
     */
    public function getUrlsNotTracked()
    {
        return $this->urlsNotTracked;
    }


}