<?php
/**
 * PAYMENT.PAYMOB.SANDBOX.INTENTION.1 runtime diagnostic.
 *
 * Verifies hybrid Paymob sandbox Intention gates. By default, with missing
 * ignored private config or missing dashboard config, this passes fail-closed
 * checks without DB writes. If all explicit sandbox gates and config values
 * are present, it creates one local checkout order, attempts one Paymob
 * sandbox Intention request, and cleans up the local order.
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
require_once APPPATH . 'libraries/Youngo_paymob_adapter.php';
require_once APPPATH . 'models/Youngo_checkout_model.php';
require_once APPPATH . 'models/Youngo_payment_config_model.php';

$checks = array();
$details = array(
    'mode' => 'fail_closed_unless_all_explicit_sandbox_gates_present',
    'sandbox_execution' => 'not_attempted',
    'cleanup' => 'not_needed',
);

function yppsi1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => is_scalar($detail) ? (string) $detail : json_encode($detail, JSON_UNESCAPED_SLASHES),
    );
}

function yppsi1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function yppsi1_count($db, $table)
{
    if (!$db->table_exists($table)) {
        return null;
    }

    return (int) $db->count_all($table);
}

function yppsi1_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = yppsi1_count($db, $table);
    }

    return $counts;
}

function yppsi1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function yppsi1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function yppsi1_has_private_value_shape($value)
{
    if (is_array($value)) {
        foreach ($value as $child) {
            if (yppsi1_has_private_value_shape($child)) {
                return true;
            }
        }

        return false;
    }

    $value = (string) $value;
    return $value !== '' && (bool) preg_match('/(PAYMOB_SECRET_KEY=|PAYMOB_HMAC_SECRET=|sk_live|sk_test|[A-Fa-f0-9]{96,})/', $value);
}

function yppsi1_fixture($db)
{
    $user = $db
        ->select('id, email, first_name, last_name, role_id, status, is_instructor')
        ->where('email', 'qa.learner@youngo.local')
        ->where('role_id', 2)
        ->where('status', 1)
        ->where('is_instructor', 0)
        ->get('users', 1)
        ->row_array();

    $course = $db
        ->select('id, title, price, discounted_price, discount_flag, is_free_course, status, youngo_access_mode')
        ->where('id', 9)
        ->where('status', 'active')
        ->where_in('youngo_access_mode', array('purchase_only', 'subscription_and_purchase'))
        ->group_start()
        ->where('is_free_course IS NULL', null, false)
        ->or_where('is_free_course', 0)
        ->group_end()
        ->get('course', 1)
        ->row_array();

    if (empty($user) || empty($course)) {
        return array();
    }

    $amount = !empty($course['discount_flag']) && (float) $course['discounted_price'] > 0
        ? (float) $course['discounted_price']
        : (float) $course['price'];

    return array(
        'user' => $user,
        'course' => $course,
        'amount' => $amount,
    );
}

function yppsi1_delete_order($db, $order_id)
{
    if ($order_id <= 0 || !$db->table_exists('youngo_checkout_orders')) {
        return;
    }

    $db->where('id', (int) $order_id)->delete('youngo_checkout_orders');
}

class Yppsi1_config_stub
{
    public function item($key)
    {
        return $key === 'encryption_key' ? '' : null;
    }
}

$paths = array(
    'default_config' => $root . '/application/config/youngo_paymob.php',
    'local_config' => $root . '/application/config/youngo_paymob.local.php',
    'adapter' => $root . '/application/libraries/Youngo_paymob_adapter.php',
    'controller' => $root . '/application/controllers/Youngo_checkout.php',
    'model' => $root . '/application/models/Youngo_checkout_model.php',
    'view' => $root . '/application/views/frontend/youngo/checkout_order.php',
    'gitignore' => $root . '/.gitignore',
);

foreach ($paths as $name => $path) {
    if ($name !== 'local_config') {
        yppsi1_check($checks, 'file_exists_' . $name, is_file($path), $path);
    }
}

$source = yppsi1_read($paths['adapter']) . "\n" . yppsi1_read($paths['controller']) . "\n" . yppsi1_read($paths['model']) . "\n" . yppsi1_read($paths['view']);
$default_config_source = yppsi1_read($paths['default_config']);
$gitignore_source = yppsi1_read($paths['gitignore']);

yppsi1_check($checks, 'production_defaults_disabled', strpos($default_config_source, "'enabled' => false") !== false && strpos($default_config_source, "'network_enabled' => false") !== false && strpos($default_config_source, "'checkout_cta_enabled' => false") !== false);
yppsi1_check($checks, 'local_config_ignored', strpos($gitignore_source, 'application/config/youngo_paymob.local.php') !== false);
yppsi1_check($checks, 'adapter_has_sandbox_intention_method', strpos($source, 'create_sandbox_intention') !== false);
yppsi1_check($checks, 'controller_uses_existing_order_model', strpos($source, 'create_or_reuse_draft_order') !== false && strpos($source, 'mark_awaiting_webhook_from_paymob_intention') !== false);
yppsi1_check($checks, 'no_legacy_gateway_model_usage', stripos($source, 'payment_gateways') === false);
yppsi1_check($checks, 'no_legacy_payment_or_enrol_writes_in_controller', strpos(yppsi1_read($paths['controller']), "->insert('payment'") === false && strpos(yppsi1_read($paths['controller']), "->insert('enrol'") === false);
yppsi1_check($checks, 'safe_source_has_no_raw_private_values', !yppsi1_has_private_value_shape($source));

$CI = new stdClass();
$CI->config = new Yppsi1_config_stub();
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(yppsi1_db_config(), true);
$CI->db = $db;

$config_model = new Youngo_payment_config_model();
$config_model->db = $db;
$checkout_model = new Youngo_checkout_model(array('db' => $db));

$protected_tables = array(
    'youngo_checkout_orders',
    'youngo_payment_transactions',
    'youngo_course_access',
    'payment',
    'enrol',
);
$baseline_counts = yppsi1_counts($db, $protected_tables);

$dashboard_result = $config_model->get_provider_config('paymob', 'sandbox');
$dashboard_config = !empty($dashboard_result['ok']) && !empty($dashboard_result['data']['config'])
    ? $dashboard_result['data']['config']
    : array();
$dashboard_exists = !empty($dashboard_result['data']['exists']);

$default_reader = new Youngo_paymob_config(array(
    'load_local_override' => false,
    'config' => array(
        'enabled' => true,
        'network_enabled' => true,
        'sandbox_network_testing_enabled' => true,
        'checkout_routes_enabled' => true,
        'checkout_local_testing_enabled' => true,
        'mode' => 'sandbox',
        'currency' => 'EGP',
    ),
));
$missing_dashboard_adapter = new Youngo_paymob_adapter(array(
    'config_reader' => $default_reader,
    'dashboard_config' => array(),
    'dashboard_config_exists' => false,
));
$missing_dashboard = $missing_dashboard_adapter->get_sandbox_readiness();
yppsi1_check($checks, 'missing_dashboard_config_fails_closed', empty($missing_dashboard['ok']) && $missing_dashboard['code'] === 'blocked_missing_dashboard_config', isset($missing_dashboard['code']) ? $missing_dashboard['code'] : '');

$fake_dashboard_config = array(
    'card_integration_id_egp' => '123456',
    'wallet_integration_id_egp' => '654321',
    'api_base_url' => 'https://accept.paymob.com',
    'checkout_base_url' => 'https://accept.paymob.com',
    'return_url' => 'http://school.local/youngo/checkout/return/{order_reference}',
    'notification_url' => 'https://example.invalid/payment/paymob/webhook',
);
$missing_private_adapter = new Youngo_paymob_adapter(array(
    'config_reader' => $default_reader,
    'dashboard_config' => $fake_dashboard_config,
    'dashboard_config_exists' => true,
));
$missing_private = $missing_private_adapter->get_sandbox_readiness();
yppsi1_check($checks, 'missing_private_config_fails_closed', empty($missing_private['ok']) && $missing_private['code'] === 'blocked_missing_private_config', isset($missing_private['code']) ? $missing_private['code'] : '');

$actual_reader = new Youngo_paymob_config(array('load_local_override' => true));
$actual_adapter = new Youngo_paymob_adapter(array(
    'config_reader' => $actual_reader,
    'dashboard_config' => $dashboard_config,
    'dashboard_config_exists' => $dashboard_exists,
));
$actual_readiness = $actual_adapter->get_sandbox_readiness();
$details['actual_readiness_code'] = isset($actual_readiness['code']) ? $actual_readiness['code'] : null;
$details['dashboard_config_exists'] = $dashboard_exists;
$details['local_override_loaded'] = !empty($actual_reader->get_safe_diagnostic_summary()['local_override_loaded']);
$details['actual_readiness_missing'] = isset($actual_readiness['missing']) ? $actual_readiness['missing'] : array();

$created_order_id = 0;
try {
    if (!empty($actual_readiness['ok'])) {
        $fixture = yppsi1_fixture($db);
        yppsi1_check($checks, 'fixture_available_for_sandbox_attempt', !empty($fixture), empty($fixture) ? 'fixture_missing' : 'fixture_selected');

        if (!empty($fixture)) {
            $order_result = $checkout_model->create_draft_order((int) $fixture['user']['id'], (int) $fixture['course']['id'], $fixture['amount'], 'EGP');
            yppsi1_check($checks, 'diagnostic_order_created', !empty($order_result['ok']), isset($order_result['code']) ? $order_result['code'] : '');
            if (!empty($order_result['ok'])) {
                $created_order_id = (int) $order_result['data']['order_id'];
                $order = $checkout_model->get_order($created_order_id);
                $customer = array(
                    'first_name' => isset($fixture['user']['first_name']) ? $fixture['user']['first_name'] : 'YounGo',
                    'last_name' => isset($fixture['user']['last_name']) ? $fixture['user']['last_name'] : 'Learner',
                    'email' => isset($fixture['user']['email']) ? $fixture['user']['email'] : 'learner@example.invalid',
                    'phone_number' => 'NA',
                );

                $intention = $actual_adapter->create_sandbox_intention($order, $customer);
                $details['sandbox_execution'] = !empty($intention['ok']) ? 'created' : 'attempted_structured_error';
                $details['sandbox_execution_code'] = isset($intention['code']) ? $intention['code'] : null;
                $details['checkout_url_present'] = !empty($intention['data']['checkout_url']);
                $details['provider_intent_id_present'] = !empty($intention['data']['provider_intent_id']);
                yppsi1_check($checks, 'sandbox_attempt_returns_structured_result', is_array($intention) && array_key_exists('ok', $intention) && isset($intention['code']), isset($intention['code']) ? $intention['code'] : '');
                yppsi1_check($checks, 'sandbox_attempt_redacts_private_values', !yppsi1_has_private_value_shape($intention));

                if (!empty($intention['ok'])) {
                    $mark = $checkout_model->mark_awaiting_webhook_from_paymob_intention($created_order_id, $intention);
                    yppsi1_check($checks, 'order_marked_awaiting_webhook_after_intention', !empty($mark['ok']), isset($mark['code']) ? $mark['code'] : '');
                }
            }
        }
    } else {
        $details['sandbox_execution'] = 'blocked_' . (isset($actual_readiness['code']) ? $actual_readiness['code'] : 'not_ready');
        yppsi1_check($checks, 'actual_config_fails_closed_or_ready', in_array($actual_readiness['code'], array(
            'paymob_network_disabled_in_this_phase',
            'paymob_network_must_remain_disabled',
            'blocked_missing_dashboard_config',
            'blocked_missing_private_config',
            'paymob_sandbox_gates_not_ready',
            'paymob_sandbox_ready',
        ), true), isset($actual_readiness['code']) ? $actual_readiness['code'] : '');
    }
} finally {
    if ($created_order_id > 0) {
        yppsi1_delete_order($db, $created_order_id);
        $details['cleanup'] = 'diagnostic_order_deleted';
    }
}

$after_counts = yppsi1_counts($db, $protected_tables);
$details['baseline_counts'] = $baseline_counts;
$details['after_counts'] = $after_counts;

yppsi1_check($checks, 'protected_counts_restored', yppsi1_counts_match($baseline_counts, $after_counts), array(
    'before' => $baseline_counts,
    'after' => $after_counts,
));
yppsi1_check($checks, 'no_payment_transactions_created', $after_counts['youngo_payment_transactions'] === $baseline_counts['youngo_payment_transactions']);
yppsi1_check($checks, 'no_entitlement_created', $after_counts['youngo_course_access'] === $baseline_counts['youngo_course_access']);
yppsi1_check($checks, 'no_legacy_payment_or_enrol_rows_created', $after_counts['payment'] === $baseline_counts['payment'] && $after_counts['enrol'] === $baseline_counts['enrol']);

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

$result = array(
    'ok' => empty($failed),
    'failed_checks' => array_keys($failed),
    'checks' => $checks,
    'details' => $details,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(empty($failed) ? 0 : 1);
