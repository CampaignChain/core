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

namespace CampaignChain\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use CampaignChain\CoreBundle\Util\ParserUtil;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="campaignchain_system")
 */
class System extends Meta
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $currency = 'USD';

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $package;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    protected $version;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $homepage;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $navigation;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $modules;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Url(
     *    checkDNS = true
     * )
     */
    protected $termsUrl;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $bitlyAccessToken;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $includeInHead;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set currency
     *
     * @param string $currency
     * @return Setting
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set package
     *
     * @param string $package
     * @return System
     */
    public function setPackage($package)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get package
     *
     * @return string
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return System
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set version
     *
     * @param string $version
     * @return System
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set homepage
     *
     * @param string $homepage
     * @return System
     */
    public function setHomepage($homepage)
    {
        $this->homepage = ParserUtil::sanitizeUrl($homepage);

        return $this;
    }

    /**
     * Get homepage
     *
     * @return string
     */
    public function getHomepage()
    {
        return $this->homepage;
    }

    /**
     * Set navigation
     *
     * @param array $navigation
     * @return System
     */
    public function setNavigation($navigation)
    {
        $this->navigation = $navigation;

        return $this;
    }

    /**
     * Get navigation
     *
     * @return array
     */
    public function getNavigation()
    {
        return $this->navigation;
    }

    /**
     * Set modules
     *
     * @param array $modules
     * @return System
     */
    public function setModules($modules)
    {
        $this->modules = $modules;

        return $this;
    }

    /**
     * Get modules
     *
     * @return array
     */
    public function getModules()
    {
        return $this->modules;
    }

    public function getDocsURL()
    {
        if($this->version == 'dev-master'){
            $docVersion = 'master';
        } else {
            $docVersion = $this->version;
        }
        return 'http://docs.campaignchain.com/en/'.$docVersion;
    }

    /**
     * Set URL of legal terms.
     *
     * @param string $termsUrl
     * @return System
     */
    public function setTermsUrl($termsUrl)
    {
        $this->termsUrl = $termsUrl;

        return $this;
    }

    /**
     * Get termsUrl
     *
     * @return string
     */
    public function getTermsUrl()
    {
        return $this->termsUrl;
    }

    /**
     * Set bitly access token
     *
     * @param string $bitlyAccessToken
     * @return System
     */
    public function setBitlyAccessToken($bitlyAccessToken)
    {
        $this->bitlyAccessToken = $bitlyAccessToken;

        return $this;
    }

    /**
     * Get bitly access token
     *
     * @return string
     */
    public function getBitlyAccessToken()
    {
        return $this->bitlyAccessToken;
    }

    /**
     * Set text to include after <body> tag.
     *
     * @param string $includeInHead
     * @return System
     */
    public function setIncludeInHead($includeInHead)
    {
        $this->includeInHead = $includeInHead;

        return $this;
    }

    /**
     * Get bitly access token
     *
     * @return string
     */
    public function getIncludeInHead()
    {
        return $this->includeInHead;
    }
}