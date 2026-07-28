<?php
/**
 * LANGUAGE.FRONTEND.COURSE_DETAIL.ACCESS_COPY.QA.1 diagnostic.
 *
 * Read-only checks for authenticated course-detail copy QA cleanup and public
 * rendered output. This script does not write DB rows, seed phrases, import
 * language packs, alter course/access/enrolment/session logic, or touch payment.
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

function yfcqa1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function yfcqa1_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function yfcqa1_read($path)
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

function yfcqa1_connect($db, $active_group)
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

function yfcqa1_read_query($mysqli, $sql, $types = '', $params = array())
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

function yfcqa1_http_get($url)
{
    $body = file_get_contents($url, false, stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'ignore_errors' => true,
            'timeout' => 20,
        ),
    )));

    $status = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('/^HTTP\/\S+\s+([0-9]+)/', $header, $matches)) {
                $status = (int) $matches[1];
            }
        }
    }

    return array('status' => $status, 'body' => $body === false ? '' : $body);
}

function yfcqa1_text($html)
{
    $text = preg_replace('/<script\b[^>]*>.*?<\/script>|<style\b[^>]*>.*?<\/style>/is', ' ', $html);
    $text = preg_replace('/<[^>]+>/s', ' ', $text);
    return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function yfcqa1_lang_dir($html)
{
    preg_match('/<html[^>]*lang="([^"]+)"/i', $html, $lang);
    preg_match('/<html[^>]*dir="([^"]+)"/i', $html, $dir);

    return array(
        'lang' => isset($lang[1]) ? $lang[1] : '',
        'dir' => isset($dir[1]) ? $dir[1] : '',
    );
}

function yfcqa1_has_arabic($value)
{
    return preg_match('/\p{Arabic}/u', (string) $value) === 1;
}

function yfcqa1_display_phrase($key, $language)
{
    $value = youngo_frontend_phrase($key, '', $language);
    if ($language !== 'arabic') {
        return $value;
    }

    $bad = !yfcqa1_has_arabic($value)
        || strpos($value, 'ترجمة مطلوبة') !== false
        || strpos($value, '????') !== false
        || stripos($value, 'translation required') !== false
        || stripos($value, 'translation needed') !== false;

    if (!$bad) {
        return $value;
    }

    $local = function_exists('youngo_frontend_local_phrase_value')
        ? youngo_frontend_local_phrase_value($key, 'arabic')
        : '';
    if ($local !== '' && yfcqa1_has_arabic($local) && strpos($local, '????') === false) {
        return $local;
    }

    $course_detail_duration_units = array(
        'hours' => 'ساعات',
        'minutes' => 'دقائق',
        'months' => 'أشهر',
    );

    return isset($course_detail_duration_units[$key]) ? $course_detail_duration_units[$key] : $value;
}

function yfcqa1_db_phrase_value($mysqli, $key, $language)
{
    $lookup = function_exists('youngo_frontend_phrase_normalize_key')
        ? youngo_frontend_phrase_normalize_key($key)
        : strtolower(preg_replace('/\s+/', '_', trim((string) $key)));
    $rows = yfcqa1_read_query($mysqli, 'SELECT english, arabic FROM language WHERE phrase = ? LIMIT 1', 's', array($lookup));
    if (empty($rows) || !isset($rows[0][$language])) {
        return '';
    }

    return trim((string) $rows[0][$language]);
}

function yfcqa1_term_hits($text, $terms, $case_insensitive = false)
{
    $hits = array();
    foreach ($terms as $term) {
        $found = $case_insensitive ? stripos($text, $term) !== false : strpos($text, $term) !== false;
        if ($found) {
            $hits[] = $term;
        }
    }

    return $hits;
}

try {
    $mysqli = yfcqa1_connect($db, $active_group);

    $sources = array(
        'course_page' => yfcqa1_read('application/views/frontend/youngo/course_page.php'),
        'course_reviews' => yfcqa1_read('application/views/frontend/youngo/course_page_reviews.php'),
        'home_controller' => yfcqa1_read('application/controllers/Home.php'),
        'frontend_language_helper' => yfcqa1_read('application/helpers/youngo_frontend_language_helper.php'),
    );

    $phrase_keys = array(
        'course_access',
        'subscription_access',
        'manual_grant_access',
        'access_locked',
        'admin_access',
        'start_now',
        'join_with_your_account_and_keep_course_access_in_one_place.',
        'lectures',
        'hours',
        'months',
        'expiry_period',
        'overview',
        'curriculum',
        'reviews',
        'no_reviews_yet.',
    );

    $phrase_summary = array();
    foreach ($phrase_keys as $key) {
        $english = yfcqa1_db_phrase_value($mysqli, $key, 'english');
        $arabic = yfcqa1_db_phrase_value($mysqli, $key, 'arabic');
        $english = $english !== '' ? $english : yfcqa1_display_phrase($key, 'english');
        $arabic = $arabic !== '' ? $arabic : yfcqa1_display_phrase($key, 'arabic');
        if (!yfcqa1_has_arabic($arabic) || strpos($arabic, '????') !== false || strpos($arabic, 'ترجمة مطلوبة') !== false) {
            $arabic = yfcqa1_display_phrase($key, 'arabic');
        }
        $phrase_summary[$key] = array(
            'english_non_empty' => trim($english) !== '',
            'arabic_non_empty' => trim($arabic) !== '',
            'arabic_has_arabic' => yfcqa1_has_arabic($arabic),
        );
        yfcqa1_assert($failures, trim($english) !== '', 'Missing English phrase value for ' . $key);
        yfcqa1_assert($failures, trim($arabic) !== '' && yfcqa1_has_arabic($arabic), 'Missing Arabic display phrase value for ' . $key);
    }

    $source_summary = array(
        'course_detail_wrapper_uses_frontend_phrase' => strpos($sources['course_page'], 'youngo_frontend_phrase($phrase_key') !== false,
        'duration_placeholder_fallback_present' => strpos($sources['course_page'], "strpos(\$value, '????')") !== false
            && strpos($sources['course_page'], '$course_detail_duration_units') !== false,
        'dynamic_content_translation_path_present' => strpos($sources['course_page'], 'youngo_frontend_translate_course_row') !== false
            && strpos($sources['course_page'], 'youngo_frontend_translate_section_rows') !== false
            && strpos($sources['course_page'], 'youngo_frontend_translate_lesson_rows') !== false,
        'arabic_translated_ui_usage' => preg_match('/arabic_translated/i', $sources['course_page'] . "\n" . $sources['course_reviews']) === 1,
        'route_strings_present' => array(
            'course_preview' => strpos($sources['course_page'], 'home/course_preview/') !== false,
            'lesson' => strpos($sources['course_page'], 'home/lesson/') !== false,
            'play_lesson' => strpos($sources['course_page'], 'home/play_lesson/') !== false,
            'rate_course' => strpos($sources['course_reviews'], 'home/rate_course') !== false,
            'free_enrol_boundary' => strpos($sources['home_controller'], 'get_enrolled_to_free_course') !== false,
        ),
    );

    yfcqa1_assert($failures, $source_summary['course_detail_wrapper_uses_frontend_phrase'], 'Course detail phrase wrapper does not call frontend phrase helper.');
    yfcqa1_assert($failures, $source_summary['duration_placeholder_fallback_present'], 'Course detail duration placeholder fallback was not detected.');
    yfcqa1_assert($failures, $source_summary['dynamic_content_translation_path_present'], 'Course dynamic translation path was not detected.');
    yfcqa1_assert($failures, !$source_summary['arabic_translated_ui_usage'], 'arabic_translated appears in course-detail UI files.');
    foreach ($source_summary['route_strings_present'] as $label => $present) {
        yfcqa1_assert($failures, $present, 'Expected route string missing: ' . $label);
    }

    $payment_terms = array('Paymob', 'Buy Now', 'Add to cart', 'Checkout', 'Pay now', 'Pay with Paymob', 'Subscribe now');
    $source_payment_hits = array();
    if (preg_match_all('/>\s*(Paymob|Buy Now|Add to cart|Checkout|Pay now|Pay with Paymob|Subscribe now)\s*</i', $sources['course_page'] . "\n" . $sources['course_reviews'], $payment_matches)) {
        $source_payment_hits = array_values(array_unique($payment_matches[1]));
    }
    yfcqa1_assert($failures, empty($source_payment_hits), 'Visible payment/checkout CTA text detected in course-detail source.');

    $rendered = array();
    $pages = array(
        'arabic_default_course_9' => array('http://localhost/home/course/robotics-and-ai-explorers/9', 'ar', 'rtl'),
        'english_course_9' => array('http://localhost/en/home/course/robotics-and-ai-explorers/9', 'en', 'ltr'),
    );
    $arabic_english_terms = array('COURSE ACCESS', 'Course access', 'Start now', 'Join with your account', 'Lectures', ' Hours', 'Months', 'Expiry period', 'Overview', 'Curriculum', 'Reviews', 'No reviews yet', 'beginner');
    foreach ($pages as $label => $page) {
        $response = yfcqa1_http_get($page[0]);
        $html = $response['body'];
        $text = yfcqa1_text($html);
        $lang_dir = yfcqa1_lang_dir($html);
        $payment_hits = yfcqa1_term_hits($text, $payment_terms, true);
        $english_hits = $page[1] === 'ar' ? yfcqa1_term_hits($text, $arabic_english_terms) : array();
        $rendered[$label] = array(
            'status' => $response['status'],
            'lang' => $lang_dir['lang'],
            'dir' => $lang_dir['dir'],
            'english_hits_on_arabic_page' => $english_hits,
            'payment_hits' => $payment_hits,
        );
        yfcqa1_assert($failures, $response['status'] === 200, $label . ' did not return HTTP 200.');
        yfcqa1_assert($failures, $lang_dir['lang'] === $page[1] && $lang_dir['dir'] === $page[2], $label . ' lang/dir mismatch.');
        yfcqa1_assert($failures, empty($english_hits), $label . ' contains targeted English terms.');
        yfcqa1_assert($failures, empty($payment_hits), $label . ' contains payment/checkout CTA terms.');
    }

    $cleanup_counts = array(
        'temporary_manual_grants' => (int) yfcqa1_read_query($mysqli, "SELECT COUNT(*) AS c FROM youngo_manual_grants WHERE note LIKE '%LANGUAGE_FRONTEND_COURSE_DETAIL_ACCESS_COPY_QA_1_%'")[0]['c'],
        'temporary_sessions' => (int) yfcqa1_read_query($mysqli, "SELECT COUNT(*) AS c FROM ci_sessions WHERE data LIKE '%LANGUAGE_FRONTEND_COURSE_DETAIL_ACCESS_COPY_QA_1_%'")[0]['c'],
        'course_access_rows' => (int) yfcqa1_read_query($mysqli, 'SELECT COUNT(*) AS c FROM youngo_course_access')[0]['c'],
        'subscription_rows' => (int) yfcqa1_read_query($mysqli, 'SELECT COUNT(*) AS c FROM youngo_user_subscriptions')[0]['c'],
        'manual_grant_rows' => (int) yfcqa1_read_query($mysqli, 'SELECT COUNT(*) AS c FROM youngo_manual_grants')[0]['c'],
        'payment_rows' => (int) yfcqa1_read_query($mysqli, 'SELECT COUNT(*) AS c FROM payment')[0]['c'],
        'checkout_order_rows' => (int) yfcqa1_read_query($mysqli, 'SELECT COUNT(*) AS c FROM youngo_checkout_orders')[0]['c'],
        'coupon_usage_rows' => (int) yfcqa1_read_query($mysqli, 'SELECT COUNT(*) AS c FROM youngo_coupon_usages')[0]['c'],
    );

    yfcqa1_assert($failures, $cleanup_counts['temporary_manual_grants'] === 0, 'Temporary manual grant rows remain.');
    yfcqa1_assert($failures, $cleanup_counts['temporary_sessions'] === 0, 'Temporary QA session rows remain.');
    yfcqa1_assert($failures, $cleanup_counts['course_access_rows'] === 0, 'YounGo course access rows are not at baseline 0.');
    yfcqa1_assert($failures, $cleanup_counts['subscription_rows'] === 0, 'YounGo subscription rows are not at baseline 0.');
    yfcqa1_assert($failures, $cleanup_counts['manual_grant_rows'] === 0, 'YounGo manual grant rows are not at baseline 0.');
    yfcqa1_assert($failures, $cleanup_counts['payment_rows'] === 0, 'Payment rows are not at baseline 0.');
    yfcqa1_assert($failures, $cleanup_counts['checkout_order_rows'] === 0, 'Checkout order rows are not at baseline 0.');
    yfcqa1_assert($failures, $cleanup_counts['coupon_usage_rows'] === 0, 'Coupon usage rows are not at baseline 0.');

    yfcqa1_print('Phrase Resolution', $phrase_summary);
    yfcqa1_print('Source Checks', $source_summary);
    yfcqa1_print('Rendered Public Checks', $rendered);
    yfcqa1_print('Cleanup Counts', $cleanup_counts);
    yfcqa1_print('DB Writes', 'none');
    yfcqa1_print('Payment/Paymob Changes', 'none');
    yfcqa1_print('Failures', $failures);

    exit(empty($failures) ? 0 : 1);
} catch (Throwable $exception) {
    yfcqa1_print('Fatal Error', $exception->getMessage());
    exit(1);
}
