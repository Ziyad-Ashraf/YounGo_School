<?php
/**
 * LOCALIZATION.AR_DEFAULT.LINKS.1 diagnostic.
 *
 * Read-only checks for public frontend link generation after Arabic default
 * and English /en route support. This script does not execute DB writes,
 * submit forms, call payment providers, or create checkout/payment records.
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

function youngo_links_diag_print($title, $payload)
{
    echo "\n== {$title} ==\n";
    echo is_string($payload) ? $payload . "\n" : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

function youngo_links_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function youngo_links_diag_route_map($source)
{
    $routes = array();
    if (preg_match_all('/\\$route\\[[\'"]([^\'"]+)[\'"]\\]\\s*=\\s*[\'"]([^\'"]+)[\'"]\\s*;/', $source, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $routes[$match[1]] = $match[2];
        }
    }

    return $routes;
}

function youngo_links_diag_file_contains($path, $needle)
{
    return is_file($path) && strpos(file_get_contents($path), $needle) !== false;
}

function youngo_links_diag_changed_files($root)
{
    $output = array();
    $exit_code = 1;
    exec('git -C ' . escapeshellarg($root) . ' status --short', $output, $exit_code);

    if ($exit_code !== 0) {
        return array();
    }

    $files = array();
    foreach ($output as $line) {
        $files[] = trim(substr($line, 3));
    }

    return $files;
}

function youngo_links_diag_added_lines($root)
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

$language_helper_file = $root . '/application/helpers/youngo_frontend_language_helper.php';
$content_helper_file = $root . '/application/helpers/youngo_frontend_content_helper.php';
$routes_file = $root . '/application/config/routes.php';
$common_helper_file = $root . '/application/helpers/common_helper.php';
$checkout_cta_helper_file = $root . '/application/helpers/youngo_checkout_cta_helper.php';

$required_files = array(
    'application/helpers/youngo_frontend_language_helper.php' => is_file($language_helper_file),
    'application/helpers/youngo_frontend_content_helper.php' => is_file($content_helper_file),
    'application/config/routes.php' => is_file($routes_file),
    'application/helpers/common_helper.php' => is_file($common_helper_file),
    'application/helpers/youngo_checkout_cta_helper.php' => is_file($checkout_cta_helper_file),
);
youngo_links_diag_print('Required files', $required_files);
foreach ($required_files as $label => $exists) {
    youngo_links_diag_assert($failures, $exists, $label . ' is missing.');
}

if (is_file($language_helper_file)) {
    require_once $language_helper_file;
}
if (is_file($content_helper_file)) {
    require_once $content_helper_file;
}

$path_checks = array();
if (function_exists('youngo_frontend_public_path')) {
    $examples = array(
        'arabic_home' => array('', 'arabic', ''),
        'english_home' => array('', 'english', 'en'),
        'arabic_courses' => array('home/courses', 'arabic', 'home/courses'),
        'english_courses' => array('home/courses', 'english', 'en/home/courses'),
        'arabic_course_detail' => array('home/course/robotics-and-ai-explorers/9', 'arabic', 'home/course/robotics-and-ai-explorers/9'),
        'english_course_detail' => array('home/course/robotics-and-ai-explorers/9', 'english', 'en/home/course/robotics-and-ai-explorers/9'),
        'arabic_blog' => array('blog', 'arabic', 'home/blog'),
        'english_blog' => array('blog', 'english', 'en/home/blog'),
        'arabic_blog_detail' => array('blog/details/demo-post/1', 'arabic', 'blog/details/demo-post/1'),
        'english_blog_detail' => array('blog/details/demo-post/1', 'english', 'en/blog/details/demo-post/1'),
        'arabic_contact' => array('home/contact_us', 'arabic', 'home/contact'),
        'english_contact' => array('home/contact_us', 'english', 'en/home/contact'),
        'arabic_login' => array('login', 'arabic', 'login'),
        'english_login' => array('login', 'english', 'en/login'),
        'arabic_signup' => array('sign_up', 'arabic', 'sign_up'),
        'english_signup' => array('sign_up', 'english', 'en/sign-up'),
        'arabic_my_courses' => array('home/my_courses', 'arabic', 'home/my_courses'),
        'english_my_courses' => array('home/my_courses', 'english', 'en/home/my_courses'),
        'arabic_my_access' => array('home/my_access', 'arabic', 'home/my_access'),
        'english_my_access' => array('home/my_access', 'english', 'en/home/my_access'),
        'arabic_wishlist' => array('home/my_wishlist', 'arabic', 'home/my_wishlist'),
        'english_wishlist' => array('home/my_wishlist', 'english', 'en/home/my_wishlist'),
        'payment_unlocalized' => array('payment/paymob/webhook', 'english', 'payment/paymob/webhook'),
        'admin_unlocalized' => array('admin/dashboard', 'arabic', 'admin/dashboard'),
        'ajax_unlocalized' => array('home/toggleWishlistItems/1', 'english', 'home/toggleWishlistItems/1'),
    );

    foreach ($examples as $label => $example) {
        $actual = youngo_frontend_public_path($example[0], $example[1]);
        $path_checks[$label] = array('expected' => $example[2], 'actual' => $actual, 'ok' => $actual === $example[2]);
        youngo_links_diag_assert($failures, $actual === $example[2], 'Public path check failed: ' . $label);
    }
} else {
    youngo_links_diag_assert($failures, false, 'youngo_frontend_public_path() is unavailable.');
}
youngo_links_diag_print('Public path helper checks', $path_checks);

$switch_checks = array();
if (function_exists('youngo_frontend_language_switch_path')) {
    $switch_examples = array(
        'default_courses_to_english' => array('english', 'home/courses', 'en/home/courses'),
        'english_courses_to_arabic' => array('arabic', 'en/home/courses', 'home/courses'),
        'compat_courses_to_arabic' => array('arabic', 'ar/home/courses', 'home/courses'),
        'default_blog_to_english' => array('english', 'home/blog', 'en/home/blog'),
        'english_blog_to_arabic' => array('arabic', 'en/home/blog', 'home/blog'),
        'default_contact_to_english' => array('english', 'home/contact', 'en/home/contact'),
        'english_contact_to_arabic' => array('arabic', 'en/home/contact', 'home/contact'),
        'payment_stays_unlocalized' => array('english', 'payment/paymob/webhook', 'payment/paymob/webhook'),
    );

    foreach ($switch_examples as $label => $example) {
        $actual = youngo_frontend_language_switch_path($example[0], $example[1], false);
        $switch_checks[$label] = array('expected' => $example[2], 'actual' => $actual, 'ok' => $actual === $example[2]);
        youngo_links_diag_assert($failures, $actual === $example[2], 'Language switch path failed: ' . $label);
    }
} else {
    youngo_links_diag_assert($failures, false, 'youngo_frontend_language_switch_path() is unavailable.');
}
youngo_links_diag_print('Language switch checks', $switch_checks);

$routes_source = is_file($routes_file) ? file_get_contents($routes_file) : '';
$routes = youngo_links_diag_route_map($routes_source);
$route_checks = array(
    'ar_alias_root' => isset($routes['ar']) && $routes['ar'] === 'home/index',
    'ar_alias_courses' => isset($routes['ar/home/courses']) && $routes['ar/home/courses'] === 'home/courses',
    'ar_alias_blog_detail' => isset($routes['ar/blog/details/(:any)/(:num)']) && $routes['ar/blog/details/(:any)/(:num)'] === 'blog/details/$1/$2',
    'en_root' => isset($routes['en']) && $routes['en'] === 'home/index',
    'en_courses' => isset($routes['en/home/courses']) && $routes['en/home/courses'] === 'home/courses',
    'en_blog_detail' => isset($routes['en/blog/details/(:any)/(:num)']) && $routes['en/blog/details/(:any)/(:num)'] === 'blog/details/$1/$2',
    'en_forgot_password' => isset($routes['en/login/forgot_password_request']) && $routes['en/login/forgot_password_request'] === 'login/forgot_password_request',
);
youngo_links_diag_print('Route alias checks', $route_checks);
foreach ($route_checks as $label => $ok) {
    youngo_links_diag_assert($failures, $ok, 'Route alias check failed: ' . $label);
}

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
youngo_links_diag_print('Forbidden localized route checks', $forbidden_routes);
youngo_links_diag_assert($failures, empty($forbidden_routes), 'Admin/payment/API/write route aliases were localized.');

$view_files = array(
    'application/views/frontend/youngo/header.php',
    'application/views/frontend/youngo/footer.php',
    'application/views/frontend/youngo/courses_page.php',
    'application/views/frontend/youngo/course_page.php',
    'application/views/frontend/youngo/blogs.php',
    'application/views/frontend/youngo/blog_details.php',
    'application/views/frontend/youngo/contact_us.php',
    'application/views/frontend/youngo/login.php',
    'application/views/frontend/youngo/sign_up.php',
    'application/views/frontend/youngo/my_wishlist.php',
    'application/views/frontend/youngo/wishlist_items.php',
    'application/views/frontend/youngo/my_courses.php',
    'application/views/frontend/youngo/my_access.php',
    'application/views/frontend/youngo/profile_menus.php',
);
$view_source = '';
foreach ($view_files as $view_file) {
    $path = $root . '/' . $view_file;
    $view_source .= is_file($path) ? "\n/* {$view_file} */\n" . file_get_contents($path) : '';
}

