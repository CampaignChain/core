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
class Version20161107121900 extends AbstractMigration implements ContainerAwareInterface
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
        $userImg = file_get_contents(
            SystemUtil::getRootDir().'vendor/campaignchain/core/Resources/public/images/default_user.png'
        );

        /** @var FileUploadService $fileUploadService */
        $fileUploadService = $this->container->get('campaignchain.core.service.file_upload');

        $fileUploadService->storeImage('default_user.png', $userImg);
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        /*
         * We take the created date and fill in the empty start date. This fixes
         * the problem where Campaigns that were edited with a start date in the
         * past created a NULL value for the start date, because the form did
         * not submit the start date, due to the field being disabled.
         */
        $this->addSql("UPDATE `campaignchain_campaign` SET `startDate` = `createdDate` WHERE `startDate` IS NULL");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
