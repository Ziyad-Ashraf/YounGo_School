<?php
/**
 * PAYMENT.MANUAL.INSTAPAY.CONFIG.1 diagnostic.
 *
 * Verifies manual Instapay target configuration storage and helpers.
 * This script temporarily writes only the instapay_manual/manual config row,
 * restores the prior row state, and verifies protected payment/access/order
 * counts remain unchanged.
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
require_once APPPATH . 'models/Youngo_payment_config_model.php';
require_once APPPATH . 'models/Youngo_payment_config_audit_model.php';
require_once APPPATH . 'models/Youngo_instapay_payment_model.php';

$checks = array();
$details = array(
    'db_writes' => 'temporary_instapay_manual_config_row_saved_then_restored',
    'cleanup' => 'not_started',
    'approved_instapay_review_statuses' => 'pending_review, approved, rejected',
);

function ymic1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ymic1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ymic1_count($db, $table)
{
    return $db->table_exists($table) ? (int) $db->count_all($table) : null;
}

function ymic1_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ymic1_count($db, $table);
    }

    return $counts;
}

function ymic1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ymic1_config_row($db)
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

function ymic1_restore_config_row($db, $row)
{
    if (!$db->table_exists('youngo_payment_provider_configs')) {
        return false;
    }

    $db->where('provider', 'instapay_manual')
        ->where('mode', 'manual')
        ->delete('youngo_payment_provider_configs');

    if (!empty($row)) {
        $filtered = array();
        foreach ($row as $field => $value) {
            if ($db->field_exists($field, 'youngo_payment_provider_configs')) {
                $filtered[$field] = $value;
            }
        }

        return $db->insert('youngo_payment_provider_configs', $filtered);
    }

    return true;
}

function ymic1_column_type($db, $table, $column)
{
    if (!$db->table_exists($table)) {
        return '';
    }

    $query = $db->query(
        'SELECT DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        array($table, $column)
    );
    if (!$query || $query->num_rows() === 0) {
        return '';
    }

    $row = $query->row_array();
    return strtolower($row['DATA_TYPE'] . '|' . $row['COLUMN_TYPE'] . '|nullable_' . $row['IS_NULLABLE'] . '|default_' . $row['COLUMN_DEFAULT']);
}

$db =& DB('', true);
$payment_config_model = new Youngo_payment_config_model(array('db' => $db));
$audit_model = new Youngo_payment_config_audit_model(array('db' => $db));
$instapay_model = new Youngo_instapay_payment_model(array('db' => $db));

$paths = array(
    'payment_config_model' => $root . '/application/models/Youngo_payment_config_model.php',
    'audit_model' => $root . '/application/models/Youngo_payment_config_audit_model.php',
    'instapay_model' => $root . '/application/models/Youngo_instapay_payment_model.php',
    'settings_controller' => $root . '/application/controllers/Youngo_payment_settings.php',
    'settings_view' => $root . '/application/views/backend/admin/youngo_payment_settings.php',
    'checkout_controller' => $root . '/application/controllers/Youngo_checkout.php',
    'routes' => $root . '/application/config/routes.php',
    'up_sql' => $root . '/scripts/phase_2/payment_manual_instapay_config_1_up.sql',
    'down_sql' => $root . '/scripts/phase_2/payment_manual_instapay_config_1_down.sql',
);

$payment_config_source = ymic1_read($paths['payment_config_model']);
$audit_source = ymic1_read($paths['audit_model']);
$instapay_source = ymic1_read($paths['instapay_model']);
$controller_source = ymic1_read($paths['settings_controller']);
$view_source = ymic1_read($paths['settings_view']);
$checkout_controller_source = ymic1_read($paths['checkout_controller']);
$routes_source = ymic1_read($paths['routes']);
$up_sql = ymic1_read($paths['up_sql']);
$down_sql = ymic1_read($paths['down_sql']);

ymic1_check($checks, 'payment_config_model_loads', $payment_config_model instanceof Youngo_payment_config_model);
ymic1_check($checks, 'audit_model_loads', $audit_model instanceof Youngo_payment_config_audit_model);
ymic1_check($checks, 'instapay_model_loads', $instapay_model instanceof Youngo_instapay_payment_model);
foreach (array('get_instapay_config', 'is_instapay_checkout_enabled', 'build_instapay_target_snapshot') as $method) {
    ymic1_check($checks, 'payment_config_method_exists_' . $method, method_exists($payment_config_model, $method));
    ymic1_check($checks, 'instapay_method_exists_' . $method, method_exists($instapay_model, $method));
}

ymic1_check($checks, 'root_only_payment_settings_controller', strpos($controller_source, 'require_root_admin') !== false && strpos($controller_source, 'youngo_is_root_admin') !== false);
ymic1_check($checks, 'controller_has_instapay_save_action', strpos($controller_source, 'save_instapay_manual_config') !== false && strpos($controller_source, 'dashboard_instapay_manual_config_save') !== false);
ymic1_check($checks, 'view_has_instapay_config_fields', strpos($view_source, 'data-youngo-instapay-manual-config-form') !== false && strpos($view_source, 'instapay_target_address') !== false && strpos($view_source, 'instapay_instructions_ar') !== false);
ymic1_check($checks, 'view_has_no_upload_input', stripos($view_source, 'type="file"') === false && stripos($view_source, 'multipart/form-data') === false);
ymic1_check($checks, 'routes_have_upload_and_read_only_admin_review_without_decisions', strpos($routes_source, "youngo/checkout/instapay/submit/(:any)") !== false && strpos($routes_source, 'admin/youngo/instapay-payments') !== false && stripos($routes_source, 'approve') === false && stripos($routes_source, 'reject') === false);
ymic1_check($checks, 'checkout_controller_has_upload_but_no_approval', strpos($checkout_controller_source, 'submit_instapay') !== false && stripos($checkout_controller_source, 'approve_instapay') === false && stripos($checkout_controller_source, 'reject_instapay') === false);
ymic1_check($checks, 'sql_targets_existing_config_table_only', preg_match('/ALTER\s+TABLE\s+`youngo_payment_provider_configs`/i', $up_sql) === 1 && preg_match('/CREATE\s+TABLE|DROP\s+TABLE/i', $up_sql . "\n" . $down_sql) !== 1);

$expected_columns = array(
    'instapay_enabled_for_checkout' => 'tinyint',
    'instapay_target_label' => 'varchar',
    'instapay_target_address' => 'varchar',
    'instapay_target_link' => 'varchar',
    'instapay_instructions_ar' => 'text',
    'instapay_instructions_en' => 'text',
    'instapay_max_upload_mb' => 'decimal',
    'instapay_allowed_mimes' => 'varchar',
);

ymic1_check($checks, 'payment_provider_config_table_exists', $db->table_exists('youngo_payment_provider_configs'));
foreach ($expected_columns as $column => $expected_type) {
    $type = ymic1_column_type($db, 'youngo_payment_provider_configs', $column);
    ymic1_check($checks, 'config_field_exists_' . $column, $db->field_exists($column, 'youngo_payment_provider_configs'), $type);
    ymic1_check($checks, 'config_field_type_' . $column, strpos($type, $expected_type) !== false, $type);
}
ymic1_check($checks, 'instapay_schema_ready_helper_true', $payment_config_model->instapay_schema_ready());

$protected_tables = array(
    'youngo_payment_provider_configs',
    'youngo_payment_config_audit_logs',
    'youngo_instapay_payment_submissions',
    'youngo_checkout_orders',
    'youngo_course_access',
    'youngo_user_subscriptions',
    'youngo_manual_grants',
    'payment',
    'enrol',
);

$before_counts = ymic1_counts($db, $protected_tables);
$before_row = ymic1_config_row($db);
$details['instapay_config_existed_before'] = !empty($before_row) ? 'yes' : 'no';
$details['current_instapay_enabled_before'] = $payment_config_model->is_instapay_checkout_enabled() ? 'yes' : 'no';

try {
    $save_result = $payment_config_model->upsert_dashboard_instapay_manual_config(array(
        'instapay_enabled_for_checkout' => 0,
        'instapay_target_label' => 'YounGo Instapay Diagnostic',
        'instapay_target_address' => 'diagnostic@instapay',
        'instapay_target_link' => 'https://example.com/youngo-instapay-diagnostic',
        'instapay_instructions_ar' => 'تعليمات تشخيصية مؤقتة فقط.',
        'instapay_instructions_en' => 'Temporary diagnostic instructions only.',
        'instapay_max_upload_mb' => '5',
    ), null);

    $snapshot = $payment_config_model->build_instapay_target_snapshot('english');
    $instapay_model_snapshot = $instapay_model->build_instapay_target_snapshot('arabic');
    $blocked_save = $payment_config_model->upsert_dashboard_instapay_manual_config(array(
        'enabled' => 1,
        'instapay_target_label' => 'Blocked Diagnostic',
    ), null);

    $saved_row = ymic1_config_row($db);

    ymic1_check($checks, 'temporary_instapay_config_save_ok', !empty($save_result['ok']), isset($save_result['code']) ? $save_result['code'] : '');
    ymic1_check($checks, 'saved_config_checkout_flag_disabled', isset($saved_row['instapay_enabled_for_checkout']) && (int) $saved_row['instapay_enabled_for_checkout'] === 0);
    ymic1_check($checks, 'target_snapshot_built', !empty($snapshot['ok']) && isset($snapshot['data']['snapshot']['label']) && $snapshot['data']['snapshot']['label'] === 'YounGo Instapay Diagnostic');
    ymic1_check($checks, 'target_snapshot_disabled_and_egp', !empty($snapshot['data']['snapshot']) && empty($snapshot['data']['snapshot']['enabled']) && $snapshot['data']['snapshot']['currency'] === 'EGP');
    ymic1_check($checks, 'target_snapshot_allowed_mimes_images_only', !empty($snapshot['data']['snapshot']['allowed_mimes']) && $snapshot['data']['snapshot']['allowed_mimes'] === array('image/jpeg', 'image/png', 'image/webp'));
    ymic1_check($checks, 'instapay_model_snapshot_wrapper_built', !empty($instapay_model_snapshot['ok']) && isset($instapay_model_snapshot['data']['snapshot']['instructions']) && strpos($instapay_model_snapshot['data']['snapshot']['instructions'], 'تشخيصية') !== false);
    ymic1_check($checks, 'blocked_paymob_activation_gate_rejected', empty($blocked_save['ok']) && isset($blocked_save['code']) && $blocked_save['code'] === 'blocked_fields_rejected');
    ymic1_check($checks, 'checkout_enabled_helper_false_for_disabled_config', !$payment_config_model->is_instapay_checkout_enabled() && !$instapay_model->is_instapay_checkout_enabled());
} finally {
    $details['cleanup'] = ymic1_restore_config_row($db, $before_row) ? 'completed' : 'failed';
}

$after_counts = ymic1_counts($db, $protected_tables);
$after_row = ymic1_config_row($db);
ymic1_check($checks, 'instapay_config_row_restored', $before_row == $after_row);
ymic1_check($checks, 'protected_counts_restored', ymic1_counts_match($before_counts, $after_counts), json_encode(array('before' => $before_counts, 'after' => $after_counts), JSON_UNESCAPED_SLASHES));
ymic1_check($checks, 'no_entitlement_access_enrolment_rows_created', $before_counts['youngo_course_access'] === $after_counts['youngo_course_access'] && $before_counts['youngo_user_subscriptions'] === $after_counts['youngo_user_subscriptions'] && $before_counts['youngo_manual_grants'] === $after_counts['youngo_manual_grants'] && $before_counts['payment'] === $after_counts['payment'] && $before_counts['enrol'] === $after_counts['enrol']);
ymic1_check($checks, 'no_checkout_or_submission_rows_created', $before_counts['youngo_checkout_orders'] === $after_counts['youngo_checkout_orders'] && $before_counts['youngo_instapay_payment_submissions'] === $after_counts['youngo_instapay_payment_submissions']);
ymic1_check($checks, 'audit_count_unchanged_by_diagnostic', $before_counts['youngo_payment_config_audit_logs'] === $after_counts['youngo_payment_config_audit_logs']);
ymic1_check($checks, 'no_paymob_activation_in_model_source', stripos($payment_config_source, "'enabled' => 1") === false && stripos($payment_config_source, "checkout_cta_enabled'] = 1") === false);
ymic1_check($checks, 'instapay_status_values_limited', $instapay_model->allowed_statuses() === array('pending_review', 'approved', 'rejected') && $instapay_model->normalize_status('cancelled') === null && $instapay_model->normalize_status('draft') === null && $instapay_model->normalize_status('expired') === null);
ymic1_check($checks, 'audit_manual_mode_supported', strpos($audit_source, "'manual'") !== false);
ymic1_check($checks, 'no_live_approve_reject_access_methods_added', method_exists($instapay_model, 'create_pending_submission') && !method_exists($instapay_model, 'create_submission') && !method_exists($instapay_model, 'approve_submission') && !method_exists($instapay_model, 'reject_submission') && stripos($instapay_source, 'issue_course_purchase_access') === false && stripos($instapay_source, 'issue_subscription_purchase') === false);

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo 'PAYMENT.MANUAL.INSTAPAY.CONFIG.1 diagnostic' . PHP_EOL;
echo 'status: ' . (empty($failed) ? 'PASS' : 'FAIL') . PHP_EOL;
echo 'db_writes: ' . $details['db_writes'] . PHP_EOL;
echo 'cleanup: ' . $details['cleanup'] . PHP_EOL;
echo 'approved_instapay_review_statuses: ' . $details['approved_instapay_review_statuses'] . PHP_EOL;
echo 'instapay_config_existed_before: ' . $details['instapay_config_existed_before'] . PHP_EOL;
echo 'current_instapay_enabled_before: ' . $details['current_instapay_enabled_before'] . PHP_EOL;
foreach ($checks as $name => $check) {
    echo $check['status'] . ' ' . $name;
    if ($check['detail'] !== '') {
        echo ' :: ' . $check['detail'];
    }
    echo PHP_EOL;
}

exit(empty($failed) ? 0 : 1);
