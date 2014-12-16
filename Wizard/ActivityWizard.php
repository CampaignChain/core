<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Wizard;

use Symfony\Component\HttpFoundation\Request;
use CampaignChain\CoreBundle\Wizard\Session;

class ActivityWizard
{
    private $session;
    private $container;

    public function setContainer($container){
        $this->container = $container;
        $this->session = new Session($this->container->get('request'));
    }

    public function start($campaign, $location, $activity, $activityModule){
        // Store in session
        $this->session->start();
        $this->session->set('campaignchain_campaign', $campaign);
        $this->session->set('campaignchain_location', $location);
        $this->session->set('campaignchain_activity', $activity);
        $this->session->set('campaignchain_activityModule', $activityModule);
    }

    public function getCampaign(){
        $this->session->resume();
        return $this->session->get('campaignchain_campaign');
    }

    public function getLocation(){
        $this->session->resume();
        return $this->session->get('campaignchain_location');
    }

    public function setOperation($operation){
        $this->session->resume();
        $this->session->set('campaignchain_operation', $operation);
    }

    public function getActivity(){
        $this->session->resume();
        return $this->session->get('campaignchain_activity');
    }

    public function setName($name){
        $this->session->resume();
        $activity = $this->session->get('campaignchain_activity');
        $activity->setName($name);
        $this->session->set('campaignchain_activity', $activity);
    }

    public function equalsOperation(bool $equal){
        $this->session->resume();
        $activity = $this->session->get('campaignchain_activity');
        $activity->setEqualsOperation($equal);
        $this->session->set('campaignchain_activity', $activity);
    }

    public function end(){
        $this->session->resume();

        $repository = $this->container->get('doctrine')->getManager();

        $campaign = $this->session->get('campaignchain_campaign');
        $campaign = $repository->merge($campaign);
        $location = $this->session->get('campaignchain_location');
        $location = $repository
            ->getRepository('CampaignChainCoreBundle:Location')
            ->find($location);
//        $location = $repository->merge($location);
        $activityModule = $this->session->get('campaignchain_activityModule');
        $activityModule = $repository->merge($activityModule);

        $activity = $this->session->get('campaignchain_activity');
        $activity->setCampaign($campaign);
        $activity->setLocation($location);
        $activity->setChannel($location->getChannel());
        $activity->setActivityModule($activityModule);

        //$this->session->destroy();

        return $activity;
    }
}