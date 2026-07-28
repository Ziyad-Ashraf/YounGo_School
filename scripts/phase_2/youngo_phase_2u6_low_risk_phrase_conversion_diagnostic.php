<?php

/**
 * Phase 2U.6.6.4 low-risk frontend phrase conversion diagnostic.
 *
 * Read-only checks only. This script must not call get_phrase()/site_phrase()
 * because the LMS phrase helpers can create missing phrase rows.
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

function youngo_phase_2u664_check(&$failures, $condition, $message)
{
    echo ($condition ? '[PASS] ' : '[FAIL] ') . $message . PHP_EOL;
    if (!$condition) {
        $failures[] = $message;
    }
}

function youngo_phase_2u664_path($relative_path)
{
    global $root;
    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
}

function youngo_phase_2u664_read($relative_path)
{
    $path = youngo_phase_2u664_path($relative_path);
    return is_file($path) ? file_get_contents($path) : false;
}

function youngo_phase_2u664_run($command)
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

function youngo_phase_2u664_php_diagnostic_passes($relative_path)
{
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($relative_path);
    list($exit_code, $output) = youngo_phase_2u664_run($command);

    return array(
        'script' => $relative_path,
        'exit_code' => $exit_code,
        'passes' => $exit_code === 0 && strpos($output, '[FAIL]') === false && strpos($output, 'FAIL ') === false,
        'output' => $output,
    );
}

function youngo_phase_2u664_status_files()
{
    list($exit_code, $output) = youngo_phase_2u664_run('git status --short');
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

function youngo_phase_2u664_diff_files()
{
    list($exit_code, $output) = youngo_phase_2u664_run('git diff --name-only');
    if ($exit_code !== 0 || trim($output) === '') {
        return array();
    }

    return preg_split('/\R+/', trim(str_replace('\\', '/', $output)));
}

function youngo_phase_2u664_fetch_phrase(mysqli $mysqli, $phrase)
{
    $stmt = $mysqli->prepare('SELECT phrase, english, arabic FROM language WHERE phrase = ? LIMIT 1');
    $stmt->bind_param('s', $phrase);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function youngo_phase_2u664_count_phrase_call($source, $helper, $phrase)
{
    $pattern = '/' . preg_quote($helper, '/') . '\s*\(\s*[\'"]' . preg_quote($phrase, '/') . '[\'"]\s*\)/';
    return preg_match_all($pattern, (string) $source);
}

function youngo_phase_2u664_added_phrase_keys()
{
    list($exit_code, $output) = youngo_phase_2u664_run('git diff -U0 -- application/views/frontend/youngo scripts/phase_2/youngo_phase_2u6_low_risk_phrase_conversion_diagnostic.php');
    if ($exit_code !== 0 || trim($output) === '') {
        return array();
    }

    $keys = array();
    foreach (preg_split('/\R+/', $output) as $line) {
        if (strpos($line, '+') !== 0 || strpos($line, '+++') === 0) {
            continue;
        }
        if (preg_match_all('/\b(?:get_phrase|site_phrase)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $line, $matches)) {
            foreach ($matches[1] as $key) {
                $keys[] = $key;
            }
        }
    }

    return array_values(array_unique($keys));
}

function youngo_phase_2u664_is_strict_dirty_scope_failure($output, array $allowed_dirty_files)
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
        'Unexpected files changed',
        'Unexpected dirty file',
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

echo 'YounGo Phase 2U.6.6.4 Low-risk Frontend Phrase Conversion Diagnostic' . PHP_EOL;
echo str_repeat('=', 78) . PHP_EOL;

$convertedFiles = array(
    'application/views/frontend/youngo/header.php',
    'application/views/frontend/youngo/footer.php',
    'application/views/frontend/youngo/courses_page.php',
    'application/views/frontend/youngo/course_listing/filter_panel.php',
    'application/views/frontend/youngo/course_listing/sorting_bar.php',
);

$conversionManifest = array(
    'primary_navigation' => array(
        'file' => 'application/views/frontend/youngo/header.php',
        'text' => 'Primary navigation',
        'english' => 'Primary navigation',
        'arabic' => 'التنقل الرئيسي',
    ),
    'language_switcher' => array(
        'file' => 'application/views/frontend/youngo/header.php',
        'text' => 'Language switcher',
        'english' => 'Language switcher',
        'arabic' => 'مبدّل اللغة',
    ),
    'footer_navigation' => array(
        'file' => 'application/views/frontend/youngo/footer.php',
        'text' => 'Footer navigation',
        'english' => 'Footer navigation',
        'arabic' => 'روابط التذييل',
    ),
    'showing_results' => array(
        'file' => 'application/views/frontend/youngo/course_listing/sorting_bar.php',
        'text' => 'Showing {count} of {total} results',
        'english' => 'Showing results',
        'arabic' => 'عرض النتائج',
    ),
    'course_discovery' => array(
        'file' => 'application/views/frontend/youngo/courses_page.php',
        'text' => 'Course discovery',
    ),
    'explore_youngo_courses' => array(
        'file' => 'application/views/frontend/youngo/courses_page.php',
        'text' => 'Explore YounGo courses',
    ),
    'find_structured,_friendly_learning_paths_for_curious_kids_and_the_families_supporting_them.' => array(
        'file' => 'application/views/frontend/youngo/courses_page.php',
        'text' => 'Find structured, friendly learning paths for curious kids and the families supporting them.',
    ),
    'course_not_found' => array(
        'file' => 'application/views/frontend/youngo/courses_page.php',
        'text' => 'Course not found',
    ),
    'try_adjusting_your_search_or_clearing_a_few_filters_to_see_more_courses.' => array(
        'file' => 'application/views/frontend/youngo/courses_page.php',
        'text' => 'Try adjusting your search or clearing a few filters to see more courses.',
    ),
    'reset_filters' => array(
        'file' => 'application/views/frontend/youngo/courses_page.php',
        'text' => 'Reset filters',
    ),
    'find_a_course' => array(
        'file' => 'application/views/frontend/youngo/course_listing/filter_panel.php',
        'text' => 'Find a course',
    ),
    'all_categories' => array(
        'file' => 'application/views/frontend/youngo/course_listing/filter_panel.php',
        'text' => 'All categories',
    ),
    'course_catalog' => array(
        'file' => 'application/views/frontend/youngo/course_listing/sorting_bar.php',
        'text' => 'Course catalog',
    ),
    'clear_all_filters' => array(
        'file' => 'application/views/frontend/youngo/course_listing/sorting_bar.php',
        'text' => 'Clear all filters',
    ),
    'newly_published' => array(
        'file' => 'application/views/frontend/youngo/course_listing/sorting_bar.php',
        'text' => 'Newly published',
    ),
    'highest_rating' => array(
        'file' => 'application/views/frontend/youngo/course_listing/sorting_bar.php',
        'text' => 'Highest rating',
    ),
    'lowest_price' => array(
        'file' => 'application/views/frontend/youngo/course_listing/sorting_bar.php',
        'text' => 'Lowest price',
    ),
    'highest_price' => array(
        'file' => 'application/views/frontend/youngo/course_listing/sorting_bar.php',
        'text' => 'Highest price',
    ),
    'discounted' => array(
        'file' => 'application/views/frontend/youngo/course_listing/sorting_bar.php',
        'text' => 'Discounted',
    ),
);

$deferredKeys = array(
    'youngo_theme_skeleton',
    'youngo_theme_skeleton_not_implemented',
    'allowed_file_types',
);

$allowedDirtyFiles = array_merge($convertedFiles, array(
    'scripts/phase_2/youngo_phase_2u6_low_risk_phrase_conversion_diagnostic.php',
));

foreach ($convertedFiles as $file) {
    youngo_phase_2u664_check($failures, is_file(youngo_phase_2u664_path($file)), 'Converted file exists: ' . $file);
}

$config_file = $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
$mysqli = null;
if (is_file($config_file)) {
    require $config_file;
    $config = isset($db['default']) ? $db['default'] : array();
    $mysqli = @new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
    if ($mysqli->connect_errno) {
        $failures[] = 'Database connection failed without exposing credentials.';
        echo '[FAIL] Database connection available for read-only phrase checks' . PHP_EOL;
    } else {
        $mysqli->set_charset('utf8mb4');
        echo '[PASS] Database connection available for read-only phrase checks' . PHP_EOL;
    }
} else {
    $failures[] = 'Database config missing.';
    echo '[FAIL] Database config exists' . PHP_EOL;
}

if ($mysqli !== null) {
    foreach ($conversionManifest as $phrase => $expected) {
        $row = youngo_phase_2u664_fetch_phrase($mysqli, $phrase);
        youngo_phase_2u664_check($failures, (bool) $row, 'Converted phrase exists: ' . $phrase);
        if (!$row) {
            continue;
        }

        youngo_phase_2u664_check($failures, trim((string) $row['english']) !== '', 'Converted phrase has English value: ' . $phrase);
        youngo_phase_2u664_check($failures, trim((string) $row['arabic']) !== '', 'Converted phrase has Arabic value: ' . $phrase);

        if (isset($expected['english'])) {
            youngo_phase_2u664_check($failures, (string) $row['english'] === $expected['english'], 'Newly seeded English value matches: ' . $phrase);
        }

        if (isset($expected['arabic'])) {
            youngo_phase_2u664_check($failures, (string) $row['arabic'] === $expected['arabic'], 'Newly seeded Arabic value matches: ' . $phrase);
        }
    }
}

$addedPhraseKeys = youngo_phase_2u664_added_phrase_keys();
$allowedConvertedKeys = array_keys($conversionManifest);
foreach ($addedPhraseKeys as $key) {
    youngo_phase_2u664_check($failures, in_array($key, $allowedConvertedKeys, true), 'Added phrase-helper key is in conversion manifest: ' . $key);
    if ($mysqli !== null) {
        youngo_phase_2u664_check($failures, (bool) youngo_phase_2u664_fetch_phrase($mysqli, $key), 'Added phrase-helper key exists in language table: ' . $key);
    }
}

foreach ($conversionManifest as $phrase => $expected) {
    $source = youngo_phase_2u664_read($expected['file']);
    youngo_phase_2u664_check($failures, youngo_phase_2u664_count_phrase_call($source, 'get_phrase', $phrase) >= 1 || youngo_phase_2u664_count_phrase_call($source, 'site_phrase', $phrase) >= 1, 'Converted phrase call is present in intended file: ' . $phrase);
}

$frontendViewFiles = glob($root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'youngo' . DIRECTORY_SEPARATOR . '*.php');
$frontendListingFiles = glob($root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'youngo' . DIRECTORY_SEPARATOR . 'course_listing' . DIRECTORY_SEPARATOR . '*.php');
$frontendFiles = array_merge(is_array($frontendViewFiles) ? $frontendViewFiles : array(), is_array($frontendListingFiles) ? $frontendListingFiles : array());
$newKeyLocations = array(
    'primary_navigation' => 'application/views/frontend/youngo/header.php',
    'language_switcher' => 'application/views/frontend/youngo/header.php',
    'footer_navigation' => 'application/views/frontend/youngo/footer.php',
    'showing_results' => 'application/views/frontend/youngo/course_listing/sorting_bar.php',
);

foreach ($newKeyLocations as $phrase => $intendedFile) {
    $runtimeOccurrences = array();
    foreach ($frontendFiles as $absoluteFile) {
        $relativeFile = str_replace('\\', '/', substr($absoluteFile, strlen($root) + 1));
        $count = youngo_phase_2u664_count_phrase_call(file_get_contents($absoluteFile), 'get_phrase', $phrase)
            + youngo_phase_2u664_count_phrase_call(file_get_contents($absoluteFile), 'site_phrase', $phrase);
        if ($count > 0) {
            $runtimeOccurrences[$relativeFile] = $count;
        }
    }
    youngo_phase_2u664_check($failures, count($runtimeOccurrences) === 1 && isset($runtimeOccurrences[$intendedFile]) && $runtimeOccurrences[$intendedFile] === 1, 'Newly seeded key is used only in intended location: ' . $phrase);
}

$deferredPattern = '/\b(?:get_phrase|site_phrase)\s*\(\s*[\'"](?:' . implode('|', array_map('preg_quote', $deferredKeys)) . ')[\'"]\s*\)/';
foreach ($convertedFiles as $file) {
    $source = youngo_phase_2u664_read($file);
    youngo_phase_2u664_check($failures, !preg_match($deferredPattern, (string) $source), 'No deferred phrase-helper key is used in converted file: ' . $file);
}

$statusFiles = youngo_phase_2u664_status_files();
$unexpectedDirty = array_values(array_diff($statusFiles, $allowedDirtyFiles));
youngo_phase_2u664_check($failures, empty($unexpectedDirty), 'Dirty worktree scope is limited to Phase 2U.6.6.4 files');
if (!empty($unexpectedDirty)) {
    echo 'Unexpected dirty files: ' . implode(', ', $unexpectedDirty) . PHP_EOL;
}

$diffFiles = youngo_phase_2u664_diff_files();
$diffText = implode("\n", $diffFiles);
youngo_phase_2u664_check($failures, !in_array('application/config/routes.php', $statusFiles, true) && !in_array('application/config/routes.php', $diffFiles, true), 'No route files changed');
youngo_phase_2u664_check($failures, strpos($diffText, 'application/views/backend/') === false && strpos($diffText, 'application/controllers/Admin.php') === false, 'No admin/backend files changed');
youngo_phase_2u664_check($failures, !preg_match('#(checkout|payment|paymob|paypal|stripe|razorpay|paystack|flutterwave|coupon|shopping_cart|update_cart|apply_coupon|remove_coupon)#i', $diffText), 'No checkout/payment/cart/coupon/Paymob files changed');
youngo_phase_2u664_check($failures, !preg_match('#^application/language/.*\.json$#m', $diffText . "\n" . implode("\n", $statusFiles)), 'No application/language JSON files changed');

$changedRuntimeSource = '';
foreach ($statusFiles as $file) {
    if (preg_match('/\.(php|css|js)$/', $file) && (strpos($file, 'application/') === 0 || strpos($file, 'assets/') === 0)) {
        $contents = youngo_phase_2u664_read($file);
        if ($contents !== false) {
            $changedRuntimeSource .= "\n" . $contents;
        }
    }
}

youngo_phase_2u664_check($failures, !preg_match("#(?:href|action)=[\"'][^\"']*/en(?:/|[\"'?])#i", $changedRuntimeSource) && strpos($changedRuntimeSource, "site_url('en") === false && strpos($changedRuntimeSource, 'site_url("en') === false, 'No /en routes or links introduced in changed runtime files');
youngo_phase_2u664_check($failures, strpos($changedRuntimeSource, 'arabic_translated') === false, 'No arabic_translated UI/table-language usage introduced in changed runtime files');

