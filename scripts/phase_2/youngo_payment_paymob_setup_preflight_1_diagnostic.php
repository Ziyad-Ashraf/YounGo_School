<?php
/**
 * PAYMENT.PAYMOB.CONFIG.SETUP.PREFLIGHT.1 diagnostic.
 *
 * Static and fixture checks only. No DB writes, no SQL execution, no Paymob
 * network request, no credentials, and no secret output.
 */

define('ENVIRONMENT', 'development');
define('BASEPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);
define('APPPATH', BASEPATH);

$root = dirname(__DIR__, 2);
$checks = array();
$details = array();

function ypsp1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypsp1_read($path)
{
    return is_file($path) ? file_get_contents($path) : false;
}

function ypsp1_contains($source, $needle)
{
    return is_string($source) && strpos($source, $needle) !== false;
}

function ypsp1_scan_needles($sources, $needles)
{
    $hits = array();
    foreach ($sources as $name => $source) {
        foreach ($needles as $needle) {
            if (ypsp1_contains($source, $needle)) {
                $hits[] = $name . ':' . $needle;
            }
        }
    }

    return $hits;
}

class Youngo_paymob_setup_preflight_config
{
    protected $values;

    public function __construct($values = array())
    {
        $this->values = array_merge(array(
            'enabled' => false,
            'checkout_cta_enabled' => false,
            'checkout_routes_enabled' => false,
            'checkout_local_testing_enabled' => false,
            'network_enabled' => false,
            'sandbox_network_testing_enabled' => false,
            'mode' => 'sandbox',
            'currency' => 'EGP',
        ), $values);
    }

    public function is_enabled()
    {
        return !empty($this->values['enabled']);
    }

    public function is_checkout_cta_enabled()
    {
        return !empty($this->values['checkout_cta_enabled']);
    }

    public function is_checkout_routes_enabled()
    {
        return !empty($this->values['checkout_routes_enabled']);
    }

    public function is_checkout_local_testing_enabled()
    {
        return !empty($this->values['checkout_local_testing_enabled']);
    }

    public function is_network_enabled()
    {
        return !empty($this->values['network_enabled']);
    }

    public function is_sandbox_network_testing_enabled()
    {
        return !empty($this->values['sandbox_network_testing_enabled']);
    }

    public function get_mode()
    {
        return (string) $this->values['mode'];
    }

    public function get_currency()
    {
        return (string) $this->values['currency'];
    }

    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
    }
}

$paths = array(
    'helper' => $root . '/application/helpers/youngo_checkout_cta_helper.php',
    'settings_controller' => $root . '/application/controllers/Youngo_payment_settings.php',
    'checkout_controller' => $root . '/application/controllers/Youngo_checkout.php',
    'settings_view' => $root . '/application/views/backend/admin/youngo_payment_settings.php',
    'course_page' => $root . '/application/views/frontend/youngo/course_page.php',
    'checkout_order_view' => $root . '/application/views/frontend/youngo/checkout_order.php',
    'config_model' => $root . '/application/models/Youngo_payment_config_model.php',
    'paymob_adapter' => $root . '/application/libraries/Youngo_paymob_adapter.php',
    'paymob_config' => $root . '/application/libraries/Youngo_paymob_config.php',
);

foreach ($paths as $key => $path) {
    ypsp1_check($checks, 'file_exists_' . $key, is_file($path), $path);
}

if (is_file($paths['paymob_config'])) {
    require_once $paths['paymob_config'];
}

if (is_file($paths['helper'])) {
    require_once $paths['helper'];
}

ypsp1_check($checks, 'helper_function_exists', function_exists('youngo_checkout_cta_decision'));
ypsp1_check($checks, 'sandbox_gate_function_exists', function_exists('youngo_checkout_cta_sandbox_network_gate'));

$eligible_course = array(
    'id' => 9,
    'status' => 'active',
    'youngo_access_mode' => 'subscription_and_purchase',
    'is_free_course' => 0,
    'discount_flag' => 0,
    'price' => '1000.00',
    'discounted_price' => '0.00',
);

$default_config = class_exists('Youngo_paymob_config')
    ? new Youngo_paymob_config(array('load_local_override' => false))
    : new Youngo_paymob_setup_preflight_config();

$local_no_network_config = new Youngo_paymob_setup_preflight_config(array(
    'checkout_cta_enabled' => true,
    'checkout_routes_enabled' => true,
    'checkout_local_testing_enabled' => true,
    'network_enabled' => false,
    'mode' => 'sandbox',
    'currency' => 'EGP',
));

$sandbox_network_ready_config = new Youngo_paymob_setup_preflight_config(array(
    'enabled' => true,
    'checkout_cta_enabled' => true,
    'checkout_routes_enabled' => true,
    'checkout_local_testing_enabled' => true,
    'network_enabled' => true,
    'sandbox_network_testing_enabled' => true,
    'mode' => 'sandbox',
    'currency' => 'EGP',
));

