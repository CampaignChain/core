<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EntityService;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\Campaign;

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

    public function moveCampaign(Campaign $campaign, $interval){
        $hookService = $this->container->get($campaign->getTriggerHook()->getServices()['entity']);
        $hook = $hookService->getHook($campaign);
        if($hook->getStartDate() !== null){
            $hook->setStartDate(new \DateTime($hook->getStartDate()->add($interval)->format(\DateTime::ISO8601)));
        }
        if($hook->getEndDate() !== null){
            $hook->setEndDate(new \DateTime($hook->getEndDate()->add($interval)->format(\DateTime::ISO8601)));
        }
        return $hookService->processHook($campaign, $hook);
    }

    public function cloneCampaign(Campaign $campaign){
        try {
            $this->em->getConnection()->beginTransaction();

            $clonedCampaign = clone $campaign;

            // Clone all related milestones.
            $milestones = $campaign->getMilestones();
            if($milestones->count()){
                $milestoneService = $this->container->get('campaignchain.core.milestone');
                foreach($milestones as $milestone){
                    $milestone = $milestoneService->cloneMilestone($clonedCampaign, $milestone);
                    $clonedCampaign->addMilestone($milestone);
                }
            }

            // Clone all related activities.
            $activities = $campaign->getActivities();
            if($activities->count()){
                $activityService = $this->container->get('campaignchain.core.activity');
                foreach($activities as $activity){
                    $activity = $activityService->cloneActivity($clonedCampaign, $activity);
                    $clonedCampaign->addActivity($activity);
                }
            }

            $this->em->persist($clonedCampaign);
            $this->em->flush();

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }

        return $clonedCampaign;
    }
}