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

To update only the configuration provided by the modules that have already been
downloaded, then use the <comment>--config-only</comment> option.
EOT
            )
            ->addOption(
                'schema-update',
                null,
                InputOption::VALUE_OPTIONAL,
                'During install should a doctrine schema update run.'
            )
            ->addOption(
                'config-only',
                null,
                InputOption::VALUE_NONE,
                'Register config.yml files of all modules.'
            )
            ->addOption(
                'routing-only',
                null,
                InputOption::VALUE_NONE,
                'Register routing.yml files of all modules.'
            )
            ->addOption(
                'class-only',
                null,
                InputOption::VALUE_NONE,
                'Register bundle classes of all modules in AppKernel.php.'
            )
            ->addOption(
                'security-only',
                null,
                InputOption::VALUE_NONE,
                'Register security.yml files of all modules.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (
            $input->getOption('config-only') ||
            $input->getOption('routing-only') ||
            $input->getOption('class-only') ||
            $input->getOption('security-only')
        ) {
            $types = [];
            $listing = [];

            if ($input->getOption('config-only')){
                $types = array_merge($types, ['configs' => true]);
                $listing[] = 'Registering config.yml files of all CampaignChain modules';
            }

            if ($input->getOption('routing-only')){
                $types = array_merge($types, ['routings' => true]);
                $listing[] = 'Registering routing.yml files of all CampaignChain modules';
            }

            if ($input->getOption('class-only')) {
                $types = array_merge($types, ['classes' => true]);
                $listing[] = 'Registering bundle classes of all CampaignChain modules in AppKernel.php.';
            }

            if($input->getOption('security-only')) {
                $types = array_merge($types, ['security' => true]);
                $listing[] = 'Registering security.yml files of all CampaignChain modules';
            }

            $availableBundles = $this->getContainer()
                ->get('campaignchain.core.module.locator')
                ->getAvailableBundles();

            $kernel = $this->getContainer()->get('campaignchain.core.module.kernel');
            $kernel->parseBundlesForKernelConfig($availableBundles);

            $io->listing($listing);
            $kernel->register($types);

            $io->success('CampaignChain modules configuration files are updated');

            return;
        } else {
            $withSchemaUpdate = $input->getOption('schema-update') != 'false';
            $io->text(sprintf('Updating CampaignChain system registry for all modules <comment>%s</comment> Schema update', $withSchemaUpdate ? 'with' : 'without'));

            $installer = $this->getContainer()->get('campaignchain.core.module.installer');
            $installer->install($io, $withSchemaUpdate);

            $io->success('CampaignChain modules configuration files & database tables are updated');
            
            return;
        }
    }
}