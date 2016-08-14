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

namespace CampaignChain\CoreBundle\Command;

use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\Job;
use CampaignChain\CoreBundle\Entity\Scheduler;
use CampaignChain\CoreBundle\Entity\SchedulerReportLocation;
use CampaignChain\CoreBundle\Entity\SchedulerReportOperation;
use DateTime;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\LockHandler;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Class SchedulerCommand.
 *
 * Usage:
 * php app/console campaignchain:scheduler
 *
 * Configuration:
 * Create a cron job that runs this command every minute.
 *
 * @author Sandro Groganz <sandro@campaignchain.com>
 */
class SchedulerCommand extends ContainerAwareCommand
{
    const LOGGER_MSG_START = '----- START -----';
    const LOGGER_MSG_END = '----- END -----';

    /**
     * @var int Interval in minutes.
     */
    protected $interval = 5;

    /**
     * @var int Process timeout in seconds.
     */
    protected $timeout = 600;

    /**
     * The order of actions to be executed.
     *
     * Campaigns first, because they might create Milestones, Activities and
     * Operations that have to be executed immediately after creation.
     *
     * Next are milestones.
     *
     * Then come Activities, which can create Operations.
     *
     * @var array
     */
    protected $actionsOrder = [
        Action::TYPE_CAMPAIGN,
        Action::TYPE_MILESTONE,
        Action::TYPE_ACTIVITY,
        Action::TYPE_OPERATION,
    ];

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * @var DateTime
     */
    protected $now;

    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var Stopwatch
     */
    protected $stopwatchScheduler;

    /**
     * @return Scheduler
     */
    public function getScheduler()
    {
        return $this->scheduler;
    }

