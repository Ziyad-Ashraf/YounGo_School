<?php
/**
 * PAYMENT.SECRETS.HYBRID.CONFIG.1 diagnostic.
 *
 * Verifies hybrid Paymob config readiness: non-private DB settings plus
 * private presence from ignored local/server config only. Uses fake
 * placeholders, prints no private values, and cleans up DB/config writes.
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
require_once APPPATH . 'models/Youngo_payment_config_model.php';

$checks = array();
$details = array(
    'db_writes' => 'one_paymob_sandbox_non_private_config_row_then_restore',
    'temporary_local_config' => 'not_started',
    'cleanup' => 'not_started',
);

function ypshc1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypshc1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypshc1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypshc1_count($db, $table)
{
    if (!$db->table_exists($table)) {
        return null;
    }

    return (int) $db->count_all($table);
}

function ypshc1_has_private_raw_value($value)
{
    if (is_array($value)) {
        foreach ($value as $child) {
            if (ypshc1_has_private_raw_value($child)) {
                return true;
            }
        }

        return false;
    }

    $value = (string) $value;
    return $value !== '' && (bool) preg_match('/(FAKE_PAYMOB_|DIAGNOSTIC_PRIVATE_VALUE|sk_live|sk_test|Bearer\s+|[A-Fa-f0-9]{64,})/', $value);
}

function ypshc1_restore_config_row($db, $baseline_row)
{
    $table = 'youngo_payment_provider_configs';
    if (!$db->table_exists($table)) {
        return;
    }

    $db->where('provider', 'paymob')->where('mode', 'sandbox')->delete($table);
    if (!empty($baseline_row) && is_array($baseline_row)) {
        $db->insert($table, $baseline_row);
    }
}

class Ypshc1_config_stub
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
    'default_config' => $root . '/application/config/youngo_paymob.php',
    'example_config' => $root . '/application/config/youngo_paymob.local.example.php',
    'local_config' => $root . '/application/config/youngo_paymob.local.php',
    'config_reader' => $root . '/application/libraries/Youngo_paymob_config.php',
    'config_model' => $root . '/application/models/Youngo_payment_config_model.php',
    'controller' => $root . '/application/controllers/Youngo_payment_settings.php',
    'view' => $root . '/application/views/backend/admin/youngo_payment_settings.php',
    'gitignore' => $root . '/.gitignore',
);

foreach ($paths as $name => $path) {
    if ($name !== 'local_config') {
        ypshc1_check($checks, 'file_exists_' . $name, is_file($path), $path);
    }
}

$default_config_source = ypshc1_read($paths['default_config']);
$example_source = ypshc1_read($paths['example_config']);
$reader_source = ypshc1_read($paths['config_reader']);
$model_source = ypshc1_read($paths['config_model']);
$controller_source = ypshc1_read($paths['controller']);
$view_source = ypshc1_read($paths['view']);
$gitignore_source = ypshc1_read($paths['gitignore']);
$changed_source = $default_config_source . "\n" . $example_source . "\n" . $reader_source . "\n" . $model_source . "\n" . $controller_source . "\n" . $view_source;

ypshc1_check($checks, 'local_override_ignored', strpos($gitignore_source, 'application/config/youngo_paymob.local.php') !== false);
ypshc1_check($checks, 'default_gates_disabled', strpos($default_config_source, "'enabled' => false") !== false && strpos($default_config_source, "'network_enabled' => false") !== false && strpos($default_config_source, "'checkout_cta_enabled' => false") !== false);
ypshc1_check($checks, 'sandbox_network_gate_default_false', strpos($default_config_source, "'sandbox_network_testing_enabled' => false") !== false);
ypshc1_check($checks, 'example_has_private_placeholders_only', strpos($example_source, 'PAYMOB_SECRET_KEY') !== false && strpos($example_source, 'PAYMOB_PUBLIC_KEY') !== false && strpos($example_source, 'PAYMOB_HMAC_SECRET') !== false);
ypshc1_check($checks, 'reader_has_runtime_getters', strpos($reader_source, 'get_secret_key_for_runtime') !== false && strpos($reader_source, 'get_hmac_secret_for_runtime') !== false);
ypshc1_check($checks, 'model_has_hybrid_readiness', strpos($model_source, 'get_hybrid_readiness_summary') !== false && strpos($model_source, 'get_safe_hybrid_config_summary') !== false);
ypshc1_check($checks, 'controller_uses_hybrid_readiness', strpos($controller_source, 'get_hybrid_readiness_summary') !== false && strpos($controller_source, 'Youngo_paymob_config') !== false);
ypshc1_check($checks, 'dashboard_shows_hybrid_storage', strpos($view_source, 'Hybrid Storage') !== false && strpos($view_source, 'server_config_required') !== false);
ypshc1_check($checks, 'dashboard_has_no_private_inputs', strpos($view_source, 'name="secret_key"') === false && strpos($view_source, 'name="hmac_secret"') === false && strpos($view_source, 'name="api_key"') === false && strpos($view_source, 'type="password"') === false);
ypshc1_check($checks, 'no_paymob_network_calls_added', strpos($changed_source, 'curl_exec') === false && !preg_match('/file_get_contents\s*\(\s*[\'"]http/i', $changed_source));
ypshc1_check($checks, 'no_legacy_payment_gateways_dependency', stripos($changed_source, 'payment_gateways') === false);

$CI = new stdClass();
$CI->config = new Ypshc1_config_stub(array('encryption_key' => ''));
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ypshc1_db_config(), true);
$CI->db = $db;
$model = new Youngo_payment_config_model();
$model->db = $db;

$config_table = 'youngo_payment_provider_configs';
$baseline_counts = array(
    $config_table => ypshc1_count($db, $config_table),
    'payment_gateways' => ypshc1_count($db, 'payment_gateways'),
    'payment' => ypshc1_count($db, 'payment'),
    'enrol' => ypshc1_count($db, 'enrol'),
);
$baseline_config_row = $db
    ->where('provider', 'paymob')
    ->where('mode', 'sandbox')
    ->get($config_table, 1)
    ->row_array();

$created_local_config = false;
try {
    ypshc1_check($checks, 'model_loads', $model instanceof Youngo_payment_config_model);
    ypshc1_check($checks, 'schema_ready', $model->schema_ready());
    ypshc1_check($checks, 'encryption_key_empty', !$model->encryption_key_is_ready());

    $payload = array(
        'mode' => 'sandbox',
        'currency' => 'EGP',
        'amount_multiplier' => '100',
        'card_integration_id_egp' => '987654',
        'api_base_url' => 'https://acceptance.paymob.example.invalid/api',
        'checkout_base_url' => 'https://acceptance.paymob.example.invalid/unifiedcheckout',
        'return_url' => 'http://school.local/youngo/checkout/return/{order_reference}',
        'notification_url' => 'https://example.invalid/payment/paymob/webhook',
    );
    $save = $model->upsert_dashboard_non_private_config('paymob', 'sandbox', $payload, 0);
    ypshc1_check($checks, 'non_private_db_save_works', !empty($save['ok']), isset($save['code']) ? $save['code'] : '');

    $private_save = $model->upsert_dashboard_non_private_config('paymob', 'sandbox', array(
        'mode' => 'sandbox',
        'currency' => 'EGP',
        'amount_multiplier' => '100',
        'secret_key' => 'DIAGNOSTIC_PRIVATE_VALUE_SHOULD_NOT_STORE',
    ), 0);
    ypshc1_check($checks, 'private_db_save_rejected', empty($private_save['ok']) && $private_save['code'] === 'blocked_fields_rejected', isset($private_save['code']) ? $private_save['code'] : '');

    if (is_file($paths['local_config'])) {
        $details['temporary_local_config'] = 'skipped_existing_ignored_config_not_touched';
        $reader = new Youngo_paymob_config(array('load_local_override' => true));
    } else {
        $local_config = <<<'PHP'
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['youngo_paymob_local'] = array(
    'enabled' => false,
    'mode' => 'sandbox',
    'currency' => 'EGP',
    'amount_multiplier' => 100,
    'network_enabled' => false,
    'sandbox_network_testing_enabled' => false,
    'webhook_testing_enabled' => false,
    'checkout_routes_enabled' => false,
    'checkout_local_testing_enabled' => false,
    'checkout_cta_enabled' => false,
    'live_mode_allowed' => false,
    'secret_key' => 'FAKE_PAYMOB_SECRET_PLACEHOLDER',
    'public_key' => 'FAKE_PAYMOB_PUBLIC_PLACEHOLDER',
    'hmac_secret' => 'FAKE_PAYMOB_HMAC_PLACEHOLDER',
);
PHP;
        file_put_contents($paths['local_config'], $local_config);
        $created_local_config = true;
        $details['temporary_local_config'] = 'created_fake_ignored_config';
        $reader = new Youngo_paymob_config(array('load_local_override' => true));
    }

    $safe_reader_summary = $reader->get_safe_diagnostic_summary();
    $private_presence = $reader->get_private_presence_summary();
    ypshc1_check($checks, 'fake_ignored_private_presence_detected', !empty($private_presence['local_override_loaded']) && $private_presence['secret_key'] === 'configured_redacted' && $private_presence['public_key'] === 'configured_redacted' && $private_presence['hmac_secret'] === 'configured_redacted');
    ypshc1_check($checks, 'safe_reader_summary_redacts_raw_values', !ypshc1_has_private_raw_value($safe_reader_summary));

    $hybrid_summary = $model->get_safe_hybrid_config_summary('paymob', 'sandbox', $safe_reader_summary);
    $hybrid_readiness = $model->get_hybrid_readiness_summary('paymob', 'sandbox', $safe_reader_summary);
    $hybrid_data = !empty($hybrid_summary['data']) ? $hybrid_summary['data'] : array();
    $readiness_data = !empty($hybrid_readiness['data']['readiness']) ? $hybrid_readiness['data']['readiness'] : array();

    ypshc1_check($checks, 'hybrid_summary_loads', !empty($hybrid_summary['ok']) && isset($hybrid_data['storage_mode']) && $hybrid_data['storage_mode'] === 'hybrid');
    ypshc1_check($checks, 'hybrid_private_db_storage_blocked', isset($hybrid_data['private_db_storage']) && $hybrid_data['private_db_storage'] === 'db_private_storage_blocked');
    ypshc1_check($checks, 'hybrid_summary_redacts_raw_values', !ypshc1_has_private_raw_value($hybrid_summary));
    ypshc1_check($checks, 'hybrid_readiness_reports_disabled', !empty($hybrid_readiness['ok']) && !empty($readiness_data['storage_mode']) && empty($readiness_data['ready_for_network']) && empty($readiness_data['ready_for_cta']));
    ypshc1_check($checks, 'hybrid_readiness_redacts_raw_values', !ypshc1_has_private_raw_value($hybrid_readiness));

    $config = $model->get_provider_config('paymob', 'sandbox');
    $row = !empty($config['data']['config']) ? $config['data']['config'] : array();
    ypshc1_check(
        $checks,
        'gates_remain_disabled',
        empty($row['enabled'])
        && empty($row['network_enabled'])
        && empty($row['sandbox_network_testing_enabled'])
        && empty($row['webhook_testing_enabled'])
        && empty($row['checkout_routes_enabled'])
        && empty($row['checkout_cta_enabled'])
        && empty($row['live_mode_allowed'])
    );
} finally {
    ypshc1_restore_config_row($db, $baseline_config_row);
    if ($created_local_config && is_file($paths['local_config'])) {
        unlink($paths['local_config']);
    }
}

$after_counts = array(
    $config_table => ypshc1_count($db, $config_table),
    'payment_gateways' => ypshc1_count($db, 'payment_gateways'),
    'payment' => ypshc1_count($db, 'payment'),
    'enrol' => ypshc1_count($db, 'enrol'),
);

$details['cleanup'] = 'counts_restored_and_temporary_config_removed';
$details['baseline_counts'] = $baseline_counts;
$details['after_counts'] = $after_counts;
$details['baseline_paymob_config_existed'] = !empty($baseline_config_row);

ypshc1_check($checks, 'cleanup_restored_counts', $baseline_counts === $after_counts, json_encode($after_counts));
ypshc1_check($checks, 'temporary_local_config_removed', !$created_local_config || !is_file($paths['local_config']));

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[] = $name;
    }
}

echo json_encode(array(
    'phase' => 'PAYMENT.SECRETS.HYBRID.CONFIG.1',
    'ok' => empty($failed),
    'failed_checks' => $failed,
    'checks' => $checks,
    'details' => $details,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
