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

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160717050911 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE campaignchain_module_location_channel (locationmodule_id INT NOT NULL, channelmodule_id INT NOT NULL, INDEX IDX_FDF9BAA05057D213 (locationmodule_id), INDEX IDX_FDF9BAA0F6B28358 (channelmodule_id), PRIMARY KEY(locationmodule_id, channelmodule_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE campaignchain_module_location_channel ADD CONSTRAINT FK_FDF9BAA05057D213 FOREIGN KEY (locationmodule_id) REFERENCES campaignchain_module (id)');
        $this->addSql('ALTER TABLE campaignchain_module_location_channel ADD CONSTRAINT FK_FDF9BAA0F6B28358 FOREIGN KEY (channelmodule_id) REFERENCES campaignchain_module (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_82D961D6B548B0F ON campaignchain_bundle (path)');
        $this->addSql('ALTER TABLE campaignchain_activity CHANGE location_id location_id INT DEFAULT NULL, CHANGE channel_id channel_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campaignchain_module ADD trackingAlias VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE campaignchain_campaign ADD description LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_DA8C534EF47645AE ON campaignchain_location');
        $this->addSql('UPDATE `campaignchain_report_cta` SET source_location_id = target_location_id WHERE referrer_location_id = source_location_id');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE campaignchain_module_location_channel');
        $this->addSql('ALTER TABLE campaignchain_activity CHANGE channel_id channel_id INT NOT NULL, CHANGE location_id location_id INT NOT NULL');
        $this->addSql('DROP INDEX UNIQ_82D961D6B548B0F ON campaignchain_bundle');
        $this->addSql('ALTER TABLE campaignchain_campaign DROP description');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DA8C534EF47645AE ON campaignchain_location (url)');
        $this->addSql('ALTER TABLE campaignchain_module DROP trackingAlias');
    }
}
