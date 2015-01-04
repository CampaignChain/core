<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Twig;

use CampaignChain\CoreBundle\Util\ParserUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;

class CampaignChainCoreExtension extends \Twig_Extension
{
    protected $em;
    protected $container;
    protected $datetime;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
        $this->datetime = $this->container->get('campaignchain.core.util.datetime');
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('campaignchain_image', array($this, 'image')),
            new \Twig_SimpleFilter('campaignchain_channel_asset_path', array($this, 'channelAssetPath')),
            new \Twig_SimpleFilter('campaignchain_channel_icon_name', array($this, 'channelIconName')),
            new \Twig_SimpleFilter('campaignchain_datetime', array($this, 'datetime')),
            new \Twig_SimpleFilter('campaignchain_timezone', array($this, 'timezone')),
            new \Twig_SimpleFilter('campaignchain_data_trigger_hook', array($this, 'dataTriggerHook')),
            new \Twig_SimpleFilter('campaignchain_tpl_trigger_hook_inline', array($this, 'tplTriggerHookInline')),
            new \Twig_SimpleFilter('campaignchain_channel_root_locations', array($this, 'channelRootLocations')),
            new \Twig_SimpleFilter('campaignchain_remaining_time', array($this, 'remainingTime')),
            new \Twig_SimpleFilter('campaignchain_remaining_time_badge', array($this, 'remainingTimeBadge')),
            new \Twig_SimpleFilter('campaignchain_parse_url', array($this, 'parseUrl')),
            new \Twig_SimpleFilter('campaignchain_ltrim', array($this, 'ltrim')),
            new \Twig_SimpleFilter('campaignchain_make_links', array($this, 'makeLinks')),
        );
    }

    public function system(){
        return $this->em->getRepository('CampaignChainCoreBundle:System')->find(1);
    }

    public function image($object)
    {
        $class = get_class($object);

        if(strpos($class, 'CoreBundle\Entity\Location') !== false){
            return $object->getImage();
        } else {
            return $this->channelAssetPath($object).'/images/icons/16x16/'.$this->channelIconName($object);
        }
    }

    public function channelAssetPath($object)
    {
        $class = get_class($object);

        if(strpos($class, 'CoreBundle\Entity\Bundle') !== false){
            $bundlePath = $object->getPath();
        } elseif(strpos($class, 'CoreBundle\Entity\ChannelModule') !== false){
            $bundlePath = $object->getBundle()->getPath();
        } elseif(strpos($class, 'CoreBundle\Entity\Channel') !== false){
            $bundlePath = $object->getChannelModule()->getBundle()->getPath();
        } elseif(strpos($class, 'CoreBundle\Entity\Activity') !== false){
            $bundlePath = $object->getChannel()->getChannelModule()->getBundle()->getPath();
        }

        $path = 'bundles/campaignchain'.strtolower(str_replace(DIRECTORY_SEPARATOR, '', str_replace('Bundle', '', str_replace('-', '', $bundlePath))));

        return $path;
    }

    public function channelIconName($object)
    {
        $class = get_class($object);

        if(strpos($class, 'CoreBundle\Entity\Bundle') !== false){
            $bundleName = $object->getName();
        } elseif(strpos($class, 'CoreBundle\Entity\ChannelModule') !== false){
            $bundleName = $object->getBundle()->getName();
        } elseif(strpos($class, 'CoreBundle\Entity\Channel') !== false){
            $bundleName = $object->getChannelModule()->getBundle()->getName();
        } elseif(strpos($class, 'CoreBundle\Entity\Activity') !== false){
            $bundleName = $object->getChannel()->getChannelModule()->getBundle()->getName();
        }

        $iconName = str_replace('campaignchain/channel-', '', $bundleName).'.png';

        return $iconName;
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

    public function tplTriggerHookInline($object)
    {
        // TODO: Store already retrieved service string in a property of this class for performance reasons.
        $hookConfig = $this->em->getRepository('CampaignChainCoreBundle:Hook')->find($object->getTriggerHook());
        $hookService = $this->container->get($hookConfig->getServices()['entity']);
        return $hookService->tplInline($object);
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

    public function getGlobals()
    {
        // Do not load globals during installation, which is when no
        // tables exists yet in the database.
        $schemaManager = $this->em->getConnection()->getSchemaManager();
        if (!$schemaManager->listTables()) {
            return array();
        }

        return array(
            "campaignchain_user_datetime_format" => array(
                'moment_js' => $this->datetime->getUserDatetimeFormat('moment_js'),
                'iso8601' => $this->container->get('session')->get('campaignchain.dateFormat').' '.$this->container->get('session')->get('campaignchain.timeFormat'),
            ),
            "campaignchain_user_timezone_offset" => $this->getGlobalTimezoneOffset(),
            "campaignchain_user_timezone_abbreviation" => $this->getGlobalTimezoneAbbreviation(),
            'campaignchain_system' => $this->system(),
            'campaignchain_dev' => $this->container->getParameter('campaignchain_dev'),
        );
    }

    private function getGlobalTimezoneOffset(){
        // Execute only if the user is logged in.
        if( $this->container->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED') ){
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
        if( $this->container->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED') ){
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
}
