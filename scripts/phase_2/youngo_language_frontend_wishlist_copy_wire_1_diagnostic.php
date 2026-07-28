<?php
/**
 * LANGUAGE.FRONTEND.WISHLIST.COPY.WIRE.1 diagnostic.
 *
 * Read-only checks for YounGo public wishlist copy wiring. This script does not
 * write DB rows, import language packs, change routes, touch wishlist/user data,
 * call payment gateways, or create checkout/order/access records.
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
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once APPPATH . 'helpers' . DIRECTORY_SEPARATOR . 'youngo_frontend_language_helper.php';

$failures = array();

function yfwcqd1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function yfwcqd1_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function yfwcqd1_connect($db, $active_group)
{
    if (!isset($db[$active_group])) {
        throw new RuntimeException('Active database group was not found.');
    }

    $config = $db[$active_group];
    $mysqli = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
    if ($mysqli->connect_errno) {
        throw new RuntimeException('DB connection failed without exposing credentials.');
    }

    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

function yfwcqd1_read($path)
{
    if (!is_file($path)) {
        throw new RuntimeException('Required file missing: ' . $path);
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException('Unable to read required file: ' . $path);
    }

    return $contents;
}

function yfwcqd1_read_query($mysqli, $sql, $types = '', $params = array())
{
    if (preg_match('/^\s*(INSERT|UPDATE|DELETE|ALTER|DROP|CREATE|TRUNCATE|REPLACE|GRANT|REVOKE|LOAD|CALL|OPTIMIZE|ANALYZE)\b/i', $sql)) {
        throw new RuntimeException('Blocked non-read SQL in diagnostic.');
    }

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('DB read prepare failed without exposing credentials.');
    }

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : array();
    $stmt->close();

    return $rows;
}

function yfwcqd1_has_phrase_call($source, $key)
{
    return preg_match('/(?:youngo_frontend_phrase(?:_e)?|youngo_wishlist_phrase)\(\s*[\'"]' . preg_quote($key, '/') . '[\'"]/m', $source) === 1;
}

function yfwcqd1_has_arabic($value)
{
    return preg_match('/\p{Arabic}/u', (string) $value) === 1;
}

function yfwcqd1_required_phrase_keys()
{
    return array(
        'breadcrumb',
        'home',
        'my_wishlist',
        'saved_courses',
        'keep_favorite_youngo_courses_in_one_place_then_return_when_your_child_is_ready_to_start.',
        'wishlist_summary',
        'saved_courses_stay_here_so_you_can_compare_learning_paths_before_access_is_granted.',
        'sign_in_required',
        'log_in_to_view_your_wishlist',
        'wishlist_items_are_saved_to_your_learner_account_so_they_stay_available_across_visits.',
        'log_in',
        'browse_courses',
        'wishlist',
        'courses_you_saved',
        'explore_more_courses',
        'remove_from_wishlist',
        'course_added_to_wishlist',
        'course_removed_from_wishlist',
        'course',
        'free',
        'lessons',
        'course_details',
        'start_now',
        'enroll_now',
        'contact',
        'no_saved_courses_yet',
        "build_your_child's_shortlist",
        'save_interesting_courses_while_browsing,_then_compare_options_before_enrolling.',
    );
}

function yfwcqd1_payment_cta_hits($source)
{
    $hits = array();
    $patterns = array(
        'payment_or_checkout_link' => '/<(?:a|form)\b[^>]*(?:href|action)=["\'][^"\']*(?:paymob|payment|checkout|course_payment|shopping_cart|cart|apply_coupon|confirm_payment)[^"\']*["\']/i',
        'visible_payment_cta' => '/\b(?:Buy Now|Add to cart|Checkout|Pay now|Pay with Paymob|Subscribe now)\b/i',
    );

    foreach ($patterns as $label => $pattern) {
        if (preg_match($pattern, $source) === 1) {
            $hits[] = $label;
        }
    }

    return $hits;
}

try {
    $files = array(
        'home_controller' => 'application/controllers/Home.php',
        'wishlist_page' => 'application/views/frontend/youngo/my_wishlist.php',
        'wishlist_items_partial' => 'application/views/frontend/youngo/wishlist_items.php',
        'frontend_language_helper' => 'application/helpers/youngo_frontend_language_helper.php',
    );

    $sources = array();
    foreach ($files as $label => $path) {
        $sources[$label] = yfwcqd1_read($path);
    }

    $viewKeys = array(
        'home',
        'my_wishlist',
        'saved_courses',
        'keep_favorite_youngo_courses_in_one_place_then_return_when_your_child_is_ready_to_start.',
        'wishlist_summary',
        'saved_courses_stay_here_so_you_can_compare_learning_paths_before_access_is_granted.',
        'sign_in_required',
        'log_in_to_view_your_wishlist',
        'wishlist_items_are_saved_to_your_learner_account_so_they_stay_available_across_visits.',
        'log_in',
        'browse_courses',
        'wishlist',
        'courses_you_saved',
        'explore_more_courses',
        'remove_from_wishlist',
        'course',
        'free',
        'lessons',
        'course_details',
        'start_now',
        'enroll_now',
        'contact',
        'no_saved_courses_yet',
        "build_your_child's_shortlist",
        'save_interesting_courses_while_browsing,_then_compare_options_before_enrolling.',
    );

    $viewPhraseCoverage = array();
    foreach ($viewKeys as $key) {
        $viewPhraseCoverage[$key] = array(
            'my_wishlist' => yfwcqd1_has_phrase_call($sources['wishlist_page'], $key),
            'wishlist_items' => yfwcqd1_has_phrase_call($sources['wishlist_items_partial'], $key),
        );
    }

    yfwcqd1_assert($failures, strpos($sources['home_controller'], "youngo_frontend_public_phrase('course_added_to_wishlist'") !== false, 'Wishlist added toast is not using the URI-aware frontend phrase wrapper.');
    yfwcqd1_assert($failures, strpos($sources['home_controller'], "youngo_frontend_public_phrase('course_removed_from_wishlist'") !== false, 'Wishlist removed toast is not using the URI-aware frontend phrase wrapper.');
    yfwcqd1_assert($failures, strpos($sources['home_controller'], "youngo_frontend_public_arabic_safe_phrase('my_wishlist'") !== false, 'Wishlist page title is not using the Arabic-safe frontend phrase wrapper.');
    yfwcqd1_assert($failures, strpos($sources['home_controller'], 'function youngo_frontend_public_arabic_safe_phrase') !== false, 'Arabic-safe frontend phrase wrapper was not detected in Home controller.');
    yfwcqd1_assert($failures, strpos($sources['home_controller'], "get_phrase('Course added to wishlist')") === false, 'Legacy wishlist added get_phrase() copy is still present.');
    yfwcqd1_assert($failures, strpos($sources['home_controller'], "get_phrase('Course removed from wishlist')") === false, 'Legacy wishlist removed get_phrase() copy is still present.');
    yfwcqd1_assert($failures, strpos($sources['wishlist_page'], 'function youngo_wishlist_phrase') !== false && strpos($sources['wishlist_page'], 'youngo_frontend_phrase($phrase_key') !== false, 'Wishlist page wrapper is not delegating to the frontend phrase helper.');
    yfwcqd1_assert($failures, strpos($sources['wishlist_items_partial'], 'function youngo_wishlist_phrase') !== false && strpos($sources['wishlist_items_partial'], 'youngo_frontend_phrase($phrase_key') !== false, 'Wishlist partial wrapper is not delegating to the frontend phrase helper.');

    yfwcqd1_assert($failures, strpos($sources['wishlist_page'], 'site_url(\'home/toggleWishlistItems/\' . $course_id)') !== false, 'Wishlist page remove action URL changed or was not detected.');
    yfwcqd1_assert($failures, strpos($sources['wishlist_items_partial'], 'site_url(\'home/toggleWishlistItems/\' . $course_id)') !== false, 'Wishlist partial remove action URL changed or was not detected.');
    yfwcqd1_assert($failures, strpos($sources['wishlist_page'], 'site_url(\'home/get_enrolled_to_free_course/\' . $course_id)') !== false, 'Existing free-course wishlist enrol URL changed or was not detected.');
    yfwcqd1_assert($failures, strpos($sources['wishlist_items_partial'], 'site_url(\'home/get_enrolled_to_free_course/\' . $course_id)') !== false, 'Existing free-course wishlist enrol URL in partial changed or was not detected.');
    yfwcqd1_assert($failures, strpos($sources['wishlist_page'], 'youngo_frontend_translate_course_rows') !== false, 'Wishlist page no longer uses frontend course translation shaping.');
    yfwcqd1_assert($failures, strpos($sources['wishlist_items_partial'], 'youngo_frontend_translate_course_row') !== false, 'Wishlist partial no longer uses frontend course translation shaping.');

    $targetSource = $sources['wishlist_page'] . "\n" . $sources['wishlist_items_partial'];
    yfwcqd1_assert($failures, stripos($targetSource, 'arabic_translated') === false, 'arabic_translated appears in wishlist UI source.');

    $paymentHits = array();
    foreach (array('wishlist_page', 'wishlist_items_partial') as $label) {
        $hits = yfwcqd1_payment_cta_hits($sources[$label]);
        if (!empty($hits)) {
            $paymentHits[$label] = $hits;
        }
    }
    yfwcqd1_assert($failures, empty($paymentHits), 'Payment/checkout CTA or link found in wishlist views.');

    $mysqli = yfwcqd1_connect($db, $active_group);
    $columns = yfwcqd1_read_query($mysqli, "SHOW COLUMNS FROM language WHERE Field IN ('phrase', 'english', 'arabic', 'arabic_translated')");
    $columnNames = array_map(function ($row) {
        return $row['Field'];
    }, $columns);

    yfwcqd1_assert($failures, in_array('phrase', $columnNames, true), 'language.phrase column missing.');
    yfwcqd1_assert($failures, in_array('english', $columnNames, true), 'language.english column missing.');
    yfwcqd1_assert($failures, in_array('arabic', $columnNames, true), 'language.arabic column missing.');

    $phraseRows = array();
    $phraseCoverage = array();
    foreach (yfwcqd1_required_phrase_keys() as $key) {
        $rows = yfwcqd1_read_query($mysqli, 'SELECT phrase, english, arabic FROM language WHERE phrase = ? LIMIT 1', 's', array($key));
        $row = isset($rows[0]) ? $rows[0] : null;
        $phraseRows[$key] = $row;
        $localFallbackArabic = youngo_frontend_phrase($key, '', 'arabic');
        $arabicColumnClean = is_array($row) && youngo_frontend_phrase_value_is_clean($row['arabic']) && yfwcqd1_has_arabic($row['arabic']);
        $localFallbackClean = youngo_frontend_phrase_value_is_clean($localFallbackArabic) && yfwcqd1_has_arabic($localFallbackArabic);
        $phraseCoverage[$key] = array(
            'exists' => is_array($row),
            'english_present' => is_array($row) && youngo_frontend_phrase_value_is_clean($row['english']),
            'arabic_column_present' => is_array($row) && trim((string) $row['arabic']) !== '',
            'arabic_column_clean' => $arabicColumnClean,
            'local_fallback_arabic_clean' => $localFallbackClean,
            'clean_arabic_display_available' => $arabicColumnClean || $localFallbackClean,
        );

        yfwcqd1_assert($failures, $phraseCoverage[$key]['exists'], 'Missing wishlist phrase row: ' . $key);
        yfwcqd1_assert($failures, $phraseCoverage[$key]['english_present'], 'Missing or unclean english wishlist phrase value: ' . $key);
        yfwcqd1_assert($failures, $phraseCoverage[$key]['arabic_column_present'], 'Missing arabic wishlist phrase value: ' . $key);
        yfwcqd1_assert($failures, $phraseCoverage[$key]['clean_arabic_display_available'], 'Wishlist phrase has no clean Arabic DB value or local fallback: ' . $key);
    }

    $badUiLanguageRows = yfwcqd1_read_query($mysqli, "SELECT COUNT(*) AS row_count FROM language WHERE phrase = 'arabic_translated'");
    $arabicTranslatedPhraseRows = isset($badUiLanguageRows[0]['row_count']) ? (int) $badUiLanguageRows[0]['row_count'] : 0;
    yfwcqd1_assert($failures, $arabicTranslatedPhraseRows === 0, 'language table contains arabic_translated as a UI phrase key.');

    $mysqli->close();

    yfwcqd1_print('Wishlist Copy Diagnostic', array(
        'files_checked' => $files,
        'view_phrase_helper_coverage' => $viewPhraseCoverage,
        'phrase_table_columns' => $columnNames,
        'phrase_coverage' => $phraseCoverage,
        'action_urls_preserved' => true,
        'payment_cta_hits' => $paymentHits,
        'db_writes' => 0,
    ));

    if (!empty($failures)) {
        yfwcqd1_print('Failures', $failures);
        exit(1);
    }

    yfwcqd1_print('Result', 'PASS');
    exit(0);
} catch (Throwable $exception) {
    yfwcqd1_print('Diagnostic Error', array('error' => $exception->getMessage()));
    exit(1);
}
