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

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CampaignChain\CoreBundle\Entity\Operation;
use CampaignChain\CoreBundle\Entity\Activity;

class OperationService
{
    protected $em;
    protected $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function getOperation($id){
        $activity = $this->em
            ->getRepository('CampaignChainCoreBundle:Operation')
            ->find($id);

        if (!$activity) {
            throw new \Exception(
                'No Operation found for id '.$id
            );
        }

        return $activity;
    }

    public function getOperationModule($bundleIdentifier, $operationIdentifier){
        // Get bundle.
        $bundle = $this->em
            ->getRepository('CampaignChainCoreBundle:Bundle')
            ->findOneByName($bundleIdentifier);
        if (!$bundle) {
            throw new \Exception(
                'No bundle found for identifier '.$bundleIdentifier
            );
        }

        // Get the operation module's config.
        $operationModule = $this->em
            ->getRepository('CampaignChainCoreBundle:OperationModule')
            ->findOneBy(array(
                    'bundle' => $bundle,
                    'identifier' => $operationIdentifier,
                )
            );
        if (!$operationModule) {
            throw new \Exception(
                'No operation module found for bundle '.$bundle->getName().' and identifier '.$operationIdentifier
            );
        }

        return $operationModule;
    }

    public function moveOperation(Operation $operation, $interval){
        $hookService = $this->container->get($operation->getTriggerHook()->getServices()['entity']);
        $hook = $hookService->getHook($operation);

        if($hook->getStartDate() !== null){
            if($operation->getActivity()->getEqualsOperation() != true){
                $hook->setStartDate(new \DateTime($hook->getStartDate()->add($interval)->format(\DateTime::ISO8601)));
            } else {
                $hook->setStartDate($operation->getActivity()->getStartDate());
            }
        }
        if($hook->getEndDate() !== null){
            if($operation->getActivity()->getEqualsOperation() != true){
                $hook->setEndDate(new \DateTime($hook->getEndDate()->add($interval)->format(\DateTime::ISO8601)));
            } else {
                $hook->setEndDate($operation->getActivity()->getEndDate());
            }
        }

        $operation = $hookService->processHook($operation, $hook);

        $this->em->persist($operation);
        $this->em->flush();

        return $operation;
    }

    public function cloneOperation(Activity $activity, Operation $operation, $status = null)
    {
        $clonedOperation = clone $operation;
        $clonedOperation->setActivity($activity);
        $activity->addOperation($clonedOperation);

        if($status != null){
            $clonedOperation->setStatus($status);
        }

        $this->em->persist($clonedOperation);
        $this->em->flush();

        // Execute clone method of module.
        $moduleServices = $clonedOperation->getOperationModule()->getServices();
        if(
            $moduleServices != null &&
            is_array($moduleServices) &&
            isset($moduleServices['operation'])
        ){
            $moduleOperationService = $this->container->get($moduleServices['operation']);
            $moduleOperationService->clone($operation, $clonedOperation);
        }

        return $clonedOperation;
    }

    public function newOperationByActivity(Activity $activity, $bundleName, $moduleIdentifier)
    {
        $operationModule = $this->getOperationModule(
            $bundleName,
            $moduleIdentifier
        );

        $operation = new Operation();
        $operation->setName($activity->getName());
        $operation->setStartDate($activity->getStartDate());
        $operation->setEndDate($activity->getEndDate());
        $operation->setTriggerHook($activity->getTriggerHook());
        $operation->setActivity($activity);
        $activity->addOperation($operation);
        $operationModule->addOperation($operation);
        $operation->setOperationModule($operationModule);

        return $operation;
    }
}