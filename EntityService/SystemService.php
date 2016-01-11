<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EntityService;

use Doctrine\ORM\EntityManager;

class SystemService
{
    /** @var EntityManager */
    private $em;

    /**
     * SystemService constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getActiveSystem()
    {
        return $this->em->getRepository('CampaignChainCoreBundle:System')->findOneBy([], ['id' => 'ASC']);
    }

    /*
     * Get bitly access token from database
     *
     * @return string access token
     */
    public function getBitlyAccessToken()
    {
        $activeSystem = $this->getActiveSystem();

        return $activeSystem->getBitlyAccessToken();
    }

    /*
     * Update Bitly access token
     *
     * @param string $access_token
     */
    public function updateBitlyAccessToken($access_token)
    {
        $activeSystem = $this->getActiveSystem();
        $activeSystem->setBitlyAccessToken($access_token);
        $this->em->persist($activeSystem);
        $this->em->flush();
    }
}
