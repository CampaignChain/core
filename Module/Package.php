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

namespace CampaignChain\CoreBundle\Module;

class Package
{
    private $packages;

    public function __construct($root, $env = 'prod')
    {
        $composerLock = json_decode(file_get_contents(
                $root.
                DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'composer.lock')
        );

        $this->packages = $composerLock->packages;

        // Also get required dev packages if in dev mode
        if($env == 'dev'){
            $this->packages = array_merge($this->packages, $composerLock->{'packages-dev'});
        }
    }

    public function getVersion($name) {
        foreach($this->packages as $package) {
            if($package->name == $name){
                return $package->version;
            }
        }
        return null;
    }
}