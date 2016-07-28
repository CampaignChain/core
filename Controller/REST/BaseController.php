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

use CampaignChain\CoreBundle\Entity\Activity;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as REST;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Request\ParamFetcher;
use \Doctrine\ORM\QueryBuilder;

/**
 * @REST\Version("v1")
 */
class BaseController extends FOSRestController
{
    protected $qb;

    protected function getQueryBuilder()
    {
        return $this->getDoctrine()->getEntityManager()->createQueryBuilder();
    }

    protected function response($responseData)
    {
        if(!$responseData || !is_array($responseData) || !count($responseData)){
            $view = $this->view(null, 400);
            return $this->handleView($view);
        }

        $view = $this->view($responseData, 200);

        return $this->responseHeaders($this->handleView($view));
    }

    protected function errorResponse($message, $code = 400)
    {
        if($code == 0){
            $code = 400;
        }
        $response['error'] = array(
            'message'   => $message,
            'code'      => $code,
        );

        $view = $this->view($response, $code);
        return $this->responseHeaders($this->handleView($view));
    }

    protected function responseHeaders(Response $response)
    {
        $system = $this->get('campaignchain.core.system')->getActiveSystem();
        $response->headers->set('campaignchain-api-version', $system->getVersion());
        return $response;
    }

    protected function getModulePackage(QueryBuilder $qb, $entityModuleAttribute)
    {
        $qb->select($this->getDQLSelects($qb).', b.name AS composerPackage, m.identifier AS moduleIdentifier');
        $qb->from('CampaignChain\CoreBundle\Entity\Module', 'm');
        $qb->from('CampaignChain\CoreBundle\Entity\Bundle', 'b');
        $qb->andWhere('b.id = m.bundle');
        $qb->andWhere($entityModuleAttribute.' = m.id');

        return $qb;
    }

    protected function getLocationChannelId(QueryBuilder $qb)
    {
        $qb->select($this->getDQLSelects($qb).', IDENTITY(parent.channel) AS channelId');
        $qb->leftJoin(
            'CampaignChain\CoreBundle\Entity\Location',
            'parent',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'parent.id = l.parent'
        );

        return $qb;
    }

    private function getDQLSelects(QueryBuilder $qb){
        $selectStatement = '';

        $selects = $qb->getDQLPart('select');

        if(is_array($selects) && count($selects)){
            foreach($selects as $select){
                $selectStatement .= $select;
            }
        }

        return $selectStatement;
    }

    protected function getModuleRelation(QueryBuilder $qb, $entityModuleAttribute, $uri){
        $uriParts = explode('/', $uri);
        $vendor = $uriParts[0];
        $project = $uriParts[1];
        $identifier = $uriParts[2];

        $qb->from('CampaignChain\CoreBundle\Entity\Module', 'm');
        $qb->from('CampaignChain\CoreBundle\Entity\Bundle', 'b');
        $qb->where('b.id = m.bundle');
        $qb->andWhere('b.name = :package');
        $qb->andWhere('m.identifier = :module');
        $qb->setParameter('package', $vendor.'/'.$project);
        $qb->setParameter('module', $identifier);
        $qb->andWhere($entityModuleAttribute.' = m.id');

        return $qb;
    }
}