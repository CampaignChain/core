<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EventListener;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class LoginListener
{
    protected $security;
    protected $session;

    /**
     * Constructs a new instance of SecurityListener.
     *
     * @param SecurityContext $security The security context
     * @param Session $session The session
     */
    public function __construct(SecurityContext $security, Session $session)
    {
        $this->security = $security;
        $this->session = $session;
    }

    /**
     * Invoked after a successful login.
     *
     * @param InteractiveLoginEvent $event The event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $this->session->set('campaignchain.locale', $this->security->getToken()->getUser()->getLocale());
        $this->session->set('campaignchain.timezone', $this->security->getToken()->getUser()->getTimezone());
        $this->session->set('campaignchain.dateFormat', $this->security->getToken()->getUser()->getDateFormat());
        $this->session->set('campaignchain.timeFormat', $this->security->getToken()->getUser()->getTimeFormat());
    }

    public function setLocale(GetResponseEvent $event)
    {
        // Execute only if the user is logged in.
        if( $this->security->isGranted('IS_AUTHENTICATED_REMEMBERED') ){
                // authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
            if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
                return;
            }

            $request = $event->getRequest();
            $request->setLocale($this->security->getToken()->getUser()->getLocale());
        }
    }
}