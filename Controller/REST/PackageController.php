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

class PackageController extends BaseController
{
    /**
     * Get all Composer packages of a vendor that contain modules.
     *
     * Sample Request
     * ==============
     *
     *      GET /api/v1/packages/vendors/campaignchain
     *
     * Sample Response
     * ===============
     *
    {
        "response": [
            {
                "id": 22,
                "type": "campaignchain-activity",
                "name": "campaignchain/activity-facebook",
                "description": "Collection of various Facebook activities, such as post or share a message.",
                "license": "Apache-2.0",
                "authors": {
                    "name": "CampaignChain, Inc.",
                    "email": "info@campaignchain.com\""
                },
                "homepage": "http://www.campaignchain.com",
                "path": "vendor/campaignchain/activity-facebook",
                "class": "CampaignChain\\Activity\\FacebookBundle\\CampaignChainActivityFacebookBundle",
                "version": "dev-master",
                "createdDate": "2015-11-26T11:08:29+0000"
            },
            {
                "id": 25,
                "type": "campaignchain-activity",
                "name": "campaignchain/activity-gotowebinar",
                "description": "Include a Webinar into a campaign.",
                "license": "Apache-2.0",
                "authors": {
                    "name": "CampaignChain, Inc.",
                    "email": "info@campaignchain.com\""
                },
                "homepage": "http://www.campaignchain.com",
                "path": "vendor/campaignchain/activity-gotowebinar",
                "class": "CampaignChain\\Activity\\GoToWebinarBundle\\CampaignChainActivityGoToWebinarBundle",
                "version": "dev-master",
                "createdDate": "2015-11-26T11:08:29+0000"
            },
            {
                "id": 24,
                "type": "campaignchain-activity",
                "name": "campaignchain/activity-linkedin",
                "description": "Collection of various LinkedIn activities, such as tweeting and re-tweeting.",
                "license": "Apache-2.0",
                "authors": {
                    "name": "CampaignChain, Inc.",
                    "email": "info@campaignchain.com\""
                },
                "homepage": "http://www.campaignchain.com",
                "path": "vendor/campaignchain/activity-linkedin",
                "class": "CampaignChain\\Activity inkedInBundle\\CampaignChainActivityLinkedInBundle",
                "version": "dev-master",
                "createdDate": "2015-11-26T11:08:29+0000"
            },
            {
                "id": 26,
                "type": "campaignchain-activity",
                "name": "campaignchain/activity-mailchimp",
                "description": "Add upcoming newsletter campaign",
                "license": "Apache-2.0",
                "authors": {
                    "name": "CampaignChain, Inc.",
                    "email": "info@campaignchain.com\""
                },
                "homepage": "http://www.campaignchain.com",
                "path": "vendor/campaignchain/activity-mailchimp",
                "class": "CampaignChain\\Activity\\MailChimpBundle\\CampaignChainActivityMailChimpBundle",
                "version": "dev-master",
                "createdDate": "2015-11-26T11:08:29+0000"
            }
        ]
    }
     *
     * @ApiDoc(
     *  section="Packages",
     *  requirements={
     *      {
     *          "name"="vendor",
     *          "requirement"="[A-Za-z0-9][A-Za-z0-9_.-]*"
     *      }
     *  }
     * )
     *
     * @param   string     $vendor     A Composer package's vendor name, e.g. 'campaignchain'.
     *
     * @return Response
     */
    public function getVendorsAction($vendor)
    {
        $qb = $this->getQueryBuilder();
        $qb->select('b');
        $qb->from('CampaignChain\CoreBundle\Entity\Bundle', 'b');
        $qb->where('b.name LIKE :package');
        $qb->setParameter('package', '%'.$vendor.'/%');
        $qb->orderBy('b.name');
        $query = $qb->getQuery();

        return $this->response(
            $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        );
    }

    /**
     * Get one specific Composer package that contains CampaignChain modules.
     *
     * Sample Request
     * ==============
     *
     *      GET /api/v1/packages/vendors/campaignchain/projects/location-facebook
     *
     * Sample Response
     * ===============
     *
    {
        "response": [
            {
                "id": 13,
                "type": "campaignchain-location",
                "name": "campaignchain/location-facebook",
                "description": "Facebook user and page stream.",
                "license": "Apache-2.0",
                "authors": {
                    "name": "CampaignChain, Inc.",
                    "email": "info@campaignchain.com\""
                },
                "homepage": "http://www.campaignchain.com",
                "path": "vendor/campaignchain/location-facebook",
                "class": "CampaignChain ocation\\FacebookBundle\\CampaignChainLocationFacebookBundle",
                "version": "dev-master",
                "createdDate": "2015-11-26T11:08:29+0000"
            }
        ]
    }
     *
     * @ApiDoc(
     *  section="Packages",
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
     * @return CampaignChain\CoreBundle\Entity\Bundle
     */
    public function getPackagesVendorsProjectsAction($vendor, $project)
    {
        $qb = $this->getQueryBuilder();
        $qb->select('b');
        $qb->from('CampaignChain\CoreBundle\Entity\Bundle', 'b');
        $qb->andWhere('b.name = :package');
        $qb->setParameter('package', $vendor.'/'.$project);
        $qb->orderBy('b.name');
        $query = $qb->getQuery();

        return $this->response(
            $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        );
    }
}