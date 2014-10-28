<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Controller;

use CampaignChain\CoreBundle\Entity\Bundle,
    CampaignChain\CoreBundle\Entity\Hook,
    CampaignChain\CoreBundle\Entity\ChannelModule,
    CampaignChain\CoreBundle\Entity\LocationModule,
    CampaignChain\CoreBundle\Entity\CampaignModule,
    CampaignChain\CoreBundle\Entity\MilestoneModule,
    CampaignChain\CoreBundle\Entity\ActivityModule,
    CampaignChain\CoreBundle\Entity\OperationModule,
    CampaignChain\CoreBundle\Entity\Report;
use CampaignChain\CoreBundle\Entity\System;
use Symfony\Bundle\FrameworkBundle\Command\CacheClearCommand;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Yaml\Yaml;
use Symfony\Bundle\FrameworkBundle\Console\Application,
    Symfony\Component\Console\Input\ArrayInput,
    Symfony\Component\Console\Output\NullOutput;
use Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand;
use Symfony\Bundle\FrameworkBundle\Command\AssetsInstallCommand;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Bundle\AsseticBundle\Command\DumpCommand;

class ModuleController extends Controller
{
    public function indexAction(Request $request){
        $logger = $this->get('logger');

        // TODO: Test whether module installation also works if CampaignChain is in src/ as well as in vendors/.
        $campaignchainRoot = realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);

        $newBundles = $this->getNewBundles($campaignchainRoot);

