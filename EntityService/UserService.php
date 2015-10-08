<?php


namespace CampaignChain\CoreBundle\EntityService;


use CampaignChain\CoreBundle\Entity\User;
use GuzzleHttp\ClientInterface as HttpClient;
use GuzzleHttp\Stream\GuzzleStreamWrapper;

class UserService
{
    /** @var string */
    private $uploadDirectory;

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

    public function __construct($uploadDirectory, HttpClient $httpClient)
    {
        $this->uploadDirectory = $uploadDirectory;
        $this->httpClient = $httpClient;
    }

    public function generateAvatarBasename(User $user)
    {
        return "{$user->getId()}_".bin2hex(openssl_random_pseudo_bytes(8));
    }

    public function downloadGravatar(User $user)
    {
        $gravatarUrl = $user->getGravatarUrl();
        
        $response = $this->httpClient->get($gravatarUrl);

        // TODO: Do something sensible on unknown content type
        $extension = self::getExtensionForContentType($response->getHeader('Content-Type')) ?: "jpg";
        $avatarPath = self::AVATAR_DIR . "/{$this->generateAvatarBasename($user)}.{$extension}";

        // Make sure the upload directory exists
        $avatarStoragePath = "{$this->uploadDirectory}/" . self::AVATAR_DIR;
        if (!file_exists($avatarStoragePath)) {
            mkdir($avatarStoragePath, 0755, true);
        }

        // Copy response body to file
        $f = fopen("{$this->uploadDirectory}/{$avatarPath}", "w");
        stream_copy_to_stream(GuzzleStreamWrapper::getResource($response->getBody()), $f);
        fclose($f);

        $user->setAvatarImage($avatarPath);
    }

    public function getAvatarImageFilePath($avatarPath)
    {
        return "{$this->uploadDirectory}/{$avatarPath}";
    }
}