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

namespace CampaignChain\CoreBundle\Service;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Clones a route.
 *
 * Class RoutingLoaderService
 * @package CampaignChain\CoreBundle\Service
 */
class RoutingLoaderService extends Loader {

    var $typePrefix = 'annotation/';

    public function load($resource, $type = null)
    {
        $routeNamePrefix = substr($type,strlen($this->typePrefix));

        $collection = new RouteCollection();
        $routes = $this->import($resource);
        $routes2 = clone $routes;

        foreach ($routes as $name => $route) {
            $routes2->add($routeNamePrefix.$name,$route);
            $routes2->remove($name);
        }

        $collection->addCollection($routes2);

        return $collection;
    }

    public function supports($resource, $type = null)
    {
        return substr($type, 0, strlen($this->typePrefix)) === $this->typePrefix;
    }
}