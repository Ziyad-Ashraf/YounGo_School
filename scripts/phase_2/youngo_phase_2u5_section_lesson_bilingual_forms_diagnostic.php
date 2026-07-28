<?php
/**
 * Phase 2U.5.5 section/lesson bilingual forms diagnostic.
 *
 * Read-only checks for section and lesson dashboard bilingual form wiring.
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

function u55_section($title, $data)
{
    echo "\n== " . $title . " ==\n";
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
}

function u55_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function u55_file($root, $path)
{
    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
}

function u55_read($root, $path)
{
    $file = u55_file($root, $path);
    if (!is_file($file)) {
        return null;
    }

    return file_get_contents($file);
}

function u55_contains($content, $needle)
{
    return is_string($content) && strpos($content, $needle) !== false;
}

function u55_routes_diff_is_arabic_alias_only($root)
{
    $diff_lines = array();
    $exit_code = 1;
    exec('git -C ' . escapeshellarg($root) . ' diff -- application/config/routes.php', $diff_lines, $exit_code);
    if ($exit_code !== 0) {
        return false;
    }

    foreach ($diff_lines as $line) {
        if ($line === '' || $line[0] !== '+' || strpos($line, '+++') === 0) {
            continue;
        }

        $added = substr($line, 1);
        if (trim($added) === '' || trim($added) === '// YounGo Arabic public frontend aliases.') {
            continue;
        }

        if (!preg_match('/^\\$route\\[\'ar(?:\'|\\/[^\'"]+\')\\]\\s*=\\s*\'[^\'"]+\';$/', $added)) {
            return false;
        }
    }

    foreach ($diff_lines as $line) {
        if ($line !== '' && $line[0] === '-' && strpos($line, '---') !== 0) {
            return false;
        }
    }

    return true;
}

function u55_query($mysqli, $sql)
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

function u55_table_exists($mysqli, $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    $rows = u55_query($mysqli, "SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function u55_count($mysqli, $table, $where = '')
{
    if (!u55_table_exists($mysqli, $table)) {
        return null;
    }

    $sql = "SELECT COUNT(*) AS c FROM `" . $table . "`";
    if ($where !== '') {
        $sql .= " WHERE " . $where;
    }

    $rows = u55_query($mysqli, $sql);
    return isset($rows[0]['c']) ? (int) $rows[0]['c'] : null;
}

function u55_run_php_script($root, $script)
{
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(u55_file($root, $script));
    $output = array();
    $exit_code = 1;
    exec($command, $output, $exit_code);

    return array(
        'script' => $script,
        'exit_code' => $exit_code,
        'passes' => $exit_code === 0,
    );
}

$required_files = array(
    'translation_model' => 'application/models/Youngo_translation_model.php',
    'crud_model' => 'application/models/Crud_model.php',
    'section_add' => 'application/views/backend/admin/section_add.php',
    'section_edit' => 'application/views/backend/admin/section_edit.php',
    'lesson_add' => 'application/views/backend/admin/lesson_add.php',
    'lesson_edit' => 'application/views/backend/admin/lesson_edit.php',
    'text_lesson_add' => 'application/views/backend/admin/text_type_lesson_add.php',
    'text_lesson_edit' => 'application/views/backend/admin/text_type_lesson_edit.php',
);

$file_exists = array();
$contents = array();
foreach ($required_files as $key => $path) {
    $file_exists[$path] = is_file(u55_file($root, $path));
    $contents[$key] = u55_read($root, $path);
}
u55_section('Required file availability', $file_exists);

foreach ($file_exists as $path => $exists) {
    u55_assert($failures, $exists, 'Missing required file: ' . $path);
}

$section_form_checks = array();
foreach (array('section_add', 'section_edit') as $key) {
    $content = $contents[$key];
    $section_form_checks[$key] = array(
        'english_title' => u55_contains($content, 'name="english_title"'),
        'arabic_title' => u55_contains($content, 'name="arabic_title"'),
        'arabic_dir_rtl' => u55_contains($content, 'dir="rtl"'),
        'english_required' => u55_contains($content, 'name="english_title"') && u55_contains($content, 'required'),
    );

    foreach ($section_form_checks[$key] as $check => $ok) {
        u55_assert($failures, $ok, $key . ' missing expected section bilingual marker: ' . $check);
    }
}
u55_section('Section form source checks', $section_form_checks);

$lesson_form_checks = array();
foreach (array('lesson_add', 'lesson_edit') as $key) {
    $content = $contents[$key];
    $lesson_form_checks[$key] = array(
        'english_title' => u55_contains($content, 'name="english_title"'),
        'english_summary' => u55_contains($content, 'name="english_summary"'),
        'arabic_title' => u55_contains($content, 'name="arabic_title"'),
        'arabic_summary' => u55_contains($content, 'name="arabic_summary"'),
        'arabic_dir_rtl' => u55_contains($content, 'dir="rtl"'),
        'shared_course_id' => u55_contains($content, 'name="course_id"'),
        'shared_section_id' => u55_contains($content, 'name="section_id"'),
        'shared_lesson_type_includes' => u55_contains($content, "include('youtube_type_lesson_") && u55_contains($content, "include('document_type_lesson_"),
        'shared_free_preview_flag' => u55_contains($content, 'name="free_lesson"'),
    );

    foreach ($lesson_form_checks[$key] as $check => $ok) {
        u55_assert($failures, $ok, $key . ' missing expected lesson bilingual/shared marker: ' . $check);
    }
}
u55_section('Lesson form source checks', $lesson_form_checks);

$text_lesson_checks = array(
    'text_add_english_text_content' => u55_contains($contents['text_lesson_add'], 'name="english_text_content"'),
    'text_add_arabic_text_content' => u55_contains($contents['text_lesson_add'], 'name="arabic_text_content"'),
    'text_add_arabic_dir_rtl' => u55_contains($contents['text_lesson_add'], 'dir="rtl"'),
    'text_add_lesson_type_preserved' => u55_contains($contents['text_lesson_add'], 'name="lesson_type" value="text-description"'),
    'text_edit_english_text_content' => u55_contains($contents['text_lesson_edit'], 'name="english_text_content"'),
    'text_edit_arabic_text_content' => u55_contains($contents['text_lesson_edit'], 'name="arabic_text_content"'),
    'text_edit_arabic_dir_rtl' => u55_contains($contents['text_lesson_edit'], 'dir="rtl"'),
    'text_edit_lesson_type_preserved' => u55_contains($contents['text_lesson_edit'], 'name="lesson_type" value="text-description"'),
);
u55_section('Text lesson body source checks', $text_lesson_checks);
foreach ($text_lesson_checks as $check => $ok) {
    u55_assert($failures, $ok, 'Text lesson partial missing expected marker: ' . $check);
}

$crud = $contents['crud_model'];
$save_checks = array(
    'loads_translation_model' => u55_contains($crud, "load->model('youngo_translation_model')") || u55_contains($crud, 'load->model("youngo_translation_model")'),
    'reads_section_english_title' => u55_contains($crud, "post('english_title')") && u55_contains($crud, 'get_posted_section_translation_fields'),
    'reads_section_arabic_title' => u55_contains($crud, "post('arabic_title')") && u55_contains($crud, 'sync_section_translations'),
    'syncs_canonical_section_title_from_english' => u55_contains($crud, "\$data['title'] = html_escape(\$translation_fields['english_title'])") || u55_contains($crud, "\$data['title'] = \$translation_fields['english_title']"),
    'upserts_english_section_translation' => u55_contains($crud, "upsert_section_translation(\$section_id, 'english'"),
    'conditionally_upserts_arabic_section_translation' => u55_contains($crud, "upsert_section_translation(\$section_id, 'arabic'") && u55_contains($crud, "arabic_title'] !== ''"),
    'reads_lesson_english_fields' => u55_contains($crud, "'english_summary'") && u55_contains($crud, "'english_text_content'"),
    'reads_lesson_arabic_fields' => u55_contains($crud, "post('arabic_summary'") && u55_contains($crud, "post('arabic_text_content'"),
    'syncs_canonical_lesson_title_from_english' => u55_contains($crud, "\$data['title'] = html_escape(\$translation_fields['english']['title'])"),
    'syncs_canonical_lesson_summary_from_english' => u55_contains($crud, "\$data['summary'] = htmlspecialchars_(\$translation_fields['english']['summary'])"),
    'syncs_text_lesson_attachment_from_english' => u55_contains($crud, "\$data['attachment'] = htmlspecialchars_(\$translation_fields['english']['text_content'])"),
    'preserves_legacy_lesson_post_fields_for_media_handlers' => u55_contains($crud, 'sync_legacy_lesson_post_fields') && u55_contains($crud, '$_POST[\'title\']'),
    'upserts_english_lesson_translation' => u55_contains($crud, "upsert_lesson_translation(\$lesson_id, 'english'") || u55_contains($crud, "upsert_lesson_translation(\$inserted_id, 'english'"),
    'conditionally_upserts_arabic_lesson_translation' => u55_contains($crud, "upsert_lesson_translation(\$lesson_id, 'arabic'") && u55_contains($crud, 'youngo_lesson_translation_has_content'),
    'preserves_upload_media_flow' => u55_contains($crud, 'move_uploaded_file') && u55_contains($crud, '$_FILES') && u55_contains($crud, 'video_url') && u55_contains($crud, 'attachment_type'),
    'preserves_sort_course_association' => u55_contains($crud, "\$data['course_id']") && u55_contains($crud, "\$data['section_id']") && u55_contains($crud, 'serialize_section'),
);
u55_section('Save path source checks', $save_checks);
foreach ($save_checks as $check => $ok) {
    u55_assert($failures, $ok, 'Crud_model missing expected save-path marker: ' . $check);
}

$empty_arabic_checks = array(
    'section_source_avoids_empty_arabic_rows' => u55_contains($crud, "if (\$translation_fields['arabic_title'] !== '')"),
    'lesson_source_avoids_empty_arabic_rows' => u55_contains($crud, 'youngo_lesson_translation_has_content') && u55_contains($crud, "upsert_lesson_translation(\$lesson_id, 'arabic'"),
    'section_db_empty_arabic_rows' => u55_count($mysqli, 'youngo_section_translations', "language_code = 'arabic' AND TRIM(COALESCE(title, '')) = ''"),
    'lesson_db_empty_arabic_rows' => u55_count($mysqli, 'youngo_lesson_translations', "language_code = 'arabic' AND TRIM(COALESCE(title, '')) = '' AND TRIM(COALESCE(summary, '')) = '' AND TRIM(COALESCE(text_content, '')) = ''"),
);
u55_section('Empty Arabic row checks', $empty_arabic_checks);
u55_assert($failures, $empty_arabic_checks['section_source_avoids_empty_arabic_rows'], 'Section save path does not visibly guard empty Arabic rows.');
u55_assert($failures, $empty_arabic_checks['lesson_source_avoids_empty_arabic_rows'], 'Lesson save path does not visibly guard empty Arabic rows.');
u55_assert($failures, $empty_arabic_checks['section_db_empty_arabic_rows'] === 0, 'Empty Arabic section translation rows exist.');
u55_assert($failures, $empty_arabic_checks['lesson_db_empty_arabic_rows'] === 0, 'Empty Arabic lesson translation rows exist.');

$translation_table_state = array(
    'section' => u55_count($mysqli, 'section'),
    'lesson' => u55_count($mysqli, 'lesson'),
    'youngo_section_translations_exists' => u55_table_exists($mysqli, 'youngo_section_translations'),
    'youngo_lesson_translations_exists' => u55_table_exists($mysqli, 'youngo_lesson_translations'),
    'english_section_translation_rows' => u55_count($mysqli, 'youngo_section_translations', "language_code = 'english'"),
    'english_lesson_translation_rows' => u55_count($mysqli, 'youngo_lesson_translations', "language_code = 'english'"),
    'arabic_section_translation_rows' => u55_count($mysqli, 'youngo_section_translations', "language_code = 'arabic'"),
    'arabic_lesson_translation_rows' => u55_count($mysqli, 'youngo_lesson_translations', "language_code = 'arabic'"),
);
u55_section('Section/lesson translation table state', $translation_table_state);
u55_assert($failures, $translation_table_state['youngo_section_translations_exists'], 'youngo_section_translations table is missing.');
u55_assert($failures, $translation_table_state['youngo_lesson_translations_exists'], 'youngo_lesson_translations table is missing.');
u55_assert($failures, $translation_table_state['english_section_translation_rows'] !== null && $translation_table_state['section'] !== null && $translation_table_state['english_section_translation_rows'] >= $translation_table_state['section'], 'English section translation coverage is lower than section count.');
u55_assert($failures, $translation_table_state['english_lesson_translation_rows'] !== null && $translation_table_state['lesson'] !== null && $translation_table_state['english_lesson_translation_rows'] >= $translation_table_state['lesson'], 'English lesson translation coverage is lower than lesson count.');
u55_assert($failures, $translation_table_state['arabic_section_translation_rows'] === 0, 'Arabic section translation rows changed from the no-QA-write baseline.');
u55_assert($failures, $translation_table_state['arabic_lesson_translation_rows'] === 0, 'Arabic lesson translation rows changed from the no-QA-write baseline.');

$scope_files = array();
exec('git -C ' . escapeshellarg($root) . ' diff --name-only', $scope_files);
$forbidden_changed = array();
$arabic_route_alias_diagnostic_exists = is_file($root . '/scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php');
$frontend_content_translation_files = array(
    'application/controllers/Home.php',
    'application/helpers/common_helper.php',
    'application/helpers/youngo_frontend_content_helper.php',
    'application/views/frontend/youngo/course_listing/course_card.php',
    'application/views/frontend/youngo/course_listing/filter_panel.php',
    'application/views/frontend/youngo/course_listing/sorting_bar.php',
    'application/views/frontend/youngo/course_page.php',
    'application/views/frontend/youngo/courses_page.php',
    'application/views/frontend/youngo/my_wishlist.php',
    'application/views/frontend/youngo/wishlist_items.php',
    'scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php',
    'scripts/phase_2/youngo_phase_2u6_frontend_language_context_diagnostic.php',
    'scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php',
);
$frontend_shell_localization_files = array(
    'application/helpers/youngo_frontend_language_helper.php',
    'application/views/frontend/youngo/index.php',
    'application/views/frontend/youngo/header.php',
    'assets/frontend/youngo/css/youngo.css',
    'scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php',
);
foreach ($scope_files as $path) {
    if ($path === 'application/config/routes.php' && $arabic_route_alias_diagnostic_exists && u55_routes_diff_is_arabic_alias_only($root)) {
        continue;
    }

    if (in_array($path, $frontend_content_translation_files, true) || in_array($path, $frontend_shell_localization_files, true)) {
        continue;
    }

    if (preg_match('#^(application/controllers/Admin\.php|application/controllers/Home\.php|application/config/routes\.php|application/views/backend/admin/course_|application/views/backend/admin/category_|application/views/backend/admin/sub_category_|application/controllers/Payment\.php|application/controllers/Paymob\.php|application/views/frontend/)#', $path)) {
        $forbidden_changed[] = $path;
    }
}
$scope_checks = array(
    'changed_files' => $scope_files,
    'forbidden_course_category_role_frontend_route_payment_changes' => $forbidden_changed,
);
u55_section('Scope boundary checks', $scope_checks);
u55_assert($failures, empty($forbidden_changed), 'Out-of-scope source files changed: ' . implode(', ', $forbidden_changed));

$protected_counts = array(
    'users' => u55_count($mysqli, 'users'),
    'permissions' => u55_count($mysqli, 'permissions'),
    'youngo_user_roles' => u55_count($mysqli, 'youngo_user_roles'),
    'enrol' => u55_count($mysqli, 'enrol'),
    'payment' => u55_count($mysqli, 'payment'),
    'watch_histories' => u55_count($mysqli, 'watch_histories'),
    'watched_duration' => u55_count($mysqli, 'watched_duration'),
    'youngo_course_access' => u55_count($mysqli, 'youngo_course_access'),
    'youngo_user_subscriptions' => u55_count($mysqli, 'youngo_user_subscriptions'),
    'youngo_manual_grants' => u55_count($mysqli, 'youngo_manual_grants'),
    'youngo_checkout_orders' => u55_count($mysqli, 'youngo_checkout_orders'),
    'youngo_coupon_usages' => u55_count($mysqli, 'youngo_coupon_usages'),
    'youngo_coupon_subscription_plans' => u55_count($mysqli, 'youngo_coupon_subscription_plans'),
    'youngo_coupon_courses' => u55_count($mysqli, 'youngo_coupon_courses'),
);
u55_section('Protected table counts', $protected_counts);
foreach (array('payment', 'watch_histories', 'watched_duration', 'youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants', 'youngo_checkout_orders', 'youngo_coupon_usages', 'youngo_coupon_subscription_plans', 'youngo_coupon_courses') as $table) {
    u55_assert($failures, $protected_counts[$table] === 0, $table . ' should remain 0.');
}

$existing_diagnostics = array(
    'role_assignment' => u55_run_php_script($root, 'scripts/phase_2/youngo_phase_2v_role_assignment_diagnostic.php'),
    'course_bilingual_forms' => u55_run_php_script($root, 'scripts/phase_2/youngo_phase_2u5_course_bilingual_forms_diagnostic.php'),
    'category_bilingual_forms' => u55_run_php_script($root, 'scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php'),
);
u55_section('Existing diagnostic subprocess checks', $existing_diagnostics);
foreach ($existing_diagnostics as $label => $result) {
    u55_assert($failures, !empty($result['passes']), $label . ' diagnostic did not pass.');
}

u55_section('Warnings', $warnings);
u55_section('Read-only safety', array(
    'result' => 'Diagnostic used file reads, git diff inspection, SELECT/SHOW-only queries, and read-only diagnostic subprocesses. It submitted no forms, called no model write methods directly, and did not modify data.',
));

if (!empty($failures)) {
    u55_section('Result', array('status' => 'FAIL', 'failures' => $failures));
    exit(1);
}

u55_section('Result', array('status' => 'PASS', 'failures' => array()));
exit(0);
