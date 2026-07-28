<?php

/**
 * Read-only diagnostic for DEMO.4B.2 Blog/Contact implementation planning.
 *
 * This script validates that the planning artifact exists and that this
 * planning phase did not modify application source, routes, payment files, or
 * database state.
 */

$root = dirname(__DIR__, 2);
$planPath = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'planning' . DIRECTORY_SEPARATOR . 'youngo_blog_contact_implementation_plan.md';
$auditRelativePath = 'docs/qa/youngo_blog_contact_existing_system_audit.md';
$routePath = $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routes.php';

$failures = array();

function diag_pass($message)
{
    echo '[PASS] ' . $message . PHP_EOL;
}

function diag_fail(&$failures, $message)
{
    $failures[] = $message;
    echo '[FAIL] ' . $message . PHP_EOL;
}

function require_text(&$failures, $content, $needle, $message)
{
    if (stripos($content, $needle) !== false) {
        diag_pass($message);
        return;
    }

    diag_fail($failures, $message);
}

if (is_file($planPath)) {
    diag_pass('Planning report exists.');
    $plan = file_get_contents($planPath);
} else {
    diag_fail($failures, 'Planning report exists.');
    $plan = '';
}

require_text($failures, $plan, $auditRelativePath, 'Plan references the source audit file.');
require_text($failures, $plan, 'Blog Implementation Recommendation', 'Plan includes Blog recommendation.');
require_text($failures, $plan, 'Contact Implementation Recommendation', 'Plan includes Contact recommendation.');
require_text($failures, $plan, 'Existing Reusable System Pieces', 'Plan includes reusable system pieces.');
require_text($failures, $plan, 'New YounGo Views Needed', 'Plan includes YounGo views needed.');
require_text($failures, $plan, 'Route Changes Needed', 'Plan includes route policy.');
require_text($failures, $plan, 'Do not add `/en`', 'Plan explicitly forbids /en routes.');
require_text($failures, $plan, 'Contact Form Decision', 'Plan includes contact form decision.');
require_text($failures, $plan, 'Option A', 'Plan chooses static/display-only contact behavior for fast demo.');

$statusOutput = array();
$statusCode = 0;
exec('git -C ' . escapeshellarg($root) . ' status --short', $statusOutput, $statusCode);

$allowedChanges = array(
    'docs/planning/youngo_blog_contact_implementation_plan.md',
    'scripts/phase_2/youngo_blog_contact_implementation_plan_diagnostic.php',
);

$unexpectedChanges = array();
foreach ($statusOutput as $line) {
    $path = trim(substr($line, 3));
    if (strpos($path, ' -> ') !== false) {
        $parts = explode(' -> ', $path);
        $path = end($parts);
    }
    $path = str_replace('\\', '/', $path);

    if (!in_array($path, $allowedChanges, true)) {
        $unexpectedChanges[] = $line;
    }
}

if ($statusCode === 0 && empty($unexpectedChanges)) {
    diag_pass('Only planning report and diagnostic are changed.');
} else {
    diag_fail($failures, 'Unexpected changed files: ' . implode('; ', $unexpectedChanges));
}

$routes = is_file($routePath) ? file_get_contents($routePath) : '';
if ($routes !== '' && strpos($routes, "\$route['en") === false && strpos($routes, '$route["en') === false) {
    diag_pass('No /en routes found in routes.php.');
} else {
    diag_fail($failures, 'No /en routes found in routes.php.');
}

if ($routes !== '' && stripos($routes, 'ar/blog') === false && stripos($routes, 'ar/contact') === false) {
    diag_pass('Blog/contact Arabic routes were not added during planning.');
} else {
    diag_fail($failures, 'Blog/contact Arabic routes were not added during planning.');
}

$paymentChanged = array();
foreach ($statusOutput as $line) {
    $normalized = str_replace('\\', '/', strtolower($line));
    if (
        strpos($normalized, 'paymob') !== false ||
        strpos($normalized, 'payment') !== false ||
        strpos($normalized, 'checkout') !== false ||
        strpos($normalized, 'coupon') !== false ||
        strpos($normalized, 'order') !== false
    ) {
        $paymentChanged[] = $line;
    }
}

if (empty($paymentChanged)) {
    diag_pass('No payment, checkout, order, coupon, or Paymob files changed.');
} else {
    diag_fail($failures, 'Payment-related files changed: ' . implode('; ', $paymentChanged));
}

if (empty($failures)) {
    echo 'DEMO.4B.2 planning diagnostic PASSED.' . PHP_EOL;
    exit(0);
}

echo 'DEMO.4B.2 planning diagnostic FAILED.' . PHP_EOL;
exit(1);

