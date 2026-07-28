<?php
/**
 * YounGo Phase 2E.1 read-only preflight script.
 *
 * This script does not create, alter, drop, insert, update, or delete data.
 * Run from the project root with:
 *
 *   php scripts/phase_2/youngo_phase_2e_preflight.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This preflight script is CLI-only.\n";
    exit(1);
}

$projectRoot = dirname(__DIR__, 2);
$dbConfigPath = $projectRoot . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

if (!is_file($dbConfigPath)) {
    echo "Database config not found at application/config/database.php\n";
    exit(1);
}

if (!defined('BASEPATH')) {
    define('BASEPATH', $projectRoot . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
}

if (!defined('APPPATH')) {
    define('APPPATH', $projectRoot . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);
}

if (!defined('FCPATH')) {
    define('FCPATH', $projectRoot . DIRECTORY_SEPARATOR);
}

if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}

$db = [];
require $dbConfigPath;

if (!isset($db['default']) || !is_array($db['default'])) {
    echo "Default database configuration was not found.\n";
    exit(1);
}

$config = $db['default'];
$requiredConfigKeys = ['hostname', 'username', 'database'];
foreach ($requiredConfigKeys as $key) {
    if (!array_key_exists($key, $config) || $config[$key] === '') {
        echo "Database configuration is missing required key: {$key}\n";
        exit(1);
    }
}

mysqli_report(MYSQLI_REPORT_OFF);

$mysqli = @new mysqli(
    $config['hostname'],
    $config['username'],
    isset($config['password']) ? $config['password'] : '',
    $config['database']
);

if ($mysqli->connect_errno) {
    echo "Database connection failed. Check local database settings and server status.\n";
    exit(1);
}

$charset = isset($config['char_set']) && $config['char_set'] !== '' ? $config['char_set'] : 'utf8';
@$mysqli->set_charset($charset);

function print_section($title)
{
    echo "\n== {$title} ==\n";
}

function fetch_all_assoc(mysqli $mysqli, $sql)
{
    $trimmed = ltrim($sql);
    if (!preg_match('/^(SELECT|SHOW|DESCRIBE)\b/i', $trimmed)) {
        throw new RuntimeException('Blocked non-read-only query in preflight script.');
    }

    $result = $mysqli->query($sql);
    if ($result === false) {
        return [
            [
                'error' => $mysqli->error,
                'sql' => $sql,
            ],
        ];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
    return $rows;
}

function print_rows(array $rows)
{
    if (!$rows) {
        echo "(none)\n";
        return;
    }

    foreach ($rows as $row) {
        echo json_encode($row, JSON_UNESCAPED_SLASHES) . "\n";
    }
}

function table_exists(mysqli $mysqli, $table)
{
    $tableEscaped = $mysqli->real_escape_string($table);
    $rows = fetch_all_assoc($mysqli, "SHOW TABLES LIKE '{$tableEscaped}'");
    return count($rows) > 0;
}

function column_exists(mysqli $mysqli, $table, $column)
{
    $tableEscaped = str_replace('`', '``', $table);
    $columnEscaped = $mysqli->real_escape_string($column);
    $rows = fetch_all_assoc($mysqli, "SHOW COLUMNS FROM `{$tableEscaped}` LIKE '{$columnEscaped}'");
    return count($rows) > 0;
}

print_section('Connection');
print_rows(fetch_all_assoc($mysqli, 'SELECT DATABASE() AS database_name, VERSION() AS server_version'));

print_section('Existing youngo_% tables');
print_rows(fetch_all_assoc($mysqli, "SHOW TABLES LIKE 'youngo\\_%'"));

$legacyTables = [
    'users',
    'role',
    'permissions',
    'enrol',
    'payment',
    'course',
    'coupons',
    'watch_histories',
    'watched_duration',
    'lesson',
    'section',
    'settings',
    'frontend_settings',
];

print_section('Required legacy tables');
foreach ($legacyTables as $table) {
    echo $table . ': ' . (table_exists($mysqli, $table) ? 'present' : 'missing') . "\n";
}

$requiredColumns = [
    'users' => ['id', 'email', 'role_id', 'is_instructor', 'status'],
    'permissions' => ['id', 'admin_id', 'permissions'],
    'course' => ['id', 'price', 'discount_flag', 'discounted_price', 'is_free_course', 'expiry_period', 'user_id', 'creator', 'multi_instructor'],
    'coupons' => ['id', 'code', 'discount_percentage', 'expiry_date'],
    'enrol' => ['id', 'user_id', 'course_id', 'expiry_date'],
    'payment' => ['id', 'user_id', 'course_id'],
    'watch_histories' => ['student_id', 'course_id'],
    'watched_duration' => ['watched_id', 'watched_student_id', 'watched_course_id'],
];

print_section('Required legacy columns');
foreach ($requiredColumns as $table => $columns) {
    if (!table_exists($mysqli, $table)) {
        echo $table . ': table missing, columns not checked' . "\n";
        continue;
    }

    foreach ($columns as $column) {
        echo $table . '.' . $column . ': ' . (column_exists($mysqli, $table, $column) ? 'present' : 'missing') . "\n";
    }
}

print_section('Admin and permissions state');
if (table_exists($mysqli, 'users') && table_exists($mysqli, 'permissions')) {
    print_rows(fetch_all_assoc(
        $mysqli,
        "SELECT u.id, u.email, u.role_id, u.is_instructor, u.status, " .
        "CASE WHEN p.id IS NULL THEN 'root_admin_by_legacy_missing_permissions_row' ELSE 'restricted_admin_by_permissions_row' END AS legacy_admin_state, " .
        "p.permissions " .
        "FROM users u LEFT JOIN permissions p ON p.admin_id = u.id " .
        "WHERE u.role_id = 1 ORDER BY u.id"
    ));

    print_rows(fetch_all_assoc(
        $mysqli,
        "SELECT COUNT(*) AS inferred_root_admin_count " .
        "FROM users u LEFT JOIN permissions p ON p.admin_id = u.id " .
        "WHERE u.role_id = 1 AND p.id IS NULL"
    ));
}

print_section('Client admin check');
if (table_exists($mysqli, 'users') && table_exists($mysqli, 'permissions')) {
    print_rows(fetch_all_assoc(
        $mysqli,
        "SELECT u.id, u.email, u.role_id, u.is_instructor, u.status, p.permissions " .
        "FROM users u LEFT JOIN permissions p ON p.admin_id = u.id " .
        "WHERE u.id = 7 OR u.email = 'client@gmail.com'"
    ));
}

print_section('Legacy row counts');
$countTables = ['users', 'permissions', 'enrol', 'payment', 'course', 'coupons', 'watch_histories', 'watched_duration'];
foreach ($countTables as $table) {
    if (!table_exists($mysqli, $table)) {
        echo $table . ': table missing' . "\n";
        continue;
    }

    $tableEscaped = str_replace('`', '``', $table);
    print_rows(fetch_all_assoc($mysqli, "SELECT '{$table}' AS table_name, COUNT(*) AS row_count FROM `{$tableEscaped}`"));
}

print_section('Preflight complete');
echo "Read-only preflight completed. No writes were executed.\n";

$mysqli->close();
