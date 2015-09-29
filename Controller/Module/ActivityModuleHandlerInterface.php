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

use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\CoreBundle\Entity\Operation;

/**
 * Interface ActivityModuleHandlerInterface
 *
 * @author Sandro Groganz <sandro@campaignchain.com>
 * @package CampaignChain\CoreBundle\Controller\Module
 */
interface ActivityModuleHandlerInterface
{
    public function getOperationDetail(Location $location, Operation $operation = null);

    public function processOperationDetail(Operation $operation, $data);

    public function readOperationDetail(Operation $operation);
}