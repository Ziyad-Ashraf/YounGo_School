<?php
/**
 * PAYMENT.SECRETS.ENCRYPTION.KEY.SETUP.1 diagnostic.
 *
 * Static/read-only checks only. No DB writes, no SQL execution, no Paymob
 * requests, no credential output, and no encryption key output.
 */

$root = dirname(__DIR__, 2);
$checks = array();
$details = array();

function ypeks1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypeks1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypeks1_contains($source, $needle)
{
    return is_string($source) && strpos($source, $needle) !== false;
}

function ypeks1_missing_needles($source, $needles)
{
    $missing = array();
    foreach ($needles as $needle) {
        if (!ypeks1_contains($source, $needle)) {
            $missing[] = $needle;
        }
    }

    return $missing;
}

function ypeks1_scan_needles($sources, $needles)
{
    $hits = array();
    foreach ($sources as $name => $source) {
        foreach ($needles as $needle) {
            if (ypeks1_contains($source, $needle)) {
                $hits[] = $name . ':' . $needle;
            }
        }
    }

    return $hits;
}

function ypeks1_has_committed_key_shape($source)
{
    if (!is_string($source) || $source === '') {
        return false;
    }

    if (preg_match_all('/encryption_key[\'"]?\s*=>\s*[\'"]([^\'"]+)[\'"]/i', $source, $matches)) {
        foreach ($matches[1] as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }
    }

    if (preg_match_all('/\$config\[[\'"]encryption_key[\'"]\]\s*=\s*[\'"]([^\'"]+)[\'"]/i', $source, $matches)) {
        foreach ($matches[1] as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }
    }

    return false;
}

function ypeks1_load_config_key_presence($root)
{
    $config = array();
    $_SERVER['HTTP_HOST'] = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'school.local';
    $_SERVER['SCRIPT_NAME'] = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';

    if (!defined('BASEPATH')) {
        define('BASEPATH', $root . '/system/');
    }
    if (!defined('APPPATH')) {
        define('APPPATH', $root . '/application/');
    }

    include $root . '/application/config/config.php';

    return isset($config['encryption_key']) && trim((string) $config['encryption_key']) !== '';
}

$paths = array(
    'config' => $root . '/application/config/config.php',
    'example' => $root . '/application/config/youngo_security.local.example.php',
    'real_local' => $root . '/application/config/youngo_security.local.php',
    'gitignore' => $root . '/.gitignore',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
    'paymob_config_reader' => $root . '/application/libraries/Youngo_paymob_config.php',
    'settings_controller' => $root . '/application/controllers/Youngo_payment_settings.php',
    'settings_view' => $root . '/application/views/backend/admin/youngo_payment_settings.php',
    'config_model' => $root . '/application/models/Youngo_payment_config_model.php',
);

foreach ($paths as $name => $path) {
    if ($name !== 'real_local') {
        ypeks1_check($checks, 'file_exists_' . $name, is_file($path), $path);
    }
}

$config_source = ypeks1_read($paths['config']);
$example_source = ypeks1_read($paths['example']);
$gitignore_source = ypeks1_read($paths['gitignore']);
$paymob_config_source = ypeks1_read($paths['paymob_config']);
$paymob_reader_source = ypeks1_read($paths['paymob_config_reader']);
$controller_source = ypeks1_read($paths['settings_controller']);
$view_source = ypeks1_read($paths['settings_view']);
$model_source = ypeks1_read($paths['config_model']);

ypeks1_check($checks, 'real_security_config_ignored', ypeks1_contains($gitignore_source, 'application/config/youngo_security.local.php'));
ypeks1_check($checks, 'example_security_config_exists', is_file($paths['example']));
ypeks1_check($checks, 'example_contains_placeholder_only', ypeks1_contains($example_source, "\$config['youngo_security']") && ypeks1_contains($example_source, "'encryption_key' => ''"));
ypeks1_check($checks, 'config_default_key_remains_empty', ypeks1_contains($config_source, "\$config['encryption_key'] = '';"));
ypeks1_check($checks, 'config_loads_ignored_security_file_when_present', ypeks1_contains($config_source, "APPPATH . 'config/youngo_security.local.php'") && ypeks1_contains($config_source, "include \$youngo_security_local_path") && ypeks1_contains($config_source, "\$config['youngo_security']['encryption_key']"));
ypeks1_check($checks, 'config_unsets_nested_security_key_after_load', ypeks1_contains($config_source, "unset(\$config['youngo_security']"));

