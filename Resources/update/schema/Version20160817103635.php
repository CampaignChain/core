<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160817103635 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE campaignchain_campaign ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campaignchain_campaign ADD CONSTRAINT FK_9B07C858727ACA70 FOREIGN KEY (parent_id) REFERENCES campaignchain_campaign (id)');
        $this->addSql('CREATE INDEX IDX_9B07C858727ACA70 ON campaignchain_campaign (parent_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE campaignchain_campaign DROP FOREIGN KEY FK_9B07C858727ACA70');
        $this->addSql('DROP INDEX IDX_9B07C858727ACA70 ON campaignchain_campaign');
        $this->addSql('ALTER TABLE campaignchain_campaign DROP parent_id');
    }
}
