<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ModuleUpdateCommand
 * @package CampaignChain\CoreBundle\Command
 * @author Sandro Groganz <sandro@campaignchain.com>
 *
 * Usage:
 * php app/console campaignchain:module:update
 */
class ModuleUpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('campaignchain:module:update')
            ->setDescription('Updates modules.')
            ->setHelp(<<<EOT
The <info>campaignchain:module:update</info> command updates CampaignChain modules:

  <info>php app/console campaignchain:module:update</info>
EOT
            )
            ->addOption(
                'schema-update',
                null,
                InputOption::VALUE_OPTIONAL,
                'During install should a doctrine schema update run.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $withSchemaUpdate = $input->getOption('schema-update') != 'false';
        $io->text(sprintf('Updating CampaignChain system registry for all modules <comment>%s</comment> Schema update', $withSchemaUpdate ? 'with' : 'without'));

        $installer = $this->getContainer()->get('campaignchain.core.module.installer');
        $installer->install($io, $withSchemaUpdate);

        $io->success('CampaignChain modules configuration files & database tables are updated');

        return;
    }
}