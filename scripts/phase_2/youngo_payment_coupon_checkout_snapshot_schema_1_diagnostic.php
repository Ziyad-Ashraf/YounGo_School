<?php
/**
 * PAYMENT.COUPON.CHECKOUT.SNAPSHOT.SCHEMA.1 diagnostic.
 *
 * Verifies additive checkout snapshot columns and helper behavior. This script
 * writes one temporary checkout order row, reads it, deletes it, and verifies
 * protected payment/access/coupon counts return to their baseline.
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
require_once APPPATH . 'models/Youngo_checkout_model.php';

$checks = array();
$details = array(
    'db_writes' => 'one_temporary_checkout_order_inserted_read_deleted',
    'cleanup' => 'not_started',
    'instapay_review_statuses_later' => 'pending_review, approved, rejected',
);

function ypcs_schema1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypcs_schema1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypcs_schema1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypcs_schema1_count($db, $table)
{
    return $db->table_exists($table) ? (int) $db->count_all($table) : null;
}

function ypcs_schema1_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ypcs_schema1_count($db, $table);
    }

    return $counts;
}

function ypcs_schema1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ypcs_schema1_column_type($db, $table, $column)
{
    if (!$db->table_exists($table)) {
        return '';
    }

    $query = $db->query(
        'SELECT DATA_TYPE, COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        array($table, $column)
    );
    if (!$query || $query->num_rows() === 0) {
        return '';
    }

    $row = $query->row_array();
    return strtolower($row['DATA_TYPE'] . '|' . $row['COLUMN_TYPE'] . '|nullable_' . $row['IS_NULLABLE']);
}

function ypcs_schema1_filter_columns($db, $table, $data)
{
    $filtered = array();
    foreach ($data as $field => $value) {
        if ($db->field_exists($field, $table)) {
            $filtered[$field] = $value;
        }
    }

    return $filtered;
}

function ypcs_schema1_first_id($db, $table, $where = array())
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

class Ypcs_schema1_session_stub
{
    public function userdata($key)
    {
        return null;
    }
}

$CI = new stdClass();
$CI->session = new Ypcs_schema1_session_stub();
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ypcs_schema1_db_config(), true);
$CI->db = $db;
$model = new Youngo_checkout_model(array('db' => $db));

$paths = array(
    'checkout_model' => $root . '/application/models/Youngo_checkout_model.php',
    'checkout_controller' => $root . '/application/controllers/Youngo_checkout.php',
    'payment_model' => $root . '/application/models/Youngo_payment_model.php',
    'entitlement_model' => $root . '/application/models/Youngo_entitlement_write_model.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
    'routes' => $root . '/application/config/routes.php',
    'up_sql' => $root . '/scripts/phase_2/payment_coupon_checkout_snapshot_schema_1_up.sql',
    'down_sql' => $root . '/scripts/phase_2/payment_coupon_checkout_snapshot_schema_1_down.sql',
);

foreach ($paths as $name => $path) {
    ypcs_schema1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

$checkout_model_source = ypcs_schema1_read($paths['checkout_model']);
$checkout_controller_source = ypcs_schema1_read($paths['checkout_controller']);
$payment_model_source = ypcs_schema1_read($paths['payment_model']);
$entitlement_model_source = ypcs_schema1_read($paths['entitlement_model']);
$paymob_config_source = ypcs_schema1_read($paths['paymob_config']);
$routes_source = ypcs_schema1_read($paths['routes']);
$up_sql = ypcs_schema1_read($paths['up_sql']);
$down_sql = ypcs_schema1_read($paths['down_sql']);
$up_without_comments = preg_replace('/--.*$/m', '', $up_sql);
$create_draft_order_source = '';
if (preg_match('/public function create_draft_order\([\\s\\S]*?\\n    public function create_or_reuse_draft_order\(/', $checkout_model_source, $matches)) {
    $create_draft_order_source = $matches[0];
}

ypcs_schema1_check($checks, 'model_loads', $model instanceof Youngo_checkout_model);
ypcs_schema1_check($checks, 'up_sql_targets_checkout_orders_only', strpos($up_sql, '`youngo_checkout_orders`') !== false && preg_match('/`youngo_(?!checkout_orders\b)/', $up_without_comments) !== 1);
ypcs_schema1_check($checks, 'up_sql_is_additive_alter_only', preg_match('/\b(DROP|TRUNCATE|DELETE|UPDATE|INSERT|CREATE)\b/i', $up_without_comments) !== 1 && strpos($up_sql, 'ADD COLUMN IF NOT EXISTS') !== false);
ypcs_schema1_check($checks, 'down_sql_drops_only_phase_columns', strpos($down_sql, 'DROP COLUMN IF EXISTS `coupon_discount_type`') !== false && strpos($down_sql, 'DROP COLUMN IF EXISTS `checkout_snapshot_json`') !== false && strpos($down_sql, '`youngo_checkout_orders`') !== false);
ypcs_schema1_check($checks, 'checkout_controller_has_no_coupon_or_instapay_behavior', stripos($checkout_controller_source, 'instapay') === false && stripos($checkout_controller_source, 'coupon_code') === false);
ypcs_schema1_check($checks, 'routes_have_no_new_coupon_or_instapay_checkout_routes', stripos($routes_source, 'youngo/checkout/coupon') === false && stripos($routes_source, 'instapay') === false);
ypcs_schema1_check($checks, 'payment_model_not_changed_for_instapay', stripos($payment_model_source, 'instapay') === false);
ypcs_schema1_check($checks, 'entitlement_write_model_not_changed_for_schema_phase', strpos($entitlement_model_source, 'issue_course_purchase_access') !== false && strpos($entitlement_model_source, 'issue_subscription_purchase') !== false && stripos($entitlement_model_source, 'coupon_discount_type') === false);
ypcs_schema1_check($checks, 'model_helper_methods_exist', method_exists($model, 'checkout_snapshot_fields_available') && method_exists($model, 'normalize_selected_payment_method') && method_exists($model, 'build_checkout_snapshot_array') && method_exists($model, 'prepare_coupon_snapshot_fields'));
ypcs_schema1_check($checks, 'existing_create_order_payload_not_wired_to_coupon_snapshot', $create_draft_order_source !== '' && strpos($create_draft_order_source, "'selected_payment_method' =>") === false && strpos($create_draft_order_source, "'coupon_discount_type' =>") === false && strpos($create_draft_order_source, "'checkout_snapshot_json' =>") === false);

$reader = new Youngo_paymob_config(array('load_local_override' => false));
ypcs_schema1_check($checks, 'paymob_default_enabled_false', $reader->is_enabled() === false);
ypcs_schema1_check($checks, 'paymob_default_network_false', $reader->is_network_enabled() === false);
ypcs_schema1_check($checks, 'paymob_default_checkout_routes_false', $reader->is_checkout_routes_enabled() === false);
ypcs_schema1_check($checks, 'paymob_default_checkout_cta_false', $reader->is_checkout_cta_enabled() === false);
ypcs_schema1_check($checks, 'paymob_config_static_gates_false', preg_match('/[\'"]enabled[\'"]\s*=>\s*false\b/', $paymob_config_source) === 1 && preg_match('/[\'"]network_enabled[\'"]\s*=>\s*false\b/', $paymob_config_source) === 1);

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

$baseline_counts = ypcs_schema1_counts($db, $protected_tables);

ypcs_schema1_check($checks, 'db_table_youngo_checkout_orders_exists', $db->table_exists('youngo_checkout_orders'));
foreach (array('subtotal_amount', 'total_amount', 'currency', 'coupon_id', 'coupon_code', 'discount_amount', 'metadata', 'entitlement_issued', 'entitlement_issuance_status') as $column) {
    ypcs_schema1_check($checks, 'existing_order_column_still_exists_' . $column, $db->field_exists($column, 'youngo_checkout_orders'));
}

$expected_new_columns = array(
    'coupon_discount_type' => 'varchar',
    'coupon_discount_value' => 'decimal',
    'selected_payment_method' => 'varchar',
    'item_title_snapshot' => 'varchar',
    'checkout_snapshot_json' => 'longtext',
);

foreach ($expected_new_columns as $column => $expected_type) {
    $exists = $db->field_exists($column, 'youngo_checkout_orders');
    $type = ypcs_schema1_column_type($db, 'youngo_checkout_orders', $column);
    ypcs_schema1_check($checks, 'new_order_column_exists_' . $column, $exists, $type);
    ypcs_schema1_check($checks, 'new_order_column_nullable_' . $column, $exists && strpos($type, 'nullable_yes') !== false, $type);
    ypcs_schema1_check($checks, 'new_order_column_type_' . $column, $exists && strpos($type, $expected_type) !== false, $type);
}

$snapshot_availability = $model->checkout_snapshot_fields_available();
ypcs_schema1_check($checks, 'model_reports_snapshot_fields_ready', !empty($snapshot_availability['ready']));
ypcs_schema1_check($checks, 'normalize_instapay_manual', $model->normalize_selected_payment_method(' Instapay Manual ') === 'instapay_manual');
ypcs_schema1_check($checks, 'normalize_card_disabled', $model->normalize_selected_payment_method('Card Disabled') === 'card_disabled');
ypcs_schema1_check($checks, 'normalize_wallet_disabled', $model->normalize_selected_payment_method('wallet-disabled') === 'wallet_disabled');
ypcs_schema1_check($checks, 'normalize_rejects_unsafe_method_text', $model->normalize_selected_payment_method('instapay<script>') === null);

$coupon_fields = $model->prepare_coupon_snapshot_fields(array(
    'coupon_code' => 'SAVE10',
    'discount_type' => 'percentage',
    'discount_value' => '10',
    'discount_amount' => '25.5',
));
ypcs_schema1_check($checks, 'coupon_snapshot_helper_prepares_code_type_value_amount', $coupon_fields['coupon_code'] === 'SAVE10' && $coupon_fields['coupon_discount_type'] === 'percentage' && $coupon_fields['coupon_discount_value'] === '10.00' && $coupon_fields['discount_amount'] === '25.50');

$snapshot = $model->build_checkout_snapshot_array(
    array(
        'id' => 123,
        'order_reference' => 'YGO-DIAG',
        'user_id' => 8,
        'order_type' => 'course_purchase',
        'course_id' => 1,
        'subtotal_amount' => '100.00',
        'total_amount' => '75.00',
        'currency' => 'EGP',
    ),
    array('title' => 'Diagnostic Course'),
    $coupon_fields,
    'instapay_manual',
    array('checkout_source' => 'diagnostic')
);
ypcs_schema1_check($checks, 'snapshot_builder_has_expected_amounts_coupon_and_method', $snapshot['original_amount'] === '100.00' && $snapshot['final_amount'] === '75.00' && $snapshot['coupon_code'] === 'SAVE10' && $snapshot['selected_payment_method'] === 'instapay_manual' && $snapshot['item_title_snapshot'] === 'Diagnostic Course');

ypcs_schema1_check($checks, 'instapay_submission_table_not_created_yet', !$db->table_exists('youngo_instapay_payment_submissions'));

$temp_order_id = null;
$details['cleanup'] = 'not_needed';

if ($db->table_exists('youngo_checkout_orders') && !in_array(false, array_map(function ($column) use ($db) {
    return $db->field_exists($column, 'youngo_checkout_orders');
}, array_keys($expected_new_columns)), true)) {
    $details['cleanup'] = 'started';
    $now = time();
    $user_id = ypcs_schema1_first_id($db, 'users', array('role_id' => 2));
    if ($user_id === null) {
        $user_id = ypcs_schema1_first_id($db, 'users');
    }
    $course_id = ypcs_schema1_first_id($db, 'course');

    $reference = 'YGO-SNAPSHOT-DIAG-' . gmdate('YmdHis') . '-' . substr(hash('sha256', (string) microtime(true)), 0, 10);
    $snapshot_json = json_encode(array(
        'source' => 'youngo_payment_coupon_checkout_snapshot_schema_1_diagnostic',
        'phase' => 'PAYMENT.COUPON.CHECKOUT.SNAPSHOT.SCHEMA.1',
        'expected_statuses_later' => array('pending_review', 'approved', 'rejected'),
    ), JSON_UNESCAPED_SLASHES);

    $data = ypcs_schema1_filter_columns($db, 'youngo_checkout_orders', array(
        'user_id' => (int) $user_id,
        'order_reference' => $reference,
        'order_type' => 'course_purchase',
        'status' => 'draft',
        'course_id' => $course_id,
        'plan_id' => null,
        'subtotal_amount' => '100.00',
        'discount_amount' => '10.00',
        'tax_amount' => '0.00',
        'total_amount' => '90.00',
        'total_amount_cents' => 9000,
        'currency' => 'EGP',
        'coupon_id' => null,
        'coupon_code' => 'DIAG10',
        'coupon_discount_type' => 'percentage',
        'coupon_discount_value' => '10.00',
        'selected_payment_method' => 'instapay_manual',
        'item_title_snapshot' => 'Diagnostic checkout snapshot course',
        'checkout_snapshot_json' => $snapshot_json,
        'payment_gateway' => null,
        'gateway_environment' => 'sandbox',
        'idempotency_key' => hash('sha256', 'PAYMENT.COUPON.CHECKOUT.SNAPSHOT.SCHEMA.1|' . $reference),
        'last_hmac_verified' => 0,
        'entitlement_issued' => 0,
        'entitlement_issuance_status' => 'not_started',
        'metadata' => json_encode(array(
            'source' => 'youngo_payment_coupon_checkout_snapshot_schema_1_diagnostic',
            'temporary' => true,
        ), JSON_UNESCAPED_SLASHES),
        'created_at' => $now,
        'updated_at' => $now,
    ));

    $inserted = $db->insert('youngo_checkout_orders', $data);
    $temp_order_id = $inserted ? (int) $db->insert_id() : null;
    ypcs_schema1_check($checks, 'temporary_order_inserted_for_snapshot_readback', $temp_order_id !== null && $temp_order_id > 0);

    if ($temp_order_id !== null && $temp_order_id > 0) {
        $row = $db->where('id', $temp_order_id)->get('youngo_checkout_orders', 1)->row_array();
        ypcs_schema1_check($checks, 'temporary_order_snapshot_fields_read_back', !empty($row) && $row['coupon_code'] === 'DIAG10' && $row['coupon_discount_type'] === 'percentage' && $row['selected_payment_method'] === 'instapay_manual' && $row['item_title_snapshot'] === 'Diagnostic checkout snapshot course');
    }
} else {
    ypcs_schema1_check($checks, 'temporary_order_inserted_for_snapshot_readback', false, 'schema_not_ready');
}

if ($temp_order_id !== null && $temp_order_id > 0) {
    $db->where('id', $temp_order_id)->delete('youngo_checkout_orders');
    $deleted = $db->where('id', $temp_order_id)->get('youngo_checkout_orders', 1)->num_rows() === 0;
    ypcs_schema1_check($checks, 'temporary_order_deleted', $deleted);
    $details['cleanup'] = $deleted ? 'completed' : 'failed';
}

$after_counts = ypcs_schema1_counts($db, $protected_tables);
ypcs_schema1_check($checks, 'protected_table_counts_restored_after_diagnostic', ypcs_schema1_counts_match($baseline_counts, $after_counts), json_encode(array('before' => $baseline_counts, 'after' => $after_counts), JSON_UNESCAPED_SLASHES));
ypcs_schema1_check($checks, 'payment_access_tables_unchanged', $baseline_counts['youngo_payment_transactions'] === $after_counts['youngo_payment_transactions'] && $baseline_counts['youngo_course_access'] === $after_counts['youngo_course_access'] && $baseline_counts['youngo_user_subscriptions'] === $after_counts['youngo_user_subscriptions'] && $baseline_counts['payment'] === $after_counts['payment'] && $baseline_counts['enrol'] === $after_counts['enrol']);
ypcs_schema1_check($checks, 'coupon_usage_table_unchanged', $baseline_counts['youngo_coupon_usages'] === $after_counts['youngo_coupon_usages']);

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo 'phase: PAYMENT.COUPON.CHECKOUT.SNAPSHOT.SCHEMA.1' . PHP_EOL;
echo 'mode: additive_schema_diagnostic_temp_order_cleaned' . PHP_EOL;
echo 'db_writes: ' . $details['db_writes'] . PHP_EOL;
echo 'cleanup: ' . $details['cleanup'] . PHP_EOL;
echo 'approved_instapay_review_statuses_later: pending_review, approved, rejected' . PHP_EOL;
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
