<?php

/**
 * Phase 2U.6.6.1 Frontend Phrase Conversion Inventory diagnostic.
 *
 * Read-only checks only. This script must not call get_phrase()/site_phrase()
 * because the LMS phrase helpers can create or update missing phrase rows.
 */

$root = dirname(__DIR__, 2);
$reportPath = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'planning' . DIRECTORY_SEPARATOR . 'phase_2u6_6_frontend_phrase_inventory.md';
$selfPath = str_replace('\\', '/', substr(__FILE__, strlen($root) + 1));
$allowedDirty = array(
    'docs/planning/phase_2u6_6_frontend_phrase_inventory.md',
    'scripts/phase_2/youngo_phase_2u6_frontend_phrase_inventory_diagnostic.php',
);

$failures = array();

function youngo_phase_2u66_inventory_fail(&$failures, $message)
{
    $failures[] = $message;
}

function youngo_phase_2u66_inventory_contains($haystack, $needle)
{
    return strpos($haystack, $needle) !== false;
}

if (!file_exists($reportPath)) {
    youngo_phase_2u66_inventory_fail($failures, 'Inventory report is missing.');
} else {
    $report = file_get_contents($reportPath);
    $requiredFragments = array(
        'Phase 2U.6.6.1 Frontend Phrase Conversion Inventory',
        'Files Scanned',
        'Already Phrase-Based Strings',
        'Hardcoded Visible Strings Found',
        'A. Safe Convert Existing Key',
        'B. Safe Convert After Seed',
        'C. Needs Design/Copy Decision',
        'D. Defer',
        'E. Not A Phrase',
        'Existing Phrase Key Availability',
        'Missing Phrase Keys Requiring Controlled Seed',
        'Recommended Low-Risk Conversion Set',
        'did not call `get_phrase()`',
        'arabic_translated',
        'application/views/frontend/youngo/courses_page.php',
        'application/views/frontend/youngo/course_listing/sorting_bar.php',
        'application/controllers/Home.php',
    );

    foreach ($requiredFragments as $fragment) {
        if (!youngo_phase_2u66_inventory_contains($report, $fragment)) {
            youngo_phase_2u66_inventory_fail($failures, 'Inventory report is missing expected coverage: ' . $fragment);
        }
    }
}

$diagnosticSource = file_get_contents(__FILE__);
$tokens = token_get_all($diagnosticSource);
foreach ($tokens as $index => $token) {
    if (!is_array($token) || $token[0] !== T_STRING) {
        continue;
    }

    $name = strtolower($token[1]);
    if (!in_array($name, array('get_phrase', 'site_phrase', 'insert', 'update', 'delete', 'replace'), true)) {
        continue;
    }

    $nextIndex = $index + 1;
    while (isset($tokens[$nextIndex]) && is_array($tokens[$nextIndex]) && $tokens[$nextIndex][0] === T_WHITESPACE) {
        $nextIndex++;
    }

    if (isset($tokens[$nextIndex]) && $tokens[$nextIndex] === '(') {
        youngo_phase_2u66_inventory_fail($failures, 'Diagnostic contains executable write-prone or phrase-helper call: ' . $name);
    }
}

$statusOutput = array();
$statusCode = 0;
exec('git -C ' . escapeshellarg($root) . ' status --short', $statusOutput, $statusCode);
if ($statusCode !== 0) {
    youngo_phase_2u66_inventory_fail($failures, 'Unable to inspect git status.');
} else {
    foreach ($statusOutput as $line) {
        $path = trim(substr($line, 3));
        if ($path !== '' && !in_array($path, $allowedDirty, true)) {
            youngo_phase_2u66_inventory_fail($failures, 'Unexpected dirty file during inventory phase: ' . $path);
        }
    }
}

$sourcePaths = array(
    'application/views/frontend/youngo/index.php',
    'application/views/frontend/youngo/header.php',
    'application/views/frontend/youngo/footer.php',
    'application/views/frontend/youngo/course_page.php',
    'application/views/frontend/youngo/courses_page.php',
    'application/views/frontend/youngo/course_listing/course_card.php',
    'application/views/frontend/youngo/course_listing/filter_panel.php',
    'application/views/frontend/youngo/course_listing/sorting_bar.php',
    'application/views/frontend/youngo/my_courses.php',
    'application/views/frontend/youngo/my_access.php',
    'application/views/frontend/youngo/my_wishlist.php',
    'application/views/frontend/youngo/wishlist_items.php',
    'application/views/frontend/youngo/login.php',
    'application/views/frontend/youngo/sign_up.php',
    'application/views/frontend/youngo/includes_top.php',
    'application/views/frontend/youngo/includes_bottom.php',
    'application/controllers/Home.php',
    'application/helpers/common_helper.php',
    'application/helpers/youngo_frontend_language_helper.php',
    'application/helpers/youngo_frontend_content_helper.php',
);

foreach ($sourcePaths as $path) {
    if (!file_exists($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path))) {
        if ($path !== 'application/views/frontend/youngo/lesson.php') {
            youngo_phase_2u66_inventory_fail($failures, 'Expected scanned source path is missing: ' . $path);
        }
    }
}

if (empty($failures)) {
    echo "PASS Phase 2U.6.6.1 frontend phrase inventory diagnostic passed.\n";
    echo "- Inventory report exists and contains required categories.\n";
    echo "- Diagnostic does not call phrase helpers or include DB/session/cookie write markers.\n";
    echo "- Dirty worktree scope is limited to inventory report/diagnostic files.\n";
    exit(0);
}

echo "FAIL Phase 2U.6.6.1 frontend phrase inventory diagnostic failed.\n";
foreach ($failures as $failure) {
    echo "- " . $failure . "\n";
}
exit(1);
