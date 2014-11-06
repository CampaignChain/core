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

class ExecuteController extends Controller
{
    public function indexAction()
    {
        return $this->render(
            'CampaignChainCoreBundle:Execute:index.html.twig',
            array(
                'page_title' => 'Execute'
            )
        );
    }
}
