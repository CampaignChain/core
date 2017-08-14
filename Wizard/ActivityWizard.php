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

namespace CampaignChain\CoreBundle\Wizard;

use CampaignChain\CoreBundle\Entity\Activity;
use Symfony\Component\HttpFoundation\Request;
use CampaignChain\CoreBundle\Wizard\Session;

class ActivityWizard
{
    private $session;
    private $container;

    public function setContainer($container){
        $this->container = $container;
        $this->session = new Session($this->container->get('request_stack')->getCurrentRequest());
    }

    public function start($campaign, $activityModule, $location = null){
        // Store in session
        $this->session->start();
        $this->session->set('campaignchain_campaign', $campaign);
        $this->session->set('campaignchain_activityModule', $activityModule);
        $this->session->set('campaignchain_referrer', $_SERVER['HTTP_REFERER']);

        if($location){
            $this->session->set('campaignchain_location', $location);
            $this->session->set('campaignchain_channel', $location->getChannel());
            $this->session->set('campaignchain_channelModule', $location->getChannel()->getChannelModule());
            // Fixes lazy loading issue
            $bundle = clone $location->getChannel()->getChannelModule()->getBundle();
            $this->session->set('campaignchain_channelModuleBundle', $bundle);
        }
    }

    public function getCampaign(){
        $this->session->resume();
        return $this->session->get('campaignchain_campaign');
    }

    public function getLocation(){
        $this->session->resume();
        if($this->session->has('campaignchain_location')) {
            return $this->session->get('campaignchain_location');
        }

        return null;
    }

    public function getChannel(){
        $this->session->resume();
        return $this->session->get('campaignchain_channel');
    }

    public function getChannelModule(){
        $this->session->resume();
        return $this->session->get('campaignchain_channelModule');
    }

    public function getChannelModuleBundle(){
        $this->session->resume();
        return $this->session->get('campaignchain_channelModuleBundle');
    }

    public function setOperation($operation){
        $this->session->resume();
        $this->session->set('campaignchain_operation', $operation);
    }

    public function getActivityModule()
    {
        $this->session->resume();
        return $this->session->get('campaignchain_activityModule');
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

    public function getReferrer(){
        $this->session->get('campaignchain_referrer');
    }

    public function getNewActivity(){
        // Reset memory of pre-selected campaign.
        $this->container->get('session')->set('campaignchain.campaign', null);

        // Merge context with persistence manager.
        $repository = $this->container->get('doctrine')->getManager();
        $this->session->resume();

        $campaign = $this->session->get('campaignchain_campaign');
        $campaign = $repository->merge($campaign);

        $activityModule = $this->session->get('campaignchain_activityModule');
        $activityModule = $repository->merge($activityModule);

        // Create new Activity.
        $activity = new Activity();
        $activity->setActivityModule($activityModule);
        $activity->setCampaign($campaign);

        if($this->session->has('campaignchain_location')) {
            $location = $this->session->get('campaignchain_location');
            $location = $repository
                ->getRepository('CampaignChainCoreBundle:Location')
                ->find($location);
            //$location = $repository->merge($location);
            $activity->setLocation($location);
            $activity->setChannel($location->getChannel());
        }

        return $activity;
    }

    public function end()
    {
        $this->session->destroy();
    }
}