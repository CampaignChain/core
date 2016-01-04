<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
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
 * Class PackageController
 *
 * @REST\NamePrefix("campaignchain_core_rest_package_")
 *
 * @package CampaignChain\CoreBundle\Controller\REST
 */
class PackageController extends BaseController
{
    const SELECT_STATEMENT = 'b.name AS composerPackage, b.type AS packageType, b.description, b.license, b.authors, b.homepage, b.version, b.createdDate';

    /**
     * List all available vendors of installed Composer packages containing CampaignChain Modules.
     *
     * Example Request
     * ===============
     *
     *      GET /api/v1/packages/vendors
     *
     * Example Response
     * ================
    [
        "campaignchain"
    ]
     *
     * @ApiDoc(
     *  section="Core"
     * )
     *
     * @REST\Get("/vendors")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getVendorsMetaAction()
    {
        $qb = $this->getQueryBuilder();
        $qb->select('b.name');
        $qb->from('CampaignChain\CoreBundle\Entity\Bundle', 'b');
        $qb->groupBy('b.name');
        $qb->orderBy('b.name');
        $query = $qb->getQuery();

        $packages = VariableUtil::arrayFlatten(
                        $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
                    );

        // Extract just the vendor names from the query result.
        $vendors = array();
        foreach($packages as $package){
            $packageParts = explode('/', $package);
            $vendor = $packageParts[0];
            if(!in_array($vendor, $vendors))
            array_push($vendors, $vendor);
        }

        return $this->response(
            $vendors
        );
    }

    /**
     * Get all Composer packages of a vendor that contain modules.
     *
     * Example Request
     * ===============
     *
     *      GET /api/v1/packages/vendors/campaignchain
     *
     * Example Response
     * ================
     *
    [
        {
            "id": 22,
            "packageType": "campaignchain-activity",
            "composerPackage": "campaignchain/activity-facebook",
            "description": "Collection of various Facebook activities, such as post or share a message.",
            "license": "Apache-2.0",
            "authors": {
                "name": "CampaignChain, Inc.",
                "email": "info@campaignchain.com\""
            },
            "homepage": "http://www.campaignchain.com",
            "version": "dev-master",
            "createdDate": "2015-11-26T11:08:29+0000"
        },
        {
            "id": 25,
            "packageType": "campaignchain-activity",
            "composerPackage": "campaignchain/activity-gotowebinar",
            "description": "Include a Webinar into a campaign.",
            "license": "Apache-2.0",
            "authors": {
                "name": "CampaignChain, Inc.",
                "email": "info@campaignchain.com\""
            },
            "homepage": "http://www.campaignchain.com",
            "version": "dev-master",
            "createdDate": "2015-11-26T11:08:29+0000"
        },
        {
            "id": 24,
            "packageType": "campaignchain-activity",
            "composerPackage": "campaignchain/activity-linkedin",
            "description": "Collection of various LinkedIn activities, such as tweeting and re-tweeting.",
            "license": "Apache-2.0",
            "authors": {
                "name": "CampaignChain, Inc.",
                "email": "info@campaignchain.com\""
            },
            "homepage": "http://www.campaignchain.com",
            "version": "dev-master",
            "createdDate": "2015-11-26T11:08:29+0000"
        },
        {
            "id": 26,
            "packageType": "campaignchain-activity",
            "composerPackage": "campaignchain/activity-mailchimp",
            "description": "Add upcoming newsletter campaign",
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
        $qb->select(self::SELECT_STATEMENT);
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
     * Example Request
     * ===============
     *
     *      GET /api/v1/packages/campaignchain%2Flocation-facebook
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
     *          "name"="package",
     *          "requirement"="[A-Za-z0-9][A-Za-z0-9_.-]*\/[A-Za-z0-9][A-Za-z0-9_.-]*"
     *      }
     *  }
     * )
     *
     * @REST\NoRoute() // We have specified a route manually.
     *
     * @param string $package A Composer package's name, e.g. 'campaignchain/location-facebook'. The value should be URL encoded.
     *
     * @return CampaignChain\CoreBundle\Entity\Bundle
     */
    public function getPackagesAction($package)
    {
        $qb = $this->getQueryBuilder();
        $qb->select(self::SELECT_STATEMENT);
        $qb->from('CampaignChain\CoreBundle\Entity\Bundle', 'b');
        $qb->andWhere('b.name = :package');
        $qb->setParameter('package', $package);
        $qb->orderBy('b.name');
        $query = $qb->getQuery();

        return $this->response(
            $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        );
    }
}
