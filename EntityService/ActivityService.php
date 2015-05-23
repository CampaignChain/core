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

use CampaignChain\CoreBundle\Twig\CampaignChainCoreExtension;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\Activity;
use CampaignChain\CoreBundle\Entity\Campaign;

class ActivityService
{
    protected $em;
    protected $container;


    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function getAllActivities($options = array()){
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('CampaignChain\CoreBundle\Entity\Activity', 'a')
            ->orderBy('a.startDate');
        if(isset($options['limit'])){
            $qb->setMaxResults($options['limit']);
        }
        $query = $qb->getQuery();
        return $query->getResult();
    }

    public function getUpcomingActivities($options = array()){
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('CampaignChain\CoreBundle\Entity\Activity', 'a')
            ->where('a.startDate > :now')
            ->andWhere('a.status != :paused')
            ->orderBy('a.startDate', 'ASC')
            ->setParameter('now', new \DateTime('now'))
            ->setParameter('paused', Action::STATUS_PAUSED);
        if(isset($options['limit'])){
            $qb->setMaxResults($options['limit']);
        }
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

        // TODO: Move all related operations.

        return $hookService->processHook($activity, $hook);
    }

    /**
     * Compose the channel icon path
     *
     * @param $channel
     * @return mixed
     */
    public function getIcons($activity)
    {
        $twigExt = new CampaignChainCoreExtension($this->em, $this->container);

        $icon['location_icon'] = $twigExt->mediumIcon($activity->getLocation());
        $icon['activity_icon'] = '/'.$twigExt->mediumContext($activity->getLocation());

        return $icon;
    }

    public function tplTeaser($activity, $options = array())
    {
        $twigExt = new CampaignChainCoreExtension($this->em, $this->container);

        return $twigExt->tplTeaser($activity, $options);

        return $icon;
    }

    public function cloneActivity(Campaign $campaign, Activity $activity)
    {
        $clonedActivity = clone $activity;
        $clonedActivity->setCampaign($campaign);
        $this->em->persist($clonedActivity);

        // Clone all related Operations.
        $operations = $activity->getOperations();
        if($operations->count()){
            $operationService = $this->container->get('campaignchain.core.operation');
            foreach($operations as $operation){
                $operation = $operationService->cloneOperation($activity, $operation);
                $clonedActivity->addOperation($operation);
            }
        }

        $this->em->flush();

        return $clonedActivity;
    }
}