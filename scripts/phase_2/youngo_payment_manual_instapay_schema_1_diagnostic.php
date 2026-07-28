<?php
/**
 * PAYMENT.MANUAL.INSTAPAY.SCHEMA.1 diagnostic.
 *
 * Verifies the additive manual Instapay submission table and inert model
 * foundation. This script inserts one temporary checkout order and one
 * temporary Instapay submission row, reads both, deletes both, and verifies
 * protected payment/access counts return to their baseline.
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
require_once APPPATH . 'models/Youngo_checkout_model.php';
require_once APPPATH . 'models/Youngo_instapay_payment_model.php';

$checks = array();
$details = array(
    'db_writes' => 'one_temporary_checkout_order_and_one_temporary_instapay_submission_inserted_then_deleted',
    'cleanup' => 'not_started',
    'approved_instapay_review_statuses' => 'pending_review, approved, rejected',
);
$fixture_order_ids = array();
$fixture_submission_ids = array();

function ymis1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ymis1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ymis1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ymis1_count($db, $table)
{
    return $db->table_exists($table) ? (int) $db->count_all($table) : null;
}

function ymis1_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ymis1_count($db, $table);
    }

    return $counts;
}

function ymis1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ymis1_filter_columns($db, $table, $data)
{
    $filtered = array();
    foreach ($data as $field => $value) {
        if ($db->field_exists($field, $table)) {
            $filtered[$field] = $value;
        }
    }

    return $filtered;
}

function ymis1_first_id($db, $table, $where = array())
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

function ymis1_column_type($db, $table, $column)
{
    if (!$db->table_exists($table)) {
        return '';
    }

    $query = $db->query(
        'SELECT DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        array($table, $column)
    );
    if (!$query || $query->num_rows() === 0) {
        return '';
    }

    $row = $query->row_array();
    return strtolower($row['DATA_TYPE'] . '|' . $row['COLUMN_TYPE'] . '|nullable_' . $row['IS_NULLABLE'] . '|default_' . $row['COLUMN_DEFAULT'] . '|comment_' . $row['COLUMN_COMMENT']);
}

function ymis1_index_names($db, $table)
{
    if (!$db->table_exists($table)) {
        return array();
    }

    $query = $db->query(
        'SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        array($table)
    );
    if (!$query) {
        return array();
    }

    $names = array();
    foreach ($query->result_array() as $row) {
        $names[] = (string) $row['INDEX_NAME'];
    }

    return $names;
}

function ymis1_insert_order($db, $user_id, $course_id, $reference)
{
    $now = time();
    $checkout_snapshot = array(
        'snapshot_version' => 'PAYMENT.COUPON.CHECKOUT.SNAPSHOT.WRITE.1',
        'snapshot_action' => 'diagnostic_prelocked_coupon_snapshot',
        'created_at' => $now,
        'order' => array(
            'original_amount' => '100.00',
            'coupon_code' => 'YMIS1SAVE30',
            'coupon_discount_type' => 'percentage',
            'coupon_discount_value' => '30.00',
            'discount_amount' => '30.00',
            'final_amount' => '70.00',
            'currency' => 'EGP',
            'selected_payment_method' => 'instapay_manual',
        ),
    );

    $data = ymis1_filter_columns($db, 'youngo_checkout_orders', array(
        'user_id' => (int) $user_id,
        'order_reference' => $reference,
        'order_type' => 'course_purchase',
        'status' => 'draft',
        'course_id' => (int) $course_id,
        'plan_id' => null,
        'subtotal_amount' => '100.00',
        'discount_amount' => '30.00',
        'tax_amount' => '0.00',
        'total_amount' => '70.00',
        'total_amount_cents' => 7000,
        'currency' => 'EGP',
        'coupon_id' => null,
        'coupon_code' => 'YMIS1SAVE30',
        'coupon_discount_type' => 'percentage',
        'coupon_discount_value' => '30.00',
        'selected_payment_method' => 'instapay_manual',
        'item_title_snapshot' => 'Manual Instapay Diagnostic Course',
        'checkout_snapshot_json' => json_encode($checkout_snapshot, JSON_UNESCAPED_SLASHES),
        'payment_gateway' => null,
        'gateway_environment' => 'sandbox',
        'idempotency_key' => hash('sha256', 'PAYMENT.MANUAL.INSTAPAY.SCHEMA.1|' . $reference),
        'last_hmac_verified' => 0,
        'entitlement_issued' => 0,
        'entitlement_issuance_status' => 'not_started',
        'metadata' => json_encode(array('source' => 'youngo_payment_manual_instapay_schema_1_diagnostic'), JSON_UNESCAPED_SLASHES),
        'created_at' => $now,
        'updated_at' => $now,
    ));

    $db->insert('youngo_checkout_orders', $data);
    return (int) $db->insert_id();
}

function ymis1_insert_submission($db, $order_id, $user_id, $snapshot)
{
    $now = time();
    $data = ymis1_filter_columns($db, 'youngo_instapay_payment_submissions', array(
        'order_id' => (int) $order_id,
        'user_id' => (int) $user_id,
        'status' => 'pending_review',
        'expected_amount' => '70.00',
        'submitted_amount' => '70.00',
        'currency' => 'EGP',
        'instapay_target_label' => 'YounGo Instapay Diagnostic',
        'instapay_target_address' => 'diagnostic@instapay',
        'instapay_target_link' => null,
        'screenshot_path' => null,
        'screenshot_original_name' => null,
        'screenshot_mime' => null,
        'screenshot_size' => null,
        'transaction_reference' => 'YMIS1-TEMP-REF',
        'user_note' => 'Temporary diagnostic submission.',
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
    ));

    $db->insert('youngo_instapay_payment_submissions', $data);
    return (int) $db->insert_id();
}

class Ymis1_session_stub
{
    public function userdata($key)
    {
        return null;
    }
}

$CI = new stdClass();
$CI->session = new Ymis1_session_stub();
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ymis1_db_config(), true);
$CI->db = $db;
$checkout_model = new Youngo_checkout_model(array('db' => $db));
$instapay_model = new Youngo_instapay_payment_model(array('db' => $db));

$paths = array(
    'checkout_model' => $root . '/application/models/Youngo_checkout_model.php',
    'coupon_evaluator_model' => $root . '/application/models/Youngo_coupon_evaluator_model.php',
    'entitlement_write_model' => $root . '/application/models/Youngo_entitlement_write_model.php',
    'instapay_model' => $root . '/application/models/Youngo_instapay_payment_model.php',
    'checkout_controller' => $root . '/application/controllers/Youngo_checkout.php',
    'checkout_view' => $root . '/application/views/frontend/youngo/checkout_order.php',
    'routes' => $root . '/application/config/routes.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
    'up_sql' => $root . '/scripts/phase_2/payment_manual_instapay_schema_1_up.sql',
    'down_sql' => $root . '/scripts/phase_2/payment_manual_instapay_schema_1_down.sql',
);

foreach ($paths as $name => $path) {
    ymis1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

$instapay_source = ymis1_read($paths['instapay_model']);
$routes_source = ymis1_read($paths['routes']);
$checkout_controller_source = ymis1_read($paths['checkout_controller']);
$checkout_view_source = ymis1_read($paths['checkout_view']);
$paymob_source = ymis1_read($paths['paymob_config']);
$up_sql = ymis1_read($paths['up_sql']);
$down_sql = ymis1_read($paths['down_sql']);

ymis1_check($checks, 'instapay_model_loads', $instapay_model instanceof Youngo_instapay_payment_model);
foreach (array('table_exists', 'allowed_statuses', 'normalize_status', 'build_submission_snapshot', 'get_submission_by_id', 'get_submissions_for_order', 'can_create_submission_for_order', 'sanitize_expected_amount') as $method) {
    ymis1_check($checks, 'method_exists_' . $method, method_exists($instapay_model, $method));
}
ymis1_check($checks, 'model_exposes_pending_create_but_no_approval_access_methods', method_exists($instapay_model, 'create_pending_submission') && !method_exists($instapay_model, 'create_submission') && !method_exists($instapay_model, 'approve_submission') && !method_exists($instapay_model, 'reject_submission') && stripos($instapay_source, 'issue_course_purchase_access') === false && stripos($instapay_source, 'issue_subscription_purchase') === false);

$allowed_statuses = $instapay_model->allowed_statuses();
ymis1_check($checks, 'allowed_statuses_exact', $allowed_statuses === array('pending_review', 'approved', 'rejected'), implode(', ', $allowed_statuses));
ymis1_check($checks, 'normalize_valid_statuses', $instapay_model->normalize_status(' pending review ') === 'pending_review' && $instapay_model->normalize_status('Approved') === 'approved' && $instapay_model->normalize_status('rejected') === 'rejected');
ymis1_check($checks, 'normalize_rejects_non_review_statuses', $instapay_model->normalize_status('draft') === null && $instapay_model->normalize_status('cancelled') === null && $instapay_model->normalize_status('expired') === null);

$reader = new Youngo_paymob_config(array('load_local_override' => false));
ymis1_check($checks, 'paymob_default_enabled_false', $reader->is_enabled() === false);
ymis1_check($checks, 'paymob_default_network_false', $reader->is_network_enabled() === false);
ymis1_check($checks, 'paymob_default_checkout_cta_false', $reader->is_checkout_cta_enabled() === false);
ymis1_check($checks, 'paymob_static_gates_false', preg_match('/[\'"]enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1 && preg_match('/[\'"]network_enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1 && preg_match('/[\'"]checkout_cta_enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1);
ymis1_check($checks, 'routes_have_upload_and_read_only_admin_review_without_decisions', strpos($routes_source, "youngo/checkout/instapay/submit/(:any)") !== false && strpos($routes_source, 'admin/youngo/instapay-payments') !== false && stripos($routes_source, 'approve') === false && stripos($routes_source, 'reject') === false);
ymis1_check($checks, 'checkout_controller_has_upload_but_no_approval', strpos($checkout_controller_source, 'submit_instapay') !== false && stripos($checkout_controller_source, 'approve_instapay') === false && stripos($checkout_controller_source, 'reject_instapay') === false);
ymis1_check($checks, 'checkout_view_placeholders_remain_disabled', stripos($checkout_view_source, 'Instapay') !== false && stripos($checkout_view_source, 'Coming soon') !== false && stripos($checkout_view_source, 'Cards') !== false && stripos($checkout_view_source, 'Digital Wallets') !== false && stripos($checkout_view_source, 'Not available yet') !== false);

ymis1_check($checks, 'up_sql_creates_only_instapay_submission_table', preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`youngo_instapay_payment_submissions`/i', $up_sql) === 1 && preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`youngo_(?!instapay_payment_submissions\b)/i', preg_replace('/--.*$/m', '', $up_sql)) !== 1);
ymis1_check($checks, 'down_sql_drops_only_instapay_submission_table', preg_match('/DROP\s+TABLE\s+IF\s+EXISTS\s+`youngo_instapay_payment_submissions`/i', $down_sql) === 1 && preg_match('/DROP\s+TABLE\s+IF\s+EXISTS\s+`youngo_(?!instapay_payment_submissions\b)/i', preg_replace('/--.*$/m', '', $down_sql)) !== 1);

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
$baseline_counts = ymis1_counts($db, $protected_tables);

$instapay_table_exists = $db->table_exists('youngo_instapay_payment_submissions') && $instapay_model->table_exists();
ymis1_check($checks, 'instapay_submission_table_exists', $instapay_table_exists);
foreach (array(
    'id' => 'bigint',
    'order_id' => 'bigint',
    'user_id' => 'int',
    'status' => 'varchar',
    'expected_amount' => 'decimal',
    'submitted_amount' => 'decimal',
    'currency' => 'varchar',
    'instapay_target_label' => 'varchar',
    'instapay_target_address' => 'varchar',
    'instapay_target_link' => 'varchar',
    'screenshot_path' => 'varchar',
    'screenshot_original_name' => 'varchar',
    'screenshot_mime' => 'varchar',
    'screenshot_size' => 'int',
    'transaction_reference' => 'varchar',
    'user_note' => 'text',
    'admin_note' => 'text',
    'reviewed_by_user_id' => 'int',
    'reviewed_at' => 'int',
    'approved_at' => 'int',
    'rejected_at' => 'int',
    'access_issued' => 'tinyint',
    'access_issued_at' => 'int',
    'access_reference_type' => 'varchar',
    'access_reference_id' => 'bigint',
    'snapshot_json' => 'longtext',
    'created_at' => 'int',
    'updated_at' => 'int',
) as $column => $expected_type) {
    $type = $instapay_table_exists ? ymis1_column_type($db, 'youngo_instapay_payment_submissions', $column) : '';
    $field_exists = $instapay_table_exists && $db->field_exists($column, 'youngo_instapay_payment_submissions');
    ymis1_check($checks, 'instapay_field_exists_' . $column, $field_exists, $type);
    ymis1_check($checks, 'instapay_field_type_' . $column, $field_exists && strpos($type, $expected_type) !== false, $type);
}

$status_type = $instapay_table_exists ? ymis1_column_type($db, 'youngo_instapay_payment_submissions', 'status') : '';
ymis1_check($checks, 'status_default_pending_review', strpos($status_type, 'default_pending_review') !== false || strpos($status_type, "default_'pending_review'") !== false, $status_type);
ymis1_check($checks, 'status_comment_documents_allowed_values', strpos($status_type, 'pending_review') !== false && strpos($status_type, 'approved') !== false && strpos($status_type, 'rejected') !== false, $status_type);
$index_names = $instapay_table_exists ? ymis1_index_names($db, 'youngo_instapay_payment_submissions') : array();
foreach (array('idx_yips_order_id', 'idx_yips_user_id', 'idx_yips_status', 'idx_yips_reviewed_by', 'idx_yips_created_at') as $index) {
    ymis1_check($checks, 'index_exists_' . $index, in_array($index, $index_names, true), implode(', ', $index_names));
}

ymis1_check($checks, 'checkout_order_base_fields_still_exist', $db->field_exists('subtotal_amount', 'youngo_checkout_orders') && $db->field_exists('total_amount', 'youngo_checkout_orders') && $db->field_exists('currency', 'youngo_checkout_orders') && $db->field_exists('checkout_snapshot_json', 'youngo_checkout_orders'));

$user_id = ymis1_first_id($db, 'users', array('role_id' => 2));
if ($user_id === null) {
    $user_id = ymis1_first_id($db, 'users');
}
$course_id = ymis1_first_id($db, 'course');
ymis1_check($checks, 'fixture_user_course_available', $user_id !== null && $course_id !== null, 'user=' . $user_id . ', course=' . $course_id);

if ($user_id !== null && $course_id !== null && $db->table_exists('youngo_instapay_payment_submissions')) {
    $details['cleanup'] = 'started';
    $reference = 'YGO-INSTAPAY-SCHEMA-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true)), 0, 8);
    $order_id = ymis1_insert_order($db, $user_id, $course_id, $reference);
    $fixture_order_ids[] = $order_id;
    $order_row = $db->where('id', $order_id)->get('youngo_checkout_orders', 1)->row_array();

    $review = $checkout_model->get_safe_order_review_snapshot($order_id, $user_id);
    $review_snapshot = !empty($review['ok']) && !empty($review['data']['review_snapshot']) ? $review['data']['review_snapshot'] : array();
    $can_create_before = $instapay_model->can_create_submission_for_order($review_snapshot);
    $submission_snapshot = $instapay_model->build_submission_snapshot($review_snapshot, array(
        'submitted_amount' => '70.00',
        'instapay_target_label' => 'YounGo Instapay Diagnostic',
        'instapay_target_address' => 'diagnostic@instapay',
        'transaction_reference' => 'YMIS1-TEMP-REF',
        'user_note' => 'Temporary diagnostic submission.',
    ));
    $submission_id = ymis1_insert_submission($db, $order_id, $user_id, $submission_snapshot);
    $fixture_submission_ids[] = $submission_id;

    $submission = $instapay_model->get_submission_by_id($submission_id);
    $submissions_for_order = $instapay_model->get_submissions_for_order($order_id);
    $can_create_after = $instapay_model->can_create_submission_for_order($review_snapshot);
    $decoded_submission_snapshot = !empty($submission['snapshot_json']) ? json_decode($submission['snapshot_json'], true) : array();

    ymis1_check($checks, 'temporary_checkout_order_inserted', !empty($order_row) && (int) $order_row['id'] === $order_id);
    ymis1_check($checks, 'review_snapshot_uses_checkout_final_amount', !empty($review['ok']) && isset($review_snapshot['original_amount']) && $review_snapshot['original_amount'] === '100.00' && isset($review_snapshot['final_amount']) && $review_snapshot['final_amount'] === '70.00' && isset($review_snapshot['coupon_code']) && $review_snapshot['coupon_code'] === 'YMIS1SAVE30');
    ymis1_check($checks, 'can_create_before_pending_submission', !empty($can_create_before['ok']) && isset($can_create_before['data']['expected_amount']) && $can_create_before['data']['expected_amount'] === '70.00');
    ymis1_check($checks, 'submission_snapshot_contains_checkout_coupon_amounts', isset($submission_snapshot['order']['original_amount']) && $submission_snapshot['order']['original_amount'] === '100.00' && $submission_snapshot['order']['final_amount'] === '70.00' && $submission_snapshot['order']['coupon_discount_type'] === 'percentage' && $submission_snapshot['instapay']['expected_amount'] === '70.00');
    ymis1_check($checks, 'temporary_instapay_submission_inserted_and_read', !empty($submission) && (int) $submission['id'] === $submission_id && (int) $submission['order_id'] === $order_id && (int) $submission['user_id'] === (int) $user_id);
    ymis1_check($checks, 'submission_row_expected_amount_and_status', isset($submission['expected_amount']) && (string) $submission['expected_amount'] === '70.00' && isset($submission['status']) && $submission['status'] === 'pending_review' && isset($submission['currency']) && $submission['currency'] === 'EGP');
    ymis1_check($checks, 'get_submissions_for_order_reads_temp_submission', count($submissions_for_order) === 1 && (int) $submissions_for_order[0]['id'] === $submission_id);
    ymis1_check($checks, 'pending_submission_blocks_second_pending_review', empty($can_create_after['ok']) && isset($can_create_after['code']) && $can_create_after['code'] === 'pending_submission_exists');
    ymis1_check($checks, 'decoded_submission_snapshot_preserves_final_amount', isset($decoded_submission_snapshot['order']['final_amount']) && $decoded_submission_snapshot['order']['final_amount'] === '70.00' && isset($decoded_submission_snapshot['instapay']['transaction_reference']) && $decoded_submission_snapshot['instapay']['transaction_reference'] === 'YMIS1-TEMP-REF');
    ymis1_check($checks, 'submission_does_not_issue_access', isset($submission['access_issued']) && (int) $submission['access_issued'] === 0 && $submission['access_issued_at'] === null && $submission['access_reference_type'] === null && $submission['access_reference_id'] === null);
}

foreach ($fixture_submission_ids as $submission_id) {
    $db->where('id', (int) $submission_id)->delete('youngo_instapay_payment_submissions');
}
foreach ($fixture_order_ids as $order_id) {
    $db->where('id', (int) $order_id)->delete('youngo_checkout_orders');
}
$details['cleanup'] = 'completed';

$after_counts = ymis1_counts($db, $protected_tables);
ymis1_check($checks, 'temporary_instapay_rows_deleted', $db->table_exists('youngo_instapay_payment_submissions') && (int) $db->where_in('id', empty($fixture_submission_ids) ? array(0) : $fixture_submission_ids)->count_all_results('youngo_instapay_payment_submissions') === 0);
ymis1_check($checks, 'temporary_checkout_rows_deleted', $db->table_exists('youngo_checkout_orders') && (int) $db->where_in('id', empty($fixture_order_ids) ? array(0) : $fixture_order_ids)->count_all_results('youngo_checkout_orders') === 0);
ymis1_check($checks, 'protected_counts_restored', ymis1_counts_match($baseline_counts, $after_counts), 'before=' . json_encode($baseline_counts) . '; after=' . json_encode($after_counts));
ymis1_check($checks, 'no_entitlement_access_enrolment_rows_created', $baseline_counts['youngo_course_access'] === $after_counts['youngo_course_access'] && $baseline_counts['youngo_user_subscriptions'] === $after_counts['youngo_user_subscriptions'] && $baseline_counts['youngo_manual_grants'] === $after_counts['youngo_manual_grants'] && $baseline_counts['payment'] === $after_counts['payment'] && $baseline_counts['enrol'] === $after_counts['enrol']);

$failed_checks = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed_checks[$name] = $check;
    }
}

echo 'phase: PAYMENT.MANUAL.INSTAPAY.SCHEMA.1' . PHP_EOL;
echo 'mode: additive_schema_model_foundation_with_temp_row_cleanup' . PHP_EOL;
echo 'db_writes: ' . $details['db_writes'] . PHP_EOL;
echo 'cleanup: ' . $details['cleanup'] . PHP_EOL;
echo 'approved_instapay_review_statuses: ' . $details['approved_instapay_review_statuses'] . PHP_EOL;
echo PHP_EOL;

foreach ($checks as $name => $check) {
    echo $check['status'] . ' - ' . $name;
    if ($check['detail'] !== '') {
        echo ' [' . $check['detail'] . ']';
    }
    echo PHP_EOL;
}

echo PHP_EOL;
echo 'summary: ' . (count($failed_checks) === 0 ? 'PASS' : 'FAIL') . PHP_EOL;
echo 'checks_total: ' . count($checks) . PHP_EOL;
echo 'checks_failed: ' . count($failed_checks) . PHP_EOL;

exit(count($failed_checks) === 0 ? 0 : 1);
