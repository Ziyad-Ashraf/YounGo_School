<?php
/**
 * Phase 2U.4 Arabic UI phrase seed.
 *
 * Creates a clean canonical Arabic phrase set from current phrase keys and
 * English phrase values. This script is idempotent by default: it only fills
 * missing/empty Arabic values unless --force is explicitly provided.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$root = dirname(dirname(__DIR__));
define('ENVIRONMENT', 'development');
define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'config/database.php';

$force = in_array('--force', $argv, true);
$write_file = !in_array('--no-file', $argv, true);

$config = $db['default'];
$mysqli = @new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
if ($mysqli->connect_errno) {
    echo "Database connection failed without exposing credentials.\n";
    exit(2);
}
$mysqli->set_charset('utf8');

function u4_seed_section($title, $payload)
{
    echo "\n== {$title} ==\n";
    echo is_string($payload) ? $payload . "\n" : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}

function u4_seed_column_exists($mysqli, $table, $column)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }

    $result = $mysqli->query("SHOW COLUMNS FROM `{$table}` LIKE '" . $mysqli->real_escape_string($column) . "'");
    return $result && $result->num_rows > 0;
}

function u4_seed_extract_placeholders($value)
{
    preg_match_all('/(%(?:\\d+\\$)?[bcdeEfFgGosuxX]|\\{\\{[^}]+\\}\\}|\\{[A-Za-z0-9_]+\\}|:[A-Za-z_][A-Za-z0-9_]*|\\$[A-Za-z_][A-Za-z0-9_]*)/', (string) $value, $matches);
    $items = $matches[0];
    sort($items);
    return $items;
}

function u4_seed_has_mojibake($value)
{
    return preg_match('/(Ø|Ù|Ã|�)/u', (string) $value) === 1;
}

function u4_seed_normalize($value)
{
    $value = strtolower(trim((string) $value));
    $value = str_replace(array('-', '–', '—'), '_', $value);
    $value = preg_replace('/\\s+/', '_', $value);
    return $value;
}

$translations = array(
    'english' => 'العربية',
    'home' => 'الرئيسية',
    'courses' => 'الدورات',
    'all_courses' => 'كل الدورات',
    'course' => 'الدورة',
    'course_catalog' => 'كتالوج الدورات',
    'course_details' => 'تفاصيل الدورة',
    'course_access' => 'وصول الدورة',
    'active_course_access' => 'وصول نشط للدورة',
    'course_available' => 'الدورة متاحة',
    'browse_courses' => 'تصفح الدورات',
    'browse_included_courses' => 'تصفح الدورات المشمولة',
    'back_to_course_list' => 'العودة إلى قائمة الدورات',
    'blog' => 'المدونة',
    'contact' => 'تواصل معنا',
    'search' => 'بحث',
    'login' => 'تسجيل الدخول',
    'logout' => 'تسجيل الخروج',
    'sign_up' => 'إنشاء حساب',
    'sign_in_required' => 'تسجيل الدخول مطلوب',
    'sign_in_to_track_access' => 'سجّل الدخول لمتابعة الوصول',
    'please_enter_your_details_to_sign_in.' => 'يرجى إدخال بياناتك لتسجيل الدخول.',
    'invalid_login_credentials' => 'بيانات تسجيل الدخول غير صحيحة',
    'profile' => 'الملف الشخصي',
    'my_courses' => 'دوراتي',
    'my_access' => 'وصولي',
    'view_my_access' => 'عرض وصولي',
    'learner_access' => 'وصول المتعلم',
    'learning_access_for' => 'وصول التعلم لـ',
    'access_summary' => 'ملخص الوصول',
    'access_counts' => 'أعداد الوصول',
    'access_denied' => 'تم رفض الوصول',
    'access_managed_by_school' => 'الوصول مُدار من المدرسة',
    'access_for_this_course_is_managed_by_your_school/admin.' => 'الوصول إلى هذه الدورة مُدار من المدرسة أو المسؤول.',
    'active_access' => 'وصول نشط',
    'no_active_course_access' => 'لا يوجد وصول نشط للدورات',
    'no_active_subscription_access' => 'لا يوجد وصول اشتراك نشط',
    'courses_will_appear_after_access_is_granted' => 'ستظهر الدورات بعد منح الوصول',
    'lifetime_access' => 'وصول مدى الحياة',
    'school_grants' => 'منح المدرسة',
    'manual' => 'يدوي',
    'enrolled' => 'مسجل',
    'enrolled_courses' => 'الدورات المسجل بها',
    'no_enrolled_courses_yet' => 'لا توجد دورات مسجل بها بعد',
    'enroll_now' => 'سجّل الآن',
    'start_now' => 'ابدأ الآن',
    'start_learning' => 'ابدأ التعلم',
    'continue' => 'متابعة',
    'continue_learning' => 'متابعة التعلم',
    'get_started' => 'ابدأ الآن',
    'join_now' => 'انضم الآن',
    'add_to_cart' => 'أضف إلى السلة',
    'remove_from_cart' => 'إزالة من السلة',
    'cart' => 'السلة',
    'shopping_cart' => 'سلة التسوق',
    'cart_items' => 'عناصر السلة',
    'cart_summary' => 'ملخص السلة',
    'empty_cart' => 'السلة فارغة',
    'you_have_no_items_in_your_cart!' => 'لا توجد عناصر في سلتك!',
    'your_cart_is_waiting_for_a_course' => 'سلتك تنتظر دورة',
    'checkout' => 'إتمام الدفع',
    'course_checkout' => 'دفع الدورة',
    'demo_checkout_notice' => 'تنبيه الدفع التجريبي',
    'subscription_checkout_is_not_available_yet' => 'دفع الاشتراك غير متاح بعد',
    'subscription_not_available_yet' => 'الاشتراك غير متاح بعد',
    'subscriptions' => 'الاشتراكات',
    'subscription_status' => 'حالة الاشتراك',
    'subscription_plan_details' => 'تفاصيل خطة الاشتراك',
    'no_subscription_yet' => 'لا يوجد اشتراك بعد',
    'youngo_subscription_plans' => 'خطط اشتراك YounGo',
    'wishlist' => 'المفضلة',
    'my_wishlist' => 'مفضلتي',
    'wishlist_summary' => 'ملخص المفضلة',
    'remove_from_wishlist' => 'إزالة من المفضلة',
    'course_added_to_wishlist' => 'تمت إضافة الدورة إلى المفضلة',
    'course_removed_from_wishlist' => 'تمت إزالة الدورة من المفضلة',
    'log_in_to_view_your_wishlist' => 'سجّل الدخول لعرض مفضلتك',
    'buy_now' => 'اشترِ الآن',
    'payment_info' => 'معلومات الدفع',
    'payment_method' => 'طريقة الدفع',
    'payment_settings' => 'إعدادات الدفع',
    'payment_not_configured_yet' => 'الدفع غير مفعّل بعد',
    'continue_to_payment' => 'المتابعة إلى الدفع',
    'coupons' => 'الكوبونات',
    'coupon_code' => 'رمز الكوبون',
    'apply_coupon' => 'تطبيق الكوبون',
    'students' => 'الطلاب',
    'student' => 'طالب',
    'student_profile' => 'ملف الطالب',
    'admin' => 'مسؤول',
    'admins' => 'المسؤولون',
    'administration' => 'الإدارة',
    'root_admin' => 'المسؤول الجذر',
    'manual_grants' => 'المنح اليدوية',
    'active_course' => 'دورة نشطة',
    'active_courses' => 'دورات نشطة',
    'view_profile' => 'عرض الملف الشخصي',
    'update_profile' => 'تحديث الملف الشخصي',
    'profile_photo' => 'صورة الملف الشخصي',
    'back_to_home' => 'العودة إلى الرئيسية',
    'back_to_login' => 'العودة إلى تسجيل الدخول',
    'back_to_profile' => 'العودة إلى الملف الشخصي',
    'save_interesting_courses_while_browsing,_then_compare_options_before_enrolling.' => 'احفظ الدورات التي تهمك أثناء التصفح، ثم قارن الخيارات قبل التسجيل.',
    'keep_favorite_youngo_courses_in_one_place,_then_return_when_your_child_is_ready_to_start.' => 'احتفظ بدورات YounGo المفضلة في مكان واحد، ثم عُد إليها عندما يكون طفلك جاهزاً للبدء.',
    'wishlist_items_are_saved_to_your_learner_account_so_they_stay_available_across_visits.' => 'يتم حفظ عناصر المفضلة في حساب المتعلم لتبقى متاحة في الزيارات القادمة.',
    'review_active_school-granted_access_and_subscription_status_without_checkout_or_payment_actions.' => 'راجع وصول المدرسة النشط وحالة الاشتراك بدون إجراءات دفع أو إتمام شراء.',
    'if_your_school_grants_subscription_access_later,_it_will_appear_here.' => 'إذا منحتك المدرسة وصول اشتراك لاحقاً، فسيظهر هنا.',
    'when_a_course_is_enrolled_or_school-granted,_it_will_appear_here_with_active_lesson_access.' => 'عند التسجيل في دورة أو منحها من المدرسة، ستظهر هنا مع وصول نشط للدروس.',
    'continue_courses_from_enrolments,_school-granted_access,_and_youngo_course_access_in_one_place.' => 'تابع دوراتك من التسجيلات ووصول المدرسة ووصول YounGo في مكان واحد.',
    'enrolled_and_school-granted_courses_will_appear_here_without_creating_payments_or_checkout_orders.' => 'ستظهر هنا الدورات المسجل بها والممنوحة من المدرسة بدون إنشاء مدفوعات أو طلبات دفع.',
    'this_lecture_is_available_only_with_active_course_access' => 'هذه المحاضرة متاحة فقط عند وجود وصول نشط للدورة',
    'you_are_not_authorized_to_access_this_page' => 'غير مصرح لك بالوصول إلى هذه الصفحة',
);

function u4_seed_translate($phrase, $english, $translations, &$stats)
{
    $key = u4_seed_normalize($phrase);
    $english_key = u4_seed_normalize($english);

    if (isset($translations[$key])) {
        $stats['exact_translations']++;
        return $translations[$key];
    }

    if (isset($translations[$english_key])) {
        $stats['exact_translations']++;
        return $translations[$english_key];
    }

    $english = trim((string) $english);
    if ($english === '') {
        return '';
    }

    if (preg_match('/<[^>]+>/', $english) || preg_match('/https?:\\/\\//i', $english) || preg_match('/^[A-Z0-9_\\-\\.\\/]+$/', $english)) {
        $stats['english_fallbacks']++;
        return $english;
    }

    $stats['arabic_fallbacks']++;
    return 'ترجمة مطلوبة: ' . $english;
}

if (!u4_seed_column_exists($mysqli, 'language', 'arabic')) {
    u4_seed_section('Result', array(
        'status' => 'FAIL',
        'reason' => 'language.arabic does not exist. Apply Phase 2U.4 schema first.',
    ));
    exit(1);
}

$result = $mysqli->query("SELECT phrase_id, phrase, english, arabic FROM language ORDER BY phrase_id");
if (!$result) {
    u4_seed_section('Result', array('status' => 'FAIL', 'reason' => 'Could not read language rows.'));
    exit(1);
}

$rows = array();
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

$stats = array(
    'phrases_scanned' => count($rows),
    'exact_translations' => 0,
    'arabic_fallbacks' => 0,
    'english_fallbacks' => 0,
    'updated' => 0,
    'skipped_non_empty' => 0,
    'placeholder_warnings' => 0,
    'mojibake_warnings' => 0,
);

$json_phrases = array();
$updates = array();
foreach ($rows as $row) {
    $current = trim((string) $row['arabic']);
    $english = (string) $row['english'];
    $arabic = $current !== '' && !$force ? $current : u4_seed_translate($row['phrase'], $english, $translations, $stats);

    if (u4_seed_extract_placeholders($english) !== u4_seed_extract_placeholders($arabic)) {
        $stats['placeholder_warnings']++;
        $arabic = $english;
    }

    if (u4_seed_has_mojibake($arabic)) {
        $stats['mojibake_warnings']++;
        $arabic = $english;
    }

    $json_phrases[(string) $row['phrase']] = $arabic;

    if ($current !== '' && !$force) {
        $stats['skipped_non_empty']++;
        continue;
    }

    if ($arabic === '') {
        continue;
    }

    $updates[] = array((int) $row['phrase_id'], $arabic);
}

$mysqli->begin_transaction();
try {
    $statement = $mysqli->prepare("UPDATE language SET arabic = ? WHERE phrase_id = ?");
    if (!$statement) {
        throw new Exception('Could not prepare phrase update.');
    }

    foreach ($updates as $update) {
        $statement->bind_param('si', $update[1], $update[0]);
        if (!$statement->execute()) {
            throw new Exception('Could not update Arabic phrase.');
        }
        $stats['updated'] += $statement->affected_rows >= 0 ? 1 : 0;
    }

    $mysqli->commit();
} catch (Throwable $e) {
    $mysqli->rollback();
    u4_seed_section('Result', array('status' => 'FAIL', 'reason' => $e->getMessage()));
    exit(1);
}

if ($write_file) {
    $language_file = $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'arabic.json';
    $json = json_encode($json_phrases, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents($language_file, $json . PHP_EOL) === false) {
        u4_seed_section('Result', array('status' => 'FAIL', 'reason' => 'Could not write application/language/arabic.json.'));
        exit(1);
    }
}

u4_seed_section('Arabic phrase seed summary', $stats);
u4_seed_section('Deprecated placeholder handling', array(
    'arabic_translated_column_used' => false,
    'arabic_translated_json_used' => false,
    'arabic_json_written' => $write_file,
));
u4_seed_section('Result', array('status' => 'PASS'));
