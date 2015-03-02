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
                'Update the configuration only.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('config-only')) {
            $output->writeln('Updating configuration');
            $installer = $this->getContainer()->get('campaignchain.core.module.installer');
            $installer->setSkipVersion(true);
            $installer->getNewBundles();

            $kernel = $this->getContainer()->get('campaignchain.core.module.kernel');
            $types = array('configs' => true, 'routings' => true);
            $kernel->register($installer->getKernelConfig(), $types);
            $output->writeln('Done');
        } else {
            $this->getContainer()->enterScope('request');
            $this->getContainer()->set('request', new Request(), 'request');
            $output->writeln('Updating system registry for all existing modules');
            $installer = $this->getContainer()->get('campaignchain.core.module.installer');
//            $installer->setSkipVersion(true);
            $installer->install();
            $output->writeln('Done');
        }
    }
}