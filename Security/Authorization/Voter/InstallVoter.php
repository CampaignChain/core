<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class InstallVoter implements VoterInterface
{
    private $em;

    public function __construct($em)
    {
        $this->em = $em;
    }

    public function supportsAttribute($attribute)
    {
        return $attribute == 'CAMPAIGNCHAIN_INSTALL';
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
                'Only one attribute is allowed for CAMPAIGNCHAIN_INSTALL'
            );
        }

        // Set the attribute to check against
        $attribute = $attributes[0];

        // Check if the given attribute is covered by this voter
        if (!$this->supportsAttribute($attribute)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        /*
         * If no database connection, then we assume that the system has
         * not yet been set up and we grant access to the installer.
         */
        try {
            $this->em->getConnection()->connect();
        } catch (\Exception $e) {
            return VoterInterface::ACCESS_GRANTED;
        }

        /*
         * If database connection exists, but no tables installed yet,
         * then we grant access to the installer.
         */
        $schemaManager = $this->em->getConnection()->getSchemaManager();
        if (!$schemaManager->listTables()) {
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