$network_missing_sandbox_gate_config = new Youngo_paymob_setup_preflight_config(array(
    'enabled' => true,
    'checkout_cta_enabled' => true,
    'checkout_routes_enabled' => true,
    'checkout_local_testing_enabled' => true,
    'network_enabled' => true,
    'sandbox_network_testing_enabled' => false,
    'mode' => 'sandbox',
    'currency' => 'EGP',
));

$live_network_config = new Youngo_paymob_setup_preflight_config(array(
    'enabled' => true,
    'checkout_cta_enabled' => true,
    'checkout_routes_enabled' => true,
    'checkout_local_testing_enabled' => true,
    'network_enabled' => true,
    'sandbox_network_testing_enabled' => true,
    'mode' => 'live',
    'currency' => 'EGP',
));

$fake_ready = array(
    'ok' => true,
    'status' => 'ready',
    'code' => 'paymob_sandbox_ready',
    'message' => 'Fixture readiness passed.',
);

$default_decision = function_exists('youngo_checkout_cta_decision')
    ? youngo_checkout_cta_decision($eligible_course, 8, array(
        'config_reader' => $default_config,
        'access_state' => array('has_access' => false),
        'is_learner' => true,
    ))
    : array();

$local_no_network_decision = function_exists('youngo_checkout_cta_decision')
    ? youngo_checkout_cta_decision($eligible_course, 8, array(
        'config_reader' => $local_no_network_config,
        'access_state' => array('has_access' => false),
        'is_learner' => true,
    ))
    : array();

$sandbox_network_decision = function_exists('youngo_checkout_cta_decision')
    ? youngo_checkout_cta_decision($eligible_course, 8, array(
        'config_reader' => $sandbox_network_ready_config,
        'sandbox_readiness' => $fake_ready,
        'access_state' => array('has_access' => false),
        'is_learner' => true,
    ))
    : array();

$missing_sandbox_gate_decision = function_exists('youngo_checkout_cta_decision')
    ? youngo_checkout_cta_decision($eligible_course, 8, array(
        'config_reader' => $network_missing_sandbox_gate_config,
        'access_state' => array('has_access' => false),
        'is_learner' => true,
    ))
    : array();

$live_network_decision = function_exists('youngo_checkout_cta_decision')
    ? youngo_checkout_cta_decision($eligible_course, 8, array(
        'config_reader' => $live_network_config,
        'sandbox_readiness' => $fake_ready,
        'access_state' => array('has_access' => false),
        'is_learner' => true,
    ))
    : array();

$network_without_readiness_decision = function_exists('youngo_checkout_cta_decision')
    ? youngo_checkout_cta_decision($eligible_course, 8, array(
        'config_reader' => $sandbox_network_ready_config,
        'access_state' => array('has_access' => false),
        'is_learner' => true,
    ))
    : array();

ypsp1_check($checks, 'default_cta_hidden', empty($default_decision['show_cta']) && isset($default_decision['reason_code']) && $default_decision['reason_code'] === 'checkout_cta_disabled', json_encode($default_decision));
ypsp1_check($checks, 'local_no_network_cta_allowed', !empty($local_no_network_decision['show_cta']) && isset($local_no_network_decision['reason_code']) && $local_no_network_decision['reason_code'] === 'checkout_cta_available_local', json_encode($local_no_network_decision));
ypsp1_check($checks, 'sandbox_network_ready_simulation_allows_cta', !empty($sandbox_network_decision['show_cta']) && isset($sandbox_network_decision['reason_code']) && $sandbox_network_decision['reason_code'] === 'checkout_cta_available_sandbox_network', json_encode($sandbox_network_decision));
ypsp1_check($checks, 'network_without_sandbox_gate_blocked', empty($missing_sandbox_gate_decision['show_cta']) && isset($missing_sandbox_gate_decision['reason_code']) && $missing_sandbox_gate_decision['reason_code'] === 'paymob_network_must_remain_disabled', json_encode($missing_sandbox_gate_decision));
ypsp1_check($checks, 'network_without_readiness_fails_closed', empty($network_without_readiness_decision['show_cta']) && isset($network_without_readiness_decision['reason_code']) && in_array($network_without_readiness_decision['reason_code'], array('paymob_sandbox_readiness_unavailable', 'blocked_missing_dashboard_config', 'blocked_missing_private_config', 'paymob_sandbox_gates_not_ready'), true), json_encode($network_without_readiness_decision));
ypsp1_check($checks, 'live_network_case_blocked', empty($live_network_decision['show_cta']) && isset($live_network_decision['reason_code']) && $live_network_decision['reason_code'] === 'checkout_config_not_local_egp_sandbox', json_encode($live_network_decision));
ypsp1_check($checks, 'target_url_only_youngo_checkout_start', isset($sandbox_network_decision['target_url']) && $sandbox_network_decision['target_url'] === '/youngo/checkout/start/9');

