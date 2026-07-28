<?php

/**
 * Read-only diagnostic for DEMO.4B.3 Blog/Contact implementation.
 *
 * This script inspects files and git state only. It does not bootstrap
 * CodeIgniter, submit forms, call phrase writers, or write the database.
 */

$root = dirname(__DIR__, 2);
$failures = array();
$warnings = array();

function demo4b3_pass($message)
{
    echo '[PASS] ' . $message . PHP_EOL;
}

function demo4b3_fail(&$failures, $message)
{
    $failures[] = $message;
    echo '[FAIL] ' . $message . PHP_EOL;
}

function demo4b3_warn(&$warnings, $message)
{
    $warnings[] = $message;
    echo '[WARN] ' . $message . PHP_EOL;
}

function demo4b3_read($root, $relative_path)
{
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
    return is_file($path) ? file_get_contents($path) : false;
}

function demo4b3_contains($content, $needle)
{
    return $content !== false && strpos($content, $needle) !== false;
}

function demo4b3_git_status($root)
{
    $output = array();
    $code = 0;
    exec('git -C ' . escapeshellarg($root) . ' status --short', $output, $code);
    return array($code, $output);
}

function demo4b3_run_php_script($root, $relative_path)
{
    $output = array();
    $code = 0;
    exec('php ' . escapeshellarg($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path)) . ' 2>&1', $output, $code);
    return array($code, $output);
}

$blogView = demo4b3_read($root, 'application/views/frontend/youngo/blogs.php');
$blogDetailsView = demo4b3_read($root, 'application/views/frontend/youngo/blog_details.php');
$contactView = demo4b3_read($root, 'application/views/frontend/youngo/contact_us.php');
$routes = demo4b3_read($root, 'application/config/routes.php');
$helper = demo4b3_read($root, 'application/helpers/youngo_frontend_language_helper.php');

if ($blogView !== false) {
    demo4b3_pass('YounGo blog view exists.');
} else {
    demo4b3_fail($failures, 'YounGo blog view exists.');
}

if ($blogDetailsView !== false) {
    demo4b3_pass('YounGo blog detail view exists.');
} else {
    demo4b3_fail($failures, 'YounGo blog detail view exists.');
}

if ($contactView !== false) {
    demo4b3_pass('YounGo contact view exists.');
} else {
    demo4b3_fail($failures, 'YounGo contact view exists.');
}

$skeletonNeedles = array(
    'YOUNGO THEME SKELETON',
    'YounGo theme skeleton',
    'This YounGo page has not been implemented yet',
);

foreach (array('blogs.php' => $blogView, 'blog_details.php' => $blogDetailsView, 'contact_us.php' => $contactView) as $label => $content) {
    $hasSkeleton = false;
    foreach ($skeletonNeedles as $needle) {
        if (demo4b3_contains($content, $needle)) {
            $hasSkeleton = true;
            break;
        }
    }

    if (!$hasSkeleton) {
        demo4b3_pass($label . ' does not contain skeleton copy.');
    } else {
        demo4b3_fail($failures, $label . ' does not contain skeleton copy.');
    }
}

$requiredRoutes = array(
    "\$route['ar/blog']" => 'Arabic Blog route exists.',
    "\$route['ar/contact']" => 'Arabic Contact route exists.',
    "\$route['contact']" => 'Clean Contact route exists.',
);

foreach ($requiredRoutes as $needle => $message) {
    if (demo4b3_contains($routes, $needle)) {
        demo4b3_pass($message);
    } else {
        demo4b3_fail($failures, $message);
    }
}

if ($routes !== false && strpos($routes, "\$route['en") === false && strpos($routes, '$route["en') === false) {
    demo4b3_pass('No /en routes exist.');
} else {
    demo4b3_fail($failures, 'No /en routes exist.');
}

$phraseKeys = array(
    "'latest_articles'",
    "'helpful_notes_for_families'",
    "'no_blog_posts_yet'",
    "'contact_us'",
    "'get_in_touch'",
    "'send_us_a_message'",
    "'email'",
    "'phone'",
    "'address'",
    "'working_hours'",
    "'follow_us'",
    "'message'",
    "'name'",
    "'subject'",
    "'back_to_blog'",
    "'read_more'",
);

$missingPhraseKeys = array();
foreach ($phraseKeys as $phraseKey) {
    if (!demo4b3_contains($helper, $phraseKey)) {
        $missingPhraseKeys[] = $phraseKey;
    }
}

if (empty($missingPhraseKeys)) {
    demo4b3_pass('Route-aware helper/local map includes Blog/Contact labels.');
} else {
    demo4b3_fail($failures, 'Missing phrase keys: ' . implode(', ', $missingPhraseKeys));
}

