<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
