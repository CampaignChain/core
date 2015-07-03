<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Util;

use Symfony\Component\DependencyInjection\ContainerInterface;

class DateTimeUtil
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getNow($timezone = 'UTC'){
        return new \DateTime('now', new \DateTimeZone($timezone));
    }

    public function getUserNow(){
        return new \DateTime('now', new \DateTimeZone($this->container->get('session')->get('campaignchain.timezone')));
    }

    public function setUserTimezone(\DateTime $dateTime){
        if($this->container->get('session')->isStarted()){
            $timezone = $this->container->get('session')->get('campaignchain.timezone');
            if(empty($timezone)){
                $timezone = 'UTC';
            }
        } else {
            $timezone = 'UTC';
        }

        return $dateTime->setTimezone(new \DateTimeZone($timezone));
    }

    public function isUserTimezone(\DateTime $dateTime){
        return $dateTime->getTimezone()->getName() == $this->container->get('session')->get('campaignchain.timezone');
    }

    /*
     * A Symfony form sends datetime back in UTC timezone,
     * because no timezone string has been provided with the posted data.
     */
    public function modifyTimezoneOffset(\DateTime $dateTime){
        $userTimezone = new \DateTimeZone($this->container->get('session')->get('campaignchain.timezone'));
        $offset = $userTimezone->getOffset($dateTime);
        return $dateTime->modify('-'.$offset.' sec');
    }

    public function getUserLocale(){
        return $this->container->get('session')->get('campaignchain.locale');
    }

    public function getUserTimezone(){
        return $this->container->get('session')->get('campaignchain.timezone');
    }

    public function getUserDatetimeFormat($format = 'default'){
        $datetimeFormat = $this->container->get('session')->get('campaignchain.dateFormat').' '.$this->container->get('session')->get('campaignchain.timeFormat');
        switch($format){
            case 'php_date':
                return $this->convertToPHPDateFormat($datetimeFormat);
                break;
            case 'moment_js':
                return $this->convertToMomentJSFormat($datetimeFormat);
                break;
            case 'datepicker':
                return $this->convertToDatepickerFormat($datetimeFormat);
                break;
            case 'default':
                return $datetimeFormat;
                break;
        }
    }

    public function getUserDateFormat(){
        return $this->container->get('session')->get('campaignchain.dateFormat');
    }

    public function getUserTimeFormat(){
        return $this->container->get('session')->get('campaignchain.timeFormat');
    }

    public function getRemainingTime($futureDate, $format = 'string'){
        $now = new \DateTime('now', new \DateTimeZone($this->getUserTimezone()));

        if($futureDate <= $now){
            throw new \Exception('Date must not be in the past.');
        }

        $interval = $futureDate->diff($now);

        if($format == 'string'){
            $dateStringParts = array();

            $year = $interval->format("%y");
            if($year != '0'){
                if($year == '1'){
                    $dateStringParts[] = $year.' year';
                } else {
                    $dateStringParts[] = $year.' years';
                }
            }

            $month = $interval->format("%m");
            if($month != '0'){
                if($month == '1'){
                    $dateStringParts[] = $month.' month';
                } else {
                    $dateStringParts[] = $month.' months';
                }
            }

            $day = $interval->format("%d");
            if($day != '0' && $year == '0'){
                if($day == '1'){
                    $dateStringParts[] = $day.' day';
                } else {
                    $dateStringParts[] = $day.' days';
                }
            }

            $hour = $interval->format("%h");
            if($hour != '0' && $year == '0' && $month == '0'){
                if($hour == '1'){
                    $dateStringParts[] = $hour.' hour';
                } else {
                    $dateStringParts[] = $hour.' hours';
                }
            }

            $minute = $interval->format("%i");
            if($minute != '0' && $year == '0' && $month == '0' && $day == '0'){
                if($minute == '1'){
                    $dateStringParts[] = $minute.' minute';
                } else {
                    $dateStringParts[] = $minute.' minutes';
                }
            }

            $second = $interval->format("%s");
            if($second != '0' && $year == '0' && $month == '0' && $day == '0' && $hour == '0'){
                if($second == '1'){
                    $dateStringParts[] = $second.' second';
                } else {
                    $dateStringParts[] = $second.' seconds';
                }
            }

            return implode(', ', $dateStringParts);
        } else {
            throw new \Exception('Unknown format "'.$format.'"');
        }
    }

    static function roundMinutes($datetime){
        // 1) Set number of seconds to 0 (by rounding up to the nearest minute).
        $second = $datetime->format("s");
        $datetime->add(new \DateInterval("PT".(60-$second)."S"));
        // 2) Round to 5 minute increment.
        $minutes = (round($datetime->format("i")/5) * 5) % 60;
        // 3) Set rounded minutes.
        $datetime->setTime($datetime->format('H'), $minutes, 0);

        return $datetime;
    }

    static function isWithinDuration(\DateTime $start, \DateTime $moment, \DateTime $end, $timezone = 'UTC'){
        // Set all dates to UTC to normalize timezone.
        $utc = new \DateTimeZone($timezone);
        $start->setTimezone($utc);
        $moment->setTimezone($utc);
        $end->setTimezone($utc);

        if($moment > $start && $moment < $end){
            return true;
        }

        return false;
    }

    public function formatLocale(\DateTime $object, $format = null, $timezone = null){
        if(!$timezone){
            $timezone = $this->container->get('session')->get('campaignchain.timezone');
        }

        switch($format){
            case 'ISO8601':
                $object->setTimezone(
                    new \DateTimeZone($timezone)
                );
                return $object->format(\DateTime::ISO8601);
                break;
            default:
                // Apply timezone and locale to DateTime object
                $localeFormat = new \IntlDateFormatter(
                    $this->container->get('session')->get('campaignchain.locale'),
                    \IntlDateFormatter::FULL,
                    \IntlDateFormatter::FULL,
                    $timezone
                );
                $localeFormat->setPattern($this->container->get('session')->get('campaignchain.dateFormat').' '.$this->container->get('session')->get('campaignchain.timeFormat'));
                return $localeFormat->format($object);
                break;
        }
    }

    public function getLocalizedTime(\DateTime $object, $timezone = null){
        if(!$timezone){
            $timezone = $this->container->get('session')->get('campaignchain.timezone');
        }

        // Apply timezone and locale to DateTime object
        $localeFormat = new \IntlDateFormatter(
            $this->container->get('session')->get('campaignchain.locale'),
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            $timezone
        );
        $localeFormat->setPattern($this->container->get('session')->get('campaignchain.timeFormat'));
        return $localeFormat->format($object);
    }

    private function convertToPHPDateFormat($format){
        $patterns[] = '/mm/';
        $patterns[] = '/dd/';
        $patterns[] = '/MM/';
        $patterns[] = '/HH/';
        $patterns[] = '/yyyy/';

        $replacements[] = 'i';
        $replacements[] = 'd';
        $replacements[] = 'm';
        $replacements[] = 'H';
        $replacements[] = 'Y';

        return preg_replace($patterns, $replacements, $format);
    }

    private function convertToMomentJSFormat($format){
        // moment.js re-formatting
        $patterns[] = '/d/';
        $patterns[] = '/yyyy/';
        $patterns[] = '/EEE/';
        $patterns[] = '/EEEE/';

        $replacements[] = 'D';
        $replacements[] = 'YYYY';
        $replacements[] = 'ddd';
        $replacements[] = 'dddd';

        return preg_replace($patterns, $replacements, $format);
    }

    private function convertToDatepickerFormat($format){
        // moment.js re-formatting
        $patterns[] = '/m/';
        $patterns[] = '/mm/';
        $patterns[] = '/M/';
        $patterns[] = '/MM/';
        $patterns[] = '/h/';
        $patterns[] = '/hh/';
        $patterns[] = '/H/';
        $patterns[] = '/HH/';

        $replacements[] = 'i';
        $replacements[] = 'ii';
        $replacements[] = 'm';
        $replacements[] = 'mm';
        $replacements[] = 'H';
        $replacements[] = 'HH';
        $replacements[] = 'h';
        $replacements[] = 'hh';

        return preg_replace($patterns, $replacements, $format);
    }
}