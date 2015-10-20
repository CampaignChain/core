<?php


namespace CampaignChain\CoreBundle\EntityService;


use CampaignChain\CoreBundle\Entity\User;
use CampaignChain\CoreBundle\Service\FileUploadService;
use GuzzleHttp\ClientInterface as HttpClient;
use GuzzleHttp\Stream\GuzzleStreamWrapper;

class UserService
{
    /** @var FileUploadService */
    private $fileUploadService;

    /** @var HttpClient */
    private $httpClient;

    const AVATAR_DIR = "avatar";

    private static $IMAGE_EXTENSIONS = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
    ];

    private static function getExtensionForContentType($mimeType)
    {
        // Strip out encoding information
        $matches = null;
        if (preg_match('/^[^;]+/', $mimeType, $matches)) {
            $mimeType = $matches[0];
        }

        if (array_key_exists($mimeType, self::$IMAGE_EXTENSIONS)) {
            return self::$IMAGE_EXTENSIONS[$mimeType];
        }

        return null;
    }

    public function __construct(FileUploadService $fileUploadService, HttpClient $httpClient)
    {
        $this->fileUploadService = $fileUploadService;
        $this->httpClient = $httpClient;
    }

    public function generateAvatarPath($mimeType)
    {
        // TODO: Do something sensible on unknown content type
        $extension = self::getExtensionForContentType($mimeType) ?: "jpg";
        return self::AVATAR_DIR . "/" . $this->fileUploadService->generateFileName(".{$extension}");
    }

    public function downloadGravatar(User $user)
    {
        $gravatarUrl = $user->getGravatarUrl();

        $response = $this->httpClient->get($gravatarUrl);
        $avatarPath = $this->generateAvatarPath($response->getHeader('Content-Type'));

        // Copy response body to file
        $f = $this->fileUploadService->openFile($avatarPath, "w");
        stream_copy_to_stream(GuzzleStreamWrapper::getResource($response->getBody()), $f);
        fclose($f);

        $user->setAvatarImage($avatarPath);
    }

    public function deleteAvatar($avatarPath)
    {
        $this->fileUploadService->deleteFile($avatarPath);
    }
}