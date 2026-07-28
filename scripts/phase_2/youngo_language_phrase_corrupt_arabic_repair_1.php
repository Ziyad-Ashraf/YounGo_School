<?php
/**
 * LANGUAGE.PHRASE.CORRUPT.ARABIC.REPAIR.1
 *
 * Repairs only allowlisted corrupt Arabic public frontend phrase values.
 * It updates language.arabic only, never writes arabic_translated, never
 * changes English values, and avoids checkout/payment/cart/coupon phrases.
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

function ylpcar1_repair_allowlist()
{
    return array(
        array('key' => '1_star_rating', 'english' => '1 star rating', 'arabic' => 'تقييم نجمة واحدة', 'area' => 'course detail reviews', 'reason' => 'question_mark_placeholder'),
        array('key' => '2_star_rating', 'english' => '2 star rating', 'arabic' => 'تقييم نجمتين', 'area' => 'course detail reviews', 'reason' => 'question_mark_placeholder'),
        array('key' => '3_star_rating', 'english' => '3 star rating', 'arabic' => 'تقييم ثلاث نجوم', 'area' => 'course detail reviews', 'reason' => 'question_mark_placeholder'),
        array('key' => '4_star_rating', 'english' => '4 star rating', 'arabic' => 'تقييم أربع نجوم', 'area' => 'course detail reviews', 'reason' => 'question_mark_placeholder'),
        array('key' => '5_star_rating', 'english' => '5 star rating', 'arabic' => 'تقييم خمس نجوم', 'area' => 'course detail reviews', 'reason' => 'question_mark_placeholder'),
        array('key' => 'access_for_this_course_is_managed_by_your_school/admin.', 'english' => 'Access for this course is managed by your school/admin.', 'arabic' => 'يتم إدارة الوصول إلى هذا الكورس من خلال المدرسة أو المسؤول.', 'area' => 'course detail access status', 'reason' => 'question_mark_placeholder'),
        array('key' => 'apply_filters', 'english' => 'Apply filters', 'arabic' => 'تطبيق الفلاتر', 'area' => 'course listing filters', 'reason' => 'question_mark_placeholder'),
        array('key' => 'blog', 'english' => 'Blog', 'arabic' => 'المدونة', 'area' => 'header footer blog', 'reason' => 'question_mark_placeholder'),
        array('key' => 'categories', 'english' => 'Categories', 'arabic' => 'التصنيفات', 'area' => 'course listing blog common', 'reason' => 'question_mark_placeholder'),
        array('key' => 'contact_us', 'english' => 'Contact us', 'arabic' => 'تواصل معنا', 'area' => 'contact footer common', 'reason' => 'question_mark_placeholder'),
        array('key' => 'course_access', 'english' => 'Course access', 'arabic' => 'الوصول إلى الكورس', 'area' => 'course detail access status', 'reason' => 'question_mark_placeholder'),
        array('key' => 'course_added_to_wishlist', 'english' => 'Course added to wishlist', 'arabic' => 'تمت إضافة الكورس إلى المفضلة', 'area' => 'wishlist toast', 'reason' => 'question_mark_placeholder'),
        array('key' => 'course_catalog', 'english' => 'Course catalog', 'arabic' => 'كتالوج الكورسات', 'area' => 'course listing sort', 'reason' => 'question_mark_placeholder'),
        array('key' => 'course_details', 'english' => 'Course details', 'arabic' => 'تفاصيل الكورس', 'area' => 'course card wishlist my courses', 'reason' => 'question_mark_placeholder'),
        array('key' => 'course_filters', 'english' => 'Course filters', 'arabic' => 'فلاتر الكورسات', 'area' => 'course listing filters', 'reason' => 'question_mark_placeholder'),
        array('key' => 'course_guide', 'english' => 'Course guide', 'arabic' => 'مرشد الكورس', 'area' => 'course detail instructor', 'reason' => 'question_mark_placeholder'),
        array('key' => 'course_layout', 'english' => 'Course layout', 'arabic' => 'طريقة عرض الكورسات', 'area' => 'course listing controls', 'reason' => 'question_mark_placeholder'),
        array('key' => 'course_removed_from_wishlist', 'english' => 'Course removed from wishlist', 'arabic' => 'تمت إزالة الكورس من المفضلة', 'area' => 'wishlist toast', 'reason' => 'question_mark_placeholder'),
        array('key' => 'course_results', 'english' => 'Course results', 'arabic' => 'نتائج الكورسات', 'area' => 'course listing summary', 'reason' => 'question_mark_placeholder'),
        array('key' => 'courses_you_saved', 'english' => 'Courses you saved', 'arabic' => 'الكورسات التي حفظتها', 'area' => 'wishlist', 'reason' => 'question_mark_placeholder'),
        array('key' => 'discounted', 'english' => 'Discounted', 'arabic' => 'عليها خصم', 'area' => 'course listing sort', 'reason' => 'question_mark_placeholder'),
        array('key' => 'edit', 'english' => 'Edit', 'arabic' => 'تعديل', 'area' => 'course detail reviews', 'reason' => 'question_mark_placeholder'),
        array('key' => 'email_address', 'english' => 'Email address', 'arabic' => 'البريد الإلكتروني', 'area' => 'auth', 'reason' => 'question_mark_placeholder'),
        array('key' => 'English', 'english' => 'English', 'arabic' => 'الإنجليزية', 'area' => 'course detail taxonomy', 'reason' => 'question_mark_placeholder'),
        array('key' => 'enroll_now', 'english' => 'Enroll now', 'arabic' => 'سجل الآن', 'area' => 'legacy-compatible public course CTA', 'reason' => 'question_mark_placeholder'),
        array('key' => 'explore_more_courses', 'english' => 'Explore more courses', 'arabic' => 'استكشف المزيد من الكورسات', 'area' => 'wishlist my courses', 'reason' => 'question_mark_placeholder'),
        array('key' => 'explore_youngo_courses', 'english' => 'Explore youngo courses', 'arabic' => 'استكشف كورسات YounGo', 'area' => 'course listing hero', 'reason' => 'question_mark_placeholder'),
        array('key' => 'filters', 'english' => 'Filters', 'arabic' => 'الفلاتر', 'area' => 'course listing filters', 'reason' => 'question_mark_placeholder'),
        array('key' => 'find_a_course', 'english' => 'Find a course', 'arabic' => 'ابحث عن كورس', 'area' => 'course listing filters', 'reason' => 'question_mark_placeholder'),
        array('key' => 'find_structured,_friendly_learning_paths_for_curious_kids_and_the_families_supporting_them.', 'english' => 'Find structured, friendly learning paths for curious kids and the families supporting them.', 'arabic' => 'اعثر على مسارات تعلم منظمة ولطيفة للأطفال الفضوليين وللأسر التي تدعمهم.', 'area' => 'course listing hero', 'reason' => 'question_mark_placeholder'),
        array('key' => 'frequently_asked_questions', 'english' => 'Frequently asked questions', 'arabic' => 'الأسئلة الشائعة', 'area' => 'course detail FAQ', 'reason' => 'question_mark_placeholder'),
        array('key' => 'highest_price', 'english' => 'Highest price', 'arabic' => 'الأعلى سعرا', 'area' => 'course listing sort', 'reason' => 'question_mark_placeholder'),
        array('key' => 'highest_rating', 'english' => 'Highest rating', 'arabic' => 'الأعلى تقييما', 'area' => 'course listing sort', 'reason' => 'question_mark_placeholder'),
        array('key' => 'hours', 'english' => 'Hours', 'arabic' => 'ساعات', 'area' => 'course detail duration', 'reason' => 'question_mark_placeholder'),
        array('key' => 'language', 'english' => 'Language', 'arabic' => 'اللغة', 'area' => 'course listing course detail', 'reason' => 'question_mark_placeholder'),
        array('key' => 'log_in_to_view_your_wishlist', 'english' => 'Log in to view your wishlist', 'arabic' => 'سجل الدخول لعرض قائمتك المفضلة', 'area' => 'wishlist auth prompt', 'reason' => 'question_mark_placeholder'),
        array('key' => 'login', 'english' => 'Login', 'arabic' => 'تسجيل الدخول', 'area' => 'auth header common', 'reason' => 'question_mark_placeholder'),
        array('key' => 'lowest_price', 'english' => 'Lowest price', 'arabic' => 'الأقل سعرا', 'area' => 'course listing sort', 'reason' => 'question_mark_placeholder'),
        array('key' => 'my_courses', 'english' => 'My courses', 'arabic' => 'كورساتي', 'area' => 'account navigation', 'reason' => 'question_mark_placeholder'),
        array('key' => 'my_wishlist', 'english' => 'My wishlist', 'arabic' => 'قائمتي المفضلة', 'area' => 'wishlist header navigation', 'reason' => 'question_mark_placeholder'),
        array('key' => 'newly_published', 'english' => 'Newly published', 'arabic' => 'الأحدث نشرا', 'area' => 'course listing sort', 'reason' => 'question_mark_placeholder'),
        array('key' => 'no_curriculum_sections_are_available_yet.', 'english' => 'No curriculum sections are available yet.', 'arabic' => 'لا توجد أقسام للمنهج متاحة بعد.', 'area' => 'course detail curriculum', 'reason' => 'question_mark_placeholder'),
        array('key' => 'no_saved_courses_yet', 'english' => 'No saved courses yet', 'arabic' => 'لا توجد كورسات محفوظة بعد', 'area' => 'wishlist empty state', 'reason' => 'question_mark_placeholder'),
        array('key' => 'rating', 'english' => 'Rating', 'arabic' => 'التقييم', 'area' => 'course detail reviews', 'reason' => 'question_mark_placeholder'),
        array('key' => 'ratings', 'english' => 'Ratings', 'arabic' => 'التقييمات', 'area' => 'course listing filters', 'reason' => 'question_mark_placeholder'),
        array('key' => 'remove', 'english' => 'Remove', 'arabic' => 'إزالة', 'area' => 'wishlist reviews', 'reason' => 'question_mark_placeholder'),
        array('key' => 'remove_from_wishlist', 'english' => 'Remove from wishlist', 'arabic' => 'إزالة من المفضلة', 'area' => 'wishlist', 'reason' => 'question_mark_placeholder'),
        array('key' => 'remove_review', 'english' => 'Remove review', 'arabic' => 'إزالة التقييم', 'area' => 'course detail reviews', 'reason' => 'question_mark_placeholder'),
        array('key' => 'reset', 'english' => 'Reset', 'arabic' => 'إعادة ضبط', 'area' => 'course listing filters', 'reason' => 'question_mark_placeholder'),
        array('key' => 'review', 'english' => 'Review', 'arabic' => 'التقييم', 'area' => 'course detail reviews', 'reason' => 'question_mark_placeholder'),
        array('key' => 'save_interesting_courses_while_browsing,_then_compare_options_before_enrolling.', 'english' => 'Save interesting courses while browsing, then compare options before enrolling.', 'arabic' => 'احفظ الكورسات التي تهمك أثناء التصفح، ثم قارن الخيارات قبل التسجيل.', 'area' => 'wishlist empty state', 'reason' => 'question_mark_placeholder'),
        array('key' => 'saved_courses', 'english' => 'Saved courses', 'arabic' => 'الكورسات المحفوظة', 'area' => 'wishlist', 'reason' => 'question_mark_placeholder'),
        array('key' => 'search_by_keyword', 'english' => 'Search by keyword', 'arabic' => 'البحث بكلمة مفتاحية', 'area' => 'course listing filters', 'reason' => 'question_mark_placeholder'),
        array('key' => 'search_courses', 'english' => 'Search courses', 'arabic' => 'ابحث في الكورسات', 'area' => 'course listing filters', 'reason' => 'question_mark_placeholder'),
        array('key' => 'share_on_facebook', 'english' => 'Share on facebook', 'arabic' => 'مشاركة على فيسبوك', 'area' => 'course detail sharing', 'reason' => 'question_mark_placeholder'),
        array('key' => 'share_on_linkedin', 'english' => 'Share on linkedin', 'arabic' => 'مشاركة على لينكدإن', 'area' => 'course detail sharing', 'reason' => 'question_mark_placeholder'),
        array('key' => 'share_on_twitter', 'english' => 'Share on twitter', 'arabic' => 'مشاركة على تويتر', 'area' => 'course detail sharing', 'reason' => 'question_mark_placeholder'),
        array('key' => 'share_on_whatsapp', 'english' => 'Share on whatsapp', 'arabic' => 'مشاركة عبر واتساب', 'area' => 'course detail sharing', 'reason' => 'question_mark_placeholder'),
        array('key' => 'sign_in_required', 'english' => 'Sign in required', 'arabic' => 'تسجيل الدخول مطلوب', 'area' => 'wishlist auth prompt', 'reason' => 'question_mark_placeholder'),
        array('key' => 'sign_in_to_track_access', 'english' => 'Sign in to track access', 'arabic' => 'سجل الدخول لمتابعة الوصول', 'area' => 'course detail access status', 'reason' => 'question_mark_placeholder'),
        array('key' => 'sort_by', 'english' => 'Sort by', 'arabic' => 'ترتيب حسب', 'area' => 'course listing sort', 'reason' => 'question_mark_placeholder'),
        array('key' => 'stars', 'english' => 'Stars', 'arabic' => 'نجوم', 'area' => 'course detail reviews', 'reason' => 'question_mark_placeholder'),
        array('key' => 'structured_lessons', 'english' => 'Structured lessons', 'arabic' => 'دروس منظمة', 'area' => 'course detail summary', 'reason' => 'question_mark_placeholder'),
        array('key' => 'submit', 'english' => 'Submit', 'arabic' => 'إرسال', 'area' => 'course detail reviews auth forms', 'reason' => 'question_mark_placeholder'),
        array('key' => 'trusted_guide', 'english' => 'Trusted guide', 'arabic' => 'مرشد موثوق', 'area' => 'course detail instructor', 'reason' => 'question_mark_placeholder'),
        array('key' => 'use_a_student_account_to_keep_course_access_and_progress_in_one_place.', 'english' => 'Use a student account to keep course access and progress in one place.', 'arabic' => 'استخدم حساب الطالب لحفظ الوصول والتقدم في مكان واحد.', 'area' => 'course detail access status', 'reason' => 'question_mark_placeholder'),
        array('key' => 'video_url_is_not_supported', 'english' => 'Video url is not supported', 'arabic' => 'رابط الفيديو غير مدعوم', 'area' => 'course preview modal', 'reason' => 'question_mark_placeholder'),
        array('key' => 'view_details', 'english' => 'View details', 'arabic' => 'عرض التفاصيل', 'area' => 'course cards listing', 'reason' => 'question_mark_placeholder'),
        array('key' => 'wishlist', 'english' => 'Wishlist', 'arabic' => 'المفضلة', 'area' => 'wishlist header navigation', 'reason' => 'question_mark_placeholder'),
        array('key' => 'wishlist_items_are_saved_to_your_learner_account_so_they_stay_available_across_visits.', 'english' => 'Wishlist items are saved to your learner account so they stay available across visits.', 'arabic' => 'يتم حفظ عناصر المفضلة في حساب المتعلم لتبقى متاحة في الزيارات القادمة.', 'area' => 'wishlist auth prompt', 'reason' => 'question_mark_placeholder'),
        array('key' => 'wishlist_summary', 'english' => 'Wishlist summary', 'arabic' => 'ملخص المفضلة', 'area' => 'wishlist', 'reason' => 'question_mark_placeholder'),
        array('key' => 'write_a_review', 'english' => 'Write a review', 'arabic' => 'اكتب تقييما', 'area' => 'course detail reviews', 'reason' => 'question_mark_placeholder'),
        array('key' => 'write_your_comment', 'english' => 'Write your comment', 'arabic' => 'اكتب تعليقك', 'area' => 'course detail reviews', 'reason' => 'question_mark_placeholder'),
        array('key' => 'yes', 'english' => 'Yes', 'arabic' => 'نعم', 'area' => 'course detail facts', 'reason' => 'question_mark_placeholder'),
    );
}

function ylpcar1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function ylpcar1_connect($db, $active_group)
{
    if (!isset($db[$active_group])) {
        throw new RuntimeException('Active database group was not found.');
    }

    $config = $db[$active_group];
    $mysqli = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
    if ($mysqli->connect_errno) {
        throw new RuntimeException('DB connection failed without exposing credentials.');
    }

    $mysqli->set_charset('utf8');
    return $mysqli;
}

function ylpcar1_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) AS table_count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return !empty($row['table_count']);
}

function ylpcar1_is_corrupt_arabic_value($value, $english)
{
    $value = (string) $value;
    $trimmed = trim($value);
    if ($trimmed === '') {
        return false;
    }

    if (preg_match('/\?{4,}/', $value)) {
        return true;
    }

    if (strpos($value, 'Ø') !== false || strpos($value, 'Ù') !== false) {
        return true;
    }

    if ($english !== '' && strcasecmp($trimmed, trim((string) $english)) === 0) {
        return true;
    }

    return false;
}

function ylpcar1_has_arabic_script($value)
{
    return preg_match('/\p{Arabic}/u', (string) $value) === 1;
}

function ylpcar1_manual_override_status($mysqli, $phrase_key)
{
    static $meta_exists = null;
    if ($meta_exists === null) {
        $meta_exists = ylpcar1_table_exists($mysqli, 'youngo_language_phrase_meta');
    }

    if (!$meta_exists) {
        return array('manual_override' => false, 'source' => null, 'manually_overridden_at' => null);
    }

    $stmt = $mysqli->prepare("SELECT source, manually_overridden_at FROM youngo_language_phrase_meta WHERE phrase_key = ? AND language_code = 'arabic' ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $phrase_key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return array('manual_override' => false, 'source' => null, 'manually_overridden_at' => null);
    }

    return array(
        'manual_override' => $row['source'] === 'manual_override' || !empty($row['manually_overridden_at']),
        'source' => $row['source'],
        'manually_overridden_at' => $row['manually_overridden_at'],
    );
}

function ylpcar1_find_phrase_rows($mysqli, $phrase_key)
{
    $stmt = $mysqli->prepare('SELECT phrase_id, phrase, english, arabic FROM language WHERE BINARY phrase = BINARY ? ORDER BY phrase_id ASC');
    $stmt->bind_param('s', $phrase_key);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();

    return $rows;
}

function ylpcar1_update_arabic($mysqli, $phrase_id, $old_value, $new_value)
{
    $stmt = $mysqli->prepare('UPDATE language SET arabic = ? WHERE phrase_id = ? AND BINARY arabic = BINARY ?');
    $phrase_id = (int) $phrase_id;
    $stmt->bind_param('sis', $new_value, $phrase_id, $old_value);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    return $affected > 0;
}

if (defined('YLPCAR1_LIBRARY_ONLY') && YLPCAR1_LIBRARY_ONLY) {
    return;
}

try {
    $mysqli = ylpcar1_connect($db, $active_group);
    $allowlist = ylpcar1_repair_allowlist();
    $blockedPattern = '/(?:paymob|payment|checkout|shopping_cart|cart|coupon|buy_now|add_to_cart|pay_now)/i';

    $counts = array(
        'allowlisted_keys' => count($allowlist),
        'rows_seen' => 0,
        'rows_repaired' => 0,
        'keys_repaired' => 0,
        'already_clean_rows' => 0,
        'missing_rows' => 0,
        'manual_overrides_preserved' => 0,
        'manual_overrides_repaired_as_documented_corrupt' => 0,
        'blocked_payment_checkout_keys' => 0,
        'english_values_changed' => 0,
        'writes_arabic_translated' => false,
        'errors' => 0,
    );
    $repairedKeys = array();
    $skippedKeys = array();
    $areaSummary = array();

    $mysqli->begin_transaction();

    foreach ($allowlist as $entry) {
        $key = $entry['key'];
        $areaSummary[$entry['area']] = isset($areaSummary[$entry['area']]) ? $areaSummary[$entry['area']] + 1 : 1;

        if (preg_match($blockedPattern, $key)) {
            $counts['blocked_payment_checkout_keys']++;
            $counts['errors']++;
            $skippedKeys[] = $key;
            continue;
        }

        if (!ylpcar1_has_arabic_script($entry['arabic'])) {
            $counts['errors']++;
            $skippedKeys[] = $key;
            continue;
        }

        $rows = ylpcar1_find_phrase_rows($mysqli, $key);
        if (empty($rows)) {
            $counts['missing_rows']++;
            $skippedKeys[] = $key;
            continue;
        }

        $manual = ylpcar1_manual_override_status($mysqli, $key);
        $keyChanged = false;
        foreach ($rows as $row) {
            $counts['rows_seen']++;
            if ((string) $row['english'] !== (string) $entry['english']) {
                $counts['errors']++;
                $skippedKeys[] = $key;
                continue;
            }

            $isCorrupt = ylpcar1_is_corrupt_arabic_value($row['arabic'], $row['english']);
            if (!$isCorrupt) {
                $counts['already_clean_rows']++;
                continue;
            }

            if (!empty($manual['manual_override'])) {
                $counts['manual_overrides_repaired_as_documented_corrupt']++;
            }

            if (ylpcar1_update_arabic($mysqli, (int) $row['phrase_id'], (string) $row['arabic'], $entry['arabic'])) {
                $counts['rows_repaired']++;
                $keyChanged = true;
            }
        }

        if ($keyChanged) {
            $repairedKeys[] = $key;
        }
    }

    $counts['keys_repaired'] = count(array_unique($repairedKeys));

    if ($counts['errors'] > 0 || $counts['writes_arabic_translated']) {
        $mysqli->rollback();
        ylpcar1_print('Repair summary', $counts);
        ylpcar1_print('Skipped keys', array_values(array_unique($skippedKeys)));
        ylpcar1_print('Result', 'FAIL: repair rolled back because a safety check failed.');
        exit(1);
    }

    $mysqli->commit();
    ksort($areaSummary);

    ylpcar1_print('Repair allowlist by public area', $areaSummary);
    ylpcar1_print('Repair summary', $counts);
    ylpcar1_print('Repaired keys', array_values(array_unique($repairedKeys)));
    ylpcar1_print('Result', 'PASS: corrupt Arabic public phrase repair completed idempotently.');
    exit(0);
} catch (Throwable $exception) {
    ylpcar1_print('Result', array('status' => 'failed', 'error' => $exception->getMessage()));
    exit(1);
}
