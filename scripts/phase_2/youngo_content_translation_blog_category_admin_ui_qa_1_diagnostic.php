<?php
/**
 * CONTENT.TRANSLATION.BLOG.CATEGORY.ADMIN.UI.QA.1 diagnostic.
 *
 * Read-only final-state checks after authenticated Blog category admin UI QA.
 * This script does not write DB rows, change schema, call payment/Paymob, or
 * create checkout/order/access records.
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

function ybcauq1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function ybcauq1_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function ybcauq1_connect($db, $active_group)
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

function ybcauq1_query($mysqli, $sql)
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

function ybcauq1_scalar($mysqli, $sql)
{
    $rows = ybcauq1_query($mysqli, $sql);
    if (empty($rows)) {
        return null;
    }

    $row = $rows[0];
    return reset($row);
}

function ybcauq1_source($root, $relative)
{
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    return is_file($path) ? file_get_contents($path) : '';
}

function ybcauq1_http_get($path)
{
    $url = 'http://localhost/' . ltrim($path, '/');
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'ignore_errors' => true,
            'timeout' => 15,
            'header' => "User-Agent: YounGoBlogCategoryAdminUiQaDiagnostic/1.0\r\n",
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

    return array('url' => $url, 'status' => $status, 'html' => $html === false ? '' : $html);
}

function ybcauq1_plain_text($html)
{
    $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', (string) $html);
    $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $text);
    $text = preg_replace('/<[^>]+>/', ' ', $text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $text));
}

function ybcauq1_has_payment_cta($html)
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

try {
    $mysqli = ybcauq1_connect($db, $active_group);

    $categoryRows = ybcauq1_query($mysqli, "SELECT blog_category_id, title, subtitle, slug FROM blog_category ORDER BY blog_category_id");
    $translationRows = ybcauq1_query(
        $mysqli,
        "SELECT blog_category_id, language_code, title, subtitle, display_slug FROM youngo_blog_category_translations ORDER BY blog_category_id, language_code"
    );
    $translationCounts = ybcauq1_query(
        $mysqli,
        "SELECT language_code, COUNT(*) AS row_count FROM youngo_blog_category_translations GROUP BY language_code ORDER BY language_code"
    );
    $invalidLanguageRows = (int) ybcauq1_scalar(
        $mysqli,
        "SELECT COUNT(*) FROM youngo_blog_category_translations WHERE language_code NOT IN ('english', 'arabic')"
    );
    $arabicTranslatedRows = (int) ybcauq1_scalar(
        $mysqli,
        "SELECT COUNT(*) FROM youngo_blog_category_translations WHERE language_code = 'arabic_translated'"
    );
    $temporaryCategoryRows = (int) ybcauq1_scalar(
        $mysqli,
        "SELECT COUNT(*) FROM blog_category WHERE title LIKE 'YounGo QA Blog Category%' OR slug LIKE 'youngo-qa-blog-category%'"
    );
    $temporaryTranslationRows = (int) ybcauq1_scalar(
        $mysqli,
        "SELECT COUNT(*) FROM youngo_blog_category_translations WHERE title LIKE 'YounGo QA Blog Category%' OR subtitle LIKE 'Temporary admin UI QA category.%'"
    );
    $blogRows = (int) ybcauq1_scalar($mysqli, "SELECT COUNT(*) FROM blogs");
    $canonicalSlugRows = ybcauq1_query(
        $mysqli,
        "SELECT blog_category_id, slug FROM blog_category WHERE blog_category_id IN (1, 2, 3) ORDER BY blog_category_id"
    );

    $dbChecks = array(
        'blog_category_rows' => count($categoryRows),
        'blog_rows' => $blogRows,
        'translation_rows' => count($translationRows),
        'translation_counts' => $translationCounts,
        'invalid_language_rows' => $invalidLanguageRows,
        'arabic_translated_rows' => $arabicTranslatedRows,
        'temporary_category_rows' => $temporaryCategoryRows,
        'temporary_translation_rows' => $temporaryTranslationRows,
        'canonical_slugs' => $canonicalSlugRows,
    );
    ybcauq1_print('DB final-state checks', $dbChecks);

    ybcauq1_assert($failures, count($categoryRows) === 3, 'Expected only the 3 baseline Blog categories after cleanup.');
    ybcauq1_assert($failures, $blogRows === 4, 'Unexpected Blog post count drift.');
    ybcauq1_assert($failures, count($translationRows) === 6, 'Expected exactly 6 intended Blog category translation rows.');
    ybcauq1_assert($failures, $invalidLanguageRows === 0, 'Non-english/non-arabic Blog category translation language rows found.');
    ybcauq1_assert($failures, $arabicTranslatedRows === 0, 'arabic_translated Blog category rows found.');
    ybcauq1_assert($failures, $temporaryCategoryRows === 0, 'Temporary QA Blog category rows remain.');
    ybcauq1_assert($failures, $temporaryTranslationRows === 0, 'Temporary QA Blog category translation rows remain.');

    $expectedSlugs = array(1 => 'parent-guides', 2 => 'learning-tips', 3 => 'future-skills');
    foreach ($canonicalSlugRows as $row) {
        $id = (int) $row['blog_category_id'];
        ybcauq1_assert($failures, isset($expectedSlugs[$id]) && $row['slug'] === $expectedSlugs[$id], 'Canonical Blog category slug changed for ID ' . $id);
    }

    $crudSource = ybcauq1_source($root, 'application/models/Crud_model.php');
    $sourceChecks = array(
        'delete_cleans_translation_rows' => strpos($crudSource, "delete('youngo_blog_category_translations')") !== false,
        'no_arabic_translated_in_category_sources' => stripos($crudSource . ybcauq1_source($root, 'application/views/backend/admin/blog_category_add.php') . ybcauq1_source($root, 'application/views/backend/admin/blog_category_edit.php'), 'arabic_translated') === false,
    );
    ybcauq1_print('Source checks', $sourceChecks);
    foreach ($sourceChecks as $label => $ok) {
        ybcauq1_assert($failures, $ok, 'Source check failed: ' . $label);
    }

    $httpPaths = array(
        'ar_default_blog' => '/home/blog',
        'ar_default_detail' => '/blog/details/helping-children-start-their-coding-journey/1',
        'en_blog' => '/en/home/blog',
        'en_detail' => '/en/blog/details/helping-children-start-their-coding-journey/1',
        'ar_alias_blog' => '/ar/home/blog',
    );
    $httpResults = array();
    foreach ($httpPaths as $label => $path) {
        $response = ybcauq1_http_get($path);
        $text = ybcauq1_plain_text($response['html']);
        $httpResults[$label] = array(
            'url' => $response['url'],
            'status' => $response['status'],
            'contains_payment_cta' => ybcauq1_has_payment_cta($response['html']),
            'contains_arabic_category' => strpos($text, 'إرشادات للأهل') !== false,
            'contains_english_category' => strpos($text, 'Parent Guides') !== false,
        );

        ybcauq1_assert($failures, $response['status'] >= 200 && $response['status'] < 400, 'HTTP status was not 2xx/3xx for ' . $path);
        ybcauq1_assert($failures, !$httpResults[$label]['contains_payment_cta'], 'Forbidden payment/checkout CTA detected on ' . $path);
    }
    ybcauq1_print('Public smoke checks', $httpResults);
    ybcauq1_assert($failures, $httpResults['ar_default_blog']['contains_arabic_category'], 'Arabic/default Blog page did not show the Arabic category label.');
    ybcauq1_assert($failures, $httpResults['ar_default_detail']['contains_arabic_category'], 'Arabic/default Blog detail did not show the Arabic category label.');
    ybcauq1_assert($failures, $httpResults['en_blog']['contains_english_category'], '/en Blog page did not show the English category label.');
    ybcauq1_assert($failures, $httpResults['en_detail']['contains_english_category'], '/en Blog detail did not show the English category label.');
    ybcauq1_assert($failures, $httpResults['ar_alias_blog']['contains_arabic_category'], '/ar Blog page did not show the Arabic category label.');

    $mysqli->close();
} catch (Throwable $exception) {
    $failures[] = $exception->getMessage();
}

if (!empty($warnings)) {
    ybcauq1_print('Warnings', $warnings);
}

if (!empty($failures)) {
    ybcauq1_print('Failures', $failures);
    exit(1);
}

echo "\nCONTENT.TRANSLATION.BLOG.CATEGORY.ADMIN.UI.QA.1 diagnostic passed.\n";
