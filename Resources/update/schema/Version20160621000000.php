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
class Version20160621000000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE campaignchain_activity (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, campaign_id INT DEFAULT NULL, channel_id INT NOT NULL, location_id INT NOT NULL, assignee INT DEFAULT NULL, equalsOperation TINYINT(1) NOT NULL, name VARCHAR(100) DEFAULT NULL, startDate DATETIME DEFAULT NULL, endDate DATETIME DEFAULT NULL, `interval` VARCHAR(100) DEFAULT NULL, intervalStartDate DATETIME DEFAULT NULL, intervalNextRun DATETIME DEFAULT NULL, intervalEndDate DATETIME DEFAULT NULL, intervalEndOccurrence SMALLINT DEFAULT NULL, status VARCHAR(20) NOT NULL, createdDate DATETIME NOT NULL, modifiedDate DATETIME DEFAULT NULL, activityModule_id INT DEFAULT NULL, triggerHook_id INT DEFAULT NULL, INDEX IDX_2866D3DF727ACA70 (parent_id), INDEX IDX_2866D3DFF639F774 (campaign_id), INDEX IDX_2866D3DF72F5A1AA (channel_id), INDEX IDX_2866D3DF64D218E (location_id), INDEX IDX_2866D3DFA3F9C25C (activityModule_id), INDEX IDX_2866D3DF7C9DFC0C (assignee), INDEX IDX_2866D3DF1F7D1E56 (triggerHook_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_module (id INT AUTO_INCREMENT NOT NULL, bundle_id INT DEFAULT NULL, identifier VARCHAR(255) NOT NULL, displayName VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, routes LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', services LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', hooks LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', params LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', createdDate DATETIME NOT NULL, modifiedDate DATETIME DEFAULT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_2B867503F1FAD9D3 (bundle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_module_activity_channel (activitymodule_id INT NOT NULL, channelmodule_id INT NOT NULL, INDEX IDX_13EEDFE4210840FF (activitymodule_id), INDEX IDX_13EEDFE4F6B28358 (channelmodule_id), PRIMARY KEY(activitymodule_id, channelmodule_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_bundle (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, license VARCHAR(100) NOT NULL, authors LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', homepage VARCHAR(255) DEFAULT NULL, path VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, version VARCHAR(20) NOT NULL, createdDate DATETIME NOT NULL, modifiedDate DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_82D961D65E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_campaign (id INT AUTO_INCREMENT NOT NULL, assignee INT DEFAULT NULL, timezone VARCHAR(40) NOT NULL, hasRelativeDates TINYINT(1) NOT NULL, name VARCHAR(100) DEFAULT NULL, startDate DATETIME DEFAULT NULL, endDate DATETIME DEFAULT NULL, `interval` VARCHAR(100) DEFAULT NULL, intervalStartDate DATETIME DEFAULT NULL, intervalNextRun DATETIME DEFAULT NULL, intervalEndDate DATETIME DEFAULT NULL, intervalEndOccurrence SMALLINT DEFAULT NULL, status VARCHAR(20) NOT NULL, createdDate DATETIME NOT NULL, modifiedDate DATETIME DEFAULT NULL, campaignModule_id INT DEFAULT NULL, triggerHook_id INT DEFAULT NULL, INDEX IDX_9B07C8583183A42D (campaignModule_id), INDEX IDX_9B07C8587C9DFC0C (assignee), INDEX IDX_9B07C8581F7D1E56 (triggerHook_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_campaign_module_conversion (id INT AUTO_INCREMENT NOT NULL, `from` INT NOT NULL, `to` INT NOT NULL, INDEX IDX_4F9CE8B0B018BCAC (`from`), INDEX IDX_4F9CE8B0E64DFEA3 (`to`), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_channel (id INT AUTO_INCREMENT NOT NULL, trackingId VARCHAR(255) NOT NULL, name VARCHAR(100) DEFAULT NULL, status VARCHAR(20) NOT NULL, createdDate DATETIME NOT NULL, modifiedDate DATETIME DEFAULT NULL, channelModule_id INT DEFAULT NULL, INDEX IDX_E62D554744301FB (channelModule_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_cta (id INT AUTO_INCREMENT NOT NULL, operation_id INT NOT NULL, location_id INT NOT NULL, originalUrl VARCHAR(255) NOT NULL, expandedUrl VARCHAR(255) NOT NULL, trackingUrl VARCHAR(255) DEFAULT NULL, shortenedTrackingUrl VARCHAR(30) DEFAULT NULL, trackingId VARCHAR(255) NOT NULL, createdDate DATETIME NOT NULL, modifiedDate DATETIME DEFAULT NULL, INDEX IDX_1078D68044AC3583 (operation_id), INDEX IDX_1078D68064D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_group (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', createdDate DATETIME NOT NULL, modifiedDate DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_CDD2C5B45E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_hook (id INT AUTO_INCREMENT NOT NULL, bundle_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, identifier VARCHAR(255) NOT NULL, `label` VARCHAR(100) NOT NULL, services LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', UNIQUE INDEX UNIQ_BC3B4AB5772E836A (identifier), UNIQUE INDEX UNIQ_BC3B4AB5EA750E8 (`label`), INDEX IDX_BC3B4AB5F1FAD9D3 (bundle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_job (id INT AUTO_INCREMENT NOT NULL, scheduler_id INT DEFAULT NULL, pid INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, startDate DATETIME DEFAULT NULL, endDate DATETIME DEFAULT NULL, duration SMALLINT DEFAULT NULL, actionId INT NOT NULL, actionType VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, message LONGTEXT DEFAULT NULL, jobType VARCHAR(255) DEFAULT NULL, createdDate DATETIME NOT NULL, modifiedDate DATETIME DEFAULT NULL, INDEX IDX_2F96772FA9D0F7D9 (scheduler_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_location (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, channel_id INT DEFAULT NULL, operation_id INT DEFAULT NULL, identifier VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, name VARCHAR(100) DEFAULT NULL, status VARCHAR(20) NOT NULL, createdDate DATETIME NOT NULL, modifiedDate DATETIME DEFAULT NULL, locationModule_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_DA8C534EF47645AE (url), INDEX IDX_DA8C534E727ACA70 (parent_id), INDEX IDX_DA8C534ED2A650B0 (locationModule_id), INDEX IDX_DA8C534E72F5A1AA (channel_id), INDEX IDX_DA8C534E44AC3583 (operation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_milestone (id INT AUTO_INCREMENT NOT NULL, campaign_id INT DEFAULT NULL, assignee INT DEFAULT NULL, name VARCHAR(100) DEFAULT NULL, startDate DATETIME DEFAULT NULL, endDate DATETIME DEFAULT NULL, `interval` VARCHAR(100) DEFAULT NULL, intervalStartDate DATETIME DEFAULT NULL, intervalNextRun DATETIME DEFAULT NULL, intervalEndDate DATETIME DEFAULT NULL, intervalEndOccurrence SMALLINT DEFAULT NULL, status VARCHAR(20) NOT NULL, createdDate DATETIME NOT NULL, modifiedDate DATETIME DEFAULT NULL, milestoneModule_id INT DEFAULT NULL, triggerHook_id INT DEFAULT NULL, INDEX IDX_D2FAE6F7F639F774 (campaign_id), INDEX IDX_D2FAE6F7C6219C61 (milestoneModule_id), INDEX IDX_D2FAE6F77C9DFC0C (assignee), INDEX IDX_D2FAE6F71F7D1E56 (triggerHook_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_operation (id INT AUTO_INCREMENT NOT NULL, activity_id INT DEFAULT NULL, name VARCHAR(100) DEFAULT NULL, startDate DATETIME DEFAULT NULL, endDate DATETIME DEFAULT NULL, `interval` VARCHAR(100) DEFAULT NULL, intervalStartDate DATETIME DEFAULT NULL, intervalNextRun DATETIME DEFAULT NULL, intervalEndDate DATETIME DEFAULT NULL, intervalEndOccurrence SMALLINT DEFAULT NULL, status VARCHAR(20) NOT NULL, createdDate DATETIME NOT NULL, modifiedDate DATETIME DEFAULT NULL, operationModule_id INT DEFAULT NULL, triggerHook_id INT DEFAULT NULL, INDEX IDX_84D7C318E6850DDF (operationModule_id), INDEX IDX_84D7C31881C06096 (activity_id), INDEX IDX_84D7C3181F7D1E56 (triggerHook_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_report_analytics_activity_fact (id INT AUTO_INCREMENT NOT NULL, operation_id INT DEFAULT NULL, activity_id INT DEFAULT NULL, campaign_id INT DEFAULT NULL, metric_id INT DEFAULT NULL, value INT NOT NULL, time DATETIME NOT NULL, INDEX IDX_AAC0865444AC3583 (operation_id), INDEX IDX_AAC0865481C06096 (activity_id), INDEX IDX_AAC08654F639F774 (campaign_id), INDEX IDX_AAC08654A952D583 (metric_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_report_analytics_activity_metric (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, bundle VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_report_analytics_channel_fact (id INT AUTO_INCREMENT NOT NULL, channel_id INT DEFAULT NULL, campaign_id INT DEFAULT NULL, metric_id INT DEFAULT NULL, value INT NOT NULL, time DATETIME NOT NULL, INDEX IDX_5F2F19D172F5A1AA (channel_id), INDEX IDX_5F2F19D1F639F774 (campaign_id), INDEX IDX_5F2F19D1A952D583 (metric_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_report_analytics_channel_metric (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_report_cta (id INT AUTO_INCREMENT NOT NULL, cta_id INT NOT NULL, operation_id INT NOT NULL, activity_id INT NOT NULL, campaign_id INT NOT NULL, channel_id INT NOT NULL, referrer_location_id INT NOT NULL, source_location_id INT NOT NULL, target_location_id INT DEFAULT NULL, referrerUrl VARCHAR(255) NOT NULL, referrerName VARCHAR(255) NOT NULL, sourceUrl VARCHAR(255) NOT NULL, sourceName VARCHAR(255) NOT NULL, targetUrl VARCHAR(255) NOT NULL, targetName VARCHAR(255) DEFAULT NULL, time DATETIME NOT NULL, INDEX IDX_143FEA06296A161C (cta_id), INDEX IDX_143FEA0644AC3583 (operation_id), INDEX IDX_143FEA0681C06096 (activity_id), INDEX IDX_143FEA06F639F774 (campaign_id), INDEX IDX_143FEA0672F5A1AA (channel_id), INDEX IDX_143FEA0636CCEE3B (referrer_location_id), INDEX IDX_143FEA063A32712E (source_location_id), INDEX IDX_143FEA0681776E84 (target_location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_scheduler (id INT AUTO_INCREMENT NOT NULL, periodInterval SMALLINT NOT NULL, duration SMALLINT DEFAULT NULL, periodStart DATETIME NOT NULL, periodEnd DATETIME NOT NULL, executionStart DATETIME NOT NULL, executionEnd DATETIME DEFAULT NULL, status VARCHAR(20) NOT NULL, message LONGTEXT DEFAULT NULL, createdDate DATETIME NOT NULL, modifiedDate DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_scheduler_report (id INT AUTO_INCREMENT NOT NULL, end_campaign_id INT DEFAULT NULL, end_milestone_id INT DEFAULT NULL, end_activity_id INT DEFAULT NULL, operation_id INT DEFAULT NULL, startDate DATETIME NOT NULL, prevRun DATETIME DEFAULT NULL, `interval` VARCHAR(100) DEFAULT NULL, nextRun DATETIME DEFAULT NULL, endDate DATETIME DEFAULT NULL, prolongation VARCHAR(100) DEFAULT NULL, prolongationInterval VARCHAR(100) DEFAULT NULL, createdDate DATETIME NOT NULL, modifiedDate DATETIME DEFAULT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_CC15A946FC37110B (end_campaign_id), INDEX IDX_CC15A9468B8E4C91 (end_milestone_id), INDEX IDX_CC15A9468BCE86E9 (end_activity_id), INDEX IDX_CC15A94644AC3583 (operation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_system (id INT AUTO_INCREMENT NOT NULL, currency VARCHAR(100) NOT NULL, package VARCHAR(100) DEFAULT NULL, name VARCHAR(100) DEFAULT NULL, version VARCHAR(20) DEFAULT NULL, homepage VARCHAR(255) DEFAULT NULL, navigation LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', modules LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', termsUrl VARCHAR(255) DEFAULT NULL, bitlyAccessToken VARCHAR(255) DEFAULT NULL, createdDate DATETIME NOT NULL, modifiedDate DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, username_canonical VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, email_canonical VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, salt VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, last_login DATETIME DEFAULT NULL, locked TINYINT(1) NOT NULL, expired TINYINT(1) NOT NULL, expires_at DATETIME DEFAULT NULL, confirmation_token VARCHAR(255) DEFAULT NULL, password_requested_at DATETIME DEFAULT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', credentials_expired TINYINT(1) NOT NULL, credentials_expire_at DATETIME DEFAULT NULL, language VARCHAR(10) DEFAULT NULL, locale VARCHAR(20) DEFAULT NULL, timezone VARCHAR(40) DEFAULT NULL, currency VARCHAR(3) DEFAULT NULL, dateFormat VARCHAR(255) DEFAULT NULL, timeFormat VARCHAR(255) DEFAULT NULL, createdDate DATETIME NOT NULL, modifiedDate DATETIME DEFAULT NULL, firstName VARCHAR(255) NOT NULL, lastName VARCHAR(255) NOT NULL, avatarImage VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_95F0DFA992FC23A8 (username_canonical), UNIQUE INDEX UNIQ_95F0DFA9A0D96FBF (email_canonical), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaignchain_user_group (user_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_AFF06C4BA76ED395 (user_id), INDEX IDX_AFF06C4BFE54D947 (group_id), PRIMARY KEY(user_id, group_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE campaignchain_activity ADD CONSTRAINT FK_2866D3DF727ACA70 FOREIGN KEY (parent_id) REFERENCES campaignchain_activity (id)');
        $this->addSql('ALTER TABLE campaignchain_activity ADD CONSTRAINT FK_2866D3DFF639F774 FOREIGN KEY (campaign_id) REFERENCES campaignchain_campaign (id)');
        $this->addSql('ALTER TABLE campaignchain_activity ADD CONSTRAINT FK_2866D3DF72F5A1AA FOREIGN KEY (channel_id) REFERENCES campaignchain_channel (id)');
        $this->addSql('ALTER TABLE campaignchain_activity ADD CONSTRAINT FK_2866D3DF64D218E FOREIGN KEY (location_id) REFERENCES campaignchain_location (id)');
        $this->addSql('ALTER TABLE campaignchain_activity ADD CONSTRAINT FK_2866D3DFA3F9C25C FOREIGN KEY (activityModule_id) REFERENCES campaignchain_module (id)');
        $this->addSql('ALTER TABLE campaignchain_activity ADD CONSTRAINT FK_2866D3DF7C9DFC0C FOREIGN KEY (assignee) REFERENCES campaignchain_user (id)');
        $this->addSql('ALTER TABLE campaignchain_activity ADD CONSTRAINT FK_2866D3DF1F7D1E56 FOREIGN KEY (triggerHook_id) REFERENCES campaignchain_hook (id)');
        $this->addSql('ALTER TABLE campaignchain_module ADD CONSTRAINT FK_2B867503F1FAD9D3 FOREIGN KEY (bundle_id) REFERENCES campaignchain_bundle (id)');
        $this->addSql('ALTER TABLE campaignchain_module_activity_channel ADD CONSTRAINT FK_13EEDFE4210840FF FOREIGN KEY (activitymodule_id) REFERENCES campaignchain_module (id)');
        $this->addSql('ALTER TABLE campaignchain_module_activity_channel ADD CONSTRAINT FK_13EEDFE4F6B28358 FOREIGN KEY (channelmodule_id) REFERENCES campaignchain_module (id)');
        $this->addSql('ALTER TABLE campaignchain_campaign ADD CONSTRAINT FK_9B07C8583183A42D FOREIGN KEY (campaignModule_id) REFERENCES campaignchain_module (id)');
        $this->addSql('ALTER TABLE campaignchain_campaign ADD CONSTRAINT FK_9B07C8587C9DFC0C FOREIGN KEY (assignee) REFERENCES campaignchain_user (id)');
        $this->addSql('ALTER TABLE campaignchain_campaign ADD CONSTRAINT FK_9B07C8581F7D1E56 FOREIGN KEY (triggerHook_id) REFERENCES campaignchain_hook (id)');
        $this->addSql('ALTER TABLE campaignchain_campaign_module_conversion ADD CONSTRAINT FK_4F9CE8B0B018BCAC FOREIGN KEY (`from`) REFERENCES campaignchain_module (`id`)');
        $this->addSql('ALTER TABLE campaignchain_campaign_module_conversion ADD CONSTRAINT FK_4F9CE8B0E64DFEA3 FOREIGN KEY (`to`) REFERENCES campaignchain_module (`id`)');
        $this->addSql('ALTER TABLE campaignchain_channel ADD CONSTRAINT FK_E62D554744301FB FOREIGN KEY (channelModule_id) REFERENCES campaignchain_module (id)');
        $this->addSql('ALTER TABLE campaignchain_cta ADD CONSTRAINT FK_1078D68044AC3583 FOREIGN KEY (operation_id) REFERENCES campaignchain_operation (id)');
        $this->addSql('ALTER TABLE campaignchain_cta ADD CONSTRAINT FK_1078D68064D218E FOREIGN KEY (location_id) REFERENCES campaignchain_location (id)');
        $this->addSql('ALTER TABLE campaignchain_hook ADD CONSTRAINT FK_BC3B4AB5F1FAD9D3 FOREIGN KEY (bundle_id) REFERENCES campaignchain_bundle (id)');
        $this->addSql('ALTER TABLE campaignchain_job ADD CONSTRAINT FK_2F96772FA9D0F7D9 FOREIGN KEY (scheduler_id) REFERENCES campaignchain_scheduler (id)');
        $this->addSql('ALTER TABLE campaignchain_location ADD CONSTRAINT FK_DA8C534E727ACA70 FOREIGN KEY (parent_id) REFERENCES campaignchain_location (id)');
        $this->addSql('ALTER TABLE campaignchain_location ADD CONSTRAINT FK_DA8C534ED2A650B0 FOREIGN KEY (locationModule_id) REFERENCES campaignchain_module (id)');
        $this->addSql('ALTER TABLE campaignchain_location ADD CONSTRAINT FK_DA8C534E72F5A1AA FOREIGN KEY (channel_id) REFERENCES campaignchain_channel (id)');
        $this->addSql('ALTER TABLE campaignchain_location ADD CONSTRAINT FK_DA8C534E44AC3583 FOREIGN KEY (operation_id) REFERENCES campaignchain_operation (id)');
        $this->addSql('ALTER TABLE campaignchain_milestone ADD CONSTRAINT FK_D2FAE6F7F639F774 FOREIGN KEY (campaign_id) REFERENCES campaignchain_campaign (id)');
        $this->addSql('ALTER TABLE campaignchain_milestone ADD CONSTRAINT FK_D2FAE6F7C6219C61 FOREIGN KEY (milestoneModule_id) REFERENCES campaignchain_module (id)');
        $this->addSql('ALTER TABLE campaignchain_milestone ADD CONSTRAINT FK_D2FAE6F77C9DFC0C FOREIGN KEY (assignee) REFERENCES campaignchain_user (id)');
        $this->addSql('ALTER TABLE campaignchain_milestone ADD CONSTRAINT FK_D2FAE6F71F7D1E56 FOREIGN KEY (triggerHook_id) REFERENCES campaignchain_hook (id)');
        $this->addSql('ALTER TABLE campaignchain_operation ADD CONSTRAINT FK_84D7C318E6850DDF FOREIGN KEY (operationModule_id) REFERENCES campaignchain_module (id)');
        $this->addSql('ALTER TABLE campaignchain_operation ADD CONSTRAINT FK_84D7C31881C06096 FOREIGN KEY (activity_id) REFERENCES campaignchain_activity (id)');
        $this->addSql('ALTER TABLE campaignchain_operation ADD CONSTRAINT FK_84D7C3181F7D1E56 FOREIGN KEY (triggerHook_id) REFERENCES campaignchain_hook (id)');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_activity_fact ADD CONSTRAINT FK_AAC0865444AC3583 FOREIGN KEY (operation_id) REFERENCES campaignchain_operation (id)');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_activity_fact ADD CONSTRAINT FK_AAC0865481C06096 FOREIGN KEY (activity_id) REFERENCES campaignchain_activity (id)');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_activity_fact ADD CONSTRAINT FK_AAC08654F639F774 FOREIGN KEY (campaign_id) REFERENCES campaignchain_campaign (id)');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_activity_fact ADD CONSTRAINT FK_AAC08654A952D583 FOREIGN KEY (metric_id) REFERENCES campaignchain_report_analytics_activity_metric (id)');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_channel_fact ADD CONSTRAINT FK_5F2F19D172F5A1AA FOREIGN KEY (channel_id) REFERENCES campaignchain_channel (id)');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_channel_fact ADD CONSTRAINT FK_5F2F19D1F639F774 FOREIGN KEY (campaign_id) REFERENCES campaignchain_campaign (id)');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_channel_fact ADD CONSTRAINT FK_5F2F19D1A952D583 FOREIGN KEY (metric_id) REFERENCES campaignchain_report_analytics_channel_metric (id)');
        $this->addSql('ALTER TABLE campaignchain_report_cta ADD CONSTRAINT FK_143FEA06296A161C FOREIGN KEY (cta_id) REFERENCES campaignchain_cta (id)');
        $this->addSql('ALTER TABLE campaignchain_report_cta ADD CONSTRAINT FK_143FEA0644AC3583 FOREIGN KEY (operation_id) REFERENCES campaignchain_operation (id)');
        $this->addSql('ALTER TABLE campaignchain_report_cta ADD CONSTRAINT FK_143FEA0681C06096 FOREIGN KEY (activity_id) REFERENCES campaignchain_activity (id)');
        $this->addSql('ALTER TABLE campaignchain_report_cta ADD CONSTRAINT FK_143FEA06F639F774 FOREIGN KEY (campaign_id) REFERENCES campaignchain_campaign (id)');
        $this->addSql('ALTER TABLE campaignchain_report_cta ADD CONSTRAINT FK_143FEA0672F5A1AA FOREIGN KEY (channel_id) REFERENCES campaignchain_channel (id)');
        $this->addSql('ALTER TABLE campaignchain_report_cta ADD CONSTRAINT FK_143FEA0636CCEE3B FOREIGN KEY (referrer_location_id) REFERENCES campaignchain_location (id)');
        $this->addSql('ALTER TABLE campaignchain_report_cta ADD CONSTRAINT FK_143FEA063A32712E FOREIGN KEY (source_location_id) REFERENCES campaignchain_location (id)');
        $this->addSql('ALTER TABLE campaignchain_report_cta ADD CONSTRAINT FK_143FEA0681776E84 FOREIGN KEY (target_location_id) REFERENCES campaignchain_location (id)');
        $this->addSql('ALTER TABLE campaignchain_scheduler_report ADD CONSTRAINT FK_CC15A946FC37110B FOREIGN KEY (end_campaign_id) REFERENCES campaignchain_campaign (id)');
        $this->addSql('ALTER TABLE campaignchain_scheduler_report ADD CONSTRAINT FK_CC15A9468B8E4C91 FOREIGN KEY (end_milestone_id) REFERENCES campaignchain_milestone (id)');
        $this->addSql('ALTER TABLE campaignchain_scheduler_report ADD CONSTRAINT FK_CC15A9468BCE86E9 FOREIGN KEY (end_activity_id) REFERENCES campaignchain_activity (id)');
        $this->addSql('ALTER TABLE campaignchain_scheduler_report ADD CONSTRAINT FK_CC15A94644AC3583 FOREIGN KEY (operation_id) REFERENCES campaignchain_operation (id)');
        $this->addSql('ALTER TABLE campaignchain_user_group ADD CONSTRAINT FK_AFF06C4BA76ED395 FOREIGN KEY (user_id) REFERENCES campaignchain_user (id)');
        $this->addSql('ALTER TABLE campaignchain_user_group ADD CONSTRAINT FK_AFF06C4BFE54D947 FOREIGN KEY (group_id) REFERENCES campaignchain_group (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE campaignchain_activity DROP FOREIGN KEY FK_2866D3DF727ACA70');
        $this->addSql('ALTER TABLE campaignchain_operation DROP FOREIGN KEY FK_84D7C31881C06096');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_activity_fact DROP FOREIGN KEY FK_AAC0865481C06096');
        $this->addSql('ALTER TABLE campaignchain_report_cta DROP FOREIGN KEY FK_143FEA0681C06096');
        $this->addSql('ALTER TABLE campaignchain_scheduler_report DROP FOREIGN KEY FK_CC15A9468BCE86E9');
        $this->addSql('ALTER TABLE campaignchain_activity DROP FOREIGN KEY FK_2866D3DFA3F9C25C');
        $this->addSql('ALTER TABLE campaignchain_module_activity_channel DROP FOREIGN KEY FK_13EEDFE4210840FF');
        $this->addSql('ALTER TABLE campaignchain_module_activity_channel DROP FOREIGN KEY FK_13EEDFE4F6B28358');
        $this->addSql('ALTER TABLE campaignchain_campaign DROP FOREIGN KEY FK_9B07C8583183A42D');
        $this->addSql('ALTER TABLE campaignchain_campaign_module_conversion DROP FOREIGN KEY FK_4F9CE8B0B018BCAC');
        $this->addSql('ALTER TABLE campaignchain_campaign_module_conversion DROP FOREIGN KEY FK_4F9CE8B0E64DFEA3');
        $this->addSql('ALTER TABLE campaignchain_channel DROP FOREIGN KEY FK_E62D554744301FB');
        $this->addSql('ALTER TABLE campaignchain_location DROP FOREIGN KEY FK_DA8C534ED2A650B0');
        $this->addSql('ALTER TABLE campaignchain_milestone DROP FOREIGN KEY FK_D2FAE6F7C6219C61');
        $this->addSql('ALTER TABLE campaignchain_operation DROP FOREIGN KEY FK_84D7C318E6850DDF');
        $this->addSql('ALTER TABLE campaignchain_module DROP FOREIGN KEY FK_2B867503F1FAD9D3');
        $this->addSql('ALTER TABLE campaignchain_hook DROP FOREIGN KEY FK_BC3B4AB5F1FAD9D3');
        $this->addSql('ALTER TABLE campaignchain_activity DROP FOREIGN KEY FK_2866D3DFF639F774');
        $this->addSql('ALTER TABLE campaignchain_milestone DROP FOREIGN KEY FK_D2FAE6F7F639F774');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_activity_fact DROP FOREIGN KEY FK_AAC08654F639F774');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_channel_fact DROP FOREIGN KEY FK_5F2F19D1F639F774');
        $this->addSql('ALTER TABLE campaignchain_report_cta DROP FOREIGN KEY FK_143FEA06F639F774');
        $this->addSql('ALTER TABLE campaignchain_scheduler_report DROP FOREIGN KEY FK_CC15A946FC37110B');
        $this->addSql('ALTER TABLE campaignchain_activity DROP FOREIGN KEY FK_2866D3DF72F5A1AA');
        $this->addSql('ALTER TABLE campaignchain_location DROP FOREIGN KEY FK_DA8C534E72F5A1AA');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_channel_fact DROP FOREIGN KEY FK_5F2F19D172F5A1AA');
        $this->addSql('ALTER TABLE campaignchain_report_cta DROP FOREIGN KEY FK_143FEA0672F5A1AA');
        $this->addSql('ALTER TABLE campaignchain_report_cta DROP FOREIGN KEY FK_143FEA06296A161C');
        $this->addSql('ALTER TABLE campaignchain_user_group DROP FOREIGN KEY FK_AFF06C4BFE54D947');
        $this->addSql('ALTER TABLE campaignchain_activity DROP FOREIGN KEY FK_2866D3DF1F7D1E56');
        $this->addSql('ALTER TABLE campaignchain_campaign DROP FOREIGN KEY FK_9B07C8581F7D1E56');
        $this->addSql('ALTER TABLE campaignchain_milestone DROP FOREIGN KEY FK_D2FAE6F71F7D1E56');
        $this->addSql('ALTER TABLE campaignchain_operation DROP FOREIGN KEY FK_84D7C3181F7D1E56');
        $this->addSql('ALTER TABLE campaignchain_activity DROP FOREIGN KEY FK_2866D3DF64D218E');
        $this->addSql('ALTER TABLE campaignchain_cta DROP FOREIGN KEY FK_1078D68064D218E');
        $this->addSql('ALTER TABLE campaignchain_location DROP FOREIGN KEY FK_DA8C534E727ACA70');
        $this->addSql('ALTER TABLE campaignchain_report_cta DROP FOREIGN KEY FK_143FEA0636CCEE3B');
        $this->addSql('ALTER TABLE campaignchain_report_cta DROP FOREIGN KEY FK_143FEA063A32712E');
        $this->addSql('ALTER TABLE campaignchain_report_cta DROP FOREIGN KEY FK_143FEA0681776E84');
        $this->addSql('ALTER TABLE campaignchain_scheduler_report DROP FOREIGN KEY FK_CC15A9468B8E4C91');
        $this->addSql('ALTER TABLE campaignchain_cta DROP FOREIGN KEY FK_1078D68044AC3583');
        $this->addSql('ALTER TABLE campaignchain_location DROP FOREIGN KEY FK_DA8C534E44AC3583');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_activity_fact DROP FOREIGN KEY FK_AAC0865444AC3583');
        $this->addSql('ALTER TABLE campaignchain_report_cta DROP FOREIGN KEY FK_143FEA0644AC3583');
        $this->addSql('ALTER TABLE campaignchain_scheduler_report DROP FOREIGN KEY FK_CC15A94644AC3583');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_activity_fact DROP FOREIGN KEY FK_AAC08654A952D583');
        $this->addSql('ALTER TABLE campaignchain_report_analytics_channel_fact DROP FOREIGN KEY FK_5F2F19D1A952D583');
        $this->addSql('ALTER TABLE campaignchain_job DROP FOREIGN KEY FK_2F96772FA9D0F7D9');
        $this->addSql('ALTER TABLE campaignchain_activity DROP FOREIGN KEY FK_2866D3DF7C9DFC0C');
        $this->addSql('ALTER TABLE campaignchain_campaign DROP FOREIGN KEY FK_9B07C8587C9DFC0C');
        $this->addSql('ALTER TABLE campaignchain_milestone DROP FOREIGN KEY FK_D2FAE6F77C9DFC0C');
        $this->addSql('ALTER TABLE campaignchain_user_group DROP FOREIGN KEY FK_AFF06C4BA76ED395');
        $this->addSql('DROP TABLE campaignchain_activity');
        $this->addSql('DROP TABLE campaignchain_module');
        $this->addSql('DROP TABLE campaignchain_module_activity_channel');
        $this->addSql('DROP TABLE campaignchain_bundle');
        $this->addSql('DROP TABLE campaignchain_campaign');
        $this->addSql('DROP TABLE campaignchain_campaign_module_conversion');
        $this->addSql('DROP TABLE campaignchain_channel');
        $this->addSql('DROP TABLE campaignchain_cta');
        $this->addSql('DROP TABLE campaignchain_group');
        $this->addSql('DROP TABLE campaignchain_hook');
        $this->addSql('DROP TABLE campaignchain_job');
        $this->addSql('DROP TABLE campaignchain_location');
        $this->addSql('DROP TABLE campaignchain_milestone');
        $this->addSql('DROP TABLE campaignchain_operation');
        $this->addSql('DROP TABLE campaignchain_report_analytics_activity_fact');
        $this->addSql('DROP TABLE campaignchain_report_analytics_activity_metric');
        $this->addSql('DROP TABLE campaignchain_report_analytics_channel_fact');
        $this->addSql('DROP TABLE campaignchain_report_analytics_channel_metric');
        $this->addSql('DROP TABLE campaignchain_report_cta');
        $this->addSql('DROP TABLE campaignchain_scheduler');
        $this->addSql('DROP TABLE campaignchain_scheduler_report');
        $this->addSql('DROP TABLE campaignchain_system');
        $this->addSql('DROP TABLE campaignchain_user');
        $this->addSql('DROP TABLE campaignchain_user_group');
    }
}
