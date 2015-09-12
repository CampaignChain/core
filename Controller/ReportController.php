<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use CampaignChain\CoreBundle\Entity\Report;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function indexAction(Request $request, $id){
        $repository = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:ReportModule');

        if(!$id){
            $reports = $repository->findAll();
            return $this->render(
                'CampaignChainCoreBundle:Report:index.html.twig',
                array(
                    'page_title' => 'Reports',
                    'reports' => $reports,
                ));
        } else {
            $report = $repository->find($id);

            if (!$report) {
                throw new \Exception(
                    'No report found for id '.$id
                );
            }

            return $this->redirect(
                $this->generateUrl(
                    $report->getRoutes()['index']
                )
            );
        }
//
//        }


    }

    public function apiListCtaLocationsPerCampaignAction(Request $request, $id){
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

        foreach($locations as $location){
            $response[] = array(
                'id' => $location->getTargetLocation()->getId(),
                'display_name' => $location->getTargetName()
                                    .' ('.$location->getTargetUrl().')',
            );
        }

        $encoders = array(new JsonEncoder());
        $normalizers = array(new GetSetMethodNormalizer());

        $serializer = new Serializer($normalizers, $encoders);

        return new Response($serializer->serialize($response, 'json'));
    }
}