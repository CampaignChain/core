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

class ChannelWizard
{
    private $session;
    private $container;

    public function setContainer($container){
        $this->container = $container;
        $this->session = new Session($this->container->get('request'));
    }

    public function start($channel, $module){
        // Store in session
        $this->session->start();
        $this->session->set('campaignchain_channel', $channel);
        $this->session->set('campaignchain_module', $module);
    }

    public function getChannel(){
        $this->session->resume();
        return $this->session->get('campaignchain_channel');
    }

    public function setName($name){
        $this->session->resume();
        $channel = $this->session->get('campaignchain_channel');
        $channel->setName($name);
        $this->session->set('campaignchain_channel', $channel);
    }

    public function addLocation($identifier, $location){
        $this->session->resume();
        if($this->session->has('campaignchain_locations')){
            $locations = $this->session->get('campaignchain_locations');
        }
        $locations[$identifier] = $location;
        $this->session->set('campaignchain_locations', $locations);
    }

    public function getLocation($identifier){
        $this->session->resume();
        if($this->session->has('campaignchain_locations')){
            $locations = $this->session->get('campaignchain_locations');
        }
        return $locations[$identifier];
    }

    public function removeLocation($identifier){
        $this->session->resume();
        if($this->session->has('campaignchain_locations')){
            $locations = $this->session->get('campaignchain_locations');
            unset($locations[$identifier]);
            $this->setLocations($locations);
            return true;
        }

        return false;
    }

    public function setLocations(array $locations){
        $this->session->resume();
        $this->session->set('campaignchain_locations', $locations);
    }

    public function getLocations(){
        $this->session->resume();
        return $this->session->get('campaignchain_locations');
    }

    public function set($key, $val){
        // TODO: If key is 'channel' or 'module', throw error.
        $this->session->resume();
        $this->session->set($key, $val);
    }

    public function get($key){
        $this->session->resume();
        return $this->session->get($key);
    }

    public function has($key){
        return $this->session->has($key);
    }

    public function persist(){
        $this->session->resume();
        $channel = $this->session->get('campaignchain_channel');

        // Set the Tracking ID for the Channel.
        $channelService = $this->container->get('campaignchain.core.channel');
        $trackingId = $channelService->generateTrackingId();
        $channel->setTrackingId($trackingId);

        // TODO: Check whether bundle set channel name.
        // If not, throw exception.

        $repository = $this->container->get('doctrine')->getManager();

        // Bring detached entities from session back into repository manager
        $module = $this->session->get('campaignchain_module');
        $module = $repository->merge($module);
        $channel->setChannelModule($module);

        $locations = $this->session->get('campaignchain_locations');
        // TODO: Error if not minimum 1 location has been provided.
        foreach($locations as $identifier => $location){
            $location = $repository->merge($location);
            $location->setChannel($channel);
            $channel->addLocation($location);
        }

        $repository->persist($channel);
        $repository->flush();

        return $channel;
    }

    public function end(){
        $this->session->resume();
        $this->session->destroy();
    }
}