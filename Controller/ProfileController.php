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

use CampaignChain\CoreBundle\Form\Type\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;

class ProfileController extends Controller
{
    public function editAction(Request $request, $id){
        $user = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:User')
            ->find($id);

        if (!$user) {
            return $this->createNotFoundException('No user found');
        }

        $form = $this->createForm('campaignchain_core_user', $user);

        $form->handleRequest($request);

        if ($form->isValid()) {
            // Change new profile in user's session
            $request->getSession()->set('campaignchain.locale', $user->getLocale());
            $request->getSession()->set('campaignchain.timezone', $user->getTimezone());
            $request->getSession()->set('campaignchain.dateFormat', $user->getDateFormat());
            $request->getSession()->set('campaignchain.timeFormat', $user->getTimeFormat());

            $repository = $this->getDoctrine()->getManager();
            $repository->persist($user);
            $repository->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                'Your profile was edited successfully.'
            );

            return $this->redirect($this->generateUrl('campaignchain_core_profile_edit', ['id' => $id]));
        }

        return $this->render(
            'CampaignChainCoreBundle:Profile:new.html.twig',
            array(
                'page_title' => 'Profile',
                'form' => $form->createView(),
                'form_submit_label' => 'Save',
                'user' => $user,
            ));
    }

    public function changePasswordAction(Request $request, $id)
    {
        $user = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:User')
            ->find($id);

        if (!$user) {
            return $this->createNotFoundException('No user found');
        }

        /** @var $formFactory \FOS\UserBundle\Form\Factory\FactoryInterface */
        $formFactory = $this->get('fos_user.change_password.form.factory');

        $form = $formFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);

            $this->addFlash('success', 'Your password was successfully changed!');

            return $this->redirectToRoute('campaignchain_core_user');
        }

        return $this->render('CampaignChainCoreBundle:Profile:changePassword.html.twig', array(
            'form' => $form->createView(),
            'page_title' => 'Change Password',
        ));
    }
}