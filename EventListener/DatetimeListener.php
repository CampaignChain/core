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
        if($this->container->get('request_stack')->getCurrentRequest()){
            $entity = $args->getEntity();

            $reflect = new \ReflectionObject($entity);
            foreach ($reflect->getProperties(\ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED) as $prop) {
                $prop->setAccessible(true);
                $value = $prop->getValue($entity);

                if (! $value instanceof \DateTime) {
                    $prop->setAccessible(false);
                    continue;
                }

                // Don't execute this upon login.
                if( $this->container->get('request_stack')->getCurrentRequest()->get('_route') != 'fos_user_security_check' ){
                    $value = $this->datetime->setUserTimezone($value);
                }

                $prop->setValue($entity, $value);
                $prop->setAccessible(false);
            }
        }
    }

    public function prePersist(LifecycleEventArgs $args) {
        // Only execute if HTTP request and not called as command.
        if($this->container->get('request_stack')->getCurrentRequest()){
            $this->locale2UTC($args);
        }
    }

    public function preUpdate(LifecycleEventArgs $args) {
        // Only execute if HTTP request and not called as command.
        if($this->container->get('request_stack')->getCurrentRequest()){
            $this->locale2UTC($args);
        }
    }

    public function locale2UTC(LifecycleEventArgs $args){
        $entity = $args->getEntity();

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
            // $value = DateTimeUtil::roundMinutes($value);

            $prop->setValue($entity, $value);
            $prop->setAccessible(false);
        }
    }
}