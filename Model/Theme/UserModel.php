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

namespace CampaignChain\CoreBundle\Model\Theme;

use Avanzu\AdminThemeBundle\Model\UserInterface as ThemeUser;
use CampaignChain\CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserModel implements ThemeUser {

    /**
     * @var User
     */
    protected $user;

    public function __construct(TokenStorageInterface $token)
    {
        $this->user = $token->getToken()->getUser();
    }

    public function getAvatar()
    {
        return $this->user->getAvatarImage();
    }

    public function getUsername()
    {
        return $this->user->getUsernameCanonical();
    }

    public function getName()
    {
        return $this->user->getName();
    }

    public function getMemberSince()
    {
        return $this->user->getCreatedDate();
    }

    public function isOnline()
    {
        return true;
    }

    public function getIdentifier()
    {
        return $this->user->getId();
    }

    public function getTitle()
    {
         return null;
    }

}