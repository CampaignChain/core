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
