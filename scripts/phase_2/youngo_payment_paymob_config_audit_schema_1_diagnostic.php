<?php
/**
 * PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.SCHEMA.1 diagnostic.
 *
 * Verifies dedicated YounGo Paymob config audit logging, writes one redacted
 * diagnostic audit row, rejects raw private values, reads the safe summary,
 * and cleans up. No Paymob calls, no credentials, no payment activation.
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
require_once APPPATH . 'models/Youngo_payment_config_audit_model.php';

$checks = array();
$details = array(
    'db_writes' => 'one_redacted_diagnostic_audit_row_inserted_then_deleted',
    'cleanup' => 'not_started',
);

function ypca1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypca1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypca1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypca1_count($db, $table)
{
    if (!$db->table_exists($table)) {
        return null;
    }

    return (int) $db->count_all($table);
}

function ypca1_column_exists($db, $table, $column)
{
    return $db->table_exists($table) && $db->field_exists($column, $table);
}

function ypca1_index_exists($db, $table, $index)
{
    if (!$db->table_exists($table)) {
        return false;
    }

    $query = $db->query(
        'SELECT COUNT(*) AS count FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
        array($table, $index)
    );
    if (!$query) {
        return false;
    }

    $row = $query->row_array();
    return !empty($row['count']);
}

function ypca1_array_contains_private_value_shape($value)
{
    if (is_array($value)) {
        foreach ($value as $child) {
            if (ypca1_array_contains_private_value_shape($child)) {
                return true;
            }
        }
        return false;
    }

    $value = (string) $value;
    return $value !== '' && (bool) preg_match('/(DIAGNOSTIC_PRIVATE_VALUE|Bearer\s+|[A-Fa-f0-9]{64,})/', $value);
}

class Ypca1_session_stub
{
    public function userdata($key)
    {
        return null;
    }
}

$paths = array(
    'up_sql' => $root . '/scripts/phase_2/payment_paymob_config_audit_schema_1_up.sql',
    'down_sql' => $root . '/scripts/phase_2/payment_paymob_config_audit_schema_1_down.sql',
    'audit_model' => $root . '/application/models/Youngo_payment_config_audit_model.php',
    'config_model' => $root . '/application/models/Youngo_payment_config_model.php',
    'settings_controller' => $root . '/application/controllers/Youngo_payment_settings.php',
    'settings_view' => $root . '/application/views/backend/admin/youngo_payment_settings.php',
);

foreach ($paths as $name => $path) {
    ypca1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

$up_sql = ypca1_read($paths['up_sql']);
$up_sql_without_comments = preg_replace('/--.*$/m', '', $up_sql);
$down_sql = ypca1_read($paths['down_sql']);
$audit_model_source = ypca1_read($paths['audit_model']);
$settings_source = ypca1_read($paths['settings_controller']) . "\n" . ypca1_read($paths['settings_view']);

ypca1_check($checks, 'up_sql_dedicated_audit_table', strpos($up_sql, 'CREATE TABLE IF NOT EXISTS `youngo_payment_config_audit_logs`') !== false);
ypca1_check($checks, 'up_sql_no_destructive_data_statements', !preg_match('/\b(DROP|TRUNCATE|DELETE|UPDATE|INSERT)\b/i', $up_sql_without_comments));
ypca1_check($checks, 'up_sql_no_legacy_payment_gateways_use', strpos($up_sql_without_comments, 'payment_gateways') === false);
ypca1_check($checks, 'up_sql_secret_presence_only', strpos($up_sql, 'secret_presence_changes_json') !== false && strpos($up_sql, '`secret_key`') === false && strpos($up_sql, '`hmac_secret`') === false);
ypca1_check($checks, 'down_sql_review_marker', strpos($down_sql, 'ROLLBACK SQL FOR LOCAL PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.SCHEMA.1 ONLY') !== false && strpos($down_sql, 'NOT EXECUTED') !== false);
ypca1_check($checks, 'audit_model_rejects_private_values', strpos($audit_model_source, 'private_values_rejected') !== false);
ypca1_check($checks, 'audit_model_uses_redacted_summaries', strpos($audit_model_source, 'redacted_config_summary') !== false && strpos($audit_model_source, 'secret_presence_changes') !== false);
$settings_audit_wired = strpos($settings_source, 'Youngo_payment_config_audit_model') !== false
    || strpos($settings_source, 'youngo_payment_config_audit_model') !== false;
ypca1_check(
    $checks,
    'settings_ui_audit_wiring_safe_if_present',
    !$settings_audit_wired || (
        strpos($settings_source, 'record_config_audit') !== false
        && strpos($settings_source, 'name="secret_key"') === false
        && strpos($settings_source, 'name="hmac_secret"') === false
        && strpos($settings_source, 'name="api_key"') === false
    ),
    $settings_audit_wired ? 'wired_without_secret_inputs' : 'not_wired_in_schema_phase'
);

$CI = new stdClass();
$CI->session = new Ypca1_session_stub();
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ypca1_db_config(), true);
$CI->db = $db;
$model = new Youngo_payment_config_audit_model(array('db' => $db));

$table = 'youngo_payment_config_audit_logs';
$baseline_counts = array(
    $table => ypca1_count($db, $table),
    'youngo_payment_provider_configs' => ypca1_count($db, 'youngo_payment_provider_configs'),
    'payment_gateways' => ypca1_count($db, 'payment_gateways'),
    'payment' => ypca1_count($db, 'payment'),
    'enrol' => ypca1_count($db, 'enrol'),
);

$baseline_gate_row = null;
if ($db->table_exists('youngo_payment_provider_configs')) {
    $baseline_gate_row = $db
        ->select('enabled, network_enabled, sandbox_network_testing_enabled, webhook_testing_enabled, checkout_routes_enabled, checkout_cta_enabled, live_mode_allowed')
        ->where('provider', 'paymob')
        ->where('mode', 'sandbox')
        ->get('youngo_payment_provider_configs', 1)
        ->row_array();
}

ypca1_check($checks, 'model_loads', $model instanceof Youngo_payment_config_audit_model);
ypca1_check($checks, 'audit_table_exists', $db->table_exists($table));

$expected_columns = array(
    'provider',
    'mode',
    'action',
    'actor_user_id',
    'actor_role',
    'actor_type',
    'changed_fields_json',
    'before_summary_json',
    'after_summary_json',
    'secret_presence_changes_json',
    'ip_address',
    'user_agent',
    'created_at',
);

foreach ($expected_columns as $column) {
    ypca1_check($checks, 'column_exists_' . $column, ypca1_column_exists($db, $table, $column));
}

foreach (array('idx_ypcal_provider_mode_created', 'idx_ypcal_actor_created', 'idx_ypcal_action_created') as $index) {
    ypca1_check($checks, 'index_exists_' . $index, ypca1_index_exists($db, $table, $index));
}

ypca1_check($checks, 'schema_ready', $model->schema_ready());

$before = array(
    'provider' => 'paymob',
    'mode' => 'sandbox',
    'currency' => 'EGP',
    'amount_multiplier' => 100,
    'enabled' => 0,
    'network_enabled' => 0,
    'checkout_cta_enabled' => 0,
    'secret_key' => 'missing',
    'hmac_secret' => 'missing',
    'api_key' => 'missing',
    'card_integration_id_egp' => '',
    'api_base_url' => '',
    'checkout_base_url' => '',
    'return_url' => '',
    'notification_url' => '',
);

$after = array_merge($before, array(
    'card_integration_id_egp' => '123456',
    'api_base_url' => 'https://example.invalid/paymob-api',
    'checkout_base_url' => 'https://example.invalid/paymob-checkout',
    'return_url' => 'http://school.local/youngo/checkout/return/{order_reference}',
    'notification_url' => 'https://example.invalid/payment/paymob/webhook',
    'secret_key' => 'configured_redacted',
));

$private_reject = $model->record_config_audit('paymob', 'sandbox', 'diagnostic_private_reject', $before, array_merge($after, array(
    'secret_key' => 'DIAGNOSTIC_PRIVATE_VALUE_SHOULD_BE_REJECTED',
)), 0);
ypca1_check($checks, 'private_raw_value_rejected', empty($private_reject['ok']) && $private_reject['code'] === 'private_values_rejected');

$record = $model->record_config_audit('paymob', 'sandbox', 'diagnostic_non_private_save', $before, $after, 0);
ypca1_check($checks, 'audit_insert_works', !empty($record['ok']), isset($record['code']) ? $record['code'] : '');
$audit_id = !empty($record['data']['audit_id']) ? (int) $record['data']['audit_id'] : 0;

$recent = $model->get_recent_audit_logs('paymob', 'sandbox', 5);
ypca1_check($checks, 'recent_audit_read_works', !empty($recent['ok']) && !empty($recent['data']['logs']));

$safe_summary = array();
if ($audit_id > 0) {
    $row = $db->where('id', $audit_id)->get($table, 1)->row_array();
    $safe_summary = $model->get_safe_audit_summary($row);
}

ypca1_check($checks, 'safe_summary_redacts_private_fields', !empty($safe_summary['after_summary']['secret_key']) && $safe_summary['after_summary']['secret_key'] === 'configured_redacted');
ypca1_check($checks, 'safe_summary_no_private_value_shapes', !ypca1_array_contains_private_value_shape($safe_summary));
ypca1_check($checks, 'secret_presence_change_recorded', !empty($safe_summary['secret_presence_changes']['secret_key']['presence_changed']));

if ($audit_id > 0) {
    $deleted = $db->where('id', $audit_id)->delete($table);
    $details['cleanup'] = $deleted ? 'diagnostic_audit_row_deleted' : 'cleanup_delete_failed';
    ypca1_check($checks, 'diagnostic_audit_row_deleted', (bool) $deleted);
} else {
    ypca1_check($checks, 'diagnostic_audit_row_deleted', false, 'audit_id_missing');
}

$after_counts = array(
    $table => ypca1_count($db, $table),
    'youngo_payment_provider_configs' => ypca1_count($db, 'youngo_payment_provider_configs'),
    'payment_gateways' => ypca1_count($db, 'payment_gateways'),
    'payment' => ypca1_count($db, 'payment'),
    'enrol' => ypca1_count($db, 'enrol'),
);

$after_gate_row = null;
if ($db->table_exists('youngo_payment_provider_configs')) {
    $after_gate_row = $db
        ->select('enabled, network_enabled, sandbox_network_testing_enabled, webhook_testing_enabled, checkout_routes_enabled, checkout_cta_enabled, live_mode_allowed')
        ->where('provider', 'paymob')
        ->where('mode', 'sandbox')
        ->get('youngo_payment_provider_configs', 1)
        ->row_array();
}

ypca1_check($checks, 'audit_table_count_restored_after_cleanup', $baseline_counts[$table] === $after_counts[$table], 'before=' . $baseline_counts[$table] . ', after=' . $after_counts[$table]);
ypca1_check($checks, 'provider_config_count_unchanged', $baseline_counts['youngo_payment_provider_configs'] === $after_counts['youngo_payment_provider_configs']);
ypca1_check($checks, 'payment_gateways_unchanged', $baseline_counts['payment_gateways'] === $after_counts['payment_gateways']);
ypca1_check($checks, 'legacy_payment_enrol_unchanged', $baseline_counts['payment'] === $after_counts['payment'] && $baseline_counts['enrol'] === $after_counts['enrol']);
ypca1_check($checks, 'gates_unchanged', $baseline_gate_row === $after_gate_row);

$network_pattern_found = false;
foreach (array($paths['audit_model'], $paths['config_model'], $paths['settings_controller']) as $path) {
    $source = ypca1_read($path);
    if (strpos($source, 'curl_exec') !== false || preg_match('/file_get_contents\s*\(\s*[\'"]http/i', $source)) {
        $network_pattern_found = true;
        break;
    }
}
ypca1_check($checks, 'no_paymob_network_call_patterns', !$network_pattern_found);

$details['baseline_counts'] = $baseline_counts;
$details['after_counts'] = $after_counts;

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

$result = array(
    'phase' => 'PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.SCHEMA.1',
    'ok' => empty($failed),
    'failed_checks' => $failed,
    'checks' => $checks,
    'details' => $details,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(empty($failed) ? 0 : 1);
