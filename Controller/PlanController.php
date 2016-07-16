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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;

class PlanController extends Controller
{
    public function indexAction(Request $request)
    {
        $repository = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:CampaignModule');

        $query = $repository->createQueryBuilder('cm')
            ->orderBy('cm.displayName', 'ASC')
            ->getQuery();

        $campaignModules = $query->getResult();

        return $this->render(
            'CampaignChainCoreBundle:Plan:index.html.twig',
            array(
                'page_title' => 'Select Campaign Type',
                'campaign_modules' => $campaignModules,
            ));
    }
}

//        $form = $this->createFormBuilder()
//            ->add('campaign_module', 'entity', array(
//                'label' => 'Type',
//                'class' => 'CampaignChainCoreBundle:CampaignModule',
//                'query_builder' => function(EntityRepository $er) {
//                        return $er->createQueryBuilder('cm')
//                            ->orderBy('cm.displayName', 'ASC');
//                    },
//                'property' => 'displayName',
//                'empty_value' => 'Select the type of campaign',
//                'empty_data' => null,
//            ))
//            ->getForm();
//
//        $form->handleRequest($request);
//
//        if ($form->isValid()) {
//            // Get the activity module's activity.
//            $campaignService = $this->get('campaignchain.core.campaign');
//            $campaignModule = $campaignService->getCampaignModule($form->get('campaign_module')->getData());
//
//            $routes = $campaignModule->getRoutes();
//            return $this->redirect(
//                $this->generateUrl($routes['plan'])
//            );
//        }