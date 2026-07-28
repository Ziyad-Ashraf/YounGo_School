<?php
/**
 * DEMO.FIX.1 phrase-safe demo surface cleanup diagnostic.
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
$failures = array();
$warnings = array();
$passes = array();

function demo_fix1_say($label, $message)
{
    echo '[' . $label . '] ' . $message . PHP_EOL;
}

function demo_fix1_pass(&$passes, $message)
{
    $passes[] = $message;
    demo_fix1_say('PASS', $message);
}

function demo_fix1_warn(&$warnings, $message)
{
    $warnings[] = $message;
    demo_fix1_say('WARN', $message);
}

function demo_fix1_fail(&$failures, $message)
{
    $failures[] = $message;
    demo_fix1_say('FAIL', $message);
}

function demo_fix1_path($root, $relative)
{
    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function demo_fix1_read($root, $relative)
{
    $path = demo_fix1_path($root, $relative);
    return is_file($path) ? file_get_contents($path) : false;
}

function demo_fix1_rel_path($root, $path)
{
    return str_replace('\\', '/', str_replace($root . DIRECTORY_SEPARATOR, '', $path));
}

function demo_fix1_list_files($root, $relative)
{
    $base = demo_fix1_path($root, $relative);
    if (!is_dir($base)) {
        return array();
    }

    $files = array();
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $files[] = demo_fix1_rel_path($root, $file->getPathname());
        }
    }
    sort($files);
    return $files;
}

function demo_fix1_has_legacy_phrase_call($source)
{
    $legacy_functions = array('get_' . 'phrase', 'site_' . 'phrase');
    foreach ($legacy_functions as $function_name) {
        if (preg_match('/\b' . preg_quote($function_name, '/') . '\s*\(/', (string) $source)) {
            return true;
        }
    }
    return false;
}

function demo_fix1_git_status_files($root)
{
    $output = array();
    $code = 0;
    exec('git -C ' . escapeshellarg($root) . ' status --short', $output, $code);
    if ($code !== 0) {
        return null;
    }

    $files = array();
    foreach ($output as $line) {
        $path = trim(substr($line, 3));
        if (strpos($path, ' -> ') !== false) {
            $parts = explode(' -> ', $path);
            $path = end($parts);
        }
        if ($path !== '') {
            $files[] = str_replace('\\', '/', $path);
        }
    }

    return $files;
}

function demo_fix1_db_connect($root, &$warnings)
{
    $config = demo_fix1_path($root, 'application/config/database.php');
    if (!is_file($config)) {
        demo_fix1_warn($warnings, 'Database config is missing; DB checks skipped.');
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
        demo_fix1_warn($warnings, 'Database active group is unavailable; DB checks skipped.');
        return null;
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $cfg = $db[$active_group];
    $mysqli = @new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database']);
    if ($mysqli->connect_errno) {
        demo_fix1_warn($warnings, 'Database connection failed; DB checks skipped: ' . $mysqli->connect_error);
        return null;
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

function demo_fix1_db_scalar($mysqli, $sql)
{
    $result = $mysqli->query($sql);
    if (!$result) {
        return null;
    }
    $row = $result->fetch_row();
    return $row ? $row[0] : null;
}

function demo_fix1_table_exists($mysqli, $table)
{
    $safe_table = $mysqli->real_escape_string($table);
    return (int) demo_fix1_db_scalar($mysqli, "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '" . $safe_table . "'") > 0;
}

demo_fix1_say('INFO', 'YounGo DEMO.FIX.1 phrase-safe demo surface cleanup diagnostic');

$expected_files = array(
    'application/helpers/youngo_frontend_language_helper.php',
    'application/views/frontend/youngo/my_wishlist.php',
    'application/views/frontend/youngo/wishlist_items.php',
    'application/views/frontend/youngo/course_page.php',
    'application/views/frontend/youngo/course_page_reviews.php',
    'application/views/frontend/youngo/course_listing/course_card.php',
    'application/views/frontend/youngo/login.php',
    'application/views/frontend/youngo/sign_up.php',
    'application/views/frontend/youngo/blog_details.php',
    'application/views/frontend/youngo/contact_us.php',
);

foreach ($expected_files as $relative) {
    if (is_file(demo_fix1_path($root, $relative))) {
        demo_fix1_pass($passes, 'Expected file exists: ' . $relative);
    } else {
        demo_fix1_fail($failures, 'Expected file missing: ' . $relative);
    }
}

$helper = demo_fix1_read($root, 'application/helpers/youngo_frontend_language_helper.php');
if ($helper !== false && !demo_fix1_has_legacy_phrase_call($helper)) {
    demo_fix1_pass($passes, 'Route-aware YounGo helper has no direct legacy phrase helper calls.');
} else {
    demo_fix1_fail($failures, 'Route-aware YounGo helper must not call legacy phrase helpers.');
}

$required_phrase_keys = array(
    'guided_learning_for_curious_kids',
    'starts',
    'course_actions',
    'preview_this_course',
    'course_description',
    'learning_goals',
    'what_will_i_learn?',
    'before_class',
    'no_curriculum_sections_are_available_yet.',
    'meet_your_guide',
    'course_guide',
    'trusted_guide',
    'view_profile',
    'family_and_learner_feedback',
    'frequently_asked_questions',
    'more_details',
    'additional_information',
    'watch_video',
    'course_confidence',
    'a_structured_learning_path_with_clear_lessons,_instructor_guidance,_and_progress-friendly_activities.',
    'share_on_facebook',
    'share_on_twitter',
    'share_on_whatsapp',
    'share_on_linkedin',
    'keep_exploring',
    'related_courses',
    'access_locked',
    'write_a_review',
    'rating',
    '1_star_rating',
    '2_star_rating',
    '3_star_rating',
    '4_star_rating',
    '5_star_rating',
    'review',
    'write_your_comment',
    'submit',
    'no_reviews_yet.',
    'stars',
    'edit',
    'remove_review',
    'remove',
    'keep_favorite_youngo_courses_in_one_place_then_return_when_your_child_is_ready_to_start.',
    'saved_courses_stay_here_so_you_can_compare_learning_paths_before_access_is_granted.',
    'courses_you_saved',
    'explore_more_courses',
    'or_continue_with',
    'or_sign_up_with',
);

$missing_keys = array();
foreach ($required_phrase_keys as $phrase_key) {
    $needle = "'" . $phrase_key . "' =>";
    if ($helper === false || substr_count($helper, $needle) < 2) {
        $missing_keys[] = $phrase_key;
    }
}

if (empty($missing_keys)) {
    demo_fix1_pass($passes, 'Local phrase map includes converted English and Arabic demo labels.');
} else {
    demo_fix1_fail($failures, 'Missing English/Arabic local phrase-map keys: ' . implode(', ', $missing_keys));
}

$converted_demo_views = array(
    'application/views/frontend/youngo/my_wishlist.php',
    'application/views/frontend/youngo/wishlist_items.php',
    'application/views/frontend/youngo/course_page.php',
    'application/views/frontend/youngo/course_page_reviews.php',
    'application/views/frontend/youngo/course_listing/course_card.php',
    'application/views/frontend/youngo/login.php',
    'application/views/frontend/youngo/sign_up.php',
);

$legacy_hits = array();
foreach ($converted_demo_views as $relative) {
    $source = demo_fix1_read($root, $relative);
    if ($source !== false && demo_fix1_has_legacy_phrase_call($source)) {
        $legacy_hits[] = $relative;
    }
}

if (empty($legacy_hits)) {
    demo_fix1_pass($passes, 'Converted demo-critical YounGo views have no direct legacy phrase helper calls.');
} else {
    demo_fix1_fail($failures, 'Converted demo-critical views still call legacy phrase helpers: ' . implode(', ', $legacy_hits));
}

$wishlist = demo_fix1_read($root, 'application/views/frontend/youngo/my_wishlist.php');
$old_wishlist_sentence = 'Saved courses stay here so you can compare learning paths before access is granted or checkout becomes available.';
if ($wishlist !== false && strpos($wishlist, $old_wishlist_sentence) === false && strpos($wishlist, "youngo_frontend_phrase('saved_courses_stay_here_so_you_can_compare_learning_paths_before_access_is_granted.')") !== false) {
    demo_fix1_pass($passes, 'Wishlist inserted sentence now renders through the route-aware local phrase key.');
} else {
    demo_fix1_fail($failures, 'Wishlist inserted sentence cleanup is incomplete.');
}

$frontend_files = demo_fix1_list_files($root, 'application/views/frontend/youngo');
$frontend_files[] = 'application/config/routes.php';
$en_patterns = array(
    '/\$route\s*\[\s*[\'"]en(?:\/|[\'"])/i',
    '/href\s*=\s*[\'"][^\'"]*\/en(?:\/|[\'"?#])/i',
    '/site_url\s*\(\s*[\'"]en(?:\/|[\'"])/i',
    '/base_url\s*\(\s*[\'"]en(?:\/|[\'"])/i',
);
$en_hits = array();
foreach ($frontend_files as $relative) {
    $source = demo_fix1_read($root, $relative);
    if ($source === false) {
        continue;
    }
    foreach ($en_patterns as $pattern) {
        if (preg_match($pattern, $source)) {
            $en_hits[] = $relative;
            break;
        }
    }
}

if (empty($en_hits)) {
    demo_fix1_pass($passes, 'No /en route or link patterns found in YounGo frontend files.');
} else {
    demo_fix1_fail($failures, 'Unexpected /en route/link patterns: ' . implode(', ', $en_hits));
}

$demo_link_views = array(
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
    'application/views/frontend/youngo/login.php',
    'application/views/frontend/youngo/sign_up.php',
);
$payment_link_patterns = array(
    '/<a\b[^>]*href\s*=\s*[\'"][^\'"]*(?:cart|checkout|payment|paymob|coupon)/i',
    '/<form\b[^>]*action\s*=\s*[\'"][^\'"]*(?:cart|checkout|payment|paymob|coupon)/i',
    '/site_url\s*\(\s*[\'"][^\'"]*(?:cart|checkout|payment|paymob|coupon)/i',
    '/base_url\s*\(\s*[\'"][^\'"]*(?:cart|checkout|payment|paymob|coupon)/i',
    '/>\s*(?:Buy Now|Add to cart|Continue to Payment|Apply coupon|Coupon code)\s*</i',
    '/Paymob/i',
);
$payment_link_hits = array();
foreach ($demo_link_views as $relative) {
    $source = demo_fix1_read($root, $relative);
    if ($source === false) {
        continue;
    }
    foreach ($payment_link_patterns as $pattern) {
        if (preg_match($pattern, $source)) {
            $payment_link_hits[] = $relative;
            break;
        }
    }
}

if (empty($payment_link_hits)) {
    demo_fix1_pass($passes, 'No payment/cart/checkout/coupon/Paymob entry links found in demo YounGo views.');
} else {
    demo_fix1_fail($failures, 'Payment/cart/checkout/coupon/Paymob entry links found in demo views: ' . implode(', ', $payment_link_hits));
}

$contact = demo_fix1_read($root, 'application/views/frontend/youngo/contact_us.php');
if ($contact !== false && !preg_match('/<form\b[^>]*method\s*=\s*[\'"]?post/i', $contact) && stripos($contact, 'form_open') === false) {
    demo_fix1_pass($passes, 'YounGo contact view has no contact POST form.');
} else {
    demo_fix1_fail($failures, 'YounGo contact view appears to contain a POST/form helper.');
}

$blog_detail = demo_fix1_read($root, 'application/views/frontend/youngo/blog_details.php');
if ($blog_detail !== false && !preg_match('/<form\b[^>]*(comment|method\s*=\s*[\'"]?post)/i', $blog_detail) && stripos($blog_detail, 'add_comment') === false) {
    demo_fix1_pass($passes, 'YounGo blog detail view has no comment POST form.');
} else {
    demo_fix1_fail($failures, 'YounGo blog detail view appears to contain a comment POST form.');
}

$changed_files = demo_fix1_git_status_files($root);
if ($changed_files === null) {
    demo_fix1_fail($failures, 'Unable to read git status.');
} else {
    $allowed_changed_files = array(
        'application/helpers/youngo_frontend_language_helper.php',
        'application/views/frontend/youngo/course_listing/course_card.php',
        'application/views/frontend/youngo/course_page.php',
        'application/views/frontend/youngo/course_page_reviews.php',
        'application/views/frontend/youngo/login.php',
        'application/views/frontend/youngo/my_wishlist.php',
        'application/views/frontend/youngo/sign_up.php',
        'scripts/phase_2/youngo_demo_phrase_safe_surface_cleanup_diagnostic.php',
    );
    $unexpected_changed_files = array();
    $disallowed_changed_files = array();
    foreach ($changed_files as $changed_file) {
        $normalized = strtolower(str_replace('\\', '/', $changed_file));
        if (!in_array($changed_file, $allowed_changed_files, true)) {
            $unexpected_changed_files[] = $changed_file;
        }
        if (
            $normalized === 'application/config/routes.php' ||
            preg_match('#^application/language/.*\.json$#', $normalized) ||
            preg_match('#(^|/)(paymob|payment|checkout|coupon|cart|shopping_cart)(/|_|\.|$)#', $normalized)
        ) {
            $disallowed_changed_files[] = $changed_file;
        }
    }

    if (empty($unexpected_changed_files)) {
        demo_fix1_pass($passes, 'Git dirty scope is limited to DEMO.FIX.1 cleanup files.');
    } else {
        demo_fix1_fail($failures, 'Unexpected changed files: ' . implode(', ', $unexpected_changed_files));
    }

    if (empty($disallowed_changed_files)) {
        demo_fix1_pass($passes, 'No route, language JSON, or payment/cart/checkout/coupon/Paymob files are changed.');
    } else {
        demo_fix1_fail($failures, 'Disallowed changed files found: ' . implode(', ', $disallowed_changed_files));
    }
}

$self_source = file_get_contents(__FILE__);
if (!preg_match('/\b(?:get_phrase|site_phrase)\s*\(/', $self_source)) {
    demo_fix1_pass($passes, 'Diagnostic source contains no direct legacy phrase helper calls.');
} else {
    demo_fix1_fail($failures, 'Diagnostic source must not call legacy phrase helpers.');
}

$mysqli = demo_fix1_db_connect($root, $warnings);
if ($mysqli) {
    $language_total = demo_fix1_table_exists($mysqli, 'language') ? demo_fix1_db_scalar($mysqli, 'SELECT COUNT(*) FROM language') : null;
    $session_total = demo_fix1_table_exists($mysqli, 'ci_sessions') ? demo_fix1_db_scalar($mysqli, 'SELECT COUNT(*) FROM ci_sessions') : null;
    $arabic_qmark_count = demo_fix1_table_exists($mysqli, 'language') ? demo_fix1_db_scalar($mysqli, "SELECT COUNT(*) FROM language WHERE arabic REGEXP '\\\\?{3,}'") : null;
    $arabic_empty_count = demo_fix1_table_exists($mysqli, 'language') ? demo_fix1_db_scalar($mysqli, "SELECT COUNT(*) FROM language WHERE arabic IS NULL OR TRIM(arabic) = ''") : null;
    $inserted_phrase = 'saved_courses_stay_here_so_you_can_compare_learning_paths_before_access_is_granted_or_checkout_becomes_available.';
    $inserted_phrase_count = null;
    if (demo_fix1_table_exists($mysqli, 'language')) {
        $safe_phrase = $mysqli->real_escape_string($inserted_phrase);
        $inserted_phrase_count = demo_fix1_db_scalar($mysqli, "SELECT COUNT(*) FROM language WHERE phrase = '" . $safe_phrase . "'");
    }

    demo_fix1_pass($passes, 'Read-only DB SELECT checks completed.');
    demo_fix1_say('INFO', 'language rows: ' . ($language_total === null ? 'n/a' : $language_total));
    demo_fix1_say('INFO', 'ci_sessions rows: ' . ($session_total === null ? 'n/a' : $session_total));
    demo_fix1_say('INFO', 'Arabic empty/null phrase values: ' . ($arabic_empty_count === null ? 'n/a' : $arabic_empty_count));

    if ($arabic_qmark_count !== null && (int) $arabic_qmark_count > 0) {
        demo_fix1_warn($warnings, 'Arabic phrase DB corruption is still present and reported only; repeated question-mark rows: ' . $arabic_qmark_count);
    } elseif ($arabic_qmark_count !== null) {
        demo_fix1_warn($warnings, 'Arabic repeated-question-mark rows were not found; this diagnostic did not repair phrase data.');
    }

    if ($inserted_phrase_count !== null && (int) $inserted_phrase_count > 0) {
        demo_fix1_warn($warnings, 'Previously inserted wishlist phrase row remains present and was not deleted: count ' . $inserted_phrase_count);
    } elseif ($inserted_phrase_count !== null) {
        demo_fix1_warn($warnings, 'Previously inserted wishlist phrase row is not present; this diagnostic did not delete or repair it.');
    }
} else {
    demo_fix1_warn($warnings, 'DB checks skipped; source checks still completed.');
}

demo_fix1_say('INFO', 'Passes: ' . count($passes) . '; warnings: ' . count($warnings) . '; failures: ' . count($failures));

if (!empty($failures)) {
    exit(1);
}

exit(0);
