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
use CampaignChain\CoreBundle\Exception\ExternalApiException;
use Mremi\UrlShortener\Exception\InvalidApiResponseException;
use Mremi\UrlShortener\Provider\UrlShortenerProviderInterface;

/**
 * Class UrlShortenerService
 * @package CampaignChain\CoreBundle\Service
 */
class UrlShortenerService implements UrlShortenerServiceInterface
{
    /**
     * @var UrlShortenerProviderInterface
     */
    private $provider;

    /**
     * UrlShortenerService constructor.
     * @param UrlShortenerProviderInterface $provider
     */
    public function __construct(UrlShortenerProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @inheritDoc
     */
    public function shorten(LinkInterface $link)
    {
        try {
            $this->provider->shorten($link);
        } catch (InvalidApiResponseException $e) {
            throw new ExternalApiException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function expand(LinkInterface $link)
    {
        try {
            $this->provider->expand($link);
        } catch (InvalidApiResponseException $e) {
            throw new ExternalApiException($e->getMessage(), $e->getCode(), $e);
        }
    }


}