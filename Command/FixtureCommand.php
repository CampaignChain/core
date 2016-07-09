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