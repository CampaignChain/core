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