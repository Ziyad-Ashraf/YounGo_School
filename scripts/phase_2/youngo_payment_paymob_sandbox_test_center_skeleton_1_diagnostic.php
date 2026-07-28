<?php
/**
 * PAYMENT.PAYMOB.SANDBOX.TEST.CENTER.SKELETON.1 diagnostic.
 *
 * Verifies the read-only Paymob Test Center skeleton. No secrets, network
 * calls, checkout orders, payment rows, enrol rows, or entitlement writes.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', $root . '/application/');

$checks = array();
$details = array(
    'db_writes' => 'none',
    'network_requests' => 'none',
    'phase' => 'PAYMENT.PAYMOB.SANDBOX.TEST.CENTER.SKELETON.1',
);

function yppstc1_add_check(&$checks, $name, $passed, $detail = '')
{
    $checks[$name] = array(
        'status' => $passed ? 'PASS' : 'FAIL',
        'detail' => $detail,
    );
}

function yppstc1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function yppstc1_contains_all($source, $needles)
{
    foreach ($needles as $needle) {
        if (strpos($source, $needle) === false) {
            return false;
        }
    }

    return true;
}

function yppstc1_contains_any($source, $needles)
{
    foreach ($needles as $needle) {
        if (stripos($source, $needle) !== false) {
            return true;
        }
    }

    return false;
}

function yppstc1_load_config_value($path, $key)
{
    if (!is_file($path)) {
        return array();
    }

    $config = array();
    include $path;

    return isset($config[$key]) && is_array($config[$key]) ? $config[$key] : array();
}

function yppstc1_db_config($root)
{
    defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');

    $db = array();
    $active_group = 'default';
    require $root . '/application/config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function yppstc1_connect($config)
{
    $host = isset($config['hostname']) ? $config['hostname'] : 'localhost';
    $port = null;
    if (strpos($host, ':') !== false && substr_count($host, ':') === 1) {
        list($host, $port) = explode(':', $host, 2);
        $port = (int) $port;
    }

    $mysqli = @new mysqli(
        $host,
        isset($config['username']) ? $config['username'] : '',
        isset($config['password']) ? $config['password'] : '',
        isset($config['database']) ? $config['database'] : '',
        $port ?: null
    );

    if ($mysqli->connect_errno) {
        return null;
    }

    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

function yppstc1_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    return $row && (int) $row[0] > 0;
}

function yppstc1_scalar($mysqli, $sql)
{
    $result = $mysqli->query($sql);
    if (!$result) {
        return null;
    }

    $row = $result->fetch_row();
    return $row ? $row[0] : null;
}

function yppstc1_table_count($mysqli, $table)
{
    if (!yppstc1_table_exists($mysqli, $table)) {
        return null;
    }

    return (int) yppstc1_scalar($mysqli, 'SELECT COUNT(*) FROM `' . $mysqli->real_escape_string($table) . '`');
}

function yppstc1_counts($mysqli, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = yppstc1_table_count($mysqli, $table);
    }

    return $counts;
}

function yppstc1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

$paths = array(
    'settings_controller' => $root . '/application/controllers/Youngo_payment_settings.php',
    'test_center_view' => $root . '/application/views/backend/admin/youngo_payment_test_center.php',
    'settings_view' => $root . '/application/views/backend/admin/youngo_payment_settings.php',
    'routes' => $root . '/application/config/routes.php',
    'return_controller' => $root . '/application/controllers/Youngo_payment_return.php',
    'return_view' => $root . '/application/views/frontend/youngo/payment_return_disabled.php',
    'webhook_controller' => $root . '/application/controllers/Youngo_payment_webhook.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
    'paymob_adapter' => $root . '/application/libraries/Youngo_paymob_adapter.php',
    'payment_config_model' => $root . '/application/models/Youngo_payment_config_model.php',
    'course_page' => $root . '/application/views/frontend/youngo/course_page.php',
    'course_card' => $root . '/application/views/frontend/youngo/course_listing/course_card.php',
    'my_wishlist' => $root . '/application/views/frontend/youngo/my_wishlist.php',
    'wishlist_items' => $root . '/application/views/frontend/youngo/wishlist_items.php',
);

foreach ($paths as $name => $path) {
    yppstc1_add_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

$settings_controller = yppstc1_read($paths['settings_controller']);
$test_center_view = yppstc1_read($paths['test_center_view']);
$settings_view = yppstc1_read($paths['settings_view']);
$routes_source = yppstc1_read($paths['routes']);
$return_controller = yppstc1_read($paths['return_controller']);
$return_view = yppstc1_read($paths['return_view']);
$webhook_controller = yppstc1_read($paths['webhook_controller']);
$paymob_config_source = yppstc1_read($paths['paymob_config']);
$adapter_source = yppstc1_read($paths['paymob_adapter']);
$model_source = yppstc1_read($paths['payment_config_model']);
$default_config = yppstc1_load_config_value($paths['paymob_config'], 'youngo_paymob');

yppstc1_add_check(
    $checks,
    'test_center_route_exists',
    strpos($routes_source, "\$route['admin/youngo/payment-settings/test'] = 'youngo_payment_settings/test';") !== false
);

yppstc1_add_check(
    $checks,
    'root_admin_guard_exists',
    strpos($settings_controller, '$this->require_root_admin();') !== false
        && strpos($settings_controller, 'public function test()') !== false
        && strpos($settings_controller, 'youngo_is_root_admin') !== false
);

yppstc1_add_check(
    $checks,
    'test_center_view_has_readiness_checks',
    yppstc1_contains_all($settings_controller . "\n" . $test_center_view, array(
        'data-youngo-paymob-test-center="true"',
        'Readiness Checks',
        'API key',
        'Public key',
        'Secret key',
        'HMAC secret',
        'Card integration ID',
        'Mobile wallet integration ID',
        'API base URL',
        'Checkout base URL',
        'Return URL',
        'Notification URL',
        'Payment execution',
        'Network execution',
        'Checkout CTA',
        'Live mode',
    ))
);

yppstc1_add_check(
    $checks,
    'readiness_checks_redacted',
    !yppstc1_contains_any($test_center_view, array(
        'encrypted_api_key',
        'encrypted_public_key',
        'encrypted_secret_key',
        'encrypted_hmac_secret',
        'youngo_security.local.php',
        'PAYMOB_SECRET_KEY',
        'PAYMOB_HMAC_SECRET',
        'Authorization: Token',
    ))
);

yppstc1_add_check(
    $checks,
    'test_buttons_disabled_nonfunctional',
    substr_count($test_center_view, 'data-youngo-paymob-test-action-disabled="true"') === 1
        && strpos($test_center_view, 'disabled aria-disabled="true"') !== false
        && strpos($test_center_view, 'Check config readiness') !== false
        && strpos($test_center_view, 'Test card sandbox intention') !== false
        && strpos($test_center_view, 'Test wallet sandbox intention') !== false
        && strpos($test_center_view, 'Test webhook/HMAC callback') !== false
        && strpos($test_center_view, 'Test return URL') !== false
        && strpos($test_center_view, 'Available after deployment to HTTPS domain and explicit sandbox execution enablement.') !== false
        && strpos($test_center_view, '<form') === false
);

yppstc1_add_check(
    $checks,
    'production_upload_checklist_exists',
    yppstc1_contains_all($test_center_view, array(
        'Upload latest code',
        'Apply DB migrations',
        'Create server encryption key file',
        'Enter Paymob credentials in dashboard',
        'Enter card/wallet integration IDs',
        'Update return_url to production domain',
        'Update notification_url to production domain',
        'Keep sandbox mode first',
        'Run Test Center sandbox tests',
        'Enable public checkout only after successful sandbox QA',
    ))
);

yppstc1_add_check(
    $checks,
    'settings_page_links_test_center',
    strpos($settings_view, "site_url('admin/youngo/payment-settings/test')") !== false
        && strpos($settings_view, 'data-youngo-paymob-test-center-link="true"') !== false
);

yppstc1_add_check(
    $checks,
    'return_route_exists_and_disabled',
    strpos($routes_source, "\$route['payment/paymob/return'] = 'youngo_payment_return/paymob';") !== false
        && strpos($return_controller, 'paymob_return_disabled_no_write') !== false
        && strpos($return_controller, "'query_params_trusted' => false") !== false
        && strpos($return_controller, "'marks_paid' => false") !== false
        && strpos($return_controller, "'issues_access' => false") !== false
        && strpos($return_view, 'data-youngo-paymob-return-disabled="true"') !== false
);

yppstc1_add_check(
    $checks,
    'webhook_route_fail_closed',
    strpos($routes_source, "\$route['payment/paymob/webhook'] = 'youngo_payment_webhook/paymob';") !== false
        && strpos($webhook_controller, 'webhook_testing_disabled') !== false
        && strpos($webhook_controller, "if (\$this->request_method() !== 'POST')") !== false
);

yppstc1_add_check(
    $checks,
    'tracked_gates_disabled',
    isset($default_config['enabled'], $default_config['network_enabled'], $default_config['sandbox_network_testing_enabled'], $default_config['checkout_cta_enabled'], $default_config['live_mode_allowed'])
        && $default_config['enabled'] === false
        && $default_config['network_enabled'] === false
        && $default_config['sandbox_network_testing_enabled'] === false
        && $default_config['checkout_cta_enabled'] === false
        && $default_config['live_mode_allowed'] === false
);

yppstc1_add_check(
    $checks,
    'test_center_does_not_enable_paymob_network_calls',
    strpos($settings_controller, 'create_sandbox_intention(') === false
        && strpos($test_center_view, 'create_sandbox_intention') === false
        && yppstc1_contains_all($adapter_source, array('get_sandbox_readiness()', 'create_sandbox_intention('))
        && strpos($test_center_view, 'accept.paymob.com') === false
);

yppstc1_add_check(
    $checks,
    'model_still_blocks_activation_gates',
    yppstc1_contains_all($model_source, array(
        "'enabled'",
        "'network_enabled'",
        "'sandbox_network_testing_enabled'",
        "'checkout_cta_enabled'",
        "'live_mode_allowed'",
        'Private values and activation gates cannot be saved in this phase.',
    ))
);

$phase_sources = $settings_controller . "\n" . $test_center_view . "\n" . $return_controller . "\n" . $return_view;
yppstc1_add_check(
    $checks,
    'new_phase_files_have_no_legacy_payment_or_enrol_writes',
    !yppstc1_contains_any($phase_sources, array(
        'payment_gateways',
        "insert('payment'",
        'insert("payment"',
        "->insert('enrol'",
        '->insert("enrol"',
        'enrol_student',
        'issue_paid_order_entitlement',
        'issue_course_purchase_access',
        'grant_course_access',
        'grant_subscription',
        'mark_paid',
    ))
);

$public_sources = array(
    'course_card' => yppstc1_read($paths['course_card']),
    'my_wishlist' => yppstc1_read($paths['my_wishlist']),
    'wishlist_items' => yppstc1_read($paths['wishlist_items']),
);

foreach ($public_sources as $name => $source) {
    yppstc1_add_check(
        $checks,
        'no_static_checkout_start_link_' . $name,
        strpos($source, 'youngo/checkout/start') === false
            && strpos($source, 'data-youngo-checkout-cta') === false
    );
}

yppstc1_add_check(
    $checks,
    'course_detail_checkout_cta_remains_helper_gated',
    strpos(yppstc1_read($paths['course_page']), 'youngo_checkout_cta_decision') !== false
        && strpos(yppstc1_read($paths['course_page']), "site_url('youngo/checkout/start") === false
);

$protected_tables = array(
    'youngo_checkout_orders',
    'youngo_payment_transactions',
    'youngo_course_access',
    'youngo_user_subscriptions',
    'youngo_manual_grants',
    'youngo_coupon_usages',
    'payment',
    'enrol',
);

$mysqli = yppstc1_connect(yppstc1_db_config($root));
if (!$mysqli) {
    yppstc1_add_check($checks, 'db_connect_read_only', false, 'connect_failed');
} else {
    yppstc1_add_check($checks, 'db_connect_read_only', true, 'connected');
    $before_counts = yppstc1_counts($mysqli, $protected_tables);
    $details['protected_counts_before'] = $before_counts;

    $gate_count = null;
    if (yppstc1_table_exists($mysqli, 'youngo_payment_provider_configs')) {
        $gate_count = (int) yppstc1_scalar($mysqli, 'SELECT COALESCE(SUM(enabled + network_enabled + sandbox_network_testing_enabled + webhook_testing_enabled + checkout_routes_enabled + checkout_local_testing_enabled + checkout_cta_enabled + live_mode_allowed + transaction_inquiry_enabled), 0) FROM youngo_payment_provider_configs');
    }
    $details['enabled_gate_count'] = $gate_count;
    yppstc1_add_check($checks, 'db_payment_gates_remain_disabled', $gate_count === 0 || $gate_count === null, 'gate_count=' . var_export($gate_count, true));

    $after_counts = yppstc1_counts($mysqli, $protected_tables);
    $details['protected_counts_after'] = $after_counts;
    yppstc1_add_check($checks, 'protected_counts_unchanged', yppstc1_counts_match($before_counts, $after_counts));
    yppstc1_add_check(
        $checks,
        'no_payment_entitlement_enrol_rows_created',
        $after_counts['payment'] === $before_counts['payment']
            && $after_counts['enrol'] === $before_counts['enrol']
            && $after_counts['youngo_course_access'] === $before_counts['youngo_course_access']
            && $after_counts['youngo_checkout_orders'] === $before_counts['youngo_checkout_orders']
    );
}

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

$result = array(
    'phase' => 'PAYMENT.PAYMOB.SANDBOX.TEST.CENTER.SKELETON.1',
    'status' => empty($failed) ? 'PASS' : 'FAIL',
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
