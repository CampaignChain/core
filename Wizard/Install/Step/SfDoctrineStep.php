<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Wizard\Install\Step;

use CampaignChain\CoreBundle\Wizard\Install\Driver\YamlConfig;
use Sensio\Bundle\DistributionBundle\Configurator\Step\DoctrineStep;
use Symfony\Bundle\FrameworkBundle\Console\Application,
    Symfony\Component\Console\Input\ArrayInput,
    Symfony\Component\Console\Output\NullOutput;
use Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand;

class SfDoctrineStep extends DoctrineStep
{
    private $context;

    private $kernel;

    public function setKernel($kernel){
        $this->kernel = $kernel;
    }

    public function setContext(array $context){
        $this->context = $context;
    }

    public function setParameters(array $parameters)
    {
        $yamlConfig = new YamlConfig($this->context['kernel_dir'], 'config'.DIRECTORY_SEPARATOR.'parameters.yml');
        $parameters = $yamlConfig->read()['parameters'];

        parent::setParameters($parameters);
    }

    public function getTemplate()
    {
        return 'CampaignChainCoreBundle:Wizard/Install/Step:doctrine.html.twig';
    }

    public function execute($parameters){
        // Write the new parameters.yml file
        $yamlConfig = new YamlConfig($this->context['kernel_dir'], 'config'.DIRECTORY_SEPARATOR.'parameters.yml');
        $yamlConfig->write(array('parameters' => $parameters));
        $yamlConfig->clean();

        // Load schemas of entities into database
        $application = new Application($this->kernel);
        $application->add(new UpdateSchemaDoctrineCommand());
        $command = $application->find('doctrine:schema:update');

        $arguments = array(
            'doctrine:schema:update',
            '--force' => true,
        );
        $input = new ArrayInput($arguments);
        $output = new NullOutput();

        $command->run($input, $output);
    }
}
