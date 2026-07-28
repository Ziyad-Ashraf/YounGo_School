<?php
/**
 * LANGUAGE.FRONTEND.AUTH.COPY.QA.1 diagnostic.
 *
 * Read-only source/DB checks for public auth copy QA. This script does not
 * submit auth forms, write DB rows, import language packs, alter sessions,
 * change auth behavior, call Paymob, or create checkout/order/access records.
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
defined('FCPATH') || define('FCPATH', $root . DIRECTORY_SEPARATOR);
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'config' . DIRECTORY_SEPARATOR . 'database.php';

$failures = array();

function yfacq1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function yfacq1_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function yfacq1_file($path)
{
    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException('Could not read expected file: ' . $path);
    }

    return $contents;
}

function yfacq1_connect($db, $active_group)
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

function yfacq1_scalar($mysqli, $sql)
{
    if (preg_match('/^\s*(INSERT|UPDATE|DELETE|ALTER|DROP|CREATE|TRUNCATE|REPLACE|GRANT|REVOKE|LOAD|CALL|OPTIMIZE|ANALYZE)\b/i', $sql)) {
        throw new RuntimeException('Blocked non-read SQL in diagnostic.');
    }

    $result = $mysqli->query($sql);
    if (!$result) {
        throw new RuntimeException('DB read failed without exposing credentials.');
    }

    $row = $result->fetch_row();
    $result->free();

    return isset($row[0]) ? $row[0] : null;
}

function yfacq1_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    $stmt->close();

    return isset($row[0]) && (int) $row[0] > 0;
}

function yfacq1_seed_phrase_keys($root)
{
    $seed = yfacq1_file($root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'phase_2' . DIRECTORY_SEPARATOR . 'youngo_language_frontend_auth_copy_wire_1_seed.php');
    preg_match_all("/array\\('key' => '([^']+)'/", $seed, $matches);

    return array_values(array_unique($matches[1]));
}

function yfacq1_phrase_summary($mysqli, $keys)
{
    $summary = array(
        'keys_checked' => count($keys),
        'missing_rows' => 0,
        'blank_english' => 0,
        'blank_arabic' => 0,
        'arabic_without_arabic_script' => 0,
        'arabic_translated_metadata_rows' => 0,
    );

    $stmt = $mysqli->prepare('SELECT phrase, english, arabic FROM language WHERE phrase = ? LIMIT 1');
    foreach ($keys as $key) {
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            $summary['missing_rows']++;
            continue;
        }

        if (trim((string) $row['english']) === '') {
            $summary['blank_english']++;
        }

        if (trim((string) $row['arabic']) === '') {
            $summary['blank_arabic']++;
        }

        if (!preg_match('/\p{Arabic}/u', (string) $row['arabic'])) {
            $summary['arabic_without_arabic_script']++;
        }
    }
    $stmt->close();

    if (yfacq1_table_exists($mysqli, 'youngo_language_phrase_meta')) {
        $summary['arabic_translated_metadata_rows'] = (int) yfacq1_scalar($mysqli, "SELECT COUNT(*) FROM youngo_language_phrase_meta WHERE language_code = 'arabic_translated'");
    }

    return $summary;
}

function yfacq1_flash_phrase_status($mysqli)
{
    $keys = array(
        'invalid_login_credentials',
        'user_not_found',
        'recaptcha_verification_failed',
        'check_your_inbox_for_the_request',
        'invalid_verification_code',
        'please_send_a_new_forgot_password_request',
        'time_over',
        'please_try_again',
        'verification_code_is_wrong',
    );

    $status = array();
    $stmt = $mysqli->prepare('SELECT phrase, english, arabic FROM language WHERE phrase = ? LIMIT 1');
    foreach ($keys as $key) {
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $status[$key] = array(
            'exists' => (bool) $row,
            'english_present' => $row ? trim((string) $row['english']) !== '' : false,
            'arabic_present' => $row ? trim((string) $row['arabic']) !== '' : false,
            'arabic_has_script' => $row ? preg_match('/\p{Arabic}/u', (string) $row['arabic']) === 1 : false,
        );
    }
    $stmt->close();

    return $status;
}

function yfacq1_source_checks($root)
{
    $view_paths = array(
        'login' => 'application/views/frontend/youngo/login.php',
        'sign_up' => 'application/views/frontend/youngo/sign_up.php',
        'forgot_password' => 'application/views/frontend/youngo/forgot_password.php',
        'change_password' => 'application/views/frontend/youngo/change_password_from_forgot_password.php',
        'verification_code' => 'application/views/frontend/youngo/verification_code.php',
        'new_login_confirmation' => 'application/views/frontend/youngo/new_login_confirmation.php',
    );

    $views = array();
    $combined = '';
    foreach ($view_paths as $name => $relative_path) {
        $contents = yfacq1_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path));
        $views[$name] = $contents;
        $combined .= "\n" . $contents;
    }

    $helper = yfacq1_file($root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'youngo_frontend_language_helper.php');
    $controller = yfacq1_file($root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'Login.php');

    return array(
        'views_use_frontend_phrase_helper' => strpos($combined, 'youngo_frontend_phrase_e(') !== false && strpos($combined, 'youngo_frontend_phrase(') !== false,
        'login_action_unchanged' => strpos($views['login'], "site_url('login/validate_login')") !== false,
        'signup_action_unchanged' => strpos($views['sign_up'], "site_url('login/register')") !== false,
        'forgot_action_unchanged' => strpos($views['forgot_password'], "site_url('login/forgot_password/frontend')") !== false,
        'change_password_action_unchanged' => strpos($views['change_password'], "site_url('login/change_password/' . \$verification_code)") !== false,
        'verification_action_unchanged' => strpos($views['verification_code'], "site_url('login/verify_email_address')") !== false,
        'verification_fetch_urls_unchanged' => strpos($views['verification_code'], "site_url('login/verify_email_address/')") !== false && strpos($views['verification_code'], "site_url('login/resend_verification_code/')") !== false,
        'new_device_action_unchanged' => strpos($views['new_login_confirmation'], "site_url('login/new_login_confirmation/submit')") !== false,
        'new_device_resend_url_unchanged' => strpos($views['new_login_confirmation'], "site_url('login/new_login_confirmation/resend')") !== false,
        'auth_views_no_arabic_translated_ui' => stripos($combined, 'arabic_translated') === false,
        'helper_does_not_return_arabic_translated_as_phrase_language' => strpos($helper, "return 'arabic_translated'") === false && strpos($helper, "return 'arabic';") !== false,
        'controller_keeps_legacy_post_routes' => strpos($controller, 'function validate_login') !== false && strpos($controller, "redirect(site_url('login'), 'refresh')") !== false,
        'no_payment_checkout_paymob_links_in_auth_views' => preg_match('/(?:paymob|payment|checkout|home\/shopping_cart|home\/course_payment|youngo\/checkout|apply_coupon|remove_coupon)/i', $combined) !== 1,
    );
}

function yfacq1_expected_form_actions()
{
    return array(
        'login' => 'login/validate_login',
        'signup' => 'login/register',
        'forgot_password' => 'login/forgot_password/frontend',
        'change_password' => 'login/change_password/{verification_code}',
        'verification_code' => 'login/verify_email_address',
        'verification_resend' => 'login/resend_verification_code',
        'new_device_confirmation' => 'login/new_login_confirmation/submit',
        'new_device_resend' => 'login/new_login_confirmation/resend',
    );
}

function yfacq1_protected_counts($mysqli)
{
    $tables = array(
        'payment',
        'enrol',
        'youngo_checkout_orders',
        'youngo_coupon_usages',
        'youngo_course_access',
        'youngo_user_subscriptions',
        'youngo_manual_grants',
    );

    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = yfacq1_table_exists($mysqli, $table)
            ? (int) yfacq1_scalar($mysqli, 'SELECT COUNT(*) FROM `' . $mysqli->real_escape_string($table) . '`')
            : null;
    }

    return $counts;
}

try {
    $sourceChecks = yfacq1_source_checks($root);
    $mysqli = yfacq1_connect($db, $active_group);
    $phraseSummary = yfacq1_phrase_summary($mysqli, yfacq1_seed_phrase_keys($root));
    $flashPhraseStatus = yfacq1_flash_phrase_status($mysqli);
    $protectedCounts = yfacq1_protected_counts($mysqli);
    $mysqli->close();

    foreach ($sourceChecks as $check => $passed) {
        yfacq1_assert($failures, $passed, 'Source check failed: ' . $check);
    }

    yfacq1_assert($failures, $phraseSummary['keys_checked'] >= 80, 'Auth display phrase inventory is smaller than expected.');
    yfacq1_assert($failures, $phraseSummary['missing_rows'] === 0, 'One or more auth display phrase rows are missing.');
    yfacq1_assert($failures, $phraseSummary['blank_english'] === 0, 'One or more auth display English values are blank.');
    yfacq1_assert($failures, $phraseSummary['blank_arabic'] === 0, 'One or more auth display Arabic values are blank.');
    yfacq1_assert($failures, $phraseSummary['arabic_without_arabic_script'] === 0, 'One or more auth display Arabic values lack Arabic script.');
    yfacq1_assert($failures, $phraseSummary['arabic_translated_metadata_rows'] === 0, 'arabic_translated metadata rows exist.');

    yfacq1_print('Source checks', $sourceChecks);
    yfacq1_print('Expected unchanged auth action URLs', yfacq1_expected_form_actions());
    yfacq1_print('Auth display phrase DB status', $phraseSummary);
    yfacq1_print('Backend flash phrase status for QA documentation', $flashPhraseStatus);
    yfacq1_print('Protected table counts read-only snapshot', $protectedCounts);

    if (!empty($failures)) {
        yfacq1_print('Result', array('status' => 'failed', 'failures' => $failures));
        exit(1);
    }

    yfacq1_print('Result', array(
        'status' => 'passed',
        'note' => 'Scoped auth display copy wiring, expected form actions, UI language-code boundary, and payment/checkout link boundaries passed read-only checks.',
    ));
    exit(0);
} catch (Throwable $exception) {
    yfacq1_print('Result', array('status' => 'failed', 'error' => $exception->getMessage()));
    exit(1);
}
