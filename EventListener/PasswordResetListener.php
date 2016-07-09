<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EventListener;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


/**
 * Listen to FOSUserBundle reset event to modifiy the response
 *
 * @package CampaignChain\CoreBundle\EventListener
 */
class PasswordResetListener implements EventSubscriberInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $router;


    /**
     *
     */
    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    /**
     * FOSUserBundle will dispatch an event and we will subscribe to it
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::RESETTING_RESET_SUCCESS => 'onPasswordResettingSuccess',
        );
    }

    /**
     * Redirect to home after successfully resetting the password
     *
     * @param FormEvent $event
     */
    public function onPasswordResettingSuccess(FormEvent $event)
    {
        $url = $this->router->generate('campaignchain_core_homepage');

        $event->setResponse(new RedirectResponse($url));
    }
}