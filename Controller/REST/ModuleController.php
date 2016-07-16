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
 * Class ModuleController
 *
 * @REST\NamePrefix("campaignchain_core_rest_module_")
 *
 * @package CampaignChain\CoreBundle\Controller\REST
 */
class ModuleController extends BaseController
{
    const SELECT_STATEMENT = 'b.name AS composerPackage, m.identifier AS moduleIdentifier, m.displayName, m.description, m.routes, m.services, m.hooks, m.params, m.createdDate';

    /**
     * List all available types for modules
     *
     * Example Request
     * ===============
     *
     *      GET /api/v1/modules/types
     *
     * Example Response
     * ================
    [
        "activity",
        "campaign",
        "channel",
        "location",
        "milestone",
        "operation",
        "report",
        "security"
    ]
     *
     * @ApiDoc(
     *  section="Core"
     * )
     *
     * @REST\GET("/types")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getTypesMetaAction()
    {
        $typeClasses = $this->getDoctrine()->getEntityManager()->getClassMetadata('CampaignChain\CoreBundle\Entity\Module')->discriminatorMap;

        return $this->response(
            VariableUtil::arrayFlatten(array_keys($typeClasses))
        );
    }

    /**
     * Get all modules of same type.
     *
     * Example Request
     * ===============
     *
     *      GET /api/v1/modules/types/location
     *
     * Example Response
     * ================
     *
    [
        {
            "composerPackage": "campaignchain/location-facebook",
            "moduleIdentifier": "campaignchain-facebook-page",
            "displayName": "Facebook page stream",
            "hooks": {
                "default": {
                    "campaignchain-assignee": true
                }
            },
            "createdDate": "2015-11-26T11:08:29+0000"
        },
        {
            "composerPackage": "campaignchain/location-facebook",
            "moduleIdentifier": "campaignchain-facebook-status",
            "displayName": "Facebook status",
            "createdDate": "2015-11-26T11:08:29+0000"
        },
        {
            "composerPackage": "campaignchain/location-facebook",
            "moduleIdentifier": "campaignchain-facebook-user",
            "displayName": "Facebook user stream",
            "hooks": {
                "default": {
                    "campaignchain-assignee": true
                }
            },
            "createdDate": "2015-11-26T11:08:29+0000"
        },
        {
            "composerPackage": "campaignchain/location-linkedin",
            "moduleIdentifier": "campaignchain-linkedin-user",
            "displayName": "LinkedIn user stream",
            "createdDate": "2015-11-26T11:08:29+0000"
        },
        {
            "composerPackage": "campaignchain/location-twitter",
            "moduleIdentifier": "campaignchain-twitter-status",
            "displayName": "Twitter post (aka Tweet)",
            "createdDate": "2015-11-26T11:08:29+0000"
        },
        {
            "composerPackage": "campaignchain/location-twitter",
            "moduleIdentifier": "campaignchain-twitter-user",
            "displayName": "Twitter user stream",
            "hooks": {
                "default": {
                    "campaignchain-assignee": true
                }
            },
            "createdDate": "2015-11-26T11:08:29+0000"
        }
    ]
     *
     * @ApiDoc(
     *  section="Core",
     *  requirements={
     *      {
     *          "name"="type",
     *          "requirement"="(campaign|channel|location|activity|operation|report|security)"
     *      }
     *  }
     * )
     *
     * @param   string     $type    The type of a module, e.g. 'location'.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getTypesAction($type)
    {
        $qb = $this->getQueryBuilder();
        $qb->select(self::SELECT_STATEMENT);
        $typeClasses = $this->getDoctrine()->getEntityManager()->getClassMetadata('CampaignChain\CoreBundle\Entity\Module')->discriminatorMap;
        $qb->from($typeClasses[$type], 'm');
        $qb->join('m.bundle', 'b');
        $qb->where('b.id = m.bundle');
        $qb->orderBy('m.identifier');
        $query = $qb->getQuery();

        return $this->response(
            $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        );
    }

    /**
     * List all installed Composer packages which include CampaignChain Modules.
     *
     * Example Request
     * ===============
     *
     *      GET /api/v1/modules/packages
     *
     * Example Response
     * ================
     *
    [
        "campaignchain/report-analytics-cta-tracking",
        "campaignchain/location-citrix",
        "campaignchain/channel-facebook",
        "campaignchain/location-facebook",
        "campaignchain/activity-facebook",
        "campaignchain/operation-facebook",
        "campaignchain/location-facebook",
        "campaignchain/location-facebook",
        "campaignchain/channel-google",
        "campaignchain/report-google",
        "campaignchain/report-google-analytics",
        "campaignchain/channel-google-analytics",
        "campaignchain/location-google-analytics",
        "campaignchain/activity-gotowebinar",
        "campaignchain/channel-citrix",
        "campaignchain/location-citrix",
        "campaignchain/operation-gotowebinar",
        "campaignchain/channel-linkedin",
        "campaignchain/activity-linkedin",
        "campaignchain/operation-linkedin",
        "campaignchain/location-linkedin",
        "campaignchain/activity-mailchimp",
        "campaignchain/channel-mailchimp",
        "campaignchain/operation-mailchimp",
        "campaignchain/location-mailchimp",
        "campaignchain/location-mailchimp",
        "campaignchain/report-analytics-metrics-per-activity",
        "campaignchain/campaign-repeating",
        "campaignchain/campaign-scheduled",
        "campaignchain/milestone-scheduled",
        "campaignchain/security-authentication-client-oauth",
        "campaignchain/security-authentication-server-oauth",
        "campaignchain/operation-slideshare",
        "campaignchain/activity-slideshare",
        "campaignchain/channel-slideshare",
        "campaignchain/location-slideshare",
        "campaignchain/campaign-template",
        "campaignchain/channel-twitter",
        "campaignchain/location-twitter",
        "campaignchain/operation-twitter",
        "campaignchain/activity-twitter",
        "campaignchain/location-twitter",
        "campaignchain/channel-website",
        "campaignchain/location-citrix",
        "campaignchain/location-website",
        "campaignchain/location-citrix",
        "campaignchain/location-website"
    ]
     *
     * @ApiDoc(
     *  section="Core"
     * )
     *
     * @REST\GET("/packages")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getPackagesMetaAction()
    {
        $qb = $this->getQueryBuilder();
        $qb->select("b.name");
        $qb->from('CampaignChain\CoreBundle\Entity\Module', 'm');
        $qb->from('CampaignChain\CoreBundle\Entity\Bundle', 'b');
        $qb->where('b.id = m.bundle');
        $qb->orderBy('m.identifier');
        $query = $qb->getQuery();

        return $this->response(
            VariableUtil::arrayFlatten(
                $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
            )
        );
    }

    /**
     * Get all modules contained in a Composer package.
     *
     * Example Request
     * ===============
     *
     *      GET /api/v1/modules/packages/campaignchain%2Flocation-facebook
     *
     * Example Response
     * ================
     *
    [
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
     * @param   string     $package     A Composer package's name, e.g. 'campaignchain/location-facebook'. The value should be URL encoded.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getPackagesAction($package)
    {
        $uriParts = explode('/', $package);
        $vendor = $uriParts[0];
        $project = $uriParts[1];

        $qb = $this->getQueryBuilder();
        $qb->select(self::SELECT_STATEMENT);
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
     * List all available Module URIs.
     *
     * Example Request
     * ===============
     *
     *      GET /api/v1/modules/uris
     *
     * Example Response
     * ================
     *
    [
        "campaignchain/report-analytics-cta-tracking/campaignchain-analytics-cta-tracking-per-location",
        "campaignchain/location-citrix/campaignchain-citrix-user",
        "campaignchain/channel-facebook/campaignchain-facebook",
        "campaignchain/location-facebook/campaignchain-facebook-page",
        "campaignchain/activity-facebook/campaignchain-facebook-publish-status",
        "campaignchain/operation-facebook/campaignchain-facebook-publish-status",
        "campaignchain/location-facebook/campaignchain-facebook-status",
        "campaignchain/location-facebook/campaignchain-facebook-user",
        "campaignchain/channel-google/campaignchain-google",
        "campaignchain/location-google-analytics/campaignchain-google-analytics",
        "campaignchain/channel-google-analytics/campaignchain-google-analytics",
        "campaignchain/report-google/campaignchain-google-analytics",
        "campaignchain/report-google-analytics/campaignchain-google-analytics",
        "campaignchain/activity-gotowebinar/campaignchain-gotowebinar",
        "campaignchain/channel-citrix/campaignchain-gotowebinar",
        "campaignchain/operation-gotowebinar/campaignchain-gotowebinar",
        "campaignchain/location-citrix/campaignchain-gotowebinar",
        "campaignchain/channel-linkedin/campaignchain-linkedin",
        "campaignchain/activity-linkedin/campaignchain-linkedin-share-news-item",
        "campaignchain/operation-linkedin/campaignchain-linkedin-share-news-item",
        "campaignchain/location-linkedin/campaignchain-linkedin-user",
        "campaignchain/activity-mailchimp/campaignchain-mailchimp",
        "campaignchain/channel-mailchimp/campaignchain-mailchimp",
        "campaignchain/location-mailchimp/campaignchain-mailchimp-newsletter",
        "campaignchain/operation-mailchimp/campaignchain-mailchimp-newsletter",
        "campaignchain/location-mailchimp/campaignchain-mailchimp-user",
        "campaignchain/report-analytics-metrics-per-activity/campaignchain-metrics-per-activity",
        "campaignchain/campaign-repeating/campaignchain-repeating",
        "campaignchain/campaign-scheduled/campaignchain-scheduled",
        "campaignchain/milestone-scheduled/campaignchain-scheduled",
        "campaignchain/security-authentication-client-oauth/campaignchain-security-authentication-client-oauth",
        "campaignchain/security-authentication-server-oauth/campaignchain-security-authentication-server-oauth",
        "campaignchain/activity-slideshare/campaignchain-slideshare",
        "campaignchain/operation-slideshare/campaignchain-slideshare",
        "campaignchain/channel-slideshare/campaignchain-slideshare",
        "campaignchain/location-slideshare/campaignchain-slideshare-user",
        "campaignchain/campaign-template/campaignchain-template",
        "campaignchain/channel-twitter/campaignchain-twitter",
        "campaignchain/location-twitter/campaignchain-twitter-status",
        "campaignchain/activity-twitter/campaignchain-twitter-update-status",
        "campaignchain/operation-twitter/campaignchain-twitter-update-status",
        "campaignchain/location-twitter/campaignchain-twitter-user",
        "campaignchain/location-website/campaignchain-website",
        "campaignchain/channel-website/campaignchain-website",
        "campaignchain/location-citrix/campaignchain-website",
        "campaignchain/location-website/campaignchain-website-page",
        "campaignchain/location-citrix/campaignchain-website-page"
    ]
     *
     * @ApiDoc(
     *  section="Core"
     * )
     *
     * @REST\GET("/uris")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getUrisMetaAction()
    {
        $qb = $this->getQueryBuilder();
        $qb->select("CONCAT(b.name, '/', m.identifier)");
        $qb->from('CampaignChain\CoreBundle\Entity\Module', 'm');
        $qb->from('CampaignChain\CoreBundle\Entity\Bundle', 'b');
        $qb->where('b.id = m.bundle');
        $qb->orderBy('m.identifier');
        $query = $qb->getQuery();

        return $this->response(
            VariableUtil::arrayFlatten(
                $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
            )
        );
    }

    /**
     * Get one specific module by Module URI.
     *
     * Example Request
     * ===============
     *
     *      GET /api/v1/modules/uris/campaignchain%2Flocation-facebook%2Fcampaignchain-facebook-user
     *
     * Example Response
     * ================
     *
    [
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
     *
     * @ApiDoc(
     *  section="Core",
     *  requirements={
     *      {
     *          "name"="uri",
     *          "requirement"="[A-Za-z0-9][A-Za-z0-9_.-]*\/[A-Za-z0-9][A-Za-z0-9_.-]*\/[A-Za-z0-9][A-Za-z0-9_.-]*"
     *      }
     *  }
     * )
     *
     * @REST\NoRoute() // We have specified a route manually.
     *
     * @param   string     $uri     A Module URI, e.g. 'campaignchain/location-facebook/campaignchain-facebook-user'. The value should be URL encoded.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getUrisAction($uri)
    {
        $uriParts = explode('/', $uri);
        $vendor = $uriParts[0];
        $project = $uriParts[1];
        $identifier = $uriParts[2];

        $qb = $this->getQueryBuilder();
        $qb->select(self::SELECT_STATEMENT);
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