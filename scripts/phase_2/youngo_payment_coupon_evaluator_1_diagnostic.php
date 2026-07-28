<?php
/**
 * PAYMENT.COUPON.EVALUATOR.1 diagnostic.
 *
 * Inserts temporary coupon fixtures, evaluates them through the read-only
 * evaluator, deletes the fixtures, and verifies checkout/payment/access rows
 * remain unchanged.
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

$checks = array();
$fixture_coupon_ids = array();
$fixture_usage_ids = array();
$fixture_course_rule_ids = array();
$fixture_plan_rule_ids = array();
$details = array(
    'db_writes' => 'temporary_coupon_fixture_rows_inserted_then_deleted',
    'cleanup' => 'not_started',
);

function ypce1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypce1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypce1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypce1_count($db, $table)
{
    return $db->table_exists($table) ? (int) $db->count_all($table) : null;
}

function ypce1_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ypce1_count($db, $table);
    }

    return $counts;
}

function ypce1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ypce1_filter_columns($db, $table, $data)
{
    $filtered = array();
    foreach ($data as $field => $value) {
        if ($db->field_exists($field, $table)) {
            $filtered[$field] = $value;
        }
    }

    return $filtered;
}

function ypce1_first_id($db, $table, $where = array())
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

function ypce1_insert_coupon($db, $code, $discount_percentage, $discount_type, $discount_value, $scope, $status, $expiry_date, $max_usage_count = null)
{
    $now = time();
    $data = ypce1_filter_columns($db, 'coupons', array(
        'code' => $code,
        'discount_percentage' => $discount_percentage,
        'created_at' => $now,
        'expiry_date' => $expiry_date,
        'discount_type' => $discount_type,
        'discount_value' => $discount_value,
        'scope' => $scope,
        'max_usage_count' => $max_usage_count,
        'status' => $status,
        'updated_at' => $now,
    ));

    $db->insert('coupons', $data);
    return (int) $db->insert_id();
}

class Ypce1_session_stub
{
    public $values = array();

    public function userdata($key)
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : null;
    }

    public function set_userdata($key, $value)
    {
        $this->values[$key] = $value;
    }
}

$CI = new stdClass();
$CI->session = new Ypce1_session_stub();
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ypce1_db_config(), true);
$CI->db = $db;
$model = new Youngo_coupon_evaluator_model(array('db' => $db));

$paths = array(
    'evaluator_model' => $root . '/application/models/Youngo_coupon_evaluator_model.php',
    'checkout_model' => $root . '/application/models/Youngo_checkout_model.php',
    'checkout_controller' => $root . '/application/controllers/Youngo_checkout.php',
    'home_controller' => $root . '/application/controllers/Home.php',
    'payment_controller' => $root . '/application/controllers/Payment.php',
    'payment_model' => $root . '/application/models/Youngo_payment_model.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
    'routes' => $root . '/application/config/routes.php',
);

foreach ($paths as $name => $path) {
    ypce1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

$evaluator_source = ypce1_read($paths['evaluator_model']);
$checkout_controller_source = ypce1_read($paths['checkout_controller']);
$payment_model_source = ypce1_read($paths['payment_model']);
$paymob_config_source = ypce1_read($paths['paymob_config']);
$routes_source = ypce1_read($paths['routes']);

ypce1_check($checks, 'evaluator_model_loads', $model instanceof Youngo_coupon_evaluator_model);
ypce1_check($checks, 'evaluate_method_exists', method_exists($model, 'evaluate_coupon_for_checkout'));
ypce1_check($checks, 'evaluator_source_has_no_session_or_cart_dependency', stripos($evaluator_source, 'session') === false && stripos($evaluator_source, 'cart') === false && stripos($evaluator_source, 'applied_coupon') === false);
ypce1_check($checks, 'evaluator_source_has_no_db_write_calls', strpos($evaluator_source, '->insert(') === false && strpos($evaluator_source, '->update(') === false && strpos($evaluator_source, '->delete(') === false);
ypce1_check($checks, 'checkout_controller_not_wired_to_evaluator_yet', stripos($checkout_controller_source, 'Youngo_coupon_evaluator_model') === false && stripos($checkout_controller_source, 'evaluate_coupon_for_checkout') === false);
ypce1_check($checks, 'routes_have_no_coupon_checkout_route', stripos($routes_source, 'youngo/checkout/coupon') === false && stripos($routes_source, 'coupon/evaluate') === false);
ypce1_check($checks, 'payment_model_not_wired_to_evaluator_or_instapay', stripos($payment_model_source, 'Youngo_coupon_evaluator_model') === false && stripos($payment_model_source, 'instapay') === false);

$reader = new Youngo_paymob_config(array('load_local_override' => false));
ypce1_check($checks, 'paymob_default_enabled_false', $reader->is_enabled() === false);
ypce1_check($checks, 'paymob_default_network_false', $reader->is_network_enabled() === false);
ypce1_check($checks, 'paymob_default_checkout_cta_false', $reader->is_checkout_cta_enabled() === false);
ypce1_check($checks, 'paymob_static_gates_false', preg_match('/[\'"]enabled[\'"]\s*=>\s*false\b/', $paymob_config_source) === 1 && preg_match('/[\'"]network_enabled[\'"]\s*=>\s*false\b/', $paymob_config_source) === 1);

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

$baseline_counts = ypce1_counts($db, $protected_tables);

ypce1_check($checks, 'coupon_table_exists', $db->table_exists('coupons'));
foreach (array('id', 'code', 'discount_percentage', 'expiry_date', 'discount_type', 'discount_value', 'scope', 'max_usage_count', 'status', 'updated_at') as $column) {
    ypce1_check($checks, 'coupon_column_exists_' . $column, $db->field_exists($column, 'coupons'));
}
ypce1_check($checks, 'phase2_coupon_scope_tables_exist', $db->table_exists('youngo_coupon_courses') && $db->table_exists('youngo_coupon_subscription_plans') && $db->table_exists('youngo_coupon_usages'));

$user_id = ypce1_first_id($db, 'users', array('role_id' => 2));
if ($user_id === null) {
    $user_id = ypce1_first_id($db, 'users');
}
$course_id = ypce1_first_id($db, 'course');
$plan_id = ypce1_first_id($db, 'youngo_subscription_plans');

ypce1_check($checks, 'fixture_user_course_plan_available', $user_id !== null && $course_id !== null && $plan_id !== null, 'user=' . $user_id . ', course=' . $course_id . ', plan=' . $plan_id);

if ($db->table_exists('coupons') && $user_id !== null && $course_id !== null && $plan_id !== null) {
    $details['cleanup'] = 'started';
    $future = strtotime('+30 days');
    $past = strtotime('-30 days');
    $prefix = 'YCEVAL1_' . gmdate('YmdHis') . '_';

    $valid_percentage_id = ypce1_insert_coupon($db, $prefix . 'PCT', '25', 'percentage', '25.00', 'both', 'active', $future);
    $fixed_over_id = ypce1_insert_coupon($db, $prefix . 'FIXED', '0', 'fixed', '150.00', 'course_purchase', 'active', $future);
    $expired_id = ypce1_insert_coupon($db, $prefix . 'EXPIRED', '10', 'percentage', '10.00', 'both', 'active', $past);
    $inactive_id = ypce1_insert_coupon($db, $prefix . 'INACTIVE', '10', 'percentage', '10.00', 'both', 'inactive', $future);
    $usage_id = ypce1_insert_coupon($db, $prefix . 'USED', '10', 'percentage', '10.00', 'both', 'active', $future, 1);
    $course_scope_id = ypce1_insert_coupon($db, $prefix . 'COURSE', '15', 'percentage', '15.00', 'course_purchase', 'active', $future);
    $subscription_id = ypce1_insert_coupon($db, $prefix . 'SUB', '0', 'fixed', '20.00', 'subscription', 'active', $future);

    $fixture_coupon_ids = array($valid_percentage_id, $fixed_over_id, $expired_id, $inactive_id, $usage_id, $course_scope_id, $subscription_id);

    $db->insert('youngo_coupon_usages', ypce1_filter_columns($db, 'youngo_coupon_usages', array(
        'coupon_id' => $usage_id,
        'coupon_code' => $prefix . 'USED',
        'user_id' => $user_id,
        'discount_amount' => '10.00',
        'used_at' => time(),
    )));
    $fixture_usage_ids[] = (int) $db->insert_id();

    $db->insert('youngo_coupon_courses', ypce1_filter_columns($db, 'youngo_coupon_courses', array(
        'coupon_id' => $course_scope_id,
        'course_id' => $course_id,
        'rule_type' => 'include',
    )));
    $fixture_course_rule_ids[] = (int) $db->insert_id();

    $db->insert('youngo_coupon_subscription_plans', ypce1_filter_columns($db, 'youngo_coupon_subscription_plans', array(
        'coupon_id' => $subscription_id,
        'plan_id' => $plan_id,
    )));
    $fixture_plan_rule_ids[] = (int) $db->insert_id();

    $valid = $model->evaluate_coupon_for_checkout($user_id, 'course', $course_id, '100.00', $prefix . 'PCT', 'EGP');
    ypce1_check($checks, 'valid_percentage_coupon_calculates_snapshot', !empty($valid['valid']) && $valid['discount_type'] === 'percentage' && $valid['discount_value'] === '25.00' && $valid['discount_amount'] === '25.00' && $valid['final_amount'] === '75.00' && $valid['snapshot']['coupon_code'] === $prefix . 'PCT');

    $missing = $model->evaluate_coupon_for_checkout($user_id, 'course', $course_id, '100.00', $prefix . 'MISSING', 'EGP');
    ypce1_check($checks, 'missing_coupon_invalid_safely', empty($missing['valid']) && $missing['error_code'] === 'coupon_not_found' && $missing['final_amount'] === '100.00');

    $expired = $model->evaluate_coupon_for_checkout($user_id, 'course', $course_id, '100.00', $prefix . 'EXPIRED', 'EGP');
    ypce1_check($checks, 'expired_coupon_invalid', empty($expired['valid']) && $expired['error_code'] === 'coupon_expired');

    $inactive = $model->evaluate_coupon_for_checkout($user_id, 'course', $course_id, '100.00', $prefix . 'INACTIVE', 'EGP');
    ypce1_check($checks, 'inactive_coupon_invalid', empty($inactive['valid']) && $inactive['error_code'] === 'coupon_inactive');

    $over = $model->evaluate_coupon_for_checkout($user_id, 'course', $course_id, '100.00', $prefix . 'FIXED', 'EGP');
    ypce1_check($checks, 'over_discount_clamps_to_original_amount', !empty($over['valid']) && $over['discount_type'] === 'fixed' && $over['discount_amount'] === '100.00' && $over['final_amount'] === '0.00' && !empty($over['zero_final_amount_policy_not_enabled']) && !empty($over['snapshot']['calculation']['discount_clamped']));

    $usage = $model->evaluate_coupon_for_checkout($user_id, 'course', $course_id, '100.00', $prefix . 'USED', 'EGP');
    ypce1_check($checks, 'usage_limit_reached_invalid', empty($usage['valid']) && $usage['error_code'] === 'coupon_usage_limit_reached');

    $scope = $model->evaluate_coupon_for_checkout($user_id, 'course', $course_id, '100.00', $prefix . 'COURSE', 'EGP');
    ypce1_check($checks, 'course_scope_include_valid_for_matching_course', !empty($scope['valid']) && $scope['discount_amount'] === '15.00');

    $subscription = $model->evaluate_coupon_for_checkout($user_id, 'subscription', $plan_id, '100.00', $prefix . 'SUB', 'EGP');
    ypce1_check($checks, 'subscription_fixed_coupon_valid_for_matching_plan', !empty($subscription['valid']) && $subscription['discount_type'] === 'fixed' && $subscription['discount_amount'] === '20.00' && $subscription['final_amount'] === '80.00');

    $currency = $model->evaluate_coupon_for_checkout($user_id, 'course', $course_id, '100.00', $prefix . 'PCT', 'USD');
    ypce1_check($checks, 'non_egp_currency_rejected', empty($currency['valid']) && $currency['error_code'] === 'unsupported_currency');

    ypce1_check($checks, 'session_not_mutated_by_evaluator', empty($CI->session->values));
}

if (!empty($fixture_usage_ids)) {
    $db->where_in('id', $fixture_usage_ids)->delete('youngo_coupon_usages');
}
if (!empty($fixture_course_rule_ids)) {
    $db->where_in('id', $fixture_course_rule_ids)->delete('youngo_coupon_courses');
}
if (!empty($fixture_plan_rule_ids)) {
    $db->where_in('id', $fixture_plan_rule_ids)->delete('youngo_coupon_subscription_plans');
}
if (!empty($fixture_coupon_ids)) {
    $db->where_in('id', $fixture_coupon_ids)->delete('coupons');
}

$after_counts = ypce1_counts($db, $protected_tables);
$details['cleanup'] = ypce1_counts_match($baseline_counts, $after_counts) ? 'completed' : 'failed';
ypce1_check($checks, 'temporary_coupon_fixtures_deleted', ypce1_counts_match($baseline_counts, $after_counts), json_encode(array('before' => $baseline_counts, 'after' => $after_counts), JSON_UNESCAPED_SLASHES));
ypce1_check($checks, 'checkout_payment_access_counts_unchanged', $baseline_counts['youngo_checkout_orders'] === $after_counts['youngo_checkout_orders'] && $baseline_counts['youngo_payment_transactions'] === $after_counts['youngo_payment_transactions'] && $baseline_counts['youngo_course_access'] === $after_counts['youngo_course_access'] && $baseline_counts['youngo_user_subscriptions'] === $after_counts['youngo_user_subscriptions'] && $baseline_counts['payment'] === $after_counts['payment'] && $baseline_counts['enrol'] === $after_counts['enrol']);

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo 'phase: PAYMENT.COUPON.EVALUATOR.1' . PHP_EOL;
echo 'mode: evaluator_fixture_diagnostic_temp_coupons_cleaned' . PHP_EOL;
echo 'db_writes: ' . $details['db_writes'] . PHP_EOL;
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
