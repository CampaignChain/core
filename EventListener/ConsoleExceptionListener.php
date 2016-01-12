<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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