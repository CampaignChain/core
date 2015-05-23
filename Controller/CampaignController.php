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

use CampaignChain\CoreBundle\Util\DateTimeUtil;
use CampaignChain\CoreBundle\Form\Type\CampaignType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use CampaignChain\CoreBundle\Entity\Campaign;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Doctrine\ORM\EntityRepository;

class CampaignController extends Controller
{
    const FORMAT_DATEINTERVAL = 'Years: %Y, months: %m, days: %d, hours: %h, minutes: %i, seconds: %s';

    public function indexAction(){
        $repository = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:Campaign');

        $query = $repository->createQueryBuilder('campaign')
            ->orderBy('campaign.startDate', 'DESC')
            ->getQuery();

        $repository_campaigns = $query->getResult();

        return $this->render(
            'CampaignChainCoreBundle:Campaign:index.html.twig',
            array(
                'page_title' => 'Campaigns',
                'repository_campaigns' => $repository_campaigns
            ));
    }

    public function newAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('campaign_module', 'entity', array(
                'label' => 'Type',
                'class' => 'CampaignChainCoreBundle:CampaignModule',
                'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('cm')
                            ->orderBy('cm.displayName', 'ASC');
                    },
                'property' => 'displayName',
                'empty_value' => 'Select the type of campaign',
                'empty_data' => null,
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            // Get the activity module's activity.
            $campaignService = $this->get('campaignchain.core.campaign');
            $campaignModule = $campaignService->getCampaignModule($form->get('campaign_module')->getData());

            $routes = $campaignModule->getRoutes();
            return $this->redirect(
                $this->generateUrl($routes['new'])
            );
        }

        return $this->render(
            'CampaignChainCoreBundle:Base:new.html.twig',
            array(
                'page_title' => 'Create New Campaign',
                'form' => $form->createView(),
            ));
    }

    public function editAction(Request $request, $id)
    {
        // TODO: If a campaign is ongoing, only the end date can be changed.
        // TODO: If a campaign is done, it cannot be edited.
        $campaignService = $this->get('campaignchain.core.campaign');
        $campaignModule = $campaignService->getCampaignModuleByCampaign($id);
        $routes = $campaignModule->getRoutes();

        return $this->redirect(
            $this->generateUrl(
                $routes['edit'],
                array(
                    'id' => $id,
                )
            )
        );
    }

    public function editModalAction(Request $request, $id)
    {
        // TODO: If a campaign is ongoing, only the end date can be changed.
        // TODO: If a campaign is done, it cannot be edited.
        $campaignService = $this->get('campaignchain.core.campaign');
        $campaignModule = $campaignService->getCampaignModuleByCampaign($id);
        $routes = $campaignModule->getRoutes();

        return $this->redirect(
            $this->generateUrl(
                $routes['edit_modal'],
                array(
                    'id' => $id,
                )
            )
        );
    }

    public function moveApiAction(Request $request)
    {
        $encoders = array(new JsonEncoder());
        $normalizers = array(new GetSetMethodNormalizer());
        $serializer = new Serializer($normalizers, $encoders);

        $responseData = array();

        $id = $request->request->get('id');
        $newStartDate = new \DateTime($request->request->get('start_date'));
        $newStartDate = DateTimeUtil::roundMinutes($newStartDate);

        $repository = $this->getDoctrine()->getManager();

        // Make sure that data stays intact by using transactions.
        try {
            $repository->getConnection()->beginTransaction();

            $campaignService = $this->get('campaignchain.core.campaign');
            $campaign = $campaignService->getCampaign($id);

            $responseData['campaign']['id'] = $campaign->getId();

            $oldCampaignStartDate = clone $campaign->getStartDate();
            $responseData['campaign']['old_start_date'] = $oldCampaignStartDate->format(\DateTime::ISO8601);
            $responseData['campaign']['old_end_date'] = $campaign->getEndDate()->format(\DateTime::ISO8601);

            // Calculate time difference.
            $interval = $campaign->getStartDate()->diff($newStartDate);

            $campaign = $campaignService->moveCampaign($campaign, $interval);

            $responseData['campaign']['new_start_date'] = $campaign->getStartDate()->format(\DateTime::ISO8601);
            $responseData['campaign']['new_end_date'] = $campaign->getEndDate()->format(\DateTime::ISO8601);

            // Change due date of all related milestones.
            $milestones = $campaign->getMilestones();
            if($milestones->count()){
                $milestoneService = $this->get('campaignchain.core.milestone');
                foreach($milestones as $milestone){
                    $milestone = $milestoneService->moveMilestone($milestone, $interval);
                    $campaign->addMilestone($milestone);
                }
            }

            // Change due date of all related activities.
            $activities = $campaign->getActivities();
            if($activities->count()){
                $activityService = $this->get('campaignchain.core.activity');
                foreach($activities as $activity){
                    $activity = $activityService->moveActivity($activity, $interval);
                    $campaign->addActivity($activity);
                }
            }

            $repository->persist($campaign);
            $repository->flush();

            $repository->getConnection()->commit();
        } catch (\Exception $e) {
            // TODO: Respond with JSON and HTTP error code.
            $repository->getConnection()->rollback();
            throw $e;
        }

        $response = new Response($serializer->serialize($responseData, 'json'));
        return $response->setStatusCode(Response::HTTP_OK);
    }
}