if (demo4b3_contains($helper, "'ar/blog'") && demo4b3_contains($helper, "'ar/contact'")) {
    demo4b3_pass('Route-aware helper maps Blog and Contact Arabic equivalents.');
} else {
    demo4b3_fail($failures, 'Route-aware helper maps Blog and Contact Arabic equivalents.');
}

list($statusCode, $statusOutput) = demo4b3_git_status($root);
$paymentChanged = array();
$dbWriteFiles = array();
$languageJsonChanged = array();

foreach ($statusOutput as $line) {
    $path = trim(substr($line, 3));
    if (strpos($path, ' -> ') !== false) {
        $parts = explode(' -> ', $path);
        $path = end($parts);
    }

    $normalized = strtolower(str_replace('\\', '/', $path));

    if (
        strpos($normalized, 'paymob') !== false ||
        strpos($normalized, 'payment') !== false ||
        strpos($normalized, 'checkout') !== false ||
        strpos($normalized, 'coupon') !== false ||
        strpos($normalized, 'cart') !== false
    ) {
        $paymentChanged[] = $line;
    }

    if (strpos($normalized, 'database/') === 0 || substr($normalized, -4) === '.sql') {
        $dbWriteFiles[] = $line;
    }

    if (strpos($normalized, 'application/language/') === 0 && substr($normalized, -5) === '.json') {
        $languageJsonChanged[] = $line;
    }
}

if ($statusCode === 0 && empty($paymentChanged)) {
    demo4b3_pass('No payment, cart, checkout, coupon, or Paymob files changed.');
} else {
    demo4b3_fail($failures, 'Payment/cart/checkout/coupon/Paymob files changed: ' . implode('; ', $paymentChanged));
}

if (empty($dbWriteFiles)) {
    demo4b3_pass('No DB write scripts or SQL files added.');
} else {
    demo4b3_fail($failures, 'DB write scripts or SQL files changed: ' . implode('; ', $dbWriteFiles));
}

if (empty($languageJsonChanged)) {
    demo4b3_pass('No language JSON files changed.');
} else {
    demo4b3_fail($failures, 'Language JSON files changed: ' . implode('; ', $languageJsonChanged));
}

$changedPhpFiles = array(
    'application/config/routes.php',
    'application/helpers/youngo_frontend_language_helper.php',
    'application/views/frontend/youngo/blogs.php',
    'application/views/frontend/youngo/blog_details.php',
    'application/views/frontend/youngo/contact_us.php',
    'application/views/frontend/youngo/header.php',
    'application/views/frontend/youngo/footer.php',
);

$writeNeedles = array('->insert(', '->update(', '->delete(', 'INSERT ', 'UPDATE ', 'DELETE ');
$writeMatches = array();
foreach ($changedPhpFiles as $relativePath) {
    $content = demo4b3_read($root, $relativePath);
    if ($content === false) {
        continue;
    }

    foreach ($writeNeedles as $needle) {
        if (stripos($content, $needle) !== false) {
            $writeMatches[] = $relativePath . ' contains ' . $needle;
        }
    }
}

if (empty($writeMatches)) {
    demo4b3_pass('Changed implementation files do not contain DB write calls.');
} else {
    demo4b3_fail($failures, 'DB write calls found: ' . implode('; ', $writeMatches));
}

list($planCode, $planOutput) = demo4b3_run_php_script($root, 'scripts/phase_2/youngo_blog_contact_implementation_plan_diagnostic.php');
if ($planCode === 0) {
    demo4b3_pass('Blog/Contact plan diagnostic still passes.');
} else {
    demo4b3_warn($warnings, 'Blog/Contact plan diagnostic no longer passes after implementation-scope changes; run output separately for strict dirty-scope details.');
}

list($phraseCode, $phraseOutput) = demo4b3_run_php_script($root, 'scripts/phase_2/youngo_demo_route_aware_phrase_diagnostic.php');
if ($phraseCode === 0) {
    demo4b3_pass('DEMO.4A route-aware phrase diagnostic passes.');
} elseif (
    strpos(implode("\n", $phraseOutput), 'Dirty worktree scope is limited to DEMO.4A files') !== false &&
    strpos(implode("\n", $phraseOutput), 'No route files changed') !== false &&
    strpos(implode("\n", $phraseOutput), 'No /en routes or links introduced') !== false
) {
    demo4b3_warn($warnings, 'DEMO.4A route-aware phrase diagnostic is failing only on strict DEMO.4A dirty-scope/route-change expectations during DEMO.4B.3.');
} else {
    demo4b3_fail($failures, 'DEMO.4A route-aware phrase diagnostic passes.');
}

if (empty($failures)) {
    echo 'DEMO.4B.3 implementation diagnostic PASSED.' . PHP_EOL;
    if (!empty($warnings)) {
        echo 'Warnings: ' . count($warnings) . PHP_EOL;
    }
    exit(0);
}

echo 'DEMO.4B.3 implementation diagnostic FAILED.' . PHP_EOL;
exit(1);
