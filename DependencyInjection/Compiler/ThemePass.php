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

namespace CampaignChain\CoreBundle\DependencyInjection\Compiler;

use CampaignChain\CoreBundle\Util\VariableUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Change parameters of the Avanzu AdminLTE bundle.
 *
 * Class ThemePass
 * @package CampaignChain\CoreBundle\DependencyInjection\Compiler
 */
class ThemePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('twig.form.resources')) {
            return;
        }

        /*
         * Avoid that Avanzu overrides the Braincrafted form widgets.
         */
        $param = $container->getParameter('twig.form.resources');
        print_r($param);
        $param = VariableUtil::unsetInNumericArrayByValue(
            $param,
            'AvanzuAdminThemeBundle:layout:form-theme.html.twig'
        );print_r($param);
        $container->setParameter(
            'twig.form.resources',
            $param
        );
    }
}
