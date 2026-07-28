<?php
/**
 * Phase 2U.4 Arabic phrase diagnostic.
 *
 * Read-only checks for canonical Arabic UI phrase support.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$root = dirname(dirname(__DIR__));
define('ENVIRONMENT', 'development');
define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'config/database.php';

$config = $db['default'];
$mysqli = @new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
if ($mysqli->connect_errno) {
    echo "== Connection ==\n";
    echo json_encode(array('connected' => false, 'error' => 'DB connection failed without exposing credentials.')) . "\n";
    exit(2);
}
$mysqli->set_charset('utf8');

$failures = array();
$warnings = array();

function u4_diag_section($title, $data)
{
    echo "\n== " . $title . " ==\n";
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}

function u4_diag_query($mysqli, $sql)
{
    if (preg_match('/\b(insert|update|delete|replace|alter|drop|create|truncate|grant|revoke|set)\b/i', $sql)) {
        throw new Exception('Write SQL blocked by diagnostic wrapper.');
    }

    $result = $mysqli->query($sql);
    if (!$result) {
        return array('error' => 'Query failed without exposing credentials.');
    }

    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function u4_diag_table_exists($mysqli, $table)
{
    $rows = u4_diag_query($mysqli, "SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function u4_diag_column_exists($mysqli, $table, $column)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column) || !u4_diag_table_exists($mysqli, $table)) {
        return false;
    }
    $rows = u4_diag_query($mysqli, "SHOW COLUMNS FROM `{$table}` LIKE '" . $mysqli->real_escape_string($column) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function u4_diag_count($mysqli, $table)
{
    if (!u4_diag_table_exists($mysqli, $table)) {
        return null;
    }
    $rows = u4_diag_query($mysqli, "SELECT COUNT(*) AS c FROM `{$table}`");
    return isset($rows[0]['c']) ? (int) $rows[0]['c'] : null;
}

function u4_diag_placeholders($value)
{
    preg_match_all('/(%(?:\\d+\\$)?[bcdeEfFgGosuxX]|\\{\\{[^}]+\\}\\}|\\{[A-Za-z0-9_]+\\}|:[A-Za-z_][A-Za-z0-9_]*|\\$[A-Za-z_][A-Za-z0-9_]*)/', (string) $value, $matches);
    $items = $matches[0];
    sort($items);
    return $items;
}

u4_diag_section('Connection', array(
    'connected' => true,
    'database_name' => $config['database'],
    'server_version' => $mysqli->server_info,
));

$schema = array(
    'language_table_exists' => u4_diag_table_exists($mysqli, 'language'),
    'english_column_exists' => u4_diag_column_exists($mysqli, 'language', 'english'),
    'arabic_column_exists' => u4_diag_column_exists($mysqli, 'language', 'arabic'),
    'arabic_translated_column_exists' => u4_diag_column_exists($mysqli, 'language', 'arabic_translated'),
);
u4_diag_section('Language schema', $schema);
foreach (array('language_table_exists', 'english_column_exists', 'arabic_column_exists') as $key) {
    if (!$schema[$key]) {
        $failures[] = $key . ' is required.';
    }
}

$settings = array('language' => null, 'language_dirs' => null, 'arabic_dir' => null);
if (u4_diag_table_exists($mysqli, 'settings')) {
    $rows = u4_diag_query($mysqli, "SELECT `key`, `value` FROM settings WHERE `key` IN ('language', 'language_dirs')");
    foreach ($rows as $row) {
        $settings[$row['key']] = $row['value'];
    }
    $dirs = json_decode((string) $settings['language_dirs'], true);
    $settings['arabic_dir'] = is_array($dirs) && isset($dirs['arabic']) ? $dirs['arabic'] : null;
}
u4_diag_section('Language settings', $settings);
if ($settings['language'] !== 'english') {
    $failures[] = 'Default language must remain english.';
}
if ($settings['arabic_dir'] !== 'rtl') {
    $failures[] = 'settings.language_dirs must contain arabic: rtl.';
}

$coverage = array();
if ($schema['arabic_column_exists']) {
    $rows = u4_diag_query($mysqli, "
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN english IS NOT NULL AND TRIM(english) <> '' THEN 1 ELSE 0 END) AS english_non_empty,
            SUM(CASE WHEN arabic IS NOT NULL AND TRIM(arabic) <> '' THEN 1 ELSE 0 END) AS arabic_non_empty,
            SUM(CASE WHEN english IS NOT NULL AND TRIM(english) <> '' AND (arabic IS NULL OR TRIM(arabic) = '') THEN 1 ELSE 0 END) AS arabic_empty_for_english,
            SUM(CASE WHEN arabic REGEXP 'Ø|Ù|Ã|�' THEN 1 ELSE 0 END) AS mojibake_count,
            SUM(CASE WHEN arabic REGEXP '[اأإآبتثجحخدذرزسشصضطظعغفقكلمنهويىة]' THEN 1 ELSE 0 END) AS arabic_script_count,
            SUM(CASE WHEN english IS NOT NULL AND arabic IS NOT NULL AND TRIM(english) <> '' AND TRIM(english) = TRIM(arabic) THEN 1 ELSE 0 END) AS identical_to_english
        FROM language
    ");
    $coverage = isset($rows[0]) ? $rows[0] : array();
    $coverage['arabic_coverage_percent'] = ((int) $coverage['english_non_empty'] > 0)
        ? round(((int) $coverage['arabic_non_empty'] / (int) $coverage['english_non_empty']) * 100, 2)
        : 0;
}
u4_diag_section('Arabic phrase coverage', $coverage);
if (isset($coverage['arabic_empty_for_english']) && (int) $coverage['arabic_empty_for_english'] > 0) {
    $warnings[] = 'Some English phrases still have empty Arabic values.';
}
if (isset($coverage['mojibake_count']) && (int) $coverage['mojibake_count'] > 0) {
    $failures[] = 'Arabic phrases contain mojibake patterns.';
}

$priority_phrases = array(
    'home',
    'courses',
    'my_courses',
    'my_access',
    'my_wishlist',
    'access_for_this_course_is_managed_by_your_school/admin.',
    'subscription_checkout_is_not_available_yet',
    'subscription_not_available_yet',
    'add_to_cart',
    'buy_now',
    'enroll_now',
    'start_now',
    'continue_learning',
    'login',
    'sign_up',
    'shopping_cart',
);

$priority_status = array();
if ($schema['arabic_column_exists']) {
    foreach ($priority_phrases as $phrase) {
        $rows = u4_diag_query($mysqli, "SELECT phrase, english, arabic FROM language WHERE phrase = '" . $mysqli->real_escape_string($phrase) . "' LIMIT 1");
        $row = isset($rows[0]) ? $rows[0] : null;
        $ok = $row && trim((string) $row['arabic']) !== '' && trim((string) $row['arabic']) !== trim((string) $row['english']) && !preg_match('/(Ø|Ù|Ã|�)/u', (string) $row['arabic']);
        $priority_status[$phrase] = $ok;
        if (!$ok) {
            $failures[] = 'High-priority Arabic phrase missing or unsafe: ' . $phrase;
        }
    }
}
u4_diag_section('High priority phrase status', $priority_status);

$placeholder_rows = array();
if ($schema['arabic_column_exists']) {
    $placeholder_rows = u4_diag_query($mysqli, "SELECT phrase, english, arabic FROM language WHERE english REGEXP '%[bcdeEfFgGosuxX]|\\\\{\\\\{|\\\\{[A-Za-z0-9_]+\\\\}|:[A-Za-z_]|\\\\$[A-Za-z_]'");
}
$placeholder_warnings = array();
foreach ($placeholder_rows as $row) {
    if (u4_diag_placeholders($row['english']) !== u4_diag_placeholders($row['arabic'])) {
        $placeholder_warnings[] = $row['phrase'];
    }
}
u4_diag_section('Placeholder preservation', array(
    'checked' => count($placeholder_rows),
    'warnings' => count($placeholder_warnings),
));
if (count($placeholder_warnings) > 0) {
    $failures[] = 'One or more Arabic phrases do not preserve placeholders.';
}

$phase_2u3_counts = array();
foreach (array('youngo_course_translations', 'youngo_category_translations', 'youngo_section_translations', 'youngo_lesson_translations') as $table) {
    $phase_2u3_counts[$table] = array(
        'total' => u4_diag_count($mysqli, $table),
        'arabic_rows' => null,
    );
    if (u4_diag_table_exists($mysqli, $table)) {
        $rows = u4_diag_query($mysqli, "SELECT COUNT(*) AS c FROM `{$table}` WHERE language_code = 'arabic'");
        $phase_2u3_counts[$table]['arabic_rows'] = isset($rows[0]['c']) ? (int) $rows[0]['c'] : null;
        if ($table !== 'youngo_course_translations' && $phase_2u3_counts[$table]['arabic_rows'] !== 0) {
            $failures[] = "{$table} should not contain Arabic rows in Phase 2U.4.";
        }
    }
}
u4_diag_section('Phase 2U.3 translation table language state', $phase_2u3_counts);

$protected_counts = array();
foreach (array('youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants', 'youngo_checkout_orders', 'youngo_coupon_usages', 'payment') as $table) {
    $protected_counts[$table] = u4_diag_count($mysqli, $table);
}
u4_diag_section('Protected table counts', $protected_counts);
foreach (array('youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants', 'youngo_checkout_orders') as $table) {
    if ($protected_counts[$table] !== 0) {
        $failures[] = "{$table} should remain empty.";
    }
}

u4_diag_section('Deprecated Arabic placeholder state', array(
    'arabic_translated_column_exists' => $schema['arabic_translated_column_exists'],
    'arabic_translated_json_exists' => is_file($root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'arabic_translated.json'),
    'canonical_language_code' => 'arabic',
));

u4_diag_section('Warnings', $warnings);
u4_diag_section('Read-only safety', array(
    'result' => 'Diagnostic used SELECT/SHOW-only queries, blocked write SQL verbs, created no sessions/cookies, and did not modify data.',
));
u4_diag_section('Result', array(
    'status' => empty($failures) ? 'PASS' : 'FAIL',
    'failures' => $failures,
));

exit(empty($failures) ? 0 : 1);
