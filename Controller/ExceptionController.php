<?php
/**
 *
 * This file is part of the CampaignChain package.
 *
 *  (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *
 */

namespace CampaignChain\CoreBundle\Controller;

use Symfony\Bundle\TwigBundle\Controller\ExceptionController as TwigExceptionController;
use Symfony\Component\HttpFoundation\Request;

class ExceptionController extends TwigExceptionController
{

    /**
     * Just a modified Symfony\Bundle\TwigBundle\Controller\ExceptionController::findTemplate().
     * It will try to find an appropriate error template in the CoreBundle and will fallback to
     * the Twig default template if it can't find anything.
     *
     * @param Request $request
     * @param string  $format
     * @param int     $code          An HTTP response status code
     * @param bool    $showException
     *
     * @return string
     */
    protected function findTemplate(Request $request, $format, $code, $showException)
    {
        $name = $showException ? 'exception' : 'error';
        if ($showException && 'html' == $format) {
            $name = 'exception_full';
        }

        // For error pages, try to find a template for the specific HTTP status code and format
        if (!$showException) {
            // CampaignChain template?
            $template = sprintf('@CampaignChainCore/Exception/%s%s.%s.twig', $name, $code, $format);
            if ($this->templateExists($template)) {
                return $template;
            }

            // Fallback to default
            $template = sprintf('@Twig/Exception/%s%s.%s.twig', $name, $code, $format);
            if ($this->templateExists($template)) {
                return $template;
            }

        }

        // try to find a template for the given format
        // CampaignChain template?
        $template = sprintf('@CampaignChainCore/Exception/%s.%s.twig', $name, $code, $format);
        if ($this->templateExists($template)) {
            return $template;
        }

        // Fallback to default
        $template = sprintf('@Twig/Exception/%s.%s.twig', $name, $format);
        if ($this->templateExists($template)) {
            return $template;
        }

        // default to a generic HTML exception
        $request->setRequestFormat('html');

        return sprintf('@Twig/Exception/%s.html.twig', $showException ? 'exception_full' : $name);
    }

}