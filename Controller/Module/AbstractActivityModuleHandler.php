<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Controller\Module;

use CampaignChain\CoreBundle\Entity\Activity;
use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\CoreBundle\Entity\Operation;

/**
 * Class AbstractActivityModuleHandler
 * @package CampaignChain\CoreBundle\Controller\Module
 */
abstract class AbstractActivityModuleHandler
{
    abstract public function getOperationDetail(Location $location, Operation $operation = null);

    public function processActivity(Activity $activity, $data)
    {
        return $activity;
    }

    public function processOperationLocation(Location $location, $data)
    {
        return $location;
    }

    abstract public function processOperationDetails(Operation $operation, $data);

    abstract public function readOperationDetailsAction(Operation $operation);

    public function postPersistNewAction(Operation $operation)
    {
        return null;
    }

    public function preFormCreateEditAction(Operation $operation)
    {
        return null;
    }

    public function getRenderOptionsEditAction(Operation $operation)
    {
        return null;
    }

    public function preFormCreateEditModalAction(Operation $operation)
    {
        return null;
    }

    public function getRenderOptionsEditModalAction(Operation $operation)
    {
        return null;
    }

    public function hasOperationForm($view)
    {
        return true;
    }
}