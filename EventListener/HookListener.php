<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;

class HookListener implements EventSubscriberInterface
{
    protected $em;
    protected $container;
    protected $builder;

    protected $bundle;
    protected $entityModule;
    protected $hooks;
    private $view = 'default';
    private $campaign = null;
    private $hooksOptions = array();

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function setView($view){
        $this->view = $view;
    }

    public function setHooksOptions(array $hooksOptions){
        $this->hooksOptions = $hooksOptions;
    }

    public function setCampaign($campaign){
        $this->campaign = $campaign;
    }

    public function init($builder, $bundleName, $configIdentifier)
    {
        $this->builder = $builder;

        $this->bundle = $this->em->getRepository('CampaignChainCoreBundle:Bundle')->findOneByName($bundleName);
        switch($this->bundle->getType()){
            case 'campaignchain-location':
                $repositoryName = 'CampaignChainCoreBundle:LocationModule';
                break;
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
                die('No repository name defined for bundle type "'.$this->bundle->getType().'".');
                break;
        }

        $this->entityModule = $this->em->getRepository($repositoryName)->findOneBy(array(
            'bundle' => $this->bundle,
            'identifier' => $configIdentifier,
        ));
        $this->hooks = $this->entityModule->getHooks();
    }

    static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'onPreSetData',
//            FormEvents::PRE_SUBMIT   => 'onPreSubmit',
        );
    }

    public function onPreSetData(FormEvent $event)
    {
        $entity = $event->getData();
        $form = $event->getForm();

        // Set the association between entity and its module if the entity is new.
        if(!$entity || $entity->getId() === null){
            switch($this->bundle->getType()){
                case 'campaignchain-location':
                    $entity->setLocationModule($this->entityModule);
                    break;
                case 'campaignchain-campaign':
                    $entity->setCampaignModule($this->entityModule);
                    break;
                case 'campaignchain-milestone':
                    $entity->setMilestoneModule($this->entityModule);
                    break;
                case 'campaignchain-activity':
                    $entity->setActivityModule($this->entityModule);
                    break;
                default:
                    // TODO: Throw exception.
                    die('No relationship between entity and entity module defined for bundle type "'.$this->bundle->getType().'".');
                    break;
            }
        }

        if(
            isset($this->hooks[$this->view]) &&
            is_array($this->hooks[$this->view]) &&
            count($this->hooks[$this->view])
        ){
            foreach($this->hooks[$this->view] as $identifier => $active){
                if($active){
                    $hookConfig = $this->em->getRepository('CampaignChainCoreBundle:Hook')->findOneByIdentifier($identifier);
                    $hookForm = $this->container->get($hookConfig->getServices()['form']);
                    $hookForm->setView($this->view);
                    if(isset($this->hooksOptions[$identifier])){
                        $hookForm->setHooksOptions($this->hooksOptions[$identifier]);
                    }
                    switch($this->bundle->getType()){
                        case 'campaignchain-milestone':
                        case 'campaignchain-activity':
                            if(!$this->campaign){
                                // TODO: Throw exception.
                            } else {
                                $hookForm->setCampaign($this->campaign);
                            }
                            break;
                    }

                    $hookService = $this->container->get($hookConfig->getServices()['entity']);
                    $hookData = $hookService->getHook($entity);
                    $hookLabel = $hookConfig->getLabel();
                    $hookHelpText = null;

                    if(isset($this->hooksOptions[$hookConfig->getIdentifier()])){
                        $hookOptions = $this->hooksOptions[$hookConfig->getIdentifier()];
                        if(isset($hookOptions['label'])){
                            $hookLabel = $hookOptions['label'];
                        }
                        if(isset($hookOptions['help_text'])){
                            $hookHelpText = $hookOptions['help_text'];
                        }
                    }

                    $hookFormIdentifier = str_replace('-', '_', $hookConfig->getIdentifier());
                    $form->add('campaignchain_hook_'.$hookFormIdentifier, $hookForm, array(
                        'label' => $hookLabel,
                        'mapped' => false,
                        'data' => $hookData,
                        'attr' => array(
                            'id' => 'campaignchain_hook_'.$hookFormIdentifier,
                            'help_text' => $hookHelpText,
                        ),
                    ));

                    // Does the hook have a form listener?
                    if(isset($hookConfig->getServices()['event_subscriber'])){
                        $this->builder->addEventSubscriber($this->container->get('campaignchain.hook.listener.campaignchain.due'));
                    }
                }
            }
        }
    }
}