<?php
/**
 * DYNAMIC.CONTENT.ARABIC.PUBLIC.LOCALIZATION.QA.1 diagnostic.
 *
 * Read-only public localization QA checks. This script does not write DB rows,
 * import language packs, seed phrases, change routes, call Paymob, or create
 * checkout/order/access records.
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

function youngo_public_loc_qa_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function youngo_public_loc_qa_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function youngo_public_loc_qa_http_get($path)
{
    $url = 'http://localhost' . $path;
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'ignore_errors' => true,
            'timeout' => 15,
            'header' => "User-Agent: YounGoPublicLocalizationQaDiagnostic/1.0\r\n",
        ),
    ));

    $html = @file_get_contents($url, false, $context);
    $headers = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : array();

    $status = 0;
    $refresh = '';
    foreach ($headers as $header) {
        if (preg_match('#^HTTP/\S+\s+([0-9]{3})#', $header, $matches)) {
            $status = (int) $matches[1];
        }
        if (stripos($header, 'Refresh:') === 0) {
            $refresh = trim(substr($header, strlen('Refresh:')));
        }
    }

    return array(
        'path' => $path,
        'url' => $url,
        'status' => $status,
        'refresh' => $refresh,
        'html' => $html === false ? '' : $html,
    );
}

function youngo_public_loc_qa_html_has_lang_dir($html, $lang, $dir)
{
    return preg_match('/<html\b[^>]*\blang=["\']' . preg_quote($lang, '/') . '["\'][^>]*\bdir=["\']' . preg_quote($dir, '/') . '["\']/i', $html) === 1
        || preg_match('/<html\b[^>]*\bdir=["\']' . preg_quote($dir, '/') . '["\'][^>]*\blang=["\']' . preg_quote($lang, '/') . '["\']/i', $html) === 1;
}

function youngo_public_loc_qa_has_arabic($value)
{
    return preg_match('/\p{Arabic}/u', (string) $value) === 1;
}

function youngo_public_loc_qa_text($html)
{
    $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html);
    $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $text);
    $text = preg_replace('/<[^>]+>/', ' ', $text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);

    return trim($text);
}

function youngo_public_loc_qa_has_forbidden_payment_cta($html)
{
    $patterns = array(
        '#<(?:a|form)\b[^>]*(?:href|action)=["\'][^"\']*(?:payment/paymob|paymob|home/course_payment|home/shopping_cart|youngo/checkout|checkout|order|enrol|grant)[^"\']*["\']#i',
        '#\b(?:Buy Now|Add to cart|Checkout|Pay now|Pay with Paymob|Subscribe now)\b#i',
    );

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html) === 1) {
            return true;
        }
    }

    return false;
}

function youngo_public_loc_qa_contains_ar_compat_canonical_link($html)
{
    return preg_match('#href=["\'][^"\']*/ar/(?:home/)?(?:courses|course|subscriptions|blog|contact)[^"\']*["\']#i', $html) === 1;
}

function youngo_public_loc_qa_has_bad_arabic_translated_ui_usage($html)
{
    $clean = preg_replace('/name=["\']language["\']\s+value=["\']arabic_translated["\']/i', '', $html);
    $clean = preg_replace('/value=["\']arabic_translated["\']\s+[^>]*name=["\']language["\']/i', '', $clean);

    return stripos($clean, 'arabic_translated') !== false;
}

function youngo_public_loc_qa_has_unexpected_arabic_on_english($text)
{
    $clean = preg_replace('/\bعربي\b/u', '', (string) $text);
    return youngo_public_loc_qa_has_arabic($clean);
}

function youngo_public_loc_qa_connect($db, $active_group)
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

function youngo_public_loc_qa_scalar($mysqli, $sql)
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

$arabicPages = array(
    '/',
    '/home/courses',
    '/home/course/robotics-and-ai-explorers/9',
    '/subscriptions',
    '/home/blog',
    '/home/contact',
    '/home/my_wishlist',
    '/login',
);

$arabicRedirectPages = array(
    '/home/my_courses' => 'http://localhost/',
    '/home/my_access' => 'http://localhost/',
);

$arCompatPages = array(
    '/ar',
    '/ar/subscriptions',
    '/ar/home/courses',
);

$englishPages = array(
    '/en',
    '/en/home/courses',
    '/en/home/course/robotics-and-ai-explorers/9',
    '/en/subscriptions',
    '/en/home/blog',
    '/en/home/contact',
    '/en/login',
);

$englishRedirectPages = array(
    '/en/home/my_courses' => 'http://localhost/en',
    '/en/home/my_access' => 'http://localhost/en',
);

