<?php
/**
 * PAYMENT.WEBHOOK.ROUTE.1 diagnostic.
 *
 * Verifies the disabled Paymob webhook route/controller skeleton.
 * No secrets, network calls, DB writes, entitlement issuance, or order updates.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', $root . '/application/');

$checks = array();
$details = array(
    'db_writes' => 'none',
    'network_requests' => 'none',
    'route' => 'payment/paymob/webhook',
);

function ypwr1_add_check(&$checks, $name, $passed, $detail = '')
{
    $checks[$name] = array(
        'status' => $passed ? 'PASS' : 'FAIL',
        'detail' => $detail,
    );
}

function ypwr1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypwr1_db_config($root)
{
    defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');

    $db = array();
    $active_group = 'default';
    require $root . '/application/config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypwr1_connect($config)
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

function ypwr1_scalar($mysqli, $sql)
{
    $result = $mysqli->query($sql);
    if (!$result) {
        return null;
    }

    $row = $result->fetch_row();
    return $row ? $row[0] : null;
}

function ypwr1_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    return $row && (int) $row[0] > 0;
}

function ypwr1_table_count($mysqli, $table)
{
    if (!ypwr1_table_exists($mysqli, $table)) {
        return null;
    }

    return (int) ypwr1_scalar($mysqli, 'SELECT COUNT(*) FROM `' . $mysqli->real_escape_string($table) . '`');
}

function ypwr1_counts($mysqli, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ypwr1_table_count($mysqli, $table);
    }

    return $counts;
}

function ypwr1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ypwr1_contains_any($source, $needles)
{
    foreach ($needles as $needle) {
        if (stripos($source, $needle) !== false) {
            return true;
        }
    }

    return false;
}

function ypwr1_run_php_script($root, $script)
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
    'controller' => $root . '/application/controllers/Youngo_payment_webhook.php',
    'routes' => $root . '/application/config/routes.php',
    'config_default' => $root . '/application/config/youngo_paymob.php',
    'config_reader' => $root . '/application/libraries/Youngo_paymob_config.php',
    'webhook_library' => $root . '/application/libraries/Youngo_paymob_webhook.php',
    'adapter' => $root . '/application/libraries/Youngo_paymob_adapter.php',
    'order_model' => $root . '/application/models/Youngo_checkout_model.php',
    'home_controller' => $root . '/application/controllers/Home.php',
    'course_page' => $root . '/application/views/frontend/youngo/course_page.php',
    'course_card' => $root . '/application/views/frontend/youngo/course_listing/course_card.php',
    'my_wishlist' => $root . '/application/views/frontend/youngo/my_wishlist.php',
    'wishlist_items' => $root . '/application/views/frontend/youngo/wishlist_items.php',
    'webhook_1_diagnostic' => $root . '/scripts/phase_2/youngo_payment_webhook_1_diagnostic.php',
);

foreach ($paths as $name => $path) {
    ypwr1_add_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

$controller_source = ypwr1_read($paths['controller']);
$routes_source = ypwr1_read($paths['routes']);
$config_source = ypwr1_read($paths['config_default']);

ypwr1_add_check(
    $checks,
    'route_exists',
    strpos($routes_source, "\$route['payment/paymob/webhook'] = 'youngo_payment_webhook/paymob';") !== false,
    'payment/paymob/webhook -> youngo_payment_webhook/paymob'
);

ypwr1_add_check(
    $checks,
    'controller_defines_expected_class_and_method',
    strpos($controller_source, 'class Youngo_payment_webhook extends CI_Controller') !== false
        && strpos($controller_source, 'public function paymob()') !== false
);

ypwr1_add_check(
    $checks,
    'controller_reads_raw_json_input_only',
    strpos($controller_source, "file_get_contents('php://input')") !== false
        && strpos($controller_source, 'json_decode($raw_input, true)') !== false
        && strpos($controller_source, 'MAX_PAYLOAD_BYTES') !== false
);

ypwr1_add_check(
    $checks,
    'controller_post_only',
    strpos($controller_source, "request_method() !== 'POST'") !== false
        && strpos($controller_source, 'method_not_allowed') !== false
);

ypwr1_add_check(
    $checks,
    'controller_fails_closed_by_default',
    strpos($controller_source, "get('webhook_testing_enabled', false)") !== false
        && strpos($controller_source, 'webhook_testing_disabled') !== false
        && strpos($config_source, "'webhook_testing_enabled' => false") !== false
);

ypwr1_add_check(
    $checks,
    'controller_uses_webhook_library_for_validation',
    strpos($controller_source, 'Youngo_paymob_webhook') !== false
        && strpos($controller_source, 'verify_hmac(') !== false
        && strpos($controller_source, 'get_safe_payload_summary(') !== false
);

ypwr1_add_check(
    $checks,
    'controller_does_not_use_session',
    !ypwr1_contains_any($controller_source, array('$this->session', 'userdata(', 'set_flashdata(', 'payment_details', 'redirect('))
);

ypwr1_add_check(
    $checks,
    'controller_has_no_db_write_or_order_update_code',
    !ypwr1_contains_any($controller_source, array('$this->db', '->insert(', '->update(', '->delete(', 'trans_begin', 'mark_pending_gateway', 'mark_awaiting_webhook', 'mark_failed', 'mark_cancelled', 'mark_paid'))
);

ypwr1_add_check(
    $checks,
    'controller_has_no_entitlement_or_legacy_enrol_issuance',
    !ypwr1_contains_any($controller_source, array('grant_course_access', 'grant_subscription', 'issue_course_purchase', 'issue_subscription', 'enrol_student', 'course_purchase'))
);

ypwr1_add_check(
    $checks,
    'controller_has_no_paymob_network_calls',
    !ypwr1_contains_any($controller_source, array('curl_exec', 'curl_init', 'file_get_contents("http', "file_get_contents('http", 'fsockopen', 'stream_socket_client', 'GuzzleHttp', 'vendor/autoload'))
);

ypwr1_add_check(
    $checks,
    'controller_safe_json_responses',
    strpos($controller_source, "set_content_type('application/json'") !== false
        && strpos($controller_source, 'json_encode($body') !== false
        && strpos($controller_source, 'set_status_header') !== false
);

$cta_markers = array(
    'home_younggo_boundary' => array($paths['home_controller'], 'youngo_remove_managed_access_courses_from_cart'),
    'course_page_boundary' => array($paths['course_page'], '$youngo_is_managed_access'),
    'course_card_boundary' => array($paths['course_card'], '$youngo_card_is_managed_access'),
    'wishlist_boundary' => array($paths['my_wishlist'], 'youngo_wishlist_course_boundary_state'),
    'wishlist_items_boundary' => array($paths['wishlist_items'], 'youngo_wishlist_course_boundary_state'),
);

foreach ($cta_markers as $name => $marker) {
    ypwr1_add_check($checks, 'public_cta_boundary_marker_' . $name, strpos(ypwr1_read($marker[0]), $marker[1]) !== false, $marker[1]);
}

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

$mysqli = ypwr1_connect(ypwr1_db_config($root));
$before_counts = array();
if (!$mysqli) {
    ypwr1_add_check($checks, 'db_connect_read_only', false, 'connect_failed');
} else {
    ypwr1_add_check($checks, 'db_connect_read_only', true, 'connected');
    $before_counts = ypwr1_counts($mysqli, $protected_tables);
    $details['protected_counts_before'] = $before_counts;
}

$webhook_1 = ypwr1_run_php_script($root, 'scripts/phase_2/youngo_payment_webhook_1_diagnostic.php');
$details['webhook_1_diagnostic_exit_code'] = $webhook_1['exit_code'];
ypwr1_add_check($checks, 'webhook_1_diagnostic_still_passes', $webhook_1['exit_code'] === 0, $webhook_1['last_line']);

if ($mysqli) {
    $after_counts = ypwr1_counts($mysqli, $protected_tables);
    $details['protected_counts_after'] = $after_counts;
    ypwr1_add_check($checks, 'protected_table_counts_unchanged', ypwr1_counts_match($before_counts, $after_counts), json_encode($after_counts));
}

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

$result = array(
    'phase' => 'PAYMENT.WEBHOOK.ROUTE.1',
    'status' => empty($failed) ? 'PASS' : 'FAIL',
    'checks' => $checks,
    'details' => $details,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
