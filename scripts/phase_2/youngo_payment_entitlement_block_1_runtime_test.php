<?php
/**
 * PAYMENT.ENTITLEMENT.BLOCK.1 runtime diagnostic.
 *
 * Creates a fixture-only paid checkout order, issues YounGo course access
 * through the existing entitlement write service, verifies read-model
 * compatibility, and cleans up all diagnostic rows. No Paymob calls, secrets,
 * public CTAs, legacy payment/enrol writes, or live system changes.
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

if (!function_exists('site_url')) {
    function site_url($uri = '')
    {
        return 'http://localhost/' . ltrim((string) $uri, '/');
    }
}

if (!function_exists('slugify')) {
    function slugify($string)
    {
        $string = strtolower(trim((string) $string));
        $string = preg_replace('/[^a-z0-9]+/', '-', $string);
        return trim($string, '-');
    }
}

require_once BASEPATH . 'core/Model.php';
require_once BASEPATH . 'database/DB.php';
require_once APPPATH . 'libraries/Youngo_paymob_config.php';
require_once APPPATH . 'libraries/Youngo_paymob_webhook.php';
require_once APPPATH . 'libraries/Youngo_paymob_fixture_processor.php';
require_once APPPATH . 'models/Youngo_checkout_model.php';
require_once APPPATH . 'models/Youngo_payment_model.php';
require_once APPPATH . 'models/Youngo_entitlement_write_model.php';
require_once APPPATH . 'models/Youngo_entitlement_model.php';

$checks = array();
$details = array(
    'db_writes' => 'diagnostic_checkout_order_transaction_and_course_access_created_then_deleted',
    'network_requests' => 'none',
    'cleanup' => 'not_started',
);

function ypeb1_add_check(&$checks, $name, $passed, $detail = '')
{
    $checks[$name] = array(
        'status' => $passed ? 'PASS' : 'FAIL',
        'detail' => $detail,
    );
}

function ypeb1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypeb1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypeb1_load_json($path)
{
    if (!is_file($path)) {
        return null;
    }

    $payload = json_decode(file_get_contents($path), true);
    return json_last_error() === JSON_ERROR_NONE ? $payload : null;
}

function ypeb1_table_count($db, $table)
{
    return $db->table_exists($table) ? (int) $db->count_all($table) : null;
}

function ypeb1_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ypeb1_table_count($db, $table);
    }

    return $counts;
}

function ypeb1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ypeb1_auto_increment($db, $table)
{
    $query = $db->query(
        'SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        array($table)
    );
    $row = $query ? $query->row_array() : array();

    return isset($row['AUTO_INCREMENT']) ? (int) $row['AUTO_INCREMENT'] : null;
}

function ypeb1_select_fixture($db)
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

function ypeb1_prepare_payload($webhook, $payload, $order_reference, $amount_cents, $secret)
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

function ypeb1_has_remote_network_code($source)
{
    foreach (array('curl_exec', 'curl_init', 'file_get_contents("http', "file_get_contents('http", 'fsockopen', 'stream_socket_client', 'GuzzleHttp', 'vendor/autoload') as $pattern) {
        if (stripos($source, $pattern) !== false) {
            return true;
        }
    }

    return false;
}

$paths = array(
    'payment_model' => APPPATH . 'models/Youngo_payment_model.php',
    'checkout_model' => APPPATH . 'models/Youngo_checkout_model.php',
    'entitlement_write_model' => APPPATH . 'models/Youngo_entitlement_write_model.php',
    'entitlement_model' => APPPATH . 'models/Youngo_entitlement_model.php',
    'fixture_processor' => APPPATH . 'libraries/Youngo_paymob_fixture_processor.php',
    'webhook_library' => APPPATH . 'libraries/Youngo_paymob_webhook.php',
    'webhook_controller' => APPPATH . 'controllers/Youngo_payment_webhook.php',
    'routes' => APPPATH . 'config/routes.php',
    'success_fixture' => $root . '/scripts/phase_2/fixtures/paymob/success_verified_payload.json',
    'home_controller' => APPPATH . 'controllers/Home.php',
    'course_page' => APPPATH . 'views/frontend/youngo/course_page.php',
    'course_card' => APPPATH . 'views/frontend/youngo/course_listing/course_card.php',
);

foreach ($paths as $name => $path) {
    ypeb1_add_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

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

$db_obj = null;
$before_counts = array();
$diagnostic_order_ids = array();
$diagnostic_access_ids = array();

try {
    $db_config = ypeb1_db_config();
    $db_obj = DB($db_config, true);
    if (!$db_obj || !$db_obj->conn_id) {
        throw new RuntimeException('DB connection failed without exposing credentials.');
    }

    $config = new Youngo_paymob_config(array('load_local_override' => true));
    $webhook = new Youngo_paymob_webhook(array('diagnostic_hmac_secret' => 'fixture-diagnostic-secret-only'));
    $checkout_model = new Youngo_checkout_model(array('db' => $db_obj));
    $payment_model = new Youngo_payment_model(array('db' => $db_obj));
    $entitlement_write_model = new Youngo_entitlement_write_model(array('db' => $db_obj));
    $entitlement_model = new Youngo_entitlement_model(array('db' => $db_obj));
    $processor = new Youngo_paymob_fixture_processor(array(
        'webhook' => $webhook,
        'checkout_model' => $checkout_model,
        'payment_model' => $payment_model,
        'apply_order_status_transitions' => true,
    ));

    ypeb1_add_check($checks, 'models_and_libraries_load', true);
    ypeb1_add_check($checks, 'config_default_disabled', !$config->is_enabled());
    ypeb1_add_check($checks, 'webhook_testing_disabled_by_default', !$config->get('webhook_testing_enabled', false));
    ypeb1_add_check($checks, 'payment_bridge_methods_exist', method_exists($payment_model, 'issue_paid_order_entitlement') && method_exists($payment_model, 'can_issue_entitlement_for_order') && method_exists($payment_model, 'get_entitlement_issuance_summary'));
    ypeb1_add_check($checks, 'entitlement_checkout_issuance_method_exists', method_exists($entitlement_write_model, 'issue_course_purchase_access'));

    $source_to_scan = ypeb1_read($paths['payment_model']) . ypeb1_read($paths['entitlement_write_model']) . ypeb1_read($paths['fixture_processor']) . ypeb1_read($paths['webhook_controller']);
    ypeb1_add_check($checks, 'no_paymob_network_code', !ypeb1_has_remote_network_code($source_to_scan));

    $schema = $payment_model->get_schema_readiness();
    $entitlement_schema = $entitlement_write_model->get_schema_readiness();
    ypeb1_add_check($checks, 'payment_schema_ready', !empty($schema['ready']));
    ypeb1_add_check($checks, 'entitlement_schema_ready', !empty($entitlement_schema['base_ready']));

    $before_counts = ypeb1_counts($db_obj, $protected_tables);
    $details['protected_counts_before'] = $before_counts;
    $details['checkout_auto_increment_before'] = ypeb1_auto_increment($db_obj, 'youngo_checkout_orders');
    $details['transaction_auto_increment_before'] = ypeb1_auto_increment($db_obj, 'youngo_payment_transactions');
    $details['course_access_auto_increment_before'] = ypeb1_auto_increment($db_obj, 'youngo_course_access');

    $fixture = ypeb1_select_fixture($db_obj);
    $details['fixture'] = empty($fixture) ? array() : array(
        'user_id' => (int) $fixture['user']['id'],
        'user_email' => (string) $fixture['user']['email'],
        'course_id' => (int) $fixture['course']['id'],
        'course_title' => (string) $fixture['course']['title'],
        'course_mode' => (string) $fixture['course']['youngo_access_mode'],
        'amount' => number_format((float) $fixture['amount'], 2, '.', ''),
        'currency' => 'EGP',
    );
    ypeb1_add_check($checks, 'fixture_available', !empty($fixture));
    ypeb1_add_check($checks, 'fixture_user_not_root', !empty($fixture) && (int) $fixture['user']['role_id'] !== 1);

    if (!empty($fixture)) {
        $create = $checkout_model->create_draft_order((int) $fixture['user']['id'], (int) $fixture['course']['id'], $fixture['amount'], 'EGP');
        $details['create_order_code'] = isset($create['code']) ? $create['code'] : null;
        ypeb1_add_check($checks, 'diagnostic_order_created', !empty($create['ok']));

        if (!empty($create['ok'])) {
            $order_id = (int) $create['data']['order_id'];
            $order_reference = (string) $create['data']['order_reference'];
            $diagnostic_order_ids[] = $order_id;

            $pending = $checkout_model->mark_pending_gateway($order_id, 'paymob', 'diagnostic-fixture-intention');
            $awaiting = $checkout_model->mark_awaiting_webhook($order_id);
            ypeb1_add_check($checks, 'diagnostic_order_pending_gateway', !empty($pending['ok']));
            ypeb1_add_check($checks, 'diagnostic_order_awaiting_webhook', !empty($awaiting['ok']));

            $success_payload = ypeb1_prepare_payload(
                $webhook,
                ypeb1_load_json($paths['success_fixture']),
                $order_reference,
                (int) $fixture['amount_cents'],
                'fixture-diagnostic-secret-only'
            );
            ypeb1_add_check($checks, 'success_payload_prepared', !empty($success_payload));

            if (!empty($success_payload)) {
                $processed = $processor->process_fixture_payload($success_payload);
                $details['success_process_code'] = isset($processed['code']) ? $processed['code'] : null;
                ypeb1_add_check($checks, 'success_fixture_processed', !empty($processed['ok']));

                $transaction_id = !empty($processed['data']['transaction_id']) ? (int) $processed['data']['transaction_id'] : 0;
                $paid_order = $checkout_model->get_order($order_id);
                ypeb1_add_check($checks, 'fixture_marked_order_paid', !empty($paid_order) && (string) $paid_order['status'] === 'paid');

                $issued = $payment_model->issue_paid_order_entitlement($order_id, $transaction_id);
                $details['issue_entitlement_code'] = isset($issued['code']) ? $issued['code'] : null;
                ypeb1_add_check($checks, 'paid_order_entitlement_issued', !empty($issued['ok']));

                $issued_order = $checkout_model->get_order($order_id);
                $course_access_id = !empty($issued_order['entitlement_course_access_id']) ? (int) $issued_order['entitlement_course_access_id'] : 0;
                if ($course_access_id > 0) {
                    $diagnostic_access_ids[] = $course_access_id;
                }

                ypeb1_add_check($checks, 'order_entitlement_flags_updated', !empty($issued_order) && (int) $issued_order['entitlement_issued'] === 1 && (string) $issued_order['entitlement_issuance_status'] === 'issued' && $course_access_id > 0);

                $access_row = $course_access_id > 0
                    ? $db_obj->where('id', $course_access_id)->get('youngo_course_access', 1)->row_array()
                    : array();
                ypeb1_add_check($checks, 'course_access_row_created', !empty($access_row) && (int) $access_row['checkout_order_id'] === $order_id && (string) $access_row['access_source'] === 'course_purchase' && (string) $access_row['status'] === 'active');

                $state = $entitlement_model->get_course_access_state((int) $fixture['user']['id'], (int) $fixture['course']['id']);
                ypeb1_add_check($checks, 'entitlement_read_model_detects_access', !empty($state['has_access']) && (string) $state['status'] === 'active' && (string) $state['access_source'] === 'course_purchase');

                $direct_access_count = (int) $db_obj
                    ->where('user_id', (int) $fixture['user']['id'])
                    ->where('course_id', (int) $fixture['course']['id'])
                    ->where('status', 'active')
                    ->where('access_source', 'course_purchase')
                    ->count_all_results('youngo_course_access');
                ypeb1_add_check($checks, 'my_courses_core_read_data_available', $direct_access_count === 1 && !empty($state['has_access']));

                $summary = $payment_model->get_entitlement_issuance_summary($order_id);
                ypeb1_add_check($checks, 'payment_entitlement_summary_reports_access', !empty($summary['ok']) && !empty($summary['data']['course_access']['id']));

                $duplicate = $payment_model->issue_paid_order_entitlement($order_id, $transaction_id);
                $details['duplicate_issue_code'] = isset($duplicate['code']) ? $duplicate['code'] : null;
                ypeb1_add_check($checks, 'duplicate_entitlement_issuance_rejected', empty($duplicate['ok']) && isset($duplicate['code']) && $duplicate['code'] === 'entitlement_already_issued');
            }
        }
    }

    $routes_source = ypeb1_read($paths['routes']);
    $controller_source = ypeb1_read($paths['webhook_controller']);
    ypeb1_add_check($checks, 'public_webhook_route_exists', strpos($routes_source, "\$route['payment/paymob/webhook'] = 'youngo_payment_webhook/paymob';") !== false);
    ypeb1_add_check($checks, 'public_webhook_route_fail_closed_source', strpos($controller_source, 'webhook_testing_disabled') !== false && strpos($controller_source, "get('webhook_testing_enabled', false)") !== false);
    ypeb1_add_check($checks, 'public_cta_boundaries_present', strpos(ypeb1_read($paths['home_controller']), 'youngo_remove_managed_access_courses_from_cart') !== false && strpos(ypeb1_read($paths['course_page']), '$youngo_is_managed_access') !== false && strpos(ypeb1_read($paths['course_card']), '$youngo_card_is_managed_access') !== false);
} catch (Throwable $exception) {
    ypeb1_add_check($checks, 'runtime_exception', false, get_class($exception) . ': ' . $exception->getMessage());
}

if ($db_obj && !empty($diagnostic_order_ids)) {
    $db_obj->where_in('checkout_order_id', $diagnostic_order_ids)->delete('youngo_course_access');
    $deleted_access = $db_obj->affected_rows();
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
    if (isset($before_counts['youngo_course_access']) && (int) $before_counts['youngo_course_access'] === 0) {
        $db_obj->query('ALTER TABLE `youngo_course_access` AUTO_INCREMENT = 1');
        $details['course_access_auto_increment_reset'] = true;
    }

    $details['cleanup_deleted_orders'] = $deleted_orders;
    $details['cleanup_deleted_transactions'] = $deleted_transactions;
    $details['cleanup_deleted_course_access'] = $deleted_access;
}

if ($db_obj) {
    $after_counts = ypeb1_counts($db_obj, $protected_tables);
    $details['protected_counts_after_cleanup'] = $after_counts;
    $details['checkout_auto_increment_after_cleanup'] = ypeb1_auto_increment($db_obj, 'youngo_checkout_orders');
    $details['transaction_auto_increment_after_cleanup'] = ypeb1_auto_increment($db_obj, 'youngo_payment_transactions');
    $details['course_access_auto_increment_after_cleanup'] = ypeb1_auto_increment($db_obj, 'youngo_course_access');
    $details['cleanup'] = !empty($before_counts) && ypeb1_counts_match($before_counts, $after_counts) ? 'protected_counts_restored' : 'cleanup_incomplete';

    if (!empty($before_counts)) {
        ypeb1_add_check($checks, 'protected_counts_restored_after_cleanup', ypeb1_counts_match($before_counts, $after_counts), json_encode($after_counts));
        ypeb1_add_check($checks, 'no_legacy_payment_or_enrol_rows_created', $after_counts['payment'] === $before_counts['payment'] && $after_counts['enrol'] === $before_counts['enrol']);
        ypeb1_add_check($checks, 'checkout_transaction_access_counts_restored', $after_counts['youngo_checkout_orders'] === $before_counts['youngo_checkout_orders'] && $after_counts['youngo_payment_transactions'] === $before_counts['youngo_payment_transactions'] && $after_counts['youngo_course_access'] === $before_counts['youngo_course_access']);
        ypeb1_add_check($checks, 'no_manual_grant_or_subscription_rows_created', $after_counts['youngo_manual_grants'] === $before_counts['youngo_manual_grants'] && $after_counts['youngo_user_subscriptions'] === $before_counts['youngo_user_subscriptions']);
    }
}

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[] = $name;
    }
}

$result = array(
    'phase' => 'PAYMENT.ENTITLEMENT.BLOCK.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
