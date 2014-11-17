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

class MilestoneService
{
    protected $em;
    protected $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
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
            ->orderBy('m.startDate', 'ASC')
            ->setParameter('now', new \DateTime('now'));
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

    public function moveMilestone($milestone, $interval){
        $hookService = $this->container->get($milestone->getTriggerHook()->getServices()['entity']);
        $hook = $hookService->getHook($milestone);
        if($hook->getStartDate() !== null){
            $hook->setStartDate(new \DateTime($hook->getStartDate()->add($interval)->format(\DateTime::ISO8601)));
        }
        if($hook->getEndDate() !== null){
            $hook->setEndDate(new \DateTime($hook->getEndDate()->add($interval)->format(\DateTime::ISO8601)));
        }
        return $hookService->processHook($milestone, $hook);
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
        $modulePath = $milestone->getMilestoneModule()->getBundle()->getPath();
        $bundlePath = 'bundles/campaignchain'.strtolower(str_replace(DIRECTORY_SEPARATOR, '', str_replace('Bundle', '', $modulePath)));
        $bundleName = $milestone->getMilestoneModule()->getBundle()->getName();
        $iconName = str_replace('campaignchain/', '', $bundleName).'.png';
        $icon['16px'] = '/'.$bundlePath.'/images/icons/16x16/'.$iconName;
        $icon['24px'] = '/'.$bundlePath.'/images/icons/24x24/'.$iconName;

        return $icon;
    }
}