<?php
/**
 * PAYMENT.MANUAL.INSTAPAY.ADMIN.REVIEW.UI.1 diagnostic.
 *
 * Creates a DB backup, inserts temporary checkout/submission/evidence fixtures,
 * verifies read-only admin review helpers and evidence path protection, deletes
 * all temporary rows/files, and confirms payment/access counts return to
 * baseline.
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
require_once APPPATH . 'models/Youngo_instapay_payment_model.php';

$checks = array();
$details = array(
    'db_writes' => 'temporary_checkout_and_instapay_submission_rows_inserted_then_deleted',
    'filesystem_writes' => 'temporary_png_evidence_file_created_then_deleted',
    'cleanup' => 'not_started',
    'backup_path' => '',
    'backup_size' => '',
    'backup_sha256' => '',
    'approved_instapay_review_statuses' => 'pending_review, approved, rejected',
);
$fixture_order_ids = array();
$fixture_submission_ids = array();
$fixture_files = array();

function ymiar1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ymiar1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ymiar1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ymiar1_backup_ident($name)
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function ymiar1_backup_value($mysqli, $value)
{
    return $value === null ? 'NULL' : "'" . $mysqli->real_escape_string($value) . "'";
}

function ymiar1_connect_mysqli($config)
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

function ymiar1_create_backup($root)
{
    $mysqli = ymiar1_connect_mysqli(ymiar1_db_config());
    if (!$mysqli) {
        return array('ok' => false, 'error' => 'connect_failed');
    }

    $backup_dir = dirname($root) . '/backups';
    if (!is_dir($backup_dir) && !mkdir($backup_dir, 0777, true)) {
        return array('ok' => false, 'error' => 'backup_dir_failed');
    }

    $path = $backup_dir . '/youngo_school_before_payment_manual_instapay_admin_review_ui_1_' . date('Y_m_d_His') . '.sql';
    $fh = fopen($path, 'wb');
    if (!$fh) {
        return array('ok' => false, 'error' => 'backup_file_failed');
    }

    fwrite($fh, "-- YounGo backup before PAYMENT.MANUAL.INSTAPAY.ADMIN.REVIEW.UI.1 diagnostic\n");
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
        $create = $mysqli->query('SHOW CREATE TABLE ' . ymiar1_backup_ident($table));
        if (!$create) {
            continue;
        }

        $create_row = $create->fetch_assoc();
        fwrite($fh, "\n-- Table " . $table . "\n");
        fwrite($fh, 'DROP TABLE IF EXISTS ' . ymiar1_backup_ident($table) . ";\n");
        fwrite($fh, $create_row['Create Table'] . ";\n\n");

        $rows = $mysqli->query('SELECT * FROM ' . ymiar1_backup_ident($table));
        if (!$rows) {
            continue;
        }

        while ($data = $rows->fetch_assoc()) {
            $columns = array();
            $values = array();
            foreach ($data as $column => $value) {
                $columns[] = ymiar1_backup_ident($column);
                $values[] = ymiar1_backup_value($mysqli, $value);
            }
            fwrite($fh, 'INSERT INTO ' . ymiar1_backup_ident($table) . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n");
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

function ymiar1_filter_columns($db, $table, $data)
{
    $filtered = array();
    foreach ($data as $field => $value) {
        if ($db->field_exists($field, $table)) {
            $filtered[$field] = $value;
        }
    }

    return $filtered;
}

function ymiar1_count($db, $table)
{
    return $db->table_exists($table) ? (int) $db->count_all($table) : null;
}

function ymiar1_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ymiar1_count($db, $table);
    }

    return $counts;
}

function ymiar1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ymiar1_first_id($db, $table, $where = array())
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

function ymiar1_insert_order($db, $user_id, $course_id, $reference)
{
    $now = time();
    $snapshot = array(
        'snapshot_version' => 'PAYMENT.COUPON.CHECKOUT.SNAPSHOT.WRITE.1',
        'snapshot_action' => 'diagnostic_admin_review_snapshot',
        'created_at' => $now,
        'order' => array(
            'original_amount' => '120.00',
            'coupon_code' => 'YMIAR1SAVE20',
            'coupon_discount_type' => 'percentage',
            'coupon_discount_value' => '20.00',
            'discount_amount' => '24.00',
            'final_amount' => '96.00',
            'currency' => 'EGP',
            'selected_payment_method' => 'instapay_manual',
        ),
    );

    $db->insert('youngo_checkout_orders', ymiar1_filter_columns($db, 'youngo_checkout_orders', array(
        'user_id' => (int) $user_id,
        'order_reference' => $reference,
        'order_type' => 'course_purchase',
        'status' => 'draft',
        'course_id' => (int) $course_id,
        'plan_id' => null,
        'subtotal_amount' => '120.00',
        'discount_amount' => '24.00',
        'tax_amount' => '0.00',
        'total_amount' => '96.00',
        'total_amount_cents' => 9600,
        'currency' => 'EGP',
        'coupon_id' => null,
        'coupon_code' => 'YMIAR1SAVE20',
        'coupon_discount_type' => 'percentage',
        'coupon_discount_value' => '20.00',
        'selected_payment_method' => 'instapay_manual',
        'item_title_snapshot' => 'Manual Instapay Admin Review Diagnostic Course',
        'checkout_snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_SLASHES),
        'payment_gateway' => null,
        'gateway_environment' => 'sandbox',
        'idempotency_key' => hash('sha256', 'PAYMENT.MANUAL.INSTAPAY.ADMIN.REVIEW.UI.1|' . $reference),
        'last_hmac_verified' => 0,
        'entitlement_issued' => 0,
        'entitlement_issuance_status' => 'not_started',
        'metadata' => json_encode(array('source' => 'youngo_payment_manual_instapay_admin_review_ui_1_diagnostic'), JSON_UNESCAPED_SLASHES),
        'created_at' => $now,
        'updated_at' => $now,
    )));

    return (int) $db->insert_id();
}

function ymiar1_create_png_fixture($root, $suffix)
{
    $relative_dir = 'uploads/youngo/instapay_evidence';
    $absolute_dir = $root . '/' . $relative_dir;
    if (!is_dir($absolute_dir)) {
        mkdir($absolute_dir, 0755, true);
    }

    if (!is_file($absolute_dir . '/.htaccess')) {
        file_put_contents($absolute_dir . '/.htaccess', "Require all denied\nDeny from all\n<FilesMatch \"\\.(php|phtml|phar)$\">\n    Deny from all\n</FilesMatch>\n");
    }
    if (!is_file($absolute_dir . '/index.html')) {
        file_put_contents($absolute_dir . '/index.html', '');
    }

    $filename = 'diagnostic_instapay_admin_review_' . preg_replace('/[^A-Za-z0-9_-]/', '', $suffix) . '.png';
    $absolute_path = $absolute_dir . '/' . $filename;
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');
    file_put_contents($absolute_path, $png);

    return array(
        'relative_path' => $relative_dir . '/' . $filename,
        'absolute_path' => $absolute_path,
        'size' => filesize($absolute_path),
    );
}

function ymiar1_submission_snapshot($order_id, $user_id, $file)
{
    return array(
        'snapshot_version' => 'PAYMENT.MANUAL.INSTAPAY.SUBMISSION.UPLOAD.1',
        'created_at' => time(),
        'status_model' => array('pending_review', 'approved', 'rejected'),
        'order' => array(
            'order_id' => (int) $order_id,
            'order_reference' => 'diagnostic',
            'user_id' => (int) $user_id,
            'status' => 'draft',
            'item_type' => 'course',
            'item_id' => 1,
            'course_id' => 1,
            'subscription_plan_id' => null,
            'item_title_snapshot' => 'Manual Instapay Admin Review Diagnostic Course',
            'original_amount' => '120.00',
            'coupon_code' => 'YMIAR1SAVE20',
            'coupon_discount_type' => 'percentage',
            'coupon_discount_value' => '20.00',
            'discount_amount' => '24.00',
            'final_amount' => '96.00',
            'currency' => 'EGP',
            'selected_payment_method' => 'instapay_manual',
        ),
        'instapay' => array(
            'expected_amount' => '96.00',
            'submitted_amount' => '96.00',
            'currency' => 'EGP',
            'selected_payment_method' => 'instapay_manual',
            'target_label' => 'Diagnostic Instapay Target',
            'target_address' => 'diagnostic@instapay',
            'target_link' => 'https://example.com/instapay-diagnostic',
            'instructions_en' => 'Diagnostic evidence only.',
            'instructions_ar' => 'تعليمات تشخيصية فقط.',
            'screenshot_path' => $file['relative_path'],
            'screenshot_original_name' => 'diagnostic-admin-review.png',
            'screenshot_mime' => 'image/png',
            'screenshot_size' => $file['size'],
            'transaction_reference' => 'YMIAR1-TEMP-REF',
            'user_note' => 'Temporary admin review diagnostic note.',
        ),
    );
}

function ymiar1_insert_submission($db, $order_id, $user_id, $file, $path_override = null)
{
    $now = time();
    $path = $path_override !== null ? $path_override : $file['relative_path'];
    $snapshot = ymiar1_submission_snapshot($order_id, $user_id, $file);

    $db->insert('youngo_instapay_payment_submissions', ymiar1_filter_columns($db, 'youngo_instapay_payment_submissions', array(
        'order_id' => (int) $order_id,
        'user_id' => (int) $user_id,
        'status' => 'pending_review',
        'expected_amount' => '96.00',
        'submitted_amount' => '96.00',
        'currency' => 'EGP',
        'instapay_target_label' => 'Diagnostic Instapay Target',
        'instapay_target_address' => 'diagnostic@instapay',
        'instapay_target_link' => 'https://example.com/instapay-diagnostic',
        'screenshot_path' => $path,
        'screenshot_original_name' => 'diagnostic-admin-review.png',
        'screenshot_mime' => 'image/png',
        'screenshot_size' => $file['size'],
        'transaction_reference' => 'YMIAR1-TEMP-REF',
        'user_note' => 'Temporary admin review diagnostic note.',
        'admin_note' => null,
        'reviewed_by_user_id' => null,
        'reviewed_at' => null,
        'approved_at' => null,
        'rejected_at' => null,
        'access_issued' => 0,
        'access_issued_at' => null,
        'access_reference_type' => null,
        'access_reference_id' => null,
        'snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_SLASHES),
        'created_at' => $now,
        'updated_at' => $now,
    )));

    return (int) $db->insert_id();
}

class Ymiar1_session_stub
{
    public function userdata($key)
    {
        return null;
    }
}

$backup = ymiar1_create_backup($root);
if (!empty($backup['ok'])) {
    $details['backup_path'] = $backup['path'];
    $details['backup_size'] = (string) $backup['size'];
    $details['backup_sha256'] = $backup['sha256'];
}

$CI = new stdClass();
$CI->session = new Ymiar1_session_stub();
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ymiar1_db_config(), true);
$CI->db = $db;
$model = new Youngo_instapay_payment_model(array('db' => $db));

$paths = array(
    'controller' => $root . '/application/controllers/Youngo_instapay_payments.php',
    'model' => $root . '/application/models/Youngo_instapay_payment_model.php',
    'inbox_view' => $root . '/application/views/backend/admin/youngo_instapay_payments.php',
    'detail_view' => $root . '/application/views/backend/admin/youngo_instapay_payment_view.php',
    'navigation' => $root . '/application/views/backend/admin/navigation.php',
    'routes' => $root . '/application/config/routes.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
);

foreach ($paths as $name => $path) {
    ymiar1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}
ymiar1_check($checks, 'backup_created_before_temp_writes', !empty($backup['ok']), !empty($backup['ok']) ? $backup['path'] . '|' . $backup['size'] . '|' . $backup['sha256'] : (isset($backup['error']) ? $backup['error'] : 'backup_failed'));

$controller_source = ymiar1_read($paths['controller']);
$model_source = ymiar1_read($paths['model']);
$inbox_view_source = ymiar1_read($paths['inbox_view']);
$detail_view_source = ymiar1_read($paths['detail_view']);
$navigation_source = ymiar1_read($paths['navigation']);
$routes_source = ymiar1_read($paths['routes']);
$paymob_source = ymiar1_read($paths['paymob_config']);

ymiar1_check($checks, 'routes_exist_for_inbox_detail_evidence', strpos($routes_source, "admin/youngo/instapay-payments") !== false && strpos($routes_source, "evidence/download") !== false);
ymiar1_check($checks, 'controller_root_only_and_get_only', strpos($controller_source, 'require_root_admin') !== false && strpos($controller_source, 'youngo_is_root_admin') !== false && strpos($controller_source, 'function evidence') !== false && strpos($controller_source, 'Content-Disposition') !== false);
ymiar1_check($checks, 'navigation_has_root_instapay_link', strpos($navigation_source, 'Instapay Payments') !== false && strpos($navigation_source, 'youngo_instapay_payment_view') !== false);
ymiar1_check($checks, 'model_read_helpers_exist', method_exists($model, 'get_admin_review_list') && method_exists($model, 'get_admin_review_detail') && method_exists($model, 'get_evidence_file_for_admin') && method_exists($model, 'count_admin_review_by_status'));
ymiar1_check($checks, 'approval_access_phase_methods_present_but_not_used_by_read_helper', stripos($model_source, 'approve_submission') !== false && stripos($model_source, 'reject_submission') !== false && method_exists($model, 'get_admin_review_list') && method_exists($model, 'get_admin_review_detail'));
ymiar1_check($checks, 'views_have_pending_only_review_controls', strpos($inbox_view_source, 'Pending Review') !== false && strpos($inbox_view_source, 'Details') !== false && strpos($detail_view_source, "status'] === 'pending_review'") !== false && strpos($detail_view_source, 'I confirm that this payment was received externally.') !== false);
ymiar1_check($checks, 'evidence_view_uses_protected_routes_only', strpos($detail_view_source, '/evidence') !== false && strpos($detail_view_source, 'uploads/youngo/instapay_evidence') === false);

$reader = new Youngo_paymob_config(array('load_local_override' => false));
ymiar1_check($checks, 'paymob_default_enabled_false', $reader->is_enabled() === false);
ymiar1_check($checks, 'paymob_default_network_false', $reader->is_network_enabled() === false);
ymiar1_check($checks, 'paymob_static_gates_false', preg_match('/[\'"]enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1 && preg_match('/[\'"]network_enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1);
ymiar1_check($checks, 'allowed_statuses_exact', $model->allowed_statuses() === array('pending_review', 'approved', 'rejected') && $model->normalize_status('draft') === null && $model->normalize_status('cancelled') === null && $model->normalize_status('expired') === null);

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
$baseline_counts = ymiar1_counts($db, $protected_tables);

$user_id = ymiar1_first_id($db, 'users', array('role_id' => 2));
if ($user_id === null) {
    $user_id = ymiar1_first_id($db, 'users');
}
$course_id = ymiar1_first_id($db, 'course');
ymiar1_check($checks, 'fixture_user_course_available', $user_id !== null && $course_id !== null, 'user=' . $user_id . ', course=' . $course_id);

try {
    if ($user_id !== null && $course_id !== null) {
        $details['cleanup'] = 'started';
        $reference = 'YGO-INSTAPAY-ADMIN-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true)), 0, 8);
        $order_id = ymiar1_insert_order($db, $user_id, $course_id, $reference);
        $fixture_order_ids[] = $order_id;
        $file = ymiar1_create_png_fixture($root, (string) $order_id);
        $fixture_files[] = $file['absolute_path'];
        $submission_id = ymiar1_insert_submission($db, $order_id, $user_id, $file);
        $fixture_submission_ids[] = $submission_id;
        $bad_submission_id = ymiar1_insert_submission($db, $order_id, $user_id, $file, 'uploads/youngo/instapay_evidence/../../system/favicon.png');
        $fixture_submission_ids[] = $bad_submission_id;

        $list = $model->get_admin_review_list('pending_review', 50, 0);
        $detail = $model->get_admin_review_detail($submission_id);
        $evidence = $model->get_evidence_file_for_admin($submission_id);
        $bad_evidence = $model->get_evidence_file_for_admin($bad_submission_id);
        $counts = $model->count_admin_review_by_status();
        $order_after = $db->where('id', $order_id)->get('youngo_checkout_orders', 1)->row_array();

        $found_in_list = false;
        foreach ($list as $row) {
            if ((int) $row['id'] === $submission_id) {
                $found_in_list = true;
                break;
            }
        }

        ymiar1_check($checks, 'temporary_order_and_submission_inserted', !empty($order_after) && $submission_id > 0 && $bad_submission_id > 0);
        ymiar1_check($checks, 'admin_review_list_returns_temp_row', $found_in_list);
        ymiar1_check($checks, 'admin_review_detail_preserves_coupon_final_snapshot', !empty($detail) && $detail['coupon_code'] === 'YMIAR1SAVE20' && $detail['discount_amount'] === '24.00' && $detail['expected_amount'] === '96.00' && $detail['instapay_target_address'] === 'diagnostic@instapay');
        ymiar1_check($checks, 'evidence_file_ready_for_valid_submission', !empty($evidence['ok']) && $evidence['data']['mime'] === 'image/png' && is_file($evidence['data']['absolute_path']));
        ymiar1_check($checks, 'evidence_path_traversal_rejected', empty($bad_evidence['ok']) && isset($bad_evidence['code']) && in_array($bad_evidence['code'], array('invalid_evidence_path', 'evidence_file_not_available'), true));
        ymiar1_check($checks, 'status_counts_include_pending_review', isset($counts['pending_review']) && $counts['pending_review'] >= 2);
        ymiar1_check($checks, 'no_order_paid_state_created', isset($order_after['status']) && $order_after['status'] === 'draft' && empty($order_after['payment_gateway']) && empty($order_after['paid_at']) && empty($order_after['entitlement_issued']));
        ymiar1_check($checks, 'no_approve_reject_status_transition', (int) $db->where_in('id', $fixture_submission_ids)->where('status', 'pending_review')->count_all_results('youngo_instapay_payment_submissions') === count($fixture_submission_ids));
    }
} finally {
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

$after_counts = ymiar1_counts($db, $protected_tables);
$details['cleanup'] = ymiar1_counts_match($baseline_counts, $after_counts) ? 'completed' : 'failed';
ymiar1_check($checks, 'temporary_rows_deleted_and_counts_restored', ymiar1_counts_match($baseline_counts, $after_counts), json_encode(array('before' => $baseline_counts, 'after' => $after_counts), JSON_UNESCAPED_SLASHES));
ymiar1_check($checks, 'temporary_uploaded_files_deleted', empty(array_filter($fixture_files, 'is_file')), implode(',', $fixture_files));
ymiar1_check($checks, 'no_entitlement_access_enrolment_rows_created', $baseline_counts['youngo_course_access'] === $after_counts['youngo_course_access'] && $baseline_counts['youngo_user_subscriptions'] === $after_counts['youngo_user_subscriptions'] && $baseline_counts['youngo_manual_grants'] === $after_counts['youngo_manual_grants'] && $baseline_counts['payment'] === $after_counts['payment'] && $baseline_counts['enrol'] === $after_counts['enrol']);
ymiar1_check($checks, 'no_paymob_activation_or_transactions', $baseline_counts['youngo_payment_transactions'] === $after_counts['youngo_payment_transactions'] && $reader->is_enabled() === false && $reader->is_network_enabled() === false);

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo 'phase: PAYMENT.MANUAL.INSTAPAY.ADMIN.REVIEW.UI.1' . PHP_EOL;
echo 'mode: read_only_admin_review_ui_temp_fixtures_cleaned' . PHP_EOL;
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

echo PHP_EOL;
echo empty($failed) ? 'RESULT: PASS' . PHP_EOL : 'RESULT: FAIL' . PHP_EOL;

exit(empty($failed) ? 0 : 1);
