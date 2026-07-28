<?php
/**
 * PAYMENT.SECRETS.ENCRYPTED.DB.SCHEMA.1 diagnostic.
 *
 * Read-only checks only. No DB writes, no Paymob requests, no raw credential
 * output, and no encryption key output.
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
    'db_writes' => 'none',
    'network_requests' => 'none',
    'credential_values' => 'not_printed_or_saved',
    'encryption_key' => 'presence_only',
);

function ypsedb1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypsedb1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypsedb1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypsedb1_count($db, $table)
{
    if (!$db->table_exists($table)) {
        return null;
    }

    return (int) $db->count_all($table);
}

function ypsedb1_columns($db, $table)
{
    if (!$db->table_exists($table)) {
        return array();
    }

    return $db->list_fields($table);
}

function ypsedb1_index_columns($db, $table, $index)
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

function ypsedb1_key_presence_from_config($root)
{
    $config = array();
    $_SERVER['HTTP_HOST'] = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'school.local';
    $_SERVER['SCRIPT_NAME'] = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';

    include $root . '/application/config/config.php';

    return isset($config['encryption_key']) && trim((string) $config['encryption_key']) !== '';
}

function ypsedb1_scan_needles($sources, $needles)
{
    $hits = array();
    foreach ($sources as $name => $source) {
        foreach ($needles as $needle) {
            if (strpos($source, $needle) !== false) {
                $hits[] = $name . ':' . $needle;
            }
        }
    }

    return $hits;
}

class Ypsedb1_config_stub
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
    'up_sql' => $root . '/scripts/phase_2/payment_secrets_encrypted_db_schema_1_up.sql',
    'down_sql' => $root . '/scripts/phase_2/payment_secrets_encrypted_db_schema_1_down.sql',
    'model' => $root . '/application/models/Youngo_payment_config_model.php',
    'controller' => $root . '/application/controllers/Youngo_payment_settings.php',
    'view' => $root . '/application/views/backend/admin/youngo_payment_settings.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
    'gitignore' => $root . '/.gitignore',
    'real_security_config' => $root . '/application/config/youngo_security.local.php',
);

foreach ($paths as $name => $path) {
    if ($name !== 'real_security_config') {
        ypsedb1_check($checks, 'file_exists_' . $name, is_file($path), $path);
    }
}

$up_sql = ypsedb1_read($paths['up_sql']);
$down_sql = ypsedb1_read($paths['down_sql']);
$up_sql_without_comments = preg_replace('/--.*$/m', '', $up_sql);
$model_source = ypsedb1_read($paths['model']);
$controller_source = ypsedb1_read($paths['controller']);
$view_source = ypsedb1_read($paths['view']);
$paymob_config_source = ypsedb1_read($paths['paymob_config']);
$gitignore_source = ypsedb1_read($paths['gitignore']);

ypsedb1_check($checks, 'up_sql_creates_secret_table', strpos($up_sql, 'CREATE TABLE IF NOT EXISTS `youngo_payment_provider_secret_configs`') !== false);
ypsedb1_check($checks, 'down_sql_review_marker', strpos($down_sql, 'ROLLBACK SQL FOR LOCAL PAYMENT.SECRETS.ENCRYPTED.DB.SCHEMA.1 ONLY') !== false && strpos($down_sql, 'NOT EXECUTED') !== false);
ypsedb1_check($checks, 'up_sql_no_inserts_or_real_values', !preg_match('/\bINSERT\b/i', $up_sql_without_comments) && stripos($up_sql_without_comments, 'sk_live_') === false && stripos($up_sql_without_comments, 'sk_test_') === false && stripos($up_sql_without_comments, 'pk_live_') === false && stripos($up_sql_without_comments, 'pk_test_') === false);
ypsedb1_check($checks, 'up_sql_no_legacy_gateway_changes', stripos($up_sql_without_comments, 'payment_gateways') === false);

$config_key_ready = ypsedb1_key_presence_from_config($root);
$CI = new stdClass();
$CI->config = new Ypsedb1_config_stub(array(
    'encryption_key' => $config_key_ready ? 'configured_for_presence_check_only' : '',
));
function get_instance()
{
    global $CI;
    return $CI;
}

$db_config = ypsedb1_db_config();
$db = DB($db_config, true);
$CI->db = $db;
$model = new Youngo_payment_config_model();
$model->db = $db;

$secret_table = 'youngo_payment_provider_secret_configs';
$baseline_counts = array(
    'youngo_checkout_orders' => ypsedb1_count($db, 'youngo_checkout_orders'),
    'youngo_payment_transactions' => ypsedb1_count($db, 'youngo_payment_transactions'),
    'youngo_course_access' => ypsedb1_count($db, 'youngo_course_access'),
    'payment' => ypsedb1_count($db, 'payment'),
    'enrol' => ypsedb1_count($db, 'enrol'),
    'payment_gateways' => ypsedb1_count($db, 'payment_gateways'),
    $secret_table => ypsedb1_count($db, $secret_table),
);

ypsedb1_check($checks, 'model_loads', $model instanceof Youngo_payment_config_model);
ypsedb1_check($checks, 'encrypted_credential_table_exists', $db->table_exists($secret_table));
ypsedb1_check($checks, 'model_secret_schema_ready', $model->secret_schema_ready());

$columns = ypsedb1_columns($db, $secret_table);
$expected_columns = array(
    'id',
    'provider',
    'mode',
    'encrypted_api_key',
    'encrypted_public_key',
    'encrypted_secret_key',
    'encrypted_hmac_secret',
    'has_api_key',
    'has_public_key',
    'has_secret_key',
    'has_hmac_secret',
    'encryption_version',
    'key_fingerprint',
    'storage_status',
    'last_rotated_at',
    'updated_by_user_id',
    'created_at',
    'updated_at',
);

foreach ($expected_columns as $column) {
    ypsedb1_check($checks, 'column_exists_' . $column, in_array($column, $columns, true));
}

$forbidden_raw_columns = array('api_key', 'public_key', 'secret_key', 'hmac_secret');
$raw_column_hits = array_values(array_intersect($forbidden_raw_columns, $columns));
ypsedb1_check($checks, 'no_raw_plain_credential_columns', empty($raw_column_hits), implode(', ', $raw_column_hits));
ypsedb1_check($checks, 'unique_provider_mode_index_exists', ypsedb1_index_columns($db, $secret_table, 'uniq_yppsc_provider_mode') === array('provider', 'mode'));
$secret_row_count = ypsedb1_count($db, $secret_table);
ypsedb1_check($checks, 'secret_table_row_count_inspectable', $secret_row_count !== null, 'count=' . $secret_row_count);

ypsedb1_check($checks, 'real_security_config_ignored', strpos($gitignore_source, 'application/config/youngo_security.local.php') !== false);
ypsedb1_check($checks, 'real_security_config_exists_without_printing', is_file($paths['real_security_config']), is_file($paths['real_security_config']) ? 'exists_not_printed' : 'missing');
ypsedb1_check($checks, 'encryption_key_detected_redacted', $config_key_ready, $config_key_ready ? 'configured_redacted' : 'missing');

$storage_status = $model->encrypted_credential_storage_status('paymob', 'sandbox');
$secret_presence = $model->get_secret_credential_presence('paymob', 'sandbox');
$allowed_storage_statuses = array('key_ready_schema_ready_no_values', 'key_ready_schema_ready_partial_values', 'key_ready_schema_ready_configured_redacted', 'db_private_storage_blocked');
$allowed_presence_statuses = array('missing', 'configured_redacted');
ypsedb1_check($checks, 'storage_status_redacted_and_safe', in_array($storage_status, $allowed_storage_statuses, true), $storage_status);
ypsedb1_check(
    $checks,
    'secret_presence_summary_redacted',
    !empty($secret_presence['ok'])
    && isset($secret_presence['data']['public_key_status'])
    && in_array($secret_presence['data']['public_key_status'], $allowed_presence_statuses, true)
    && isset($secret_presence['data']['key_fingerprint'])
    && in_array($secret_presence['data']['key_fingerprint'], $allowed_presence_statuses, true)
);

$private_post_names = array('name="api_key"', "name='api_key'", 'name="public_key"', "name='public_key'", 'name="secret_key"', "name='secret_key'", 'name="hmac_secret"', "name='hmac_secret'");
$private_post_hits = ypsedb1_scan_needles(array('settings_view' => $view_source), $private_post_names);
ypsedb1_check($checks, 'dashboard_has_no_flat_private_submit_names', empty($private_post_hits), implode(', ', $private_post_hits));
ypsedb1_check($checks, 'dashboard_private_fields_use_encrypted_credentials_namespace', strpos($view_source, 'name="credentials[') !== false && strpos($view_source, 'data-youngo-private-input="encrypted-db"') !== false);
ypsedb1_check($checks, 'dashboard_private_fields_no_value_attributes', !preg_match('/name="credentials\[[^"]+\]"[^>]*\svalue=/i', $view_source));
ypsedb1_check($checks, 'dashboard_schema_ready_status_present', strpos($view_source, 'key_ready_schema_ready_no_values') !== false && strpos($view_source, 'Encrypted credential schema') !== false);

$credential_save_hits = ypsedb1_scan_needles(
    array('settings_controller' => $controller_source, 'settings_view' => $view_source, 'model' => $model_source),
    array('save_secret_credentials', 'save_encrypted_credentials', 'dashboard_secret_credentials_save')
);
ypsedb1_check($checks, 'credential_save_uses_encrypted_flow_only', count($credential_save_hits) >= 3, implode(', ', $credential_save_hits));

$network_hits = ypsedb1_scan_needles(
    array(
        'up_sql' => $up_sql,
        'down_sql' => $down_sql,
        'model' => $model_source,
        'controller' => $controller_source,
        'view' => $view_source,
    ),
    array('curl_init', 'curl_exec', 'CURLOPT_', 'file_get_contents(\'http', 'file_get_contents("http', 'fsockopen', 'stream_socket_client')
);
ypsedb1_check($checks, 'no_paymob_call_patterns_added_here', empty($network_hits), implode(', ', $network_hits));

ypsedb1_check($checks, 'tracked_paymob_defaults_still_disabled', strpos($paymob_config_source, "'enabled' => false") !== false && strpos($paymob_config_source, "'network_enabled' => false") !== false && strpos($paymob_config_source, "'checkout_cta_enabled' => false") !== false);

$gate_query = $db->query(
    'SELECT COALESCE(SUM(enabled + network_enabled + sandbox_network_testing_enabled + webhook_testing_enabled + checkout_routes_enabled + checkout_local_testing_enabled + checkout_cta_enabled + live_mode_allowed + transaction_inquiry_enabled), 0) AS enabled_gate_count FROM youngo_payment_provider_configs'
);
$enabled_gate_count = $gate_query ? (int) $gate_query->row_array()['enabled_gate_count'] : -1;
ypsedb1_check($checks, 'db_activation_gates_remain_disabled', $enabled_gate_count === 0, 'enabled_gate_count=' . $enabled_gate_count);

$after_counts = array(
    'youngo_checkout_orders' => ypsedb1_count($db, 'youngo_checkout_orders'),
    'youngo_payment_transactions' => ypsedb1_count($db, 'youngo_payment_transactions'),
    'youngo_course_access' => ypsedb1_count($db, 'youngo_course_access'),
    'payment' => ypsedb1_count($db, 'payment'),
    'enrol' => ypsedb1_count($db, 'enrol'),
    'payment_gateways' => ypsedb1_count($db, 'payment_gateways'),
    $secret_table => ypsedb1_count($db, $secret_table),
);

$protected_unchanged = $baseline_counts['youngo_checkout_orders'] === $after_counts['youngo_checkout_orders']
    && $baseline_counts['youngo_payment_transactions'] === $after_counts['youngo_payment_transactions']
    && $baseline_counts['youngo_course_access'] === $after_counts['youngo_course_access']
    && $baseline_counts['payment'] === $after_counts['payment']
    && $baseline_counts['enrol'] === $after_counts['enrol']
    && $baseline_counts['payment_gateways'] === $after_counts['payment_gateways']
    && $baseline_counts[$secret_table] === $after_counts[$secret_table];
ypsedb1_check($checks, 'protected_counts_unchanged_during_diagnostic', $protected_unchanged);

$details['storage_status'] = $storage_status;
$details['secret_schema_row_count'] = $secret_row_count;
$details['baseline_counts'] = $baseline_counts;
$details['after_counts'] = $after_counts;

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo json_encode(array(
    'phase' => 'PAYMENT.SECRETS.ENCRYPTED.DB.SCHEMA.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
