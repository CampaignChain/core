<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Controller\REST;

use CampaignChain\CoreBundle\Util\VariableUtil;
use FOS\RestBundle\Controller\Annotations as REST;
use Symfony\Component\HttpFoundation\Session\Session;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Request\ParamFetcher;

/**
 * Class ChannelController
 *
 * @REST\NamePrefix("campaignchain_core_rest_location_")
 *
 * @package CampaignChain\CoreBundle\Controller\REST
 */
class LocationController extends BaseController
{
    const SELECT_STATEMENT = 'l.id, l.identifier AS remoteId, l.name, l.url, l.image, l.status, l.createdDate, l.modifiedDate, IDENTITY(l.operation) AS operationId';
}