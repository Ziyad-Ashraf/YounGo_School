<?php
/**
 * Phase 2U.6 Arabic public route alias diagnostic.
 *
 * Read-only checks for safe Arabic frontend route aliases.
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

function phase_2u63_print($title, $payload)
{
    echo "\n== {$title} ==\n";
    echo is_string($payload) ? $payload . "\n" : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function phase_2u63_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function phase_2u63_file_contains($path, $needle)
{
    return is_file($path) && strpos(file_get_contents($path), $needle) !== false;
}

function phase_2u63_route_map($source)
{
    $routes = array();
    if (preg_match_all('/\\$route\\[[\'"]([^\'"]+)[\'"]\\]\\s*=\\s*[\'"]([^\'"]+)[\'"]\\s*;/', $source, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $routes[$match[1]] = $match[2];
        }
    }

    return $routes;
}

function phase_2u63_line_number($source, $needle)
{
    $position = strpos($source, $needle);
    if ($position === false) {
        return null;
    }

    return substr_count(substr($source, 0, $position), "\n") + 1;
}

function phase_2u63_run_php_script($root, $script)
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

$routes_file = $root . '/application/config/routes.php';
$home_file = $root . '/application/controllers/Home.php';
$login_file = $root . '/application/controllers/Login.php';
$sign_up_file = $root . '/application/controllers/Sign_up.php';
$helper_file = $root . '/application/helpers/youngo_frontend_language_helper.php';

$required_files = array(
    'application/config/routes.php' => is_file($routes_file),
    'application/controllers/Home.php' => is_file($home_file),
    'application/controllers/Login.php' => is_file($login_file),
    'application/controllers/Sign_up.php' => is_file($sign_up_file),
    'application/helpers/youngo_frontend_language_helper.php' => is_file($helper_file),
);
phase_2u63_print('Required file availability', $required_files);
foreach ($required_files as $label => $exists) {
    phase_2u63_assert($failures, $exists, $label . ' is missing.');
}

$routes_source = is_file($routes_file) ? file_get_contents($routes_file) : '';
$routes = phase_2u63_route_map($routes_source);

$expected_arabic_aliases = array(
    'ar' => 'home/index',
    'ar/courses' => 'home/courses',
    'ar/courses/(:num)' => 'home/courses',
    'ar/course/(:any)/(:num)' => 'home/course/$1/$2',
    'ar/search' => 'home/search',
    'ar/search/(:any)' => 'home/search/$1',
    'ar/my-courses' => 'home/my_courses',
    'ar/my-access' => 'home/my_access',
    'ar/wishlist' => 'home/my_wishlist',
    'ar/login' => 'login/index',
    'ar/sign-up' => 'sign_up/index',
);

$alias_status = array();
foreach ($expected_arabic_aliases as $pattern => $target) {
    $actual = isset($routes[$pattern]) ? $routes[$pattern] : null;
    $alias_status[$pattern] = array(
        'expected' => $target,
        'actual' => $actual,
        'ok' => $actual === $target,
    );
    phase_2u63_assert($failures, $actual === $target, 'Arabic route alias missing or mismapped: ' . $pattern);
}
phase_2u63_print('Arabic public alias checks', $alias_status);

$english_route_status = array(
    'default_controller_home' => isset($routes['default_controller']) && $routes['default_controller'] === 'home',
    'Home::index' => phase_2u63_file_contains($home_file, 'public function index()'),
    'Home::courses' => phase_2u63_file_contains($home_file, 'public function courses()'),
    'Home::course' => phase_2u63_file_contains($home_file, 'public function course($slug = "", $course_id = "")'),
    'Home::search' => phase_2u63_file_contains($home_file, 'public function search($search_string = "")'),
    'Home::my_courses' => phase_2u63_file_contains($home_file, 'public function my_courses()'),
    'Home::my_access' => phase_2u63_file_contains($home_file, 'public function my_access()'),
    'Home::my_wishlist' => phase_2u63_file_contains($home_file, 'public function my_wishlist()'),
    'Login::index' => phase_2u63_file_contains($login_file, 'public function index()'),
    'Sign_up::index' => phase_2u63_file_contains($sign_up_file, 'public function index()'),
);
phase_2u63_print('English unprefixed route preservation checks', $english_route_status);
foreach ($english_route_status as $label => $ok) {
    phase_2u63_assert($failures, $ok, 'English route/controller behavior was not found: ' . $label);
}

$route_boundary_status = array(
    'no_en_routes' => true,
    'no_arabic_translated_route_usage' => strpos($routes_source, 'arabic_translated') === false,
    'no_ar_admin_aliases' => true,
    'no_ar_payment_checkout_cart_coupon_write_aliases' => true,
    'no_ar_api_cron_aliases' => true,
);

$forbidden_key_patterns = array(
    '#^en(?:/|$)#' => 'no_en_routes',
    '#^ar/admin(?:/|$)#' => 'no_ar_admin_aliases',
    '#^ar/(?:addons|api|cron)(?:/|$)#' => 'no_ar_api_cron_aliases',
    '#^ar/(?:payment|paymob|paypal|stripe|razorpay|paystack|flutterwave)(?:/|$)#' => 'no_ar_payment_checkout_cart_coupon_write_aliases',
    '#^ar/home/(?:payment|paypal|stripe|paymob|razorpay|paystack|flutterwave|course_payment|shopping_cart|update_cart|apply_coupon|remove_coupon|checkout|confirm_payment|webhook|handle_cart_items|handleCartItems|handle_buy_now|coupon|apply_coupon|remove_coupon)(?:/|$)#i' => 'no_ar_payment_checkout_cart_coupon_write_aliases',
);

$forbidden_target_pattern = '#(?:admin/|addons/|api/|cron/|payment|paymob|paypal|stripe|razorpay|paystack|flutterwave|course_payment|shopping_cart|update_cart|apply_coupon|remove_coupon|checkout|confirm_payment|webhook|handle_cart_items|handleCartItems|handle_buy_now|coupon)#i';
$forbidden_aliases = array();
foreach ($routes as $pattern => $target) {
    foreach ($forbidden_key_patterns as $regex => $status_key) {
        if (preg_match($regex, $pattern)) {
            $route_boundary_status[$status_key] = false;
            $forbidden_aliases[] = array('pattern' => $pattern, 'target' => $target, 'reason' => $status_key);
        }
    }

    if (strpos($pattern, 'ar') === 0 && preg_match($forbidden_target_pattern, $target)) {
        $route_boundary_status['no_ar_payment_checkout_cart_coupon_write_aliases'] = false;
        $forbidden_aliases[] = array('pattern' => $pattern, 'target' => $target, 'reason' => 'forbidden_arabic_target');
    }
}
$route_boundary_status['forbidden_aliases'] = $forbidden_aliases;
phase_2u63_print('Route boundary checks', $route_boundary_status);
phase_2u63_assert($failures, $route_boundary_status['no_en_routes'], '/en route aliases were added.');
phase_2u63_assert($failures, $route_boundary_status['no_arabic_translated_route_usage'], 'arabic_translated appears in route configuration.');
phase_2u63_assert($failures, $route_boundary_status['no_ar_admin_aliases'], 'Arabic admin/dashboard aliases were added.');
phase_2u63_assert($failures, $route_boundary_status['no_ar_payment_checkout_cart_coupon_write_aliases'], 'Arabic payment/checkout/cart/coupon/write aliases were added.');
phase_2u63_assert($failures, $route_boundary_status['no_ar_api_cron_aliases'], 'Arabic API/cron aliases were added.');

$argument_status = array(
    'course_alias_preserves_slug_id_order' => isset($routes['ar/course/(:any)/(:num)']) && $routes['ar/course/(:any)/(:num)'] === 'home/course/$1/$2',
    'search_alias_preserves_search_string_order' => isset($routes['ar/search/(:any)']) && $routes['ar/search/(:any)'] === 'home/search/$1',
    'courses_pagination_does_not_pass_extra_method_argument' => isset($routes['ar/courses/(:num)']) && $routes['ar/courses/(:num)'] === 'home/courses',
);
phase_2u63_print('Segment and argument mapping checks', $argument_status);
foreach ($argument_status as $label => $ok) {
    phase_2u63_assert($failures, $ok, 'Arabic route argument mapping is unsafe: ' . $label);
}

$first_ar_alias_line = phase_2u63_line_number($routes_source, "\$route['ar']");
$route_order_status = array(
    'first_ar_alias_line' => $first_ar_alias_line,
    'catch_all_route_before_ar_aliases' => false,
    'ar_aliases_before_translate_uri_dashes' => false,
);
if ($first_ar_alias_line !== null) {
    $before_ar_source = implode("\n", array_slice(explode("\n", $routes_source), 0, $first_ar_alias_line - 1));
    $route_order_status['catch_all_route_before_ar_aliases'] = preg_match('/\\$route\\[[\'"](?:\\(:any\\)|ar\\/\\(:any\\))[\'"]\\]/', $before_ar_source) === 1;
    $translate_line = phase_2u63_line_number($routes_source, "\$route['translate_uri_dashes'] = false;");
    $route_order_status['ar_aliases_before_translate_uri_dashes'] = $translate_line === null || $first_ar_alias_line < $translate_line;
}
phase_2u63_print('Route order checks', $route_order_status);
phase_2u63_assert($failures, $first_ar_alias_line !== null, 'Arabic aliases were not found in routes.php.');
phase_2u63_assert($failures, !$route_order_status['catch_all_route_before_ar_aliases'], 'A catch-all route appears before Arabic aliases.');
phase_2u63_assert($failures, $route_order_status['ar_aliases_before_translate_uri_dashes'], 'Arabic aliases are after translate_uri_dashes.');

$helper_status = array();
if (is_file($helper_file)) {
    require_once $helper_file;
}
if (function_exists('youngo_frontend_language_from_uri')) {
    $helper_ar_examples = array(
        'ar' => 'arabic',
        'ar/courses' => 'arabic',
        'ar/course/scratch-coding-for-young-creators/1' => 'arabic',
        'ar/search?query=scratch' => 'arabic',
        'ar/my-courses' => 'arabic',
        'ar/my-access' => 'arabic',
        'ar/wishlist' => 'arabic',
        'ar/login' => 'arabic',
        'ar/sign-up' => 'arabic',
    );
    foreach ($helper_ar_examples as $uri => $expected) {
        $actual = youngo_frontend_language_from_uri($uri);
        $helper_status['arabic:' . $uri] = array('expected' => $expected, 'actual' => $actual, 'ok' => $actual === $expected);
        phase_2u63_assert($failures, $actual === $expected, 'Helper does not recognize Arabic alias: ' . $uri);
    }

    $helper_english_examples = array(
        '' => 'english',
        'home/courses' => 'english',
        'home/course/scratch-coding-for-young-creators/1' => 'english',
        'login' => 'english',
        'sign_up' => 'english',
    );
    foreach ($helper_english_examples as $uri => $expected) {
        $actual = youngo_frontend_language_from_uri($uri);
        $helper_status['english:' . ($uri === '' ? '(empty)' : $uri)] = array('expected' => $expected, 'actual' => $actual, 'ok' => $actual === $expected);
        phase_2u63_assert($failures, $actual === $expected, 'Helper does not preserve English behavior: ' . ($uri === '' ? '(empty)' : $uri));
    }

    $helper_excluded_examples = array(
        'ar/admin/dashboard',
        'ar/home/course_payment',
        'ar/home/shopping_cart',
        'ar/home/apply_coupon',
        'ar/api/course/list',
        'ar/cron/run',
    );
    foreach ($helper_excluded_examples as $uri) {
        $is_localizable = youngo_frontend_is_localizable_uri($uri);
        $language = youngo_frontend_language_from_uri($uri);
        $helper_status['excluded:' . $uri] = array('is_localizable' => $is_localizable, 'language' => $language, 'ok' => !$is_localizable && $language === 'english');
        phase_2u63_assert($failures, !$is_localizable && $language === 'english', 'Helper exclusion failed for: ' . $uri);
    }
} else {
    phase_2u63_assert($failures, false, 'Frontend language helper functions are unavailable.');
}
phase_2u63_print('Helper compatibility checks', $helper_status);

$status_output = array();
$status_exit = 1;
exec('git -C ' . escapeshellarg($root) . ' status --short', $status_output, $status_exit);
$allowed_status_files = array(
    '.gitignore',
    'application/config/routes.php',
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
    'scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php',
    'scripts/phase_2/youngo_phase_2u6_frontend_language_context_diagnostic.php',
    'scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php',
    'scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php',
    'application/helpers/youngo_frontend_language_helper.php',
    'application/views/frontend/youngo/index.php',
    'application/views/frontend/youngo/header.php',
    'assets/frontend/youngo/css/youngo.css',
    'scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php',
);
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
    'scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php',
);
$allowed_frontend_shell_localization_files = array(
    'application/helpers/youngo_frontend_language_helper.php',
    'application/views/frontend/youngo/index.php',
    'application/views/frontend/youngo/header.php',
    'assets/frontend/youngo/css/youngo.css',
    'scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php',
);
$status_files = array();
$unexpected_status_files = array();
foreach ($status_output as $line) {
    $file = trim(substr($line, 3));
    $status_files[] = $file;
    if (!in_array($file, $allowed_status_files, true)) {
        $unexpected_status_files[] = $file;
    }
}
$git_scope_status = array(
    'status_files' => $status_files,
    'unexpected_status_files' => $unexpected_status_files,
);
phase_2u63_print('Git scope checks', $git_scope_status);
phase_2u63_assert($failures, empty($unexpected_status_files), 'Unexpected source changes found during route alias phase.');

$rendering_boundary_status = array(
    'frontend_views_changed' => array(),
    'controllers_changed' => array(),
    'models_changed' => array(),
    'payment_checkout_files_changed' => array(),
);
foreach ($status_files as $file) {
    if (in_array($file, $allowed_content_translation_files, true) || in_array($file, $allowed_frontend_shell_localization_files, true)) {
        continue;
    }

    if (strpos($file, 'application/views/frontend/') === 0) {
        $rendering_boundary_status['frontend_views_changed'][] = $file;
    }
    if (strpos($file, 'application/controllers/') === 0) {
        $rendering_boundary_status['controllers_changed'][] = $file;
    }
    if (strpos($file, 'application/models/') === 0) {
        $rendering_boundary_status['models_changed'][] = $file;
    }
    if (preg_match('#(Payment|Paymob|checkout|payment|coupon|shopping_cart|cart)#i', $file)) {
        $rendering_boundary_status['payment_checkout_files_changed'][] = $file;
    }
}
phase_2u63_print('Rendering and protected source boundary checks', $rendering_boundary_status);
phase_2u63_assert($failures, empty($rendering_boundary_status['frontend_views_changed']), 'Unexpected frontend view rendering changes were introduced outside Phase 2U.6.4 content shaping.');
phase_2u63_assert($failures, empty($rendering_boundary_status['controllers_changed']), 'Unexpected controller changes were introduced outside Phase 2U.6.4 content shaping.');
phase_2u63_assert($failures, empty($rendering_boundary_status['models_changed']), 'Model changes were introduced.');
phase_2u63_assert($failures, empty($rendering_boundary_status['payment_checkout_files_changed']), 'Payment/checkout/cart/coupon files were changed.');

$subprocess_checks = array(
    'frontend_language_context' => phase_2u63_run_php_script($root, 'scripts/phase_2/youngo_phase_2u6_frontend_language_context_diagnostic.php'),
    'phase_2s_route_cta_boundary' => phase_2u63_run_php_script($root, 'scripts/phase_2/youngo_phase_2s_route_cta_boundary_diagnostic.php'),
);
phase_2u63_print('Compatibility diagnostic subprocess checks', $subprocess_checks);
foreach ($subprocess_checks as $label => $status) {
    phase_2u63_assert($failures, $status['passes'], 'Compatibility diagnostic failed: ' . $label);
}

phase_2u63_print('Warnings', $warnings);
phase_2u63_print('Read-only safety', array(
    'result' => 'Diagnostic used file reads, helper pure-function calls, git status inspection, and read-only diagnostic subprocesses. It did not call get_phrase(), submit forms, or modify data.',
));
phase_2u63_print('Result', array(
    'status' => empty($failures) ? 'PASS' : 'FAIL',
    'failures' => $failures,
));

exit(empty($failures) ? 0 : 1);
