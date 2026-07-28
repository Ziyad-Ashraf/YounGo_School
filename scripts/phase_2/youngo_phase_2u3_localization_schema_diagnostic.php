<?php
/**
 * Phase 2U.3 localization schema diagnostic.
 *
 * Read-only checks for the bilingual translation-table foundation.
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

function u3_diag_section($title, $data)
{
    echo "\n== " . $title . " ==\n";
    echo json_encode($data, JSON_UNESCAPED_SLASHES) . "\n";
}

function u3_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function u3_diag_query($mysqli, $sql)
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

function u3_diag_table_exists($mysqli, $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    $rows = u3_diag_query($mysqli, "SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function u3_diag_column_exists($mysqli, $table, $column)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column) || !u3_diag_table_exists($mysqli, $table)) {
        return false;
    }

    $rows = u3_diag_query($mysqli, "SHOW COLUMNS FROM `" . $table . "` LIKE '" . $mysqli->real_escape_string($column) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function u3_diag_count($mysqli, $table)
{
    if (!u3_diag_table_exists($mysqli, $table)) {
        return null;
    }

    $rows = u3_diag_query($mysqli, "SELECT COUNT(*) AS c FROM `" . $table . "`");
    return isset($rows[0]['c']) ? (int) $rows[0]['c'] : null;
}

function u3_diag_index_exists($mysqli, $table, $index)
{
    if (!u3_diag_table_exists($mysqli, $table)) {
        return false;
    }

    $rows = u3_diag_query($mysqli, "SHOW INDEX FROM `" . $table . "` WHERE Key_name = '" . $mysqli->real_escape_string($index) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function u3_diag_duplicate_pairs($mysqli, $table, $entity_column)
{
    if (!u3_diag_table_exists($mysqli, $table)) {
        return array();
    }

    return u3_diag_query($mysqli, "
        SELECT {$entity_column}, language_code, COUNT(*) AS c
        FROM `{$table}`
        GROUP BY {$entity_column}, language_code
        HAVING COUNT(*) > 1
    ");
}

u3_diag_section('Connection', array(
    'connected' => true,
    'database_name' => $config['database'],
    'server_version' => $mysqli->server_info,
));

$tables = array(
    'youngo_course_translations',
    'youngo_category_translations',
    'youngo_section_translations',
    'youngo_lesson_translations',
);

$table_status = array();
foreach ($tables as $table) {
    $table_status[$table] = u3_diag_table_exists($mysqli, $table);
}
u3_diag_section('Translation table availability', $table_status);
u3_diag_assert($failures, !in_array(false, $table_status, true), 'One or more Phase 2U.3 translation tables are missing.');

$expected_columns = array(
    'youngo_course_translations' => array('id', 'course_id', 'language_code', 'title', 'slug', 'short_description', 'description', 'outcomes', 'requirements', 'faqs', 'seo_title', 'meta_keywords', 'meta_description', 'created_at', 'updated_at'),
    'youngo_category_translations' => array('id', 'category_id', 'language_code', 'name', 'slug', 'description', 'created_at', 'updated_at'),
    'youngo_section_translations' => array('id', 'section_id', 'language_code', 'title', 'created_at', 'updated_at'),
    'youngo_lesson_translations' => array('id', 'lesson_id', 'language_code', 'title', 'summary', 'text_content', 'created_at', 'updated_at'),
);

$column_status = array();
foreach ($expected_columns as $table => $columns) {
    $column_status[$table] = array();
    foreach ($columns as $column) {
        $column_status[$table][$column] = u3_diag_column_exists($mysqli, $table, $column);
    }
    u3_diag_assert($failures, !in_array(false, $column_status[$table], true), "{$table} is missing expected columns.");
}
u3_diag_section('Expected column availability', $column_status);

$expected_indexes = array(
    'youngo_course_translations' => array('uniq_yct_course_language', 'idx_yct_language_code', 'idx_yct_slug'),
    'youngo_category_translations' => array('uniq_ycat_category_language', 'idx_ycat_language_code', 'idx_ycat_slug'),
    'youngo_section_translations' => array('uniq_yst_section_language', 'idx_yst_language_code'),
    'youngo_lesson_translations' => array('uniq_ylt_lesson_language', 'idx_ylt_language_code'),
);

$index_status = array();
foreach ($expected_indexes as $table => $indexes) {
    $index_status[$table] = array();
    foreach ($indexes as $index) {
        $index_status[$table][$index] = u3_diag_index_exists($mysqli, $table, $index);
    }
    u3_diag_assert($failures, !in_array(false, $index_status[$table], true), "{$table} is missing expected indexes.");
}
u3_diag_section('Expected index availability', $index_status);

$counts = array();
foreach (array('course', 'category', 'section', 'lesson', 'youngo_course_translations', 'youngo_category_translations', 'youngo_section_translations', 'youngo_lesson_translations', 'enrol', 'payment', 'watch_histories', 'watched_duration', 'youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants', 'youngo_checkout_orders', 'youngo_coupon_usages') as $table) {
    $counts[$table] = u3_diag_count($mysqli, $table);
}
u3_diag_section('Row counts', $counts);

$english_counts = array();
foreach (array(
    'youngo_course_translations' => 'course',
    'youngo_category_translations' => 'category',
    'youngo_section_translations' => 'section',
    'youngo_lesson_translations' => 'lesson',
) as $translation_table => $canonical_table) {
    if (!u3_diag_table_exists($mysqli, $translation_table)) {
        $english_counts[$translation_table] = null;
        continue;
    }
    $rows = u3_diag_query($mysqli, "SELECT COUNT(*) AS c FROM `{$translation_table}` WHERE language_code = 'english'");
    $english_count = isset($rows[0]['c']) ? (int) $rows[0]['c'] : null;
    $english_counts[$translation_table] = array(
        'english_rows' => $english_count,
        'canonical_rows' => $counts[$canonical_table],
        'matches_or_exceeds_canonical' => $english_count !== null && $counts[$canonical_table] !== null && $english_count >= $counts[$canonical_table],
    );
    u3_diag_assert($failures, $english_counts[$translation_table]['matches_or_exceeds_canonical'], "{$translation_table} English row count is less than {$canonical_table} count.");
}
u3_diag_section('English seed coverage', $english_counts);

$duplicates = array(
    'youngo_course_translations' => u3_diag_duplicate_pairs($mysqli, 'youngo_course_translations', 'course_id'),
    'youngo_category_translations' => u3_diag_duplicate_pairs($mysqli, 'youngo_category_translations', 'category_id'),
    'youngo_section_translations' => u3_diag_duplicate_pairs($mysqli, 'youngo_section_translations', 'section_id'),
    'youngo_lesson_translations' => u3_diag_duplicate_pairs($mysqli, 'youngo_lesson_translations', 'lesson_id'),
);
u3_diag_section('Duplicate entity/language pairs', $duplicates);
foreach ($duplicates as $table => $rows) {
    u3_diag_assert($failures, is_array($rows) && count($rows) === 0, "{$table} has duplicate entity/language pairs.");
}

$language_codes = array();
foreach ($tables as $table) {
    if (!u3_diag_table_exists($mysqli, $table)) {
        continue;
    }
    $language_codes[$table] = u3_diag_query($mysqli, "SELECT DISTINCT language_code FROM `{$table}` ORDER BY language_code");
}
u3_diag_section('Language codes present', $language_codes);
foreach ($language_codes as $table => $rows) {
    foreach ($rows as $row) {
        if (!in_array($row['language_code'], array('english', 'arabic'), true)) {
            $failures[] = "{$table} contains unexpected language code: " . $row['language_code'];
        }
    }
}

$canonical_columns = array(
    'course' => array('id', 'title', 'short_description', 'description', 'outcomes', 'faqs', 'language', 'category_id', 'sub_category_id', 'requirements', 'meta_keywords', 'meta_description', 'youngo_access_mode'),
    'category' => array('id', 'name', 'slug', 'parent'),
    'section' => array('id', 'title', 'course_id'),
    'lesson' => array('id', 'title', 'course_id', 'section_id', 'summary'),
);
$canonical_status = array();
foreach ($canonical_columns as $table => $columns) {
    $canonical_status[$table] = array();
    foreach ($columns as $column) {
        $canonical_status[$table][$column] = u3_diag_column_exists($mysqli, $table, $column);
    }
    u3_diag_assert($failures, !in_array(false, $canonical_status[$table], true), "{$table} canonical columns unexpectedly missing.");
}
u3_diag_section('Canonical table compatibility columns', $canonical_status);

$entitlement_counts = array(
    'youngo_course_access' => $counts['youngo_course_access'],
    'youngo_user_subscriptions' => $counts['youngo_user_subscriptions'],
    'youngo_manual_grants' => $counts['youngo_manual_grants'],
    'youngo_checkout_orders' => $counts['youngo_checkout_orders'],
);
u3_diag_section('Entitlement table counts', $entitlement_counts);
foreach ($entitlement_counts as $table => $count) {
    u3_diag_assert($failures, $count === 0, "{$table} is expected to remain empty after cleanup.");
}

u3_diag_section('Warnings', $warnings);
u3_diag_section('Read-only safety', array(
    'result' => 'Diagnostic used SELECT/SHOW-only queries, blocked write SQL verbs, created no sessions/cookies, and did not modify data.',
));
u3_diag_section('Result', array(
    'status' => empty($failures) ? 'PASS' : 'FAIL',
    'failures' => $failures,
));

exit(empty($failures) ? 0 : 1);