$view_checks = array(
    'no_generated_ar_site_url' => !preg_match('/site_url\\([^)]+[\'"]ar(?:\\/|[\'"])/', $view_source),
    'no_generated_ar_courses_literal' => strpos($view_source, 'ar/courses') === false,
    'header_uses_home_helper' => youngo_links_diag_file_contains($root . '/application/views/frontend/youngo/header.php', 'youngo_frontend_home_url'),
    'header_uses_english_switcher' => youngo_links_diag_file_contains($root . '/application/views/frontend/youngo/header.php', "youngo_frontend_language_switch_url('english')"),
    'header_uses_arabic_switcher' => youngo_links_diag_file_contains($root . '/application/views/frontend/youngo/header.php', "youngo_frontend_language_switch_url('arabic')"),
    'blog_uses_detail_helper' => youngo_links_diag_file_contains($root . '/application/views/frontend/youngo/blogs.php', 'youngo_frontend_blog_detail_url'),
    'common_homepage_uses_public_helper' => youngo_links_diag_file_contains($common_helper_file, 'youngo_frontend_public_url'),
    'wishlist_public_links_use_helpers' => youngo_links_diag_file_contains($root . '/application/views/frontend/youngo/my_wishlist.php', 'youngo_frontend_courses_url'),
    'account_menu_uses_helpers' => youngo_links_diag_file_contains($root . '/application/views/frontend/youngo/profile_menus.php', 'youngo_frontend_my_courses_url'),
);
youngo_links_diag_print('View link-generation checks', $view_checks);
foreach ($view_checks as $label => $ok) {
    youngo_links_diag_assert($failures, $ok, 'View link-generation check failed: ' . $label);
}

