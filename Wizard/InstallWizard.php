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

namespace CampaignChain\CoreBundle\Wizard;

use Sensio\Bundle\DistributionBundle\Configurator\Configurator;
use Sensio\Bundle\DistributionBundle\Configurator\Step\StepInterface;

class InstallWizard extends Configurator
{
    protected $steps;
    protected $sortedSteps;

    public function __construct($kernelDir)
    {
        $this->kernelDir = $kernelDir;
        $this->steps = array();
    }

    /**
     * @param StepInterface $step
     * @param int $priority
     */
    public function addStep(StepInterface $step, $priority = 0)
    {
        $step->setContext(array('kernel_dir' => $this->kernelDir));
        if (!isset($this->steps[$priority])) {
            $this->steps[$priority] = array();
        }

        $this->steps[$priority][] = $step;
        $this->sortedSteps = null;
    }

    /**
     * @return StepInterface[]
     */
    public function getSteps()
    {
        if ($this->sortedSteps === null) {
            $this->sortedSteps = $this->getSortedSteps();
            foreach ($this->sortedSteps as $step) {
                // TODO: Create custom Interface and change param requirement.
                $step->setParameters(array());
            }
        }

        return $this->sortedSteps;
    }

    /**
     * Sort routers by priority.
     * The lowest number is the highest priority
     *
     * @return StepInterface[]
     */
    private function getSortedSteps()
    {
        $sortedSteps = array();
        ksort($this->steps);

        foreach ($this->steps as $steps) {
            $sortedSteps = array_merge($sortedSteps, $steps);
        }

        return $sortedSteps;
    }

    public function execute(StepInterface $step, $parameters)
    {
        return $step->execute($parameters);
    }
}
