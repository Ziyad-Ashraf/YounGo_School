<?php
/**
 * PAYMENT.COUPON.CHECKOUT.SNAPSHOT.WRITE.1 diagnostic.
 *
 * Creates a DB backup, inserts temporary checkout/coupon fixtures, exercises
 * model-level coupon snapshot writes, deletes the fixtures, and verifies no
 * payment/access/entitlement side effects.
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
require_once APPPATH . 'models/Youngo_checkout_model.php';

$checks = array();
$details = array(
    'db_writes' => 'temporary_checkout_order_and_coupon_fixture_rows_inserted_then_deleted',
    'cleanup' => 'not_started',
    'backup_path' => '',
    'backup_size' => '',
    'backup_sha256' => '',
);
$fixture_order_ids = array();
$fixture_coupon_ids = array();

function ypcw1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypcw1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypcw1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypcw1_backup_ident($name)
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function ypcw1_backup_value($mysqli, $value)
{
    return $value === null ? 'NULL' : "'" . $mysqli->real_escape_string($value) . "'";
}

function ypcw1_connect_mysqli($config)
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

function ypcw1_create_backup($root)
{
    $mysqli = ypcw1_connect_mysqli(ypcw1_db_config());
    if (!$mysqli) {
        return array('ok' => false, 'error' => 'connect_failed');
    }

    $backup_dir = dirname($root) . '/backups';
    if (!is_dir($backup_dir) && !mkdir($backup_dir, 0777, true)) {
        return array('ok' => false, 'error' => 'backup_dir_failed');
    }

    $path = $backup_dir . '/youngo_school_before_payment_coupon_checkout_snapshot_write_1_' . date('Y_m_d_His') . '.sql';
    $fh = fopen($path, 'wb');
    if (!$fh) {
        return array('ok' => false, 'error' => 'backup_file_failed');
    }

    fwrite($fh, "-- YounGo backup before PAYMENT.COUPON.CHECKOUT.SNAPSHOT.WRITE.1 diagnostic\n");
    fwrite($fh, "-- Created at: " . date('c') . "\n");
    fwrite($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    $tables = array();
    $result = $mysqli->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    if (!$result) {
        fclose($fh);
        return array('ok' => false, 'error' => 'table_list_failed');
    }

    while ($row = $result->fetch_array(MYSQLI_NUM)) {
        $tables[] = $row[0];
    }

    foreach ($tables as $table) {
        $create = $mysqli->query('SHOW CREATE TABLE ' . ypcw1_backup_ident($table));
        if (!$create) {
            continue;
        }

        $create_row = $create->fetch_assoc();
        fwrite($fh, "\n-- Table " . $table . "\n");
        fwrite($fh, 'DROP TABLE IF EXISTS ' . ypcw1_backup_ident($table) . ";\n");
        fwrite($fh, $create_row['Create Table'] . ";\n\n");

        $rows = $mysqli->query('SELECT * FROM ' . ypcw1_backup_ident($table));
        if (!$rows) {
            continue;
        }

        while ($data = $rows->fetch_assoc()) {
            $columns = array();
            $values = array();
            foreach ($data as $column => $value) {
                $columns[] = ypcw1_backup_ident($column);
                $values[] = ypcw1_backup_value($mysqli, $value);
            }
            fwrite($fh, 'INSERT INTO ' . ypcw1_backup_ident($table) . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n");
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

function ypcw1_count($db, $table)
{
    return $db->table_exists($table) ? (int) $db->count_all($table) : null;
}

function ypcw1_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ypcw1_count($db, $table);
    }

    return $counts;
}

function ypcw1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ypcw1_filter_columns($db, $table, $data)
{
    $filtered = array();
    foreach ($data as $field => $value) {
        if ($db->field_exists($field, $table)) {
            $filtered[$field] = $value;
        }
    }

    return $filtered;
}

function ypcw1_first_id($db, $table, $where = array())
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

function ypcw1_insert_coupon($db, $code, $discount_percentage, $discount_type, $discount_value, $scope)
{
    $now = time();
    $data = ypcw1_filter_columns($db, 'coupons', array(
        'code' => $code,
        'discount_percentage' => $discount_percentage,
        'created_at' => $now,
        'expiry_date' => strtotime('+30 days'),
        'discount_type' => $discount_type,
        'discount_value' => $discount_value,
        'scope' => $scope,
        'max_usage_count' => null,
        'status' => 'active',
        'updated_at' => $now,
    ));

    $db->insert('coupons', $data);
    return (int) $db->insert_id();
}

function ypcw1_insert_order($db, $user_id, $course_id, $reference)
{
    $now = time();
    $data = ypcw1_filter_columns($db, 'youngo_checkout_orders', array(
        'user_id' => (int) $user_id,
        'order_reference' => $reference,
        'order_type' => 'course_purchase',
        'status' => 'draft',
        'course_id' => (int) $course_id,
        'plan_id' => null,
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
        'item_title_snapshot' => null,
        'checkout_snapshot_json' => null,
        'payment_gateway' => null,
        'gateway_environment' => 'sandbox',
        'idempotency_key' => hash('sha256', 'PAYMENT.COUPON.CHECKOUT.SNAPSHOT.WRITE.1|' . $reference),
        'last_hmac_verified' => 0,
        'entitlement_issued' => 0,
        'entitlement_issuance_status' => 'not_started',
        'metadata' => json_encode(array('source' => 'youngo_payment_coupon_checkout_snapshot_write_1_diagnostic'), JSON_UNESCAPED_SLASHES),
        'created_at' => $now,
        'updated_at' => $now,
    ));

    $db->insert('youngo_checkout_orders', $data);
    return (int) $db->insert_id();
}

class Ypcw1_session_stub
{
    public function userdata($key)
    {
        return null;
    }
}

$backup = ypcw1_create_backup($root);
if (!empty($backup['ok'])) {
    $details['backup_path'] = $backup['path'];
    $details['backup_size'] = (string) $backup['size'];
    $details['backup_sha256'] = $backup['sha256'];
}

$CI = new stdClass();
$CI->session = new Ypcw1_session_stub();
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ypcw1_db_config(), true);
$CI->db = $db;
$checkout_model = new Youngo_checkout_model(array('db' => $db));

$paths = array(
    'checkout_model' => $root . '/application/models/Youngo_checkout_model.php',
    'coupon_evaluator' => $root . '/application/models/Youngo_coupon_evaluator_model.php',
    'checkout_controller' => $root . '/application/controllers/Youngo_checkout.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
    'routes' => $root . '/application/config/routes.php',
);

foreach ($paths as $name => $path) {
    ypcw1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

ypcw1_check($checks, 'backup_created_before_temp_writes', !empty($backup['ok']), !empty($backup['ok']) ? $backup['path'] . '|' . $backup['size'] . '|' . $backup['sha256'] : (isset($backup['error']) ? $backup['error'] : 'backup_failed'));
ypcw1_check($checks, 'checkout_model_loads', $checkout_model instanceof Youngo_checkout_model);
foreach (array('apply_coupon_snapshot_to_order', 'clear_coupon_snapshot_from_order', 'get_safe_order_review_snapshot') as $method) {
    ypcw1_check($checks, 'method_exists_' . $method, method_exists($checkout_model, $method));
}

$checkout_source = ypcw1_read($paths['checkout_controller']);
$routes_source = ypcw1_read($paths['routes']);
$paymob_source = ypcw1_read($paths['paymob_config']);
ypcw1_check($checks, 'checkout_controller_coupon_actions_are_post_model_backed_if_present', stripos($checkout_source, 'apply_coupon_snapshot_to_order') === false || (strpos($checkout_source, 'function apply_coupon') !== false && strpos($checkout_source, 'function clear_coupon') !== false && strpos($checkout_source, "method(true)") !== false && strpos($checkout_source, 'clear_coupon_snapshot_from_order') !== false));
ypcw1_check($checks, 'routes_have_expected_coupon_targets_if_present', stripos($routes_source, 'youngo/checkout/coupon') === false || (strpos($routes_source, "youngo/checkout/coupon/apply/(:any)") !== false && strpos($routes_source, "youngo/checkout/coupon/clear/(:any)") !== false && stripos($routes_source, 'coupon/snapshot') === false));

$reader = new Youngo_paymob_config(array('load_local_override' => false));
ypcw1_check($checks, 'paymob_default_enabled_false', $reader->is_enabled() === false);
ypcw1_check($checks, 'paymob_default_network_false', $reader->is_network_enabled() === false);
ypcw1_check($checks, 'paymob_default_checkout_cta_false', $reader->is_checkout_cta_enabled() === false);
ypcw1_check($checks, 'paymob_static_gates_false', preg_match('/[\'"]enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1 && preg_match('/[\'"]network_enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1);

$protected_tables = array(
    'coupons',
    'youngo_coupon_courses',
    'youngo_coupon_subscription_plans',
    'youngo_coupon_usages',
    'youngo_checkout_orders',
    'youngo_payment_transactions',
    'youngo_course_access',
    'youngo_user_subscriptions',
    'youngo_manual_grants',
    'payment',
    'enrol',
);
$baseline_counts = ypcw1_counts($db, $protected_tables);

$user_id = ypcw1_first_id($db, 'users', array('role_id' => 2));
if ($user_id === null) {
    $user_id = ypcw1_first_id($db, 'users');
}
$course_id = ypcw1_first_id($db, 'course');
ypcw1_check($checks, 'fixture_user_course_available', $user_id !== null && $course_id !== null, 'user=' . $user_id . ', course=' . $course_id);

if ($user_id !== null && $course_id !== null) {
    $details['cleanup'] = 'started';
    $prefix = 'YCWRITE1_' . gmdate('YmdHis') . '_';
    $valid_code = $prefix . 'SAVE20';
    $zero_code = $prefix . 'ZERO';
    $missing_code = $prefix . 'MISSING';

    $valid_coupon_id = ypcw1_insert_coupon($db, $valid_code, '20', 'percentage', '20.00', 'both');
    $zero_coupon_id = ypcw1_insert_coupon($db, $zero_code, '0', 'fixed', '150.00', 'both');
    $fixture_coupon_ids = array($valid_coupon_id, $zero_coupon_id);

    $order_id = ypcw1_insert_order($db, $user_id, $course_id, 'YGO-COUPON-WRITE-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true)), 0, 8));
    $fixture_order_ids[] = $order_id;

    $apply = $checkout_model->apply_coupon_snapshot_to_order($order_id, $user_id, $valid_code);
    $row = $db->where('id', $order_id)->get('youngo_checkout_orders', 1)->row_array();
    $json = !empty($row['checkout_snapshot_json']) ? json_decode($row['checkout_snapshot_json'], true) : array();
    ypcw1_check($checks, 'apply_valid_coupon_snapshot_ok', !empty($apply['ok']));
    ypcw1_check($checks, 'subtotal_unchanged_after_apply', isset($row['subtotal_amount']) && (string) $row['subtotal_amount'] === '100.00');
    ypcw1_check($checks, 'total_amount_updated_to_final', isset($row['total_amount']) && (string) $row['total_amount'] === '80.00' && (int) $row['total_amount_cents'] === 8000);
    ypcw1_check($checks, 'coupon_fields_populated', (int) $row['coupon_id'] === $valid_coupon_id && $row['coupon_code'] === $valid_code && $row['coupon_discount_type'] === 'percentage' && (string) $row['coupon_discount_value'] === '20.00' && (string) $row['discount_amount'] === '20.00');
    ypcw1_check($checks, 'item_title_snapshot_populated', isset($row['item_title_snapshot']) && trim((string) $row['item_title_snapshot']) !== '');
    ypcw1_check($checks, 'checkout_snapshot_json_contains_coupon_amounts', is_array($json) && isset($json['snapshot_version']) && $json['snapshot_version'] === 'PAYMENT.COUPON.CHECKOUT.SNAPSHOT.WRITE.1' && isset($json['order']['original_amount']) && $json['order']['original_amount'] === '100.00' && isset($json['order']['final_amount']) && $json['order']['final_amount'] === '80.00' && isset($json['coupon_evaluator']['coupon_code']) && $json['coupon_evaluator']['coupon_code'] === $valid_code);

    $before_invalid = array(
        'coupon_id' => $row['coupon_id'],
        'coupon_code' => $row['coupon_code'],
        'discount_amount' => $row['discount_amount'],
        'total_amount' => $row['total_amount'],
        'checkout_snapshot_json' => $row['checkout_snapshot_json'],
    );
    $invalid = $checkout_model->apply_coupon_snapshot_to_order($order_id, $user_id, $missing_code);
    $after_invalid_row = $db->where('id', $order_id)->get('youngo_checkout_orders', 1)->row_array();
    $after_invalid = array(
        'coupon_id' => $after_invalid_row['coupon_id'],
        'coupon_code' => $after_invalid_row['coupon_code'],
        'discount_amount' => $after_invalid_row['discount_amount'],
        'total_amount' => $after_invalid_row['total_amount'],
        'checkout_snapshot_json' => $after_invalid_row['checkout_snapshot_json'],
    );
    ypcw1_check($checks, 'invalid_coupon_returns_failure', empty($invalid['ok']) && isset($invalid['code']) && $invalid['code'] === 'coupon_snapshot_invalid');
    ypcw1_check($checks, 'invalid_coupon_does_not_corrupt_order', $before_invalid === $after_invalid);

    $clear = $checkout_model->clear_coupon_snapshot_from_order($order_id, $user_id);
    $cleared_row = $db->where('id', $order_id)->get('youngo_checkout_orders', 1)->row_array();
    ypcw1_check($checks, 'clear_coupon_snapshot_ok', !empty($clear['ok']));
    ypcw1_check($checks, 'clear_restores_total_to_subtotal', (string) $cleared_row['subtotal_amount'] === '100.00' && (string) $cleared_row['total_amount'] === '100.00' && (int) $cleared_row['total_amount_cents'] === 10000);
    ypcw1_check($checks, 'clear_resets_coupon_snapshot_fields', $cleared_row['coupon_id'] === null && $cleared_row['coupon_code'] === null && (string) $cleared_row['discount_amount'] === '0.00' && $cleared_row['coupon_discount_type'] === null && $cleared_row['coupon_discount_value'] === null && $cleared_row['checkout_snapshot_json'] === null);

    $zero = $checkout_model->apply_coupon_snapshot_to_order($order_id, $user_id, $zero_code);
    $zero_row = $db->where('id', $order_id)->get('youngo_checkout_orders', 1)->row_array();
    $zero_json = !empty($zero_row['checkout_snapshot_json']) ? json_decode($zero_row['checkout_snapshot_json'], true) : array();
    ypcw1_check($checks, 'zero_final_coupon_snapshot_applies_without_access', !empty($zero['ok']) && (string) $zero_row['total_amount'] === '0.00' && (int) $zero_row['total_amount_cents'] === 0 && (int) $zero_row['entitlement_issued'] === 0 && (string) $zero_row['entitlement_issuance_status'] === 'not_started');
    ypcw1_check($checks, 'zero_final_course_completion_flag_preserved_in_snapshot', isset($zero_json['zero_final_amount_coupon_completion_available']) && !empty($zero_json['zero_final_amount_coupon_completion_available']) && isset($zero_json['coupon_evaluator']['zero_final_amount_policy_not_enabled']) && !empty($zero_json['coupon_evaluator']['zero_final_amount_policy_not_enabled']));

    $review = $checkout_model->get_safe_order_review_snapshot($order_id, $user_id);
    ypcw1_check($checks, 'safe_order_review_snapshot_contains_admin_coupon_fields', !empty($review['ok']) && isset($review['data']['review_snapshot']['original_amount']) && $review['data']['review_snapshot']['original_amount'] === '100.00' && $review['data']['review_snapshot']['final_amount'] === '0.00' && $review['data']['review_snapshot']['coupon_code'] === $zero_code && !empty($review['data']['review_snapshot']['zero_final_amount_coupon_completion_available']));
}

if (!empty($fixture_order_ids)) {
    $db->where_in('id', $fixture_order_ids)->delete('youngo_checkout_orders');
}
if (!empty($fixture_coupon_ids)) {
    $db->where_in('id', $fixture_coupon_ids)->delete('coupons');
}

$after_counts = ypcw1_counts($db, $protected_tables);
$details['cleanup'] = ypcw1_counts_match($baseline_counts, $after_counts) ? 'completed' : 'failed';
ypcw1_check($checks, 'temporary_rows_deleted_and_counts_restored', ypcw1_counts_match($baseline_counts, $after_counts), json_encode(array('before' => $baseline_counts, 'after' => $after_counts), JSON_UNESCAPED_SLASHES));
ypcw1_check($checks, 'payment_access_counts_unchanged', $baseline_counts['youngo_payment_transactions'] === $after_counts['youngo_payment_transactions'] && $baseline_counts['youngo_course_access'] === $after_counts['youngo_course_access'] && $baseline_counts['youngo_user_subscriptions'] === $after_counts['youngo_user_subscriptions'] && $baseline_counts['youngo_manual_grants'] === $after_counts['youngo_manual_grants'] && $baseline_counts['payment'] === $after_counts['payment'] && $baseline_counts['enrol'] === $after_counts['enrol']);

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo 'phase: PAYMENT.COUPON.CHECKOUT.SNAPSHOT.WRITE.1' . PHP_EOL;
echo 'mode: model_level_snapshot_write_temp_fixtures_cleaned' . PHP_EOL;
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
