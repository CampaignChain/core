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

namespace CampaignChain\CoreBundle\Twig;

use CampaignChain\CoreBundle\Entity\Campaign;
use CampaignChain\CoreBundle\Entity\User;
use CampaignChain\CoreBundle\EntityService\ModuleService;
use CampaignChain\CoreBundle\Util\ParserUtil;
use CampaignChain\CoreBundle\Util\SystemUtil;
use Proxies\__CG__\CampaignChain\CoreBundle\Entity\Milestone;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

class CampaignChainCoreExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    const DEFAULT_LAYOUT_NAME = 'default';

    protected $em;
    protected $container;
    protected $datetime;

    public function __construct(ManagerRegistry $managerRegistry, ContainerInterface $container)
    {
        $this->em = $managerRegistry->getManager();
        $this->container = $container;
        $this->datetime = $this->container->get('campaignchain.core.util.datetime');
    }

    private $environment = null;

    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    private function loggedIn()
    {
        return $this->container->get('security.token_storage')->getToken()
            && $this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED');
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('campaignchain_medium_icon', array($this, 'mediumIcon')),
            new \Twig_SimpleFilter('campaignchain_medium_context', array($this, 'mediumContext')),
            new \Twig_SimpleFilter('campaignchain_channel_asset_path', array($this, 'channelAssetPath')),
            new \Twig_SimpleFilter('campaignchain_channel_icon_name', array($this, 'channelIconName')),
            new \Twig_SimpleFilter('campaignchain_datetime', array($this, 'datetime')),
            new \Twig_SimpleFilter('campaignchain_timestamp_to_datetime', array($this, 'timestampToDatetime')),
            new \Twig_SimpleFilter('campaignchain_timezone', array($this, 'timezone')),
            new \Twig_SimpleFilter('campaignchain_data_trigger_hook', array($this, 'dataTriggerHook')),
            new \Twig_SimpleFilter('campaignchain_tpl_teaser', array($this, 'tplTeaser'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('campaignchain_tpl_trigger_hook', array($this, 'tplTriggerHook'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('campaignchain_tpl_delete_activity', array($this, 'tplDeleteActivity'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('campaignchain_channel_root_locations', array($this, 'channelRootLocations')),
            new \Twig_SimpleFilter('campaignchain_remaining_time', array($this, 'remainingTime')),
            new \Twig_SimpleFilter('campaignchain_remaining_time_badge', array($this, 'remainingTimeBadge')),
            new \Twig_SimpleFilter('campaignchain_parse_url', array($this, 'parseUrl')),
            new \Twig_SimpleFilter('campaignchain_ltrim', array($this, 'ltrim')),
            new \Twig_SimpleFilter('campaignchain_make_links', array($this, 'makeLinks')),
            new \Twig_SimpleFilter('campaignchain_btn_copy_campaign', array($this, 'btnCopyCampaign'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('campaignchain_is_removable', array($this, 'isRemovable')),
        );
    }

    public function system(){
        return $this->container->get('campaignchain.core.system')->getActiveSystem();
    }

    public function theme(){
        return $this->em
            ->getRepository('CampaignChainCoreBundle:Theme')
            ->find(1);
    }

    public function mediumIcon($object)
    {
        $class = get_class($object);

        if(strpos($class, 'CoreBundle\Entity\Location') !== false) {
            return $object->getImage();
        } elseif(strpos($class, 'CoreBundle\Entity\User') !== false){
            return $this->container->get('liip_imagine.cache.manager')->getBrowserPath($object->getAvatarImage(), 'navbar_avatar');
        } else {
            return $this->channelAssetPath($object).'/images/icons/32x32/'.$this->channelIconName($object);
        }
    }

    public function mediumContext($object, $size = '16')
    {
        $class = get_class($object);

        if(strpos($class, 'CoreBundle\Entity\Location') !== false){
            return $this->channelAssetPath($object).'/images/icons/'.$size.'x'.$size.'/'.$this->channelIconName($object);
        } else {
            return false;
        }
    }

    public function channelAssetPath($object)
    {
        $class = get_class($object);

        if(strpos($class, 'CoreBundle\Entity\Bundle') !== false){
            $bundlePath = $object->getWebAssetsPath();
        } elseif(strpos($class, 'CoreBundle\Entity\ChannelModule') !== false){
            $bundlePath = $object->getBundle()->getWebAssetsPath();
        } elseif(strpos($class, 'CoreBundle\Entity\Location') !== false){
            $bundlePath = $object->getChannel()->getChannelModule()->getBundle()->getWebAssetsPath();
        } elseif(strpos($class, 'CoreBundle\Entity\Channel') !== false){
            $bundlePath = $object->getChannelModule()->getBundle()->getWebAssetsPath();
        } elseif(strpos($class, 'CoreBundle\Entity\Activity') !== false){
            if(!$object->getChannel()){
                // Activity is not related to a Channel.
                $bundlePath = $object->getActivityModule()->getBundle()->getWebAssetsPath();
            } else {
                $bundlePath = $object->getChannel()->getChannelModule()->getBundle()->getWebAssetsPath();
            }
        } else {
            return false;
        }

        return $bundlePath;
    }

    public function channelIconName($object)
    {
        $class = get_class($object);

        if(strpos($class, 'CoreBundle\Entity\Bundle') !== false){
            // $channelIdentifier = $object->getName();
            throw new \Exception('Cannot derive icon name for Bundle object');
        } elseif(strpos($class, 'CoreBundle\Entity\ChannelModule') !== false){
            $channelModule = $object;
        } elseif(strpos($class, 'CoreBundle\Entity\Location') !== false){
            $channelModule = $object->getChannel()->getChannelModule();
        } elseif(strpos($class, 'CoreBundle\Entity\Channel') !== false){
            $channelModule = $object->getChannelModule();
        } elseif(strpos($class, 'CoreBundle\Entity\Activity') !== false){
            if(!$object->getChannel()){
                $channelModule = $object->getActivityModule();
            } else {
                $channelModule = $object->getChannel()->getChannelModule();
            }
        } else {
            return false;
        }

        return $this->bundleName2IconFileName(
            $channelModule->getBundle()->getName(),
            $channelModule->getIdentifier()
        );
    }

    public function bundleName2IconFileName($bundleName, $channelIdentifier)
    {
        $bundleNameParts    = explode('/', $bundleName);
        $bundleVendor       = $bundleNameParts[0];

        return str_replace($bundleVendor.'-', '', $channelIdentifier).'.png';
    }

    public function tplTeaser($object, $options = array())
    {
        $teaserOptions = array(
            'only_icon' => false,
            'only_context_icon' => false,
            'activity_name' => 'activity',
            'show_trigger' => false,
            'truncate_middle' => 0,
        );

        if(is_array($options) && count($options)){
            $teaserOptions = array_merge($teaserOptions, $options);
        }

        $class = get_class($object);

        if(strpos($class, 'CoreBundle\Entity\Location') !== false){
            $tplVars['url'] = $object->getUrl();
            $tplVars['icon_path'] = $this->mediumIcon($object);
            $tplVars['context_icon_path'] = $this->mediumContext($object);
            if(!$tplVars['icon_path']){
                $tplVars['icon_size'] = 32;
                $tplVars['icon_path'] = $this->mediumContext($object, $tplVars['icon_size']);
                $tplVars['context_icon_path'] = null;
            }
            $tplVars['name'] = $object->getName();
        } elseif(strpos($class, 'CoreBundle\Entity\Activity') !== false){
            $tplVars['url'] = $this->container->get('router')->generate(
                'campaignchain_core_activity_edit',
                array('id' => $object->getId()),
                true
            );
            if(!$object->getLocation()){
                $bundleWithImages = $object;
            } else {
                $bundleWithImages = $object->getLocation();
            }
            $tplVars['icon_path'] = $this->mediumIcon($bundleWithImages);
            $tplVars['context_icon_path'] = $this->mediumContext($bundleWithImages);
            if(!$tplVars['icon_path']){
                $tplVars['icon_size'] = 32;
                $tplVars['icon_path'] = $this->mediumContext($bundleWithImages, $tplVars['icon_size']);
                $tplVars['context_icon_path'] = null;
            }
            if($teaserOptions['activity_name'] == 'activity'){
                $tplVars['name'] = $object->getName();
            } elseif($object->getLocation()) {
                $tplVars['name'] = $object->getLocation()->getName();
            }
            if($teaserOptions['show_trigger'] == true){
                $tplVars['trigger'] = $this->tplTriggerHook($object);
            }
        } elseif(strpos($class, 'CoreBundle\Entity\CampaignModule') !== false){
            $tplVars['url'] = $this->container->get('router')->generate(
                $object->getRoutes()['plan'],
                array(),
                true
            );

            if(!isset($options['size'])){
                $tplVars['icon_size'] = 32;
            } else {
                $tplVars['icon_size'] = $options['size'];
            }
            $tplVars['icon_path'] = $object->getBundle()->getWebAssetsPath().
                '/images/icons/'.$tplVars['icon_size'].'x'.$tplVars['icon_size'].'/'.$this->bundleName2IconFileName(
                    $object->getBundle()->getName(),
                    $object->getIdentifier()
                );
            $tplVars['context_icon_path'] = null;
            $tplVars['name'] = $object->getDisplayName();
        } elseif(strpos($class, 'CoreBundle\Entity\Campaign') !== false){
            $tplVars['url'] = $this->container->get('router')->generate(
                'campaignchain_core_campaign_edit',
                array('id' => $object->getId()),
                true
            );
            if(!isset($options['size'])){
                $tplVars['icon_size'] = 32;
            } else {
                $tplVars['icon_size'] = $options['size'];
            }
            $tplVars['icon_path'] = $object->getCampaignModule()->getBundle()->getWebAssetsPath().
                '/images/icons/'.$tplVars['icon_size'].'x'.$tplVars['icon_size'].'/'.$this->bundleName2IconFileName(
                    $object->getCampaignModule()->getBundle()->getName(),
                    $object->getCampaignModule()->getIdentifier()
                );
            $tplVars['context_icon_path'] = null;
            $tplVars['name'] = $object->getName();
        } else {
            throw new \Exception(
                'Value must either be instance of CampaignChain\CoreBundle\Entity\Activity, '.
                'CampaignChain\CoreBundle\Entity\CampaignModule '.
                'or CampaignChain\CoreBundle\Entity\Location.'
            );
        }

        if($teaserOptions['truncate_middle'] > 5){
            $tplVars['name'] = ParserUtil::truncateMiddle(
                $tplVars['name'], $teaserOptions['truncate_middle']
            );
        }

        $tplVars['options'] = $teaserOptions;

        if(!isset($tplVars['icon_size'])){
            $tplVars['icon_size'] = 32;
        }

        if(
            $teaserOptions['only_context_icon'] &&
            isset($tplVars['icon_path']) &&
            isset($tplVars['context_icon_path'])
        ){
            $tplVars['icon_path'] = str_replace('16x16', '32x32', $tplVars['context_icon_path']);
            $tplVars['context_icon_path'] = null;
        }

        return $this->container->get('templating')->render(
            'CampaignChainCoreBundle:Base:teaser_widget.html.twig',
            $tplVars
        );
    }

    public function remainingTime(\DateTime $object)
    {
        $datetimeUtil = $this->container->get('campaignchain.core.util.datetime');

        // If less than 1 hour, then display as countdown.
        $now = $datetimeUtil->getNow($this->container->get('session')->get('campaignchain.timezone'));
        $totalMinutes = abs($object->getTimestamp() - $now->getTimestamp()) / 60;

        if($totalMinutes < 60){
            $id = rand();
            return '<span id="campaignchain-countdown-'.$id.'"></span>
                    <script type="text/javascript">
                    $("#campaignchain-countdown-'.$id.'")
                    .countdown(moment("'.$object->format(\DateTime::W3C).'").zone(window.campaignchainTimezoneOffset).format("YYYY/MM/DD HH:mm:ss"), function(event) {
                        $(this).text(
                            event.strftime(\'%M minutes, %S seconds\')
                        );
                    });
                    $("#campaignchain-countdown-'.$id.'").on("finish.countdown", function(event) {
                        $(this).parent().parent().fadeOut("slow");
                    });
                    </script>';
        } elseif($totalMinutes < 1440){
                $id = rand();
                return '<span id="campaignchain-countdown-'.$id.'"></span>
                    <script type="text/javascript">
                    $("#campaignchain-countdown-'.$id.'")
                    .countdown(moment("'.$object->format(\DateTime::W3C).'").zone(window.campaignchainTimezoneOffset).format("YYYY/MM/DD HH:mm:ss"), function(event) {
                        $(this).text(
                            event.strftime(\'%H hours, %M minutes\')
                        );
                    });
                    </script>';
        } else {
            return $datetimeUtil->getRemainingTime($object);
        }
    }

    public function remainingTimeBadge(\DateTime $object)
    {
        $datetimeUtil = $this->container->get('campaignchain.core.util.datetime');

        // If less than 1 hour, then display as countdown.
        $now = $datetimeUtil->getNow($this->container->get('session')->get('campaignchain.timezone'));
        $totalMinutes = abs($object->getTimestamp() - $now->getTimestamp()) / 60;

        if($totalMinutes < 60){
            $id = rand();
            return '<span class="badge alert-danger">< 1h</span>';
        } elseif($totalMinutes < 1440){
            $id = rand();
            return '<span class="badge alert-warning">< 24h</span>';
        }
    }

    public function datetime($object, $format = null){
        if($object instanceof \DateTime){
            $datetimeUtil = $this->container->get('campaignchain.core.util.datetime');
            return $datetimeUtil->formatLocale($object, $format);
        } else {
            // TODO: Throw error.
        }
    }

    public function timestampToDatetime($object){
        if(is_int($object)){
            $datetimeUtil = $this->container->get('campaignchain.core.util.datetime');
            return $datetimeUtil->timestampToDatetime($object);
        } else {
            // TODO: Throw error.
        }
    }

    public function timezone($object){
        return $object->setTimezone(new \DateTimeZone($this->container->get('session')->get('campaignchain.timezone')));
    }

    public function dataTriggerHook($object)
    {
        $hookConfig = $this->em->getRepository('CampaignChainCoreBundle:Hook')->find($object->getTriggerHook());
        $hookService = $this->container->get($hookConfig->getServices()['entity']);
        $hookData = $hookService->getHook($object);

        return $hookData;
    }

    public function tplTriggerHook($object)
    {
        // TODO: Store already retrieved service string in a property of this class for performance reasons.
        $hookConfig = $this->em->getRepository('CampaignChainCoreBundle:Hook')->find($object->getTriggerHook());
        $hookService = $this->container->get($hookConfig->getServices()['entity']);
        return $hookService->tplInline($object);
    }

    public function tplDeleteActivity($object)
    {
        return $this->container->get('templating')->render(
            'CampaignChainCoreBundle:Activity:widget_delete.html.twig',
            array('activity' => $object)
        );
    }

    public function channelRootLocations($object)
    {
        $channelService = $this->container->get('campaignchain.core.channel');
        return $channelService->getRootLocations($object);
    }

    public function parseUrl($object)
    {
        return parse_url($object);
    }

    public function ltrim($string, $needle = '')
    {
        return ltrim($string, $needle);
    }

    public function makeLinks($text, $target='_blank', $class=''){
        return ParserUtil::makeLinks($text, $target, $class);
    }

    public function btnCopyCampaign($campaignId)
    {
        // Get the available campaign types for conversion
        /** @var ModuleService $moduleService */
        $moduleService = $this->container->get('campaignchain.core.module');
        $campaignTypes = $moduleService->getCopyAsCampaignModules($campaignId);

        if(count($campaignTypes)){
            return $this->container->get('templating')->render(
                'CampaignChainCoreBundle:Campaign:btn_copy_campaign_widget.html.twig',
                array(
                    'campaign_types' => $campaignTypes,
                    'template_id' => $campaignId,
                )
            );
        } else {
            return false;
        }
    }

    public function getGlobals()
    {
        if(SystemUtil::isInstallMode()){
            return array();
        }

        return array(
            "campaignchain_user_time_format" => array(
                'moment_js' => $this->datetime->getUserTimeFormat(),
            ),
            "campaignchain_user_datetime_format" => array(
                'moment_js' => $this->datetime->getUserDatetimeFormat('moment_js'),
                'iso8601' => $this->container->get('session')->get('campaignchain.dateFormat').' '.$this->container->get('session')->get('campaignchain.timeFormat'),
            ),
            "campaignchain_user_timezone_offset" => $this->getGlobalTimezoneOffset(),
            "campaignchain_user_timezone_abbreviation" => $this->getGlobalTimezoneAbbreviation(),
            'campaignchain_system' => $this->system(),
            'campaignchain_theme' => $this->theme(),
            'campaignchain_env' => $this->container->getParameter('campaignchain.env'),
            'campaignchain_core_scheduler_interval'  => $this->container->getParameter('campaignchain_core.scheduler.interval'),
            'campaignchain_relative_start_date'  => new \DateTime(Campaign::RELATIVE_START_DATE),
            'campaignchain_layout' => $this->getGlobalLayout(),
            'campaignchain_is_first_login' => $this->isFirstLogin(),
        );
    }

    private function getGlobalTimezoneOffset(){
        // Execute only if the user is logged in.
        if( $this->loggedIn() ){
            $timezoneUser = new \DateTimeZone($this->container->get('session')->get('campaignchain.timezone'));
            $timezoneUTC = new \DateTimeZone("UTC");
    //
            $dateUser = new \DateTime("now", $timezoneUser);
            $dateUTC = new \DateTime("now", $timezoneUTC);

            $offset = $timezoneUser->getOffset($dateUTC);

            $offsetHours = round(abs($offset)/3600);
            $offsetMinutes = round((abs($offset) - $offsetHours * 3600) / 60);
            $offsetString = ($offset < 0 ? '-' : '+')
                . ($offsetHours < 10 ? '0' : '') . $offsetHours
                . ':'
                . ($offsetMinutes < 10 ? '0' : '') . $offsetMinutes;

            return $offsetString;
        } else {
            return '+00:00';
        }
    }

    private function getGlobalTimezoneAbbreviation(){
        // Execute only if the user is logged in.
        if( $this->loggedIn() ){
            $timezoneUser = new \DateTimeZone($this->container->get('session')->get('campaignchain.timezone'));
            $dateUser = new \DateTime("now", $timezoneUser);
            return $dateUser->format('T');
        } else {
            return 'UTC';
        }
    }

    public function getName()
    {
        return 'campaignchain_core_extension';
    }

    public function isRemovable($object){
        $class = get_class($object);
        switch($class){
            case 'CampaignChain\CoreBundle\Entity\Location':
                $locationService = $this->container->get('campaignchain.core.location');
                return $locationService->isRemovable($object);
            case 'CampaignChain\CoreBundle\Entity\Activity':
                $activityService = $this->container->get('campaignchain.core.activity');
                return $activityService->isRemovable($object);
            case 'CampaignChain\CoreBundle\Entity\Channel':
                $channelService = $this->container->get('campaignchain.core.channel');
                return $channelService->isRemovable($object);
            case 'CampaignChain\CoreBundle\Entity\Milestone':
                $milestoneService = $this->container->get('campaignchain.core.milestone');
                return $milestoneService->isRemovable($object);

        }
    }

    public function getGlobalLayout()
    {
        /** @var RequestStack $requestService */
        $requestService = $this->container->get('request_stack');
        $request = $requestService->getCurrentRequest();

        // If name of Layout is not set as URL parameter, use default options.
        if(
            !$request->get('campaignchain-layout') &&
            !$this->container->get('session')->has('campaignchain.layout')
        ){
            $layoutName = self::DEFAULT_LAYOUT_NAME;
        } elseif($request->get('campaignchain-layout')){
            $layoutName = $request->get('campaignchain-layout');
        } elseif ($this->container->get('session')->has('campaignchain.layout')){
            return $this->container->get('session')->get('campaignchain.layout');
        }

        // If the new layout name is set, then save it in the session.

        $paramParent = 'campaignchain_core.theme.layouts.' . $layoutName;

        if (
            $this->container->hasParameter($paramParent . '.show_header')
        ) {
            $layout['show_header'] = $this->container->getParameter($paramParent . '.show_header');
            $layout['show_footer'] = $this->container->getParameter($paramParent . '.show_footer');

            $this->container->get('session')->set('campaignchain.layout', $layout);

            return $layout;
        }

        throw new \Exception('No layout parameters defined for '.$paramParent);
    }

    public function isFirstLogin()
    {
        $session = $this->container->get('session');

        $isFirstLogin = false;
        if ($session->has('isFirstLogin')) {
            $isFirstLogin = true;
            $session->remove('isFirstLogin');
        }

        return $isFirstLogin;
    }
}
