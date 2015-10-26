<?php
/**
 * Created by PhpStorm.
 * User: andrasratz
 * Date: 09/10/15
 * Time: 17:07
 */

namespace CampaignChain\CoreBundle\Entity;


interface AssignableInterface
{
    public function getAssignee();

    public function setAssignee($user);
}