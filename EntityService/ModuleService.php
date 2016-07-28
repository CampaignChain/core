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

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CampaignChain\CoreBundle\Entity\Module;

class ModuleService
{
    protected $em;
    protected $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function getModule($repository, $bundleName, $moduleIdentifier){
        if (!in_array($repository, array(
            Module::REPOSITORY_CAMPAIGN,
            Module::REPOSITORY_MILESTONE,
            Module::REPOSITORY_ACTIVITY,
            Module::REPOSITORY_OPERATION,
            Module::REPOSITORY_CHANNEL,
            Module::REPOSITORY_LOCATION,
            Module::REPOSITORY_SECURITY,
            Module::REPOSITORY_REPORT,
        ))) {
            throw new \InvalidArgumentException("Invalid module repository.");
        }

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
            ->getRepository('CampaignChainCoreBundle:'.$repository)
            ->findOneBy(array(
                    'bundle' => $bundle,
                    'identifier' => $moduleIdentifier,
                )
            );
        if (!$module) {
            throw new \Exception(
                'No operation module found for bundle '.$bundle->getName().' and identifier '.$moduleIdentifier
            );
        }

        return $module;
    }
}