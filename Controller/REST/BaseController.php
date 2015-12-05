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