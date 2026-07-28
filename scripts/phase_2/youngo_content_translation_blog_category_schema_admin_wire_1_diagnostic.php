<?php
/**
 * CONTENT.TRANSLATION.BLOG.CATEGORY.SCHEMA.ADMIN.WIRE.1 diagnostic.
 *
 * Verifies additive Blog category translation schema, admin/source wiring,
 * public display overlay, and cleanup of temporary diagnostic rows. The only
 * DB writes are a controlled temporary insert/read/delete cycle against
 * youngo_blog_category_translations.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
mysqli_report(MYSQLI_REPORT_OFF);

$root = dirname(__DIR__, 2);
chdir($root);

defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');
defined('FCPATH') || define('FCPATH', $root . DIRECTORY_SEPARATOR);
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'config' . DIRECTORY_SEPARATOR . 'database.php';

$failures = array();
$warnings = array();
$tempCategoryId = 987654321;

function ybcsaw1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function ybcsaw1_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function ybcsaw1_path($root, $relative)
{
    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function ybcsaw1_source($root, $relative)
{
    $path = ybcsaw1_path($root, $relative);
    return is_file($path) ? file_get_contents($path) : '';
}

function ybcsaw1_connect($db, $active_group)
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

function ybcsaw1_query($mysqli, $sql)
{
    $result = $mysqli->query($sql);
    if (!$result) {
        throw new RuntimeException('DB query failed without exposing credentials: ' . $mysqli->error);
    }

    if ($result === true) {
        return array();
    }

    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();

    return $rows;
}

function ybcsaw1_scalar($mysqli, $sql)
{
    $rows = ybcsaw1_query($mysqli, $sql);
    if (empty($rows)) {
        return null;
    }

    $row = $rows[0];
    return reset($row);
}

function ybcsaw1_table_exists($mysqli, $table)
{
    return (int) ybcsaw1_scalar(
        $mysqli,
        "SELECT COUNT(*) AS row_count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '" . $mysqli->real_escape_string($table) . "'"
    ) > 0;
}

function ybcsaw1_columns($mysqli, $table)
{
    if (!ybcsaw1_table_exists($mysqli, $table)) {
        return array();
    }

    $rows = ybcsaw1_query(
        $mysqli,
        "SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '" . $mysqli->real_escape_string($table) . "' ORDER BY ordinal_position"
    );

    $columns = array();
    foreach ($rows as $row) {
        $columns[] = $row['column_name'];
    }

    return $columns;
}

function ybcsaw1_indexes($mysqli, $table)
{
    if (!ybcsaw1_table_exists($mysqli, $table)) {
        return array();
    }

    return ybcsaw1_query($mysqli, "SHOW INDEX FROM `" . $table . "`");
}

function ybcsaw1_http_get($path)
{
    $url = 'http://localhost/' . ltrim($path, '/');
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'ignore_errors' => true,
            'timeout' => 15,
            'header' => "User-Agent: YounGoBlogCategorySchemaAdminWireDiagnostic/1.0\r\n",
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

function ybcsaw1_plain_text($html)
{
    $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', (string) $html);
    $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $text);
    $text = preg_replace('/<[^>]+>/', ' ', $text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $text));
}

function ybcsaw1_has_payment_cta($html)
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

function ybcsaw1_contains($haystack, $needle)
{
    return $needle !== '' && strpos((string) $haystack, (string) $needle) !== false;
}

function ybcsaw1_has_arabic($value)
{
    return preg_match('/\p{Arabic}/u', (string) $value) === 1;
}

try {
    $mysqli = ybcsaw1_connect($db, $active_group);
    ybcsaw1_query($mysqli, "DELETE FROM youngo_blog_category_translations WHERE blog_category_id = " . (int) $tempCategoryId);

    $files = array(
        'crud_model' => 'application/models/Crud_model.php',
        'admin_controller' => 'application/controllers/Admin.php',
        'blog_controller' => 'application/controllers/Blog.php',
        'backend_add' => 'application/views/backend/admin/blog_category_add.php',
        'backend_edit' => 'application/views/backend/admin/blog_category_edit.php',
        'frontend_blogs' => 'application/views/frontend/youngo/blogs.php',
        'frontend_detail' => 'application/views/frontend/youngo/blog_details.php',
        'up_sql' => 'scripts/phase_2/content_translation_blog_category_schema_admin_wire_1_up.sql',
        'down_sql' => 'scripts/phase_2/content_translation_blog_category_schema_admin_wire_1_down.sql',
    );

    $source = array();
    foreach ($files as $label => $relative) {
        $source[$label] = ybcsaw1_source($root, $relative);
        ybcsaw1_assert($failures, $source[$label] !== '', 'Missing or unreadable file: ' . $relative);
    }

    $columns = ybcsaw1_columns($mysqli, 'youngo_blog_category_translations');
    $indexes = ybcsaw1_indexes($mysqli, 'youngo_blog_category_translations');
    $constraints = ybcsaw1_query(
        $mysqli,
        "SELECT constraint_name, check_clause FROM information_schema.check_constraints WHERE constraint_schema = DATABASE() AND table_name = 'youngo_blog_category_translations'"
    );

    $expectedColumns = array('id', 'blog_category_id', 'language_code', 'title', 'subtitle', 'display_slug', 'created_by_user_id', 'updated_by_user_id', 'created_at', 'updated_at');
    $schemaChecks = array(
        'table_exists' => ybcsaw1_table_exists($mysqli, 'youngo_blog_category_translations'),
        'expected_columns_present' => count(array_diff($expectedColumns, $columns)) === 0,
        'unique_category_language_index' => false,
        'language_check_constraint' => false,
    );

    foreach ($indexes as $index) {
        if (isset($index['Key_name']) && $index['Key_name'] === 'uniq_ybct_category_language') {
            $schemaChecks['unique_category_language_index'] = true;
        }
    }
    foreach ($constraints as $constraint) {
        $clause = isset($constraint['check_clause']) ? strtolower($constraint['check_clause']) : '';
        if (strpos($clause, 'english') !== false && strpos($clause, 'arabic') !== false) {
            $schemaChecks['language_check_constraint'] = true;
        }
    }
    ybcsaw1_print('Schema checks', $schemaChecks);
    foreach ($schemaChecks as $label => $ok) {
        ybcsaw1_assert($failures, $ok, 'Schema check failed: ' . $label);
    }

    $sourceChecks = array(
        'crud_has_normalizer' => strpos($source['crud_model'], 'youngo_normalize_blog_category_translation_language') !== false,
        'crud_has_save_method' => strpos($source['crud_model'], 'youngo_save_blog_category_translation') !== false,
        'crud_has_display_overlay' => strpos($source['crud_model'], 'youngo_apply_blog_category_translation') !== false,
        'crud_preserves_canonical_slug_after_overlay' => strpos($source['crud_model'], "\$category['slug'] = \$canonical_slug;") !== false,
        'admin_add_keeps_original_action' => strpos($source['backend_add'], "site_url('admin/blog_category/add')") !== false,
        'admin_edit_keeps_original_action' => strpos($source['backend_edit'], "site_url('admin/blog_category/update/'.\$blog_category['blog_category_id'])") !== false,
        'admin_has_english_fields' => strpos($source['backend_add'] . $source['backend_edit'], 'english_title') !== false,
        'admin_has_arabic_rtl_fields' => strpos($source['backend_add'] . $source['backend_edit'], 'arabic_title') !== false && strpos($source['backend_add'] . $source['backend_edit'], 'dir="rtl"') !== false,
        'frontend_list_uses_overlay' => strpos($source['frontend_blogs'], 'youngo_apply_blog_category_translation') !== false,
        'frontend_category_page_uses_overlay' => strpos($source['frontend_blogs'], 'youngo_apply_blog_category_translations') !== false,
        'frontend_detail_uses_overlay' => strpos($source['frontend_detail'], 'youngo_apply_blog_category_translation') !== false,
        'no_payment_terms_in_changed_sources' => stripos($source['backend_add'] . $source['backend_edit'] . $source['frontend_blogs'] . $source['frontend_detail'] . $source['up_sql'], 'paymob') === false
            && stripos($source['backend_add'] . $source['backend_edit'] . $source['frontend_blogs'] . $source['frontend_detail'], 'checkout') === false,
    );
    ybcsaw1_print('Source wiring checks', $sourceChecks);
    foreach ($sourceChecks as $label => $ok) {
        ybcsaw1_assert($failures, $ok, 'Source wiring check failed: ' . $label);
    }

    $tempNow = time();
    ybcsaw1_query(
        $mysqli,
        "INSERT INTO youngo_blog_category_translations (blog_category_id, language_code, title, subtitle, display_slug, created_at, updated_at) VALUES " .
        "(" . (int) $tempCategoryId . ", 'english', 'Temporary English Category', 'Temporary English subtitle', 'temporary-english-category', " . (int) $tempNow . ", " . (int) $tempNow . ")," .
        "(" . (int) $tempCategoryId . ", 'arabic', 'تصنيف عربي مؤقت', 'وصف عربي مؤقت', NULL, " . (int) $tempNow . ", " . (int) $tempNow . ")"
    );
    $tempRows = ybcsaw1_query($mysqli, "SELECT language_code, title FROM youngo_blog_category_translations WHERE blog_category_id = " . (int) $tempCategoryId . " ORDER BY language_code");

    $badInsertAccepted = @$mysqli->query(
        "INSERT INTO youngo_blog_category_translations (blog_category_id, language_code, title, created_at, updated_at) VALUES (" .
        (int) ($tempCategoryId + 1) . ", 'arabic_translated', 'Bad language', " . (int) $tempNow . ", " . (int) $tempNow . ")"
    );
    if ($badInsertAccepted) {
        ybcsaw1_query($mysqli, "DELETE FROM youngo_blog_category_translations WHERE blog_category_id = " . (int) ($tempCategoryId + 1));
    }

    $tempChecks = array(
        'temporary_english_arabic_rows_inserted' => count($tempRows) === 2,
        'arabic_translated_rejected_by_table' => !$badInsertAccepted,
    );
    ybcsaw1_query($mysqli, "DELETE FROM youngo_blog_category_translations WHERE blog_category_id IN (" . (int) $tempCategoryId . ", " . (int) ($tempCategoryId + 1) . ")");
    $tempChecks['temporary_rows_cleaned'] = (int) ybcsaw1_scalar($mysqli, "SELECT COUNT(*) FROM youngo_blog_category_translations WHERE blog_category_id IN (" . (int) $tempCategoryId . ", " . (int) ($tempCategoryId + 1) . ")") === 0;
    ybcsaw1_print('Temporary write/read/delete checks', $tempChecks);
    foreach ($tempChecks as $label => $ok) {
        ybcsaw1_assert($failures, $ok, 'Temporary translation check failed: ' . $label);
    }

    $categoryRows = ybcsaw1_query(
        $mysqli,
        "SELECT bc.blog_category_id, bc.slug, bc.title AS canonical_title, en.title AS english_title, ar.title AS arabic_title " .
        "FROM blog_category bc " .
        "LEFT JOIN youngo_blog_category_translations en ON en.blog_category_id = bc.blog_category_id AND en.language_code = 'english' " .
        "LEFT JOIN youngo_blog_category_translations ar ON ar.blog_category_id = bc.blog_category_id AND ar.language_code = 'arabic' " .
        "ORDER BY bc.blog_category_id"
    );
    $translationCounts = ybcsaw1_query($mysqli, "SELECT language_code, COUNT(*) AS row_count FROM youngo_blog_category_translations GROUP BY language_code ORDER BY language_code");
    $badLanguageRows = (int) ybcsaw1_scalar($mysqli, "SELECT COUNT(*) FROM youngo_blog_category_translations WHERE language_code NOT IN ('english', 'arabic')");
    ybcsaw1_print('Blog category translation rows', array('categories' => $categoryRows, 'translation_counts' => $translationCounts, 'bad_language_rows' => $badLanguageRows));
    ybcsaw1_assert($failures, $badLanguageRows === 0, 'Unexpected non-canonical language code rows found.');
    foreach ($categoryRows as $categoryRow) {
        ybcsaw1_assert($failures, ybcsaw1_has_arabic(isset($categoryRow['arabic_title']) ? $categoryRow['arabic_title'] : ''), 'Arabic Blog category title is missing real Arabic text for category ID ' . $categoryRow['blog_category_id']);
    }

    $httpPaths = array(
        'ar_default_blog' => '/home/blog',
        'ar_default_detail' => '/blog/details/helping-children-start-their-coding-journey/1',
        'en_blog' => '/en/home/blog',
        'en_detail' => '/en/blog/details/helping-children-start-their-coding-journey/1',
        'ar_alias_blog' => '/ar/home/blog',
        'ar_alias_detail' => '/ar/blog/details/blog/1',
    );
    $httpResults = array();
    foreach ($httpPaths as $label => $path) {
        $response = ybcsaw1_http_get($path);
        $text = ybcsaw1_plain_text($response['html']);
        $httpResults[$label] = array(
            'url' => $response['url'],
            'status' => $response['status'],
            'contains_payment_cta' => ybcsaw1_has_payment_cta($response['html']),
            'contains_parent_guides' => ybcsaw1_contains($text, 'Parent Guides'),
            'contains_arabic_category' => isset($categoryRows[0]['arabic_title']) ? ybcsaw1_contains($text, $categoryRows[0]['arabic_title']) : false,
        );

        if ($response['status'] === 0) {
            $warnings[] = 'HTTP probe unavailable: ' . $path;
            continue;
        }

        ybcsaw1_assert($failures, $response['status'] >= 200 && $response['status'] < 400, 'HTTP status was not 2xx/3xx for ' . $path);
        ybcsaw1_assert($failures, !$httpResults[$label]['contains_payment_cta'], 'Forbidden payment/checkout CTA detected on ' . $path);
    }
    ybcsaw1_print('HTTP probes', $httpResults);

    $mysqli->close();
} catch (Throwable $exception) {
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        @$mysqli->query("DELETE FROM youngo_blog_category_translations WHERE blog_category_id IN (" . (int) $tempCategoryId . ", " . (int) ($tempCategoryId + 1) . ")");
    }
    $failures[] = $exception->getMessage();
}

if (!empty($warnings)) {
    ybcsaw1_print('Warnings', $warnings);
}

if (!empty($failures)) {
    ybcsaw1_print('Failures', $failures);
    exit(1);
}

echo "\nCONTENT.TRANSLATION.BLOG.CATEGORY.SCHEMA.ADMIN.WIRE.1 diagnostic passed.\n";
