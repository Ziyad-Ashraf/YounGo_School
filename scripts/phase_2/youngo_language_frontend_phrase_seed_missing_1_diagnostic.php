<?php
/**
 * LANGUAGE.FRONTEND.PHRASE.SEED.MISSING.1 diagnostic.
 *
 * Read-only verification for the approved public frontend phrase seed.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$root = dirname(__DIR__, 2);
chdir($root);

defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);

require $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

$failures = array();

function yfpsm1d_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function yfpsm1d_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function yfpsm1d_connect($db, $active_group)
{
    if (!isset($db[$active_group])) {
        throw new RuntimeException('Active database group was not found.');
    }

    $config = $db[$active_group];
    $mysqli = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
    if ($mysqli->connect_errno) {
        throw new RuntimeException('DB connection failed without exposing credentials.');
    }
    $mysqli->set_charset('utf8mb4');

    return $mysqli;
}

function yfpsm1d_seed_keys($root)
{
    $seedFile = $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'phase_2' . DIRECTORY_SEPARATOR . 'youngo_language_frontend_phrase_seed_missing_1.php';
    $source = file_get_contents($seedFile);
    preg_match_all("/array\\('key'\\s*=>\\s*'([^']+)'/", $source, $matches);

    return array_values(array_unique($matches[1]));
}

function yfpsm1d_table_exists($mysqli, $table)
{
    $result = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    $exists = $result && $result->num_rows > 0;

    return $exists;
}

try {
    $mysqli = yfpsm1d_connect($db, $active_group);
    $keys = yfpsm1d_seed_keys($root);
    $counts = array(
        'approved_seed_keys' => count($keys),
        'rows_found' => 0,
        'missing_rows' => 0,
        'blank_english' => 0,
        'blank_arabic' => 0,
        'arabic_translated_column_exists' => false,
        'arabic_translated_metadata_rows' => 0,
        'payment_or_paymob_key_count' => 0,
        'route_or_url_like_value_count' => 0,
    );

    foreach ($keys as $key) {
        if (stripos($key, 'paymob') !== false || preg_match('/(^|_)order(_|$)/', $key) || preg_match('/(^|_)enrol(_|$)/', $key)) {
            $counts['payment_or_paymob_key_count']++;
        }

        $stmt = $mysqli->prepare('SELECT phrase, english, arabic FROM language WHERE phrase = ? ORDER BY phrase_id ASC LIMIT 1');
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $counts['missing_rows']++;
            continue;
        }

        $counts['rows_found']++;
        if (trim((string) $row['english']) === '') {
            $counts['blank_english']++;
        }
        if (trim((string) $row['arabic']) === '') {
            $counts['blank_arabic']++;
        }
        if (preg_match('#https?://|/payment|paymob|checkout/#i', (string) $row['english'] . ' ' . (string) $row['arabic'])) {
            $counts['route_or_url_like_value_count']++;
        }
    }

    $columnResult = $mysqli->query("SHOW COLUMNS FROM language LIKE 'arabic_translated'");
    $counts['arabic_translated_column_exists'] = $columnResult && $columnResult->num_rows > 0;

    if (yfpsm1d_table_exists($mysqli, 'youngo_language_phrase_meta')) {
        $result = $mysqli->query("SELECT COUNT(*) AS total FROM youngo_language_phrase_meta WHERE language_code = 'arabic_translated'");
        $row = $result ? $result->fetch_assoc() : array('total' => 0);
        $counts['arabic_translated_metadata_rows'] = (int) $row['total'];
    }

    $helperSource = file_get_contents($root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'youngo_frontend_language_helper.php');
    $modelSource = file_get_contents($root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Youngo_language_phrase_model.php');
    $seedSource = file_get_contents($root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'phase_2' . DIRECTORY_SEPARATOR . 'youngo_language_frontend_phrase_seed_missing_1.php');
    $gitStatus = trim(shell_exec('git -C ' . escapeshellarg($root) . ' status --short'));

    $checks = array(
        'approved_seed_key_count_is_83' => count($keys) === 83,
        'all_seed_rows_exist' => $counts['rows_found'] === 83 && $counts['missing_rows'] === 0,
        'all_seed_rows_have_english' => $counts['blank_english'] === 0,
        'all_seed_rows_have_arabic' => $counts['blank_arabic'] === 0,
        'arabic_translated_column_not_created' => $counts['arabic_translated_column_exists'] === false,
        'arabic_translated_metadata_not_created' => $counts['arabic_translated_metadata_rows'] === 0,
        'no_paymob_order_enrol_seed_keys' => $counts['payment_or_paymob_key_count'] === 0,
        'no_payment_or_paymob_urls_in_seed_values' => $counts['route_or_url_like_value_count'] === 0,
        'seed_script_checks_manual_override_metadata' => strpos($seedSource, 'manual_override') !== false && strpos($seedSource, 'yfPSM') === false,
        'frontend_phrase_helper_still_present' => strpos($helperSource, 'function youngo_frontend_phrase(') !== false && strpos($helperSource, 'function youngo_frontend_phrase_e(') !== false,
        'frontend_phrase_helper_rejects_arabic_translated' => strpos($helperSource, "youngo_frontend_phrase_language_code('arabic_translated')") !== false || strpos($helperSource, 'arabic_translated') !== false,
        'edit_phrase_pagination_model_present' => strpos($modelSource, 'get_paginated_edit_phrases') !== false,
        'no_payment_or_paymob_files_changed' => !preg_match('/payment|paymob/i', $gitStatus),
    );

    yfpsm1d_print('Seed diagnostic counts', $counts);
    yfpsm1d_print('Diagnostic checks', $checks);

    foreach ($checks as $label => $ok) {
        yfpsm1d_assert($failures, $ok, 'Diagnostic check failed: ' . $label);
    }

    if (!empty($failures)) {
        yfpsm1d_print('FAILURES', $failures);
        exit(1);
    }

    yfpsm1d_print('Result', 'PASS: approved public frontend seed keys exist with English and Arabic values, without arabic_translated or payment behavior changes.');
    exit(0);
} catch (Throwable $e) {
    yfpsm1d_print('Result', 'FAIL: ' . $e->getMessage());
    exit(1);
}
