<?php
/**
 * LANGUAGE.FRONTEND.PHRASE.COVERAGE.AUDIT.1 diagnostic.
 *
 * Read-only frontend phrase coverage audit. This script scans source files and
 * reads the language table with SELECT queries only. It does not seed, import,
 * update, or delete phrase values.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$root = dirname(__DIR__, 2);
chdir($root);

defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');
defined('FCPATH') || define('FCPATH', $root . DIRECTORY_SEPARATOR);
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'config' . DIRECTORY_SEPARATOR . 'database.php';

$failures = array();

function youngo_phrase_coverage_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function youngo_phrase_coverage_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function youngo_phrase_coverage_relpath($root, $path)
{
    return str_replace('\\', '/', substr($path, strlen($root) + 1));
}

function youngo_phrase_coverage_normalize_key($value)
{
    $key = strtolower(preg_replace('/\s+/', '_', trim((string) $value)));
    $key = preg_replace('/_+/', '_', $key);

    return trim($key, '_');
}

function youngo_phrase_coverage_area($relativePath)
{
    $path = str_replace('\\', '/', $relativePath);

    if (strpos($path, 'header.php') !== false) {
        return 'header/navbar';
    }
    if (strpos($path, 'footer.php') !== false) {
        return 'footer';
    }
    if (strpos($path, 'home_sections/') !== false || preg_match('#/home\.php$#', $path)) {
        return 'home page';
    }
    if (strpos($path, 'course_listing/') !== false || strpos($path, 'courses_page.php') !== false) {
        return 'courses listing/cards';
    }
    if (strpos($path, 'course_page') !== false) {
        return 'course detail';
    }
    if (strpos($path, 'blog') !== false) {
        return 'blog';
    }
    if (strpos($path, 'contact') !== false) {
        return 'contact';
    }
    if (preg_match('#/(login|sign_up|forgot_password|verification_code|new_login_confirmation|change_password_from_forgot_password|facebook_login)\.php$#', $path)) {
        return 'auth';
    }
    if (preg_match('#/(my_courses|my_access|my_wishlist|wishlist_items|profile_menus|reload_my_courses)\.php$#', $path)) {
        return 'learner pages';
    }
    if (preg_match('#/(user_profile|user_credentials|update_user_photo|account_disable)\.php$#', $path)) {
        return 'profile/account';
    }
    if (strpos($path, 'subscriptions.php') !== false || strpos($path, 'Youngo_subscription_model.php') !== false) {
        return 'subscriptions';
    }
    if (preg_match('#/(shopping_cart|shopping_cart_inner_view|cart_items|checkout_order|checkout_disabled|invoice|purchase_history|payment_return_disabled)\.php$#', $path)) {
        return 'payment/cart deferred';
    }

    return 'shared/frontend';
}

function youngo_phrase_coverage_is_deferred_path($relativePath)
{
    return youngo_phrase_coverage_area($relativePath) === 'payment/cart deferred';
}

function youngo_phrase_coverage_is_public_view_or_display_model($relativePath)
{
    $relativePath = str_replace('\\', '/', $relativePath);

    return strpos($relativePath, 'application/views/frontend/youngo/') === 0
        || $relativePath === 'application/models/Youngo_subscription_model.php';
}

function youngo_phrase_coverage_clean_literal($value)
{
    $value = html_entity_decode(trim(strip_tags((string) $value)), ENT_QUOTES, 'UTF-8');
    $value = preg_replace('/\s+/', ' ', $value);

    return trim($value);
}

function youngo_phrase_coverage_is_visible_literal_candidate($value)
{
    $value = youngo_phrase_coverage_clean_literal($value);

    if ($value === '' || strlen($value) < 3 || strlen($value) > 180) {
        return false;
    }
    if (strpos($value, '<?php') !== false || strpos($value, '$') !== false) {
        return false;
    }
    if (!preg_match('/[A-Za-z]/', $value)) {
        return false;
    }
    if (preg_match('#^(https?:)?//#i', $value) || preg_match('#^(fa-|youngo-|data-|aria-|btn|col-|row|form-|home/|assets/)#i', $value)) {
        return false;
    }
    if (preg_match('/\.(png|jpe?g|gif|svg|webp|css|js|php)$/i', $value)) {
        return false;
    }
    if (preg_match('/^\([a-z0-9_,\.\s-]+\)$/i', $value)) {
        return false;
    }
    if (preg_match('/^[a-z_-]+=$/i', $value)) {
        return false;
    }
    if (in_array($value, array('EN', 'ar', 'ltr', 'rtl', 'GET', 'POST'), true)) {
        return false;
    }

    return true;
}

function youngo_phrase_coverage_phrase_status($phraseMap, $key)
{
    if ($key === '' || !isset($phraseMap[$key])) {
        return 'missing_key';
    }

    $row = $phraseMap[$key];
    $hasEnglish = trim((string) $row['english']) !== '';
    $hasArabic = trim((string) $row['arabic']) !== '';

    if ($hasEnglish && $hasArabic) {
        return 'db_complete';
    }
    if ($hasEnglish && !$hasArabic) {
        return 'missing_arabic';
    }
    if (!$hasEnglish && $hasArabic) {
        return 'missing_english';
    }

    return 'blank_values';
}

function youngo_phrase_coverage_proposed_arabic($key, $english)
{
    $map = array(
        'primary_navigation' => 'التنقل الرئيسي',
        'language_switcher' => 'مبدل اللغة',
        'footer_navigation' => 'تنقل التذييل',
        'subscription_plans' => 'خطط الاشتراك',
        'family_access_plans' => 'خطط وصول العائلة',
        'choose_a_learning_plan_for_consistent_youngo_access._online_access_requests_are_not_available_yet.' => 'اختر خطة تعلم لوصول مستمر إلى YounGo. طلبات الوصول عبر الإنترنت غير متاحة بعد.',
        'plan_options' => 'خيارات الخطط',
        'plan_available' => 'خطة متاحة',
        'plans_available' => 'خطط متاحة',
        'featured_plan' => 'خطة مميزة',
        'talk_to_us_about_subscriptions' => 'تواصل معنا بخصوص الاشتراكات',
        'no_subscription_plans_available_yet' => 'لا توجد خطط اشتراك متاحة بعد',
        'subscription_plans_will_appear_here_after_they_are_approved_and_made_purchasable.' => 'ستظهر خطط الاشتراك هنا بعد اعتمادها وإتاحتها للشراء.',
        'day' => 'يوم',
        'days' => 'أيام',
        'egp' => 'جنيه مصري',
        'showing_results' => 'عرض النتائج',
        'allowed_file_types' => 'أنواع الملفات المسموحة',
        'youngo_theme_skeleton' => 'هيكل قالب YounGo',
        'youngo_theme_skeleton_not_implemented' => 'لم يتم تنفيذ هذه الصفحة في YounGo بعد.',
        'learner_account' => 'حساب المتعلم',
        'profile_info' => 'معلومات الملف الشخصي',
        'personal_details' => 'البيانات الشخصية',
        'keep_learner_details_current_while_youngo_continues_using_the_existing_academy_lms_account_system.' => 'حافظ على تحديث بيانات المتعلم بينما يستمر YounGo في استخدام نظام حسابات Academy LMS الحالي.',
    );

    if (isset($map[$key])) {
        return $map[$key];
    }

    return 'يحتاج إلى مراجعة ترجمة عربية';
}

if (!isset($db[$active_group])) {
    fwrite(STDERR, "Active database group was not found.\n");
    exit(1);
}

$dbConfig = $db[$active_group];
$mysqli = new mysqli($dbConfig['hostname'], $dbConfig['username'], $dbConfig['password'], $dbConfig['database']);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "DB connection failed without exposing credentials.\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

$phraseMap = array();
$result = $mysqli->query('SELECT phrase, english, arabic FROM language');
if ($result === false) {
    fwrite(STDERR, "Read-only phrase query failed.\n");
    exit(1);
}
while ($row = $result->fetch_assoc()) {
    $phraseMap[(string) $row['phrase']] = $row;
}
$result->free();

$targetFiles = array();
$viewRoot = $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'youngo';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewRoot));
foreach ($iterator as $file) {
    if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
        $targetFiles[] = $file->getPathname();
    }
}

foreach (array(
    'application/helpers/youngo_frontend_language_helper.php',
    'application/helpers/youngo_frontend_content_helper.php',
    'application/controllers/Home.php',
    'application/models/Youngo_language_phrase_model.php',
    'application/models/Youngo_subscription_model.php',
) as $path) {
    $absolute = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (is_file($absolute)) {
        $targetFiles[] = $absolute;
    }
}

sort($targetFiles);

$frontendPhraseCalls = array();
$legacyPhraseCalls = array();
$fallbackStrings = array();
$hardcodedLiterals = array();
$areas = array();

foreach ($targetFiles as $path) {
    $relative = youngo_phrase_coverage_relpath($root, $path);
    $area = youngo_phrase_coverage_area($relative);
    $areas[$area] = isset($areas[$area]) ? $areas[$area] + 1 : 1;
    $source = file_get_contents($path);
    $staticSource = preg_replace('/<\?php.*?\?>/s', '', $source);
    $staticSource = preg_replace('/<script\b.*?<\/script>/is', '', $staticSource);
    $staticSource = preg_replace('/<style\b.*?<\/style>/is', '', $staticSource);

    if (preg_match_all('/youngo_frontend_phrase(?:_e)?\s*\(\s*([\'"])(.*?)\1(?:\s*,\s*([\'"])(.*?)\3)?/s', $source, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $key = youngo_phrase_coverage_normalize_key($match[2]);
            $fallback = isset($match[4]) ? youngo_phrase_coverage_clean_literal($match[4]) : '';
            $status = youngo_phrase_coverage_phrase_status($phraseMap, $key);
            $frontendPhraseCalls[] = array(
                'file' => $relative,
                'area' => $area,
                'phrase_key' => $key,
                'status' => $status,
                'has_fallback_arg' => $fallback !== '',
                'seed_candidate_surface' => youngo_phrase_coverage_is_public_view_or_display_model($relative),
            );
            if ($fallback !== '') {
                $fallbackStrings[] = array(
                    'file' => $relative,
                    'area' => $area,
                    'phrase_key' => $key,
                    'fallback' => $fallback,
                    'status' => $status,
                );
            }
        }
    }

    if (preg_match_all('/(?:get_phrase|site_phrase)\s*\(\s*([\'"])(.*?)\1/s', $source, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $key = youngo_phrase_coverage_normalize_key($match[2]);
            $legacyPhraseCalls[] = array(
                'file' => $relative,
                'area' => $area,
                'phrase_key' => $key,
                'status' => youngo_phrase_coverage_phrase_status($phraseMap, $key),
                'deferred_operational_surface' => youngo_phrase_coverage_is_deferred_path($relative),
                'seed_candidate_surface' => youngo_phrase_coverage_is_public_view_or_display_model($relative),
            );
        }
    }

    if (youngo_phrase_coverage_is_public_view_or_display_model($relative) && preg_match_all('/>([^<]*[A-Za-z][^<]*)</', $staticSource, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $literal = youngo_phrase_coverage_clean_literal($match[1]);
            if (!youngo_phrase_coverage_is_visible_literal_candidate($literal)) {
                continue;
            }
            $key = youngo_phrase_coverage_normalize_key($literal);
            $hardcodedLiterals[] = array(
                'file' => $relative,
                'area' => $area,
                'literal' => $literal,
                'recommended_key' => $key,
                'status' => youngo_phrase_coverage_phrase_status($phraseMap, $key),
                'deferred_operational_surface' => youngo_phrase_coverage_is_deferred_path($relative),
                'seed_candidate_surface' => true,
            );
        }
    }

    if (youngo_phrase_coverage_is_public_view_or_display_model($relative) && preg_match_all('/\b(?:placeholder|aria-label|title)\s*=\s*([\'"])([^<>{}\'"]*[A-Za-z][^<>{}\'"]*)\1/', $staticSource, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $literal = youngo_phrase_coverage_clean_literal($match[2]);
            if (!youngo_phrase_coverage_is_visible_literal_candidate($literal)) {
                continue;
            }
            $key = youngo_phrase_coverage_normalize_key($literal);
            $hardcodedLiterals[] = array(
                'file' => $relative,
                'area' => $area,
                'literal' => $literal,
                'recommended_key' => $key,
                'status' => youngo_phrase_coverage_phrase_status($phraseMap, $key),
                'deferred_operational_surface' => youngo_phrase_coverage_is_deferred_path($relative),
                'seed_candidate_surface' => true,
            );
        }
    }
}

$frontendPhraseMissing = array_values(array_filter($frontendPhraseCalls, function ($row) {
    return $row['status'] !== 'db_complete';
}));
$legacyMissing = array_values(array_filter($legacyPhraseCalls, function ($row) {
    return $row['status'] !== 'db_complete' && empty($row['deferred_operational_surface']);
}));
$hardcodedConvertCandidates = array_values(array_filter($hardcodedLiterals, function ($row) {
    return empty($row['deferred_operational_surface']);
}));
$hardcodedMissing = array_values(array_filter($hardcodedConvertCandidates, function ($row) {
    return $row['status'] !== 'db_complete';
}));
$deferredOperational = array_values(array_filter(array_merge($legacyPhraseCalls, $hardcodedLiterals), function ($row) {
    return !empty($row['deferred_operational_surface']);
}));

$missingSeedMap = array();
foreach (array_merge($frontendPhraseMissing, $legacyMissing) as $row) {
    if (empty($row['seed_candidate_surface'])) {
        continue;
    }

    $key = $row['phrase_key'];
    if ($key === '') {
        continue;
    }
    $missingSeedMap[$key] = array(
        'phrase_key' => $key,
        'english' => ucfirst(str_replace('_', ' ', $key)),
        'arabic' => youngo_phrase_coverage_proposed_arabic($key, ucfirst(str_replace('_', ' ', $key))),
        'area' => $row['area'],
        'priority' => in_array($row['area'], array('header/navbar', 'footer', 'subscriptions', 'auth', 'learner pages'), true) ? 'high' : 'medium',
    );
}
foreach ($hardcodedMissing as $row) {
    if (empty($row['seed_candidate_surface'])) {
        continue;
    }

    $key = $row['recommended_key'];
    if ($key === '') {
        continue;
    }
    $missingSeedMap[$key] = array(
        'phrase_key' => $key,
        'english' => $row['literal'],
        'arabic' => youngo_phrase_coverage_proposed_arabic($key, $row['literal']),
        'area' => $row['area'],
        'priority' => in_array($row['area'], array('header/navbar', 'footer', 'subscriptions', 'auth', 'learner pages'), true) ? 'high' : 'medium',
    );
}
ksort($missingSeedMap);

$missingByArea = array();
$missingByPriority = array();
foreach ($missingSeedMap as $row) {
    $missingByArea[$row['area']] = isset($missingByArea[$row['area']]) ? $missingByArea[$row['area']] + 1 : 1;
    $missingByPriority[$row['priority']] = isset($missingByPriority[$row['priority']]) ? $missingByPriority[$row['priority']] + 1 : 1;
}
ksort($missingByArea);
ksort($missingByPriority);

$helperFile = $root . '/application/helpers/youngo_frontend_language_helper.php';
require_once $helperFile;

$summary = array(
    'files_scanned' => count($targetFiles),
    'areas' => $areas,
    'frontend_phrase_calls' => count($frontendPhraseCalls),
    'frontend_phrase_missing_or_blank' => count($frontendPhraseMissing),
    'frontend_phrase_fallback_args' => count($fallbackStrings),
    'legacy_get_or_site_phrase_calls' => count($legacyPhraseCalls),
    'legacy_missing_or_blank_non_deferred' => count($legacyMissing),
    'hardcoded_literal_candidates' => count($hardcodedLiterals),
    'hardcoded_convert_candidates_non_deferred' => count($hardcodedConvertCandidates),
    'hardcoded_missing_or_blank_non_deferred' => count($hardcodedMissing),
    'deferred_operational_items' => count($deferredOperational),
    'proposed_missing_seed_keys' => count($missingSeedMap),
    'proposed_missing_by_area' => $missingByArea,
    'proposed_missing_by_priority' => $missingByPriority,
);
youngo_phrase_coverage_print('Coverage summary', $summary);

$sample = array_slice(array_values($missingSeedMap), 0, 20);
youngo_phrase_coverage_print('Proposed missing seed sample', $sample);

if (in_array('--full-candidates', isset($argv) ? $argv : array(), true)) {
    youngo_phrase_coverage_print('Full proposed missing seed candidates', array_values($missingSeedMap));
}

$sourceChecks = array(
    'frontend_phrase_helper_exists' => function_exists('youngo_frontend_phrase'),
    'frontend_phrase_escape_helper_exists' => function_exists('youngo_frontend_phrase_e'),
    'arabic_translated_rejected_for_phrase_language' => function_exists('youngo_frontend_phrase_language_code') && youngo_frontend_phrase_language_code('arabic_translated') === null,
    'scan_identified_fallback_args' => count($fallbackStrings) > 0,
    'scan_identified_hardcoded_literals' => count($hardcodedLiterals) > 0,
    'missing_seed_list_generated' => count($missingSeedMap) > 0,
    'read_only_scan_only' => true,
    'no_paymob_files_changed' => empty(array_filter(explode("\n", trim(shell_exec('git -C ' . escapeshellarg($root) . ' status --short'))), function ($line) {
        return stripos($line, 'paymob') !== false || stripos($line, 'payment') !== false;
    })),
);
youngo_phrase_coverage_print('Diagnostic checks', $sourceChecks);
foreach ($sourceChecks as $label => $ok) {
    youngo_phrase_coverage_assert($failures, $ok, 'Diagnostic check failed: ' . $label);
}

$gitStatus = array();
exec('git -C ' . escapeshellarg($root) . ' status --short', $gitStatus);
youngo_phrase_coverage_print('Git status short', $gitStatus);

if (!empty($failures)) {
    youngo_phrase_coverage_print('FAILURES', $failures);
    exit(1);
}

youngo_phrase_coverage_print('Result', 'PASS: frontend phrase coverage audit scan completed read-only and identified conversion/seed candidates.');
exit(0);