try {
    $httpSummary = array();

    foreach ($arabicPages as $path) {
        $response = youngo_public_loc_qa_http_get($path);
        $html = $response['html'];
        $text = youngo_public_loc_qa_text($html);
        $checks = array(
            'status' => $response['status'],
            'lang_dir_ok' => youngo_public_loc_qa_html_has_lang_dir($html, 'ar', 'rtl'),
            'has_arabic_text' => youngo_public_loc_qa_has_arabic($text),
            'no_arabic_translated_ui_usage' => !youngo_public_loc_qa_has_bad_arabic_translated_ui_usage($html),
            'no_payment_cta' => !youngo_public_loc_qa_has_forbidden_payment_cta($html),
            'no_ar_compat_canonical_links' => !youngo_public_loc_qa_contains_ar_compat_canonical_link($html),
        );
        $httpSummary[$path] = $checks;
        youngo_public_loc_qa_assert($failures, $checks['status'] === 200, $path . ' did not return HTTP 200.');
        youngo_public_loc_qa_assert($failures, $checks['lang_dir_ok'], $path . ' did not render lang=ar dir=rtl.');
        youngo_public_loc_qa_assert($failures, $checks['has_arabic_text'], $path . ' did not include Arabic visible text.');
        youngo_public_loc_qa_assert($failures, $checks['no_arabic_translated_ui_usage'], $path . ' rendered arabic_translated outside the allowed course-content marker.');
        youngo_public_loc_qa_assert($failures, $checks['no_payment_cta'], $path . ' rendered a payment/checkout/Paymob CTA.');
        youngo_public_loc_qa_assert($failures, $checks['no_ar_compat_canonical_links'], $path . ' generated /ar as a canonical public link.');
    }

    foreach ($arabicRedirectPages as $path => $expectedRefreshUrl) {
        $response = youngo_public_loc_qa_http_get($path);
        $checks = array(
            'status' => $response['status'],
            'empty_body' => trim($response['html']) === '',
            'refresh_target' => $response['refresh'],
            'refresh_preserves_arabic_default' => strpos($response['refresh'], 'url=' . $expectedRefreshUrl) !== false,
        );
        $httpSummary[$path] = $checks;
        youngo_public_loc_qa_assert($failures, $checks['status'] === 200, $path . ' redirect shell did not return HTTP 200.');
        youngo_public_loc_qa_assert($failures, $checks['empty_body'], $path . ' unexpectedly rendered a body during unauthenticated redirect.');
        youngo_public_loc_qa_assert($failures, $checks['refresh_preserves_arabic_default'], $path . ' did not refresh to the Arabic/default target.');
    }

    foreach ($arCompatPages as $path) {
        $response = youngo_public_loc_qa_http_get($path);
        $html = $response['html'];
        $checks = array(
            'status' => $response['status'],
            'lang_dir_ok' => youngo_public_loc_qa_html_has_lang_dir($html, 'ar', 'rtl'),
            'has_arabic_text' => youngo_public_loc_qa_has_arabic(youngo_public_loc_qa_text($html)),
            'no_payment_cta' => !youngo_public_loc_qa_has_forbidden_payment_cta($html),
            'no_ar_compat_canonical_links' => !youngo_public_loc_qa_contains_ar_compat_canonical_link($html),
        );
        $httpSummary[$path] = $checks;
        youngo_public_loc_qa_assert($failures, $checks['status'] === 200, $path . ' did not return HTTP 200.');
        youngo_public_loc_qa_assert($failures, $checks['lang_dir_ok'], $path . ' did not render lang=ar dir=rtl.');
        youngo_public_loc_qa_assert($failures, $checks['has_arabic_text'], $path . ' did not include Arabic visible text.');
        youngo_public_loc_qa_assert($failures, $checks['no_payment_cta'], $path . ' rendered a payment/checkout/Paymob CTA.');
        youngo_public_loc_qa_assert($failures, $checks['no_ar_compat_canonical_links'], $path . ' generated /ar as a canonical public link.');
    }

    foreach ($englishPages as $path) {
        $response = youngo_public_loc_qa_http_get($path);
        $html = $response['html'];
        $text = youngo_public_loc_qa_text($html);
        $checks = array(
            'status' => $response['status'],
            'lang_dir_ok' => youngo_public_loc_qa_html_has_lang_dir($html, 'en', 'ltr'),
            'no_unexpected_arabic_text' => !youngo_public_loc_qa_has_unexpected_arabic_on_english($text),
            'no_arabic_translated_ui_usage' => !youngo_public_loc_qa_has_bad_arabic_translated_ui_usage($html),
            'no_payment_cta' => !youngo_public_loc_qa_has_forbidden_payment_cta($html),
            'no_ar_compat_canonical_links' => !youngo_public_loc_qa_contains_ar_compat_canonical_link($html),
        );
        $httpSummary[$path] = $checks;
        youngo_public_loc_qa_assert($failures, $checks['status'] === 200, $path . ' did not return HTTP 200.');
        youngo_public_loc_qa_assert($failures, $checks['lang_dir_ok'], $path . ' did not render lang=en dir=ltr.');
        youngo_public_loc_qa_assert($failures, $checks['no_unexpected_arabic_text'], $path . ' rendered unexpected Arabic text on an English page.');
        youngo_public_loc_qa_assert($failures, $checks['no_arabic_translated_ui_usage'], $path . ' rendered arabic_translated outside the allowed course-content marker.');
        youngo_public_loc_qa_assert($failures, $checks['no_payment_cta'], $path . ' rendered a payment/checkout/Paymob CTA.');
        youngo_public_loc_qa_assert($failures, $checks['no_ar_compat_canonical_links'], $path . ' generated /ar as a canonical public link.');
    }

    foreach ($englishRedirectPages as $path => $expectedRefreshUrl) {
        $response = youngo_public_loc_qa_http_get($path);
        $checks = array(
            'status' => $response['status'],
            'empty_body' => trim($response['html']) === '',
            'refresh_target' => $response['refresh'],
            'refresh_preserves_english' => strpos($response['refresh'], 'url=' . $expectedRefreshUrl) !== false,
        );
        $httpSummary[$path] = $checks;
        youngo_public_loc_qa_assert($failures, $checks['status'] === 200, $path . ' redirect shell did not return HTTP 200.');
        youngo_public_loc_qa_assert($failures, $checks['empty_body'], $path . ' unexpectedly rendered a body during unauthenticated redirect.');
        youngo_public_loc_qa_assert($failures, $checks['refresh_preserves_english'], $path . ' did not refresh to the English target.');
    }

    youngo_public_loc_qa_print('HTTP localization checks', $httpSummary);

    $mysqli = youngo_public_loc_qa_connect($db, $active_group);
    $subscriptionChecks = array(
        'subscription_translation_rows' => (int) youngo_public_loc_qa_scalar($mysqli, "SELECT COUNT(*) FROM youngo_subscription_plan_translations"),
        'subscription_arabic_rows' => (int) youngo_public_loc_qa_scalar($mysqli, "SELECT COUNT(*) FROM youngo_subscription_plan_translations WHERE language_code = 'arabic'"),
        'subscription_english_rows' => (int) youngo_public_loc_qa_scalar($mysqli, "SELECT COUNT(*) FROM youngo_subscription_plan_translations WHERE language_code = 'english'"),
        'subscription_arabic_translated_rows' => (int) youngo_public_loc_qa_scalar($mysqli, "SELECT COUNT(*) FROM youngo_subscription_plan_translations WHERE language_code = 'arabic_translated'"),
        'public_plan_count' => (int) youngo_public_loc_qa_scalar($mysqli, "SELECT COUNT(*) FROM youngo_subscription_plans WHERE is_active = 1 AND is_purchasable = 1 AND currency = 'EGP' AND price > 0 AND duration_days > 0 AND archived_at IS NULL"),
    );
    $mysqli->close();

    youngo_public_loc_qa_print('Subscription localization checks', $subscriptionChecks);
    youngo_public_loc_qa_assert($failures, $subscriptionChecks['subscription_translation_rows'] === 6, 'Expected 6 subscription translation rows.');
    youngo_public_loc_qa_assert($failures, $subscriptionChecks['subscription_arabic_rows'] === 3, 'Expected 3 Arabic subscription translation rows.');
    youngo_public_loc_qa_assert($failures, $subscriptionChecks['subscription_english_rows'] === 3, 'Expected 3 English subscription translation rows.');
    youngo_public_loc_qa_assert($failures, $subscriptionChecks['subscription_arabic_translated_rows'] === 0, 'arabic_translated subscription rows must not exist.');
    youngo_public_loc_qa_assert($failures, $subscriptionChecks['public_plan_count'] === 3, 'Expected 3 public subscription plans.');
} catch (Throwable $exception) {
    $failures[] = $exception->getMessage();
}

if (!empty($failures)) {
    youngo_public_loc_qa_print('Failures', $failures);
    exit(1);
}

youngo_public_loc_qa_print('Result', 'PASS: full public localization QA diagnostic checks passed read-only.');
exit(0);
