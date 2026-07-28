<?php
/**
 * LANGUAGE.FRONTEND.AUTH.COPY.WIRE.1 diagnostic.
 *
 * Read-only checks for URI-aware public auth copy wiring. This script does not
 * write DB rows, import language packs, alter auth behavior, call payment
 * providers, or create checkout/order/access records.
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

function yfacw1d_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function yfacw1d_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function yfacw1d_connect($db, $active_group)
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

function yfacw1d_scalar($mysqli, $sql)
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

function yfacw1d_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    $stmt->close();

    return isset($row[0]) && (int) $row[0] > 0;
}

function yfacw1d_phrase_keys()
{
    $seed = file_get_contents(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'phase_2' . DIRECTORY_SEPARATOR . 'youngo_language_frontend_auth_copy_wire_1_seed.php');
    preg_match_all("/array\\('key' => '([^']+)'/", $seed, $matches);

    return array_values(array_unique($matches[1]));
}

function yfacw1d_phrase_summary($mysqli, $keys)
{
    $summary = array(
        'keys_checked' => count($keys),
        'missing_rows' => 0,
        'blank_english' => 0,
        'blank_arabic' => 0,
        'arabic_without_arabic_script' => 0,
        'manual_overrides' => 0,
        'arabic_translated_rows' => 0,
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

    if (yfacw1d_table_exists($mysqli, 'youngo_language_phrase_meta')) {
        $summary['manual_overrides'] = (int) yfacw1d_scalar($mysqli, "SELECT COUNT(*) FROM youngo_language_phrase_meta WHERE language_code = 'arabic' AND (source = 'manual_override' OR manually_overridden_at IS NOT NULL)");
        $summary['arabic_translated_rows'] = (int) yfacw1d_scalar($mysqli, "SELECT COUNT(*) FROM youngo_language_phrase_meta WHERE language_code = 'arabic_translated'");
    }

    return $summary;
}

function yfacw1d_http_get($path)
{
    $url = 'http://localhost' . $path;
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'ignore_errors' => true,
            'timeout' => 15,
            'header' => "User-Agent: YounGoAuthCopyWireDiagnostic/1.0\r\n",
        ),
    ));

    $html = @file_get_contents($url, false, $context);
    $headers = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : array();
    $status = 0;
    foreach ($headers as $header) {
        if (preg_match('#^HTTP/\S+\s+([0-9]{3})#', $header, $matches)) {
            $status = (int) $matches[1];
        }
    }

    return array('path' => $path, 'status' => $status, 'html' => $html === false ? '' : $html);
}

function yfacw1d_text($html)
{
    $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html);
    $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $text);
    $text = preg_replace('/<[^>]+>/', ' ', $text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);

    return trim($text);
}

function yfacw1d_has_lang_dir($html, $lang, $dir)
{
    return preg_match('/<html\b[^>]*\blang=["\']' . preg_quote($lang, '/') . '["\'][^>]*\bdir=["\']' . preg_quote($dir, '/') . '["\']/i', $html) === 1
        || preg_match('/<html\b[^>]*\bdir=["\']' . preg_quote($dir, '/') . '["\'][^>]*\blang=["\']' . preg_quote($lang, '/') . '["\']/i', $html) === 1;
}

function yfacw1d_has_arabic($value)
{
    return preg_match('/\p{Arabic}/u', (string) $value) === 1;
}

function yfacw1d_has_forbidden_payment_link($html)
{
    $patterns = array(
        '#<(?:a|form)\b[^>]*(?:href|action)=["\'][^"\']*(?:payment/paymob|paymob|home/course_payment|home/shopping_cart|youngo/checkout|checkout|order|enrol|grant)[^"\']*["\']#i',
        '#\b(?:Buy Now|Add to cart|Checkout|Pay now|Pay with Paymob|Subscribe now)\b#i',
    );

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html)) {
            return true;
        }
    }

    return false;
}

function yfacw1d_form_action_present($html, $action_path, $method)
{
    return preg_match('#<form\b[^>]*\baction=["\'][^"\']*' . preg_quote($action_path, '#') . '["\'][^>]*\bmethod=["\']' . preg_quote($method, '#') . '["\']#i', $html) === 1;
}

function yfacw1d_auth_source_checks($root)
{
    $views = array(
        'application/views/frontend/youngo/login.php',
        'application/views/frontend/youngo/sign_up.php',
        'application/views/frontend/youngo/forgot_password.php',
        'application/views/frontend/youngo/change_password_from_forgot_password.php',
        'application/views/frontend/youngo/verification_code.php',
        'application/views/frontend/youngo/new_login_confirmation.php',
    );
    $combined = '';
    foreach ($views as $view) {
        $combined .= "\n" . file_get_contents($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $view));
    }

    $helper = file_get_contents($root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'youngo_frontend_language_helper.php');
    $seed = file_get_contents($root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'phase_2' . DIRECTORY_SEPARATOR . 'youngo_language_frontend_auth_copy_wire_1_seed.php');

    return array(
        'auth_views_use_frontend_phrase_e' => strpos($combined, 'youngo_frontend_phrase_e(') !== false,
        'no_legacy_phrase_calls_in_auth_views' => strpos($combined, 'get_phrase(') === false && strpos($combined, 'site_phrase(') === false,
        'login_post_action_unchanged' => strpos($combined, "site_url('login/validate_login')") !== false,
        'register_post_action_unchanged' => strpos($combined, "site_url('login/register')") !== false,
        'forgot_post_action_unchanged' => strpos($combined, "site_url('login/forgot_password/frontend')") !== false,
        'change_password_post_action_unchanged' => strpos($combined, "site_url('login/change_password/' . \$verification_code)") !== false,
        'verification_post_action_unchanged' => strpos($combined, "site_url('login/verify_email_address')") !== false,
        'helper_has_auth_local_fallbacks' => strpos($helper, "'account_help' =>") !== false && strpos($helper, "'email_verification' =>") !== false && strpos($helper, "'document' =>") !== false,
        'seed_is_auth_scoped' => strpos($seed, 'LANGUAGE.FRONTEND.AUTH.COPY.WIRE.1') !== false && strpos($seed, 'yfacw1_seed_inventory') !== false,
        'seed_preserves_manual_overrides' => strpos($seed, 'manual_override') !== false,
        'seed_never_writes_arabic_translated' => strpos($seed, "arabic_translated") !== false && strpos($seed, "'writes_arabic_translated' => false") !== false,
        'seed_outputs_counts_only' => strpos($seed, 'without printing phrase values') !== false,
    );
}

try {
    $mysqli = yfacw1d_connect($db, $active_group);
    $keys = yfacw1d_phrase_keys();
    $phraseSummary = yfacw1d_phrase_summary($mysqli, $keys);
    $sourceChecks = yfacw1d_auth_source_checks($root);

    $httpChecks = array();
    $pages = array(
        'arabic_login' => array('/login', 'ar', 'rtl', 'login/validate_login', array()),
        'english_login' => array('/en/login', 'en', 'ltr', 'login/validate_login', array('Log in to YounGo', 'Email address', 'Password')),
        'arabic_compat_login' => array('/ar/login', 'ar', 'rtl', 'login/validate_login', array()),
        'arabic_signup' => array('/sign_up', 'ar', 'rtl', 'login/register', array()),
        'english_signup' => array('/en/sign-up', 'en', 'ltr', 'login/register', array('Join YounGo', 'First name', 'Create password')),
        'arabic_forgot' => array('/login/forgot_password_request', 'ar', 'rtl', 'login/forgot_password/frontend', array()),
        'english_forgot' => array('/en/login/forgot_password_request', 'en', 'ltr', 'login/forgot_password/frontend', array('Forgot password', 'Your email', 'Send request')),
    );

    foreach ($pages as $label => $expectation) {
        $response = yfacw1d_http_get($expectation[0]);
        $text = yfacw1d_text($response['html']);
        $checks = array(
            'path' => $expectation[0],
            'status' => $response['status'],
            'lang_dir_ok' => yfacw1d_has_lang_dir($response['html'], $expectation[1], $expectation[2]),
            'text_has_arabic' => yfacw1d_has_arabic($text),
            'expected_english_auth_copy_present' => true,
            'form_action_present' => yfacw1d_form_action_present($response['html'], $expectation[3], 'post'),
            'no_payment_or_checkout_links' => !yfacw1d_has_forbidden_payment_link($response['html']),
        );

        if ($expectation[1] === 'ar') {
            $checks['arabic_shell_has_arabic_auth_copy'] = $checks['text_has_arabic'];
        } else {
            foreach ($expectation[4] as $expectedText) {
                if (stripos($text, $expectedText) === false) {
                    $checks['expected_english_auth_copy_present'] = false;
                    break;
                }
            }
        }

        $httpChecks[$label] = $checks;
    }

    foreach ($sourceChecks as $check => $passed) {
        yfacw1d_assert($failures, $passed, 'Source check failed: ' . $check);
    }

    yfacw1d_assert($failures, $phraseSummary['keys_checked'] >= 80, 'Auth phrase inventory is smaller than expected.');
    yfacw1d_assert($failures, $phraseSummary['missing_rows'] === 0, 'One or more auth phrase rows are missing.');
    yfacw1d_assert($failures, $phraseSummary['blank_english'] === 0, 'One or more auth English values are blank.');
    yfacw1d_assert($failures, $phraseSummary['blank_arabic'] === 0, 'One or more auth Arabic values are blank.');
    yfacw1d_assert($failures, $phraseSummary['arabic_without_arabic_script'] === 0, 'One or more auth Arabic values still lack Arabic script.');
    yfacw1d_assert($failures, $phraseSummary['arabic_translated_rows'] === 0, 'arabic_translated metadata rows exist.');

    foreach ($httpChecks as $label => $checks) {
        yfacw1d_assert($failures, $checks['status'] === 200, $label . ' did not return HTTP 200.');
        yfacw1d_assert($failures, $checks['lang_dir_ok'], $label . ' did not render expected lang/dir.');
        yfacw1d_assert($failures, $checks['form_action_present'], $label . ' did not keep expected POST action.');
        yfacw1d_assert($failures, $checks['no_payment_or_checkout_links'], $label . ' rendered a forbidden payment/checkout link.');
        if (isset($checks['arabic_shell_has_arabic_auth_copy'])) {
            yfacw1d_assert($failures, $checks['arabic_shell_has_arabic_auth_copy'], $label . ' did not render Arabic auth copy.');
        }
        if ($expectation[1] === 'en') {
            yfacw1d_assert($failures, $checks['expected_english_auth_copy_present'], $label . ' did not render expected English auth copy.');
        }
    }

    yfacw1d_print('Source checks', $sourceChecks);
    yfacw1d_print('Phrase DB status', $phraseSummary);
    yfacw1d_print('HTTP auth checks', $httpChecks);

    if (!empty($failures)) {
        yfacw1d_print('Result', array('status' => 'failed', 'failures' => $failures));
        exit(1);
    }

    yfacw1d_print('Result', array('status' => 'passed', 'note' => 'Public auth copy is URI-aware, Arabic-ready, and operational auth actions are unchanged.'));
    exit(0);
} catch (Throwable $exception) {
    yfacw1d_print('Result', array('status' => 'failed', 'error' => $exception->getMessage()));
    exit(1);
}
