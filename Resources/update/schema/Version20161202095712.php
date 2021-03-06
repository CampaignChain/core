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
