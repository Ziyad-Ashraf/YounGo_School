<?php
/**
 * LOCALIZATION.AR_DEFAULT.ROUTE.SKELETON.1 diagnostic.
 *
 * Read-only checks for Arabic default public language detection, English /en
 * route aliases, /ar compatibility aliases, and protected route boundaries.
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

function youngo_ar_default_diag_print($title, $payload)
{
    echo "\n== {$title} ==\n";
    echo is_string($payload) ? $payload . "\n" : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

function youngo_ar_default_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function youngo_ar_default_diag_route_map($source)
{
    $routes = array();
    if (preg_match_all('/\\$route\\[[\'"]([^\'"]+)[\'"]\\]\\s*=\\s*[\'"]([^\'"]+)[\'"]\\s*;/', $source, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $routes[$match[1]] = $match[2];
        }
    }

    return $routes;
}

function youngo_ar_default_diag_run_php_script($root, $script)
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

function youngo_ar_default_diag_git_diff_added_lines($root)
{
    $output = array();
    $exit_code = 1;
    exec('git -C ' . escapeshellarg($root) . ' diff --', $output, $exit_code);

    if ($exit_code !== 0) {
        return array();
    }

    $added = array();
    foreach ($output as $line) {
        if ($line !== '' && $line[0] === '+' && strpos($line, '+++') !== 0) {
            $added[] = substr($line, 1);
        }
    }

    return $added;
}

$routes_file = $root . '/application/config/routes.php';
$home_file = $root . '/application/controllers/Home.php';
$language_helper_file = $root . '/application/helpers/youngo_frontend_language_helper.php';
$content_helper_file = $root . '/application/helpers/youngo_frontend_content_helper.php';
$checkout_cta_helper_file = $root . '/application/helpers/youngo_checkout_cta_helper.php';

$required_files = array(
    'application/config/routes.php' => is_file($routes_file),
    'application/controllers/Home.php' => is_file($home_file),
    'application/helpers/youngo_frontend_language_helper.php' => is_file($language_helper_file),
    'application/helpers/youngo_frontend_content_helper.php' => is_file($content_helper_file),
    'application/helpers/youngo_checkout_cta_helper.php' => is_file($checkout_cta_helper_file),
);
youngo_ar_default_diag_print('Required files', $required_files);
foreach ($required_files as $label => $exists) {
    youngo_ar_default_diag_assert($failures, $exists, $label . ' is missing.');
}

$routes_source = is_file($routes_file) ? file_get_contents($routes_file) : '';
$routes = youngo_ar_default_diag_route_map($routes_source);

$expected_routes = array(
    'home/blog' => 'blog/index',
    'home/contact' => 'home/contact_us',
    'en' => 'home/index',
    'en/home' => 'home/index',
    'en/home/courses' => 'home/courses',
    'en/home/courses/(:num)' => 'home/courses',
    'en/home/course/(:any)/(:num)' => 'home/course/$1/$2',
    'en/home/search' => 'home/search',
    'en/home/search/(:any)' => 'home/search/$1',
    'en/home/blog' => 'blog/index',
    'en/home/contact' => 'home/contact_us',
    'en/login' => 'login/index',
    'en/sign-up' => 'sign_up/index',
    'ar' => 'home/index',
    'ar/home' => 'home/index',
    'ar/home/courses' => 'home/courses',
    'ar/home/courses/(:num)' => 'home/courses',
    'ar/home/course/(:any)/(:num)' => 'home/course/$1/$2',
    'ar/home/search' => 'home/search',
    'ar/home/search/(:any)' => 'home/search/$1',
    'ar/home/blog' => 'blog/index',
    'ar/home/contact' => 'home/contact_us',
    'ar/courses' => 'home/courses',
    'ar/course/(:any)/(:num)' => 'home/course/$1/$2',
);

$route_status = array();
foreach ($expected_routes as $pattern => $target) {
    $actual = isset($routes[$pattern]) ? $routes[$pattern] : null;
    $route_status[$pattern] = array('expected' => $target, 'actual' => $actual, 'ok' => $actual === $target);
    youngo_ar_default_diag_assert($failures, $actual === $target, 'Route missing or mismapped: ' . $pattern);
}
youngo_ar_default_diag_print('Route skeleton checks', $route_status);

$forbidden_route_patterns = array(
    '#^(?:en|ar)/admin(?:/|$)#',
    '#^(?:en|ar)/(?:payment|paymob|paypal|stripe|razorpay|paystack|flutterwave)(?:/|$)#',
    '#^(?:en|ar)/home/(?:payment|paypal|stripe|paymob|razorpay|paystack|flutterwave|course_payment|shopping_cart|update_cart|apply_coupon|remove_coupon|checkout|confirm_payment|webhook|handle_cart_items|refreshwishlist|toggleWishlistItems|rate_course)(?:/|$)#i',
    '#^(?:en|ar)/(?:api|cron|addons)(?:/|$)#',
);
$forbidden_routes = array();
foreach ($routes as $pattern => $target) {
    foreach ($forbidden_route_patterns as $regex) {
        if (preg_match($regex, $pattern)) {
            $forbidden_routes[] = array('pattern' => $pattern, 'target' => $target);
        }
    }
}
youngo_ar_default_diag_print('Forbidden localized route checks', $forbidden_routes);
youngo_ar_default_diag_assert($failures, empty($forbidden_routes), 'Admin/payment/API/write route aliases were localized.');

if (is_file($language_helper_file)) {
    require_once $language_helper_file;
}
if (is_file($content_helper_file)) {
    require_once $content_helper_file;
}

$language_detection = array();
if (function_exists('youngo_frontend_language_from_uri')) {
    $language_examples = array(
        '' => 'arabic',
        '/' => 'arabic',
        'home' => 'arabic',
        'home/courses' => 'arabic',
        'home/course/demo/1' => 'arabic',
        'home/blog' => 'arabic',
        'home/contact' => 'arabic',
        'ar' => 'arabic',
        'ar/home/courses' => 'arabic',
        'ar/home/course/demo/1' => 'arabic',
        'ar/courses' => 'arabic',
        'ar/course/demo/1' => 'arabic',
        'en' => 'english',
        'en/home' => 'english',
        'en/home/courses' => 'english',
        'en/home/course/demo/1' => 'english',
        'en/home/blog' => 'english',
        'en/home/contact' => 'english',
    );

    foreach ($language_examples as $uri => $expected) {
        $actual = youngo_frontend_language_from_uri($uri);
        $label = $uri === '' ? '(empty)' : $uri;
        $language_detection[$label] = array('expected' => $expected, 'actual' => $actual, 'ok' => $actual === $expected);
        youngo_ar_default_diag_assert($failures, $actual === $expected, 'Language detection failed for: ' . $label);
    }
} else {
    youngo_ar_default_diag_assert($failures, false, 'youngo_frontend_language_from_uri() is unavailable.');
}
youngo_ar_default_diag_print('Language detection checks', $language_detection);

$prefix_checks = array();
if (function_exists('youngo_frontend_strip_language_prefix') && function_exists('youngo_frontend_add_language_prefix')) {
    $prefix_examples = array(
        array('fn' => 'strip', 'input' => 'en/home/courses', 'expected' => 'home/courses'),
        array('fn' => 'strip', 'input' => 'ar/home/courses', 'expected' => 'home/courses'),
        array('fn' => 'add', 'input' => 'home/courses', 'language' => 'english', 'expected' => 'en/home/courses'),
        array('fn' => 'add', 'input' => 'home/courses', 'language' => 'arabic', 'expected' => 'home/courses'),
        array('fn' => 'add', 'input' => '', 'language' => 'english', 'expected' => 'en'),
        array('fn' => 'add', 'input' => '', 'language' => 'arabic', 'expected' => ''),
    );

    foreach ($prefix_examples as $example) {
        if ($example['fn'] === 'strip') {
            $actual = youngo_frontend_strip_language_prefix($example['input']);
            $key = 'strip:' . $example['input'];
        } else {
            $actual = youngo_frontend_add_language_prefix($example['input'], $example['language']);
            $key = 'add:' . $example['language'] . ':' . ($example['input'] === '' ? '(empty)' : $example['input']);
        }

        $prefix_checks[$key] = array('expected' => $example['expected'], 'actual' => $actual, 'ok' => $actual === $example['expected']);
        youngo_ar_default_diag_assert($failures, $actual === $example['expected'], 'Prefix helper failed for: ' . $key);
    }
}
youngo_ar_default_diag_print('Prefix helper checks', $prefix_checks);

$route_builder_checks = array();
if (function_exists('youngo_frontend_courses_path') && function_exists('youngo_frontend_search_path')) {
    $route_builder_checks['courses_arabic'] = youngo_frontend_courses_path('arabic') === 'home/courses';
    $route_builder_checks['courses_english'] = youngo_frontend_courses_path('english') === 'en/home/courses';
    $route_builder_checks['search_arabic'] = youngo_frontend_search_path('arabic') === 'home/search';
    $route_builder_checks['search_english'] = youngo_frontend_search_path('english') === 'en/home/search';

    foreach ($route_builder_checks as $label => $ok) {
        youngo_ar_default_diag_assert($failures, $ok, 'Content route builder check failed: ' . $label);
    }
}
youngo_ar_default_diag_print('Content helper route builder checks', $route_builder_checks);

$protected_boundary = array();
if (function_exists('youngo_frontend_is_localizable_uri')) {
    $boundary_examples = array(
        'admin/dashboard' => false,
        'ar/admin/dashboard' => false,
        'en/admin/dashboard' => false,
        'payment/paymob/webhook' => false,
        'payment/paymob/return' => false,
        'en/payment/paymob/webhook' => false,
        'ar/payment/paymob/return' => false,
        'home/course_payment' => false,
        'home/apply_coupon' => false,
        'home/toggleWishlistItems/1' => false,
        'home/rate_course' => false,
        'login/validate_login' => false,
        'login/register' => false,
        'login' => true,
        'sign_up' => true,
        'home/contact_us' => true,
        'home/contact_us/submit' => false,
    );

    foreach ($boundary_examples as $uri => $expected) {
        $actual = youngo_frontend_is_localizable_uri($uri);
        $protected_boundary[$uri] = array('expected' => $expected, 'actual' => $actual, 'ok' => $actual === $expected);
        youngo_ar_default_diag_assert($failures, $actual === $expected, 'Protected/localizable boundary failed for: ' . $uri);
    }
}
youngo_ar_default_diag_print('Protected route boundary checks', $protected_boundary);

$html_checks = array();
if (function_exists('youngo_frontend_html_lang') && function_exists('youngo_frontend_html_dir')) {
    $html_checks = array(
        'arabic_lang' => youngo_frontend_html_lang('arabic'),
        'arabic_dir' => youngo_frontend_html_dir('arabic'),
        'english_lang' => youngo_frontend_html_lang('english'),
        'english_dir' => youngo_frontend_html_dir('english'),
    );

    youngo_ar_default_diag_assert($failures, $html_checks['arabic_lang'] === 'ar', 'Arabic html lang is not ar.');
    youngo_ar_default_diag_assert($failures, $html_checks['arabic_dir'] === 'rtl', 'Arabic html dir is not rtl.');
    youngo_ar_default_diag_assert($failures, $html_checks['english_lang'] === 'en', 'English html lang is not en.');
    youngo_ar_default_diag_assert($failures, $html_checks['english_dir'] === 'ltr', 'English html dir is not ltr.');
}
youngo_ar_default_diag_print('HTML lang/dir checks', $html_checks);

$changed_files = array();
$status_output = array();
$status_exit = 1;
exec('git -C ' . escapeshellarg($root) . ' status --short', $status_output, $status_exit);
if ($status_exit === 0) {
    foreach ($status_output as $line) {
        $changed_files[] = trim(substr($line, 3));
    }
}

$allowed_changed_files = array(
    'application/config/routes.php',
    'application/controllers/Home.php',
    'application/helpers/common_helper.php',
    'application/helpers/youngo_frontend_language_helper.php',
    'application/helpers/youngo_frontend_content_helper.php',
    'application/views/frontend/youngo/blog_details.php',
    'application/views/frontend/youngo/blogs.php',
    'application/views/frontend/youngo/change_password_from_forgot_password.php',
    'application/views/frontend/youngo/contact_us.php',
    'application/views/frontend/youngo/course_page.php',
    'application/views/frontend/youngo/courses_page.php',
    'application/views/frontend/youngo/footer.php',
    'application/views/frontend/youngo/forgot_password.php',
    'application/views/frontend/youngo/header.php',
    'application/views/frontend/youngo/index.php',
    'application/views/frontend/youngo/login.php',
    'application/views/frontend/youngo/my_access.php',
    'application/views/frontend/youngo/my_courses.php',
    'application/views/frontend/youngo/my_wishlist.php',
    'application/views/frontend/youngo/new_login_confirmation.php',
    'application/views/frontend/youngo/profile_menus.php',
    'application/views/frontend/youngo/sign_up.php',
    'application/views/frontend/youngo/verification_code.php',
    'application/views/frontend/youngo/wishlist_items.php',
    'scripts/phase_2/youngo_localization_ar_default_route_skeleton_1_diagnostic.php',
    'scripts/phase_2/youngo_localization_ar_default_links_1_diagnostic.php',
    'docs/qa/youngo_localization_ar_default_route_skeleton_1_report.md',
    'docs/qa/youngo_localization_ar_default_links_1_report.md',
    'docs/qa/youngo_localization_ar_default_public_qa_1_report.md',
    'docs/qa/youngo_localization_ar_default_authenticated_public_qa_1_report.md',
);
$unexpected_changed_files = array_values(array_diff($changed_files, $allowed_changed_files));
$protected_changed_files = array();
foreach ($changed_files as $file) {
    if (preg_match('#(Payment|Paymob|payment|paymob|checkout|coupon|payment_gateways|enrol)#', $file)) {
        $protected_changed_files[] = $file;
    }
    if (strpos($file, 'database/') === 0 || preg_match('#(^|/)schema|migration|sql#i', $file)) {
        $protected_changed_files[] = $file;
    }
}

$git_scope = array(
    'changed_files' => $changed_files,
    'unexpected_changed_files' => $unexpected_changed_files,
    'protected_changed_files' => array_values(array_unique($protected_changed_files)),
);
youngo_ar_default_diag_print('Git scope checks', $git_scope);
youngo_ar_default_diag_assert($failures, empty($unexpected_changed_files), 'Unexpected changed files detected.');
youngo_ar_default_diag_assert($failures, empty($protected_changed_files), 'Protected payment/checkout/DB files were changed.');

$added_lines = youngo_ar_default_diag_git_diff_added_lines($root);
$added_redirect_lines = array();
foreach ($added_lines as $line) {
    if (stripos($line, 'redirect(') !== false || stripos($line, 'header(') !== false) {
        $added_redirect_lines[] = $line;
    }
}
youngo_ar_default_diag_print('Redirect introduction checks', $added_redirect_lines);
youngo_ar_default_diag_assert($failures, empty($added_redirect_lines), 'Redirect/header behavior was added.');

$cta_status = array(
    'checkout_cta_helper_present' => is_file($checkout_cta_helper_file),
    'checkout_cta_gate_still_references_config' => is_file($checkout_cta_helper_file) && strpos(file_get_contents($checkout_cta_helper_file), 'is_checkout_cta_enabled') !== false,
    'paymob_routes_present' => isset($routes['payment/paymob/webhook'], $routes['payment/paymob/return']),
    'paymob_routes_unlocalized' => !isset($routes['en/payment/paymob/webhook'], $routes['ar/payment/paymob/webhook'], $routes['en/payment/paymob/return'], $routes['ar/payment/paymob/return']),
);
youngo_ar_default_diag_print('Payment and CTA safety checks', $cta_status);
foreach ($cta_status as $label => $ok) {
    youngo_ar_default_diag_assert($failures, $ok, 'Payment/CTA safety check failed: ' . $label);
}

$compatibility_diagnostic = youngo_ar_default_diag_run_php_script($root, 'scripts/phase_2/youngo_phase_2s_route_cta_boundary_diagnostic.php');
youngo_ar_default_diag_print('CTA boundary diagnostic subprocess', $compatibility_diagnostic);
if (!$compatibility_diagnostic['passes']) {
    $warnings[] = 'Existing CTA boundary diagnostic failed; investigate separately from route skeleton source checks.';
}

youngo_ar_default_diag_print('Warnings', $warnings);
youngo_ar_default_diag_print('Read-only safety', array(
    'result' => 'Diagnostic used file reads, helper pure-function calls, git status/diff inspection, and one existing read-only diagnostic subprocess. It did not execute SQL writes, submit forms, call Paymob, or modify data.',
));
youngo_ar_default_diag_print('Result', array(
    'status' => empty($failures) ? 'PASS' : 'FAIL',
    'failures' => $failures,
));

exit(empty($failures) ? 0 : 1);
