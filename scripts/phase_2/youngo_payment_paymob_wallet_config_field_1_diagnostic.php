<?php
/**
 * PAYMENT.PAYMOB.WALLET.CONFIG.FIELD.1 diagnostic.
 *
 * Verifies non-private Paymob wallet integration ID support, dashboard form
 * wiring, redacted audit behavior, and cleanup. No Paymob requests, no
 * private credential output, and no payment/CTA activation.
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
    'db_writes' => 'one_paymob_sandbox_non_private_config_save_plus_one_redacted_audit_row_then_restore',
    'network_requests' => 'none',
    'credential_values' => 'not_printed_or_saved',
    'cleanup' => 'not_started',
);

function ypwcf1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypwcf1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypwcf1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypwcf1_count($db, $table)
{
    if (!$db->table_exists($table)) {
        return null;
    }

    return (int) $db->count_all($table);
}

function ypwcf1_max_id($db, $table)
{
    if (!$db->table_exists($table)) {
        return 0;
    }

    $row = $db->select_max('id')->get($table)->row_array();
    return isset($row['id']) ? (int) $row['id'] : 0;
}

function ypwcf1_scan_needles($sources, $needles)
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

function ypwcf1_has_private_value_shape($value)
{
    if (is_array($value)) {
        foreach ($value as $child) {
            if (ypwcf1_has_private_value_shape($child)) {
                return true;
            }
        }

        return false;
    }

    $value = (string) $value;
    if ($value === '') {
        return false;
    }

    return (bool) preg_match('/(sk_live|sk_test|pk_live|pk_test|Bearer\s+|[A-Fa-f0-9]{64,})/', $value);
}

function ypwcf1_result_data($result)
{
    return is_array($result) && !empty($result['ok']) && isset($result['data']) && is_array($result['data'])
        ? $result['data']
        : array();
}

class Ypwcf1_config_stub
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

class Ypwcf1_session_stub
{
    public function userdata($key)
    {
        return null;
    }
}

$paths = array(
    'up_sql' => $root . '/scripts/phase_2/payment_paymob_wallet_config_field_1_up.sql',
    'down_sql' => $root . '/scripts/phase_2/payment_paymob_wallet_config_field_1_down.sql',
    'config_model' => $root . '/application/models/Youngo_payment_config_model.php',
    'audit_model' => $root . '/application/models/Youngo_payment_config_audit_model.php',
    'settings_controller' => $root . '/application/controllers/Youngo_payment_settings.php',
    'settings_view' => $root . '/application/views/backend/admin/youngo_payment_settings.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
    'paymob_local_example' => $root . '/application/config/youngo_paymob.local.example.php',
    'paymob_config_reader' => $root . '/application/libraries/Youngo_paymob_config.php',
    'paymob_adapter' => $root . '/application/libraries/Youngo_paymob_adapter.php',
    'cta_helper' => $root . '/application/helpers/youngo_checkout_cta_helper.php',
    'course_page' => $root . '/application/views/frontend/youngo/course_page.php',
    'gitignore' => $root . '/.gitignore',
);

foreach ($paths as $name => $path) {
    ypwcf1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

$up_sql = ypwcf1_read($paths['up_sql']);
$down_sql = ypwcf1_read($paths['down_sql']);
$up_sql_without_comments = preg_replace('/--.*$/m', '', $up_sql);
$model_source = ypwcf1_read($paths['config_model']);
$audit_model_source = ypwcf1_read($paths['audit_model']);
$controller_source = ypwcf1_read($paths['settings_controller']);
$view_source = ypwcf1_read($paths['settings_view']);
$paymob_config_source = ypwcf1_read($paths['paymob_config']);
$paymob_local_example_source = ypwcf1_read($paths['paymob_local_example']);
$paymob_config_reader_source = ypwcf1_read($paths['paymob_config_reader']);
$paymob_adapter_source = ypwcf1_read($paths['paymob_adapter']);
$cta_helper_source = ypwcf1_read($paths['cta_helper']);
$course_page_source = ypwcf1_read($paths['course_page']);
$gitignore_source = ypwcf1_read($paths['gitignore']);

ypwcf1_check($checks, 'up_sql_adds_wallet_column', strpos($up_sql, 'wallet_integration_id_egp') !== false && preg_match('/ALTER TABLE\s+`youngo_payment_provider_configs`/i', $up_sql));
ypwcf1_check($checks, 'down_sql_drops_wallet_column_with_review_marker', strpos($down_sql, 'ROLLBACK SQL FOR LOCAL PAYMENT.PAYMOB.WALLET.CONFIG.FIELD.1 ONLY') !== false && strpos($down_sql, 'DROP COLUMN IF EXISTS `wallet_integration_id_egp`') !== false);
ypwcf1_check($checks, 'up_sql_has_no_credentials_or_gateway_changes', !preg_match('/\bINSERT\b/i', $up_sql_without_comments) && stripos($up_sql_without_comments, 'payment_gateways') === false && !ypwcf1_has_private_value_shape($up_sql_without_comments));
ypwcf1_check($checks, 'dashboard_form_has_wallet_field', strpos($view_source, 'name="wallet_integration_id_egp"') !== false && strpos($view_source, 'Mobile wallet integration ID EGP') !== false);
ypwcf1_check($checks, 'readiness_and_checklist_show_wallet_status', strpos($view_source, 'Mobile wallet integration ID') !== false && strpos($view_source, "wallet_integration_id_egp'") !== false);
ypwcf1_check($checks, 'model_allows_and_validates_wallet_non_private_field', strpos($model_source, "'wallet_integration_id_egp'") !== false && strpos($model_source, 'invalid_wallet_integration_id_egp') !== false);
ypwcf1_check($checks, 'audit_model_tracks_wallet_field_name_only', strpos($audit_model_source, "'wallet_integration_id_egp'") !== false);
ypwcf1_check($checks, 'config_examples_have_wallet_placeholder_only', strpos($paymob_config_source, "'integration_id_wallet_egp' => null") !== false && strpos($paymob_local_example_source, "'integration_id_wallet_egp' => null") !== false && strpos($paymob_config_source, 'PAYMOB_INTEGRATION_ID_WALLET_EGP') !== false);
ypwcf1_check($checks, 'config_reader_has_wallet_getter_and_redacted_summary', strpos($paymob_config_reader_source, 'get_integration_id_wallet_egp') !== false && strpos($paymob_config_reader_source, "'integration_id_wallet_egp'") !== false);
ypwcf1_check($checks, 'adapter_readiness_knows_wallet_without_enabling_network', strpos($paymob_adapter_source, 'integration_id_wallet_egp_present') !== false && strpos($paymob_adapter_source, 'get_integration_id_wallet_egp') !== false);
ypwcf1_check($checks, 'root_admin_guard_still_present', strpos($controller_source, 'require_root_admin') !== false && strpos($controller_source, 'youngo_is_root_admin') !== false);
ypwcf1_check($checks, 'default_payment_and_cta_flags_disabled', strpos($paymob_config_source, "'enabled' => false") !== false && strpos($paymob_config_source, "'network_enabled' => false") !== false && strpos($paymob_config_source, "'checkout_cta_enabled' => false") !== false);
ypwcf1_check($checks, 'security_local_file_ignored', strpos($gitignore_source, 'application/config/youngo_security.local.php') !== false);

$private_form_hits = ypwcf1_scan_needles(array('settings_view' => $view_source), array(
    'name="api_key"',
    'name="public_key"',
    'name="secret_key"',
    'name="hmac_secret"',
    'name="client_secret"',
));
ypwcf1_check($checks, 'private_credentials_not_flat_post_fields', empty($private_form_hits), implode(', ', $private_form_hits));
ypwcf1_check($checks, 'credential_inputs_have_no_value_attributes', !preg_match('/name="credentials\[[^"]+\]"[^>]*\svalue=/i', $view_source));

$non_adapter_network_hits = ypwcf1_scan_needles(array(
    'settings_controller' => $controller_source,
    'settings_view' => $view_source,
    'config_model' => $model_source,
    'audit_model' => $audit_model_source,
    'paymob_config_reader' => $paymob_config_reader_source,
), array('curl_init', 'CURLOPT_', 'curl_exec', 'file_get_contents(\'http', 'file_get_contents("http', 'fsockopen', 'stream_socket_client'));
ypwcf1_check($checks, 'no_paymob_calls_added_to_dashboard_or_models', empty($non_adapter_network_hits), implode(', ', $non_adapter_network_hits));
ypwcf1_check($checks, 'no_legacy_payment_gateways_dependency_added', stripos($controller_source . "\n" . $view_source . "\n" . $model_source . "\n" . $audit_model_source, 'payment_gateways') === false);
ypwcf1_check($checks, 'public_cta_stays_gated_not_static', strpos($course_page_source, 'youngo_checkout_cta_decision') !== false && strpos($course_page_source, 'data-youngo-checkout-cta="local-course-detail"') !== false && strpos($cta_helper_source, 'is_checkout_cta_enabled') !== false && strpos($cta_helper_source, 'checkout_cta_disabled') !== false && strpos($cta_helper_source, 'youngo/checkout/start/') !== false);

$CI = new stdClass();
$CI->config = new Ypwcf1_config_stub(array('encryption_key' => 'configured_for_presence_check_only'));
$CI->session = new Ypwcf1_session_stub();
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ypwcf1_db_config(), true);
$CI->db = $db;
$config_model = new Youngo_payment_config_model();
$config_model->db = $db;
$audit_model = new Youngo_payment_config_audit_model(array('db' => $db));

$config_table = 'youngo_payment_provider_configs';
$audit_table = 'youngo_payment_config_audit_logs';
$secret_table = 'youngo_payment_provider_secret_configs';
$protected_tables = array(
    $config_table,
    $audit_table,
    $secret_table,
    'youngo_checkout_orders',
    'youngo_payment_transactions',
    'youngo_course_access',
    'payment',
    'enrol',
    'payment_gateways',
);

$baseline_counts = array();
foreach ($protected_tables as $table) {
    $baseline_counts[$table] = ypwcf1_count($db, $table);
}
$baseline_config_row = $db->table_exists($config_table)
    ? $db->where('provider', 'paymob')->where('mode', 'sandbox')->get($config_table, 1)->row_array()
    : array();
$baseline_audit_max_id = ypwcf1_max_id($db, $audit_table);

ypwcf1_check($checks, 'model_loads', $config_model instanceof Youngo_payment_config_model);
ypwcf1_check($checks, 'audit_model_loads', $audit_model instanceof Youngo_payment_config_audit_model);
ypwcf1_check($checks, 'config_schema_ready_with_wallet_column', $config_model->schema_ready() && $db->field_exists('wallet_integration_id_egp', $config_table));
ypwcf1_check($checks, 'secret_schema_still_ready', $config_model->secret_schema_ready());
ypwcf1_check($checks, 'audit_schema_ready', $audit_model->schema_ready());

$valid_payload = array(
    'mode' => 'sandbox',
    'currency' => 'EGP',
    'amount_multiplier' => '100',
    'card_integration_id_egp' => '123456',
    'wallet_integration_id_egp' => '654321',
    'api_base_url' => 'https://acceptance.paymob.example.invalid/api',
    'checkout_base_url' => 'https://acceptance.paymob.example.invalid/unifiedcheckout',
    'return_url' => 'http://school.local/youngo/checkout/return/{order_reference}',
    'notification_url' => 'https://example.invalid/payment/paymob/webhook',
);

if ($db->table_exists($config_table)) {
    $db->where('provider', 'paymob')->where('mode', 'sandbox')->delete($config_table);
}

$before_summary = $config_model->get_safe_config_summary('paymob', 'sandbox');
$db->trans_begin();
$save = $config_model->upsert_dashboard_non_private_config('paymob', 'sandbox', $valid_payload, 0);
$after_summary = $config_model->get_safe_config_summary('paymob', 'sandbox');
$audit = !empty($save['ok'])
    ? $audit_model->record_config_audit('paymob', 'sandbox', 'dashboard_non_private_save', ypwcf1_result_data($before_summary), ypwcf1_result_data($after_summary), 0)
    : array('ok' => false, 'code' => isset($save['code']) ? $save['code'] : 'save_failed');

if (!empty($save['ok']) && !empty($audit['ok']) && $db->trans_status() !== false) {
    $db->trans_commit();
} else {
    $db->trans_rollback();
}

ypwcf1_check($checks, 'card_and_wallet_non_private_save_succeeds', !empty($save['ok']), isset($save['code']) ? $save['code'] : '');

$saved = $config_model->get_provider_config('paymob', 'sandbox');
$saved_config = !empty($saved['data']['config']) && is_array($saved['data']['config']) ? $saved['data']['config'] : array();
ypwcf1_check($checks, 'saved_card_and_wallet_numeric', isset($saved_config['card_integration_id_egp'], $saved_config['wallet_integration_id_egp']) && preg_match('/^[0-9]+$/', (string) $saved_config['card_integration_id_egp']) && preg_match('/^[0-9]+$/', (string) $saved_config['wallet_integration_id_egp']));

$safe_summary = $config_model->get_safe_config_summary('paymob', 'sandbox');
$safe_data = !empty($safe_summary['data']) && is_array($safe_summary['data']) ? $safe_summary['data'] : array();
ypwcf1_check($checks, 'safe_summary_marks_card_and_wallet_configured', isset($safe_data['card_integration_id_egp'], $safe_data['wallet_integration_id_egp']) && $safe_data['card_integration_id_egp'] === 'configured_redacted' && $safe_data['wallet_integration_id_egp'] === 'configured_redacted');
ypwcf1_check($checks, 'safe_summary_has_no_private_shapes', !ypwcf1_has_private_value_shape($safe_data));

$readiness = $config_model->get_hybrid_readiness_summary('paymob', 'sandbox', array(
    'secret_key' => 'missing',
    'public_key' => 'missing',
    'hmac_secret' => 'missing',
    'api_key' => 'missing',
));
$readiness_errors = !empty($readiness['data']['readiness']['errors']) && is_array($readiness['data']['readiness']['errors'])
    ? $readiness['data']['readiness']['errors']
    : array();
ypwcf1_check($checks, 'readiness_does_not_flag_wallet_after_numeric_save', !in_array('wallet_integration_id_egp_missing_or_invalid', $readiness_errors, true));

$invalid_wallet = $config_model->upsert_dashboard_non_private_config('paymob', 'sandbox', array(
    'mode' => 'sandbox',
    'currency' => 'EGP',
    'amount_multiplier' => '100',
    'wallet_integration_id_egp' => 'WALLET-NOT-NUMERIC',
), 0);
ypwcf1_check($checks, 'invalid_wallet_id_rejected', empty($invalid_wallet['ok']) && isset($invalid_wallet['code']) && $invalid_wallet['code'] === 'invalid_wallet_integration_id_egp', isset($invalid_wallet['code']) ? $invalid_wallet['code'] : '');

$audit_id = !empty($audit['data']['audit_id']) ? (int) $audit['data']['audit_id'] : 0;
$audit_row = $audit_id > 0 ? $db->where('id', $audit_id)->get($audit_table, 1)->row_array() : array();
$audit_summary = !empty($audit_row) ? $audit_model->get_safe_audit_summary($audit_row) : array();
$changed_fields = isset($audit_summary['changed_fields']) && is_array($audit_summary['changed_fields']) ? $audit_summary['changed_fields'] : array();
ypwcf1_check($checks, 'audit_records_card_and_wallet_field_names', in_array('card_integration_id_egp', $changed_fields, true) && in_array('wallet_integration_id_egp', $changed_fields, true));
ypwcf1_check($checks, 'audit_summary_redacted', !ypwcf1_has_private_value_shape($audit_summary));

$gate_query = $db->query(
    'SELECT COALESCE(SUM(enabled + network_enabled + sandbox_network_testing_enabled + webhook_testing_enabled + checkout_routes_enabled + checkout_local_testing_enabled + checkout_cta_enabled + live_mode_allowed + transaction_inquiry_enabled), 0) AS enabled_gate_count FROM youngo_payment_provider_configs'
);
$gate_row = $gate_query ? $gate_query->row_array() : array('enabled_gate_count' => -1);
$enabled_gate_count = isset($gate_row['enabled_gate_count']) ? (int) $gate_row['enabled_gate_count'] : -1;
ypwcf1_check($checks, 'payment_network_cta_live_gates_remain_disabled', $enabled_gate_count === 0, 'enabled_gate_count=' . $enabled_gate_count);

if ($db->table_exists($audit_table)) {
    $db->where('provider', 'paymob')->where('mode', 'sandbox')->where('id >', $baseline_audit_max_id)->delete($audit_table);
}

if ($db->table_exists($config_table)) {
    $db->where('provider', 'paymob')->where('mode', 'sandbox')->delete($config_table);
    if (!empty($baseline_config_row)) {
        $db->insert($config_table, $baseline_config_row);
    }
}

$after_counts = array();
foreach ($protected_tables as $table) {
    $after_counts[$table] = ypwcf1_count($db, $table);
}

$counts_restored = $baseline_counts === $after_counts;
$details['baseline_counts'] = $baseline_counts;
$details['after_counts'] = $after_counts;
$details['baseline_paymob_config_existed'] = !empty($baseline_config_row);
$details['audit_rows_created_after_baseline'] = $db->table_exists($audit_table)
    ? (int) $db->where('provider', 'paymob')->where('mode', 'sandbox')->where('id >', $baseline_audit_max_id)->count_all_results($audit_table)
    : 0;
$details['cleanup'] = $counts_restored ? 'counts_restored' : 'cleanup_incomplete';

ypwcf1_check($checks, 'diagnostic_rows_cleaned_up', $counts_restored);
ypwcf1_check($checks, 'legacy_payment_enrol_gateway_counts_restored', $baseline_counts['payment'] === $after_counts['payment'] && $baseline_counts['enrol'] === $after_counts['enrol'] && $baseline_counts['payment_gateways'] === $after_counts['payment_gateways']);
ypwcf1_check($checks, 'encrypted_credential_save_unaffected', $baseline_counts[$secret_table] === $after_counts[$secret_table]);

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo json_encode(array(
    'phase' => 'PAYMENT.PAYMOB.WALLET.CONFIG.FIELD.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
