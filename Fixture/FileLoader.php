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

use Doctrine\Common\Persistence\ManagerRegistry;
use Fidry\AliceDataFixtures\LoaderInterface;
use Nelmio\Alice\Loader\NativeLoader;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

class FileLoader
{
    /**
     * @var NativeLoader
     */
    private $fixtureManager;

    /**
     * @var ManagerRegistry
     */
    private $em;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * FileLoader constructor.
     *
     * @param LoaderInterface $fixtureManager
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(
        LoaderInterface $fixtureManager,
        ManagerRegistry $managerRegistry
    )
    {
        $this->fixtureManager = $fixtureManager;
        $this->em = $managerRegistry->getManager();
    }

    /**
     * @param array $files
     * @param bool $doDrop
     */
    public function load(array $files, $doDrop = true)
    {
        try {
            $this->em->getConnection()->beginTransaction();

            // TODO Keep Module data intact
            $bundles =   $this->em->getRepository("CampaignChain\CoreBundle\Entity\Bundle")->findAll();
            $modules = $this->em->getRepository("CampaignChain\CoreBundle\Entity\Module")->findAll();

            if($this->fixtureManager->load($files)){
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