<?php
/**
 * CONTENT.TRANSLATION.BLOG.DETAIL.WIRE.1 diagnostic.
 *
 * Read-only checks for YounGo Blog detail translation wiring. This script
 * uses source reads, SELECT-only DB reads, and public GET requests only. It
 * does not write DB rows, alter schema, edit Blog content, change routes, call
 * Paymob, or create checkout/order/access records.
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
$warnings = array();

function ybdw1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function ybdw1_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function ybdw1_connect($db, $active_group)
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

function ybdw1_query($mysqli, $sql)
{
    if (preg_match('/^\s*(INSERT|UPDATE|DELETE|ALTER|DROP|CREATE|TRUNCATE|REPLACE|GRANT|REVOKE|LOAD|CALL|OPTIMIZE|ANALYZE|SET)\b/i', $sql)) {
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

function ybdw1_scalar($mysqli, $sql)
{
    $rows = ybdw1_query($mysqli, $sql);
    if (empty($rows)) {
        return null;
    }

    $row = $rows[0];
    return reset($row);
}

function ybdw1_table_exists($mysqli, $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    return (int) ybdw1_scalar(
        $mysqli,
        "SELECT COUNT(*) AS row_count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '" . $mysqli->real_escape_string($table) . "'"
    ) > 0;
}

function ybdw1_source($root, $relative)
{
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    return is_file($path) ? file_get_contents($path) : '';
}

function ybdw1_has_arabic($value)
{
    return preg_match('/\p{Arabic}/u', (string) $value) === 1;
}

function ybdw1_plain_text($html)
{
    $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', (string) $html);
    $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $text);
    $text = preg_replace('/<[^>]+>/', ' ', $text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);

    return trim($text);
}

function ybdw1_clean_english_text($text)
{
    return str_replace(array('عربي', 'العربية'), '', (string) $text);
}

function ybdw1_http_get($path)
{
    $url = 'http://localhost/' . ltrim($path, '/');
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'ignore_errors' => true,
            'timeout' => 15,
            'header' => "User-Agent: YounGoBlogDetailWireDiagnostic/1.0\r\n",
        ),
    ));

    $html = @file_get_contents($url, false, $context);
    $headers = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : array();
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
        'html' => $html === false ? '' : $html,
    );
}

function ybdw1_has_lang_dir($html, $lang, $dir)
{
    return preg_match('/<html\b[^>]*\blang=["\']' . preg_quote($lang, '/') . '["\'][^>]*\bdir=["\']' . preg_quote($dir, '/') . '["\']/i', $html) === 1
        || preg_match('/<html\b[^>]*\bdir=["\']' . preg_quote($dir, '/') . '["\'][^>]*\blang=["\']' . preg_quote($lang, '/') . '["\']/i', $html) === 1;
}

function ybdw1_has_forbidden_payment_cta($html)
{
    $patterns = array(
        '#<(?:a|form)\b[^>]*(?:href|action)=["\'][^"\']*(?:payment/paymob|paymob|home/course_payment|home/shopping_cart|youngo/checkout|checkout|order|apply_coupon|remove_coupon)[^"\']*["\']#i',
        '#\b(?:Buy Now|Add to cart|Checkout|Pay now|Pay with Paymob|Subscribe now)\b#i',
    );

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, (string) $html) === 1) {
            return true;
        }
    }

    return false;
}

function ybdw1_slug($value)
{
    $value = trim(strip_tags(html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8')));
    $slug = preg_replace('/[^A-Za-z0-9]+/', '-', $value);
    $slug = strtolower(trim($slug, '-'));

    return $slug === '' ? 'blog' : $slug;
}

function ybdw1_contains_translated_title($html, $title)
{
    $title = trim(html_entity_decode((string) $title, ENT_QUOTES, 'UTF-8'));
    if ($title === '') {
        return false;
    }

    return strpos(ybdw1_plain_text($html), $title) !== false;
}

try {
    $mysqli = ybdw1_connect($db, $active_group);
    $blogControllerSource = ybdw1_source($root, 'application/controllers/Blog.php');
    $blogDetailsSource = ybdw1_source($root, 'application/views/frontend/youngo/blog_details.php');
    $blogListSource = ybdw1_source($root, 'application/views/frontend/youngo/blogs.php');
    $routeSource = ybdw1_source($root, 'application/config/routes.php');

    $sourceChecks = array(
        'controller_calls_localize_blog_details' => strpos($blogControllerSource, 'youngo_localize_blog_details') !== false,
        'controller_reuses_crud_blog_translation_method' => strpos($blogControllerSource, 'youngo_get_blog_translation') !== false,
        'controller_has_english_fallback_for_arabic' => strpos($blogControllerSource, "\$english_translation = \$this->youngo_get_blog_translation_candidate(\$blog_id, 'english')") !== false,
        'controller_sets_localized_page_title' => strpos($blogControllerSource, "\$page_data['page_title'] = \$isYoungoTheme && !empty(\$blog_details['title'])") !== false,
        'detail_view_uses_blog_details_row' => strpos($blogDetailsSource, "\$blog_details['description']") !== false && strpos($blogDetailsSource, '$youngo_blog_detail_title') !== false,
        'listing_already_reads_translation_table' => strpos($blogListSource, 'youngo_blog_translations') !== false,
        'no_arabic_translated_in_changed_blog_sources' => stripos($blogControllerSource . $blogDetailsSource, 'arabic_translated') === false,
    );
    ybdw1_print('Source wiring checks', $sourceChecks);
    foreach ($sourceChecks as $label => $ok) {
        ybdw1_assert($failures, $ok, 'Source check failed: ' . $label);
    }

    $tableChecks = array(
        'youngo_blog_translations_exists' => ybdw1_table_exists($mysqli, 'youngo_blog_translations'),
        'blogs_exists' => ybdw1_table_exists($mysqli, 'blogs'),
        'arabic_translated_rows' => ybdw1_table_exists($mysqli, 'youngo_blog_translations')
            ? (int) ybdw1_scalar($mysqli, "SELECT COUNT(*) FROM youngo_blog_translations WHERE language_code = 'arabic_translated'")
            : null,
        'english_rows' => ybdw1_table_exists($mysqli, 'youngo_blog_translations')
            ? (int) ybdw1_scalar($mysqli, "SELECT COUNT(*) FROM youngo_blog_translations WHERE language_code = 'english'")
            : null,
        'arabic_rows' => ybdw1_table_exists($mysqli, 'youngo_blog_translations')
            ? (int) ybdw1_scalar($mysqli, "SELECT COUNT(*) FROM youngo_blog_translations WHERE language_code = 'arabic'")
            : null,
    );
    ybdw1_print('Blog translation table checks', $tableChecks);
    ybdw1_assert($failures, $tableChecks['youngo_blog_translations_exists'], 'youngo_blog_translations table is missing.');
    ybdw1_assert($failures, $tableChecks['blogs_exists'], 'blogs table is missing.');
    ybdw1_assert($failures, $tableChecks['english_rows'] > 0, 'No English Blog translation rows detected.');
    ybdw1_assert($failures, $tableChecks['arabic_rows'] > 0, 'No Arabic Blog translation rows detected.');
    ybdw1_assert($failures, $tableChecks['arabic_translated_rows'] === 0, 'Blog translations must not use arabic_translated.');

    $rows = ybdw1_query(
        $mysqli,
        "SELECT b.blog_id, b.title AS canonical_title, b.description AS canonical_description, en.title AS english_title, en.description AS english_description, ar.title AS arabic_title, ar.description AS arabic_description
         FROM blogs b
         INNER JOIN youngo_blog_translations en ON en.blog_id = b.blog_id AND en.language_code = 'english'
         INNER JOIN youngo_blog_translations ar ON ar.blog_id = b.blog_id AND ar.language_code = 'arabic'
         WHERE b.status = 1 AND TRIM(en.title) <> '' AND TRIM(ar.title) <> ''
         ORDER BY b.blog_id ASC
         LIMIT 1"
    );
    ybdw1_assert($failures, !empty($rows), 'No published Blog row with both English and Arabic translations was detected.');
    $sample = !empty($rows) ? $rows[0] : array();
    ybdw1_print('Sample translated blog row', array(
        'blog_id' => isset($sample['blog_id']) ? (int) $sample['blog_id'] : 0,
        'english_title_detected' => !empty($sample['english_title']),
        'arabic_title_has_arabic' => isset($sample['arabic_title']) && ybdw1_has_arabic($sample['arabic_title']),
    ));

    if (!empty($sample)) {
        $blogId = (int) $sample['blog_id'];
        $defaultPath = 'blog/details/' . ybdw1_slug($sample['canonical_title']) . '/' . $blogId;
        $englishPath = 'en/blog/details/' . ybdw1_slug($sample['english_title']) . '/' . $blogId;
        $arCompatPath = 'ar/blog/details/' . ybdw1_slug($sample['arabic_title']) . '/' . $blogId;

        $httpTargets = array(
            'arabic_default_detail' => array('path' => $defaultPath, 'lang' => 'ar', 'dir' => 'rtl', 'title' => $sample['arabic_title'], 'expects_arabic' => true),
            'english_detail' => array('path' => $englishPath, 'lang' => 'en', 'dir' => 'ltr', 'title' => $sample['english_title'], 'expects_arabic' => false),
            'arabic_compat_detail' => array('path' => $arCompatPath, 'lang' => 'ar', 'dir' => 'rtl', 'title' => $sample['arabic_title'], 'expects_arabic' => true),
            'arabic_default_list' => array('path' => 'home/blog', 'lang' => 'ar', 'dir' => 'rtl', 'title' => $sample['arabic_title'], 'expects_arabic' => true),
            'english_list' => array('path' => 'en/home/blog', 'lang' => 'en', 'dir' => 'ltr', 'title' => $sample['english_title'], 'expects_arabic' => false),
        );

        $httpChecks = array();
        foreach ($httpTargets as $label => $target) {
            $response = ybdw1_http_get($target['path']);
            $html = $response['html'];
            $plainText = ybdw1_plain_text($html);
            $cleanEnglishText = ybdw1_clean_english_text($plainText);
            $hasArabic = ybdw1_has_arabic($cleanEnglishText);

            $httpChecks[$label] = array(
                'url' => $response['url'],
                'status' => $response['status'],
                'lang_dir_ok' => ybdw1_has_lang_dir($html, $target['lang'], $target['dir']),
                'contains_expected_title' => ybdw1_contains_translated_title($html, $target['title']),
                'has_arabic_text_after_switcher_cleanup' => $hasArabic,
                'no_forbidden_payment_cta' => !ybdw1_has_forbidden_payment_cta($html),
                'no_arabic_translated_visible' => stripos($html, 'arabic_translated') === false,
            );

            ybdw1_assert($failures, $response['status'] === 200, $label . ' did not return HTTP 200.');
            ybdw1_assert($failures, $httpChecks[$label]['lang_dir_ok'], $label . ' did not render expected lang/dir.');
            ybdw1_assert($failures, $httpChecks[$label]['contains_expected_title'], $label . ' did not contain the expected translated Blog title.');
            ybdw1_assert($failures, $httpChecks[$label]['no_forbidden_payment_cta'], $label . ' rendered a payment/checkout/Paymob CTA.');
            ybdw1_assert($failures, $httpChecks[$label]['no_arabic_translated_visible'], $label . ' rendered arabic_translated visibly.');
            if (!empty($target['expects_arabic'])) {
                ybdw1_assert($failures, $hasArabic, $label . ' did not render Arabic text.');
            } else {
                ybdw1_assert($failures, !$hasArabic, $label . ' rendered Arabic content on an English page after switcher cleanup.');
            }
        }
        ybdw1_print('HTTP Blog detail/list checks', $httpChecks);
    }

    $routeChecks = array(
        'english_blog_detail_route_exists' => strpos($routeSource, "\$route['en/blog/details/(:any)/(:num)']") !== false,
        'arabic_compat_blog_detail_route_exists' => strpos($routeSource, "\$route['ar/blog/details/(:any)/(:num)']") !== false,
        'routes_do_not_use_arabic_translated' => stripos($routeSource, 'arabic_translated') === false,
    );
    ybdw1_print('Route checks', $routeChecks);
    foreach ($routeChecks as $label => $ok) {
        ybdw1_assert($failures, $ok, 'Route check failed: ' . $label);
    }

    $changedFiles = array();
    exec('git -C ' . escapeshellarg($root) . ' diff --name-only', $changedFiles);
    $forbiddenChangedFiles = array();
    foreach ($changedFiles as $changedFile) {
        if (preg_match('#(?:paymob|payment|checkout|coupon|cart)#i', $changedFile)) {
            $forbiddenChangedFiles[] = $changedFile;
        }
    }

    $safetyChecks = array(
        'changed_files' => array_values(array_filter($changedFiles)),
        'forbidden_payment_or_checkout_changed_files' => $forbiddenChangedFiles,
        'no_payment_or_checkout_files_changed' => empty($forbiddenChangedFiles),
    );
    ybdw1_print('Payment/checkout safety checks', $safetyChecks);
    ybdw1_assert($failures, $safetyChecks['no_payment_or_checkout_files_changed'], 'Payment/checkout/Paymob/coupon/cart files changed unexpectedly.');

    $fallbackChecks = array(
        'source_has_field_level_candidate_loop' => strpos($blogControllerSource, "foreach (array('title', 'description', 'excerpt') as \$field)") !== false,
        'source_falls_back_from_arabic_to_english' => strpos($blogControllerSource, "\$language_code === 'arabic'") !== false && strpos($blogControllerSource, "'english'") !== false,
        'source_preserves_canonical_when_no_candidate_field' => strpos($blogControllerSource, "\$blog_details[\$field] = \$candidate['row'][\$field]") !== false,
    );
    ybdw1_print('Fallback source checks', $fallbackChecks);
    foreach ($fallbackChecks as $label => $ok) {
        ybdw1_assert($failures, $ok, 'Fallback check failed: ' . $label);
    }

    $mysqli->close();
} catch (Throwable $exception) {
    $failures[] = $exception->getMessage();
}

if (!empty($warnings)) {
    ybdw1_print('Warnings', $warnings);
}

if (!empty($failures)) {
    ybdw1_print('Failures', $failures);
    ybdw1_print('Result', 'FAIL: Blog detail translation wiring diagnostic found blocking issues.');
    exit(1);
}

ybdw1_print('Result', 'PASS: Blog detail translation wiring diagnostic completed read-only.');
exit(0);
