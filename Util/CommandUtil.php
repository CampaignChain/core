<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Util;

use Composer\Command\RequireCommand;
use Symfony\Bundle\FrameworkBundle\Command\CacheClearCommand;
use Symfony\Bundle\FrameworkBundle\Command\CacheWarmupCommand;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application,
    Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand;
use Symfony\Bundle\FrameworkBundle\Command\AssetsInstallCommand;
use Symfony\Bundle\AsseticBundle\Command\DumpCommand;
use FOS\UserBundle\Command\CreateUserCommand;

class CommandUtil
{
    private $kernel;
    private $application;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
        $this->application = new Application($kernel);
    }

    protected function run($command, $arguments)
    {
        $input = new ArrayInput($arguments);
        $output = new BufferedOutput();
        $command->run($input, $output);

        return $output->fetch();
    }

    public function doctrineSchemaUpdate()
    {
        $this->application->add(new UpdateSchemaDoctrineCommand());
        $command = $this->application->find('doctrine:schema:update');

        $arguments = array(
            'doctrine:schema:update',
            '--force' => true,
        );

        return $this->run($command, $arguments);
    }

    public function assetsInstallWeb()
    {
        $this->application->add(new AssetsInstallCommand());

        // app/console assets:install web
        $command = $this->application->find('assets:install');
        $arguments = array(
            'assets:install',
            'target' => $this->kernel->getRootDir() . '/../web',
        );
        return $this->run($command, $arguments);
    }

    public function asseticDump()
    {
        $this->application->add(new DumpCommand());
        $command = $this->application->find('assetic:dump');
        $arguments = array(
            'assets:install',
            '--no-debug' => true,
        );
        return $this->run($command, $arguments);
    }

    public function createAdminUser($email, $password)
    {
        $this->application->add(new CreateUserCommand());
        $command = $this->application->find('fos:user:create');

        $arguments = array(
            'doctrine:schema:update',
            'username' => 'admin',
            '--super-admin' => true,
            'email' => $email,
            'password' => $password,
        );
        return $this->run($command, $arguments);
    }

    public function composerRequire($name, $version)
    {
        $this->application->add(new RequireCommand());
        $command = $this->application->find('require');
        $arguments = array(
            'require',
            'packages' => $name.':'.$version,
            '-n' => true,
        );
        return $this->run($command, $arguments);
    }

    public function clearCache($warmup = true)
    {
        $this->application->add(new CacheClearCommand());
        $command = $this->application->find('cache:clear');
        $arguments = array(
            'cache:clear',
            '--no-debug' => true,
            '--env' => 'prod',
        );

        if($warmup == false){
            $arguments['--no-warmup'] = true;
        }

        return $this->run($command, $arguments);
    }

    public function warumupCache()
    {
        $this->application->add(new CacheWarmupCommand());
        $command = $this->application->find('cache:warmup');
        $arguments = array(
            'cache:warmup',
        );
        return $this->run($command, $arguments);
    }
}