<?php

namespace CampaignChain\CoreBundle\Service;

use Gaufrette\Filesystem;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

class FileUploadService
{
    /** @var string */
    private $uploadDirectory;

    /** @var string */
    private $uploadDirectoryUrlPrefix;

    /** @var Filesystem  */
    private $filesystem;

    /** @var CacheManager  */
    private $cacheManager;

    public function __construct($uploadDirectory, $uploadDirectoryUrlPrefix, Filesystem $filesystem, CacheManager $cacheManager)
    {
        $this->uploadDirectory = $uploadDirectory;
        $this->uploadDirectoryUrlPrefix = $uploadDirectoryUrlPrefix;
        $this->filesystem = $filesystem;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Get the URL where the given file can be reached
     *
     * We are using the imagine bundle to create a public
     * url for the images, independent where are they stored
     *
     * @param string $file
     * @param string $filter
     * @return string
     */
    public function getPublicUrl($file, $filter = "auto_rotate")
    {
        return $this->cacheManager->getBrowserPath($file, $filter);
    }

    /**
     * @param $path
     * @param $content
     */
    public function storeImage($path, $content)
    {
        $this->filesystem->write($path, $content);
    }

    /**
     * delete the given file
     *
     * @param string $file
     */
    public function deleteFile($file)
    {
        if (!$this->filesystem->has($file)) {
            return;
        }

        $this->filesystem->delete($file);
        $this->cacheManager->remove($file);
    }

    /**
     * Generate a file name. $prefix and $suffix will be prepended and appended to the resulting file name respectively.
     * E.g. if a ".jpg" file extension is required, pass ".jpg" (with the dot) as $suffix.
     *
     * @param string $suffix
     * @param string $prefix
     * @return string
     */
    public function generateFileName($suffix = "", $prefix = "")
    {
        return $prefix . bin2hex(openssl_random_pseudo_bytes(8)) . $suffix;
    }
}
