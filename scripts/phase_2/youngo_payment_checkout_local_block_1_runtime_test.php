<?php
/**
 * PAYMENT.CHECKOUT.LOCAL.BLOCK.1 runtime diagnostic.
 *
 * Creates one controlled local checkout order through Youngo_checkout_model,
 * verifies local-only order/status assumptions, then deletes the diagnostic row.
 * No Paymob calls, credentials, entitlements, legacy payment rows, or legacy
 * enrol rows are created.
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

$checks = array();
$details = array(
    'db_writes' => 'one_diagnostic_checkout_order_created_then_deleted',
    'cleanup' => 'not_started',
);

function ypclb1_add_check(&$checks, $name, $passed, $detail = '')
{
    $checks[$name] = array(
        'status' => $passed ? 'PASS' : 'FAIL',
        'detail' => $detail,
    );
}

function ypclb1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypclb1_contains_any($source, $needles)
{
    foreach ($needles as $needle) {
        if (stripos($source, $needle) !== false) {
            return true;
        }
    }

    return false;
}

function ypclb1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypclb1_table_exists($db, $table)
{
    return $db->table_exists($table);
}

function ypclb1_table_count($db, $table)
{
    if (!ypclb1_table_exists($db, $table)) {
        return null;
    }

    return (int) $db->count_all($table);
}

function ypclb1_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ypclb1_table_count($db, $table);
    }

    return $counts;
}

function ypclb1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ypclb1_auto_increment($db, $table)
{
    $query = $db->query(
        'SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        array($table)
    );
    $row = $query ? $query->row_array() : array();

    return isset($row['AUTO_INCREMENT']) ? (int) $row['AUTO_INCREMENT'] : null;
}

function ypclb1_select_fixture($db)
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
    );
}

function ypclb1_select_free_course($db)
{
    return $db
        ->select('id, title, is_free_course, status, youngo_access_mode')
        ->where('status', 'active')
        ->where('is_free_course', 1)
        ->order_by('id', 'asc')
        ->get('course', 1)
        ->row_array();
}

function ypclb1_select_subscription_only_course($db)
{
    return $db
        ->select('id, title, is_free_course, status, youngo_access_mode')
        ->where('status', 'active')
        ->where('youngo_access_mode', 'subscription_only')
        ->order_by('id', 'asc')
        ->get('course', 1)
        ->row_array();
}

function ypclb1_reference_is_safe($reference)
{
    return is_string($reference) && preg_match('/^YGO-[0-9]{14}-[A-F0-9]{12,20}$/', $reference);
}

$paths = array(
    'controller' => $root . '/application/controllers/Youngo_checkout.php',
    'order_view' => $root . '/application/views/frontend/youngo/checkout_order.php',
    'disabled_view' => $root . '/application/views/frontend/youngo/checkout_disabled.php',
    'routes' => $root . '/application/config/routes.php',
    'course_page' => $root . '/application/views/frontend/youngo/course_page.php',
    'course_card' => $root . '/application/views/frontend/youngo/course_listing/course_card.php',
    'my_wishlist' => $root . '/application/views/frontend/youngo/my_wishlist.php',
    'wishlist_items' => $root . '/application/views/frontend/youngo/wishlist_items.php',
);

foreach ($paths as $name => $path) {
    ypclb1_add_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

$controller_source = ypclb1_read($paths['controller']);
$view_source = ypclb1_read($paths['order_view']) . "\n" . ypclb1_read($paths['disabled_view']);
$route_source = ypclb1_read($paths['routes']);

ypclb1_add_check(
    $checks,
    'route_start_uses_controlled_start_method',
    strpos($route_source, "\$route['youngo/checkout/start/(:num)'] = 'youngo_checkout/start/\$1';") !== false
);
ypclb1_add_check(
    $checks,
    'controller_uses_existing_order_model',
    strpos($controller_source, "load->model('Youngo_checkout_model'") !== false
        && strpos($controller_source, 'create_or_reuse_draft_order') !== false
        && strpos($controller_source, 'get_order_by_reference') !== false
);
ypclb1_add_check(
    $checks,
    'controller_uses_existing_payment_summary_model',
    strpos($controller_source, "load->model('Youngo_payment_model'") !== false
        && strpos($controller_source, 'get_payment_status_summary') !== false
);
ypclb1_add_check(
    $checks,
    'controller_reuses_paymob_config_and_disabled_adapter',
    strpos($controller_source, 'Youngo_paymob_config') !== false
        && strpos($controller_source, 'Youngo_paymob_adapter') !== false
        && strpos($controller_source, 'paymob_network_must_remain_disabled') !== false
);
ypclb1_add_check(
    $checks,
    'controller_has_no_entitlement_or_legacy_writes',
    !ypclb1_contains_any($controller_source, array('issue_paid_order_entitlement', 'issue_course_purchase_access', 'grant_course_access', 'grant_subscription', 'enrol_to_free_course', 'enrol_student', 'configure_course_payment', 'home/course_payment', 'cart_items'))
);
ypclb1_add_check(
    $checks,
    'controller_has_no_paymob_network_calls',
    !ypclb1_contains_any($controller_source, array('curl_exec', 'curl_init', 'fsockopen', 'stream_socket_client', 'GuzzleHttp', 'vendor/autoload', 'file_get_contents("http', "file_get_contents('http"))
);
ypclb1_add_check(
    $checks,
    'checkout_views_have_no_payment_button_or_gateway_selector',
    !ypclb1_contains_any($view_source, array('<button', 'type="submit"', 'Buy Now', 'Add to cart', 'gateway selector', 'PAYMOB_SECRET', 'PAYMOB_HMAC', 'client_secret'))
);

foreach (array('course_page', 'course_card', 'my_wishlist', 'wishlist_items') as $name) {
    ypclb1_add_check(
        $checks,
        'no_public_checkout_start_link_' . $name,
        strpos(ypclb1_read($paths[$name]), 'youngo/checkout/start') === false
    );
}

$db_obj = null;
$model = null;
$diagnostic_order_id = null;
$diagnostic_order_reference = null;
$cleanup_deleted = false;

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
    $db_obj = DB(ypclb1_db_config(), true);
    $model = new Youngo_checkout_model(array('db' => $db_obj));
    $reader = new Youngo_paymob_config(array('load_local_override' => false));
    $local_reader = new Youngo_paymob_config(array(
        'load_local_override' => false,
        'config' => array(
            'checkout_routes_enabled' => true,
            'checkout_local_testing_enabled' => true,
            'checkout_cta_enabled' => false,
            'enabled' => false,
            'network_enabled' => false,
            'mode' => 'sandbox',
            'currency' => 'EGP',
        ),
    ));
    $adapter = new Youngo_paymob_adapter(array('config_reader' => $reader));

    ypclb1_add_check($checks, 'default_checkout_routes_disabled', $reader->is_checkout_routes_enabled() === false);
    ypclb1_add_check($checks, 'default_checkout_local_testing_disabled', $reader->is_checkout_local_testing_enabled() === false);
    ypclb1_add_check($checks, 'default_checkout_cta_disabled', $reader->is_checkout_cta_enabled() === false);
    ypclb1_add_check($checks, 'default_payment_disabled', $reader->is_enabled() === false);
    ypclb1_add_check($checks, 'default_network_disabled', (bool) $reader->get('network_enabled', false) === false);
    ypclb1_add_check($checks, 'injected_local_flags_can_enable_route_testing_without_payment_network', $local_reader->is_checkout_routes_enabled() && $local_reader->is_checkout_local_testing_enabled() && !$local_reader->is_checkout_cta_enabled() && !$local_reader->is_enabled() && !(bool) $local_reader->get('network_enabled', false));

    $adapter_ready = $adapter->is_ready_for_sandbox();
    $details['adapter_readiness_code'] = isset($adapter_ready['code']) ? $adapter_ready['code'] : null;
    ypclb1_add_check($checks, 'paymob_adapter_remains_network_disabled', empty($adapter_ready['ok']) && $adapter_ready['code'] === 'paymob_network_disabled_in_this_phase');

    $schema = $model->get_schema_readiness();
    $details['schema_ready'] = !empty($schema['ready']);
    ypclb1_add_check($checks, 'schema_ready', !empty($schema['ready']));

    $fixture = ypclb1_select_fixture($db_obj);
    if (empty($fixture)) {
        ypclb1_add_check($checks, 'fixture_available', false, 'No safe learner plus purchase-compatible YounGo course was found.');
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

        ypclb1_add_check($checks, 'fixture_available', true);
        ypclb1_add_check($checks, 'fixture_user_safe_learner', (int) $fixture['user']['id'] !== 1 && (int) $fixture['user']['role_id'] === 2 && (int) $fixture['user']['is_instructor'] === 0);
        ypclb1_add_check($checks, 'fixture_course_purchase_compatible', in_array($fixture['course']['youngo_access_mode'], array('purchase_only', 'subscription_and_purchase'), true));

        $before_counts = ypclb1_counts($db_obj, $protected_tables);
        $details['protected_counts_before'] = $before_counts;
        $before_checkout_auto_increment = ypclb1_auto_increment($db_obj, 'youngo_checkout_orders');
        $details['checkout_auto_increment_before'] = $before_checkout_auto_increment;

        $invalid = $model->can_start_checkout((int) $fixture['user']['id'], 999999999);
        $details['invalid_course_result_code'] = isset($invalid['code']) ? $invalid['code'] : null;
        ypclb1_add_check($checks, 'invalid_course_rejected_without_write', empty($invalid['ok']) && $invalid['code'] === 'course_not_found');

        $free_course = ypclb1_select_free_course($db_obj);
        if (!empty($free_course)) {
            $free = $model->can_start_checkout((int) $fixture['user']['id'], (int) $free_course['id']);
            $details['free_course_result_code'] = isset($free['code']) ? $free['code'] : null;
            ypclb1_add_check($checks, 'free_course_rejected_without_write', empty($free['ok']) && $free['code'] === 'payment_not_required');
        } else {
            ypclb1_add_check($checks, 'free_course_rejected_without_write', true, 'skipped_no_free_course_fixture');
        }

        $subscription_only_course = ypclb1_select_subscription_only_course($db_obj);
        if (!empty($subscription_only_course)) {
            $subscription_only = $model->can_start_checkout((int) $fixture['user']['id'], (int) $subscription_only_course['id']);
            $details['subscription_only_result_code'] = isset($subscription_only['code']) ? $subscription_only['code'] : null;
            ypclb1_add_check($checks, 'subscription_only_course_rejected_without_write', empty($subscription_only['ok']) && in_array($subscription_only['code'], array('course_not_purchase_enabled', 'payment_not_required'), true));
        } else {
            ypclb1_add_check($checks, 'subscription_only_course_rejected_without_write', true, 'skipped_no_subscription_only_fixture');
        }

        $create = $model->create_or_reuse_draft_order((int) $fixture['user']['id'], (int) $fixture['course']['id'], $fixture['amount'], 'EGP');
        $details['create_result_code'] = isset($create['code']) ? $create['code'] : null;
        ypclb1_add_check($checks, 'draft_order_created_via_reuse_wrapper', !empty($create['ok']) && $create['code'] === 'draft_order_created' && isset($create['data']['order_id'], $create['data']['order_reference']));

        if (!empty($create['ok']) && $create['code'] === 'draft_order_created') {
            $diagnostic_order_id = (int) $create['data']['order_id'];
            $diagnostic_order_reference = (string) $create['data']['order_reference'];
            $details['diagnostic_order_id'] = $diagnostic_order_id;
            $details['diagnostic_order_reference'] = $diagnostic_order_reference;

            ypclb1_add_check($checks, 'order_reference_format_safe', ypclb1_reference_is_safe($diagnostic_order_reference));

            $reuse = $model->create_or_reuse_draft_order((int) $fixture['user']['id'], (int) $fixture['course']['id'], $fixture['amount'], 'EGP');
            $details['reuse_result_code'] = isset($reuse['code']) ? $reuse['code'] : null;
            ypclb1_add_check($checks, 'open_order_reused_without_duplicate', !empty($reuse['ok']) && $reuse['code'] === 'open_order_reused' && isset($reuse['data']['order_id']) && (int) $reuse['data']['order_id'] === $diagnostic_order_id);

            $by_reference = $model->get_order_by_reference($diagnostic_order_reference);
            ypclb1_add_check($checks, 'order_lookup_by_reference', !empty($by_reference) && (int) $by_reference['id'] === $diagnostic_order_id);
            ypclb1_add_check($checks, 'order_amount_currency_status_safe', !empty($by_reference) && (string) $by_reference['currency'] === 'EGP' && (string) $by_reference['status'] === 'draft' && (int) $by_reference['total_amount_cents'] === (int) round($fixture['amount'] * 100));
            ypclb1_add_check($checks, 'order_entitlement_not_issued', !empty($by_reference) && (int) $by_reference['entitlement_issued'] === 0 && (string) $by_reference['entitlement_issuance_status'] === 'not_started');
        }
    }
} catch (Throwable $exception) {
    ypclb1_add_check($checks, 'runtime_exception', false, get_class($exception) . ': ' . $exception->getMessage());
}

if ($db_obj && $diagnostic_order_id && $diagnostic_order_reference) {
    $db_obj
        ->where('id', $diagnostic_order_id)
        ->where('order_reference', $diagnostic_order_reference)
        ->delete('youngo_checkout_orders');
    $cleanup_deleted = $db_obj->affected_rows() === 1;
    $details['cleanup_deleted_order'] = $cleanup_deleted;

    if ($cleanup_deleted && isset($before_counts['youngo_checkout_orders']) && (int) $before_counts['youngo_checkout_orders'] === 0) {
        $db_obj->query('ALTER TABLE `youngo_checkout_orders` AUTO_INCREMENT = 1');
        $details['checkout_auto_increment_reset'] = true;
    }
}

if ($db_obj) {
    $after_counts = ypclb1_counts($db_obj, $protected_tables);
    $after_checkout_auto_increment = ypclb1_auto_increment($db_obj, 'youngo_checkout_orders');
    $details['protected_counts_after_cleanup'] = $after_counts;
    $details['checkout_auto_increment_after_cleanup'] = $after_checkout_auto_increment;
    $details['cleanup'] = $cleanup_deleted ? 'deleted_diagnostic_order' : 'no_order_created';

    if (isset($before_counts)) {
        ypclb1_add_check($checks, 'checkout_order_count_restored', $after_counts['youngo_checkout_orders'] === $before_counts['youngo_checkout_orders']);
        ypclb1_add_check($checks, 'checkout_auto_increment_clean_when_empty', (int) $before_counts['youngo_checkout_orders'] !== 0 || $after_checkout_auto_increment === 1);
        ypclb1_add_check($checks, 'protected_counts_restored_after_cleanup', ypclb1_counts_match($before_counts, $after_counts));
        ypclb1_add_check($checks, 'no_payment_transactions_created', $after_counts['youngo_payment_transactions'] === $before_counts['youngo_payment_transactions']);
        ypclb1_add_check($checks, 'no_entitlement_rows_created', $after_counts['youngo_course_access'] === $before_counts['youngo_course_access'] && $after_counts['youngo_user_subscriptions'] === $before_counts['youngo_user_subscriptions'] && $after_counts['youngo_manual_grants'] === $before_counts['youngo_manual_grants']);
        ypclb1_add_check($checks, 'no_legacy_payment_or_enrol_rows_created', $after_counts['payment'] === $before_counts['payment'] && $after_counts['enrol'] === $before_counts['enrol']);
    }
}

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[] = $name;
    }
}

$result = array(
    'phase' => 'PAYMENT.CHECKOUT.LOCAL.BLOCK.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
