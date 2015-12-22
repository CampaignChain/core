<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EntityService;

/**
 * Interface HookServiceDefaultInterface
 *
 * @author Sandro Groganz <sandro@campaignchain.com>
 * @package CampaignChain\CoreBundle\EntityService
 */
interface HookServiceDefaultInterface
{
    /**
     * @param $entity
     * @return object The hook object.
     */
    public function getHook($entity);

    /**
     * @param $entity
     * @param $hook
     * @return object The entity object.
     */
    public function processHook($entity, $hook);
}