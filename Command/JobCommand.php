<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Command;

use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\Scheduler;
use CampaignChain\CoreBundle\Entity\Job;
use CampaignChain\CoreBundle\Job\JobActionInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Class JobCommand
 * @package CampaignChain\CoreBundle\Command
 * @author Sandro Groganz <sandro@campaignchain.com>
 *
 * Usage:
 * php app/console campaignchain:job <jobId>
 *
 * Example:
 * php app/console campaignchain:job 42
 */
class JobCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('campaignchain:job')
            ->setDescription('Executes jobs queued by the scheduler.')
            ->addArgument(
                'jobId',
                InputArgument::REQUIRED,
                'The ID of a scheduled job'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Capture duration of job with Symfony's Stopwatch component.
        $stopwatch = new Stopwatch();
        // Start capturing duration of job execution.
        $stopwatch->start('job');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $jobId = $input->getArgument('jobId');

        $job = $em->getRepository('CampaignChainCoreBundle:Job')->find($jobId);

        if (!$job) {
            // TODO: Log this message, because it won't surface anywhere else.
            throw new \Exception('No job found with ID: '.$jobId);
        }

        try{
            // TODO: Set status for operation entity (and activity if equal) as well.

            // If a Job service is provided, then execute it.
            if($job->getName()){
                $jobService = $this->getContainer()->get($job->getName());

                // A Job service must implement the respective interface.
                if($jobService instanceof JobActionInterface){
                    try{
                        $status = $jobService->execute($job->getActionId());
                        switch($status){
                            case JobActionInterface::STATUS_OK:
                                $job->setStatus(JOB::STATUS_CLOSED);
                                break;
                            case JobActionInterface::STATUS_ERROR:
                                $job->setStatus(JOB::STATUS_ERROR);
                                break;
                        }
                        $job->setMessage($jobService->getMessage());
                    } catch(\Exception $e) {
                        $job->setMessage($e->getMessage());
                        $job->setStatus(JOB::STATUS_ERROR);
                        // TODO: Notify owner of operation of error.
                    }
                } else {
                    $errorMsg = 'The job service "'.$job->getName().'" with the class "'.get_class($jobService).'" does not implement the interface CampaignChain\CoreBundle\Job\JobActionInterface as required.';

                    $job->setStatus(JOB::STATUS_ERROR);
                    $job->setMessage($errorMsg);
                    $em->flush();

                    throw new \Exception($errorMsg);
                }
            } else {
                // Actions have a job type of null value.
                if($job->getJobType() == null){
                    // No Job service, so let's just close the action's entity and the job.
                    $action = $em->getRepository(Action::getRepositoryName($job->getActionType()))->find($job->getActionId());
                    $action->setStatus(Action::STATUS_CLOSED);
                    $job->setStatus(JOB::STATUS_CLOSED);
                }
            }
            $job->setEndDate(new \DateTime('now', new \DateTimeZone('UTC')));

            // Job is done, let's see how long it took.
            $stopwatchEvent = $stopwatch->stop('job');
            $job->setDuration($stopwatchEvent->getDuration());

            $em->persist($job);
            $em->flush();
        } catch(\Exception $e) {
            $job->setMessage($e->getMessage());
            $job->setStatus(Job::STATUS_ERROR);
            $em->flush();
            // TODO: Send automatic notification.
        }
    }
}