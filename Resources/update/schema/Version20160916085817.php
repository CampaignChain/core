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

use CampaignChain\CoreBundle\Entity\Activity;
use CampaignChain\CoreBundle\Entity\Operation;
use CampaignChain\CoreBundle\EntityService\OperationService;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160916085817 extends AbstractMigration implements ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE campaignchain_activity ADD mustValidate TINYINT(1) NOT NULL');
    }

    public function postUp(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $this->container->get('doctrine');
        $em = $managerRegistry->getManager();

        $activities = $em->getRepository('CampaignChainCoreBundle:Activity')->findAll();

        if(count($activities)) {
            try {
                $em->getConnection()->beginTransaction();

                /** @var Activity $activity */
                foreach ($activities as $activity) {
                    $operations = $em->getRepository('CampaignChainCoreBundle:Operation')
                        ->findByActivity($activity);

                    if (count($operations)) {
                        /** @var OperationService $operationService */
                        $operationService = $this->container->get('campaignchain.core.operation');

                        /** @var Operation $operation */
                        foreach ($operations as $operation) {
                            try {
                                $content = $operationService->getContent($operation);

                                $handlerService = $this->container->get(
                                    $activity->getModule()->getServices()['handler']
                                );

                                $activity->setMustValidate(
                                    $handlerService->mustValidate($content)
                                );

                                $em->persist($activity);
                                $em->flush();
                            } catch (\Exception $e) {
                                // Do nothing.
                            }
                        }
                    }
                }

                $em->getConnection()->commit();

                return $activity;
            } catch (\Exception $e) {
                $em->getConnection()->rollback();
                throw $e;
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE campaignchain_activity DROP mustValidate');
    }
}
