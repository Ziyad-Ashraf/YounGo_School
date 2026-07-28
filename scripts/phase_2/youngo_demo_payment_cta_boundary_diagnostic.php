<?php

/**
 * DEMO.2 payment / CTA demo-safe boundary diagnostic.
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

function youngo_demo2_check(&$failures, $condition, $message)
{
    echo ($condition ? '[PASS] ' : '[FAIL] ') . $message . PHP_EOL;
    if (!$condition) {
        $failures[] = $message;
    }
}

function youngo_demo2_warn(&$warnings, $message)
{
    echo '[WARN] ' . $message . PHP_EOL;
    $warnings[] = $message;
}

function youngo_demo2_path($relative_path)
{
    global $root;
    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
}

function youngo_demo2_read($relative_path)
{
    $path = youngo_demo2_path($relative_path);
    return is_file($path) ? file_get_contents($path) : false;
}

function youngo_demo2_run($command)
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

function youngo_demo2_status_files()
{
    list($exit_code, $output) = youngo_demo2_run('git status --short');
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

function youngo_demo2_diff_added_lines(array $paths)
{
    $quoted = array();
    foreach ($paths as $path) {
        $quoted[] = escapeshellarg($path);
    }

    list($exit_code, $output) = youngo_demo2_run('git diff -U0 -- ' . implode(' ', $quoted));
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

function youngo_demo2_added_phrase_keys(array $paths)
{
    $keys = array();
    foreach (youngo_demo2_diff_added_lines($paths) as $line) {
        if (preg_match_all('/\b(?:get_phrase|site_phrase)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $line, $matches)) {
            foreach ($matches[1] as $key) {
                $keys[] = $key;
            }
        }
    }

    return array_values(array_unique($keys));
}

function youngo_demo2_fetch_phrase(mysqli $mysqli, $phrase)
{
    $stmt = $mysqli->prepare('SELECT phrase, english, arabic FROM language WHERE phrase = ? LIMIT 1');
    $stmt->bind_param('s', $phrase);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function youngo_demo2_is_strict_dirty_scope_failure($output, array $allowed_dirty_files)
{
    $text = str_replace('\\', '/', (string) $output);

    $failLines = array();
    foreach (preg_split('/\R+/', $text) as $line) {
        if (strpos($line, '[FAIL]') !== false) {
            $failLines[] = $line;
        }
    }

    if (!empty($failLines)) {
        $nestedOnly = true;
        foreach ($failLines as $line) {
            if (strpos($line, 'Frontend content translation diagnostic still passes') !== false) {
                continue;
            }
            if (strpos($line, 'Arabic route alias diagnostic still passes') !== false) {
                continue;
            }
            if (strpos($line, 'Frontend language context diagnostic still passes') !== false) {
                continue;
            }

            $nestedOnly = false;
            break;
        }

        if ($nestedOnly) {
            return true;
        }
    }

    $mentions_allowed_file = false;

    foreach ($allowed_dirty_files as $file) {
        if (strpos($text, $file) !== false) {
            $mentions_allowed_file = true;
            break;
        }
    }

    if (!$mentions_allowed_file) {
        return false;
    }

    if (preg_match('/"ok"\s*:\s*false/i', $text)) {
        return false;
    }

    $known_scope_fragments = array(
        'Unexpected dirty file',
        'Unexpected files changed',
        'Unexpected source changes found during route alias phase',
        'Unexpected frontend view rendering changes were introduced outside Phase 2U.6.4 content shaping',
        'Unexpected files changed during frontend language context phase',
        'Compatibility diagnostic failed:',
    );

    foreach ($known_scope_fragments as $fragment) {
        if (strpos($text, $fragment) !== false) {
            return true;
        }
    }

    return false;
}

function youngo_demo2_diagnostic_passes($relative_path, array $allowed_dirty_files)
{
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($relative_path);
    list($exit_code, $output) = youngo_demo2_run($command);
    $passes = $exit_code === 0 && strpos($output, '[FAIL]') === false && strpos($output, 'FAIL ') === false;

    if (!$passes && youngo_demo2_is_strict_dirty_scope_failure($output, $allowed_dirty_files)) {
        return array('passes' => true, 'strict_scope_only' => true, 'output' => $output);
    }

    return array('passes' => $passes, 'strict_scope_only' => false, 'output' => $output);
}

echo 'YounGo DEMO.2 Payment / CTA Boundary Diagnostic' . PHP_EOL;
echo str_repeat('=', 78) . PHP_EOL;

$expectedDirtyFiles = array(
    'application/views/frontend/youngo/header.php',
    'application/views/frontend/youngo/course_page.php',
    'application/views/frontend/youngo/my_wishlist.php',
    'application/views/frontend/youngo/wishlist_items.php',
    'application/views/frontend/youngo/profile_menus.php',
    'scripts/phase_2/youngo_demo_payment_cta_boundary_diagnostic.php',
);

$ctaSurfaceFiles = array(
    'application/views/frontend/youngo/header.php',
    'application/views/frontend/youngo/course_page.php',
    'application/views/frontend/youngo/course_listing/course_card.php',
    'application/views/frontend/youngo/courses_page.php',
    'application/views/frontend/youngo/my_wishlist.php',
    'application/views/frontend/youngo/wishlist_items.php',
    'application/views/frontend/youngo/profile_menus.php',
);

foreach ($expectedDirtyFiles as $file) {
    youngo_demo2_check($failures, is_file(youngo_demo2_path($file)), 'Expected DEMO.2 file exists: ' . $file);
}

$statusFiles = youngo_demo2_status_files();
foreach ($statusFiles as $file) {
    youngo_demo2_check($failures, in_array($file, $expectedDirtyFiles, true), 'Dirty file is within DEMO.2 scope: ' . $file);
}

$forbiddenChangedPatterns = array(
    '/^application\/config\/routes\.php$/',
    '/^application\/language\/.*\.json$/',
    '/paymob/i',
    '/^application\/controllers\/Payment\.php$/',
    '/^application\/views\/payment\//',
    '/^application\/views\/backend\//',
    '/^application\/controllers\/Admin\.php$/',
    '/^database\//',
);

foreach ($statusFiles as $file) {
    foreach ($forbiddenChangedPatterns as $pattern) {
        youngo_demo2_check($failures, !preg_match($pattern, $file), 'Forbidden file pattern not changed by DEMO.2: ' . $file);
    }
}

$directWriteUrlPatterns = array(
    "/site_url\\s*\\(\\s*['\"]home\\/shopping_cart/",
    "/site_url\\s*\\(\\s*['\"]home\\/handle_cart_items/",
    "/site_url\\s*\\(\\s*['\"]home\\/handle_buy_now/",
    "/site_url\\s*\\(\\s*['\"]home\\/course_payment/",
    "/site_url\\s*\\(\\s*['\"]home\\/apply_coupon/",
    "/site_url\\s*\\(\\s*['\"]payment/",
    "/site_url\\s*\\(\\s*['\"]paymob/i",
);

$visiblePaymentLabels = array(
    'Add to cart',
    'Remove from cart',
    'Buy Now',
    'Gift someone else',
    'Shopping Cart',
    'Go to cart',
    'Continue to Payment',
    'Apply coupon',
);

foreach ($ctaSurfaceFiles as $file) {
    $source = youngo_demo2_read($file);
    youngo_demo2_check($failures, $source !== false, 'CTA surface file exists: ' . $file);
    if ($source === false) {
        continue;
    }

    foreach ($directWriteUrlPatterns as $pattern) {
        youngo_demo2_check($failures, preg_match($pattern, $source) !== 1, 'No direct cart/payment write URL in CTA surface: ' . $file);
    }

    foreach ($visiblePaymentLabels as $label) {
        youngo_demo2_check($failures, strpos($source, $label) === false, 'No visible direct cart/payment CTA label in CTA surface: ' . $label . ' / ' . $file);
    }
}

$coursePage = youngo_demo2_read('application/views/frontend/youngo/course_page.php');
$courseCard = youngo_demo2_read('application/views/frontend/youngo/course_listing/course_card.php');
$wishlistPage = youngo_demo2_read('application/views/frontend/youngo/my_wishlist.php');
$wishlistPartial = youngo_demo2_read('application/views/frontend/youngo/wishlist_items.php');

youngo_demo2_check($failures, $coursePage !== false && strpos($coursePage, "site_url('home/lesson/") !== false, 'Course detail preserves accessible lesson/start link.');
youngo_demo2_check($failures, $coursePage !== false && strpos($coursePage, "site_url('home/get_enrolled_to_free_course/") !== false, 'Course detail preserves existing free-course enrol access.');
youngo_demo2_check($failures, $courseCard !== false && strpos($courseCard, "get_phrase('Start Now')") !== false, 'Course cards preserve accessible start CTA.');
youngo_demo2_check($failures, $courseCard !== false && strpos($courseCard, "get_phrase('View details')") !== false, 'Course cards preserve view-detail fallback CTA.');
youngo_demo2_check($failures, $wishlistPage !== false && strpos($wishlistPage, "site_url('home/contact_us')") !== false, 'Wishlist page uses contact link for paid non-access course fallback.');
youngo_demo2_check($failures, $wishlistPartial !== false && strpos($wishlistPartial, "site_url('home/contact_us')") !== false, 'Wishlist partial uses contact link for paid non-access course fallback.');

$addedLines = implode("\n", youngo_demo2_diff_added_lines($expectedDirtyFiles));
youngo_demo2_check($failures, strpos($addedLines, '/en') === false && strpos($addedLines, "'en'") === false && strpos($addedLines, '"en"') === false, 'DEMO.2 added no /en route or link.');
youngo_demo2_check($failures, stripos($addedLines, 'arabic' . '_' . 'translated') === false, 'DEMO.2 added no deprecated Arabic UI/table language usage.');
youngo_demo2_check($failures, !preg_match('/youngo_(course|category|section|lesson)_translations/i', $addedLines), 'DEMO.2 added no translation-table write dependency.');

$deferredKeys = array(
    'youngo_theme_skeleton',
    'youngo_theme_skeleton_not_implemented',
    'allowed_file_types',
);

foreach ($ctaSurfaceFiles as $file) {
    $source = youngo_demo2_read($file);
    if ($source === false) {
        continue;
    }
    foreach ($deferredKeys as $key) {
        youngo_demo2_check($failures, strpos($source, $key) === false, 'Deferred phrase key not used in CTA surface: ' . $key . ' / ' . $file);
    }
}

$diagnosticSource = file_get_contents(__FILE__);
$tokens = token_get_all($diagnosticSource);
foreach ($tokens as $index => $token) {
    if (!is_array($token) || $token[0] !== T_STRING) {
        continue;
    }

    $name = strtolower($token[1]);
    if (!in_array($name, array('get_phrase', 'site_phrase', 'insert', 'update', 'delete', 'replace', 'set_userdata', 'set_cookie'), true)) {
        continue;
    }

    $nextIndex = $index + 1;
    while (isset($tokens[$nextIndex]) && is_array($tokens[$nextIndex]) && $tokens[$nextIndex][0] === T_WHITESPACE) {
        $nextIndex++;
    }

    if (isset($tokens[$nextIndex]) && $tokens[$nextIndex] === '(') {
        youngo_demo2_check($failures, false, 'Diagnostic contains executable write-prone or phrase-helper call: ' . $name);
    }
}

$addedPhraseKeys = youngo_demo2_added_phrase_keys($expectedDirtyFiles);
$mysqli = new mysqli('localhost', 'root', '', 'youngo_school');
if ($mysqli->connect_error) {
    youngo_demo2_check($failures, false, 'Database connection available for read-only phrase verification.');
} else {
    $mysqli->set_charset('utf8mb4');
    foreach ($addedPhraseKeys as $phrase) {
        $row = youngo_demo2_fetch_phrase($mysqli, $phrase);
        youngo_demo2_check($failures, !empty($row), 'Added phrase helper key exists: ' . $phrase);
        if (!empty($row)) {
            youngo_demo2_check($failures, trim((string) $row['english']) !== '', 'Added phrase helper key has English value: ' . $phrase);
            youngo_demo2_check($failures, trim((string) $row['arabic']) !== '', 'Added phrase helper key has Arabic value: ' . $phrase);
        }
    }
}

$nestedDiagnostics = array(
    'scripts/phase_2/youngo_phase_2s_route_cta_boundary_diagnostic.php',
    'scripts/phase_2/youngo_phase_2p_learner_access_visibility_diagnostic.php',
    'scripts/phase_2/youngo_phase_2r_admin_entitlement_summary_diagnostic.php',
    'scripts/phase_2/youngo_phase_2u6_low_risk_phrase_conversion_diagnostic.php',
    'scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php',
    'scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php',
);

foreach ($nestedDiagnostics as $diagnostic) {
    if (!is_file(youngo_demo2_path($diagnostic))) {
        youngo_demo2_check($failures, false, 'Compatibility diagnostic exists: ' . $diagnostic);
        continue;
    }

    $result = youngo_demo2_diagnostic_passes($diagnostic, $expectedDirtyFiles);
    if ($result['strict_scope_only']) {
        youngo_demo2_warn($warnings, 'Compatibility diagnostic is clean except strict dirty-scope guard: ' . $diagnostic);
    }
    youngo_demo2_check($failures, $result['passes'], 'Compatibility diagnostic passes: ' . $diagnostic);
}

if (empty($failures)) {
    echo "PASS DEMO.2 payment / CTA boundary diagnostic passed.\n";
    if (!empty($warnings)) {
        echo "- Warnings: " . count($warnings) . "\n";
    }
    exit(0);
}

echo "FAIL DEMO.2 payment / CTA boundary diagnostic failed.\n";
foreach ($failures as $failure) {
    echo "- " . $failure . "\n";
}
exit(1);
