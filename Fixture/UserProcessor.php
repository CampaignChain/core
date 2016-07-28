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

namespace CampaignChain\CoreBundle\Fixture;

use CampaignChain\CoreBundle\Entity\User;
use CampaignChain\CoreBundle\EntityService\UserService;
use Liip\ImagineBundle\Model\Binary;
use Nelmio\Alice\ProcessorInterface;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesserInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

class UserProcessor implements ProcessorInterface
{
    /** @var string */
    private $vendorBaseDir;

    /** @var UserService */
    private $userService;

    /** @var MimeTypeGuesserInterface */
    private $mimeTypeGuesser;

    /** @var ExtensionGuesserInterface */
    private $extensionGuesser;

    public function __construct($vendorBaseDir, UserService $userService, MimeTypeGuesserInterface $mimeTypeGuesser, ExtensionGuesserInterface $extensionGuesser)
    {
        $this->vendorBaseDir = rtrim($vendorBaseDir, "/");
        $this->userService = $userService;
        $this->mimeTypeGuesser = $mimeTypeGuesser;
        $this->extensionGuesser = $extensionGuesser;
    }


    public function preProcess($object)
    {

    }

    public function postProcess($object)
    {
        if (!($object instanceof User)) {
            return;
        }

        $imagePath = $object->getAvatarImage();

        // No image given, bail out
        if (empty($imagePath)) {
            return;
        }

        $fullPath = "{$this->vendorBaseDir}/{$imagePath}";

        // Image doesn't exist, bail out
        if (!file_exists($fullPath)) {
            return;
        }

        $mimeType = $this->mimeTypeGuesser->guess($fullPath);
        $image = new Binary(
            file_get_contents($fullPath),
            $mimeType,
            $this->extensionGuesser->guess($mimeType)
        );

        $avatarImage = $this->userService->storeImageAsAvatar($image);
        $object->setAvatarImage($avatarImage);
    }
}