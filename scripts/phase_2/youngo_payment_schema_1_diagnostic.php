<?php
/**
 * PAYMENT.SCHEMA.1 read-only diagnostic.
 *
 * Verifies the local YounGo payment schema foundation without printing
 * credentials and without modifying the database.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
$checks = array();
$details = array();

function yps1_add_check(&$checks, $name, $passed, $detail = '')
{
    $checks[$name] = array(
        'status' => $passed ? 'PASS' : 'FAIL',
        'detail' => $detail,
    );
}

function yps1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function yps1_db_config($root)
{
    define('BASEPATH', $root . DIRECTORY_SEPARATOR);
    defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');

    $db = array();
    $active_group = 'default';
    require $root . '/application/config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function yps1_connect($config)
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

function yps1_scalar($mysqli, $sql)
{
    $result = $mysqli->query($sql);
    if (!$result) {
        return null;
    }

    $row = $result->fetch_row();
    return $row ? $row[0] : null;
}

function yps1_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    return $row && (int) $row[0] > 0;
}

function yps1_column_default($mysqli, $table, $column)
{
    $stmt = $mysqli->prepare('SELECT COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? $row['COLUMN_DEFAULT'] : null;
}

function yps1_column_exists($mysqli, $table, $column)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    return $row && (int) $row[0] > 0;
}

function yps1_index_info($mysqli, $table, $index)
{
    $stmt = $mysqli->prepare('SELECT INDEX_NAME, NON_UNIQUE, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ",") AS columns_csv FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? GROUP BY INDEX_NAME, NON_UNIQUE');
    $stmt->bind_param('ss', $table, $index);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function yps1_index_matches($mysqli, $table, $index, $columns, $unique = null)
{
    $info = yps1_index_info($mysqli, $table, $index);
    if (!$info) {
        return false;
    }

    if ($info['columns_csv'] !== implode(',', $columns)) {
        return false;
    }

    if ($unique !== null && ((int) $info['NON_UNIQUE'] === 0) !== $unique) {
        return false;
    }

    return true;
}

function yps1_file_contains($path, $needle)
{
    return is_file($path) && strpos(file_get_contents($path), $needle) !== false;
}

$required_files = array(
    'proposed_sql' => $root . '/scripts/phase_2/payment_config_2_youngo_payment_schema_proposed.sql',
    'down_sql' => $root . '/scripts/phase_2/payment_schema_1_down.sql',
    'home_controller' => $root . '/application/controllers/Home.php',
    'checkout_controller' => $root . '/application/controllers/Youngo_checkout.php',
    'course_page' => $root . '/application/views/frontend/youngo/course_page.php',
    'course_card' => $root . '/application/views/frontend/youngo/course_listing/course_card.php',
    'my_wishlist' => $root . '/application/views/frontend/youngo/my_wishlist.php',
    'wishlist_items' => $root . '/application/views/frontend/youngo/wishlist_items.php',
    'routes' => $root . '/application/config/routes.php',
);

foreach ($required_files as $name => $path) {
    yps1_add_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

$sql_source = yps1_read($required_files['proposed_sql']);
$down_source = yps1_read($required_files['down_sql']);
$sql_without_comments = preg_replace('/--.*$/m', '', $sql_source);

yps1_add_check($checks, 'proposed_sql_not_executed_marker_present', strpos($sql_source, 'STATUS: NOT EXECUTED') !== false);
yps1_add_check($checks, 'proposed_sql_no_destructive_data_statements', !preg_match('/\b(DROP|TRUNCATE|DELETE|UPDATE|INSERT)\b/i', $sql_without_comments));
yps1_add_check($checks, 'proposed_sql_no_urls', !preg_match('#https?://#i', $sql_without_comments));
yps1_add_check($checks, 'proposed_sql_egp_defaults_present', strpos($sql_source, "DEFAULT 'EGP'") !== false);
yps1_add_check($checks, 'rollback_sql_created_unexecuted_marker_present', strpos($down_source, 'ROLLBACK SQL FOR LOCAL PAYMENT.SCHEMA.1 ONLY') !== false && strpos($down_source, 'NOT EXECUTED IN THIS PHASE') !== false);

yps1_add_check(
    $checks,
    'home_blocks_legacy_checkout_paths',
    yps1_file_contains($required_files['home_controller'], 'youngo_remove_managed_access_courses_from_cart')
        && yps1_file_contains($required_files['home_controller'], 'Subscription checkout is not available yet')
        && yps1_file_contains($required_files['home_controller'], 'Coupon-based checkout is not available yet')
);

yps1_add_check(
    $checks,
    'youngo_course_cta_boundaries_still_present',
    yps1_file_contains($required_files['course_page'], '$youngo_is_managed_access')
        && yps1_file_contains($required_files['course_card'], '$youngo_card_is_managed_access')
        && yps1_file_contains($required_files['my_wishlist'], 'youngo_wishlist_course_boundary_state')
        && yps1_file_contains($required_files['wishlist_items'], 'youngo_wishlist_course_boundary_state')
);

$routes_source = yps1_read($required_files['routes']);
$checkout_controller_source = yps1_read($required_files['checkout_controller']);
$has_checkout_routes = strpos($routes_source, 'youngo_checkout') !== false;
yps1_add_check(
    $checks,
    'checkout_routes_absent_or_disabled_skeleton_only',
    !$has_checkout_routes
        || (
            (
                strpos($routes_source, "\$route['youngo/checkout/start/(:num)'] = 'youngo_checkout/start_course/\$1';") !== false
                || strpos($routes_source, "\$route['youngo/checkout/start/(:num)'] = 'youngo_checkout/start/\$1';") !== false
            )
            && strpos($routes_source, "\$route['youngo/checkout/order/(:any)'] = 'youngo_checkout/order/\$1';") !== false
            && strpos($routes_source, "\$route['youngo/checkout/return/(:any)'] = 'youngo_checkout/return/\$1';") !== false
            && strpos($routes_source, "\$route['youngo/checkout/status/(:any)'] = 'youngo_checkout/status/\$1';") !== false
            && strpos($checkout_controller_source, 'checkout_routes_disabled') !== false
            && strpos($checkout_controller_source, 'checkout_local_testing_available') !== false
            && strpos($checkout_controller_source, 'paymob_network_must_remain_disabled') !== false
            && strpos($checkout_controller_source, 'configure_course_payment') === false
        )
);

$mysqli = yps1_connect(yps1_db_config($root));
if (!$mysqli) {
    yps1_add_check($checks, 'db_connect_read_only', false, 'connect_failed');
} else {
    yps1_add_check($checks, 'db_connect_read_only', true, 'connected');
    $version = (string) yps1_scalar($mysqli, 'SELECT VERSION()');
    $details['db_server_version'] = $version;
    yps1_add_check($checks, 'mariadb_10_4_detected', stripos($version, 'mariadb') !== false && version_compare(preg_replace('/[^0-9.].*$/', '', $version), '10.4', '>='));

    $expected_tables = array(
        'youngo_checkout_orders',
        'youngo_payment_transactions',
        'youngo_course_access',
        'youngo_user_subscriptions',
        'youngo_coupon_usages',
    );

    foreach ($expected_tables as $table) {
        yps1_add_check($checks, 'table_exists_' . $table, yps1_table_exists($mysqli, $table));
    }

    $checkout_columns = array(
        'order_reference',
        'total_amount_cents',
        'gateway_environment',
        'provider_order_id',
        'idempotency_key',
        'last_hmac_verified',
        'entitlement_issued',
        'entitlement_issuance_status',
        'entitlement_course_access_id',
        'entitlement_subscription_id',
        'entitlement_issued_at',
        'entitlement_issuance_error',
        'failure_code',
        'failure_message',
        'payment_started_at',
        'return_seen_at',
        'last_webhook_at',
        'paid_at',
        'failed_at',
        'cancelled_at',
        'expired_at',
    );

    $missing_checkout_columns = array();
    foreach ($checkout_columns as $column) {
        if (!yps1_column_exists($mysqli, 'youngo_checkout_orders', $column)) {
            $missing_checkout_columns[] = $column;
        }
    }
    yps1_add_check($checks, 'checkout_columns_present', empty($missing_checkout_columns), implode(',', $missing_checkout_columns));

    $transaction_columns = array(
        'checkout_order_id',
        'user_id',
        'order_reference',
        'gateway_provider',
        'gateway_environment',
        'event_type',
        'status',
        'gateway_status',
        'currency',
        'amount_cents',
        'amount_decimal',
        'provider_intent_id',
        'provider_order_id',
        'provider_transaction_id',
        'provider_integration_id',
        'merchant_order_reference',
        'hmac_received',
        'hmac_verified',
        'verification_source',
        'idempotency_key',
        'payload_hash',
        'raw_payload_redacted',
        'error_code',
        'error_message',
        'received_at',
        'verified_at',
        'reconciled_at',
        'processed_at',
        'created_at',
        'updated_at',
    );

    $missing_transaction_columns = array();
    foreach ($transaction_columns as $column) {
        if (!yps1_column_exists($mysqli, 'youngo_payment_transactions', $column)) {
            $missing_transaction_columns[] = $column;
        }
    }
    yps1_add_check($checks, 'payment_transaction_columns_present', empty($missing_transaction_columns), implode(',', $missing_transaction_columns));

    yps1_add_check($checks, 'checkout_currency_default_egp', trim((string) yps1_column_default($mysqli, 'youngo_checkout_orders', 'currency'), "'") === 'EGP');
    yps1_add_check($checks, 'checkout_status_default_draft', trim((string) yps1_column_default($mysqli, 'youngo_checkout_orders', 'status'), "'") === 'draft');
    yps1_add_check($checks, 'transaction_currency_default_egp', trim((string) yps1_column_default($mysqli, 'youngo_payment_transactions', 'currency'), "'") === 'EGP');
    yps1_add_check($checks, 'transaction_status_default_received', trim((string) yps1_column_default($mysqli, 'youngo_payment_transactions', 'status'), "'") === 'received');

    $index_checks = array(
        'yco_unique_order_reference' => array('youngo_checkout_orders', 'uniq_yco_order_reference', array('order_reference'), true),
        'yco_unique_idempotency_key' => array('youngo_checkout_orders', 'uniq_yco_idempotency_key', array('idempotency_key'), true),
        'yco_provider_order' => array('youngo_checkout_orders', 'idx_yco_provider_order', array('provider_order_id'), false),
        'yco_provider_transaction' => array('youngo_checkout_orders', 'idx_yco_provider_transaction', array('provider_transaction_id'), false),
        'yco_gateway_env_status' => array('youngo_checkout_orders', 'idx_yco_gateway_env_status', array('payment_gateway', 'gateway_environment', 'status'), false),
        'yco_entitlement_status' => array('youngo_checkout_orders', 'idx_yco_entitlement_status', array('entitlement_issuance_status', 'entitlement_issued'), false),
        'ypt_unique_idempotency_key' => array('youngo_payment_transactions', 'uniq_ypt_idempotency_key', array('idempotency_key'), true),
        'ypt_unique_provider_tx_event' => array('youngo_payment_transactions', 'uniq_ypt_provider_tx_event', array('gateway_provider', 'gateway_environment', 'provider_transaction_id', 'event_type'), true),
        'yca_unique_checkout_order' => array('youngo_course_access', 'uniq_yca_checkout_order', array('checkout_order_id'), true),
        'yca_checkout_payment' => array('youngo_course_access', 'idx_yca_checkout_payment', array('checkout_order_id', 'payment_id'), false),
        'yca_user_course_source_status' => array('youngo_course_access', 'idx_yca_user_course_source_status', array('user_id', 'course_id', 'access_source', 'status'), false),
        'yus_unique_checkout_order' => array('youngo_user_subscriptions', 'uniq_yus_checkout_order', array('checkout_order_id'), true),
        'yus_checkout_payment' => array('youngo_user_subscriptions', 'idx_yus_checkout_payment', array('checkout_order_id', 'payment_id'), false),
        'yus_user_source_status' => array('youngo_user_subscriptions', 'idx_yus_user_source_status', array('user_id', 'source', 'status'), false),
        'ycu_unique_checkout_order' => array('youngo_coupon_usages', 'uniq_ycu_checkout_order', array('checkout_order_id'), true),
        'ycu_user_coupon_order' => array('youngo_coupon_usages', 'idx_ycu_user_coupon_order', array('user_id', 'coupon_id', 'checkout_order_id'), false),
    );

    foreach ($index_checks as $name => $spec) {
        yps1_add_check($checks, 'index_' . $name, yps1_index_matches($mysqli, $spec[0], $spec[1], $spec[2], $spec[3]));
    }

    if (yps1_table_exists($mysqli, 'payment_gateways')) {
        $gateway_total = (int) yps1_scalar($mysqli, 'SELECT COUNT(*) FROM payment_gateways');
        $gateway_active = (int) yps1_scalar($mysqli, 'SELECT COUNT(*) FROM payment_gateways WHERE status = 1');
        $gateway_active_egp = (int) yps1_scalar($mysqli, "SELECT COUNT(*) FROM payment_gateways WHERE status = 1 AND UPPER(COALESCE(currency, '')) = 'EGP'");
        $gateway_active_non_egp = (int) yps1_scalar($mysqli, "SELECT COUNT(*) FROM payment_gateways WHERE status = 1 AND UPPER(COALESCE(currency, '')) <> 'EGP'");

        $details['legacy_gateway_total'] = $gateway_total;
        $details['legacy_gateway_active'] = $gateway_active;
        $details['legacy_gateway_active_egp'] = $gateway_active_egp;
        $details['legacy_gateway_active_non_egp'] = $gateway_active_non_egp;

        yps1_add_check(
            $checks,
            'legacy_gateway_currency_state_unchanged',
            $gateway_active === 15 && $gateway_active_non_egp === 15 && $gateway_active_egp === 0,
            'active=' . $gateway_active . ', active_non_egp=' . $gateway_active_non_egp . ', active_egp=' . $gateway_active_egp
        );
    } else {
        yps1_add_check($checks, 'legacy_gateway_currency_state_unchanged', false, 'payment_gateways_missing');
    }

    $protected_counts = array();
    foreach (array('payment', 'enrol', 'youngo_checkout_orders', 'youngo_payment_transactions', 'youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants', 'youngo_coupon_usages', 'watch_histories', 'watched_duration') as $table) {
        if (yps1_table_exists($mysqli, $table)) {
            $protected_counts[$table] = (int) yps1_scalar($mysqli, 'SELECT COUNT(*) FROM `' . $table . '`');
        } else {
            $protected_counts[$table] = 'MISSING';
        }
    }
    $details['protected_table_counts'] = $protected_counts;

    yps1_add_check($checks, 'checkout_and_transaction_rows_still_empty', $protected_counts['youngo_checkout_orders'] === 0 && $protected_counts['youngo_payment_transactions'] === 0);
    yps1_add_check($checks, 'entitlement_payment_coupon_rows_still_clean', $protected_counts['payment'] === 0 && $protected_counts['youngo_course_access'] === 0 && $protected_counts['youngo_user_subscriptions'] === 0 && $protected_counts['youngo_manual_grants'] === 0 && $protected_counts['youngo_coupon_usages'] === 0);
}

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[] = $name;
    }
}

$result = array(
    'phase' => 'PAYMENT.SCHEMA.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
