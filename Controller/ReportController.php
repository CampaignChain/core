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

use CampaignChain\CoreBundle\Entity\ReportModule;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class ReportController.
 */
class ReportController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        $reports = $this->getDoctrine()->getRepository('CampaignChainCoreBundle:ReportModule')->findAll();

        return $this->render(
            'CampaignChainCoreBundle:Report:index.html.twig',
            [
                'page_title' => 'Reports',
                'reports' => $reports,
            ]
        );
    }

    /**
     * @param ReportModule|null $reportModule
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function showAction(ReportModule $reportModule = null)
    {
        return $this->redirectToRoute($reportModule->getRoutes()['index']);
    }

    /**
     * @ApiDoc(
     *  section = "Core",
     *  views = { "private" },
     *  requirements={
     *      {
     *          "name"="id",
     *          "requirement"="\d+"
     *      }
     *  }
     * )
     *
     * @param $id
     * @return Response
     */
    public function apiListCtaLocationsPerCampaignAction($id)
    {
        $repository = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:ReportCTA');
        $qb = $repository->createQueryBuilder('r');
        $qb->select('r')
            ->where('r.campaign = :campaignId')
            ->andWhere('r.sourceLocation = r.targetLocation')
            ->andWhere('r.targetLocation IS NOT NULL')
            ->groupBy('r.sourceLocation')
            ->orderBy('r.sourceName', 'ASC')
            ->setParameter('campaignId', $id);
        $query = $qb->getQuery();
        $locations = $query->getResult();

        $response = array();
        foreach ($locations as $location) {
            $response[] = [
                'id' => $location->getTargetLocation()->getId(),
                'display_name' => $location->getTargetName()
                    .' ('.$location->getTargetUrl().')',
            ];
        }

        $serializer = $this->get('campaignchain.core.serializer.default');

        return new Response($serializer->serialize($response, 'json'));
    }
}
