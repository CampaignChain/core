<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="campaignchain_bundle")
 */
class Bundle extends Meta
{
    const TYPE_CORE = 'campaignchain-core';
    const TYPE_DISTRIBUTION = 'campaignchain-distribution';
    const TYPE_CAMPAIGN = 'campaignchain-campaign';
    const TYPE_MILESTONE = 'campaignchain-milestone';
    const TYPE_ACTIVITY = 'campaignchain-activity';
    const TYPE_OPERATION = 'campaignchain-operation';
    const TYPE_CHANNEL = 'campaignchain-channel';
    const TYPE_LOCATION = 'campaignchain-location';
    const TYPE_SECURITY = 'campaignchain-security';
    const TYPE_REPORT = 'campaignchain-report';
    const TYPE_REPORT_ANALYTICS = 'campaignchain-report-analytics';
    const TYPE_REPORT_BUDGET = 'campaignchain-report/budget';
    const TYPE_REPORT_SALES = 'campaignchain-report/sales';
    const TYPE_HOOK = 'campaignchain-hook';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="CampaignModule", mappedBy="bundle", cascade={"persist"})
     */
    protected $campaignModules;

    /**
     * @ORM\OneToMany(targetEntity="MilestoneModule", mappedBy="bundle", cascade={"persist"})
     */
    protected $milestoneModules;

    /**
     * @ORM\OneToMany(targetEntity="ActivityModule", mappedBy="bundle", cascade={"persist"})
     */
    protected $activityModules;

    /**
     * @ORM\OneToMany(targetEntity="OperationModule", mappedBy="bundle", cascade={"persist"})
     */
    protected $operationModules;

    /**
     * @ORM\OneToMany(targetEntity="Hook", mappedBy="bundle", cascade={"persist"})
     */
    protected $hooks;

    /**
     * @ORM\OneToMany(targetEntity="ReportModule", mappedBy="bundle", cascade={"persist"})
     */
    protected $reportModules;

    /**
     * @ORM\OneToMany(targetEntity="SecurityModule", mappedBy="bundle", cascade={"persist"})
     */
    protected $securityModules;

    /**
     * @ORM\OneToMany(targetEntity="ChannelModule", mappedBy="bundle", cascade={"persist"})
     */
    protected $channelModules;

    /**
     * @ORM\OneToMany(targetEntity="LocationModule", mappedBy="bundle", cascade={"persist"})
     */
    protected $locationModules;

    /**
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $description;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $license;

    /**
     * @ORM\Column(type="array")
     */
    protected $authors;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $homepage;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $path;

    /**
     * The bundle class.
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $class;

    /**
     * @ORM\Column(type="string", length=20)
     */
    protected $version = 'dev-master';

    protected $extra;

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
     * Set name
     *
     * @param string $name
     * @return Bundle
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
     * Set description
     *
     * @param string $description
     * @return Bundle
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set license
     *
     * @param string $license
     * @return Bundle
     */
    public function setLicense($license)
    {
        $this->license = $license;

        return $this;
    }

    /**
     * Get license
     *
     * @return string
     */
    public function getLicense()
    {
        return $this->license;
    }

    /**
     * Set authors
     *
     * @param array $authors
     * @return Bundle
     */
    public function setAuthors($authors)
    {
        $this->authors = $authors;

        return $this;
    }

    /**
     * Get authors
     *
     * @return array
     */
    public function getAuthors()
    {
        return $this->authors;
    }

    /**
     * Set homepage
     *
     * @param string $homepage
     * @return Bundle
     */
    public function setHomepage($homepage)
    {
        $this->homepage = $homepage;

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
     * Set type
     *
     * @param string $type
     * @return Bundle
     */
    public function setType($type)
    {
        if (!in_array($type, array(
            self::TYPE_CORE,
            self::TYPE_DISTRIBUTION,
            self::TYPE_CAMPAIGN,
            self::TYPE_MILESTONE,
            self::TYPE_ACTIVITY,
            self::TYPE_OPERATION,
            self::TYPE_CHANNEL,
            self::TYPE_LOCATION,
            self::TYPE_SECURITY,
            self::TYPE_REPORT,
            self::TYPE_REPORT_ANALYTICS,
            self::TYPE_REPORT_BUDGET,
            self::TYPE_REPORT_SALES,
            self::TYPE_HOOK,
        ))) {
            throw new \InvalidArgumentException("Invalid bundle type.");
        }
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return Bundle
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set class
     *
     * @param string $class
     * @return Bundle
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getWebAssetsPath()
    {
        $class = $this->getClass();
        $classParts = explode("\\", $class);
        $class = end($classParts);

        $path = 'bundles/'.strtolower(
                str_replace(
                    'Bundle', '',
                    $class
                )
            );
        return $path;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getParameterIdentifier(){
        return str_replace('-', '_', str_replace('/', '_', $this->getName()));
    }

    /**
     * Get service identifier
     *
     * @return string
     */
    public function getServiceIdentifier(){
        return str_replace('-', '.', str_replace('/', '.', $this->getName()));
    }

    /**
     * Add channels
     *
     * @param \CampaignChain\CoreBundle\Entity\Channel $channels
     * @return Bundle
     */
    public function addChannel(\CampaignChain\CoreBundle\Entity\Channel $channels)
    {
        $this->channels[] = $channels;

        return $this;
    }

    /**
     * Remove channels
     *
     * @param \CampaignChain\CoreBundle\Entity\Channel $channels
     */
    public function removeChannel(\CampaignChain\CoreBundle\Entity\Channel $channels)
    {
        $this->channels->removeElement($channels);
    }

    /**
     * Get channels
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChannels()
    {
        return $this->channels;
    }

    /**
     * Add activityModules
     *
     * @param \CampaignChain\CoreBundle\Entity\ActivityModule $activityModules
     * @return Bundle
     */
    public function addActivityModule(\CampaignChain\CoreBundle\Entity\ActivityModule $activityModules)
    {
        $this->activityModules[] = $activityModules;

        return $this;
    }

    /**
     * Remove activityModules
     *
     * @param \CampaignChain\CoreBundle\Entity\ActivityModule $activityModules
     */
    public function removeActivityModule(\CampaignChain\CoreBundle\Entity\ActivityModule $activityModules)
    {
        $this->activityModules->removeElement($activityModules);
    }

    /**
     * Get activityModules
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getActivityModules()
    {
        return $this->activityModules;
    }

    /**
     * Add operationModules
     *
     * @param \CampaignChain\CoreBundle\Entity\ActivityModule $operationModules
     * @return Bundle
     */
    public function addOperationModule(\CampaignChain\CoreBundle\Entity\OperationModule $operationModules)
    {
        $this->operationModules[] = $operationModules;

        return $this;
    }

    /**
     * Remove operationModules
     *
     * @param \CampaignChain\CoreBundle\Entity\ActivityModule $operationModules
     */
    public function removeOperationModule(\CampaignChain\CoreBundle\Entity\OperationModule $operationModules)
    {
        $this->operationModules->removeElement($operationModules);
    }

    /**
     * Get operationModules
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOperationModules()
    {
        return $this->operationModules;
    }

    /**
     * Add hookModules
     *
     * @param \CampaignChain\CoreBundle\Entity\Hook $hooks
     * @return Bundle
     */
    public function addHook(\CampaignChain\CoreBundle\Entity\Hook $hooks)
    {
        $this->hooks[] = $hooks;

        return $this;
    }

    /**
     * Remove hookModules
     *
     * @param \CampaignChain\CoreBundle\Entity\Hook $hooks
     */
    public function removeHook(\CampaignChain\CoreBundle\Entity\Hook $hooks)
    {
        $this->hooks->removeElement($hooks);
    }

    /**
     * Get hookModules
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHooks()
    {
        return $this->hooks;
    }

    /**
     * Add campaignModules
     *
     * @param \CampaignChain\CoreBundle\Entity\CampaignModule $campaignModules
     * @return Bundle
     */
    public function addCampaignModule(\CampaignChain\CoreBundle\Entity\CampaignModule $campaignModules)
    {
        $this->campaignModules[] = $campaignModules;

        return $this;
    }

    /**
     * Remove campaignModules
     *
     * @param \CampaignChain\CoreBundle\Entity\CampaignModule $campaignModules
     */
    public function removeCampaignModule(\CampaignChain\CoreBundle\Entity\CampaignModule $campaignModules)
    {
        $this->campaignModules->removeElement($campaignModules);
    }

    /**
     * Get campaignModules
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCampaignModules()
    {
        return $this->campaignModules;
    }

    /**
     * Add milestoneModules
     *
     * @param \CampaignChain\CoreBundle\Entity\MilestoneModule $milestoneModules
     * @return Bundle
     */
    public function addMilestoneModule(\CampaignChain\CoreBundle\Entity\MilestoneModule $milestoneModules)
    {
        $this->milestoneModules[] = $milestoneModules;

        return $this;
    }

    /**
     * Remove milestoneModules
     *
     * @param \CampaignChain\CoreBundle\Entity\MilestoneModule $milestoneModules
     */
    public function removeMilestoneModule(\CampaignChain\CoreBundle\Entity\MilestoneModule $milestoneModules)
    {
        $this->milestoneModules->removeElement($milestoneModules);
    }

    /**
     * Get milestoneModules
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMilestoneModules()
    {
        return $this->milestoneModules;
    }

    /**
     * Add locations
     *
     * @param \CampaignChain\CoreBundle\Entity\Location $locations
     * @return Bundle
     */
    public function addLocation(\CampaignChain\CoreBundle\Entity\Location $locations)
    {
        $this->locations[] = $locations;

        return $this;
    }

    /**
     * Remove locations
     *
     * @param \CampaignChain\CoreBundle\Entity\Location $locations
     */
    public function removeLocation(\CampaignChain\CoreBundle\Entity\Location $locations)
    {
        $this->locations->removeElement($locations);
    }

    /**
     * Get locations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * Add channelModules
     *
     * @param \CampaignChain\CoreBundle\Entity\ChannelModule $channelModules
     * @return Bundle
     */
    public function addChannelModule(\CampaignChain\CoreBundle\Entity\ChannelModule $channelModules)
    {
        $this->channelModules[] = $channelModules;

        return $this;
    }

    /**
     * Remove channelModules
     *
     * @param \CampaignChain\CoreBundle\Entity\ChannelModule $channelModules
     */
    public function removeChannelModule(\CampaignChain\CoreBundle\Entity\ChannelModule $channelModules)
    {
        $this->channelModules->removeElement($channelModules);
    }

    /**
     * Get channelModules
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChannelModules()
    {
        return $this->channelModules;
    }

    /**
     * Add locationModules
     *
     * @param \CampaignChain\CoreBundle\Entity\LocationModule $locationModules
     * @return Bundle
     */
    public function addLocationModule(\CampaignChain\CoreBundle\Entity\LocationModule $locationModules)
    {
        $this->locationModules[] = $locationModules;

        return $this;
    }

    /**
     * Remove locationModules
     *
     * @param \CampaignChain\CoreBundle\Entity\LocationModule $locationModules
     */
    public function removeLocationModule(\CampaignChain\CoreBundle\Entity\LocationModule $locationModules)
    {
        $this->locationModules->removeElement($locationModules);
    }

    /**
     * Get locationModules
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLocationModules()
    {
        return $this->locationModules;
    }

    /**
     * Add reportModules
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportModule $reportModules
     * @return Bundle
     */
    public function addReportModule(\CampaignChain\CoreBundle\Entity\ReportModule $reportModules)
    {
        $this->reportModules[] = $reportModules;

        return $this;
    }

    /**
     * Remove activityModules
     *
     * @param \CampaignChain\CoreBundle\Entity\ReportModule $reportModules
     */
    public function removeReportModule(\CampaignChain\CoreBundle\Entity\ReportModule $reportModules)
    {
        $this->reportModules->removeElement($reportModules);
    }

    /**
     * Get activityModules
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReportModules()
    {
        return $this->reportModules;
    }

    /**
     * Add securityModules
     *
     * @param \CampaignChain\CoreBundle\Entity\SecurityModule $securityModules
     * @return Bundle
     */
    public function addSecurityModule(\CampaignChain\CoreBundle\Entity\SecurityModule $securityModules)
    {
        $this->securityModules[] = $securityModules;

        return $this;
    }

    /**
     * Remove activityModules
     *
     * @param \CampaignChain\CoreBundle\Entity\SecurityModule $securityModules
     */
    public function removeSecurityModule(\CampaignChain\CoreBundle\Entity\SecurityModule $securityModules)
    {
        $this->securityModules->removeElement($securityModules);
    }

    /**
     * Get activityModules
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSecurityModules()
    {
        return $this->securityModules;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->campaignModules = new \Doctrine\Common\Collections\ArrayCollection();
        $this->milestoneModules = new \Doctrine\Common\Collections\ArrayCollection();
        $this->activityModules = new \Doctrine\Common\Collections\ArrayCollection();
        $this->operationModules = new \Doctrine\Common\Collections\ArrayCollection();
        $this->hooks = new \Doctrine\Common\Collections\ArrayCollection();
        $this->reports = new \Doctrine\Common\Collections\ArrayCollection();
        $this->channelModules = new \Doctrine\Common\Collections\ArrayCollection();
        $this->locationModules = new \Doctrine\Common\Collections\ArrayCollection();
        $this->reportModules = new \Doctrine\Common\Collections\ArrayCollection();
        $this->securityModules = new \Doctrine\Common\Collections\ArrayCollection();
        $this->activities = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add activities
     *
     * @param \CampaignChain\CoreBundle\Entity\ActivityModule $activities
     * @return Bundle
     */
    public function addActivity(\CampaignChain\CoreBundle\Entity\ActivityModule $activities)
    {
        $this->activities[] = $activities;

        return $this;
    }

    /**
     * Remove activities
     *
     * @param \CampaignChain\CoreBundle\Entity\ActivityModule $activities
     */
    public function removeActivity(\CampaignChain\CoreBundle\Entity\ActivityModule $activities)
    {
        $this->activities->removeElement($activities);
    }

    /**
     * Get activities
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getActivities()
    {
        return $this->activities;
    }

    /**
     * Set version
     *
     * @param string $version
     * @return Bundle
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

    public function setExtra($extra)
    {
        $this->extra = $extra;

        return $this;
    }

    public function getExtra()
    {
        return $this->extra;
    }
}
