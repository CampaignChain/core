<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EntityService;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CampaignChain\CoreBundle\Util\ParserUtil;
use CampaignChain\CoreBundle\Entity\CTA;

class ChannelService
{
    protected $em;
    protected $container;


    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    /*
     * Generates a Tracking ID
     *
     * This method also makes sure that the ID is unique, i.e. that it does
     * not yet exist for another Channel.
     *
     * @return string
     */
    public function generateTrackingId()
    {
        $trackingId = md5(uniqid(mt_rand(), true));

        // Check with DB, whether already exists. If yes, then generate new one and check again.
        $cta = $this->em->getRepository('CampaignChainCoreBundle:Channel')->findOneByTrackingId($trackingId);

        if($cta){
            return $this->generateTrackingId();
        } else {
            return $trackingId;
        }
    }

    /**
     * Compose the channel icon path
     *
     * @param $channel
     * @return mixed
     */
    public function getIcons($channel)
    {
        // Compose the channel icon path
        $modulePath = $channel->getChannelModule()->getBundle()->getPath();
        $bundlePath = 'bundles/campaignchain'.strtolower(str_replace(DIRECTORY_SEPARATOR, '', str_replace('Bundle', '', $modulePath)));
        $bundleName = $channel->getChannelModule()->getBundle()->getName();
        $iconName = str_replace('campaignchain/channel-', '', $bundleName).'.png';
        $icon['16px'] = '/'.$bundlePath.'/images/icons/16x16/'.$iconName;
        $icon['24px'] = '/'.$bundlePath.'/images/icons/24x24/'.$iconName;

        return $icon;
    }
}