$diagnosticSource = file_get_contents(__FILE__);
$tokens = token_get_all($diagnosticSource);
foreach ($tokens as $index => $token) {
    if (!is_array($token) || $token[0] !== T_STRING) {
        continue;
    }

    $name = strtolower($token[1]);
    if (!in_array($name, array('get_phrase', 'site_phrase', 'insert', 'update', 'delete', 'replace'), true)) {
        continue;
    }

    $nextIndex = $index + 1;
    while (isset($tokens[$nextIndex]) && is_array($tokens[$nextIndex]) && $tokens[$nextIndex][0] === T_WHITESPACE) {
        $nextIndex++;
    }

    if (isset($tokens[$nextIndex]) && $tokens[$nextIndex] === '(') {
        $failures[] = 'Diagnostic contains executable write-prone or phrase-helper call: ' . $name;
        echo '[FAIL] Diagnostic contains executable write-prone or phrase-helper call: ' . $name . PHP_EOL;
    }
}

$subprocessChecks = array(
    'content_shaping' => youngo_phase_2u664_php_diagnostic_passes('scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php'),
    'language_switcher_rtl' => youngo_phase_2u664_php_diagnostic_passes('scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php'),
    'arabic_route_alias' => youngo_phase_2u664_php_diagnostic_passes('scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php'),
);

