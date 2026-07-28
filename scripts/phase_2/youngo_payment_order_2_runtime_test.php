<?php
/**
 * PAYMENT.ORDER.2 controlled local runtime test.
 *
 * Creates one local diagnostic checkout order through Youngo_checkout_model,
 * exercises lookup and status transitions, then deletes the diagnostic order.
 * No Paymob calls, entitlements, legacy enrolments, or payment rows are created.
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
    'db_writes' => 'one_diagnostic_checkout_order_created_then_deleted',
    'cleanup' => 'not_started',
);

function ypo2_add_check(&$checks, $name, $passed, $detail = '')
{
    $checks[$name] = array(
        'status' => $passed ? 'PASS' : 'FAIL',
        'detail' => $detail,
    );
}

function ypo2_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypo2_table_exists($db, $table)
{
    return $db->table_exists($table);
}

function ypo2_table_count($db, $table)
{
    if (!ypo2_table_exists($db, $table)) {
        return null;
    }

    return (int) $db->count_all($table);
}

function ypo2_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ypo2_table_count($db, $table);
    }

    return $counts;
}

function ypo2_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ypo2_auto_increment($db, $table)
{
    $query = $db->query(
        'SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        array($table)
    );
    $row = $query ? $query->row_array() : array();

    return isset($row['AUTO_INCREMENT']) ? (int) $row['AUTO_INCREMENT'] : null;
}

function ypo2_select_fixture($db)
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

function ypo2_reference_is_safe($reference)
{
    return is_string($reference) && preg_match('/^YGO-[0-9]{14}-[A-F0-9]{12,20}$/', $reference);
}

function ypo2_reference_count($db, $reference)
{
    return (int) $db
        ->where('order_reference', $reference)
        ->count_all_results('youngo_checkout_orders');
}

$db_obj = null;
$model = null;
$diagnostic_order_id = null;
$diagnostic_order_reference = null;
$cleanup_deleted = false;
$cleanup_marked_cancelled = false;

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
    $db_config = ypo2_db_config();
    $db_obj = DB($db_config, true);
    $model = new Youngo_checkout_model(array('db' => $db_obj));
    $reader = new Youngo_paymob_config(array('load_local_override' => false));

    ypo2_add_check($checks, 'config_default_disabled', $reader->is_enabled() === false);
    ypo2_add_check($checks, 'config_default_currency_egp', $reader->get_currency() === 'EGP');
    ypo2_add_check($checks, 'config_default_mode_sandbox', $reader->get_mode() === 'sandbox');

    $schema = $model->get_schema_readiness();
    $details['schema_ready'] = !empty($schema['ready']);
    ypo2_add_check($checks, 'schema_ready', !empty($schema['ready']));

    $fixture = ypo2_select_fixture($db_obj);
    if (empty($fixture)) {
        ypo2_add_check($checks, 'fixture_available', false, 'No safe non-root learner plus YounGo paid purchase-compatible course was found.');
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

        ypo2_add_check($checks, 'fixture_available', true);
        ypo2_add_check($checks, 'fixture_user_not_root', (int) $fixture['user']['id'] !== 1 && (int) $fixture['user']['role_id'] === 2);
        ypo2_add_check($checks, 'fixture_course_purchase_compatible', in_array($fixture['course']['youngo_access_mode'], array('purchase_only', 'subscription_and_purchase'), true));

        $before_counts = ypo2_counts($db_obj, $protected_tables);
        $details['protected_counts_before'] = $before_counts;
        $before_checkout_auto_increment = ypo2_auto_increment($db_obj, 'youngo_checkout_orders');
        $details['checkout_auto_increment_before'] = $before_checkout_auto_increment;

        $can_start = $model->can_start_checkout((int) $fixture['user']['id'], (int) $fixture['course']['id']);
        $details['can_start_checkout_code'] = isset($can_start['code']) ? $can_start['code'] : null;
        ypo2_add_check($checks, 'can_start_checkout_allowed', !empty($can_start['ok']) && $can_start['code'] === 'checkout_allowed');

        $create = $model->create_draft_order((int) $fixture['user']['id'], (int) $fixture['course']['id'], $fixture['amount'], 'EGP');
        $details['create_result_code'] = isset($create['code']) ? $create['code'] : null;
        ypo2_add_check($checks, 'draft_order_created', !empty($create['ok']) && isset($create['data']['order_id'], $create['data']['order_reference']));

        if (!empty($create['ok'])) {
            $diagnostic_order_id = (int) $create['data']['order_id'];
            $diagnostic_order_reference = (string) $create['data']['order_reference'];
            $details['diagnostic_order_id'] = $diagnostic_order_id;
            $details['diagnostic_order_reference'] = $diagnostic_order_reference;

            ypo2_add_check($checks, 'order_reference_format_safe', ypo2_reference_is_safe($diagnostic_order_reference));
            ypo2_add_check($checks, 'order_reference_unique', ypo2_reference_count($db_obj, $diagnostic_order_reference) === 1);

            $by_id = $model->get_order($diagnostic_order_id);
            $by_reference = $model->get_order_by_reference($diagnostic_order_reference);
            ypo2_add_check($checks, 'lookup_by_id', !empty($by_id) && (int) $by_id['id'] === $diagnostic_order_id);
            ypo2_add_check($checks, 'lookup_by_reference', !empty($by_reference) && (int) $by_reference['id'] === $diagnostic_order_id);
            ypo2_add_check($checks, 'draft_order_amount_currency_egp', !empty($by_id) && (string) $by_id['currency'] === 'EGP' && (int) $by_id['total_amount_cents'] === (int) round($fixture['amount'] * 100));
            ypo2_add_check($checks, 'draft_order_entitlement_not_started', !empty($by_id) && (int) $by_id['entitlement_issued'] === 0 && (string) $by_id['entitlement_issuance_status'] === 'not_started');

            $pending = $model->mark_pending_gateway($diagnostic_order_id, 'paymob', 'diagnostic-intention-reference');
            $details['pending_result_code'] = isset($pending['code']) ? $pending['code'] : null;
            ypo2_add_check($checks, 'mark_pending_gateway', !empty($pending['ok']) && $pending['code'] === 'order_pending_gateway');

            $awaiting = $model->mark_awaiting_webhook($diagnostic_order_id);
            $details['awaiting_result_code'] = isset($awaiting['code']) ? $awaiting['code'] : null;
            ypo2_add_check($checks, 'mark_awaiting_webhook', !empty($awaiting['ok']) && $awaiting['code'] === 'order_awaiting_webhook');

            $invalid_transition = $model->mark_pending_gateway($diagnostic_order_id, 'paymob', 'diagnostic-second-reference');
            $details['invalid_transition_result_code'] = isset($invalid_transition['code']) ? $invalid_transition['code'] : null;
            ypo2_add_check($checks, 'invalid_transition_rejected', empty($invalid_transition['ok']) && $invalid_transition['code'] === 'invalid_order_status');

            $cancel = $model->mark_cancelled($diagnostic_order_id, 'PAYMENT.ORDER.2 diagnostic cleanup');
            $details['cancel_result_code'] = isset($cancel['code']) ? $cancel['code'] : null;
            ypo2_add_check($checks, 'mark_cancelled_for_cleanup', !empty($cancel['ok']) && $cancel['code'] === 'order_cancelled');
            $cleanup_marked_cancelled = !empty($cancel['ok']);
        }
    }
} catch (Throwable $exception) {
    ypo2_add_check($checks, 'runtime_exception', false, get_class($exception) . ': ' . $exception->getMessage());
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
    $after_counts = ypo2_counts($db_obj, $protected_tables);
    $after_checkout_auto_increment = ypo2_auto_increment($db_obj, 'youngo_checkout_orders');
    $details['protected_counts_after_cleanup'] = $after_counts;
    $details['checkout_auto_increment_after_cleanup'] = $after_checkout_auto_increment;
    $details['cleanup'] = $cleanup_deleted ? 'deleted_diagnostic_order' : ($cleanup_marked_cancelled ? 'left_cancelled_order' : 'no_order_created');

    if (isset($before_counts)) {
        ypo2_add_check($checks, 'checkout_order_count_restored', isset($before_counts['youngo_checkout_orders']) && $after_counts['youngo_checkout_orders'] === $before_counts['youngo_checkout_orders']);
        ypo2_add_check($checks, 'checkout_auto_increment_clean_when_empty', (int) $before_counts['youngo_checkout_orders'] !== 0 || $after_checkout_auto_increment === 1);
        ypo2_add_check($checks, 'protected_counts_restored_after_cleanup', ypo2_counts_match($before_counts, $after_counts));
        ypo2_add_check($checks, 'no_payment_transactions_created', isset($before_counts['youngo_payment_transactions']) && $after_counts['youngo_payment_transactions'] === $before_counts['youngo_payment_transactions']);
        ypo2_add_check($checks, 'no_payment_enrol_entitlement_rows_created', isset($before_counts['payment'], $before_counts['enrol'], $before_counts['youngo_course_access'], $before_counts['youngo_user_subscriptions']) && $after_counts['payment'] === $before_counts['payment'] && $after_counts['enrol'] === $before_counts['enrol'] && $after_counts['youngo_course_access'] === $before_counts['youngo_course_access'] && $after_counts['youngo_user_subscriptions'] === $before_counts['youngo_user_subscriptions']);
    }
}

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[] = $name;
    }
}

$result = array(
    'phase' => 'PAYMENT.ORDER.2',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
