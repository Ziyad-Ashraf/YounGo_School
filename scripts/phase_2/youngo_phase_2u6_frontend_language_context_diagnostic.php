<?php
/**
 * Phase 2U.6 frontend language context diagnostic.
 *
 * Read-only checks for the frontend language helper foundation.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$root = dirname(__DIR__, 2);
define('ENVIRONMENT', 'development');
define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

$failures = array();
$warnings = array();

function phase_2u6_print($title, $payload)
{
    echo "\n== {$title} ==\n";
    echo is_string($payload) ? $payload . "\n" : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function phase_2u6_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function phase_2u6_file_contains($path, $needle)
{
    return is_file($path) && strpos(file_get_contents($path), $needle) !== false;
}

function phase_2u6_routes_diff_is_arabic_alias_only($root)
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

function phase_2u6_query($mysqli, $sql)
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

function phase_2u6_table_exists($mysqli, $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    $rows = phase_2u6_query($mysqli, "SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function phase_2u6_language_codes($mysqli, $table)
{
    if (!phase_2u6_table_exists($mysqli, $table)) {
        return array();
    }

    $rows = phase_2u6_query($mysqli, "SELECT DISTINCT language_code FROM `" . $table . "` ORDER BY language_code ASC");
    $codes = array();
    foreach ($rows as $row) {
        if (isset($row['language_code'])) {
            $codes[] = $row['language_code'];
        }
    }

    return $codes;
}

function phase_2u6_run_php_script($root, $script)
{
    $command = 'php ' . escapeshellarg($script);
    $output = array();
    $exit_code = 1;
    $cwd = getcwd();
    chdir($root);
    exec($command, $output, $exit_code);
    chdir($cwd);

    return array(
        'script' => $script,
        'exit_code' => $exit_code,
        'passes' => $exit_code === 0,
    );
}

$helper_file = $root . '/application/helpers/youngo_frontend_language_helper.php';
$routes_file = $root . '/application/config/routes.php';
$autoload_file = $root . '/application/config/autoload.php';

$required_files = array(
    'application/helpers/youngo_frontend_language_helper.php' => is_file($helper_file),
    'application/config/routes.php' => is_file($routes_file),
    'application/config/autoload.php' => is_file($autoload_file),
);
phase_2u6_print('Required file availability', $required_files);
foreach ($required_files as $label => $exists) {
    phase_2u6_assert($failures, $exists, $label . ' is missing.');
}

if (is_file($helper_file)) {
    require_once $helper_file;
}

$expected_functions = array(
    'youngo_frontend_supported_languages',
    'youngo_frontend_normalize_language_code',
    'youngo_frontend_language_from_uri',
    'youngo_frontend_active_language',
    'youngo_frontend_is_arabic_uri',
    'youngo_frontend_strip_language_prefix',
    'youngo_frontend_add_language_prefix',
    'youngo_frontend_is_localizable_uri',
    'youngo_frontend_language_url',
    'youngo_frontend_html_lang',
    'youngo_frontend_html_dir',
);

$function_status = array();
foreach ($expected_functions as $function) {
    $function_status[$function] = function_exists($function);
    phase_2u6_assert($failures, $function_status[$function], 'Expected helper function missing: ' . $function);
}
phase_2u6_print('Expected helper function availability', $function_status);

$normalization_status = array();
if (function_exists('youngo_frontend_normalize_language_code')) {
    $normalization_inputs = array(
        'en' => 'english',
        'eng' => 'english',
        'english' => 'english',
        'ar' => 'arabic',
        'ara' => 'arabic',
        'arabic' => 'arabic',
        'arabic_translated' => 'arabic',
        '' => 'english',
        'unknown' => 'english',
    );

    foreach ($normalization_inputs as $input => $expected) {
        $actual = youngo_frontend_normalize_language_code($input);
        $normalization_status[$input === '' ? '(empty)' : $input] = array(
            'expected' => $expected,
            'actual' => $actual,
            'ok' => $actual === $expected,
        );
        phase_2u6_assert($failures, $actual === $expected, 'Language normalization failed for: ' . ($input === '' ? '(empty)' : $input));
    }
}
phase_2u6_print('Language normalization checks', $normalization_status);

$uri_detection_status = array();
if (function_exists('youngo_frontend_language_from_uri')) {
    $uri_inputs = array(
        '' => 'english',
        '/' => 'english',
        'courses' => 'english',
        'home/courses' => 'english',
        'course/my-course/1' => 'english',
        'ar' => 'arabic',
        'ar/' => 'arabic',
        'ar/courses' => 'arabic',
        'ar/course/my-course/1' => 'arabic',
        'ar/admin/users' => 'english',
        'ar/home/course_payment' => 'english',
    );

    foreach ($uri_inputs as $uri => $expected) {
        $actual = youngo_frontend_language_from_uri($uri);
        $uri_detection_status[$uri === '' ? '(empty)' : $uri] = array(
            'expected' => $expected,
            'actual' => $actual,
            'is_arabic_uri' => youngo_frontend_is_arabic_uri($uri),
            'ok' => $actual === $expected,
        );
        phase_2u6_assert($failures, $actual === $expected, 'URI language detection failed for: ' . ($uri === '' ? '(empty)' : $uri));
    }
}
phase_2u6_print('URI language detection checks', $uri_detection_status);

$strip_add_status = array();
if (function_exists('youngo_frontend_strip_language_prefix') && function_exists('youngo_frontend_add_language_prefix')) {
    $strip_examples = array(
        'ar/courses' => 'courses',
        'ar/course/my-course/1' => 'course/my-course/1',
        'courses' => 'courses',
    );
    foreach ($strip_examples as $input => $expected) {
        $actual = youngo_frontend_strip_language_prefix($input);
        $strip_add_status['strip:' . $input] = array('expected' => $expected, 'actual' => $actual, 'ok' => $actual === $expected);
        phase_2u6_assert($failures, $actual === $expected, 'Language prefix strip failed for: ' . $input);
    }

    $add_examples = array(
        array('uri' => 'courses', 'language' => 'arabic', 'expected' => 'ar/courses'),
        array('uri' => '', 'language' => 'arabic', 'expected' => 'ar'),
        array('uri' => 'ar/courses', 'language' => 'english', 'expected' => 'courses'),
        array('uri' => 'courses', 'language' => 'english', 'expected' => 'courses'),
    );
    foreach ($add_examples as $example) {
        $actual = youngo_frontend_add_language_prefix($example['uri'], $example['language']);
        $key = 'add:' . $example['language'] . ':' . ($example['uri'] === '' ? '(empty)' : $example['uri']);
        $strip_add_status[$key] = array('expected' => $example['expected'], 'actual' => $actual, 'ok' => $actual === $example['expected']);
        phase_2u6_assert($failures, $actual === $example['expected'], 'Language prefix add failed for: ' . $key);
    }
}
phase_2u6_print('Prefix strip/add checks', $strip_add_status);

$language_url_status = array();
if (function_exists('youngo_frontend_language_url')) {
    $url_examples = array(
        array('uri' => 'courses?category=robotics&page=2', 'language' => 'arabic', 'preserve_query' => true, 'expected' => 'ar/courses?category=robotics&page=2'),
        array('uri' => 'ar/courses?category=robotics&page=2', 'language' => 'english', 'preserve_query' => true, 'expected' => 'courses?category=robotics&page=2'),
        array('uri' => 'courses?category=robotics&page=2', 'language' => 'arabic', 'preserve_query' => false, 'expected' => 'ar/courses'),
        array('uri' => 'home/course_payment?course_id=1', 'language' => 'arabic', 'preserve_query' => true, 'expected' => 'home/course_payment?course_id=1'),
        array('uri' => 'admin/dashboard?x=1', 'language' => 'arabic', 'preserve_query' => true, 'expected' => 'admin/dashboard?x=1'),
    );
    foreach ($url_examples as $example) {
        $actual = youngo_frontend_language_url($example['language'], $example['uri'], $example['preserve_query']);
        $key = $example['language'] . ':' . $example['uri'] . ':' . ($example['preserve_query'] ? 'query' : 'no-query');
        $language_url_status[$key] = array('expected' => $example['expected'], 'actual' => $actual, 'ok' => $actual === $example['expected']);
        phase_2u6_assert($failures, $actual === $example['expected'], 'Language URL generation failed for: ' . $key);
        phase_2u6_assert($failures, strpos($actual, 'en/') !== 0 && $actual !== 'en', 'Language URL generated canonical /en for: ' . $key);
    }
}
phase_2u6_print('Language URL generation checks', $language_url_status);

$localizable_status = array();
if (function_exists('youngo_frontend_is_localizable_uri') && function_exists('youngo_frontend_non_localizable_uri_prefixes')) {
    $required_exclusions = array(
        'admin',
        'addons',
        'login/admin',
        'home/payment',
        'home/paypal',
        'home/stripe',
        'home/paymob',
        'home/razorpay',
        'home/paystack',
        'home/flutterwave',
        'home/course_payment',
        'home/shopping_cart',
        'home/update_cart',
        'home/apply_coupon',
        'home/remove_coupon',
        'home/checkout',
        'home/confirm_payment',
        'home/webhook',
        'api',
        'cron',
    );
    $configured_exclusions = youngo_frontend_non_localizable_uri_prefixes();
    foreach ($required_exclusions as $prefix) {
        $exists = in_array($prefix, $configured_exclusions, true);
        $localizable_status['exclusion:' . $prefix] = $exists;
        phase_2u6_assert($failures, $exists, 'Required non-localizable prefix missing: ' . $prefix);
    }

    $boundary_examples = array(
        'courses' => true,
        'ar/courses' => true,
        'admin/dashboard' => false,
        'ar/admin/dashboard' => false,
        'home/course_payment' => false,
        'ar/home/course_payment' => false,
        'api/course/list' => false,
        'cron/run' => false,
    );
    foreach ($boundary_examples as $uri => $expected) {
        $actual = youngo_frontend_is_localizable_uri($uri);
        $localizable_status['uri:' . $uri] = array('expected' => $expected, 'actual' => $actual, 'ok' => $actual === $expected);
        phase_2u6_assert($failures, $actual === $expected, 'Localizable URI boundary failed for: ' . $uri);
    }
}
phase_2u6_print('Localizable URI boundary checks', $localizable_status);

$html_status = array();
if (function_exists('youngo_frontend_html_lang') && function_exists('youngo_frontend_html_dir')) {
    $html_status = array(
        'english_lang' => youngo_frontend_html_lang('english'),
        'english_dir' => youngo_frontend_html_dir('english'),
        'arabic_lang' => youngo_frontend_html_lang('arabic'),
        'arabic_dir' => youngo_frontend_html_dir('arabic'),
        'arabic_translated_lang' => youngo_frontend_html_lang('arabic_translated'),
        'arabic_translated_dir' => youngo_frontend_html_dir('arabic_translated'),
    );

    phase_2u6_assert($failures, $html_status['english_lang'] === 'en', 'English HTML lang is not en.');
    phase_2u6_assert($failures, $html_status['english_dir'] === 'ltr', 'English HTML dir is not ltr.');
    phase_2u6_assert($failures, $html_status['arabic_lang'] === 'ar', 'Arabic HTML lang is not ar.');
    phase_2u6_assert($failures, $html_status['arabic_dir'] === 'rtl', 'Arabic HTML dir is not rtl.');
    phase_2u6_assert($failures, $html_status['arabic_translated_lang'] === 'ar', 'arabic_translated input did not normalize to Arabic HTML lang.');
    phase_2u6_assert($failures, $html_status['arabic_translated_dir'] === 'rtl', 'arabic_translated input did not normalize to Arabic HTML dir.');
}
phase_2u6_print('HTML lang/dir helper checks', $html_status);

$source_boundary_checks = array(
    'routes_may_have_ar_route_aliases_after_phase_2u6_3' => is_file($routes_file),
    'routes_has_no_en_route_alias' => is_file($routes_file) && !preg_match("/\\\$route\\[['\\\"]en(?:\\/|['\\\"])/", file_get_contents($routes_file)),
    'helper_not_autoloaded' => is_file($autoload_file) && !phase_2u6_file_contains($autoload_file, 'youngo_frontend_language'),
    'helper_does_not_call_get_phrase' => is_file($helper_file) && !phase_2u6_file_contains($helper_file, 'get_phrase('),
    'helper_does_not_use_arabic_translated_as_output_language' => function_exists('youngo_frontend_supported_languages') && !array_key_exists('arabic_translated', youngo_frontend_supported_languages()),
);
phase_2u6_print('Source boundary checks', $source_boundary_checks);
phase_2u6_assert($failures, !empty($source_boundary_checks['routes_has_no_en_route_alias']), '/en route aliases were added during this phase.');
phase_2u6_assert($failures, !empty($source_boundary_checks['helper_not_autoloaded']), 'Frontend language helper was autoloaded unexpectedly.');
phase_2u6_assert($failures, !empty($source_boundary_checks['helper_does_not_call_get_phrase']), 'Helper calls get_phrase(), which can write missing phrase rows.');
phase_2u6_assert($failures, !empty($source_boundary_checks['helper_does_not_use_arabic_translated_as_output_language']), 'Helper exposes arabic_translated as a supported output language.');

$translation_language_status = array();
$config_file = $root . '/application/config/database.php';
if (is_file($config_file)) {
    require $config_file;
    $config = isset($db['default']) ? $db['default'] : array();
    $mysqli = @new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
    if ($mysqli->connect_errno) {
        $warnings[] = 'Database connection failed without exposing credentials; translation language-code DB checks skipped.';
    } else {
        $mysqli->set_charset('utf8');
        $translation_tables = array(
            'youngo_course_translations',
            'youngo_category_translations',
            'youngo_section_translations',
            'youngo_lesson_translations',
        );
        foreach ($translation_tables as $table) {
            $codes = phase_2u6_language_codes($mysqli, $table);
            $unexpected = array_values(array_diff($codes, array('english', 'arabic')));
            $translation_language_status[$table] = array(
                'codes' => $codes,
                'unexpected_codes' => $unexpected,
                'ok' => empty($unexpected),
            );
            phase_2u6_assert($failures, empty($unexpected), $table . ' has unexpected translation language codes.');
        }
    }
} else {
    $warnings[] = 'database.php not found; translation language-code DB checks skipped.';
}
phase_2u6_print('Translation table language-code checks', $translation_language_status);

$changed_files = array();
$git_output = array();
$git_exit = 1;
exec('git -C ' . escapeshellarg($root) . ' diff --name-only', $git_output, $git_exit);
if ($git_exit === 0) {
    $changed_files = $git_output;
}

$forbidden_changed_files = array();
$allowed_content_translation_files = array(
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
    'scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php',
);
$allowed_frontend_shell_localization_files = array(
    'application/helpers/youngo_frontend_language_helper.php',
    'application/views/frontend/youngo/index.php',
    'application/views/frontend/youngo/header.php',
    'assets/frontend/youngo/css/youngo.css',
    'scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php',
);
foreach ($changed_files as $changed_file) {
    if ($changed_file === 'application/config/routes.php' && phase_2u6_routes_diff_is_arabic_alias_only($root)) {
        continue;
    }

    if (in_array($changed_file, $allowed_content_translation_files, true) || in_array($changed_file, $allowed_frontend_shell_localization_files, true)) {
        continue;
    }

    if (preg_match('#^(application/controllers/(Admin|Home|Payment|Paymob)\.php|application/views/frontend/|application/models/|application/config/autoload\.php)#', $changed_file)) {
        $forbidden_changed_files[] = $changed_file;
    }
}
$git_boundary = array(
    'changed_files' => $changed_files,
    'forbidden_route_admin_frontend_model_payment_autoload_changes' => $forbidden_changed_files,
);
phase_2u6_print('Git scope boundary checks', $git_boundary);
phase_2u6_assert($failures, empty($forbidden_changed_files), 'Unexpected route/admin/frontend/model/payment/autoload source changes detected.');

$existing_diagnostics = array(
    'section_lesson_bilingual_forms' => 'scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php',
    'role_assignment' => 'scripts/phase_2/youngo_phase_2v_role_assignment_diagnostic.php',
    'course_bilingual_forms' => 'scripts/phase_2/youngo_phase_2u5_course_bilingual_forms_diagnostic.php',
    'category_bilingual_forms' => 'scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php',
    'translation_model' => 'scripts/phase_2/youngo_phase_2u5_translation_model_diagnostic.php',
    'arabic_phrase' => 'scripts/phase_2/youngo_phase_2u4_arabic_phrase_diagnostic.php',
    'localization_schema' => 'scripts/phase_2/youngo_phase_2u3_localization_schema_diagnostic.php',
);
$existing_diagnostic_status = array();
foreach ($existing_diagnostics as $label => $script) {
    $existing_diagnostic_status[$label] = phase_2u6_run_php_script($root, $script);
    phase_2u6_assert($failures, $existing_diagnostic_status[$label]['passes'], 'Existing diagnostic failed: ' . $script);
}
phase_2u6_print('Existing diagnostic subprocess checks', $existing_diagnostic_status);

phase_2u6_print('Warnings', $warnings);
phase_2u6_print('Read-only safety', array(
    'result' => 'Diagnostic used file reads, helper pure-function calls, git diff inspection, SELECT/SHOW-only queries, and read-only diagnostic subprocesses. It did not call get_phrase(), submit forms, or modify data.',
));
phase_2u6_print('Result', array(
    'status' => empty($failures) ? 'PASS' : 'FAIL',
    'failures' => $failures,
));

exit(empty($failures) ? 0 : 1);
