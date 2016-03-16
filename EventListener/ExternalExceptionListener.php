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

namespace CampaignChain\CoreBundle\EventListener;

use CampaignChain\CoreBundle\Exception\ExternalApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * This will log all external exception events (e.g. API calls) to a separate channel
 * @package CampaignChain\CoreBundle\EventListener
 */
class ExternalExceptionListener
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ExternalExceptionListener constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {

        $exception = $event->getException();

        if ($exception instanceof ExternalApiException) {
            $this->logger->error($exception->getMessage());
        }
    }

}