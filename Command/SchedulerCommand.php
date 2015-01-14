<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Command;

use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\Scheduler;
use CampaignChain\CoreBundle\Entity\Job;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Process\Process;

/**
 * Class SchedulerCommand
 *
 * Usage:
 * php app/console campaignchain:scheduler
 *
 * Configuration:
 * Create a cron job that runs this command every minute.
 *
 * Order of execution:
 * 1. Operations
 * 2. Activities
 * 3. Milestones
 * 4. Campaigns
 *
 * @package CampaignChain\CoreBundle\Command
 * @author Sandro Groganz <sandro@campaignchain.com>
 */
class SchedulerCommand extends ContainerAwareCommand
{
    // TODO: Get this from the system settings.
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
     * @var array
     */
    protected $actionsOrder = array(
        0 => Action::TYPE_OPERATION,
        1 => Action::TYPE_ACTIVITY,
        2 => Action::TYPE_MILESTONE,
        3 => Action::TYPE_CAMPAIGN
    );

    protected $logger;
    protected $em;

    protected $scheduler;

    protected function configure()
    {
        $this
            ->setName('campaignchain:scheduler')
            ->setDescription('Executes scheduled campaigns, activities, operations, etc.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Capture duration of scheduler with Symfony's Stopwatch component.
        $stopwatchScheduler = new Stopwatch();

        // Start capturing duration of scheduler.
        $stopwatchScheduler->start('scheduler');

        $this->logger = $this->getContainer()->get('logger');
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // Test whether we have a database connection.
        $this->testDatabase();

        $output->writeln('<comment>Running scheduler with:</comment>');
        $this->output = $output;

        $this->scheduler = $this->startScheduler($this->interval);

        // Test whether we have Internet access.
        //$this->testInternet();

        try {
            $output->writeln('Scheduler ID: '.$this->scheduler->getId());
            $output->writeln('Interval: '.$this->scheduler->getPeriodInterval().' minute(s)');
            $output->writeln('Period starts: '.$this->scheduler->getPeriodStart()->format('Y-m-d h:i:s'));
            $output->writeln('Period ends: '.$this->scheduler->getPeriodEnd()->format('Y-m-d h:i:s'));

            foreach($this->actionsOrder as $actionType){
                // Find all Operations to be processed.
                $actions = $this->getActions($actionType, $this->scheduler->getPeriodStart(), $this->scheduler->getPeriodEnd());

                // Store all the operation-related info in the Job entity.
                if($actions){
                    $table = new Table($this->output);
                    $tableHeaders = array('ID', 'Start Date', 'End Date', 'Name', 'Status');
                    if($actionType != Action::TYPE_CAMPAIGN){
                        $tableHeaders[] = 'Campaign ID';
                        $tableHeaders[] = 'Campaign Status';
                    }
                    if($actionType == Action::TYPE_OPERATION){
                        $tableHeaders[] = 'Activity ID';
                        $tableHeaders[] = 'Activity Status';
                    }
                    $table->setHeaders($tableHeaders);

                    $outputTableRows = array();

                    foreach($actions as $action){
                        // Check whether this operation is executable per its trigger hook.
                        if($this->isExecutable($action)){
                            // Queue new Job.
                            $this->queueJob(
                                $action->getType(),
                                $action->getId(),
                                $action->getModule()->getServices()['job']
                            );

                            // Highlight the date that is within the execution period.
                            $startDate = $action->getStartDate()->format('Y-m-d h:i:s');
                            $endDate = null;

                            if($action->getStartDate() >= $this->scheduler->getPeriodStart()){
                                $startDate = '<options=bold>'.$startDate.'</options=bold>';
                            } elseif($action->getEndDate()) {
                                $endDate = $action->getEndDate()->format('Y-m-d h:i:s');
                                $endDate = '<options=bold>'.$endDate.'</options=bold>';
                            }

                            $tableRows = array(
                                $action->getId(), $startDate, $endDate, $action->getName(), $action->getStatus()
                            );
                            if($actionType == Action::TYPE_ACTIVITY || $actionType == Action::TYPE_MILESTONE){
                                $tableRows[] = $action->getCampaign()->getId();
                                $tableRows[] = $action->getCampaign()->getStatus();
                            }
                            if($actionType == Action::TYPE_OPERATION){
                                $tableRows[] = $action->getActivity()->getCampaign()->getId();
                                $tableRows[] = $action->getActivity()->getCampaign()->getStatus();
                                $tableRows[] = $action->getActivity()->getId();
                                $tableRows[] = $action->getActivity()->getStatus();
                            }

                            $outputTableRows[] = $tableRows;
                        }
                    }

                    if(count($outputTableRows)){
                        // Create the table rows for output.
                        $output->writeln('');
                        $output->writeln('<info>These actions of type "'.$actionType.'"  will be executed:</info>');
                        $table->setRows($outputTableRows);
                        $table->render();
                    } else {
                        $output->writeln('<error>No actions of type "'.$actionType.'" scheduled within the past '.$this->interval.' minutes.</error>');
                    }
                } else {
                    $output->writeln('<error>No actions of type "'.$actionType.'" scheduled within the past '.$this->interval.' minutes.</error>');
                }
            }

            // Queue the scheduled reports.
            $output->writeln('Processing scheduled reports.');

            $qb = $this->em->createQueryBuilder();
            $qb->select('sr')
                ->from('CampaignChain\CoreBundle\Entity\SchedulerReport', 'sr')
                ->where('sr.endDate > :now')
//                ->andWhere('sr.nextRun >= :periodStart AND sr.nextRun <= :periodEnd')
                ->setParameter('now', new \DateTime('now', new \DateTimeZone('UTC')));
//                ->setParameter('periodEnd', $this->scheduler->getPeriodEnd()->format('Y-m-d h:i:s'))
//                ->setParameter('periodStart', $this->scheduler->getPeriodStart()->format('Y-m-d h:i:s'));
            $query = $qb->getQuery();
            $scheduledReports = $query->getResult();
            $output->writeln(count($scheduledReports));
            foreach($scheduledReports as $scheduledReport){
                if($scheduledReport instanceof \CampaignChain\CoreBundle\Entity\SchedulerReportOperation){
                    $module = $scheduledReport->getOperation()->getOperationModule();
                    $type = Action::TYPE_OPERATION;
                    $id = $scheduledReport->getOperation()->getId();
                    $name = $scheduledReport->getOperation()->getName();
                }/* elseif($scheduledReport instanceof \CampaignChain\CoreBundle\Entity\SchedulerReportActivity){
                    $module = $scheduledReport->getActivity()->getActivityModule();
                    $type = Action::TYPE_ACTIVITY;
                    $id = $scheduledReport->getActivity()->getId();
                } elseif($scheduledReport instanceof \CampaignChain\CoreBundle\Entity\SchedulerReportMilestone){
                    $module = $scheduledReport->getMilestone()->getMilestoneModule();
                    $type = Action::TYPE_MILESTONE;
                    $id = $scheduledReport->getMilestone()->getId();
                }*/

                $output->writeln('Adding Job for collecting report data for '.Action::TYPE_OPERATION.' '.$id.' "'.$name.'".');

                $this->queueJob(
                    $type,
                    $id,
                    $module->getServices()['report'],
                    'report'
                );

                // Update next run.
                if($scheduledReport->getInterval() != null){
                    $interval = \DateInterval::createfromdatestring($scheduledReport->getInterval());
                    $nextRun = clone $scheduledReport->getNextRun();
                    $scheduledReport->setNextRun($nextRun->add($interval));
                }
            }

            // Get the Jobs to be processed.
            $qb = $this->em->createQueryBuilder();
            $qb->select('j')
                ->from('CampaignChain\CoreBundle\Entity\Job', 'j')
                ->where('j.scheduler = :scheduler')
                ->andWhere('j.status = :status')
                ->setParameter('scheduler', $this->scheduler)
                ->setParameter('status', Job::STATUS_OPEN)
                ->orderBy('j.id', 'DESC');
            $query = $qb->getQuery();
            $jobs = $query->getResult();

            // Executing the open operations.
            if(count($jobs)){
                $output->writeln('<info>Executing jobs now:</info>');

                // create a new progress bar
                $progress = new ProgressBar($output, count($jobs));
                // start and displays the progress bar
                $progress->start();

                foreach($jobs as $job){
                    // Execute job.
                    $this->executeJob($job);

                    // advance the progress bar 1 unit
                    $progress->advance();
                }

                // ensure that the progress bar is at 100%
                $progress->finish();
                $output->writeln('');
            }

            // Get the processed jobs.
            $qb = $this->em->createQueryBuilder();
            $qb->select('j')
                ->from('CampaignChain\CoreBundle\Entity\Job', 'j')
                ->where('j.scheduler = :scheduler')
                ->andWhere('j.status != :status')
                ->setParameter('scheduler', $this->scheduler)
                ->setParameter('status', Job::STATUS_OPEN)
                ->orderBy('j.id', 'DESC');
            $query = $qb->getQuery();
            $jobs = $query->getResult();

            // Display the results of the execution.
            $output->writeln('<info>Results of executed actions:</info>');
            $table = new Table($this->output);
            $table
                ->setHeaders(array(
                    'Job ID', 'Operation ID', 'Process ID', 'Job Name', 'Job Start Date', 'Job End Date', 'Duration', 'Status', 'Message'
                ));

            $outputTableRows = array();
            foreach($jobs as $job){
                $startDate = null;
                $endDate = null;

                if($job->getStartDate()) {
                    $startDate = $job->getStartDate()->format('Y-m-d h:i:s');
                }
                if($job->getEndDate()) {
                    $endDate = $job->getEndDate()->format('Y-m-d h:i:s');
                }

                $outputTableRows[] = array(
                    $job->getId(), $job->getActionId(), $job->getPid(), $job->getName(), $startDate, $endDate, $job->getDuration().' ms', $job->getStatus(), $job->getMessage(),
                );
            }

            // Create the table rows for output.
            $table->setRows($outputTableRows);
            $table->render();


            // Scheduler is done, let's see how long it took.
            $stopwatchSchedulerEvent = $stopwatchScheduler->stop('scheduler');

            $this->scheduler->setDuration($stopwatchSchedulerEvent->getDuration());
            $this->scheduler->setExecutionEnd(new \DateTime('now', new \DateTimeZone('UTC')));
            $this->scheduler->setStatus(Scheduler::STATUS_CLOSED);
            $this->em->persist($this->scheduler);
            $this->em->flush();

            $output->writeln('Duration of scheduler: '.$stopwatchSchedulerEvent->getDuration().' milliseconds');
        } catch(\Exception $e) {
            $this->scheduler->setMessage($e->getMessage());
            $this->scheduler->setStatus(Scheduler::STATUS_ERROR);
            $this->em->flush();

            $output->writeln('<error>'.$this->scheduler->getMessage().'</error>');
            // TODO: Send automatic notification.
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
        $connected = @fsockopen("www.example.com", 80);
        if ($connected){
            fclose($connected);
            return true;
        } else {
            $errMsg = 'No connection to Internet.';
            $this->scheduler->setStatus(Scheduler::STATUS_ERROR);
            $this->scheduler->setMessage($errMsg);
            $this->em->flush();
            $this->logger->error($errMsg);
            throw new \Exception($errMsg);
            exit;
        }
    }

    protected function startScheduler($interval){
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $periodStart = new \DateTime('now', new \DateTimeZone('UTC'));
        $periodStart->modify("-".$interval." minutes");

        $scheduler = new Scheduler();
        $scheduler->setStatus(Scheduler::STATUS_RUNNING);
        $scheduler->setExecutionStart($now);
        $scheduler->setPeriodStart($periodStart);
        $scheduler->setPeriodEnd($now);
        $scheduler->setPeriodInterval($interval);

        $this->em->persist($scheduler);
        $this->em->flush();

        return $scheduler;
    }

    protected function getActions($actionType, \DateTime $periodStart, \DateTime $periodEnd){
        $qb = $this->em->createQueryBuilder();

        switch($actionType){
            case Action::TYPE_OPERATION:
                $qb->select('o')
                    ->from('CampaignChain\CoreBundle\Entity\Operation', 'o')
                    ->from('CampaignChain\CoreBundle\Entity\Activity', 'a')
                    ->from('CampaignChain\CoreBundle\Entity\Campaign', 'c')
                    // We only want operations with status "open":
                    ->where('o.status = :status')
                    // We don't want operations to already be processed by another scheduler, that's why we check all Job entities:
                    ->andWhere(
                        'NOT EXISTS (SELECT j.id FROM CampaignChain\CoreBundle\Entity\Job j WHERE o.id = j.actionId AND j.actionType = :actionType)'
                    )
                    // The parent activity of the operation should also have the status "open":
                    ->andWhere('o.activity = a')
                    ->andWhere('a.status = :status')
                    // The campaign within which the operation and parent activity reside must also have the status "open":
                    ->andWhere('a.campaign = c')
                    ->andWhere('c.status = :status')
                    // Get all operations where the start date is within the execution period
                    // or get all operations where the start date is outside the period, but the end date - if not NULL - is within the period.
                    ->andWhere('(o.startDate >= :periodStart AND o.startDate <= :periodEnd) OR (o.endDate IS NOT NULL AND o.startDate <= :periodStart AND o.endDate >= :periodStart AND o.endDate <= :periodEnd)')
                    ->setParameter('status', 'open')
                    ->setParameter('actionType', $actionType)
                    ->setParameter('periodEnd', $periodEnd->format('Y-m-d h:i:s'))
                    ->setParameter('periodStart', $periodStart->format('Y-m-d h:i:s'));
                break;
            case Action::TYPE_ACTIVITY:
                $qb->select('a')
                    ->from('CampaignChain\CoreBundle\Entity\Activity', 'a')
                    ->from('CampaignChain\CoreBundle\Entity\Campaign', 'c')
                    // We only want activities with status "open":
                    ->where('a.status = :status')
                    // We don't want activities to already be processed by another scheduler, that's why we check all Job entities:
                    ->andWhere(
                        'NOT EXISTS (SELECT j.id FROM CampaignChain\CoreBundle\Entity\Job j WHERE a.id = j.actionId AND j.actionType = :actionType)'
                    )
                    // The campaign within which the activity resides must also have the status "open":
                    ->andWhere('a.campaign = c')
                    ->andWhere('c.status = :status')
                    // Get all activities where the start date is within the execution period
                    // or get all activities where the start date is outside the period, but the end date - if not NULL - is within the period.
                    ->andWhere('(a.startDate >= :periodStart AND a.startDate <= :periodEnd) OR (a.endDate IS NOT NULL AND a.startDate <= :periodStart AND a.endDate >= :periodStart AND a.endDate <= :periodEnd)')
                    ->setParameter('status', 'open')
                    ->setParameter('actionType', $actionType)
                    ->setParameter('periodEnd', $periodEnd->format('Y-m-d h:i:s'))
                    ->setParameter('periodStart', $periodStart->format('Y-m-d h:i:s'));
                break;
            case Action::TYPE_MILESTONE:
                $qb->select('m')
                    ->from('CampaignChain\CoreBundle\Entity\Milestone', 'm')
                    ->from('CampaignChain\CoreBundle\Entity\Campaign', 'c')
                    // We only want milestones with status "open":
                    ->where('m.status = :status')
                    // We don't want milestones to already be processed by another scheduler, that's why we check all Job entities:
                    ->andWhere(
                        'NOT EXISTS (SELECT j.id FROM CampaignChain\CoreBundle\Entity\Job j WHERE m.id = j.actionId AND j.actionType = :actionType)'
                    )
                    // The campaign within which the milestone resides must also have the status "open":
                    ->andWhere('m.campaign = c')
                    ->andWhere('c.status = :status')
                    // Get all milestones where the start date is within the execution period
                    // or get all milestones where the start date is outside the period, but the end date - if not NULL - is within the period.
                    ->andWhere('(m.startDate >= :periodStart AND m.startDate <= :periodEnd) OR (m.endDate IS NOT NULL AND m.startDate <= :periodStart AND m.endDate >= :periodStart AND m.endDate <= :periodEnd)')
                    ->setParameter('status', 'open')
                    ->setParameter('actionType', $actionType)
                    ->setParameter('periodEnd', $periodEnd->format('Y-m-d h:i:s'))
                    ->setParameter('periodStart', $periodStart->format('Y-m-d h:i:s'));
                break;
            case Action::TYPE_CAMPAIGN:
                $qb->select('c')
                    ->from('CampaignChain\CoreBundle\Entity\Campaign', 'c')
                    // We only want campaigns with status "open":
                    ->where('c.status = :status')
                    // We don't want campaigns to already be processed by another scheduler, that's why we check all Job entities:
                    ->andWhere(
                        'NOT EXISTS (SELECT j.id FROM CampaignChain\CoreBundle\Entity\Job j WHERE c.id = j.actionId AND j.actionType = :actionType)'
                    )
                    // Get all campaigns where the start date is within the execution period
                    // or get all campaigns where the start date is outside the period, but the end date - if not NULL - is within the period.
                    ->andWhere('(c.startDate >= :periodStart AND c.startDate <= :periodEnd) OR (c.endDate IS NOT NULL AND c.startDate <= :periodStart AND c.endDate >= :periodStart AND c.endDate <= :periodEnd)')
                    ->setParameter('status', 'open')
                    ->setParameter('actionType', $actionType)
                    ->setParameter('periodEnd', $periodEnd->format('Y-m-d h:i:s'))
                    ->setParameter('periodStart', $periodStart->format('Y-m-d h:i:s'));
                break;
        }

        $query = $qb->getQuery();
        return $query->getResult();
    }

    protected function queueJob($actionType, $actionId, $service, $jobType = null){
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

    protected function executeJob(Job $job){
        $job->setStartDate(new \DateTime('now', new \DateTimeZone('UTC')));

        $command = 'php app/console campaignchain:job '.$job->getId();
        $process = new Process($command);
        $this->logger->info('Executing Job with command: '.$command);
//        $process->setTimeout($this->timeout);
//        $this->logger->info('Timeout: '.$this->timeout.' seconds');
        $process->start();

//        if (!$process->isSuccessful()) {
//            $this->logger->error($process->getErrorOutput());
//            throw new \RuntimeException($process->getErrorOutput());
//        }

        while ($process->isRunning()) {
            $this->logger->info('Process ID: '.$process->getPid());
            $job->setPid($process->getPid());
            $this->em->flush();
        }
    }

    protected function isExecutable($action){
        $hookServiceName = $action->getTriggerHook()->getServices()['entity'];
        $hookService = $this->getContainer()->get($hookServiceName);
        return $hookService->isExecutable($action);
    }
}