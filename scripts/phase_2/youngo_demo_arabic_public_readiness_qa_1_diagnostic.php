<?php
/**
 * DEMO.ARABIC.PUBLIC.READINESS.QA.1 diagnostic.
 *
 * Read-only public demo QA for Arabic/default, /en, and /ar compatibility
 * routes. It does not write DB rows, seed phrases, import language packs,
 * change routes, touch payment/Paymob behavior, or create checkout/order rows.
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

function ydaprq1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function ydaprq1_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function ydaprq1_connect($db, $activeGroup)
{
    if (!isset($db[$activeGroup])) {
        throw new RuntimeException('Active database group was not found.');
    }

    $config = $db[$activeGroup];
    $mysqli = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
    if ($mysqli->connect_errno) {
        throw new RuntimeException('DB connection failed without exposing credentials.');
    }

    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

function ydaprq1_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    $stmt->close();

    return isset($row[0]) && (int) $row[0] > 0;
}

function ydaprq1_query($mysqli, $sql)
{
    if (preg_match('/^\s*(INSERT|UPDATE|DELETE|ALTER|DROP|CREATE|TRUNCATE|REPLACE|GRANT|REVOKE|LOAD|CALL|OPTIMIZE|ANALYZE)\b/i', $sql)) {
        throw new RuntimeException('Blocked non-read SQL in diagnostic.');
    }

    $result = $mysqli->query($sql);
    if (!$result) {
        throw new RuntimeException('DB read failed without exposing credentials.');
    }

    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();

    return $rows;
}

function ydaprq1_scalar($mysqli, $sql)
{
    $rows = ydaprq1_query($mysqli, $sql);
    if (empty($rows)) {
        return null;
    }

    $row = $rows[0];
    return reset($row);
}

function ydaprq1_table_fingerprint($mysqli, $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !ydaprq1_table_exists($mysqli, $table)) {
        return array('exists' => false);
    }

    $count = ydaprq1_scalar($mysqli, 'SELECT COUNT(*) FROM `' . $table . '`');
    $checksumRows = ydaprq1_query($mysqli, 'CHECKSUM TABLE `' . $table . '`');
    $checksum = isset($checksumRows[0]['Checksum']) ? $checksumRows[0]['Checksum'] : null;

    return array(
        'exists' => true,
        'count' => (int) $count,
        'checksum' => $checksum,
    );
}

function ydaprq1_snapshot($mysqli, $tables)
{
    $snapshot = array();
    foreach ($tables as $table) {
        $snapshot[$table] = ydaprq1_table_fingerprint($mysqli, $table);
    }

    return $snapshot;
}

function ydaprq1_http_get($url)
{
    $headers = array();
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'ignore_errors' => true,
            'timeout' => 20,
            'header' => "User-Agent: YounGoDemoArabicPublicReadinessQa/1.0\r\n",
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
        'url' => $url,
        'status' => $status,
        'headers' => $headers,
        'html' => $html === false ? '' : $html,
    );
}

function ydaprq1_html_has_lang_dir($html, $lang, $dir)
{
    return preg_match('/<html\b[^>]*\blang=["\']' . preg_quote($lang, '/') . '["\'][^>]*\bdir=["\']' . preg_quote($dir, '/') . '["\']/i', $html) === 1
        || preg_match('/<html\b[^>]*\bdir=["\']' . preg_quote($dir, '/') . '["\'][^>]*\blang=["\']' . preg_quote($lang, '/') . '["\']/i', $html) === 1;
}

function ydaprq1_visible_text($html)
{
    $html = preg_replace('#<head\b[^>]*>.*?</head>#is', ' ', $html);
    $html = preg_replace('#<(script|style|svg|noscript)\b[^>]*>.*?</\1>#is', ' ', $html);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);

    return trim($text);
}

function ydaprq1_has_disallowed_arabic_translated_ui_code($html)
{
    $html = preg_replace('/<input\b[^>]*\bname=["\']language["\'][^>]*\bvalue=["\']arabic_translated["\'][^>]*>/i', ' ', $html);

    return stripos($html, 'arabic_translated') !== false;
}

function ydaprq1_has_arabic($text)
{
    return preg_match('/\p{Arabic}/u', (string) $text) === 1;
}

function ydaprq1_corrupt_markers($text)
{
    return array(
        'repeated_question_marks' => preg_match('/\?{4,}/', $text) === 1,
        'mojibake_markers' => preg_match('/(?:Ø|Ù|Û|Ã|Â|�)/u', $text) === 1,
    );
}

function ydaprq1_contains_any($text, $needles)
{
    foreach ($needles as $needle) {
        if (stripos($text, $needle) !== false) {
            return $needle;
        }
    }

    return false;
}

function ydaprq1_has_forbidden_payment_surface($html, $text)
{
    $linkPatterns = array(
        '#<(?:a|form)\b[^>]*(?:href|action)=["\'][^"\']*(?:paymob|payment/paymob|home/course_payment|home/shopping_cart|shopping_cart|youngo/checkout|/checkout|/order|coupon)[^"\']*["\']#i',
    );
    $ctaPatterns = array(
        '/\b(?:Buy Now|Add to cart|Pay now|Pay with Paymob|Subscribe now|Go to checkout|Checkout now)\b/i',
        '/(?:باي موب|ادفع الآن|اشتر الآن|أضف إلى السلة|إتمام الشراء الآن)/u',
    );

    foreach ($linkPatterns as $pattern) {
        if (preg_match($pattern, $html) === 1) {
            return true;
        }
    }

    foreach ($ctaPatterns as $pattern) {
        if (preg_match($pattern, $text) === 1) {
            return true;
        }
    }

    return false;
}

function ydaprq1_extract_hrefs($html)
{
    $hrefs = array();
    if (preg_match_all('/\bhref=["\']([^"\']+)["\']/i', $html, $matches)) {
        $hrefs = $matches[1];
    }

    return $hrefs;
}

function ydaprq1_has_ar_compat_generated_link($hrefs)
{
    foreach ($hrefs as $href) {
        if (preg_match('#^(?:https?://localhost)?/ar(?:/|$)#i', $href) === 1) {
            return true;
        }
    }

    return false;
}

function ydaprq1_has_en_prefixed_link($hrefs)
{
    foreach ($hrefs as $href) {
        if (preg_match('#^(?:https?://localhost)?/en(?:/|$)#i', $href) === 1) {
            return true;
        }
    }

    return false;
}

function ydaprq1_language_counts($mysqli, $table)
{
    if (!ydaprq1_table_exists($mysqli, $table)) {
        return array('exists' => false);
    }

    $rows = ydaprq1_query($mysqli, 'SELECT language_code, COUNT(*) AS row_count FROM `' . $table . '` GROUP BY language_code ORDER BY language_code ASC');
    $counts = array('exists' => true);
    foreach ($rows as $row) {
        $counts[$row['language_code']] = (int) $row['row_count'];
    }

    $counts['invalid'] = (int) ydaprq1_scalar($mysqli, "SELECT COUNT(*) FROM `" . $table . "` WHERE language_code NOT IN ('english', 'arabic')");
    $counts['arabic_translated'] = (int) ydaprq1_scalar($mysqli, "SELECT COUNT(*) FROM `" . $table . "` WHERE language_code = 'arabic_translated'");

    return $counts;
}

try {
    $mysqli = ydaprq1_connect($db, $active_group);
    $baseUrl = 'http://localhost';

    $contentAndProtectedTables = array(
        'language',
        'blogs',
        'blog_category',
        'youngo_blog_translations',
        'youngo_blog_category_translations',
        'youngo_subscription_plans',
        'youngo_subscription_plan_translations',
        'youngo_course_translations',
        'youngo_category_translations',
        'youngo_section_translations',
        'youngo_lesson_translations',
        'payment',
        'enrol',
        'cart',
        'orders',
        'youngo_checkout_orders',
        'youngo_coupon_usages',
        'youngo_coupon_subscription_plans',
        'youngo_coupon_courses',
        'youngo_course_access',
        'youngo_user_subscriptions',
        'youngo_manual_grants',
        'watch_histories',
        'course_progress',
    );

    $sessionTable = ydaprq1_table_exists($mysqli, 'ci_sessions')
        ? array('ci_sessions' => ydaprq1_table_fingerprint($mysqli, 'ci_sessions'))
        : array('ci_sessions' => array('exists' => false));
    $beforeSnapshot = ydaprq1_snapshot($mysqli, $contentAndProtectedTables);

    $arabicUiEnglishMarkers = array(
        'Course discovery',
        'Explore YounGo courses',
        'Course results',
        'Apply filters',
        'Sort by',
        'Email address',
        'Forgot password',
        'Sign up',
        'Log in',
        'My wishlist',
        'Saved courses',
        'Subscription plans',
        'Family access plans',
        'Latest articles',
        'Helpful notes for families',
        'Get in touch',
        'Contact us',
        'Read more',
        'View details',
        'Access locked',
        'Use a student account',
    );

    $pages = array(
        'arabic_default' => array(
            array('path' => '/', 'lang' => 'ar', 'dir' => 'rtl'),
            array('path' => '/login', 'lang' => 'ar', 'dir' => 'rtl'),
            array('path' => '/sign_up', 'lang' => 'ar', 'dir' => 'rtl'),
            array('path' => '/login/forgot_password_request', 'lang' => 'ar', 'dir' => 'rtl'),
            array('path' => '/home/courses', 'lang' => 'ar', 'dir' => 'rtl'),
            array('path' => '/home/course/robotics-and-ai-explorers/9', 'lang' => 'ar', 'dir' => 'rtl'),
            array('path' => '/home/my_wishlist', 'lang' => 'ar', 'dir' => 'rtl', 'allow_redirect' => true),
            array('path' => '/subscriptions', 'lang' => 'ar', 'dir' => 'rtl'),
            array('path' => '/home/blog', 'lang' => 'ar', 'dir' => 'rtl'),
            array('path' => '/blog/details/helping-children-start-their-coding-journey/1', 'lang' => 'ar', 'dir' => 'rtl'),
            array('path' => '/home/contact', 'lang' => 'ar', 'dir' => 'rtl'),
        ),
        'english' => array(
            array('path' => '/en', 'lang' => 'en', 'dir' => 'ltr'),
            array('path' => '/en/login', 'lang' => 'en', 'dir' => 'ltr'),
            array('path' => '/en/sign-up', 'lang' => 'en', 'dir' => 'ltr'),
            array('path' => '/en/home/courses', 'lang' => 'en', 'dir' => 'ltr'),
            array('path' => '/en/home/course/robotics-and-ai-explorers/9', 'lang' => 'en', 'dir' => 'ltr'),
            array('path' => '/en/subscriptions', 'lang' => 'en', 'dir' => 'ltr'),
            array('path' => '/en/home/blog', 'lang' => 'en', 'dir' => 'ltr'),
            array('path' => '/en/blog/details/helping-children-start-their-coding-journey/1', 'lang' => 'en', 'dir' => 'ltr'),
            array('path' => '/en/home/contact', 'lang' => 'en', 'dir' => 'ltr'),
        ),
        'ar_compatibility' => array(
            array('path' => '/ar', 'lang' => 'ar', 'dir' => 'rtl'),
            array('path' => '/ar/home/courses', 'lang' => 'ar', 'dir' => 'rtl'),
            array('path' => '/ar/subscriptions', 'lang' => 'ar', 'dir' => 'rtl'),
            array('path' => '/ar/home/blog', 'lang' => 'ar', 'dir' => 'rtl'),
        ),
    );

    $matrix = array();
    foreach ($pages as $group => $groupPages) {
        $matrix[$group] = array();
        foreach ($groupPages as $page) {
            $response = ydaprq1_http_get($baseUrl . $page['path']);
            $status = $response['status'];
            $html = $response['html'];
            $text = ydaprq1_visible_text($html);
            $hrefs = ydaprq1_extract_hrefs($html);
            $corrupt = ydaprq1_corrupt_markers($text);
            $isRedirect = in_array($status, array(301, 302, 303, 307, 308), true);
            $statusOk = $status === 200 || (!empty($page['allow_redirect']) && $isRedirect);
            $hasLangDir = $status === 200 && ydaprq1_html_has_lang_dir($html, $page['lang'], $page['dir']);
            $hasArabic = ydaprq1_has_arabic($text);
            $forbiddenPayment = ydaprq1_has_forbidden_payment_surface($html, $text);
            $englishMarker = $group === 'english' ? false : ydaprq1_contains_any($text, $arabicUiEnglishMarkers);
            $englishTextWithoutSwitcher = preg_replace('/(?:عربي|العربية|English|الإنجليزية)/u', ' ', $text);
            $hasUnexpectedArabicOnEnglish = $group === 'english' && ydaprq1_has_arabic($englishTextWithoutSwitcher);
            $hasArCompatLink = ydaprq1_has_ar_compat_generated_link($hrefs);
            $hasEnPrefixedLink = ydaprq1_has_en_prefixed_link($hrefs);
            $hasArabicTranslatedUiCode = ydaprq1_has_disallowed_arabic_translated_ui_code($html);

            $checks = array(
                'status' => $status,
                'status_ok' => $statusOk,
                'lang_dir_ok' => $hasLangDir,
                'has_arabic_copy' => $group === 'english' ? null : $hasArabic,
                'has_no_repeated_question_marks' => !$corrupt['repeated_question_marks'],
                'has_no_mojibake_markers' => !$corrupt['mojibake_markers'],
                'high_visibility_english_ui_marker' => $englishMarker,
                'has_unexpected_arabic_on_english' => $hasUnexpectedArabicOnEnglish,
                'has_no_payment_checkout_cta_or_link' => !$forbiddenPayment,
                'ar_generated_links_unprefixed' => $group === 'english' ? null : !$hasArCompatLink,
                'en_generated_links_prefixed' => $hasEnPrefixedLink,
                'has_no_arabic_translated_ui_code' => !$hasArabicTranslatedUiCode,
            );

            $matrix[$group][$page['path']] = $checks;

            ydaprq1_assert($failures, $statusOk, $group . ' ' . $page['path'] . ' returned unexpected HTTP status ' . $status . '.');
            if ($status === 200) {
                ydaprq1_assert($failures, $hasLangDir, $group . ' ' . $page['path'] . ' did not render expected html lang/dir.');
                ydaprq1_assert($failures, !$corrupt['repeated_question_marks'], $group . ' ' . $page['path'] . ' rendered repeated question-mark placeholders.');
                ydaprq1_assert($failures, !$corrupt['mojibake_markers'], $group . ' ' . $page['path'] . ' rendered mojibake/corrupt Arabic markers.');
                ydaprq1_assert($failures, !$forbiddenPayment, $group . ' ' . $page['path'] . ' rendered a forbidden payment/checkout/Paymob CTA or link.');
                ydaprq1_assert($failures, !$hasArabicTranslatedUiCode, $group . ' ' . $page['path'] . ' rendered arabic_translated as UI code.');
                ydaprq1_assert($failures, $hasEnPrefixedLink, $group . ' ' . $page['path'] . ' did not render an /en-prefixed public link.');
                if ($group === 'english') {
                    ydaprq1_assert($failures, !$hasUnexpectedArabicOnEnglish, $group . ' ' . $page['path'] . ' rendered unexpected Arabic text outside the language switcher.');
                } else {
                    ydaprq1_assert($failures, $hasArabic, $group . ' ' . $page['path'] . ' did not render Arabic visible copy.');
                    ydaprq1_assert($failures, $englishMarker === false, $group . ' ' . $page['path'] . ' rendered high-visibility English UI marker: ' . $englishMarker);
                    ydaprq1_assert($failures, !$hasArCompatLink, $group . ' ' . $page['path'] . ' generated /ar as a canonical public link.');
                }
            }
        }
    }

    ydaprq1_print('HTTP QA matrix', $matrix);

    $translationChecks = array(
        'youngo_subscription_plan_translations' => ydaprq1_language_counts($mysqli, 'youngo_subscription_plan_translations'),
        'youngo_blog_translations' => ydaprq1_language_counts($mysqli, 'youngo_blog_translations'),
        'youngo_blog_category_translations' => ydaprq1_language_counts($mysqli, 'youngo_blog_category_translations'),
    );

    ydaprq1_print('Translation row checks', $translationChecks);
    ydaprq1_assert($failures, !empty($translationChecks['youngo_subscription_plan_translations']['exists']), 'Subscription plan translation table is missing.');
    ydaprq1_assert($failures, !empty($translationChecks['youngo_subscription_plan_translations']['english']) && $translationChecks['youngo_subscription_plan_translations']['english'] === 3, 'Expected 3 English subscription plan translation rows.');
    ydaprq1_assert($failures, !empty($translationChecks['youngo_subscription_plan_translations']['arabic']) && $translationChecks['youngo_subscription_plan_translations']['arabic'] === 3, 'Expected 3 Arabic subscription plan translation rows.');
    ydaprq1_assert($failures, $translationChecks['youngo_subscription_plan_translations']['invalid'] === 0, 'Invalid subscription plan translation language rows found.');
    ydaprq1_assert($failures, !empty($translationChecks['youngo_blog_translations']['exists']), 'Blog translation table is missing.');
    ydaprq1_assert($failures, !empty($translationChecks['youngo_blog_translations']['english']) && $translationChecks['youngo_blog_translations']['english'] === 4, 'Expected 4 English Blog translation rows.');
    ydaprq1_assert($failures, !empty($translationChecks['youngo_blog_translations']['arabic']) && $translationChecks['youngo_blog_translations']['arabic'] === 4, 'Expected 4 Arabic Blog translation rows.');
    ydaprq1_assert($failures, $translationChecks['youngo_blog_translations']['invalid'] === 0, 'Invalid Blog translation language rows found.');
    ydaprq1_assert($failures, !empty($translationChecks['youngo_blog_category_translations']['exists']), 'Blog category translation table is missing.');
    ydaprq1_assert($failures, !empty($translationChecks['youngo_blog_category_translations']['english']) && $translationChecks['youngo_blog_category_translations']['english'] === 3, 'Expected 3 English Blog category translation rows.');
    ydaprq1_assert($failures, !empty($translationChecks['youngo_blog_category_translations']['arabic']) && $translationChecks['youngo_blog_category_translations']['arabic'] === 3, 'Expected 3 Arabic Blog category translation rows.');
    ydaprq1_assert($failures, $translationChecks['youngo_blog_category_translations']['invalid'] === 0, 'Invalid Blog category translation language rows found.');

    $afterSnapshot = ydaprq1_snapshot($mysqli, $contentAndProtectedTables);
    $afterSessionTable = ydaprq1_table_exists($mysqli, 'ci_sessions')
        ? array('ci_sessions' => ydaprq1_table_fingerprint($mysqli, 'ci_sessions'))
        : array('ci_sessions' => array('exists' => false));

    $dbDrift = array();
    foreach ($beforeSnapshot as $table => $before) {
        if ($before !== $afterSnapshot[$table]) {
            $dbDrift[$table] = array('before' => $before, 'after' => $afterSnapshot[$table]);
        }
    }

    ydaprq1_print('DB write guard', array(
        'content_and_protected_tables_unchanged' => empty($dbDrift),
        'changed_content_or_protected_tables' => array_keys($dbDrift),
        'ci_sessions_observed_before' => $sessionTable['ci_sessions'],
        'ci_sessions_observed_after' => $afterSessionTable['ci_sessions'],
        'ci_sessions_note' => 'Observed separately because this app uses the database session driver for HTTP rendering.',
    ));
    ydaprq1_assert($failures, empty($dbDrift), 'Content/protected DB table drift detected during read-only public QA.');

    if (!empty($failures)) {
        ydaprq1_print('Failures', $failures);
        exit(1);
    }

    ydaprq1_print('Result', 'PASS: Arabic public demo-readiness QA diagnostic passed read-only content/protected-table checks.');
    exit(0);
} catch (Throwable $e) {
    ydaprq1_print('Fatal error', $e->getMessage());
    exit(1);
}
