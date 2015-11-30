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

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as REST;
use Symfony\Component\HttpFoundation\Session\Session;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Request\ParamFetcher;

/**
 * @REST\Version("v1")
 */
class BaseController extends FOSRestController
{
    protected function getQueryBuilder()
    {
        return $this->getDoctrine()->getEntityManager()->createQueryBuilder();
    }

    protected function response($responseData, $requestData = null)
    {
        $response = array();

        if($requestData){
            $response['request'] = $requestData;
        }

        if(!$responseData || !is_array($responseData) || !count($responseData)){
            $view = $this->view($response, 400);
            return $this->handleView($view);
        }

        $response['response'] = $responseData;
        $view = $this->view($response, 200);
        return $this->handleView($view);
    }
}