<?php
/**
 * PAYMENT.PAYMOB.SETUP.WIZARD.1 diagnostic.
 *
 * Static/read-only checks only. No DB writes, no SQL execution, no Paymob
 * requests, no credential output, and no private value persistence.
 */

define('ENVIRONMENT', 'development');
define('BASEPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);
define('APPPATH', BASEPATH);

$root = dirname(__DIR__, 2);
$checks = array();
$details = array();

function ypws1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypws1_read($path)
{
    return is_file($path) ? file_get_contents($path) : false;
}

function ypws1_contains($source, $needle)
{
    return is_string($source) && strpos($source, $needle) !== false;
}

function ypws1_scan_needles($sources, $needles)
{
    $hits = array();
    foreach ($sources as $name => $source) {
        foreach ($needles as $needle) {
            if (ypws1_contains($source, $needle)) {
                $hits[] = $name . ':' . $needle;
            }
        }
    }

    return $hits;
}

$paths = array(
    'settings_controller' => $root . '/application/controllers/Youngo_payment_settings.php',
    'settings_view' => $root . '/application/views/backend/admin/youngo_payment_settings.php',
    'config_model' => $root . '/application/models/Youngo_payment_config_model.php',
    'paymob_config_reader' => $root . '/application/libraries/Youngo_paymob_config.php',
    'routes' => $root . '/application/config/routes.php',
    'navigation' => $root . '/application/views/backend/admin/navigation.php',
    'default_paymob_config' => $root . '/application/config/youngo_paymob.php',
    'course_card' => $root . '/application/views/frontend/youngo/course_listing/course_card.php',
    'my_wishlist' => $root . '/application/views/frontend/youngo/my_wishlist.php',
    'wishlist_items' => $root . '/application/views/frontend/youngo/wishlist_items.php',
);

foreach ($paths as $key => $path) {
    ypws1_check($checks, 'file_exists_' . $key, is_file($path), $path);
}

$view = ypws1_read($paths['settings_view']);
$controller = ypws1_read($paths['settings_controller']);
$model = ypws1_read($paths['config_model']);
$config_reader = ypws1_read($paths['paymob_config_reader']);
$routes = ypws1_read($paths['routes']);
$navigation = ypws1_read($paths['navigation']);
$default_config = ypws1_read($paths['default_paymob_config']);
$course_card = ypws1_read($paths['course_card']);
$my_wishlist = ypws1_read($paths['my_wishlist']);
$wishlist_items = ypws1_read($paths['wishlist_items']);

ypws1_check($checks, 'setup_checklist_marker_exists', ypws1_contains($view, 'data-youngo-paymob-setup-checklist="true"'));
ypws1_check($checks, 'required_fields_marker_exists', ypws1_contains($view, 'data-youngo-paymob-required-fields="true"'));
ypws1_check($checks, 'next_steps_marker_exists', ypws1_contains($view, 'data-youngo-paymob-next-steps="true"'));

$setup_steps = array(
    'Create/activate Paymob sandbox account',
    'Create/confirm EGP card integration',
    'Create/confirm EGP mobile wallet integration',
    'Prepare return URL',
    'Prepare payment notification URL',
    'Enter non-private values in this dashboard page',
    'Save encrypted Paymob credentials',
    'Sandbox test remains disabled until readiness is complete',
);

$missing_steps = array();
foreach ($setup_steps as $step) {
    if (!ypws1_contains($view, $step)) {
        $missing_steps[] = $step;
    }
}
ypws1_check($checks, 'setup_checklist_steps_present', empty($missing_steps), implode(', ', $missing_steps));

$non_private_fields = array(
    'mode',
    'currency',
    'amount_multiplier',
    'card_integration_id_egp',
    'wallet_integration_id_egp',
    'api_base_url',
    'checkout_base_url',
    'return_url',
    'notification_url',
);

$missing_non_private = array();
foreach ($non_private_fields as $field) {
    if (!ypws1_contains($view, '<code><?php echo html_escape($field_name); ?></code>') && !ypws1_contains($view, "'" . $field . "'")) {
        $missing_non_private[] = $field;
    }
}
ypws1_check($checks, 'required_non_private_field_names_present', empty($missing_non_private), implode(', ', $missing_non_private));

$private_fields = array('public_key', 'secret_key', 'hmac_secret');
$missing_private = array();
foreach ($private_fields as $field) {
    if (!ypws1_contains($view, "'" . $field . "'")) {
        $missing_private[] = $field;
    }
}
ypws1_check($checks, 'private_server_config_field_names_present', empty($missing_private), implode(', ', $missing_private));

$required_statuses = array('complete', 'missing', 'pending_server_config', 'blocked_private_db_storage', 'disabled_until_approved');
$missing_statuses = array();
foreach ($required_statuses as $status) {
    if (!ypws1_contains($view, "'" . $status . "'") && !ypws1_contains($view, '>' . $status . '<')) {
        $missing_statuses[] = $status;
    }
}
ypws1_check($checks, 'safe_status_labels_present', empty($missing_statuses), implode(', ', $missing_statuses));

$private_input_needles = array('name="public_key"', "name='public_key'", 'name="secret_key"', "name='secret_key'", 'name="hmac_secret"', "name='hmac_secret'");
$private_input_hits = ypws1_scan_needles(array('settings_view' => $view), $private_input_needles);
ypws1_check($checks, 'no_private_save_fields_in_view', empty($private_input_hits), implode(', ', $private_input_hits));

ypws1_check($checks, 'sandbox_test_control_disabled', ypws1_contains($view, 'data-youngo-sandbox-test-disabled="true"') && ypws1_contains($view, 'disabled aria-disabled="true"'));
$sandbox_action_hits = ypws1_scan_needles(array('settings_view' => $view), array('href="#sandbox-test"', 'href="sandbox-test', 'action="sandbox-test', 'site_url(\'admin/youngo/paymob-sandbox', 'site_url("admin/youngo/paymob-sandbox'));
ypws1_check($checks, 'sandbox_test_control_non_functional', empty($sandbox_action_hits) && ypws1_contains($view, '<button type="button"') && ypws1_contains($view, 'data-youngo-sandbox-test-disabled="true"'), implode(', ', $sandbox_action_hits));
ypws1_check($checks, 'save_behavior_existing_form_unchanged', ypws1_contains($view, 'action="<?php echo site_url(\'admin/youngo/payment-settings\'); ?>"') && ypws1_contains($view, 'Save non-private settings'));
ypws1_check($checks, 'audit_log_review_anchor_exists', ypws1_contains($view, 'id="youngo-payment-audit-log"') && ypws1_contains($view, 'href="#youngo-payment-audit-log"'));

$network_hits = ypws1_scan_needles(array(
    'settings_controller' => $controller,
    'settings_view' => $view,
    'config_model' => $model,
    'paymob_config_reader' => $config_reader,
), array('curl_init', 'CURLOPT_', 'file_get_contents(\'http', 'file_get_contents("http', 'fsockopen', 'stream_socket_client'));
ypws1_check($checks, 'no_paymob_call_patterns_in_setup_wizard_paths', empty($network_hits), implode(', ', $network_hits));

$legacy_gateway_hits = ypws1_scan_needles(array(
    'settings_controller' => $controller,
    'settings_view' => $view,
    'config_model' => $model,
), array('get_payment_gateways', 'update_payment_settings', 'payment_gateway.php', "->get('payment_gateways'", '->get("payment_gateways"'));
ypws1_check($checks, 'legacy_payment_gateways_not_used', empty($legacy_gateway_hits), implode(', ', $legacy_gateway_hits));

ypws1_check($checks, 'root_only_page_remains', ypws1_contains($controller, '$this->require_root_admin();') && ypws1_contains($controller, 'youngo_is_root_admin') && ypws1_contains($controller, "check_session_data('admin')"));
ypws1_check($checks, 'route_still_points_to_settings_controller', ypws1_contains($routes, "\$route['admin/youngo/payment-settings'] = 'youngo_payment_settings/index';"));
ypws1_check($checks, 'navigation_contains_payment_settings_link', ypws1_contains($navigation, 'admin/youngo/payment-settings'));

ypws1_check($checks, 'tracked_checkout_cta_default_false', ypws1_contains($default_config, "'checkout_cta_enabled' => false"));
ypws1_check($checks, 'tracked_network_defaults_false', ypws1_contains($default_config, "'enabled' => false") && ypws1_contains($default_config, "'network_enabled' => false") && ypws1_contains($default_config, "'sandbox_network_testing_enabled' => false"));
ypws1_check($checks, 'listing_and_wishlist_have_no_checkout_start_links', !ypws1_contains($course_card, 'youngo/checkout/start') && !ypws1_contains($my_wishlist, 'youngo/checkout/start') && !ypws1_contains($wishlist_items, 'youngo/checkout/start'));
ypws1_check($checks, 'setup_view_has_no_public_checkout_start_link', !ypws1_contains($view, 'youngo/checkout/start'));

$raw_secret_hits = array();
foreach (array('settings_view' => $view) as $name => $source) {
    if (is_string($source) && preg_match('/(sk_[A-Za-z0-9]{8,}|pk_[A-Za-z0-9]{8,}|PAYMOB_[A-Z_]+=[^\\s]+)/', $source)) {
        $raw_secret_hits[] = $name;
    }
}
ypws1_check($checks, 'no_private_values_in_setup_view', empty($raw_secret_hits), implode(', ', $raw_secret_hits));

$details['non_private_dashboard_fields'] = $non_private_fields;
$details['private_credential_fields'] = $private_fields;
$details['safe_statuses'] = $required_statuses;
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
    'phase' => 'PAYMENT.PAYMOB.SETUP.WIZARD.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
