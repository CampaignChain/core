<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EntityService;

use CampaignChain\CoreBundle\Entity\Hook;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\Campaign;
use CampaignChain\CoreBundle\Twig\CampaignChainCoreExtension;

class CampaignService
{
    protected $em;
    protected $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function getAllCampaigns(){
        $qb = $this->em->createQueryBuilder();
        $qb->select('c')
            ->from('CampaignChain\CoreBundle\Entity\Campaign', 'c')
            ->orderBy('c.startDate');
        $query = $qb->getQuery();
        return $query->getResult();
    }

    public function getOngoingCampaigns($options = array()){
        $qb = $this->em->createQueryBuilder();
        $qb->select('c')
            ->from('CampaignChain\CoreBundle\Entity\Campaign', 'c')
            ->where('c.startDate < :now')
            ->andWhere('c.endDate > :now')
            ->andWhere('c.status != :paused')
            ->orderBy('c.endDate', 'ASC')
            ->setParameter('now', new \DateTime('now'))
            ->setParameter('paused', Action::STATUS_PAUSED);
        if(isset($options['limit'])){
            $qb->setMaxResults($options['limit']);
        }
        $query = $qb->getQuery();
        return $query->getResult();
    }

    public function getCampaign($id){
        $campaign = $this->em
            ->getRepository('CampaignChainCoreBundle:Campaign')
            ->find($id);

        if (!$campaign) {
            throw new \Exception(
                'No campaign found for id '.$id
            );
        }

        return $campaign;
    }

    public function getCampaignModule($id){
        $campaignModule = $this->em
            ->getRepository('CampaignChainCoreBundle:CampaignModule')
            ->find($id);

        if (!$campaignModule) {
            throw new \Exception(
                'No campaign module found for id '.$id
            );
        }

        return $campaignModule;
    }

    public function getCampaignModuleByCampaign($id){
        $campaign = $this->getCampaign($id);

        return $campaign->getCampaignModule();
    }

    public function getCampaignsDatesJson(){
        $qb = $this->em->createQueryBuilder();
        $qb->select('c.id, c.startDate, c.endDate')
            ->from('CampaignChain\CoreBundle\Entity\Campaign', 'c');
        $query = $qb->getQuery();
        $campaigns = $query->getResult();

        foreach($campaigns as $campaign){
            $campaignsDates[$campaign['id']] = array(
                'startDate' => $campaign['startDate']->format('Y-m-d H:i'),
                'endDate' => $campaign['endDate']->format('Y-m-d H:i'),
            );
        }

        $encoders = array(new JsonEncoder());
        $normalizers = array(new GetSetMethodNormalizer());
        $serializer = new Serializer($normalizers, $encoders);

        return $serializer->serialize($campaignsDates, 'json');
    }

    public function moveCampaign(Campaign $campaign, $newStartDate, $status = null){
        // Make sure that data stays intact by using transactions.
        try {
            $this->em->getConnection()->beginTransaction();

            // Calculate time difference.
            $interval = $campaign->getStartDate()->diff($newStartDate);

            $hookService = $this->container->get($campaign->getTriggerHook()->getServices()['entity']);
            $hook = $hookService->getHook($campaign, Hook::MODE_MOVE);
            if($hook->getStartDate() !== null){
                $hook->setStartDate(new \DateTime($hook->getStartDate()->add($interval)->format(\DateTime::ISO8601)));
            }
            if($hook->getEndDate() !== null){
                $hook->setEndDate(new \DateTime($hook->getEndDate()->add($interval)->format(\DateTime::ISO8601)));
            }

            $campaign = $hookService->processHook($campaign, $hook);

            if($status != null){
                $campaign->setStatus($status);
            }

            // Change due date of all related milestones.
            $milestones = $campaign->getMilestones();
            if($milestones->count()){
                $milestoneService = $this->container->get('campaignchain.core.milestone');
                foreach($milestones as $milestone){
                    if($status != null){
                        $milestone->setStatus($status);
                    }
                    $milestone = $milestoneService->moveMilestone($milestone, $interval);
                    $campaign->addMilestone($milestone);
                }
            }

            // Change due date of all related activities.
            $activities = $campaign->getActivities();
            if($activities->count()){
                $activityService = $this->container->get('campaignchain.core.activity');
                foreach($activities as $activity){
                    if($status != null){
                        $activity->setStatus($status);
                    }
                    $activity = $activityService->moveActivity($activity, $interval);
                    $campaign->addActivity($activity);
                }
            }

            $this->em->flush();

            $this->em->getConnection()->commit();

            return $campaign;
        } catch (\Exception $e) {
            // TODO: Respond with JSON and HTTP error code.
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    public function cloneCampaign(Campaign $campaign, $status = null){
        try {
            $this->em->getConnection()->beginTransaction();

            $clonedCampaign = clone $campaign;

            $this->em->persist($clonedCampaign);
            $this->em->flush();

            $activities = $clonedCampaign->getActivities();
            foreach($activities as $activity){
                $clonedActivity = clone $activity;
                $clonedActivity->setCampaign($clonedCampaign);
                $clonedCampaign->addActivity($clonedActivity);
                $clonedCampaign->removeActivity($activity);
                $this->em->persist($clonedActivity);

                $operations = $clonedActivity->getOperations();
                foreach($operations as $operation){
                    $clonedOperation = clone $operation;
                    $clonedOperation->setActivity($clonedActivity);
                    $clonedActivity->addOperation($clonedOperation);
                    $clonedActivity->removeOperation($operation);

                    // Execute clone method of module.
                    $moduleServices = $clonedOperation->getOperationModule()->getServices();
                    if(
                        $moduleServices != null &&
                        is_array($moduleServices) &&
                        isset($moduleServices['operation'])
                    ){
                        $moduleOperationService = $this->container->get($moduleServices['operation']);
                        $moduleOperationService->cloneOperation($operation, $clonedOperation);
                    }

                    $this->em->persist($clonedOperation);
                }
            }

            $milestones = $clonedCampaign->getMilestones();
            foreach($milestones as $milestone){
                $clonedMilestone = clone $milestone;
                $clonedMilestone->setCampaign($clonedCampaign);
                $clonedCampaign->addMilestone($clonedMilestone);
                $clonedCampaign->removeMilestone($milestone);
                $this->em->persist($clonedMilestone);
            }

            $this->em->commit();

            return $clonedCampaign;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    public function getCampaignURI(Campaign $campaign)
    {
        $bundleName = $campaign->getCampaignModule()->getBundle()->getName();
        $moduleIdentifier = $campaign->getCampaignModule()->getIdentifier();
        return $bundleName.'/'.$moduleIdentifier;
    }

    public function tplTeaser($campaign, $options = array())
    {
        $twigExt = new CampaignChainCoreExtension($this->em, $this->container);

        return $twigExt->tplTeaser($campaign, $options);
    }
}