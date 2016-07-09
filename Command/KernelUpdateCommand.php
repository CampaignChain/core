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

use CampaignChain\CoreBundle\Module\BundleLocator;
use CampaignChain\CoreBundle\Module\Kernel;
use CampaignChain\CoreBundle\Module\Package;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class KernelUpdateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('campaignchain:kernel:update')
            ->setDescription('Updates modules.')
            ->setHelp(<<<EOT
The <info>%command.full_name%</info> command updates CampaignChain kernel
configuration files of modules:

  <info>php %command.full_name%</info>

To update only the configuration provided by the modules that have already been
downloaded, then use the <comment>--config-only</comment> option.

By setting the environment, you can exclude or include Composer packages defined
in require-dev. By default, require-dev packages are included. To exclude them
issue the command with:

  <info>php %command.full_name% --env=prod</info>
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
        
        $types = [];
        $listing = [];

        // If no option selected, all are active.
        if(
            !$input->getOption('config-only') &&
            !$input->getOption('routing-only') &&
            !$input->getOption('class-only') &&
            !$input->getOption('security-only')
        ){
            $input->setOption('config-only', true);
            $input->setOption('routing-only', true);
            $input->setOption('class-only', true);
            $input->setOption('security-only', true);
        }

        if ($input->getOption('class-only')) {
            $types = array_merge($types, ['classes' => true]);
            $listing[] = 'Registering bundle classes of all CampaignChain modules in AppKernel.php.';
        }

        if ($input->getOption('config-only')){
            $types = array_merge($types, ['configs' => true]);
            $listing[] = 'Registering config.yml files of all CampaignChain modules';
        }

        if ($input->getOption('routing-only')){
            $types = array_merge($types, ['routings' => true]);
            $listing[] = 'Registering routing.yml files of all CampaignChain modules';
        }

        if($input->getOption('security-only')) {
            $types = array_merge($types, ['security' => true]);
            $listing[] = 'Registering security.yml files of all CampaignChain modules';
        }

        $kernelRootDir = __DIR__
            .DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'
            .DIRECTORY_SEPARATOR.'..';
        
        $devEnv = true;
        if($input->getOption('env') == 'prod'){
            $devEnv = false;
        }

        $packages = new Package($kernelRootDir, $devEnv);
        $locator = new BundleLocator($kernelRootDir, $packages);

        $availableBundles = $locator->getAvailableBundles();
        
        $kernel = new Kernel($kernelRootDir);
        $kernel->parseBundlesForKernelConfig($availableBundles);

        $io->listing($listing);
        $kernel->register($types);

        $io->success('CampaignChain modules configuration files are updated');
    }
}