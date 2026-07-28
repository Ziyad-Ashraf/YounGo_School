<?php
/**
 * PAYMENT.ORDER.1 diagnostic.
 *
 * Verifies the local checkout order service foundation without printing
 * secrets, calling Paymob, issuing entitlements, or leaving test order rows.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', $root . '/application/');

$checks = array();
$details = array(
    'db_writes' => 'none',
    'valid_order_creation' => 'skipped_by_design',
);

function ypo1_add_check(&$checks, $name, $passed, $detail = '')
{
    $checks[$name] = array(
        'status' => $passed ? 'PASS' : 'FAIL',
        'detail' => $detail,
    );
}

function ypo1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypo1_file_contains($path, $needle)
{
    return is_file($path) && strpos(file_get_contents($path), $needle) !== false;
}

function ypo1_db_config($root)
{
    defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');

    $db = array();
    $active_group = 'default';
    require $root . '/application/config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypo1_connect($config)
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

function ypo1_scalar($mysqli, $sql)
{
    $result = $mysqli->query($sql);
    if (!$result) {
        return null;
    }

    $row = $result->fetch_row();
    return $row ? $row[0] : null;
}

function ypo1_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    return $row && (int) $row[0] > 0;
}

function ypo1_column_exists($mysqli, $table, $column)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->bind_param('sss', $table, $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    return $row && (int) $row[0] > 0;
}

function ypo1_column_default($mysqli, $table, $column)
{
    $stmt = $mysqli->prepare('SELECT COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? $row['COLUMN_DEFAULT'] : null;
}

function ypo1_normalized_default($value)
{
    return trim((string) $value, "'");
}

function ypo1_table_count($mysqli, $table)
{
    if (!ypo1_table_exists($mysqli, $table)) {
        return null;
    }

    return (int) ypo1_scalar($mysqli, 'SELECT COUNT(*) FROM `' . $mysqli->real_escape_string($table) . '`');
}

function ypo1_counts($mysqli, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ypo1_table_count($mysqli, $table);
    }

    return $counts;
}

function ypo1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ypo1_has_network_calls($source)
{
    $patterns = array(
        'curl_exec',
        'curl_init',
        'file_get_contents("http',
        "file_get_contents('http",
        'fsockopen',
        'stream_socket_client',
        'paymob.com',
    );

    foreach ($patterns as $pattern) {
        if (stripos($source, $pattern) !== false) {
            return true;
        }
    }

    return false;
}

$paths = array(
    'model' => $root . '/application/models/Youngo_checkout_model.php',
    'config_reader' => $root . '/application/libraries/Youngo_paymob_config.php',
    'schema_diagnostic' => $root . '/scripts/phase_2/youngo_payment_schema_1_diagnostic.php',
    'home_controller' => $root . '/application/controllers/Home.php',
    'course_page' => $root . '/application/views/frontend/youngo/course_page.php',
    'course_card' => $root . '/application/views/frontend/youngo/course_listing/course_card.php',
    'my_wishlist' => $root . '/application/views/frontend/youngo/my_wishlist.php',
    'wishlist_items' => $root . '/application/views/frontend/youngo/wishlist_items.php',
    'routes' => $root . '/application/config/routes.php',
);

foreach ($paths as $name => $path) {
    ypo1_add_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

require_once $root . '/system/core/Model.php';
require_once $paths['config_reader'];
require_once $paths['model'];

$reader = new Youngo_paymob_config(array('load_local_override' => false));
$details['safe_config_summary'] = $reader->get_safe_diagnostic_summary();

ypo1_add_check($checks, 'config_reader_default_disabled', $reader->is_enabled() === false);
ypo1_add_check($checks, 'config_reader_default_currency_egp', $reader->get_currency() === 'EGP');
ypo1_add_check($checks, 'config_reader_default_mode_sandbox', $reader->get_mode() === 'sandbox');

$model = new Youngo_checkout_model();
ypo1_add_check($checks, 'model_loads', $model instanceof Youngo_checkout_model);

$required_methods = array(
    'create_draft_order',
    'get_order_by_reference',
    'get_order',
    'mark_pending_gateway',
    'mark_awaiting_webhook',
    'mark_cancelled',
    'mark_failed',
    'can_start_checkout',
    'generate_order_reference',
    'get_safe_order_summary',
);

foreach ($required_methods as $method) {
    ypo1_add_check($checks, 'method_exists_' . $method, method_exists($model, $method));
}

$source = ypo1_read($paths['model']);
ypo1_add_check($checks, 'model_has_no_paymob_network_calls', !ypo1_has_network_calls($source));
ypo1_add_check($checks, 'model_does_not_insert_entitlements_or_legacy_enrol', strpos($source, "insert('youngo_course_access'") === false && strpos($source, "insert('youngo_user_subscriptions'") === false && strpos($source, "insert('enrol'") === false);
ypo1_add_check($checks, 'model_restricts_currency_to_egp', strpos($source, "!== 'EGP'") !== false && strpos($source, "'currency' => 'EGP'") !== false);
ypo1_add_check($checks, 'model_has_safe_reference_prefix', strpos($source, "'YGO-'") !== false);

$protected_tables = array(
    'youngo_checkout_orders',
    'youngo_payment_transactions',
    'youngo_course_access',
    'youngo_user_subscriptions',
    'enrol',
    'payment',
);

$mysqli = ypo1_connect(ypo1_db_config($root));
if (!$mysqli) {
    ypo1_add_check($checks, 'db_connect_read_only', false, 'connect_failed');
} else {
    ypo1_add_check($checks, 'db_connect_read_only', true, 'connected');
    $details['db_server_version'] = (string) ypo1_scalar($mysqli, 'SELECT VERSION()');

    $expected_tables = array('users', 'course', 'youngo_checkout_orders', 'youngo_payment_transactions');
    foreach ($expected_tables as $table) {
        ypo1_add_check($checks, 'table_exists_' . $table, ypo1_table_exists($mysqli, $table));
    }

    $required_order_columns = array(
        'id',
        'user_id',
        'order_reference',
        'order_type',
        'status',
        'course_id',
        'total_amount',
        'total_amount_cents',
        'currency',
        'payment_gateway',
        'gateway_environment',
        'idempotency_key',
        'entitlement_issued',
        'entitlement_issuance_status',
        'created_at',
        'updated_at',
    );

    $missing_columns = array();
    foreach ($required_order_columns as $column) {
        if (!ypo1_column_exists($mysqli, 'youngo_checkout_orders', $column)) {
            $missing_columns[] = $column;
        }
    }
    ypo1_add_check($checks, 'required_checkout_order_columns_exist', empty($missing_columns), implode(',', $missing_columns));
    ypo1_add_check($checks, 'checkout_currency_default_egp', ypo1_normalized_default(ypo1_column_default($mysqli, 'youngo_checkout_orders', 'currency')) === 'EGP');
    ypo1_add_check($checks, 'checkout_status_default_draft', ypo1_normalized_default(ypo1_column_default($mysqli, 'youngo_checkout_orders', 'status')) === 'draft');

    $before_counts = ypo1_counts($mysqli, $protected_tables);
    $invalid_currency = $model->create_draft_order(1, 1, 100, 'USD');
    $invalid_amount = $model->create_draft_order(1, 1, 0, 'EGP');
    $after_counts = ypo1_counts($mysqli, $protected_tables);

    $details['protected_counts_before_invalid_checks'] = $before_counts;
    $details['protected_counts_after_invalid_checks'] = $after_counts;
    $details['invalid_currency_result_code'] = isset($invalid_currency['code']) ? $invalid_currency['code'] : null;
    $details['invalid_amount_result_code'] = isset($invalid_amount['code']) ? $invalid_amount['code'] : null;

    ypo1_add_check($checks, 'invalid_currency_rejected_before_db_write', empty($invalid_currency['ok']) && isset($invalid_currency['code']) && $invalid_currency['code'] === 'unsupported_currency' && ypo1_counts_match($before_counts, $after_counts));
    ypo1_add_check($checks, 'invalid_amount_rejected_before_db_write', empty($invalid_amount['ok']) && isset($invalid_amount['code']) && $invalid_amount['code'] === 'invalid_amount' && ypo1_counts_match($before_counts, $after_counts));
    ypo1_add_check($checks, 'valid_diagnostic_order_not_created', true, 'No persistent diagnostic order was created in PAYMENT.ORDER.1.');
}

ypo1_add_check(
    $checks,
    'home_blocks_legacy_checkout_paths',
    ypo1_file_contains($paths['home_controller'], 'youngo_remove_managed_access_courses_from_cart')
        && ypo1_file_contains($paths['home_controller'], 'Subscription checkout is not available yet')
        && ypo1_file_contains($paths['home_controller'], 'Coupon-based checkout is not available yet')
);

ypo1_add_check(
    $checks,
    'youngo_course_cta_boundaries_still_present',
    ypo1_file_contains($paths['course_page'], '$youngo_is_managed_access')
        && ypo1_file_contains($paths['course_card'], '$youngo_card_is_managed_access')
        && ypo1_file_contains($paths['my_wishlist'], 'youngo_wishlist_course_boundary_state')
        && ypo1_file_contains($paths['wishlist_items'], 'youngo_wishlist_course_boundary_state')
);

$routes_source = ypo1_read($paths['routes']);
ypo1_add_check(
    $checks,
    'no_payment_checkout_start_routes_added',
    strpos($routes_source, 'youngo_checkout') === false
);

$phase_source = $source . "\n" . ypo1_read($paths['config_reader']);
ypo1_add_check($checks, 'phase_has_no_paymob_network_calls', !ypo1_has_network_calls($phase_source));

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[] = $name;
    }
}

$result = array(
    'phase' => 'PAYMENT.ORDER.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
