<?php
/**
 * PAYMENT.CHECKOUT.ROUTE.SKELETON.1 diagnostic.
 *
 * Verifies disabled local YounGo checkout routes/controller/view skeletons.
 * No secrets, network calls, DB writes, order creation, entitlement issuance,
 * public checkout CTA exposure, or legacy payment/enrol writes.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', $root . '/application/');

$checks = array();
$details = array(
    'db_writes' => 'none',
    'network_requests' => 'none',
    'routes' => array(
        'youngo/checkout/start/(:num)',
        'youngo/checkout/order/(:any)',
        'youngo/checkout/return/(:any)',
        'youngo/checkout/status/(:any)',
    ),
);

function ypcrs1_add_check(&$checks, $name, $passed, $detail = '')
{
    $checks[$name] = array(
        'status' => $passed ? 'PASS' : 'FAIL',
        'detail' => $detail,
    );
}

function ypcrs1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypcrs1_contains_any($source, $needles)
{
    foreach ($needles as $needle) {
        if (stripos($source, $needle) !== false) {
            return true;
        }
    }

    return false;
}

function ypcrs1_load_config_value($path, $key)
{
    if (!is_file($path)) {
        return array();
    }

    $config = array();
    include $path;

    return isset($config[$key]) && is_array($config[$key]) ? $config[$key] : array();
}

function ypcrs1_run_php_script($root, $script)
{
    $command = 'php ' . escapeshellarg($root . '/' . $script);
    $output = array();
    $exit_code = 1;
    exec($command, $output, $exit_code);

    return array(
        'exit_code' => $exit_code,
        'last_line' => !empty($output) ? end($output) : '',
    );
}

$paths = array(
    'controller' => $root . '/application/controllers/Youngo_checkout.php',
    'routes' => $root . '/application/config/routes.php',
    'config_default' => $root . '/application/config/youngo_paymob.php',
    'config_example' => $root . '/application/config/youngo_paymob.local.example.php',
    'config_reader' => $root . '/application/libraries/Youngo_paymob_config.php',
    'adapter' => $root . '/application/libraries/Youngo_paymob_adapter.php',
    'disabled_view' => $root . '/application/views/frontend/youngo/checkout_disabled.php',
    'webhook_controller' => $root . '/application/controllers/Youngo_payment_webhook.php',
    'home_controller' => $root . '/application/controllers/Home.php',
    'course_page' => $root . '/application/views/frontend/youngo/course_page.php',
    'course_card' => $root . '/application/views/frontend/youngo/course_listing/course_card.php',
    'my_wishlist' => $root . '/application/views/frontend/youngo/my_wishlist.php',
    'wishlist_items' => $root . '/application/views/frontend/youngo/wishlist_items.php',
    'webhook_route_diagnostic' => $root . '/scripts/phase_2/youngo_payment_webhook_route_1_diagnostic.php',
);

foreach ($paths as $name => $path) {
    ypcrs1_add_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

$controller_source = ypcrs1_read($paths['controller']);
$routes_source = ypcrs1_read($paths['routes']);
$view_source = ypcrs1_read($paths['disabled_view']);
$reader_source = ypcrs1_read($paths['config_reader']);
$default_config = ypcrs1_load_config_value($paths['config_default'], 'youngo_paymob');
$example_config = ypcrs1_load_config_value($paths['config_example'], 'youngo_paymob_local');

$expected_routes = array(
    "\$route['youngo/checkout/start/(:num)'] = 'youngo_checkout/start/\$1';",
    "\$route['youngo/checkout/order/(:any)'] = 'youngo_checkout/order/\$1';",
    "\$route['youngo/checkout/return/(:any)'] = 'youngo_checkout/return/\$1';",
    "\$route['youngo/checkout/status/(:any)'] = 'youngo_checkout/status/\$1';",
);

foreach ($expected_routes as $index => $route_line) {
    ypcrs1_add_check($checks, 'route_exists_' . $index, strpos($routes_source, $route_line) !== false, $route_line);
}

ypcrs1_add_check(
    $checks,
    'controller_defines_expected_class_and_methods',
    strpos($controller_source, 'class Youngo_checkout extends CI_Controller') !== false
        && strpos($controller_source, 'public function start(') !== false
        && strpos($controller_source, 'public function start_course(') !== false
        && strpos($controller_source, 'public function order(') !== false
        && strpos($controller_source, 'public function return(') !== false
        && strpos($controller_source, 'public function status(') !== false
);

ypcrs1_add_check(
    $checks,
    'controller_reuses_config_and_adapter',
    strpos($controller_source, 'Youngo_paymob_config') !== false
        && strpos($controller_source, 'Youngo_paymob_adapter') !== false
);

ypcrs1_add_check(
    $checks,
    'controller_fails_closed_from_config_flags',
    strpos($controller_source, 'is_checkout_routes_enabled()') !== false
        && strpos($controller_source, 'is_checkout_local_testing_enabled()') !== false
        && strpos($controller_source, 'checkout_routes_disabled') !== false
        && strpos($controller_source, 'checkout_local_testing_disabled') !== false
);

ypcrs1_add_check(
    $checks,
    'controller_allows_order_creation_only_behind_local_flags',
    strpos($controller_source, 'create_or_reuse_draft_order') !== false
        && strpos($controller_source, 'checkout_routes_disabled') !== false
        && strpos($controller_source, 'checkout_local_testing_disabled') !== false
        && strpos($controller_source, 'checkout_local_testing_available') !== false
        && !ypcrs1_contains_any($controller_source, array('->insert(', '->update(', '->delete(', 'trans_begin', 'record_received_transaction', 'mark_paid', 'mark_failed_from'))
);

ypcrs1_add_check(
    $checks,
    'controller_has_no_paymob_network_calls',
    !ypcrs1_contains_any($controller_source, array('curl_exec', 'curl_init', 'fsockopen', 'stream_socket_client', 'GuzzleHttp', 'vendor/autoload', 'file_get_contents("http', "file_get_contents('http", 'accept.paymob.com'))
);

ypcrs1_add_check(
    $checks,
    'controller_has_no_entitlement_or_legacy_payment_writes',
    !ypcrs1_contains_any($controller_source, array('issue_paid_order_entitlement', 'issue_course_purchase_access', 'grant_course_access', 'grant_subscription', 'enrol_to_free_course', 'enrol_student', 'configure_course_payment', 'cart_items', 'home/course_payment'))
);

ypcrs1_add_check(
    $checks,
    'status_endpoint_returns_safe_json',
    strpos($controller_source, 'public function status(') !== false
        && strpos($controller_source, "set_content_type('application/json'") !== false
        && strpos($controller_source, 'json_encode($body') !== false
);

ypcrs1_add_check(
    $checks,
    'disabled_view_contains_no_payment_button_or_gateway_selection',
    strpos($view_source, 'Checkout is not enabled') !== false
        && strpos($view_source, 'No gateway is active') !== false
        && !ypcrs1_contains_any($view_source, array('<button', 'type="submit"', 'Buy Now', 'Add to cart', 'gateway selection', 'PAYMOB_SECRET', 'PAYMOB_HMAC'))
);

$flag_keys = array(
    'checkout_routes_enabled',
    'checkout_local_testing_enabled',
    'checkout_cta_enabled',
);

foreach ($flag_keys as $key) {
    ypcrs1_add_check(
        $checks,
        'default_flag_false_' . $key,
        array_key_exists($key, $default_config) && $default_config[$key] === false
    );
    ypcrs1_add_check(
        $checks,
        'example_flag_false_' . $key,
        array_key_exists($key, $example_config) && $example_config[$key] === false
    );
}

ypcrs1_add_check(
    $checks,
    'config_reader_exposes_checkout_flag_methods',
    strpos($reader_source, 'is_checkout_routes_enabled()') !== false
        && strpos($reader_source, 'is_checkout_local_testing_enabled()') !== false
        && strpos($reader_source, 'is_checkout_cta_enabled()') !== false
);

if (is_file($paths['config_reader'])) {
    require_once $paths['config_reader'];
    $reader = new Youngo_paymob_config(array('load_local_override' => false));
    $summary = $reader->get_safe_diagnostic_summary();
    $details['safe_config_summary'] = $summary;

    ypcrs1_add_check($checks, 'reader_checkout_routes_disabled', $reader->is_checkout_routes_enabled() === false);
    ypcrs1_add_check($checks, 'reader_checkout_local_testing_disabled', $reader->is_checkout_local_testing_enabled() === false);
    ypcrs1_add_check($checks, 'reader_checkout_cta_disabled', $reader->is_checkout_cta_enabled() === false);
    ypcrs1_add_check($checks, 'reader_default_payment_disabled', $reader->is_enabled() === false);
}

$public_cta_sources = array(
    'course_page' => ypcrs1_read($paths['course_page']),
    'course_card' => ypcrs1_read($paths['course_card']),
    'my_wishlist' => ypcrs1_read($paths['my_wishlist']),
    'wishlist_items' => ypcrs1_read($paths['wishlist_items']),
);

foreach ($public_cta_sources as $name => $source) {
    ypcrs1_add_check(
        $checks,
        'no_public_checkout_route_exposed_' . $name,
        strpos($source, 'youngo/checkout/start') === false && strpos($source, 'Youngo_checkout') === false
    );
}

ypcrs1_add_check(
    $checks,
    'existing_cta_boundaries_still_present',
    strpos($public_cta_sources['course_page'], '$youngo_is_managed_access') !== false
        && strpos($public_cta_sources['course_card'], '$youngo_card_is_managed_access') !== false
        && strpos($public_cta_sources['my_wishlist'], 'youngo_wishlist_course_boundary_state') !== false
        && strpos($public_cta_sources['wishlist_items'], 'youngo_wishlist_course_boundary_state') !== false
);

ypcrs1_add_check(
    $checks,
    'webhook_route_remains_fail_closed',
    strpos(ypcrs1_read($paths['webhook_controller']), 'webhook_testing_disabled') !== false
        && isset($default_config['webhook_testing_enabled'])
        && $default_config['webhook_testing_enabled'] === false
);

$webhook_route = ypcrs1_run_php_script($root, 'scripts/phase_2/youngo_payment_webhook_route_1_diagnostic.php');
$details['webhook_route_diagnostic_exit_code'] = $webhook_route['exit_code'];
ypcrs1_add_check($checks, 'webhook_route_diagnostic_still_passes', $webhook_route['exit_code'] === 0, $webhook_route['last_line']);

$phase_sources = $controller_source . "\n" . $view_source . "\n" . $reader_source . "\n" . ypcrs1_read($paths['config_default']) . "\n" . ypcrs1_read($paths['config_example']);
$network_hits = array();
foreach (array('curl_exec', 'curl_init', 'fsockopen', 'stream_socket_client', 'GuzzleHttp', 'vendor/autoload', 'file_get_contents("http', "file_get_contents('http", 'accept.paymob.com') as $pattern) {
    if (stripos($phase_sources, $pattern) !== false) {
        $network_hits[] = $pattern;
    }
}
$details['phase_network_pattern_hits'] = $network_hits;
ypcrs1_add_check($checks, 'no_paymob_network_call_code_in_phase_files', empty($network_hits), implode(',', $network_hits));

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

$result = array(
    'phase' => 'PAYMENT.CHECKOUT.ROUTE.SKELETON.1',
    'status' => empty($failed) ? 'PASS' : 'FAIL',
    'checks' => $checks,
    'details' => $details,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
