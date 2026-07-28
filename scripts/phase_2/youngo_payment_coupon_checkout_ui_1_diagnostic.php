<?php
/**
 * PAYMENT.COUPON.CHECKOUT.UI.1 diagnostic.
 *
 * Creates a DB backup, inserts temporary checkout/coupon fixtures, verifies
 * model-backed coupon UI targets and safe apply/clear behavior, deletes all
 * temporary rows, and checks that payment/access data remains unchanged.
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
$fixture_order_ids = array();
$fixture_coupon_ids = array();
$details = array(
    'db_writes' => 'temporary_checkout_order_and_coupon_fixture_rows_inserted_then_deleted',
    'cleanup' => 'not_started',
    'backup_path' => '',
    'backup_size' => '',
    'backup_sha256' => '',
);

function ypcui1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypcui1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypcui1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypcui1_backup_ident($name)
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function ypcui1_backup_value($mysqli, $value)
{
    return $value === null ? 'NULL' : "'" . $mysqli->real_escape_string($value) . "'";
}

function ypcui1_connect_mysqli($config)
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

function ypcui1_create_backup($root)
{
    $mysqli = ypcui1_connect_mysqli(ypcui1_db_config());
    if (!$mysqli) {
        return array('ok' => false, 'error' => 'connect_failed');
    }

    $backup_dir = dirname($root) . '/backups';
    if (!is_dir($backup_dir) && !mkdir($backup_dir, 0777, true)) {
        return array('ok' => false, 'error' => 'backup_dir_failed');
    }

    $path = $backup_dir . '/youngo_school_before_payment_coupon_checkout_ui_1_' . date('Y_m_d_His') . '.sql';
    $fh = fopen($path, 'wb');
    if (!$fh) {
        return array('ok' => false, 'error' => 'backup_file_failed');
    }

    fwrite($fh, "-- YounGo backup before PAYMENT.COUPON.CHECKOUT.UI.1 diagnostic\n");
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
        $create = $mysqli->query('SHOW CREATE TABLE ' . ypcui1_backup_ident($table));
        if (!$create) {
            continue;
        }

        $create_row = $create->fetch_assoc();
        fwrite($fh, "\n-- Table " . $table . "\n");
        fwrite($fh, 'DROP TABLE IF EXISTS ' . ypcui1_backup_ident($table) . ";\n");
        fwrite($fh, $create_row['Create Table'] . ";\n\n");

        $rows = $mysqli->query('SELECT * FROM ' . ypcui1_backup_ident($table));
        if (!$rows) {
            continue;
        }

        while ($data = $rows->fetch_assoc()) {
            $columns = array();
            $values = array();
            foreach ($data as $column => $value) {
                $columns[] = ypcui1_backup_ident($column);
                $values[] = ypcui1_backup_value($mysqli, $value);
            }
            fwrite($fh, 'INSERT INTO ' . ypcui1_backup_ident($table) . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n");
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

function ypcui1_count($db, $table)
{
    return $db->table_exists($table) ? (int) $db->count_all($table) : null;
}

function ypcui1_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ypcui1_count($db, $table);
    }

    return $counts;
}

function ypcui1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ypcui1_filter_columns($db, $table, $data)
{
    $filtered = array();
    foreach ($data as $field => $value) {
        if ($db->field_exists($field, $table)) {
            $filtered[$field] = $value;
        }
    }

    return $filtered;
}

function ypcui1_first_id($db, $table, $where = array())
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

function ypcui1_insert_coupon($db, $code)
{
    $now = time();
    $data = ypcui1_filter_columns($db, 'coupons', array(
        'code' => $code,
        'discount_percentage' => '30',
        'created_at' => $now,
        'expiry_date' => strtotime('+30 days'),
        'discount_type' => 'percentage',
        'discount_value' => '30.00',
        'scope' => 'both',
        'max_usage_count' => null,
        'status' => 'active',
        'updated_at' => $now,
    ));

    $db->insert('coupons', $data);
    return (int) $db->insert_id();
}

function ypcui1_insert_order($db, $user_id, $course_id, $reference)
{
    $now = time();
    $data = ypcui1_filter_columns($db, 'youngo_checkout_orders', array(
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
        'payment_gateway' => null,
        'gateway_environment' => 'sandbox',
        'idempotency_key' => hash('sha256', 'PAYMENT.COUPON.CHECKOUT.UI.1|' . $reference),
        'last_hmac_verified' => 0,
        'entitlement_issued' => 0,
        'entitlement_issuance_status' => 'not_started',
        'metadata' => json_encode(array('source' => 'youngo_payment_coupon_checkout_ui_1_diagnostic'), JSON_UNESCAPED_SLASHES),
        'created_at' => $now,
        'updated_at' => $now,
    ));

    $db->insert('youngo_checkout_orders', $data);
    return (int) $db->insert_id();
}

class Ypcui1_session_stub
{
    public function userdata($key)
    {
        return null;
    }
}

$backup = ypcui1_create_backup($root);
if (!empty($backup['ok'])) {
    $details['backup_path'] = $backup['path'];
    $details['backup_size'] = (string) $backup['size'];
    $details['backup_sha256'] = $backup['sha256'];
}

$CI = new stdClass();
$CI->session = new Ypcui1_session_stub();
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ypcui1_db_config(), true);
$CI->db = $db;
$checkout_model = new Youngo_checkout_model(array('db' => $db));

$paths = array(
    'checkout_controller' => $root . '/application/controllers/Youngo_checkout.php',
    'checkout_model' => $root . '/application/models/Youngo_checkout_model.php',
    'coupon_evaluator' => $root . '/application/models/Youngo_coupon_evaluator_model.php',
    'checkout_view' => $root . '/application/views/frontend/youngo/checkout_order.php',
    'routes' => $root . '/application/config/routes.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
);

foreach ($paths as $name => $path) {
    ypcui1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

ypcui1_check($checks, 'backup_created_before_temp_writes', !empty($backup['ok']), !empty($backup['ok']) ? $backup['path'] . '|' . $backup['size'] . '|' . $backup['sha256'] : (isset($backup['error']) ? $backup['error'] : 'backup_failed'));

$controller_source = ypcui1_read($paths['checkout_controller']);
$view_source = ypcui1_read($paths['checkout_view']);
$routes_source = ypcui1_read($paths['routes']);
$paymob_source = ypcui1_read($paths['paymob_config']);

ypcui1_check($checks, 'controller_has_post_coupon_actions', strpos($controller_source, 'function apply_coupon') !== false && strpos($controller_source, 'function clear_coupon') !== false && strpos($controller_source, "method(true)") !== false && strpos($controller_source, 'apply_coupon_snapshot_to_order') !== false && strpos($controller_source, 'clear_coupon_snapshot_from_order') !== false);
ypcui1_check($checks, 'routes_have_minimal_coupon_post_targets', strpos($routes_source, "youngo/checkout/coupon/apply/(:any)") !== false && strpos($routes_source, "youngo/checkout/coupon/clear/(:any)") !== false);
ypcui1_check($checks, 'view_has_coupon_input_apply_clear_summary', strpos($view_source, 'name="coupon_code"') !== false && strpos($view_source, 'youngo/checkout/coupon/apply/') !== false && strpos($view_source, 'youngo/checkout/coupon/clear/') !== false && strpos($view_source, 'Original amount') !== false && strpos($view_source, 'Discount') !== false && strpos($view_source, 'Final amount') !== false);
ypcui1_check($checks, 'view_does_not_use_legacy_cart_coupon_session', strpos($view_source, 'home/apply_coupon') === false && strpos($view_source, 'applied_coupon') === false && strpos($view_source, 'cart_items') === false && strpos($view_source, 'coupon_offer_100_percent') === false);
ypcui1_check($checks, 'view_has_disabled_payment_method_placeholders', strpos($view_source, 'Instapay') !== false && strpos($view_source, 'Coming soon') !== false && strpos($view_source, 'Cards') !== false && strpos($view_source, 'Digital Wallets') !== false && strpos($view_source, 'Not available yet') !== false);
ypcui1_check($checks, 'instapay_upload_may_exist_but_no_approval_access', strpos($controller_source, 'submit_instapay') !== false && strpos($routes_source, "youngo/checkout/instapay/submit/(:any)") !== false && strpos($view_source, 'name="instapay_screenshot"') !== false && stripos($controller_source, 'approve_instapay') === false && stripos($controller_source, 'reject_instapay') === false);

$reader = new Youngo_paymob_config(array('load_local_override' => false));
ypcui1_check($checks, 'paymob_default_enabled_false', $reader->is_enabled() === false);
ypcui1_check($checks, 'paymob_default_network_false', $reader->is_network_enabled() === false);
ypcui1_check($checks, 'paymob_default_checkout_cta_false', $reader->is_checkout_cta_enabled() === false);
ypcui1_check($checks, 'paymob_static_gates_false', preg_match('/[\'"]enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1 && preg_match('/[\'"]network_enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1);

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
$baseline_counts = ypcui1_counts($db, $protected_tables);

$user_id = ypcui1_first_id($db, 'users', array('role_id' => 2));
if ($user_id === null) {
    $user_id = ypcui1_first_id($db, 'users');
}
$course_id = ypcui1_first_id($db, 'course');
ypcui1_check($checks, 'fixture_user_course_available', $user_id !== null && $course_id !== null, 'user=' . $user_id . ', course=' . $course_id);

if ($user_id !== null && $course_id !== null) {
    $details['cleanup'] = 'started';
    $prefix = 'YCUI1_' . gmdate('YmdHis') . '_';
    $coupon_code = $prefix . 'SAVE30';
    $missing_code = $prefix . 'MISSING';
    $coupon_id = ypcui1_insert_coupon($db, $coupon_code);
    $fixture_coupon_ids[] = $coupon_id;
    $order_id = ypcui1_insert_order($db, $user_id, $course_id, 'YGO-COUPON-UI-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true)), 0, 8));
    $fixture_order_ids[] = $order_id;

    $apply = $checkout_model->apply_coupon_snapshot_to_order($order_id, $user_id, $coupon_code);
    $applied_row = $db->where('id', $order_id)->get('youngo_checkout_orders', 1)->row_array();
    ypcui1_check($checks, 'valid_coupon_can_apply_through_model_backing_form_path', !empty($apply['ok']) && (int) $applied_row['coupon_id'] === $coupon_id && $applied_row['coupon_code'] === $coupon_code && (string) $applied_row['total_amount'] === '70.00' && (string) $applied_row['subtotal_amount'] === '100.00');

    $before_invalid = array(
        'coupon_id' => $applied_row['coupon_id'],
        'coupon_code' => $applied_row['coupon_code'],
        'total_amount' => $applied_row['total_amount'],
        'discount_amount' => $applied_row['discount_amount'],
    );
    $invalid = $checkout_model->apply_coupon_snapshot_to_order($order_id, $user_id, $missing_code);
    $after_invalid_row = $db->where('id', $order_id)->get('youngo_checkout_orders', 1)->row_array();
    $after_invalid = array(
        'coupon_id' => $after_invalid_row['coupon_id'],
        'coupon_code' => $after_invalid_row['coupon_code'],
        'total_amount' => $after_invalid_row['total_amount'],
        'discount_amount' => $after_invalid_row['discount_amount'],
    );
    ypcui1_check($checks, 'invalid_coupon_does_not_alter_order', empty($invalid['ok']) && $before_invalid === $after_invalid);

    $clear = $checkout_model->clear_coupon_snapshot_from_order($order_id, $user_id);
    $cleared_row = $db->where('id', $order_id)->get('youngo_checkout_orders', 1)->row_array();
    ypcui1_check($checks, 'clear_coupon_restores_total', !empty($clear['ok']) && (string) $cleared_row['subtotal_amount'] === '100.00' && (string) $cleared_row['total_amount'] === '100.00' && $cleared_row['coupon_id'] === null && $cleared_row['coupon_code'] === null && (string) $cleared_row['discount_amount'] === '0.00');
}

if (!empty($fixture_order_ids)) {
    $db->where_in('id', $fixture_order_ids)->delete('youngo_checkout_orders');
}
if (!empty($fixture_coupon_ids)) {
    $db->where_in('id', $fixture_coupon_ids)->delete('coupons');
}

$after_counts = ypcui1_counts($db, $protected_tables);
$details['cleanup'] = ypcui1_counts_match($baseline_counts, $after_counts) ? 'completed' : 'failed';
ypcui1_check($checks, 'temporary_rows_deleted_and_counts_restored', ypcui1_counts_match($baseline_counts, $after_counts), json_encode(array('before' => $baseline_counts, 'after' => $after_counts), JSON_UNESCAPED_SLASHES));
ypcui1_check($checks, 'payment_access_counts_unchanged', $baseline_counts['youngo_payment_transactions'] === $after_counts['youngo_payment_transactions'] && $baseline_counts['youngo_course_access'] === $after_counts['youngo_course_access'] && $baseline_counts['youngo_user_subscriptions'] === $after_counts['youngo_user_subscriptions'] && $baseline_counts['youngo_manual_grants'] === $after_counts['youngo_manual_grants'] && $baseline_counts['payment'] === $after_counts['payment'] && $baseline_counts['enrol'] === $after_counts['enrol']);

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo 'phase: PAYMENT.COUPON.CHECKOUT.UI.1' . PHP_EOL;
echo 'mode: coupon_checkout_ui_model_backed_temp_fixtures_cleaned' . PHP_EOL;
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
