<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161001154608 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE campaignchain_cta ADD shortenedExpandedUrl VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE campaignchain_cta CHANGE location_id location_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campaignchain_cta CHANGE trackingId trackingId VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE campaignchain_cta ADD uniqueExpandedUrl VARCHAR(255) DEFAULT NULL, ADD shortenedUniqueExpandedUrl VARCHAR(30) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE campaignchain_cta DROP shortenedExpandedUrl');
        $this->addSql('ALTER TABLE campaignchain_cta CHANGE location_id location_id INT NOT NULL');
        $this->addSql('ALTER TABLE campaignchain_cta CHANGE trackingId trackingId VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE campaignchain_cta DROP uniqueExpandedUrl, DROP shortenedUniqueExpandedUrl');
    }
}
