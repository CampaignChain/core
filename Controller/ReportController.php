<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Controller;

use CampaignChain\CoreBundle\Entity\ReportModule;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

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

    public function apiListCtaLocationsPerCampaignAction($id)
    {
        $repository = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:ReportCTA');
        $qb = $repository->createQueryBuilder('r');
        $qb->select('r')
            ->where('r.campaign = :campaignId')
            ->andWhere('r.sourceLocation = r.referrerLocation')
            ->andWhere('r.targetLocation is not NULL')
            ->groupBy('r.targetLocation')
            ->orderBy('r.targetName', 'ASC')
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
