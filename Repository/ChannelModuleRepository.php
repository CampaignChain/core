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
namespace CampaignChain\CoreBundle\Repository;

use CampaignChain\CoreBundle\Entity\ActivityModule;
use CampaignChain\CoreBundle\Entity\LocationModule;
use CampaignChain\CoreBundle\Entity\Module;
use Doctrine\ORM\EntityRepository;

class ChannelModuleRepository extends EntityRepository
{
    public function findRegisteredModulesByActivityModule(ActivityModule $activityModule)
    {
        return $this->createQueryBuilder('channelRepository')
            ->select('channelRepository')
            ->join('channelRepository.activityModules', 'activityModule')
            ->where('activityModule = :activityModule')
            ->setParameter('activityModule', $activityModule)
            ->getQuery()
            ->getResult();
    }

    public function findRegisteredModulesByLocationModule(LocationModule $locationModule)
    {
        return $this->createQueryBuilder('channelRepository')
            ->select('channelRepository')
            ->join('channelRepository.locationModules', 'locationModule')
            ->where('locationModule = :locationModule')
            ->setParameter('locationModule', $locationModule)
            ->getQuery()
            ->getResult();
    }

    public function getActiveChannelModules()
    {
        return $this->createQueryBuilder('cm')
            ->select('cm')
            ->leftJoin('cm.channels', 'c')
            ->where('cm.status != :status')
            ->orderBy('cm.displayName', 'ASC')
            ->setParameter('status', Module::STATUS_INACTIVE)
            ->getQuery()
            ->getResult();
    }

    public function getAllChannelModules()
    {
        return $this->createQueryBuilder('cm')
            ->select('cm')
            ->orderBy('cm.displayName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
