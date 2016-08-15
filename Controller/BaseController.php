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

namespace CampaignChain\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class BaseController extends Controller
{
    protected function apiErrorResponse($message, $code = 400)
    {
        if($code == 0){
            $code = 400;
        }
        $responseData['error'] = array(
            'message'   => $message,
            'code'      => $code,
        );

        $response = new JsonResponse($responseData);
        $response->setStatusCode($code);
        return $this->apiResponseHeaders($response);
    }

    protected function apiResponseHeaders(Response $response)
    {
        $system = $this->get('campaignchain.core.system')->getActiveSystem();
        $response->headers->set('campaignchain-api-version', $system->getVersion());
        return $response;
    }
}