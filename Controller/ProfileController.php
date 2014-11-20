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

use CampaignChain\CoreBundle\Form\Type\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;

class ProfileController extends Controller
{
    public function editAction(Request $request){
        $user = $this->get('security.context')->getToken()->getUser();

        $formUserType = new UserType($this->container->getParameter('campaignchain_core')['formats']);

        $form = $this->createForm($formUserType, $user);

        $form->handleRequest($request);

        if ($form->isValid()) {
            // Change new profile in user's session
            $this->container->get('session')->set('campaignchain.locale', $user->getLocale());
            $this->container->get('session')->set('campaignchain.timezone', $user->getTimezone());
            $this->container->get('session')->set('campaignchain.dateFormat', $user->getDateFormat());
            $this->container->get('session')->set('campaignchain.timeFormat', $user->getTimeFormat());

            $repository = $this->getDoctrine()->getManager();
            $repository->persist($user);
            $repository->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                'Your profile was edited successfully.'
            );

            return $this->redirect($this->generateUrl('campaignchain_core_profile_edit'));
        }

        return $this->render(
            'CampaignChainCoreBundle:Profile:new.html.twig',
            array(
                'page_title' => 'Profile',
                'form' => $form->createView(),
                'form_submit_label' => 'Save',
            ));
    }
}