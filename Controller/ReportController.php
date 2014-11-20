<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use CampaignChain\CoreBundle\Entity\Report;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;

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
                    $report->getRoutes()['index'],
                    array(
                        'id' => $report->getId(),
                    )
                )
            );
        }
//
//        }


    }
}