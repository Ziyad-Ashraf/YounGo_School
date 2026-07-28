<?php
/**
 * LANGUAGE.FRONTEND.COURSE_DETAIL.ACCESS_COPY.WIRE.1
 *
 * Idempotently seeds public course-detail display phrase keys. It inserts
 * missing rows and fills blank english/arabic values only. It preserves existing
 * non-empty values and manual overrides, writes only english/arabic, and never
 * writes arabic_translated.
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

function yfcda1_seed_inventory()
{
    return array(
        array('key' => 'course', 'english' => 'Course', 'arabic' => 'كورس'),
        array('key' => 'breadcrumb', 'english' => 'Breadcrumb', 'arabic' => 'مسار الصفحة'),
        array('key' => 'home', 'english' => 'Home', 'arabic' => 'الرئيسية'),
        array('key' => 'courses', 'english' => 'Courses', 'arabic' => 'الكورسات'),
        array('key' => 'details', 'english' => 'Details', 'arabic' => 'التفاصيل'),
        array('key' => 'guided_learning_for_curious_kids', 'english' => 'Guided learning for curious kids', 'arabic' => 'تعلم موجه للأطفال الفضوليين'),
        array('key' => 'created_by', 'english' => 'Created by', 'arabic' => 'إعداد'),
        array('key' => 'reviews', 'english' => 'Reviews', 'arabic' => 'التقييمات'),
        array('key' => 'enrolled', 'english' => 'Enrolled', 'arabic' => 'مشترك'),
        array('key' => 'updated', 'english' => 'Updated', 'arabic' => 'آخر تحديث'),
        array('key' => 'starts', 'english' => 'Starts', 'arabic' => 'يبدأ'),
        array('key' => 'course_actions', 'english' => 'Course actions', 'arabic' => 'إجراءات الكورس'),
        array('key' => 'preview_this_course', 'english' => 'Preview this course', 'arabic' => 'معاينة هذا الكورس'),
        array('key' => 'course_access', 'english' => 'Course access', 'arabic' => 'الوصول للكورس'),
        array('key' => 'subscription_access', 'english' => 'Subscription access', 'arabic' => 'وصول بالاشتراك'),
        array('key' => 'free', 'english' => 'Free', 'arabic' => 'مجاني'),
        array('key' => 'sign_in_to_track_access', 'english' => 'Sign in to track access', 'arabic' => 'سجل الدخول لمتابعة الوصول'),
        array('key' => 'use_a_student_account_to_keep_course_access_and_progress_in_one_place.', 'english' => 'Use a student account to keep course access and progress in one place.', 'arabic' => 'استخدم حساب الطالب لحفظ الوصول والتقدم في مكان واحد.'),
        array('key' => 'access_for_this_course_is_managed_by_your_school/admin.', 'english' => 'Access for this course is managed by your school/admin.', 'arabic' => 'الوصول لهذا الكورس يتم من خلال إدارة المدرسة.'),
        array('key' => 'start_now', 'english' => 'Start now', 'arabic' => 'ابدأ الآن'),
        array('key' => 'enroll_now', 'english' => 'Enroll now', 'arabic' => 'سجل الآن'),
        array('key' => 'contact', 'english' => 'Contact', 'arabic' => 'تواصل معنا'),
        array('key' => 'join_with_your_account_and_keep_course_access_in_one_place.', 'english' => 'Join with your account and keep course access in one place.', 'arabic' => 'انضم بحسابك واحتفظ بصلاحية الوصول للكورس في مكان واحد.'),
        array('key' => 'lectures', 'english' => 'Lectures', 'arabic' => 'محاضرات'),
        array('key' => 'hours', 'english' => 'Hours', 'arabic' => 'ساعات'),
        array('key' => 'minutes', 'english' => 'Minutes', 'arabic' => 'دقائق'),
        array('key' => 'expiry_period', 'english' => 'Expiry period', 'arabic' => 'مدة الوصول'),
        array('key' => 'lifetime', 'english' => 'Lifetime', 'arabic' => 'مدى الحياة'),
        array('key' => 'months', 'english' => 'Months', 'arabic' => 'أشهر'),
        array('key' => 'certificate', 'english' => 'Certificate', 'arabic' => 'الشهادة'),
        array('key' => 'yes', 'english' => 'Yes', 'arabic' => 'نعم'),
        array('key' => 'overview', 'english' => 'Overview', 'arabic' => 'نظرة عامة'),
        array('key' => 'course_description', 'english' => 'Course description', 'arabic' => 'وصف الكورس'),
        array('key' => 'learning_goals', 'english' => 'Learning goals', 'arabic' => 'أهداف التعلم'),
        array('key' => 'what_will_i_learn?', 'english' => 'What will I learn?', 'arabic' => 'ماذا سيتعلم طفلي؟'),
        array('key' => 'before_class', 'english' => 'Before class', 'arabic' => 'قبل الحصة'),
        array('key' => 'requirements', 'english' => 'Requirements', 'arabic' => 'المتطلبات'),
        array('key' => 'curriculum', 'english' => 'Curriculum', 'arabic' => 'المنهج'),
        array('key' => 'lessons_inside_this_course', 'english' => 'Lessons inside this course', 'arabic' => 'الدروس داخل هذا الكورس'),
        array('key' => 'lessons', 'english' => 'Lessons', 'arabic' => 'دروس'),
        array('key' => 'preview', 'english' => 'Preview', 'arabic' => 'معاينة'),
        array('key' => 'no_curriculum_sections_are_available_yet.', 'english' => 'No curriculum sections are available yet.', 'arabic' => 'لا توجد أقسام للمنهج متاحة بعد.'),
        array('key' => 'instructor', 'english' => 'Instructor', 'arabic' => 'المدرب'),
        array('key' => 'meet_your_guide', 'english' => 'Meet your guide', 'arabic' => 'تعرف على المرشد'),
        array('key' => 'course_guide', 'english' => 'Course guide', 'arabic' => 'مرشد الكورس'),
        array('key' => 'trusted_guide', 'english' => 'Trusted guide', 'arabic' => 'مرشد موثوق'),
        array('key' => 'structured_lessons', 'english' => 'Structured lessons', 'arabic' => 'دروس منظمة'),
        array('key' => 'view_profile', 'english' => 'View profile', 'arabic' => 'عرض الملف الشخصي'),
        array('key' => 'follow', 'english' => 'Follow', 'arabic' => 'متابعة'),
        array('key' => 'unfollow', 'english' => 'Unfollow', 'arabic' => 'إلغاء المتابعة'),
        array('key' => 'family_and_learner_feedback', 'english' => 'Family and learner feedback', 'arabic' => 'آراء الأسر والمتعلمين'),
        array('key' => 'questions', 'english' => 'Questions', 'arabic' => 'الأسئلة'),
        array('key' => 'frequently_asked_questions', 'english' => 'Frequently asked questions', 'arabic' => 'الأسئلة الشائعة'),
        array('key' => 'more_details', 'english' => 'More details', 'arabic' => 'تفاصيل إضافية'),
        array('key' => 'additional_information', 'english' => 'Additional information', 'arabic' => 'معلومات إضافية'),
        array('key' => 'watch_video', 'english' => 'Watch video', 'arabic' => 'مشاهدة الفيديو'),
        array('key' => 'course_confidence', 'english' => 'Course confidence', 'arabic' => 'ثقة في الكورس'),
        array('key' => 'a_structured_learning_path_with_clear_lessons,_instructor_guidance,_and_progress-friendly_activities.', 'english' => 'A structured learning path with clear lessons, instructor guidance, and progress-friendly activities.', 'arabic' => 'مسار تعلم منظم بدروس واضحة وإرشاد من المدرب وأنشطة تساعد على متابعة التقدم.'),
        array('key' => 'keep_exploring', 'english' => 'Keep exploring', 'arabic' => 'تابع الاستكشاف'),
        array('key' => 'related_courses', 'english' => 'Related courses', 'arabic' => 'كورسات ذات صلة'),
        array('key' => 'write_a_review', 'english' => 'Write a review', 'arabic' => 'اكتب تقييما'),
        array('key' => 'rating', 'english' => 'Rating', 'arabic' => 'التقييم'),
        array('key' => '1_star_rating', 'english' => '1 star rating', 'arabic' => 'تقييم نجمة واحدة'),
        array('key' => '2_star_rating', 'english' => '2 star rating', 'arabic' => 'تقييم نجمتين'),
        array('key' => '3_star_rating', 'english' => '3 star rating', 'arabic' => 'تقييم ثلاث نجوم'),
        array('key' => '4_star_rating', 'english' => '4 star rating', 'arabic' => 'تقييم أربع نجوم'),
        array('key' => '5_star_rating', 'english' => '5 star rating', 'arabic' => 'تقييم خمس نجوم'),
        array('key' => 'review', 'english' => 'Review', 'arabic' => 'التقييم'),
        array('key' => 'write_your_comment', 'english' => 'Write your comment', 'arabic' => 'اكتب تعليقك'),
        array('key' => 'submit', 'english' => 'Submit', 'arabic' => 'إرسال'),
        array('key' => 'no_reviews_yet.', 'english' => 'No reviews yet.', 'arabic' => 'لا توجد تقييمات بعد.'),
        array('key' => 'stars', 'english' => 'stars', 'arabic' => 'نجوم'),
        array('key' => 'edit', 'english' => 'Edit', 'arabic' => 'تعديل'),
        array('key' => 'remove_review', 'english' => 'Remove review', 'arabic' => 'إزالة التقييم'),
        array('key' => 'remove', 'english' => 'Remove', 'arabic' => 'إزالة'),
        array('key' => 'beginner', 'english' => 'Beginner', 'arabic' => 'مبتدئ'),
        array('key' => 'intermediate', 'english' => 'Intermediate', 'arabic' => 'متوسط'),
        array('key' => 'advanced', 'english' => 'Advanced', 'arabic' => 'متقدم'),
        array('key' => 'english', 'english' => 'English', 'arabic' => 'الإنجليزية'),
        array('key' => 'arabic', 'english' => 'Arabic', 'arabic' => 'العربية'),
        array('key' => 'close', 'english' => 'Close', 'arabic' => 'إغلاق'),
    );
}

function yfcda1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function yfcda1_connect($db, $active_group)
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

function yfcda1_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) AS table_count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return !empty($row['table_count']);
}

function yfcda1_has_manual_override($mysqli, $phrase_key, $language_code)
{
    static $meta_exists = null;
    if ($meta_exists === null) {
        $meta_exists = yfcda1_table_exists($mysqli, 'youngo_language_phrase_meta');
    }

    if (!$meta_exists) {
        return false;
    }

    $stmt = $mysqli->prepare("SELECT id FROM youngo_language_phrase_meta WHERE phrase_key = ? AND language_code = ? AND (source = 'manual_override' OR manually_overridden_at IS NOT NULL) LIMIT 1");
    $stmt->bind_param('ss', $phrase_key, $language_code);
    $stmt->execute();
    $has = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $has;
}

function yfcda1_find_phrase($mysqli, $phrase_key)
{
    $stmt = $mysqli->prepare('SELECT phrase_id, phrase, english, arabic FROM language WHERE phrase = ? LIMIT 1');
    $stmt->bind_param('s', $phrase_key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function yfcda1_insert_phrase($mysqli, $phrase_key, $english, $arabic)
{
    $stmt = $mysqli->prepare('INSERT INTO language (phrase, english, arabic) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $phrase_key, $english, $arabic);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function yfcda1_update_blank_column($mysqli, $phrase_id, $column, $value)
{
    if (!in_array($column, array('english', 'arabic'), true)) {
        return false;
    }

    $sql = 'UPDATE language SET `' . $column . '` = ? WHERE phrase_id = ? AND (`' . $column . '` IS NULL OR TRIM(`' . $column . '`) = \'\')';
    $stmt = $mysqli->prepare($sql);
    $phrase_id = (int) $phrase_id;
    $stmt->bind_param('si', $value, $phrase_id);
    $stmt->execute();
    $changed = $stmt->affected_rows > 0;
    $stmt->close();

    return $changed;
}

try {
    $mysqli = yfcda1_connect($db, $active_group);
    $counts = array(
        'keys_considered' => 82,
        'inserted_rows' => 0,
        'blank_english_values_filled' => 0,
        'blank_arabic_values_filled' => 0,
        'existing_non_empty_english_preserved' => 0,
        'existing_non_empty_arabic_preserved' => 0,
        'manual_overrides_preserved' => 0,
        'writes_arabic_translated' => false,
        'errors' => 0,
    );

    $inventory = yfcda1_seed_inventory();
    $counts['keys_considered'] = count($inventory);

    foreach ($inventory as $phrase) {
        $phrase_key = $phrase['key'];
        if ($phrase_key === 'arabic_translated') {
            $counts['writes_arabic_translated'] = true;
            $counts['errors']++;
            continue;
        }

        $row = yfcda1_find_phrase($mysqli, $phrase_key);
        if (!$row) {
            if (yfcda1_insert_phrase($mysqli, $phrase_key, $phrase['english'], $phrase['arabic'])) {
                $counts['inserted_rows']++;
            } else {
                $counts['errors']++;
            }
            continue;
        }

        foreach (array('english', 'arabic') as $column) {
            if (yfcda1_has_manual_override($mysqli, $phrase_key, $column)) {
                $counts['manual_overrides_preserved']++;
                continue;
            }

            if (trim((string) $row[$column]) === '') {
                if (yfcda1_update_blank_column($mysqli, (int) $row['phrase_id'], $column, $phrase[$column])) {
                    $counts['blank_' . $column . '_values_filled']++;
                }
            } else {
                $counts['existing_non_empty_' . $column . '_preserved']++;
            }
        }
    }

    $mysqli->close();
    yfcda1_print('Course Detail Phrase Seed Result', $counts);

    if ($counts['errors'] > 0 || $counts['writes_arabic_translated']) {
        exit(1);
    }

    exit(0);
} catch (Throwable $exception) {
    yfcda1_print('Course Detail Phrase Seed Error', array('error' => $exception->getMessage()));
    exit(1);
}
