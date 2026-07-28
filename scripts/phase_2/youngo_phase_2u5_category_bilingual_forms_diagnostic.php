<?php
/**
 * Phase 2U.5.3 category/subcategory bilingual forms diagnostic.
 *
 * Read-only checks for category/subcategory dashboard form wiring.
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

function u53_section($title, $data)
{
    echo "\n== " . $title . " ==\n";
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
}

function u53_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function u53_file($root, $path)
{
    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
}

function u53_read($root, $path)
{
    $file = u53_file($root, $path);
    if (!is_file($file)) {
        return null;
    }

    return file_get_contents($file);
}

function u53_contains($content, $needle)
{
    return is_string($content) && strpos($content, $needle) !== false;
}

function u53_query($mysqli, $sql)
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

function u53_table_exists($mysqli, $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    $rows = u53_query($mysqli, "SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function u53_count($mysqli, $table, $where = '')
{
    if (!u53_table_exists($mysqli, $table)) {
        return null;
    }

    $sql = "SELECT COUNT(*) AS c FROM `" . $table . "`";
    if ($where !== '') {
        $sql .= " WHERE " . $where;
    }

    $rows = u53_query($mysqli, $sql);
    return isset($rows[0]['c']) ? (int) $rows[0]['c'] : null;
}

$required_files = array(
    'translation_model' => 'application/models/Youngo_translation_model.php',
    'crud_model' => 'application/models/Crud_model.php',
    'category_add' => 'application/views/backend/admin/category_add.php',
    'category_edit' => 'application/views/backend/admin/category_edit.php',
    'sub_category_add' => 'application/views/backend/admin/sub_category_add.php',
    'sub_category_edit' => 'application/views/backend/admin/sub_category_edit.php',
);

$file_exists = array();
$contents = array();
foreach ($required_files as $key => $path) {
    $file_exists[$path] = is_file(u53_file($root, $path));
    $contents[$key] = u53_read($root, $path);
}
u53_section('Required file availability', $file_exists);

foreach ($file_exists as $path => $exists) {
    u53_assert($failures, $exists, 'Missing required file: ' . $path);
}

$form_checks = array();
foreach (array('category_add', 'category_edit', 'sub_category_add', 'sub_category_edit') as $key) {
    $content = $contents[$key];
    $form_checks[$key] = array(
        'english_name' => u53_contains($content, 'name="english_name"'),
        'english_slug' => u53_contains($content, 'name="english_slug"'),
        'arabic_name' => u53_contains($content, 'name="arabic_name"'),
        'arabic_slug' => u53_contains($content, 'name="arabic_slug"'),
        'arabic_dir_rtl' => u53_contains($content, 'dir="rtl"'),
        'shared_code_field' => u53_contains($content, 'name = "code"') || u53_contains($content, 'name="code"'),
        'shared_parent_or_category_field' => u53_contains($content, 'name="parent"') || u53_contains($content, 'name = "parent"') || u53_contains($content, 'name="category_id"'),
    );

    foreach ($form_checks[$key] as $check => $ok) {
        u53_assert($failures, $ok, $key . ' missing expected bilingual/shared form marker: ' . $check);
    }
}
u53_section('Category/subcategory form source checks', $form_checks);

$save_checks = array(
    'loads_translation_model' => u53_contains($contents['crud_model'], "load->model('youngo_translation_model')") || u53_contains($contents['crud_model'], 'load->model("youngo_translation_model")'),
    'reads_english_name' => u53_contains($contents['crud_model'], "post('english_name')"),
    'reads_english_slug' => u53_contains($contents['crud_model'], "post('english_slug')"),
    'reads_arabic_name' => u53_contains($contents['crud_model'], "post('arabic_name')"),
    'reads_arabic_slug' => u53_contains($contents['crud_model'], "post('arabic_slug')"),
    'upserts_english_category_translation' => u53_contains($contents['crud_model'], 'upsert_category_translation($category_id, \'english\''),
    'conditionally_upserts_arabic_category_translation' => u53_contains($contents['crud_model'], 'upsert_category_translation($category_id, \'arabic\'') && u53_contains($contents['crud_model'], "arabic_name'] !== ''"),
    'syncs_canonical_name_from_english' => u53_contains($contents['crud_model'], "\$data['name']   = html_escape(\$translation_fields['english_name'])"),
    'syncs_canonical_slug_from_english' => u53_contains($contents['crud_model'], "\$data['slug']   = html_escape(\$translation_fields['english_slug'])"),
);
u53_section('Save path source checks', $save_checks);
foreach ($save_checks as $check => $ok) {
    u53_assert($failures, $ok, 'Crud_model missing expected save-path marker: ' . $check);
}

$out_of_scope_files = array(
    'application/views/backend/admin/course_add.php',
    'application/views/backend/admin/course_edit.php',
    'application/views/backend/admin/section_add.php',
    'application/views/backend/admin/section_edit.php',
    'application/views/backend/admin/lesson_add.php',
    'application/views/backend/admin/lesson_edit.php',
);
$out_of_scope_checks = array();
foreach ($out_of_scope_files as $path) {
    $content = u53_read($root, $path);
    $out_of_scope_checks[$path] = array(
        'exists' => $content !== null,
        'no_category_bilingual_field_markers' => !u53_contains($content, 'name="arabic_name"') && !u53_contains($content, 'name="english_name"'),
    );
}
u53_section('Out-of-scope form source checks', $out_of_scope_checks);

$category_count = u53_count($mysqli, 'category');
$english_category_rows = u53_count($mysqli, 'youngo_category_translations', "language_code = 'english'");
$arabic_category_rows = u53_count($mysqli, 'youngo_category_translations', "language_code = 'arabic'");
$table_state = array(
    'category' => $category_count,
    'youngo_category_translations_exists' => u53_table_exists($mysqli, 'youngo_category_translations'),
    'english_category_translation_rows' => $english_category_rows,
    'arabic_category_translation_rows' => $arabic_category_rows,
);
u53_section('Category translation table state', $table_state);
u53_assert($failures, $table_state['youngo_category_translations_exists'], 'youngo_category_translations table is missing.');
u53_assert($failures, $english_category_rows !== null && $category_count !== null && $english_category_rows >= $category_count, 'English category translation coverage is lower than category count.');
u53_assert($failures, $arabic_category_rows === 0, 'Arabic category translation rows exist; this phase should not create them through QA.');

$other_translation_rows = array(
    'youngo_course_translations.arabic' => u53_count($mysqli, 'youngo_course_translations', "language_code = 'arabic'"),
    'youngo_section_translations.arabic' => u53_count($mysqli, 'youngo_section_translations', "language_code = 'arabic'"),
    'youngo_lesson_translations.arabic' => u53_count($mysqli, 'youngo_lesson_translations', "language_code = 'arabic'"),
);
u53_section('Out-of-scope translation table Arabic rows', $other_translation_rows);
foreach ($other_translation_rows as $label => $count) {
    if ($label === 'youngo_course_translations.arabic') {
        continue;
    }
    u53_assert($failures, $count === 0, $label . ' should remain 0 in Phase 2U.5.3.');
}

$protected_counts = array(
    'course' => u53_count($mysqli, 'course'),
    'section' => u53_count($mysqli, 'section'),
    'lesson' => u53_count($mysqli, 'lesson'),
    'enrol' => u53_count($mysqli, 'enrol'),
    'payment' => u53_count($mysqli, 'payment'),
    'watch_histories' => u53_count($mysqli, 'watch_histories'),
    'watched_duration' => u53_count($mysqli, 'watched_duration'),
    'youngo_course_access' => u53_count($mysqli, 'youngo_course_access'),
    'youngo_user_subscriptions' => u53_count($mysqli, 'youngo_user_subscriptions'),
    'youngo_manual_grants' => u53_count($mysqli, 'youngo_manual_grants'),
    'youngo_checkout_orders' => u53_count($mysqli, 'youngo_checkout_orders'),
    'youngo_coupon_usages' => u53_count($mysqli, 'youngo_coupon_usages'),
);
u53_section('Protected table counts', $protected_counts);
foreach (array('payment', 'watch_histories', 'watched_duration', 'youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants', 'youngo_checkout_orders', 'youngo_coupon_usages') as $table) {
    u53_assert($failures, $protected_counts[$table] === 0, $table . ' should remain 0.');
}

u53_section('Warnings', $warnings);
u53_section('Read-only safety', array(
    'result' => 'Diagnostic used file reads and SELECT/SHOW-only queries, submitted no forms, called no model write methods, and did not modify data.',
));

if (!empty($failures)) {
    u53_section('Result', array('status' => 'FAIL', 'failures' => $failures));
    exit(1);
}

u53_section('Result', array('status' => 'PASS', 'failures' => array()));
exit(0);
