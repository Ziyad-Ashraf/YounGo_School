<?php
/**
 * LANGUAGE.FRONTEND.COURSE_DETAIL.ACCESS_COPY.WIRE.1 diagnostic.
 *
 * Read-only checks for YounGo public course-detail static copy wiring. This
 * script does not write DB rows, import language packs, change course content,
 * alter access/enrolment/payment behavior, or create checkout/order records.
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

function yfcda1d_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function yfcda1d_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function yfcda1d_connect($db, $active_group)
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

function yfcda1d_read($path)
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

function yfcda1d_read_query($mysqli, $sql, $types = '', $params = array())
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

function yfcda1d_has_phrase_call($source, $key)
{
    $quoted_key = preg_quote($key, '/');
    return preg_match('/(?:youngo_course_detail_phrase|youngo_frontend_phrase(?:_e)?)\(\s*[\'"]' . $quoted_key . '[\'"]/m', $source) === 1;
}

function yfcda1d_has_arabic($value)
{
    return preg_match('/\p{Arabic}/u', (string) $value) === 1;
}

function yfcda1d_required_phrase_keys()
{
    return array(
        'course',
        'breadcrumb',
        'home',
        'courses',
        'details',
        'guided_learning_for_curious_kids',
        'created_by',
        'reviews',
        'enrolled',
        'updated',
        'starts',
        'course_actions',
        'preview_this_course',
        'course_access',
        'subscription_access',
        'free',
        'sign_in_to_track_access',
        'use_a_student_account_to_keep_course_access_and_progress_in_one_place.',
        'access_for_this_course_is_managed_by_your_school/admin.',
        'start_now',
        'enroll_now',
        'contact',
        'join_with_your_account_and_keep_course_access_in_one_place.',
        'lectures',
        'hours',
        'minutes',
        'expiry_period',
        'lifetime',
        'months',
        'certificate',
        'yes',
        'overview',
        'course_description',
        'learning_goals',
        'what_will_i_learn?',
        'before_class',
        'requirements',
        'curriculum',
        'lessons_inside_this_course',
        'lessons',
        'preview',
        'no_curriculum_sections_are_available_yet.',
        'instructor',
        'meet_your_guide',
        'course_guide',
        'trusted_guide',
        'structured_lessons',
        'view_profile',
        'follow',
        'unfollow',
        'family_and_learner_feedback',
        'questions',
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
        'beginner',
        'intermediate',
        'advanced',
        'english',
        'arabic',
        'close',
    );
}

function yfcda1d_payment_cta_hits($source)
{
    $hits = array();
    $patterns = array(
        'hardcoded_payment_or_cart_route' => '/(?:site_url|base_url)\(\s*[\'"][^\'"]*(?:paymob|payment|course_payment|shopping_cart|cart|apply_coupon|confirm_payment)[^\'"]*[\'"]/i',
        'visible_payment_cta' => '/>\s*(?:Buy Now|Add to cart|Checkout|Pay now|Pay with Paymob|Subscribe now)\s*</i',
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
        'course_page' => 'application/views/frontend/youngo/course_page.php',
        'course_reviews' => 'application/views/frontend/youngo/course_page_reviews.php',
        'frontend_language_helper' => 'application/helpers/youngo_frontend_language_helper.php',
        'frontend_content_helper' => 'application/helpers/youngo_frontend_content_helper.php',
        'translation_model' => 'application/models/Youngo_translation_model.php',
    );

    $sources = array();
    foreach ($files as $label => $path) {
        $sources[$label] = yfcda1d_read($path);
    }

    $coursePageKeys = array(
        'breadcrumb',
        'home',
        'courses',
        'details',
        'guided_learning_for_curious_kids',
        'created_by',
        'reviews',
        'enrolled',
        'updated',
        'starts',
        'course_actions',
        'preview_this_course',
        'course_access',
        'subscription_access',
        'free',
        'start_now',
        'enroll_now',
        'contact',
        'join_with_your_account_and_keep_course_access_in_one_place.',
        'lectures',
        'hours',
        'minutes',
        'expiry_period',
        'lifetime',
        'months',
        'overview',
        'course_description',
        'curriculum',
        'lessons_inside_this_course',
        'lessons',
        'preview',
        'instructor',
        'meet_your_guide',
        'view_profile',
        'follow',
        'unfollow',
        'family_and_learner_feedback',
        'questions',
        'course_confidence',
        'close',
    );

    $reviewKeys = array(
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
    );

    $viewPhraseCoverage = array();
    foreach ($coursePageKeys as $key) {
        $viewPhraseCoverage[$key] = yfcda1d_has_phrase_call($sources['course_page'], $key);
        yfcda1d_assert($failures, $viewPhraseCoverage[$key], 'Course page is missing URI-aware phrase wiring for key: ' . $key);
    }

    $reviewPhraseCoverage = array();
    foreach ($reviewKeys as $key) {
        $reviewPhraseCoverage[$key] = yfcda1d_has_phrase_call($sources['course_reviews'], $key);
        yfcda1d_assert($failures, $reviewPhraseCoverage[$key], 'Course reviews partial is missing URI-aware phrase wiring for key: ' . $key);
    }

    yfcda1d_assert($failures, strpos($sources['course_page'], 'function youngo_course_detail_phrase') !== false, 'Course detail Arabic-safe phrase wrapper was not detected.');
    yfcda1d_assert($failures, strpos($sources['course_reviews'], '$youngo_frontend_language') !== false, 'Course reviews partial does not derive/preserve frontend language.');
    yfcda1d_assert($failures, strpos($sources['course_page'], "youngo_frontend_translate_course_row") !== false, 'Course dynamic translation call was not detected.');
    yfcda1d_assert($failures, strpos($sources['course_page'], "youngo_frontend_translate_section_rows") !== false, 'Section dynamic translation call was not detected.');
    yfcda1d_assert($failures, strpos($sources['course_page'], "youngo_frontend_translate_lesson_rows") !== false, 'Lesson dynamic translation call was not detected.');
    yfcda1d_assert($failures, strpos($sources['frontend_content_helper'], 'Youngo_translation_model') !== false, 'Frontend content helper no longer references Youngo_translation_model.');

    $requiredRouteFragments = array(
        "site_url('home/course_preview/' . \$course_details['id'])",
        "site_url('home/lesson/' . slugify(\$youngo_course_player_title) . '/' . \$course_details['id'])",
        "site_url('home/get_enrolled_to_free_course/' . \$course_details['id'])",
        "site_url('home/play_lesson/' . \$lesson['id'])",
        "site_url('home/play_lesson/' . \$lesson['id'] . '/preview')",
        "site_url('home/rate_course')",
        "site_url('home/remove_rating/' . \$course_details['id'] . '/' . \$rating['id'])",
    );
    foreach ($requiredRouteFragments as $fragment) {
        $haystack = strpos($fragment, 'rate_course') !== false || strpos($fragment, 'remove_rating') !== false
            ? $sources['course_reviews']
            : $sources['course_page'];
        yfcda1d_assert($failures, strpos($haystack, $fragment) !== false, 'Expected course route/action fragment changed or was not detected: ' . $fragment);
    }

    foreach (array('course_page', 'course_reviews') as $label) {
        yfcda1d_assert($failures, strpos($sources[$label], 'arabic_translated') === false, $label . ' contains arabic_translated UI usage.');
    }

    $paymentHits = array(
        'course_page' => yfcda1d_payment_cta_hits($sources['course_page']),
        'course_reviews' => yfcda1d_payment_cta_hits($sources['course_reviews']),
    );
    foreach ($paymentHits as $label => $hits) {
        yfcda1d_assert($failures, empty($hits), $label . ' contains payment/checkout CTA pattern(s): ' . implode(', ', $hits));
    }

    $mysqli = yfcda1d_connect($db, $active_group);
    $columns = yfcda1d_read_query($mysqli, "SHOW COLUMNS FROM language WHERE Field IN ('phrase', 'english', 'arabic', 'arabic_translated')");
    $columnNames = array_column($columns, 'Field');
    yfcda1d_assert($failures, in_array('phrase', $columnNames, true), 'language.phrase column missing.');
    yfcda1d_assert($failures, in_array('english', $columnNames, true), 'language.english column missing.');
    yfcda1d_assert($failures, in_array('arabic', $columnNames, true), 'language.arabic column missing.');

    $phraseCoverage = array();
    foreach (yfcda1d_required_phrase_keys() as $key) {
        $rows = yfcda1d_read_query($mysqli, 'SELECT phrase, english, arabic FROM language WHERE phrase = ? LIMIT 1', 's', array($key));
        $row = isset($rows[0]) ? $rows[0] : null;
        $localEnglish = function_exists('youngo_frontend_local_phrase_value') ? youngo_frontend_local_phrase_value($key, 'english') : '';
        $localArabic = function_exists('youngo_frontend_local_phrase_value') ? youngo_frontend_local_phrase_value($key, 'arabic') : '';
        $englishValue = $row && trim((string) $row['english']) !== '' ? $row['english'] : $localEnglish;
        $dbArabicValue = $row && trim((string) $row['arabic']) !== '' ? $row['arabic'] : '';
        $arabicValue = $dbArabicValue !== '' && yfcda1d_has_arabic($dbArabicValue) ? $dbArabicValue : $localArabic;

        $phraseCoverage[$key] = array(
            'db_row' => (bool) $row,
            'english_available' => trim((string) $englishValue) !== '',
            'db_arabic_has_arabic_script' => yfcda1d_has_arabic($dbArabicValue),
            'arabic_display_available' => trim((string) $arabicValue) !== '',
            'arabic_display_has_arabic_script' => yfcda1d_has_arabic($arabicValue),
        );

        yfcda1d_assert($failures, $phraseCoverage[$key]['english_available'], 'English phrase value missing for key: ' . $key);
        yfcda1d_assert($failures, $phraseCoverage[$key]['arabic_display_available'], 'Arabic phrase display value missing for key: ' . $key);
        yfcda1d_assert($failures, $phraseCoverage[$key]['arabic_display_has_arabic_script'], 'Arabic display value does not contain Arabic script for key: ' . $key);
    }

    $badUiLanguageRows = yfcda1d_read_query($mysqli, "SELECT COUNT(*) AS row_count FROM language WHERE phrase = 'arabic_translated'");
    yfcda1d_assert($failures, (int) $badUiLanguageRows[0]['row_count'] === 0, 'language table contains arabic_translated as a phrase key.');

    $mysqli->close();

    yfcda1d_print('View Phrase Coverage', array(
        'course_page_keys_checked' => count($coursePageKeys),
        'review_keys_checked' => count($reviewKeys),
    ));
    yfcda1d_print('Phrase Value Coverage', array(
        'keys_checked' => count($phraseCoverage),
        'all_have_english' => count(array_filter($phraseCoverage, function ($row) { return !$row['english_available']; })) === 0,
        'all_have_arabic_display' => count(array_filter($phraseCoverage, function ($row) { return !$row['arabic_display_has_arabic_script']; })) === 0,
    ));
    yfcda1d_print('Dynamic Content Path', array(
        'course_translation_call' => true,
        'section_translation_call' => true,
        'lesson_translation_call' => true,
    ));
    yfcda1d_print('Route And CTA Safety', array(
        'route_fragments_checked' => count($requiredRouteFragments),
        'payment_cta_hits' => $paymentHits,
        'no_db_writes' => true,
    ));

    if (!empty($failures)) {
        yfcda1d_print('Failures', $failures);
        exit(1);
    }

    yfcda1d_print('Result', 'PASS');
    exit(0);
} catch (Throwable $exception) {
    yfcda1d_print('Diagnostic Error', array('error' => $exception->getMessage()));
    exit(1);
}
