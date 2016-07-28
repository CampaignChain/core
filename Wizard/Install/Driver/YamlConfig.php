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
    public function write($parameters, $expanded = 2)
    {
        $this->parameters = $this->read();
        $this->mergeParameters($parameters);

        $filename = $this->isFileWritable() ? $this->filename : $this->getCacheFilename();

        return file_put_contents($filename, $this->render($expanded));
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