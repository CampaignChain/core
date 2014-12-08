<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Module;

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
use CampaignChain\CoreBundle\Util\CommandUtil;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class Installer
{
    const STATUS_REGISTERED_NO = 'Not registered';
    const STATUS_REGISTERED_SAME = 'Same version already registered';
    const STATUS_REGISTERED_OLDER = 'Older version registered';

    private $em;
    private $container;

    private $root;

    private $newBundles = array();
    private $newBundle;

    private $isRegisteredBundle = array();

    private $systemParams = array();

    private $activityChannels = array();

    private $packageService;

    private $repositoryService;

    private $kernelConfig;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
        $this->command = $this->container->get('campaignchain.core.util.command');
        $this->logger = $this->container->get('logger');
        $this->root = realpath(
            __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);
        $this->packageService = $this->container->get('campaignchain.core.module.package');
        $this->repositoryService = $this->container->get('campaignchain.core.module.repository');
        $this->kernelConfig = new KernelConfig();
    }

    public function install(){
        if(!$this->getNewBundles($this->root)){
            return false;
        }

        // Increase timeout limit to run this script.
        set_time_limit(240);

        // Load schemas of entities into database
        $output = $this->command->doctrineSchemaUpdate();
        $this->logger->info('Output of doctrine:schema:update --force');
        $this->logger->info($output);

        $loggerResult = '';

        try {
            $this->em->getConnection()->beginTransaction();

            foreach($this->newBundles as $this->newBundle){
                $params = $this->getModule(
                    $this->root.DIRECTORY_SEPARATOR.$this->newBundle->getPath().DIRECTORY_SEPARATOR.'campaignchain.yml'
                    );

                switch($this->newBundle->getType()){
                    case 'campaignchain-core':
                        break;
                    case 'campaignchain-distribution':
                        // TODO: new vs. update
                        $this->registerDistribution($params);
                        break;
                    case 'campaignchain-hook':
                        // TODO: new vs. update
                        $this->registerHook($params);
                        break;
                    default:
                        $this->registerModule($params);
                        break;
                }

                $loggerResult .= $this->newBundle->getType().', ';


                $this->em->persist($this->newBundle);
                $this->em->flush();
            }

            // Store the channels related to an activity.
            $this->registerActivityChannels();

            // Store the system parameters injected by modules.
            $this->registerModuleSystemParams();

            // Register any new Bundles in the Symfony kernel.
            $kernelService = $this->container->get('campaignchain.core.module.kernel');
            $kernelService->register($this->kernelConfig);

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->logger->error($e->getMessage());
            throw $e;
        }

        $this->logger->info('Installed/updated modules: '.rtrim($loggerResult, ', '));

        // Load schemas of entities into database
        $output = $this->command->doctrineSchemaUpdate();
        $this->logger->info('Output of doctrine:schema:update --force');
        $this->logger->info($output);

        // Load schemas of entities into database
        $output = $this->command->clearCache(false);
        $this->logger->info('Output of cache:clear --no-warmup');
        $this->logger->info($output);

        // Install assets to web/ directory and dump assetic files.
        $output = $this->command->assetsInstallWeb();
        $this->logger->info('Output of assets:install web');
        $this->logger->info($output);

        // app/console assetic:dump --no-debug
        $output = $this->command->asseticDump();
        $this->logger->info('Output of assetic:dump --no-debug');
        $this->logger->info($output);

        return true;
    }

    public function isRegisteredBundle(Bundle $newBundle)
    {
        if(!isset($this->isRegisteredBundle[$newBundle->getName()])){
            $registeredBundle = $this->em
                ->getRepository('CampaignChainCoreBundle:Bundle')
                ->findOneByName($newBundle->getName());

            if(!$registeredBundle){
                $this->isRegisteredBundle[$newBundle->getName()] = self::STATUS_REGISTERED_NO;
            } elseif(version_compare($registeredBundle->getVersion(), $newBundle->getVersion(), '==')){
                // Bundle with same version is already registered.
                $this->isRegisteredBundle[$newBundle->getName()] = self::STATUS_REGISTERED_SAME;
            } elseif(version_compare(
                $registeredBundle->getVersion(), $newBundle->getVersion(), '<'
            )){
                // Bundle with older version is already registered.
                $this->isRegisteredBundle[$newBundle->getName()] = self::STATUS_REGISTERED_OLDER;
            }
        }

        return $this->isRegisteredBundle[$newBundle->getName()];
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

    public function getNewBundles(){
        $finder = new Finder();
        // Find all the CampaignChain module configuration files.
        $finder->files()->in($this->root)->name('campaignchain.yml');

        $bundles = null;

        // campaignchain-core package
        $coreComposerFile = $this->root.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'composer.json';
        $this->getNewBundle($coreComposerFile);

        foreach ($finder as $moduleConfig) {
            $bundleComposer = $this->root.DIRECTORY_SEPARATOR.str_replace(
                    'campaignchain.yml',
                    'composer.json',
                    $moduleConfig->getRelativePathname()
                );
            $this->getNewBundle($bundleComposer);
        }

        if(!count($this->newBundles)){
            return false;
        }

        return true;
    }

    protected function getNewBundle($bundleComposer)
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

            $extra = $bundle->getExtra();

            if($extra && isset($extra['campaignchain'])){
                if(isset($extra['campaignchain']['kernel'])){
                    $this->kernelConfig->addClasses($extra['campaignchain']['kernel']['classes']);
                }
                if(isset($extra['campaignchain']['kernel']['routing'])){
                    $this->kernelConfig->addRouting($extra['campaignchain']['kernel']['routing']);
                }
                $configFile = dirname($bundleComposer).DIRECTORY_SEPARATOR.
                    'Resources'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.
                    'config.yml';
                if(file_exists($configFile)){
                    /*
                     * Make the absolute config file path relative to
                     * app/config/config.yml.
                     */
                    $symfonyRoot = str_replace('app','',
                        $this->container->get('kernel')->getRootDir()
                    );
                    $configFile = '..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.
                        str_replace($symfonyRoot, '', $configFile);

                    $this->kernelConfig->addConfig($configFile);
                }
            }

            // Set the version of the installed bundle.
            $version = $this->packageService->getVersion($bundle->getName());
            $bundle->setVersion($version);

            // Set relative path of bundle.
            $bundle->setPath(
                // Remove the root directory to get the relative path
                str_replace($this->root.DIRECTORY_SEPARATOR, '',
                    // Remove the composer file from the path
                    str_replace(DIRECTORY_SEPARATOR.'composer.json', '', $bundleComposer)
                )
            );

            // Check whether this bundle has already been installed
            switch($this->isRegisteredBundle($bundle)){
                case self::STATUS_REGISTERED_NO:
                    $this->newBundles[] = $bundle;
                    return true;
                case self::STATUS_REGISTERED_OLDER:
                    // Get the existing bundle.
                    $registeredBundle = $this->em
                        ->getRepository('CampaignChainCoreBundle:Bundle')
                        ->findOneByName($bundle->getName());
                    // Update the existing bundle's data.
                    $registeredBundle->setDescription($bundle->getDescription());
                    $registeredBundle->setLicense($bundle->getLicense());
                    $registeredBundle->setAuthors($bundle->getAuthors());
                    $registeredBundle->setHomepage($bundle->getHomepage());
                    $registeredBundle->setVersion($bundle->getVersion());

                    $this->newBundles[] = $registeredBundle;

                    return true;
                case self::STATUS_REGISTERED_SAME:
                    return false;
            }
        } else {
            // TODO: Throw exception if file does not exist?
            return false;
        }
    }

    private function registerHook($params)
    {
        if(is_array($params['hooks']) && count($params['hooks'])){
            foreach($params['hooks'] as $identifier => $hookParams){
                $hook = new Hook();
                $hook->setIdentifier($identifier);
                $hook->setBundle($this->newBundle);
                $hook->setServices($hookParams['services']);
                $hook->setType($hookParams['type']);
                $hook->setLabel($hookParams['label']);
                $this->newBundle->addHook($hook);
            }
        }
    }

    private function registerDistribution($params)
    {
        /*
         * If a system entry already exists (e.g. from sample
         * data), then update it.
         */
        $system = $this->em->getRepository('CampaignChainCoreBundle:System')->find(1);

        if(!$system){
            $system = new System();
        }
        $system->setName($params['name']);
        if(isset($params['version']) && !empty($params['version'])){
            $system->setVersion($params['version']);
        }
        $system->setHomepage($this->newBundle->getHomepage());
        if(isset($params['logo']) && !empty($params['logo'])){
            $logoPath = $this->container->get('templating.helper.assets')
                ->getUrl($params['assets_path'].$params['logo'],null);
            $system->setLogo($logoPath);
        }
        if(isset($params['icon']) && !empty($params['icon'])){
            $iconPath = $this->container->get('templating.helper.assets')
                ->getUrl($params['assets_path'].$params['icon'],null);
            $system->setIcon($iconPath);
        }
        if(isset($params['style']) && !empty($params['style'])){
            $cssPath = $this->container->get('templating.helper.assets')
                ->getUrl($params['assets_path'].$params['style'],null);
            $system->setStyle($cssPath);
        }

        $system->setModules($params['modules']);

        $this->em->persist($system);
    }

    private function registerModule($params){
        // General installation routine.
        if(is_array($params['modules']) && count($params['modules'])){

            $module = null;

            foreach($params['modules'] as $identifier => $moduleParams){

                /*
                 * TODO: Detect whether a module has previously been registered
                 * for this bundle, but the module is not anymore defined in
                 * its campaignchain.yml.
                 */
                if($this->isRegisteredBundle($this->newBundle)
                    == self::STATUS_REGISTERED_OLDER){
                    $module = $this->em
                        ->getRepository('CampaignChainCoreBundle:Module')
                        ->findOneByIdentifier(strtolower($identifier));
                }

                if(!$module){
                    switch($this->newBundle->getType()){
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
                    $entityClass = 'CampaignChain\\CoreBundle\\Entity\\'.$moduleEntity;
                    $module = new $entityClass();
                    $module->setIdentifier(strtolower($identifier));
                    $module->setBundle($this->newBundle);
                }

                $module->setDisplayName($moduleParams['display_name']);
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
                    $this->systemParams = array_merge($this->systemParams, $moduleParams['system']);
                }

                // Process the params specific to a module type.

                // Params that must be defined for Operation modules
                if($this->newBundle->getType() == 'campaignchain-operation' && !isset($moduleParams['params']['owns_location'])){
                    throw new \Exception("You must set the 'owns_location' parameter in campaignchain.yml to 'true' or 'false' for module '".$identifier."' in bundle '".$this->newBundle->getName()."'.");
                }

                if(isset($moduleParams['params'])){
                    $module->setParams($moduleParams['params']);
                }

                // Add new module to new bundle.
                $reflect = new \ReflectionClass($module);
                $addModuleMethod = 'add'.$reflect->getShortName();
                $this->newBundle->$addModuleMethod($module);

                // If an activity module, remember the related channels.
                if($this->newBundle->getType() == 'campaignchain-activity'){
                    $this->activityChannels[$this->newBundle->getName()][$module->getIdentifier()] = $moduleParams['channels'];
                }
            }
        }
    }

    private function registerModuleSystemParams()
    {
        // Store the system parameters.
        if(count($this->systemParams)){
            foreach($this->systemParams as $key => $params){
                switch($key){
                    case 'navigation':
                        /*
                         * If a system entry already exists, then update it. Otherwise, create a new one.
                         */
                        $system = $this->em->getRepository('CampaignChainCoreBundle:System')->find(1);

                        if(!$system){
                            $system = new System();
                        }
                        $system->setNavigation($params);
                        break;
                }
            }

            $this->em->flush();
        }
    }

    private function registerActivityChannels()
    {
        if(count($this->activityChannels)){
            foreach($this->activityChannels as $activityBundleIdentifier => $activityModules){
                $activityBundle = $this->em->getRepository('CampaignChainCoreBundle:Bundle')
                    ->findOneByName($activityBundleIdentifier);

                foreach($activityModules as $activityModuleIdentifier => $activityModuleChannels){
                    $activityModule = $this->em->getRepository('CampaignChainCoreBundle:ActivityModule')
                        ->findOneBy(array(
                            'bundle' => $activityBundle,
                            'identifier' => $activityModuleIdentifier
                        ));
                    /*
                     * If an updated bundle, then we grab the existing
                     * Activity/Channel relationships.
                     */
                    if($this->isRegisteredBundle($activityBundle)
                        == self::STATUS_REGISTERED_OLDER){
                        $existingChannelModules = $activityModule->getChannelModules();
                    }
                    foreach($activityModuleChannels as $channelURI){
                        $channelURISplit = explode('/', $channelURI);
                        $channelBundleIdentifier = $channelURISplit[0].'/'.$channelURISplit[1];
                        $channelModuleIdentifier = $channelURISplit[2];
                        $channelBundle = $this->em->getRepository('CampaignChainCoreBundle:Bundle')
                            ->findOneByName($channelBundleIdentifier);
                        $channelModule = $this->em->getRepository('CampaignChainCoreBundle:ChannelModule')
                            ->findOneBy(array(
                                'bundle' => $channelBundle,
                                'identifier' => $channelModuleIdentifier
                            ));

                        /*
                         * If an updated bundle, then do nothing for an existing
                         * Activity/Channel relationship.
                         *
                         * TODO: Check if existing relationship has been removed
                         * from campaignchain.yml and throw error.
                         */
                        if($this->isRegisteredBundle($activityBundle)
                            == self::STATUS_REGISTERED_OLDER
                            ){
                            $qb = $this->em->getRepository('CampaignChainCoreBundle:ChannelModule')
                                ->createQueryBuilder('cm');
                            $qb->join('cm.activityModules', 'am')
                                ->where('am.id = :activityModule')
                                ->setParameter('activityModule', $activityModule->getId());
                            $query = $qb->getQuery();
                            $result = $query->getResult();

                            if(count($result) && $result[0]->getIdentifier() == $channelModule->getIdentifier()){
                                continue;
                            }
                        }

                        // Map activity and channel.
                        $activityModule->addChannelModule($channelModule);
                        $this->em->persist($activityModule);
                    }
                }
            }

            $this->em->flush();
        }
    }

    public function getAll()
    {
        if(!$this->repositoryService->loadRepositories()){
            return Repository::STATUS_NO_REPOSITORIES;
        }

        $modules = $this->repositoryService->getModules();

        // Is a higher version of an already installed package available?
        $packageService = $this->container->get('campaignchain.core.module.package');
        foreach($modules as $key => $module) {
            $version = $packageService->getVersion($module->name);

            if(!$version){
                // Not installed at all.
                unset($modules[$key]);
            } elseif(version_compare($version, $module->version, '<')){
                // Older version installed.
                $modules[$key]->hasUpdate = true;
                $modules[$key]->versionInstalled = $version;
            } else {
                $modules[$key]->hasUpdate = false;
                $modules[$key]->versionInstalled = $version;
            }
        }

        return $modules;
    }

    public function getUpdates()
    {
        if(!$this->repositoryService->loadRepositories()){
            return Repository::STATUS_NO_REPOSITORIES;
        }

        $modules = $this->repositoryService->getModules();

        // Is a higher version of an already installed package available?
        $packageService = $this->container->get('campaignchain.core.module.package');
        foreach($modules as $key => $module) {
            $version = $packageService->getVersion($module->name);

            if(!$version){
                // Not installed at all.
                unset($modules[$key]);
            } elseif(version_compare($version, $module->version, '<')){
                // Older version installed.
                $modules[$key]->versionInstalled = $version;
            } else {
                unset($modules[$key]);
            }
        }

        return $modules;
    }

    public function getInstalls()
    {
        if(!$this->repositoryService->loadRepositories()){
            return Repository::STATUS_NO_REPOSITORIES;
        }

        $modules = $this->repositoryService->getModules();

        // Is the package already installed? If yes, is a higher version available?
        $packageService = $this->container->get('campaignchain.core.module.package');
        foreach($modules as $key => $module) {
            $version = $packageService->getVersion($module->name);
            // Not installed yet.
            if($version){
                // Older version installed.
                unset($modules[$key]);
            }
        }

        return $modules;
    }
}