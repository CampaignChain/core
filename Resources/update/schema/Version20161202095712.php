<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161202095712 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE campaignchain_report_analytics_location_fact DROP FOREIGN KEY FK_2900E88664D218E');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_location_fact ADD CONSTRAINT FK_2900E88664D218E FOREIGN KEY (location_id) REFERENCES campaignchain_location (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE campaignchain_scheduler_report DROP FOREIGN KEY FK_CC15A94664D218E');
        $this->addSql('ALTER TABLE campaignchain_scheduler_report ADD CONSTRAINT FK_CC15A94664D218E FOREIGN KEY (location_id) REFERENCES campaignchain_location (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE campaignchain_report_analytics_location_fact DROP FOREIGN KEY FK_2900E88664D218E');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_location_fact ADD CONSTRAINT FK_2900E88664D218E FOREIGN KEY (location_id) REFERENCES campaignchain_location (id)');
        $this->addSql('ALTER TABLE campaignchain_scheduler_report DROP FOREIGN KEY FK_CC15A94664D218E');
        $this->addSql('ALTER TABLE campaignchain_scheduler_report ADD CONSTRAINT FK_CC15A94664D218E FOREIGN KEY (location_id) REFERENCES campaignchain_location (id)');
    }
}
