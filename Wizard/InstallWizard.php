<?php

/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
