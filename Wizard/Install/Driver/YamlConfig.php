<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Wizard\Install\Driver;

use Symfony\Component\Yaml\Yaml;

class YamlConfig
{
    protected $filename;
    protected $parameters;
    public $kernelDir;

    public function __construct($kernelDir, $filename)
    {
        $this->kernelDir = $kernelDir;
        $this->filename = $this->kernelDir.DIRECTORY_SEPARATOR.$filename;
    }

    public function isFileWritable()
    {
        return is_writable($this->filename);
    }

    public function clean()
    {
        if (file_exists($this->getCacheFilename())) {
            @unlink($this->getCacheFilename());
        }
    }

    /**
     * @param array $parameters
     */
    public function mergeParameters($parameters)
    {
        $this->parameters = array_replace_recursive($this->parameters, $parameters);
    }

    /**
     * Renders parameters as a string.
     *
     * @param int $expanded
     *
     * @return string
     */
    public function render($expanded = 2)
    {
        return Yaml::dump($this->parameters, $expanded);
    }

    /**
     * Writes parameters to parameters.yml or temporary in the cache directory.
     *
     * @param int $expanded
     *
     * @return int
     */
    public function write($parameters)
    {
        $this->parameters = $this->read();
        $this->mergeParameters($parameters);

        $filename = $this->isFileWritable() ? $this->filename : $this->getCacheFilename();

        return file_put_contents($filename, $this->render());
    }

    /**
     * Reads parameters from file.
     *
     * @return array
     */
    public function read()
    {
        $filename = $this->filename;
//        if (!$this->isFileWritable() && file_exists($this->getCacheFilename())) {
//            $filename = $this->getCacheFilename();
//        }

        $ret = Yaml::parse($filename);
        if (false === $ret || array() === $ret) {
            throw new \InvalidArgumentException(sprintf('The %s file is not valid.', $filename));
        }

        if (count($ret)) {
            return $ret;
        } else {
            return array();
        }
    }

    /**
     * getCacheFilename
     *
     * @return string
     */
    protected function getCacheFilename()
    {
        return $this->kernelDir.'/cache/parameters.yml';
    }
}