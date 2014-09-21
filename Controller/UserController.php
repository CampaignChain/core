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
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;

class UserController extends Controller
{
    public function indexAction(){
        $userManager = $this->get('fos_user.user_manager');
        $users = $userManager->findUsers();


        return $this->render('CampaignChainCoreBundle:User:index.html.twig',
            array(
                'users' =>   $users,
                'page_title' => 'Users',
            ));
    }
}