<?php
/**
 * Phase 2V.1 role assignment diagnostic.
 *
 * Read-only checks for YounGo role assignment UI, permission bridge, and scope boundaries.
 */

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
$warnings = array();

function v21_section($title, $data)
{
    echo "\n== " . $title . " ==\n";
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
}

function v21_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function v21_file($root, $path)
{
    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
}

function v21_read($root, $path)
{
    $file = v21_file($root, $path);
    return is_file($file) ? file_get_contents($file) : null;
}

function v21_contains($content, $needle)
{
    return is_string($content) && strpos($content, $needle) !== false;
}

function v21_query($mysqli, $sql)
{
    if (preg_match('/^\s*(insert|update|delete|replace|alter|drop|create|truncate|grant|revoke|set|load|call)\b/i', $sql)) {
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

function v21_table_exists($mysqli, $table)
{
    $rows = v21_query($mysqli, "SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function v21_count($mysqli, $table)
{
    if (!v21_table_exists($mysqli, $table)) {
        return null;
    }
    $rows = v21_query($mysqli, 'SELECT COUNT(*) AS c FROM `' . $table . '`');
    return isset($rows[0]['c']) ? (int) $rows[0]['c'] : null;
}

function v21_slug_exists($mysqli, $table, $field, $value)
{
    if (!v21_table_exists($mysqli, $table)) {
        return false;
    }
    $rows = v21_query($mysqli, "SELECT COUNT(*) AS c FROM `" . $table . "` WHERE `" . $field . "` = '" . $mysqli->real_escape_string($value) . "'");
    return isset($rows[0]['c']) && (int) $rows[0]['c'] > 0;
}

$required_files = array(
    'controller' => 'application/controllers/Youngo_role_assignments.php',
    'model' => 'application/models/Youngo_role_assignment_model.php',
    'view' => 'application/views/backend/admin/youngo_role_assignments.php',
    'routes' => 'application/config/routes.php',
    'navigation' => 'application/views/backend/admin/navigation.php',
    'admin_manage_roles_up' => 'database/phase_2/youngo_phase_2v1_admin_manage_roles_up.sql',
    'admin_manage_roles_down' => 'database/phase_2/youngo_phase_2v1_admin_manage_roles_down.sql',
);

$file_status = array();
$contents = array();
foreach ($required_files as $key => $path) {
    $file_status[$path] = is_file(v21_file($root, $path));
    $contents[$key] = v21_read($root, $path);
}
v21_section('Required file availability', $file_status);
foreach ($file_status as $path => $exists) {
    v21_assert($failures, $exists, 'Missing required file: ' . $path);
}

$route_checks = array(
    'index_route' => v21_contains($contents['routes'], "admin/youngo/role-assignments"),
    'update_route' => v21_contains($contents['routes'], "admin/youngo/role-assignments/update"),
    'navigation_label' => v21_contains($contents['navigation'], 'Role Assignments'),
    'navigation_guard_manage_roles' => v21_contains($contents['navigation'], 'manage_roles'),
);
v21_section('Route and navigation checks', $route_checks);
foreach ($route_checks as $label => $ok) {
    v21_assert($failures, $ok, 'Route/navigation check failed: ' . $label);
}

$view_checks = array(
    'admin_toggle' => v21_contains($contents['view'], 'data-role-toggle="admin"'),
    'content_toggle' => v21_contains($contents['view'], 'data-role-toggle="content"'),
    'course_toggle' => v21_contains($contents['view'], 'data-role-toggle="course"'),
    'instructor_toggle' => v21_contains($contents['view'], 'data-role-toggle="instructor"'),
    'root_admin_read_only' => v21_contains($contents['view'], 'Protected Root Admin') && v21_contains($contents['view'], 'disabled'),
    'admin_mutual_exclusion_js' => v21_contains($contents['view'], 'youngo-admin-role-toggle') && v21_contains($contents['view'], 'youngo-granular-role-toggle'),
);
v21_section('View behavior markers', $view_checks);
foreach ($view_checks as $label => $ok) {
    v21_assert($failures, $ok, 'View behavior marker missing: ' . $label);
}

$model_checks = array(
    'root_rejects_updates' => v21_contains($contents['model'], 'protected_root_admin') && v21_contains($contents['model'], 'is_protected_root'),
    'admin_mutual_exclusion_server' => v21_contains($contents['model'], 'admin_mutual_exclusion'),
    'legacy_course_bridge' => v21_contains($contents['model'], "'course'") && v21_contains($contents['model'], 'sync_legacy_permissions'),
    'legacy_category_bridge' => v21_contains($contents['model'], "'category'") && v21_contains($contents['model'], 'sync_legacy_permissions'),
    'preserves_unmanaged_permissions' => v21_contains($contents['model'], 'array_diff($permissions, $this->managed_legacy_permissions)'),
    'permission_row_upsert' => v21_contains($contents['model'], 'upsert_legacy_permission_row'),
    'admin_display_suppresses_granular_toggles' => v21_contains($contents['model'], '$admin_on') && v21_contains($contents['model'], '!$admin_on &&'),
    'does_not_write_passwords' => !v21_contains($contents['model'], 'password'),
);
v21_section('Model bridge markers', $model_checks);
foreach ($model_checks as $label => $ok) {
    v21_assert($failures, $ok, 'Model bridge marker failed: ' . $label);
}

$controller_checks = array(
    'guarded_by_manage_roles' => v21_contains($contents['controller'], "youngo_require_capability('manage_roles'"),
    'loads_model' => v21_contains($contents['controller'], 'Youngo_role_assignment_model'),
    'post_update' => v21_contains($contents['controller'], 'require_post'),
);
v21_section('Controller guard markers', $controller_checks);
foreach ($controller_checks as $label => $ok) {
    v21_assert($failures, $ok, 'Controller guard marker failed: ' . $label);
}

$table_checks = array(
    'users' => v21_table_exists($mysqli, 'users'),
    'permissions' => v21_table_exists($mysqli, 'permissions'),
    'youngo_roles' => v21_table_exists($mysqli, 'youngo_roles'),
    'youngo_capabilities' => v21_table_exists($mysqli, 'youngo_capabilities'),
    'youngo_role_capabilities' => v21_table_exists($mysqli, 'youngo_role_capabilities'),
    'youngo_user_roles' => v21_table_exists($mysqli, 'youngo_user_roles'),
);
v21_section('Role/permission table availability', $table_checks);
foreach ($table_checks as $label => $ok) {
    v21_assert($failures, $ok, 'Missing table: ' . $label);
}

$role_keys = array('admin', 'content_manager', 'course_manager', 'instructor');
$role_status = array();
foreach ($role_keys as $role_key) {
    $role_status[$role_key] = v21_slug_exists($mysqli, 'youngo_roles', 'role_key', $role_key);
}
v21_section('YounGo role availability', $role_status);
foreach ($role_status as $label => $ok) {
    v21_assert($failures, $ok, 'Missing YounGo role: ' . $label);
}

$capability_keys = array(
    'manage_roles',
    'manage_courses',
    'manage_course_categories',
    'manage_lessons',
    'publish_courses',
    'manage_homepage_content',
    'manage_static_content',
    'manage_media',
    'manage_assigned_course_lessons',
);
$capability_status = array();
foreach ($capability_keys as $capability_key) {
    $capability_status[$capability_key] = v21_slug_exists($mysqli, 'youngo_capabilities', 'capability_key', $capability_key);
}
v21_section('YounGo capability availability', $capability_status);
foreach ($capability_status as $label => $ok) {
    v21_assert($failures, $ok, 'Missing YounGo capability: ' . $label);
}

$admin_manage_roles_rows = array();
$scoped_manage_roles_rows = array();
if ($table_checks['youngo_roles'] && $table_checks['youngo_capabilities'] && $table_checks['youngo_role_capabilities']) {
    $admin_manage_roles_rows = v21_query(
        $mysqli,
        "SELECT r.`role_key`, c.`capability_key`
         FROM `youngo_role_capabilities` rc
         INNER JOIN `youngo_roles` r ON r.`id` = rc.`role_id`
         INNER JOIN `youngo_capabilities` c ON c.`id` = rc.`capability_id`
         WHERE r.`role_key` = 'admin'
           AND c.`capability_key` = 'manage_roles'"
    );
    $scoped_manage_roles_rows = v21_query(
        $mysqli,
        "SELECT r.`role_key`, c.`capability_key`
         FROM `youngo_role_capabilities` rc
         INNER JOIN `youngo_roles` r ON r.`id` = rc.`role_id`
         INNER JOIN `youngo_capabilities` c ON c.`id` = rc.`capability_id`
         WHERE r.`role_key` IN ('content_manager', 'course_manager', 'instructor')
           AND c.`capability_key` = 'manage_roles'"
    );
}
$admin_authority_checks = array(
    'up_sql_maps_admin_manage_roles' => v21_contains($contents['admin_manage_roles_up'], "r.`role_key` = 'admin'") && v21_contains($contents['admin_manage_roles_up'], "c.`capability_key` = 'manage_roles'"),
    'down_sql_removes_only_admin_manage_roles' => v21_contains($contents['admin_manage_roles_down'], "r.`role_key` = 'admin'") && v21_contains($contents['admin_manage_roles_down'], "c.`capability_key` = 'manage_roles'"),
    'admin_role_has_manage_roles' => is_array($admin_manage_roles_rows) && count($admin_manage_roles_rows) === 1,
    'scoped_roles_do_not_have_manage_roles' => is_array($scoped_manage_roles_rows) && count($scoped_manage_roles_rows) === 0,
);
v21_section('Admin manage_roles authority checks', $admin_authority_checks);
foreach ($admin_authority_checks as $label => $ok) {
    v21_assert($failures, $ok, 'Admin manage_roles authority check failed: ' . $label);
}

$counts = array(
    'users' => v21_count($mysqli, 'users'),
    'permissions' => v21_count($mysqli, 'permissions'),
    'youngo_user_roles' => v21_count($mysqli, 'youngo_user_roles'),
    'payment' => v21_count($mysqli, 'payment'),
    'youngo_checkout_orders' => v21_count($mysqli, 'youngo_checkout_orders'),
    'youngo_coupon_usages' => v21_count($mysqli, 'youngo_coupon_usages'),
);
v21_section('Current counts', $counts);

$diff_output = shell_exec('git diff --name-only 2>NUL');
$diff_files = array_values(array_filter(array_map('trim', explode("\n", (string) $diff_output))));
$status_output = shell_exec('git status --short 2>NUL');
$status_files = array();
foreach (array_filter(explode("\n", (string) $status_output)) as $status_line) {
    $path = '';
    if (preg_match('/^..\\s+(.+)$/', rtrim($status_line), $match)) {
        $path = trim($match[1]);
    }
    if ($path !== '') {
        $status_files[] = $path;
    }
}
$scope_files = array_values(array_unique(array_merge($diff_files, $status_files)));
$forbidden_changed = array();
foreach ($scope_files as $file) {
    if (preg_match('#(Payment|Paymob|checkout|order|coupon)#i', $file)) {
        $forbidden_changed[] = $file;
    }
}
$diff_checks = array(
    'changed_files' => $diff_files,
    'status_files' => $status_files,
    'payment_checkout_coupon_files_changed' => $forbidden_changed,
);
v21_section('Scope boundary checks', $diff_checks);
v21_assert($failures, empty($forbidden_changed), 'Payment/checkout/order/coupon/Paymob file changed during role assignment work.');

v21_section('Read-only safety', array(
    'result' => 'Diagnostic used file reads, git diff inspection, and SELECT/SHOW-only queries. It did not write DB or submit forms.',
));

if (!empty($warnings)) {
    v21_section('Warnings', $warnings);
}

if (!empty($failures)) {
    v21_section('Failures', $failures);
    exit(1);
}

v21_section('Result', array(
    'status' => 'PASS',
    'failures' => array(),
));
