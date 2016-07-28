<?php
/*
 * Copyright 2016 CampaignChain, Inc. <info@campaignchain.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
        /*
         * Avoid OAuth Authorization attack as described at
         * http://software-security.sans.org/blog/2011/03/07/oauth-authorization-attacks-secure-implementation.
         */
        if ($this->session->has('_security.target_path')) {
            if (false !== strpos($this->session->get('_security.target_path'), $this->generateUrl('fos_oauth_server_authorize'))) {
                $this->session->set('_fos_oauth_server.ensure_logout', true);
            }
        }

    }

    public function setLocale(GetResponseEvent $event)
    {
        // Execute only if the user is logged in.
        if( $this->tokenStorage->getToken() && $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED') && $this->tokenStorage->getToken()->getUser() ){
                // authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
            if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
                return;
            }

            $request = $event->getRequest();
            $request->setLocale($this->tokenStorage->getToken()->getUser()->getLocale());
        }
    }
}