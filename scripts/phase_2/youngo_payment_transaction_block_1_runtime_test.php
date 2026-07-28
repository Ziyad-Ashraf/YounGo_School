<?php
/**
 * PAYMENT.TRANSACTION.BLOCK.1 runtime diagnostic.
 *
 * Creates one local diagnostic checkout order, processes fake Paymob webhook
 * fixtures, records transaction rows, verifies duplicate handling, then
 * deletes diagnostic rows. No Paymob calls, secrets, entitlements, legacy
 * payment/enrol writes, public CTA exposure, or live system changes.
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
require_once APPPATH . 'libraries/Youngo_paymob_webhook.php';
require_once APPPATH . 'libraries/Youngo_paymob_fixture_processor.php';
require_once APPPATH . 'models/Youngo_checkout_model.php';
require_once APPPATH . 'models/Youngo_payment_model.php';

$checks = array();
$details = array(
    'db_writes' => 'one_diagnostic_checkout_order_and_fixture_transactions_created_then_deleted',
    'network_requests' => 'none',
    'cleanup' => 'not_started',
);

function yptb1_add_check(&$checks, $name, $passed, $detail = '')
{
    $checks[$name] = array(
        'status' => $passed ? 'PASS' : 'FAIL',
        'detail' => $detail,
    );
}

function yptb1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function yptb1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function yptb1_load_json($path)
{
    if (!is_file($path)) {
        return null;
    }

    $payload = json_decode(file_get_contents($path), true);
    return json_last_error() === JSON_ERROR_NONE ? $payload : null;
}

function yptb1_table_exists($db, $table)
{
    return $db->table_exists($table);
}

function yptb1_table_count($db, $table)
{
    if (!yptb1_table_exists($db, $table)) {
        return null;
    }

    return (int) $db->count_all($table);
}

function yptb1_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = yptb1_table_count($db, $table);
    }

    return $counts;
}

function yptb1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function yptb1_auto_increment($db, $table)
{
    $query = $db->query(
        'SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        array($table)
    );
    $row = $query ? $query->row_array() : array();

    return isset($row['AUTO_INCREMENT']) ? (int) $row['AUTO_INCREMENT'] : null;
}

function yptb1_select_fixture($db)
{
    $user = $db
        ->select('id, email, role_id, status, is_instructor')
        ->where('id', 8)
        ->where('role_id', 2)
        ->where('status', 1)
        ->where('is_instructor', 0)
        ->get('users', 1)
        ->row_array();

    if (empty($user)) {
        $user = $db
            ->select('id, email, role_id, status, is_instructor')
            ->where('id <>', 1)
            ->where('role_id', 2)
            ->where('status', 1)
            ->where('is_instructor', 0)
            ->order_by('id', 'asc')
            ->get('users', 1)
            ->row_array();
    }

    $course = $db
        ->select('id, title, price, discounted_price, discount_flag, is_free_course, status, youngo_access_mode')
        ->where('id', 9)
        ->where_in('youngo_access_mode', array('purchase_only', 'subscription_and_purchase'))
        ->where('status', 'active')
        ->group_start()
        ->where('is_free_course IS NULL', null, false)
        ->or_where('is_free_course', 0)
        ->group_end()
        ->get('course', 1)
        ->row_array();

    if (empty($course)) {
        $course = $db
            ->select('id, title, price, discounted_price, discount_flag, is_free_course, status, youngo_access_mode')
            ->where_in('youngo_access_mode', array('purchase_only', 'subscription_and_purchase'))
            ->where('status', 'active')
            ->group_start()
            ->where('is_free_course IS NULL', null, false)
            ->or_where('is_free_course', 0)
            ->group_end()
            ->order_by('id', 'asc')
            ->get('course', 1)
            ->row_array();
    }

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
        'amount_cents' => (int) round($amount * 100),
    );
}

function yptb1_prepare_payload($webhook, $payload, $order_reference, $amount_cents, $secret)
{
    if (!is_array($payload)) {
        return array();
    }

    $payload['amount_cents'] = (int) $amount_cents;
    if (!isset($payload['order']) || !is_array($payload['order'])) {
        $payload['order'] = array();
    }
    $payload['order']['merchant_order_id'] = (string) $order_reference;
    $payload['hmac'] = '';

    $calculated = $webhook->calculate_hmac($payload, $secret);
    if (empty($calculated['ok']) || empty($calculated['data']['hmac'])) {
        return array();
    }

    $payload['hmac'] = $calculated['data']['hmac'];
    return $payload;
}

function yptb1_has_remote_network_code($source)
{
    foreach (array('curl_exec', 'curl_init', 'file_get_contents("http', "file_get_contents('http", 'fsockopen', 'stream_socket_client', 'GuzzleHttp', 'vendor/autoload') as $pattern) {
        if (stripos($source, $pattern) !== false) {
            return true;
        }
    }

    return false;
}

function yptb1_transaction_status_counts($db, $order_id)
{
    $query = $db
        ->select('status, COUNT(*) AS count_value')
        ->where('checkout_order_id', (int) $order_id)
        ->group_by('status')
        ->get('youngo_payment_transactions');

    $counts = array();
    foreach ($query->result_array() as $row) {
        $counts[$row['status']] = (int) $row['count_value'];
    }

    return $counts;
}

$paths = array(
    'payment_model' => APPPATH . 'models/Youngo_payment_model.php',
    'checkout_model' => APPPATH . 'models/Youngo_checkout_model.php',
    'config_reader' => APPPATH . 'libraries/Youngo_paymob_config.php',
    'webhook_library' => APPPATH . 'libraries/Youngo_paymob_webhook.php',
    'adapter' => APPPATH . 'libraries/Youngo_paymob_adapter.php',
    'fixture_processor' => APPPATH . 'libraries/Youngo_paymob_fixture_processor.php',
    'webhook_controller' => APPPATH . 'controllers/Youngo_payment_webhook.php',
    'routes' => APPPATH . 'config/routes.php',
    'success_fixture' => $root . '/scripts/phase_2/fixtures/paymob/success_verified_payload.json',
    'duplicate_fixture' => $root . '/scripts/phase_2/fixtures/paymob/duplicate_success_payload.json',
    'failed_fixture' => $root . '/scripts/phase_2/fixtures/paymob/failed_payment_payload.json',
    'malformed_fixture' => $root . '/scripts/phase_2/fixtures/paymob/malformed_payload.json',
    'home_controller' => APPPATH . 'controllers/Home.php',
    'course_page' => APPPATH . 'views/frontend/youngo/course_page.php',
    'course_card' => APPPATH . 'views/frontend/youngo/course_listing/course_card.php',
);

$db_obj = null;
$checkout_model = null;
$payment_model = null;
$diagnostic_order_id = null;
$diagnostic_order_reference = null;
$before_counts = array();
$cleanup_deleted_order = false;
$cleanup_deleted_transactions = false;
$diagnostic_secret = 'youngo-payment-transaction-block-diagnostic-secret';

$protected_tables = array(
    'youngo_checkout_orders',
    'youngo_payment_transactions',
    'youngo_course_access',
    'youngo_user_subscriptions',
    'youngo_manual_grants',
    'youngo_coupon_usages',
    'payment',
    'enrol',
    'watch_histories',
    'watched_duration',
);

try {
    foreach ($paths as $name => $path) {
        yptb1_add_check($checks, 'file_exists_' . $name, is_file($path), $path);
    }

    $db_config = yptb1_db_config();
    $db_obj = DB($db_config, true);
    $checkout_model = new Youngo_checkout_model(array('db' => $db_obj));
    $payment_model = new Youngo_payment_model(array('db' => $db_obj));
    $config_reader = new Youngo_paymob_config(array('load_local_override' => false));
    $webhook = new Youngo_paymob_webhook(array(
        'diagnostic_hmac_secret' => $diagnostic_secret,
        'config_reader' => $config_reader,
    ));
    $processor = new Youngo_paymob_fixture_processor(array(
        'webhook' => $webhook,
        'checkout_model' => $checkout_model,
        'payment_model' => $payment_model,
    ));

    yptb1_add_check($checks, 'payment_model_loads', $payment_model instanceof Youngo_payment_model);
    yptb1_add_check($checks, 'fixture_processor_loads', $processor instanceof Youngo_paymob_fixture_processor);
    yptb1_add_check($checks, 'config_default_disabled', $config_reader->is_enabled() === false);
    yptb1_add_check($checks, 'config_webhook_testing_disabled', $config_reader->get('webhook_testing_enabled', false) === false);
    yptb1_add_check($checks, 'config_currency_egp', $config_reader->get_currency() === 'EGP');

    $schema = $payment_model->get_schema_readiness();
    $details['payment_schema_ready'] = !empty($schema['ready']);
    yptb1_add_check($checks, 'payment_schema_ready', !empty($schema['ready']));

    foreach (array('record_received_transaction', 'mark_transaction_verified', 'mark_transaction_rejected', 'mark_transaction_duplicate', 'get_transaction', 'get_transaction_by_gateway_reference', 'get_transactions_for_order', 'is_duplicate_gateway_event', 'get_safe_transaction_summary') as $method) {
        yptb1_add_check($checks, 'payment_model_method_exists_' . $method, method_exists($payment_model, $method));
    }

    $source_bundle = yptb1_read($paths['payment_model']) . yptb1_read($paths['fixture_processor']) . yptb1_read($paths['webhook_controller']);
    yptb1_add_check($checks, 'no_paymob_network_code_in_transaction_block', !yptb1_has_remote_network_code($source_bundle));
    yptb1_add_check($checks, 'public_route_controller_not_wired_to_processor', strpos(yptb1_read($paths['webhook_controller']), 'Youngo_paymob_fixture_processor') === false && strpos(yptb1_read($paths['webhook_controller']), 'Youngo_payment_model') === false);

    $before_counts = yptb1_counts($db_obj, $protected_tables);
    $before_checkout_auto_increment = yptb1_auto_increment($db_obj, 'youngo_checkout_orders');
    $before_transaction_auto_increment = yptb1_auto_increment($db_obj, 'youngo_payment_transactions');
    $details['protected_counts_before'] = $before_counts;
    $details['checkout_auto_increment_before'] = $before_checkout_auto_increment;
    $details['transaction_auto_increment_before'] = $before_transaction_auto_increment;

    $fixture = yptb1_select_fixture($db_obj);
    if (empty($fixture)) {
        yptb1_add_check($checks, 'fixture_available', false, 'No safe learner/course fixture found.');
    } else {
        $details['fixture'] = array(
            'user_id' => (int) $fixture['user']['id'],
            'user_email' => $fixture['user']['email'],
            'course_id' => (int) $fixture['course']['id'],
            'course_title' => $fixture['course']['title'],
            'course_mode' => $fixture['course']['youngo_access_mode'],
            'amount' => number_format($fixture['amount'], 2, '.', ''),
            'currency' => 'EGP',
        );

        yptb1_add_check($checks, 'fixture_available', true);
        yptb1_add_check($checks, 'fixture_user_not_root', (int) $fixture['user']['id'] !== 1 && (int) $fixture['user']['role_id'] === 2);
        yptb1_add_check($checks, 'fixture_course_purchase_compatible', in_array($fixture['course']['youngo_access_mode'], array('purchase_only', 'subscription_and_purchase'), true));

        $create = $checkout_model->create_draft_order((int) $fixture['user']['id'], (int) $fixture['course']['id'], $fixture['amount'], 'EGP');
        $details['create_order_code'] = isset($create['code']) ? $create['code'] : null;
        yptb1_add_check($checks, 'diagnostic_order_created', !empty($create['ok']) && isset($create['data']['order_id'], $create['data']['order_reference']));

        if (!empty($create['ok'])) {
            $diagnostic_order_id = (int) $create['data']['order_id'];
            $diagnostic_order_reference = (string) $create['data']['order_reference'];
            $details['diagnostic_order_id'] = $diagnostic_order_id;
            $details['diagnostic_order_reference'] = $diagnostic_order_reference;

            $pending = $checkout_model->mark_pending_gateway($diagnostic_order_id, 'paymob', 'diagnostic-fixture-intention');
            $awaiting = $checkout_model->mark_awaiting_webhook($diagnostic_order_id);
            yptb1_add_check($checks, 'diagnostic_order_pending_gateway', !empty($pending['ok']));
            yptb1_add_check($checks, 'diagnostic_order_awaiting_webhook', !empty($awaiting['ok']));

            $success_payload = yptb1_prepare_payload($webhook, yptb1_load_json($paths['success_fixture']), $diagnostic_order_reference, $fixture['amount_cents'], $diagnostic_secret);
            $duplicate_payload = yptb1_prepare_payload($webhook, yptb1_load_json($paths['duplicate_fixture']), $diagnostic_order_reference, $fixture['amount_cents'], $diagnostic_secret);
            $failed_payload = yptb1_prepare_payload($webhook, yptb1_load_json($paths['failed_fixture']), $diagnostic_order_reference, $fixture['amount_cents'], $diagnostic_secret);
            $malformed_payload = yptb1_load_json($paths['malformed_fixture']);

            yptb1_add_check($checks, 'success_payload_prepared_with_fake_hmac', !empty($success_payload['hmac']));
            yptb1_add_check($checks, 'duplicate_payload_prepared_with_fake_hmac', !empty($duplicate_payload['hmac']));
            yptb1_add_check($checks, 'failed_payload_prepared_with_fake_hmac', !empty($failed_payload['hmac']));
            yptb1_add_check($checks, 'malformed_payload_loaded', is_array($malformed_payload));

            $success = $processor->process_fixture_payload($success_payload);
            $details['success_process_code'] = isset($success['code']) ? $success['code'] : null;
            yptb1_add_check($checks, 'success_fixture_processed', !empty($success['ok']) && $success['code'] === 'fixture_webhook_processed' && $success['data']['event_status'] === 'success');

            $success_transaction_id = !empty($success['data']['transaction_id']) ? (int) $success['data']['transaction_id'] : 0;
            $success_transaction = $payment_model->get_transaction($success_transaction_id);
            yptb1_add_check($checks, 'success_transaction_recorded_verified', !empty($success_transaction) && (string) $success_transaction['status'] === 'verified' && (int) $success_transaction['hmac_verified'] === 1);
            yptb1_add_check($checks, 'success_transaction_safe_currency_amount', !empty($success_transaction) && (string) $success_transaction['currency'] === 'EGP' && (int) $success_transaction['amount_cents'] === (int) $fixture['amount_cents']);

            $after_success_count = $db_obj->where('checkout_order_id', $diagnostic_order_id)->count_all_results('youngo_payment_transactions');
            $duplicate = $processor->process_fixture_payload($duplicate_payload);
            $after_duplicate_count = $db_obj->where('checkout_order_id', $diagnostic_order_id)->count_all_results('youngo_payment_transactions');
            $details['duplicate_process_code'] = isset($duplicate['code']) ? $duplicate['code'] : null;
            yptb1_add_check($checks, 'duplicate_fixture_detected', !empty($duplicate['ok']) && $duplicate['code'] === 'fixture_duplicate_detected');
            yptb1_add_check($checks, 'duplicate_did_not_create_new_transaction', $after_duplicate_count === $after_success_count);

            $failed = $processor->process_fixture_payload($failed_payload);
            $details['failed_process_code'] = isset($failed['code']) ? $failed['code'] : null;
            yptb1_add_check($checks, 'failed_fixture_processed_as_rejected', !empty($failed['ok']) && $failed['code'] === 'fixture_webhook_processed' && $failed['data']['event_status'] === 'failed');

            $failed_transaction_id = !empty($failed['data']['transaction_id']) ? (int) $failed['data']['transaction_id'] : 0;
            $failed_transaction = $payment_model->get_transaction($failed_transaction_id);
            yptb1_add_check($checks, 'failed_transaction_recorded_rejected', !empty($failed_transaction) && (string) $failed_transaction['status'] === 'rejected');

            $after_failed_count = $db_obj->where('checkout_order_id', $diagnostic_order_id)->count_all_results('youngo_payment_transactions');
            $malformed = $processor->process_fixture_payload($malformed_payload);
            $after_malformed_count = $db_obj->where('checkout_order_id', $diagnostic_order_id)->count_all_results('youngo_payment_transactions');
            $details['malformed_process_code'] = isset($malformed['code']) ? $malformed['code'] : null;
            yptb1_add_check($checks, 'malformed_fixture_rejected_safely', empty($malformed['ok']) && in_array($malformed['code'], array('payload_shape_invalid', 'missing_hmac'), true));
            yptb1_add_check($checks, 'malformed_did_not_create_transaction', $after_malformed_count === $after_failed_count);

            $transactions = $payment_model->get_transactions_for_order($diagnostic_order_id);
            $status_counts = yptb1_transaction_status_counts($db_obj, $diagnostic_order_id);
            $details['transaction_status_counts_before_cleanup'] = $status_counts;
            yptb1_add_check($checks, 'two_fixture_transactions_recorded', count($transactions) === 2);
            yptb1_add_check($checks, 'transaction_status_mix_verified_rejected', isset($status_counts['verified'], $status_counts['rejected']) && $status_counts['verified'] === 1 && $status_counts['rejected'] === 1);

            $order_after = $checkout_model->get_order($diagnostic_order_id);
            yptb1_add_check($checks, 'order_not_marked_paid', !empty($order_after) && (string) $order_after['status'] !== 'paid' && empty($order_after['paid_at']));
            yptb1_add_check($checks, 'order_hmac_metadata_updated_only', !empty($order_after) && (int) $order_after['last_hmac_verified'] === 1 && !empty($order_after['last_webhook_at']));
        }
    }

    $routes_source = yptb1_read($paths['routes']);
    $controller_source = yptb1_read($paths['webhook_controller']);
    yptb1_add_check($checks, 'public_webhook_route_exists', strpos($routes_source, "\$route['payment/paymob/webhook'] = 'youngo_payment_webhook/paymob';") !== false);
    yptb1_add_check($checks, 'public_webhook_route_fail_closed_source', strpos($controller_source, 'webhook_testing_disabled') !== false && strpos($controller_source, "get('webhook_testing_enabled', false)") !== false);
    yptb1_add_check($checks, 'public_cta_boundaries_present', strpos(yptb1_read($paths['home_controller']), 'youngo_remove_managed_access_courses_from_cart') !== false && strpos(yptb1_read($paths['course_page']), '$youngo_is_managed_access') !== false && strpos(yptb1_read($paths['course_card']), '$youngo_card_is_managed_access') !== false);
} catch (Throwable $exception) {
    yptb1_add_check($checks, 'runtime_exception', false, get_class($exception) . ': ' . $exception->getMessage());
}

if ($db_obj && $diagnostic_order_id) {
    $db_obj->where('checkout_order_id', $diagnostic_order_id)->delete('youngo_payment_transactions');
    $cleanup_deleted_transactions = $db_obj->affected_rows() >= 0;

    if ($diagnostic_order_reference) {
        $db_obj
            ->where('id', $diagnostic_order_id)
            ->where('order_reference', $diagnostic_order_reference)
            ->delete('youngo_checkout_orders');
        $cleanup_deleted_order = $db_obj->affected_rows() === 1;
    }

    if ($cleanup_deleted_order && isset($before_counts['youngo_checkout_orders']) && (int) $before_counts['youngo_checkout_orders'] === 0) {
        $db_obj->query('ALTER TABLE `youngo_checkout_orders` AUTO_INCREMENT = 1');
        $details['checkout_auto_increment_reset'] = true;
    }
    if ($cleanup_deleted_transactions && isset($before_counts['youngo_payment_transactions']) && (int) $before_counts['youngo_payment_transactions'] === 0) {
        $db_obj->query('ALTER TABLE `youngo_payment_transactions` AUTO_INCREMENT = 1');
        $details['transaction_auto_increment_reset'] = true;
    }
}

if ($db_obj) {
    $after_counts = yptb1_counts($db_obj, $protected_tables);
    $details['protected_counts_after_cleanup'] = $after_counts;
    $details['cleanup_deleted_order'] = $cleanup_deleted_order;
    $details['cleanup_deleted_transactions'] = $cleanup_deleted_transactions;
    $details['checkout_auto_increment_after_cleanup'] = yptb1_auto_increment($db_obj, 'youngo_checkout_orders');
    $details['transaction_auto_increment_after_cleanup'] = yptb1_auto_increment($db_obj, 'youngo_payment_transactions');
    $details['cleanup'] = ($cleanup_deleted_order && $cleanup_deleted_transactions) ? 'deleted_diagnostic_order_and_transactions' : 'cleanup_incomplete_or_no_order_created';

    if (!empty($before_counts)) {
        yptb1_add_check($checks, 'protected_counts_restored_after_cleanup', yptb1_counts_match($before_counts, $after_counts), json_encode($after_counts));
        yptb1_add_check($checks, 'no_entitlement_rows_created', $after_counts['youngo_course_access'] === $before_counts['youngo_course_access'] && $after_counts['youngo_user_subscriptions'] === $before_counts['youngo_user_subscriptions'] && $after_counts['youngo_manual_grants'] === $before_counts['youngo_manual_grants']);
        yptb1_add_check($checks, 'no_legacy_payment_or_enrol_rows_created', $after_counts['payment'] === $before_counts['payment'] && $after_counts['enrol'] === $before_counts['enrol']);
        yptb1_add_check($checks, 'checkout_and_transaction_counts_restored', $after_counts['youngo_checkout_orders'] === $before_counts['youngo_checkout_orders'] && $after_counts['youngo_payment_transactions'] === $before_counts['youngo_payment_transactions']);
    }
}

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[] = $name;
    }
}

$result = array(
    'phase' => 'PAYMENT.TRANSACTION.BLOCK.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
