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

namespace CampaignChain\CoreBundle\Service\UrlShortener;

use CampaignChain\CoreBundle\Entity\LinkInterface;

/**
 * Interface UrlShortenerServiceInterface
 * @package CampaignChain\CoreBundle\Service\UrlShortener
 */
interface UrlShortenerServiceInterface
{

    /**
     * Shorten a link
     *
     * @param LinkInterface $link
     * @return mixed
     */
    public function shorten(LinkInterface $link);

    /**
     * Expand a link
     *
     * @param LinkInterface $link
     * @return mixed
     */
    public function expand(LinkInterface $link);
}