<?php
/**
 *
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace CampaignChain\CoreBundle\EventListener;


use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Save date and time settings to a session on various events
 * Class UserDateTimeListener
 *
 * @package CampaignChain\CoreBundle\EventListener
 */
class UserDateTimeListener implements EventSubscriberInterface
{

    /**
     * - listen to Symfony AND FOSUser login events
     * - listen to profile change events for the current user
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::SECURITY_IMPLICIT_LOGIN => array('onImplicitLogin', 100),
            FOSUserEvents::PROFILE_EDIT_SUCCESS => array('onProfileEditSuccess', 100),
            SecurityEvents::INTERACTIVE_LOGIN => array('onSecurityInteractiveLogin', 100),
        );
    }

    /**
     * @param UserEvent $event
     */
    public function onImplicitLogin(UserEvent $event)
    {
        $this->storeDateTimeSettingsInSession(
            $event->getUser(),
            $event->getRequest()->getSession()
        );
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $this->storeDateTimeSettingsInSession(
            $event->getAuthenticationToken()->getUser(),
            $event->getRequest()->getSession()
        );
    }

    /**
     * @param UserEvent $event
     */
    public function onProfileEditSuccess(UserEvent $event)
    {

        $this->storeDateTimeSettingsInSession(
            $event->getUser(),
            $event->getRequest()->getSession()
        );
    }

    /**
     * Make user date and time settings sticky
     *
     * @param UserInterface $user
     * @param SessionInterface $session
     */
    protected function storeDateTimeSettingsInSession(UserInterface $user, SessionInterface $session)
    {
        $session->set('campaignchain.locale', $user->getLocale());
        $session->set('campaignchain.timezone', $user->getTimezone());
        $session->set('campaignchain.dateFormat', $user->getDateFormat());
        $session->set('campaignchain.timeFormat', $user->getTimeFormat());
    }
}



