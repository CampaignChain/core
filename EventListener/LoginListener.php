<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EventListener;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class LoginListener
{
    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;

    /**
     * @var Session
     */
    private $session;

    /**
     * Constructs a new instance of SecurityListener.
     *
     * @param TokenStorage $tokenStorage
     * @param AuthorizationChecker $authorizationChecker
     * @param Session $session The session
     */
    public function __construct(TokenStorage $tokenStorage, AuthorizationChecker $authorizationChecker, Session $session)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->session = $session;
    }

    /**
     * Invoked after a successful login.
     *
     * @param InteractiveLoginEvent $event The event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $this->session->set('campaignchain.locale', $this->tokenStorage->getToken()->getUser()->getLocale());
        $this->session->set('campaignchain.timezone', $this->tokenStorage->getToken()->getUser()->getTimezone());
        $this->session->set('campaignchain.dateFormat', $this->tokenStorage->getToken()->getUser()->getDateFormat());
        $this->session->set('campaignchain.timeFormat', $this->tokenStorage->getToken()->getUser()->getTimeFormat());
    }

    public function setLocale(GetResponseEvent $event)
    {
        // Execute only if the user is logged in.
        if( $this->tokenStorage->getToken() && $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED') ){
                // authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
            if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
                return;
            }

            $request = $event->getRequest();
            $request->setLocale($this->tokenStorage->getToken()->getUser()->getLocale());
        }
    }
}