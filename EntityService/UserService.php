<?php


namespace CampaignChain\CoreBundle\EntityService;


use CampaignChain\CoreBundle\Entity\User;
use CampaignChain\CoreBundle\Service\FileUploadService;
use GuzzleHttp\ClientInterface as HttpClient;
use GuzzleHttp\Stream\GuzzleStreamWrapper;
use Liip\ImagineBundle\Binary\BinaryInterface;

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
        'image/gif' => 'gif'
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


    /**
     * Generate Gravatar URL for the given email address
     *
     * @param $email
     * @return string Gravatar URL
     */
    public function generateGravatarUrl($email)
    {
        // If email is missing, just use a random string that looks like an MD5 hash
        // to generate a random identicon
        $emailHash = empty($email) ? bin2hex(openssl_random_pseudo_bytes(16)) : md5($email);

        return "https://secure.gravatar.com/avatar/{$emailHash}?s=250&d=identicon";
    }

    /**
     * Download Gravatar image and store it to the user upload directory.
     *
     * @param $email
     * @return string path to downloaded image, relative to the storage directory
     */
    public function downloadGravatarImage($email)
    {
        $gravatarUrl = $this->generateGravatarUrl($email);

        $response = $this->httpClient->get($gravatarUrl);
        $avatarPath = $this->generateAvatarPath($response->getHeader('Content-Type'));

        // Copy response body to file
        $f = $this->fileUploadService->openFile($avatarPath, "w");
        stream_copy_to_stream(GuzzleStreamWrapper::getResource($response->getBody()), $f);
        fclose($f);

        return $avatarPath;
    }

    public function downloadAndSetGravatarImage(User $user)
    {
        $avatarPath = $this->downloadGravatarImage($user->getEmail());
        $user->setAvatarImage($avatarPath);
    }

    public function deleteAvatar($avatarPath)
    {
        $this->fileUploadService->deleteFile($avatarPath);
    }
    public function storeImageAsAvatar(BinaryInterface $image)
    {
        $path = $this->generateAvatarPath($image->getMimeType());
        $f = $this->fileUploadService->openFile($path, "w");
        fwrite($f, $image->getContent());
        fclose($f);

        return $path;
    }
}