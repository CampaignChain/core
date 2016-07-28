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
 * @REST\NamePrefix("campaignchain_core_rest_channel_")
 *
 * @package CampaignChain\CoreBundle\Controller\REST
 */
class ChannelController extends BaseController
{
    /**
     * List all URLs of all connected Locations in all Channels.
     *
     * Example Request
     * ===============
     *
     *      GET /api/v1/locations/urls
     *
     * Example Response
     * ================
     *
    {
        "1": "http://wordpress.amariki.com",
        "2": "http://www.slideshare.net/amariki_test",
        "3": "https://global.gotowebinar.com/webinars.tmpl",
        "4": "https://twitter.com/AmarikiTest1",
        "5": "https://www.facebook.com/pages/Amariki/1384145015223372",
        "6": "https://www.facebook.com/profile.php?id=100008874400259",
        "7": "https://www.facebook.com/profile.php?id=100008922632416",
        "8": "https://www.linkedin.com/pub/amariki-software/a1/455/616"
    }
     *
     * @ApiDoc(
     *  section="Core"
     * )
     *
     * @REST\GET("/locations/urls")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getLocationsUrlsMetaAction()
    {
        $qb = $this->getQueryBuilder();
        $qb->select('l.url');
        $qb->from('CampaignChain\CoreBundle\Entity\Channel', 'c');
        $qb->from('CampaignChain\CoreBundle\Entity\Location', 'l');
        $qb->where('c.id = l.channel');
        $qb->andWhere('l.operation IS NULL');
        $qb->groupBy(('l.url'));
        $query = $qb->getQuery();

        $urls = $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        return $this->response(
            VariableUtil::arrayFlatten($urls)
        );
    }

    /**
     * Get a specific Channel Location by its URL.
     *
     * Example Request
     * ===============
     *
     *      GET /api/v1/channels/locations/urls/https%3A%2F%2Ftwitter.com%2FAmarikiTest1
     *
     * Example Response
     * ================
     *
    [
        {
            "id": 2,
            "displayName": "Corporate Twitter Account",
            "url": "https://twitter.com/AmarikiTest1",
            "trackingId": "2f6d485e7789e7b6d70b546d221739cf",
            "status": "active",
            "createdDate": "2015-11-26T11:08:29+0000",
            "composerPackage": "campaignchain/channel-twitter",
            "moduleIdentifier": "campaignchain-twitter"
        }
    ]
     *
     * @ApiDoc(
     *  section="Core",
     *     requirements={
     *      {
     *          "name"="url",
     *          "requirement"=".+"
     *      }
     *  }
     * )
     *
     * @REST\NoRoute() // We have specified a route manually.
     *
     * @param   string     $url    URL of a Channel connected to CampaignChain, e.g. 'https://twitter.com/AmarikiTest1'.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getLocationsUrlsAction($url)
    {
        $qb = $this->getQueryBuilder();
        $qb->select('c.id, c.name AS displayName, l.url, c.trackingId, c.status, c.createdDate');
        $qb->from('CampaignChain\CoreBundle\Entity\Channel', 'c');
        $qb->from('CampaignChain\CoreBundle\Entity\Location', 'l');
        $qb->where('c.id = l.channel');
        $qb->andWhere('l.url = :url');
        $qb->setParameter('url', $url);
        $qb = $this->getModulePackage($qb, 'c.channelModule');
        $query = $qb->getQuery();

        return $this->response(
            $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        );
    }
}