        if(is_array($newBundles) && count($newBundles)){
            $form = $this->createFormBuilder()
                ->add('save', 'submit', array(
                    'label' => 'Install',
                ))
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                // Increase timeout limit to run this script.
                set_time_limit(20);

                // Load schemas of entities into database
                $application = new Application($this->container->get( 'kernel' ));
                $application->add(new UpdateSchemaDoctrineCommand());
                $command = $application->find('doctrine:schema:update');

                $arguments = array(
                    'doctrine:schema:update',
                    '--force' => true,
                );
                $input = new ArrayInput($arguments);
                $output = new NullOutput();

                $command->run($input, $output);

                // Persist new bundles and modules
                $repository = $this->getDoctrine()->getManager();

                try {
                    $repository->getConnection()->beginTransaction();

                    // Prepare array where we store system parameters.
                    $systemParams = array();

                    foreach($newBundles as $newBundle){
                        // Skip the core bundle
                        if($newBundle->getType() == 'campaignchain-core'){
                            continue;
                        }

                        $params = $this->getModule($campaignchainRoot.DIRECTORY_SEPARATOR.$newBundle->getPath().DIRECTORY_SEPARATOR.'campaignchain.yml');

                        if($newBundle->getType() == 'campaignchain-hook'){
                            if(is_array($params['hooks']) && count($params['hooks'])){
                                foreach($params['hooks'] as $identifier => $hookParams){
                                    $hook = new Hook();
                                    $hook->setIdentifier($identifier);
                                    $hook->setBundle($newBundle);
                                    $hook->setServices($hookParams['services']);
                                    $hook->setType($hookParams['type']);
                                    $hook->setLabel($hookParams['label']);
                                    $newBundle->addHook($hook);
                                }
                            }
                        } elseif($newBundle->getType() == 'campaignchain-distribution'){
                            /*
                             * If a system entry already exists (e.g. from sample
                             * data), then update it.
                             */
                            $system = $repository->getRepository('CampaignChainCoreBundle:System')->find(1);

                            // TODO: Add handling of sample data.
                            if(!$system){
                                $system = new System();
                            }
                            $system->setName($params['name']);
                            if(isset($params['version']) && !empty($params['version'])){
                                $system->setVersion($params['version']);
                            }
                            $system->setHomepage($newBundle->getHomepage());
                            if(isset($params['logo']) && !empty($params['logo'])){
                                $logoPath = $this->get('templating.helper.assets')
                                    ->getUrl($params['assets_path'].$params['logo'],null);
                                $system->setLogo($logoPath);
                            }
                            if(isset($params['style']) && !empty($params['style'])){
                                $cssPath = $this->get('templating.helper.assets')
                                    ->getUrl($params['assets_path'].$params['style'],null);
                                $system->setStyle($cssPath);
                            }

                            $repository->persist($system);
                        } else {
                            switch($newBundle->getType()){
                                case 'campaignchain-channel':
                                    $moduleEntity = 'ChannelModule';
                                    break;
                                case 'campaignchain-location':
                                    $moduleEntity = 'LocationModule';
                                    break;
                                case 'campaignchain-campaign':
                                    $moduleEntity = 'CampaignModule';
                                    break;
                                case 'campaignchain-milestone':
                                    $moduleEntity = 'MilestoneModule';
                                    break;
                                case 'campaignchain-activity':
                                    $moduleEntity = 'ActivityModule';
                                    break;
                                case 'campaignchain-operation':
                                    $moduleEntity = 'OperationModule';
                                    break;
                                case 'campaignchain-report':
                                case 'campaignchain-report-analytics':
                                case 'campaignchain-report-budget':
                                case 'campaignchain-report-sales':
                                    $moduleEntity = 'ReportModule';
                                    break;
                                case 'campaignchain-security':
                                    $moduleEntity = 'SecurityModule';
                                    break;
                            }

                            // General installation routine.
                            if(is_array($params['modules']) && count($params['modules'])){
                                foreach($params['modules'] as $identifier => $moduleParams){
                                    $moduleEntityClass = 'CampaignChain\\CoreBundle\\Entity\\'.$moduleEntity;
                                    $module = new $moduleEntityClass();
                                    $module->setIdentifier(strtolower($identifier));
                                    $module->setDisplayName($moduleParams['display_name']);
                                    $module->setBundle($newBundle);
                                    if(isset($moduleParams['routes']) && is_array($moduleParams['routes']) && count($moduleParams['routes'])){
                                        $module->setRoutes($moduleParams['routes']);
                                    }
                                    if(isset($moduleParams['services']) && is_array($moduleParams['services']) && count($moduleParams['services'])){
                                        $module->setServices($moduleParams['services']);
                                    }
                                    if(isset($moduleParams['hooks']) && is_array($moduleParams['hooks']) && count($moduleParams['hooks'])){
                                        // TODO: Check that there's only 1 trigger hook included.
                                        $module->setHooks($moduleParams['hooks']);
                                    }
                                    if(isset($moduleParams['system']) && is_array($moduleParams['system']) && count($moduleParams['system'])){
                                        $systemParams = array_merge($systemParams, $moduleParams['system']);
                                    }

                                    // Process the params specific to a module type.
                                    $params = array();

                                    // If an Operation, make sure the is_location flag is set.
                                    if($newBundle->getType() == 'campaignchain-operation'){
                                        if(!isset($moduleParams['owns_location'])){
                                            throw new \Exception("You must set the 'owns_location' parameter in campaignchain.yml to 'true' or 'false' for module '".$identifier."' in bundle '".$newBundle->getName()."'.");
                                        } else {
                                            $params['owns_location'] = $moduleParams['owns_location'];
                                        }
                                    }

                                    $module->setParams($params);

                                    // Add new module to new bundle.
                                    $addModuleMethod = 'add'.$moduleEntity;
                                    $newBundle->$addModuleMethod($module);

                                    // If an activity module, remember the related channels.
                                    if($newBundle->getType() == 'campaignchain-activity'){
                                        $activityChannels[$newBundle->getName()][$module->getIdentifier()] = $moduleParams['channels'];
                                    }
                                }
                            }

                        }

                        $repository->persist($newBundle);
                        $repository->flush();
                    }

                    // Store the channels related to an activity.
                    if(isset($activityChannels) && count($activityChannels)){
                        foreach($activityChannels as $activityBundleIdentifier => $activityModules){
                            $activityBundle = $repository->getRepository('CampaignChainCoreBundle:Bundle')
                                ->findOneByName($activityBundleIdentifier);
                            foreach($activityModules as $activityModuleIdentifier => $activityModuleChannels){
                                $activityModule = $repository->getRepository('CampaignChainCoreBundle:ActivityModule')
                                    ->findOneBy(array(
                                        'bundle' => $activityBundle,
                                        'identifier' => $activityModuleIdentifier
                                    ));
                                foreach($activityModuleChannels as $channelURI){
                                    $channelURISplit = explode('/', $channelURI);
                                    $channelBundleIdentifier = $channelURISplit[0].'/'.$channelURISplit[1];
                                    $channelModuleIdentifier = $channelURISplit[2];
                                    $channelBundle = $repository->getRepository('CampaignChainCoreBundle:Bundle')
                                        ->findOneByName($channelBundleIdentifier);
                                    $channelModule = $repository->getRepository('CampaignChainCoreBundle:ChannelModule')
                                        ->findOneBy(array(
                                            'bundle' => $channelBundle,
                                            'identifier' => $channelModuleIdentifier
                                        ));

                                    // Map activity and channel.
                                    $activityModule->addChannelModule($channelModule);
                                    $repository->persist($activityModule);
                                }
                            }
                        }

                        $repository->flush();
                    }

                    // Store the system parameters.
                    if(count($systemParams)){
                        foreach($systemParams as $key => $params){
                            switch($key){
                                case 'navigation':
                                    /*
                                     * If a system entry already exists, then update it. Otherwise, create a new one.
                                     */
                                    $system = $repository->getRepository('CampaignChainCoreBundle:System')->find(1);

                                    if(!$system){
                                        $system = new System();
                                    }
                                    $system->setNavigation($params);
                                    break;
                            }
                        }

                        $repository->flush();
                    }

                    $repository->getConnection()->commit();
                } catch (\Exception $e) {
                    $repository->getConnection()->rollback();
                    throw $e;
                }

                // Install assets to web/ directory and dump assetic files.
                $application = new Application($this->container->get( 'kernel' ));
                $application->add(new CacheClearCommand());
                $application->add(new AssetsInstallCommand());
                $application->add(new DumpCommand());

                // app/console assets:install web
                $command = $application->find('assets:install');
                $arguments = array(
                    'assets:install',
                    'target' => $this->get('kernel')->getRootDir() . '/../web',
                );
                $input = new ArrayInput($arguments);
                $output = new BufferedOutput();
                $command->run($input, $output);
                $logger->info('Output of assets:install:');
                $logger->info($output->fetch());

                // app/console assetic:dump --no-debug
                $command = $application->find('assetic:dump');
                $arguments = array(
                    'assets:install',
                    '--no-debug' => true,
                );
                $input = new ArrayInput($arguments);
                $output = new BufferedOutput();
                $command->run($input, $output);
                $logger->info('Output of assetic:dump:');
                $logger->info($output->fetch());

                $this->get('session')->getFlashBag()->add(
                    'success',
                    'Modules installed successfully.'
                );
            }

