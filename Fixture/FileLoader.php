<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
                realpath(SystemUtil::getRootDir()), $this->userService,
                $this->mimeTypeGuesser, $this->extensionGuesser
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