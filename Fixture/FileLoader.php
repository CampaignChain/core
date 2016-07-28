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

use CampaignChain\CoreBundle\EntityService\UserService;
use CampaignChain\CoreBundle\Util\SystemUtil;
use Doctrine\ORM\EntityManager;
use h4cc\AliceFixturesBundle\Fixtures\FixtureManager;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesserInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

class FileLoader
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var FixtureManager
     */
    private $fixtureManager;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var MimeTypeGuesserInterface
     */
    private $mimeTypeGuesser;

    /**
     * @var ExtensionGuesserInterface
     */
    private $extensionGuesser;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * SampleDataUtil constructor.
     * @param UserService $userService
     * @param FixtureManager $fixtureManager
     * @param EntityManager $em
     * @param MimeTypeGuesserInterface $mimeTypeGuesser
     * @param ExtensionGuesserInterface $extensionGuesser
     */
    public function __construct(
        UserService $userService, FixtureManager $fixtureManager,
        EntityManager $em, MimeTypeGuesserInterface $mimeTypeGuesser,
        ExtensionGuesserInterface $extensionGuesser
    )
    {
        $this->userService = $userService;
        $this->fixtureManager = $fixtureManager;
        $this->em = $em;
        $this->mimeTypeGuesser = $mimeTypeGuesser;
        $this->extensionGuesser = $extensionGuesser;
    }

    /**
     * @param array $files
     * @param bool $doDrop
     */
    public function load(array $files, $doDrop = true)
    {
        try {
            $this->em->getConnection()->beginTransaction();

            $userProcessor = new UserProcessor(
                realpath(
                    SystemUtil::getRootDir().DIRECTORY_SEPARATOR.
                    'vendor'.DIRECTORY_SEPARATOR
                ),
                $this->userService, $this->mimeTypeGuesser, $this->extensionGuesser
            );

            // Create Alice manager and fixture set
            $this->fixtureManager->addProcessor($userProcessor);
            $set = $this->fixtureManager->createFixtureSet();

            // Add the fixture files
            foreach($files as $file) {
                $set->addFile($file, 'yaml');
            }

            $set->setDoDrop($doDrop);
            $set->setDoPersist(true);
            $set->setSeed(1337 + 42);

            // TODO Keep Module data intact
            $bundles =   $this->em->getRepository("CampaignChain\CoreBundle\Entity\Bundle")->findAll();
            $modules = $this->em->getRepository("CampaignChain\CoreBundle\Entity\Module")->findAll();

            if($this->fixtureManager->load($set)){
                // TODO: Restore modules data
                foreach($bundles as $bundle){
                    $this->em->persist($bundle);
                }
                foreach($modules as $module){
                    $this->em->persist($module);
                }
                $this->em->flush();

                $this->em->getConnection()->commit();

                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->setException($e);
            
            return false;
        }
    }

    public function setException($exception)
    {
        $this->exception = $exception;
    }

    public function getException()
    {
        return $this->exception;
    }
}