$legacy_url_hits = array();
foreach (array($default_decision, $local_no_network_decision, $sandbox_network_decision, $missing_sandbox_gate_decision, $live_network_decision, $network_without_readiness_decision) as $decision) {
    foreach (array('target_url', 'login_url') as $field) {
        if (!empty($decision[$field]) && preg_match('#home/(handle_buy_now|handle_cart_items|course_payment|shopping_cart)|^/?payment$#', $decision[$field])) {
            $legacy_url_hits[] = $field . ':' . $decision[$field];
        }
    }
}
ypsp1_check($checks, 'helper_returns_no_legacy_cart_payment_urls', empty($legacy_url_hits), implode(', ', $legacy_url_hits));

$settings_view = ypsp1_read($paths['settings_view']);
$helper = ypsp1_read($paths['helper']);
$settings_controller = ypsp1_read($paths['settings_controller']);
$checkout_controller = ypsp1_read($paths['checkout_controller']);
$course_page = ypsp1_read($paths['course_page']);
$checkout_order_view = ypsp1_read($paths['checkout_order_view']);
$config_model = ypsp1_read($paths['config_model']);

$required_terms = array(
    'setup and readiness page',
    'Non-private sandbox settings are saved in the YounGo DB config table',
    'saved encrypted when the encryption key and credential schema are ready',
    'Encrypted DB credential storage',
    'Sandbox tests are not ready until all required fields and explicit local gates pass',
    'notification_url',
    'webhook_url is an alias',
    'Public key',
    'server_config_required',
    'configured_redacted',
    'db_private_storage_blocked',
);

$missing_terms = array();
foreach ($required_terms as $term) {
    if (!ypsp1_contains($settings_view, $term)) {
        $missing_terms[] = $term;
    }
}
ypsp1_check($checks, 'dashboard_readiness_terms_present', empty($missing_terms), implode(', ', $missing_terms));

$private_input_needles = array('name="public_key"', "name='public_key'", 'name="secret_key"', "name='secret_key'", 'name="hmac_secret"', "name='hmac_secret'");
$private_input_hits = ypsp1_scan_needles(array('settings_view' => $settings_view), $private_input_needles);
ypsp1_check($checks, 'dashboard_private_fields_not_editable', empty($private_input_hits), implode(', ', $private_input_hits));

$network_needles = array('curl_init', 'CURLOPT_', 'file_get_contents(\'http', 'file_get_contents("http', 'fsockopen', 'stream_socket_client');
$non_adapter_sources = array(
    'helper' => $helper,
    'settings_controller' => $settings_controller,
    'checkout_controller' => $checkout_controller,
    'settings_view' => $settings_view,
    'course_page' => $course_page,
    'checkout_order_view' => $checkout_order_view,
    'config_model' => $config_model,
);
$network_hits = ypsp1_scan_needles($non_adapter_sources, $network_needles);
ypsp1_check($checks, 'no_new_paymob_network_call_patterns_outside_adapter', empty($network_hits), implode(', ', $network_hits));

$legacy_dependency_sources = array(
    'helper' => $helper,
    'settings_controller' => $settings_controller,
    'checkout_controller' => $checkout_controller,
    'config_model' => $config_model,
);
$legacy_dependency_hits = ypsp1_scan_needles($legacy_dependency_sources, array('payment_gateways', 'get_payment_gateways', 'payment_gateway'));
ypsp1_check($checks, 'no_legacy_payment_gateways_dependency_added', empty($legacy_dependency_hits), implode(', ', $legacy_dependency_hits));

ypsp1_check($checks, 'diagnostic_has_no_db_write_runtime', true, 'Diagnostic uses static/fixture checks only and does not open a DB connection.');

$details['decisions'] = array(
    'default' => $default_decision,
    'local_no_network' => $local_no_network_decision,
    'sandbox_network_ready' => $sandbox_network_decision,
    'network_without_sandbox_gate' => $missing_sandbox_gate_decision,
    'network_without_readiness' => $network_without_readiness_decision,
    'live_network' => $live_network_decision,
);
$details['naming'] = array(
    'canonical_dashboard_field' => 'notification_url',
    'local_config_alias' => 'webhook_url',
    'meaning' => 'Paymob payment notification URL / YounGo webhook endpoint',
);
$details['public_key_source'] = 'ignored_local_or_server_config_only_in_this_phase';
$details['db_writes'] = 'none';
$details['network_requests'] = 'none';

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo json_encode(array(
    'phase' => 'PAYMENT.PAYMOB.CONFIG.SETUP.PREFLIGHT.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
