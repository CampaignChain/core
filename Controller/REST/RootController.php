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

class RootController extends BaseController
{
    /**
     * Get a list of all installed modules.
     *
     * Sample Request
     * ==============
     *
     *      GET /api/v1/modules
     *
     * Sample Response
     * ===============
     *
    {
        "response": [
            {
                "id": 37,
                "identifier": "campaignchain-analytics-cta-tracking-per-location",
                "displayName": "CTAs per Location",
                "description": "Shows Activities that linked to a Location and CTAs executed on that Location.",
                "routes": {
                    "index": "campaignchain_analytics_cta_tracking_per_location_index"
                },
                "createdDate": "2015-11-26T11:08:29+0000",
                "type": "report"
            },
            {
                "id": 16,
                "identifier": "campaignchain-citrix-user",
                "displayName": "Citrix user",
                "createdDate": "2015-11-26T11:08:29+0000",
                "type": "location"
            },
            {
                "id": 1,
                "identifier": "campaignchain-facebook",
                "displayName": "Facebook",
                "routes": {
                    "new": "campaignchain_channel_facebook_create"
                },
                "createdDate": "2015-11-26T11:08:29+0000",
                "type": "channel"
            }
        ]
    }
     *
     * @ApiDoc(
     *  section="Modules"
     * )
     */
    public function getModulesAction()
    {
        $qb = $this->getQueryBuilder();
        $qb->select('m');
        $qb->from('CampaignChain\CoreBundle\Entity\Module', 'm');
        $qb->orderBy('m.identifier');
        $query = $qb->getQuery();

        return $this->response(
            $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        );
    }

    /**
     * Get a list of all installed Composer packages containing CampaignChain modules.
     *
     * Sample Request
     * ==============
     *
     *      GET /api/v1/packages
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
            }
        ]
    }
     *
     * @ApiDoc(
     *  section="Packages"
     * )
     */
    public function getPackagesAction()
    {
        $qb = $this->getQueryBuilder();
        $qb->select('b');
        $qb->from('CampaignChain\CoreBundle\Entity\Bundle', 'b');
        $qb->orderBy('b.name');
        $query = $qb->getQuery();

        return $this->response(
            $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        );
    }

    /**
     * Get a list of all campaigns.
     *
     * Sample Request
     * ==============
     *
     *      GET /api/v1/campaigns.json?fromNow[]=ongoing&moduleURI[]=campaignchain/campaign-scheduled/campaignchain-scheduled&status[]=open
     *
     * Sample Response
     * ===============
     *
    {
        "request": {
            "fromNow": [
                "ongoing"
            ],
            "moduleURI": [
                "campaignchain/campaign-scheduled/campaignchain-scheduled"
            ]
        },
        "response": [
            {
                "id": 2,
                "timezone": "America/Paramaribo",
                "hasRelativeDates": false,
                "name": "Company Anniversary",
                "startDate": "2015-06-10T22:01:32+0000",
                "endDate": "2015-12-21T05:04:27+0000",
                "status": "open",
                "createdDate": "2015-11-26T11:08:29+0000"
            },
            {
                "id": 3,
                "timezone": "Asia/Tashkent",
                "hasRelativeDates": false,
                "name": "Customer Win Story",
                "startDate": "2015-09-28T07:02:39+0000",
                "endDate": "2016-04-18T01:44:23+0000",
                "status": "open",
                "createdDate": "2015-11-26T11:08:29+0000"
            }
        ]
    }
     *
     * @ApiDoc(
     *  section="Campaigns"
     * )
     *
     * @REST\QueryParam(
     *      name="fromNow",
     *      map=true,
     *      requirements="(done|ongoing|upcoming)",
     *      description="Filters per start and end date, options: 'upcoming' (start date is after now), 'ongoing' (start date is before and end date after now), 'done' (end date is before now)."
     * )
     * @REST\QueryParam(
     *      name="status",
     *      map=true,
     *      requirements="(open|closed|paused|background process|interaction required)",
     *      description="Workflow status of a campaign."
     * )
     * @REST\QueryParam(
     *      name="moduleURI",
     *      map=true,
     *      requirements="[A-Za-z0-9][A-Za-z0-9_.-]*\/[A-Za-z0-9][A-Za-z0-9_.-]*\/[A-Za-z0-9][A-Za-z0-9_.-]*",
     *      description="The module URI is composed of the Composer package name and the module identifier, e.g. campaignchain/location-facebook/location-facebook-user."
     *  )
     */
    public function getCampaignsAction(ParamFetcher $paramFetcher)
    {
        $params = $paramFetcher->all();

        $qb = $this->getQueryBuilder();
        $qb->select('c');
        $qb->from('CampaignChain\CoreBundle\Entity\Campaign', 'c');

        if($params['moduleURI']){
            foreach($params['moduleURI'] as $moduleURI) {
                $moduleURIParts = explode('/', $moduleURI);
                $vendor = $moduleURIParts[0];
                $project = $moduleURIParts[1];
                $identifier = $moduleURIParts[2];
                $qb->from('CampaignChain\CoreBundle\Entity\Bundle', 'b');
                $qb->from('CampaignChain\CoreBundle\Entity\Module', 'm');
                $qb->andWhere('b.id = m.bundle');
                $qb->andWhere('b.name = :package');
                $qb->setParameter('package', $vendor . '/' . $project);
                $qb->andWhere('m.identifier = :identifier');
                $qb->setParameter('identifier', $identifier);
                $qb->andWhere('c.campaignModule = m.id');
            }
        }

        if($params['fromNow']){
            foreach($params['fromNow'] as $fromNow) {
                switch($fromNow){
                    case 'done':
                        $qb->andWhere('c.startDate < CURRENT_TIMESTAMP() AND c.endDate < CURRENT_TIMESTAMP()');
                        break;
                    case 'ongoing':
                        $qb->andWhere('c.startDate < CURRENT_TIMESTAMP() AND c.endDate > CURRENT_TIMESTAMP()');
                        break;
                    case 'upcoming':
                        $qb->andWhere('c.startDate > CURRENT_TIMESTAMP() AND c.endDate > CURRENT_TIMESTAMP()');
                        break;
                }
            }
        }

        if($params['status']){
            foreach($params['status'] as $status) {
                $qb->andWhere('c.status = :status');
                $qb->setParameter('status', $status);
            }
        }

        $qb->orderBy('c.name');
        $query = $qb->getQuery();


        return $this->response(
            $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY),
            $paramFetcher->all()
        );
    }
}