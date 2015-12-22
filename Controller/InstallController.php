<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

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
     * @param Request $request
     * @param int $index
     * @return Response A Response instance
     */
    public function stepAction(Request $request, $index = 0)
    {
        $installWizard = $this->container->get('campaignchain.core.install.wizard');

        $step = $installWizard->getStep($index);
        $form = $this->container->get('form.factory')->create($step->getFormType(), $step);
        $form->handleRequest($request);

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

        return $this->container->get('templating')->renderResponse($step->getTemplate(), array(
            'form'    => $form->createView(),
            'index'   => $index,
            'count'   => $installWizard->getStepCount(),
        ));
    }
}
