<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EventListener;

use CampaignChain\CoreBundle\Util\DateTimeUtil;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DatetimeListener {

    protected $container;

    private $datetime;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->datetime = $this->container->get('campaignchain.core.util.datetime');
    }

    public function postLoad(LifecycleEventArgs $args) {
        // Only execute if HTTP request and not called as command.
        if($this->container->isScopeActive('request')){
            $entity = $args->getEntity();
            $em = $args->getEntityManager();

            $reflect = new \ReflectionObject($entity);
            foreach ($reflect->getProperties(\ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED) as $prop) {
                $prop->setAccessible(true);
                $value = $prop->getValue($entity);

                if (! $value instanceof \DateTime) {
                    $prop->setAccessible(false);
                    continue;
                }
//                echo 'Timezone in DB: '.$value->getTimezone()->getName();

                // Don't execute this upon login.
                if( $this->container->get('request')->get('_route') != 'fos_user_security_check' ){
                    $value = $this->datetime->setUserTimezone($value);
                }

                $prop->setValue($entity, $value);
                $prop->setAccessible(false);
            }
        }
    }

    public function prePersist(LifecycleEventArgs $args) {
        // Only execute if HTTP request and not called as command.
        if($this->container->isScopeActive('request')){
            $this->locale2UTC($args);
        }
    }

    public function preUpdate(LifecycleEventArgs $args) {
        // Only execute if HTTP request and not called as command.
        if($this->container->isScopeActive('request')){
            $this->locale2UTC($args);
        }
    }

    public function locale2UTC(LifecycleEventArgs $args){
        $entity = $args->getEntity();
        $em = $args->getEntityManager();

        $reflect = new \ReflectionObject($entity);
        foreach ($reflect->getProperties(\ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED) as $prop) {
            $prop->setAccessible(true);
            $value = $prop->getValue($entity);

            if (! $value instanceof \DateTime) {
                $prop->setAccessible(false);
                continue;
            }

            $value->setTimezone(new \DateTimeZone('UTC'));

            // Round date to 5 minute increments, because that's the minimum time interval of the scheduler.
            // The frontend should actually take care of this. Hence, this is a fallback solution.
            $value = DateTimeUtil::roundMinutes($value);

            $prop->setValue($entity, $value);
            $prop->setAccessible(false);
        }
    }
}