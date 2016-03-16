<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Wizard\Install\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsValidBitlyToken extends Constraint
{
    public $message = 'The string "%string%" doesn\'t seem to be a valid bitly access token - API test failed.';

    public function validatedBy()
    {
        return get_class($this).'Validator';
    }
}