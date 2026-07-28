<?php

$root = dirname(__DIR__, 2);

$files = array(
    'checkout_model' => $root . '/application/models/Youngo_checkout_model.php',
    'payment_model' => $root . '/application/models/Youngo_payment_model.php',
    'payment_config_model' => $root . '/application/models/Youngo_payment_config_model.php',
    'entitlement_write_model' => $root . '/application/models/Youngo_entitlement_write_model.php',
    'entitlement_model' => $root . '/application/models/Youngo_entitlement_model.php',
    'fixture_processor' => $root . '/application/libraries/Youngo_paymob_fixture_processor.php',
    'checkout_controller' => $root . '/application/controllers/Youngo_checkout.php',
    'payment_settings_controller' => $root . '/application/controllers/Youngo_payment_settings.php',
);

$checks = array();

foreach ($files as $key => $path) {
    $checks['file_exists_' . $key] = check(is_file($path), $path);
}

$model_files = array(
    'checkout_model' => $files['checkout_model'],
    'payment_model' => $files['payment_model'],
    'payment_config_model' => $files['payment_config_model'],
    'entitlement_write_model' => $files['entitlement_write_model'],
    'entitlement_model' => $files['entitlement_model'],
);

foreach ($model_files as $key => $path) {
    $source = is_file($path) ? file_get_contents($path) : '';
    $has_public_db = preg_match('/public\s+\$db\s*;/', $source) === 1;
    $has_constructor = preg_match('/function\s+__construct\s*\(/', $source) === 1;
    $has_injected_db_path = strpos($source, "\$params['db']") !== false || strpos($source, '$params["db"]') !== false;
    $has_http_db_fallback = strpos($source, 'get_instance()') !== false
        && preg_match('/\$CI->db|\$ci->db/', $source) === 1;
    $has_db_instance_fallback = preg_match('/function\s+db_instance\s*\(/', $source) === 1
        && preg_match('/\$CI->db|\$ci->db/', $source) === 1;

    $checks[$key . '_has_public_db_marker'] = check($has_public_db, 'manual CLI DB injection compatibility marker');
    $checks[$key . '_constructor_exists'] = check($has_constructor, '');
    $checks[$key . '_safe_http_db_fallback'] = check($has_http_db_fallback || $has_db_instance_fallback, 'constructor or db_instance falls back to CI db');

    if ($has_injected_db_path) {
        $checks[$key . '_keeps_cli_db_injection'] = check(true, 'diagnostic injection retained');
    }
}

$script_files = glob($root . '/scripts/phase_2/*payment*diagnostic*.php');
$runtime_files = glob($root . '/scripts/phase_2/*payment*runtime_test*.php');
$payment_scripts = array_merge(is_array($script_files) ? $script_files : array(), is_array($runtime_files) ? $runtime_files : array());
$manual_injection_count = 0;
foreach ($payment_scripts as $path) {
    $source = file_get_contents($path);
    if (strpos($source, '->db = $db') !== false || strpos($source, '$CI->db = $db') !== false) {
        $manual_injection_count++;
    }
}

$checks['payment_diagnostics_scanned'] = check(count($payment_scripts) > 0, count($payment_scripts) . ' files');
$checks['manual_diagnostic_db_injection_retained'] = check($manual_injection_count > 0, $manual_injection_count . ' scripts');
$network_pattern_found = false;
foreach ($files as $path) {
    $source = is_file($path) ? file_get_contents($path) : '';
    if (strpos($source, 'curl_exec') !== false || preg_match('/file_get_contents\s*\(\s*[\'"]http/i', $source)) {
        $network_pattern_found = true;
        break;
    }
}

$checks['no_network_patterns_in_scanned_files'] = check(!$network_pattern_found, '');
$checks['no_db_write_patterns_in_sweep_diagnostic'] = check(
    !preg_match('/->(insert|update|delete|replace|query)\s*\(/', file_get_contents(__FILE__)),
    ''
);

$failed = array();
foreach ($checks as $name => $result) {
    if ($result['status'] !== 'PASS') {
        $failed[] = $name;
    }
}

echo json_encode(array(
    'phase' => 'PAYMENT.DB.ACCESS.SWEEP.1',
    'ok' => empty($failed),
    'failed_checks' => $failed,
    'checks' => $checks,
    'details' => array(
        'db_writes' => 'none',
        'network_calls' => 'none',
        'payment_scripts_scanned' => count($payment_scripts),
        'manual_injection_scripts' => $manual_injection_count,
    ),
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);

function check($condition, $detail)
{
    return array(
        'status' => $condition ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}
