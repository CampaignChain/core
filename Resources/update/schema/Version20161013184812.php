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

use CampaignChain\CoreBundle\Entity\Theme;
use CampaignChain\CoreBundle\Service\FileUploadService;
use CampaignChain\CoreBundle\Util\SystemUtil;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161013184812 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function preUp(Schema $schema)
    {
        $themeDataDir = SystemUtil::getRootDir().'vendor/campaignchain/core/Resources/data/theme/';

        /** @var FileUploadService $fileUploadService */
        $fileUploadService = $this->container->get('campaignchain.core.service.file_upload');

        $faviconContent = file_get_contents($themeDataDir.'favicon.ico');
        $newFaviconPath = Theme::STORAGE_PATH.'/favicon.ico';
        $fileUploadService->storeImage($newFaviconPath, $faviconContent);

        $logoContent = file_get_contents($themeDataDir.'logo.png');
        $newLogoPath = Theme::STORAGE_PATH.'/logo.png';
        $fileUploadService->storeImage($newLogoPath, $logoContent);
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE campaignchain_theme (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, favicon VARCHAR(255) NOT NULL, logo VARCHAR(255) NOT NULL, createdDate DATETIME NOT NULL, modifiedDate DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql(
"INSERT INTO `campaignchain_theme` (`id`, `name`, `favicon`, `logo`, `createdDate`, `modifiedDate`) VALUES
(1, 'CampaignChain', '".Theme::STORAGE_PATH."/favicon.ico', '".Theme::STORAGE_PATH."/logo.png', NOW(), NOW());"
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE campaignchain_theme');
    }
}
