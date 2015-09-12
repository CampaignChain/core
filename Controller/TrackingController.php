<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
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

        // Check whether the Tracking ID name has been provided.
        if($request->get('id_name') == null){
            $msg = 'No Tracking ID name provided.';
            $logger->error($msg);
            $response = new Response($msg);
            return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        // Check whether the Tracking ID name is correct.
        if($request->get('id_name') != CTAService::TRACKING_ID_NAME){
            $msg = 'Provided Tracking ID name ("'.$request->get('id_name').'") does not match, should be "'.CTAService::TRACKING_ID_NAME.'".';
            $logger->error($msg);
            $response = new Response($msg);
            return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        if($request->get('id_value') != null){
            $trackingId = $request->get('id_value');

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

            // TODO: Set Referer info by going CTA -> Operation -> Location.
            $referrerLocation = $cta->getOperation()->getLocations()[0];

            if(!$referrerLocation){
                $msg = Response::HTTP_INTERNAL_SERVER_ERROR.': No referrer Location.';
                $logger->error($msg);
                $response = new Response($msg);
                return $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            if($request->get('source') == $request->get('target')){
                /*
                 * If the source equals the target, then the source is actually
                 * an Activity's CTA.
                 */
                $sourceUrl = $referrerLocation->getUrl();
                $sourceLocation = $referrerLocation;
                // Remove the Tracking ID from the URL.
                $targetUrl = ParserUtil::removeUrlParam($request->get('target'), CTAService::TRACKING_ID_NAME);

            } else {
                // Remove the Tracking ID from the URL.
                $sourceUrl = ParserUtil::removeUrlParam($request->get('source'), CTAService::TRACKING_ID_NAME);
                $sourceLocation = $cta->getLocation();
                $targetUrl = $request->get('target');
            }

//            /*
//             * Check if the source URL provided in CTA record is the same as
//             * the one passed to this API.
//             */
//            if($cta->getUrl() != $sourceUrl){
//                $msg = Response::HTTP_BAD_REQUEST.': Provided source URL "'.$sourceUrl.'" does not match URL for Tracking ID "'.$trackingId.'".';
//                $logger->error($msg);
//                $response = new Response($msg);
//                return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
//            }

//            // Verify that the source exists as a Location within CampaignChain.
//            $location = $this->getDoctrine()
//                ->getRepository('CampaignChainCoreBundle:Location')
//                ->findOneBy(array('URL' => $sourceUrl));
//
//            if (!$location) {
//                $response = new Response('A Location does not exist for URL "'.$sourceUrl.'".');
//                return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
//            }

            /*
             * Check if the target URL is in a connected Channel. If yes, add
             * as new Location if supported by module.
             */
            $locationService = $this->container->get('campaignchain.core.location');
            $targetLocation = $locationService->findLocationByUrl($targetUrl, $cta->getOperation());

            // Add new CTA to report.
            $reportCTA = new ReportCTA();
            $reportCTA->setCTA($cta);
            $reportCTA->setOperation($cta->getOperation());
            $reportCTA->setActivity($cta->getOperation()->getActivity());
            $reportCTA->setCampaign($cta->getOperation()->getActivity()->getCampaign());
            $reportCTA->setChannel($cta->getOperation()->getActivity()->getChannel());
            $reportCTA->setReferrerLocation($referrerLocation);
            $reportCTA->setReferrerName($referrerLocation->getName());
            $reportCTA->setReferrerUrl($referrerLocation->getUrl());
            $reportCTA->setSourceLocation($sourceLocation);
            $reportCTA->setSourceName($sourceLocation->getName());
            $reportCTA->setSourceUrl($sourceUrl);
            $reportCTA->setTargetUrl($targetUrl);
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
            $logger->info('Source: '.$sourceUrl);
            $logger->info('Target: '.$targetUrl);
            $logger->info('-------');
            $logger->info('Done tracking');

            /*
             * Set the target's affiliation with CampaignChain in the
             * response. Options are:
             *
             * - current:   The target URL resides within the current
             *              Location.
             * - connected: The target URL resides within another
             *              Location which is connected with
             *              CampaignChain.
             * - unknown:   The target URL resides within another
             *              Location which is _not_ connected with
             *              CampaignChain.
             */
            if($request->get('source') == $request->get('target')){
                $targetAffiliation = 'connected';
            } elseif($reportCTA->getTargetLocation()){
                if($reportCTA->getTargetLocation()->getChannel()->getTrackingId()
                    ==
                    $reportCTA->getSourceLocation()->getChannel()->getTrackingId()){
                    $targetAffiliation = 'current';
                } else {
                    $targetAffiliation = 'connected';
                }
            } else {
                $targetAffiliation = 'unknown';
            }

            $response = array('target_affiliation' => $targetAffiliation);
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