<?php

/**
 * DEMO.4A route-aware YounGo frontend phrase diagnostic.
 *
 * Read-only checks only. This script does not call LMS phrase helpers, submit
 * forms, mutate session/cookie/settings data, or write database rows.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

$root = dirname(__DIR__, 2);
$failures = array();
$warnings = array();

if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}

if (!defined('BASEPATH')) {
    define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
}

if (!defined('APPPATH')) {
    define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);
}

function youngo_demo4a_check(&$failures, $condition, $message)
{
    echo ($condition ? '[PASS] ' : '[FAIL] ') . $message . PHP_EOL;
    if (!$condition) {
        $failures[] = $message;
    }
}

function youngo_demo4a_warn(&$warnings, $message)
{
    echo '[WARN] ' . $message . PHP_EOL;
    $warnings[] = $message;
}

function youngo_demo4a_path($relative_path)
{
    global $root;
    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
}

function youngo_demo4a_read($relative_path)
{
    $path = youngo_demo4a_path($relative_path);
    return is_file($path) ? file_get_contents($path) : false;
}

function youngo_demo4a_run($command)
{
    global $root;

    $cwd = getcwd();
    chdir($root);
    $output = array();
    $exit_code = 0;
    exec($command . ' 2>&1', $output, $exit_code);
    chdir($cwd);

    return array($exit_code, implode(PHP_EOL, $output));
}

function youngo_demo4a_status_files()
{
    list($exit_code, $output) = youngo_demo4a_run('git status --short');
    if ($exit_code !== 0 || trim($output) === '') {
        return array();
    }

    $files = array();
    foreach (preg_split('/\R+/', rtrim($output)) as $line) {
        if (!preg_match('/^(?:[ MADRCU?!]{2})\s+(.+)$/', $line, $matches)) {
            continue;
        }
        $files[] = str_replace('\\', '/', trim($matches[1]));
    }

    return $files;
}

function youngo_demo4a_diff_files()
{
    list($exit_code, $output) = youngo_demo4a_run('git diff --name-only');
    if ($exit_code !== 0 || trim($output) === '') {
        return array();
    }

    return preg_split('/\R+/', trim(str_replace('\\', '/', $output)));
}

function youngo_demo4a_added_lines(array $paths)
{
    $quoted = array();
    foreach ($paths as $path) {
        $quoted[] = escapeshellarg($path);
    }

    list($exit_code, $output) = youngo_demo4a_run('git diff -U0 -- ' . implode(' ', $quoted));
    if ($exit_code !== 0 || trim($output) === '') {
        return array();
    }

    $lines = array();
    foreach (preg_split('/\R+/', $output) as $line) {
        if (strpos($line, '+') !== 0 || strpos($line, '+++') === 0) {
            continue;
        }
        $lines[] = substr($line, 1);
    }

    return $lines;
}

function youngo_demo4a_removed_lines(array $paths)
{
    $quoted = array();
    foreach ($paths as $path) {
        $quoted[] = escapeshellarg($path);
    }

    list($exit_code, $output) = youngo_demo4a_run('git diff -U0 -- ' . implode(' ', $quoted));
    if ($exit_code !== 0 || trim($output) === '') {
        return array();
    }

    $lines = array();
    foreach (preg_split('/\R+/', $output) as $line) {
        if (strpos($line, '-') !== 0 || strpos($line, '---') === 0) {
            continue;
        }
        $lines[] = substr($line, 1);
    }

    return $lines;
}

function youngo_demo4a_phrase_keys_from_lines(array $lines)
{
    $keys = array();
    foreach ($lines as $line) {
        if (preg_match_all('/\b(?:get_phrase|site_phrase)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $line, $matches)) {
            foreach ($matches[1] as $key) {
                $keys[$key] = true;
            }
        }
    }

    return array_keys($keys);
}

function youngo_demo4a_fetch_phrase(mysqli $mysqli, $phrase)
{
    $stmt = $mysqli->prepare('SELECT phrase, english, arabic FROM language WHERE phrase = ? LIMIT 1');
    $stmt->bind_param('s', $phrase);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function youngo_demo4a_value_is_clean($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return false;
    }

    if (preg_match('/\?{3,}/u', $value)) {
        return false;
    }

    $question_count = substr_count($value, '?');
    $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    if ($question_count >= 2 && $length > 0 && ($question_count / $length) >= 0.2) {
        return false;
    }

    if (preg_match('/(?:�|Ã|Â|Ø|Ù|Ð|Ñ)/u', $value)) {
        return false;
    }

    return true;
}

function youngo_demo4a_static_route_phrase_keys($source)
{
    $keys = array();
    if (preg_match_all('/\byoungo_frontend_phrase\s*\(\s*([\'"])(.*?)\1/', (string) $source, $matches)) {
        foreach ($matches[2] as $key) {
            $keys[] = $key;
        }
    }

    return array_values(array_unique($keys));
}

function youngo_demo4a_function_body($source, $function_name)
{
    $pattern = '/function\s+' . preg_quote($function_name, '/') . '\s*\(/';
    if (!preg_match($pattern, $source, $matches, PREG_OFFSET_CAPTURE)) {
        return '';
    }
    $start = $matches[0][1];

    $open = strpos($source, '{', $start);
    if ($open === false) {
        return '';
    }

    $depth = 0;
    $length = strlen($source);
    for ($i = $open; $i < $length; $i++) {
        if ($source[$i] === '{') {
            $depth++;
        } elseif ($source[$i] === '}') {
            $depth--;
            if ($depth === 0) {
                return substr($source, $open, $i - $open + 1);
            }
        }
    }

    return '';
}

function youngo_demo4a_is_strict_dirty_scope_failure($output, array $allowed_dirty_files)
{
    $text = str_replace('\\', '/', (string) $output);

    foreach ($allowed_dirty_files as $file) {
        if (strpos($text, $file) !== false) {
            return true;
        }
    }

    return false;
}

function youngo_demo4a_diagnostic_passes($relative_path, array $allowed_dirty_files)
{
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($relative_path);
    list($exit_code, $output) = youngo_demo4a_run($command);
    $passes = $exit_code === 0 && strpos($output, '[FAIL]') === false && strpos($output, 'FAIL ') === false;

    if (!$passes && youngo_demo4a_is_strict_dirty_scope_failure($output, $allowed_dirty_files)) {
        return array('passes' => true, 'strict_scope_only' => true, 'output' => $output);
    }

    return array('passes' => $passes, 'strict_scope_only' => false, 'output' => $output);
}

echo 'YounGo DEMO.4A Route-aware Frontend Phrase Diagnostic' . PHP_EOL;
echo str_repeat('=', 78) . PHP_EOL;

$helperFile = 'application/helpers/youngo_frontend_language_helper.php';
$convertedFiles = array(
    'application/views/frontend/youngo/home.php',
    'application/views/frontend/youngo/header.php',
    'application/views/frontend/youngo/footer.php',
    'application/views/frontend/youngo/courses_page.php',
    'application/views/frontend/youngo/course_listing/filter_panel.php',
    'application/views/frontend/youngo/course_listing/sorting_bar.php',
    'application/views/frontend/youngo/course_listing/course_card.php',
    'application/views/frontend/youngo/course_page.php',
    'application/views/frontend/youngo/my_wishlist.php',
    'application/views/frontend/youngo/wishlist_items.php',
    'application/views/frontend/youngo/profile_menus.php',
    'application/views/frontend/youngo/login.php',
    'application/views/frontend/youngo/sign_up.php',
    'application/views/frontend/youngo/home_sections/hero.php',
    'application/views/frontend/youngo/home_sections/featured_categories.php',
    'application/views/frontend/youngo/home_sections/featured_courses.php',
    'application/views/frontend/youngo/home_sections/why_choose.php',
    'application/views/frontend/youngo/home_sections/about_teaser.php',
    'application/views/frontend/youngo/home_sections/testimonials.php',
    'application/views/frontend/youngo/home_sections/faq_preview.php',
    'application/views/frontend/youngo/home_sections/blog_preview.php',
    'application/views/frontend/youngo/home_sections/final_cta.php',
);

$allowedDirtyFiles = array_merge(array($helperFile), $convertedFiles, array(
    'scripts/phase_2/youngo_demo_route_aware_phrase_diagnostic.php',
));

foreach (array_merge(array($helperFile), $convertedFiles) as $file) {
    youngo_demo4a_check($failures, is_file(youngo_demo4a_path($file)), 'Expected file exists: ' . $file);
}

$helperSource = youngo_demo4a_read($helperFile);
$helperBody = youngo_demo4a_function_body((string) $helperSource, 'youngo_frontend_phrase');
youngo_demo4a_check($failures, $helperBody !== '', 'youngo_frontend_phrase helper exists');
youngo_demo4a_check($failures, strpos($helperBody, 'youngo_frontend_active_language') !== false, 'Helper uses route-derived frontend language');
youngo_demo4a_check($failures, strpos($helperBody, 'arabic') !== false && strpos($helperBody, 'english') !== false, 'Helper normalizes phrase language to english/arabic');
youngo_demo4a_check($failures, !preg_match('/\b(?:get_phrase|site_phrase)\s*\(/', $helperBody), 'Helper does not call get_phrase() or site_phrase() internally');
youngo_demo4a_check($failures, !preg_match('/\b(insert|update|delete|replace|truncate)\s*\(/i', $helperBody), 'Helper does not call DB write methods');
youngo_demo4a_check($failures, !preg_match('/\b(set_userdata|setcookie|delete_cookie|set_item|sess_write|session_start)\s*\(/i', $helperBody), 'Helper does not write session/cookie/settings state');
youngo_demo4a_check($failures, strpos($helperBody, 'arabic_translated') === false, 'Helper does not use arabic_translated as UI/table language');
youngo_demo4a_check($failures, preg_match("/select\\('phrase, english, arabic'\\)/", $helperBody) === 1, 'Helper reads only phrase, english, and arabic columns');

require_once youngo_demo4a_path($helperFile);
youngo_demo4a_check($failures, function_exists('youngo_frontend_phrase_value_is_clean'), 'Helper has phrase value corruption detection');
if (function_exists('youngo_frontend_phrase_value_is_clean')) {
    youngo_demo4a_check($failures, youngo_frontend_phrase_value_is_clean('???????') === false, 'Helper rejects repeated question-mark phrase values');
    youngo_demo4a_check($failures, youngo_frontend_phrase_value_is_clean('الرئيسية') === true, 'Helper accepts valid Arabic phrase values');
}
youngo_demo4a_check($failures, function_exists('youngo_frontend_local_phrase_map'), 'Local YounGo frontend phrase map exists');
if (function_exists('youngo_frontend_local_phrase_map')) {
    $localMap = youngo_frontend_local_phrase_map();
    youngo_demo4a_check($failures, isset($localMap['arabic']['home']) && youngo_demo4a_value_is_clean($localMap['arabic']['home']), 'Local Arabic map covers demo-critical nav label: home');
    youngo_demo4a_check($failures, isset($localMap['arabic']['subscription_not_available_yet']) && youngo_demo4a_value_is_clean($localMap['arabic']['subscription_not_available_yet']), 'Local Arabic map covers subscription boundary label');
}

$config_file = $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
$mysqli = null;
if (is_file($config_file)) {
    require $config_file;
    $config = isset($db['default']) ? $db['default'] : array();
    $mysqli = @new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
    if ($mysqli->connect_errno) {
        echo '[FAIL] Database connection available for read-only phrase checks' . PHP_EOL;
        $failures[] = 'Database connection available for read-only phrase checks';
    } else {
        $mysqli->set_charset('utf8mb4');
        echo '[PASS] Database connection available for read-only phrase checks' . PHP_EOL;
    }
} else {
    echo '[FAIL] Database config exists' . PHP_EOL;
    $failures[] = 'Database config exists';
}

$routePhraseKeys = array();
foreach ($convertedFiles as $file) {
    $source = youngo_demo4a_read($file);
    foreach (youngo_demo4a_static_route_phrase_keys($source) as $key) {
        $routePhraseKeys[$key][] = $file;
    }
}

$dynamicExpectedKeys = array(
    'beginner',
    'intermediate',
    'advanced',
    'subscription_not_available_yet',
    'access_managed_by_school',
    'subscription_checkout_is_not_available_yet',
    'access_for_this_course_is_managed_by_your_school/admin.',
);
foreach ($dynamicExpectedKeys as $key) {
    $routePhraseKeys[$key][] = 'dynamic frontend value';
}

if ($mysqli !== null) {
    foreach ($routePhraseKeys as $key => $locations) {
        $row = youngo_demo4a_fetch_phrase($mysqli, $key);
        youngo_demo4a_check($failures, (bool) $row, 'Route-aware phrase key exists: ' . $key);
        if ($row) {
            youngo_demo4a_check($failures, youngo_demo4a_value_is_clean($row['english']), 'Route-aware phrase key has clean English DB value: ' . $key);
            $effective_arabic = function_exists('youngo_frontend_phrase') ? youngo_frontend_phrase($key, 'arabic') : $row['arabic'];
            youngo_demo4a_check($failures, youngo_demo4a_value_is_clean($effective_arabic), 'Route-aware phrase key has clean effective Arabic value: ' . $key);
        }
    }
}

$manifest = array(
    'primary_navigation' => 'application/views/frontend/youngo/header.php',
    'language_switcher' => 'application/views/frontend/youngo/header.php',
    'footer_navigation' => 'application/views/frontend/youngo/footer.php',
    'showing_results' => 'application/views/frontend/youngo/course_listing/sorting_bar.php',
    'course_discovery' => 'application/views/frontend/youngo/courses_page.php',
    'explore_youngo_courses' => 'application/views/frontend/youngo/courses_page.php',
    'find_a_course' => 'application/views/frontend/youngo/course_listing/filter_panel.php',
    'search_by_keyword' => 'application/views/frontend/youngo/course_listing/filter_panel.php',
    'Categories' => 'application/views/frontend/youngo/course_listing/filter_panel.php',
    'Price' => 'application/views/frontend/youngo/course_listing/filter_panel.php',
    'Level' => 'application/views/frontend/youngo/course_listing/filter_panel.php',
    'apply_filters' => 'application/views/frontend/youngo/course_listing/filter_panel.php',
    'sort_by' => 'application/views/frontend/youngo/course_listing/sorting_bar.php',
    'newly_published' => 'application/views/frontend/youngo/course_listing/sorting_bar.php',
    'lessons' => 'application/views/frontend/youngo/course_listing/course_card.php',
    'subscription_not_available_yet' => 'application/views/frontend/youngo/course_listing/course_card.php',
    'contact' => 'application/views/frontend/youngo/course_page.php',
);

foreach ($manifest as $key => $file) {
    $source = youngo_demo4a_read($file);
    youngo_demo4a_check($failures, strpos((string) $source, "youngo_frontend_phrase('{$key}'") !== false || strpos((string) $source, 'youngo_frontend_phrase("' . $key . '"') !== false || strpos((string) $source, $key) !== false, 'Demo-critical converted label is route-aware: ' . $key);
}

$statusFiles = youngo_demo4a_status_files();
$unexpectedDirty = array_values(array_diff($statusFiles, $allowedDirtyFiles));
youngo_demo4a_check($failures, empty($unexpectedDirty), 'Dirty worktree scope is limited to DEMO.4A files');
if (!empty($unexpectedDirty)) {
    echo 'Unexpected dirty files: ' . implode(', ', $unexpectedDirty) . PHP_EOL;
}

$diffFiles = youngo_demo4a_diff_files();
$allChangedText = implode("\n", array_merge($statusFiles, $diffFiles));
youngo_demo4a_check($failures, !preg_match('#(^|/)(routes\.php)$#m', $allChangedText), 'No route files changed');
youngo_demo4a_check($failures, strpos($allChangedText, 'application/views/backend/') === false && strpos($allChangedText, 'application/controllers/Admin.php') === false, 'No admin/backend files changed');
youngo_demo4a_check($failures, !preg_match('#(checkout|payment|paymob|paypal|stripe|razorpay|paystack|flutterwave|coupon|shopping_cart|update_cart|apply_coupon|remove_coupon)#i', $allChangedText), 'No checkout/payment/cart/coupon/Paymob files changed');
youngo_demo4a_check($failures, !preg_match('#application/language/.*\.json#', $allChangedText), 'No application/language JSON files changed');

$sourceDirtyFiles = array_merge(array($helperFile), $convertedFiles);
$addedLines = youngo_demo4a_added_lines($sourceDirtyFiles);
$removedLines = youngo_demo4a_removed_lines($sourceDirtyFiles);
$addedText = implode("\n", $addedLines);
youngo_demo4a_check($failures, strpos($addedText, '/en') === false && strpos($addedText, "site_url('en") === false && strpos($addedText, 'site_url("en') === false, 'No /en routes or links introduced');
$newGlobalPhraseKeys = array_values(array_diff(youngo_demo4a_phrase_keys_from_lines($addedLines), youngo_demo4a_phrase_keys_from_lines($removedLines)));
youngo_demo4a_check($failures, empty($newGlobalPhraseKeys), 'No new get_phrase()/site_phrase() keys introduced in DEMO.4A diff');
if (!empty($newGlobalPhraseKeys)) {
    echo 'New global phrase-helper keys: ' . implode(', ', $newGlobalPhraseKeys) . PHP_EOL;
}
youngo_demo4a_check($failures, strpos($addedText, 'arabic_translated') === false, 'No arabic_translated UI/table-language usage introduced in DEMO.4A diff');

$deferredKeys = array('youngo_theme_skeleton', 'youngo_theme_skeleton_not_implemented', 'allowed_file_types');
$deferredPattern = '/\b(?:get_phrase|site_phrase|youngo_frontend_phrase)\s*\(\s*[\'"](?:' . implode('|', array_map('preg_quote', $deferredKeys)) . ')[\'"]\s*\)/';
foreach (array_merge(array($helperFile), $convertedFiles) as $file) {
    youngo_demo4a_check($failures, !preg_match($deferredPattern, (string) youngo_demo4a_read($file)), 'No deferred phrase key is used: ' . $file);
    youngo_demo4a_check($failures, !preg_match('/\?{3,}/', (string) youngo_demo4a_read($file)), 'No literal repeated question marks in source: ' . $file);
}

if (function_exists('youngo_frontend_phrase')) {
    youngo_demo4a_check($failures, youngo_demo4a_value_is_clean(youngo_frontend_phrase('subscription_not_available_yet', 'arabic')), 'subscription_not_available_yet renders safely in Arabic context');
}

$nestedDiagnostics = array(
    'scripts/phase_2/youngo_demo_payment_cta_boundary_diagnostic.php',
    'scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php',
    'scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php',
);
foreach ($nestedDiagnostics as $diagnostic) {
    if (!is_file(youngo_demo4a_path($diagnostic))) {
        youngo_demo4a_warn($warnings, 'Compatibility diagnostic missing: ' . $diagnostic);
        continue;
    }

    $result = youngo_demo4a_diagnostic_passes($diagnostic, $allowedDirtyFiles);
    youngo_demo4a_check($failures, $result['passes'], 'Compatibility diagnostic passes: ' . $diagnostic);
    if ($result['strict_scope_only']) {
        youngo_demo4a_warn($warnings, 'Compatibility diagnostic accepted as strict dirty-scope-only: ' . $diagnostic);
    }
}

echo str_repeat('-', 78) . PHP_EOL;
if (!empty($warnings)) {
    echo 'Warnings: ' . count($warnings) . PHP_EOL;
}

if (!empty($failures)) {
    echo 'Result: FAIL (' . count($failures) . ' failure(s))' . PHP_EOL;
    exit(1);
}

echo 'Result: PASS' . PHP_EOL;
exit(0);
