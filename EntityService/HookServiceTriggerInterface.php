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
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use CampaignChain\CoreBundle\Util\DateTimeUtil;
use CampaignChain\CoreBundle\EntityService\CampaignService;
use CampaignChain\CoreBundle\Entity\Action;

/**
 * Interface HookServiceDefaultInterface
 *
 * @author Sandro Groganz <sandro@campaignchain.com>
 * @package CampaignChain\CoreBundle\EntityService
 */
abstract class HookServiceTriggerInterface extends HookServiceDefaultInterface
{
    protected $em;
    protected $dateTimeUtil;
    protected $templating;
    protected $campaignService;

    public function __construct(
        ManagerRegistry $managerRegistry,
        DateTimeUtil $dateTimeUtil,
        EngineInterface $templating,
        CampaignService $campaignService
    )
    {
        $this->em = $managerRegistry->getManager();
        $this->dateTimeUtil = $dateTimeUtil;
        $this->templating = $templating;
        $this->campaignService = $campaignService;
    }

    /**
     * @param $entity
     * @param $mode
     * @return object The hook object.
     */
    public function getHook($entity, $mode = Hook::MODE_DEFAULT){}

    /**
     * @return string The hook's start date field attribute name as specified in the respective form type.
     */
    abstract public function getStartDateIdentifier();

    /**
     * @return string The hook's end date field attribute name as specified in the respective form type.
     */
    abstract public function getEndDateIdentifier();

    public function setPostStartDateLimit($entity)
    {
        /** @var Action $firstAction */
        $firstAction = $this->em->getRepository('CampaignChain\CoreBundle\Entity\Campaign')
            ->getFirstAction($entity);

        if ($firstAction) {
            try {
                $entity->setPostStartDateLimit($firstAction->getStartDate());
            } catch(\Exception $e) {
                $entity->setStartDate($firstAction->getStartDate());
            }
        }

        return $entity;
    }

    public function setPreEndDateLimit($entity)
    {
        /** @var Action $lastAction */
        $lastAction = $this->em->getRepository('CampaignChain\CoreBundle\Entity\Campaign')
            ->getLastAction($entity);

        if ($lastAction) {
            if (!$lastAction->getEndDate()) {
                $preEndDateLimit = $lastAction->getStartDate();
            } else {
                $preEndDateLimit = $lastAction->getEndDate();
            }

            try {
                $entity->setPreEndDateLimit($preEndDateLimit);
            } catch(\Exception $e) {
                $entity->setEndDate($preEndDateLimit);
            }
        }

        return $entity;
    }
}