            $tplVariables['new_bundles'] = $this->getNewBundles($campaignchainRoot);
            $tplVariables['form'] = $form->createView();
        } else {
            $tplVariables['new_bundles'] = false;
            $tplVariables['form'] = false;
        }

        $tplVariables['installed_bundles'] = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:Bundle')
            ->findAll();

        $tplVariables['page_title'] = 'Modules';

        return $this->render(
            'CampaignChainCoreBundle:System:module.html.twig', $tplVariables);
    }

    protected function getModule($moduleConfig)
    {
        if(file_exists($moduleConfig)){
            $moduleConfigContent = file_get_contents($moduleConfig);
            return Yaml::parse($moduleConfigContent);
        } else {
            // TODO: Exception if file does not exist.
        }
    }

    protected function getNewBundles($rootDir){
        $finder = new Finder();
        // Find all the CampaignChain module configuration files.
        $finder->files()->in($rootDir)->name('campaignchain.yml');

        $bundles = null;

        foreach ($finder as $moduleConfig) {
            $bundleComposer = $rootDir.DIRECTORY_SEPARATOR.str_replace('campaignchain.yml', 'composer.json', $moduleConfig->getRelativePathname());
            //echo $composer->getRealpath().'<br/>';
            $bundle = $this->getNewBundle($rootDir, $bundleComposer);
            if($bundle){
                $bundles[] = $bundle;
            }
        }

        return $bundles;
    }

    protected function getNewBundle($rootDir, $bundleComposer)
    {
        if(file_exists($bundleComposer)){
            $bundleComposerData = file_get_contents($bundleComposer);

            $normalizer = new GetSetMethodNormalizer();
            $normalizer->setIgnoredAttributes(array(
                'require',
                'keywords',
            ));
            $encoder = new JsonEncoder();
            $serializer = new Serializer(array($normalizer), array($encoder));
            $bundle = $serializer->deserialize($bundleComposerData,'CampaignChain\CoreBundle\Entity\Bundle','json');

            // Check whether this bundle has already been installed
            if(!$this->getDoctrine()
                ->getRepository('CampaignChainCoreBundle:Bundle')
                ->findOneByName($bundle->getName())){
                $bundle->setPath(
                // Remove the root directory to get the relative path
                    str_replace($rootDir.DIRECTORY_SEPARATOR, '',
                        // Remove the composer file from the path
                        str_replace(DIRECTORY_SEPARATOR.'composer.json', '', $bundleComposer)
                    )
                );

                return $bundle;
            } else {
                return false;
            }
        } else {
            // TODO: Throw exception if file does not exist?
            return false;
        }
    }
}