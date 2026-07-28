<?php
/**
 * PAYMENT.ORDER.STATUS.1 runtime diagnostic.
 *
 * Processes fake Paymob fixtures and applies local-only checkout order status
 * transitions from verified/rejected transaction rows. Cleans up all diagnostic
 * rows. No Paymob calls, secrets, entitlements, legacy payment/enrol writes,
 * public CTA exposure, or live system changes.
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
    'db_writes' => 'diagnostic_checkout_orders_and_fixture_transactions_created_then_deleted',
    'network_requests' => 'none',
    'cleanup' => 'not_started',
);

function ypos1_add_check(&$checks, $name, $passed, $detail = '')
{
    $checks[$name] = array(
        'status' => $passed ? 'PASS' : 'FAIL',
        'detail' => $detail,
    );
}

function ypos1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypos1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypos1_load_json($path)
{
    if (!is_file($path)) {
        return null;
    }

    $payload = json_decode(file_get_contents($path), true);
    return json_last_error() === JSON_ERROR_NONE ? $payload : null;
}

function ypos1_table_count($db, $table)
{
    return $db->table_exists($table) ? (int) $db->count_all($table) : null;
}

function ypos1_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ypos1_table_count($db, $table);
    }

    return $counts;
}

function ypos1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ypos1_auto_increment($db, $table)
{
    $query = $db->query(
        'SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        array($table)
    );
    $row = $query ? $query->row_array() : array();

    return isset($row['AUTO_INCREMENT']) ? (int) $row['AUTO_INCREMENT'] : null;
}

function ypos1_select_fixture($db)
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

function ypos1_prepare_payload($webhook, $payload, $order_reference, $amount_cents, $secret)
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

function ypos1_has_remote_network_code($source)
{
    foreach (array('curl_exec', 'curl_init', 'file_get_contents("http', "file_get_contents('http", 'fsockopen', 'stream_socket_client', 'GuzzleHttp', 'vendor/autoload') as $pattern) {
        if (stripos($source, $pattern) !== false) {
            return true;
        }
    }

    return false;
}

function ypos1_create_gateway_order($checkout_model, $fixture, &$details, $label)
{
    $create = $checkout_model->create_draft_order((int) $fixture['user']['id'], (int) $fixture['course']['id'], $fixture['amount'], 'EGP');
    $details[$label . '_create_code'] = isset($create['code']) ? $create['code'] : null;
    if (empty($create['ok'])) {
        return array('ok' => false, 'result' => $create);
    }

    $order_id = (int) $create['data']['order_id'];
    $pending = $checkout_model->mark_pending_gateway($order_id, 'paymob', 'diagnostic-' . $label . '-intention');
    $awaiting = $checkout_model->mark_awaiting_webhook($order_id);

    return array(
        'ok' => !empty($pending['ok']) && !empty($awaiting['ok']),
        'order_id' => $order_id,
        'order_reference' => (string) $create['data']['order_reference'],
        'pending' => $pending,
        'awaiting' => $awaiting,
    );
}

$paths = array(
    'payment_model' => APPPATH . 'models/Youngo_payment_model.php',
    'checkout_model' => APPPATH . 'models/Youngo_checkout_model.php',
    'fixture_processor' => APPPATH . 'libraries/Youngo_paymob_fixture_processor.php',
    'webhook_library' => APPPATH . 'libraries/Youngo_paymob_webhook.php',
    'webhook_controller' => APPPATH . 'controllers/Youngo_payment_webhook.php',
    'routes' => APPPATH . 'config/routes.php',
    'success_fixture' => $root . '/scripts/phase_2/fixtures/paymob/success_verified_payload.json',
    'duplicate_fixture' => $root . '/scripts/phase_2/fixtures/paymob/duplicate_success_payload.json',
    'failed_fixture' => $root . '/scripts/phase_2/fixtures/paymob/failed_payment_payload.json',
    'home_controller' => APPPATH . 'controllers/Home.php',
    'course_page' => APPPATH . 'views/frontend/youngo/course_page.php',
    'course_card' => APPPATH . 'views/frontend/youngo/course_listing/course_card.php',
);

$db_obj = null;
$diagnostic_order_ids = array();
$before_counts = array();
$diagnostic_secret = 'youngo-payment-order-status-diagnostic-secret';

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
        ypos1_add_check($checks, 'file_exists_' . $name, is_file($path), $path);
    }

    $db_obj = DB(ypos1_db_config(), true);
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
        'apply_order_status_transitions' => true,
    ));

    ypos1_add_check($checks, 'payment_model_loads', $payment_model instanceof Youngo_payment_model);
    ypos1_add_check($checks, 'fixture_processor_loads', $processor instanceof Youngo_paymob_fixture_processor);
    ypos1_add_check($checks, 'config_default_disabled', $config_reader->is_enabled() === false);
    ypos1_add_check($checks, 'webhook_testing_disabled_by_default', $config_reader->get('webhook_testing_enabled', false) === false);

    foreach (array('mark_paid_from_verified_transaction', 'mark_failed_from_rejected_transaction', 'can_mark_paid', 'can_mark_failed', 'get_payment_status_summary') as $method) {
        ypos1_add_check($checks, 'payment_model_method_exists_' . $method, method_exists($payment_model, $method));
    }

    $source_bundle = ypos1_read($paths['payment_model']) . ypos1_read($paths['fixture_processor']) . ypos1_read($paths['webhook_controller']);
    ypos1_add_check($checks, 'no_paymob_network_code', !ypos1_has_remote_network_code($source_bundle));
    ypos1_add_check($checks, 'public_route_not_wired_to_payment_model', strpos(ypos1_read($paths['webhook_controller']), 'Youngo_payment_model') === false && strpos(ypos1_read($paths['webhook_controller']), 'Youngo_paymob_fixture_processor') === false);

    $schema = $payment_model->get_schema_readiness();
    ypos1_add_check($checks, 'payment_schema_ready', !empty($schema['ready']));

    $before_counts = ypos1_counts($db_obj, $protected_tables);
    $details['protected_counts_before'] = $before_counts;
    $details['checkout_auto_increment_before'] = ypos1_auto_increment($db_obj, 'youngo_checkout_orders');
    $details['transaction_auto_increment_before'] = ypos1_auto_increment($db_obj, 'youngo_payment_transactions');

    $fixture = ypos1_select_fixture($db_obj);
    ypos1_add_check($checks, 'fixture_available', !empty($fixture));
    if (!empty($fixture)) {
        $details['fixture'] = array(
            'user_id' => (int) $fixture['user']['id'],
            'user_email' => $fixture['user']['email'],
            'course_id' => (int) $fixture['course']['id'],
            'course_title' => $fixture['course']['title'],
            'course_mode' => $fixture['course']['youngo_access_mode'],
            'amount' => number_format($fixture['amount'], 2, '.', ''),
            'currency' => 'EGP',
        );
        ypos1_add_check($checks, 'fixture_user_not_root', (int) $fixture['user']['id'] !== 1);

        $paid_order = ypos1_create_gateway_order($checkout_model, $fixture, $details, 'paid_order');
        ypos1_add_check($checks, 'paid_diagnostic_order_created_and_awaiting', !empty($paid_order['ok']));
        if (!empty($paid_order['ok'])) {
            $diagnostic_order_ids[] = $paid_order['order_id'];
            $success_payload = ypos1_prepare_payload($webhook, ypos1_load_json($paths['success_fixture']), $paid_order['order_reference'], $fixture['amount_cents'], $diagnostic_secret);
            $duplicate_payload = ypos1_prepare_payload($webhook, ypos1_load_json($paths['duplicate_fixture']), $paid_order['order_reference'], $fixture['amount_cents'], $diagnostic_secret);
            ypos1_add_check($checks, 'success_payload_prepared', !empty($success_payload['hmac']));

            $success = $processor->process_fixture_payload($success_payload);
            $details['success_process_code'] = isset($success['code']) ? $success['code'] : null;
            ypos1_add_check($checks, 'success_fixture_processed', !empty($success['ok']) && $success['code'] === 'fixture_webhook_processed');

            $paid_after = $checkout_model->get_order($paid_order['order_id']);
            ypos1_add_check($checks, 'verified_transaction_marked_order_paid', !empty($paid_after) && (string) $paid_after['status'] === 'paid' && !empty($paid_after['paid_at']));
            ypos1_add_check($checks, 'paid_order_entitlement_not_issued', !empty($paid_after) && (int) $paid_after['entitlement_issued'] === 0 && (string) $paid_after['entitlement_issuance_status'] === 'not_started');

            $status_summary = $payment_model->get_payment_status_summary($paid_order['order_id']);
            ypos1_add_check($checks, 'payment_status_summary_reports_paid', !empty($status_summary['ok']) && $status_summary['data']['order']['status'] === 'paid' && $status_summary['data']['transaction_count'] === 1);

            $paid_count_before_duplicate = $db_obj->where('checkout_order_id', $paid_order['order_id'])->count_all_results('youngo_payment_transactions');
            $paid_at_before_duplicate = (int) $paid_after['paid_at'];
            $duplicate = $processor->process_fixture_payload($duplicate_payload);
            $paid_count_after_duplicate = $db_obj->where('checkout_order_id', $paid_order['order_id'])->count_all_results('youngo_payment_transactions');
            $paid_after_duplicate = $checkout_model->get_order($paid_order['order_id']);
            $details['duplicate_process_code'] = isset($duplicate['code']) ? $duplicate['code'] : null;
            ypos1_add_check($checks, 'duplicate_success_detected', !empty($duplicate['ok']) && $duplicate['code'] === 'fixture_duplicate_detected');
            ypos1_add_check($checks, 'duplicate_success_no_extra_transaction', $paid_count_after_duplicate === $paid_count_before_duplicate);
            ypos1_add_check($checks, 'duplicate_success_no_duplicate_paid_state', !empty($paid_after_duplicate) && (string) $paid_after_duplicate['status'] === 'paid' && (int) $paid_after_duplicate['paid_at'] === $paid_at_before_duplicate);

            $repeat_paid = $payment_model->mark_paid_from_verified_transaction($paid_order['order_id'], (int) $success['data']['transaction_id']);
            $details['repeat_paid_code'] = isset($repeat_paid['code']) ? $repeat_paid['code'] : null;
            ypos1_add_check($checks, 'paid_order_cannot_be_paid_twice', empty($repeat_paid['ok']) && $repeat_paid['code'] === 'order_already_paid');
        }

        $failed_order = ypos1_create_gateway_order($checkout_model, $fixture, $details, 'failed_order');
        ypos1_add_check($checks, 'failed_diagnostic_order_created_and_awaiting', !empty($failed_order['ok']));
        if (!empty($failed_order['ok'])) {
            $diagnostic_order_ids[] = $failed_order['order_id'];
            $failed_payload = ypos1_prepare_payload($webhook, ypos1_load_json($paths['failed_fixture']), $failed_order['order_reference'], $fixture['amount_cents'], $diagnostic_secret);
            ypos1_add_check($checks, 'failed_payload_prepared', !empty($failed_payload['hmac']));

            $failed = $processor->process_fixture_payload($failed_payload);
            $details['failed_process_code'] = isset($failed['code']) ? $failed['code'] : null;
            ypos1_add_check($checks, 'failed_fixture_processed', !empty($failed['ok']) && $failed['code'] === 'fixture_webhook_processed');

            $failed_after = $checkout_model->get_order($failed_order['order_id']);
            ypos1_add_check($checks, 'rejected_transaction_marked_order_failed', !empty($failed_after) && (string) $failed_after['status'] === 'failed' && !empty($failed_after['failed_at']));
            ypos1_add_check($checks, 'failed_order_entitlement_not_issued', !empty($failed_after) && (int) $failed_after['entitlement_issued'] === 0 && (string) $failed_after['entitlement_issuance_status'] === 'not_started');

            $invalid_paid = $payment_model->mark_paid_from_verified_transaction($failed_order['order_id'], (int) $failed['data']['transaction_id']);
            $details['invalid_paid_from_failed_order_code'] = isset($invalid_paid['code']) ? $invalid_paid['code'] : null;
            ypos1_add_check($checks, 'failed_order_cannot_be_marked_paid', empty($invalid_paid['ok']) && $invalid_paid['code'] === 'invalid_order_status');
        }

        $cancelled_order = ypos1_create_gateway_order($checkout_model, $fixture, $details, 'cancelled_order');
        ypos1_add_check($checks, 'cancelled_diagnostic_order_created_and_awaiting', !empty($cancelled_order['ok']));
        if (!empty($cancelled_order['ok'])) {
            $diagnostic_order_ids[] = $cancelled_order['order_id'];
            $cancel = $checkout_model->mark_cancelled($cancelled_order['order_id'], 'PAYMENT.ORDER.STATUS.1 diagnostic invalid transition test');
            $cancelled_after = $checkout_model->get_order($cancelled_order['order_id']);
            $can_paid_cancelled = $payment_model->can_mark_paid($cancelled_after);
            ypos1_add_check($checks, 'cancelled_order_marked_cancelled', !empty($cancel['ok']));
            ypos1_add_check($checks, 'cancelled_order_cannot_be_marked_paid', empty($can_paid_cancelled['ok']) && $can_paid_cancelled['code'] === 'invalid_order_status');
        }
    }

    $routes_source = ypos1_read($paths['routes']);
    $controller_source = ypos1_read($paths['webhook_controller']);
    ypos1_add_check($checks, 'public_webhook_route_exists', strpos($routes_source, "\$route['payment/paymob/webhook'] = 'youngo_payment_webhook/paymob';") !== false);
    ypos1_add_check($checks, 'public_webhook_route_fail_closed_source', strpos($controller_source, 'webhook_testing_disabled') !== false && strpos($controller_source, "get('webhook_testing_enabled', false)") !== false);
    ypos1_add_check($checks, 'public_cta_boundaries_present', strpos(ypos1_read($paths['home_controller']), 'youngo_remove_managed_access_courses_from_cart') !== false && strpos(ypos1_read($paths['course_page']), '$youngo_is_managed_access') !== false && strpos(ypos1_read($paths['course_card']), '$youngo_card_is_managed_access') !== false);
} catch (Throwable $exception) {
    ypos1_add_check($checks, 'runtime_exception', false, get_class($exception) . ': ' . $exception->getMessage());
}

if ($db_obj && !empty($diagnostic_order_ids)) {
    $db_obj->where_in('checkout_order_id', $diagnostic_order_ids)->delete('youngo_payment_transactions');
    $deleted_transactions = $db_obj->affected_rows();
    $db_obj->where_in('id', $diagnostic_order_ids)->delete('youngo_checkout_orders');
    $deleted_orders = $db_obj->affected_rows();

    if (isset($before_counts['youngo_checkout_orders']) && (int) $before_counts['youngo_checkout_orders'] === 0) {
        $db_obj->query('ALTER TABLE `youngo_checkout_orders` AUTO_INCREMENT = 1');
        $details['checkout_auto_increment_reset'] = true;
    }
    if (isset($before_counts['youngo_payment_transactions']) && (int) $before_counts['youngo_payment_transactions'] === 0) {
        $db_obj->query('ALTER TABLE `youngo_payment_transactions` AUTO_INCREMENT = 1');
        $details['transaction_auto_increment_reset'] = true;
    }

    $details['cleanup_deleted_orders'] = $deleted_orders;
    $details['cleanup_deleted_transactions'] = $deleted_transactions;
}

if ($db_obj) {
    $after_counts = ypos1_counts($db_obj, $protected_tables);
    $details['protected_counts_after_cleanup'] = $after_counts;
    $details['checkout_auto_increment_after_cleanup'] = ypos1_auto_increment($db_obj, 'youngo_checkout_orders');
    $details['transaction_auto_increment_after_cleanup'] = ypos1_auto_increment($db_obj, 'youngo_payment_transactions');
    $details['cleanup'] = !empty($before_counts) && ypos1_counts_match($before_counts, $after_counts) ? 'protected_counts_restored' : 'cleanup_incomplete';

    if (!empty($before_counts)) {
        ypos1_add_check($checks, 'protected_counts_restored_after_cleanup', ypos1_counts_match($before_counts, $after_counts), json_encode($after_counts));
        ypos1_add_check($checks, 'no_entitlement_rows_created', $after_counts['youngo_course_access'] === $before_counts['youngo_course_access'] && $after_counts['youngo_user_subscriptions'] === $before_counts['youngo_user_subscriptions'] && $after_counts['youngo_manual_grants'] === $before_counts['youngo_manual_grants']);
        ypos1_add_check($checks, 'no_legacy_payment_or_enrol_rows_created', $after_counts['payment'] === $before_counts['payment'] && $after_counts['enrol'] === $before_counts['enrol']);
        ypos1_add_check($checks, 'checkout_and_transaction_counts_restored', $after_counts['youngo_checkout_orders'] === $before_counts['youngo_checkout_orders'] && $after_counts['youngo_payment_transactions'] === $before_counts['youngo_payment_transactions']);
    }
}

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[] = $name;
    }
}

$result = array(
    'phase' => 'PAYMENT.ORDER.STATUS.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
