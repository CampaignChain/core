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

use CampaignChain\CoreBundle\Util\DateTimeUtil;
use CampaignChain\CoreBundle\Form\Type\CampaignType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use CampaignChain\CoreBundle\Entity\Campaign;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Doctrine\ORM\EntityRepository;
use CampaignChain\CoreBundle\Entity\Action;

class CampaignController extends Controller
{
    const FORMAT_DATEINTERVAL = 'Years: %Y, months: %m, days: %d, hours: %h, minutes: %i, seconds: %s';

    public function indexAction()
    {
        $repository = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:Campaign');

        $query = $repository->createQueryBuilder('campaign')
            ->where('campaign.status != :statusBackgroundProcess')
            ->setParameter('statusBackgroundProcess', Action::STATUS_BACKGROUND_PROCESS)
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
                'query_builder' => function (EntityRepository $er) {
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

            if ($this->getRequest()->isXmlHttpRequest()) {
                return new JsonResponse(array(
                    'step' => 1,
                    'next_step' => $routes['new'],
                ));
            } else {
                return $this->redirectToRoute($routes['new']);
            }
        }

        return $this->render(
            $this->getRequest()->isXmlHttpRequest() ? 'CampaignChainCoreBundle:Base:new_modal.html.twig' : 'CampaignChainCoreBundle:Base:new.html.twig',
            array(
                'page_title' => 'Create New Campaign',
                'form' => $form->createView(),
                'form_submit_label' => 'Next',
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

        $campaignService = $this->get('campaignchain.core.campaign');
        $campaign = $campaignService->getCampaign($id);

        // Preserve old campaign data for response.
        $responseData['campaign']['id'] = $campaign->getId();
        $oldCampaignStartDate = clone $campaign->getStartDate();
        $responseData['campaign']['old_start_date'] = $oldCampaignStartDate->format(\DateTime::ISO8601);
        $responseData['campaign']['old_end_date'] = $campaign->getEndDate()->format(\DateTime::ISO8601);

        // Move campaign's start date.
        $campaign = $campaignService->moveCampaign($campaign, $newStartDate);

        // Add new campaign dates to response.
        $responseData['campaign']['new_start_date'] = $campaign->getStartDate()->format(\DateTime::ISO8601);
        $responseData['campaign']['new_end_date'] = $campaign->getEndDate()->format(\DateTime::ISO8601);

        $response = new Response($serializer->serialize($responseData, 'json'));
        return $response->setStatusCode(Response::HTTP_OK);
    }
}