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
     *      GET /api/v1/activities/42
     *
     * Example Response
     * ================
     *
    [
        {
            "activity": {
                "id": 42,
                "equalsOperation": true,
                "name": "Announcement 11 on LinkedIn",
                "startDate": "2015-12-18T14:44:54+0000",
                "status": "open",
                "createdDate": "2015-12-14T11:02:23+0000"
            }
        },
        {
            "campaign": {
                "id": 3,
                "timezone": "Africa/Sao_Tome",
                "hasRelativeDates": false,
                "name": "Campaign 3",
                "startDate": "2015-10-30T23:09:57+0000",
                "endDate": "2016-04-23T14:18:03+0000",
                "status": "open",
                "createdDate": "2015-12-14T11:02:23+0000"
            }
        },
        {
            "location": {
                "id": 101,
                "identifier": "idW8ynCjb7",
                "image": "/bundles/campaignchainchannellinkedin/ghost_person.png",
                "url": "https://www.linkedin.com/pub/amariki-software/a1/455/616",
                "name": "Amariki Software",
                "status": "active",
                "createdDate": "2015-12-14T11:02:23+0000"
            }
        },
        {
            "operations": {
                "id": 72,
                "name": "Announcement 11 on LinkedIn",
                "startDate": "2015-12-18T14:44:54+0000",
                "status": "open",
                "createdDate": "2015-12-14T11:02:23+0000"
            }
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
        $qb->select('a AS activity, c AS campaign, o AS operations, l AS location');
        $qb->from('CampaignChain\CoreBundle\Entity\Activity', 'a');
        $qb->from('CampaignChain\CoreBundle\Entity\Campaign', 'c');
        $qb->from('CampaignChain\CoreBundle\Entity\Location', 'l');
        $qb->from('CampaignChain\CoreBundle\Entity\Operation', 'o');
        $qb->where('a.id = :activity');
        $qb->andWhere('a.id = o.activity');
        $qb->andWhere('a.location = l.id');
        $qb->andWhere('a.campaign = c.id');
        $qb->setParameter('activity', $id);
        $query = $qb->getQuery();

        return $this->response(
            $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        );
    }
}