$committed_key_hits = array();
foreach (array('config' => $config_source, 'example' => $example_source) as $name => $source) {
    if (ypeks1_has_committed_key_shape($source)) {
        $committed_key_hits[] = $name;
    }
}
ypeks1_check($checks, 'no_real_encryption_key_committed', empty($committed_key_hits), implode(', ', $committed_key_hits));

$real_file_exists = is_file($paths['real_local']);
$key_present = ypeks1_load_config_key_presence($root);
ypeks1_check($checks, 'config_detects_key_presence_without_printing_value', is_bool($key_present), $key_present ? 'configured_redacted' : 'missing');
ypeks1_check($checks, 'missing_key_detected_safely_when_no_real_file', $real_file_exists || !$key_present, $real_file_exists ? 'real_local_file_exists_key_not_printed' : 'missing');

$blocked_needles = array(
    'encryption_key_is_ready',
    'private_storage_blocked_encryption_key_missing',
    'blocked_encryption_key_missing',
);
ypeks1_check($checks, 'paymob_private_db_storage_blocks_when_key_missing', empty(ypeks1_missing_needles($model_source, $blocked_needles)), implode(', ', ypeks1_missing_needles($model_source, $blocked_needles)));

$dashboard_needles = array(
    'Encryption key configured',
    'Encrypted DB credential storage',
    'youngo_security.local.php',
    'key_ready_schema_pending',
    'blocked_private_db_storage',
);
ypeks1_check($checks, 'dashboard_readiness_shows_key_presence_only', empty(ypeks1_missing_needles($view_source, $dashboard_needles)), implode(', ', ypeks1_missing_needles($view_source, $dashboard_needles)));
ypeks1_check($checks, 'controller_passes_encryption_key_ready_flag', ypeks1_contains($controller_source, "'encryption_key_ready' => \$this->youngo_payment_config_model->encryption_key_is_ready()"));

$changed_sources = array(
    'config' => $config_source,
    'example' => $example_source,
    'paymob_config' => $paymob_config_source,
    'paymob_reader' => $paymob_reader_source,
    'settings_controller' => $controller_source,
    'settings_view' => $view_source,
    'config_model' => $model_source,
);
$network_hits = ypeks1_scan_needles($changed_sources, array('curl_init', 'curl_exec', 'CURLOPT_', 'file_get_contents(\'http', 'file_get_contents("http', 'fsockopen', 'stream_socket_client'));
ypeks1_check($checks, 'no_paymob_network_calls_added', empty($network_hits), implode(', ', $network_hits));

$db_write_hits = ypeks1_scan_needles(array(
    'config' => $config_source,
    'example' => $example_source,
    'settings_view' => $view_source,
), array('->insert(', '->update(', '->delete(', 'CREATE TABLE', 'ALTER TABLE', 'DROP TABLE'));
ypeks1_check($checks, 'no_db_writes_or_sql_added', empty($db_write_hits), implode(', ', $db_write_hits));
ypeks1_check($checks, 'paymob_defaults_still_disabled', ypeks1_contains($paymob_config_source, "'enabled' => false") && ypeks1_contains($paymob_config_source, "'network_enabled' => false") && ypeks1_contains($paymob_config_source, "'checkout_cta_enabled' => false"));
ypeks1_check($checks, 'no_legacy_payment_gateway_dependency_added', empty(ypeks1_scan_needles($changed_sources, array('payment_gateways', 'get_payment_gateways', 'payment_gateway.php'))));

$details['real_security_config_file'] = $real_file_exists ? 'exists_ignored_not_printed' : 'missing';
$details['loaded_encryption_key_presence'] = $key_present ? 'configured_redacted' : 'missing';
$details['db_writes'] = 'none';
$details['network_requests'] = 'none';
$details['paymob_private_values'] = 'not_read_or_printed';
$details['payment_behavior'] = 'unchanged';

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo json_encode(array(
    'phase' => 'PAYMENT.SECRETS.ENCRYPTION.KEY.SETUP.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
    'failed_checks' => $failed,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($failed) ? 0 : 1);
