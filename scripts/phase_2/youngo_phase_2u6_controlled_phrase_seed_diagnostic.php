<?php

/**
 * Phase 2U.6.6.3 controlled phrase seed diagnostic.
 *
 * Read-only. This script verifies the seeded phrase rows and expected file
 * scope only. It never calls phrase helpers.
 */

$root = dirname(__DIR__, 2);
$failures = array();

$approvedPhrases = array(
    'primary_navigation' => array(
        'english' => 'Primary navigation',
        'arabic' => 'التنقل الرئيسي',
    ),
    'language_switcher' => array(
        'english' => 'Language switcher',
        'arabic' => 'مبدّل اللغة',
    ),
    'footer_navigation' => array(
        'english' => 'Footer navigation',
        'arabic' => 'روابط التذييل',
    ),
    'showing_results' => array(
        'english' => 'Showing results',
        'arabic' => 'عرض النتائج',
    ),
);

$deferredPhrases = array(
    'youngo_theme_skeleton',
    'youngo_theme_skeleton_not_implemented',
    'allowed_file_types',
);

$expectedFiles = array(
    'database/phase_2/youngo_phase_2u6_6_3_controlled_phrase_seed_up.sql',
    'database/phase_2/youngo_phase_2u6_6_3_controlled_phrase_seed_down.sql',
    'scripts/phase_2/youngo_phase_2u6_controlled_phrase_seed_diagnostic.php',
    'scripts/phase_2/youngo_phase_2u6_apply_controlled_phrase_seed.php',
);

function youngo_phase_2u663_seed_diag_fail(&$failures, $message)
{
    $failures[] = $message;
}

function youngo_phase_2u663_seed_diag_fetch_phrase(mysqli $mysqli, $phrase)
{
    $stmt = $mysqli->prepare('SELECT phrase, english, arabic FROM language WHERE phrase = ? LIMIT 1');
    $stmt->bind_param('s', $phrase);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

foreach ($expectedFiles as $file) {
    if (!file_exists($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file))) {
        youngo_phase_2u663_seed_diag_fail($failures, 'Expected Phase 2U.6.6.3 file is missing: ' . $file);
    }
}

$sqlFiles = array(
    $root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'phase_2' . DIRECTORY_SEPARATOR . 'youngo_phase_2u6_6_3_controlled_phrase_seed_up.sql',
    $root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'phase_2' . DIRECTORY_SEPARATOR . 'youngo_phase_2u6_6_3_controlled_phrase_seed_down.sql',
);

$deprecatedArabicAlias = 'arabic' . '_' . 'translated';

foreach ($sqlFiles as $sqlFile) {
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        if (stripos($sql, $deprecatedArabicAlias) !== false) {
            youngo_phase_2u663_seed_diag_fail($failures, basename($sqlFile) . ' references deprecated Arabic alias.');
        }
        foreach ($deferredPhrases as $phrase) {
            if (stripos($sql, $phrase) !== false) {
                youngo_phase_2u663_seed_diag_fail($failures, basename($sqlFile) . ' references deferred key: ' . $phrase);
            }
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
        youngo_phase_2u663_seed_diag_fail($failures, 'Diagnostic contains executable write-prone or phrase-helper call: ' . $name);
    }
}

$mysqli = new mysqli('localhost', 'root', '', 'youngo_school');
if ($mysqli->connect_error) {
    youngo_phase_2u663_seed_diag_fail($failures, 'Database connection failed: ' . $mysqli->connect_error);
} else {
    $mysqli->set_charset('utf8mb4');

    foreach ($approvedPhrases as $phrase => $values) {
        $row = youngo_phase_2u663_seed_diag_fetch_phrase($mysqli, $phrase);
        if (!$row) {
            youngo_phase_2u663_seed_diag_fail($failures, 'Approved phrase is missing: ' . $phrase);
            continue;
        }

        if ((string) $row['english'] !== $values['english']) {
            youngo_phase_2u663_seed_diag_fail($failures, 'Approved phrase English mismatch: ' . $phrase);
        }

        if ((string) $row['arabic'] !== $values['arabic']) {
            youngo_phase_2u663_seed_diag_fail($failures, 'Approved phrase Arabic mismatch: ' . $phrase);
        }
    }

    foreach ($deferredPhrases as $phrase) {
        if (youngo_phase_2u663_seed_diag_fetch_phrase($mysqli, $phrase)) {
            youngo_phase_2u663_seed_diag_fail($failures, 'Deferred phrase key exists and should not be seeded by this phase: ' . $phrase);
        }
    }
}

$statusOutput = array();
$statusCode = 0;
exec('git -C ' . escapeshellarg($root) . ' status --short', $statusOutput, $statusCode);
if ($statusCode !== 0) {
    youngo_phase_2u663_seed_diag_fail($failures, 'Unable to inspect git status.');
} else {
    foreach ($statusOutput as $line) {
        $path = trim(substr($line, 3));
        if ($path !== '' && !in_array($path, $expectedFiles, true)) {
            youngo_phase_2u663_seed_diag_fail($failures, 'Unexpected dirty file: ' . $path);
        }
    }
}

if (empty($failures)) {
    echo "PASS Phase 2U.6.6.3 controlled phrase seed diagnostic passed.\n";
    echo "- Approved phrase keys exist with exact English and Arabic values.\n";
    echo "- Deferred phrase keys are absent.\n";
    echo "- Required SQL/runner/diagnostic files exist.\n";
    echo "- Dirty worktree scope is limited to Phase 2U.6.6.3 files.\n";
    exit(0);
}

echo "FAIL Phase 2U.6.6.3 controlled phrase seed diagnostic failed.\n";
foreach ($failures as $failure) {
    echo "- " . $failure . "\n";
}
exit(1);

