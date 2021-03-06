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

namespace CampaignChain\CoreBundle\Controller;

use CampaignChain\CoreBundle\Entity\CTA;
use CampaignChain\CoreBundle\Entity\Medium;
use CampaignChain\CoreBundle\Entity\ReportCTA;
use CampaignChain\CoreBundle\EntityService\CTAService;
use CampaignChain\CoreBundle\EntityService\LocationService;
use CampaignChain\CoreBundle\Util\ParserUtil;
use Doctrine\DBAL\Query\QueryBuilder;
use GK\JavascriptPacker;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Url;

class TrackingController extends Controller
{
    const TRACKING_REPORT_BASE_URL_NAME = 'cctapi';
    const TRACKING_JS_URI_OLD = '/bundles/campaignchaincore/js/campaignchain/campaignchain_tracking.js';

    public function trackingJsAction(Request $request)
    {
        // Take care of old path to tracking.js
        if($request->getPathInfo() == self::TRACKING_JS_URI_OLD){
            $trackingIdName = <<<EOT
if(window.location.href.toLowerCase().indexOf("campaignchain-id") >= 0) {
            this.idName = "campaignchain-id";
        } else {
            this.idName = "
EOT;
            $trackingIdName .= $this->getParameter('campaignchain_core.tracking.id_name');
            $trackingIdName .= <<<EOT
";
        }
        
EOT;
            $twigParams = array(
                'tracking_id_name' => $trackingIdName,
                'tracking_js_class' => 'CampaignChain',
                'tracking_js_init' => 'init',
                'tracking_init_compatibility' =><<<EOT
window["init"](window.campaignchainChannel);
EOT
            );
        } else {
            $twigParams = array(
                'tracking_id_name' => 'this.idName = "'.$this->getParameter('campaignchain_core.tracking.id_name').'";',
                'tracking_js_class' => $this->getParameter('campaignchain_core.tracking.js_class'),
                'tracking_js_init' => $this->getParameter('campaignchain_core.tracking.js_init'),
                'tracking_init_compatibility' => '',
            );
        }

        $twigParams['tracking_js_mode'] = $this->getParameter('campaignchain_core.tracking.js_mode');
        $twigParams['tracking_report_base_url_name'] = self::TRACKING_REPORT_BASE_URL_NAME;

        $trackingJs = $this->renderView(
            'CampaignChainCoreBundle:Tracking:tracking.js.twig', $twigParams
        );

        // Uglify tracking JavaScript if in prod environment.
        if($this->get( 'kernel' )->getEnvironment() == 'prod') {
            $packer = new JavascriptPacker($trackingJs);
            $trackingJs = $packer->pack();
        }

        $response = new Response($trackingJs);
        $response->headers->set('Content-Type','application/javascript');
        return $response;
    }

    /**
     * Sample URI for testing:
     * /app_dev.php/test/trackingjs/b982b267f991e731a7453fe839a6e70d?cctid=1
     *
     * @param Request $request
     * @param $channel
     * @param $dev
     * @param $old
     * @return Response
     */
    public function testTrackingJsAction(Request $request, $trackingId, $dev, $old)
    {
        if($dev) {
            $trackingJsRoute = '/app_dev.php';
        } else {
            $trackingJsRoute = '/app.php';
        }

        if($old){
            $trackingJsRoute .= self::TRACKING_JS_URI_OLD;
        } else {
            $trackingJsRoute .= $this->getParameter('campaignchain.tracking.js_route');
        }

        $channelService = $this->get('campaignchain.core.channel');
        $channel = $channelService->getChannelByTrackingId($trackingId);

        $trackingSnippet = $this->renderView(
            'CampaignChainCoreBundle:Channel:_cta_tracking_snippet.js.twig',
            array(
                'channel' => $channel,
                'tracking_js_init' => $this->getParameter('campaignchain_core.tracking.js_init'),
                'tracking_js_route' => $this->getParameter('campaignchain.tracking.js_route')
            )
        );

        return $this->render(
            'CampaignChainCoreBundle:Tracking:test_tracking.js.html.twig',
            array(
                'page_title' => 'Test Tracking JS',
                'channel.trackingId' => $channel,
                'tracking_snippet' => $trackingSnippet,
                'tracking_js_init' => $this->getParameter('campaignchain_core.tracking.js_init'),
                'tracking_js_route' => $trackingJsRoute,
                'is_old_tracking_js_route' => $old,
            ));
    }

