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

namespace CampaignChain\CoreBundle\EntityService;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HookService
{
    protected $em;
    protected $container;
    protected $view = 'default';
    protected $errorCodes = array();
    protected $entity = null;

    public function __construct(ManagerRegistry $managerRegistry, ContainerInterface $container)
    {
        $this->em = $managerRegistry->getManager();
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
                    /** @var HookServiceDefaultInterface $hookService */
                    $hookService = $this->container->get($hookModule->getServices()['entity']);

                    // If the entity is new and the hook is of type "trigger", then define it for the entity.
                    if($new && $hookModule->getType() == 'trigger'){
                        $entity->setTriggerHook($hookModule);
                    }

                    $hasHookData = false;

                    if(is_array($data)){
                        // Check if data for specific Hook is available.
                        if(isset($data['campaignchain_hook_'.str_replace('-', '_', $identifier)])){
                            $hookDataArray = $data['campaignchain_hook_'.str_replace('-', '_', $identifier)];
                            $hook = $hookService->arrayToObject($hookDataArray);

                            $hasHookData = true;
                        }
                    } elseif(is_object($data) && get_class($data) == 'Symfony\Component\Form\Form'){
                        $hook = $data->get('campaignchain_hook_'.str_replace('-', '_', $identifier))->getData();

                        $hasHookData = true;
                    }

                    if($hasHookData){
                        if(!$hookService->processHook($entity, $hook)){
                            $this->addErrorCode($hookService->getErrorCodes());
                        }
                    }
                }
            }
        }

        // Post process entity per campaign.
        if(
            strpos(get_class($entity), 'CoreBundle\Entity\Campaign') === false &&
            $entity->getCampaign()->getCampaignModule()->getServices() &&
            is_array($entity->getCampaign()->getCampaignModule()->getServices())
        ){
            $campaignModuleServices = $entity->getCampaign()->getCampaignModule()->getServices();
            if(isset($campaignModuleServices['hook'])) {
                $campaignModuleService = $this->container->get($campaignModuleServices['hook']);
                $entity = $campaignModuleService->processAction($entity);
            }
        }

        $this->setEntity($entity);

        if($this->hasErrors()){
            return false;
        }

        return true;
    }

    public function getHook($identifier)
    {
        $hook = $this->em->getRepository('CampaignChainCoreBundle:Hook')->findOneByIdentifier($identifier);

        if(!$hook){
            throw new \Exception('No hook with identifier '.$identifier);
        }

        return $hook;
    }

    /**
     * @param $entity   An Action (Campaign, Activity, Milestone) or Medium
     *                  (Channel, Location).
     */
    protected function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @throws \Exception
     * @return object The entity object.
     */
    public function getEntity()
    {
        if($this->entity === null){
            throw new \Exception('Please execute processHook() first.');
        }

        return $this->entity;
    }

    protected function addErrorCode($errorCode)
    {
        $this->errorCodes = array_merge($this->errorCodes, $errorCode);
    }

    public function getErrorCodes()
    {
        return $this->errorCodes;
    }

    public function hasErrors()
    {
        return count($this->errorCodes);
    }
}