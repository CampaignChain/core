<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class DevVoter implements VoterInterface
{
    const CAMPAIGNCHAIN_DEV = 'CAMPAIGNCHAIN_DEV';

    private $dev;

    public function __construct($dev)
    {
        $this->dev = $dev;
    }

    public function supportsAttribute($attribute)
    {
        return null !== $attribute && ($attribute === self::CAMPAIGNCHAIN_DEV);
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
                'Only one attribute is allowed for '.self::CAMPAIGNCHAIN_DEV
            );
        }

        // Set the attribute to check against
        $attribute = $attributes[0];

        // Check if the given attribute is covered by this voter
        if (!$this->supportsAttribute($attribute)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        /*
         * If campaignchain.dev_mode is true, then we grant access.
         */
        if($this->dev){
            return VoterInterface::ACCESS_GRANTED;
        }

        return VoterInterface::ACCESS_DENIED;
    }
}