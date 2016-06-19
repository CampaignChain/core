<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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