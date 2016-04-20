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

use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserInterface;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ProfileController
 * @package CampaignChain\CoreBundle\Controller
 */
class ProfileController extends Controller
{

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request)
    {
        $user = $this->getUser();

        if (!is_object($user) || !$user instanceof UserInterface) {
            throw $this->createAccessDeniedException('This user does not have access to this section.');
        }

        $form = $this->createForm('campaignchain_core_user', $user);

        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);

            /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
            $dispatcher = $this->get('event_dispatcher');
            $event = new UserEvent($user, $request);
            $dispatcher->dispatch(FOSUserEvents::PROFILE_EDIT_SUCCESS, $event);

            $this->addFlash(
                'success',
                'Your profile was edited successfully.'
            );

            return $this->redirectToRoute('campaignchain_core_profile_edit');
        }

        return $this->render(
            'CampaignChainCoreBundle:Profile:edit.html.twig',
            array(
                'page_title' => 'Profile',
                'form' => $form->createView(),
                'form_submit_label' => 'Save',
                'user' => $user,
            ));
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function previewGravatarAction(Request $request)
    {
        $email = $request->query->get('email');
        return $this->redirect($this->get('campaignchain.core.user')->generateGravatarUrl($email));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function grabGravatarAction(Request $request)
    {
        $email = $request->request->get('email');
        $avatarPath = $this->get('campaignchain.core.user')->downloadGravatarImage($email);

        return new JsonResponse([
            'path' => $avatarPath,
            'url' => $this->get('campaignchain.core.service.file_upload')->getPublicUrl($avatarPath),
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function cropAvatarAction(Request $request)
    {
        $lastUpload = $request->getSession()->get('campaignchain_last_uploaded_avatar');
        $imageLoader = $this->get('liip_imagine.binary.loader.uploads');
        $filterManager = $this->get('liip_imagine.filter.manager');
        $userService = $this->get('campaignchain.core.user');
        $fileUploadService = $this->get('campaignchain.core.service.file_upload');

        try {
            $image = $imageLoader->find($lastUpload);
        } catch (NotLoadableException $e) {
            throw new NotFoundHttpException("No pending avatar upload found", $e);
        }

        $requestVars = $request->request;

        $croppedImage = $filterManager->applyFilter($image, "cropper", array(
            'filters' => array(
                'crop' => array(
                    'start' => array($requestVars->get('x', 0), $requestVars->get('y', 0)),
                    'size' => array($requestVars->get('width'), $requestVars->get('height')),
                ),
                'rotate' => array(
                    'angle' => $requestVars->get('rotate')
                )
            )
        ));

        $newPath = $userService->storeImageAsAvatar($croppedImage);
        $fileUploadService->deleteFile($lastUpload);

        $imageUrl = $this->get('liip_imagine.cache.manager')->getBrowserPath($fileUploadService->getPublicUrl($newPath),
            'avatar');

        return new JsonResponse(array(
            'path' => $newPath,
            'url' => $imageUrl,
        ));
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function changePasswordAction(Request $request)
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw $this->createAccessDeniedException('This user does not have access to this section.');
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

            return $this->redirectToRoute('campaignchain_core_profile_edit');
        }

        return $this->render('CampaignChainCoreBundle:Profile:changePassword.html.twig', array(
            'form' => $form->createView(),
            'page_title' => 'Change Password',
        ));
    }

}