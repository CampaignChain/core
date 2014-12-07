<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * InstallController.
 *
 * Based on Sensio\Bundle\DistributionBundle\Controller\ConfiguratorController;
 *
 * @author Sandro Groganz <sandro@campaignchain.com>
 * @author Fabien Potencier <fabien@symfony.com>
 */
class InstallController extends ContainerAware
{
    public function checkAction()
    {
        $installWizard = $this->container->get('campaignchain.core.install.wizard');

        // Trying to get as much requirements as possible
        $majors = $installWizard->getRequirements();
        $minors = $installWizard->getOptionalSettings();

        $url = $this->container->get('router')->generate('campaignchain_core_install_step', array('index' => 0));

        if (empty($majors) && empty($minors)) {
            return new RedirectResponse($url);
        }

        return $this->container->get('templating')->renderResponse('SensioDistributionBundle::Configurator/check.html.twig', array(
            'majors'  => $majors,
            'minors'  => $minors,
            'url'     => $url,
        ));
    }

    /**
     * @return Response A Response instance
     */
    public function stepAction($index = 0)
    {
        $installWizard = $this->container->get('campaignchain.core.install.wizard');

        $step = $installWizard->getStep($index);
        $form = $this->container->get('form.factory')->create($step->getFormType(), $step);

        $request = $this->container->get('request');
        if ($request->isMethod('POST')) {
            $form->submit($request);
            if ($form->isValid()) {
                $installWizard->execute($step, $step->update($form->getData()));

                $index++;

                if ($index < $installWizard->getStepCount()) {
                    /*
                     * This is a hack to avoid that an error about a missing
                     * bundle for a route will be shown after installing the
                     * system modules.
                     *
                     * By redirecting to the next step without calling the
                     * Symfony router component, we can avoid the above issue.
                     *
                     * TODO: Fix this in a proper way :)
                     */
                    if($index == 1){
                        header('Location: /install/step/1');
                        exit;
                    }
                    return new RedirectResponse($this->container->get('router')->generate('campaignchain_core_install_step', array('index' => $index)));
                }

                return new RedirectResponse($this->container->get('router')->generate('campaignchain_core_homepage'));
            }
        }

        return $this->container->get('templating')->renderResponse($step->getTemplate(), array(
            'form'    => $form->createView(),
            'index'   => $index,
            'count'   => $installWizard->getStepCount(),
        ));
    }
}
