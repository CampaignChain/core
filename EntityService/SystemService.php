<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
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
}