    /**
     * @return SymfonyStyle
     */
    public function getIo()
    {
        return $this->io;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('campaignchain:scheduler')
            ->setDescription('Executes scheduled campaigns, activities, operations, etc.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initializeVariables();

        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('CampaignChain Scheduler');

        // Prevent multiple console runs
        $lock = new LockHandler('campaignchain:scheduler');

        if (!$lock->lock()) {
            $this->io->error('The command is already in another process.');

            return 0;
        }

        $this->scheduler = $this->startScheduler();

        $this->logger->info(self::LOGGER_MSG_START);
        $this->logger->info('Scheduler with ID {id} started', ['id' => $this->scheduler->getId()]);

        $this->io->text('Running scheduler with:');
        $this->io->listing(
            [
                'Scheduler ID: '.$this->scheduler->getId(),
                'Interval: '.$this->scheduler->getPeriodInterval().' minute(s)',
                'Period starts: '.$this->scheduler->getPeriodStart()->format('Y-m-d H:i:s T'),
                'Period ends: '.$this->scheduler->getPeriodEnd()->format('Y-m-d H:i:s T'),
            ]
        );

        //Que jobs
        $this->gatherActionData();

        // Execute the scheduled report jobs.
        $this->prepareReportJobs();

        $this->executeJobs();

        // Scheduler is done, let's see how long it took.
        $stopwatchSchedulerEvent = $this->stopwatchScheduler->stop('scheduler');

        $this->scheduler->setDuration($stopwatchSchedulerEvent->getDuration());
        $this->scheduler->setExecutionEnd($this->now);
        $this->scheduler->setStatus(Scheduler::STATUS_CLOSED);
        $this->em->persist($this->scheduler);
        $this->em->flush();

        $this->io->success('Duration of scheduler: '.$stopwatchSchedulerEvent->getDuration().' milliseconds');
        $this->logger->info(self::LOGGER_MSG_END);
    }

    /**
     * Initialize the variables for the command.
     */
    protected function initializeVariables()
    {
        // Capture duration of scheduler with Symfony's Stopwatch component.
        $this->stopwatchScheduler = new Stopwatch();
        $this->stopwatchScheduler->start('scheduler');

        // If in dev mode, use a long interval to make testing the scheduler easier.
        if ($this->getContainer()->getParameter('campaignchain.env') == 'dev') {
            $this->interval = $this->getContainer()->getParameter('campaignchain_core.scheduler.interval_dev');
        } else {
            $this->interval = $this->getContainer()->getParameter('campaignchain_core.scheduler.interval');
        }

        $this->timeout = $this->getContainer()->getParameter('campaignchain_core.scheduler.timeout');;

        if ($this->getContainer()->has('monolog.logger.scheduler')) {
            $this->logger = $this->getContainer()->get('monolog.logger.scheduler');
        } else {
            $this->logger = $this->getContainer()->get('logger');
        }

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * Create a new scheduler instance.
     *
     * @return Scheduler
     */
    protected function startScheduler()
    {
        $this->now = new \DateTime('now', new \DateTimeZone('UTC'));

        $periodStart = clone $this->now;
        $periodStart->modify('-'.$this->interval.' minutes');

        $scheduler = new Scheduler();
        $scheduler->setStatus(Scheduler::STATUS_RUNNING);
        $scheduler->setExecutionStart($this->now);
        $scheduler->setPeriodStart($periodStart);
        $scheduler->setPeriodEnd($this->now);
        $scheduler->setPeriodInterval($this->interval);

        $this->em->persist($scheduler);
        $this->em->flush();

        return $scheduler;
    }

    /**
     * Gather all the action for the command
     * and if it's possible add it to the job queue.
     */
    protected function gatherActionData()
    {
        foreach ($this->actionsOrder as $actionType) {
            // Find all Actions to be processed.
            $actions = $this->getActions(
                $actionType,
                $this->scheduler->getPeriodStart(),
                $this->scheduler->getPeriodEnd()
            );

            // Store all the Action-related info in the Job entity.
            if (!$actions) {
                $this->io->text(
                    'No actions of type "'.$actionType.'" scheduled within the past '.$this->interval.' minutes.'
                );

                continue;
            }

            $tableHeaders = ['ID', 'Start Date', 'End Date', 'Name', 'Status'];
            if ($actionType != Action::TYPE_CAMPAIGN) {
                $tableHeaders[] = 'Campaign ID';
                $tableHeaders[] = 'Campaign Status';
            }
            if ($actionType == Action::TYPE_OPERATION) {
                $tableHeaders[] = 'Action ID';
                $tableHeaders[] = 'Action Status';
            }

            $outputTableRows = [];

            foreach ($actions as $action) {
                // Has a scheduler job been defined in the Action's module?
                $actionServices = $action->getModule()->getServices();
                if (!is_array($actionServices) || !isset($actionServices['job'])) {
                    $message =
                        'No job service defined for module "'
                        .$action->getModule()->getIdentifier()
                        .'" in bundle "'
                        .$action->getModule()->getBundle()->getName().'" '
                        .'while processing Action of type "'.$action->getType().'" '
                        .'with ID "'.$action->getId().'"'
                        .'.';

                    $this->io->text($message);

                    continue;
                }

                // Queue new Job.
                $this->queueJob(
                    $action->getType(),
                    $action->getId(),
                    $action->getModule()->getServices()['job'],
                    'action'
                );

                $this->logger->info(
                    'New job of {type} with the action ID {id} queued',
                    [
                        'type' => $action->getType(),
                        'id' => $action->getId(),
                    ]
                );

                // Highlight the date that is within the execution period.
                $startDate = $action->getStartDate()->format('Y-m-d H:i:s');
                $endDate = null;

                if ($action->getStartDate() >= $this->scheduler->getPeriodStart()) {
                    $startDate = '<options=bold>'.$startDate.'</options=bold>';
                } elseif ($action->getEndDate()) {
                    $endDate = $action->getEndDate()->format('Y-m-d H:i:s');
                    $endDate = '<options=bold>'.$endDate.'</options=bold>';
                }

                $tableRows = [
                    $action->getId(),
                    $startDate,
                    $endDate,
                    $action->getName(),
                    $action->getStatus(),
                ];

                if ($actionType == Action::TYPE_ACTIVITY || $actionType == Action::TYPE_MILESTONE) {
                    $tableRows[] = $action->getCampaign()->getId();
                    $tableRows[] = $action->getCampaign()->getStatus();
                }
                if ($actionType == Action::TYPE_OPERATION) {
                    $tableRows[] = $action->getActivity()->getCampaign()->getId();
                    $tableRows[] = $action->getActivity()->getCampaign()->getStatus();
                    $tableRows[] = $action->getActivity()->getId();
                    $tableRows[] = $action->getActivity()->getStatus();
                }

                $outputTableRows[] = $tableRows;
            }

            if (count($outputTableRows)) {
                // Create the table rows for output.
                $this->io->newLine();
                $this->io->text('These actions of type "'.$actionType.'"  will be executed:');
                $this->io->table($tableHeaders, $outputTableRows);
            } else {
                $this->io->text(
                    'No actions of type "'.$actionType.'" scheduled within the past '.$this->interval.' minutes.'
                );
            }
        }
    }

    /**
     * @param $actionType
     * @param DateTime $periodStart
     * @param DateTime $periodEnd
     *
     * @return Action[]
     */
    protected function getActions($actionType, \DateTime $periodStart, \DateTime $periodEnd)
    {
        switch ($actionType) {
            case Action::TYPE_OPERATION:
                return $this->em
                    ->getRepository('CampaignChainCoreBundle:Operation')
                    ->getScheduledOperation($periodStart, $periodEnd);

            case Action::TYPE_ACTIVITY:
                return $this->em
                    ->getRepository('CampaignChainCoreBundle:Activity')
                    ->getScheduledActivity($periodStart, $periodEnd);

            case Action::TYPE_MILESTONE:
                return $this->em
                    ->getRepository('CampaignChainCoreBundle:Milestone')
                    ->getScheduledMilestone($periodStart, $periodEnd);

            case Action::TYPE_CAMPAIGN:
                return $this->em
                    ->getRepository('CampaignChainCoreBundle:Campaign')
                    ->getScheduledCampaign($periodStart, $periodEnd);

            default:
                throw new \LogicException('Wrong action type: '.$actionType);
        }
    }

    /**
     * Create new job.
     *
     * @param $actionType
     * @param $actionId
     * @param $service
     * @param null $jobType
     */
    protected function queueJob($actionType, $actionId, $service, $jobType = null)
    {
        $job = new Job();
        $job->setScheduler($this->scheduler);
        //$this->scheduler->addJob($job);
        $job->setActionType($actionType);
        $job->setActionId($actionId);
        $job->setName($service);
        $job->setJobType($jobType);
        $job->setStatus(Job::STATUS_OPEN);

        $this->em->persist($job);
        $this->em->flush();
    }

    /**
     * TODO: Make this work with Milestones and Activities as well.
     *
     * @throws \Exception
     */
    protected function prepareReportJobs()
    {
        $this->prepareReportOperationJobs();
        $this->prepareReportLocationJobs();
    }

    protected function prepareReportOperationJobs()
    {
        /** @var SchedulerReportOperation[] $scheduledReports */
        $scheduledReports = $this->em
            ->getRepository('CampaignChainCoreBundle:SchedulerReportOperation')
            ->getScheduledReportJobsForSchedulerCommand(
                $this->scheduler->getPeriodStart(),
                $this->scheduler->getPeriodEnd()
            );

        if (empty($scheduledReports)) {
            $this->io->text('No scheduled Operation reports.');
            $this->logger->info('No scheduled Operation reports.');

            return;
        }

        // Queue the scheduled reports.
        $this->io->text('Processing scheduled Operation reports.');
        $this->logger->info('Processing scheduled Operation reports.');

        foreach ($scheduledReports as $scheduledReport) {
            $txt = 'Report ID: '.$scheduledReport->getId();
            $this->io->section($txt);
            $this->logger->info($txt);

            // Check whether the Action's end date has been modified
            // since we last ran this report job.
            $endDateChanged = false;

            if ($scheduledReport->getEndAction() != $scheduledReport->getEndDate()) {
                /*
                 * This flag will ensure that the report job will be
                 * executed so that it can handle the end date change.
                 */
                $endDateChanged = true;

                // Update end date of report scheduler entry to Action's end date.
                $newEndDate = clone $scheduledReport->getEndAction();
                $scheduledReport->setEndDate($newEndDate);

                $txt = "Action's end date changed to ".$newEndDate->format(\DateTime::ISO8601);
                $this->io->note($txt);
                $this->logger->info($txt);
            }

            // Check whether we're past the prolonged end date if defined.
            if ($scheduledReport->getProlongation() != null) {
                $this->io->text('Prolongation: '.$scheduledReport->getProlongation());

                $interval = \DateInterval::createFromDateString($scheduledReport->getProlongation());
                $prolongedEndDate = clone $scheduledReport->getEndDate();
                $prolongedEndDate->add($interval);

                // Prolonged end date is older than now.
                if ($prolongedEndDate < $this->now) {
                    if (!$endDateChanged) {
                        $txt = 'Past prolongation period. Skipping this report job.';
                        $this->io->text($txt);
                        $this->logger->info($txt);

                        // Don't execute this report job.
                        continue;
                    } else {
                        $txt = "Past prolongation period and end date changed. We'll let the report job handle this.";
                        $this->io->text($txt);
                        $this->logger->info($txt);
                    }
                }
                // No prolongation, so check if end date is older than next run.
            } elseif ($scheduledReport->getEndDate() < $scheduledReport->getNextRun()) {
                if (!$endDateChanged) {
                    $txt = 'No prolongation and past end date. Skipping this report job.';
                    $this->io->text($txt);
                    $this->logger->info($txt);

                    continue;
                } else {
                    $txt = "No prolongation and past end date, but end date changed. We'll let the report job handle this.";
                    $this->io->text($txt);
                    $this->logger->info($txt);
                }
            }

            $this->queueReportJob($scheduledReport);

            /*
             * Update next run.
             */

            // Are we within the regular scheduled period?
            if (
                $scheduledReport->getEndDate() > $this->now &&
                $scheduledReport->getInterval() != null
            ) {
                $interval = \DateInterval::createFromDateString($scheduledReport->getInterval());
                $nextRun = clone $scheduledReport->getNextRun();
                $scheduledReport->setNextRun($nextRun->add($interval));

                $txt = 'Regular period. Next run is in '.$scheduledReport->getInterval();
                $this->io->text($txt);
                $this->logger->info($txt);

                // ... or are we within the prolonged period?
            } elseif (
                isset($prolongedEndDate) &&
                $prolongedEndDate > $this->now &&
                $scheduledReport->getProlongationInterval() != null
            ) {
                $interval = \DateInterval::createFromDateString($scheduledReport->getProlongationInterval());
                /*
                 * The prolongation interval starts with the end date.
                 * Hence, if this is the first interval within the
                 * prolonged period, then we add the interval on top of
                 * the end date. If not, then on top of the next run date.
                 */
                if ($scheduledReport->getNextRun() < $scheduledReport->getEndDate()) {
                    $nextRun = clone $scheduledReport->getEndDate();
                } else {
                    $nextRun = clone $scheduledReport->getNextRun();
                }
                $scheduledReport->setNextRun($nextRun->add($interval));

                $txt = 'Prolonged period. Next run is in '.$scheduledReport->getProlongationInterval();
                $this->io->text($txt);
                $this->logger->info($txt);
            }

            $this->em->persist($scheduledReport);
        }

        //update the next run dates for the scheduler
        $this->em->flush();
    }

    protected function queueReportJob($scheduledReport)
    {
        if ($scheduledReport instanceof SchedulerReportOperation) {
            $module = $scheduledReport->getOperation()->getOperationModule();
            $type = Action::TYPE_OPERATION;
            $id = $scheduledReport->getOperation()->getId();
            $name = $scheduledReport->getOperation()->getName();
        } elseif ($scheduledReport instanceof SchedulerReportLocation) {
            $module = $scheduledReport->getLocation()->getLocationModule();
            $type = Action::TYPE_LOCATION;
            $id = $scheduledReport->getLocation()->getId();
            $name = $scheduledReport->getLocation()->getName();
        }

        /* elseif($scheduledReport instanceof \CampaignChain\CoreBundle\Entity\SchedulerReportActivity){
            $module = $scheduledReport->getActivity()->getActivityModule();
            $type = Action::TYPE_ACTIVITY;
            $id = $scheduledReport->getActivity()->getId();
        } elseif($scheduledReport instanceof \CampaignChain\CoreBundle\Entity\SchedulerReportMilestone){
            $module = $scheduledReport->getMilestone()->getMilestoneModule();
            $type = Action::TYPE_MILESTONE;
            $id = $scheduledReport->getMilestone()->getId();
        }*/

        $text = 'Adding Job for collecting report data for '.Action::TYPE_OPERATION.' '.$id.' "'.$name.'".';
        $this->io->text($text);
        $this->logger->info($text);

        // Has a report job been defined for the module?
        $moduleServices = $module->getServices();
        if (!is_array($moduleServices) || !isset($moduleServices['report'])) {
            $msg = 'No report service defined for module "'
                .$module->getIdentifier()
                .'" in bundle "'
                .$module->getBundle()->getName().'".';

            $this->logger->error($msg);
            if ($this->getContainer()->getParameter('campaignchain.env') == 'dev') {
                throw new \Exception($msg);
            }
            $this->logger->error($msg);
        }

        $this->queueJob(
            $type,
            $id,
            $module->getServices()['report'],
            'report'
        );

        $this->io->text('Queued report job with service '.$module->getServices()['report']);
    }

    protected function prepareReportLocationJobs()
    {
        /** @var SchedulerReportLocation[] $scheduledReports */
        $scheduledReports = $this->em
            ->getRepository('CampaignChainCoreBundle:SchedulerReportLocation')
            ->getScheduledReportJobsForSchedulerCommand(
                $this->scheduler->getPeriodStart(),
                $this->scheduler->getPeriodEnd()
            );

        if (empty($scheduledReports)) {
            $this->io->text('No scheduled Location reports.');
            $this->logger->info('No scheduled Location reports.');

            return;
        }

        // Queue the scheduled reports.
        $this->io->text('Processing scheduled Location reports.');
        $this->logger->info('Processing scheduled Location reports.');

        foreach ($scheduledReports as $scheduledReport) {
            $txt = 'Report ID: '.$scheduledReport->getId();
            $this->io->section($txt);
            $this->logger->info($txt);

            $this->queueReportJob($scheduledReport);

            /*
             * Update next run.
             */

            // Are we within the regular scheduled period?
            if (
                $scheduledReport->getEndDate() > $this->now &&
                $scheduledReport->getInterval() != null
            ) {
                $interval = \DateInterval::createFromDateString($scheduledReport->getInterval());
                $nextRun = clone $scheduledReport->getNextRun();
                $scheduledReport->setNextRun($nextRun->add($interval));

                $txt = 'Regular period. Next run is in '.$scheduledReport->getInterval();
                $this->io->text($txt);
                $this->logger->info($txt);
            }

            $this->em->persist($scheduledReport);
        }

        //update the next run dates for the scheduler
        $this->em->flush();
    }

    /**
     * Search for open jobs and executes them
     * then show a report about the done job.
     */
    protected function executeJobs()
    {
        // Get the Jobs to be processed.
        $jobsInQueue = $this->em
            ->getRepository('CampaignChainCoreBundle:Job')
            ->getOpenJobsForScheduler($this->scheduler);

        if (empty($jobsInQueue)) {
            return;
        }

        $this->io->section('Executing jobs now:');
        $this->logger->info('Executing {counter} jobs now:', ['counter' => count($jobsInQueue)]);
        $this->io->progressStart(count($jobsInQueue));

        foreach ($jobsInQueue as $jobInQueue) {
            // Execute job.
            $this->executeJob($jobInQueue);

            $this->io->progressAdvance();
        }

        // ensure that the progress bar is at 100%
        $this->io->progressFinish();

        $this->em->clear();
        // Get the processed jobs.
        $jobsProcessed = $this->em
            ->getRepository('CampaignChainCoreBundle:Job')
            ->getProcessedJobsForScheduler($this->scheduler);

        if (empty($jobsProcessed)) {
            return;
        }

        // Display the results of the execution.

        $tableHeader = [
            'Job ID',
            'Operation ID',
            'Process ID',
            'Job Name',
            'Job Start Date',
            'Job End Date',
            'Duration',
            'Status',
            'Message',
        ];
        $outputTableRows = [];

        foreach ($jobsProcessed as $jobProcessed) {
            $startDate = null;
            $endDate = null;

            if ($jobProcessed->getStartDate()) {
                $startDate = $jobProcessed->getStartDate()->format('Y-m-d H:i:s');
            }
            if ($jobProcessed->getEndDate()) {
                $endDate = $jobProcessed->getEndDate()->format('Y-m-d H:i:s');
            }

            $jobData = [
                $jobProcessed->getId(),
                $jobProcessed->getActionId(),
                $jobProcessed->getPid(),
                $jobProcessed->getName(),
                $startDate,
                $endDate,
                $jobProcessed->getDuration().' ms',
                $jobProcessed->getStatus(),
                $jobProcessed->getMessage(),
            ];

            $outputTableRows[] = $jobData;

            if (Job::STATUS_ERROR === $jobProcessed->getStatus()) {
                $context = array_combine($tableHeader, $jobData);
                $this->logger->error($jobProcessed->getMessage(), $context);
            }
        }

        $this->io->text('Results of executed actions:');
        $this->io->table($tableHeader, $outputTableRows);
    }

    /**
     * Execute one job.
     *
     * @param Job $job
     */
    protected function executeJob(Job $job)
    {
        $job->setStartDate($this->now);

        $command = 'php app/console campaignchain:job '.$job->getId().' --env='.$this->getContainer()->get(
                'kernel'
            )->getEnvironment();
        $process = new Process($command);

        $this->logger->info('Executing Job with command: '.$command);
        $process->setTimeout($this->timeout);

        try {
            $process->mustRun();

            $this->logger->info('Process ID: '.$process->getPid());

            $job->setPid($process->getPid());
            $this->em->flush();
        } catch (ProcessFailedException $e) {
            $errMsg = 'Process failed: '.$e->getMessage();

            $this->logger->error($errMsg);
            $this->logger->info(self::LOGGER_MSG_END);

            $job->setPid($process->getPid());
            $job->setStatus(Job::STATUS_ERROR);
            $job->setMessage($errMsg);
            $this->em->flush();

            throw new \RuntimeException($e->getMessage());
        }
    }

    protected function testDatabase()
    {
        try {
            $this->em->getConnection()->connect();
        } catch (\Exception $e) {
            $this->logger->error('No database connection.');
            exit;
        }
    }

    protected function testInternet()
    {
        $connected = @fsockopen('www.example.com', 80);
        if ($connected) {
            fclose($connected);

            return true;
        } else {
            $errMsg = 'No connection to Internet.';
            $this->scheduler->setStatus(Scheduler::STATUS_ERROR);
            $this->scheduler->setMessage($errMsg);
            $this->em->flush();
            $this->logger->error($errMsg);
            throw new \Exception($errMsg);
        }
    }
}
