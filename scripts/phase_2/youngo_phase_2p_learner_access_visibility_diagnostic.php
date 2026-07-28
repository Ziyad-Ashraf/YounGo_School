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

function p_diag_section($title, $data)
{
    echo "\n== " . $title . " ==\n";
    echo json_encode($data, JSON_UNESCAPED_SLASHES) . "\n";
}

function p_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function p_diag_query($mysqli, $sql)
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

function p_diag_table_exists($mysqli, $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    $rows = p_diag_query($mysqli, "SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function p_diag_column_exists($mysqli, $table, $column)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column) || !p_diag_table_exists($mysqli, $table)) {
        return false;
    }

    $rows = p_diag_query($mysqli, "SHOW COLUMNS FROM `" . $table . "` LIKE '" . $mysqli->real_escape_string($column) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function p_diag_count($mysqli, $table)
{
    if (!p_diag_table_exists($mysqli, $table)) {
        return null;
    }

    $rows = p_diag_query($mysqli, "SELECT COUNT(*) AS c FROM `" . $table . "`");
    return isset($rows[0]['c']) ? (int) $rows[0]['c'] : null;
}

function p_diag_file_contains($path, $needle)
{
    return is_file($path) && strpos(file_get_contents($path), $needle) !== false;
}

p_diag_section('Connection', array(
    'connected' => true,
    'database_name' => $config['database'],
    'server_version' => $mysqli->server_info,
));

$files = array(
    'model' => APPPATH . 'models' . DIRECTORY_SEPARATOR . 'Youngo_entitlement_model.php',
    'home_controller' => APPPATH . 'controllers' . DIRECTORY_SEPARATOR . 'Home.php',
    'my_courses_view' => APPPATH . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'youngo' . DIRECTORY_SEPARATOR . 'my_courses.php',
    'reload_my_courses_view' => APPPATH . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'youngo' . DIRECTORY_SEPARATOR . 'reload_my_courses.php',
    'my_access_view' => APPPATH . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'youngo' . DIRECTORY_SEPARATOR . 'my_access.php',
    'profile_menu' => APPPATH . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'youngo' . DIRECTORY_SEPARATOR . 'profile_menus.php',
);

$file_status = array();
foreach ($files as $key => $path) {
    $file_status[$key] = is_file($path);
}
p_diag_section('Required file availability', $file_status);
p_diag_assert($failures, !in_array(false, $file_status, true), 'One or more Phase 2P learner access files are missing.');

$method_status = array(
    'get_learner_course_access_items' => p_diag_file_contains($files['model'], 'function get_learner_course_access_items'),
    'get_learner_subscription_summary' => p_diag_file_contains($files['model'], 'function get_learner_subscription_summary'),
    'get_learner_access_counts' => p_diag_file_contains($files['model'], 'function get_learner_access_counts'),
    'Home::my_access' => p_diag_file_contains($files['home_controller'], 'function my_access'),
);
p_diag_section('Learner access method availability', $method_status);
p_diag_assert($failures, !in_array(false, $method_status, true), 'One or more learner access read methods are missing.');

$view_status = array(
    'my_courses_uses_prepared_items' => p_diag_file_contains($files['my_courses_view'], 'learner_course_access_items'),
    'my_courses_not_directly_legacy_only' => !p_diag_file_contains($files['my_courses_view'], 'user_model->my_courses'),
    'reload_my_courses_exists' => $file_status['reload_my_courses_view'],
    'reload_my_courses_uses_prepared_items' => p_diag_file_contains($files['reload_my_courses_view'], 'learner_course_access_items'),
    'my_access_view_exists' => $file_status['my_access_view'],
    'profile_menu_has_my_access' => p_diag_file_contains($files['profile_menu'], 'home/my_access'),
);
p_diag_section('Learner access view wiring', $view_status);
p_diag_assert($failures, !in_array(false, $view_status, true), 'Learner access views are not fully wired.');

$phase_2m_schema = array(
    'youngo_course_access.revoked_by_user_id' => p_diag_column_exists($mysqli, 'youngo_course_access', 'revoked_by_user_id'),
    'youngo_course_access.revoke_note' => p_diag_column_exists($mysqli, 'youngo_course_access', 'revoke_note'),
    'youngo_user_subscriptions.revoked_by_user_id' => p_diag_column_exists($mysqli, 'youngo_user_subscriptions', 'revoked_by_user_id'),
    'youngo_user_subscriptions.revoke_note' => p_diag_column_exists($mysqli, 'youngo_user_subscriptions', 'revoke_note'),
);
p_diag_section('Phase 2M schema readiness', $phase_2m_schema);
p_diag_assert($failures, !in_array(false, $phase_2m_schema, true), 'Phase 2M schema is not fully applied.');

$counts = array();
foreach (array('enrol', 'payment', 'youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants', 'youngo_checkout_orders', 'youngo_coupon_usages') as $table) {
    $counts[$table] = p_diag_count($mysqli, $table);
}
p_diag_section('Learner access related row counts', $counts);

$currency = array(
    'expected_currency' => 'EGP',
    'system_currency' => null,
    'system_currency_is_expected' => false,
    'non_egp_subscription_plan_count' => null,
);
if (p_diag_table_exists($mysqli, 'settings')) {
    $settings = p_diag_query($mysqli, "SELECT `value` FROM settings WHERE `key` = 'system_currency' LIMIT 1");
    $currency['system_currency'] = isset($settings[0]['value']) ? trim($settings[0]['value']) : null;
    $currency['system_currency_is_expected'] = $currency['system_currency'] === 'EGP';
}
if (p_diag_table_exists($mysqli, 'youngo_subscription_plans')) {
    $non_egp = p_diag_query($mysqli, "SELECT COUNT(*) AS c FROM youngo_subscription_plans WHERE UPPER(currency) <> 'EGP'");
    $currency['non_egp_subscription_plan_count'] = isset($non_egp[0]['c']) ? (int) $non_egp[0]['c'] : null;
}
p_diag_section('Phase 2L currency readiness', $currency);
p_diag_assert($failures, $currency['system_currency_is_expected'] === true, 'System currency is not EGP.');
p_diag_assert($failures, $currency['non_egp_subscription_plan_count'] === 0, 'One or more subscription plans are not EGP.');

p_diag_section('Read-only safety', array(
    'result' => 'Diagnostic used file reads and SELECT/SHOW-only queries, blocked write SQL verbs, created no sessions/cookies, and did not modify data.',
));

p_diag_section('Result', array(
    'status' => empty($failures) ? 'PASS' : 'FAIL',
    'failures' => $failures,
));

exit(empty($failures) ? 0 : 1);
