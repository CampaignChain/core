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

use CampaignChain\CoreBundle\Entity\Milestone;
use CampaignChain\CoreBundle\EntityService\MilestoneService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityRepository;
use CampaignChain\CoreBundle\Entity\Action;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

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

    public function editAction(Request $request, $id)
    {
        /** @var MilestoneService $milestoneService */
        $milestoneService = $this->get('campaignchain.core.milestone');
        /** @var Milestone $milestone */
        $milestone = $milestoneService->getMilestone($id);
        $routes = $milestone->getMilestoneModule()->getRoutes();

        $routeType = 'edit';

        // If closed activity, then redirect to read view.
        if($milestone->getStatus() == Action::STATUS_CLOSED){
            $routeType = 'read';
        }

        return $this->redirect(
            $this->generateUrl(
                $routes[$routeType],
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

    /**
     * Move a Milestone to a new start date.
     *
     * @ApiDoc(
     *  section = "Core",
     *  views = { "private" },
     *  requirements={
     *      {
     *          "name"="id",
     *          "description" = "Milestone ID",
     *          "requirement"="\d+"
     *      },
     *     {
     *          "name"="start_date",
     *          "description" = "Start date in ISO8601 format",
     *          "requirement"="/(\d{4})-(\d{2})-(\d{2})T(\d{2})\:(\d{2})\:(\d{2})[+-](\d{2})\:(\d{2})/"
     *      }
     *  }
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function moveApiAction(Request $request)
    {
        $serializer = $this->get('campaignchain.core.serializer.default');

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

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return new Response($serializer->serialize($responseData, 'json'));
    }
    public function  removeAction(Request $request, $id)
    {
        $milestoneService = $this->get('campaignchain.core.milestone');
        $milestoneService->removeMilestone($id);
        return $this->redirectToRoute('campaignchain_core_milestone');
    }
}