<?php
/**
 * PAYMENT.PAYMOB.CONFIG.DASHBOARD.SCHEMA.1 diagnostic.
 *
 * Verifies dedicated YounGo Paymob dashboard config storage, inserts/updates
 * one local non-private diagnostic row, rejects private values, and cleans up.
 * No Paymob calls, no real private values, no legacy gateway writes.
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

$checks = array();
$details = array(
    'db_writes' => 'one_non_private_diagnostic_config_row_inserted_or_updated_then_deleted',
    'cleanup' => 'not_started',
);

function ypcd1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypcd1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypcd1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypcd1_count($db, $table)
{
    if (!$db->table_exists($table)) {
        return null;
    }

    return (int) $db->count_all($table);
}

function ypcd1_column_exists($db, $table, $column)
{
    return $db->table_exists($table) && $db->field_exists($column, $table);
}

function ypcd1_index_columns($db, $table, $index)
{
    $query = $db->query(
        'SELECT COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? ORDER BY SEQ_IN_INDEX',
        array($table, $index)
    );
    if (!$query) {
        return array();
    }

    $columns = array();
    foreach ($query->result_array() as $row) {
        $columns[] = $row['COLUMN_NAME'];
    }

    return $columns;
}

function ypcd1_value_contains_secret_shape($value)
{
    if (!is_string($value) || $value === '') {
        return false;
    }

    return (bool) preg_match('/(sk_live|sk_test|pk_live_[A-Za-z0-9]{12,}|[A-Fa-f0-9]{64,}|Bearer\\s+)/', $value);
}

function ypcd1_array_has_secret_shape($value)
{
    if (is_array($value)) {
        foreach ($value as $child) {
            if (ypcd1_array_has_secret_shape($child)) {
                return true;
            }
        }
        return false;
    }

    return ypcd1_value_contains_secret_shape((string) $value);
}

class Ypcd1_config_stub
{
    protected $values;

    public function __construct($values)
    {
        $this->values = is_array($values) ? $values : array();
    }

    public function item($key)
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : null;
    }
}

$paths = array(
    'up_sql' => $root . '/scripts/phase_2/payment_paymob_config_dashboard_schema_1_up.sql',
    'down_sql' => $root . '/scripts/phase_2/payment_paymob_config_dashboard_schema_1_down.sql',
    'model' => $root . '/application/models/Youngo_payment_config_model.php',
    'config_php' => $root . '/application/config/config.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
    'paymob_config_reader' => $root . '/application/libraries/Youngo_paymob_config.php',
);

foreach ($paths as $name => $path) {
    ypcd1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

$config_source = ypcd1_read($paths['config_php']);
$up_sql = ypcd1_read($paths['up_sql']);
$down_sql = ypcd1_read($paths['down_sql']);
$model_source = ypcd1_read($paths['model']);
$up_sql_without_comments = preg_replace('/--.*$/m', '', $up_sql);

ypcd1_check($checks, 'encryption_key_empty', strpos($config_source, "\$config['encryption_key'] = '';") !== false);
ypcd1_check($checks, 'up_sql_dedicated_table', strpos($up_sql, 'CREATE TABLE IF NOT EXISTS `youngo_payment_provider_configs`') !== false);
ypcd1_check($checks, 'up_sql_no_legacy_payment_gateway_use', strpos($up_sql_without_comments, 'payment_gateways') === false);
ypcd1_check($checks, 'up_sql_defaults_disabled', strpos($up_sql, '`enabled` TINYINT(1) NOT NULL DEFAULT 0') !== false && strpos($up_sql, '`network_enabled` TINYINT(1) NOT NULL DEFAULT 0') !== false && strpos($up_sql, '`checkout_cta_enabled` TINYINT(1) NOT NULL DEFAULT 0') !== false);
ypcd1_check($checks, 'up_sql_egp_sandbox_defaults', strpos($up_sql, "`mode` VARCHAR(20) NOT NULL DEFAULT 'sandbox'") !== false && strpos($up_sql, "`currency` VARCHAR(10) NOT NULL DEFAULT 'EGP'") !== false);
ypcd1_check($checks, 'up_sql_private_status_only', strpos($up_sql, '`secret_key_present`') !== false && strpos($up_sql, '`hmac_secret_present`') !== false && strpos($up_sql, '`private_storage_status`') !== false && strpos($up_sql, '`secret_key`') === false && strpos($up_sql, '`hmac_secret`') === false);
ypcd1_check($checks, 'down_sql_review_marker', strpos($down_sql, 'ROLLBACK SQL FOR LOCAL PAYMENT.PAYMOB.CONFIG.DASHBOARD.SCHEMA.1 ONLY') !== false && strpos($down_sql, 'NOT EXECUTED') !== false);
ypcd1_check($checks, 'model_rejects_private_fields', strpos($model_source, 'private_storage_blocked_encryption_key_missing') !== false && strpos($model_source, 'Private Paymob values cannot be saved in this phase') !== false);
ypcd1_check($checks, 'model_forces_activation_flags_disabled', strpos($model_source, 'activation_flags_disabled_in_this_phase') !== false);

$CI = new stdClass();
$CI->config = new Ypcd1_config_stub(array('encryption_key' => ''));
function get_instance()
{
    global $CI;
    return $CI;
}

$db_config = ypcd1_db_config();
$db = DB($db_config, true);
$CI->db = $db;
$model = new Youngo_payment_config_model();
$model->db = $db;

$table = 'youngo_payment_provider_configs';
$diagnostic_provider = 'paymob_schema_diag';
$baseline_counts = array(
    $table => ypcd1_count($db, $table),
    'payment_gateways' => ypcd1_count($db, 'payment_gateways'),
    'payment' => ypcd1_count($db, 'payment'),
    'enrol' => ypcd1_count($db, 'enrol'),
);

ypcd1_check($checks, 'model_loads', $model instanceof Youngo_payment_config_model);
ypcd1_check($checks, 'table_exists_youngo_payment_provider_configs', $db->table_exists($table));

$expected_columns = array(
    'provider',
    'mode',
    'currency',
    'amount_multiplier',
    'enabled',
    'network_enabled',
    'sandbox_network_testing_enabled',
    'webhook_testing_enabled',
    'checkout_routes_enabled',
    'checkout_local_testing_enabled',
    'checkout_cta_enabled',
    'live_mode_allowed',
    'public_key',
    'public_key_present',
    'secret_key_present',
    'hmac_secret_present',
    'api_key_present',
    'card_integration_id_egp',
    'api_base_url',
    'checkout_base_url',
    'return_url',
    'notification_url',
    'readiness_status',
    'private_storage_status',
    'updated_by_user_id',
    'created_at',
    'updated_at',
);

foreach ($expected_columns as $column) {
    ypcd1_check($checks, 'column_exists_' . $column, ypcd1_column_exists($db, $table, $column));
}

ypcd1_check($checks, 'unique_provider_mode_index_exists', ypcd1_index_columns($db, $table, 'uniq_yppc_provider_mode') === array('provider', 'mode'));

$defaults = $model->get_provider_config('paymob', 'sandbox');
ypcd1_check($checks, 'defaults_load', !empty($defaults['ok']) && isset($defaults['data']['config']));
if (!empty($defaults['data']['config'])) {
    $default_config = $defaults['data']['config'];
    ypcd1_check($checks, 'defaults_disabled', empty($default_config['enabled']) && empty($default_config['network_enabled']) && empty($default_config['checkout_cta_enabled']));
    ypcd1_check($checks, 'defaults_egp_sandbox', $default_config['currency'] === 'EGP' && $default_config['mode'] === 'sandbox');
    ypcd1_check($checks, 'defaults_private_storage_blocked', $default_config['private_storage_status'] === 'blocked_encryption_key_missing');
}

$private_save = $model->upsert_non_private_config('paymob', 'sandbox', array(
    'secret_key' => 'DIAGNOSTIC_PRIVATE_VALUE_SHOULD_BE_REJECTED',
));
ypcd1_check($checks, 'private_value_save_rejected', empty($private_save['ok']) && $private_save['code'] === 'private_storage_blocked_encryption_key_missing');

$enabled_save = $model->upsert_non_private_config('paymob', 'sandbox', array(
    'enabled' => 1,
));
ypcd1_check($checks, 'activation_flag_enable_rejected', empty($enabled_save['ok']) && $enabled_save['code'] === 'activation_flags_disabled_in_this_phase');

$diagnostic_data = array(
    'currency' => 'EGP',
    'amount_multiplier' => 100,
    'public_key' => 'DIAGNOSTIC_PUBLIC_PLACEHOLDER_ONLY',
    'card_integration_id_egp' => '123456',
    'api_base_url' => 'https://example.invalid/paymob-api',
    'checkout_base_url' => 'https://example.invalid/paymob-checkout',
    'return_url' => 'http://school.local/youngo/checkout/return/{order_reference}',
    'notification_url' => 'https://example.invalid/payment/paymob/webhook',
    'updated_by_user_id' => 0,
);

$insert = $model->upsert_non_private_config($diagnostic_provider, 'sandbox', $diagnostic_data);
ypcd1_check($checks, 'non_private_config_inserted', !empty($insert['ok']), isset($insert['code']) ? $insert['code'] : '');

$update = $model->upsert_non_private_config($diagnostic_provider, 'sandbox', array_merge($diagnostic_data, array(
    'card_integration_id_egp' => '654321',
)));
ypcd1_check($checks, 'non_private_config_updated', !empty($update['ok']), isset($update['code']) ? $update['code'] : '');

$summary = $model->get_safe_config_summary($diagnostic_provider, 'sandbox');
ypcd1_check($checks, 'safe_summary_loaded', !empty($summary['ok']) && isset($summary['data']));
if (!empty($summary['data'])) {
    ypcd1_check($checks, 'safe_summary_redacts_private_fields', isset($summary['data']['secret_key']) && $summary['data']['secret_key'] === 'missing' && isset($summary['data']['hmac_secret']) && $summary['data']['hmac_secret'] === 'missing');
    ypcd1_check($checks, 'safe_summary_no_secret_shape', !ypcd1_array_has_secret_shape($summary['data']));
}

$readiness = $model->get_readiness_summary($diagnostic_provider, 'sandbox');
ypcd1_check($checks, 'readiness_summary_blocks_network', !empty($readiness['ok']) && empty($readiness['data']['readiness']['ready_for_network']));
ypcd1_check(
    $checks,
    'readiness_reports_encryption_blocker',
    !empty($readiness['data']['readiness']['errors']) && in_array('private_storage_blocked_encryption_key_missing', $readiness['data']['readiness']['errors'], true)
);

$delete = $db->where('provider', $diagnostic_provider)->where('mode', 'sandbox')->delete($table);
$details['cleanup'] = $delete ? 'diagnostic_config_row_deleted' : 'cleanup_delete_failed';
ypcd1_check($checks, 'diagnostic_config_cleanup_deleted', (bool) $delete);

$after_counts = array(
    $table => ypcd1_count($db, $table),
    'payment_gateways' => ypcd1_count($db, 'payment_gateways'),
    'payment' => ypcd1_count($db, 'payment'),
    'enrol' => ypcd1_count($db, 'enrol'),
);

$protected_unchanged = $baseline_counts['payment_gateways'] === $after_counts['payment_gateways']
    && $baseline_counts['payment'] === $after_counts['payment']
    && $baseline_counts['enrol'] === $after_counts['enrol'];
ypcd1_check($checks, 'legacy_payment_tables_unchanged', $protected_unchanged);

$config_table_restored = $baseline_counts[$table] === $after_counts[$table];
ypcd1_check($checks, 'config_table_count_restored', $config_table_restored, 'before=' . $baseline_counts[$table] . ', after=' . $after_counts[$table]);

$details['baseline_counts'] = $baseline_counts;
$details['after_counts'] = $after_counts;

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

$result = array(
    'phase' => 'PAYMENT.PAYMOB.CONFIG.DASHBOARD.SCHEMA.1',
    'ok' => empty($failed),
    'failed_checks' => $failed,
    'checks' => $checks,
    'details' => $details,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(empty($failed) ? 0 : 1);
