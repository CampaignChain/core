<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EntityService;

use CampaignChain\CoreBundle\Entity\Hook;
use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\CoreBundle\Entity\Operation;
use CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityFact;
use CampaignChain\CoreBundle\Twig\CampaignChainCoreExtension;
use Doctrine\Common\Collections\ArrayCollection;
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

    public function getAllActiveActivities($options = array()){
        $qb = $this->em->createQueryBuilder();
        $qb->select('a', 'l')
            ->from('CampaignChain\CoreBundle\Entity\Activity', 'a')
            ->join('a.location','l')
            ->where('a.parent IS NULL')
            ->andWhere('l.status = ?1')
            ->orderBy('a.startDate')
            ->setParameters(array(1 => Location::STATUS_ACTIVE));
        if(isset($options['limit'])){
            $qb->setMaxResults($options['limit']);
        }
        $query = $qb->getQuery();
        return $query->getResult();
    }
    public function getAllActivities($options = array()){
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('CampaignChain\CoreBundle\Entity\Activity', 'a')
            ->where('a.parent IS NULL')
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
            ->join('a.location','l')
            ->where('a.startDate > :now')
            ->andWhere('a.status != :paused')
            ->andWhere('a.parent IS NULL')
            ->andWhere('l.status = :status')
            ->orderBy('a.startDate', 'ASC')
            ->setParameter('now', new \DateTime('now'))
            ->setParameter('status', Location::STATUS_ACTIVE)
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

    /**
     * @param Activity $activity
     * @return bool
     */
    public function isRemovable(Activity $activity)
    {
        //Deletion should only be possible if the activity is not closed
        if ($activity->getStatus() == "closed") {
            return false;
        }

        /** @var Activity $activity */
        $activity = $this->em
            ->getRepository('CampaignChainCoreBundle:Activity')
            ->createQueryBuilder('a')
            ->select('a, f, o, sr, cta')
            ->leftJoin('a.fact', 'f')
            ->leftJoin('a.operations', 'o')
            ->leftJoin('o.scheduledReports', 'sr')
            ->leftJoin('o.outboundCTAs', 'cta')
            ->where('a.id = :id')
            ->setParameter('id', $activity)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$activity->getFact()->isEmpty()) {
            return false;
        }

        /** @var Operation[] $operations */
        $operations = new ArrayCollection();
        foreach ($activity->getOperations() as $op) {
            $operations->add($op);
        }
        //Check if there are scheduled reports or cta data for the operation
        foreach ($operations as $op) {
            if (!$op->getScheduledReports()->isEmpty() or !$op->getOutboundCTAs()->isEmpty()) {
                return false;
            }
        }

        $schedulerReportsActivities = $this->em
                ->getRepository('CampaignChainCoreBundle:SchedulerReportActivity')
                ->findBy(array('endActivity' => $activity));
        $ctaActivities =$this->em
                ->getRepository('CampaignChainCoreBundle:ReportCTA')
                ->findBy(array('activity' => $activity));

        if (!empty($schedulerReportsActivities) or !empty($ctaActivities)) {
            return false;
        }

        return true;
    }

    /**
     * This method deletes the activity and operations together with the belonging location.
     * Each activity has at least one general operation.
     * Each activity has one location.
     * Each activity has a module specific operation i.e. "operation_twitter_status".
     *
     * @param $id
     * @throws \Exception
     */
    public function removeActivity($id){
        $activity = $this->em
            ->getRepository('CampaignChainCoreBundle:Activity')
            ->find($id);

        if (!$activity) {
            throw new \Exception(
                'No activity found for id '.$id
            );
        }

        if ( !$this->isRemovable($activity)) {
            throw new \LogicException(
                'Deletion of activities is not possible when status is set to closed or there are scheduled reports'
            );
        }

        //Put all belonging operations in an ArrayCollection
        $operations = new ArrayCollection();
        foreach ($activity->getOperations() as $op) {
            $operations->add($op);
        }
        //Set Activity Id of the operations to null and remove the belonging locations
        foreach ($operations as $op) {
            if ($activity->getOperations()->contains($op)) {
                $op->setActivity(null);
                foreach ($op->getLocations() as $opLocation) {
                    $this->em->remove($opLocation);
                    $this->em->flush();
                }
                //Delete the module specific operation i.e. "operation_twitter_status"
                $operationServices = $op->getOperationModule()->getServices();
                if (isset($operationServices['operation'])) {
                    $opService = $this->container->get($operationServices['operation']);
                    $opService->removeOperation($op->getId());
                }
                //Delete the operation from the operation table
                $this->em->remove($op);
            }
        }
        $this->em->flush();
        //Delete the activity
        $this->em->remove($activity);
        $this->em->flush();
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

    public function moveActivity(Activity $activity, $interval){
        $hookService = $this->container->get($activity->getTriggerHook()->getServices()['entity']);
        $hook = $hookService->getHook($activity, Hook::MODE_MOVE);
        if($hook->getStartDate() !== null){
            $hook->setStartDate(new \DateTime($hook->getStartDate()->add($interval)->format(\DateTime::ISO8601)));
        }
        if($hook->getEndDate() !== null){
            $hook->setEndDate(new \DateTime($hook->getEndDate()->add($interval)->format(\DateTime::ISO8601)));
        }

        $activity = $hookService->processHook($activity, $hook);

        $this->em->persist($activity);

        // Move all related Operations.
        $operations = $activity->getOperations();
        if($operations->count()){
            $operationService = $this->container->get('campaignchain.core.operation');
            foreach($operations as $operation){
                $operation = $operationService->moveOperation($operation, $interval);
                //$activity->addOperation($operation);
            }
        }

        $this->em->flush();

        return $activity;
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
    }

    public function cloneActivity(Campaign $campaign, Activity $activity, $status = null)
    {
        $clonedActivity = clone $activity;
        $clonedActivity->setCampaign($campaign);
        $campaign->addActivity($clonedActivity);

        if($status != null){
            $clonedActivity->setStatus($status);
        }

        $this->em->persist($clonedActivity);

        // Clone all related Operations.
        $operations = $activity->getOperations();
        if($operations->count()){
            $operationService = $this->container->get('campaignchain.core.operation');
            foreach($operations as $operation){
                $operationService->cloneOperation($activity, $operation);
            }
        }

        $this->em->flush();

        return $clonedActivity;
    }
}