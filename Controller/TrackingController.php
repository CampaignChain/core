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

use CampaignChain\CoreBundle\Entity\ReportCTA;
use CampaignChain\CoreBundle\EntityService\CTAService;
use CampaignChain\CoreBundle\Util\ParserUtil;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Url;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class TrackingController extends Controller
{
    /**
     * @Security("has_role('IS_AUTHENTICATED_ANONYMOUSLY')")
     * @Security("has_role('ROLE_USER')")
     */
    public function newApiAction(Request $request, $channel)
    {
        $hasError = false;
        $logger = $this->get('logger');
        $logger->info('Start tracking');

        // Check whether the channel has access to tracking.

        // 1. Has the channel ID been provided
        if(!$channel){
            $msg = 'No Channel Tracking ID provided.';
            $logger->error($msg);
            $response = new Response($msg);
            return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        $logger->info('Channel Tracking ID: '.$channel);

        // 2. Is it a valid Channel Tracking ID?
        $channel = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:Channel')
            ->findOneByTrackingId($channel);

        if (!$channel) {
            $msg = 'Unknown Channel Tracking ID';
            $logger->error($msg);
            $response = new Response($msg);
            return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        // Check whether required parameters have been provided.
        if(!$request->get('source')){
            $hasError = true;
            $msg = 'URL of source Location missing.';
        } elseif(!$request->get('target')){
            $hasError = true;
            $msg = 'URL of target Location missing.';
        }

        if($hasError){
            $logger->error($msg);
            $response = new Response($msg);
            return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        // Check if URLs are valid.
        $constraint = new Url();

        $constraint->message = "Source Location '".$request->get('source')."' is not a valid URL.";
        $errors = $this->get('validator')->validateValue(
            $request->get('source'),
            $constraint
        );
        if(count($errors)){
            $hasError = true;
            $msg = $errors[0]->getMessage();
        }

        $constraint->message = "Target Location '".$request->get('target')."' is not a valid URL.";
        $errors = $this->get('validator')->validateValue(
            $request->get('target'),
            $constraint
        );
        if(count($errors)){
            $hasError = true;
            $msg = $errors[0]->getMessage();
        }

        if($hasError){
            $logger->error($msg);
            $response = new Response($msg);
            return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        // Extract the Tracking ID from the source URL.
        $query_str = parse_url($request->get('source'), PHP_URL_QUERY);
        parse_str($query_str, $query_params);
        if(isset($query_params[CTAService::TRACKING_ID_NAME])){
            $trackingId = $query_params[CTAService::TRACKING_ID_NAME];

            // Does the CTA for the provided Tracking ID exist?
            $cta = $this->getDoctrine()
                ->getRepository('CampaignChainCoreBundle:CTA')
                ->findOneByTrackingId($trackingId);

            if (!$cta) {
                $msg = 'Unknown CTA Tracking ID "'.$trackingId.'".';
                $logger->error($msg);
                $response = new Response($msg);
                return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            }

            // Remove the Tracking ID from the URL.
            $source = ParserUtil::removeUrlParam($request->get('source'), CTAService::TRACKING_ID_NAME);

//            /*
//             * Check if the source URL provided in CTA record is the same as
//             * the one passed to this API.
//             */
//            if($cta->getUrl() != $source){
//                $msg = Response::HTTP_BAD_REQUEST.': Provided source URL "'.$source.'" does not match URL for Tracking ID "'.$trackingId.'".';
//                $logger->error($msg);
//                $response = new Response($msg);
//                return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
//            }

//            // Verify that the source exists as a Location within CampaignChain.
//            $location = $this->getDoctrine()
//                ->getRepository('CampaignChainCoreBundle:Location')
//                ->findOneBy(array('URL' => $source));
//
//            if (!$location) {
//                $response = new Response('A Location does not exist for URL "'.$source.'".');
//                return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
//            }

            /*
             * Check if the target URL is in a connected Channel. If yes, add
             * as new Location if supported by module.
             */
            $locationService = $this->container->get('campaignchain.core.location');
            $targetLocation = $locationService->findLocationByUrl($request->get('target'), $cta->getOperation());

            // Add new CTA to report.

            // TODO: Set Referer info by going CTA -> Operation -> Location.
            $referrerLocation = $cta->getOperation()->getLocations()[0];

            if(!$referrerLocation){
                $msg = Response::HTTP_INTERNAL_SERVER_ERROR.': No referrer Location.';
                $logger->error($msg);
                $response = new Response($msg);
                return $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $reportCTA = new ReportCTA();
            $reportCTA->setCTA($cta);
            $reportCTA->setReferrerLocation($referrerLocation);
            $reportCTA->setReferrerName($referrerLocation->getName());
            $reportCTA->setReferrerUrl($referrerLocation->getUrl());
            $reportCTA->setSourceLocation($cta->getLocation());
            $reportCTA->setSourceName($cta->getLocation()->getName());
            $reportCTA->setSourceUrl($source);
            $reportCTA->setTargetUrl($request->get('target'));
            if($targetLocation){
                $reportCTA->setTargetName($targetLocation->getName());
                $reportCTA->setTargetLocation($targetLocation);
            }
            $reportCTA->setTime();

            $repository = $this->getDoctrine()->getManager();
            $repository->persist($reportCTA);
            $repository->flush();

            $logger->info('-------');
            $logger->info('Tracking data:');
            $logger->info('Tracking ID: '.$trackingId);
            $logger->info('Source: '.$source);
            $logger->info('Target: '.$request->get('target'));
            $logger->info('-------');
            $logger->info('Done tracking');


            $response = array('status' => 'OK');
            $response = new JsonResponse($response, 200, array());
            $response->setCallback($request->get('callback'));
            return $response;
        } else {
            $msg = 'Tracking ID missing as part of source Location.';
            $logger->error($msg);
            $response = new Response($msg);
            return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        }
    }
}