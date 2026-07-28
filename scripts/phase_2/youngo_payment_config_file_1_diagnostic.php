<?php
/**
 * PAYMENT.CONFIG.FILE.1 read-only diagnostic.
 *
 * Verifies the non-secret Paymob config foundation without printing secrets,
 * modifying the database, or performing network requests.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', $root . '/application/');

$checks = array();
$details = array();

function ypcf1_add_check(&$checks, $name, $passed, $detail = '')
{
    $checks[$name] = array(
        'status' => $passed ? 'PASS' : 'FAIL',
        'detail' => $detail,
    );
}

function ypcf1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypcf1_load_config_value($path, $key)
{
    if (!is_file($path)) {
        return array();
    }

    $config = array();
    include $path;

    return isset($config[$key]) && is_array($config[$key]) ? $config[$key] : array();
}

function ypcf1_flatten_values($value)
{
    $values = array();
    if (is_array($value)) {
        foreach ($value as $child) {
            $values = array_merge($values, ypcf1_flatten_values($child));
        }
    } else {
        $values[] = $value;
    }

    return $values;
}

function ypcf1_config_values_are_safe($config)
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

    foreach (ypcf1_flatten_values($config) as $value) {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            continue;
        }

        if (!is_string($value) || !in_array($value, $allowed_literals, true)) {
            return false;
        }
    }

    return true;
}

function ypcf1_file_contains($path, $needle)
{
    return is_file($path) && strpos(file_get_contents($path), $needle) !== false;
}

$paths = array(
    'config' => $root . '/application/config/youngo_paymob.php',
    'local_example' => $root . '/application/config/youngo_paymob.local.example.php',
    'local_override' => $root . '/application/config/youngo_paymob.local.php',
    'reader' => $root . '/application/libraries/Youngo_paymob_config.php',
    'gitignore' => $root . '/.gitignore',
    'home_controller' => $root . '/application/controllers/Home.php',
    'course_page' => $root . '/application/views/frontend/youngo/course_page.php',
    'course_card' => $root . '/application/views/frontend/youngo/course_listing/course_card.php',
    'my_wishlist' => $root . '/application/views/frontend/youngo/my_wishlist.php',
    'wishlist_items' => $root . '/application/views/frontend/youngo/wishlist_items.php',
    'routes' => $root . '/application/config/routes.php',
);

foreach (array('config', 'local_example', 'reader', 'gitignore') as $name) {
    ypcf1_add_check($checks, 'file_exists_' . $name, is_file($paths[$name]), $paths[$name]);
}

ypcf1_add_check($checks, 'local_override_not_created', !is_file($paths['local_override']), $paths['local_override']);

$git_check_ignore_code = 1;
$git_check_ignore_output = array();
exec('git check-ignore -q -- application/config/youngo_paymob.local.php', $git_check_ignore_output, $git_check_ignore_code);
ypcf1_add_check($checks, 'local_override_path_ignored_by_git', $git_check_ignore_code === 0);

$default_config = ypcf1_load_config_value($paths['config'], 'youngo_paymob');
$example_config = ypcf1_load_config_value($paths['local_example'], 'youngo_paymob_local');

ypcf1_add_check($checks, 'tracked_default_config_loads', !empty($default_config));
ypcf1_add_check($checks, 'tracked_example_config_loads', !empty($example_config));
ypcf1_add_check($checks, 'default_payment_disabled', isset($default_config['enabled']) && $default_config['enabled'] === false);
ypcf1_add_check($checks, 'default_mode_sandbox', isset($default_config['mode']) && $default_config['mode'] === 'sandbox');
ypcf1_add_check($checks, 'default_currency_egp', isset($default_config['currency']) && $default_config['currency'] === 'EGP');
ypcf1_add_check($checks, 'default_amount_multiplier_100', isset($default_config['amount_multiplier']) && (int) $default_config['amount_multiplier'] === 100);
ypcf1_add_check($checks, 'default_network_disabled', isset($default_config['network_enabled']) && $default_config['network_enabled'] === false);
ypcf1_add_check($checks, 'default_live_mode_not_allowed', isset($default_config['live_mode_allowed']) && $default_config['live_mode_allowed'] === false);

$required_placeholders = array(
    'secret_key' => 'PAYMOB_SECRET_KEY',
    'public_key' => 'PAYMOB_PUBLIC_KEY',
    'hmac_secret' => 'PAYMOB_HMAC_SECRET',
    'integration_id_card_egp' => 'PAYMOB_INTEGRATION_ID_CARD_EGP',
    'return_url' => 'PAYMOB_RETURN_URL',
    'webhook_url' => 'PAYMOB_WEBHOOK_URL',
);

$placeholder_ok = isset($default_config['placeholder_names']) && is_array($default_config['placeholder_names']);
foreach ($required_placeholders as $key => $placeholder) {
    $placeholder_ok = $placeholder_ok
        && array_key_exists($key, $default_config)
        && $default_config[$key] === null
        && isset($default_config['placeholder_names'][$key])
        && $default_config['placeholder_names'][$key] === $placeholder;
}
ypcf1_add_check($checks, 'default_placeholders_present_and_empty', $placeholder_ok);

ypcf1_add_check($checks, 'tracked_default_values_are_non_secret', ypcf1_config_values_are_safe($default_config));
ypcf1_add_check($checks, 'tracked_example_values_are_non_secret', ypcf1_config_values_are_safe($example_config));
ypcf1_add_check($checks, 'no_client_secret_config_key', strpos(ypcf1_read($paths['config']) . ypcf1_read($paths['local_example']), 'client_secret') === false);
ypcf1_add_check($checks, 'no_live_paymob_url_in_config_files', !preg_match('#https?://#i', ypcf1_read($paths['config']) . ypcf1_read($paths['local_example'])));

require_once $paths['reader'];

$reader = new Youngo_paymob_config(array('load_local_override' => false));
$summary = $reader->get_safe_diagnostic_summary();
$details['safe_default_summary'] = $summary;

ypcf1_add_check($checks, 'config_reader_loads', $reader instanceof Youngo_paymob_config);
ypcf1_add_check($checks, 'reader_default_disabled', $reader->is_enabled() === false);
ypcf1_add_check($checks, 'reader_mode_sandbox', $reader->get_mode() === 'sandbox');
ypcf1_add_check($checks, 'reader_currency_egp', $reader->get_currency() === 'EGP');
ypcf1_add_check($checks, 'reader_required_placeholders_present', $reader->has_required_sandbox_placeholders() === true);
ypcf1_add_check($checks, 'reader_getters_return_empty_placeholders', $reader->get_integration_id_card_egp() === null && $reader->get_return_url() === null && $reader->get_webhook_url() === null);

$fake_secret = 'diagnostic-secret-value';
$fake_public = 'diagnostic-public-value';
$fake_hmac = 'diagnostic-hmac-value';
$fake_integration = 'diagnostic-integration-value';
$fake_return = 'diagnostic-return-placeholder';
$fake_webhook = 'diagnostic-webhook-placeholder';
$redaction_reader = new Youngo_paymob_config(array(
    'load_local_override' => false,
    'config' => array(
        'secret_key' => $fake_secret,
        'public_key' => $fake_public,
        'hmac_secret' => $fake_hmac,
        'integration_id_card_egp' => $fake_integration,
        'return_url' => $fake_return,
        'webhook_url' => $fake_webhook,
    ),
));
$redaction_summary_json = json_encode($redaction_reader->get_safe_diagnostic_summary());
$redaction_ok = strpos($redaction_summary_json, $fake_secret) === false
    && strpos($redaction_summary_json, $fake_public) === false
    && strpos($redaction_summary_json, $fake_hmac) === false
    && strpos($redaction_summary_json, $fake_integration) === false
    && strpos($redaction_summary_json, $fake_return) === false
    && strpos($redaction_summary_json, $fake_webhook) === false
    && substr_count($redaction_summary_json, 'configured_redacted') >= 6;
ypcf1_add_check($checks, 'safe_summary_redacts_sensitive_fields', $redaction_ok);

ypcf1_add_check(
    $checks,
    'home_blocks_legacy_checkout_paths',
    ypcf1_file_contains($paths['home_controller'], 'youngo_remove_managed_access_courses_from_cart')
        && ypcf1_file_contains($paths['home_controller'], 'Subscription checkout is not available yet')
        && ypcf1_file_contains($paths['home_controller'], 'Coupon-based checkout is not available yet')
);

ypcf1_add_check(
    $checks,
    'youngo_course_cta_boundaries_still_present',
    ypcf1_file_contains($paths['course_page'], '$youngo_is_managed_access')
        && ypcf1_file_contains($paths['course_card'], '$youngo_card_is_managed_access')
        && ypcf1_file_contains($paths['my_wishlist'], 'youngo_wishlist_course_boundary_state')
        && ypcf1_file_contains($paths['wishlist_items'], 'youngo_wishlist_course_boundary_state')
);

$routes_source = ypcf1_read($paths['routes']);
ypcf1_add_check(
    $checks,
    'no_payment_checkout_routes_added',
    strpos($routes_source, 'youngo_checkout') === false && strpos($routes_source, 'payment/paymob/webhook') === false
);

$phase_files_source = ypcf1_read($paths['config'])
    . "\n" . ypcf1_read($paths['local_example'])
    . "\n" . ypcf1_read($paths['reader']);

$network_patterns = array(
    'curl_exec',
    'curl_init',
    'fsockopen',
    'stream_socket_client',
    'GuzzleHttp',
    'PAYMOB_BASE_URL=https',
    'accept.paymob.com',
    'POST /v1/intention',
    'file_get_contents("http',
    "file_get_contents('http",
);

$network_hits = array();
foreach ($network_patterns as $pattern) {
    if (stripos($phase_files_source, $pattern) !== false) {
        $network_hits[] = $pattern;
    }
}
$details['phase_network_pattern_hits'] = $network_hits;
ypcf1_add_check($checks, 'no_paymob_network_call_code_in_phase_files', empty($network_hits), implode(',', $network_hits));

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[] = $name;
    }
}

$result = array(
    'phase' => 'PAYMENT.CONFIG.FILE.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
