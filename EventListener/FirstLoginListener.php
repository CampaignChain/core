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

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;


/*
 * Do something on the first login of any user
 *
 */
class FirstLoginListener implements EventSubscriberInterface
{

    public function __construct()
    {
    }

    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::SECURITY_IMPLICIT_LOGIN => array('onImplicitLogin', 100),
            SecurityEvents::INTERACTIVE_LOGIN => array('onSecurityInteractiveLogin', 100),
        );
    }

    /**
     * @param UserEvent $event
     */
    public function onImplicitLogin(UserEvent $event)
    {
        $user = $event->getUser();

        // if first login, set a session flag so we can detect it in the controller
        if ($user->isFirstLogin()) {
            $event->getRequest()->getSession()->set('isFirstLogin', true);
        }
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();

        // if first login, set a session flag so we can detect it in the controller
        if ($user instanceof UserInterface && $user->isFirstLogin()) {
            $event->getRequest()->getSession()->set('isFirstLogin', true);
        }
    }
}
