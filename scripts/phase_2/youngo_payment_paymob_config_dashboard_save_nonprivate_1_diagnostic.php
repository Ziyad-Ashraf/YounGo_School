<?php
/**
 * PAYMENT.PAYMOB.CONFIG.DASHBOARD.SAVE.NONPRIVATE.1 diagnostic.
 *
 * Verifies Root-only non-private Paymob dashboard save foundation.
 * Writes one controlled local config row through Youngo_payment_config_model
 * and restores the previous row state afterward.
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
    'db_writes' => 'one_paymob_sandbox_non_private_diagnostic_row_save_then_restore',
    'cleanup' => 'not_started',
);

function ypcdsn1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypcdsn1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypcdsn1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypcdsn1_count($db, $table)
{
    if (!$db->table_exists($table)) {
        return null;
    }

    return (int) $db->count_all($table);
}

function ypcdsn1_contains_any($source, $needles)
{
    foreach ($needles as $needle) {
        if (stripos($source, $needle) !== false) {
            return true;
        }
    }

    return false;
}

function ypcdsn1_array_has_secret_shape($value)
{
    if (is_array($value)) {
        foreach ($value as $child) {
            if (ypcdsn1_array_has_secret_shape($child)) {
                return true;
            }
        }

        return false;
    }

    $value = (string) $value;
    if ($value === '') {
        return false;
    }

    return (bool) preg_match('/(sk_live|sk_test|Bearer\\s+|[A-Fa-f0-9]{64,})/', $value);
}

class Ypcdsn1_config_stub
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
    'controller' => $root . '/application/controllers/Youngo_payment_settings.php',
    'view' => $root . '/application/views/backend/admin/youngo_payment_settings.php',
    'model' => $root . '/application/models/Youngo_payment_config_model.php',
    'routes' => $root . '/application/config/routes.php',
    'navigation' => $root . '/application/views/backend/admin/navigation.php',
    'config_php' => $root . '/application/config/config.php',
);

foreach ($paths as $name => $path) {
    ypcdsn1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

$controller = ypcdsn1_read($paths['controller']);
$view = ypcdsn1_read($paths['view']);
$model_source = ypcdsn1_read($paths['model']);
$routes = ypcdsn1_read($paths['routes']);
$navigation = ypcdsn1_read($paths['navigation']);
$source = $controller . "\n" . $view . "\n" . $model_source;

ypcdsn1_check($checks, 'route_exists', strpos($routes, "\$route['admin/youngo/payment-settings'] = 'youngo_payment_settings/index';") !== false);
ypcdsn1_check($checks, 'controller_root_only_marker', strpos($controller, 'require_root_admin') !== false && strpos($controller, 'youngo_is_root_admin') !== false);
ypcdsn1_check($checks, 'navigation_root_only_marker', strpos($navigation, '$can_view_youngo_payment_settings') !== false && strpos($navigation, 'youngo_is_root_admin') !== false);
ypcdsn1_check($checks, 'controller_uses_model_save_wrapper', strpos($controller, 'upsert_dashboard_non_private_config') !== false);
ypcdsn1_check($checks, 'form_exists', strpos($view, '<form') !== false && strpos($view, 'Save non-private settings') !== false);
ypcdsn1_check($checks, 'allowed_form_fields_present', strpos($view, 'name="mode"') !== false && strpos($view, 'name="currency"') !== false && strpos($view, 'name="amount_multiplier"') !== false && strpos($view, 'name="card_integration_id_egp"') !== false && strpos($view, 'name="wallet_integration_id_egp"') !== false && strpos($view, 'name="notification_url"') !== false);
ypcdsn1_check($checks, 'no_flat_private_form_inputs', !ypcdsn1_contains_any($view, array('name="secret_key"', 'name="hmac_secret"', 'name="api_key"', 'name="client_secret"', 'name="public_key"')));
ypcdsn1_check($checks, 'credential_form_is_separate_from_non_private_save', strpos($view, 'data-youngo-paymob-secret-form="true"') !== false && strpos($view, 'name="credentials[') !== false && strpos($view, 'data-youngo-paymob-non-private-form="true"') !== false);
ypcdsn1_check($checks, 'model_has_strict_dashboard_save_wrapper', strpos($model_source, 'upsert_dashboard_non_private_config') !== false && strpos($model_source, 'blocked_fields_rejected') !== false);
ypcdsn1_check($checks, 'no_paymob_network_call_patterns', !ypcdsn1_contains_any($source, array('curl_', 'file_get_contents("http', "file_get_contents('http", 'fsockopen', 'Guzzle', 'create_intention', 'Create Intention')));
ypcdsn1_check($checks, 'no_legacy_payment_gateways_dependency', stripos($source, 'payment_gateways') === false);
ypcdsn1_check($checks, 'encryption_key_empty', strpos(ypcdsn1_read($paths['config_php']), "\$config['encryption_key'] = '';") !== false);

$CI = new stdClass();
$CI->config = new Ypcdsn1_config_stub(array('encryption_key' => ''));
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ypcdsn1_db_config(), true);
$CI->db = $db;
$model = new Youngo_payment_config_model();
$model->db = $db;

$table = 'youngo_payment_provider_configs';
$baseline_counts = array(
    $table => ypcdsn1_count($db, $table),
    'payment_gateways' => ypcdsn1_count($db, 'payment_gateways'),
    'payment' => ypcdsn1_count($db, 'payment'),
    'enrol' => ypcdsn1_count($db, 'enrol'),
);

$baseline_row = $db
    ->where('provider', 'paymob')
    ->where('mode', 'sandbox')
    ->get($table, 1)
    ->row_array();

ypcdsn1_check($checks, 'model_loads', $model instanceof Youngo_payment_config_model);
ypcdsn1_check($checks, 'schema_ready', $model->schema_ready());

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

$save = $model->upsert_dashboard_non_private_config('paymob', 'sandbox', $valid_payload, 0);
ypcdsn1_check($checks, 'non_private_upsert_works', !empty($save['ok']), isset($save['code']) ? $save['code'] : '');

$saved = $model->get_provider_config('paymob', 'sandbox');
if (!empty($saved['ok']) && !empty($saved['data']['config'])) {
    $config = $saved['data']['config'];
    ypcdsn1_check($checks, 'saved_currency_egp', isset($config['currency']) && $config['currency'] === 'EGP');
    ypcdsn1_check($checks, 'saved_mode_sandbox', isset($config['mode']) && $config['mode'] === 'sandbox');
    ypcdsn1_check($checks, 'saved_wallet_integration_id_numeric', isset($config['wallet_integration_id_egp']) && preg_match('/^[0-9]+$/', (string) $config['wallet_integration_id_egp']));
    ypcdsn1_check($checks, 'gates_remain_disabled_after_save', empty($config['enabled']) && empty($config['network_enabled']) && empty($config['sandbox_network_testing_enabled']) && empty($config['webhook_testing_enabled']) && empty($config['checkout_routes_enabled']) && empty($config['checkout_cta_enabled']) && empty($config['live_mode_allowed']));
}

$invalid_cases = array(
    'reject_live_mode' => array('mode' => 'live', 'currency' => 'EGP', 'amount_multiplier' => '100'),
    'reject_usd' => array('mode' => 'sandbox', 'currency' => 'USD', 'amount_multiplier' => '100'),
    'reject_bad_multiplier' => array('mode' => 'sandbox', 'currency' => 'EGP', 'amount_multiplier' => '1'),
    'reject_bad_integration_id' => array('mode' => 'sandbox', 'currency' => 'EGP', 'amount_multiplier' => '100', 'card_integration_id_egp' => 'ABC123'),
    'reject_bad_wallet_integration_id' => array('mode' => 'sandbox', 'currency' => 'EGP', 'amount_multiplier' => '100', 'wallet_integration_id_egp' => 'WALLET123'),
    'reject_bad_url' => array('mode' => 'sandbox', 'currency' => 'EGP', 'amount_multiplier' => '100', 'api_base_url' => 'ftp://example.invalid'),
    'reject_secret_key' => array('mode' => 'sandbox', 'currency' => 'EGP', 'amount_multiplier' => '100', 'secret_key' => 'DIAGNOSTIC_BLOCKED_VALUE'),
    'reject_public_key_this_phase' => array('mode' => 'sandbox', 'currency' => 'EGP', 'amount_multiplier' => '100', 'public_key' => 'DIAGNOSTIC_BLOCKED_PUBLIC_VALUE'),
    'reject_activation_gate' => array('mode' => 'sandbox', 'currency' => 'EGP', 'amount_multiplier' => '100', 'enabled' => '1'),
);

foreach ($invalid_cases as $name => $payload) {
    $result = $model->upsert_dashboard_non_private_config('paymob', 'sandbox', $payload, 0);
    ypcdsn1_check($checks, $name, empty($result['ok']), isset($result['code']) ? $result['code'] : '');
}

$summary = $model->get_safe_config_summary('paymob', 'sandbox');
ypcdsn1_check($checks, 'safe_summary_loads', !empty($summary['ok']) && isset($summary['data']));
if (!empty($summary['data'])) {
    ypcdsn1_check($checks, 'safe_summary_redacts_private_fields', isset($summary['data']['secret_key']) && in_array($summary['data']['secret_key'], array('missing', 'configured_redacted'), true) && isset($summary['data']['hmac_secret']) && in_array($summary['data']['hmac_secret'], array('missing', 'configured_redacted'), true));
    ypcdsn1_check($checks, 'safe_summary_no_secret_shapes', !ypcdsn1_array_has_secret_shape($summary['data']));
}

$db->where('provider', 'paymob')->where('mode', 'sandbox')->delete($table);
if (!empty($baseline_row)) {
    $restore = $db->insert($table, $baseline_row);
    $details['cleanup'] = $restore ? 'paymob_sandbox_config_row_restored' : 'restore_failed';
    ypcdsn1_check($checks, 'paymob_config_row_restored', (bool) $restore);
} else {
    $details['cleanup'] = 'diagnostic_paymob_sandbox_config_row_deleted';
    ypcdsn1_check($checks, 'diagnostic_paymob_config_row_deleted', true);
}

$after_counts = array(
    $table => ypcdsn1_count($db, $table),
    'payment_gateways' => ypcdsn1_count($db, 'payment_gateways'),
    'payment' => ypcdsn1_count($db, 'payment'),
    'enrol' => ypcdsn1_count($db, 'enrol'),
);

ypcdsn1_check($checks, 'table_counts_restored', $baseline_counts === $after_counts);
ypcdsn1_check($checks, 'legacy_payment_tables_unchanged', $baseline_counts['payment_gateways'] === $after_counts['payment_gateways'] && $baseline_counts['payment'] === $after_counts['payment'] && $baseline_counts['enrol'] === $after_counts['enrol']);

$details['baseline_counts'] = $baseline_counts;
$details['after_counts'] = $after_counts;
$details['baseline_paymob_config_existed'] = !empty($baseline_row);

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

$result = array(
    'phase' => 'PAYMENT.PAYMOB.CONFIG.DASHBOARD.SAVE.NONPRIVATE.1',
    'ok' => empty($failed),
    'failed_checks' => $failed,
    'checks' => $checks,
    'details' => $details,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(empty($failed) ? 0 : 1);
