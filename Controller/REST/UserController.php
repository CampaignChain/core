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
 * Class UserController
 *
 * @REST\NamePrefix("campaignchain_core_rest_user_")
 *
 * @package CampaignChain\CoreBundle\Controller\REST
 */
class UserController extends BaseController
{
    const SELECT_STATEMENT = 'u.id, u.usernameCanonical AS username, u.firstName, u.lastName, u.emailCanonical AS email, u.roles, u.language, u.locale, u.timezone, u.currency, u.dateFormat, u.timeFormat, u.avatarImage AS profileImage';

    /**
     * Get one specific user.
     *
     * Example Request
     * ===============
     *
     *      GET /api/v1/users/1
     *
     * Example Response
     * ================
     *
    [
        {
            "id": 1,
            "username": "admin",
            "firstName": "Sandro",
            "lastName": "Groganz",
            "email": "admin@example.com",
            "roles": [
                "ROLE_SUPER_ADMIN"
            ],
            "language": "en_US",
            "locale": "en_US",
            "timezone": "UTC",
            "currency": "USD",
            "dateFormat": "yyyy-MM-dd",
            "timeFormat": "HH:mm",
            "profileImage": "avatar/4d6e7d832be2ab4c.jpg"
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
     * @param string $id The ID of a user, e.g. '42'.
     *
     * @return CampaignChain\CoreBundle\Entity\Bundle
     */
    public function getUsersAction($id)
    {
        $qb = $this->getQueryBuilder();
        $qb->select(self::SELECT_STATEMENT);
        $qb->from('CampaignChain\CoreBundle\Entity\User', 'u');
        $qb->where('u.id = :user');
        $qb->setParameter('user', $id);
        $qb->orderBy('u.username');
        $query = $qb->getQuery();

        return $this->response(
            $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        );
    }
}