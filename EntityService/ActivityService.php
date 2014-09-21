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
use Symfony\Component\DependencyInjection\ContainerInterface;

class ActivityService
{
    protected $em;
    protected $container;


    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function getAllActivities(){
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('CampaignChain\CoreBundle\Entity\Activity', 'a')
            ->orderBy('a.startDate');
        $query = $qb->getQuery();
        return $query->getResult();
    }

    public function getActivity($id){
        $activity = $this->em
            ->getRepository('CampaignChainCoreBundle:Activity')
            ->find($id);

        if (!$activity) {
            throw new \Exception(
                'No activity found for id '.$id
            );
        }

        return $activity;
    }

    public function getActivityModule($id){
        // Get the activity module's activity.
        $activityModule = $this->em
            ->getRepository('CampaignChainCoreBundle:ActivityModule')
            ->find($id);

        if (!$activityModule) {
            throw new \Exception(
                'No activity module found for id '.$id
            );
        }

        return $activityModule;
    }

    public function getActivityModuleByActivity($id){
        $activity = $this->getActivity($id);

        return $activity->getActivityModule();
    }

    public function getOperation($id){
        // TODO: Exception if equalsOperation == false.
        $activity = $this->getActivity($id);
        $operations = $activity->getOperations();
        return $operations[0];
    }

    public function moveActivity($activity, $interval){
        $hookService = $this->container->get($activity->getTriggerHook()->getServices()['entity']);
        $hook = $hookService->getHook($activity);
        if($hook->getStartDate() !== null){
            $hook->setStartDate(new \DateTime($hook->getStartDate()->add($interval)->format(\DateTime::ISO8601)));
        }
        if($hook->getEndDate() !== null){
            $hook->setEndDate(new \DateTime($hook->getEndDate()->add($interval)->format(\DateTime::ISO8601)));
        }
        return $hookService->processHook($activity, $hook);
    }
}