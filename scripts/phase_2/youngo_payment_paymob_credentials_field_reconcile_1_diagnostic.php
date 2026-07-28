<?php
/**
 * PAYMENT.PAYMOB.CREDENTIALS.FIELD.RECONCILE.1 diagnostic.
 *
 * Static/read-only checks only. No DB writes, no SQL execution, no Paymob
 * requests, no credential output, and no private value persistence.
 */

$root = dirname(__DIR__, 2);
$checks = array();
$details = array();

function ypcfr1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypcfr1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypcfr1_contains($source, $needle)
{
    return is_string($source) && strpos($source, $needle) !== false;
}

function ypcfr1_missing_needles($source, $needles)
{
    $missing = array();
    foreach ($needles as $needle) {
        if (!ypcfr1_contains($source, $needle)) {
            $missing[] = $needle;
        }
    }

    return $missing;
}

function ypcfr1_scan_needles($sources, $needles)
{
    $hits = array();
    foreach ($sources as $name => $source) {
        foreach ($needles as $needle) {
            if (ypcfr1_contains($source, $needle)) {
                $hits[] = $name . ':' . $needle;
            }
        }
    }

    return $hits;
}

function ypcfr1_has_real_secret_shape($source)
{
    if (!is_string($source) || $source === '') {
        return false;
    }

    $allowed_placeholders = array(
        'PAYMOB_API_KEY',
        'PAYMOB_PUBLIC_KEY',
        'PAYMOB_SECRET_KEY',
        'PAYMOB_HMAC_SECRET',
    );

    $normalized = str_replace($allowed_placeholders, '', $source);

    return (bool) preg_match('/(sk_live_[A-Za-z0-9]{8,}|sk_test_[A-Za-z0-9]{8,}|pk_live_[A-Za-z0-9]{8,}|pk_test_[A-Za-z0-9]{8,}|Bearer\s+[A-Za-z0-9._\-]{12,}|[A-Fa-f0-9]{64,})/', $normalized);
}

$paths = array(
    'default_config' => $root . '/application/config/youngo_paymob.php',
    'local_example' => $root . '/application/config/youngo_paymob.local.example.php',
    'config_reader' => $root . '/application/libraries/Youngo_paymob_config.php',
    'adapter' => $root . '/application/libraries/Youngo_paymob_adapter.php',
    'config_model' => $root . '/application/models/Youngo_payment_config_model.php',
    'settings_controller' => $root . '/application/controllers/Youngo_payment_settings.php',
    'settings_view' => $root . '/application/views/backend/admin/youngo_payment_settings.php',
    'audit_model' => $root . '/application/models/Youngo_payment_config_audit_model.php',
    'routes' => $root . '/application/config/routes.php',
);

foreach ($paths as $key => $path) {
    ypcfr1_check($checks, 'file_exists_' . $key, is_file($path), $path);
}

$default_config = ypcfr1_read($paths['default_config']);
$local_example = ypcfr1_read($paths['local_example']);
$config_reader = ypcfr1_read($paths['config_reader']);
$adapter = ypcfr1_read($paths['adapter']);
$config_model = ypcfr1_read($paths['config_model']);
$settings_controller = ypcfr1_read($paths['settings_controller']);
$settings_view = ypcfr1_read($paths['settings_view']);
$audit_model = ypcfr1_read($paths['audit_model']);
$routes = ypcfr1_read($paths['routes']);

$dashboard_labels = array(
    'API key',
    'Public key',
    'Secret key',
    'HMAC secret',
);
ypcfr1_check($checks, 'paymob_dashboard_credential_labels_represented', empty(ypcfr1_missing_needles($settings_view, $dashboard_labels)), implode(', ', ypcfr1_missing_needles($settings_view, $dashboard_labels)));

$server_config_fields = array(
    "'api_key'",
    "'public_key'",
    "'secret_key'",
    "'hmac_secret'",
);
ypcfr1_check($checks, 'private_server_config_fields_present_in_view', empty(ypcfr1_missing_needles($settings_view, $server_config_fields)) && ypcfr1_contains($settings_view, 'data-youngo-private-field'), implode(', ', ypcfr1_missing_needles($settings_view, $server_config_fields)));
ypcfr1_check($checks, 'private_fields_disabled_and_non_submitting', ypcfr1_contains($settings_view, 'disabled readonly') && ypcfr1_contains($settings_view, 'has no submit name'));

$private_input_hits = ypcfr1_scan_needles(array('settings_view' => $settings_view), array(
    'name="api_key"',
    "name='api_key'",
    'name="public_key"',
    "name='public_key'",
    'name="secret_key"',
    "name='secret_key'",
    'name="hmac_secret"',
    "name='hmac_secret'",
    'type="password"',
));
ypcfr1_check($checks, 'no_private_submit_field_names', empty($private_input_hits), implode(', ', $private_input_hits));

$config_placeholders = array(
    "'api_key' => null",
    "'public_key' => null",
    "'secret_key' => null",
    "'hmac_secret' => null",
    "'api_key' => 'PAYMOB_API_KEY'",
    "'public_key' => 'PAYMOB_PUBLIC_KEY'",
    "'secret_key' => 'PAYMOB_SECRET_KEY'",
    "'hmac_secret' => 'PAYMOB_HMAC_SECRET'",
);
ypcfr1_check($checks, 'tracked_default_config_has_placeholder_only_fields', empty(ypcfr1_missing_needles($default_config, $config_placeholders)), implode(', ', ypcfr1_missing_needles($default_config, $config_placeholders)));
ypcfr1_check($checks, 'local_example_has_placeholder_only_fields', empty(ypcfr1_missing_needles($local_example, array('PAYMOB_API_KEY', 'PAYMOB_PUBLIC_KEY', 'PAYMOB_SECRET_KEY', 'PAYMOB_HMAC_SECRET'))), implode(', ', ypcfr1_missing_needles($local_example, array('PAYMOB_API_KEY', 'PAYMOB_PUBLIC_KEY', 'PAYMOB_SECRET_KEY', 'PAYMOB_HMAC_SECRET'))));

