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
    public function storeImage($path, $content, $overwrite = true)
    {
        $this->filesystem->write($path, $content, $overwrite);
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
