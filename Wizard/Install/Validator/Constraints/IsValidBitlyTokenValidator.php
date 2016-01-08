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

use Guzzle\Http\Exception\ServerErrorResponseException;
use Hpatoio\Bitly\Client;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


class IsValidBitlyTokenValidator extends ConstraintValidator
{

    /**
     * Test if the provided token is not empty and working with bitly
     *
     * @param mixed $value
     * @param Constraint $constraint
     * @throws \Exception
     */
    public function validate($value, Constraint $constraint)
    {

        $bitlyClient = new Client($value);

        try {
            $bitlyClient->Shorten(array("longUrl" => 'http://www.campaignchain.com'));
        } catch (ServerErrorResponseException $e) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%string%', $value)
                ->addViolation();
        } catch (\Exception $e) {
            // rethrow it
            throw $e;
        }


    }

}