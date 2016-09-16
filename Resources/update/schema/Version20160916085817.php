<?php

namespace Application\Migrations;

use CampaignChain\CoreBundle\Entity\Activity;
use CampaignChain\CoreBundle\Entity\Operation;
use CampaignChain\CoreBundle\EntityService\OperationService;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManager;
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

        $this->addSql('ALTER TABLE campaignchain_activity ADD checkExecutable TINYINT(1) NOT NULL');
    }

    public function postUp(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        /** @var EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');
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

                                $activity->setCheckExecutable(
                                    $handlerService->checkExecutable($content)
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

        $this->addSql('ALTER TABLE campaignchain_activity DROP checkExecutable');
    }
}
