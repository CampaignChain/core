<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Module;

use CampaignChain\CoreBundle\Entity\ActivityModule;
use CampaignChain\CoreBundle\Entity\Bundle;
use CampaignChain\CoreBundle\Entity\CampaignModule;
use CampaignChain\CoreBundle\Entity\CampaignModuleConversion;
use CampaignChain\CoreBundle\Entity\ChannelModule;
use CampaignChain\CoreBundle\Entity\Hook;
use CampaignChain\CoreBundle\Entity\Module;
use CampaignChain\CoreBundle\Entity\System;
use CampaignChain\CoreBundle\EntityService\SystemService;
use CampaignChain\CoreBundle\Util\CommandUtil;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Installer.
 */
class Installer
{
    const STATUS_REGISTERED_NO = 'Not registered';
    const STATUS_REGISTERED_SAME = 'Same version already registered';
    const STATUS_REGISTERED_OLDER = 'Older version registered';

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var BundleConfig
     */
    private $bundleConfigService;

    /**
     * @var SystemService
     */
    private $systemService;

    /**
     * @var array
     */
    private $campaignConversions = [];

    /**
     * @var Kernel
     */
    private $kernelService;

    /**
     * @var Package
     */
    private $packageService;

    /**
     * @var CommandUtil
     */
    private $command;

    /**
     * @var Repository
     */
    private $repositoryService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $systemParams = [];

    /**
     * @var array
     */
    private $activityChannels = [];

    /**
     * Specifies which routes must be defined by which type of module.
     *
     * @var array
     */
    private $requiredRoutes = [
        'campaignchain-channel' => [
            'new',
        ],
        'campaignchain-activity' => [
            'new',
            'edit',
            'edit_modal',
            'edit_api',
            'read',
        ],
        'campaignchain-campaign' => [
            'new',
            'edit',
            'edit_modal',
            'edit_api',
            'plan',
            'plan_detail',
        ],
        'campaignchain-milestone' => [
            'new',
            'edit',
            'edit_modal',
            'edit_api',
        ],
    ];

