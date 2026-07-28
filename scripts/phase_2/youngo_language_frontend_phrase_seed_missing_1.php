<?php
/**
 * LANGUAGE.FRONTEND.PHRASE.SEED.MISSING.1
 *
 * Idempotently seeds approved public frontend phrase keys into the existing
 * language table. This script inserts missing rows and fills blank english or
 * arabic values only. It does not write arabic_translated, import packs, change
 * routes, or touch payment/checkout behavior.
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

require $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

function yfpsm1_seed_inventory()
{
    return array(
        array('key' => 'change_password', 'english' => 'Change password', 'arabic' => 'تغيير كلمة المرور', 'area' => 'auth'),
        array('key' => 'change_your_password_to_secure_your_account', 'english' => 'Change your password to secure your account', 'arabic' => 'غير كلمة المرور لحماية حسابك', 'area' => 'auth'),
        array('key' => 'confirm_your_new_password', 'english' => 'Confirm your new password', 'arabic' => 'أكد كلمة المرور الجديدة', 'area' => 'auth'),
        array('key' => 'enter_a_new_password', 'english' => 'Enter a new password', 'arabic' => 'أدخل كلمة مرور جديدة', 'area' => 'auth'),
        array('key' => 'enter_the_code_from_the_email_sent_to', 'english' => 'Enter the code from the email sent to', 'arabic' => 'أدخل الرمز من البريد المرسل إلى', 'area' => 'auth'),
        array('key' => 'enter_the_verification_code', 'english' => 'Enter the verification code', 'arabic' => 'أدخل رمز التحقق', 'area' => 'auth'),
        array('key' => 'enter_your_verification_code', 'english' => 'Enter your verification code', 'arabic' => 'أدخل رمز التحقق الخاص بك', 'area' => 'auth'),
        array('key' => 'enter_your_verification_code_here', 'english' => 'Enter your verification code here', 'arabic' => 'أدخل رمز التحقق هنا', 'area' => 'auth'),
        array('key' => 'let_us_know_that_this_email_address_belongs_to_you', 'english' => 'Let us know that this email address belongs to you', 'arabic' => 'ساعدنا على التأكد أن هذا البريد الإلكتروني يخصك', 'area' => 'auth'),
        array('key' => 'login_confirmation', 'english' => 'Login confirmation', 'arabic' => 'تأكيد تسجيل الدخول', 'area' => 'auth'),
        array('key' => 'new_device_verification_code', 'english' => 'New device verification code', 'arabic' => 'رمز تحقق لجهاز جديد', 'area' => 'auth'),
        array('key' => 'or_continue_with', 'english' => 'Or continue with', 'arabic' => 'أو تابع باستخدام', 'area' => 'auth'),
        array('key' => 'or_sign_up_with', 'english' => 'Or sign up with', 'arabic' => 'أو سجل باستخدام', 'area' => 'auth'),
        array('key' => 'please_try_again', 'english' => 'Please try again', 'arabic' => 'يرجى المحاولة مرة أخرى', 'area' => 'auth'),
        array('key' => 'resend_mail', 'english' => 'Resend mail', 'arabic' => 'إعادة إرسال البريد', 'area' => 'auth'),
        array('key' => 'resend_verification_code', 'english' => 'Resend verification code', 'arabic' => 'إعادة إرسال رمز التحقق', 'area' => 'auth'),
        array('key' => 'retype_your_new_password', 'english' => 'Retype your new password', 'arabic' => 'أعد كتابة كلمة المرور الجديدة', 'area' => 'auth'),
        array('key' => 'sending', 'english' => 'Sending', 'arabic' => 'جار الإرسال', 'area' => 'auth'),
        array('key' => 'sent', 'english' => 'Sent', 'arabic' => 'تم الإرسال', 'area' => 'auth'),
        array('key' => 'verification_code', 'english' => 'Verification code', 'arabic' => 'رمز التحقق', 'area' => 'auth'),
        array('key' => 'access', 'english' => 'Access', 'arabic' => 'الوصول', 'area' => 'learner pages'),
        array('key' => 'access_active', 'english' => 'Access active', 'arabic' => 'الوصول نشط', 'area' => 'learner pages'),
        array('key' => 'access_until', 'english' => 'Access until', 'arabic' => 'الوصول متاح حتى', 'area' => 'learner pages'),
        array('key' => 'checkout_and_renewal_actions_are_not_available_yet.', 'english' => 'Checkout and renewal actions are not available yet.', 'arabic' => 'إجراءات الدفع والتجديد غير متاحة بعد.', 'area' => 'learner pages'),
        array('key' => 'days', 'english' => 'Days', 'arabic' => 'أيام', 'area' => 'learner pages'),
        array('key' => 'keep_favorite_youngo_courses_in_one_place_then_return_when_your_child_is_ready_to_start.', 'english' => 'Keep favorite YounGo courses in one place then return when your child is ready to start.', 'arabic' => 'احتفظ بدورات YounGo المفضلة في مكان واحد ثم عد عندما يكون طفلك مستعدا للبدء.', 'area' => 'learner pages'),
        array('key' => 'no_active_access_matched_this_filter', 'english' => 'No active access matched this filter', 'arabic' => 'لا يوجد وصول نشط يطابق هذا الفلتر', 'area' => 'learner pages'),
        array('key' => 'no_active_subscription', 'english' => 'No active subscription', 'arabic' => 'لا يوجد اشتراك نشط', 'area' => 'learner pages'),
        array('key' => 'no_matching_courses', 'english' => 'No matching courses', 'arabic' => 'لا توجد دورات مطابقة', 'area' => 'learner pages'),
        array('key' => 'quizzes', 'english' => 'Quizzes', 'arabic' => 'الاختبارات', 'area' => 'learner pages'),
        array('key' => 'saved_courses_stay_here_so_you_can_compare_learning_paths_before_access_is_granted.', 'english' => 'Saved courses stay here so you can compare learning paths before access is granted.', 'arabic' => 'تبقى الدورات المحفوظة هنا لتتمكن من مقارنة مسارات التعلم قبل منح الوصول.', 'area' => 'learner pages'),
        array('key' => 'subscription_access', 'english' => 'Subscription access', 'arabic' => 'وصول الاشتراك', 'area' => 'learner pages'),
        array('key' => 'subscription_active', 'english' => 'Subscription active', 'arabic' => 'الاشتراك نشط', 'area' => 'learner pages'),
        array('key' => 'subscription_inactive', 'english' => 'Subscription inactive', 'arabic' => 'الاشتراك غير نشط', 'area' => 'learner pages'),
        array('key' => 'subscription_plan', 'english' => 'Subscription plan', 'arabic' => 'خطة الاشتراك', 'area' => 'learner pages'),
        array('key' => 'try_another_search_or_browse_all_youngo_courses.', 'english' => 'Try another search or browse all YounGo courses.', 'arabic' => 'جرب بحثا آخر أو تصفح كل دورات YounGo.', 'area' => 'learner pages'),
        array('key' => 'until', 'english' => 'Until', 'arabic' => 'حتى', 'area' => 'learner pages'),
        array('key' => 'your_active_learning_access', 'english' => 'Your active learning access', 'arabic' => 'وصولك التعليمي النشط', 'area' => 'learner pages'),
        array('key' => 'your_latest_subscription_is_not_active._checkout_and_renewal_actions_are_not_available_yet.', 'english' => 'Your latest subscription is not active. Checkout and renewal actions are not available yet.', 'arabic' => 'اشتراكك الأخير غير نشط. إجراءات الدفع والتجديد غير متاحة بعد.', 'area' => 'learner pages'),
        array('key' => 'your_subscription_access_is_active', 'english' => 'Your subscription access is active', 'arabic' => 'وصول الاشتراك الخاص بك نشط', 'area' => 'learner pages'),
        array('key' => 'choose_a_learning_plan_for_consistent_youngo_access._online_access_requests_are_not_available_yet.', 'english' => 'Choose a learning plan for consistent YounGo access. Online access requests are not available yet.', 'arabic' => 'اختر خطة تعلم لوصول مستمر إلى YounGo. طلبات الوصول عبر الإنترنت غير متاحة بعد.', 'area' => 'subscriptions'),
        array('key' => 'coming_soon', 'english' => 'Coming soon', 'arabic' => 'قريبا', 'area' => 'subscriptions'),
        array('key' => 'contact_us_to_choose_the_right_starting_point_before_subscriptions_open_online.', 'english' => 'Contact us to choose the right starting point before subscriptions open online.', 'arabic' => 'تواصل معنا لاختيار نقطة البداية المناسبة قبل فتح الاشتراكات عبر الإنترنت.', 'area' => 'subscriptions'),
        array('key' => 'family_access_plans', 'english' => 'Family access plans', 'arabic' => 'خطط وصول العائلة', 'area' => 'subscriptions'),
        array('key' => 'featured_plan', 'english' => 'Featured plan', 'arabic' => 'خطة مميزة', 'area' => 'subscriptions'),
        array('key' => 'no_subscription_plans_available_yet', 'english' => 'No subscription plans available yet', 'arabic' => 'لا توجد خطط اشتراك متاحة بعد', 'area' => 'subscriptions'),
        array('key' => 'plan_available', 'english' => 'Plan available', 'arabic' => 'خطة متاحة', 'area' => 'subscriptions'),
        array('key' => 'plan_options', 'english' => 'Plan options', 'arabic' => 'خيارات الخطط', 'area' => 'subscriptions'),
        array('key' => 'plans_available', 'english' => 'Plans available', 'arabic' => 'خطط متاحة', 'area' => 'subscriptions'),
        array('key' => 'subscription_plans', 'english' => 'Subscription plans', 'arabic' => 'خطط الاشتراك', 'area' => 'subscriptions'),
        array('key' => 'subscription_plans_will_appear_here_after_they_are_approved_and_made_purchasable.', 'english' => 'Subscription plans will appear here after they are approved and made purchasable.', 'arabic' => 'ستظهر خطط الاشتراك هنا بعد اعتمادها وإتاحتها للشراء.', 'area' => 'subscriptions'),
        array('key' => 'talk_to_us_about_subscriptions', 'english' => 'Talk to us about subscriptions', 'arabic' => 'تواصل معنا بخصوص الاشتراكات', 'area' => 'subscriptions'),
        array('key' => 'all_articles', 'english' => 'All articles', 'arabic' => 'كل المقالات', 'area' => 'blog'),
        array('key' => 'back_to_blog', 'english' => 'Back to blog', 'arabic' => 'العودة إلى المدونة', 'area' => 'blog'),
        array('key' => 'helpful_notes_for_families', 'english' => 'Helpful notes for families', 'arabic' => 'ملاحظات مفيدة للعائلات', 'area' => 'blog'),
        array('key' => 'latest_articles', 'english' => 'Latest articles', 'arabic' => 'أحدث المقالات', 'area' => 'blog'),
        array('key' => 'no_blog_posts_yet', 'english' => 'No blog posts yet', 'arabic' => 'لا توجد مقالات بعد', 'area' => 'blog'),
        array('key' => 'practical_articles_and_learning_tips_for_parents_will_appear_here_soon.', 'english' => 'Practical articles and learning tips for parents will appear here soon.', 'arabic' => 'ستظهر هنا قريبا مقالات عملية ونصائح تعلم للأهل.', 'area' => 'blog'),
        array('key' => 'read_more', 'english' => 'Read more', 'arabic' => 'اقرأ المزيد', 'area' => 'blog'),
        array('key' => 'we_will_share_family_learning_notes_here_as_the_youngo_library_grows.', 'english' => 'We will share family learning notes here as the YounGo library grows.', 'arabic' => 'سنشارك هنا ملاحظات تعلم للعائلات مع نمو مكتبة YounGo.', 'area' => 'blog'),
        array('key' => 'contact_us_by_email', 'english' => 'Contact us by email', 'arabic' => 'تواصل معنا عبر البريد الإلكتروني', 'area' => 'contact'),
        array('key' => 'follow_us', 'english' => 'Follow us', 'arabic' => 'تابعنا', 'area' => 'contact'),
        array('key' => 'get_in_touch', 'english' => 'Get in touch', 'arabic' => 'تواصل معنا', 'area' => 'contact'),
        array('key' => 'have_a_question_about_youngo_programs?_we_would_be_happy_to_hear_from_you.', 'english' => 'Have a question about YounGo programs? We would be happy to hear from you.', 'arabic' => 'هل لديك سؤال عن برامج YounGo؟ يسعدنا التواصل معك.', 'area' => 'contact'),
        array('key' => 'ready_to_talk_about_the_right_learning_path?', 'english' => 'Ready to talk about the right learning path?', 'arabic' => 'هل أنت مستعد للحديث عن مسار التعلم المناسب؟', 'area' => 'contact'),
        array('key' => 'send_us_an_email_and_the_youngo_team_will_help_you_choose_a_good_starting_point.', 'english' => 'Send us an email and the YounGo team will help you choose a good starting point.', 'arabic' => 'أرسل لنا بريدا إلكترونيا وسيساعدك فريق YounGo في اختيار نقطة بداية مناسبة.', 'area' => 'contact'),
        array('key' => 'additional_information', 'english' => 'Additional information', 'arabic' => 'معلومات إضافية', 'area' => 'course detail'),
        array('key' => 'certificate', 'english' => 'Certificate', 'arabic' => 'شهادة', 'area' => 'course detail'),
        array('key' => 'more_details', 'english' => 'More details', 'arabic' => 'المزيد من التفاصيل', 'area' => 'course detail'),
        array('key' => 'starts', 'english' => 'Starts', 'arabic' => 'يبدأ', 'area' => 'course detail'),
        array('key' => 'watch_video', 'english' => 'Watch video', 'arabic' => 'شاهد الفيديو', 'area' => 'course detail'),
        array('key' => 'access_locked', 'english' => 'Access locked', 'arabic' => 'الوصول مقفل', 'area' => 'courses listing/cards'),
        array('key' => 'create', 'english' => 'Create', 'arabic' => 'ابتكر', 'area' => 'home page'),
        array('key' => 'grow', 'english' => 'Grow', 'arabic' => 'انم', 'area' => 'home page'),
        array('key' => 'play', 'english' => 'Play', 'arabic' => 'العب', 'area' => 'home page'),
        array('key' => 'confirm', 'english' => 'Confirm', 'arabic' => 'تأكيد', 'area' => 'profile/account'),
        array('key' => 'confirm_your_password', 'english' => 'Confirm your password', 'arabic' => 'أكد كلمة المرور', 'area' => 'profile/account'),
        array('key' => 'if_you_want_to_reactivate_the_account_after_it_has_been_disabled,_you_must_first_authenticate_your_account_from_signup_page.', 'english' => 'If you want to reactivate the account after it has been disabled, you must first authenticate your account from signup page.', 'arabic' => 'إذا أردت إعادة تفعيل الحساب بعد تعطيله، يجب أولا توثيق حسابك من صفحة التسجيل.', 'area' => 'profile/account'),
        array('key' => 'short_title_about_yourself', 'english' => 'Short title about yourself', 'arabic' => 'عنوان قصير عنك', 'area' => 'profile/account'),
        array('key' => 'youngo_learner', 'english' => 'YounGo learner', 'arabic' => 'متعلم في YounGo', 'area' => 'profile/account'),
        array('key' => 'your_skills', 'english' => 'Your skills', 'arabic' => 'مهاراتك', 'area' => 'profile/account'),
        array('key' => 'this_page_is_not_part_of_the_current_public_demo_flow.', 'english' => 'This page is not part of the current public demo flow.', 'arabic' => 'هذه الصفحة ليست جزءا من مسار العرض العام الحالي.', 'area' => 'shared/frontend'),
        array('key' => 'youngo_demo_page', 'english' => 'YounGo demo page', 'arabic' => 'صفحة عرض YounGo', 'area' => 'shared/frontend'),
    );
}

function yfpsm1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function yfpsm1_connect($db, $active_group)
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

function yfpsm1_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SHOW TABLES LIKE ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();

    return $exists;
}

function yfpsm1_has_manual_override($mysqli, $phrase_key, $language_code)
{
    if (!yfpsm1_table_exists($mysqli, 'youngo_language_phrase_meta')) {
        return false;
    }

    $stmt = $mysqli->prepare("SELECT id FROM youngo_language_phrase_meta WHERE phrase_key = ? AND language_code = ? AND (source = 'manual_override' OR manually_overridden_at IS NOT NULL) LIMIT 1");
    $stmt->bind_param('ss', $phrase_key, $language_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $has = $result && $result->num_rows > 0;
    $stmt->close();

    return $has;
}

function yfpsm1_find_phrase($mysqli, $phrase_key)
{
    $stmt = $mysqli->prepare('SELECT phrase_id, phrase, english, arabic FROM language WHERE phrase = ? ORDER BY phrase_id ASC LIMIT 1');
    $stmt->bind_param('s', $phrase_key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function yfpsm1_insert_phrase($mysqli, $phrase_key, $english, $arabic)
{
    $stmt = $mysqli->prepare('INSERT INTO language (phrase, english, arabic) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $phrase_key, $english, $arabic);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function yfpsm1_update_blank_column($mysqli, $phrase_id, $column, $value)
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
    $mysqli = yfpsm1_connect($db, $active_group);
    $inventory = yfpsm1_seed_inventory();
    $counts = array(
        'keys_considered' => count($inventory),
        'inserted_rows' => 0,
        'blank_english_values_filled' => 0,
        'blank_arabic_values_filled' => 0,
        'existing_non_empty_english_preserved' => 0,
        'existing_non_empty_arabic_preserved' => 0,
        'manual_overrides_preserved' => 0,
        'skipped_deferred_keys' => 0,
        'errors' => 0,
        'writes_arabic_translated' => false,
    );
    $byArea = array();

    $mysqli->begin_transaction();

    foreach ($inventory as $seed) {
        $phraseKey = (string) $seed['key'];
        $byArea[$seed['area']] = isset($byArea[$seed['area']]) ? $byArea[$seed['area']] + 1 : 1;

        if (stripos($phraseKey, 'paymob') !== false || preg_match('/(^|_)order(_|$)/', $phraseKey) || preg_match('/(^|_)enrol(_|$)/', $phraseKey)) {
            $counts['skipped_deferred_keys']++;
            continue;
        }

        $row = yfpsm1_find_phrase($mysqli, $phraseKey);
        if (!$row) {
            if (!yfpsm1_insert_phrase($mysqli, $phraseKey, $seed['english'], $seed['arabic'])) {
                $counts['errors']++;
            } else {
                $counts['inserted_rows']++;
            }
            continue;
        }

        if (trim((string) $row['english']) === '') {
            if (yfpsm1_has_manual_override($mysqli, $phraseKey, 'english')) {
                $counts['manual_overrides_preserved']++;
            } elseif (yfpsm1_update_blank_column($mysqli, $row['phrase_id'], 'english', $seed['english'])) {
                $counts['blank_english_values_filled']++;
            }
        } else {
            $counts['existing_non_empty_english_preserved']++;
        }

        if (trim((string) $row['arabic']) === '') {
            if (yfpsm1_has_manual_override($mysqli, $phraseKey, 'arabic')) {
                $counts['manual_overrides_preserved']++;
            } elseif (yfpsm1_update_blank_column($mysqli, $row['phrase_id'], 'arabic', $seed['arabic'])) {
                $counts['blank_arabic_values_filled']++;
            }
        } else {
            $counts['existing_non_empty_arabic_preserved']++;
        }
    }

    if ($counts['errors'] > 0) {
        $mysqli->rollback();
        yfpsm1_print('Seed summary', $counts);
        yfpsm1_print('Result', 'FAIL: seed rolled back because one or more writes failed.');
        exit(1);
    }

    $mysqli->commit();
    ksort($byArea);

    yfpsm1_print('Seed inventory by area', $byArea);
    yfpsm1_print('Seed summary', $counts);
    yfpsm1_print('Result', 'PASS: missing public frontend phrase seed completed idempotently.');
    exit(0);
} catch (Throwable $e) {
    yfpsm1_print('Result', 'FAIL: ' . $e->getMessage());
    exit(1);
}
