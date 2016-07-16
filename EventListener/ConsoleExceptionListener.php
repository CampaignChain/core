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

namespace CampaignChain\CoreBundle\EventListener;


use CampaignChain\CoreBundle\Command\SchedulerCommand;
use CampaignChain\CoreBundle\Entity\Scheduler;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;

/**
 * Class ConsoleExceptionListener
 * @package CampaignChain\CoreBundle\EventListener
 */
class ConsoleExceptionListener
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * ConsoleExceptionListener constructor.
     * @param LoggerInterface $logger
     * @param EntityManager $entityManager
     */
    public function __construct(LoggerInterface $logger, EntityManager $entityManager)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * In case of an exception in scheduler console command
     * the message should be saved into the scheduler entity
     *
     * @param ConsoleExceptionEvent $event
     */
    public function onConsoleException(ConsoleExceptionEvent $event)
    {
        /** @var SchedulerCommand $command */
        $command = $event->getCommand();

        if ($command->getName() != 'campaignchain:scheduler') {
            return;
        }

        // if scheduler is null exception happened in early stage
        // maybe email should be sent
        if (!$command->getScheduler()) {
            return;
        }

        /** @var Scheduler $scheduler */
        $scheduler = $command->getScheduler();

        $scheduler->setMessage($event->getException()->getMessage());
        $scheduler->setStatus(Scheduler::STATUS_ERROR);
        $scheduler->setExecutionEnd(new \DateTime());

        $this->entityManager->persist($scheduler);
        $this->entityManager->flush();

        $command->getIo()->error($scheduler->getMessage());
        $this->logger->critical($scheduler->getMessage());
    }
}