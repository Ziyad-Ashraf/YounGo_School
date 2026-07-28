<?php
/**
 * SUBSCRIPTIONS.PAGE.DYNAMIC.UI.1 diagnostic.
 *
 * Read-only checks for the dynamic public subscriptions page. This script
 * does not execute DB writes, submit forms, call payment providers, create
 * orders, create enrolments, or grant access.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$root = dirname(__DIR__, 2);
defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

$failures = array();

function youngo_subscriptions_ui_diag_print($title, $payload)
{
    echo "\n== {$title} ==\n";
    echo is_string($payload) ? $payload . "\n" : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

function youngo_subscriptions_ui_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function youngo_subscriptions_ui_diag_route_map($source)
{
    $routes = array();
    if (preg_match_all('/\\$route\\[[\'"]([^\'"]+)[\'"]\\]\\s*=\\s*[\'"]([^\'"]+)[\'"]\\s*;/', $source, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $routes[$match[1]] = $match[2];
        }
    }

    return $routes;
}

function youngo_subscriptions_ui_diag_changed_files($root)
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

function youngo_subscriptions_ui_diag_added_source_lines($root)
{
    $paths = array(
        'application/config/routes.php',
        'application/controllers/Home.php',
        'application/helpers/youngo_frontend_language_helper.php',
        'application/views/frontend/youngo/header.php',
        'application/views/frontend/youngo/footer.php',
        'application/views/frontend/youngo/subscriptions.php',
        'assets/frontend/youngo/css/youngo.css',
    );
    $output = array();
    $exit_code = 1;
    $command = 'git -C ' . escapeshellarg($root) . ' diff -- ' . implode(' ', array_map('escapeshellarg', $paths));
    exec($command, $output, $exit_code);
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
$subscription_model_file = $root . '/application/models/Youngo_subscription_model.php';
$header_file = $root . '/application/views/frontend/youngo/header.php';
$footer_file = $root . '/application/views/frontend/youngo/footer.php';
$subscriptions_view_file = $root . '/application/views/frontend/youngo/subscriptions.php';
$css_file = $root . '/assets/frontend/youngo/css/youngo.css';

$required_files = array(
    'application/config/routes.php' => is_file($routes_file),
    'application/controllers/Home.php' => is_file($home_file),
    'application/helpers/youngo_frontend_language_helper.php' => is_file($language_helper_file),
    'application/helpers/youngo_frontend_content_helper.php' => is_file($content_helper_file),
    'application/models/Youngo_subscription_model.php' => is_file($subscription_model_file),
    'application/views/frontend/youngo/header.php' => is_file($header_file),
    'application/views/frontend/youngo/footer.php' => is_file($footer_file),
    'application/views/frontend/youngo/subscriptions.php' => is_file($subscriptions_view_file),
    'assets/frontend/youngo/css/youngo.css' => is_file($css_file),
);
youngo_subscriptions_ui_diag_print('Required files', $required_files);
foreach ($required_files as $label => $exists) {
    youngo_subscriptions_ui_diag_assert($failures, $exists, $label . ' is missing.');
}

$routes_source = is_file($routes_file) ? file_get_contents($routes_file) : '';
$routes = youngo_subscriptions_ui_diag_route_map($routes_source);
$route_checks = array(
    'default_subscriptions_route' => isset($routes['subscriptions']) && $routes['subscriptions'] === 'home/subscriptions',
    'english_subscriptions_route' => isset($routes['en/subscriptions']) && $routes['en/subscriptions'] === 'home/subscriptions',
    'arabic_compat_subscriptions_route' => isset($routes['ar/subscriptions']) && $routes['ar/subscriptions'] === 'home/subscriptions',
);
youngo_subscriptions_ui_diag_print('Route checks', $route_checks);
foreach ($route_checks as $label => $ok) {
    youngo_subscriptions_ui_diag_assert($failures, $ok, 'Route check failed: ' . $label);
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
youngo_subscriptions_ui_diag_print('Forbidden localized route checks', $forbidden_routes);
youngo_subscriptions_ui_diag_assert($failures, empty($forbidden_routes), 'Admin/payment/API/write route aliases were localized.');

if (is_file($language_helper_file)) {
    require_once $language_helper_file;
}

$helper_checks = array();
if (function_exists('youngo_frontend_public_path') && function_exists('youngo_frontend_language_switch_path')) {
    $examples = array(
        'arabic_default_subscriptions' => array('actual' => youngo_frontend_public_path('subscriptions', 'arabic'), 'expected' => 'subscriptions'),
        'english_subscriptions' => array('actual' => youngo_frontend_public_path('subscriptions', 'english'), 'expected' => 'en/subscriptions'),
        'compat_subscriptions_canonical' => array('actual' => youngo_frontend_public_path('ar/subscriptions', 'arabic'), 'expected' => 'subscriptions'),
        'default_to_english_switch' => array('actual' => youngo_frontend_language_switch_path('english', 'subscriptions', false), 'expected' => 'en/subscriptions'),
        'english_to_arabic_switch' => array('actual' => youngo_frontend_language_switch_path('arabic', 'en/subscriptions', false), 'expected' => 'subscriptions'),
        'compat_to_arabic_switch' => array('actual' => youngo_frontend_language_switch_path('arabic', 'ar/subscriptions', false), 'expected' => 'subscriptions'),
        'payment_stays_unlocalized' => array('actual' => youngo_frontend_public_path('payment/paymob/webhook', 'english'), 'expected' => 'payment/paymob/webhook'),
        'admin_stays_unlocalized' => array('actual' => youngo_frontend_public_path('admin/dashboard', 'arabic'), 'expected' => 'admin/dashboard'),
    );

    foreach ($examples as $label => $check) {
        $check['ok'] = $check['actual'] === $check['expected'];
        $helper_checks[$label] = $check;
        youngo_subscriptions_ui_diag_assert($failures, $check['ok'], 'Helper check failed: ' . $label);
    }
} else {
    youngo_subscriptions_ui_diag_assert($failures, false, 'Frontend language helper functions are unavailable.');
}
youngo_subscriptions_ui_diag_print('Helper checks', $helper_checks);

$home_source = is_file($home_file) ? file_get_contents($home_file) : '';
$view_source = is_file($subscriptions_view_file) ? file_get_contents($subscriptions_view_file) : '';
$header_source = is_file($header_file) ? file_get_contents($header_file) : '';
$footer_source = is_file($footer_file) ? file_get_contents($footer_file) : '';
$css_source = is_file($css_file) ? file_get_contents($css_file) : '';

$controller_checks = array(
    'subscriptions_method_exists' => strpos($home_source, 'public function subscriptions()') !== false,
    'loads_subscription_model' => strpos($home_source, "load->model('Youngo_subscription_model'") !== false,
    'uses_public_model_method' => strpos($home_source, 'get_public_subscription_plans($youngo_frontend_language)') !== false,
    'sets_subscriptions_page_name' => strpos($home_source, "\$page_data['page_name'] = 'subscriptions';") !== false,
    'renders_frontend_index' => strpos($home_source, "load->view('frontend/' . get_frontend_settings('theme') . '/index'") !== false,
);
youngo_subscriptions_ui_diag_print('Controller checks', $controller_checks);
foreach ($controller_checks as $label => $ok) {
    youngo_subscriptions_ui_diag_assert($failures, $ok, 'Controller check failed: ' . $label);
}

$view_checks = array(
    'dynamic_plan_loop_exists' => strpos($view_source, 'foreach ($youngo_subscription_plans as $plan)') !== false,
    'uses_model_rows_variable' => strpos($view_source, '$subscription_plans') !== false,
    'empty_state_exists' => strpos($view_source, 'no_subscription_plans_available_yet') !== false,
    'price_display_rendered' => strpos($view_source, "['price_display']") !== false,
    'duration_label_rendered' => strpos($view_source, "['duration_label']") !== false,
    'featured_marker_rendered' => strpos($view_source, "['featured']") !== false,
    'contact_cta_only' => strpos($view_source, 'youngo_frontend_contact_url') !== false,
    'no_static_monthly_card' => preg_match('/Monthly|3 Months|Yearly|100\\.00|250\\.00|900\\.00/i', $view_source) !== 1,
);
youngo_subscriptions_ui_diag_print('View checks', $view_checks);
foreach ($view_checks as $label => $ok) {
    youngo_subscriptions_ui_diag_assert($failures, $ok, 'View check failed: ' . $label);
}

$navigation_checks = array(
    'header_uses_subscriptions_helper' => strpos($header_source, 'youngo_frontend_subscriptions_url') !== false,
    'header_renders_subscriptions_link' => strpos($header_source, "youngo_frontend_phrase('subscriptions") !== false
        || strpos($header_source, "youngo_frontend_phrase_e('subscriptions") !== false,
    'footer_uses_subscriptions_helper' => strpos($footer_source, 'youngo_frontend_subscriptions_url') !== false,
    'footer_renders_subscriptions_link' => strpos($footer_source, "youngo_frontend_phrase('subscriptions") !== false
        || strpos($footer_source, "youngo_frontend_phrase_e('subscriptions") !== false,
    'css_has_subscription_card_styles' => strpos($css_source, '.youngo-subscription-card') !== false,
);
youngo_subscriptions_ui_diag_print('Navigation/style checks', $navigation_checks);
foreach ($navigation_checks as $label => $ok) {
    youngo_subscriptions_ui_diag_assert($failures, $ok, 'Navigation/style check failed: ' . $label);
}

$rendered_url_sources = array(
    'subscriptions_view' => $view_source,
    'header' => $header_source,
    'footer' => $footer_source,
);
$forbidden_href_patterns = array(
    '#href\\s*=\\s*["\'][^"\']*(?:youngo/checkout|payment/paymob|home/course_payment|home/shopping_cart|checkout|order|enrol|grant)[^"\']*["\']#i',
    '#site_url\\s*\\(\\s*["\'](?:youngo/checkout|payment/paymob|home/course_payment|home/shopping_cart|checkout|order|enrol|grant)#i',
);
$forbidden_href_hits = array();
foreach ($rendered_url_sources as $label => $source) {
    foreach ($forbidden_href_patterns as $pattern) {
        if (preg_match_all($pattern, $source, $matches)) {
            foreach ($matches[0] as $match) {
                $forbidden_href_hits[] = array('source' => $label, 'match' => $match);
            }
        }
    }
}
youngo_subscriptions_ui_diag_print('Payment/CTA URL checks', $forbidden_href_hits);
youngo_subscriptions_ui_diag_assert($failures, empty($forbidden_href_hits), 'Payment/checkout/order/enrol/grant URL was added to public UI.');

$added_lines = youngo_subscriptions_ui_diag_added_source_lines($root);
$write_patterns = array(
    '/\\$this->db->(?:insert|update|delete)\\s*\\(/i',
    '/->insert\\s*\\(/i',
    '/->update\\s*\\(/i',
    '/->delete\\s*\\(/i',
    '/INSERT\\s+INTO/i',
    '/UPDATE\\s+[a-z_]/i',
    '/DELETE\\s+FROM/i',
);
$write_hits = array();
foreach ($added_lines as $line) {
    foreach ($write_patterns as $pattern) {
        if (preg_match($pattern, $line)) {
            $write_hits[] = $line;
            break;
        }
    }
}
youngo_subscriptions_ui_diag_print('DB write checks', array(
    'added_source_lines_checked' => count($added_lines),
    'write_hits' => $write_hits,
));
youngo_subscriptions_ui_diag_assert($failures, empty($write_hits), 'DB write behavior was added.');

$changed_files = youngo_subscriptions_ui_diag_changed_files($root);
$allowed_changed_files = array(
    'application/config/routes.php',
    'application/controllers/Home.php',
    'application/helpers/youngo_frontend_language_helper.php',
    'application/views/frontend/youngo/header.php',
    'application/views/frontend/youngo/footer.php',
    'application/views/frontend/youngo/subscriptions.php',
    'assets/frontend/youngo/css/youngo.css',
    'scripts/phase_2/youngo_subscriptions_page_dynamic_model_1_diagnostic.php',
    'scripts/phase_2/youngo_subscriptions_page_dynamic_ui_1_diagnostic.php',
    'scripts/phase_2/youngo_localization_ar_default_links_1_diagnostic.php',
    'docs/qa/youngo_subscriptions_page_dynamic_ui_1_report.md',
    'application/models/Youngo_subscription_model.php',
    'scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_schema_1_diagnostic.php',
    'scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_model_1_diagnostic.php',
    'docs/qa/youngo_dynamic_content_arabic_subscriptions_model_1_report.md',
);
$unexpected_changed_files = array_values(array_diff($changed_files, $allowed_changed_files));
youngo_subscriptions_ui_diag_print('Changed file scope', array(
    'changed_files' => $changed_files,
    'unexpected_changed_files' => $unexpected_changed_files,
));
youngo_subscriptions_ui_diag_assert($failures, empty($unexpected_changed_files), 'Unexpected files changed.');

if (!empty($failures)) {
    youngo_subscriptions_ui_diag_print('FAILURES', $failures);
    exit(1);
}

echo "\nSUBSCRIPTIONS.PAGE.DYNAMIC.UI.1 diagnostic passed.\n";
exit(0);
