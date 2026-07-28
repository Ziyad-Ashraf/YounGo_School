<?php
/**
 * Phase 2U.5.4 course bilingual forms diagnostic.
 *
 * Read-only checks for course add/edit bilingual form wiring.
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

function u54_section($title, $data)
{
    echo "\n== " . $title . " ==\n";
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
}

function u54_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function u54_file($root, $path)
{
    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
}

function u54_read($root, $path)
{
    $file = u54_file($root, $path);
    if (!is_file($file)) {
        return null;
    }

    return file_get_contents($file);
}

function u54_contains($content, $needle)
{
    return is_string($content) && strpos($content, $needle) !== false;
}

function u54_query($mysqli, $sql)
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

function u54_table_exists($mysqli, $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    $rows = u54_query($mysqli, "SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function u54_count($mysqli, $table, $where = '')
{
    if (!u54_table_exists($mysqli, $table)) {
        return null;
    }

    $sql = "SELECT COUNT(*) AS c FROM `" . $table . "`";
    if ($where !== '') {
        $sql .= " WHERE " . $where;
    }

    $rows = u54_query($mysqli, $sql);
    return isset($rows[0]['c']) ? (int) $rows[0]['c'] : null;
}

$required_files = array(
    'translation_model' => 'application/models/Youngo_translation_model.php',
    'crud_model' => 'application/models/Crud_model.php',
    'course_add' => 'application/views/backend/admin/course_add.php',
    'course_edit' => 'application/views/backend/admin/course_edit.php',
    'course_add_shortcut' => 'application/views/backend/admin/course_add_shortcut.php',
    'common_helper' => 'application/helpers/common_helper.php',
);

$file_exists = array();
$contents = array();
foreach ($required_files as $key => $path) {
    $file_exists[$path] = is_file(u54_file($root, $path));
    $contents[$key] = u54_read($root, $path);
}
u54_section('Required file availability', $file_exists);

foreach ($file_exists as $path => $exists) {
    u54_assert($failures, $exists, 'Missing required file: ' . $path);
}

$required_course_fields = array(
    'english_title',
    'english_slug',
    'english_short_description',
    'english_description',
    'english_faqs',
    'english_faq_descriptions',
    'english_requirements',
    'english_outcomes',
    'english_seo_title',
    'english_meta_keywords',
    'english_meta_description',
    'arabic_title',
    'arabic_slug',
    'arabic_short_description',
    'arabic_description',
    'arabic_faqs',
    'arabic_faq_descriptions',
    'arabic_requirements',
    'arabic_outcomes',
    'arabic_seo_title',
    'arabic_meta_keywords',
    'arabic_meta_description',
);

$form_checks = array();
foreach (array('course_add', 'course_edit') as $key) {
    $content = $contents[$key];
    $form_checks[$key] = array();
    foreach ($required_course_fields as $field) {
        $form_checks[$key][$field] = u54_contains($content, 'name="' . $field . '"') || u54_contains($content, 'name="' . $field . '[]"');
    }

    $form_checks[$key]['arabic_dir_rtl'] = u54_contains($content, 'dir="rtl"');
    $form_checks[$key]['shared_course_type'] = $key === 'course_edit' || u54_contains($content, 'name="course_type"') || u54_contains($content, 'name = "course_type"');
    $form_checks[$key]['shared_sub_category'] = u54_contains($content, 'name="sub_category_id"');
    $form_checks[$key]['shared_level'] = u54_contains($content, 'name="level"');
    $form_checks[$key]['shared_language_marker'] = u54_contains($content, 'name="language_made_in"');
    $form_checks[$key]['shared_price'] = u54_contains($content, 'name="price"') || u54_contains($content, 'name = "price"');
    $form_checks[$key]['shared_media_overview'] = u54_contains($content, 'name="course_overview_url"');
    $form_checks[$key]['youngo_access_settings'] = u54_contains($content, 'youngo_access_settings_submitted') && u54_contains($content, 'youngo_access_mode');
    $form_checks[$key]['language_marker_english'] = u54_contains($content, 'value="english"');
    $form_checks[$key]['language_marker_arabic'] = u54_contains($content, 'value="arabic"');
    $form_checks[$key]['language_marker_arabic_translated'] = u54_contains($content, 'value="arabic_translated"');
    $form_checks[$key]['subscription_only_price_note'] = u54_contains($content, 'One-time purchase price is not used');
    $form_checks[$key]['access_mode_pricing_toggle'] = u54_contains($content, 'youngo-subscription-only-price-note') && u54_contains($content, '#price, #discount_flag, #discounted_price');

    foreach ($form_checks[$key] as $check => $ok) {
        u54_assert($failures, $ok, $key . ' missing expected course bilingual/shared form marker: ' . $check);
    }
}
u54_section('Course add/edit form source checks', $form_checks);

$shortcut_checks = array(
    'shortcut_exists' => $contents['course_add_shortcut'] !== null,
    'shortcut_uses_english_title' => u54_contains($contents['course_add_shortcut'], 'name="english_title"'),
    'shortcut_does_not_require_arabic' => !u54_contains($contents['course_add_shortcut'], 'name="arabic_title"'),
    'shortcut_documents_followup_edit' => u54_contains($contents['course_add_shortcut'], 'full course edit form'),
    'shortcut_language_marker_english' => u54_contains($contents['course_add_shortcut'], 'value="english"'),
    'shortcut_language_marker_arabic' => u54_contains($contents['course_add_shortcut'], 'value="arabic"'),
    'shortcut_language_marker_arabic_translated' => u54_contains($contents['course_add_shortcut'], 'value="arabic_translated"'),
);
u54_section('Course shortcut source checks', $shortcut_checks);
foreach ($shortcut_checks as $check => $ok) {
    u54_assert($failures, $ok, 'Course shortcut missing expected marker: ' . $check);
}

$save_checks = array(
    'loads_translation_model' => u54_contains($contents['crud_model'], "load->model('youngo_translation_model')") || u54_contains($contents['crud_model'], 'load->model("youngo_translation_model")'),
    'reads_english_title' => u54_contains($contents['crud_model'], "post('english_title')"),
    'reads_arabic_title' => u54_contains($contents['crud_model'], "post('arabic_title')"),
    'syncs_course_translations' => u54_contains($contents['crud_model'], 'sync_course_translations'),
    'upserts_english_course_translation' => u54_contains($contents['crud_model'], 'upsert_course_translation($course_id, \'english\''),
    'conditionally_upserts_arabic_course_translation' => u54_contains($contents['crud_model'], 'upsert_course_translation($course_id, \'arabic\'') && u54_contains($contents['crud_model'], 'youngo_course_translation_has_content'),
    'syncs_canonical_title_from_english' => u54_contains($contents['crud_model'], "\$data['title'] = html_escape(\$course_translation_fields['english']['title'])") || u54_contains($contents['crud_model'], "\$data['title'] = \$course_translation_fields['english']['title']"),
    'syncs_canonical_description_from_english' => u54_contains($contents['crud_model'], "\$data['description'] = \$course_translation_fields['english']['description']"),
    'preserves_youngo_access_settings' => u54_contains($contents['crud_model'], 'normalize_youngo_course_access_settings_from_post') && u54_contains($contents['crud_model'], 'youngo_access_settings_submitted'),
);
u54_section('Course save path source checks', $save_checks);
foreach ($save_checks as $check => $ok) {
    u54_assert($failures, $ok, 'Crud_model missing expected course save-path marker: ' . $check);
}

$out_of_scope_files = array(
    'application/views/backend/admin/section_add.php',
    'application/views/backend/admin/section_edit.php',
    'application/views/backend/admin/lesson_add.php',
    'application/views/backend/admin/lesson_edit.php',
);
$out_of_scope_checks = array();
foreach ($out_of_scope_files as $path) {
    $content = u54_read($root, $path);
    $out_of_scope_checks[$path] = array(
        'exists' => $content !== null,
        'no_course_bilingual_field_markers' => !u54_contains($content, 'name="english_slug"')
            && !u54_contains($content, 'name="arabic_slug"')
            && !u54_contains($content, 'name="english_short_description"')
            && !u54_contains($content, 'name="arabic_short_description"')
            && !u54_contains($content, 'name="english_description"')
            && !u54_contains($content, 'name="arabic_description"')
            && !u54_contains($content, 'name="english_faqs"')
            && !u54_contains($content, 'name="arabic_faqs"')
            && !u54_contains($content, 'name="english_requirements"')
            && !u54_contains($content, 'name="arabic_requirements"')
            && !u54_contains($content, 'name="english_outcomes"')
            && !u54_contains($content, 'name="arabic_outcomes"')
            && !u54_contains($content, 'name="english_seo_title"')
            && !u54_contains($content, 'name="arabic_seo_title"')
            && !u54_contains($content, 'name="youngo_access_mode"'),
    );
}
u54_section('Out-of-scope section/lesson source checks', $out_of_scope_checks);
foreach ($out_of_scope_checks as $path => $checks) {
    u54_assert($failures, $checks['no_course_bilingual_field_markers'], $path . ' should not contain Phase 2U.5.4 course-specific bilingual/access field markers.');
}

$course_count = u54_count($mysqli, 'course');
$english_course_rows = u54_count($mysqli, 'youngo_course_translations', "language_code = 'english'");
$arabic_course_rows = u54_count($mysqli, 'youngo_course_translations', "language_code = 'arabic'");
$table_state = array(
    'course' => $course_count,
    'youngo_course_translations_exists' => u54_table_exists($mysqli, 'youngo_course_translations'),
    'english_course_translation_rows' => $english_course_rows,
    'arabic_course_translation_rows' => $arabic_course_rows,
);
u54_section('Course translation table state', $table_state);
u54_assert($failures, $table_state['youngo_course_translations_exists'], 'youngo_course_translations table is missing.');
u54_assert($failures, $english_course_rows !== null && $course_count !== null && $english_course_rows >= $course_count, 'English course translation coverage is lower than course count.');

$course_translation_language_rows = u54_query($mysqli, "SELECT DISTINCT language_code FROM `youngo_course_translations` ORDER BY language_code");
$course_translation_languages = array();
if (is_array($course_translation_language_rows) && !isset($course_translation_language_rows['error'])) {
    foreach ($course_translation_language_rows as $row) {
        $course_translation_languages[] = isset($row['language_code']) ? $row['language_code'] : '';
    }
}
$translation_language_state = array(
    'youngo_course_translations_language_codes' => $course_translation_languages,
    'allowed_translation_language_codes' => array('english', 'arabic'),
    'arabic_translated_allowed_as_course_marker_only' => true,
);
u54_section('Translation language code boundaries', $translation_language_state);
foreach ($course_translation_languages as $language_code) {
    u54_assert($failures, in_array($language_code, array('english', 'arabic'), true), 'Unsupported course translation language code found: ' . $language_code);
}
u54_assert($failures, !in_array('arabic_translated', $course_translation_languages, true), 'arabic_translated must not be used as a translation table language code.');

$other_translation_rows = array(
    'youngo_category_translations.arabic' => u54_count($mysqli, 'youngo_category_translations', "language_code = 'arabic'"),
    'youngo_section_translations.arabic' => u54_count($mysqli, 'youngo_section_translations', "language_code = 'arabic'"),
    'youngo_lesson_translations.arabic' => u54_count($mysqli, 'youngo_lesson_translations', "language_code = 'arabic'"),
);
u54_section('Out-of-scope translation table Arabic rows', $other_translation_rows);
foreach ($other_translation_rows as $label => $count) {
    u54_assert($failures, $count === 0, $label . ' should remain 0 in Phase 2U.5.4.');
}

$currency_checks = array(
    'common_helper_exists' => $contents['common_helper'] !== null,
    'egp_symbol_normalized' => u54_contains($contents['common_helper'], "\$symbol = 'EGP'"),
    'currency_position_respected' => u54_contains($contents['common_helper'], "in_array(\$position, array('right', 'right-space', 'left', 'left-space'), true)"),
    'egp_missing_position_fallback_right_space' => u54_contains($contents['common_helper'], "'right-space'"),
);
u54_section('Currency helper source checks', $currency_checks);
foreach ($currency_checks as $check => $ok) {
    u54_assert($failures, $ok, 'Currency helper missing expected display rule marker: ' . $check);
}

$checkout_payment_files = array(
    'application/controllers/Payment.php',
    'application/controllers/Paymob.php',
    'application/views/frontend/youngo/shopping_cart.php',
);
$checkout_payment_checks = array();
foreach ($checkout_payment_files as $path) {
    $content = u54_read($root, $path);
    $checkout_payment_checks[$path] = array(
        'exists_or_not_applicable' => $content !== null || !is_file(u54_file($root, $path)),
        'no_course_bilingual_form_markers' => $content === null || (!u54_contains($content, 'english_title') && !u54_contains($content, 'arabic_title') && !u54_contains($content, 'youngo-subscription-only-price-note')),
    );
}
u54_section('Checkout/payment source boundary checks', $checkout_payment_checks);
foreach ($checkout_payment_checks as $path => $checks) {
    u54_assert($failures, $checks['no_course_bilingual_form_markers'], $path . ' should not contain Phase 2U.5.4 course form markers.');
}

$protected_counts = array(
    'category' => u54_count($mysqli, 'category'),
    'section' => u54_count($mysqli, 'section'),
    'lesson' => u54_count($mysqli, 'lesson'),
    'enrol' => u54_count($mysqli, 'enrol'),
    'payment' => u54_count($mysqli, 'payment'),
    'watch_histories' => u54_count($mysqli, 'watch_histories'),
    'watched_duration' => u54_count($mysqli, 'watched_duration'),
    'youngo_course_access' => u54_count($mysqli, 'youngo_course_access'),
    'youngo_user_subscriptions' => u54_count($mysqli, 'youngo_user_subscriptions'),
    'youngo_manual_grants' => u54_count($mysqli, 'youngo_manual_grants'),
    'youngo_checkout_orders' => u54_count($mysqli, 'youngo_checkout_orders'),
    'youngo_coupon_usages' => u54_count($mysqli, 'youngo_coupon_usages'),
);
u54_section('Protected table counts', $protected_counts);
foreach (array('payment', 'watch_histories', 'watched_duration', 'youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants', 'youngo_checkout_orders', 'youngo_coupon_usages') as $table) {
    u54_assert($failures, $protected_counts[$table] === 0, $table . ' should remain 0.');
}

u54_section('Warnings', $warnings);
u54_section('Read-only safety', array(
    'result' => 'Diagnostic used file reads and SELECT/SHOW-only queries, submitted no forms, called no model write methods, and did not modify data.',
));

if (!empty($failures)) {
    u54_section('Result', array('status' => 'FAIL', 'failures' => $failures));
    exit(1);
}

u54_section('Result', array('status' => 'PASS', 'failures' => array()));
exit(0);
