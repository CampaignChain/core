<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (
            $input->getOption('config-only') ||
            $input->getOption('routing-only') ||
            $input->getOption('class-only')
        ) {
            $installer = $this->getContainer()->get('campaignchain.core.module.installer');
            $installer->setSkipVersion(true);
            $installer->getNewBundles();

            $kernel = $this->getContainer()->get('campaignchain.core.module.kernel');
            if($input->getOption('config-only')){
                $types = array('configs' => true);
                $output->writeln('Registering config.yml files of all CampaignChain modules');
            } elseif($input->getOption('routing-only')){
                $types = array('routings' => true);
                $output->writeln('Registering routing.yml files of all CampaignChain modules');
            } elseif($input->getOption('class-only')){
                $types = array('classes' => true);
                $output->writeln('Registering bundle classes of all CampaignChain modules in AppKernel.php.');
            }
            $kernel->register($installer->getKernelConfig(), $types);
            $output->writeln('Done');
        } else {
            $this->getContainer()->enterScope('request');
            $this->getContainer()->set('request', new Request(), 'request');
            $output->writeln('Updating CampaignChain system registry for all modules');
            $installer = $this->getContainer()->get('campaignchain.core.module.installer');
//            $installer->setSkipVersion(true);
            $installer->install();
            $output->writeln('Done');
        }
    }
}