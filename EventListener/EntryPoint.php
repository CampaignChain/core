<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EventListener;

use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This listener gets fired if a new session is being created.
 *
 * @package CampaignChain\CoreBundle\EventListener
 */
class EntryPoint implements AuthenticationEntryPointInterface
{
    protected $router;

    public function __construct($router)
    {
        $this->router = $router;
    }

    /**
     * Checks prior to creating a new session whether the request is an AJAX
     * request. If so, then we issue a 401 error to avoid that the AJAX returns
     * the login page as the response. The AJAX request would handle the 401
     * by redirecting to the login page. If not an AJAX request, we automatically
     * go to the login page.
     *
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return JsonResponse|RedirectResponse
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        // AJAX request?
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse('', Response::HTTP_UNAUTHORIZED);
        }

        return new RedirectResponse($this->router->generate('fos_user_security_login'));
    }
}