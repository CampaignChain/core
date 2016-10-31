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

use Symfony\Component\DependencyInjection\ContainerInterface;
use CampaignChain\CoreBundle\Entity\Module;
use Doctrine\Common\Persistence\ManagerRegistry;

class ModuleService
{
    protected $em;
    protected $container;

    public function __construct(ManagerRegistry $managerRegistry, ContainerInterface $container)
    {
        $this->em = $managerRegistry->getManager();
        $this->container = $container;
    }

    public function getModule($bundleName, $moduleIdentifier){
        // Get bundle.
        $bundle = $this->em
            ->getRepository('CampaignChainCoreBundle:Bundle')
            ->findOneByName($bundleName);
        if (!$bundle) {
            throw new \Exception(
                'No bundle found for identifier '.$bundleName
            );
        }

        // Get the module's config.
        $module = $this->em
            ->getRepository('CampaignChainCoreBundle:Module')
            ->findOneBy(array(
                    'bundle' => $bundle,
                    'identifier' => $moduleIdentifier,
                )
            );
        if (!$module) {
            throw new \Exception(
                'No module found for bundle '.$bundle->getName().' and identifier '.$moduleIdentifier
            );
        }

        return $module;
    }

    public function toggleStatus($bundleName, $moduleIdentifier)
    {
        /** @var Module $module */
        $module = $this->getModule($bundleName, $moduleIdentifier);

        $toggle = (($module->getStatus() == Module::STATUS_ACTIVE) ? $module->setStatus(
            Module::STATUS_INACTIVE
        ) : $module->setStatus(Module::STATUS_ACTIVE));

        if($module->getBundle()->getType() == 'campaignchain-channel') {
            foreach ($module->getChannels() as $channel) {
                $channel->setStatus($module->getStatus());
            }
        }
        $this->em->persist($module);
        $this->em->flush();

        return $module->getStatus();
    }

    public function getCopyAsCampaignModules($campaignId)
    {
        // Get the available campaign types for conversion
        $qb = $this->em->createQueryBuilder();
        $qb->select('cm')
            ->from('CampaignChain\CoreBundle\Entity\Campaign', 'c')
            ->from('CampaignChain\CoreBundle\Entity\CampaignModuleConversion', 'cmc')
            ->from('CampaignChain\CoreBundle\Entity\CampaignModule', 'cm')
            ->where('c.id = :campaignId')
            ->andWhere('c.campaignModule = cmc.from')
            ->andWhere('cmc.to = cm.id')
            ->orderBy('cm.displayName', 'ASC')
            ->setParameter('campaignId', $campaignId);
        $query = $qb->getQuery();
        return $query->getResult();
    }

    public function getModulesByType($type)
    {
        return $this->em->getRepository('CampaignChainCoreBundle:'.$type)
            ->findAll();
    }
}