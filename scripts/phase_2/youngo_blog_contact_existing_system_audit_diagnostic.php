<?php

/**
 * DEMO.4B.1 read-only diagnostic.
 *
 * Checks the Blog/Contact audit report and scope boundaries.
 * This script must not submit forms or write database rows.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
$reportPath = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'qa' . DIRECTORY_SEPARATOR . 'youngo_blog_contact_existing_system_audit.md';
$routesPath = $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routes.php';

$checks = array();

function youngo_audit_check(&$checks, $name, $passed, $details = '')
{
    $checks[] = array(
        'name' => $name,
        'passed' => (bool) $passed,
        'details' => $details,
    );
}

function youngo_audit_normalize_path($path)
{
    return str_replace('\\', '/', trim((string) $path));
}

function youngo_audit_git_status_paths($root)
{
    $command = 'git -C ' . escapeshellarg($root) . ' status --short';
    exec($command, $output, $code);

    if ($code !== 0) {
        return array(null, 'git status failed');
    }

    $paths = array();
    foreach ($output as $line) {
        $path = trim(substr($line, 3));
        if ($path === '') {
            continue;
        }

        if (strpos($path, ' -> ') !== false) {
            $parts = explode(' -> ', $path);
            $path = end($parts);
        }

        $paths[] = youngo_audit_normalize_path($path);
    }

    return array($paths, '');
}

function youngo_audit_table_count($mysqli, $database, $table)
{
    $tableExistsSql = "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . $mysqli->real_escape_string($database) . "' AND TABLE_NAME = '" . $mysqli->real_escape_string($table) . "'";
    $existsResult = $mysqli->query($tableExistsSql);
    if (!$existsResult) {
        return null;
    }

    $existsRow = $existsResult->fetch_row();
    if (empty($existsRow[0])) {
        return null;
    }

    $quotedTable = '`' . str_replace('`', '``', $table) . '`';
    $countResult = $mysqli->query("SELECT COUNT(*) FROM $quotedTable");
    if (!$countResult) {
        return null;
    }

    $countRow = $countResult->fetch_row();
    return isset($countRow[0]) ? (int) $countRow[0] : null;
}

$reportExists = is_file($reportPath);
youngo_audit_check($checks, 'report exists', $reportExists, $reportPath);

$report = $reportExists ? file_get_contents($reportPath) : '';
$requiredReportNeedles = array(
    'Existing Blog System Findings',
    'Existing Contact System Findings',
    'Existing Routes Found',
    'Existing Controllers, Models, And Views Found',
    'Existing DB Tables And Settings Found',
    'Existing Admin Management Screens Found',
    'Recommended Blog Implementation Path',
    'Recommended Contact Implementation Path',
    'English/Arabic Route Recommendation',
    'What Not To Build From Scratch',
    'What Can Be Reused',
    'What Still Needs New YounGo Views',
    'Payment Safety',
);

foreach ($requiredReportNeedles as $needle) {
    youngo_audit_check($checks, 'report includes ' . $needle, strpos($report, $needle) !== false);
}

list($statusPaths, $statusError) = youngo_audit_git_status_paths($root);
$allowedChangedPaths = array(
    'docs/qa/youngo_blog_contact_existing_system_audit.md',
    'scripts/phase_2/youngo_blog_contact_existing_system_audit_diagnostic.php',
);

if ($statusPaths === null) {
    youngo_audit_check($checks, 'git status readable', false, $statusError);
} else {
    $unexpected = array();
    foreach ($statusPaths as $path) {
        if (!in_array($path, $allowedChangedPaths, true)) {
            $unexpected[] = $path;
        }
    }

    youngo_audit_check($checks, 'no source files changed except report/diagnostic', count($unexpected) === 0, implode(', ', $unexpected));
    youngo_audit_check($checks, 'routes unchanged', !in_array('application/config/routes.php', $statusPaths, true));

    $paymentTouched = array();
    foreach ($statusPaths as $path) {
        if (preg_match('#(^|/)(Payment\.php|payment|checkout|coupon|paymob|cart)(/|$)#i', $path)) {
            $paymentTouched[] = $path;
        }
    }
    youngo_audit_check($checks, 'no payment checkout coupon Paymob cart files changed', count($paymentTouched) === 0, implode(', ', $paymentTouched));
}

$routes = is_file($routesPath) ? file_get_contents($routesPath) : '';
youngo_audit_check($checks, 'routes file readable', $routes !== '', $routesPath);
youngo_audit_check($checks, 'no English prefixed route added', !preg_match('/\$route\s*\[\s*[\'"]en(?:[\/\'"])/', $routes));
youngo_audit_check($checks, 'no Arabic blog contact routes added in audit', strpos($routes, "ar/blog") === false && strpos($routes, "ar/contact") === false);

$databaseChecksRun = false;
$databaseDetails = array();
$databasePath = $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
if (is_file($databasePath) && !defined('BASEPATH')) {
    define('BASEPATH', $root . DIRECTORY_SEPARATOR);
}
if (is_file($databasePath) && !defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}

if (is_file($databasePath)) {
    $db = array();
    include $databasePath;

    if (isset($db['default']) && extension_loaded('mysqli')) {
        $cfg = $db['default'];
        $mysqli = @new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database']);
        if (!$mysqli->connect_errno) {
            $databaseChecksRun = true;
            $tablesExpectedZero = array(
                'blogs',
                'blog_category',
                'blog_comments',
                'contact',
                'payment',
                'youngo_checkout_orders',
                'youngo_coupon_usages',
                'youngo_course_access',
                'youngo_user_subscriptions',
                'youngo_manual_grants',
            );

            foreach ($tablesExpectedZero as $table) {
                $count = youngo_audit_table_count($mysqli, $cfg['database'], $table);
                if ($count !== null) {
                    $databaseDetails[] = $table . '=' . $count;
                    youngo_audit_check($checks, 'table count unchanged/clean: ' . $table, $count === 0, (string) $count);
                }
            }

            $mysqli->close();
        }
    }
}

youngo_audit_check($checks, 'database read-only checks available', $databaseChecksRun, implode(', ', $databaseDetails));

$failed = array();
foreach ($checks as $check) {
    $status = $check['passed'] ? 'PASS' : 'FAIL';
    echo $status . ' - ' . $check['name'];
    if ($check['details'] !== '') {
        echo ' :: ' . $check['details'];
    }
    echo PHP_EOL;

    if (!$check['passed']) {
        $failed[] = $check['name'];
    }
}

if (count($failed) > 0) {
    echo 'DEMO.4B.1 Blog/Contact audit diagnostic FAILED' . PHP_EOL;
    exit(1);
}

echo 'DEMO.4B.1 Blog/Contact audit diagnostic PASSED' . PHP_EOL;
exit(0);
