<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160706094356 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE campaignchain_report_analytics_channel_fact DROP FOREIGN KEY FK_5F2F19D1A952D583');
        $this->addSql('CREATE TABLE campaignchain_report_analytics_location_fact (id INT AUTO_INCREMENT NOT NULL, location_id INT DEFAULT NULL, metric_id INT DEFAULT NULL, value INT NOT NULL, time DATETIME NOT NULL, INDEX IDX_2900E88664D218E (location_id), INDEX IDX_2900E886A952D583 (metric_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_report_analytics_location_metric (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, bundle VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_location_fact ADD CONSTRAINT FK_2900E88664D218E FOREIGN KEY (location_id) REFERENCES campaignchain_location (id)');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_location_fact ADD CONSTRAINT FK_2900E886A952D583 FOREIGN KEY (metric_id) REFERENCES campaignchain_report_analytics_location_metric (id)');
        $this->addSql('DROP TABLE campaignchain_report_analytics_channel_fact');
        $this->addSql('DROP TABLE campaignchain_report_analytics_channel_metric');
        $this->addSql('ALTER TABLE campaignchain_scheduler_report ADD location_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campaignchain_scheduler_report ADD CONSTRAINT FK_CC15A94664D218E FOREIGN KEY (location_id) REFERENCES campaignchain_location (id)');
        $this->addSql('CREATE INDEX IDX_CC15A94664D218E ON campaignchain_scheduler_report (location_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE campaignchain_report_analytics_location_fact DROP FOREIGN KEY FK_2900E886A952D583');
        $this->addSql('CREATE TABLE campaignchain_report_analytics_channel_fact (id INT AUTO_INCREMENT NOT NULL, metric_id INT DEFAULT NULL, channel_id INT DEFAULT NULL, campaign_id INT DEFAULT NULL, value INT NOT NULL, time DATETIME NOT NULL, INDEX IDX_5F2F19D172F5A1AA (channel_id), INDEX IDX_5F2F19D1F639F774 (campaign_id), INDEX IDX_5F2F19D1A952D583 (metric_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_report_analytics_channel_metric (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_channel_fact ADD CONSTRAINT FK_5F2F19D1A952D583 FOREIGN KEY (metric_id) REFERENCES campaignchain_report_analytics_channel_metric (id)');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_channel_fact ADD CONSTRAINT FK_5F2F19D172F5A1AA FOREIGN KEY (channel_id) REFERENCES campaignchain_channel (id)');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_channel_fact ADD CONSTRAINT FK_5F2F19D1F639F774 FOREIGN KEY (campaign_id) REFERENCES campaignchain_campaign (id)');
        $this->addSql('DROP TABLE campaignchain_report_analytics_location_fact');
        $this->addSql('DROP TABLE campaignchain_report_analytics_location_metric');
        $this->addSql('ALTER TABLE campaignchain_scheduler_report DROP FOREIGN KEY FK_CC15A94664D218E');
        $this->addSql('DROP INDEX IDX_CC15A94664D218E ON campaignchain_scheduler_report');
        $this->addSql('ALTER TABLE campaignchain_scheduler_report DROP location_id');
    }
}
