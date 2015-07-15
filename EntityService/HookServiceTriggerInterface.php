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

/**
 * Interface HookServiceDefaultInterface
 *
 * @author Sandro Groganz <sandro@campaignchain.com>
 * @package CampaignChain\CoreBundle\EntityService
 */
interface HookServiceTriggerInterface extends HookServiceDefaultInterface
{
    /**
     * @return string The hook's start date field attribute name as specified in the respective form type.
     */
    public function getStartDateIdentifier();

    /**
     * @return string The hook's end date field attribute name as specified in the respective form type.
     */
    public function getEndDateIdentifier();
}