<?php
/**
 * PAYMENT.PAYMOB.SETUP.FORM.UI.1 diagnostic.
 *
 * Static/read-only checks only. No DB writes, no SQL execution, no Paymob
 * requests, no credential output, and no raw private value exposure.
 */

$root = dirname(__DIR__, 2);
$checks = array();
$details = array();

function ypsfui1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypsfui1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypsfui1_contains($source, $needle)
{
    return is_string($source) && strpos($source, $needle) !== false;
}

function ypsfui1_missing_needles($source, $needles)
{
    $missing = array();
    foreach ($needles as $needle) {
        if (!ypsfui1_contains($source, $needle)) {
            $missing[] = $needle;
        }
    }

    return $missing;
}

function ypsfui1_scan_needles($sources, $needles)
{
    $hits = array();
    foreach ($sources as $name => $source) {
        foreach ($needles as $needle) {
            if (ypsfui1_contains($source, $needle)) {
                $hits[] = $name . ':' . $needle;
            }
        }
    }

    return $hits;
}

$paths = array(
    'settings_view' => $root . '/application/views/backend/admin/youngo_payment_settings.php',
    'settings_controller' => $root . '/application/controllers/Youngo_payment_settings.php',
    'config_model' => $root . '/application/models/Youngo_payment_config_model.php',
    'audit_model' => $root . '/application/models/Youngo_payment_config_audit_model.php',
    'paymob_config_reader' => $root . '/application/libraries/Youngo_paymob_config.php',
    'routes' => $root . '/application/config/routes.php',
    'navigation' => $root . '/application/views/backend/admin/navigation.php',
    'default_paymob_config' => $root . '/application/config/youngo_paymob.php',
    'legacy_payment_settings' => $root . '/application/views/backend/admin/payment_settings.php',
);