$added_lines = youngo_links_diag_added_lines($root);
$forbidden_added_lines = array();
foreach ($added_lines as $line) {
    if (preg_match('#(?:en|ar)/(?:admin|payment|paymob|paypal|stripe|razorpay|paystack|flutterwave|api|cron|addons)(?:/|[\'"])#i', $line)) {
        $forbidden_added_lines[] = $line;
    }
    if (preg_match('#(?:en|ar)/home/(?:course_payment|shopping_cart|update_cart|apply_coupon|remove_coupon|checkout|confirm_payment|webhook|handle_cart_items|refreshwishlist|toggleWishlistItems|rate_course)(?:/|[\'"])#i', $line)) {
        $forbidden_added_lines[] = $line;
    }
    if (preg_match('/\\$this->db->(?:insert|update|delete|replace)\\s*\\(/i', $line)) {
        $forbidden_added_lines[] = $line;
    }
    if (preg_match('/->insert\\s*\\(\\s*[\'"](?:payment|enrol|youngo_checkout_orders|youngo_coupon_usages)/i', $line)) {
        $forbidden_added_lines[] = $line;
    }
}
youngo_links_diag_print('Forbidden added-line checks', $forbidden_added_lines);
youngo_links_diag_assert($failures, empty($forbidden_added_lines), 'Forbidden localized/action/write/payment lines were added.');

$changed_files = youngo_links_diag_changed_files($root);
$protected_changed_files = array();
foreach ($changed_files as $file) {
    if (strpos($file, 'database/') === 0 || preg_match('#(^|/)schema|migration|\\.sql$#i', $file)) {
        $protected_changed_files[] = $file;
    }
    if (preg_match('#(^|/)(Youngo_paymob|Youngo_payment|payment_gateways|enrol)#i', $file)) {
        $protected_changed_files[] = $file;
    }
}
$git_scope = array(
    'changed_files' => $changed_files,
    'protected_changed_files' => array_values(array_unique($protected_changed_files)),
);
youngo_links_diag_print('Git scope checks', $git_scope);
youngo_links_diag_assert($failures, empty($protected_changed_files), 'Protected DB/payment/legacy files changed.');

$payment_cta_checks = array(
    'checkout_cta_helper_present' => is_file($checkout_cta_helper_file),
    'checkout_cta_gate_unchanged' => is_file($checkout_cta_helper_file) && strpos(file_get_contents($checkout_cta_helper_file), 'is_checkout_cta_enabled') !== false,
    'no_direct_checkout_routes_added' => empty(array_filter($added_lines, function ($line) {
        return preg_match('#home/(?:course_payment|shopping_cart|checkout|confirm_payment)#i', $line);
    })),
    'paymob_routes_unlocalized' => !isset($routes['en/payment/paymob/webhook'], $routes['ar/payment/paymob/webhook'], $routes['en/payment/paymob/return'], $routes['ar/payment/paymob/return']),
);
youngo_links_diag_print('Payment and CTA safety checks', $payment_cta_checks);
foreach ($payment_cta_checks as $label => $ok) {
    youngo_links_diag_assert($failures, $ok, 'Payment/CTA safety check failed: ' . $label);
}

youngo_links_diag_print('Warnings', $warnings);
youngo_links_diag_print('Read-only safety', 'This diagnostic only reads files, checks helper return values, and inspects git diff/status. It does not write DB rows or call Paymob.');
youngo_links_diag_print('Result', array(
    'status' => empty($failures) ? 'PASS' : 'FAIL',
    'failures' => $failures,
));

exit(empty($failures) ? 0 : 1);