    public function newApiAction(Request $request, $channel)
    {
        $hasError = false;

        if ($this->has('monolog.logger.tracking')) {
            $logger = $this->get('monolog.logger.tracking');
        } else {
            $logger = $this->get('logger');
        }

        $logger->info('-------');
        $logger->info('Start tracking');

        // Check whether the channel has access to tracking.

        // 1. Has the channel ID been provided
        if(!$channel){
            $msg = 'No Channel Tracking ID provided.';
            $logger->error($msg);
            return $this->errorResponse($msg, $request);
        }

        $logger->info('Channel Tracking ID: '.$channel);

        // 2. Is it a valid Channel Tracking ID?
        $channel = $this->getDoctrine()
            ->getRepository('CampaignChainCoreBundle:Channel')
            ->findOneByTrackingId($channel);

        if (!$channel) {
            $msg = 'Unknown Channel Tracking ID';
            $logger->error($msg);
            return $this->errorResponse($msg, $request);
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
            return $this->errorResponse($msg, $request);
        }

        $source = $request->get('source');
        $target = $request->get('target');
        $trackingIdName = $request->get('id_name');
        $logger->info('Tracking ID name: '.$trackingIdName);
        $trackingIdValue = $request->get('id_value');
        $logger->info('Tracking ID value: '.$trackingIdValue);
        $trackingAlias = $request->get('alias');
        $logger->info('Tracking Alias: '.$trackingAlias);

        if(
            $this->getParameter('campaignchain_core.tracking.js_mode') == 'dev' ||
            $this->getParameter('campaignchain_core.tracking.js_mode') == 'dev-stay'
        ){
            $source = ParserUtil::removeUrlParam($source, self::TRACKING_REPORT_BASE_URL_NAME);
            $target = ParserUtil::removeUrlParam($target, self::TRACKING_REPORT_BASE_URL_NAME);
        }

        $sourceUrl = ParserUtil::removeUrlParam($source, $trackingIdName);
        $targetUrl = ParserUtil::removeUrlParam($target, $trackingIdName);

        $logger->info('Source: '.$sourceUrl);
        $logger->info('Target: '.$targetUrl);

        // Check if URLs are valid.
        $constraint = new Url();

        $constraint->message = "Source Location '".$source."' is not a valid URL.";
        $errors = $this->get('validator')->validateValue(
            $source,
            $constraint
        );
        if(count($errors)){
            $hasError = true;
            $msg = $errors[0]->getMessage();
        }

        if (strpos($target, 'mailto') === false) {
            // Check if we get an absolute or a relative path, if relative, then we can assume it goes to the source host
            if (!parse_url($target, PHP_URL_HOST) && parse_url($source, PHP_URL_HOST)) {
                $parsedSource = parse_url($source);
                $target = (array_key_exists('scheme', $parsedSource) ? $parsedSource['scheme'] : 'http' ).
                    '://'.
                    rtrim($parsedSource['host'], '/').
                    '/'.
                    $target;
            }

            $constraint->message = "Target Location '". $target ."' is not a valid URL.";
            $errors = $this->get('validator')->validateValue(
                $target,
                $constraint
            );

            if(count($errors)){
                $hasError = true;
                $msg = $errors[0]->getMessage();
            }
        } else {
            // mailto links are not tracked
            $hasError = true;
            $msg = 'Mailto links are not tracked';
        }

        if($hasError){
            $logger->error($msg);
            return $this->errorResponse($msg, $request);
        }

        // Check whether the Tracking ID name has been provided.
        if($trackingIdName == null){
            $msg = 'No Tracking ID name provided.';
            $logger->error($msg);
            return $this->errorResponse($msg, $request);
        }

        // Check whether the Tracking ID name is correct.
        if(
            $trackingIdName != $this->getParameter('campaignchain_core.tracking.id_name') &&
            $trackingIdName != 'campaignchain-id'
        ){
            $msg = 'Provided Tracking ID name ("'.$trackingIdName.'") does not match, should be "'.$this->getParameter('campaignchain_core.tracking.id_name').'".';
            $logger->error($msg);
            return $this->errorResponse($msg, $request);
        }

        if($trackingIdValue != null){

            // Does the CTA for the provided Tracking ID exist?
            /** @var CTA $cta */
            $cta = $this->getDoctrine()
                ->getRepository('CampaignChainCoreBundle:CTA')
                ->findOneByTrackingId($trackingIdValue);

            if (!$cta) {
                $msg = 'Unknown CTA Tracking ID "'.$trackingIdValue.'".';
                $logger->error($msg);
                return $this->errorResponse($msg, $request);
            }

            // Get the referrer Location.
            $em = $this->getDoctrine()->getManager();
            /** @var QueryBuilder $qb */
            $qb = $em->createQueryBuilder();
            $qb->select('l')
                ->from('CampaignChain\CoreBundle\Entity\Location', 'l')
                ->from('CampaignChain\CoreBundle\Entity\CTA', 'cta')
                ->where('l.operation = :operation')
                ->andWhere('l.id != cta.location')
                ->andWhere('cta.operation = l.operation')
                ->andWhere('(l.id = :activityLocation OR l.parent = :activityLocation)')
                ->andWhere('l.status = :status')
                ->setParameter('operation', $cta->getOperation())
                ->setParameter('activityLocation', $cta->getOperation()->getActivity()->getLocation())
                ->setParameter('status', Medium::STATUS_ACTIVE);
            $query = $qb->getQuery();

            try {
                $referrerLocation = $query->getSingleResult();
            } catch(\Exception $e) {
                $msg = Response::HTTP_INTERNAL_SERVER_ERROR.': Multiple referrers are not possible.';
                $logger->error($msg);
                return $this->errorResponse($msg, $request);
            }

            if(!$referrerLocation){
                $msg = Response::HTTP_INTERNAL_SERVER_ERROR.': No referrer Location.';
                $logger->error($msg);
                return $this->errorResponse($msg, $request);
            }

            $sourceLocation = null;

            if($sourceUrl == $targetUrl){
                $logger->info('Source URL == target URL.');
                /*
                 * If the source equals the target, then the source is actually
                 * an Activity's CTA.
                 */
                $sourceUrl = $cta->getLocation()->getUrl();
                $sourceLocation = $cta->getLocation();
                $targetLocation = $cta->getLocation();
            } else {
                $logger->info('Source URL != target URL.');

                $sourceLocation = $cta->getLocation();
                /*
                 * Check if the target URL is in a connected Channel. If yes, add
                 * as new Location if supported by module.
                 */
                /** @var LocationService $locationService */
                $locationService = $this->container->get('campaignchain.core.location');
                try {
                    $logger->info('Searching for matching Location or creating new one.');
                    $targetLocation = $locationService->findLocationByUrl($targetUrl, $cta->getOperation(), $trackingAlias);
                    
                    if($targetLocation){
                        $logger->info(
                            'Found target Location with bundle '.
                            $targetLocation->getLocationModule()->getBundle()->getName().
                            ' and module '.
                            $targetLocation->getLocationModule()->getIdentifier().
                            '.'
                        );
                    } else {
                        $logger->info('No matching Location found, nor a new one created.');
                    }
                } catch (\Exception $e) {
                    $msg = Response::HTTP_INTERNAL_SERVER_ERROR.': '.$e->getMessage();
                    $logger->error($msg);
                    return $this->errorResponse($msg, $request);
                }
            }

//            /*
//             * Check if the source URL provided in CTA record is the same as
//             * the one passed to this API.
//             */
//            if($cta->getUrl() != $sourceUrl){
//                $msg = Response::HTTP_BAD_REQUEST.': Provided source URL "'.$sourceUrl.'" does not match URL for Tracking ID "'.$trackingIdName.'".';
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
            } else {
                $reportCTA->setTargetName(ParserUtil::getHTMLTitle($targetUrl));
            }
            $reportCTA->setTime();

            $em = $this->getDoctrine()->getManager();
            $em->persist($reportCTA);
            $em->flush();

            $logger->info('Done tracking');
            $logger->info('-------');

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
            if($source == $target){
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

            $response = new JsonResponse([
                'target_affiliation' => $targetAffiliation,
                'success' => true,
            ]);
            $response->setCallback($request->get('callback'));
            return $response;
        } else {
            $msg = 'Tracking ID missing as part of source Location.';
            $logger->error($msg);
            return $this->errorResponse($msg, $request);
        }
    }

    private function errorResponse($msg, Request $request)
    {
        $response = new JsonResponse([
            'message' => $msg,
            'success' => false,
        ]);
        return $response->setCallback($request->get('callback'));
    }
}