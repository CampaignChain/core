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
use CampaignChain\CoreBundle\Form\Type\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller

{
    public function loginAction()
    {
        if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {

            return $this->redirectToRoute('campaignchain_core_homepage');
        }

        return $this->forward('FOSUserBundle:Security:login');
    }

    /**
     * List every user from the DB
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(){
        $userManager = $this->get('fos_user.user_manager');
        $users = $userManager->findUsers();

        return $this->render('CampaignChainCoreBundle:User:index.html.twig',
            array(
                'users' =>   $users,
                'page_title' => 'Users',
            ));
    }

    /**
     * Create a new user
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');
        /** @var User  $user */
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
                'page_title' => 'New User',
            ));
    }

    public function toggleEnablingAction($id){
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id'=>$id));

        if (!$user) {
            throw $this->createNotFoundException(
                'No User found for id '.$id
            );
        }

        if (!$user->isSuperAdmin()) {

            $activation = ($user->isEnabled()) ? $user->setEnabled(false) : $user->setEnabled(true);
            $userManager->updateUser($user);

            if ($user->isEnabled()) {
                $this->addFlash('info', 'User account enabled!');
            } else {
                $this->addFlash('info', 'User account disabled!');
            }

            return $this->redirectToRoute('campaignchain_core_user');
        }
        else{
            $this->addFlash('warning', 'Users with super admin privileges can not be disabled');
            return $this->redirectToRoute('campaignchain_core_user');
        }
    }
}