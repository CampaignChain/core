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

use CampaignChain\CoreBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UserController
 * @package CampaignChain\CoreBundle\Controller
 */
class UserController extends Controller
{

    /**
     * List every user from the DB
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $userManager = $this->get('fos_user.user_manager');
        $users = $userManager->findUsers();

        return $this->render('CampaignChainCoreBundle:User:index.html.twig',
            array(
                'users' => $users,
                'page_title' => 'Current Users',
            ));
    }

    /**
     * @param Request $request
     * @param User $userToEdit
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function editAction(Request $request, User $userToEdit)
    {
        $form = $this->createForm('campaignchain_core_user', $userToEdit);

        $form->handleRequest($request);

        if ($form->isValid()) {

            /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($userToEdit);

            $this->addFlash(
                'success',
                'The user '.$userToEdit->getNameAndUsername().' was edited successfully.'
            );

            return $this->redirectToRoute('campaignchain_core_user_edit', array('id' => $userToEdit->getId()));
        }

        return $this->render(
            'CampaignChainCoreBundle:User:edit.html.twig',
            array(
                'page_title' => 'Edit User '.$userToEdit->getNameAndUsername(),
                'form' => $form->createView(),
                'form_submit_label' => 'Save',
                'user' => $userToEdit,
            ));
    }

    /**
     * @param Request $request
     * @param User $userToEdit
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function changePasswordAction(Request $request, User $userToEdit)
    {

        /** @var $formFactory \FOS\UserBundle\Form\Factory\FactoryInterface */
        $formFactory = $this->get('fos_user.change_password.form.factory');

        $form = $formFactory->createForm();
        $form->setData($userToEdit);
        $form->remove('current_password');

        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($userToEdit);

            $this->addFlash(
                'success',
                'The password for '.$userToEdit->getNameAndUsername().' was successfully changed!'
            );

            return $this->redirectToRoute('campaignchain_core_user');
        }

        return $this->render('CampaignChainCoreBundle:User:changePassword.html.twig', array(
            'form' => $form->createView(),
            'page_title' => 'Change Password for '.$userToEdit->getNameAndUsername(),
        ));
    }

    /**
     * Create a new user
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function newAction(Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');

        /** @var User $user */
        $user = $userManager->createUser();

        $form = $this->createForm('campaignchain_core_user', $user, ['new' => true]);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $user->setEnabled(true);
            $user->setPlainPassword($form->get('password')->getData());

            $userManager->updateUser($user);

            $this->addFlash('success', 'New user successfully created!');

            return $this->redirectToRoute('campaignchain_core_user');
        }

        return $this->render('CampaignChainCoreBundle:User:new.html.twig',
            array(
                'form' => $form->createView(),
                'page_title' => 'Create New User',
            ));
    }

    /**
     * Toggle enabled state of a user
     *
     * @param User $userToEdit
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function toggleEnablingAction(User $userToEdit)
    {

        // only normal users/admins can be changed
        if (!$userToEdit->isSuperAdmin()) {

            /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
            $userManager = $this->get('fos_user.user_manager');

            $userToEdit->setEnabled(!$userToEdit->isEnabled());
            $userManager->updateUser($userToEdit);

            $this->addFlash(
                'info',
                $userToEdit->isEnabled() ? 'User '.$userToEdit->getNameAndUsername().' enabled' : 'User '.$userToEdit->getNameAndUsername().' disabled'
            );

        } else {

            $this->addFlash('warning', 'Users with super admin privileges can not be disabled');
        }

        return $this->redirectToRoute('campaignchain_core_user');
    }
}