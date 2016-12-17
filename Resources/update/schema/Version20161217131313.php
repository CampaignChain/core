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

namespace Application\Migrations;

use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\Campaign;
use CampaignChain\CoreBundle\Entity\Theme;
use CampaignChain\CoreBundle\Service\FileUploadService;
use CampaignChain\CoreBundle\Util\SystemUtil;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161217131313 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Moves the start date of all campaigns beyond the first Action within
     * the campaign. This fixes the problem that before we implemented a check
     * that a campaign start date cannot be after its first Action, it was
     * actually possible.
     *
     * @param Schema $schema
     */
    public function preUp(Schema $schema)
    {
        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->container->get('doctrine');
        $em = $doctrine->getManager();

        // Get all Campaigns.
        $campaigns = $em->getRepository('CampaignChain\CoreBundle\Entity\Campaign')
            ->findAll();

        if($campaigns){
            try {
                $em->getConnection()->beginTransaction();

                /** @var Campaign $campaign */
                foreach($campaigns as $campaign){
                    /** @var Action $firstAction */
                    $firstAction = $em->getRepository('CampaignChain\CoreBundle\Entity\Campaign')
                        ->getFirstAction($campaign);
                    if($firstAction && $firstAction->getStartDate() < $campaign->getStartDate()){
                        $campaign->setStartDate($firstAction->getStartDate());
                        $em->persist($campaign);

                        $this->write(
                            'Changed start date of campaign "'.
                            $campaign->getName().'" ('.$campaign->getId().') '.
                            'to date "'.
                            $campaign->getStartDate()->format(\DateTime::ISO8601)
                        );
                    } else {
                        $this->write(
                            'No changes to campaign "'.
                            $campaign->getName().'" ('.$campaign->getId().').'
                        );
                    }
                }

                $em->flush();

                $em->getConnection()->commit();
            } catch (\Exception $e) {
                $em->getConnection()->rollback();
                $this->write($e->getMessage());
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