$reader_needles = array(
    'get_api_key_for_runtime',
    'get_secret_key_for_runtime',
    'get_public_key_for_runtime',
    'get_hmac_secret_for_runtime',
    "'api_key' => \$this->server_presence_label('api_key')",
    "'api_key' => \$this->presence_label(\$this->get('api_key'))",
);
ypcfr1_check($checks, 'config_reader_exposes_redacted_api_key_presence', empty(ypcfr1_missing_needles($config_reader, $reader_needles)), implode(', ', ypcfr1_missing_needles($config_reader, $reader_needles)));

$model_needles = array(
    "'api_key'",
    'blocked_fields_rejected',
    'Private values and activation gates cannot be saved in this phase.',
    'private_storage_blocked_encryption_key_missing',
    "'api_key_present'",
);
ypcfr1_check($checks, 'config_model_blocks_private_dashboard_saves', empty(ypcfr1_missing_needles($config_model, $model_needles)) && ypcfr1_contains($config_model, "'public_key',"), implode(', ', ypcfr1_missing_needles($config_model, $model_needles)));
ypcfr1_check($checks, 'audit_model_redacts_api_key_and_other_private_fields', ypcfr1_contains($audit_model, "'api_key'") && ypcfr1_contains($audit_model, "'public_key'") && ypcfr1_contains($audit_model, 'configured_redacted'));

ypcfr1_check($checks, 'api_key_not_used_by_current_intention_adapter', !ypcfr1_contains($adapter, 'get_api_key_for_runtime') && !ypcfr1_contains($adapter, "dashboard_value('api_key'"));
ypcfr1_check($checks, 'current_intention_uses_secret_public_hmac_gates', ypcfr1_contains($adapter, 'get_secret_key_for_runtime') && ypcfr1_contains($adapter, 'Authorization: Token') && ypcfr1_contains($adapter, 'get_public_key_for_runtime') && ypcfr1_contains($adapter, 'get_hmac_secret_for_runtime'));

$non_execution_sources = array(
    'default_config' => $default_config,
    'local_example' => $local_example,
    'config_reader' => $config_reader,
    'config_model' => $config_model,
    'settings_controller' => $settings_controller,
    'settings_view' => $settings_view,
    'audit_model' => $audit_model,
);
$network_hits = ypcfr1_scan_needles($non_execution_sources, array('curl_init', 'CURLOPT_', 'curl_exec', 'file_get_contents(\'http', 'file_get_contents("http', 'fsockopen', 'stream_socket_client'));
ypcfr1_check($checks, 'no_paymob_network_calls_in_reconciled_surfaces', empty($network_hits), implode(', ', $network_hits));

$legacy_hits = ypcfr1_scan_needles(array(
    'config_reader' => $config_reader,
    'config_model' => $config_model,
    'settings_controller' => $settings_controller,
    'settings_view' => $settings_view,
), array('payment_gateways', 'get_payment_gateways', 'payment_gateway.php', 'success_course_payment'));
ypcfr1_check($checks, 'no_legacy_payment_gateways_dependency_added', empty($legacy_hits), implode(', ', $legacy_hits));

ypcfr1_check($checks, 'settings_route_still_exists', ypcfr1_contains($routes, "\$route['admin/youngo/payment-settings'] = 'youngo_payment_settings/index';"));
ypcfr1_check($checks, 'setup_form_required_sections_still_exist', empty(ypcfr1_missing_needles($settings_view, array(
    'data-youngo-paymob-section="basic-setup"',
    'data-youngo-paymob-section="urls"',
    'data-youngo-paymob-section="private-credentials"',
    'data-youngo-paymob-section="readiness-status"',
    'data-youngo-paymob-section="audit-log"',
))));

$real_secret_hits = array();
foreach (array(
    'default_config' => $default_config,
    'local_example' => $local_example,
    'config_reader' => $config_reader,
    'config_model' => $config_model,
    'settings_view' => $settings_view,
) as $name => $source) {
    if (ypcfr1_has_real_secret_shape($source)) {
        $real_secret_hits[] = $name;
    }
}
ypcfr1_check($checks, 'no_real_credential_shapes_in_tracked_reconciled_files', empty($real_secret_hits), implode(', ', $real_secret_hits));

ypcfr1_check($checks, 'no_checkout_or_payment_behavior_exposed', !ypcfr1_contains($settings_view, 'youngo/checkout/start') && ypcfr1_contains($default_config, "'enabled' => false") && ypcfr1_contains($default_config, "'checkout_cta_enabled' => false"));

$details['observed_paymob_dashboard_fields'] = array('api_key', 'public_key', 'secret_key', 'hmac_secret');
$details['current_intention_required_private_fields'] = array('public_key', 'secret_key', 'hmac_secret');
$details['api_key_decision'] = 'server_config_only_displayed_for_reconciliation_not_current_intention_gate';
$details['private_storage'] = 'server_config_only_db_private_storage_blocked';
$details['db_writes'] = 'none';
$details['network_requests'] = 'none';
$details['legacy_payment_gateways'] = 'not_used';

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo json_encode(array(
    'phase' => 'PAYMENT.PAYMOB.CREDENTIALS.FIELD.RECONCILE.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
