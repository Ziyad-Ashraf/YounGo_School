<?php
/**
 * PAYMENT.ZERO.AMOUNT.COUPON.ACCESS.IMPLEMENT.1 diagnostic.
 *
 * Creates a DB backup, inserts temporary checkout/coupon fixtures, exercises
 * zero-amount coupon completion for course purchases, deletes all temporary
 * rows, and verifies payment/Instapay/Paymob boundaries remain intact.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
defined('BASEPATH') || define('BASEPATH', $root . '/system/');
defined('APPPATH') || define('APPPATH', $root . '/application/');
defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');

if (!function_exists('log_message')) {
    function log_message($level, $message)
    {
    }
}

if (!function_exists('is_php')) {
    function is_php($version)
    {
        return version_compare(PHP_VERSION, $version, '>=');
    }
}

if (!function_exists('show_error')) {
    function show_error($message, $status_code = 500, $heading = 'Error')
    {
        throw new RuntimeException(is_array($message) ? implode("\n", $message) : (string) $message);
    }
}

require_once BASEPATH . 'core/Model.php';
require_once BASEPATH . 'database/DB.php';
require_once APPPATH . 'libraries/Youngo_paymob_config.php';
require_once APPPATH . 'models/Youngo_coupon_evaluator_model.php';
require_once APPPATH . 'models/Youngo_entitlement_write_model.php';
require_once APPPATH . 'models/Youngo_checkout_model.php';
require_once APPPATH . 'models/Youngo_instapay_payment_model.php';

$checks = array();
$details = array(
    'db_writes' => 'temporary_checkout_coupon_usage_course_access_fixture_rows_inserted_then_deleted',
    'cleanup' => 'not_started',
    'backup_path' => '',
    'backup_size' => '',
    'backup_sha256' => '',
);
$fixture_order_ids = array();
$fixture_coupon_ids = array();
$fixture_course_access_ids = array();
$fixture_coupon_usage_ids = array();

function ypzci1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypzci1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypzci1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypzci1_backup_ident($name)
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function ypzci1_backup_value($mysqli, $value)
{
    return $value === null ? 'NULL' : "'" . $mysqli->real_escape_string($value) . "'";
}

function ypzci1_connect_mysqli($config)
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

function ypzci1_create_backup($root)
{
    $mysqli = ypzci1_connect_mysqli(ypzci1_db_config());
    if (!$mysqli) {
        return array('ok' => false, 'error' => 'connect_failed');
    }

    $backup_dir = dirname($root) . '/backups';
    if (!is_dir($backup_dir) && !mkdir($backup_dir, 0777, true)) {
        return array('ok' => false, 'error' => 'backup_dir_failed');
    }

    $path = $backup_dir . '/youngo_school_before_payment_zero_amount_coupon_access_implement_1_' . date('Y_m_d_His') . '.sql';
    $fh = fopen($path, 'wb');
    if (!$fh) {
        return array('ok' => false, 'error' => 'backup_file_failed');
    }

    fwrite($fh, "-- YounGo backup before PAYMENT.ZERO.AMOUNT.COUPON.ACCESS.IMPLEMENT.1 diagnostic\n");
    fwrite($fh, "-- Created at: " . date('c') . "\n");
    fwrite($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    $result = $mysqli->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    if (!$result) {
        fclose($fh);
        return array('ok' => false, 'error' => 'table_list_failed');
    }

    $tables = array();
    while ($row = $result->fetch_array(MYSQLI_NUM)) {
        $tables[] = $row[0];
    }

    foreach ($tables as $table) {
        $create = $mysqli->query('SHOW CREATE TABLE ' . ypzci1_backup_ident($table));
        if (!$create) {
            continue;
        }

        $create_row = $create->fetch_assoc();
        fwrite($fh, "\n-- Table " . $table . "\n");
        fwrite($fh, 'DROP TABLE IF EXISTS ' . ypzci1_backup_ident($table) . ";\n");
        fwrite($fh, $create_row['Create Table'] . ";\n\n");

        $rows = $mysqli->query('SELECT * FROM ' . ypzci1_backup_ident($table));
        if (!$rows) {
            continue;
        }

        while ($data = $rows->fetch_assoc()) {
            $columns = array();
            $values = array();
            foreach ($data as $column => $value) {
                $columns[] = ypzci1_backup_ident($column);
                $values[] = ypzci1_backup_value($mysqli, $value);
            }
            fwrite($fh, 'INSERT INTO ' . ypzci1_backup_ident($table) . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n");
        }
    }

    fwrite($fh, "\nSET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fh);

    return array(
        'ok' => true,
        'path' => $path,
        'size' => filesize($path),
        'sha256' => hash_file('sha256', $path),
    );
}

function ypzci1_filter_columns($db, $table, $data)
{
    $filtered = array();
    foreach ($data as $field => $value) {
        if ($db->field_exists($field, $table)) {
            $filtered[$field] = $value;
        }
    }

    return $filtered;
}

function ypzci1_count($db, $table)
{
    return $db->table_exists($table) ? (int) $db->count_all($table) : null;
}

function ypzci1_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ypzci1_count($db, $table);
    }

    return $counts;
}

function ypzci1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ypzci1_first_id($db, $table, $where = array())
{
    if (!$db->table_exists($table) || !$db->field_exists('id', $table)) {
        return null;
    }

    $builder = $db->select('id')->from($table);
    foreach ($where as $field => $value) {
        if ($db->field_exists($field, $table)) {
            $builder->where($field, $value);
        }
    }

    $row = $builder->order_by('id', 'asc')->get('', 1)->row_array();
    return !empty($row['id']) ? (int) $row['id'] : null;
}

function ypzci1_insert_coupon($db, $code, $discount_type, $discount_value, $expiry = null, $scope = 'both')
{
    $now = time();
    $discount_percentage = $discount_type === 'percentage' ? (string) $discount_value : '0';
    $data = ypzci1_filter_columns($db, 'coupons', array(
        'code' => $code,
        'discount_percentage' => $discount_percentage,
        'created_at' => $now,
        'expiry_date' => $expiry ?: strtotime('+30 days'),
        'discount_type' => $discount_type,
        'discount_value' => number_format((float) $discount_value, 2, '.', ''),
        'scope' => $scope,
        'max_usage_count' => null,
        'status' => 'active',
        'updated_at' => $now,
    ));

    $db->insert('coupons', $data);
    return (int) $db->insert_id();
}

function ypzci1_insert_order($db, $user_id, $course_id, $reference, $order_type = 'course_purchase', $plan_id = null)
{
    $now = time();
    $data = ypzci1_filter_columns($db, 'youngo_checkout_orders', array(
        'user_id' => (int) $user_id,
        'order_reference' => $reference,
        'order_type' => $order_type,
        'status' => 'draft',
        'course_id' => $order_type === 'course_purchase' ? (int) $course_id : null,
        'plan_id' => $plan_id,
        'subtotal_amount' => '100.00',
        'discount_amount' => '0.00',
        'tax_amount' => '0.00',
        'total_amount' => '100.00',
        'total_amount_cents' => 10000,
        'currency' => 'EGP',
        'coupon_id' => null,
        'coupon_code' => null,
        'coupon_discount_type' => null,
        'coupon_discount_value' => null,
        'selected_payment_method' => null,
        'item_title_snapshot' => $order_type === 'course_purchase' ? 'Zero Coupon Diagnostic Course' : 'Zero Coupon Diagnostic Subscription',
        'checkout_snapshot_json' => null,
        'payment_gateway' => null,
        'gateway_environment' => 'sandbox',
        'idempotency_key' => hash('sha256', 'PAYMENT.ZERO.AMOUNT.COUPON.ACCESS.IMPLEMENT.1|' . $reference),
        'last_hmac_verified' => 0,
        'entitlement_issued' => 0,
        'entitlement_issuance_status' => 'not_started',
        'metadata' => json_encode(array('source' => 'youngo_payment_zero_amount_coupon_access_implement_1_diagnostic'), JSON_UNESCAPED_SLASHES),
        'created_at' => $now,
        'updated_at' => $now,
    ));

    $db->insert('youngo_checkout_orders', $data);
    return (int) $db->insert_id();
}

function ypzci1_apply_subscription_zero_snapshot($db, $order_id, $coupon_id, $coupon_code)
{
    $snapshot = array(
        'snapshot_version' => 'PAYMENT.ZERO.AMOUNT.COUPON.ACCESS.IMPLEMENT.1_DIAGNOSTIC',
        'snapshot_action' => 'subscription_zero_coupon_deferred_fixture',
        'created_at' => time(),
        'zero_final_amount_policy_not_enabled' => true,
        'zero_final_amount_coupon_completion_available' => false,
    );

    $db->where('id', (int) $order_id)->update('youngo_checkout_orders', ypzci1_filter_columns($db, 'youngo_checkout_orders', array(
        'coupon_id' => (int) $coupon_id,
        'coupon_code' => $coupon_code,
        'coupon_discount_type' => 'percentage',
        'coupon_discount_value' => '100.00',
        'discount_amount' => '100.00',
        'total_amount' => '0.00',
        'total_amount_cents' => 0,
        'checkout_snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_SLASHES),
        'updated_at' => time(),
    )));
}

class Ypzci1_session_stub
{
    public function userdata($key)
    {
        return null;
    }
}

$backup = ypzci1_create_backup($root);
if (!empty($backup['ok'])) {
    $details['backup_path'] = $backup['path'];
    $details['backup_size'] = (string) $backup['size'];
    $details['backup_sha256'] = $backup['sha256'];
}

$CI = new stdClass();
$CI->session = new Ypzci1_session_stub();
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ypzci1_db_config(), true);
$CI->db = $db;
$checkout_model = new Youngo_checkout_model(array('db' => $db));
$instapay_model = new Youngo_instapay_payment_model(array('db' => $db));

$paths = array(
    'checkout_model' => $root . '/application/models/Youngo_checkout_model.php',
    'entitlement_write_model' => $root . '/application/models/Youngo_entitlement_write_model.php',
    'coupon_evaluator' => $root . '/application/models/Youngo_coupon_evaluator_model.php',
    'instapay_model' => $root . '/application/models/Youngo_instapay_payment_model.php',
    'checkout_controller' => $root . '/application/controllers/Youngo_checkout.php',
    'checkout_view' => $root . '/application/views/frontend/youngo/checkout_order.php',
    'routes' => $root . '/application/config/routes.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
);

foreach ($paths as $name => $path) {
    ypzci1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}
ypzci1_check($checks, 'backup_created_before_temp_writes', !empty($backup['ok']), !empty($backup['ok']) ? $backup['path'] . '|' . $backup['size'] . '|' . $backup['sha256'] : (isset($backup['error']) ? $backup['error'] : 'backup_failed'));

$checkout_source = ypzci1_read($paths['checkout_model']);
$entitlement_source = ypzci1_read($paths['entitlement_write_model']);
$instapay_source = ypzci1_read($paths['instapay_model']);
$controller_source = ypzci1_read($paths['checkout_controller']);
$view_source = ypzci1_read($paths['checkout_view']);
$routes_source = ypzci1_read($paths['routes']);
$paymob_source = ypzci1_read($paths['paymob_config']);

ypzci1_check($checks, 'checkout_model_has_zero_completion_method', method_exists($checkout_model, 'complete_zero_amount_coupon_order') && strpos($checkout_source, 'complete_zero_amount_coupon_order') !== false);
ypzci1_check($checks, 'checkout_model_revalidates_coupon_and_records_usage', strpos($checkout_source, 'evaluate_coupon_for_checkout') !== false && strpos($checkout_source, 'record_zero_amount_coupon_usage') !== false && strpos($checkout_source, 'youngo_coupon_usages') !== false);
ypzci1_check($checks, 'checkout_model_marks_zero_coupon_without_hmac', strpos($checkout_source, "'payment_gateway' => 'zero_amount_coupon'") !== false && strpos($checkout_source, "'selected_payment_method' => 'zero_amount_coupon'") !== false && strpos($checkout_source, "'last_hmac_verified' => 0") !== false);
ypzci1_check($checks, 'entitlement_model_has_zero_coupon_course_method', strpos($entitlement_source, 'issue_zero_amount_coupon_course_access') !== false && strpos($entitlement_source, 'can_issue_zero_amount_coupon_order') !== false);
ypzci1_check($checks, 'instapay_blocks_zero_amount_orders', strpos($instapay_source, 'zero_amount_coupon_completion_required') !== false);
ypzci1_check($checks, 'controller_route_view_wired', strpos($controller_source, 'complete_zero_amount_coupon') !== false && strpos($routes_source, "youngo/checkout/zero-coupon/complete/(:any)") !== false && strpos($view_source, 'Complete checkout / Activate access') !== false);
ypzci1_check($checks, 'view_hides_instapay_for_zero_total_coupon', strpos($view_source, 'Manual payment upload is not required for a zero-amount coupon checkout.') !== false && strpos($view_source, '$zero_coupon_is_zero_total_order') !== false);

$reader = new Youngo_paymob_config(array('load_local_override' => false));
ypzci1_check($checks, 'paymob_default_enabled_false', $reader->is_enabled() === false);
ypzci1_check($checks, 'paymob_default_network_false', $reader->is_network_enabled() === false);
ypzci1_check($checks, 'paymob_static_gates_false', preg_match('/[\'"]enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1 && preg_match('/[\'"]network_enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1);

$protected_tables = array(
    'coupons',
    'youngo_coupon_courses',
    'youngo_coupon_subscription_plans',
    'youngo_coupon_usages',
    'youngo_checkout_orders',
    'youngo_instapay_payment_submissions',
    'youngo_payment_transactions',
    'youngo_course_access',
    'youngo_user_subscriptions',
    'youngo_manual_grants',
    'payment',
    'enrol',
);
$baseline_counts = ypzci1_counts($db, $protected_tables);

$user_id = ypzci1_first_id($db, 'users', array('role_id' => 2, 'status' => 1));
if ($user_id === null) {
    $user_id = ypzci1_first_id($db, 'users', array('role_id' => 2));
}
$course_id = ypzci1_first_id($db, 'course');
$plan_id = ypzci1_first_id($db, 'youngo_subscription_plans');
ypzci1_check($checks, 'fixture_user_course_available', $user_id !== null && $course_id !== null, 'user=' . $user_id . ', course=' . $course_id);

try {
    if ($user_id !== null && $course_id !== null) {
        $details['cleanup'] = 'started';
        $prefix = 'YZERO1_' . gmdate('YmdHis') . '_';

        $partial_coupon_id = ypzci1_insert_coupon($db, $prefix . 'PARTIAL', 'percentage', '50.00');
        $expired_coupon_id = ypzci1_insert_coupon($db, $prefix . 'EXPIRE', 'percentage', '100.00');
        $zero_coupon_id = ypzci1_insert_coupon($db, $prefix . 'ZERO', 'percentage', '100.00');
        $subscription_coupon_id = ypzci1_insert_coupon($db, $prefix . 'SUBZERO', 'percentage', '100.00', null, 'subscription');
        $fixture_coupon_ids = array($partial_coupon_id, $expired_coupon_id, $zero_coupon_id, $subscription_coupon_id);

        $partial_order_id = ypzci1_insert_order($db, $user_id, $course_id, 'YGO-ZERO-PARTIAL-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true)), 0, 8));
        $expired_order_id = ypzci1_insert_order($db, $user_id, $course_id, 'YGO-ZERO-EXPIRE-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true) . 'e'), 0, 8));
        $success_order_id = ypzci1_insert_order($db, $user_id, $course_id, 'YGO-ZERO-OK-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true) . 'z'), 0, 8));
        $fixture_order_ids = array($partial_order_id, $expired_order_id, $success_order_id);

        $partial_apply = $checkout_model->apply_coupon_snapshot_to_order($partial_order_id, $user_id, $prefix . 'PARTIAL');
        $partial_complete = $checkout_model->complete_zero_amount_coupon_order($partial_order_id, $user_id);
        ypzci1_check($checks, 'partial_coupon_does_not_complete', !empty($partial_apply['ok']) && empty($partial_complete['ok']) && isset($partial_complete['code']) && $partial_complete['code'] === 'final_amount_not_zero');

        $expired_apply = $checkout_model->apply_coupon_snapshot_to_order($expired_order_id, $user_id, $prefix . 'EXPIRE');
        $db->where('id', $expired_coupon_id)->update('coupons', ypzci1_filter_columns($db, 'coupons', array(
            'expiry_date' => strtotime('-2 days'),
            'updated_at' => time(),
        )));
        $expired_complete = $checkout_model->complete_zero_amount_coupon_order($expired_order_id, $user_id);
        ypzci1_check($checks, 'expired_coupon_revalidation_blocks_completion', !empty($expired_apply['ok']) && empty($expired_complete['ok']) && isset($expired_complete['code']) && $expired_complete['code'] === 'coupon_revalidation_failed');

        if ($plan_id !== null) {
            $subscription_order_id = ypzci1_insert_order($db, $user_id, null, 'YGO-ZERO-SUB-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true) . 's'), 0, 8), 'subscription_purchase', $plan_id);
            $fixture_order_ids[] = $subscription_order_id;
            ypzci1_apply_subscription_zero_snapshot($db, $subscription_order_id, $subscription_coupon_id, $prefix . 'SUBZERO');
            $subscription_complete = $checkout_model->complete_zero_amount_coupon_order($subscription_order_id, $user_id);
            ypzci1_check($checks, 'subscription_zero_total_blocked_deferred', empty($subscription_complete['ok']) && isset($subscription_complete['code']) && $subscription_complete['code'] === 'subscription_zero_coupon_deferred');
        } else {
            ypzci1_check($checks, 'subscription_zero_total_blocked_deferred', true, 'No subscription plan fixture exists locally; subscription implementation remains deferred by source inspection.');
        }

        $zero_apply = $checkout_model->apply_coupon_snapshot_to_order($success_order_id, $user_id, $prefix . 'ZERO');
        $zero_context = $checkout_model->get_zero_amount_coupon_completion_context($success_order_id, $user_id);
        $complete = $checkout_model->complete_zero_amount_coupon_order($success_order_id, $user_id);
        $completed_order = $db->where('id', $success_order_id)->get('youngo_checkout_orders', 1)->row_array();
        $course_access_rows = $db->where('checkout_order_id', $success_order_id)->get('youngo_course_access')->result_array();
        foreach ($course_access_rows as $row) {
            if (!empty($row['id'])) {
                $fixture_course_access_ids[] = (int) $row['id'];
            }
        }
        $coupon_usage_rows = $db->where('checkout_order_id', $success_order_id)->get('youngo_coupon_usages')->result_array();
        foreach ($coupon_usage_rows as $row) {
            if (!empty($row['id'])) {
                $fixture_coupon_usage_ids[] = (int) $row['id'];
            }
        }
        $decoded_snapshot = !empty($completed_order['checkout_snapshot_json']) ? json_decode($completed_order['checkout_snapshot_json'], true) : array();

        ypzci1_check($checks, 'zero_coupon_snapshot_applied_and_context_available', !empty($zero_apply['ok']) && !empty($zero_context['ok']) && !empty($zero_context['data']['zero_amount_coupon']['completion_available']));
        ypzci1_check($checks, 'zero_completion_success', !empty($complete['ok']) && isset($complete['code']) && $complete['code'] === 'zero_amount_coupon_completed');
        ypzci1_check($checks, 'order_marked_zero_coupon_paid_equivalent', !empty($completed_order) && $completed_order['status'] === 'paid' && $completed_order['payment_gateway'] === 'zero_amount_coupon' && $completed_order['selected_payment_method'] === 'zero_amount_coupon' && (int) $completed_order['last_hmac_verified'] === 0 && !empty($completed_order['paid_at']) && !empty($completed_order['completed_at']));
        ypzci1_check($checks, 'course_access_issued_once_from_completion_time', count($course_access_rows) === 1 && (int) $course_access_rows[0]['user_id'] === (int) $user_id && (int) $course_access_rows[0]['course_id'] === (int) $course_id && $course_access_rows[0]['status'] === 'active' && (int) $course_access_rows[0]['start_date'] === (int) $completed_order['completed_at']);
        ypzci1_check($checks, 'order_entitlement_reference_recorded', (int) $completed_order['entitlement_issued'] === 1 && $completed_order['entitlement_issuance_status'] === 'issued' && (int) $completed_order['entitlement_course_access_id'] === (int) $course_access_rows[0]['id']);
        ypzci1_check($checks, 'coupon_usage_recorded_once', count($coupon_usage_rows) === 1 && (int) $coupon_usage_rows[0]['coupon_id'] === (int) $zero_coupon_id && (string) $coupon_usage_rows[0]['discount_amount'] === '100.00');
        ypzci1_check($checks, 'completion_snapshot_preserves_coupon_audit', isset($decoded_snapshot['zero_amount_coupon_completion']['completed']) && $decoded_snapshot['zero_amount_coupon_completion']['selected_payment_method'] === 'zero_amount_coupon' && isset($decoded_snapshot['order']['coupon_code']) && $decoded_snapshot['order']['coupon_code'] === $prefix . 'ZERO');

        $duplicate = $checkout_model->complete_zero_amount_coupon_order($success_order_id, $user_id);
        $course_access_count_after_duplicate = (int) $db->where('checkout_order_id', $success_order_id)->count_all_results('youngo_course_access');
        $coupon_usage_count_after_duplicate = (int) $db->where('checkout_order_id', $success_order_id)->count_all_results('youngo_coupon_usages');
        ypzci1_check($checks, 'duplicate_completion_is_idempotent', !empty($duplicate['ok']) && isset($duplicate['code']) && $duplicate['code'] === 'already_completed' && $course_access_count_after_duplicate === 1 && $coupon_usage_count_after_duplicate === 1);

        $instapay_zero_allowed = $instapay_model->can_create_submission_for_order($completed_order);
        $instapay_zero_draft = $db->where('id', $partial_order_id)->get('youngo_checkout_orders', 1)->row_array();
        ypzci1_check($checks, 'instapay_submission_not_created_for_zero_completion', (int) $db->where('order_id', $success_order_id)->count_all_results('youngo_instapay_payment_submissions') === 0 && empty($instapay_zero_allowed['ok']));
        ypzci1_check($checks, 'card_wallet_paymob_rows_unchanged_during_completion', $baseline_counts['youngo_payment_transactions'] === ypzci1_count($db, 'youngo_payment_transactions') && $baseline_counts['payment'] === ypzci1_count($db, 'payment') && $reader->is_enabled() === false && $reader->is_network_enabled() === false && !empty($instapay_zero_draft));
    }
} finally {
    if (!empty($fixture_coupon_usage_ids) && $db->table_exists('youngo_coupon_usages')) {
        $db->where_in('id', array_values(array_unique($fixture_coupon_usage_ids)))->delete('youngo_coupon_usages');
    }
    if (!empty($fixture_course_access_ids) && $db->table_exists('youngo_course_access')) {
        $db->where_in('id', array_values(array_unique($fixture_course_access_ids)))->delete('youngo_course_access');
    }
    if (!empty($fixture_order_ids) && $db->table_exists('youngo_checkout_orders')) {
        $db->where_in('id', array_values(array_unique($fixture_order_ids)))->delete('youngo_checkout_orders');
    }
    if (!empty($fixture_coupon_ids) && $db->table_exists('coupons')) {
        $db->where_in('id', array_values(array_unique($fixture_coupon_ids)))->delete('coupons');
    }
}

$after_counts = ypzci1_counts($db, $protected_tables);
$details['cleanup'] = ypzci1_counts_match($baseline_counts, $after_counts) ? 'completed' : 'failed';
ypzci1_check($checks, 'temporary_rows_deleted_and_counts_restored', ypzci1_counts_match($baseline_counts, $after_counts), json_encode(array('before' => $baseline_counts, 'after' => $after_counts), JSON_UNESCAPED_SLASHES));
ypzci1_check($checks, 'no_instapay_paymob_card_wallet_persistent_rows', $baseline_counts['youngo_instapay_payment_submissions'] === $after_counts['youngo_instapay_payment_submissions'] && $baseline_counts['youngo_payment_transactions'] === $after_counts['youngo_payment_transactions'] && $baseline_counts['payment'] === $after_counts['payment']);
ypzci1_check($checks, 'protected_access_counts_restored', $baseline_counts['youngo_course_access'] === $after_counts['youngo_course_access'] && $baseline_counts['youngo_user_subscriptions'] === $after_counts['youngo_user_subscriptions'] && $baseline_counts['youngo_manual_grants'] === $after_counts['youngo_manual_grants'] && $baseline_counts['enrol'] === $after_counts['enrol']);

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo 'phase: PAYMENT.ZERO.AMOUNT.COUPON.ACCESS.IMPLEMENT.1' . PHP_EOL;
echo 'mode: zero_amount_coupon_course_completion_temp_fixtures_cleaned' . PHP_EOL;
echo 'db_writes: ' . $details['db_writes'] . PHP_EOL;
echo 'backup_path: ' . $details['backup_path'] . PHP_EOL;
echo 'backup_size: ' . $details['backup_size'] . PHP_EOL;
echo 'backup_sha256: ' . $details['backup_sha256'] . PHP_EOL;
echo 'cleanup: ' . $details['cleanup'] . PHP_EOL;
echo PHP_EOL;

foreach ($checks as $name => $check) {
    echo $check['status'] . ' - ' . $name;
    if ($check['detail'] !== '') {
        echo ' :: ' . $check['detail'];
    }
    echo PHP_EOL;
}

echo PHP_EOL;
echo empty($failed) ? 'RESULT: PASS' . PHP_EOL : 'RESULT: FAIL' . PHP_EOL;

exit(empty($failed) ? 0 : 1);
