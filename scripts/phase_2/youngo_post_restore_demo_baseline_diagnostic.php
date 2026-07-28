<?php
/**
 * DEMO.RESTORE.CHECK post-XAMPP restore baseline diagnostic.
 *
 * Read-only checks only. This script does not bootstrap CodeIgniter, submit
 * forms, mutate sessions/cookies/settings, or write database rows.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__, 2);
$importedBackup = 'D:\\Work\\YounGo\\backups\\youngo_school in case of failure (5).sql';
$postRestoreBackup = 'D:\\Work\\YounGo\\backups\\youngo_school_after_xampp_restore_demo_baseline_2026_07_19.sql';
$reportPath = 'docs/qa/youngo_post_xampp_restore_demo_baseline.md';
$failures = array();
$warnings = array();
$passes = array();

function restore_diag_say($label, $message)
{
    echo '[' . $label . '] ' . $message . PHP_EOL;
}

function restore_diag_pass(&$passes, $message)
{
    $passes[] = $message;
    restore_diag_say('PASS', $message);
}

function restore_diag_warn(&$warnings, $message)
{
    $warnings[] = $message;
    restore_diag_say('WARN', $message);
}

function restore_diag_fail(&$failures, $message)
{
    $failures[] = $message;
    restore_diag_say('FAIL', $message);
}

function restore_diag_path($root, $relative)
{
    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function restore_diag_read($root, $relative)
{
    $path = restore_diag_path($root, $relative);
    return is_file($path) ? file_get_contents($path) : false;
}

function restore_diag_list_files($root, $relative)
{
    $base = restore_diag_path($root, $relative);
    if (!is_dir($base)) {
        return array();
    }

    $files = array();
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $files[] = str_replace('\\', '/', str_replace($root . DIRECTORY_SEPARATOR, '', $file->getPathname()));
        }
    }
    sort($files);
    return $files;
}

function restore_diag_has_legacy_phrase_call($source)
{
    $legacy = array('get_' . 'phrase', 'site_' . 'phrase');
    foreach ($legacy as $functionName) {
        if (preg_match('/\b' . preg_quote($functionName, '/') . '\s*\(/', (string) $source)) {
            return true;
        }
    }
    return false;
}

function restore_diag_db_connect($root, &$warnings)
{
    $config = restore_diag_path($root, 'application/config/database.php');
    if (!is_file($config)) {
        restore_diag_warn($warnings, 'Database config missing; DB checks skipped.');
        return null;
    }

    if (!defined('ENVIRONMENT')) {
        define('ENVIRONMENT', 'development');
    }
    if (!defined('BASEPATH')) {
        define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
    }

    $db = null;
    $active_group = null;
    include $config;

    if (!isset($db[$active_group])) {
        restore_diag_warn($warnings, 'Database active group unavailable; DB checks skipped.');
        return null;
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $cfg = $db[$active_group];
    $mysqli = @new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database']);
    if ($mysqli->connect_errno) {
        restore_diag_warn($warnings, 'Database connection failed; DB checks skipped: ' . $mysqli->connect_error);
        return null;
    }

    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

function restore_diag_scalar($mysqli, $sql)
{
    $result = $mysqli->query($sql);
    if (!$result) {
        return null;
    }
    $row = $result->fetch_row();
    return $row ? $row[0] : null;
}

function restore_diag_rows($mysqli, $sql)
{
    $result = $mysqli->query($sql);
    if (!$result) {
        return array();
    }

    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function restore_diag_table_exists($mysqli, $table)
{
    $safe = $mysqli->real_escape_string($table);
    return (int) restore_diag_scalar($mysqli, "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '" . $safe . "'") > 0;
}

restore_diag_say('INFO', 'YounGo DEMO.RESTORE.CHECK post-XAMPP restore baseline diagnostic');

$report = restore_diag_read($root, $reportPath);
if ($report !== false) {
    restore_diag_pass($passes, 'Report file exists: ' . $reportPath);
    if (strpos($report, $importedBackup) !== false) {
        restore_diag_pass($passes, 'Imported backup path is documented in the report.');
    } else {
        restore_diag_fail($failures, 'Imported backup path is not documented in the report.');
    }
    if (strpos($report, $postRestoreBackup) !== false) {
        restore_diag_pass($passes, 'Post-restore backup path is documented in the report.');
    } else {
        restore_diag_fail($failures, 'Post-restore backup path is not documented in the report.');
    }
} else {
    restore_diag_fail($failures, 'Report file missing: ' . $reportPath);
}

if (is_file($importedBackup) && filesize($importedBackup) > 100000) {
    restore_diag_pass($passes, 'Imported recovery backup exists and has non-trivial size.');
} else {
    restore_diag_fail($failures, 'Imported recovery backup is missing or unexpectedly small.');
}

if (is_file($postRestoreBackup) && filesize($postRestoreBackup) > 100000) {
    restore_diag_pass($passes, 'New post-restore backup exists and has non-trivial size.');
} else {
    restore_diag_fail($failures, 'New post-restore backup is missing or unexpectedly small.');
}

$expectedFiles = array(
    'application/helpers/youngo_frontend_language_helper.php',
    'application/views/frontend/youngo/blogs.php',
    'application/views/frontend/youngo/blog_details.php',
    'application/views/frontend/youngo/contact_us.php',
    'application/views/frontend/youngo/course_page.php',
    'application/views/frontend/youngo/course_listing/course_card.php',
    'application/views/frontend/youngo/my_wishlist.php',
    'application/views/frontend/youngo/wishlist_items.php',
);

foreach ($expectedFiles as $relative) {
    if (is_file(restore_diag_path($root, $relative))) {
        restore_diag_pass($passes, 'Expected source file exists: ' . $relative);
    } else {
        restore_diag_fail($failures, 'Expected source file missing: ' . $relative);
    }
}

$helper = restore_diag_read($root, 'application/helpers/youngo_frontend_language_helper.php');
if ($helper !== false && strpos($helper, 'function youngo_frontend_phrase') !== false) {
    restore_diag_pass($passes, 'Route-aware frontend phrase helper exists.');
} else {
    restore_diag_fail($failures, 'Route-aware frontend phrase helper is missing.');
}

if ($helper !== false && !restore_diag_has_legacy_phrase_call($helper)) {
    restore_diag_pass($passes, 'Route-aware helper has no direct legacy phrase helper calls.');
} else {
    restore_diag_fail($failures, 'Route-aware helper must not call legacy phrase helpers.');
}

$frontendFiles = restore_diag_list_files($root, 'application/views/frontend/youngo');
$frontendFiles[] = 'application/config/routes.php';
$enPatterns = array(
    '/\$route\s*\[\s*[\'"]en(?:\/|[\'"])/i',
    '/href\s*=\s*[\'"][^\'"]*\/en(?:\/|[\'"?#])/i',
    '/site_url\s*\(\s*[\'"]en(?:\/|[\'"])/i',
    '/base_url\s*\(\s*[\'"]en(?:\/|[\'"])/i',
);
$enHits = array();
foreach ($frontendFiles as $relative) {
    $source = restore_diag_read($root, $relative);
    if ($source === false) {
        continue;
    }
    foreach ($enPatterns as $pattern) {
        if (preg_match($pattern, $source)) {
            $enHits[] = $relative;
            break;
        }
    }
}

if (empty($enHits)) {
    restore_diag_pass($passes, 'No /en route or link patterns found in YounGo frontend files.');
} else {
    restore_diag_fail($failures, 'Unexpected /en route/link patterns: ' . implode(', ', $enHits));
}

$contactView = restore_diag_read($root, 'application/views/frontend/youngo/contact_us.php');
if ($contactView !== false && !preg_match('/<form\b[^>]*method\s*=\s*[\'"]?post/i', $contactView) && stripos($contactView, 'form_open') === false) {
    restore_diag_pass($passes, 'YounGo contact view has no contact POST form.');
} else {
    restore_diag_fail($failures, 'YounGo contact view appears to contain a POST form.');
}

$blogDetailView = restore_diag_read($root, 'application/views/frontend/youngo/blog_details.php');
if ($blogDetailView !== false && !preg_match('/<form\b[^>]*(comment|method\s*=\s*[\'"]?post)/i', $blogDetailView) && stripos($blogDetailView, 'add_comment') === false) {
    restore_diag_pass($passes, 'YounGo blog detail view has no comment POST form.');
} else {
    restore_diag_fail($failures, 'YounGo blog detail view appears to contain a comment POST form.');
}

$selfSource = file_get_contents(__FILE__);
if (!preg_match('/\b(?:get_phrase|site_phrase)\s*\(/', $selfSource)) {
    restore_diag_pass($passes, 'Diagnostic source contains no direct legacy phrase helper calls.');
} else {
    restore_diag_fail($failures, 'Diagnostic source must not call legacy phrase helpers.');
}

$mysqli = restore_diag_db_connect($root, $warnings);
if ($mysqli) {
    $theme = restore_diag_scalar($mysqli, "SELECT value FROM frontend_settings WHERE `key` = 'theme' LIMIT 1");
    $currency = restore_diag_scalar($mysqli, "SELECT value FROM settings WHERE `key` = 'system_currency' LIMIT 1");
    $currencyPosition = restore_diag_scalar($mysqli, "SELECT value FROM settings WHERE `key` = 'currency_position' LIMIT 1");

    $theme === 'youngo' ? restore_diag_pass($passes, 'frontend_settings.theme is youngo.') : restore_diag_fail($failures, 'frontend_settings.theme is not youngo: ' . (string) $theme);
    $currency === 'EGP' ? restore_diag_pass($passes, 'settings.system_currency is EGP.') : restore_diag_fail($failures, 'settings.system_currency is not EGP: ' . (string) $currency);
    restore_diag_say('INFO', 'settings.currency_position=' . (string) $currencyPosition);

    foreach (array('youngo_course_translations', 'youngo_category_translations', 'youngo_section_translations', 'youngo_lesson_translations') as $table) {
        if (!restore_diag_table_exists($mysqli, $table)) {
            restore_diag_fail($failures, 'Translation table missing: ' . $table);
            continue;
        }
        $invalid = (int) restore_diag_scalar($mysqli, "SELECT COUNT(*) FROM `" . $table . "` WHERE language_code NOT IN ('english','arabic') OR language_code IS NULL OR language_code = ''");
        $legacy = (int) restore_diag_scalar($mysqli, "SELECT COUNT(*) FROM `" . $table . "` WHERE language_code = 'arabic_translated'");
        $total = restore_diag_scalar($mysqli, "SELECT COUNT(*) FROM `" . $table . "`");
        $english = restore_diag_scalar($mysqli, "SELECT COUNT(*) FROM `" . $table . "` WHERE language_code = 'english'");
        $arabic = restore_diag_scalar($mysqli, "SELECT COUNT(*) FROM `" . $table . "` WHERE language_code = 'arabic'");
        restore_diag_say('INFO', $table . ': total=' . $total . ', english=' . $english . ', arabic=' . $arabic . ', invalid=' . $invalid . ', arabic_translated=' . $legacy);
        if ($invalid === 0 && $legacy === 0) {
            restore_diag_pass($passes, 'Translation languages are valid for ' . $table . '.');
        } else {
            restore_diag_fail($failures, 'Invalid translation language rows found in ' . $table . '.');
        }
    }

    $languageRows = restore_diag_scalar($mysqli, 'SELECT COUNT(*) FROM language');
    $arabicQmarks = restore_diag_scalar($mysqli, "SELECT COUNT(*) FROM language WHERE arabic REGEXP '\\\\?{3,}'");
    $arabicEmpty = restore_diag_scalar($mysqli, "SELECT COUNT(*) FROM language WHERE arabic IS NULL OR TRIM(arabic) = ''");
    $oldWishlistPhrase = restore_diag_scalar($mysqli, "SELECT COUNT(*) FROM language WHERE phrase = 'saved_courses_stay_here_so_you_can_compare_learning_paths_before_access_is_granted_or_checkout_becomes_available.'");
    restore_diag_say('INFO', 'language rows=' . $languageRows . ', arabic_qmarks=' . $arabicQmarks . ', arabic_empty=' . $arabicEmpty . ', old_wishlist_phrase=' . $oldWishlistPhrase);
    if ((int) $arabicQmarks > 0) {
        restore_diag_warn($warnings, 'Arabic legacy phrase table corruption remains; reported only.');
    }

    $course1 = restore_diag_rows($mysqli, "SELECT id, title, status, youngo_access_mode FROM course WHERE id = 1 LIMIT 1");
    if (!empty($course1) && $course1[0]['youngo_access_mode'] === 'subscription_only') {
        restore_diag_pass($passes, 'Course 1 exists and remains subscription_only.');
    } else {
        restore_diag_fail($failures, 'Course 1 is missing or not subscription_only.');
    }

    $demoCourseCount = (int) restore_diag_scalar($mysqli, "SELECT COUNT(*) FROM course WHERE id IN (1,2,3,4,5,6,9)");
    if ($demoCourseCount >= 7) {
        restore_diag_pass($passes, 'Expected rebuilt demo course IDs exist.');
    } else {
        restore_diag_fail($failures, 'Expected rebuilt demo course IDs missing; count=' . $demoCourseCount);
    }

    foreach (array('blogs', 'blog_category', 'contact') as $table) {
        if (restore_diag_table_exists($mysqli, $table)) {
            restore_diag_say('INFO', $table . ' rows=' . restore_diag_scalar($mysqli, "SELECT COUNT(*) FROM `" . $table . "`"));
        } else {
            restore_diag_warn($warnings, 'Expected table missing: ' . $table);
        }
    }

    foreach (array('payment', 'youngo_checkout_orders', 'youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants', 'youngo_coupon_usages', 'youngo_coupon_subscription_plans', 'youngo_coupon_courses', 'ci_sessions') as $table) {
        if (restore_diag_table_exists($mysqli, $table)) {
            restore_diag_say('INFO', $table . ' rows=' . restore_diag_scalar($mysqli, "SELECT COUNT(*) FROM `" . $table . "`"));
        } else {
            restore_diag_warn($warnings, 'Protected/payment table missing: ' . $table);
        }
    }

    restore_diag_pass($passes, 'Read-only DB SELECT checks completed.');
}

restore_diag_say('INFO', 'Passes: ' . count($passes) . '; warnings: ' . count($warnings) . '; failures: ' . count($failures));

if (!empty($failures)) {
    exit(1);
}

exit(0);
