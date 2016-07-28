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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AboutController extends Controller
{
    public function indexAction()
    {

        // check if this is the first time a user logs in since registration
        $session = $this->getRequest()->getSession();

        if ($session->get('isFirstLogin', false)) {
            $session->remove('isFirstLogin');

            return $this->render(
                'CampaignChainCoreBundle:About:index_with_intro.html.twig',
                array(
                    'page_title' => 'About CampaignChain'
                )
            );
        } else {
            return $this->render(
                'CampaignChainCoreBundle:About:index.html.twig',
                array(
                    'page_title' => 'About CampaignChain'
                )
            );
        }
    }
}
