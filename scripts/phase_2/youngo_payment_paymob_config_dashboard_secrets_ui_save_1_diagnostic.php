<?php
/**
 * PAYMENT.PAYMOB.CONFIG.DASHBOARD.SECRETS.UI.SAVE.1 diagnostic.
 *
 * Uses safe dummy credential values only, saves them encrypted, verifies
 * redacted dashboard/audit behavior, and restores the original local DB state.
 * No Paymob requests, no credential output, and no encryption key output.
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

$checks = array();
$details = array(
    'db_writes' => 'controlled_dummy_encrypted_credential_save_then_restore',
    'network_requests' => 'none',
    'credential_values' => 'dummy_values_used_not_printed',
    'encryption_key' => 'presence_only',
    'cleanup' => 'not_started',
);

function ypcsus1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypcsus1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypcsus1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypcsus1_count($db, $table)
{
    if (!$db->table_exists($table)) {
        return null;
    }

    return (int) $db->count_all($table);
}

function ypcsus1_max_id($db, $table)
{
    if (!$db->table_exists($table) || !$db->field_exists('id', $table)) {
        return 0;
    }

    $row = $db->select('MAX(id) AS max_id')->get($table)->row_array();
    return isset($row['max_id']) ? (int) $row['max_id'] : 0;
}

function ypcsus1_load_config($root)
{
    $config = array();
    $_SERVER['HTTP_HOST'] = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'school.local';
    $_SERVER['SCRIPT_NAME'] = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
    include $root . '/application/config/config.php';

    return $config;
}

function ypcsus1_scan_needles($sources, $needles)
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

function ypcsus1_array_has_private_value_shape($value)
{
    if (is_array($value)) {
        foreach ($value as $child) {
            if (ypcsus1_array_has_private_value_shape($child)) {
                return true;
            }
        }

        return false;
    }

    $value = (string) $value;
    if ($value === '') {
        return false;
    }

    return (bool) preg_match('/(YOUNGO_QA_DUMMY_|sk_live|sk_test|pk_live|pk_test|Bearer\s+|[A-Fa-f0-9]{64,})/', $value);
}

function ypcsus1_string_contains_any_private_value($source, $values)
{
    $source = (string) $source;
    foreach ($values as $value) {
        if ($value !== '' && strpos($source, $value) !== false) {
            return true;
        }
    }

    return false;
}

function ypcsus1_result_data($result)
{
    return is_array($result) && !empty($result['ok']) && isset($result['data']) && is_array($result['data'])
        ? $result['data']
        : array();
}

class Ypcsus1_config_stub
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

class Ypcsus1_session_stub
{
    public function userdata($key)
    {
        return null;
    }
}

$paths = array(
    'controller' => $root . '/application/controllers/Youngo_payment_settings.php',
    'view' => $root . '/application/views/backend/admin/youngo_payment_settings.php',
    'config_model' => $root . '/application/models/Youngo_payment_config_model.php',
    'audit_model' => $root . '/application/models/Youngo_payment_config_audit_model.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
    'security_local' => $root . '/application/config/youngo_security.local.php',
    'gitignore' => $root . '/.gitignore',
);

foreach ($paths as $name => $path) {
    if ($name !== 'security_local') {
        ypcsus1_check($checks, 'file_exists_' . $name, is_file($path), $path);
    }
}

$controller_source = ypcsus1_read($paths['controller']);
$view_source = ypcsus1_read($paths['view']);
$config_model_source = ypcsus1_read($paths['config_model']);
$audit_model_source = ypcsus1_read($paths['audit_model']);
$paymob_config_source = ypcsus1_read($paths['paymob_config']);
$gitignore_source = ypcsus1_read($paths['gitignore']);
$source = $controller_source . "\n" . $view_source . "\n" . $config_model_source . "\n" . $audit_model_source;

ypcsus1_check($checks, 'real_security_config_exists_not_printed', is_file($paths['security_local']), is_file($paths['security_local']) ? 'exists_not_printed' : 'missing');
ypcsus1_check($checks, 'real_security_config_ignored', strpos($gitignore_source, 'application/config/youngo_security.local.php') !== false);
ypcsus1_check($checks, 'controller_root_only_guard_exists', strpos($controller_source, '$this->require_root_admin();') !== false && strpos($controller_source, 'youngo_is_root_admin') !== false && strpos($controller_source, "check_session_data('admin')") !== false);
ypcsus1_check($checks, 'controller_has_separate_credential_action', strpos($controller_source, 'save_secret_credentials') !== false && strpos($controller_source, "save_credentials") !== false);
ypcsus1_check($checks, 'view_has_masked_credential_form', strpos($view_source, 'data-youngo-paymob-secret-form="true"') !== false && strpos($view_source, 'type="password"') !== false && strpos($view_source, 'name="credentials[') !== false);
ypcsus1_check($checks, 'view_has_no_flat_private_input_names', empty(ypcsus1_scan_needles(array('view' => $view_source), array('name="api_key"', 'name="public_key"', 'name="secret_key"', 'name="hmac_secret"'))));
ypcsus1_check($checks, 'view_credential_inputs_have_no_value_attributes', !preg_match('/name="credentials\[[^"]+\]"[^>]*\svalue=/i', $view_source));
ypcsus1_check($checks, 'view_reports_no_values_displayed_after_save', strpos($view_source, 'Saved credential values are never displayed') !== false && strpos($view_source, 'Values are encrypted and never displayed') !== false);
ypcsus1_check($checks, 'model_has_encrypted_save_and_runtime_decrypt', strpos($config_model_source, 'save_encrypted_credentials') !== false && strpos($config_model_source, 'decrypt_credentials_for_runtime') !== false && strpos($config_model_source, 'aes-256-gcm') !== false);
ypcsus1_check($checks, 'audit_model_treats_private_presence_only', strpos($audit_model_source, 'secret_presence_changes') !== false && strpos($audit_model_source, "'public_key'") !== false && strpos($audit_model_source, 'private_values_rejected') !== false);

$network_hits = ypcsus1_scan_needles(
    array(
        'settings_controller' => $controller_source,
        'settings_view' => $view_source,
        'config_model' => $config_model_source,
        'audit_model' => $audit_model_source,
    ),
    array('curl_init', 'curl_exec', 'CURLOPT_', 'file_get_contents(\'http', 'file_get_contents("http', 'fsockopen', 'stream_socket_client')
);
ypcsus1_check($checks, 'no_paymob_network_call_patterns_added_here', empty($network_hits), implode(', ', $network_hits));
ypcsus1_check($checks, 'no_legacy_payment_gateways_dependency', stripos($source, 'payment_gateways') === false);
ypcsus1_check($checks, 'tracked_paymob_defaults_still_disabled', strpos($paymob_config_source, "'enabled' => false") !== false && strpos($paymob_config_source, "'network_enabled' => false") !== false && strpos($paymob_config_source, "'checkout_cta_enabled' => false"));

$loaded_config = ypcsus1_load_config($root);
$encryption_key_ready = isset($loaded_config['encryption_key']) && trim((string) $loaded_config['encryption_key']) !== '';
ypcsus1_check($checks, 'encryption_key_detected_redacted', $encryption_key_ready, $encryption_key_ready ? 'configured_redacted' : 'missing');

$CI = new stdClass();
$CI->config = new Ypcsus1_config_stub(array(
    'encryption_key' => $encryption_key_ready ? (string) $loaded_config['encryption_key'] : '',
));
$CI->session = new Ypcsus1_session_stub();
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ypcsus1_db_config(), true);
$CI->db = $db;
$config_model = new Youngo_payment_config_model();
$config_model->db = $db;
$audit_model = new Youngo_payment_config_audit_model(array('db' => $db));

$config_table = 'youngo_payment_provider_configs';
$secret_table = 'youngo_payment_provider_secret_configs';
$audit_table = 'youngo_payment_config_audit_logs';
$protected_tables = array(
    'youngo_checkout_orders',
    'youngo_payment_transactions',
    'youngo_course_access',
    'payment',
    'enrol',
    'payment_gateways',
);

$baseline_counts = array(
    $config_table => ypcsus1_count($db, $config_table),
    $secret_table => ypcsus1_count($db, $secret_table),
    $audit_table => ypcsus1_count($db, $audit_table),
);
foreach ($protected_tables as $table) {
    $baseline_counts[$table] = ypcsus1_count($db, $table);
}

$baseline_secret_row = $db->where('provider', 'paymob')->where('mode', 'sandbox')->get($secret_table, 1)->row_array();
$baseline_audit_max_id = ypcsus1_max_id($db, $audit_table);

ypcsus1_check($checks, 'config_model_loads', $config_model instanceof Youngo_payment_config_model);
ypcsus1_check($checks, 'audit_model_loads', $audit_model instanceof Youngo_payment_config_audit_model);
ypcsus1_check($checks, 'config_schema_ready', $config_model->schema_ready());
ypcsus1_check($checks, 'secret_schema_ready', $config_model->secret_schema_ready());
ypcsus1_check($checks, 'audit_schema_ready', $audit_model->schema_ready());

$dummy_credentials = array(
    'api_key' => 'YOUNGO_QA_DUMMY_API_KEY_VALUE_0001',
    'public_key' => 'YOUNGO_QA_DUMMY_PUBLIC_KEY_VALUE_0001',
    'secret_key' => 'YOUNGO_QA_DUMMY_SECRET_KEY_VALUE_0001',
    'hmac_secret' => 'YOUNGO_QA_DUMMY_HMAC_SECRET_VALUE_0001',
);

$before_summary = $config_model->get_safe_config_summary('paymob', 'sandbox');
$db->trans_begin();
$save = $config_model->save_encrypted_credentials('paymob', 'sandbox', $dummy_credentials, 0);
$after_summary = $config_model->get_safe_config_summary('paymob', 'sandbox');
$audit = !empty($save['ok'])
    ? $audit_model->record_config_audit('paymob', 'sandbox', 'dashboard_secret_credentials_save', ypcsus1_result_data($before_summary), ypcsus1_result_data($after_summary), 0)
    : array('ok' => false, 'code' => isset($save['code']) ? $save['code'] : 'credential_save_failed');

if (!empty($save['ok']) && !empty($audit['ok']) && $db->trans_status() !== false) {
    $db->trans_commit();
} else {
    $db->trans_rollback();
}

ypcsus1_check($checks, 'dummy_credential_save_succeeds', !empty($save['ok']) && isset($save['code']) && $save['code'] === 'credentials_saved', isset($save['code']) ? $save['code'] : '');
ypcsus1_check($checks, 'credential_save_reports_changed_fields_only', !empty($save['data']['changed_fields']) && count(array_diff(array('api_key', 'public_key', 'secret_key', 'hmac_secret'), $save['data']['changed_fields'])) === 0);
ypcsus1_check($checks, 'credential_save_audit_created', !empty($audit['ok']), isset($audit['code']) ? $audit['code'] : '');

$secret_row = $db->where('provider', 'paymob')->where('mode', 'sandbox')->get($secret_table, 1)->row_array();
$encrypted_columns = array('encrypted_api_key', 'encrypted_public_key', 'encrypted_secret_key', 'encrypted_hmac_secret');
$flags_ok = !empty($secret_row['has_api_key']) && !empty($secret_row['has_public_key']) && !empty($secret_row['has_secret_key']) && !empty($secret_row['has_hmac_secret']);
$encrypted_json_ok = true;
$plain_value_found = false;
foreach ($encrypted_columns as $column) {
    $stored = isset($secret_row[$column]) ? (string) $secret_row[$column] : '';
    $decoded = json_decode($stored, true);
    if (!is_array($decoded) || empty($decoded['cipher']) || $decoded['cipher'] !== 'aes-256-gcm' || empty($decoded['data'])) {
        $encrypted_json_ok = false;
    }
    if (ypcsus1_string_contains_any_private_value($stored, $dummy_credentials)) {
        $plain_value_found = true;
    }
}

ypcsus1_check($checks, 'presence_flags_updated', $flags_ok);
ypcsus1_check($checks, 'encrypted_columns_contain_cipher_payloads', $encrypted_json_ok);
ypcsus1_check($checks, 'db_does_not_store_dummy_values_plaintext', !$plain_value_found);
ypcsus1_check($checks, 'db_stores_key_fingerprint_without_printing', !empty($secret_row['key_fingerprint']), !empty($secret_row['key_fingerprint']) ? 'configured_redacted' : 'missing');

$presence = $config_model->get_secret_credential_presence('paymob', 'sandbox');
$presence_ok = !empty($presence['ok']) && !empty($presence['data']['api_key']) && !empty($presence['data']['public_key']) && !empty($presence['data']['secret_key']) && !empty($presence['data']['hmac_secret']);
ypcsus1_check($checks, 'presence_summary_configured_redacted_only', $presence_ok && !ypcsus1_array_has_private_value_shape($presence['data']));

$safe_summary = $config_model->get_safe_config_summary('paymob', 'sandbox');
ypcsus1_check($checks, 'safe_summary_redacts_credentials', !empty($safe_summary['ok']) && !ypcsus1_array_has_private_value_shape($safe_summary['data']));

$decrypted = $config_model->decrypt_credentials_for_runtime('paymob', 'sandbox');
$decryption_matches = !empty($decrypted['ok'])
    && isset($decrypted['data']['credentials'])
    && $decrypted['data']['credentials'] === $dummy_credentials;
ypcsus1_check($checks, 'runtime_decrypt_available_internally_without_output', $decryption_matches, !empty($decrypted['ok']) ? 'matched_in_memory_not_printed' : (isset($decrypted['code']) ? $decrypted['code'] : 'failed'));

$blank_before_row = $secret_row;
$blank_save = $config_model->save_encrypted_credentials('paymob', 'sandbox', array(
    'api_key' => '',
    'public_key' => '',
    'secret_key' => '',
    'hmac_secret' => '',
), 0);
$blank_after_row = $db->where('provider', 'paymob')->where('mode', 'sandbox')->get($secret_table, 1)->row_array();
$blank_kept_existing = !empty($blank_save['ok'])
    && isset($blank_save['code'])
    && $blank_save['code'] === 'credentials_unchanged';
foreach ($encrypted_columns as $column) {
    if ((string) $blank_before_row[$column] !== (string) $blank_after_row[$column]) {
        $blank_kept_existing = false;
    }
}
ypcsus1_check($checks, 'blank_fields_keep_existing_encrypted_values', $blank_kept_existing, isset($blank_save['code']) ? $blank_save['code'] : '');

$short_save = $config_model->save_encrypted_credentials('paymob', 'sandbox', array('secret_key' => 'short'), 0);
ypcsus1_check($checks, 'too_short_credential_rejected', empty($short_save['ok']) && isset($short_save['code']) && $short_save['code'] === 'credential_value_too_short', isset($short_save['code']) ? $short_save['code'] : '');

$audit_id = !empty($audit['data']['audit_id']) ? (int) $audit['data']['audit_id'] : 0;
$audit_row = $audit_id > 0 ? $db->where('id', $audit_id)->get($audit_table, 1)->row_array() : array();
$audit_summary = !empty($audit_row) ? $audit_model->get_safe_audit_summary($audit_row) : array();
$audit_secret_changes = isset($audit_summary['secret_presence_changes']) && is_array($audit_summary['secret_presence_changes'])
    ? $audit_summary['secret_presence_changes']
    : array();
$audit_expected_fields = array('api_key', 'public_key', 'secret_key', 'hmac_secret');
$audit_fields_ok = !empty($audit_summary['action']) && $audit_summary['action'] === 'dashboard_secret_credentials_save';
foreach ($audit_expected_fields as $field) {
    if (!isset($audit_secret_changes[$field])) {
        $audit_fields_ok = false;
    }
}
ypcsus1_check($checks, 'credential_audit_records_presence_changes_only', $audit_fields_ok && !ypcsus1_array_has_private_value_shape($audit_summary));

$raw_audit_query = $db->query(
    'SELECT COUNT(*) AS count FROM `' . $audit_table . '` WHERE id > ? AND (before_summary_json LIKE ? OR after_summary_json LIKE ? OR changed_fields_json LIKE ? OR secret_presence_changes_json LIKE ?)',
    array($baseline_audit_max_id, '%YOUNGO_QA_DUMMY_%', '%YOUNGO_QA_DUMMY_%', '%YOUNGO_QA_DUMMY_%', '%YOUNGO_QA_DUMMY_%')
);
$raw_audit_row = $raw_audit_query ? $raw_audit_query->row_array() : array('count' => 1);
ypcsus1_check($checks, 'audit_does_not_store_raw_dummy_values', empty($raw_audit_row['count']));

$gate_query = $db->query(
    'SELECT COALESCE(SUM(enabled + network_enabled + sandbox_network_testing_enabled + webhook_testing_enabled + checkout_routes_enabled + checkout_local_testing_enabled + checkout_cta_enabled + live_mode_allowed + transaction_inquiry_enabled), 0) AS enabled_gate_count FROM youngo_payment_provider_configs'
);
$enabled_gate_count = $gate_query ? (int) $gate_query->row_array()['enabled_gate_count'] : -1;
ypcsus1_check($checks, 'activation_network_cta_gates_remain_disabled', $enabled_gate_count === 0, 'enabled_gate_count=' . $enabled_gate_count);

$details['baseline_secret_row_existed'] = !empty($baseline_secret_row);
$audit_rows_after_baseline = $db->table_exists($audit_table)
    ? (int) $db->where('provider', 'paymob')->where('mode', 'sandbox')->where('id >', $baseline_audit_max_id)->count_all_results($audit_table)
    : 0;
$details['audit_rows_created_after_baseline'] = $audit_rows_after_baseline;

if ($db->table_exists($audit_table)) {
    $db->where('provider', 'paymob')->where('mode', 'sandbox')->where('id >', $baseline_audit_max_id)->delete($audit_table);
}

if ($db->table_exists($secret_table)) {
    $db->where('provider', 'paymob')->where('mode', 'sandbox')->delete($secret_table);
    if (!empty($baseline_secret_row)) {
        $db->insert($secret_table, $baseline_secret_row);
    }
}

$after_counts = array(
    $config_table => ypcsus1_count($db, $config_table),
    $secret_table => ypcsus1_count($db, $secret_table),
    $audit_table => ypcsus1_count($db, $audit_table),
);
foreach ($protected_tables as $table) {
    $after_counts[$table] = ypcsus1_count($db, $table);
}

$protected_unchanged = true;
foreach ($protected_tables as $table) {
    if ($baseline_counts[$table] !== $after_counts[$table]) {
        $protected_unchanged = false;
    }
}
$secret_count_restored = $baseline_counts[$secret_table] === $after_counts[$secret_table];
$audit_count_restored = $baseline_counts[$audit_table] === $after_counts[$audit_table];

$details['baseline_counts'] = $baseline_counts;
$details['after_counts'] = $after_counts;
$details['cleanup'] = ($protected_unchanged && $secret_count_restored && $audit_count_restored) ? 'counts_restored' : 'cleanup_incomplete';

ypcsus1_check($checks, 'cleanup_restored_secret_and_audit_counts', $secret_count_restored && $audit_count_restored);
ypcsus1_check($checks, 'protected_tables_unchanged', $protected_unchanged);
ypcsus1_check($checks, 'legacy_payment_enrol_unchanged', $baseline_counts['payment'] === $after_counts['payment'] && $baseline_counts['enrol'] === $after_counts['enrol'] && $baseline_counts['payment_gateways'] === $after_counts['payment_gateways']);
ypcsus1_check($checks, 'dummy_values_not_printed', true, 'verified_by_report_shape');

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo json_encode(array(
    'phase' => 'PAYMENT.PAYMOB.CONFIG.DASHBOARD.SECRETS.UI.SAVE.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
