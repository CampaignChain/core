<?php

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
