<?php

$root = dirname(__DIR__, 2);

$files = array(
    'legacy_payment_settings' => $root . '/application/views/backend/admin/payment_settings.php',
    'legacy_payment_gateway' => $root . '/application/views/backend/admin/payment_gateway.php',
    'navigation' => $root . '/application/views/backend/admin/navigation.php',
    'admin_controller' => $root . '/application/controllers/Admin.php',
    'youngo_payment_settings_controller' => $root . '/application/controllers/Youngo_payment_settings.php',
    'youngo_payment_settings_view' => $root . '/application/views/backend/admin/youngo_payment_settings.php',
    'routes' => $root . '/application/config/routes.php',
);

$checks = array();

function yplpl_contents($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function yplpl_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => $detail,
    );
}

$legacy_view = yplpl_contents($files['legacy_payment_settings']);
$routes = yplpl_contents($files['routes']);
$admin_controller = yplpl_contents($files['admin_controller']);
$youngo_controller = yplpl_contents($files['youngo_payment_settings_controller']);
$youngo_view = yplpl_contents($files['youngo_payment_settings_view']);
$navigation = yplpl_contents($files['navigation']);

yplpl_check($checks, 'legacy_payment_settings_view_exists', $legacy_view !== '', $files['legacy_payment_settings']);
yplpl_check($checks, 'legacy_payment_gateway_view_absent_or_unused', !is_file($files['legacy_payment_gateway']), 'payment_gateway.php is not present in this codebase; payment_settings.php is the active legacy settings view.');
yplpl_check($checks, 'youngo_paymob_link_card_exists', strpos($legacy_view, 'data-youngo-paymob-legacy-link="true"') !== false);
yplpl_check($checks, 'youngo_paymob_link_title_exists', strpos($legacy_view, 'YounGo Paymob Setup') !== false);
yplpl_check($checks, 'youngo_paymob_link_target_exists', strpos($legacy_view, "site_url('admin/youngo/payment-settings')") !== false);
yplpl_check($checks, 'separate_checkout_copy_exists', strpos($legacy_view, 'dedicated checkout and readiness flow separate from the legacy Academy payment gateways') !== false);
yplpl_check($checks, 'legacy_gateway_warning_copy_exists', strpos($legacy_view, 'does not add Paymob to legacy gateway rows or enable legacy payment behavior') !== false);
yplpl_check($checks, 'youngo_payment_settings_route_exists', strpos($routes, "\$route['admin/youngo/payment-settings'] = 'youngo_payment_settings/index';") !== false);
yplpl_check($checks, 'legacy_payment_settings_route_still_controller_based', strpos($admin_controller, 'public function payment_settings') !== false && strpos($admin_controller, "\$page_data['page_name']        = 'payment_settings';") !== false);
yplpl_check($checks, 'legacy_gateway_loop_unchanged_marker_present', strpos($legacy_view, 'foreach($payment_gateways as $payment_gateway)') !== false);

$legacy_gateway_row_patterns = array(
    "identifier' => 'paymob",
    '"identifier" => "paymob',
    'INSERT INTO payment_gateways',
    'payment_gateways SET',
    'paymob_accept',
    'paymob_gateway',
);

$combined_static = $legacy_view . "\n" . $routes . "\n" . $admin_controller . "\n" . $navigation;
$legacy_row_pattern_found = array();
foreach ($legacy_gateway_row_patterns as $pattern) {
    if (stripos($combined_static, $pattern) !== false) {
        $legacy_row_pattern_found[] = $pattern;
    }
}
yplpl_check($checks, 'no_paymob_legacy_gateway_row_code_added', empty($legacy_row_pattern_found), implode(', ', $legacy_row_pattern_found));

$youngo_static = $youngo_controller . "\n" . $youngo_view;
$youngo_legacy_gateway_dependency = array();
foreach (array('get_payment_gateways', "\$this->db->get('payment_gateways')", "\$this->db->get_where('payment_gateways'") as $pattern) {
    if (strpos($youngo_static, $pattern) !== false) {
        $youngo_legacy_gateway_dependency[] = $pattern;
    }
}
yplpl_check($checks, 'no_payment_gateways_dependency_added_for_youngo_paymob', empty($youngo_legacy_gateway_dependency), implode(', ', $youngo_legacy_gateway_dependency));

$network_patterns = array('curl_exec', 'CURLOPT_URL', 'file_get_contents("http', "file_get_contents('http", 'fsockopen', 'stream_socket_client');
$link_files_static = $legacy_view . "\n" . $youngo_view . "\n" . $youngo_controller;
$network_found = array();
foreach ($network_patterns as $pattern) {
    if (stripos($link_files_static, $pattern) !== false) {
        $network_found[] = $pattern;
    }
}
yplpl_check($checks, 'no_paymob_network_calls_added_in_link_paths', empty($network_found), implode(', ', $network_found));

yplpl_check($checks, 'diagnostic_has_no_db_writes', true, 'Static file inspection only; no CodeIgniter bootstrap or DB connection.');

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[] = $name;
    }
}

echo json_encode(array(
    'phase' => 'PAYMENT.PAYMOB.LEGACY.PAGE.LINK.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => array(
        'active_legacy_payment_page' => 'application/views/backend/admin/payment_settings.php',
        'youngo_paymob_target' => '/admin/youngo/payment-settings',
        'db_writes' => 'none',
        'network_requests' => 'none',
        'legacy_gateway_rows' => 'unchanged_by_this_phase',
    ),
    'failed_checks' => $failed,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
