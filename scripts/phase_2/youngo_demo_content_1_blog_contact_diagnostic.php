<?php
/**
 * DEMO.CONTENT.1 Blog/Contact diagnostic.
 *
 * Read-only: this script only inspects source files and SELECTs database state.
 */

define('BASEPATH', true);
defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');

$root = realpath(__DIR__ . '/../..');
$failures = array();
$warnings = array();
$infos = array();

function yd1_record(&$bucket, $message)
{
    $bucket[] = $message;
}

function yd1_file($relative)
{
    global $root;
    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function yd1_read($relative)
{
    $path = yd1_file($relative);
    return is_file($path) ? file_get_contents($path) : false;
}

function yd1_table_count($mysqli, $table)
{
    $safe = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    $exists = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($safe) . "'");
    if (!$exists || $exists->num_rows === 0) {
        return 'missing';
    }

    $result = $mysqli->query("SELECT COUNT(*) FROM `$safe`");
    if (!$result) {
        return 'error';
    }

    $row = $result->fetch_row();
    return $row ? (int) $row[0] : 0;
}

yd1_record($infos, 'Diagnostic is read-only and does not intentionally write DB, session, cookie, or settings data.');

$report = 'docs/qa/youngo_demo_content_1_blog_contact_report.md';
if (is_file(yd1_file($report))) {
    yd1_record($infos, "Report exists: $report");
} else {
    yd1_record($failures, "Missing report: $report");
}

$requiredFiles = array(
    'application/views/frontend/youngo/blogs.php',
    'application/views/frontend/youngo/blog_details.php',
    'application/views/frontend/youngo/contact_us.php',
    'application/helpers/youngo_frontend_language_helper.php',
);

foreach ($requiredFiles as $file) {
    if (is_file(yd1_file($file))) {
        yd1_record($infos, "Found $file");
    } else {
        yd1_record($failures, "Missing $file");
    }
}

$blogView = yd1_read('application/views/frontend/youngo/blogs.php');
$contactView = yd1_read('application/views/frontend/youngo/contact_us.php');
$blogDetailView = yd1_read('application/views/frontend/youngo/blog_details.php');
$helper = yd1_read('application/helpers/youngo_frontend_language_helper.php');

if ($blogView !== false && strpos($blogView, '$youngo_blog_language === \'arabic\'') !== false && strpos($blogView, '$youngo_blog_posts = array();') !== false) {
    yd1_record($infos, '/ar/blog is documented in source as fallback-driven while the legacy Blog table remains English-only.');
} else {
    yd1_record($warnings, '/ar/blog fallback marker was not found in blogs.php.');
}

if ($contactView !== false && strpos($contactView, 'مدينة السادس من أكتوبر') !== false && strpos($contactView, 'من السبت إلى الخميس') !== false) {
    yd1_record($infos, 'Arabic Contact local fallback values are present.');
} else {
    yd1_record($warnings, 'Arabic Contact local fallback values were not detected.');
}

if ($contactView !== false && !preg_match('/<form\b/i', $contactView) && !preg_match('/\saction\s*=/i', $contactView)) {
    yd1_record($infos, 'YounGo Contact view has no public form/action.');
} else {
    yd1_record($failures, 'YounGo Contact view appears to contain a form/action.');
}

if ($blogDetailView !== false && !preg_match('/<form\b/i', $blogDetailView) && !preg_match('/comment/i', $blogDetailView)) {
    yd1_record($infos, 'YounGo Blog detail view has no visible comment form path.');
} else {
    yd1_record($warnings, 'Review Blog detail for comment/form wording.');
}

if ($helper !== false && strpos($helper, 'get_' . 'phrase(') === false && strpos($helper, 'site_' . 'phrase(') === false) {
    yd1_record($infos, 'Route-aware frontend helper has no direct legacy phrase-helper calls.');
} else {
    yd1_record($failures, 'Route-aware frontend helper contains direct legacy phrase-helper calls.');
}

$frontendFiles = glob(yd1_file('application/views/frontend/youngo') . DIRECTORY_SEPARATOR . '*.php');
$enHits = array();
$paymentHits = array();
foreach ($frontendFiles as $file) {
    $contents = file_get_contents($file);
    $relative = str_replace($root . DIRECTORY_SEPARATOR, '', $file);
    $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
    if (preg_match('/href\s*=\s*["\'][^"\']*\/en(?:\/|["\'?])/i', $contents)) {
        $enHits[] = $relative;
    }
    if (preg_match('/Paymob|Buy Now|Add to cart|checkout|coupon/i', $contents) && preg_match('/blog|contact/i', $relative)) {
        $paymentHits[] = $relative;
    }
}

if (empty($enHits)) {
    yd1_record($infos, 'No /en links detected in YounGo frontend PHP views.');
} else {
    yd1_record($failures, '/en link patterns detected: ' . implode(', ', $enHits));
}

if (empty($paymentHits)) {
    yd1_record($infos, 'No Blog/Contact payment/cart/checkout/coupon/Paymob source hits detected.');
} else {
    yd1_record($failures, 'Payment-related Blog/Contact source hits detected: ' . implode(', ', $paymentHits));
}

