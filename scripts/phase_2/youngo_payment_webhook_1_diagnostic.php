<?php
/**
 * PAYMENT.WEBHOOK.1 diagnostic.
 *
 * Verifies Paymob webhook/HMAC skeleton behavior using fake fixtures only.
 * No secrets, network calls, DB writes, or entitlement issuance.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', $root . '/application/');

$checks = array();
$details = array(
    'db_writes' => 'none',
    'network_requests' => 'none',
    'route_controller_decision' => 'library_validation_with_optional_disabled_route_in_later_phase',
);

function ypw1_add_check(&$checks, $name, $passed, $detail = '')
{
    $checks[$name] = array(
        'status' => $passed ? 'PASS' : 'FAIL',
        'detail' => $detail,
    );
}

function ypw1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypw1_load_json($path)
{
    if (!is_file($path)) {
        return null;
    }

    $data = json_decode(file_get_contents($path), true);
    return json_last_error() === JSON_ERROR_NONE ? $data : null;
}

function ypw1_db_config($root)
{
    defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');

    $db = array();
    $active_group = 'default';
    require $root . '/application/config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypw1_connect($config)
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

function ypw1_scalar($mysqli, $sql)
{
    $result = $mysqli->query($sql);
    if (!$result) {
        return null;
    }

    $row = $result->fetch_row();
    return $row ? $row[0] : null;
}

function ypw1_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    return $row && (int) $row[0] > 0;
}

function ypw1_table_count($mysqli, $table)
{
    if (!ypw1_table_exists($mysqli, $table)) {
        return null;
    }

    return (int) ypw1_scalar($mysqli, 'SELECT COUNT(*) FROM `' . $mysqli->real_escape_string($table) . '`');
}

function ypw1_counts($mysqli, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ypw1_table_count($mysqli, $table);
    }

    return $counts;
}

function ypw1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ypw1_has_network_code($source)
{
    $patterns = array(
        'curl_exec',
        'curl_init',
        'file_get_contents("http',
        "file_get_contents('http",
        'fsockopen',
        'stream_socket_client',
        'GuzzleHttp',
        'vendor/autoload',
    );

    foreach ($patterns as $pattern) {
        if (stripos($source, $pattern) !== false) {
            return true;
        }
    }

    return false;
}

function ypw1_file_contains($path, $needle)
{
    return is_file($path) && strpos(file_get_contents($path), $needle) !== false;
}

$paths = array(
    'webhook_library' => $root . '/application/libraries/Youngo_paymob_webhook.php',
    'config_reader' => $root . '/application/libraries/Youngo_paymob_config.php',
    'adapter' => $root . '/application/libraries/Youngo_paymob_adapter.php',
    'order_model' => $root . '/application/models/Youngo_checkout_model.php',
    'valid_fixture' => $root . '/scripts/phase_2/fixtures/paymob/valid_shape_success_payload.json',
    'missing_hmac_fixture' => $root . '/scripts/phase_2/fixtures/paymob/missing_hmac_payload.json',
    'invalid_shape_fixture' => $root . '/scripts/phase_2/fixtures/paymob/invalid_shape_payload.json',
    'routes' => $root . '/application/config/routes.php',
    'home_controller' => $root . '/application/controllers/Home.php',
    'course_page' => $root . '/application/views/frontend/youngo/course_page.php',
    'course_card' => $root . '/application/views/frontend/youngo/course_listing/course_card.php',
    'my_wishlist' => $root . '/application/views/frontend/youngo/my_wishlist.php',
    'wishlist_items' => $root . '/application/views/frontend/youngo/wishlist_items.php',
);

foreach ($paths as $name => $path) {
    ypw1_add_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

require_once $paths['config_reader'];
require_once $paths['webhook_library'];

$diagnostic_secret = 'youngo-paymob-webhook-diagnostic-secret';
$webhook = new Youngo_paymob_webhook(array(
    'diagnostic_hmac_secret' => $diagnostic_secret,
    'config_reader' => new Youngo_paymob_config(array('load_local_override' => false)),
));
$webhook_without_secret = new Youngo_paymob_webhook(array(
    'config_reader' => new Youngo_paymob_config(array('load_local_override' => false)),
));

ypw1_add_check($checks, 'webhook_library_loads', $webhook instanceof Youngo_paymob_webhook);

$valid_payload = ypw1_load_json($paths['valid_fixture']);
$missing_hmac_payload = ypw1_load_json($paths['missing_hmac_fixture']);
$invalid_shape_payload = ypw1_load_json($paths['invalid_shape_fixture']);

ypw1_add_check($checks, 'valid_fixture_loads', is_array($valid_payload));
ypw1_add_check($checks, 'missing_hmac_fixture_loads', is_array($missing_hmac_payload));
ypw1_add_check($checks, 'invalid_shape_fixture_loads', is_array($invalid_shape_payload));

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

$mysqli = ypw1_connect(ypw1_db_config($root));
$before_counts = array();
if (!$mysqli) {
    ypw1_add_check($checks, 'db_connect_read_only', false, 'connect_failed');
} else {
    ypw1_add_check($checks, 'db_connect_read_only', true, 'connected');
    $before_counts = ypw1_counts($mysqli, $protected_tables);
    $details['protected_counts_before'] = $before_counts;
}

if (is_array($valid_payload)) {
    $normalized = $webhook->normalize_payload($valid_payload);
    $source = $webhook->get_hmac_source_fields($valid_payload);
    $calculated = $webhook->calculate_hmac($valid_payload, $diagnostic_secret);
    $verified = $webhook->verify_hmac($valid_payload, isset($valid_payload['hmac']) ? $valid_payload['hmac'] : '');
    $classification = $webhook->classify_event($valid_payload);
    $refs = $webhook->extract_gateway_refs($valid_payload);
    $summary = $webhook->get_safe_payload_summary($valid_payload);
    $missing_secret = $webhook_without_secret->verify_hmac($valid_payload, isset($valid_payload['hmac']) ? $valid_payload['hmac'] : '');

    $details['valid_fixture_summary'] = !empty($summary['data']) ? $summary['data'] : array();
    $details['valid_fixture_event_status'] = !empty($classification['data']['status']) ? $classification['data']['status'] : null;
    $details['valid_fixture_refs'] = !empty($refs['data']) ? $refs['data'] : array();

    ypw1_add_check($checks, 'normalization_works', !empty($normalized['ok']) && !empty($normalized['data']['payload']));
    ypw1_add_check($checks, 'hmac_source_fields_built', !empty($source['ok']) && isset($source['data']['source_string']) && $source['data']['source_string'] !== '');
    ypw1_add_check($checks, 'fake_valid_fixture_hmac_calculates', !empty($calculated['ok']) && isset($calculated['data']['hmac']) && $calculated['data']['hmac'] === $valid_payload['hmac']);
    ypw1_add_check($checks, 'fake_valid_fixture_hmac_verifies_with_fake_secret', !empty($verified['ok']) && $verified['code'] === 'hmac_verified');
    ypw1_add_check($checks, 'missing_hmac_secret_fails_closed', empty($missing_secret['ok']) && $missing_secret['code'] === 'missing_hmac_secret');
    ypw1_add_check($checks, 'event_classifies_success', !empty($classification['ok']) && $classification['data']['status'] === 'success');
    ypw1_add_check($checks, 'gateway_refs_extract', !empty($refs['ok']) && $refs['data']['provider_transaction_id'] === '987654321' && $refs['data']['provider_order_id'] === '24681012');

    $summary_json = json_encode($summary);
    ypw1_add_check($checks, 'safe_summary_redacts_hmac_and_pan', !empty($summary['ok']) && strpos($summary_json, $valid_payload['hmac']) === false && strpos($summary_json, '424242******4242') === false && strpos($summary_json, 'configured_redacted') !== false && strpos($summary_json, 'redacted_4242') !== false);
}

if (is_array($missing_hmac_payload)) {
    $missing_hmac = $webhook->verify_hmac($missing_hmac_payload, isset($missing_hmac_payload['hmac']) ? $missing_hmac_payload['hmac'] : '');
    ypw1_add_check($checks, 'missing_hmac_fails_closed', empty($missing_hmac['ok']) && $missing_hmac['code'] === 'missing_hmac');
}

if (is_array($invalid_shape_payload)) {
    $invalid_shape = $webhook->get_hmac_source_fields($invalid_shape_payload);
    ypw1_add_check($checks, 'invalid_shape_rejected', empty($invalid_shape['ok']) && $invalid_shape['code'] === 'payload_shape_invalid');
}

$webhook_source = ypw1_read($paths['webhook_library']);
ypw1_add_check($checks, 'webhook_library_has_no_network_code', !ypw1_has_network_code($webhook_source));
ypw1_add_check($checks, 'webhook_library_has_no_db_writes', stripos($webhook_source, 'insert(') === false && stripos($webhook_source, 'update(') === false && stripos($webhook_source, 'delete(') === false && stripos($webhook_source, '$this->db') === false);

$routes_source = ypw1_read($paths['routes']);
ypw1_add_check(
    $checks,
    'paymob_webhook_route_absent_or_disabled_controller_only',
    strpos($routes_source, 'payment/paymob/webhook') === false
        || strpos($routes_source, "\$route['payment/paymob/webhook'] = 'youngo_payment_webhook/paymob';") !== false
);

ypw1_add_check(
    $checks,
    'home_blocks_legacy_checkout_paths',
    ypw1_file_contains($paths['home_controller'], 'youngo_remove_managed_access_courses_from_cart')
        && ypw1_file_contains($paths['home_controller'], 'Subscription checkout is not available yet')
        && ypw1_file_contains($paths['home_controller'], 'Coupon-based checkout is not available yet')
);

ypw1_add_check(
    $checks,
    'youngo_course_cta_boundaries_still_present',
    ypw1_file_contains($paths['course_page'], '$youngo_is_managed_access')
        && ypw1_file_contains($paths['course_card'], '$youngo_card_is_managed_access')
        && ypw1_file_contains($paths['my_wishlist'], 'youngo_wishlist_course_boundary_state')
        && ypw1_file_contains($paths['wishlist_items'], 'youngo_wishlist_course_boundary_state')
);

if ($mysqli) {
    $after_counts = ypw1_counts($mysqli, $protected_tables);
    $details['protected_counts_after'] = $after_counts;
    ypw1_add_check($checks, 'protected_counts_unchanged', ypw1_counts_match($before_counts, $after_counts));
    ypw1_add_check($checks, 'no_payment_entitlement_enrol_rows_created', $after_counts['payment'] === $before_counts['payment'] && $after_counts['enrol'] === $before_counts['enrol'] && $after_counts['youngo_course_access'] === $before_counts['youngo_course_access'] && $after_counts['youngo_user_subscriptions'] === $before_counts['youngo_user_subscriptions']);
}

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[] = $name;
    }
}

$result = array(
    'phase' => 'PAYMENT.WEBHOOK.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
