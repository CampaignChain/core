<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class HookListenerType extends AbstractType
{
    protected $view = 'default';
    protected $bundleName;
    protected $moduleIdentifier;

    protected $em;
    protected $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function setBundleName($bundleName){
        $this->bundleName = $bundleName;
    }

    public function setModuleIdentifier($moduleIdentifier){
        $this->moduleIdentifier = $moduleIdentifier;
    }

    public function setView($view){
        $this->view = $view;
    }

    public function getHookListener($builder)
    {
        $hookListener = $this->container->get('campaignchain.core.listener.hook');
        // Initialize the hooks for this campaign.
        $hookListener->init($builder, $this->bundleName, $this->moduleIdentifier);
        $hookListener->setView($this->view);

        return $hookListener;
    }
}