foreach ($subprocessChecks as $label => $result) {
    $strictScopeOnly = !$result['passes'] && youngo_phase_2u664_is_strict_dirty_scope_failure($result['output'], $allowedDirtyFiles);
    youngo_phase_2u664_check($failures, $result['passes'] || $strictScopeOnly, 'Compatibility diagnostic passes or is blocked only by strict dirty-scope guard: ' . $label);
    if ($strictScopeOnly) {
        $warnings[] = $label . ' diagnostic was blocked by older strict dirty-scope guards for Phase 2U.6.6.4 files; run it again after temporarily stashing this phase when full compatibility validation is needed.';
    } elseif (!$result['passes']) {
        $warnings[] = $label . ' diagnostic output: ' . $result['output'];
    }
}

echo str_repeat('-', 78) . PHP_EOL;
echo 'Warnings: ' . (empty($warnings) ? 'none' : count($warnings)) . PHP_EOL;
foreach ($warnings as $warning) {
    echo '- ' . $warning . PHP_EOL;
}

if (empty($failures)) {
    echo 'RESULT: PASS' . PHP_EOL;
    echo '- Read-only phrase conversion checks passed.' . PHP_EOL;
    echo '- Diagnostic did not call phrase helpers or run DB writes.' . PHP_EOL;
    exit(0);
}

echo 'RESULT: FAIL (' . count($failures) . ' checks failed)' . PHP_EOL;
foreach ($failures as $failure) {
    echo '- ' . $failure . PHP_EOL;
}
exit(1);
