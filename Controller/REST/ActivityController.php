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
 * @REST\NamePrefix("campaignchain_core_rest_activity_")
 *
 * @package CampaignChain\CoreBundle\Controller\REST
 */
class ActivityController extends BaseController
{
    /**
     * Get one specific Activity.
     *
     * Example Request
     * ===============
     *
     *      GET /api/v1/packages/vendors/campaignchain/projects/location-facebook
     *
     * Example Response
     * ================
     *
    [
        {
            "id": 13,
            "packageType": "campaignchain-location",
            "composerPackage": "campaignchain/location-facebook",
            "description": "Facebook user and page stream.",
            "license": "Apache-2.0",
            "authors": {
                "name": "CampaignChain, Inc.",
                "email": "info@campaignchain.com\""
            },
            "homepage": "http://www.campaignchain.com",
            "version": "dev-master",
            "createdDate": "2015-11-26T11:08:29+0000"
        }
    ]
     *
     * @ApiDoc(
     *  section="Core",
     *  requirements={
     *      {
     *          "name"="id",
     *          "requirement"="\d+"
     *      }
     *  }
     * )
     *
     * @REST\NoRoute() // We have specified a route manually.
     *
     * @param string $id The ID of an Activity, e.g. '42'.
     *
     * @return CampaignChain\CoreBundle\Entity\Bundle
     */
    public function getActivitiesAction($id)
    {
        $qb = $this->getQueryBuilder();
        $qb->select('a');
        $qb->from('CampaignChain\CoreBundle\Entity\Activity', 'a');
        $qb->where('a.id = :activity');
        $qb->setParameter('activity', $id);
        $query = $qb->getQuery();

        return $this->response(
            $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        );
    }
}