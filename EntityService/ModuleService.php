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