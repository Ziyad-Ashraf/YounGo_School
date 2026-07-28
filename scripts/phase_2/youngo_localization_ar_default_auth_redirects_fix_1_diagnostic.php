<?php
/**
 * LOCALIZATION.AR_DEFAULT.AUTH.REDIRECTS.FIX.1 diagnostic.
 *
 * Read-only checks for language-aware public auth/display redirects after
 * Arabic default and English /en route support. This script does not execute
 * DB writes, submit forms, call payment providers, or create checkout records.
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

function youngo_auth_redirect_diag_print($title, $payload)
{
    echo "\n== {$title} ==\n";
    echo is_string($payload) ? $payload . "\n" : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

function youngo_auth_redirect_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function youngo_auth_redirect_diag_route_map($source)
{
    $routes = array();
    if (preg_match_all('/\\$route\\[[\'"]([^\'"]+)[\'"]\\]\\s*=\\s*[\'"]([^\'"]+)[\'"]\\s*;/', $source, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $routes[$match[1]] = $match[2];
        }
    }

    return $routes;
}

function youngo_auth_redirect_diag_changed_files($root)
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

function youngo_auth_redirect_diag_added_lines($root)
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

$home_file = $root . '/application/controllers/Home.php';
$login_file = $root . '/application/controllers/Login.php';
$user_model_file = $root . '/application/models/User_model.php';
$language_helper_file = $root . '/application/helpers/youngo_frontend_language_helper.php';
$routes_file = $root . '/application/config/routes.php';
$login_view_file = $root . '/application/views/frontend/youngo/login.php';
$signup_view_file = $root . '/application/views/frontend/youngo/sign_up.php';
$profile_menu_file = $root . '/application/views/frontend/youngo/profile_menus.php';

$required_files = array(
    'application/controllers/Home.php' => is_file($home_file),
    'application/controllers/Login.php' => is_file($login_file),
    'application/models/User_model.php' => is_file($user_model_file),
    'application/helpers/youngo_frontend_language_helper.php' => is_file($language_helper_file),
    'application/config/routes.php' => is_file($routes_file),
    'application/views/frontend/youngo/login.php' => is_file($login_view_file),
    'application/views/frontend/youngo/sign_up.php' => is_file($signup_view_file),
    'application/views/frontend/youngo/profile_menus.php' => is_file($profile_menu_file),
);
youngo_auth_redirect_diag_print('Required files', $required_files);
foreach ($required_files as $label => $exists) {
    youngo_auth_redirect_diag_assert($failures, $exists, $label . ' is missing.');
}

if (is_file($language_helper_file)) {
    require_once $language_helper_file;
}

$helper_checks = array();
if (function_exists('youngo_frontend_language_from_uri') && function_exists('youngo_frontend_public_path')) {
    $helper_examples = array(
        'default_my_courses_language' => array('actual' => youngo_frontend_language_from_uri('home/my_courses'), 'expected' => 'arabic'),
        'english_my_courses_language' => array('actual' => youngo_frontend_language_from_uri('en/home/my_courses'), 'expected' => 'english'),
        'compat_my_courses_language' => array('actual' => youngo_frontend_language_from_uri('ar/home/my_courses'), 'expected' => 'arabic'),
        'default_home_redirect_path' => array('actual' => youngo_frontend_public_path('', 'arabic'), 'expected' => ''),
        'english_home_redirect_path' => array('actual' => youngo_frontend_public_path('', 'english'), 'expected' => 'en'),
        'compat_my_courses_canonical_path' => array('actual' => youngo_frontend_public_path('ar/home/my_courses', 'arabic'), 'expected' => 'home/my_courses'),
        'english_my_courses_path' => array('actual' => youngo_frontend_public_path('home/my_courses', 'english'), 'expected' => 'en/home/my_courses'),
        'english_login_path' => array('actual' => youngo_frontend_public_path('login', 'english'), 'expected' => 'en/login'),
        'payment_unlocalized_path' => array('actual' => youngo_frontend_public_path('payment/paymob/webhook', 'english'), 'expected' => 'payment/paymob/webhook'),
        'admin_unlocalized_path' => array('actual' => youngo_frontend_public_path('admin/dashboard', 'arabic'), 'expected' => 'admin/dashboard'),
        'action_unlocalized_path' => array('actual' => youngo_frontend_public_path('home/toggleWishlistItems/1', 'english'), 'expected' => 'home/toggleWishlistItems/1'),
    );

    foreach ($helper_examples as $label => $check) {
        $check['ok'] = $check['actual'] === $check['expected'];
        $helper_checks[$label] = $check;
        youngo_auth_redirect_diag_assert($failures, $check['ok'], 'Helper check failed: ' . $label);
    }
} else {
    youngo_auth_redirect_diag_assert($failures, false, 'Frontend language helper functions are unavailable.');
}
youngo_auth_redirect_diag_print('Helper behavior checks', $helper_checks);

$home_source = is_file($home_file) ? file_get_contents($home_file) : '';
$login_source = is_file($login_file) ? file_get_contents($login_file) : '';
$user_model_source = is_file($user_model_file) ? file_get_contents($user_model_file) : '';

$source_checks = array(
    'home_public_redirect_helper_exists' => strpos($home_source, 'private function youngo_frontend_public_redirect_url') !== false,
    'home_public_course_redirect_helper_exists' => strpos($home_source, 'private function youngo_frontend_public_course_redirect_url') !== false,
    'my_courses_uses_public_redirect' => preg_match('/public function my_courses\\(\\).*?youngo_redirect_to_frontend_public\\(\'\'\\);/s', $home_source) === 1,
    'my_access_uses_public_redirect' => preg_match('/public function my_access\\(\\).*?youngo_redirect_to_frontend_public\\(\'\'\\);/s', $home_source) === 1,
    'lesson_denied_course_uses_public_course_redirect' => strpos($home_source, 'redirect($this->youngo_frontend_public_course_redirect_url($course_details), \'refresh\');') !== false,
    'free_course_url_history_uses_public_course_url' => strpos($home_source, '$url = $this->youngo_frontend_public_course_redirect_url($course_details);') !== false,
    'login_stores_public_auth_language' => strpos($login_source, 'private function youngo_store_public_auth_language') !== false && strpos($login_source, '$this->youngo_store_public_auth_language();') !== false,
    'learner_login_fallback_uses_public_url' => strpos($user_model_source, "redirect(\$this->youngo_public_auth_url(''), 'refresh');") !== false,
    'already_logged_learner_uses_public_my_courses' => strpos($user_model_source, "redirect(\$this->youngo_public_auth_url('home/my_courses'), 'refresh');") !== false,
    'admin_dashboard_redirect_remains_fixed' => strpos($user_model_source, "redirect(site_url('admin/dashboard'), 'refresh');") !== false,
);
youngo_auth_redirect_diag_print('Source wiring checks', $source_checks);
foreach ($source_checks as $label => $ok) {
    youngo_auth_redirect_diag_assert($failures, $ok, 'Source wiring check failed: ' . $label);
}

$routes_source = is_file($routes_file) ? file_get_contents($routes_file) : '';
$routes = youngo_auth_redirect_diag_route_map($routes_source);
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
youngo_auth_redirect_diag_print('Forbidden localized route checks', $forbidden_routes);
youngo_auth_redirect_diag_assert($failures, empty($forbidden_routes), 'Admin/payment/API/action route aliases were localized.');

$view_sources = array(
    'login' => is_file($login_view_file) ? file_get_contents($login_view_file) : '',
    'signup' => is_file($signup_view_file) ? file_get_contents($signup_view_file) : '',
    'profile_menu' => is_file($profile_menu_file) ? file_get_contents($profile_menu_file) : '',
);
$operational_checks = array(
    'login_post_action_fixed' => strpos($view_sources['login'], "site_url('login/validate_login')") !== false,
    'registration_post_action_fixed' => strpos($view_sources['signup'], "site_url('login/register')") !== false,
    'logout_fixed' => strpos($view_sources['profile_menu'], "site_url('login/logout')") !== false,
);
youngo_auth_redirect_diag_print('Operational URL checks', $operational_checks);
foreach ($operational_checks as $label => $ok) {
    youngo_auth_redirect_diag_assert($failures, $ok, 'Operational URL changed or missing: ' . $label);
}

$changed_files = youngo_auth_redirect_diag_changed_files($root);
$allowed_changed_files = array(
    'application/controllers/Home.php',
    'application/controllers/Login.php',
    'application/models/User_model.php',
    'scripts/phase_2/youngo_localization_ar_default_auth_redirects_fix_1_diagnostic.php',
    'docs/qa/youngo_localization_ar_default_auth_redirects_fix_1_report.md',
);
$unexpected_changed_files = array_values(array_diff($changed_files, $allowed_changed_files));
youngo_auth_redirect_diag_print('Changed file scope', array(
    'changed_files' => $changed_files,
    'unexpected_changed_files' => $unexpected_changed_files,
));
youngo_auth_redirect_diag_assert($failures, empty($unexpected_changed_files), 'Unexpected files changed.');

$added_lines = youngo_auth_redirect_diag_added_lines($root);
$forbidden_added_patterns = array(
    '/youngo\\/checkout/i',
    '/payment\\/paymob/i',
    '/home\\/course_payment/i',
    '/home\\/shopping_cart/i',
    '/\\$this->db->(?:insert|update|delete)\\s*\\(/i',
    '/->insert\\s*\\(/i',
    '/->update\\s*\\(/i',
    '/->delete\\s*\\(/i',
    '/INSERT\\s+INTO/i',
    '/UPDATE\\s+[a-z_]/i',
    '/DELETE\\s+FROM/i',
);
$forbidden_added_lines = array();
foreach ($added_lines as $line) {
    foreach ($forbidden_added_patterns as $pattern) {
        if (preg_match($pattern, $line)) {
            $forbidden_added_lines[] = $line;
            break;
        }
    }
}
youngo_auth_redirect_diag_print('Forbidden added-line checks', $forbidden_added_lines);
youngo_auth_redirect_diag_assert($failures, empty($forbidden_added_lines), 'Forbidden DB/payment/checkout code was added.');

if (!empty($failures)) {
    youngo_auth_redirect_diag_print('FAILURES', $failures);
    exit(1);
}

echo "\nPASS: LOCALIZATION.AR_DEFAULT.AUTH.REDIRECTS.FIX.1 diagnostic checks passed.\n";
exit(0);
