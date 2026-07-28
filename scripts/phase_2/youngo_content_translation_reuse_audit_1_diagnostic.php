<?php
/**
 * CONTENT.TRANSLATION.REUSE.AUDIT.1 diagnostic.
 *
 * Read-only inventory for existing bilingual/dynamic content translation
 * infrastructure. This script does not write DB rows, alter schema, seed
 * phrases, import language packs, change routes, call Paymob, or create
 * checkout/order/access records.
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
defined('FCPATH') || define('FCPATH', $root . DIRECTORY_SEPARATOR);
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'config' . DIRECTORY_SEPARATOR . 'database.php';

$failures = array();
$warnings = array();

function yctra1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function yctra1_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function yctra1_path($root, $relative)
{
    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function yctra1_source($root, $relative)
{
    $path = yctra1_path($root, $relative);
    return is_file($path) ? file_get_contents($path) : '';
}

function yctra1_has($source, $needle)
{
    return strpos((string) $source, (string) $needle) !== false;
}

function yctra1_regex($source, $pattern)
{
    return preg_match($pattern, (string) $source) === 1;
}

function yctra1_connect($db, $active_group)
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

function yctra1_query($mysqli, $sql)
{
    if (preg_match('/^\s*(INSERT|UPDATE|DELETE|ALTER|DROP|CREATE|TRUNCATE|REPLACE|GRANT|REVOKE|LOAD|CALL|OPTIMIZE|ANALYZE|SET)\b/i', $sql)) {
        throw new RuntimeException('Blocked non-read SQL in diagnostic.');
    }

    $result = $mysqli->query($sql);
    if (!$result) {
        throw new RuntimeException('DB read failed without exposing credentials.');
    }

    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();

    return $rows;
}

function yctra1_scalar($mysqli, $sql)
{
    $rows = yctra1_query($mysqli, $sql);
    if (empty($rows)) {
        return null;
    }

    $row = $rows[0];
    return reset($row);
}

function yctra1_table_exists($mysqli, $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    return (int) yctra1_scalar(
        $mysqli,
        "SELECT COUNT(*) AS row_count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '" . $mysqli->real_escape_string($table) . "'"
    ) > 0;
}

function yctra1_columns($mysqli, $table)
{
    if (!yctra1_table_exists($mysqli, $table)) {
        return array();
    }

    $rows = yctra1_query(
        $mysqli,
        "SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '" . $mysqli->real_escape_string($table) . "' ORDER BY ordinal_position"
    );

    $columns = array();
    foreach ($rows as $row) {
        $columns[] = $row['column_name'];
    }

    return $columns;
}

function yctra1_count($mysqli, $table, $where = '')
{
    if (!yctra1_table_exists($mysqli, $table)) {
        return null;
    }

    $sql = "SELECT COUNT(*) AS row_count FROM `" . $table . "`";
    if ($where !== '') {
        $sql .= " WHERE " . $where;
    }

    return (int) yctra1_scalar($mysqli, $sql);
}

function yctra1_language_counts($mysqli, $table)
{
    if (!yctra1_table_exists($mysqli, $table)) {
        return array('table_exists' => false);
    }

    $rows = yctra1_query($mysqli, "SELECT language_code, COUNT(*) AS row_count FROM `" . $table . "` GROUP BY language_code ORDER BY language_code ASC");
    $counts = array();
    foreach ($rows as $row) {
        $counts[$row['language_code']] = (int) $row['row_count'];
    }

    return array(
        'table_exists' => true,
        'total' => array_sum($counts),
        'language_counts' => $counts,
        'arabic_translated_rows' => isset($counts['arabic_translated']) ? $counts['arabic_translated'] : 0,
        'unexpected_language_codes' => array_values(array_diff(array_keys($counts), array('english', 'arabic'))),
    );
}

function yctra1_form_field_status($source, $fields)
{
    $status = array();
    foreach ($fields as $field) {
        $status[$field] = yctra1_regex($source, '/\bname=["\']' . preg_quote($field, '/') . '["\']/');
    }

    return $status;
}

try {
    $files = array(
        'translation_model' => 'application/models/Youngo_translation_model.php',
        'subscription_model' => 'application/models/Youngo_subscription_model.php',
        'crud_model' => 'application/models/Crud_model.php',
        'content_helper' => 'application/helpers/youngo_frontend_content_helper.php',
        'language_helper' => 'application/helpers/youngo_frontend_language_helper.php',
        'common_helper' => 'application/helpers/common_helper.php',
        'home_controller' => 'application/controllers/Home.php',
        'blog_controller' => 'application/controllers/Blog.php',
        'admin_controller' => 'application/controllers/Admin.php',
        'subscription_admin_controller' => 'application/controllers/Youngo_subscription_plans.php',
        'course_add' => 'application/views/backend/admin/course_add.php',
        'course_edit' => 'application/views/backend/admin/course_edit.php',
        'category_add' => 'application/views/backend/admin/category_add.php',
        'category_edit' => 'application/views/backend/admin/category_edit.php',
        'sub_category_add' => 'application/views/backend/admin/sub_category_add.php',
        'sub_category_edit' => 'application/views/backend/admin/sub_category_edit.php',
        'section_add' => 'application/views/backend/admin/section_add.php',
        'section_edit' => 'application/views/backend/admin/section_edit.php',
        'lesson_add' => 'application/views/backend/admin/lesson_add.php',
        'lesson_edit' => 'application/views/backend/admin/lesson_edit.php',
        'text_lesson_add' => 'application/views/backend/admin/text_type_lesson_add.php',
        'text_lesson_edit' => 'application/views/backend/admin/text_type_lesson_edit.php',
        'subscription_form' => 'application/views/backend/admin/youngo_subscription_plan_form.php',
        'blog_add' => 'application/views/backend/admin/blog_add.php',
        'blog_edit' => 'application/views/backend/admin/blog_edit.php',
        'blog_category_add' => 'application/views/backend/admin/blog_category_add.php',
        'blog_category_edit' => 'application/views/backend/admin/blog_category_edit.php',
        'homepage_admin' => 'application/views/backend/admin/youngo_homepage.php',
        'home_view' => 'application/views/frontend/youngo/home.php',
        'courses_view' => 'application/views/frontend/youngo/courses_page.php',
        'course_page' => 'application/views/frontend/youngo/course_page.php',
        'course_card' => 'application/views/frontend/youngo/course_listing/course_card.php',
        'filter_panel' => 'application/views/frontend/youngo/course_listing/filter_panel.php',
        'blogs_view' => 'application/views/frontend/youngo/blogs.php',
        'blog_details_view' => 'application/views/frontend/youngo/blog_details.php',
        'subscriptions_view' => 'application/views/frontend/youngo/subscriptions.php',
        'contact_view' => 'application/views/frontend/youngo/contact_us.php',
    );

    $fileStatus = array();
    $source = array();
    foreach ($files as $label => $relative) {
        $exists = is_file(yctra1_path($root, $relative));
        $fileStatus[$label] = array('path' => $relative, 'exists' => $exists);
        $source[$label] = $exists ? yctra1_source($root, $relative) : '';
    }
    yctra1_print('Detected files', $fileStatus);

    foreach (array('translation_model', 'subscription_model', 'crud_model', 'content_helper', 'home_controller') as $required) {
        yctra1_assert($failures, !empty($fileStatus[$required]['exists']), 'Required audit file missing: ' . $files[$required]);
    }

    $modelChecks = array(
        'Youngo_translation_model' => array(
            'exists' => $fileStatus['translation_model']['exists'],
            'course_config' => yctra1_has($source['translation_model'], 'youngo_course_translations'),
            'category_config' => yctra1_has($source['translation_model'], 'youngo_category_translations'),
            'section_config' => yctra1_has($source['translation_model'], 'youngo_section_translations'),
            'lesson_config' => yctra1_has($source['translation_model'], 'youngo_lesson_translations'),
            'fallback_methods' => yctra1_has($source['translation_model'], 'get_course_translation_with_fallback') && yctra1_has($source['translation_model'], 'get_lesson_translation_with_fallback'),
            'arabic_translated_input_alias_only' => yctra1_has($source['translation_model'], "'arabic_translated'") && yctra1_has($source['translation_model'], "return 'arabic';"),
        ),
        'Youngo_subscription_model' => array(
            'exists' => $fileStatus['subscription_model']['exists'],
            'translation_table' => yctra1_has($source['subscription_model'], 'youngo_subscription_plan_translations'),
            'public_translation_method' => yctra1_has($source['subscription_model'], 'get_public_subscription_plans'),
            'admin_save_method' => yctra1_has($source['subscription_model'], 'save_plan_translations_from_input'),
            'rejects_arabic_translated_public_language' => yctra1_has($source['subscription_model'], "\$language === 'arabic_translated'"),
        ),
        'Blog_translation_methods_in_Crud_model' => array(
            'get_method' => yctra1_has($source['crud_model'], 'youngo_get_blog_translation'),
            'save_method' => yctra1_has($source['crud_model'], 'youngo_save_blog_translation'),
            'post_bridge' => yctra1_has($source['crud_model'], 'youngo_save_blog_translations_from_post'),
            'translation_table' => yctra1_has($source['crud_model'], 'youngo_blog_translations'),
        ),
        'Dedicated_missing_models_expected_reuse' => array(
            'Youngo_course_translation_model_exists' => is_file(yctra1_path($root, 'application/models/Youngo_course_translation_model.php')),
            'Youngo_category_translation_model_exists' => is_file(yctra1_path($root, 'application/models/Youngo_category_translation_model.php')),
        ),
    );
    yctra1_print('Detected translation models', $modelChecks);

    $adminFormChecks = array(
        'courses' => array(
            'course_add' => yctra1_form_field_status($source['course_add'], array('english_title', 'arabic_title', 'english_short_description', 'arabic_short_description')),
            'course_edit' => yctra1_form_field_status($source['course_edit'], array('english_title', 'arabic_title', 'english_description', 'arabic_description', 'language_made_in')),
        ),
        'categories' => array(
            'category_add' => yctra1_form_field_status($source['category_add'], array('english_name', 'arabic_name', 'english_slug', 'arabic_slug')),
            'category_edit' => yctra1_form_field_status($source['category_edit'], array('english_name', 'arabic_name', 'english_slug', 'arabic_slug')),
            'sub_category_add' => yctra1_form_field_status($source['sub_category_add'], array('english_name', 'arabic_name', 'english_slug', 'arabic_slug')),
            'sub_category_edit' => yctra1_form_field_status($source['sub_category_edit'], array('english_name', 'arabic_name', 'english_slug', 'arabic_slug')),
        ),
        'sections_lessons' => array(
            'section_add' => yctra1_form_field_status($source['section_add'], array('english_title', 'arabic_title')),
            'section_edit' => yctra1_form_field_status($source['section_edit'], array('english_title', 'arabic_title')),
            'lesson_add' => yctra1_form_field_status($source['lesson_add'], array('english_title', 'arabic_title', 'english_summary', 'arabic_summary')),
            'lesson_edit' => yctra1_form_field_status($source['lesson_edit'], array('english_title', 'arabic_title', 'english_summary', 'arabic_summary')),
            'text_lesson_add' => yctra1_form_field_status($source['text_lesson_add'], array('english_text_content', 'arabic_text_content')),
            'text_lesson_edit' => yctra1_form_field_status($source['text_lesson_edit'], array('english_text_content', 'arabic_text_content')),
        ),
        'subscriptions' => array(
            'form_has_english_array' => yctra1_has($source['subscription_form'], 'translations[english]'),
            'form_has_arabic_array' => yctra1_has($source['subscription_form'], 'translations[arabic]'),
            'arabic_rtl' => yctra1_has($source['subscription_form'], 'dir="rtl"'),
        ),
        'blogs' => array(
            'blog_add' => yctra1_form_field_status($source['blog_add'], array('english_excerpt', 'arabic_title', 'arabic_excerpt', 'arabic_description')),
            'blog_edit' => yctra1_form_field_status($source['blog_edit'], array('english_excerpt', 'arabic_title', 'arabic_excerpt', 'arabic_description')),
            'blog_category_add_bilingual' => yctra1_form_field_status($source['blog_category_add'], array('english_title', 'arabic_title')),
            'blog_category_edit_bilingual' => yctra1_form_field_status($source['blog_category_edit'], array('english_title', 'arabic_title')),
        ),
        'homepage_contact' => array(
            'homepage_admin_exists' => $fileStatus['homepage_admin']['exists'],
            'homepage_admin_language_specific_fields' => yctra1_regex($source['homepage_admin'], '/\b(english_|arabic_|translations\[english\]|translations\[arabic\])/'),
            'contact_admin_shared_json' => yctra1_has($source['contact_view'], "get_frontend_settings('contact_info')"),
        ),
    );
    yctra1_print('Detected admin bilingual forms', $adminFormChecks);

    $frontendUsageChecks = array(
        'home_dynamic_content' => array(
            'loads_homepage_content' => yctra1_has($source['home_view'], 'youngo_get_homepage_content'),
            'localizes_manual_homepage_text' => yctra1_has($source['home_view'], 'youngo_frontend_homepage_localize_content'),
            'featured_categories_translate_rows' => yctra1_has($source['common_helper'], 'youngo_frontend_translate_category_rows'),
            'featured_courses_translate_rows' => yctra1_has($source['common_helper'], 'youngo_frontend_translate_course_rows'),
        ),
        'courses_categories_sections_lessons' => array(
            'home_controller_loads_content_helper' => yctra1_has($source['home_controller'], "helper('youngo_frontend_content')"),
            'courses_controller_shapes_course_rows' => yctra1_has($source['home_controller'], 'youngo_frontend_translate_course_rows'),
            'course_page_shapes_course' => yctra1_has($source['course_page'], 'youngo_frontend_translate_course_row'),
            'course_page_shapes_sections' => yctra1_has($source['course_page'], 'youngo_frontend_translate_section_rows'),
            'course_page_shapes_lessons' => yctra1_has($source['course_page'], 'youngo_frontend_translate_lesson_rows'),
            'course_card_shapes_category' => yctra1_has($source['course_card'], 'youngo_frontend_translate_category_row'),
            'filter_panel_shapes_categories' => yctra1_has($source['filter_panel'], 'youngo_frontend_translate_category_rows'),
        ),
        'subscriptions' => array(
            'home_controller_passes_language_to_model' => yctra1_has($source['home_controller'], 'get_public_subscription_plans($youngo_frontend_language)'),
            'public_view_has_no_checkout' => !yctra1_regex($source['subscriptions_view'], '/checkout|paymob|payment/i'),
        ),
        'blogs' => array(
            'listing_reads_translation_table' => yctra1_has($source['blogs_view'], 'youngo_blog_translations'),
            'listing_skips_untranslated_arabic_rows' => yctra1_has($source['blogs_view'], "continue;"),
            'detail_reads_translation_table' => yctra1_has($source['blog_details_view'], 'youngo_blog_translations'),
            'controller_shapes_blog_details' => yctra1_has($source['blog_controller'], 'youngo_blog_translations') || yctra1_has($source['blog_controller'], 'youngo_get_blog_translation'),
            'blog_categories_view_local_arabic_map_only' => yctra1_has($source['blogs_view'], 'Parent Guides') && yctra1_has($source['blogs_view'], 'arabic_titles'),
        ),
        'contact' => array(
            'shared_contact_info_json' => yctra1_has($source['contact_view'], "get_frontend_settings('contact_info')"),
            'arabic_local_fallback_copy' => yctra1_has($source['contact_view'], "\$youngo_contact_language === 'arabic'"),
            'display_only_no_public_form' => !yctra1_regex($source['contact_view'], '/<form\b/i'),
        ),
    );
    yctra1_print('Detected frontend usage of translations', $frontendUsageChecks);

    $mysqli = yctra1_connect($db, $active_group);
    $translationTables = array(
        'courses' => 'youngo_course_translations',
        'categories_subcategories' => 'youngo_category_translations',
        'sections' => 'youngo_section_translations',
        'lessons' => 'youngo_lesson_translations',
        'subscription_plans' => 'youngo_subscription_plan_translations',
        'blogs' => 'youngo_blog_translations',
        'blog_categories' => 'youngo_blog_category_translations',
        'homepage_content' => 'youngo_homepage_translations',
        'contact_content' => 'youngo_contact_translations',
    );

    $tableStatus = array();
    foreach ($translationTables as $area => $table) {
        $tableStatus[$area] = array(
            'table' => $table,
            'exists' => yctra1_table_exists($mysqli, $table),
            'columns' => yctra1_columns($mysqli, $table),
        );
    }
    yctra1_print('Detected translation tables and columns', $tableStatus);

    $coverage = array(
        'courses' => array_merge(
            array('canonical_rows' => yctra1_count($mysqli, 'course')),
            yctra1_language_counts($mysqli, 'youngo_course_translations')
        ),
        'categories_subcategories' => array_merge(
            array(
                'canonical_rows' => yctra1_count($mysqli, 'category'),
                'top_level_rows' => yctra1_count($mysqli, 'category', '`parent` = 0'),
                'subcategory_rows' => yctra1_count($mysqli, 'category', '`parent` > 0'),
            ),
            yctra1_language_counts($mysqli, 'youngo_category_translations')
        ),
        'sections' => array_merge(
            array('canonical_rows' => yctra1_count($mysqli, 'section')),
            yctra1_language_counts($mysqli, 'youngo_section_translations')
        ),
        'lessons' => array_merge(
            array('canonical_rows' => yctra1_count($mysqli, 'lesson')),
            yctra1_language_counts($mysqli, 'youngo_lesson_translations')
        ),
        'subscription_plans' => array_merge(
            array(
                'canonical_rows' => yctra1_count($mysqli, 'youngo_subscription_plans'),
                'public_rows' => yctra1_count($mysqli, 'youngo_subscription_plans', "`is_active` = 1 AND `is_purchasable` = 1 AND `archived_at` IS NULL"),
            ),
            yctra1_language_counts($mysqli, 'youngo_subscription_plan_translations')
        ),
        'blogs' => array_merge(
            array('canonical_rows' => yctra1_count($mysqli, 'blogs')),
            yctra1_language_counts($mysqli, 'youngo_blog_translations')
        ),
        'blog_categories' => array_merge(
            array('canonical_rows' => yctra1_count($mysqli, 'blog_category')),
            yctra1_language_counts($mysqli, 'youngo_blog_category_translations')
        ),
        'homepage_contact_settings' => array(
            'frontend_settings_rows' => yctra1_count($mysqli, 'frontend_settings'),
            'youngo_homepage_content_rows' => yctra1_count($mysqli, 'frontend_settings', "`key` = 'youngo_homepage_content'"),
            'contact_info_rows' => yctra1_count($mysqli, 'frontend_settings', "`key` = 'contact_info'"),
            'homepage_translation_table_exists' => yctra1_table_exists($mysqli, 'youngo_homepage_translations'),
            'contact_translation_table_exists' => yctra1_table_exists($mysqli, 'youngo_contact_translations'),
        ),
    );
    yctra1_print('Coverage counts', $coverage);

    foreach ($coverage as $area => $details) {
        if (isset($details['arabic_translated_rows']) && (int) $details['arabic_translated_rows'] > 0) {
            $warnings[] = $area . ' has arabic_translated rows in a translation table.';
        }
    }

    $courseColumns = yctra1_columns($mysqli, 'course');
    $contentMarkerChecks = array(
        'admin_form_field_language_made_in_detected' => yctra1_has($source['course_add'], 'language_made_in') && yctra1_has($source['course_edit'], 'language_made_in'),
        'course_language_column_used_for_marker' => in_array('language', $courseColumns, true),
        'course_language_marker_values' => array(),
        'translation_tables_have_no_arabic_translated_rows' => true,
    );
    if ($contentMarkerChecks['course_language_column_used_for_marker']) {
        $rows = yctra1_query($mysqli, "SELECT `language`, COUNT(*) AS row_count FROM `course` GROUP BY `language` ORDER BY `language` ASC");
        foreach ($rows as $row) {
            $contentMarkerChecks['course_language_marker_values'][(string) $row['language']] = (int) $row['row_count'];
        }
    }
    foreach ($coverage as $details) {
        if (isset($details['arabic_translated_rows']) && (int) $details['arabic_translated_rows'] !== 0) {
            $contentMarkerChecks['translation_tables_have_no_arabic_translated_rows'] = false;
            break;
        }
    }
    yctra1_print('arabic_translated and course language marker boundary', $contentMarkerChecks);
    yctra1_assert($failures, $contentMarkerChecks['translation_tables_have_no_arabic_translated_rows'], 'Translation tables must not store arabic_translated as a language_code.');

    $protectedCounts = array();
    foreach (array('payment', 'youngo_checkout_orders', 'youngo_coupon_usages', 'youngo_coupon_subscription_plans', 'youngo_coupon_courses', 'youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants') as $table) {
        $protectedCounts[$table] = yctra1_count($mysqli, $table);
    }
    yctra1_print('Protected payment/access table counts', $protectedCounts);

    $changedFiles = array();
    exec('git -C ' . escapeshellarg($root) . ' diff --name-only', $changedFiles);
    $changedFiles = array_values(array_filter($changedFiles));
    $forbiddenChangedFiles = array();
    foreach ($changedFiles as $changedFile) {
        if (preg_match('#(?:paymob|payment|checkout|coupon|cart)#i', $changedFile)) {
            $forbiddenChangedFiles[] = $changedFile;
        }
    }

    $paymentBoundary = array(
        'changed_files' => $changedFiles,
        'forbidden_payment_or_paymob_changed_files' => $forbiddenChangedFiles,
        'no_forbidden_payment_or_paymob_changed_files' => empty($forbiddenChangedFiles),
        'no_payment_translation_tables_detected' => !yctra1_table_exists($mysqli, 'youngo_payment_translations') && !yctra1_table_exists($mysqli, 'youngo_paymob_translations') && !yctra1_table_exists($mysqli, 'youngo_checkout_translations'),
    );
    yctra1_print('Payment/Paymob source boundary', $paymentBoundary);
    yctra1_assert($failures, $paymentBoundary['no_forbidden_payment_or_paymob_changed_files'], 'Payment/Paymob/checkout/coupon/cart files changed unexpectedly.');
    yctra1_assert($failures, $paymentBoundary['no_payment_translation_tables_detected'], 'Payment/Paymob/checkout translation table detected unexpectedly.');

    $notes = array(
        'read_only_scope' => 'The diagnostic uses source reads plus SELECT/information_schema reads only.',
        'expected_missing_or_partial_items' => array(
            'blog detail translation wiring is not detected',
            'blog category translation table/admin fields are not detected',
            'homepage/contact dedicated translation tables are not detected',
        ),
    );
    yctra1_print('Audit notes', $notes);

    $mysqli->close();
} catch (Throwable $exception) {
    $failures[] = $exception->getMessage();
}

if (!empty($warnings)) {
    yctra1_print('Warnings', $warnings);
}

if (!empty($failures)) {
    yctra1_print('Failures', $failures);
    yctra1_print('Result', 'FAIL: content translation reuse audit diagnostic found blocking issues.');
    exit(1);
}

yctra1_print('Result', 'PASS: content translation reuse audit diagnostic completed read-only.');
exit(0);
