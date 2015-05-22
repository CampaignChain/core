<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EntityService;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HookService
{
    protected $em;
    protected $container;
    protected $view = 'default';


    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function setView($view){
        $this->view = $view;
    }

    public function getHooks($bundleName, $configIdentifier){
        $bundle = $this->em->getRepository('CampaignChainCoreBundle:Bundle')->findOneByName($bundleName);
        switch($bundle->getType()){
            case 'campaignchain-campaign':
                $repositoryName = 'CampaignChainCoreBundle:CampaignModule';
                break;
            case 'campaignchain-milestone':
                $repositoryName = 'CampaignChainCoreBundle:MilestoneModule';
                break;
            case 'campaignchain-activity':
                $repositoryName = 'CampaignChainCoreBundle:ActivityModule';
                break;
            default:
                // TODO: Throw exception.
                die('No repository defined for bundle type "'.$bundle->getType().'"');
                break;
        }

        $config = $this->em->getRepository($repositoryName)->findOneBy(array(
            'bundle' => $bundle,
            'identifier' => $configIdentifier,
        ));

        return $config->getHooks()[$this->view];
    }

    // TODO: Do we have to pass the $repository object or could we use $this->em just as well?
    public function processHooks($bundleName, $configIdentifier, $entity, $data, $new = false)
    {
        $hooks = $this->getHooks($bundleName, $configIdentifier);

        if(is_array($hooks) && count($hooks)){
            foreach($hooks as $identifier => $active){
                if($active){
                    $hookModule = $this->em->getRepository('CampaignChainCoreBundle:Hook')->findOneByIdentifier($identifier);
                    $hookService = $this->container->get($hookModule->getServices()['entity']);

                    // If the entity is new and the hook is of type "trigger", then define it for the entity.
                    if($new && $hookModule->getType() == 'trigger'){
                        $entity->setTriggerHook($hookModule);
                    }

                    if(is_array($data)){
                        $hookDataArray = $data['campaignchain_hook_'.str_replace('-', '_', $identifier)];
                        $hook = $hookService->arrayToObject($hookDataArray);
                    } elseif(is_object($data) && get_class($data) == 'Symfony\Component\Form\Form'){
                        $hook = $data->get('campaignchain_hook_'.str_replace('-', '_', $identifier))->getData();
                    }
                    $entity = $hookService->processHook($entity, $hook);
                }
            }
        }

        return $entity;
    }

    public function getHook($identifier)
    {
        $hook = $this->em->getRepository('CampaignChainCoreBundle:Hook')->findOneByIdentifier($identifier);

        if(!$hook){
            throw new \Exception('No hook with identifier '.$identifier);
        }

        return $hook;
    }
}