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
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;

class PlanController extends Controller
{
    public function indexAction(Request $request)
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
                $this->generateUrl($routes['plan'])
            );
        }

        return $this->render(
            'CampaignChainCoreBundle:Base:new.html.twig',
            array(
                'page_title' => 'Plan Campaign',
                'form' => $form->createView(),
            ));
    }
}