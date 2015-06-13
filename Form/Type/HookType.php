<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

abstract class HookType extends AbstractType
{
    protected $campaign;
    protected $view;
    protected $hooksOptions = array();

    public function setCampaign($campaign){
        $this->campaign = $campaign;
    }

    public function setView($view){
        $this->view = $view;
    }

    public function setHooksOptions(array $hooksOptions){
        $this->hooksOptions = $hooksOptions;
    }
}