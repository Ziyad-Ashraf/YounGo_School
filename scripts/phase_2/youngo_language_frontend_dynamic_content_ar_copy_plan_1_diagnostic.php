<?php
/**
 * LANGUAGE.FRONTEND.DYNAMIC.CONTENT.AR_COPY.PLAN.1 diagnostic.
 *
 * Read-only planning diagnostic for Arabic localization of dynamic frontend
 * content records. This script does not write DB rows, import language packs,
 * seed phrases, change routes, call payment providers, or expose credentials.
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
defined('APPPATH') || define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

$failures = array();

function yfdcap_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function yfdcap_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function yfdcap_source($root, $relative)
{
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    return is_file($path) ? file_get_contents($path) : '';
}

function yfdcap_connect($db, $active_group)
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

function yfdcap_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $exists = isset($row['total']) && (int) $row['total'] > 0;
    $stmt->close();

    return $exists;
}

function yfdcap_table_fields($mysqli, $table)
{
    if (!yfdcap_table_exists($mysqli, $table)) {
        return array();
    }

    $fields = array();
    $result = $mysqli->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
    while ($result && ($row = $result->fetch_assoc())) {
        $fields[] = $row['Field'];
    }

    return $fields;
}

function yfdcap_count_rows($mysqli, $table, $where = '')
{
    if (!yfdcap_table_exists($mysqli, $table)) {
        return null;
    }

    $sql = 'SELECT COUNT(*) AS total FROM `' . str_replace('`', '``', $table) . '`';
    if ($where !== '') {
        $sql .= ' WHERE ' . $where;
    }

    $result = $mysqli->query($sql);
    if (!$result) {
        return null;
    }

    $row = $result->fetch_assoc();
    return isset($row['total']) ? (int) $row['total'] : null;
}

function yfdcap_language_counts($mysqli, $table)
{
    if (!yfdcap_table_exists($mysqli, $table) || !in_array('language_code', yfdcap_table_fields($mysqli, $table), true)) {
        return array();
    }

    $counts = array();
    $result = $mysqli->query('SELECT language_code, COUNT(*) AS total FROM `' . str_replace('`', '``', $table) . '` GROUP BY language_code ORDER BY language_code ASC');
    while ($result && ($row = $result->fetch_assoc())) {
        $counts[$row['language_code']] = (int) $row['total'];
    }

    return $counts;
}

function yfdcap_has_any_field(array $fields, array $candidates)
{
    return count(array_intersect($fields, $candidates)) > 0;
}

try {
    $files = array(
        'application/models/Youngo_subscription_model.php',
        'application/models/Youngo_translation_model.php',
        'application/models/Youngo_course_translation_model.php',
        'application/models/Youngo_category_translation_model.php',
        'application/models/Crud_model.php',
        'application/controllers/Home.php',
        'application/controllers/Blog.php',
        'application/helpers/youngo_frontend_content_helper.php',
        'application/helpers/youngo_frontend_language_helper.php',
        'application/views/frontend/youngo/subscriptions.php',
        'application/views/frontend/youngo/courses_page.php',
        'application/views/frontend/youngo/course_page.php',
        'application/views/frontend/youngo/blogs.php',
        'application/views/frontend/youngo/blog_details.php',
        'application/views/frontend/youngo/contact_us.php',
        'application/views/backend/admin/youngo_subscription_plan_form.php',
        'application/views/backend/admin/course_add.php',
        'application/views/backend/admin/course_edit.php',
        'application/views/backend/admin/category_add.php',
        'application/views/backend/admin/category_edit.php',
        'application/views/backend/admin/lesson_add.php',
        'application/views/backend/admin/lesson_edit.php',
    );

    $file_status = array();
    foreach ($files as $file) {
        $file_status[$file] = is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file));
    }

    $mysqli = yfdcap_connect($db, $active_group);

    $subscription_fields = yfdcap_table_fields($mysqli, 'youngo_subscription_plans');
    $subscription_bilingual_fields = array_values(array_intersect($subscription_fields, array(
        'english_name',
        'arabic_name',
        'name_en',
        'name_ar',
        'english_short_description',
        'arabic_short_description',
        'short_description_en',
        'short_description_ar',
        'english_description',
        'arabic_description',
        'description_en',
        'description_ar',
    )));

    $translation_tables = array(
        'course' => 'youngo_course_translations',
        'category' => 'youngo_category_translations',
        'section' => 'youngo_section_translations',
        'lesson' => 'youngo_lesson_translations',
        'blog' => 'youngo_blog_translations',
        'blog_category' => 'youngo_blog_category_translations',
    );

    $translation_table_status = array();
    foreach ($translation_tables as $label => $table) {
        $fields = yfdcap_table_fields($mysqli, $table);
        $translation_table_status[$label] = array(
            'table' => $table,
            'exists' => !empty($fields),
            'fields' => $fields,
            'language_counts' => yfdcap_language_counts($mysqli, $table),
            'arabic_translated_count' => in_array('language_code', $fields, true) ? yfdcap_count_rows($mysqli, $table, "language_code = 'arabic_translated'") : null,
        );
    }

    $source = array(
        'subscription_model' => yfdcap_source($root, 'application/models/Youngo_subscription_model.php'),
        'translation_model' => yfdcap_source($root, 'application/models/Youngo_translation_model.php'),
        'content_helper' => yfdcap_source($root, 'application/helpers/youngo_frontend_content_helper.php'),
        'language_helper' => yfdcap_source($root, 'application/helpers/youngo_frontend_language_helper.php'),
        'home_controller' => yfdcap_source($root, 'application/controllers/Home.php'),
        'blog_controller' => yfdcap_source($root, 'application/controllers/Blog.php'),
        'blogs_view' => yfdcap_source($root, 'application/views/frontend/youngo/blogs.php'),
        'blog_details_view' => yfdcap_source($root, 'application/views/frontend/youngo/blog_details.php'),
        'subscriptions_view' => yfdcap_source($root, 'application/views/frontend/youngo/subscriptions.php'),
        'contact_view' => yfdcap_source($root, 'application/views/frontend/youngo/contact_us.php'),
        'crud_model' => yfdcap_source($root, 'application/models/Crud_model.php'),
    );

    $source_checks = array(
        'central_translation_model_exists' => $file_status['application/models/Youngo_translation_model.php'],
        'course_wrapper_model_missing' => !$file_status['application/models/Youngo_course_translation_model.php'],
        'category_wrapper_model_missing' => !$file_status['application/models/Youngo_category_translation_model.php'],
        'translation_model_supports_course_category_section_lesson' =>
            strpos($source['translation_model'], "'course' => array(") !== false
            && strpos($source['translation_model'], "'category' => array(") !== false
            && strpos($source['translation_model'], "'section' => array(") !== false
            && strpos($source['translation_model'], "'lesson' => array(") !== false,
        'translation_model_canonical_codes_only' =>
            strpos($source['translation_model'], "return array('english', 'arabic');") !== false,
        'frontend_course_translation_wired' =>
            strpos($source['home_controller'], 'youngo_frontend_translate_course_rows') !== false
            && strpos($source['content_helper'], 'function youngo_frontend_translate_course_row') !== false,
        'frontend_category_translation_wired' =>
            strpos($source['content_helper'], 'function youngo_frontend_translate_category_row') !== false,
        'frontend_section_lesson_translation_wired' =>
            strpos($source['content_helper'], 'function youngo_frontend_translate_section_row') !== false
            && strpos($source['content_helper'], 'function youngo_frontend_translate_lesson_row') !== false
            && strpos($source['subscriptions_view'], 'payment/paymob') === false,
        'subscription_public_model_uses_language_param' =>
            strpos($source['subscription_model'], 'get_public_subscription_plans($language = null)') !== false
            && strpos($source['subscription_model'], 'localized_public_value') !== false,
        'subscription_view_uses_dynamic_plan_rows' =>
            strpos($source['subscriptions_view'], 'foreach ($youngo_subscription_plans as $plan)') !== false
            && strpos($source['subscriptions_view'], '$plan_name') !== false,
        'blog_list_has_translation_probe' =>
            strpos($source['blogs_view'], 'youngo_blog_translations') !== false
            && strpos($source['blogs_view'], 'youngo_blog_localized_rows') !== false,
        'blog_detail_not_translation_wired' =>
            strpos($source['blog_details_view'], 'youngo_blog_translation_row') === false
            && strpos($source['blog_details_view'], "blog_details['title']") !== false,
        'contact_uses_frontend_settings' =>
            strpos($source['contact_view'], "get_frontend_settings('contact_info')") !== false,
        'homepage_has_local_text_map_not_schema' =>
            strpos($source['language_helper'], 'youngo_frontend_homepage_localize_content') !== false
            && strpos($source['language_helper'], 'youngo_frontend_local_text_map') !== false,
        'course_admin_bilingual_forms_present' =>
            strpos($source['crud_model'], 'english_title') !== false
            && strpos($source['crud_model'], 'arabic_title') !== false
            && strpos($source['crud_model'], 'upsert_course_translation') !== false,
        'section_lesson_admin_bilingual_forms_present' =>
            strpos($source['crud_model'], 'upsert_section_translation') !== false
            && strpos($source['crud_model'], 'upsert_lesson_translation') !== false,
    );

    $db_status = array(
        'youngo_subscription_plans' => array(
            'exists' => yfdcap_table_exists($mysqli, 'youngo_subscription_plans'),
            'fields' => $subscription_fields,
            'bilingual_fields_detected' => $subscription_bilingual_fields,
            'active_purchasable_egp_public_count' => yfdcap_count_rows($mysqli, 'youngo_subscription_plans', "is_active = 1 AND is_purchasable = 1 AND currency = 'EGP' AND price > 0 AND duration_days > 0" . (in_array('archived_at', $subscription_fields, true) ? ' AND archived_at IS NULL' : '')),
        ),
        'course_count' => yfdcap_count_rows($mysqli, 'course'),
        'category_count' => yfdcap_count_rows($mysqli, 'category'),
        'section_count' => yfdcap_count_rows($mysqli, 'section'),
        'lesson_count' => yfdcap_count_rows($mysqli, 'lesson'),
        'blog_count' => yfdcap_count_rows($mysqli, 'blogs'),
        'blog_category_count' => yfdcap_count_rows($mysqli, 'blog_category'),
        'frontend_settings_has_contact_info' => yfdcap_count_rows($mysqli, 'frontend_settings', "`key` = 'contact_info'"),
        'frontend_settings_has_homepage_content' => yfdcap_count_rows($mysqli, 'frontend_settings', "`key` = 'youngo_homepage_content'"),
    );

    $dynamic_sources = array(
        'subscription_plans' => 'youngo_subscription_plans via Youngo_subscription_model::get_public_subscription_plans(); no active DB bilingual columns detected unless bilingual_fields_detected is non-empty.',
        'courses' => 'course plus youngo_course_translations through Youngo_translation_model and frontend content helper.',
        'categories_subcategories' => 'category plus youngo_category_translations through Youngo_translation_model and frontend content helper.',
        'sections_lessons' => 'section/lesson plus youngo_section_translations and youngo_lesson_translations for course-detail curriculum display; player routes remain deferred.',
        'blogs' => 'blogs/blog_category legacy tables; blogs.php probes youngo_blog_translations, but central model/admin support is not established in this phase.',
        'contact' => 'frontend_settings.contact_info and settings fallback; currently not modeled as bilingual content.',
        'homepage' => 'frontend_settings.youngo_homepage_content JSON plus course/category/blog auto sources; currently localized partly through static text map and existing course/category shaping.',
        'people_names' => 'users first_name/last_name/title/biography for instructors/authors; personal names should normally remain unchanged.',
    );

    $payment_terms = array('paymob', 'payment/paymob', 'checkout/order', 'home/checkout', 'home/shopping_cart');
    $changed_output = array();
    exec('git -C ' . escapeshellarg($root) . ' diff --name-only', $changed_output);
    $payment_changed = array_values(array_filter($changed_output, function ($path) {
        return preg_match('/paymob|payment|checkout|shopping_cart|cart_items|invoice|purchase_history/i', $path);
    }));

    yfdcap_print('Files inspected/status', $file_status);
    yfdcap_print('Dynamic source map', $dynamic_sources);
    yfdcap_print('Database dynamic content status', $db_status);
    yfdcap_print('Translation table status', $translation_table_status);
    yfdcap_print('Source readiness checks', $source_checks);
    yfdcap_print('Payment source diff check', array(
        'changed_files' => $changed_output,
        'payment_checkout_related_changed_files' => $payment_changed,
        'terms_checked' => $payment_terms,
    ));

    foreach (array(
        'application/models/Youngo_subscription_model.php',
        'application/models/Youngo_translation_model.php',
        'application/models/Crud_model.php',
        'application/controllers/Home.php',
        'application/controllers/Blog.php',
        'application/helpers/youngo_frontend_content_helper.php',
        'application/helpers/youngo_frontend_language_helper.php',
        'application/views/frontend/youngo/subscriptions.php',
        'application/views/frontend/youngo/courses_page.php',
        'application/views/frontend/youngo/course_page.php',
        'application/views/frontend/youngo/blogs.php',
        'application/views/frontend/youngo/blog_details.php',
        'application/views/frontend/youngo/contact_us.php',
    ) as $required_file) {
        yfdcap_assert($failures, !empty($file_status[$required_file]), $required_file . ' is missing.');
    }

    foreach (array(
        'central_translation_model_exists',
        'translation_model_supports_course_category_section_lesson',
        'translation_model_canonical_codes_only',
        'frontend_course_translation_wired',
        'frontend_category_translation_wired',
        'frontend_section_lesson_translation_wired',
        'subscription_public_model_uses_language_param',
        'subscription_view_uses_dynamic_plan_rows',
        'blog_list_has_translation_probe',
        'blog_detail_not_translation_wired',
        'contact_uses_frontend_settings',
        'homepage_has_local_text_map_not_schema',
        'course_admin_bilingual_forms_present',
        'section_lesson_admin_bilingual_forms_present',
    ) as $check_key) {
        yfdcap_assert($failures, !empty($source_checks[$check_key]), 'Source check failed: ' . $check_key);
    }

    yfdcap_assert($failures, !empty($db_status['youngo_subscription_plans']['exists']), 'Subscription plans table is missing.');
    yfdcap_assert($failures, yfdcap_table_exists($mysqli, 'youngo_course_translations'), 'Course translation table is missing.');
    yfdcap_assert($failures, yfdcap_table_exists($mysqli, 'youngo_category_translations'), 'Category translation table is missing.');
    yfdcap_assert($failures, yfdcap_table_exists($mysqli, 'youngo_section_translations'), 'Section translation table is missing.');
    yfdcap_assert($failures, yfdcap_table_exists($mysqli, 'youngo_lesson_translations'), 'Lesson translation table is missing.');
    yfdcap_assert($failures, empty($translation_table_status['course']['arabic_translated_count']), 'arabic_translated rows found in course translations.');
    yfdcap_assert($failures, empty($translation_table_status['category']['arabic_translated_count']), 'arabic_translated rows found in category translations.');
    yfdcap_assert($failures, empty($translation_table_status['section']['arabic_translated_count']), 'arabic_translated rows found in section translations.');
    yfdcap_assert($failures, empty($translation_table_status['lesson']['arabic_translated_count']), 'arabic_translated rows found in lesson translations.');
    yfdcap_assert($failures, empty($payment_changed), 'Payment/checkout-related files are changed in this planning phase.');

    if (!empty($failures)) {
        yfdcap_print('FAILURES', $failures);
        exit(1);
    }

    yfdcap_print('Result', 'PASS: dynamic Arabic content sources are identified; existing translation readiness is mapped; no DB writes or payment changes were performed.');
    exit(0);
} catch (Throwable $e) {
    yfdcap_print('Result', 'FAIL: ' . $e->getMessage());
    exit(1);
}
