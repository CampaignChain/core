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
