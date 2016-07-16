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

namespace CampaignChain\CoreBundle\Form\Type;

use CampaignChain\CoreBundle\Util\DateTimeUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType as SymfonyDateTimeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class DateTimeType
 * @package CampaignChain\CoreBundle\Form\Type
 *
 * A date time form type that takes into account CampaignChain's user time zone
 * and date time formatting.
 */
class DateTimeType extends AbstractType
{
    /** @var DateTimeUtil $datetime */
    protected $dateTimeUtil;

    public function __construct(DateTimeUtil $dateTimeUtil)
    {
        $this->dateTimeUtil = $dateTimeUtil;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'model_timezone' => 'UTC',
            'view_timezone' => $this->dateTimeUtil->getUserTimezone(),
            'widget' => 'single_text',
            'format' => $this->dateTimeUtil->getUserDatetimeFormat(),
            'date_format' => $this->dateTimeUtil->getUserDateFormat(),
            'input' => 'datetime',
        ));
    }

    public function getParent()
    {
        return SymfonyDateTimeType::class;
    }

    public function getName()
    {
        return 'campaignchain_datetime';
    }
}