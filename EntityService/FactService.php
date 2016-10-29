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

use CampaignChain\CoreBundle\Entity\Operation;
use Doctrine\Common\Persistence\ManagerRegistry;

class FactService
{
    protected $em;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->em = $managerRegistry->getManager();
    }

    public function addFacts($factType, $bundleName, Operation $operation, array $facts)
    {
        switch ($factType) {
            case 'activity':
                $factClass = 'CampaignChain\\CoreBundle\\Entity\\ReportAnalyticsActivityFact';
                $metricRepo = 'ReportAnalyticsActivityMetric';
                break;
            case 'location':
                $factClass = 'CampaignChain\\CoreBundle\\Entity\\ReportAnalyticsLocationFact';
                $metricRepo = 'ReportAnalyticsLocationMetric';
                break;
            default:
                throw new \Exception(
                    "Unknown fact type '".$factType."'."
                    ."Pick 'activity' or 'location' instead."
                );
                break;
        }

        foreach ($facts as $metricName => $value) {
            // Get metrics object.
            $metric = $this->em
                ->getRepository('CampaignChainCoreBundle:'.$metricRepo)
                ->findOneBy(
                    [
                        'name' => $metricName,
                        'bundle' => $bundleName,
                    ]
                );
            if (!$metric) {
                throw new \Exception('No metric found with name "'.$metricName.'" for bundle "'.$bundleName.'"');
            }
            // Create new facts entry
            $fact = new $factClass();
            $fact->setMetric($metric);
            $fact->setOperation($operation);
            $fact->setActivity($operation->getActivity());
            $fact->setCampaign($operation->getActivity()->getCampaign());
            $fact->setTime(new \DateTime('now', new \DateTimeZone('UTC')));
            $fact->setValue($value);
            $this->em->persist($fact);
        }
    }

    public function addLocationFacts($bundleName, $location, array $facts)
    {
        $factClass = 'CampaignChain\\CoreBundle\\Entity\\ReportAnalyticsLocationFact';
        $metricRepo = 'ReportAnalyticsLocationMetric';

        foreach ($facts as $metricName => $value) {
            // Get metrics object.
            $metric = $this->em
                ->getRepository('CampaignChainCoreBundle:'.$metricRepo)
                ->findOneBy(
                    [
                        'name' => $metricName,
                        'bundle' => $bundleName,
                    ]
                );
            if (!$metric) {
                throw new \Exception('No metric found with name "'.$metricName.'" for bundle "'.$bundleName.'"');
            }
            // Create new facts entry
            $fact = new $factClass();
            $fact->setMetric($metric);
            $fact->setLocation($location);
            $fact->setTime(new \DateTime('now', new \DateTimeZone('UTC')));
            $fact->setValue($value);
            $this->em->persist($fact);
        }
    }
}
