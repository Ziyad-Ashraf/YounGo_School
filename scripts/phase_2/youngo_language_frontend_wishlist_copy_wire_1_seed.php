<?php
/**
 * LANGUAGE.FRONTEND.WISHLIST.COPY.WIRE.1
 *
 * Idempotently seeds the public wishlist display phrase keys used by the YounGo
 * frontend. It inserts missing rows and fills blank english/arabic values only.
 * It preserves existing non-empty values and manual overrides, writes only the
 * english/arabic language columns, and never writes arabic_translated.
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

function yfwcw1_seed_inventory()
{
    return array(
        array('key' => 'breadcrumb', 'english' => 'Breadcrumb', 'arabic' => 'مسار الصفحة'),
        array('key' => 'home', 'english' => 'Home', 'arabic' => 'الرئيسية'),
        array('key' => 'my_wishlist', 'english' => 'My wishlist', 'arabic' => 'قائمتي المفضلة'),
        array('key' => 'saved_courses', 'english' => 'Saved courses', 'arabic' => 'الكورسات المحفوظة'),
        array('key' => 'keep_favorite_youngo_courses_in_one_place_then_return_when_your_child_is_ready_to_start.', 'english' => 'Keep favorite YounGo courses in one place, then return when your child is ready to start.', 'arabic' => 'احتفظ بكورسات YounGo المفضلة في مكان واحد، ثم عد إليها عندما يكون طفلك جاهزا للبدء.'),
        array('key' => 'wishlist_summary', 'english' => 'Wishlist summary', 'arabic' => 'ملخص المفضلة'),
        array('key' => 'saved_courses_stay_here_so_you_can_compare_learning_paths_before_access_is_granted.', 'english' => 'Saved courses stay here so you can compare learning paths before access is granted.', 'arabic' => 'تبقى الكورسات المحفوظة هنا لتتمكن من مقارنة مسارات التعلم قبل تفعيل الوصول.'),
        array('key' => 'sign_in_required', 'english' => 'Sign in required', 'arabic' => 'تسجيل الدخول مطلوب'),
        array('key' => 'log_in_to_view_your_wishlist', 'english' => 'Log in to view your wishlist', 'arabic' => 'سجل الدخول لعرض قائمتك المفضلة'),
        array('key' => 'wishlist_items_are_saved_to_your_learner_account_so_they_stay_available_across_visits.', 'english' => 'Wishlist items are saved to your learner account so they stay available across visits.', 'arabic' => 'يتم حفظ عناصر المفضلة في حساب المتعلم لتبقى متاحة في الزيارات القادمة.'),
        array('key' => 'log_in', 'english' => 'Log in', 'arabic' => 'تسجيل الدخول'),
        array('key' => 'browse_courses', 'english' => 'Browse courses', 'arabic' => 'تصفح الكورسات'),
        array('key' => 'wishlist', 'english' => 'Wishlist', 'arabic' => 'المفضلة'),
        array('key' => 'courses_you_saved', 'english' => 'Courses you saved', 'arabic' => 'الكورسات التي حفظتها'),
        array('key' => 'explore_more_courses', 'english' => 'Explore more courses', 'arabic' => 'استكشف المزيد من الكورسات'),
        array('key' => 'remove_from_wishlist', 'english' => 'Remove from wishlist', 'arabic' => 'إزالة من المفضلة'),
        array('key' => 'course_added_to_wishlist', 'english' => 'Course added to wishlist', 'arabic' => 'تمت إضافة الكورس إلى المفضلة'),
        array('key' => 'course_removed_from_wishlist', 'english' => 'Course removed from wishlist', 'arabic' => 'تمت إزالة الكورس من المفضلة'),
        array('key' => 'course', 'english' => 'Course', 'arabic' => 'كورس'),
        array('key' => 'free', 'english' => 'Free', 'arabic' => 'مجاني'),
        array('key' => 'lessons', 'english' => 'Lessons', 'arabic' => 'دروس'),
        array('key' => 'course_details', 'english' => 'Course details', 'arabic' => 'تفاصيل الكورس'),
        array('key' => 'start_now', 'english' => 'Start now', 'arabic' => 'ابدأ الآن'),
        array('key' => 'enroll_now', 'english' => 'Enroll now', 'arabic' => 'سجل الآن'),
        array('key' => 'contact', 'english' => 'Contact', 'arabic' => 'تواصل معنا'),
        array('key' => 'no_saved_courses_yet', 'english' => 'No saved courses yet', 'arabic' => 'لا توجد كورسات محفوظة بعد'),
        array('key' => "build_your_child's_shortlist", 'english' => "Build your child's shortlist", 'arabic' => 'كون قائمة مختصرة لطفلك'),
        array('key' => 'save_interesting_courses_while_browsing,_then_compare_options_before_enrolling.', 'english' => 'Save interesting courses while browsing, then compare options before enrolling.', 'arabic' => 'احفظ الكورسات التي تهمك أثناء التصفح، ثم قارن الخيارات قبل التسجيل.'),
    );
}

function yfwcw1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function yfwcw1_connect($db, $active_group)
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

function yfwcw1_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) AS table_count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return !empty($row['table_count']);
}

function yfwcw1_has_manual_override($mysqli, $phrase_key, $language_code)
{
    static $meta_exists = null;
    if ($meta_exists === null) {
        $meta_exists = yfwcw1_table_exists($mysqli, 'youngo_language_phrase_meta');
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

function yfwcw1_find_phrase($mysqli, $phrase_key)
{
    $stmt = $mysqli->prepare('SELECT phrase_id, phrase, english, arabic FROM language WHERE phrase = ? LIMIT 1');
    $stmt->bind_param('s', $phrase_key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function yfwcw1_insert_phrase($mysqli, $phrase_key, $english, $arabic)
{
    $stmt = $mysqli->prepare('INSERT INTO language (phrase, english, arabic) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $phrase_key, $english, $arabic);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function yfwcw1_update_blank_column($mysqli, $phrase_id, $column, $value)
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
    $mysqli = yfwcw1_connect($db, $active_group);
    $inventory = yfwcw1_seed_inventory();
    $counts = array(
        'keys_considered' => count($inventory),
        'inserted_rows' => 0,
        'blank_english_values_filled' => 0,
        'blank_arabic_values_filled' => 0,
        'existing_non_empty_english_preserved' => 0,
        'existing_non_empty_arabic_preserved' => 0,
        'manual_overrides_preserved' => 0,
        'writes_arabic_translated' => false,
        'errors' => 0,
    );

    foreach ($inventory as $phrase) {
        $phrase_key = $phrase['key'];
        if ($phrase_key === 'arabic_translated') {
            $counts['writes_arabic_translated'] = true;
            $counts['errors']++;
            continue;
        }

        $row = yfwcw1_find_phrase($mysqli, $phrase_key);
        if (!$row) {
            if (yfwcw1_insert_phrase($mysqli, $phrase_key, $phrase['english'], $phrase['arabic'])) {
                $counts['inserted_rows']++;
            } else {
                $counts['errors']++;
            }
            continue;
        }

        foreach (array('english', 'arabic') as $column) {
            if (yfwcw1_has_manual_override($mysqli, $phrase_key, $column)) {
                $counts['manual_overrides_preserved']++;
                continue;
            }

            if (trim((string) $row[$column]) === '') {
                if (yfwcw1_update_blank_column($mysqli, (int) $row['phrase_id'], $column, $phrase[$column])) {
                    $counts['blank_' . $column . '_values_filled']++;
                }
            } else {
                $counts['existing_non_empty_' . $column . '_preserved']++;
            }
        }
    }

    $mysqli->close();
    yfwcw1_print('Wishlist Phrase Seed Result', $counts);

    if ($counts['errors'] > 0 || $counts['writes_arabic_translated']) {
        exit(1);
    }

    exit(0);
} catch (Throwable $exception) {
    yfwcw1_print('Wishlist Phrase Seed Error', array('error' => $exception->getMessage()));
    exit(1);
}
