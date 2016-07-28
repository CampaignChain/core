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

use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\Activity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Request\ParamFetcher;
use \Doctrine\ORM\QueryBuilder;

class BaseModuleController extends BaseController
{
    protected $moduleControllerService;

    protected function getModuleControllerService()
    {
        if(!static::CONTROLLER_SERVICE){
            throw new \Exception(
                'CONTROLLER_SERVICE constant must be defined with name of the module controller service.',
                Response::HTTP_INTERNAL_SERVER_ERROR
                );
        }

        return $this->get(static::CONTROLLER_SERVICE);
    }

    protected function getActivity($id, array $entities = null)
    {
        $selectQuery = 'l AS location, a AS activity, o AS operation';

        $hasEntities = false;
        if(is_array($entities) && count($entities)){
            $hasEntities = true;
        }

        if($hasEntities) {
            foreach ($entities as $name => $class) {
                $selectQueries[] = $name . '_tbl AS ' . $name;
            }

            $selectQuery = implode(', ', $selectQueries).', '.$selectQuery;
        }

        $qb = $this->getQueryBuilder();
        $qb->select($selectQuery);
        if($hasEntities){
            foreach($entities as $name => $class){
                $qb->from($class, $name.'_tbl');
            }
        }
        $qb->from('CampaignChain\CoreBundle\Entity\Location', 'l');
        $qb->from('CampaignChain\CoreBundle\Entity\Activity', 'a');
        $qb->from('CampaignChain\CoreBundle\Entity\Operation', 'o');
        $qb->where('a.id = :activity');
        $qb->andWhere('o.activity = a.id');
        $qb->andWhere('l.operation = o.id');
        $qb->andWhere('l.parent = a.location');
        if($hasEntities) {
            foreach ($entities as $name => $class) {
                $qb->andWhere($name . '_tbl.operation = o.id');
            }
        }
        $qb->setParameter('activity', $id);
        $query = $qb->getQuery();

        return $this->response(
            $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        );
    }

    protected function postActivity(
        $getActivityControllerMethod,
        Request $request, Activity $activity
    )
    {
        try {
            $activityBag = $request->request->get('activity');

            $moduleControllerService = $this->getModuleControllerService();

            // Is this a Location that is part of the module?
            if(
            !$moduleControllerService->isValidLocation(
                $activityBag['location']
            )
            ){
                throw new \Exception(
                    'The provided Location is not part of this module or does not exist',
                    Response::HTTP_BAD_REQUEST
                );
            }

            $campaign = $this->get('campaignchain.core.campaign')->getCampaign(
                $activityBag['campaign']
            );

            $location = $this->get('campaignchain.core.location')->getLocation(
                $activityBag['location']
            );

            $moduleControllerService->setActivityContext($campaign, $location);

            $form = $this->createForm(
                $moduleControllerService->getActivityFormType('rest'),
                $activity
            );

            $form->handleRequest($request);

            if ($form->isValid()) {
                $activity = $moduleControllerService->createActivity(
                    $activity, $form
                );

                $response = $this->forward(
                    $getActivityControllerMethod,
                    array(
                        'id' => $activity->getId()
                    )
                );
                return $response->setStatusCode(Response::HTTP_CREATED);
            } else {
                return $this->errorResponse(
                    $form
                );
            }
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }
}