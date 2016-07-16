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

use Symfony\Component\HttpFoundation\Request;

class Session
{
    private $request;

    protected $session;

    public function __construct($request){
        $this->request = $request;
    }

    public function start(){
        $this->session = $this->request->getSession();
        $this->destroy();
    }

    public function resume(){
        $this->session = $this->request->getSession();
    }

    public function set($key, $data)
    {
        $wizardData = $this->session->get('CAMPAIGNCHAIN_WIZARD');
        // If data for key already exists, then remove it.
        if(isset($wizardData[$key])){
            unset($wizardData[$key]);
        }
        $wizardDataNew = array(
            $key => $data,
        );

        if(is_array($wizardData) && count($wizardData)){
            $wizardDataNew = $wizardData + $wizardDataNew;
        }

        $this->session->set('CAMPAIGNCHAIN_WIZARD', $wizardDataNew);
    }

    public function get($key)
    {
        $wizardData = $this->session->get('CAMPAIGNCHAIN_WIZARD');
        return $wizardData[$key];
    }

    public function has($key)
    {
        $wizardData = $this->session->get('CAMPAIGNCHAIN_WIZARD');
        return isset($wizardData[$key]);
    }

    public function destroy()
    {
        $this->session->remove('CAMPAIGNCHAIN_WIZARD');
    }
}
