<?php
/**
 * LANGUAGE.AUTH.PAGE_TITLE.ARABIC.WIRE.1 diagnostic.
 *
 * Read-only verification for localized YounGo public auth browser titles.
 * It does not submit forms, write DB rows, seed phrases, change routes, or
 * touch auth/security/session/payment behavior.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$root = dirname(__DIR__, 2);
chdir($root);

$failures = array();

function ylaptaw1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function ylaptaw1_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function ylaptaw1_http_get($url)
{
    $headers = array();
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'ignore_errors' => true,
            'timeout' => 20,
            'header' => "User-Agent: YounGoAuthTitleQa/1.0\r\n",
        ),
    ));

    $html = @file_get_contents($url, false, $context);
    if (isset($http_response_header) && is_array($http_response_header)) {
        $headers = $http_response_header;
    }

    $status = 0;
    foreach ($headers as $header) {
        if (preg_match('#^HTTP/\S+\s+([0-9]{3})#', $header, $matches)) {
            $status = (int) $matches[1];
            break;
        }
    }

    return array(
        'status' => $status,
        'html' => $html === false ? '' : $html,
        'headers' => $headers,
    );
}

function ylaptaw1_title($html)
{
    if (preg_match('#<title>(.*?)</title>#is', $html, $matches) === 1) {
        return trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    return '';
}

function ylaptaw1_html_has_lang_dir($html, $lang, $dir)
{
    return preg_match('/<html\b[^>]*\blang=["\']' . preg_quote($lang, '/') . '["\'][^>]*\bdir=["\']' . preg_quote($dir, '/') . '["\']/i', $html) === 1
        || preg_match('/<html\b[^>]*\bdir=["\']' . preg_quote($dir, '/') . '["\'][^>]*\blang=["\']' . preg_quote($lang, '/') . '["\']/i', $html) === 1;
}

function ylaptaw1_visible_text($html)
{
    $html = preg_replace('#<head\b[^>]*>.*?</head>#is', ' ', $html);
    $html = preg_replace('#<(script|style|svg|noscript)\b[^>]*>.*?</\1>#is', ' ', $html);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);

    return trim($text);
}

function ylaptaw1_has_arabic($value)
{
    return preg_match('/\p{Arabic}/u', (string) $value) === 1;
}

function ylaptaw1_form_actions($html)
{
    $actions = array();
    if (preg_match_all('/<form\b[^>]*\baction=["\']([^"\']*)["\']/i', $html, $matches)) {
        $actions = $matches[1];
    }

    return $actions;
}

function ylaptaw1_has_action($actions, $expectedSuffix)
{
    foreach ($actions as $action) {
        if (preg_match('#' . preg_quote($expectedSuffix, '#') . '$#', $action) === 1) {
            return true;
        }
    }

    return false;
}

function ylaptaw1_has_forbidden_payment_surface($html, $text)
{
    if (preg_match('#<(?:a|form)\b[^>]*(?:href|action)=["\'][^"\']*(?:paymob|payment/paymob|home/course_payment|home/shopping_cart|shopping_cart|youngo/checkout|/checkout|/order|coupon)[^"\']*["\']#i', $html) === 1) {
        return true;
    }

    return preg_match('/\b(?:Buy Now|Add to cart|Pay now|Pay with Paymob|Subscribe now|Go to checkout|Checkout now)\b/i', $text) === 1
        || preg_match('/(?:باي موب|ادفع الآن|اشتر الآن|أضف إلى السلة|إتمام الشراء الآن)/u', $text) === 1;
}

try {
    $baseUrl = 'http://localhost';
    $pages = array(
        '/login' => array(
            'lang' => 'ar',
            'dir' => 'rtl',
            'title_language' => 'arabic',
            'must_not_contain_title' => 'Login |',
            'expected_action' => 'login/validate_login',
        ),
        '/sign_up' => array(
            'lang' => 'ar',
            'dir' => 'rtl',
            'title_language' => 'arabic',
            'must_not_contain_title' => 'Sign up |',
            'expected_action' => 'login/register',
        ),
        '/login/forgot_password_request' => array(
            'lang' => 'ar',
            'dir' => 'rtl',
            'title_language' => 'arabic',
            'must_not_contain_title' => 'Forgot password |',
            'expected_action' => 'login/forgot_password/frontend',
        ),
        '/en/login' => array(
            'lang' => 'en',
            'dir' => 'ltr',
            'title_language' => 'english',
            'must_contain_title' => 'Login |',
            'expected_action' => 'login/validate_login',
        ),
        '/en/sign-up' => array(
            'lang' => 'en',
            'dir' => 'ltr',
            'title_language' => 'english',
            'must_contain_title' => 'Sign up |',
            'expected_action' => 'login/register',
        ),
        '/en/login/forgot_password_request' => array(
            'lang' => 'en',
            'dir' => 'ltr',
            'title_language' => 'english',
            'must_contain_title' => 'Forgot password |',
            'expected_action' => 'login/forgot_password/frontend',
        ),
        '/ar/login' => array(
            'lang' => 'ar',
            'dir' => 'rtl',
            'title_language' => 'arabic',
            'must_not_contain_title' => 'Login |',
            'expected_action' => 'login/validate_login',
        ),
        '/ar/sign-up' => array(
            'lang' => 'ar',
            'dir' => 'rtl',
            'title_language' => 'arabic',
            'must_not_contain_title' => 'Sign up |',
            'expected_action' => 'login/register',
        ),
        '/ar/login/forgot_password_request' => array(
            'lang' => 'ar',
            'dir' => 'rtl',
            'title_language' => 'arabic',
            'must_not_contain_title' => 'Forgot password |',
            'expected_action' => 'login/forgot_password/frontend',
        ),
    );

    $matrix = array();
    foreach ($pages as $path => $expectation) {
        $response = ylaptaw1_http_get($baseUrl . $path);
        $html = $response['html'];
        $title = ylaptaw1_title($html);
        $text = ylaptaw1_visible_text($html);
        $actions = ylaptaw1_form_actions($html);
        $titleHasArabic = ylaptaw1_has_arabic($title);
        $paymentSurface = ylaptaw1_has_forbidden_payment_surface($html, $text);
        $hasArabicTranslated = stripos($html, 'arabic_translated') !== false;

        $matrix[$path] = array(
            'status' => $response['status'],
            'title' => $title,
            'lang_dir_ok' => ylaptaw1_html_has_lang_dir($html, $expectation['lang'], $expectation['dir']),
            'title_has_arabic' => $titleHasArabic,
            'expected_form_action_present' => ylaptaw1_has_action($actions, $expectation['expected_action']),
            'has_no_payment_checkout_cta_or_link' => !$paymentSurface,
            'has_no_arabic_translated_ui_usage' => !$hasArabicTranslated,
        );

        ylaptaw1_assert($failures, $response['status'] === 200, $path . ' did not return HTTP 200.');
        ylaptaw1_assert($failures, $matrix[$path]['lang_dir_ok'], $path . ' did not render expected lang/dir.');
        ylaptaw1_assert($failures, $matrix[$path]['expected_form_action_present'], $path . ' did not render expected auth form action.');
        ylaptaw1_assert($failures, !$paymentSurface, $path . ' rendered a forbidden payment/checkout/Paymob surface.');
        ylaptaw1_assert($failures, !$hasArabicTranslated, $path . ' rendered arabic_translated as UI usage.');

        if ($expectation['title_language'] === 'arabic') {
            ylaptaw1_assert($failures, $titleHasArabic, $path . ' title does not contain Arabic script.');
            ylaptaw1_assert($failures, strpos($title, $expectation['must_not_contain_title']) === false, $path . ' title still contains English title marker.');
        } else {
            ylaptaw1_assert($failures, !$titleHasArabic, $path . ' English title contains Arabic script.');
            ylaptaw1_assert($failures, strpos($title, $expectation['must_contain_title']) !== false, $path . ' title does not contain expected English title marker.');
        }
    }

    ylaptaw1_print('HTTP auth title matrix', $matrix);

    $source = array(
        'Login.php' => file_get_contents($root . '/application/controllers/Login.php'),
        'Sign_up.php' => file_get_contents($root . '/application/controllers/Sign_up.php'),
        'routes.php' => file_get_contents($root . '/application/config/routes.php'),
    );
    $sourceChecks = array(
        'login_controller_uses_title_helper' => strpos($source['Login.php'], 'youngo_public_auth_page_title') !== false,
        'login_title_keys_covered' => strpos($source['Login.php'], "youngo_public_auth_page_title('login'") !== false
            && strpos($source['Login.php'], "youngo_public_auth_page_title('sign_up'") !== false
            && strpos($source['Login.php'], "youngo_public_auth_page_title('forgot_password'") !== false
            && strpos($source['Login.php'], "youngo_public_auth_page_title('change_password'") !== false
            && strpos($source['Login.php'], "youngo_public_auth_page_title('login_confirmation'") !== false,
        'signup_controller_uses_title_helper' => strpos($source['Sign_up.php'], 'youngo_public_auth_page_title') !== false,
        'signup_title_keys_covered' => strpos($source['Sign_up.php'], "youngo_public_auth_page_title('sign_up'") !== false
            && strpos($source['Sign_up.php'], "youngo_public_auth_page_title('verification_code'") !== false,
        'english_routes_preserved' => strpos($source['routes.php'], "\$route['en/login'] = 'login/index';") !== false
            && strpos($source['routes.php'], "\$route['en/sign-up'] = 'sign_up/index';") !== false
            && strpos($source['routes.php'], "\$route['en/login/forgot_password_request'] = 'login/forgot_password_request';") !== false,
        'arabic_compat_routes_preserved' => strpos($source['routes.php'], "\$route['ar/login'] = 'login/index';") !== false
            && strpos($source['routes.php'], "\$route['ar/sign-up'] = 'sign_up/index';") !== false
            && strpos($source['routes.php'], "\$route['ar/login/forgot_password_request'] = 'login/forgot_password_request';") !== false,
        'no_payment_paymob_source_reference_added' => stripos($source['Login.php'] . $source['Sign_up.php'], 'paymob') === false
            && stripos($source['Login.php'] . $source['Sign_up.php'], 'checkout') === false,
        'no_arabic_translated_source_usage' => stripos($source['Login.php'] . $source['Sign_up.php'], 'arabic_translated') === false,
    );

    ylaptaw1_print('Source checks', $sourceChecks);
    foreach ($sourceChecks as $label => $ok) {
        ylaptaw1_assert($failures, $ok, 'Source check failed: ' . $label);
    }

    if (!empty($failures)) {
        ylaptaw1_print('Failures', $failures);
        exit(1);
    }

    ylaptaw1_print('Result', 'PASS: public auth browser titles are localized by route language without auth action or payment-surface changes.');
    exit(0);
} catch (Throwable $e) {
    ylaptaw1_print('Fatal error', $e->getMessage());
    exit(1);
}
