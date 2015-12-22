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

use CampaignChain\CoreBundle\Form\Type\MilestoneType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use CampaignChain\CoreBundle\Entity\Milestone;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use CampaignChain\CoreBundle\Entity\Action;

class MilestoneController extends Controller
{
    const FORMAT_DATEINTERVAL = 'Years: %Y, months: %m, days: %d, hours: %h, minutes: %i, seconds: %s';

    public function indexAction(){
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('m')
            ->from('CampaignChain\CoreBundle\Entity\Milestone', 'm')
            ->orderBy('m.startDate');
        $query = $qb->getQuery();
        $milestones = $query->getResult();

        return $this->render(
            'CampaignChainCoreBundle:Milestone:index.html.twig',
            array(
                'page_title' => 'Milestones',
                'milestones' => $milestones
            ));
    }

    public function newAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('milestone_module', 'entity', array(
                'label' => 'Type',
                'class' => 'CampaignChainCoreBundle:MilestoneModule',
                'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('mm')
                            ->orderBy('mm.displayName', 'ASC');
                    },
                'property' => 'displayName',
                'empty_value' => 'Select the type of milestone',
                'empty_data' => null,
            ))
            ->add('campaign', 'entity', array(
                'label' => 'Campaign',
                'class' => 'CampaignChainCoreBundle:Campaign',
                'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('campaign')
                            ->where('campaign.status != :statusClosed')
                            ->andWhere('campaign.status != :statusBackgroundProcess')
                            ->setParameter('statusClosed', Action::STATUS_CLOSED)
                            ->setParameter('statusBackgroundProcess', Action::STATUS_BACKGROUND_PROCESS)
                            ->orderBy('campaign.startDate', 'ASC');
                    },
                'property' => 'name',
                'empty_value' => 'Select a Campaign',
                'empty_data' => null,
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            // Get the activity module's activity.
            $milestoneService = $this->get('campaignchain.core.milestone');
            $milestoneModule = $milestoneService->getMilestoneModule($form->get('milestone_module')->getData());

            $routes = $milestoneModule->getRoutes();
            return $this->redirect(
                $this->generateUrl($routes['new'],
                    array(
                        'campaign' => $form['campaign']->getData(),
                    )
                )
            );
        }

        return $this->render(
            'CampaignChainCoreBundle:Base:new.html.twig',
            array(
                'page_title' => 'Create New Milestone',
                'form' => $form->createView(),
                'form_submit_label' => 'Next',
            ));
    }

    public function editAction(Request $request, $id){
        // TODO: If a milestone is over/done, it cannot be edited.
        $milestoneService = $this->get('campaignchain.core.milestone');
        $milestoneModule = $milestoneService->getMilestoneModuleByMilestone($id);
        $routes = $milestoneModule->getRoutes();

        return $this->redirect(
            $this->generateUrl(
                $routes['edit'],
                array(
                    'id' => $id,
                )
            )
        );
    }

    public function editModalAction(Request $request, $id){
        // TODO: If a milestone is over/done, it cannot be edited.
        $milestoneService = $this->get('campaignchain.core.milestone');
        $milestoneModule = $milestoneService->getMilestoneModuleByMilestone($id);
        $routes = $milestoneModule->getRoutes();

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
        $newDue = new \DateTime($request->request->get('start_date'));

        $milestoneService = $this->get('campaignchain.core.milestone');
        $milestone = $milestoneService->getMilestone($id);
        $responseData['id'] = $milestone->getId();

        $oldDue = clone $milestone->getStartDate();
        $responseData['old_due_date'] = $oldDue->format(\DateTime::ISO8601);

        // Calculate time difference.
        // TODO: Check whether start = end date.
        $interval = $milestone->getStartDate()->diff($newDue);
        $responseData['interval']['object'] = json_encode($interval, true);
        $responseData['interval']['string'] = $interval->format(self::FORMAT_DATEINTERVAL);

        // Set new due date.
        $milestone = $milestoneService->moveMilestone($milestone, $interval);
        $responseData['new_due_date'] = $milestone->getStartDate()->format(\DateTime::ISO8601);

        $repository = $this->getDoctrine()->getManager();
        $repository->flush();

        $response = new Response($serializer->serialize($responseData, 'json'));
        return $response->setStatusCode(Response::HTTP_OK);
    }
}