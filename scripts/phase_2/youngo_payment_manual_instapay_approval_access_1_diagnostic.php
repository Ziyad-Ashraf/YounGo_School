<?php
/**
 * PAYMENT.MANUAL.INSTAPAY.APPROVAL.ACCESS.1 diagnostic.
 *
 * Creates a DB backup, inserts temporary checkout/submission/evidence fixtures,
 * approves and rejects through Youngo_instapay_payment_model, deletes all
 * temporary rows/files, and verifies payment/access counts return to baseline.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
defined('BASEPATH') || define('BASEPATH', $root . '/system/');
defined('APPPATH') || define('APPPATH', $root . '/application/');
defined('FCPATH') || define('FCPATH', $root . '/');
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
require_once APPPATH . 'models/Youngo_entitlement_write_model.php';
require_once APPPATH . 'models/Youngo_instapay_payment_model.php';

$checks = array();
$details = array(
    'db_writes' => 'temporary_checkout_instapay_course_access_rows_inserted_then_deleted',
    'filesystem_writes' => 'temporary_png_evidence_files_created_then_deleted',
    'cleanup' => 'not_started',
    'backup_path' => '',
    'backup_size' => '',
    'backup_sha256' => '',
    'approved_instapay_review_statuses' => 'pending_review, approved, rejected',
);
$fixture_order_ids = array();
$fixture_submission_ids = array();
$fixture_course_access_ids = array();
$fixture_files = array();

function ymiap1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ymiap1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ymiap1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ymiap1_backup_ident($name)
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function ymiap1_backup_value($mysqli, $value)
{
    return $value === null ? 'NULL' : "'" . $mysqli->real_escape_string($value) . "'";
}

function ymiap1_connect_mysqli($config)
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

function ymiap1_create_backup($root)
{
    $mysqli = ymiap1_connect_mysqli(ymiap1_db_config());
    if (!$mysqli) {
        return array('ok' => false, 'error' => 'connect_failed');
    }

    $backup_dir = dirname($root) . '/backups';
    if (!is_dir($backup_dir) && !mkdir($backup_dir, 0777, true)) {
        return array('ok' => false, 'error' => 'backup_dir_failed');
    }

    $path = $backup_dir . '/youngo_school_before_payment_manual_instapay_approval_access_1_' . date('Y_m_d_His') . '.sql';
    $fh = fopen($path, 'wb');
    if (!$fh) {
        return array('ok' => false, 'error' => 'backup_file_failed');
    }

    fwrite($fh, "-- YounGo backup before PAYMENT.MANUAL.INSTAPAY.APPROVAL.ACCESS.1 diagnostic\n");
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
        $create = $mysqli->query('SHOW CREATE TABLE ' . ymiap1_backup_ident($table));
        if (!$create) {
            continue;
        }

        $create_row = $create->fetch_assoc();
        fwrite($fh, "\n-- Table " . $table . "\n");
        fwrite($fh, 'DROP TABLE IF EXISTS ' . ymiap1_backup_ident($table) . ";\n");
        fwrite($fh, $create_row['Create Table'] . ";\n\n");

        $rows = $mysqli->query('SELECT * FROM ' . ymiap1_backup_ident($table));
        if (!$rows) {
            continue;
        }

        while ($data = $rows->fetch_assoc()) {
            $columns = array();
            $values = array();
            foreach ($data as $column => $value) {
                $columns[] = ymiap1_backup_ident($column);
                $values[] = ymiap1_backup_value($mysqli, $value);
            }
            fwrite($fh, 'INSERT INTO ' . ymiap1_backup_ident($table) . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n");
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

function ymiap1_filter_columns($db, $table, $data)
{
    $filtered = array();
    foreach ($data as $field => $value) {
        if ($db->field_exists($field, $table)) {
            $filtered[$field] = $value;
        }
    }

    return $filtered;
}

function ymiap1_count($db, $table)
{
    return $db->table_exists($table) ? (int) $db->count_all($table) : null;
}

function ymiap1_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ymiap1_count($db, $table);
    }

    return $counts;
}

function ymiap1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ymiap1_first_id($db, $table, $where = array())
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

function ymiap1_fixture_user_course($db)
{
    if (!$db->table_exists('users') || !$db->table_exists('course')) {
        return array(null, null);
    }

    $users = $db->select('id')->where('role_id', 2)->order_by('id', 'asc')->get('users')->result_array();
    $courses = $db->select('id')->order_by('id', 'asc')->get('course')->result_array();
    foreach ($users as $user) {
        foreach ($courses as $course) {
            $has_access = false;
            if ($db->table_exists('youngo_course_access')) {
                $has_access = (int) $db
                    ->where('user_id', (int) $user['id'])
                    ->where('course_id', (int) $course['id'])
                    ->where('status', 'active')
                    ->count_all_results('youngo_course_access') > 0;
            }
            if (!$has_access) {
                return array((int) $user['id'], (int) $course['id']);
            }
        }
    }

    return array(null, null);
}

function ymiap1_create_png_fixture($root, $suffix)
{
    $dir = $root . '/uploads/youngo/instapay_evidence';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $filename = 'ymiap1-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $suffix) . '-' . substr(hash('sha256', microtime(true)), 0, 10) . '.png';
    $absolute = $dir . '/' . $filename;
    file_put_contents($absolute, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAFgwJ/l9Wm1QAAAABJRU5ErkJggg=='));

    return array(
        'absolute_path' => $absolute,
        'relative_path' => 'uploads/youngo/instapay_evidence/' . $filename,
        'size' => filesize($absolute),
    );
}

function ymiap1_insert_order($db, $user_id, $course_id, $reference, $amount = '96.00', $order_type = 'course_purchase', $plan_id = null)
{
    $now = time();
    $item_type = $order_type === 'course_purchase' ? 'course' : 'subscription';
    $snapshot = array(
        'snapshot_version' => 'PAYMENT.COUPON.CHECKOUT.SNAPSHOT.WRITE.1',
        'snapshot_action' => 'diagnostic_instapay_approval_snapshot',
        'created_at' => $now,
        'order' => array(
            'order_reference' => $reference,
            'user_id' => (int) $user_id,
            'item_type' => $item_type,
            'course_id' => $item_type === 'course' ? (int) $course_id : null,
            'subscription_plan_id' => $item_type === 'subscription' ? (int) $plan_id : null,
            'item_title_snapshot' => $item_type === 'course' ? 'Diagnostic Instapay Course' : 'Diagnostic Instapay Subscription',
            'original_amount' => '120.00',
            'coupon_code' => 'YMIAP1SAVE20',
            'coupon_discount_type' => 'percentage',
            'coupon_discount_value' => '20.00',
            'discount_amount' => '24.00',
            'final_amount' => $amount,
            'currency' => 'EGP',
            'selected_payment_method' => 'instapay_manual',
        ),
    );

    $db->insert('youngo_checkout_orders', ymiap1_filter_columns($db, 'youngo_checkout_orders', array(
        'user_id' => (int) $user_id,
        'order_reference' => $reference,
        'order_type' => $order_type,
        'status' => 'draft',
        'course_id' => $item_type === 'course' ? (int) $course_id : null,
        'plan_id' => $item_type === 'subscription' ? (int) $plan_id : null,
        'subtotal_amount' => '120.00',
        'discount_amount' => '24.00',
        'tax_amount' => '0.00',
        'total_amount' => $amount,
        'total_amount_cents' => (int) round(((float) $amount) * 100),
        'currency' => 'EGP',
        'coupon_id' => 987654321,
        'coupon_code' => 'YMIAP1SAVE20',
        'coupon_discount_type' => 'percentage',
        'coupon_discount_value' => '20.00',
        'selected_payment_method' => null,
        'item_title_snapshot' => $item_type === 'course' ? 'Diagnostic Instapay Course' : 'Diagnostic Instapay Subscription',
        'checkout_snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_SLASHES),
        'payment_gateway' => null,
        'gateway_environment' => 'sandbox',
        'idempotency_key' => hash('sha256', $reference),
        'last_hmac_verified' => 0,
        'entitlement_issued' => 0,
        'entitlement_issuance_status' => 'not_started',
        'metadata' => json_encode(array('phase' => 'PAYMENT.MANUAL.INSTAPAY.APPROVAL.ACCESS.1', 'fixture' => true), JSON_UNESCAPED_SLASHES),
        'created_at' => $now,
        'updated_at' => $now,
    )));

    return (int) $db->insert_id();
}

function ymiap1_submission_snapshot($order_id, $user_id, $course_id, $file, $amount = '96.00', $item_type = 'course')
{
    $now = time();
    return array(
        'snapshot_version' => 'PAYMENT.MANUAL.INSTAPAY.SUBMISSION.UPLOAD.1',
        'created_at' => $now,
        'status_model' => array('pending_review', 'approved', 'rejected'),
        'order' => array(
            'order_id' => (int) $order_id,
            'user_id' => (int) $user_id,
            'status' => 'draft',
            'item_type' => $item_type,
            'course_id' => $item_type === 'course' ? (int) $course_id : null,
            'item_title_snapshot' => $item_type === 'course' ? 'Diagnostic Instapay Course' : 'Diagnostic Instapay Subscription',
            'original_amount' => '120.00',
            'coupon_code' => 'YMIAP1SAVE20',
            'coupon_discount_type' => 'percentage',
            'coupon_discount_value' => '20.00',
            'discount_amount' => '24.00',
            'final_amount' => $amount,
            'currency' => 'EGP',
            'selected_payment_method' => 'instapay_manual',
        ),
        'instapay' => array(
            'expected_amount' => $amount,
            'submitted_amount' => $amount,
            'currency' => 'EGP',
            'target_label' => 'Diagnostic Instapay Target',
            'target_address' => 'diagnostic@instapay',
            'target_link' => 'https://example.com/instapay-diagnostic',
            'screenshot_path' => $file['relative_path'],
            'screenshot_original_name' => 'diagnostic-instapay-approval.png',
            'screenshot_mime' => 'image/png',
            'screenshot_size' => $file['size'],
            'transaction_reference' => 'YMIAP1-TEMP-REF',
            'user_note' => 'Temporary approval diagnostic note.',
        ),
    );
}

function ymiap1_insert_submission($db, $order_id, $user_id, $course_id, $file, $amount = '96.00', $item_type = 'course')
{
    $now = time();
    $db->insert('youngo_instapay_payment_submissions', ymiap1_filter_columns($db, 'youngo_instapay_payment_submissions', array(
        'order_id' => (int) $order_id,
        'user_id' => (int) $user_id,
        'status' => 'pending_review',
        'expected_amount' => $amount,
        'submitted_amount' => $amount,
        'currency' => 'EGP',
        'instapay_target_label' => 'Diagnostic Instapay Target',
        'instapay_target_address' => 'diagnostic@instapay',
        'instapay_target_link' => 'https://example.com/instapay-diagnostic',
        'screenshot_path' => $file['relative_path'],
        'screenshot_original_name' => 'diagnostic-instapay-approval.png',
        'screenshot_mime' => 'image/png',
        'screenshot_size' => $file['size'],
        'transaction_reference' => 'YMIAP1-TEMP-REF',
        'user_note' => 'Temporary approval diagnostic note.',
        'admin_note' => null,
        'reviewed_by_user_id' => null,
        'reviewed_at' => null,
        'approved_at' => null,
        'rejected_at' => null,
        'access_issued' => 0,
        'access_issued_at' => null,
        'access_reference_type' => null,
        'access_reference_id' => null,
        'snapshot_json' => json_encode(ymiap1_submission_snapshot($order_id, $user_id, $course_id, $file, $amount, $item_type), JSON_UNESCAPED_SLASHES),
        'created_at' => $now,
        'updated_at' => $now,
    )));

    return (int) $db->insert_id();
}

class Ymiap1_session_stub
{
    public function userdata($key)
    {
        return null;
    }
}

$backup = ymiap1_create_backup($root);
if (!empty($backup['ok'])) {
    $details['backup_path'] = $backup['path'];
    $details['backup_size'] = (string) $backup['size'];
    $details['backup_sha256'] = $backup['sha256'];
}

$CI = new stdClass();
$CI->session = new Ymiap1_session_stub();
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ymiap1_db_config(), true);
$CI->db = $db;
$model = new Youngo_instapay_payment_model(array('db' => $db));

$paths = array(
    'controller' => $root . '/application/controllers/Youngo_instapay_payments.php',
    'model' => $root . '/application/models/Youngo_instapay_payment_model.php',
    'entitlement' => $root . '/application/models/Youngo_entitlement_write_model.php',
    'detail_view' => $root . '/application/views/backend/admin/youngo_instapay_payment_view.php',
    'inbox_view' => $root . '/application/views/backend/admin/youngo_instapay_payments.php',
    'routes' => $root . '/application/config/routes.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
);

foreach ($paths as $name => $path) {
    ymiap1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}
ymiap1_check($checks, 'backup_created_before_temp_writes', !empty($backup['ok']), !empty($backup['ok']) ? $backup['path'] . '|' . $backup['size'] . '|' . $backup['sha256'] : (isset($backup['error']) ? $backup['error'] : 'backup_failed'));

$controller_source = ymiap1_read($paths['controller']);
$model_source = ymiap1_read($paths['model']);
$entitlement_source = ymiap1_read($paths['entitlement']);
$detail_view_source = ymiap1_read($paths['detail_view']);
$inbox_view_source = ymiap1_read($paths['inbox_view']);
$routes_source = ymiap1_read($paths['routes']);
$paymob_source = ymiap1_read($paths['paymob_config']);

ymiap1_check($checks, 'routes_exist_for_post_approve_reject', strpos($routes_source, "admin/youngo/instapay-payments/(:num)/approve") !== false && strpos($routes_source, "admin/youngo/instapay-payments/(:num)/reject") !== false);
ymiap1_check($checks, 'controller_post_only_actions_exist', strpos($controller_source, 'function approve') !== false && strpos($controller_source, 'function reject') !== false && strpos($controller_source, 'require_post') !== false && strpos($controller_source, 'external_payment_confirmed') !== false);
ymiap1_check($checks, 'model_approval_rejection_methods_exist', method_exists($model, 'approve_submission') && method_exists($model, 'reject_submission'));
ymiap1_check($checks, 'entitlement_instapay_method_exists', strpos($entitlement_source, 'issue_instapay_manual_course_access') !== false && strpos($entitlement_source, 'last_hmac_verified') !== false && strpos($entitlement_source, 'approved_at_required') !== false);
ymiap1_check($checks, 'admin_detail_has_pending_only_live_controls', strpos($detail_view_source, "status'] === 'pending_review'") !== false && strpos($detail_view_source, 'I confirm that this payment was received externally.') !== false && strpos($detail_view_source, 'Approve payment and issue access') !== false && strpos($detail_view_source, 'Reject payment') !== false);
ymiap1_check($checks, 'inbox_shows_access_indicator', strpos($inbox_view_source, 'Access</th>') !== false && strpos($inbox_view_source, 'Issued') !== false && strpos($inbox_view_source, 'Not issued') !== false);
ymiap1_check($checks, 'approved_statuses_exact', $model->allowed_statuses() === array('pending_review', 'approved', 'rejected') && $model->normalize_status('cancelled') === null && $model->normalize_status('draft') === null && $model->normalize_status('expired') === null);

$reader = new Youngo_paymob_config(array('load_local_override' => false));
ymiap1_check($checks, 'paymob_default_enabled_false', $reader->is_enabled() === false);
ymiap1_check($checks, 'paymob_default_network_false', $reader->is_network_enabled() === false);
ymiap1_check($checks, 'paymob_static_gates_false', preg_match('/[\'"]enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1 && preg_match('/[\'"]network_enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1);

$protected_tables = array(
    'youngo_instapay_payment_submissions',
    'youngo_checkout_orders',
    'youngo_payment_transactions',
    'youngo_course_access',
    'youngo_user_subscriptions',
    'youngo_manual_grants',
    'youngo_coupon_usages',
    'payment',
    'enrol',
);
$baseline_counts = ymiap1_counts($db, $protected_tables);

$admin_id = ymiap1_first_id($db, 'users', array('role_id' => 1));
list($user_id, $course_id) = ymiap1_fixture_user_course($db);
$plan_id = ymiap1_first_id($db, 'youngo_subscription_plans');
ymiap1_check($checks, 'fixture_admin_user_course_available', $admin_id !== null && $user_id !== null && $course_id !== null, 'admin=' . $admin_id . ', user=' . $user_id . ', course=' . $course_id);

try {
    if ($admin_id !== null && $user_id !== null && $course_id !== null) {
        $details['cleanup'] = 'started';

        $file = ymiap1_create_png_fixture($root, 'approve');
        $fixture_files[] = $file['absolute_path'];
        $order_id = ymiap1_insert_order($db, $user_id, $course_id, 'YGO-YMIAP1-APPROVE-' . gmdate('His') . '-' . substr(hash('sha256', microtime(true)), 0, 6));
        $fixture_order_ids[] = $order_id;
        $submission_id = ymiap1_insert_submission($db, $order_id, $user_id, $course_id, $file);
        $fixture_submission_ids[] = $submission_id;

        $approve = $model->approve_submission($submission_id, $admin_id, 'Temporary approval diagnostic note.');
        $approved_submission = $model->get_submission_by_id($submission_id);
        $approved_order = $db->where('id', $order_id)->get('youngo_checkout_orders', 1)->row_array();
        $access_rows = $db->where('checkout_order_id', $order_id)->get('youngo_course_access')->result_array();
        foreach ($access_rows as $row) {
            $fixture_course_access_ids[] = (int) $row['id'];
        }
        $access_row = !empty($access_rows) ? $access_rows[0] : array();
        $approved_at = !empty($approved_submission['approved_at']) ? (int) $approved_submission['approved_at'] : 0;

        ymiap1_check($checks, 'approve_pending_submission_succeeds', !empty($approve['ok']) && isset($approve['code']) && $approve['code'] === 'instapay_submission_approved_access_issued', isset($approve['code']) ? $approve['code'] : 'no_code');
        ymiap1_check($checks, 'submission_approved_audit_fields_set', $approved_submission['status'] === 'approved' && (int) $approved_submission['reviewed_by_user_id'] === $admin_id && !empty($approved_submission['reviewed_at']) && !empty($approved_submission['approved_at']) && empty($approved_submission['rejected_at']));
        ymiap1_check($checks, 'order_marked_instapay_paid_equivalent', $approved_order['status'] === 'paid' && $approved_order['selected_payment_method'] === 'instapay_manual' && $approved_order['payment_gateway'] === 'instapay_manual' && empty($approved_order['last_hmac_verified']) && empty($approved_order['provider_transaction_id']));
        ymiap1_check($checks, 'access_issued_once_from_approved_at', count($access_rows) === 1 && !empty($access_row) && (int) $access_row['start_date'] === $approved_at && (int) $approved_order['entitlement_course_access_id'] === (int) $access_row['id']);
        ymiap1_check($checks, 'submission_records_access_reference', !empty($approved_submission['access_issued']) && $approved_submission['access_reference_type'] === 'course_access' && (int) $approved_submission['access_reference_id'] === (int) $access_row['id']);

        $duplicate = $model->approve_submission($submission_id, $admin_id, 'Duplicate approval attempt.');
        $access_count_after_duplicate = (int) $db->where('checkout_order_id', $order_id)->count_all_results('youngo_course_access');
        ymiap1_check($checks, 'duplicate_approve_idempotent_no_duplicate_access', !empty($duplicate['ok']) && isset($duplicate['code']) && $duplicate['code'] === 'submission_already_approved' && $access_count_after_duplicate === 1);

        $reject_approved = $model->reject_submission($submission_id, $admin_id, 'Should not reject approved.');
        ymiap1_check($checks, 'approved_cannot_be_rejected', empty($reject_approved['ok']) && isset($reject_approved['code']) && $reject_approved['code'] === 'approved_submission_cannot_be_rejected');

        $reject_file = ymiap1_create_png_fixture($root, 'reject');
        $fixture_files[] = $reject_file['absolute_path'];
        $reject_order_id = ymiap1_insert_order($db, $user_id, $course_id, 'YGO-YMIAP1-REJECT-' . gmdate('His') . '-' . substr(hash('sha256', microtime(true)), 0, 6));
        $fixture_order_ids[] = $reject_order_id;
        $reject_submission_id = ymiap1_insert_submission($db, $reject_order_id, $user_id, $course_id, $reject_file);
        $fixture_submission_ids[] = $reject_submission_id;

        $reject = $model->reject_submission($reject_submission_id, $admin_id, 'Temporary rejection diagnostic note.');
        $rejected_submission = $model->get_submission_by_id($reject_submission_id);
        $rejected_order = $db->where('id', $reject_order_id)->get('youngo_checkout_orders', 1)->row_array();
        $rejected_access_count = (int) $db->where('checkout_order_id', $reject_order_id)->count_all_results('youngo_course_access');
        ymiap1_check($checks, 'reject_pending_submission_succeeds_without_access', !empty($reject['ok']) && $rejected_submission['status'] === 'rejected' && !empty($rejected_submission['rejected_at']) && empty($rejected_submission['access_issued']) && $rejected_access_count === 0);
        ymiap1_check($checks, 'reject_does_not_mark_order_paid', $rejected_order['status'] === 'draft' && empty($rejected_order['payment_gateway']) && empty($rejected_order['paid_at']) && empty($rejected_order['entitlement_issued']));

        $approve_rejected = $model->approve_submission($reject_submission_id, $admin_id, 'Should not approve rejected.');
        ymiap1_check($checks, 'rejected_cannot_be_approved', empty($approve_rejected['ok']) && isset($approve_rejected['code']) && $approve_rejected['code'] === 'rejected_submission_cannot_be_approved');

        if ($plan_id !== null) {
            $subscription_file = ymiap1_create_png_fixture($root, 'subscription');
            $fixture_files[] = $subscription_file['absolute_path'];
            $subscription_order_id = ymiap1_insert_order($db, $user_id, null, 'YGO-YMIAP1-SUB-' . gmdate('His') . '-' . substr(hash('sha256', microtime(true)), 0, 6), '96.00', 'subscription_purchase', $plan_id);
            $fixture_order_ids[] = $subscription_order_id;
            $subscription_submission_id = ymiap1_insert_submission($db, $subscription_order_id, $user_id, null, $subscription_file, '96.00', 'subscription');
            $fixture_submission_ids[] = $subscription_submission_id;
            $subscription_approve = $model->approve_submission($subscription_submission_id, $admin_id, 'Subscription should defer.');
            $subscription_submission = $model->get_submission_by_id($subscription_submission_id);
            ymiap1_check($checks, 'subscription_approval_deferred_safely', empty($subscription_approve['ok']) && isset($subscription_approve['code']) && $subscription_approve['code'] === 'subscription_instapay_approval_deferred' && $subscription_submission['status'] === 'pending_review');
        } else {
            ymiap1_check($checks, 'subscription_approval_deferred_safely', true, 'no subscription plan fixture available; existing entitlement method remains checkout_issuance_not_implemented');
        }

        ymiap1_check($checks, 'no_instapay_status_outside_allowlist', (int) $db->where_in('id', $fixture_submission_ids)->where_not_in('status', array('pending_review', 'approved', 'rejected'))->count_all_results('youngo_instapay_payment_submissions') === 0);
        ymiap1_check($checks, 'no_paymob_or_legacy_payment_rows_created', $baseline_counts['youngo_payment_transactions'] === ymiap1_count($db, 'youngo_payment_transactions') && $baseline_counts['payment'] === ymiap1_count($db, 'payment'));
        ymiap1_check($checks, 'zero_amount_coupon_flow_source_unchanged', strpos($model_source, 'zero_amount_coupon') !== false && strpos($entitlement_source, 'issue_zero_amount_coupon_course_access') !== false);
    }
} finally {
    if (!empty($fixture_course_access_ids) && $db->table_exists('youngo_course_access')) {
        $db->where_in('id', array_values(array_unique($fixture_course_access_ids)))->delete('youngo_course_access');
    }
    if (!empty($fixture_submission_ids) && $db->table_exists('youngo_instapay_payment_submissions')) {
        $db->where_in('id', array_values(array_unique($fixture_submission_ids)))->delete('youngo_instapay_payment_submissions');
    }
    if (!empty($fixture_order_ids) && $db->table_exists('youngo_checkout_orders')) {
        $db->where_in('id', array_values(array_unique($fixture_order_ids)))->delete('youngo_checkout_orders');
    }
    foreach ($fixture_files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
}

$after_counts = ymiap1_counts($db, $protected_tables);
$details['cleanup'] = ymiap1_counts_match($baseline_counts, $after_counts) ? 'completed' : 'failed';
ymiap1_check($checks, 'temporary_rows_deleted_and_counts_restored', ymiap1_counts_match($baseline_counts, $after_counts), json_encode(array('before' => $baseline_counts, 'after' => $after_counts), JSON_UNESCAPED_SLASHES));
ymiap1_check($checks, 'temporary_uploaded_files_deleted', empty(array_filter($fixture_files, 'is_file')), implode(',', $fixture_files));
ymiap1_check($checks, 'no_access_enrolment_subscription_drift', $baseline_counts['youngo_course_access'] === $after_counts['youngo_course_access'] && $baseline_counts['youngo_user_subscriptions'] === $after_counts['youngo_user_subscriptions'] && $baseline_counts['youngo_manual_grants'] === $after_counts['youngo_manual_grants'] && $baseline_counts['enrol'] === $after_counts['enrol']);
ymiap1_check($checks, 'no_paymob_activation_or_transactions_after_cleanup', $baseline_counts['youngo_payment_transactions'] === $after_counts['youngo_payment_transactions'] && $reader->is_enabled() === false && $reader->is_network_enabled() === false);

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo 'phase: PAYMENT.MANUAL.INSTAPAY.APPROVAL.ACCESS.1' . PHP_EOL;
echo 'mode: temp_fixture_approval_rejection_access_cleanup' . PHP_EOL;
echo 'db_writes: ' . $details['db_writes'] . PHP_EOL;
echo 'filesystem_writes: ' . $details['filesystem_writes'] . PHP_EOL;
echo 'backup_path: ' . $details['backup_path'] . PHP_EOL;
echo 'backup_size: ' . $details['backup_size'] . PHP_EOL;
echo 'backup_sha256: ' . $details['backup_sha256'] . PHP_EOL;
echo 'cleanup: ' . $details['cleanup'] . PHP_EOL;
echo 'approved_instapay_review_statuses: ' . $details['approved_instapay_review_statuses'] . PHP_EOL;
echo PHP_EOL;

foreach ($checks as $name => $check) {
    echo $check['status'] . ' - ' . $name;
    if ($check['detail'] !== '') {
        echo ' :: ' . $check['detail'];
    }
    echo PHP_EOL;
}

echo PHP_EOL . 'RESULT: ' . (empty($failed) ? 'PASS' : 'FAIL') . PHP_EOL;
if (!empty($failed)) {
    exit(1);
}
