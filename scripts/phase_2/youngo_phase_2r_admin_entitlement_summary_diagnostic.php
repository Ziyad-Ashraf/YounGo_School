<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$root = dirname(dirname(__DIR__));
define('ENVIRONMENT', 'development');
define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'config/database.php';

$config = $db['default'];
$mysqli = @new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
if ($mysqli->connect_errno) {
    echo "== Connection ==\n";
    echo json_encode(array('connected' => false, 'error' => 'DB connection failed without exposing credentials.')) . "\n";
    exit(2);
}
$mysqli->set_charset('utf8');

$failures = array();

function r_diag_section($title, $data)
{
    echo "\n== " . $title . " ==\n";
    echo json_encode($data, JSON_UNESCAPED_SLASHES) . "\n";
}

function r_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function r_diag_query($mysqli, $sql)
{
    if (preg_match('/\b(insert|update|delete|replace|alter|drop|create|truncate|grant|revoke|set)\b/i', $sql)) {
        throw new Exception('Write SQL blocked by diagnostic wrapper.');
    }

    $result = $mysqli->query($sql);
    if (!$result) {
        return array('error' => 'Query failed without exposing credentials.');
    }

    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

function r_diag_table_exists($mysqli, $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    $rows = r_diag_query($mysqli, "SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function r_diag_column_exists($mysqli, $table, $column)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column) || !r_diag_table_exists($mysqli, $table)) {
        return false;
    }

    $rows = r_diag_query($mysqli, "SHOW COLUMNS FROM `" . $table . "` LIKE '" . $mysqli->real_escape_string($column) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function r_diag_count($mysqli, $table)
{
    if (!r_diag_table_exists($mysqli, $table)) {
        return null;
    }

    $rows = r_diag_query($mysqli, "SELECT COUNT(*) AS c FROM `" . $table . "`");
    return isset($rows[0]['c']) ? (int) $rows[0]['c'] : null;
}

function r_diag_file_contains($path, $needle)
{
    return is_file($path) && strpos(file_get_contents($path), $needle) !== false;
}

r_diag_section('Connection', array(
    'connected' => true,
    'database_name' => $config['database'],
    'server_version' => $mysqli->server_info,
));

$files = array(
    'model' => APPPATH . 'models' . DIRECTORY_SEPARATOR . 'Youngo_entitlement_model.php',
    'admin_controller' => APPPATH . 'controllers' . DIRECTORY_SEPARATOR . 'Admin.php',
    'user_summary_view' => APPPATH . 'views' . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'youngo_user_entitlement_summary.php',
    'course_summary_view' => APPPATH . 'views' . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'youngo_course_entitlement_summary.php',
    'user_edit_view' => APPPATH . 'views' . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'user_edit.php',
    'course_edit_view' => APPPATH . 'views' . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'course_edit.php',
    'routes' => APPPATH . 'config' . DIRECTORY_SEPARATOR . 'routes.php',
);

$file_status = array();
foreach ($files as $key => $path) {
    $file_status[$key] = is_file($path);
}
r_diag_section('Required file availability', $file_status);
r_diag_assert($failures, !in_array(false, $file_status, true), 'One or more Phase 2R admin summary files are missing.');

$method_status = array(
    'get_admin_user_entitlement_summary' => r_diag_file_contains($files['model'], 'function get_admin_user_entitlement_summary'),
    'get_admin_course_entitlement_summary' => r_diag_file_contains($files['model'], 'function get_admin_course_entitlement_summary'),
    'get_admin_recent_manual_grants_for_user' => r_diag_file_contains($files['model'], 'function get_admin_recent_manual_grants_for_user'),
    'get_admin_recent_manual_grants_for_course' => r_diag_file_contains($files['model'], 'function get_admin_recent_manual_grants_for_course'),
);
r_diag_section('Admin summary method availability', $method_status);
r_diag_assert($failures, !in_array(false, $method_status, true), 'One or more admin summary read methods are missing.');

$integration_status = array(
    'admin_controller_loads_summary_model' => r_diag_file_contains($files['admin_controller'], 'get_admin_user_entitlement_summary') && r_diag_file_contains($files['admin_controller'], 'get_admin_course_entitlement_summary'),
    'admin_controller_capability_guard' => r_diag_file_contains($files['admin_controller'], 'can_view_youngo_entitlement_summary') && r_diag_file_contains($files['admin_controller'], 'grant_manual_access'),
    'user_edit_includes_summary' => r_diag_file_contains($files['user_edit_view'], 'youngo_user_entitlement_summary'),
    'course_edit_includes_summary' => r_diag_file_contains($files['course_edit_view'], 'youngo_course_entitlement_summary'),
    'manual_grants_route_exists' => r_diag_file_contains($files['routes'], 'admin/youngo/manual-grants'),
);
r_diag_section('Admin summary integration wiring', $integration_status);
r_diag_assert($failures, !in_array(false, $integration_status, true), 'Admin summary integration is not fully wired.');

$phase_2m_schema = array(
    'youngo_course_access.revoked_by_user_id' => r_diag_column_exists($mysqli, 'youngo_course_access', 'revoked_by_user_id'),
    'youngo_course_access.revoke_note' => r_diag_column_exists($mysqli, 'youngo_course_access', 'revoke_note'),
    'youngo_user_subscriptions.revoked_by_user_id' => r_diag_column_exists($mysqli, 'youngo_user_subscriptions', 'revoked_by_user_id'),
    'youngo_user_subscriptions.revoke_note' => r_diag_column_exists($mysqli, 'youngo_user_subscriptions', 'revoke_note'),
);
r_diag_section('Phase 2M schema readiness', $phase_2m_schema);
r_diag_assert($failures, !in_array(false, $phase_2m_schema, true), 'Phase 2M schema is not fully applied.');

$counts = array();
foreach (array('users', 'course', 'enrol', 'payment', 'watch_histories', 'watched_duration', 'youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants', 'youngo_checkout_orders', 'youngo_coupon_usages') as $table) {
    $counts[$table] = r_diag_count($mysqli, $table);
}
r_diag_section('Admin entitlement related row counts', $counts);

$currency = array(
    'expected_currency' => 'EGP',
    'system_currency' => null,
    'system_currency_is_expected' => false,
    'non_egp_subscription_plan_count' => null,
);
if (r_diag_table_exists($mysqli, 'settings')) {
    $settings = r_diag_query($mysqli, "SELECT `value` FROM settings WHERE `key` = 'system_currency' LIMIT 1");
    $currency['system_currency'] = isset($settings[0]['value']) ? trim($settings[0]['value']) : null;
    $currency['system_currency_is_expected'] = $currency['system_currency'] === 'EGP';
}
if (r_diag_table_exists($mysqli, 'youngo_subscription_plans')) {
    $non_egp = r_diag_query($mysqli, "SELECT COUNT(*) AS c FROM youngo_subscription_plans WHERE UPPER(currency) <> 'EGP'");
    $currency['non_egp_subscription_plan_count'] = isset($non_egp[0]['c']) ? (int) $non_egp[0]['c'] : null;
}
r_diag_section('Phase 2L currency readiness', $currency);
r_diag_assert($failures, $currency['system_currency_is_expected'] === true, 'System currency is not EGP.');
r_diag_assert($failures, $currency['non_egp_subscription_plan_count'] === 0, 'One or more subscription plans are not EGP.');

r_diag_section('Read-only safety', array(
    'result' => 'Diagnostic used file reads and SELECT/SHOW-only queries, blocked write SQL verbs, created no sessions/cookies, and did not modify data.',
));

r_diag_section('Result', array(
    'status' => empty($failures) ? 'PASS' : 'FAIL',
    'failures' => $failures,
));

exit(empty($failures) ? 0 : 1);
