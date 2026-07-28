<?php

/**
 * Phase 2U.6.6.2 Controlled Phrase Seed Plan diagnostic.
 *
 * Read-only. This script validates the planning artifact only.
 */

$root = dirname(__DIR__, 2);
$reportPath = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'planning' . DIRECTORY_SEPARATOR . 'phase_2u6_6_controlled_phrase_seed_plan.md';
$allowedDirty = array(
    'docs/planning/phase_2u6_6_controlled_phrase_seed_plan.md',
    'scripts/phase_2/youngo_phase_2u6_controlled_phrase_seed_plan_diagnostic.php',
);

$failures = array();

function youngo_phase_2u66_seed_plan_fail(&$failures, $message)
{
    $failures[] = $message;
}

function youngo_phase_2u66_seed_plan_has($text, $fragment)
{
    return strpos($text, $fragment) !== false;
}

if (!file_exists($reportPath)) {
    youngo_phase_2u66_seed_plan_fail($failures, 'Controlled phrase seed plan report is missing.');
} else {
    $report = file_get_contents($reportPath);
    $requiredFragments = array(
        'Phase 2U.6.6.2 Controlled Phrase Seed Plan',
        'docs/planning/phase_2u6_6_frontend_phrase_inventory.md',
        'Read-Only Phrase Table Verification',
        'Approved Seed Candidates',
        'Deferred Copy/Design Candidates',
        'Proposed Next Phases',
        'Rollback / Restore Expectations',
        'primary_navigation',
        'language_switcher',
        'footer_navigation',
        'showing_results',
        'youngo_theme_skeleton',
        'youngo_theme_skeleton_not_implemented',
        'allowed_file_types',
        'Primary navigation',
        'التنقل الرئيسي',
        'Language switcher',
        'مبدّل اللغة',
        'Footer navigation',
        'روابط التذييل',
        'Showing results',
        'عرض النتائج',
        'Do not call `get_phrase()`',
        'Do not add `/en`',
        'Do not use `arabic_translated`',
    );

    foreach ($requiredFragments as $fragment) {
        if (!youngo_phase_2u66_seed_plan_has($report, $fragment)) {
            youngo_phase_2u66_seed_plan_fail($failures, 'Seed plan report missing expected coverage: ' . $fragment);
        }
    }

    if (preg_match('/\/en(\/|\b)/', $report) && !youngo_phase_2u66_seed_plan_has($report, 'Do not add `/en`')) {
        youngo_phase_2u66_seed_plan_fail($failures, 'Seed plan appears to recommend /en usage.');
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
        youngo_phase_2u66_seed_plan_fail($failures, 'Diagnostic contains executable write-prone or phrase-helper call: ' . $name);
    }
}

$statusOutput = array();
$statusCode = 0;
exec('git -C ' . escapeshellarg($root) . ' status --short', $statusOutput, $statusCode);
if ($statusCode !== 0) {
    youngo_phase_2u66_seed_plan_fail($failures, 'Unable to inspect git status.');
} else {
    foreach ($statusOutput as $line) {
        $path = trim(substr($line, 3));
        if ($path !== '' && !in_array($path, $allowedDirty, true)) {
            youngo_phase_2u66_seed_plan_fail($failures, 'Unexpected dirty file during seed plan phase: ' . $path);
        }
    }
}

if (empty($failures)) {
    echo "PASS Phase 2U.6.6.2 controlled phrase seed plan diagnostic passed.\n";
    echo "- Seed plan report exists and includes approved/deferred keys.\n";
    echo "- Report documents English/Arabic values, verification, and rollback expectations.\n";
    echo "- Diagnostic does not call phrase helpers or write-prone methods.\n";
    echo "- Dirty worktree scope is limited to seed plan files.\n";
    exit(0);
}

echo "FAIL Phase 2U.6.6.2 controlled phrase seed plan diagnostic failed.\n";
foreach ($failures as $failure) {
    echo "- " . $failure . "\n";
}
exit(1);

