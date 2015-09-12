<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EntityService;

use CampaignChain\CoreBundle\Entity\Operation;
use Doctrine\ORM\EntityManager;

class FactService
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function addFacts($factType, $bundleName, Operation $operation, array $facts)
    {
        switch($factType){
            case 'activity':
                $factClass = 'CampaignChain\\CoreBundle\\Entity\\ReportAnalyticsActivityFact';
                $metricRepo = 'ReportAnalyticsActivityMetric';
                break;
            case 'channel':
                $factClass = 'CampaignChain\\CoreBundle\\Entity\\ReportAnalyticsChannelFact';
                $metricRepo = 'ReportAnalyticsChannelMetric';
                break;
            default:
                throw new \Exception(
                    "Unknown fact type '".$factType."'."
                    ."Pick 'activity' or 'channel' instead."
                );
                break;
        }

        foreach($facts as $metricName => $value){
            // Get metrics object.
            $metric = $this->em
                ->getRepository('CampaignChainCoreBundle:'.$metricRepo)
                ->findOneBy(array(
                    'name' => $metricName,
                    'bundle'=> $bundleName
                ));
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
}