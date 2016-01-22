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

use CampaignChain\CoreBundle\Entity\Link;
use CampaignChain\CoreBundle\Service\UrlShortener\UrlShortenerService;
use Mremi\UrlShortener\Exception\InvalidApiResponseException;
use Mremi\UrlShortener\Provider\Bitly\BitlyProvider;
use Mremi\UrlShortener\Provider\Bitly\GenericAccessTokenAuthenticator;
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

        $bitlyProvider = new BitlyProvider(new GenericAccessTokenAuthenticator($value));
        $urlShortener = new UrlShortenerService($bitlyProvider);
        $link = new Link();
        $link->setLongUrl('http://www.campaignchain.com');

        try {
            $urlShortener->shorten($link);
        } catch (InvalidApiResponseException $e) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%string%', $value)
                ->addViolation();
        } catch (\Exception $e) {
            // rethrow it
            throw $e;
        }


    }

}