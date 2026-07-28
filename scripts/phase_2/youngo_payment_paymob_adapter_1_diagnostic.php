<?php
/**
 * PAYMENT.PAYMOB.ADAPTER.1 diagnostic.
 *
 * Verifies the disabled Paymob adapter skeleton without printing secrets,
 * writing to the database, creating intentions, or performing network calls.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', $root . '/application/');

$checks = array();
$details = array(
    'db_writes' => 'none',
    'network_requests' => 'none',
);

function yppa1_add_check(&$checks, $name, $passed, $detail = '')
{
    $checks[$name] = array(
        'status' => $passed ? 'PASS' : 'FAIL',
        'detail' => $detail,
    );
}

function yppa1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function yppa1_load_config_value($path, $key)
{
    if (!is_file($path)) {
        return array();
    }

    $config = array();
    include $path;

    return isset($config[$key]) && is_array($config[$key]) ? $config[$key] : array();
}

function yppa1_flatten_values($value)
{
    $values = array();
    if (is_array($value)) {
        foreach ($value as $child) {
            $values = array_merge($values, yppa1_flatten_values($child));
        }
    } else {
        $values[] = $value;
    }

    return $values;
}

function yppa1_config_values_are_safe($config)
{
    $allowed_literals = array(
        '',
        'paymob',
        'sandbox',
        'EGP',
        'PAYMOB_SECRET_KEY',
        'PAYMOB_PUBLIC_KEY',
        'PAYMOB_HMAC_SECRET',
        'PAYMOB_INTEGRATION_ID_CARD_EGP',
        'PAYMOB_RETURN_URL',
        'PAYMOB_WEBHOOK_URL',
    );

    foreach (yppa1_flatten_values($config) as $value) {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            continue;
        }

        if (!is_string($value) || !in_array($value, $allowed_literals, true)) {
            return false;
        }
    }

    return true;
}

function yppa1_has_network_code($source)
{
    $patterns = array(
        'curl_exec',
        'curl_init',
        'file_get_contents("http',
        "file_get_contents('http",
        'fsockopen',
        'stream_socket_client',
        'GuzzleHttp',
        'Composer\\Autoload',
    );

    foreach ($patterns as $pattern) {
        if (stripos($source, $pattern) !== false) {
            return true;
        }
    }

    return false;
}

function yppa1_json_does_not_contain($json, $values)
{
    foreach ($values as $value) {
        if ($value !== '' && strpos($json, $value) !== false) {
            return false;
        }
    }

    return true;
}

$paths = array(
    'adapter' => $root . '/application/libraries/Youngo_paymob_adapter.php',
    'config_reader' => $root . '/application/libraries/Youngo_paymob_config.php',
    'config' => $root . '/application/config/youngo_paymob.php',
    'local_example' => $root . '/application/config/youngo_paymob.local.example.php',
    'local_override' => $root . '/application/config/youngo_paymob.local.php',
    'order_model' => $root . '/application/models/Youngo_checkout_model.php',
    'routes' => $root . '/application/config/routes.php',
);

foreach (array('adapter', 'config_reader', 'config', 'local_example', 'order_model', 'routes') as $name) {
    yppa1_add_check($checks, 'file_exists_' . $name, is_file($paths[$name]), $paths[$name]);
}
yppa1_add_check($checks, 'local_override_not_created', !is_file($paths['local_override']), $paths['local_override']);

require_once $paths['config_reader'];
require_once $paths['adapter'];

$reader = new Youngo_paymob_config(array('load_local_override' => false));
$adapter = new Youngo_paymob_adapter(array('config_reader' => $reader));
$details['adapter_safe_summary'] = $adapter->get_safe_diagnostic_summary();

yppa1_add_check($checks, 'config_reader_loads', $reader instanceof Youngo_paymob_config);
yppa1_add_check($checks, 'adapter_loads', $adapter instanceof Youngo_paymob_adapter);
yppa1_add_check($checks, 'payment_disabled_by_default', $reader->is_enabled() === false);
yppa1_add_check($checks, 'config_mode_sandbox', $reader->get_mode() === 'sandbox');
yppa1_add_check($checks, 'config_currency_egp', $reader->get_currency() === 'EGP');

$readiness = $adapter->is_ready_for_sandbox();
$details['readiness_code'] = isset($readiness['code']) ? $readiness['code'] : null;
yppa1_add_check($checks, 'sandbox_readiness_false_without_local_secrets', empty($readiness['ok']) && isset($readiness['code']) && $readiness['code'] === 'paymob_network_disabled_in_this_phase');

$invalid_currency = $adapter->build_intention_payload(array(
    'order_reference' => 'YGO-DIAGNOSTIC-INVALID-CURRENCY',
    'currency' => 'USD',
    'total_amount_cents' => 100000,
));
$invalid_amount = $adapter->build_intention_payload(array(
    'order_reference' => 'YGO-DIAGNOSTIC-INVALID-AMOUNT',
    'currency' => 'EGP',
    'total_amount_cents' => 0,
));
$missing_reference = $adapter->build_intention_payload(array(
    'currency' => 'EGP',
    'total_amount_cents' => 100000,
));

yppa1_add_check($checks, 'payload_rejects_invalid_currency', empty($invalid_currency['ok']) && $invalid_currency['code'] === 'unsupported_currency');
yppa1_add_check($checks, 'payload_rejects_invalid_amount', empty($invalid_amount['ok']) && $invalid_amount['code'] === 'invalid_amount');
yppa1_add_check($checks, 'payload_rejects_missing_reference', empty($missing_reference['ok']) && $missing_reference['code'] === 'missing_order_reference');

$fake_order = array(
    'id' => 999,
    'order_reference' => 'YGO-DIAGNOSTIC-PAYMOB-ADAPTER-1',
    'currency' => 'EGP',
    'total_amount' => '1000.00',
    'total_amount_cents' => 100000,
);
$fake_customer = array(
    'first_name' => 'YounGo',
    'last_name' => 'Diagnostic',
    'email' => 'diagnostic@example.invalid',
    'phone_number' => 'NA',
);
$payload_result = $adapter->build_intention_payload($fake_order, $fake_customer);
$payload = !empty($payload_result['data']['payload']) ? $payload_result['data']['payload'] : array();
$details['payload_shape'] = array(
    'amount' => isset($payload['amount']) ? $payload['amount'] : null,
    'currency' => isset($payload['currency']) ? $payload['currency'] : null,
    'merchant_order_id' => isset($payload['merchant_order_id']) ? $payload['merchant_order_id'] : null,
    'payment_methods_count' => isset($payload['payment_methods']) && is_array($payload['payment_methods']) ? count($payload['payment_methods']) : 0,
    'notification_url_status' => isset($payload['notification_url']) && $payload['notification_url'] === 'PAYMOB_WEBHOOK_URL' ? 'placeholder' : 'configured_redacted',
    'redirection_url_status' => isset($payload['redirection_url']) && $payload['redirection_url'] === 'PAYMOB_RETURN_URL' ? 'placeholder' : 'configured_redacted',
);

yppa1_add_check($checks, 'payload_builder_builds_safe_egp_placeholder_payload', !empty($payload_result['ok']) && isset($payload['amount'], $payload['currency'], $payload['merchant_order_id']) && $payload['amount'] === 100000 && $payload['currency'] === 'EGP' && $payload['merchant_order_id'] === $fake_order['order_reference']);
yppa1_add_check($checks, 'payload_uses_smallest_currency_unit', !empty($payload['amount']) && $payload['amount'] === 100000);
yppa1_add_check($checks, 'payload_uses_placeholders_without_local_config', isset($payload['payment_methods'][0], $payload['notification_url'], $payload['redirection_url']) && $payload['payment_methods'][0] === 'PAYMOB_INTEGRATION_ID_CARD_EGP' && $payload['notification_url'] === 'PAYMOB_WEBHOOK_URL' && $payload['redirection_url'] === 'PAYMOB_RETURN_URL');

$disabled_create = $adapter->create_intention_disabled($fake_order, $fake_customer);
yppa1_add_check($checks, 'network_execution_method_fails_closed', empty($disabled_create['ok']) && $disabled_create['code'] === 'paymob_network_disabled_in_this_phase');

$checkout_response = $adapter->get_unified_checkout_url_from_response(array('client_secret' => 'diagnostic-client-secret'));
$checkout_json = json_encode($checkout_response);
yppa1_add_check($checks, 'unified_checkout_response_redacts_client_secret', !empty($checkout_response['ok']) && strpos($checkout_json, 'diagnostic-client-secret') === false && strpos($checkout_json, 'configured_redacted') !== false);

$hmac_shape = $adapter->verify_hmac_payload_shape(array(
    'hmac' => 'diagnostic-hmac-value',
    'id' => 'diagnostic-transaction-id',
    'amount_cents' => '100000',
    'currency' => 'EGP',
    'success' => 'true',
    'pending' => 'false',
    'order' => 'diagnostic-paymob-order-id',
    'integration_id' => 'diagnostic-integration-id',
));
yppa1_add_check($checks, 'hmac_payload_shape_validates_required_fields', !empty($hmac_shape['ok']) && $hmac_shape['code'] === 'hmac_payload_shape_valid');

$default_config = yppa1_load_config_value($paths['config'], 'youngo_paymob');
$example_config = yppa1_load_config_value($paths['local_example'], 'youngo_paymob_local');
yppa1_add_check($checks, 'tracked_default_values_are_non_secret', yppa1_config_values_are_safe($default_config));
yppa1_add_check($checks, 'tracked_example_values_are_non_secret', yppa1_config_values_are_safe($example_config));

$adapter_source = yppa1_read($paths['adapter']);
yppa1_add_check($checks, 'adapter_has_no_network_code', !yppa1_has_network_code($adapter_source));
yppa1_add_check($checks, 'adapter_has_no_composer_or_guzzle_dependency', stripos($adapter_source, 'Guzzle') === false && stripos($adapter_source, 'vendor/autoload') === false);
yppa1_add_check($checks, 'adapter_has_no_db_usage', stripos($adapter_source, '$this->db') === false && stripos($adapter_source, 'DB(') === false);

$routes_source = yppa1_read($paths['routes']);
yppa1_add_check($checks, 'no_payment_checkout_start_routes_added', strpos($routes_source, 'youngo_checkout') === false);

$safe_summary_json = json_encode($adapter->get_safe_diagnostic_summary());
yppa1_add_check(
    $checks,
    'safe_summary_does_not_expose_fake_sensitive_values',
    yppa1_json_does_not_contain($safe_summary_json, array('diagnostic-client-secret', 'diagnostic-hmac-value', 'diagnostic-integration-id'))
);

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[] = $name;
    }
}

$result = array(
    'phase' => 'PAYMENT.PAYMOB.ADAPTER.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
