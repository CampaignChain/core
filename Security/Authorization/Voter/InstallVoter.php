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

namespace CampaignChain\CoreBundle\Security\Authorization\Voter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class InstallVoter implements VoterInterface
{
    const CAMPAIGNCHAIN_INSTALL = 'CAMPAIGNCHAIN_INSTALL';

    private $em;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->em = $managerRegistry->getManager();
    }

    public function supportsAttribute($attribute)
    {
        return null !== $attribute && ($attribute === self::CAMPAIGNCHAIN_INSTALL);
    }

    public function supportsClass($class)
    {
        return true;
    }

    public function vote(TokenInterface $token, $object, array $attributes)
    {
        // Check if class of this object is supported by this voter
        if (!$this->supportsClass(get_class($object))) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // Check if the voter is used correct, only allow one attribute
        if (1 !== count($attributes)) {
            throw new \InvalidArgumentException(
                'Only one attribute is allowed for '.self::CAMPAIGNCHAIN_INSTALL
            );
        }

        // Set the attribute to check against
        $attribute = $attributes[0];

        // Check if the given attribute is covered by this voter
        if (!$this->supportsAttribute($attribute)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        /*
         * If database connection exists, but no tables installed yet,
         * then we grant access to the installer.
         */
        $schemaManager = $this->em->getConnection()->getSchemaManager();
        $tables = $schemaManager->listTables();
        if (!$tables || (is_array($tables) && !count($tables))) {
            return VoterInterface::ACCESS_GRANTED;
        }

        /*
         * If _no_ entry for the admin user exists, then the system has
         * not been set up and we grant access to the installer.
         */
        $admin = $this->em->getRepository('CampaignChainCoreBundle:User')->findOneByUsername('admin');
        if(!$admin){
            return VoterInterface::ACCESS_GRANTED;
        }

        return VoterInterface::ACCESS_DENIED;
    }
}