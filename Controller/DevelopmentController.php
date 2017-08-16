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

use CampaignChain\CoreBundle\Fixture\FileLoader;
use CampaignChain\CoreBundle\Util\SystemUtil;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class DevelopmentController extends Controller
{
    const DATA_DIR  = 'Resources/data/campaignchain';
    const DATA_FILE = 'data.yml';

    public function sampleDataAction(Request $request){
        // TODO: Test whether finding all data files also works if CampaignChain is in src/ as well as vendors/.
        $dataRoot = realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);
        $dataFiles = $this->getDataFiles($dataRoot);

        $formData = array();
        $form = $this->createFormBuilder($formData)
            ->add('dataFile', 'choice', array(
                'label' => 'Sample Data Package',
                'choices' => $dataFiles,
                'multiple' => false,
                'required' => false,
                'placeholder' => 'Choose the sample data package to be imported',
                'attr' => array(
                    'help_text' => "You can install data packages through Composer commands, e.g.'composer require amariki/data-test'",
                )
            ))
            ->add('includeFile', 'file', array(
                'label' => 'Include File',
                'required' => false,
                'attr' => array(
                    'help_text' => 'Provide an additional credentials file, e.g. to load critical data such as passwords and access tokens.',
                )
            ))
            ->add('drop', 'checkbox', array(
                'label'     => 'Drop tables?',
                'required'  => false,
                'data'     => true,
                'attr' => array(
                    'align_with_widget' => true,
                    'help_text' => 'Activating this checkbox will delete out all your data and replace it with the sample data!',
                ),
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $files[] = $form->get('dataFile')->getData();

            // Include the credentials file provided by the user
            if($form['includeFile']->getData()){
                $includeFileName = mt_rand().'.yml';
                $form['includeFile']->getData()->move(sys_get_temp_dir(), $includeFileName);
                $includeFile = $files[] =
                    sys_get_temp_dir().DIRECTORY_SEPARATOR.$includeFileName;
            }

            /** @var FileLoader $fixtureService */
            $fixtureService = $this->get('campaignchain.core.fixture');

            if($fixtureService->load($files, $form->get('drop')->getData())){
                // Clean up include file
                if($form['includeFile']->getData()){
                    $fs = new Filesystem();
                    $fs->remove($includeFile);
                }

                $this->get('session')->getFlashBag()->add(
                    'success',
                    'Sample data was loaded successfully.'
                );

                // Check if the current user has been overwritten by the
                // sample data.
                $username = $this->get('security.context')->getToken()->getUser()->getUsername();
                $user = $this->getDoctrine()
                    ->getRepository('CampaignChainCoreBundle:User')
                    ->findOneByUsername($username);

                if(!$user){
                    // Sample data overwrote the current user, hence
                    // log the user out to redirect to login screen.
                    return $this->redirect(
                        $this->generateUrl('fos_user_security_logout')
                    );
                } else {
                    // User still exists, hence update the session data
                    $this->get('session')->set('campaignchain.locale', $user->getLocale());
                    $this->get('session')->set('campaignchain.timezone', $user->getTimezone());
                    $this->get('session')->set('campaignchain.dateFormat', $user->getDateFormat());
                    $this->get('session')->set('campaignchain.timeFormat', $user->getTimeFormat());
                }
            } elseif($fixtureService->getException()){
                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $fixtureService->getException()->getMessage()
                );
            }
        }

        return $this->render(
            'CampaignChainCoreBundle:Base:new.html.twig',
            array(
                'page_title' => 'Load Sample Data',
                'form' => $form->createView(),
                'form_submit_label' => 'Upload',
            ));
    }

    public function resetSystemAction(Request $request){
        $formData = array();
        $form = $this->createFormBuilder($formData)
            ->add('confirm', 'checkbox', array(
                'label'     => 'Confirm',
                'required'  => false,
                'data'     => false,
                'attr' => array(
                    'align_with_widget' => true,
                    'help_text' => 'Please confirm that you aware that all data will be lost.',
                ),
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid() && !$form['confirm']->getData()) {
            $this->get('session')->getFlashBag()->add(
                'warning',
                'Please confirm.'
            );
        } elseif ($form->isValid() && $form['confirm']->getData()) {
            SystemUtil::resetApp();

            header('Location: ../../campaignchain/install.php');
            exit;
        }

        return $this->render(
            'CampaignChainCoreBundle:Base:new.html.twig',
            array(
                'page_title' => 'Reset System',
                'form' => $form->createView(),
                'form_submit_label' => 'Reset',
                'blockui' => true,
            ));
    }

    private function updateEntitiesDates($entities){
        foreach($entities as $entity){
            $hookConfig = $this->getDoctrine()->getRepository('CampaignChainCoreBundle:Hook')->findOneByIdentifier($entity->getTriggerHook()->getIdentifier());
            $hookService = $this->get($hookConfig->getServices()['entity']);
            $hook = $hookService->getHook($entity);
            $entity->setStartDate($hook->getStartDate());
            $entity->setEndDate($hook->getEndDate());

            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
        }
    }

    protected function getDataFiles($rootDir){
        $finder = new Finder();
        // Find all the data files.
        $finder->files()->in($rootDir)->path(self::DATA_DIR)->name(self::DATA_FILE);

        $dataFiles = array();

        foreach($finder as $file){
            $bundleRoot = str_replace(self::DATA_DIR.'/'.self::DATA_FILE, '', $file->getRealpath());
            $composerFile = $bundleRoot.'composer.json';
            $composerJSON = json_decode(file_get_contents($composerFile));

            $dataFiles[$file->getRealpath()] = $composerJSON->name;
        }

        return $dataFiles;
    }
}