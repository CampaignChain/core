<?php


namespace CampaignChain\CoreBundle\EventListener;


use CampaignChain\CoreBundle\Entity\User;
use CampaignChain\CoreBundle\EntityService\UserService;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class UserAvatarListener
{
    /** @var UserService */
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Download avatar image from Gravatar if there wasn't one uploaded
     *
     * @param User $user
     * @param LifecycleEventArgs $event
     */
    public function prePersist(User $user, LifecycleEventArgs $event)
    {
        $avatarImage = $user->getAvatarImage();
        if (empty($avatarImage)) {
            $this->userService->downloadGravatar($user);
        }
    }

    public function preUpdate(User $user, PreUpdateEventArgs $event)
    {
        // TODO: delete old image when avatar has been changed
    }
}