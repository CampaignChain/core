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

use CampaignChain\CoreBundle\Entity\Theme;
use CampaignChain\CoreBundle\Entity\User;
use CampaignChain\CoreBundle\Form\Type\ThemeType;
use CampaignChain\CoreBundle\Service\FileUploadService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ThemeController
 * @package CampaignChain\CoreBundle\Controller
 */
class ThemeController extends Controller
{
    /**
     * @param Request $request
     * @param User $userToEdit
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function settingsAction(Request $request)
    {
        /** @var Theme $theme */
        $theme = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:Theme')
            ->find(1);

        $form = $this->createForm(ThemeType::class, $theme);

        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var FileUploadService $fileUploadService */
            $fileUploadService = $this->get('campaignchain.core.service.file_upload');

            $path = Theme::STORAGE_PATH.'/logo.png';
            /** @var UploadedFile $upload */
            $upload = $theme->getLogo();
            if($upload) {
                $uploadContent = file_get_contents($upload->getRealPath());
                $fileUploadService->deleteFile($path);
                $fileUploadService->storeImage($path, $uploadContent);
            }
            $theme->setLogo($path);

            $path = Theme::STORAGE_PATH.'/favicon.ico';
            /** @var UploadedFile $upload */
            $upload = $theme->getFavicon();
            if($upload) {
                $uploadContent = file_get_contents($upload->getRealPath());
                $fileUploadService->deleteFile($path);
                $fileUploadService->storeImage($path, $uploadContent);
            }
            $theme->setFavicon($path);

            $this->getDoctrine()->getManager()->flush();

            $this->addFlash(
                'success',
                'The theme settings were successfully changed!'
            );
        }

        return $this->render('CampaignChainCoreBundle:Base:new.html.twig', array(
            'form' => $form->createView(),
            'page_title' => 'Theme Settings',
        ));
    }
}