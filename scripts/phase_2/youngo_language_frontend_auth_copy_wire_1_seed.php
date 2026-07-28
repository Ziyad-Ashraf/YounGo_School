<?php
/**
 * LANGUAGE.FRONTEND.AUTH.COPY.WIRE.1
 *
 * Seeds/polishes only public auth display phrase values needed by the YounGo
 * frontend. It preserves manual overrides, prints counts only, writes only the
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
require_once APPPATH . 'helpers' . DIRECTORY_SEPARATOR . 'youngo_frontend_language_helper.php';

function yfacw1_seed_inventory()
{
    return array(
        array('key' => 'safe_learning_doorway', 'english' => 'Safe learning doorway', 'area' => 'login'),
        array('key' => 'a_safe_place_to_keep_learning', 'english' => 'A safe place to keep learning', 'area' => 'login'),
        array('key' => 'pick_up_the_next_lesson_with_confidence.', 'english' => 'Pick up the next lesson with confidence.', 'area' => 'login'),
        array('key' => 'youngo_keeps_guided_lessons,_creative_projects,_and_progress_moments_together_for_curious_kids_and_the_families_cheering_them_on.', 'english' => 'YounGo keeps guided lessons, creative projects, and progress moments together for curious kids and the families cheering them on.', 'area' => 'login'),
        array('key' => 'today', 'english' => 'Today', 'area' => 'login'),
        array('key' => 'creative_project_ready', 'english' => 'Creative project ready', 'area' => 'login'),
        array('key' => 'parent_view', 'english' => 'Parent view', 'area' => 'login'),
        array('key' => 'progress_feels_clear', 'english' => 'Progress feels clear', 'area' => 'login'),
        array('key' => 'guided', 'english' => 'Guided', 'area' => 'login'),
        array('key' => 'lessons_with_structure', 'english' => 'Lessons with structure', 'area' => 'login'),
        array('key' => 'creative', 'english' => 'Creative', 'area' => 'login'),
        array('key' => 'projects_kids_remember', 'english' => 'Projects kids remember', 'area' => 'login'),
        array('key' => 'calm', 'english' => 'Calm', 'area' => 'login'),
        array('key' => 'space_parents_can_trust', 'english' => 'Space parents can trust', 'area' => 'login'),
        array('key' => 'welcome_back', 'english' => 'Welcome back', 'area' => 'login'),
        array('key' => 'log_in_to_youngo', 'english' => 'Log in to YounGo', 'area' => 'login'),
        array('key' => 'continue_a_safe,_joyful_learning_journey_built_for_curious_kids_and_confident_parents.', 'english' => 'Continue a safe, joyful learning journey built for curious kids and confident parents.', 'area' => 'login'),
        array('key' => 'email_address', 'english' => 'Email address', 'area' => 'auth_forms'),
        array('key' => 'enter_your_email', 'english' => 'Enter your email', 'area' => 'auth_forms'),
        array('key' => 'password', 'english' => 'Password', 'area' => 'auth_forms'),
        array('key' => 'show', 'english' => 'Show', 'area' => 'auth_forms'),
        array('key' => 'hide', 'english' => 'Hide', 'area' => 'auth_forms'),
        array('key' => 'show_password', 'english' => 'Show password', 'area' => 'auth_forms'),
        array('key' => 'hide_password', 'english' => 'Hide password', 'area' => 'auth_forms'),
        array('key' => 'forgot_password?', 'english' => 'Forgot password?', 'area' => 'auth_forms'),
        array('key' => 'log_in', 'english' => 'Log in', 'area' => 'auth_forms'),
        array('key' => 'or_continue_with', 'english' => 'Or continue with', 'area' => 'auth_forms'),
        array('key' => 'new_to_youngo?', 'english' => 'New to YounGo?', 'area' => 'auth_forms'),
        array('key' => 'sign_up', 'english' => 'Sign up', 'area' => 'auth_forms'),
        array('key' => 'account_help', 'english' => 'Account help', 'area' => 'forgot_password'),
        array('key' => 'forgot_password', 'english' => 'Forgot password', 'area' => 'forgot_password'),
        array('key' => 'enter_your_email_and_we_will_send_the_next_step_to_help_secure_your_account.', 'english' => 'Enter your email and we will send the next step to help secure your account.', 'area' => 'forgot_password'),
        array('key' => 'we_will_use_your_email_to_find_your_account.', 'english' => 'We will use your email to find your account.', 'area' => 'forgot_password'),
        array('key' => 'your_email', 'english' => 'Your email', 'area' => 'forgot_password'),
        array('key' => 'send_request', 'english' => 'Send request', 'area' => 'forgot_password'),
        array('key' => 'back_to_login', 'english' => 'Back to login', 'area' => 'auth_forms'),
        array('key' => 'account_security', 'english' => 'Account security', 'area' => 'verification'),
        array('key' => 'change_password', 'english' => 'Change password', 'area' => 'reset_password'),
        array('key' => 'change_your_password_to_secure_your_account', 'english' => 'Change your password to secure your account', 'area' => 'reset_password'),
        array('key' => 'new_password', 'english' => 'New password', 'area' => 'reset_password'),
        array('key' => 'enter_a_new_password', 'english' => 'Enter a new password', 'area' => 'reset_password'),
        array('key' => 'confirm_your_new_password', 'english' => 'Confirm your new password', 'area' => 'reset_password'),
        array('key' => 'retype_your_new_password', 'english' => 'Retype your new password', 'area' => 'reset_password'),
        array('key' => 'continue', 'english' => 'Continue', 'area' => 'auth_forms'),
        array('key' => 'email_verification', 'english' => 'Email verification', 'area' => 'verification'),
        array('key' => 'enter_your_verification_code_here', 'english' => 'Enter your verification code here', 'area' => 'verification'),
        array('key' => 'verification_code', 'english' => 'Verification code', 'area' => 'verification'),
        array('key' => 'enter_your_verification_code', 'english' => 'Enter your verification code', 'area' => 'verification'),
        array('key' => 'resend_mail', 'english' => 'Resend mail', 'area' => 'verification'),
        array('key' => 'login_confirmation', 'english' => 'Login confirmation', 'area' => 'verification'),
        array('key' => 'let_us_know_that_this_email_address_belongs_to_you', 'english' => 'Let us know that this email address belongs to you', 'area' => 'verification'),
        array('key' => 'enter_the_code_from_the_email_sent_to', 'english' => 'Enter the code from the email sent to', 'area' => 'verification'),
        array('key' => 'enter_the_verification_code', 'english' => 'Enter the verification code', 'area' => 'verification'),
        array('key' => 'new_device_verification_code', 'english' => 'New device verification code', 'area' => 'verification'),
        array('key' => 'resend_verification_code', 'english' => 'Resend verification code', 'area' => 'verification'),
        array('key' => 'sending', 'english' => 'Sending', 'area' => 'verification'),
        array('key' => 'sent', 'english' => 'Sent', 'area' => 'verification'),
        array('key' => 'please_try_again', 'english' => 'Please try again', 'area' => 'verification'),
        array('key' => 'youngo_learning_benefits', 'english' => 'YounGo learning benefits', 'area' => 'signup'),
        array('key' => 'start_with_confidence', 'english' => 'Start with confidence', 'area' => 'signup'),
        array('key' => 'a_joyful_learning_path_for_growing_minds.', 'english' => 'A joyful learning path for growing minds.', 'area' => 'signup'),
        array('key' => 'families_come_to_youngo_for_guided_lessons,_creative_practice,_and_a_calm_space_designed_around_kids_learning_well.', 'english' => 'Families come to YounGo for guided lessons, creative practice, and a calm space designed around kids learning well.', 'area' => 'signup'),
        array('key' => 'step_1', 'english' => 'Step 1', 'area' => 'signup'),
        array('key' => 'choose_a_guided_lesson', 'english' => 'Choose a guided lesson', 'area' => 'signup'),
        array('key' => 'step_2', 'english' => 'Step 2', 'area' => 'signup'),
        array('key' => 'build,_practice,_and_grow', 'english' => 'Build, practice, and grow', 'area' => 'signup'),
        array('key' => 'safe_learning_space', 'english' => 'Safe learning space', 'area' => 'signup'),
        array('key' => 'a_friendly_environment_for_young_learners.', 'english' => 'A friendly environment for young learners.', 'area' => 'signup'),
        array('key' => 'guided_discovery', 'english' => 'Guided discovery', 'area' => 'signup'),
        array('key' => 'lessons_help_kids_move_with_structure.', 'english' => 'Lessons help kids move with structure.', 'area' => 'signup'),
        array('key' => 'creative_confidence', 'english' => 'Creative confidence', 'area' => 'signup'),
        array('key' => 'projects_turn_practice_into_progress.', 'english' => 'Projects turn practice into progress.', 'area' => 'signup'),
        array('key' => 'create_your_account', 'english' => 'Create your account', 'area' => 'signup'),
        array('key' => 'join_youngo', 'english' => 'Join YounGo', 'area' => 'signup'),
        array('key' => 'start_with_guided_lessons,_creative_projects,_and_progress_moments_families_can_feel_good_about.', 'english' => 'Start with guided lessons, creative projects, and progress moments families can feel good about.', 'area' => 'signup'),
        array('key' => 'first_name', 'english' => 'First name', 'area' => 'signup'),
        array('key' => 'last_name', 'english' => 'Last name', 'area' => 'signup'),
        array('key' => 'enter_your_first_name', 'english' => 'Enter your first name', 'area' => 'signup'),
        array('key' => 'enter_your_last_name', 'english' => 'Enter your last name', 'area' => 'signup'),
        array('key' => 'create_password', 'english' => 'Create password', 'area' => 'signup'),
        array('key' => 'apply_to_become_an_instructor', 'english' => 'Apply to become an instructor', 'area' => 'signup'),
        array('key' => 'phone', 'english' => 'Phone', 'area' => 'signup'),
        array('key' => 'enter_your_phone_number', 'english' => 'Enter your phone number', 'area' => 'signup'),
        array('key' => 'document', 'english' => 'Document', 'area' => 'signup'),
        array('key' => 'provide_some_documents_about_your_qualifications', 'english' => 'Provide some documents about your qualifications', 'area' => 'signup'),
        array('key' => 'message', 'english' => 'Message', 'area' => 'signup'),
        array('key' => 'or_sign_up_with', 'english' => 'Or sign up with', 'area' => 'signup'),
        array('key' => 'already_have_an_account?', 'english' => 'Already have an account?', 'area' => 'signup'),
    );
}

function yfacw1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

function yfacw1_connect($db, $active_group)
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

function yfacw1_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) AS table_count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return !empty($row['table_count']);
}

function yfacw1_normalize_key($phrase_key)
{
    return youngo_frontend_phrase_normalize_key($phrase_key);
}

function yfacw1_has_arabic_script($value)
{
    return (bool) preg_match('/\p{Arabic}/u', (string) $value);
}

function yfacw1_arabic_needs_update($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return true;
    }

    if (!yfacw1_has_arabic_script($value)) {
        return true;
    }

    return !youngo_frontend_phrase_value_is_clean($value);
}

function yfacw1_has_manual_override($mysqli, $phrase_key, $language_code)
{
    static $meta_exists = null;
    if ($meta_exists === null) {
        $meta_exists = yfacw1_table_exists($mysqli, 'youngo_language_phrase_meta');
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

function yfacw1_find_phrase($mysqli, $phrase_key)
{
    $stmt = $mysqli->prepare('SELECT phrase_id, phrase, english, arabic FROM language WHERE phrase = ? LIMIT 1');
    $stmt->bind_param('s', $phrase_key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function yfacw1_insert_phrase($mysqli, $phrase_key, $english, $arabic)
{
    $stmt = $mysqli->prepare('INSERT INTO language (phrase, english, arabic) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $phrase_key, $english, $arabic);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function yfacw1_update_column($mysqli, $phrase_id, $column, $value, $only_blank)
{
    if (!in_array($column, array('english', 'arabic'), true)) {
        return false;
    }

    $phrase_id = (int) $phrase_id;
    $sql = 'UPDATE language SET `' . $column . '` = ? WHERE phrase_id = ?';
    if ($only_blank) {
        $sql .= ' AND (`' . $column . '` IS NULL OR TRIM(`' . $column . '`) = \'\')';
    }

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('si', $value, $phrase_id);
    $stmt->execute();
    $changed = $stmt->affected_rows > 0;
    $stmt->close();

    return $changed;
}

try {
    $mysqli = yfacw1_connect($db, $active_group);
    $inventory = yfacw1_seed_inventory();
    $counts = array(
        'keys_considered' => count($inventory),
        'inserted_rows' => 0,
        'blank_english_values_filled' => 0,
        'arabic_values_updated' => 0,
        'existing_values_preserved' => 0,
        'manual_overrides_preserved' => 0,
        'skipped_protected_keys' => 0,
        'missing_local_arabic_source' => 0,
        'errors' => 0,
        'writes_arabic_translated' => false,
    );
    $by_area = array();

    $mysqli->begin_transaction();

    foreach ($inventory as $item) {
        $key = yfacw1_normalize_key($item['key']);
        $by_area[$item['area']] = isset($by_area[$item['area']]) ? $by_area[$item['area']] + 1 : 1;

        if ($key === '' || $key === 'arabic_translated' || stripos($key, 'paymob') !== false || preg_match('/(^|_)checkout(_|$)|(^|_)payment(_|$)|(^|_)order(_|$)|(^|_)enrol(_|$)|(^|_)grant(_|$)/', $key)) {
            $counts['skipped_protected_keys']++;
            continue;
        }

        $arabic = youngo_frontend_local_phrase_value($key, 'arabic');
        if ($arabic === null || !yfacw1_has_arabic_script($arabic)) {
            $counts['missing_local_arabic_source']++;
            continue;
        }

        $english = (string) $item['english'];
        $row = yfacw1_find_phrase($mysqli, $key);
        if (!$row) {
            if (yfacw1_insert_phrase($mysqli, $key, $english, $arabic)) {
                $counts['inserted_rows']++;
            } else {
                $counts['errors']++;
            }
            continue;
        }

        if (trim((string) $row['english']) === '' && !yfacw1_has_manual_override($mysqli, $key, 'english')) {
            if (yfacw1_update_column($mysqli, (int) $row['phrase_id'], 'english', $english, true)) {
                $counts['blank_english_values_filled']++;
            }
        }

        if (yfacw1_has_manual_override($mysqli, $key, 'arabic')) {
            $counts['manual_overrides_preserved']++;
            continue;
        }

        if (yfacw1_arabic_needs_update($row['arabic'])) {
            if (yfacw1_update_column($mysqli, (int) $row['phrase_id'], 'arabic', $arabic, false)) {
                $counts['arabic_values_updated']++;
            }
        } else {
            $counts['existing_values_preserved']++;
        }
    }

    if ($counts['errors'] > 0 || $counts['writes_arabic_translated']) {
        $mysqli->rollback();
        yfacw1_print('Result', array('status' => 'failed', 'counts' => $counts));
        exit(1);
    }

    $mysqli->commit();
    ksort($by_area);

    yfacw1_print('Seed inventory by area', $by_area);
    yfacw1_print('Seed summary', $counts);
    yfacw1_print('Result', array('status' => 'passed', 'note' => 'Auth display phrases seeded/polished without printing phrase values.'));
    $mysqli->close();
} catch (Throwable $exception) {
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $mysqli->rollback();
    }
    echo "ERROR: " . $exception->getMessage() . "\n";
    exit(1);
}
