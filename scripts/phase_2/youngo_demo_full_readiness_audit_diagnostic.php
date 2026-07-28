<?php
/**
 * DEMO.REVIEW.1 read-only diagnostic.
 *
 * This script intentionally avoids CodeIgniter bootstrapping, sessions, cookies,
 * phrase helpers, settings writes, and all mutating SQL. It uses source reads and
 * direct SELECT-only mysqli checks.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__, 2);
$failures = array();
$warnings = array();
$passes = array();
$notes = array();

function rel_path($root, $path)
{
    return str_replace('\\', '/', str_replace($root . DIRECTORY_SEPARATOR, '', $path));
}

function say($label, $message)
{
    echo '[' . $label . '] ' . $message . PHP_EOL;
}

function pass_check(&$passes, $message)
{
    $passes[] = $message;
    say('PASS', $message);
}

function warn_check(&$warnings, $message)
{
    $warnings[] = $message;
    say('WARN', $message);
}

function fail_check(&$failures, $message)
{
    $failures[] = $message;
    say('FAIL', $message);
}

function read_file_safe($root, $relative)
{
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    return is_file($path) ? file_get_contents($path) : false;
}

function list_files_recursive($root, $relative)
{
    $base = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!is_dir($base)) {
        return array();
    }

    $files = array();
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $files[] = rel_path($root, $file->getPathname());
        }
    }
    sort($files);
    return $files;
}

function source_has_any($source, array $patterns)
{
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $source)) {
            return true;
        }
    }
    return false;
}

function db_connect_read_only($root, &$warnings)
{
    $config = $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
    if (!is_file($config)) {
        warn_check($warnings, 'Database config is missing; DB checks skipped.');
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
        warn_check($warnings, 'Database active group is unavailable; DB checks skipped.');
        return null;
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $cfg = $db[$active_group];
    $mysqli = @new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database']);
    if ($mysqli->connect_errno) {
        warn_check($warnings, 'Database connection failed; DB checks skipped: ' . $mysqli->connect_error);
        return null;
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

function db_scalar($mysqli, $sql)
{
    $result = $mysqli->query($sql);
    if (!$result) {
        return null;
    }
    $row = $result->fetch_row();
    return $row ? $row[0] : null;
}

function db_rows($mysqli, $sql)
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

function db_table_exists($mysqli, $table)
{
    $safe = $mysqli->real_escape_string($table);
    return (int) db_scalar($mysqli, "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '" . $safe . "'") > 0;
}

function is_unclean_phrase_value($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return true;
    }
    if (preg_match('/\?{3,}/u', $value)) {
        return true;
    }
    if (preg_match('/(?:Ã|Â|Ø|Ù|Ð|Ñ|ï¿½)/u', $value)) {
        return true;
    }
    return false;
}

say('INFO', 'YounGo DEMO.REVIEW.1 full readiness audit diagnostic');

$expectedFiles = array(
    'docs/qa/youngo_demo_full_readiness_audit.md',
    'application/config/routes.php',
    'application/controllers/Home.php',
    'application/controllers/Blog.php',
    'application/controllers/Page.php',
    'application/helpers/youngo_frontend_language_helper.php',
    'application/helpers/youngo_frontend_content_helper.php',
    'application/helpers/common_helper.php',
    'application/views/frontend/youngo/header.php',
    'application/views/frontend/youngo/footer.php',
    'application/views/frontend/youngo/home.php',
    'application/views/frontend/youngo/courses_page.php',
    'application/views/frontend/youngo/course_listing/course_card.php',
    'application/views/frontend/youngo/course_page.php',
    'application/views/frontend/youngo/blogs.php',
    'application/views/frontend/youngo/blog_details.php',
    'application/views/frontend/youngo/contact_us.php',
    'application/views/frontend/youngo/my_wishlist.php',
    'application/views/frontend/youngo/wishlist_items.php',
);

foreach ($expectedFiles as $relative) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (is_file($path)) {
        pass_check($passes, 'Expected file exists: ' . $relative);
    } else {
        fail_check($failures, 'Expected file missing: ' . $relative);
    }
}

$frontendFiles = list_files_recursive($root, 'application/views/frontend/youngo');
$frontendFiles[] = 'application/config/routes.php';
$frontendFiles[] = 'application/helpers/youngo_frontend_language_helper.php';

$enRoutePatterns = array(
    '/\$route\s*\[\s*[\'"]en(?:\/|[\'"])/i',
    '/href\s*=\s*[\'"][^\'"]*\/en(?:\/|[\'"?#])/i',
    '/site_url\s*\(\s*[\'"]en(?:\/|[\'"])/i',
    '/base_url\s*\(\s*[\'"]en(?:\/|[\'"])/i',
);
$enHits = array();
foreach ($frontendFiles as $relative) {
    $source = read_file_safe($root, $relative);
    if ($source !== false && source_has_any($source, $enRoutePatterns)) {
        $enHits[] = $relative;
    }
}
if (empty($enHits)) {
    pass_check($passes, 'No /en route or link patterns found in YounGo frontend files.');
} else {
    fail_check($failures, 'Unexpected /en route/link patterns: ' . implode(', ', $enHits));
}

$demoViews = array(
    'application/views/frontend/youngo/header.php',
    'application/views/frontend/youngo/footer.php',
    'application/views/frontend/youngo/home.php',
    'application/views/frontend/youngo/courses_page.php',
    'application/views/frontend/youngo/course_listing/course_card.php',
    'application/views/frontend/youngo/course_page.php',
    'application/views/frontend/youngo/blogs.php',
    'application/views/frontend/youngo/blog_details.php',
    'application/views/frontend/youngo/contact_us.php',
    'application/views/frontend/youngo/my_wishlist.php',
    'application/views/frontend/youngo/wishlist_items.php',
);
$commercePatterns = array(
    '/site_url\s*\(\s*[\'"]home\/(?:shopping_cart|course_payment|apply_coupon|coupon_offer_100_percent|handle_cart_items)/i',
    '/site_url\s*\(\s*[\'"](?:payment|paymob|paypal|stripe|razorpay)/i',
    '/\b(?:Add to cart|Buy Now|Continue to Payment|Apply coupon|Coupon code)\b/i',
);
$commerceHits = array();
foreach ($demoViews as $relative) {
    $source = read_file_safe($root, $relative);
    if ($source !== false && source_has_any($source, $commercePatterns)) {
        $commerceHits[] = $relative;
    }
}
if (empty($commerceHits)) {
    pass_check($passes, 'No obvious visible cart/payment/Paymob/coupon entry points in YounGo demo views.');
} else {
    fail_check($failures, 'Potential cart/payment/Paymob/coupon entry points in demo views: ' . implode(', ', $commerceHits));
}

$contactSource = read_file_safe($root, 'application/views/frontend/youngo/contact_us.php');
if ($contactSource !== false && !preg_match('/<form\b/i', $contactSource)) {
    pass_check($passes, 'YounGo contact view has no public form.');
} else {
    fail_check($failures, 'YounGo contact view appears to contain a form.');
}

$blogDetailSource = read_file_safe($root, 'application/views/frontend/youngo/blog_details.php');
if ($blogDetailSource !== false && !preg_match('/<form\b|add_blog_comment/i', $blogDetailSource)) {
    pass_check($passes, 'YounGo blog detail view has no comment form.');
} else {
    fail_check($failures, 'YounGo blog detail view appears to expose a comment form.');
}

$helperSource = read_file_safe($root, 'application/helpers/youngo_frontend_language_helper.php');
$legacyNeedles = array('get_' . 'phrase(', 'site_' . 'phrase(');
if ($helperSource !== false) {
    foreach (array(
        'youngo_frontend_active_language',
        'youngo_frontend_phrase',
        'youngo_frontend_phrase_value_is_clean',
        'youngo_frontend_public_route_equivalent_path',
        'youngo_frontend_non_localizable_uri_prefixes',
    ) as $needle) {
        if (strpos($helperSource, $needle) !== false) {
            pass_check($passes, 'Route-aware helper contains: ' . $needle);
        } else {
            fail_check($failures, 'Route-aware helper missing: ' . $needle);
        }
    }

    $helperLegacyHit = false;
    foreach ($legacyNeedles as $needle) {
        if (strpos($helperSource, $needle) !== false) {
            $helperLegacyHit = true;
        }
    }
    if ($helperLegacyHit) {
        fail_check($failures, 'Route-aware helper directly references a legacy phrase helper.');
    } else {
        pass_check($passes, 'Route-aware helper does not directly reference legacy phrase helpers.');
    }

    foreach (array('home', 'courses', 'blog', 'contact', 'login', 'sign_up', 'subscription_access', 'primary_navigation', 'language_switcher', 'footer_navigation', 'showing_results', 'latest_articles', 'helpful_notes_for_families', 'contact_us', 'get_in_touch', 'email', 'phone', 'address', 'working_hours', 'follow_us', 'read_more') as $key) {
        if (preg_match('/[\'"]' . preg_quote($key, '/') . '[\'"]\s*=>/', $helperSource)) {
            pass_check($passes, 'Local phrase map includes key: ' . $key);
        } else {
            warn_check($warnings, 'Local phrase map may be missing key: ' . $key);
        }
    }
}

$mysqli = db_connect_read_only($root, $warnings);
if ($mysqli) {
    pass_check($passes, 'Database connection available for SELECT-only checks.');

    if (db_table_exists($mysqli, 'language')) {
        $rows = db_rows($mysqli, 'SELECT phrase, english, arabic FROM language');
        $emptyArabic = 0;
        $questionArabic = 0;
        $mojibakeArabic = 0;
        $sameAsEnglish = 0;
        foreach ($rows as $row) {
            $arabic = trim((string) $row['arabic']);
            if ($arabic === '') {
                $emptyArabic++;
            }
            if (preg_match('/\?{3,}/u', $arabic)) {
                $questionArabic++;
            }
            if (preg_match('/(?:Ã|Â|Ø|Ù|Ð|Ñ|ï¿½)/u', $arabic)) {
                $mojibakeArabic++;
            }
            if ($arabic !== '' && $arabic === trim((string) $row['english'])) {
                $sameAsEnglish++;
            }
        }
        $duplicatePhrases = (int) db_scalar($mysqli, 'SELECT COUNT(*) FROM (SELECT phrase FROM language GROUP BY phrase HAVING COUNT(*) > 1) d');
        say('INFO', 'language_rows=' . count($rows));
        say('INFO', 'language_duplicate_phrase_names=' . $duplicatePhrases);
        say('INFO', 'language_arabic_empty=' . $emptyArabic);
        say('INFO', 'language_arabic_repeated_question_marks=' . $questionArabic);
        say('INFO', 'language_arabic_mojibake_markers=' . $mojibakeArabic);
        say('INFO', 'language_arabic_identical_to_english=' . $sameAsEnglish);
        if ($questionArabic > 0 || $emptyArabic > 0 || $duplicatePhrases > 0) {
            warn_check($warnings, 'Arabic language table has corruption or completeness issues.');
        } else {
            pass_check($passes, 'Arabic language table has no obvious corruption from this scan.');
        }

        foreach (array('home', 'courses', 'blog', 'contact', 'login', 'sign_up', 'subscription_access', 'primary_navigation', 'language_switcher', 'footer_navigation', 'showing_results', 'latest_articles', 'helpful_notes_for_families', 'contact_us', 'get_in_touch', 'email', 'phone', 'address', 'working_hours', 'follow_us', 'read_more') as $key) {
            $safeKey = $mysqli->real_escape_string($key);
            $phrase = db_rows($mysqli, "SELECT phrase, english, arabic FROM language WHERE phrase = '" . $safeKey . "' LIMIT 1");
            if (empty($phrase)) {
                warn_check($warnings, 'Critical phrase missing from DB: ' . $key);
            } elseif (is_unclean_phrase_value($phrase[0]['arabic'])) {
                warn_check($warnings, 'Critical phrase has unclean Arabic DB value: ' . $key);
            } else {
                pass_check($passes, 'Critical phrase has clean Arabic DB value: ' . $key);
            }
        }
    }

    foreach (array('youngo_course_translations', 'youngo_category_translations', 'youngo_section_translations', 'youngo_lesson_translations') as $table) {
        if (!db_table_exists($mysqli, $table)) {
            fail_check($failures, 'Translation table missing: ' . $table);
            continue;
        }
        $count = (int) db_scalar($mysqli, 'SELECT COUNT(*) FROM ' . $table);
        $invalid = (int) db_scalar($mysqli, "SELECT COUNT(*) FROM " . $table . " WHERE language_code NOT IN ('english','arabic') OR language_code IS NULL OR language_code = ''");
        $alias = (int) db_scalar($mysqli, "SELECT COUNT(*) FROM " . $table . " WHERE language_code = 'arabic_translated'");
        say('INFO', $table . '_rows=' . $count . ', invalid_language_rows=' . $invalid . ', arabic_translated_rows=' . $alias);
        if ($invalid > 0 || $alias > 0) {
            fail_check($failures, 'Invalid translation language values found in ' . $table);
        } else {
            pass_check($passes, 'Translation language values are limited to english/arabic: ' . $table);
        }
    }

    foreach (array('payment', 'watch_histories', 'watched_duration', 'youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants', 'youngo_checkout_orders', 'youngo_coupon_usages', 'youngo_coupon_subscription_plans', 'youngo_coupon_courses') as $table) {
        if (!db_table_exists($mysqli, $table)) {
            warn_check($warnings, 'Protected/access/payment table missing: ' . $table);
            continue;
        }
        $count = (int) db_scalar($mysqli, 'SELECT COUNT(*) FROM ' . $table);
        say('INFO', $table . '_rows=' . $count);
        if ($count === 0) {
            pass_check($passes, 'Protected/access/payment table is clean: ' . $table);
        } else {
            warn_check($warnings, 'Protected/access/payment table is non-empty: ' . $table . ' = ' . $count);
        }
    }

    if (db_table_exists($mysqli, 'settings')) {
        $settingsRows = db_rows($mysqli, 'SELECT * FROM settings');
        $settings = array();
        foreach ($settingsRows as $row) {
            if (isset($row['key'])) {
                $settings[$row['key']] = $row['value'];
            }
        }
        say('INFO', 'settings.system_currency=' . (isset($settings['system_currency']) ? $settings['system_currency'] : 'missing'));
        say('INFO', 'settings.currency_position=' . (isset($settings['currency_position']) ? $settings['currency_position'] : 'missing'));
        if (isset($settings['system_currency']) && $settings['system_currency'] === 'EGP') {
            pass_check($passes, 'System currency is EGP.');
        } else {
            fail_check($failures, 'System currency is not EGP.');
        }
    }

    if (db_table_exists($mysqli, 'frontend_settings')) {
        $frontendRows = db_rows($mysqli, 'SELECT * FROM frontend_settings');
        $frontend = array();
        foreach ($frontendRows as $row) {
            if (isset($row['key'])) {
                $frontend[$row['key']] = $row['value'];
            }
        }
        say('INFO', 'frontend_settings.theme=' . (isset($frontend['theme']) ? $frontend['theme'] : 'missing'));
        if (isset($frontend['theme']) && $frontend['theme'] === 'youngo') {
            pass_check($passes, 'Frontend theme is YounGo.');
        } else {
            fail_check($failures, 'Frontend theme is not YounGo.');
        }
    }
}

say('INFO', 'Read-only diagnostic completed. No intentional DB/session/cookie/settings writes were performed.');
say('INFO', 'Passes=' . count($passes) . ', Warnings=' . count($warnings) . ', Failures=' . count($failures));

if (!empty($failures)) {
    echo PHP_EOL . 'FAILURES:' . PHP_EOL;
    foreach ($failures as $failure) {
        echo '- ' . $failure . PHP_EOL;
    }
    exit(1);
}

if (!empty($warnings)) {
    echo PHP_EOL . 'WARNINGS:' . PHP_EOL;
    foreach ($warnings as $warning) {
        echo '- ' . $warning . PHP_EOL;
    }
}

echo PHP_EOL . 'RESULT: PASS with ' . count($warnings) . ' warning(s).' . PHP_EOL;
exit(0);