    /**
     * Installer constructor.
     *
     * @param EntityManager   $em
     * @param BundleConfig    $bundleConfigService
     * @param string          $rootDir
     * @param SystemService   $systemService
     * @param Kernel          $kernel
     * @param Package         $packageService
     * @param CommandUtil     $commandUtil
     * @param Repository      $repository
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManager $em,
        BundleConfig $bundleConfigService,
        $rootDir,
        SystemService $systemService,
        Kernel $kernel,
        Package $packageService,
        CommandUtil $commandUtil,
        Repository $repository,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->bundleConfigService = $bundleConfigService;
        $this->rootDir = $rootDir.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
        $this->systemService = $systemService;
        $this->kernelService = $kernel;
        $this->packageService = $packageService;
        $this->command = $commandUtil;
        $this->logger = $logger;
        $this->repositoryService = $repository;
    }

    /**
     * Get module versions from CampaignChain.
     *
     * @return array|string
     */
    public function getAll()
    {
        if (!$this->repositoryService->loadRepositories()) {
            return Repository::STATUS_NO_REPOSITORIES;
        }

        $modules = $this->repositoryService->getModules();

        // Is a higher version of an already installed package available?
        foreach ($modules as $key => $module) {
            $version = $this->packageService->getVersion($module->name);

            if (!$version) {
                // Not installed at all.
                unset($modules[$key]);
            } elseif (version_compare($version, $module->version, '<')) {
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

    /**
     * Get updates from CampaignChain.
     *
     * @return array|string
     */
    public function getUpdates()
    {
        if (!$this->repositoryService->loadRepositories()) {
            return Repository::STATUS_NO_REPOSITORIES;
        }

        $modules = $this->repositoryService->getModules();

        // Is a higher version of an already installed package available?
        foreach ($modules as $key => $module) {
            $version = $this->packageService->getVersion($module->name);

            if (!$version) {
                // Not installed at all.
                unset($modules[$key]);
            } elseif (version_compare($version, $module->version, '<')) {
                // Older version installed.
                $modules[$key]->versionInstalled = $version;
            } else {
                unset($modules[$key]);
            }
        }

        return $modules;
    }

    /**
     * Get modules that needs to be installed.
     *
     * @return array|string
     */
    public function getInstalls()
    {
        if (!$this->repositoryService->loadRepositories()) {
            return Repository::STATUS_NO_REPOSITORIES;
        }

        $modules = $this->repositoryService->getModules();

        // Is the package already installed? If yes, is a higher version available?
        foreach ($modules as $key => $module) {
            $version = $this->packageService->getVersion($module->name);
            // Not installed yet.
            if ($version) {
                // Older version installed.
                unset($modules[$key]);
            }
        }

        return $modules;
    }

    /**
     * @param SymfonyStyle|null $io
     * @param bool              $updateDatabase
     *
     * @return bool
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function install(SymfonyStyle $io = null, $updateDatabase = true)
    {
        $this->logger->info('START: MODULES INSTALLER');

        // Load schemas of entities into database
        if ($updateDatabase) {
            $output = $this->command->doctrineSchemaUpdate();

            $this->logger->info('Output of doctrine:schema:update --force');
            $this->logger->info($output);
        }

        $newBundles = $this->bundleConfigService->getNewBundles();

        if (empty($newBundles)) {
            if ($io) {
                $io->success('No new modules found.');
            }
            $this->logger->info('No new modules found.');
            $this->logger->info('END: MODULES INSTALLER');

            return false;
        }

        // Increase timeout limit to run this script.
        set_time_limit(240);

        $this->kernelService->parseBundlesForKernelConfig($newBundles);

        $loggerResult = '';

        $this->em->getConnection()->beginTransaction();

        $this->registerDistribution();

        try {
            foreach ($newBundles as $newBundle) {
                switch ($newBundle->getType()) {
                    case 'campaignchain-core':
                        break;
                    case 'campaignchain-hook':
                        // TODO: new vs. update
                        $this->registerHook($newBundle);
                        break;
                    default:
                        $this->registerModule($newBundle);
                        break;
                }

                $loggerResult .= $newBundle->getName().', ';

                $this->em->persist($newBundle);
            }
            $this->em->flush();

            // Store the campaign types a campaign can be copied to.
            $this->registerCampaignConversions();

            // Store the channels related to an activity.
            $this->registerActivityChannels();

            // Store the system parameters injected by modules.
            $this->registerModuleSystemParams();

            // Register any new Bundles in the Symfony kernel.
            $this->kernelService->register();

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            if ($io) {
                $io->error('Error at update: '.$e->getMessage());
            }

            $this->logger->error('Error: '.$e->getMessage());
            $this->logger->info('END: MODULES INSTALLER');

            throw $e;
        }

        $this->logger->info('Installed/updated modules: '.rtrim($loggerResult, ', '));
        if ($io) {
            $io->section('Installed/updated modules:');
            $io->listing(explode(', ', rtrim($loggerResult, ', ')));
        }

        if ($io) {
            $io->listing(['Clearing the cache']);
        }
        // Load schemas of entities into database
        $output = $this->command->clearCache(false);
        $this->logger->info('Output of cache:clear --no-warmup');
        $this->logger->info($output);

        if ($io) {
            $io->listing(['Installing assets']);
        }
        // Install assets to web/ directory and dump assetic files.
        $output = $this->command->assetsInstallWeb();
        $this->logger->info('Output of assets:install web');
        $this->logger->info($output);

        if ($io) {
            $io->listing(['Dumping assets']);
        }
        // app/console assetic:dump --no-debug
        $output = $this->command->asseticDump();
        $this->logger->info('Output of assetic:dump --no-debug');
        $this->logger->info($output);

        if ($updateDatabase) {
            if ($io) {
                $io->listing(['Update DB']);
            }
            // Load schemas of entities into database
            $output = $this->command->doctrineSchemaUpdate();
            $this->logger->info('Output of doctrine:schema:update --force');
            $this->logger->info($output);
        }

        if ($io) {
            $io->listing(['Installing bower']);
        }
        // Install or update bower JavaScript libraries.
        $output = $this->command->bowerInstall();
        $this->logger->info('Output of sp:bower:install');
        $this->logger->info($output);

        $this->logger->info('END: MODULES INSTALLER');

        return true;
    }

    /**
     * Updates the system descriptions.
     */
    private function registerDistribution()
    {
        /*
         * If a system entry already exists (e.g. from sample
         * data), then update it.
         */
        $system = $this->systemService->getActiveSystem();

        if (!$system) {
            $system = new System();
        }

        $config = $this->rootDir.'composer.json';
        $configContent = file_get_contents($config);
        $params = json_decode($configContent, true);

        $system->setPackage($params['name']);
        $system->setName($params['description']);
        $system->setVersion($params['version']);
        $system->setHomepage($params['homepage']);
        $system->setModules($params['extra']['campaignchain']['distribution']['modules']);

        if (
            isset($params['extra']['campaignchain']['distribution']['terms-url']) &&
            !empty($params['extra']['campaignchain']['distribution']['terms-url'])
        ) {
            $system->setTermsUrl($params['extra']['campaignchain']['distribution']['terms-url']);
        }

        $this->em->persist($system);
        $this->em->flush();
    }

    /**
     * @param Bundle $bundle
     */
    private function registerHook(Bundle $bundle)
    {
        $params = $this->getModule($this->rootDir.$bundle->getPath().DIRECTORY_SEPARATOR.'campaignchain.yml');

        if (!is_array($params['hooks']) || !count($params['hooks'])) {
            return;
        }

        foreach ($params['hooks'] as $identifier => $hookParams) {
            if ($bundle->getStatus()) {
                $status = $bundle->getStatus();
            } else {
                $status = $this->bundleConfigService->isRegisteredBundle($bundle);
            }

            // Check whether this Hook has already been installed
            switch ($status) {
                case self::STATUS_REGISTERED_NO :
                    $hook = new Hook();
                    $hook->setIdentifier($identifier);
                    $hook->setBundle($bundle);
                    break;
                case self::STATUS_REGISTERED_OLDER :
                    // Get the existing bundle.
                    $hook = $this->em
                        ->getRepository('CampaignChainCoreBundle:Hook')
                        ->ç(strtolower($identifier));
                    break;
                case self::STATUS_REGISTERED_SAME :
                    continue;
            }

            $hook->setServices($hookParams['services']);
            $hook->setType($hookParams['type']);
            $hook->setLabel($hookParams['label']);
            $bundle->addHook($hook);
        }
    }

    /**
     * @param $moduleConfig
     *
     * @return array
     */
    protected function getModule($moduleConfig)
    {
        if (!file_exists($moduleConfig)) {
            return [];
        }

        $moduleConfigContent = file_get_contents($moduleConfig);

        return Yaml::parse($moduleConfigContent);
    }

    /**
     * @param Bundle $bundle
     *
     * @throws \Exception
     */
    private function registerModule(Bundle $bundle)
    {
        $params = $this->getModule($this->rootDir.$bundle->getPath().DIRECTORY_SEPARATOR.'campaignchain.yml');

        // General installation routine.
        if (!is_array($params['modules']) || !count($params['modules'])) {
            return;
        }

        foreach ($params['modules'] as $identifier => $moduleParams) {
            $module = null;

            if ($bundle->getStatus()) {
                $status = $bundle->getStatus();
            } else {
                $status = $this->bundleConfigService->isRegisteredBundle($bundle);
            }

            /*
             * TODO: Detect whether a module has previously been registered
             * for this bundle, but the module is not anymore defined in
             * its campaignchain.yml.
             */
            if ($status == self::STATUS_REGISTERED_OLDER) {
                $module = $this->em
                    ->getRepository('CampaignChainCoreBundle:Module')
                    ->findOneBy(
                        [
                            'bundle' => $bundle,
                            'identifier' => strtolower($identifier),
                        ]
                    );
            }

            if (!$module) {
                switch ($bundle->getType()) {
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
                /** @var Module $module */
                $module = new $entityClass();
                $module->setIdentifier(strtolower($identifier));
                $module->setBundle($bundle);
            }

            $module->setDisplayName($moduleParams['display_name']);

            if (isset($moduleParams['description'])) {
                $module->setDescription($moduleParams['description']);
            }

            // Verify routes.
            if (in_array(
                $bundle->getType(),
                ['campaignchain-activity', 'campaignchain-channel', 'campaignchain-campaign', 'campaignchain-milestone']
            )) {
                // Throw error if no routes defined.
                if (
                    !isset($moduleParams['routes'])
                    || !is_array($moduleParams['routes'])
                    || !count($moduleParams['routes'])
                ) {
                    throw new \Exception(
                        'The module "'.$identifier
                        .'" in bundle "'.$bundle->getName().'"'
                        .' does not provide any of the required routes.'
                    );
                } else {
                    // Throw error if one or more routes are missing.
                    $hasMissingRoutes = false;
                    $missingRoutes = '';

                    foreach ($this->requiredRoutes[$bundle->getType()] as $requiredRoute) {
                        if (!array_key_exists($requiredRoute, $moduleParams['routes'])) {
                            $hasMissingRoutes = true;
                            $missingRoutes .= $requiredRoute.', ';
                        }
                    }

                    if ($hasMissingRoutes) {
                        throw new \Exception(
                            'The module "'.$identifier
                            .'" in bundle "'.$bundle->getName().'"'
                            .' must define the following route(s): '
                            .rtrim($missingRoutes, ', ').'.'
                        );
                    } else {
                        $module->setRoutes($moduleParams['routes']);
                    }
                }
            } elseif (isset($moduleParams['routes']) && is_array($moduleParams['routes']) && count(
                    $moduleParams['routes']
                )
            ) {
                $module->setRoutes($moduleParams['routes']);
            }

            if (isset($moduleParams['services']) && is_array($moduleParams['services']) && count(
                    $moduleParams['services']
                )
            ) {
                $module->setServices($moduleParams['services']);
            }
            if (isset($moduleParams['hooks']) && is_array($moduleParams['hooks']) && count($moduleParams['hooks'])) {
                // TODO: Check that there's only 1 trigger hook included.
                $module->setHooks($moduleParams['hooks']);
            }
            if (isset($moduleParams['system']) && is_array($moduleParams['system']) && count($moduleParams['system'])) {
                $this->systemParams[] = $moduleParams['system'];
            }
            // Are metrics for the reports defined?
            if (isset($moduleParams['metrics']) && is_array($moduleParams['metrics']) && count(
                    $moduleParams['metrics']
                )
            ) {
                foreach ($moduleParams['metrics'] as $metricType => $metricNames) {
                    switch ($metricType) {
                        case 'activity':
                            $metricClass = 'ReportAnalyticsActivityMetric';
                            break;
                        case 'location':
                            $metricClass = 'ReportAnalyticsLocationMetric';
                            break;
                        default:
                            throw new \Exception(
                                "Unknown metric type '".$metricType."'."
                                ."Pick 'activity' or 'location' instead."
                            );
                            break;
                    }
                    foreach ($metricNames as $metricName) {
                        $metric = $this->em->getRepository('CampaignChainCoreBundle:'.$metricClass)
                            ->findOneBy(
                                [
                                    'name' => $metricName,
                                    'bundle' => $bundle->getName(),
                                ]
                            );

                        // Does the metric already exist?
                        if ($metric) {
                            /*
                             * Throw error if bundle is new and metric
                             * has already been registered.
                             */
                            if ($status == self::STATUS_REGISTERED_NO) {
                                throw new \Exception(
                                    "Metric '".$metricName."' of type '".$metricType."'"
                                    .' already exists for bundle '.$bundle->getName().'. '
                                    .'Please define another name '
                                    .'in campaignchain.yml of '.$bundle->getName().'.'
                                );
                            }
                            // Skip if same or older version of bundle.
                            continue;
                        } else {
                            // Create new metric.
                            $metricNamespacedClass = 'CampaignChain\\CoreBundle\\Entity\\'.$metricClass;
                            $metric = new $metricNamespacedClass();
                            $metric->setName($metricName);
                            $metric->setBundle($bundle->getName());
                            $this->em->persist($metric);
                        }
                    }
                }
            }

            // Process the params specific to a module type.

            // Params that must be defined for Operation modules
            if ($bundle->getType() == 'campaignchain-operation' && !isset($moduleParams['params']['owns_location'])) {
                throw new \Exception(
                    "You must set the 'owns_location' parameter in campaignchain.yml to 'true' or 'false' for module '".$identifier."' in bundle '".$bundle->getName(
                    )."'."
                );
            }

            if (isset($moduleParams['params'])) {
                $module->setParams($moduleParams['params']);
            }

            // Add new module to new bundle.
            $reflect = new \ReflectionClass($module);
            $addModuleMethod = 'add'.$reflect->getShortName();
            $bundle->$addModuleMethod($module);

            // If a campaign module, remember the conversion to other campaign types.
            if (
                $bundle->getType() == 'campaignchain-campaign' &&
                isset($moduleParams['conversions']) &&
                is_array($moduleParams['conversions']) &&
                count($moduleParams['conversions'])
            ) {
                $this->campaignConversions[$bundle->getName()][$module->getIdentifier()] = $moduleParams['conversions'];
            }

            // If an activity module, remember the related channels.
            if (
                $bundle->getType() == 'campaignchain-activity' &&
                isset($moduleParams['channels']) &&
                is_array($moduleParams['channels'])
            ) {
                $this->activityChannels[$bundle->getName()][$module->getIdentifier()] = $moduleParams['channels'];
            }
        }
    }

    /**
     * Register campaign conversions.
     */
    private function registerCampaignConversions()
    {
        if (!count($this->campaignConversions)) {
            return;
        }

        foreach ($this->campaignConversions as $campaignBundleName => $campaignModules) {
            $campaignBundle = $this->em->getRepository('CampaignChainCoreBundle:Bundle')
                ->findOneByName($campaignBundleName);

            foreach ($campaignModules as $campaignModuleIdentifier => $conversionURIs) {
                $fromCampaignModule = $this->em
                    ->getRepository('CampaignChainCoreBundle:CampaignModule')
                    ->findOneBy(
                        [
                            'bundle' => $campaignBundle,
                            'identifier' => $campaignModuleIdentifier,
                        ]
                    );

                foreach ($conversionURIs as $conversionURI) {
                    $conversionURISplit = explode('/', $conversionURI);
                    $toCampaignBundleName = $conversionURISplit[0].'/'.$conversionURISplit[1];
                    $toCampaignModuleIdentifier = $conversionURISplit[2];
                    $toCampaignBundle = $this->em->getRepository('CampaignChainCoreBundle:Bundle')
                        ->findOneByName($toCampaignBundleName);
                    $toCampaignModule = $this->em->getRepository('CampaignChainCoreBundle:CampaignModule')
                        ->findOneBy(
                            [
                                'bundle' => $toCampaignBundle,
                                'identifier' => $toCampaignModuleIdentifier,
                            ]
                        );

                    $campaignModuleConversion = new CampaignModuleConversion();
                    $campaignModuleConversion->setFrom($fromCampaignModule);
                    $campaignModuleConversion->setTo($toCampaignModule);
                    $this->em->persist($campaignModuleConversion);
                }
            }
        }
    }

    /**
     * Register activity Channels.
     */
    private function registerActivityChannels()
    {
        if (!count($this->activityChannels)) {
            return;
        }

        foreach ($this->activityChannels as $activityBundleIdentifier => $activityModules) {
            $activityBundle = $this->em->getRepository('CampaignChainCoreBundle:Bundle')
                ->findOneByName($activityBundleIdentifier);

            foreach ($activityModules as $activityModuleIdentifier => $activityModuleChannels) {
                $activityModule = $this->em->getRepository('CampaignChainCoreBundle:ActivityModule')
                    ->findOneBy(
                        [
                            'bundle' => $activityBundle,
                            'identifier' => $activityModuleIdentifier,
                        ]
                    );

                foreach ($activityModuleChannels as $channelURI) {
                    $channelURISplit = explode('/', $channelURI);
                    $channelBundleIdentifier = $channelURISplit[0].'/'.$channelURISplit[1];
                    $channelModuleIdentifier = $channelURISplit[2];
                    $channelBundle = $this->em->getRepository('CampaignChainCoreBundle:Bundle')
                        ->findOneByName($channelBundleIdentifier);
                    $channelModule = $this->em->getRepository('CampaignChainCoreBundle:ChannelModule')
                        ->findOneBy(
                            [
                                'bundle' => $channelBundle,
                                'identifier' => $channelModuleIdentifier,
                            ]
                        );

                    /*
                     * If an updated bundle, then do nothing for an existing
                     * Activity/Channel relationship.
                     *
                     * TODO: Check if existing relationship has been removed
                     * from campaignchain.yml and throw error.
                     */
                    if ($this->bundleConfigService->isRegisteredBundle($activityBundle) == self::STATUS_REGISTERED_OLDER) {
                        $registeredModules = $this->em->getRepository('CampaignChainCoreBundle:ChannelModule')
                            ->findRegisteredModulesByActivityModule($activityModule);

                        if (count($registeredModules) && $registeredModules[0]->getIdentifier(
                            ) == $channelModule->getIdentifier()
                        ) {
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

    /**
     * Store a module's system parameters.
     */
    private function registerModuleSystemParams()
    {
        if (!count($this->systemParams)) {
            return;
        }
        /*
         * If a system entry already exists, then update it. Otherwise,
         * create a new one.
         */
        $system = $this->systemService->getActiveSystem();

        if (!$system) {
            $system = new System();
            $system->setNavigation([]);

            $this->em->persist($system);
        }

        if (!is_array($system->getNavigation())) {
            $system->setNavigation([]);
        }

        foreach ($this->systemParams as $moduleParams) {
            foreach ($moduleParams as $key => $params) {
                switch ($key) {
                    case 'navigation':
                        // Merge existing navigations.
                        $navigation = array_merge_recursive($system->getNavigation(), $params);

                        $system->setNavigation($navigation);
                        break;
                }
            }
        }

        $this->em->flush();
    }
}