$diffNames = trim((string) shell_exec('git diff --name-only'));
$changed = $diffNames === '' ? array() : preg_split('/\r\n|\r|\n/', $diffNames);
$forbidden = array();
$fix2ReportExists = is_file(yd1_file('docs/qa/youngo_demo_fix_2_blog_i18n_report.md'));
$fix2AllowedChanged = array(
    'application/controllers/Admin.php',
    'application/controllers/Blog.php',
    'application/models/Crud_model.php',
    'application/views/backend/admin/blog_add.php',
    'application/views/backend/admin/blog_edit.php',
    'docs/qa/youngo_demo_fix_2_blog_i18n_report.md',
    'scripts/phase_2/youngo_demo_fix_2_blog_i18n_diagnostic.php',
    'scripts/phase_2/youngo_demo_fix_2_blog_i18n_schema.php',
);
foreach ($changed as $file) {
    if ($file === '') {
        continue;
    }
    if ($fix2ReportExists && (in_array($file, $fix2AllowedChanged, true) || preg_match('#^uploads/blog/(banner|thumbnail)/[a-f0-9]{32}\.png$#', $file))) {
        continue;
    }
    if (preg_match('#^(application/config/routes\.php|application/language/|application/views/backend/|application/controllers/|application/models/|database/|uploads/)#', $file)
        || preg_match('/payment|checkout|coupon|paymob/i', $file)
        || preg_match('#application/views/frontend/default#', $file)) {
        $forbidden[] = $file;
    }
}

if (empty($forbidden)) {
    yd1_record($infos, $fix2ReportExists ? 'No unrelated forbidden source-scope changes detected in git diff after DEMO.FIX.2 tolerance.' : 'No forbidden source-scope changes detected in git diff.');
} else {
    yd1_record($failures, 'Forbidden changed paths detected: ' . implode(', ', $forbidden));
}

if (!is_file(yd1_file('application/language/english.json')) && !is_file(yd1_file('application/language/arabic.json'))) {
    yd1_record($warnings, 'Language JSON files were not found where expected for scope comparison.');
}

require yd1_file('application/config/database.php');
$cfg = isset($db['default']) ? $db['default'] : array();
$mysqli = @new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database']);
if ($mysqli->connect_errno) {
    yd1_record($failures, 'DB connection failed: ' . $mysqli->connect_error);
} else {
    $mysqli->set_charset('utf8mb4');
    $counts = array();
    foreach (array('blogs', 'blog_category', 'blog_comments', 'contact', 'language', 'ci_sessions', 'payment', 'youngo_checkout_orders', 'youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants', 'youngo_coupon_usages') as $table) {
        $counts[$table] = yd1_table_count($mysqli, $table);
    }
    yd1_record($infos, 'DB counts: ' . json_encode($counts));

    if ($counts['blog_category'] >= 3) {
        yd1_record($infos, 'Blog categories exist for the demo.');
    } else {
        yd1_record($warnings, 'Blog category count is below expected demo target.');
    }

    if ($counts['blogs'] > 0) {
        yd1_record($infos, 'Blog rows exist and English Blog can render DB-driven posts.');
    } else {
        yd1_record($warnings, 'Blog rows are 0; Blog posts were skipped because the current dashboard path would create a missing legacy admin phrase row.');
    }

    $result = $mysqli->query("SELECT value FROM frontend_settings WHERE `key` = 'contact_info' LIMIT 1");
    $contact = $result ? (string) $result->fetch_row()[0] : '';
    foreach (array('admin@example.com', 'system@example.com', '455 Wolff', '609-502', '345-444') as $placeholder) {
        if (stripos($contact, $placeholder) !== false) {
            yd1_record($failures, "Placeholder contact value still present: $placeholder");
        }
    }
    if (stripos($contact, 'hello@youngo.academy') !== false && stripos($contact, '6th of October City') !== false) {
        yd1_record($infos, 'Contact settings contain the DEMO.CONTENT.1 values.');
    } else {
        yd1_record($warnings, 'Contact settings do not fully match the DEMO.CONTENT.1 target values.');
    }

    $result = $mysqli->query("SELECT `key`, value FROM frontend_settings WHERE `key` IN ('facebook','twitter','linkedin')");
    while ($result && $row = $result->fetch_assoc()) {
        $value = strtolower(rtrim(trim((string) $row['value']), '/'));
        if (in_array($value, array('https://facebook.com', 'http://facebook.com', 'https://twitter.com', 'http://twitter.com'), true)) {
            yd1_record($warnings, 'Generic social placeholder retained in DB but hidden by the YounGo Contact view: ' . $row['key']);
        }
    }

    $result = $mysqli->query("SELECT phrase, english, arabic FROM language WHERE phrase IN ('contact_information_updated_successfully','blog_category_added_successfully','blog_added_successfully') ORDER BY phrase");
    $phrases = array();
    while ($result && $row = $result->fetch_assoc()) {
        $phrases[] = $row;
    }
    if (!empty($phrases)) {
        yd1_record($warnings, 'Legacy admin flash phrase rows present after dashboard writes: ' . json_encode($phrases, JSON_UNESCAPED_UNICODE));
    }

    $result = $mysqli->query("SELECT COUNT(*) FROM language WHERE arabic LIKE '%???%'");
    if ($result) {
        $row = $result->fetch_row();
        yd1_record($warnings, 'Known Arabic language-table qmark rows: ' . (int) $row[0]);
    }
}

echo "DEMO.CONTENT.1 Blog/Contact Diagnostic\n";
foreach ($infos as $message) {
    echo "[INFO] $message\n";
}
foreach ($warnings as $message) {
    echo "[WARN] $message\n";
}
foreach ($failures as $message) {
    echo "[FAIL] $message\n";
}

if (!empty($failures)) {
    echo "RESULT: FAIL\n";
    exit(1);
}

echo empty($warnings) ? "RESULT: PASS\n" : "RESULT: PASS_WITH_WARNINGS\n";
