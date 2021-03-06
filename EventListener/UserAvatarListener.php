<?php


namespace CampaignChain\CoreBundle\EventListener;


use CampaignChain\CoreBundle\Entity\User;
use CampaignChain\CoreBundle\EntityService\UserService;
use CampaignChain\CoreBundle\Service\FileUploadService;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Gaufrette\Filesystem;
use Liip\ImagineBundle\Binary\Loader\LoaderInterface as ImageLoaderInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Oneup\UploaderBundle\Uploader\File\GaufretteFile;

class UserAvatarListener
{
    /** @var UserService */
    private $userService;

    /** @var FileUploadService */
    private $fileUploadService;

    /** @var ImageLoaderInterface */
    private $dataManager;

    /** @var FilterManager */
    private $filterManager;

    /**
     * UserAvatarListener constructor.
     * @param UserService $userService
     * @param FileUploadService $fileUploadService
     * @param FilterManager $filterManager
     */
    public function __construct(UserService $userService, FileUploadService $fileUploadService, FilterManager $filterManager)
    {
        $this->userService = $userService;
        $this->fileUploadService = $fileUploadService;
        $this->filterManager = $filterManager;
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
            $this->userService->downloadAndSetGravatarImage($user);
        }
    }

    /**
     * Delete old avatar image from disk if it has been changed
     *
     * @param User $user
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(User $user, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField('avatarImage')) {
            $oldAvatarImage = $event->getOldValue('avatarImage');
            if (!empty($oldAvatarImage)) {
                $this->userService->deleteAvatar($oldAvatarImage);
            }
        }
    }

    /**
     * Delete avatar image from disk on user deletion
     *
     * @param User $user
     * @param LifecycleEventArgs $event
     */
    public function preRemove(User $user, LifecycleEventArgs $event)
    {
        $oldAvatarImage = $user->getAvatarImage();
        if (!empty($oldAvatarImage)) {
            $this->userService->deleteAvatar($oldAvatarImage);
        }
    }

    public function onUpload(PostPersistEvent $event)
    {
        $response = $event->getResponse();
        /** @var GaufretteFile $file */
        $file = $event->getFile();
        $avatarPath = $file->getPathname();
        $mimeType = $file->getMimeType();
        $rename = $file->getFilesystem()->rename($file->getPathname(), $event->getType().'/'.$avatarPath);
        if ($rename) {
            $avatarPath = $event->getType().'/'.$avatarPath;
        }

        $imageSize = getimagesize('gaufrette://images/'.$avatarPath);
        $response['path'] = $avatarPath;
        $response['url'] = $this->fileUploadService->getPublicUrl($avatarPath);
        $response['type'] = $mimeType;
        list($response['width'], $response['height']) = $imageSize;

        if ($event->getType() == 'avatar') {
            $event->getRequest()->getSession()->set('campaignchain_last_uploaded_avatar', $avatarPath);
        }
    }
}
