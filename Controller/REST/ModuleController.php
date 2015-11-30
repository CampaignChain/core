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

use FOS\RestBundle\Controller\Annotations as REST;
use Symfony\Component\HttpFoundation\Session\Session;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Controller\Annotations\Get;

class ModuleController extends BaseController
{
    /**
     * Get all modules of same type.
     *
     * Sample Request
     * ==============
     *
     *      GET /api/{version}/modules/types/location
     *
     * Sample Response
     * ===============
     *
    {
    "response": [
        {
            "id": 11,
            "identifier": "campaignchain-facebook-page",
            "displayName": "Facebook page stream",
            "hooks": {
            "default": {
                "campaignchain-assignee": true
            }
            },
            "createdDate": "2015-11-26T11:08:29+0000",
            "type": "location"
        },
        {
            "id": 12,
            "identifier": "campaignchain-facebook-status",
            "displayName": "Facebook status",
            "createdDate": "2015-11-26T11:08:29+0000",
            "type": "location"
        },
        {
            "id": 10,
            "identifier": "campaignchain-facebook-user",
            "displayName": "Facebook user stream",
            "hooks": {
            "default": {
                "campaignchain-assignee": true
            }
        },
            "createdDate": "2015-11-26T11:08:29+0000",
            "type": "location"
        },
        {
            "id": 13,
            "identifier": "campaignchain-linkedin-user",
            "displayName": "LinkedIn user stream",
            "createdDate": "2015-11-26T11:08:29+0000",
            "type": "location"
        },
        {
            "id": 9,
            "identifier": "campaignchain-twitter-status",
            "displayName": "Twitter post (aka Tweet)",
            "createdDate": "2015-11-26T11:08:29+0000",
            "type": "location"
        },
        {
            "id": 8,
            "identifier": "campaignchain-twitter-user",
            "displayName": "Twitter user stream",
            "hooks": {
            "default": {
                "campaignchain-assignee": true
        }
        },
            "createdDate": "2015-11-26T11:08:29+0000",
            "type": "location"
        }
    ]
    }
     *
     * @ApiDoc(
     *  section="Modules",
     * requirements={
     *      {
     *          "name"="type",
     *          "requirement"="(campaign|channel|location|activity|operation|report|security)"
     *      }
     *  }
     * )
     *
     * @param   string     $type    A Composer package's name, e.g. 'location'.
     *
     * @return Response
     */
    public function getTypesAction($type)
    {
        $qb = $this->getQueryBuilder();
        $qb->select('m');
        $typeClasses = $this->getDoctrine()->getEntityManager()->getClassMetadata('CampaignChain\CoreBundle\Entity\Module')->discriminatorMap;
        $qb->from($typeClasses[$type], 'm');
        $qb->orderBy('m.identifier');
        $query = $qb->getQuery();

        return $this->response(
            $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        );
    }

    /**
     * Get all modules contained in a Composer package.
     *
     * Sample Request
     * ==============
     *
     *      GET /api/v1/modules/vendors/campaignchain/projects/location-facebook
     *
     * Sample Response
     * ===============
     *
    {
        "response": [
            {
                "id": 11,
                "identifier": "campaignchain-facebook-page",
                "displayName": "Facebook page stream",
                "hooks": {
                "default": {
                "campaignchain-assignee": true
            }
            },
                "createdDate": "2015-11-26T11:08:29+0000",
                "type": "location"
            },
            {
                "id": 12,
                "identifier": "campaignchain-facebook-status",
                "displayName": "Facebook status",
                "createdDate": "2015-11-26T11:08:29+0000",
                "type": "location"
            },
            {
                "id": 10,
                "identifier": "campaignchain-facebook-user",
                "displayName": "Facebook user stream",
                "hooks": {
                "default": {
                "campaignchain-assignee": true
            }
            },
                "createdDate": "2015-11-26T11:08:29+0000",
                "type": "location"
            }
        ]
    }
     *
     * @ApiDoc(
     *  section="Modules",
     *  requirements={
     *      {
     *          "name"="vendor",
     *          "requirement"="[A-Za-z0-9][A-Za-z0-9_.-]*"
     *      },
     *      {
     *          "name"="project",
     *          "requirement"="[A-Za-z0-9][A-Za-z0-9_.-]*"
     *      }
     *  }
     * )
     *
     * @Get("/vendors/{vendor}/projects/{project}")
     *
     * @param   string     $vendor     A Composer package's vendor name, e.g. 'campaignchain'.
     * @param   string     $project    A Composer package's project name, e.g. 'location-facebook'.
     *
     * @return Response
     */
    public function getModulesVendorsProjectsAction($vendor, $project)
    {
        $qb = $this->getQueryBuilder();
        $qb->select('m');
        $qb->from('CampaignChain\CoreBundle\Entity\Module', 'm');
        $qb->from('CampaignChain\CoreBundle\Entity\Bundle', 'b');
        $qb->where('b.id = m.bundle');
        $qb->andWhere('b.name = :package');
        $qb->setParameter('package', $vendor.'/'.$project);
        $qb->orderBy('m.identifier');
        $query = $qb->getQuery();

        return $this->response(
            $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        );
    }

    /**
     * Get one specific module.
     *
     * Sample Request
     * ==============
     *
     *      GET /api/v1/modules/identifiers/campaignchain-facebook-user/vendors/campaignchain/projects/location-facebook
     *
     * Sample Response
     * ===============
     *
    {
        "response": [
            {
                "id": 10,
                "identifier": "campaignchain-facebook-user",
                "displayName": "Facebook user stream",
                "hooks":
                    {
                    "default":
                        {
                        "campaignchain-assignee": true
                        }
                    },
                "createdDate": "2015-11-26T11:08:29+0000",
                "type": "location"
            }
        ]
    }
     *
     * @ApiDoc(
     *  section="Modules",
     *  requirements={
     *      {
     *          "name"="vendor",
     *          "requirement"="[A-Za-z0-9][A-Za-z0-9_.-]*"
     *      },
     *      {
     *          "name"="project",
     *          "requirement"="[A-Za-z0-9][A-Za-z0-9_.-]*"
     *      },
     *      {
     *          "name"="identifier",
     *          "requirement"="[A-Za-z0-9][A-Za-z0-9_.-]*"
     *      }
     *  }
     * )
     *
     * @param   string     $vendor     A Composer package's vendor name, e.g. 'campaignchain'.
     * @param   string     $project    A Composer package's project name, e.g. 'location-facebook'.
     * @param   string     $identifier     A CampaignChain module identifier, e.g. 'campaignchain-facebook-user'.
     *
     * @return \CampaignChain\CoreBundle\Entity\Module
     */
    public function getIdentifiersVendorsProjectsAction($identifier, $vendor, $project)
    {
        $qb = $this->getQueryBuilder();
        $qb->select('m');
        $qb->from('CampaignChain\CoreBundle\Entity\Module', 'm');
        $qb->from('CampaignChain\CoreBundle\Entity\Bundle', 'b');
        $qb->where('b.id = m.bundle');
        $qb->andWhere('b.name = :package');
        $qb->andWhere('m.identifier = :module');
        $qb->setParameter('package', $vendor.'/'.$project);
        $qb->setParameter('module', $identifier);
        $qb->orderBy('m.identifier');
        $query = $qb->getQuery();

        return $this->response(
            $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        );
    }
}