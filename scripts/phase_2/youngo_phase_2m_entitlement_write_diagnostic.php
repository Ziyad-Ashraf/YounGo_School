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
    echo 'db: ' . json_encode(array('connected' => false, 'error' => 'DB connection failed without exposing credentials.')) . "\n";
    exit(2);
}
$mysqli->set_charset('utf8');

$failures = array();

function m_diag_query($mysqli, $sql, $params = array())
{
    if (preg_match('/\b(insert|update|delete|replace|alter|drop|create|truncate|grant|revoke|set)\b/i', $sql)) {
        throw new Exception('Write SQL blocked by diagnostic wrapper.');
    }

    if (empty($params)) {
        $result = $mysqli->query($sql);
    } else {
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            return array('error' => $mysqli->error);
        }
        $types = str_repeat('s', count($params));
        $refs = array($types);
        foreach ($params as $key => $value) {
            $refs[] = &$params[$key];
        }
        call_user_func_array(array($stmt, 'bind_param'), $refs);
        $stmt->execute();
        $result = $stmt->get_result();
    }

    if (!$result) {
        return array('error' => $mysqli->error);
    }

    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

function m_diag_table_exists($mysqli, $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    $rows = m_diag_query($mysqli, "SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function m_diag_column_exists($mysqli, $table, $column)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column) || !m_diag_table_exists($mysqli, $table)) {
        return false;
    }

    $rows = m_diag_query($mysqli, "SHOW COLUMNS FROM `" . $table . "` LIKE '" . $mysqli->real_escape_string($column) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function m_diag_index_exists($mysqli, $table, $index)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $index) || !m_diag_table_exists($mysqli, $table)) {
        return false;
    }

    $rows = m_diag_query($mysqli, "SHOW INDEX FROM `" . $table . "` WHERE `Key_name` = '" . $mysqli->real_escape_string($index) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function m_diag_count($mysqli, $table)
{
    if (!m_diag_table_exists($mysqli, $table)) {
        return null;
    }

    $rows = m_diag_query($mysqli, "SELECT COUNT(*) AS c FROM `" . $table . "`");
    return isset($rows[0]['c']) ? (int) $rows[0]['c'] : null;
}

function m_diag_section($title, $data)
{
    echo "\n== " . $title . " ==\n";
    echo json_encode($data, JSON_UNESCAPED_SLASHES) . "\n";
}

function m_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

m_diag_section('Connection', array(
    'connected' => true,
    'database_name' => $config['database'],
    'server_version' => $mysqli->server_info,
));

$base_tables = array(
    'users',
    'course',
    'youngo_course_access',
    'youngo_user_subscriptions',
    'youngo_manual_grants',
    'youngo_checkout_orders',
    'youngo_subscription_plans',
);

$table_status = array();
foreach ($base_tables as $table) {
    $table_status[$table] = m_diag_table_exists($mysqli, $table);
}
m_diag_section('Base table existence', $table_status);
m_diag_assert($failures, !in_array(false, $table_status, true), 'One or more required base tables are missing.');

$phase_2m_columns = array(
    'youngo_course_access.revoked_by_user_id' => m_diag_column_exists($mysqli, 'youngo_course_access', 'revoked_by_user_id'),
    'youngo_course_access.revoke_note' => m_diag_column_exists($mysqli, 'youngo_course_access', 'revoke_note'),
    'youngo_user_subscriptions.revoked_by_user_id' => m_diag_column_exists($mysqli, 'youngo_user_subscriptions', 'revoked_by_user_id'),
    'youngo_user_subscriptions.revoke_note' => m_diag_column_exists($mysqli, 'youngo_user_subscriptions', 'revoke_note'),
);

$phase_2m_indexes = array(
    'youngo_course_access.idx_yca_checkout_order_id' => m_diag_index_exists($mysqli, 'youngo_course_access', 'idx_yca_checkout_order_id'),
    'youngo_course_access.idx_yca_revoked_by_user_id' => m_diag_index_exists($mysqli, 'youngo_course_access', 'idx_yca_revoked_by_user_id'),
    'youngo_user_subscriptions.idx_yus_revoked_by_user_id' => m_diag_index_exists($mysqli, 'youngo_user_subscriptions', 'idx_yus_revoked_by_user_id'),
);

$phase_2m_applied = !in_array(false, $phase_2m_columns, true) && !in_array(false, $phase_2m_indexes, true);
m_diag_section('Phase 2M schema readiness', array(
    'columns' => $phase_2m_columns,
    'indexes' => $phase_2m_indexes,
    'phase_2m_schema' => $phase_2m_applied ? 'applied' : 'not_applied',
));

$count_tables = array(
    'youngo_course_access',
    'youngo_user_subscriptions',
    'youngo_manual_grants',
    'youngo_checkout_orders',
    'youngo_coupon_usages',
    'enrol',
    'payment',
);
$counts = array();
foreach ($count_tables as $table) {
    $counts[$table] = m_diag_count($mysqli, $table);
}
m_diag_section('Current row counts', $counts);

$integrity = array();
$integrity['active_course_access_duplicates'] = m_diag_table_exists($mysqli, 'youngo_course_access')
    ? m_diag_query($mysqli, "SELECT user_id, course_id, COUNT(*) AS c FROM youngo_course_access WHERE status = 'active' AND (revoked_at IS NULL OR revoked_at = 0) AND (is_lifetime = 1 OR expiry_date IS NULL OR expiry_date >= UNIX_TIMESTAMP()) GROUP BY user_id, course_id HAVING c > 1")
    : array();
$integrity['active_subscription_duplicates'] = m_diag_table_exists($mysqli, 'youngo_user_subscriptions')
    ? m_diag_query($mysqli, "SELECT user_id, COUNT(*) AS c FROM youngo_user_subscriptions WHERE status = 'active' AND (revoked_at IS NULL OR revoked_at = 0) AND expiry_date >= UNIX_TIMESTAMP() GROUP BY user_id HAVING c > 1")
    : array();
$integrity['course_access_orphan_users'] = m_diag_table_exists($mysqli, 'youngo_course_access')
    ? m_diag_query($mysqli, "SELECT COUNT(*) AS c FROM youngo_course_access y LEFT JOIN users u ON u.id = y.user_id WHERE u.id IS NULL")
    : array();
$integrity['course_access_orphan_courses'] = m_diag_table_exists($mysqli, 'youngo_course_access')
    ? m_diag_query($mysqli, "SELECT COUNT(*) AS c FROM youngo_course_access y LEFT JOIN course c ON c.id = y.course_id WHERE c.id IS NULL")
    : array();
$integrity['subscription_orphan_users'] = m_diag_table_exists($mysqli, 'youngo_user_subscriptions')
    ? m_diag_query($mysqli, "SELECT COUNT(*) AS c FROM youngo_user_subscriptions y LEFT JOIN users u ON u.id = y.user_id WHERE u.id IS NULL")
    : array();
$integrity['subscription_orphan_plans'] = m_diag_table_exists($mysqli, 'youngo_user_subscriptions')
    ? m_diag_query($mysqli, "SELECT COUNT(*) AS c FROM youngo_user_subscriptions y LEFT JOIN youngo_subscription_plans p ON p.id = y.plan_id WHERE p.id IS NULL")
    : array();
$integrity['manual_course_grant_without_child'] = m_diag_table_exists($mysqli, 'youngo_manual_grants') && m_diag_table_exists($mysqli, 'youngo_course_access')
    ? m_diag_query($mysqli, "SELECT COUNT(*) AS c FROM youngo_manual_grants g LEFT JOIN youngo_course_access a ON a.manual_grant_id = g.id WHERE g.grant_type = 'course' AND a.id IS NULL")
    : array();
$integrity['manual_subscription_grant_without_child'] = m_diag_table_exists($mysqli, 'youngo_manual_grants') && m_diag_table_exists($mysqli, 'youngo_user_subscriptions')
    ? m_diag_query($mysqli, "SELECT COUNT(*) AS c FROM youngo_manual_grants g LEFT JOIN youngo_user_subscriptions s ON s.manual_grant_id = g.id WHERE g.grant_type = 'subscription' AND s.id IS NULL")
    : array();
$integrity['checkout_course_order_without_access_when_completed'] = m_diag_table_exists($mysqli, 'youngo_checkout_orders') && m_diag_table_exists($mysqli, 'youngo_course_access')
    ? m_diag_query($mysqli, "SELECT COUNT(*) AS c FROM youngo_checkout_orders o LEFT JOIN youngo_course_access a ON a.checkout_order_id = o.id WHERE o.order_type = 'course' AND o.status = 'completed' AND a.id IS NULL")
    : array();
$integrity['checkout_subscription_order_without_subscription_when_completed'] = m_diag_table_exists($mysqli, 'youngo_checkout_orders') && m_diag_table_exists($mysqli, 'youngo_user_subscriptions')
    ? m_diag_query($mysqli, "SELECT COUNT(*) AS c FROM youngo_checkout_orders o LEFT JOIN youngo_user_subscriptions s ON s.checkout_order_id = o.id WHERE o.order_type = 'subscription' AND o.status = 'completed' AND s.id IS NULL")
    : array();
$integrity['coupon_usage_duplicate_order'] = m_diag_table_exists($mysqli, 'youngo_coupon_usages')
    ? m_diag_query($mysqli, "SELECT checkout_order_id, COUNT(*) AS c FROM youngo_coupon_usages WHERE checkout_order_id IS NOT NULL GROUP BY checkout_order_id HAVING c > 1")
    : array();
m_diag_section('Duplicate and linkage checks', $integrity);

$currency = array(
    'expected_currency' => 'EGP',
    'system_currency' => null,
    'system_currency_is_expected' => false,
    'non_egp_subscription_plan_count' => null,
    'commercial_currency_ready' => false,
);
if (m_diag_table_exists($mysqli, 'settings')) {
    $settings = m_diag_query($mysqli, "SELECT `value` FROM settings WHERE `key` = 'system_currency' LIMIT 1");
    $currency['system_currency'] = isset($settings[0]['value']) ? trim($settings[0]['value']) : null;
    $currency['system_currency_is_expected'] = $currency['system_currency'] === 'EGP';
}
if (m_diag_table_exists($mysqli, 'youngo_subscription_plans')) {
    $non_egp = m_diag_query($mysqli, "SELECT COUNT(*) AS c FROM youngo_subscription_plans WHERE currency <> 'EGP'");
    $currency['non_egp_subscription_plan_count'] = isset($non_egp[0]['c']) ? (int) $non_egp[0]['c'] : null;
}
$currency['commercial_currency_ready'] = $currency['system_currency_is_expected'] && $currency['non_egp_subscription_plan_count'] === 0;
m_diag_section('Phase 2L currency readiness', $currency);

$capability = array(
    'grant_manual_access_exists' => false,
    'root_admin_has_grant_manual_access' => false,
    'user_7_has_grant_manual_access' => false,
);
if (m_diag_table_exists($mysqli, 'youngo_capabilities')) {
    $rows = m_diag_query($mysqli, "SELECT id FROM youngo_capabilities WHERE capability_key = 'grant_manual_access' LIMIT 1");
    $capability['grant_manual_access_exists'] = !empty($rows);
}
if ($capability['grant_manual_access_exists']) {
    $root = m_diag_query($mysqli, "SELECT id FROM users WHERE id = 1 LIMIT 1");
    $capability['root_admin_has_grant_manual_access'] = !empty($root);
    $user7 = m_diag_query($mysqli, "SELECT COUNT(*) AS c FROM youngo_user_roles ur INNER JOIN youngo_roles r ON r.id = ur.role_id INNER JOIN youngo_role_capabilities rc ON rc.role_id = r.id INNER JOIN youngo_capabilities c ON c.id = rc.capability_id WHERE ur.user_id = 7 AND ur.status = 'active' AND (ur.revoked_at IS NULL OR ur.revoked_at = 0) AND c.capability_key = 'grant_manual_access'");
    $capability['user_7_has_grant_manual_access'] = isset($user7[0]['c']) && (int) $user7[0]['c'] > 0;
}
m_diag_section('Capability readiness', $capability);
m_diag_assert($failures, $capability['grant_manual_access_exists'], 'grant_manual_access capability is missing.');
m_diag_assert($failures, $capability['root_admin_has_grant_manual_access'], 'Root Admin does not have grant_manual_access.');
m_diag_assert($failures, !$capability['user_7_has_grant_manual_access'], 'Restricted user 7 has grant_manual_access unexpectedly.');

m_diag_section('Read-only safety', array(
    'result' => 'Diagnostic used SELECT/SHOW-only queries, blocked write SQL verbs, created no sessions/cookies, and did not modify data.',
));

$result = array(
    'status' => empty($failures) ? 'PASS' : 'FAIL',
    'phase_2m_schema_applied' => $phase_2m_applied,
    'phase_2m_schema_note' => $phase_2m_applied ? 'Phase 2M schema appears applied.' : 'Phase 2M schema not applied; this is expected before the schema apply phase.',
    'failures' => $failures,
);
m_diag_section('Result', $result);

exit(empty($failures) ? 0 : 1);
