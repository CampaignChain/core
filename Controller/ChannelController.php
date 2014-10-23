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
use CampaignChain\CoreBundle\Entity\Channel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

class ChannelController extends Controller
{
    public function indexAction(){
        $repository = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:Channel');

        $query = $repository->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery();

        $repository_channels = $query->getResult();

        if(!count($repository_channels)){
            $docUrl = $this->container->get('templating.helper.assets')
                ->getUrl(
                    'bundles/campaignchaindochtml/user/get_started.html#connect-to-a-channel',
                    null
                );

            $this->get('session')->getFlashBag()->add(
                'warning',
                'No channels defined yet. To learn how to create one, please <a href="#" onclick="popupwindow(\''.$docUrl.'\',\'\',900,600)">consult the documentation</a>.'
            );
        }

        return $this->render(
            'CampaignChainCoreBundle:Channel:index.html.twig',
            array(
                'page_title' => 'Channels',
                'repository_channels' => $repository_channels
            ));
    }

    public function newAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('module', 'entity', array(
                'label' => 'Channel',
                'class' => 'CampaignChainCoreBundle:ChannelModule',
                'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('m')
                            ->orderBy('m.displayName', 'ASC');
                    },
                'property' => 'displayName',
                'empty_value' => 'Select a channel',
                'empty_data' => null,
                'attr' => array(
                    'show_image' => true,
                )
            ))
            ->add('is_personal', 'checkbox', array(
                'label'     => 'Is this your personal channel?',
                'required'  => false,
                'attr' => array(
                    'align_with_widget' => true,
                ),
            ))
            ->add('save', 'submit', array(
                'label' => 'Next'
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $channel = new Channel();
//            $channel->setName($form->getData()['name']);

            // Is this a personal channel of the currently logged in user?
            if($form->getData()['is_personal']){
                $user = $this->container->get('security.context')->getToken()->getUser();
                // Store user ID for channel
                $channel->setUserId($user->getId());
            }

            $module = $form->getData()['module'];
            $wizard = $this->get('campaignchain.core.channel.wizard');
            $wizard->start($channel, $module);

            return $this->redirect(
                $this->generateUrl(
                    $module->getRoutes()['new']
                )
            );
        }

        return $this->render(
            'CampaignChainCoreBundle:Base:new.html.twig',
            array(
                'page_title' => 'Connect New Location',
                'form' => $form->createView(),
            ));
    }

    public function apiListActivitiesAction(Request $request, $id){
        $location = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:Location')
            ->find($id);

        if (!$location) {
            throw new \Exception(
                'No channel found for id '.$id
            );
        }

        // Get the modules of type "activity" that are related to the channel.
        $activityModules = $location->getChannel()->getChannelModule()->getActivityModules();

        $response = array();

        // TODO: Check whether there are any activity modules.
//        if($activityModules->count()){
            foreach($activityModules as $activityModule){
                $response[] = array(
                    'id' => $activityModule->getId(),
                    'display_name' => $activityModule->getDisplayName(),
                    'name' => $activityModule->getIdentifier(),
                );
            }
//        }

        $encoders = array(new JsonEncoder());
        $normalizers = array(new GetSetMethodNormalizer());

        $serializer = new Serializer($normalizers, $encoders);

        return new Response($serializer->serialize($response, 'json'));
    }
}