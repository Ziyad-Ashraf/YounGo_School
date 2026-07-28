<?php
/**
 * PAYMENT.PAYMOB.CONFIG.DASHBOARD.UI.1 diagnostic.
 *
 * Verifies the read-only Paymob dashboard summary page wiring and confirms
 * the config model returns redacted readiness data without DB writes.
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
    'dashboard_route' => 'admin/youngo/payment-settings',
    'ui_mode' => 'non_private_save_allowed_after_PAYMENT.PAYMOB.CONFIG.DASHBOARD.SAVE.NONPRIVATE.1',
);

function ypcdui1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypcdui1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypcdui1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypcdui1_count($db, $table)
{
    if (!$db->table_exists($table)) {
        return null;
    }

    return (int) $db->count_all($table);
}

function ypcdui1_contains_any($source, $needles)
{
    foreach ($needles as $needle) {
        if (stripos($source, $needle) !== false) {
            return true;
        }
    }

    return false;
}

function ypcdui1_array_has_private_value($value)
{
    if (is_array($value)) {
        foreach ($value as $child) {
            if (ypcdui1_array_has_private_value($child)) {
                return true;
            }
        }

        return false;
    }

    $value = (string) $value;
    if ($value === '') {
        return false;
    }

    return (bool) preg_match('/(sk_live|sk_test|pk_live_[A-Za-z0-9]{12,}|Bearer\\s+|[A-Fa-f0-9]{64,})/', $value);
}

class Ypcdui1_config_stub
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
    'routes' => $root . '/application/config/routes.php',
    'navigation' => $root . '/application/views/backend/admin/navigation.php',
    'model' => $root . '/application/models/Youngo_payment_config_model.php',
);

foreach ($paths as $name => $path) {
    ypcdui1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

$controller = ypcdui1_read($paths['controller']);
$view = ypcdui1_read($paths['view']);
$routes = ypcdui1_read($paths['routes']);
$navigation = ypcdui1_read($paths['navigation']);
$model_source = ypcdui1_read($paths['model']);
$ui_source = $controller . "\n" . $view;

ypcdui1_check($checks, 'route_exists', strpos($routes, "\$route['admin/youngo/payment-settings'] = 'youngo_payment_settings/index';") !== false);
ypcdui1_check($checks, 'navigation_entry_exists', strpos($navigation, "site_url('admin/youngo/payment-settings')") !== false);
ypcdui1_check($checks, 'navigation_root_guard_exists', strpos($navigation, 'youngo_is_root_admin') !== false && strpos($navigation, '$can_view_youngo_payment_settings') !== false);
ypcdui1_check($checks, 'controller_admin_session_guard', strpos($controller, "check_session_data('admin')") !== false);
ypcdui1_check($checks, 'controller_root_guard', strpos($controller, 'require_root_admin') !== false && strpos($controller, 'youngo_is_root_admin') !== false);
ypcdui1_check($checks, 'controller_get_or_approved_post_only', strpos($controller, "\$this->request_method() === 'POST'") !== false && strpos($controller, 'upsert_dashboard_non_private_config') !== false && strpos($controller, "\$this->request_method() !== 'GET'") !== false && strpos($controller, 'set_status_header(405)') !== false);
ypcdui1_check($checks, 'view_declares_secret_network_disabled', strpos($view, 'Secret entry') !== false && strpos($view, 'not enabled yet') !== false && strpos($view, 'activation') !== false);
ypcdui1_check($checks, 'approved_non_private_form_exists', strpos($view, '<form') !== false && strpos($view, 'Save non-private settings') !== false);
ypcdui1_check($checks, 'no_private_inputs', !ypcdui1_contains_any($view, array('type="password"', 'name="secret_key"', 'name="hmac_secret"', 'name="api_key"', 'name="client_secret"', 'name="public_key"')));
ypcdui1_check($checks, 'no_direct_db_write_in_controller', !ypcdui1_contains_any($controller, array('->insert(', '->update(', '->delete(')));
ypcdui1_check($checks, 'no_paymob_network_calls', !ypcdui1_contains_any($ui_source, array('curl_', 'file_get_contents("http', "file_get_contents('http", 'fsockopen', 'Guzzle', 'create_intention', 'Create Intention')));
ypcdui1_check($checks, 'no_legacy_payment_gateways_dependency', stripos($ui_source, 'payment_gateways') === false);
ypcdui1_check($checks, 'model_safe_summary_available', strpos($model_source, 'get_safe_config_summary') !== false && strpos($model_source, 'configured_redacted') !== false);

$CI = new stdClass();
$CI->config = new Ypcdui1_config_stub(array('encryption_key' => ''));
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ypcdui1_db_config(), true);
$CI->db = $db;
$model = new Youngo_payment_config_model();
$model->db = $db;

$baseline_counts = array(
    'youngo_payment_provider_configs' => ypcdui1_count($db, 'youngo_payment_provider_configs'),
    'payment_gateways' => ypcdui1_count($db, 'payment_gateways'),
    'payment' => ypcdui1_count($db, 'payment'),
    'enrol' => ypcdui1_count($db, 'enrol'),
);

$summary = $model->get_safe_config_summary('paymob', 'sandbox');
$readiness = $model->get_readiness_summary('paymob', 'sandbox');

ypcdui1_check($checks, 'model_loads', $model instanceof Youngo_payment_config_model);
ypcdui1_check($checks, 'safe_config_summary_loads', !empty($summary['ok']) && isset($summary['data']));
ypcdui1_check($checks, 'readiness_summary_loads', !empty($readiness['ok']) && isset($readiness['data']['readiness']));

if (!empty($summary['data'])) {
    $private_status_only = isset($summary['data']['secret_key'], $summary['data']['hmac_secret'], $summary['data']['api_key'])
        && in_array($summary['data']['secret_key'], array('missing', 'configured_redacted'), true)
        && in_array($summary['data']['hmac_secret'], array('missing', 'configured_redacted'), true)
        && in_array($summary['data']['api_key'], array('missing', 'configured_redacted'), true);
    ypcdui1_check($checks, 'private_fields_status_only', $private_status_only);
    ypcdui1_check($checks, 'safe_summary_no_private_value_shapes', !ypcdui1_array_has_private_value($summary['data']));
    ypcdui1_check($checks, 'default_payment_flags_disabled', empty($summary['data']['enabled']) && empty($summary['data']['network_enabled']) && empty($summary['data']['checkout_cta_enabled']));
}

if (!empty($readiness['data']['readiness'])) {
    ypcdui1_check($checks, 'readiness_blocks_network', empty($readiness['data']['readiness']['ready_for_network']));
    ypcdui1_check($checks, 'readiness_blocks_cta', empty($readiness['data']['readiness']['ready_for_cta']));
    ypcdui1_check(
        $checks,
        'readiness_reports_encryption_blocker',
        in_array('private_storage_blocked_encryption_key_missing', $readiness['data']['readiness']['errors'], true)
    );
}

$after_counts = array(
    'youngo_payment_provider_configs' => ypcdui1_count($db, 'youngo_payment_provider_configs'),
    'payment_gateways' => ypcdui1_count($db, 'payment_gateways'),
    'payment' => ypcdui1_count($db, 'payment'),
    'enrol' => ypcdui1_count($db, 'enrol'),
);

ypcdui1_check($checks, 'no_db_writes_detected', $baseline_counts === $after_counts);

$details['baseline_counts'] = $baseline_counts;
$details['after_counts'] = $after_counts;
$details['readiness_status'] = !empty($readiness['data']['readiness']['status'])
    ? $readiness['data']['readiness']['status']
    : 'unavailable';

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

$result = array(
    'phase' => 'PAYMENT.PAYMOB.CONFIG.DASHBOARD.UI.1',
    'ok' => empty($failed),
    'failed_checks' => $failed,
    'checks' => $checks,
    'details' => $details,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(empty($failed) ? 0 : 1);
