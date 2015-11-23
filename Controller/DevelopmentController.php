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

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Filesystem\Filesystem;
use Sonata\AdminBundle\Command\SetupAclCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
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
                'empty_value' => 'Choose the sample data package to be imported',
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
            // Create Alice manager and fixture set
            $manager = $this->get('h4cc_alice_fixtures.manager');
            $set = $manager->createFixtureSet();

            // Add the fixture files
            $dataFilePath = $form->get('dataFile')->getData();
            $set->addFile($dataFilePath, 'yaml');

            // Include the credentials file provided by the user
            if($form['includeFile']->getData()){
                $includeFileName = mt_rand().'.yml';
                $form['includeFile']->getData()->move(sys_get_temp_dir(), $includeFileName);
                $includeFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.$includeFileName;
                $set->addFile($includeFile, 'yaml');
            }

            $set->setDoDrop($form->get('drop')->getData());
            // TODO Keep Module data intact
            $em = $this->getDoctrine()->getManager();
            $bundles =   $em->getRepository("CampaignChain\CoreBundle\Entity\Bundle")->findAll();
            $modules = $em->getRepository("CampaignChain\CoreBundle\Entity\Module")->findAll();

            $set->setDoPersist(true);
            $set->setSeed(1337 + 42);
            if($manager->load($set)){
                // Clean up include file
                if($form['includeFile']->getData()){
                    $fs = new Filesystem();
                    $fs->remove($includeFile);
                }

                $this->get('session')->getFlashBag()->add(
                    'success',
                    'Sample data was loaded successfully.'
                );

                // TODO: Restore modules data
                foreach($bundles as $bundle){
                    $em->persist($bundle);
                }
                foreach($modules as $module){
                    $em->persist($module);
                }
                $em->flush();

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
            $kernelFile = $this->get('kernel')->getRootDir().DIRECTORY_SEPARATOR.'campaignchain_bundles.php';
            $configDir = $this->get('kernel')->getRootDir().DIRECTORY_SEPARATOR.'config';
            $configFile = $configDir.DIRECTORY_SEPARATOR.'campaignchain'.DIRECTORY_SEPARATOR.'config_bundles.yml';
            $routingFile = $configDir.DIRECTORY_SEPARATOR.'routing.yml';
            $securityFile = $configDir.DIRECTORY_SEPARATOR.'campaignchain'.DIRECTORY_SEPARATOR.'security.yml';

            // Reset files
            $fs = new Filesystem();
            $fs->copy($configFile.'.dist', $configFile, true);
            $fs->copy($routingFile.'.dist', $routingFile, true);
            $fs->copy($securityFile.'.dist', $securityFile, true);
            $fs->remove($kernelFile);
            $fs->dumpFile($kernelFile, '<?php'."\xA");

            // Drop all tables
            $currentDir = getcwd();
            chdir($this->get('kernel')->getRootDir().DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);
            $command = 'php app/console doctrine:schema:drop --force --full-database';
            ob_start();
            system($command, $output);
            ob_get_clean();
            chdir($currentDir);

            header('Location: ../../campaignchain/install.php');
            exit;
        }

        return $this->render(
            'CampaignChainCoreBundle:Base:new.html.twig',
            array(
                'page_title' => 'Reset System',
                'form' => $form->createView(),
                'form_submit_label' => 'Reset',
            ));
    }

    private function updateEntitiesDates($entities){
        foreach($entities as $entity){
            $hookConfig = $this->getDoctrine()->getRepository('CampaignChainCoreBundle:Hook')->findOneByIdentifier($entity->getTriggerHook()->getIdentifier());
            $hookService = $this->get($hookConfig->getServices()['entity']);
            $hook = $hookService->getHook($entity);
            $entity->setStartDate($hook->getStartDate());
            $entity->setEndDate($hook->getEndDate());

            $repository = $this->getDoctrine()->getManager();
            $repository->persist($entity);
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