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

use FOS\RestBundle\Controller\Annotations as REST;
use Symfony\Component\HttpFoundation\Session\Session;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ChannelController
 *
 * @REST\NamePrefix("campaignchain_core_rest_location_")
 *
 * @package CampaignChain\CoreBundle\Controller\REST
 */
class LocationController extends BaseController
{
    const SELECT_STATEMENT = 'l.id, l.identifier, l.name, l.url, l.image, l.status, l.createdDate, l.modifiedDate, IDENTITY(l.operation) AS operationId';

    /**
     * Get a specific Location by its ID.
     *
     * Example Request
     * ===============
     *
     *      GET /api/v1/locations/42
     *
     * Example Response
     * ================
     *
    [
        {
            "id": 129,
            "identifier": "100008922632416",
            "name": "Amariki Test One",
            "url": "https://www.facebook.com/profile.php?id=100008922632416",
            "image": "https://graph.facebook.com/100008922632416/picture?width=150&height=150",
            "status": "active",
            "createdDate": "2016-10-25T12:26:57+0000",
            "modifiedDate": "2016-10-27T13:36:44+0000"
        }
    ]
     *
     * @ApiDoc(
     *  section="Core",
     *  requirements={
     *      {
     *          "name"="id",
     *          "requirement"="\d+",
     *          "description" = "Location ID"
     *      }
     *  }
     * )
     *
     * @param   string     $id    Location ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getLocationsAction($id)
    {
        $qb = $this->getQueryBuilder();
        $qb->select(self::SELECT_STATEMENT);
        $qb->from('CampaignChain\CoreBundle\Entity\Location', 'l');
        $qb->where('l.id = :id');
        $qb->setParameter('id', $id);
        $query = $qb->getQuery();

        return $this->response(
            $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        );
    }

    /**
     * Toggle the status of a Location to active or inactive.
     *
     * Example Request
     * ===============
     *
     *      POST /api/v1/location/toggle-status
     *
     * Example Input
     * =============
     *
    {
    "id": "42"
    }
     *
     * Example Response
     * ================
     *
     * See:
     *
     *      GET /api/v1/locations/{id}
     *
     * @ApiDoc(
     *  section="Core",
     *  requirements={
     *      {
     *          "name"="id",
     *          "requirement"="\d+",
     *          "description" = "Location ID"
     *      }
     *  }
     * )
     *
     * @REST\Post("/toggle-status")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postToggleStatusAction(Request $request)
    {
        $id = $request->request->get('id');

        $service = $this->get('campaignchain.core.location');

        try {
            $status = $service->toggleStatus($id);
            $response = $this->forward(
                'CampaignChainCoreBundle:REST/Location:getLocations',
                array(
                    'id' => $request->request->get('id')
                )
            );
            return $response->setStatusCode(Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}