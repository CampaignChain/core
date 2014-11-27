<?php

if (!isset($_SERVER['HTTP_HOST'])) {
    exit('This script cannot be run from the CLI. Run it from a browser.');
}

if (!in_array(@$_SERVER['REMOTE_ADDR'], array(
    '127.0.0.1',
    '::1',
))) {
    header('HTTP/1.0 403 Forbidden');
    exit('This script is only accessible from localhost.');
}

require_once dirname(__FILE__) . '/../../../../../../app/SymfonyRequirements.php';

$symfonyRequirements = new SymfonyRequirements();

$majorProblems = $symfonyRequirements->getFailedRequirements();
$minorProblems = $symfonyRequirements->getFailedRecommendations();

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="robots" content="noindex,nofollow" />
    <title>CampaignChain Installation</title>
    <link rel="stylesheet" href="/bundles/framework/css/structure.css" media="all" />
    <link rel="stylesheet" href="/bundles/framework/css/body.css" media="all" />
    <link rel="stylesheet" href="/bundles/sensiodistribution/webconfigurator/css/install.css" media="all" />
    <link rel="stylesheet" href="/bundles/campaignchaincore/css/campaignchain/install.css" media="all" />
</head>
<body>
<div id="content">
    <div class="header clear-fix">
        <div class="header-logo">
            <a href="http://www.campaignchain.com"><img style="height: 24px;" alt="CampaignChain" src="/bundles/campaignchaincore/images/campaignchain_logo.png"></a>
        </div>
    </div>

    <div>
        <div class="block">
            <div class="symfony-block-content">
                <div class="page-header">
                    <h1><?php echo $page_title; ?> <small>Installation Wizard</small></h1>
                </div>