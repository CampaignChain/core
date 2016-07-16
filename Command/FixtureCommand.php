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

use CampaignChain\CoreBundle\Fixture\FileLoader;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FixtureCommand
 * @package CampaignChain\CoreBundle\Command
 * @author Sandro Groganz <sandro@campaignchain.com>
 *
 * Usage:
 * php app/console campaignchain:fixture
 */
class FixtureCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('campaignchain:fixture')
            ->setDescription('Updates modules.')
            ->setHelp(<<<EOT
The <info>%command.full_name%</info> loads Fixtures data into the database:

  <info>php %command.full_name% [files]</info>
EOT
            )
            ->addArgument(
                'files',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'The paths to multiple fixture files.'
            )
            ->addOption(
                'doDrop',
                null,
                InputOption::VALUE_OPTIONAL,
                'Drop all existing database content?',
                true
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        /** @var FileLoader $fixtureService */
        $fixtureService = $this->getContainer()->get('campaignchain.core.fixture');
        if(
            $fixtureService->load(
                $input->getArgument('files'), $input->getOption('doDrop')
            )
        ){
            $io->success('Successfully loaded fixture files.');
        } elseif($fixtureService->getException()){
            $io->warning($fixtureService->getException()->getMessage());
        }

        return;
    }
}