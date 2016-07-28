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


namespace CampaignChain\CoreBundle\Wizard\Install\Validator\Constraints;

use CampaignChain\CoreBundle\Entity\Link;
use CampaignChain\CoreBundle\Exception\ExternalApiException;
use CampaignChain\CoreBundle\Service\UrlShortener\UrlShortenerService;
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
        } catch (ExternalApiException $e) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%string%', $value)
                ->addViolation();
        } catch (\Exception $e) {
            // rethrow it
            throw $e;
        }


    }

}