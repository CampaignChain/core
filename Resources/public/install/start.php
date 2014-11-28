<?php
$page_title = 'Check Server Environment';

require_once 'header.php';

require_once dirname(__FILE__) . '/../../../../../../app/SymfonyRequirements.php';

$symfonyRequirements = new SymfonyRequirements();

$majorProblems = $symfonyRequirements->getFailedRequirements();
$minorProblems = $symfonyRequirements->getFailedRecommendations();
?>

<p>Welcome to your new CampaignChain application!</p>
<p>This wizard will guide you through the installation process.</p>

<?php if (count($majorProblems)): ?>
    <h3>Major Problems</h3>
    <p>Major problems have been detected and <strong>must</strong> be fixed before continuing:</p>
    <ol>
        <?php foreach ($majorProblems as $problem): ?>
            <li><?php echo $problem->getHelpHtml() ?></li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>

<?php if (count($minorProblems)): ?>
    <h3>Recommendations</h3>
    <p>
        <?php if (count($majorProblems)): ?>Additionally, to<?php else: ?>To<?php endif; ?> enhance your CampaignChain experience,
        it’s recommended that you fix the following:
    </p>
    <ol>
        <?php foreach ($minorProblems as $problem): ?>
            <li><?php echo $problem->getHelpHtml() ?></li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>

<?php if ($symfonyRequirements->hasPhpIniConfigIssue()): ?>
    <p id="phpini">*
        <?php if ($symfonyRequirements->getPhpIniConfigPath()): ?>
            Changes to the <strong>php.ini</strong> file must be done in "<strong><?php echo $symfonyRequirements->getPhpIniConfigPath() ?></strong>".
        <?php else: ?>
            To change settings, create a "<strong>php.ini</strong>".
        <?php endif; ?>
    </p>
<?php endif; ?>

<?php if (!count($majorProblems) && !count($minorProblems)): ?>
    <p class="ok">Your configuration looks good to run Symfony.</p>
<?php endif; ?>

<?php if (!count($majorProblems)): ?>
<form method="POST" action="/install/">
    <button type="submit">Next Step</button>
</form>
<?php endif; ?>

<?php if (count($majorProblems) || count($minorProblems)): ?>
    <p><a href="/bundles/campaignchaincore/install/start.php">Re-check configuration</a></p>
<?php endif; ?>
<?php
require_once 'footer.php';
?>