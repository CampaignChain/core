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

namespace CampaignChain\CoreBundle\EntityService;

use CampaignChain\CoreBundle\Entity\Hook;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\Milestone;
use CampaignChain\CoreBundle\Entity\Campaign;

class MilestoneService
{
    protected $em;
    protected $container;

    public function __construct(ManagerRegistry $managerRegistry, ContainerInterface $container)
    {
        $this->em = $managerRegistry->getManager();
        $this->container = $container;
    }

    public function getAllMilestones(){
        $qb = $this->em->createQueryBuilder();
        $qb->select('m')
            ->from('CampaignChain\CoreBundle\Entity\Milestone', 'm')
            ->orderBy('m.startDate');
        $query = $qb->getQuery();
        return $query->getResult();
    }

    public function getUpcomingMilestones($options = array()){
        $qb = $this->em->createQueryBuilder();
        $qb->select('m')
            ->from('CampaignChain\CoreBundle\Entity\Milestone', 'm')
            ->where('m.startDate > :now')
            ->andWhere('m.status != :paused')
            ->orderBy('m.startDate', 'ASC')
            ->setParameter('now', new \DateTime('now'))
            ->setParameter('paused', Action::STATUS_PAUSED);
        if(isset($options['limit'])){
            $qb->setMaxResults($options['limit']);
        }
        $query = $qb->getQuery();
        return $query->getResult();
    }

    public function getMilestone($id){
        $milestone = $this->em
            ->getRepository('CampaignChainCoreBundle:Milestone')
            ->find($id);

        if (!$milestone) {
            throw new \Exception(
                'No milestone found for id '.$id
            );
        }

        return $milestone;
    }

    public function getMilestoneModule($id){
        $milestoneModule = $this->em
            ->getRepository('CampaignChainCoreBundle:MilestoneModule')
            ->find($id);

        if (!$milestoneModule) {
            throw new \Exception(
                'No milestone module found for id '.$id
            );
        }

        return $milestoneModule;
    }

    public function getMilestoneModuleByMilestone($id){
        $milestone = $this->getMilestone($id);

        return $milestone->getMilestoneModule();
    }

    public function moveMilestone(Milestone $milestone, $interval){
        $hookService = $this->container->get($milestone->getTriggerHook()->getServices()['entity']);
        $hook = $hookService->getHook($milestone, Hook::MODE_MOVE);
        if($hook->getStartDate() !== null){
            $hook->setStartDate(new \DateTime($hook->getStartDate()->add($interval)->format(\DateTime::ISO8601)));
        }

        if($hook->getEndDate() !== null){
            $hook->setEndDate(new \DateTime($hook->getEndDate()->add($interval)->format(\DateTime::ISO8601)));
        }

        $milestone = $hookService->processHook($milestone, $hook);

        $this->em->persist($milestone);
        $this->em->flush();

        return $milestone;
    }

    /**
     * Compose the milestone icon path
     *
     * @param $channel
     * @return mixed
     */
    public function getIcons($milestone)
    {
        // Compose the channel icon path
        $bundlePath = $milestone->getMilestoneModule()->getBundle()->getWebAssetsPath();
        $bundleName = $milestone->getMilestoneModule()->getBundle()->getName();
        $iconName = str_replace('campaignchain/', '', str_replace('-', '_', $bundleName)).'.png';
        $icon['16px'] = '/'.$bundlePath.'/images/icons/16x16/'.$iconName;
        $icon['24px'] = '/'.$bundlePath.'/images/icons/24x24/'.$iconName;

        return $icon;
    }

    public function cloneMilestone(Campaign $campaign, Milestone $milestone, $status = null)
    {
        $clonedMilestone = clone $milestone;
        $clonedMilestone->setCampaign($campaign);
        $campaign->addMilestone($clonedMilestone);

        if($status != null){
            $clonedMilestone->setStatus($status);
        }

        $this->em->persist($clonedMilestone);
        $this->em->persist($campaign);
        $this->em->flush();

        return $clonedMilestone;
    }
    /**
     *
     *
     * @param $id
     * @throws \Exception
     */
    public function removeMilestone($id){
        $milestone = $this->em
            ->getRepository('CampaignChainCoreBundle:Milestone')
            ->find($id);

        if (!$milestone) {
            throw new \Exception(
                'No milestone found for id '.$id
            );
        }
        //Deletion should only be possible if the milestone is not closed
        if ( $milestone->getStatus() == "closed") {
            throw new \LogicException(
                'Deletion of milestones is not possible when status is set to closed'
            );
        }
            $this->em->remove($milestone);
            $this->em->flush();
        }

    public function isRemovable($id){
        $milestone = $this->em
            ->getRepository('CampaignChainCoreBundle:Milestone')
            ->find($id);

        if (!$milestone) {
            throw new \Exception(
                'No channel found for id ' . $id
            );
        }

        return true;
    }

}