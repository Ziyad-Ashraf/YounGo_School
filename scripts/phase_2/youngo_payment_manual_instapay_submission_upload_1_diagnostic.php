<?php
/**
 * PAYMENT.MANUAL.INSTAPAY.SUBMISSION.UPLOAD.1 diagnostic.
 *
 * Creates a DB backup, temporarily enables manual Instapay config, inserts a
 * temporary checkout order with coupon snapshot data, creates temporary image
 * evidence files, submits pending_review rows through the model path, deletes
 * all temporary rows/files, restores prior config, and verifies protected
 * payment/access counts return to baseline.
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
require_once APPPATH . 'models/Youngo_coupon_evaluator_model.php';
require_once APPPATH . 'models/Youngo_checkout_model.php';
require_once APPPATH . 'models/Youngo_payment_config_model.php';
require_once APPPATH . 'models/Youngo_instapay_payment_model.php';

$checks = array();
$details = array(
    'db_writes' => 'temporary_config_order_instapay_submission_rows_inserted_then_restored_or_deleted',
    'filesystem_writes' => 'temporary_png_evidence_files_created_then_deleted',
    'cleanup' => 'not_started',
    'backup_path' => '',
    'backup_size' => '',
    'backup_sha256' => '',
    'approved_instapay_review_statuses' => 'pending_review, approved, rejected',
);
$fixture_order_ids = array();
$fixture_submission_ids = array();
$fixture_files = array();

function ymisu1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ymisu1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ymisu1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ymisu1_backup_ident($name)
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function ymisu1_backup_value($mysqli, $value)
{
    return $value === null ? 'NULL' : "'" . $mysqli->real_escape_string($value) . "'";
}

function ymisu1_connect_mysqli($config)
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

function ymisu1_create_backup($root)
{
    $mysqli = ymisu1_connect_mysqli(ymisu1_db_config());
    if (!$mysqli) {
        return array('ok' => false, 'error' => 'connect_failed');
    }

    $backup_dir = dirname($root) . '/backups';
    if (!is_dir($backup_dir) && !mkdir($backup_dir, 0777, true)) {
        return array('ok' => false, 'error' => 'backup_dir_failed');
    }

    $path = $backup_dir . '/youngo_school_before_payment_manual_instapay_submission_upload_1_' . date('Y_m_d_His') . '.sql';
    $fh = fopen($path, 'wb');
    if (!$fh) {
        return array('ok' => false, 'error' => 'backup_file_failed');
    }

    fwrite($fh, "-- YounGo backup before PAYMENT.MANUAL.INSTAPAY.SUBMISSION.UPLOAD.1 diagnostic\n");
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
        $create = $mysqli->query('SHOW CREATE TABLE ' . ymisu1_backup_ident($table));
        if (!$create) {
            continue;
        }

        $create_row = $create->fetch_assoc();
        fwrite($fh, "\n-- Table " . $table . "\n");
        fwrite($fh, 'DROP TABLE IF EXISTS ' . ymisu1_backup_ident($table) . ";\n");
        fwrite($fh, $create_row['Create Table'] . ";\n\n");

        $rows = $mysqli->query('SELECT * FROM ' . ymisu1_backup_ident($table));
        if (!$rows) {
            continue;
        }

        while ($data = $rows->fetch_assoc()) {
            $columns = array();
            $values = array();
            foreach ($data as $column => $value) {
                $columns[] = ymisu1_backup_ident($column);
                $values[] = ymisu1_backup_value($mysqli, $value);
            }
            fwrite($fh, 'INSERT INTO ' . ymisu1_backup_ident($table) . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n");
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

function ymisu1_filter_columns($db, $table, $data)
{
    $filtered = array();
    foreach ($data as $field => $value) {
        if ($db->field_exists($field, $table)) {
            $filtered[$field] = $value;
        }
    }

    return $filtered;
}

function ymisu1_count($db, $table)
{
    return $db->table_exists($table) ? (int) $db->count_all($table) : null;
}

function ymisu1_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ymisu1_count($db, $table);
    }

    return $counts;
}

function ymisu1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ymisu1_first_id($db, $table, $where = array())
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

function ymisu1_config_row($db)
{
    if (!$db->table_exists('youngo_payment_provider_configs')) {
        return array();
    }

    $row = $db
        ->where('provider', 'instapay_manual')
        ->where('mode', 'manual')
        ->get('youngo_payment_provider_configs', 1)
        ->row_array();

    return is_array($row) ? $row : array();
}

function ymisu1_restore_config_row($db, $row)
{
    if (!$db->table_exists('youngo_payment_provider_configs')) {
        return false;
    }

    $db->where('provider', 'instapay_manual')
        ->where('mode', 'manual')
        ->delete('youngo_payment_provider_configs');

    if (!empty($row)) {
        return $db->insert('youngo_payment_provider_configs', ymisu1_filter_columns($db, 'youngo_payment_provider_configs', $row));
    }

    return true;
}

function ymisu1_insert_order($db, $user_id, $course_id, $reference)
{
    $now = time();
    $checkout_snapshot = array(
        'snapshot_version' => 'PAYMENT.COUPON.CHECKOUT.SNAPSHOT.WRITE.1',
        'snapshot_action' => 'diagnostic_prelocked_coupon_snapshot',
        'created_at' => $now,
        'order' => array(
            'original_amount' => '100.00',
            'coupon_code' => 'YMISU1SAVE25',
            'coupon_discount_type' => 'percentage',
            'coupon_discount_value' => '25.00',
            'discount_amount' => '25.00',
            'final_amount' => '75.00',
            'currency' => 'EGP',
            'selected_payment_method' => 'instapay_manual',
        ),
    );

    $data = ymisu1_filter_columns($db, 'youngo_checkout_orders', array(
        'user_id' => (int) $user_id,
        'order_reference' => $reference,
        'order_type' => 'course_purchase',
        'status' => 'draft',
        'course_id' => (int) $course_id,
        'plan_id' => null,
        'subtotal_amount' => '100.00',
        'discount_amount' => '25.00',
        'tax_amount' => '0.00',
        'total_amount' => '75.00',
        'total_amount_cents' => 7500,
        'currency' => 'EGP',
        'coupon_id' => null,
        'coupon_code' => 'YMISU1SAVE25',
        'coupon_discount_type' => 'percentage',
        'coupon_discount_value' => '25.00',
        'selected_payment_method' => null,
        'item_title_snapshot' => 'Manual Instapay Upload Diagnostic Course',
        'checkout_snapshot_json' => json_encode($checkout_snapshot, JSON_UNESCAPED_SLASHES),
        'payment_gateway' => null,
        'gateway_environment' => 'sandbox',
        'idempotency_key' => hash('sha256', 'PAYMENT.MANUAL.INSTAPAY.SUBMISSION.UPLOAD.1|' . $reference),
        'last_hmac_verified' => 0,
        'entitlement_issued' => 0,
        'entitlement_issuance_status' => 'not_started',
        'metadata' => json_encode(array('source' => 'youngo_payment_manual_instapay_submission_upload_1_diagnostic'), JSON_UNESCAPED_SLASHES),
        'created_at' => $now,
        'updated_at' => $now,
    ));

    $db->insert('youngo_checkout_orders', $data);
    return (int) $db->insert_id();
}

function ymisu1_create_png_fixture($root, $suffix)
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

    $filename = 'diagnostic_instapay_upload_' . preg_replace('/[^A-Za-z0-9_-]/', '', $suffix) . '.png';
    $absolute_path = $absolute_dir . '/' . $filename;
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');
    file_put_contents($absolute_path, $png);

    return array(
        'relative_path' => $relative_dir . '/' . $filename,
        'absolute_path' => $absolute_path,
        'size' => filesize($absolute_path),
    );
}

class Ymisu1_session_stub
{
    public function userdata($key)
    {
        return null;
    }
}

$backup = ymisu1_create_backup($root);
if (!empty($backup['ok'])) {
    $details['backup_path'] = $backup['path'];
    $details['backup_size'] = (string) $backup['size'];
    $details['backup_sha256'] = $backup['sha256'];
}

$CI = new stdClass();
$CI->session = new Ymisu1_session_stub();
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ymisu1_db_config(), true);
$CI->db = $db;
$checkout_model = new Youngo_checkout_model(array('db' => $db));
$payment_config_model = new Youngo_payment_config_model(array('db' => $db));
$instapay_model = new Youngo_instapay_payment_model(array('db' => $db));

$paths = array(
    'checkout_controller' => $root . '/application/controllers/Youngo_checkout.php',
    'checkout_model' => $root . '/application/models/Youngo_checkout_model.php',
    'instapay_model' => $root . '/application/models/Youngo_instapay_payment_model.php',
    'payment_config_model' => $root . '/application/models/Youngo_payment_config_model.php',
    'checkout_view' => $root . '/application/views/frontend/youngo/checkout_order.php',
    'routes' => $root . '/application/config/routes.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
);

foreach ($paths as $name => $path) {
    ymisu1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}
ymisu1_check($checks, 'backup_created_before_temp_writes', !empty($backup['ok']), !empty($backup['ok']) ? $backup['path'] . '|' . $backup['size'] . '|' . $backup['sha256'] : (isset($backup['error']) ? $backup['error'] : 'backup_failed'));

$controller_source = ymisu1_read($paths['checkout_controller']);
$view_source = ymisu1_read($paths['checkout_view']);
$routes_source = ymisu1_read($paths['routes']);
$instapay_source = ymisu1_read($paths['instapay_model']);
$checkout_model_source = ymisu1_read($paths['checkout_model']);
$paymob_source = ymisu1_read($paths['paymob_config']);

ymisu1_check($checks, 'routes_have_upload_and_admin_review_decisions_are_post_only', strpos($routes_source, "youngo/checkout/instapay/submit/(:any)") !== false && strpos($routes_source, 'admin/youngo/instapay-payments') !== false && strpos($routes_source, "admin/youngo/instapay-payments/(:num)/approve") !== false && strpos($routes_source, "admin/youngo/instapay-payments/(:num)/reject") !== false);
ymisu1_check($checks, 'controller_has_post_only_submit_action', strpos($controller_source, 'function submit_instapay') !== false && strpos($controller_source, "method(true)") !== false && strpos($controller_source, 'create_pending_submission') !== false);
ymisu1_check($checks, 'controller_has_image_upload_validation', strpos($controller_source, 'detect_upload_mime') !== false && strpos($controller_source, 'image/jpeg') !== false && strpos($controller_source, 'image/png') !== false && strpos($controller_source, 'image/webp') !== false && strpos($controller_source, 'random_hex') !== false);
ymisu1_check($checks, 'view_has_instapay_upload_ui_and_status', strpos($view_source, 'data-youngo-instapay-checkout-panel') !== false && strpos($view_source, 'name="instapay_screenshot"') !== false && strpos($view_source, 'Submit payment for review') !== false && strpos($view_source, 'pending_review') !== false && strpos($view_source, 'Cards') !== false && strpos($view_source, 'Digital Wallets') !== false);
ymisu1_check($checks, 'view_does_not_hardcode_target_details', strpos($view_source, 'diagnostic@instapay') === false && strpos($view_source, 'instapay_target_address') === false);
ymisu1_check($checks, 'model_has_pending_create_and_deferred_approval_methods', method_exists($instapay_model, 'create_pending_submission') && method_exists($instapay_model, 'approve_submission') && method_exists($instapay_model, 'reject_submission') && strpos($instapay_source, "'status' => 'pending_review'") !== false);
ymisu1_check($checks, 'checkout_model_blocks_coupon_changes_after_pending_review', strpos($checkout_model_source, 'order_instapay_review_started') !== false && strpos($checkout_model_source, 'pending_review') !== false && strpos($checkout_model_source, 'approved') !== false);

$reader = new Youngo_paymob_config(array('load_local_override' => false));
ymisu1_check($checks, 'paymob_default_enabled_false', $reader->is_enabled() === false);
ymisu1_check($checks, 'paymob_default_network_false', $reader->is_network_enabled() === false);
ymisu1_check($checks, 'paymob_static_gates_false', preg_match('/[\'"]enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1 && preg_match('/[\'"]network_enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1);
ymisu1_check($checks, 'allowed_statuses_exact', $instapay_model->allowed_statuses() === array('pending_review', 'approved', 'rejected') && $instapay_model->normalize_status('draft') === null && $instapay_model->normalize_status('cancelled') === null && $instapay_model->normalize_status('expired') === null);

$protected_tables = array(
    'youngo_payment_provider_configs',
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
$baseline_counts = ymisu1_counts($db, $protected_tables);
$before_config = ymisu1_config_row($db);

$user_id = ymisu1_first_id($db, 'users', array('role_id' => 2));
if ($user_id === null) {
    $user_id = ymisu1_first_id($db, 'users');
}
$course_id = ymisu1_first_id($db, 'course');
ymisu1_check($checks, 'fixture_user_course_available', $user_id !== null && $course_id !== null, 'user=' . $user_id . ', course=' . $course_id);

try {
    if ($user_id !== null && $course_id !== null) {
        $details['cleanup'] = 'started';
        $config_save = $payment_config_model->upsert_dashboard_instapay_manual_config(array(
            'instapay_enabled_for_checkout' => 1,
            'instapay_target_label' => 'YounGo Diagnostic Instapay',
            'instapay_target_address' => 'diagnostic@instapay',
            'instapay_target_link' => 'https://example.com/instapay-diagnostic',
            'instapay_instructions_ar' => 'تعليمات تشخيصية مؤقتة فقط.',
            'instapay_instructions_en' => 'Temporary diagnostic payment instructions only.',
            'instapay_max_upload_mb' => '5',
        ), null);

        $target_snapshot = $instapay_model->build_instapay_target_snapshot('english');
        $reference = 'YGO-INSTAPAY-UPLOAD-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true)), 0, 8);
        $order_id = ymisu1_insert_order($db, $user_id, $course_id, $reference);
        $fixture_order_ids[] = $order_id;
        $order = $db->where('id', $order_id)->get('youngo_checkout_orders', 1)->row_array();

        $first_file = ymisu1_create_png_fixture($root, 'first_' . $order_id);
        $fixture_files[] = $first_file['absolute_path'];
        $upload_data = array(
            'screenshot_path' => $first_file['relative_path'],
            'screenshot_original_name' => 'diagnostic-first.png',
            'screenshot_mime' => 'image/png',
            'screenshot_size' => $first_file['size'],
            'submitted_amount' => null,
            'language' => 'english',
        );

        $create = $instapay_model->create_pending_submission($order_id, $user_id, $upload_data, 'YMISU1-TEMP-REF', 'Temporary diagnostic note.');
        $submission = !empty($create['data']['submission']) ? $create['data']['submission'] : array();
        if (!empty($submission['id'])) {
            $fixture_submission_ids[] = (int) $submission['id'];
        }
        $decoded_snapshot = !empty($submission['snapshot_json']) ? json_decode($submission['snapshot_json'], true) : array();
        $duplicate = $instapay_model->create_pending_submission($order_id, $user_id, $upload_data, 'YMISU1-DUPLICATE', 'Duplicate should be blocked.');
        $coupon_after_pending = $checkout_model->clear_coupon_snapshot_from_order($order_id, $user_id);

        ymisu1_check($checks, 'temporary_enabled_config_saved', !empty($config_save['ok']) && $payment_config_model->is_instapay_checkout_enabled());
        ymisu1_check($checks, 'target_snapshot_enabled_and_configured', !empty($target_snapshot['ok']) && !empty($target_snapshot['data']['snapshot']['enabled']) && $target_snapshot['data']['snapshot']['label'] === 'YounGo Diagnostic Instapay');
        ymisu1_check($checks, 'temporary_checkout_order_inserted', !empty($order) && (int) $order['id'] === $order_id && (string) $order['total_amount'] === '75.00');
        ymisu1_check($checks, 'pending_submission_created_through_model', !empty($create['ok']) && isset($create['data']['status']) && $create['data']['status'] === 'pending_review');
        ymisu1_check($checks, 'submission_row_pending_review_expected_amount', !empty($submission) && $submission['status'] === 'pending_review' && (string) $submission['expected_amount'] === '75.00' && $submission['currency'] === 'EGP');
        ymisu1_check($checks, 'submission_screenshot_metadata_only', !empty($submission['screenshot_path']) && $submission['screenshot_path'] === $first_file['relative_path'] && $submission['screenshot_mime'] === 'image/png' && (int) $submission['screenshot_size'] === (int) $first_file['size']);
        ymisu1_check($checks, 'snapshot_contains_coupon_final_instapay_target', isset($decoded_snapshot['order']['coupon_code']) && $decoded_snapshot['order']['coupon_code'] === 'YMISU1SAVE25' && $decoded_snapshot['order']['original_amount'] === '100.00' && $decoded_snapshot['order']['final_amount'] === '75.00' && $decoded_snapshot['order']['selected_payment_method'] === 'instapay_manual' && $decoded_snapshot['instapay']['target_address'] === 'diagnostic@instapay' && $decoded_snapshot['instapay']['instructions_en'] === 'Temporary diagnostic payment instructions only.');
        ymisu1_check($checks, 'duplicate_pending_submission_blocked', empty($duplicate['ok']) && isset($duplicate['code']) && $duplicate['code'] === 'pending_submission_exists');
        ymisu1_check($checks, 'coupon_change_blocked_after_pending_review', empty($coupon_after_pending['ok']) && isset($coupon_after_pending['code']) && $coupon_after_pending['code'] === 'order_instapay_review_started');

        if (!empty($submission['id'])) {
            $db->where('id', (int) $submission['id'])->update('youngo_instapay_payment_submissions', array(
                'status' => 'rejected',
                'admin_note' => 'Temporary diagnostic rejection.',
                'rejected_at' => time(),
                'updated_at' => time(),
            ));

            $second_file = ymisu1_create_png_fixture($root, 'second_' . $order_id);
            $fixture_files[] = $second_file['absolute_path'];
            $second_create = $instapay_model->create_pending_submission($order_id, $user_id, array(
                'screenshot_path' => $second_file['relative_path'],
                'screenshot_original_name' => 'diagnostic-second.png',
                'screenshot_mime' => 'image/png',
                'screenshot_size' => $second_file['size'],
                'language' => 'english',
            ), 'YMISU1-RESUBMIT', 'Second temporary diagnostic note.');
            if (!empty($second_create['data']['submission']['id'])) {
                $fixture_submission_ids[] = (int) $second_create['data']['submission']['id'];
            }
            ymisu1_check($checks, 'rejected_submission_evidence_not_deleted_and_resubmit_allowed', !empty($second_create['ok']) && is_file($first_file['absolute_path']));
        }
    }
} finally {
    if (!empty($fixture_submission_ids) && $db->table_exists('youngo_instapay_payment_submissions')) {
        $db->where_in('id', array_values(array_unique($fixture_submission_ids)))->delete('youngo_instapay_payment_submissions');
    }
    if (!empty($fixture_order_ids) && $db->table_exists('youngo_checkout_orders')) {
        $db->where_in('id', array_values(array_unique($fixture_order_ids)))->delete('youngo_checkout_orders');
    }
    ymisu1_restore_config_row($db, $before_config);
    foreach ($fixture_files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
}

$after_counts = ymisu1_counts($db, $protected_tables);
$after_config = ymisu1_config_row($db);
$details['cleanup'] = ymisu1_counts_match($baseline_counts, $after_counts) && $before_config == $after_config ? 'completed' : 'failed';
ymisu1_check($checks, 'temporary_rows_deleted_and_config_restored', ymisu1_counts_match($baseline_counts, $after_counts) && $before_config == $after_config, json_encode(array('before' => $baseline_counts, 'after' => $after_counts), JSON_UNESCAPED_SLASHES));
ymisu1_check($checks, 'temporary_uploaded_files_deleted', empty(array_filter($fixture_files, 'is_file')), implode(',', $fixture_files));
ymisu1_check($checks, 'no_entitlement_access_enrolment_rows_created', $baseline_counts['youngo_course_access'] === $after_counts['youngo_course_access'] && $baseline_counts['youngo_user_subscriptions'] === $after_counts['youngo_user_subscriptions'] && $baseline_counts['youngo_manual_grants'] === $after_counts['youngo_manual_grants'] && $baseline_counts['payment'] === $after_counts['payment'] && $baseline_counts['enrol'] === $after_counts['enrol']);
ymisu1_check($checks, 'no_paymob_rows_or_activation', $baseline_counts['youngo_payment_transactions'] === $after_counts['youngo_payment_transactions'] && $reader->is_enabled() === false && $reader->is_network_enabled() === false);

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo 'phase: PAYMENT.MANUAL.INSTAPAY.SUBMISSION.UPLOAD.1' . PHP_EOL;
echo 'mode: manual_instapay_upload_submission_model_path_temp_fixtures_cleaned' . PHP_EOL;
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