foreach ($paths as $name => $path) {
    ypsfui1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

$view = ypsfui1_read($paths['settings_view']);
$controller = ypsfui1_read($paths['settings_controller']);
$config_model = ypsfui1_read($paths['config_model']);
$audit_model = ypsfui1_read($paths['audit_model']);
$paymob_config_reader = ypsfui1_read($paths['paymob_config_reader']);
$routes = ypsfui1_read($paths['routes']);
$navigation = ypsfui1_read($paths['navigation']);
$default_config = ypsfui1_read($paths['default_paymob_config']);
$legacy_payment_settings = ypsfui1_read($paths['legacy_payment_settings']);

$section_needles = array(
    'data-youngo-paymob-form-ui="true"',
    'data-youngo-paymob-section="basic-setup"',
    'Paymob basic setup',
    'data-youngo-paymob-section="urls"',
    '<p>URLs</p>',
    'data-youngo-paymob-section="private-credentials"',
    'Private credentials',
    'data-youngo-paymob-section="readiness-status"',
    'Readiness / status',
    'data-youngo-paymob-section="gates"',
    'Payment/network/CTA gates',
    'data-youngo-paymob-setup-checklist="true"',
    'data-youngo-paymob-required-fields="true"',
    'id="youngo-payment-audit-log"',
    'Recent Configuration Audit',
    'data-youngo-paymob-next-steps="true"',
    'Next steps',
);
ypsfui1_check($checks, 'legacy_style_youngo_paymob_sections_exist', empty(ypsfui1_missing_needles($view, $section_needles)), implode(', ', ypsfui1_missing_needles($view, $section_needles)));

$non_private_field_needles = array(
    'name="mode"',
    'name="currency"',
    'name="amount_multiplier"',
    'name="card_integration_id_egp"',
    'name="wallet_integration_id_egp"',
    'name="api_base_url"',
    'name="checkout_base_url"',
    'name="return_url"',
    'name="notification_url"',
);
ypsfui1_check($checks, 'non_private_form_fields_exist', empty(ypsfui1_missing_needles($view, $non_private_field_needles)), implode(', ', ypsfui1_missing_needles($view, $non_private_field_needles)));
ypsfui1_check($checks, 'form_posts_to_youngo_settings', ypsfui1_contains($view, 'action="<?php echo site_url(\'admin/youngo/payment-settings\'); ?>"') && ypsfui1_contains($view, 'method="post"'));
ypsfui1_check($checks, 'save_button_non_private_only', ypsfui1_contains($view, 'Save non-private settings'));

$private_field_needles = array(
    "'api_key'",
    "'public_key'",
    "'secret_key'",
    "'hmac_secret'",
    'data-youngo-private-field="<?php echo html_escape($field_name); ?>"',
    'type="password"',
    'name="credentials[',
    'data-youngo-private-input="encrypted-db"',
    'Save Paymob credentials',
    'Leave blank to keep the current encrypted value.',
    'Values are encrypted and never displayed.',
);
ypsfui1_check($checks, 'private_fields_masked_encrypted_save_only', empty(ypsfui1_missing_needles($view, $private_field_needles)), implode(', ', ypsfui1_missing_needles($view, $private_field_needles)));

$private_post_names = array('name="public_key"', "name='public_key'", 'name="secret_key"', "name='secret_key'", 'name="hmac_secret"', "name='hmac_secret'", 'name="api_key"', "name='api_key'", 'name="client_secret"');
$private_post_hits = ypsfui1_scan_needles(array('settings_view' => $view), $private_post_names);
ypsfui1_check($checks, 'no_flat_secret_input_names_submitted', empty($private_post_hits), implode(', ', $private_post_hits));
ypsfui1_check($checks, 'credential_inputs_have_no_value_attributes', !preg_match('/name="credentials\[[^"]+\]"[^>]*\svalue=/i', $view));

$model_save_needles = array(
    'upsert_dashboard_non_private_config',
    'save_encrypted_credentials',
    'decrypt_credentials_for_runtime',
    "'mode'",
    "'currency'",
    "'amount_multiplier'",
    "'card_integration_id_egp'",
    "'wallet_integration_id_egp'",
    "'api_base_url'",
    "'checkout_base_url'",
    "'return_url'",
    "'notification_url'",
    'blocked_fields_rejected',
    'Only sandbox Paymob configuration can be saved in this phase.',
    'YounGo Paymob config supports EGP only.',
    'Private Paymob values must be saved through the encrypted credential flow.',
);
ypsfui1_check($checks, 'save_flow_separates_non_private_and_encrypted_credentials', empty(ypsfui1_missing_needles($config_model, $model_save_needles)) && ypsfui1_contains($controller, 'upsert_dashboard_non_private_config') && ypsfui1_contains($controller, 'save_secret_credentials'), implode(', ', ypsfui1_missing_needles($config_model, $model_save_needles)));

$gate_names = array(
    'enabled',
    'network_enabled',
    'sandbox_network_testing_enabled',
    'webhook_testing_enabled',
    'checkout_routes_enabled',
    'checkout_local_testing_enabled',
    'checkout_cta_enabled',
    'live_mode_allowed',
);
$gate_input_hits = array();
foreach ($gate_names as $gate_name) {
    foreach (array('name="' . $gate_name . '"', "name='" . $gate_name . "'") as $needle) {
        if (ypsfui1_contains($view, $needle)) {
            $gate_input_hits[] = $needle;
        }
    }
}
ypsfui1_check($checks, 'activation_network_cta_gates_not_editable', empty($gate_input_hits), implode(', ', $gate_input_hits));
ypsfui1_check($checks, 'tracked_gate_defaults_disabled', ypsfui1_contains($default_config, "'enabled' => false") && ypsfui1_contains($default_config, "'network_enabled' => false") && ypsfui1_contains($default_config, "'sandbox_network_testing_enabled' => false") && ypsfui1_contains($default_config, "'checkout_cta_enabled' => false"));
ypsfui1_check($checks, 'model_blocks_activation_gates', ypsfui1_contains($config_model, "'enabled'") && ypsfui1_contains($config_model, "'network_enabled'") && ypsfui1_contains($config_model, "'checkout_cta_enabled'") && ypsfui1_contains($config_model, 'Payment activation/testing flags cannot be enabled in this phase.'));

$audit_needles = array(
    'Recent Configuration Audit',
    'Changed fields',
    'Secret presence',
    'Audit entries show redacted summaries only. Secret values are never displayed.',
    'record_config_audit',
);
ypsfui1_check($checks, 'audit_panel_remains_redacted', empty(ypsfui1_missing_needles($view . "\n" . $controller . "\n" . $audit_model, $audit_needles)), implode(', ', ypsfui1_missing_needles($view . "\n" . $controller . "\n" . $audit_model, $audit_needles)));

$next_step_needles = array(
    'Save non-private settings.',
    'Review audit log',
    'Sandbox test disabled until readiness and explicit approval.',
    'data-youngo-sandbox-test-disabled="true"',
    'disabled aria-disabled="true"',
    'The sandbox test control is intentionally disabled and non-functional until a later approved execution phase.',
);
ypsfui1_check($checks, 'next_steps_and_sandbox_test_boundary_exist', empty(ypsfui1_missing_needles($view, $next_step_needles)), implode(', ', ypsfui1_missing_needles($view, $next_step_needles)));

$network_hits = ypsfui1_scan_needles(array(
    'settings_view' => $view,
    'settings_controller' => $controller,
    'config_model' => $config_model,
    'audit_model' => $audit_model,
    'paymob_config_reader' => $paymob_config_reader,
), array('curl_init', 'CURLOPT_', 'file_get_contents(\'http', 'file_get_contents("http', 'fsockopen', 'stream_socket_client'));
ypsfui1_check($checks, 'no_paymob_calls_added', empty($network_hits), implode(', ', $network_hits));

$legacy_gateway_hits = ypsfui1_scan_needles(array(
    'settings_view' => $view,
    'settings_controller' => $controller,
    'config_model' => $config_model,
), array('payment_gateways', 'get_payment_gateways', 'update_payment_settings', 'payment_gateway.php'));
ypsfui1_check($checks, 'no_legacy_gateway_dependency_added_for_youngo', empty($legacy_gateway_hits), implode(', ', $legacy_gateway_hits));
ypsfui1_check($checks, 'legacy_payment_page_only_links_to_youngo', ypsfui1_contains($legacy_payment_settings, 'data-youngo-paymob-legacy-link="true"') && ypsfui1_contains($legacy_payment_settings, "site_url('admin/youngo/payment-settings')"));
ypsfui1_check($checks, 'no_production_checkout_cta_exposure', !ypsfui1_contains($view, 'youngo/checkout/start') && ypsfui1_contains($default_config, "'checkout_cta_enabled' => false"));
ypsfui1_check($checks, 'root_only_page_guard_remains', ypsfui1_contains($controller, '$this->require_root_admin();') && ypsfui1_contains($controller, 'youngo_is_root_admin') && ypsfui1_contains($controller, "check_session_data('admin')"));
ypsfui1_check($checks, 'route_remains_youngo_settings', ypsfui1_contains($routes, "\$route['admin/youngo/payment-settings'] = 'youngo_payment_settings/index';"));
ypsfui1_check($checks, 'navigation_remains_root_marker', ypsfui1_contains($navigation, '$can_view_youngo_payment_settings') && ypsfui1_contains($navigation, 'youngo_is_root_admin'));

$raw_secret_hits = array();
if (preg_match('/(sk_live|sk_test|Bearer\s+|[A-Fa-f0-9]{64,})/', $view)) {
    $raw_secret_hits[] = 'settings_view_secret_shape';
}
ypsfui1_check($checks, 'view_has_no_private_value_shapes', empty($raw_secret_hits), implode(', ', $raw_secret_hits));

$details['sections'] = array('basic_setup', 'urls', 'private_credentials', 'readiness_status', 'audit_log', 'next_steps');
$details['non_private_dashboard_fields'] = array('mode', 'currency', 'amount_multiplier', 'card_integration_id_egp', 'wallet_integration_id_egp', 'api_base_url', 'checkout_base_url', 'return_url', 'notification_url');
$details['private_credential_fields'] = array('api_key', 'public_key', 'secret_key', 'hmac_secret');
$details['db_writes'] = 'none';
$details['network_requests'] = 'none';
$details['payment_behavior'] = 'unchanged';

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo json_encode(array(
    'phase' => 'PAYMENT.PAYMOB.SETUP.FORM.UI.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
