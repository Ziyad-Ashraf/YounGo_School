<?php
/**
 * PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.WIRE.1 diagnostic.
 *
 * Verifies the non-private Paymob dashboard save path is wired to redacted
 * audit logging. Uses fake placeholder values only and cleans up all test rows.
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
    'db_writes' => 'one_paymob_sandbox_config_save_plus_one_redacted_audit_row_then_restore',
    'cleanup' => 'not_started',
);

function ypcaw1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypcaw1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypcaw1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypcaw1_count($db, $table)
{
    if (!$db->table_exists($table)) {
        return null;
    }

    return (int) $db->count_all($table);
}

function ypcaw1_array_has_private_value_shape($value)
{
    if (is_array($value)) {
        foreach ($value as $child) {
            if (ypcaw1_array_has_private_value_shape($child)) {
                return true;
            }
        }

        return false;
    }

    $value = (string) $value;
    return $value !== '' && (bool) preg_match('/(DIAGNOSTIC_PRIVATE_VALUE|Bearer\s+|[A-Fa-f0-9]{64,})/', $value);
}

function ypcaw1_result_data($result)
{
    return is_array($result) && !empty($result['ok']) && isset($result['data']) && is_array($result['data'])
        ? $result['data']
        : array();
}

class Ypcaw1_config_stub
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

class Ypcaw1_session_stub
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
);

foreach ($paths as $name => $path) {
    ypcaw1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

$controller = ypcaw1_read($paths['controller']);
$view = ypcaw1_read($paths['view']);
$source = $controller . "\n" . $view . "\n" . ypcaw1_read($paths['config_model']) . "\n" . ypcaw1_read($paths['audit_model']);

ypcaw1_check($checks, 'controller_loads_audit_model', strpos($controller, "Youngo_payment_config_audit_model") !== false);
ypcaw1_check($checks, 'controller_captures_before_after_summary', strpos($controller, '$before_summary') !== false && strpos($controller, '$after_summary') !== false);
ypcaw1_check($checks, 'controller_records_audit_after_success', strpos($controller, 'record_config_audit') !== false && strpos($controller, 'dashboard_non_private_save') !== false);
ypcaw1_check($checks, 'controller_uses_transaction_for_save_and_audit', strpos($controller, 'trans_begin') !== false && strpos($controller, 'trans_commit') !== false && strpos($controller, 'trans_rollback') !== false);
ypcaw1_check($checks, 'dashboard_recent_audit_panel_present', strpos($view, 'Recent Configuration Audit') !== false && strpos($view, 'changed_fields') !== false);
ypcaw1_check($checks, 'no_flat_private_form_inputs', strpos($view, 'name="secret_key"') === false && strpos($view, 'name="hmac_secret"') === false && strpos($view, 'name="api_key"') === false && strpos($view, 'name="public_key"') === false);
ypcaw1_check($checks, 'credential_form_is_separate_from_non_private_audit_flow', strpos($view, 'data-youngo-paymob-secret-form="true"') !== false && strpos($view, 'name="credentials[') !== false && strpos($view, 'data-youngo-paymob-non-private-form="true"') !== false);
ypcaw1_check($checks, 'no_legacy_payment_gateways_dependency', stripos($source, 'payment_gateways') === false);
ypcaw1_check($checks, 'no_paymob_network_call_patterns', strpos($source, 'curl_exec') === false && !preg_match('/file_get_contents\s*\(\s*[\'"]http/i', $source));

$CI = new stdClass();
$CI->config = new Ypcaw1_config_stub(array('encryption_key' => ''));
$CI->session = new Ypcaw1_session_stub();
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ypcaw1_db_config(), true);
$CI->db = $db;
$config_model = new Youngo_payment_config_model();
$config_model->db = $db;
$audit_model = new Youngo_payment_config_audit_model(array('db' => $db));

$config_table = 'youngo_payment_provider_configs';
$audit_table = 'youngo_payment_config_audit_logs';
$baseline_counts = array(
    $config_table => ypcaw1_count($db, $config_table),
    $audit_table => ypcaw1_count($db, $audit_table),
    'payment_gateways' => ypcaw1_count($db, 'payment_gateways'),
    'payment' => ypcaw1_count($db, 'payment'),
    'enrol' => ypcaw1_count($db, 'enrol'),
);

$baseline_config_row = $db
    ->where('provider', 'paymob')
    ->where('mode', 'sandbox')
    ->get($config_table, 1)
    ->row_array();

ypcaw1_check($checks, 'config_model_loads', $config_model instanceof Youngo_payment_config_model);
ypcaw1_check($checks, 'audit_model_loads', $audit_model instanceof Youngo_payment_config_audit_model);
ypcaw1_check($checks, 'config_schema_ready', $config_model->schema_ready());
ypcaw1_check($checks, 'audit_schema_ready', $audit_model->schema_ready());

$payload = array(
    'mode' => 'sandbox',
    'currency' => 'EGP',
    'amount_multiplier' => '100',
    'card_integration_id_egp' => '987654',
    'wallet_integration_id_egp' => '456789',
    'api_base_url' => 'https://acceptance.paymob.example.invalid/api',
    'checkout_base_url' => 'https://acceptance.paymob.example.invalid/unifiedcheckout',
    'return_url' => 'http://school.local/youngo/checkout/return/{order_reference}',
    'notification_url' => 'https://example.invalid/payment/paymob/webhook',
);

$before_summary = $config_model->get_safe_config_summary('paymob', 'sandbox');
$db->trans_begin();
$save = $config_model->upsert_dashboard_non_private_config('paymob', 'sandbox', $payload, 0);
$after_summary = $config_model->get_safe_config_summary('paymob', 'sandbox');
$audit = !empty($save['ok'])
    ? $audit_model->record_config_audit('paymob', 'sandbox', 'dashboard_non_private_save', ypcaw1_result_data($before_summary), ypcaw1_result_data($after_summary), 0)
    : array('ok' => false, 'code' => 'save_failed');

if (!empty($save['ok']) && !empty($audit['ok']) && $db->trans_status() !== false) {
    $db->trans_commit();
} else {
    $db->trans_rollback();
}

ypcaw1_check($checks, 'valid_non_private_save_works', !empty($save['ok']), isset($save['code']) ? $save['code'] : '');
ypcaw1_check($checks, 'valid_save_creates_audit_row', !empty($audit['ok']), isset($audit['code']) ? $audit['code'] : '');
$audit_id = !empty($audit['data']['audit_id']) ? (int) $audit['data']['audit_id'] : 0;

$audit_row = $audit_id > 0 ? $db->where('id', $audit_id)->get($audit_table, 1)->row_array() : array();
$audit_summary = !empty($audit_row) ? $audit_model->get_safe_audit_summary($audit_row) : array();
ypcaw1_check($checks, 'audit_row_action_correct', !empty($audit_summary['action']) && $audit_summary['action'] === 'dashboard_non_private_save');
ypcaw1_check($checks, 'audit_row_changed_fields_recorded', !empty($audit_summary['changed_fields']) && in_array('card_integration_id_egp', $audit_summary['changed_fields'], true) && in_array('wallet_integration_id_egp', $audit_summary['changed_fields'], true));
ypcaw1_check($checks, 'audit_row_redacted', !ypcaw1_array_has_private_value_shape($audit_summary));

$audit_count_after_valid = ypcaw1_count($db, $audit_table);
$invalid = $config_model->upsert_dashboard_non_private_config('paymob', 'sandbox', array(
    'mode' => 'live',
    'currency' => 'EGP',
    'amount_multiplier' => '100',
), 0);
ypcaw1_check($checks, 'invalid_save_rejected', empty($invalid['ok']) && $invalid['code'] === 'sandbox_only_in_this_phase');
ypcaw1_check($checks, 'invalid_save_no_success_audit', ypcaw1_count($db, $audit_table) === $audit_count_after_valid);

$private_attempt = $config_model->upsert_dashboard_non_private_config('paymob', 'sandbox', array(
    'mode' => 'sandbox',
    'currency' => 'EGP',
    'amount_multiplier' => '100',
    'secret_key' => 'DIAGNOSTIC_PRIVATE_VALUE_SHOULD_NOT_STORE',
), 0);
ypcaw1_check($checks, 'private_field_attempt_rejected', empty($private_attempt['ok']) && $private_attempt['code'] === 'blocked_fields_rejected');
ypcaw1_check($checks, 'private_field_attempt_no_success_audit', ypcaw1_count($db, $audit_table) === $audit_count_after_valid);

$raw_private_query = $db->query(
    'SELECT COUNT(*) AS count FROM `' . $audit_table . '` WHERE before_summary_json LIKE ? OR after_summary_json LIKE ? OR secret_presence_changes_json LIKE ?',
    array('%DIAGNOSTIC_PRIVATE_VALUE%', '%DIAGNOSTIC_PRIVATE_VALUE%', '%DIAGNOSTIC_PRIVATE_VALUE%')
);
$raw_private_row = $raw_private_query ? $raw_private_query->row_array() : array('count' => 1);
ypcaw1_check($checks, 'private_raw_value_not_stored', empty($raw_private_row['count']));

$recent = $audit_model->get_recent_audit_logs('paymob', 'sandbox', 5);
ypcaw1_check($checks, 'dashboard_can_read_recent_audit_safely', !empty($recent['ok']) && !ypcaw1_array_has_private_value_shape($recent['data']));

$saved_config = $config_model->get_provider_config('paymob', 'sandbox');
$config = !empty($saved_config['data']['config']) ? $saved_config['data']['config'] : array();
ypcaw1_check(
    $checks,
    'gates_remain_disabled',
    empty($config['enabled'])
    && empty($config['network_enabled'])
    && empty($config['sandbox_network_testing_enabled'])
    && empty($config['webhook_testing_enabled'])
    && empty($config['checkout_routes_enabled'])
    && empty($config['checkout_cta_enabled'])
    && empty($config['live_mode_allowed'])
);

if ($audit_id > 0) {
    $db->where('id', $audit_id)->delete($audit_table);
}

$db->where('provider', 'paymob')->where('mode', 'sandbox')->delete($config_table);
if (!empty($baseline_config_row)) {
    $db->insert($config_table, $baseline_config_row);
}

$after_counts = array(
    $config_table => ypcaw1_count($db, $config_table),
    $audit_table => ypcaw1_count($db, $audit_table),
    'payment_gateways' => ypcaw1_count($db, 'payment_gateways'),
    'payment' => ypcaw1_count($db, 'payment'),
    'enrol' => ypcaw1_count($db, 'enrol'),
);

$details['baseline_counts'] = $baseline_counts;
$details['after_counts'] = $after_counts;
$details['cleanup'] = $baseline_counts === $after_counts ? 'counts_restored' : 'cleanup_incomplete';
$details['baseline_paymob_config_existed'] = !empty($baseline_config_row);

ypcaw1_check($checks, 'cleanup_restored_counts', $baseline_counts === $after_counts, json_encode($after_counts));
ypcaw1_check($checks, 'payment_gateways_unchanged', $baseline_counts['payment_gateways'] === $after_counts['payment_gateways']);
ypcaw1_check($checks, 'legacy_payment_enrol_unchanged', $baseline_counts['payment'] === $after_counts['payment'] && $baseline_counts['enrol'] === $after_counts['enrol']);

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

$result = array(
    'phase' => 'PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.WIRE.1',
    'ok' => empty($failed),
    'failed_checks' => $failed,
    'checks' => $checks,
    'details' => $details,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(empty($failed) ? 0 : 1);
