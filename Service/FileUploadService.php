<?php

namespace CampaignChain\CoreBundle\Service;

class FileUploadService
{
    /** @var string */
    private $uploadDirectory;

    /** @var string */
    private $uploadDirectoryUrlPrefix;

    public function __construct($uploadDirectory, $uploadDirectoryUrlPrefix)
    {
        $this->uploadDirectory = $uploadDirectory;
        $this->uploadDirectoryUrlPrefix = $uploadDirectoryUrlPrefix;
    }

    /**
     * Get the physical path to the given file
     *
     * @param string $file
     * @return string
     */
    public function getFilesystemPath($file)
    {
        return $this->uploadDirectory . DIRECTORY_SEPARATOR . $file;
    }

    /**
     * Get the URL where the given file can be reached
     *
     * @param string $file
     * @return string
     */
    public function getPublicUrl($file)
    {
        return $this->uploadDirectoryUrlPrefix . "/" . $file;
    }

    /**
     * Open a file handle to the given file. Don't forget to close it
     * when you're done
     *
     * @param string $file
     * @param string $mode
     * @return resource
     */
    public function openFile($file, $mode)
    {
        $filesystemPath = $this->getFilesystemPath($file);
        $directory = dirname($filesystemPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        return fopen($filesystemPath, $mode);
    }

    /**
     * delete the given file
     *
     * @param string $file
     */
    public function deleteFile($file)
    {
        $filesystemPath = $this->getFilesystemPath($file);
        if (file_exists($filesystemPath)) {
            unlink($filesystemPath);
        }
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

    /**
     * Get the relative path to the upload directory of the given absolute path
     *
     * @param $path
     *
     * @return string
     */
    public function getRelativePath($path)
    {
        $from = realpath($this->uploadDirectory);
        $to = realpath($path);

        // some compatibility fixes for Windows paths
        $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
        $to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
        $from = str_replace('\\', '/', $from);
        $to   = str_replace('\\', '/', $to);

        $from     = explode('/', $from);
        $to       = explode('/', $to);
        $relPath  = $to;

        foreach($from as $depth => $dir) {
            // find first non-matching dir
            if($dir === $to[$depth]) {
                // ignore this directory
                array_shift($relPath);
            } else {
                // get number of remaining dirs to $from
                $remaining = count($from) - $depth;
                if($remaining > 1) {
                    // add traversals up to first matching dir
                    $padLength = (count($relPath) + $remaining - 1) * -1;
                    $relPath = array_pad($relPath, $padLength, '..');
                    break;
                }
            }
        }
        return implode('/', $relPath);
    }

}