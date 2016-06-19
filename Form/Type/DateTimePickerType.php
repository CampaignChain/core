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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

/**
 * Class DateTimePickerType
 * @package CampaignChain\CoreBundle\Form\Type
 *
 * A date time picker form type that takes into account CampaignChain's user
 * time zone and date time formatting. It displays a JavaScript date time picker
 * to select date and time.
 */
class DateTimePickerType extends AbstractType
{
    /** @var DateTimeUtil $datetime */
    protected $dateTimeUtil;

    public function __construct(DateTimeUtil $dateTimeUtil)
    {
        $this->dateTimeUtil = $dateTimeUtil;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $additionalOptions = array(
            'startDate' => $options['start_date'],
            'endDate' => $options['end_date']
        );

        $view->vars['pickerOptions'] = array_merge(
            $view->vars['pickerOptions'], $additionalOptions
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'model_timezone' => 'UTC',
            'view_timezone' => $this->dateTimeUtil->getUserTimezone(),
            'end_date' => null,
            'pickerOptions' => array(
                'format' => $this->dateTimeUtil->getUserDatetimeFormat('datepicker'),
                'weekStart' => 0,
                'autoclose' => true,
                'startView' => 'month',
                'minView' => 'hour',
                'maxView' => 'decade',
                'todayBtn' => false,
                'todayHighlight' => true,
                'keyboardNavigation' => true,
                'language' => 'en',
                'forceParse' => true,
                'minuteStep' => 5,
                'pickerReferer ' => 'default', //deprecated
                'pickerPosition' => 'bottom-right',
                'viewSelect' => 'hour',
                'showMeridian' => false,
            ),
        ));

        $resolver->setRequired('start_date');
    }

    public function getParent()
    {
        return 'collot_datetime';
    }

    public function getName()
    {
        return 'campaignchain_datetimepicker';
    }
}