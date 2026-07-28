<?php
/**
 * CONTENT.TRANSLATION.BLOG.SEARCH.LOCALIZATION.PLAN.1 diagnostic.
 *
 * Read-only planning diagnostic for Blog search/filter localization. It reads
 * source files and DB metadata/counts only. It does not write DB rows, create
 * schema, edit content, change routes, call Paymob, or create checkout/order
 * records.
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

function ybslp1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function ybslp1_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function ybslp1_path($root, $relative)
{
    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function ybslp1_source($root, $relative)
{
    $path = ybslp1_path($root, $relative);
    return is_file($path) ? file_get_contents($path) : '';
}

function ybslp1_has($source, $needle)
{
    return strpos((string) $source, (string) $needle) !== false;
}

function ybslp1_connect($db, $active_group)
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

function ybslp1_query($mysqli, $sql)
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

function ybslp1_scalar($mysqli, $sql)
{
    $rows = ybslp1_query($mysqli, $sql);
    if (empty($rows)) {
        return null;
    }

    $row = $rows[0];
    return reset($row);
}

function ybslp1_table_exists($mysqli, $table)
{
    return (int) ybslp1_scalar(
        $mysqli,
        "SELECT COUNT(*) AS row_count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '" . $mysqli->real_escape_string($table) . "'"
    ) > 0;
}

function ybslp1_columns($mysqli, $table)
{
    if (!ybslp1_table_exists($mysqli, $table)) {
        return array();
    }

    $rows = ybslp1_query(
        $mysqli,
        "SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '" . $mysqli->real_escape_string($table) . "' ORDER BY ordinal_position"
    );

    $columns = array();
    foreach ($rows as $row) {
        $columns[] = $row['column_name'];
    }

    return $columns;
}

try {
    $files = array(
        'blog_controller' => 'application/controllers/Blog.php',
        'crud_model' => 'application/models/Crud_model.php',
        'youngo_blogs_view' => 'application/views/frontend/youngo/blogs.php',
        'youngo_blog_details_view' => 'application/views/frontend/youngo/blog_details.php',
        'default_blog_sidebar' => 'application/views/frontend/default-new/blog_sidebar.php',
        'routes' => 'application/config/routes.php',
        'frontend_language_helper' => 'application/helpers/youngo_frontend_language_helper.php',
    );

    $source = array();
    $fileStatus = array();
    foreach ($files as $label => $relative) {
        $path = ybslp1_path($root, $relative);
        $fileStatus[$label] = array('path' => $relative, 'exists' => is_file($path));
        $source[$label] = ybslp1_source($root, $relative);
    }
    ybslp1_print('File inventory', $fileStatus);

    foreach (array('blog_controller', 'crud_model', 'youngo_blogs_view', 'youngo_blog_details_view', 'routes', 'frontend_language_helper') as $required) {
        ybslp1_assert($failures, $fileStatus[$required]['exists'], 'Required file is missing: ' . $files[$required]);
    }

    $mysqli = ybslp1_connect($db, $active_group);
    $dbStatus = array(
        'blogs' => array(
            'exists' => ybslp1_table_exists($mysqli, 'blogs'),
            'columns' => ybslp1_columns($mysqli, 'blogs'),
            'rows' => (int) ybslp1_scalar($mysqli, 'SELECT COUNT(*) FROM blogs'),
        ),
        'youngo_blog_translations' => array(
            'exists' => ybslp1_table_exists($mysqli, 'youngo_blog_translations'),
            'columns' => ybslp1_columns($mysqli, 'youngo_blog_translations'),
            'counts' => ybslp1_query($mysqli, 'SELECT language_code, COUNT(*) AS row_count FROM youngo_blog_translations GROUP BY language_code ORDER BY language_code'),
            'invalid_language_rows' => (int) ybslp1_scalar($mysqli, "SELECT COUNT(*) FROM youngo_blog_translations WHERE language_code NOT IN ('english', 'arabic')"),
        ),
        'blog_category' => array(
            'exists' => ybslp1_table_exists($mysqli, 'blog_category'),
            'columns' => ybslp1_columns($mysqli, 'blog_category'),
            'rows' => (int) ybslp1_scalar($mysqli, 'SELECT COUNT(*) FROM blog_category'),
        ),
        'youngo_blog_category_translations' => array(
            'exists' => ybslp1_table_exists($mysqli, 'youngo_blog_category_translations'),
            'columns' => ybslp1_columns($mysqli, 'youngo_blog_category_translations'),
            'counts' => ybslp1_query($mysqli, 'SELECT language_code, COUNT(*) AS row_count FROM youngo_blog_category_translations GROUP BY language_code ORDER BY language_code'),
            'invalid_language_rows' => (int) ybslp1_scalar($mysqli, "SELECT COUNT(*) FROM youngo_blog_category_translations WHERE language_code NOT IN ('english', 'arabic')"),
        ),
    );
    ybslp1_print('DB localized source inventory', $dbStatus);

    ybslp1_assert($failures, $dbStatus['blogs']['exists'], 'blogs table is missing.');
    ybslp1_assert($failures, in_array('title', $dbStatus['blogs']['columns'], true), 'blogs.title column is missing.');
    ybslp1_assert($failures, in_array('description', $dbStatus['blogs']['columns'], true), 'blogs.description column is missing.');
    ybslp1_assert($failures, $dbStatus['youngo_blog_translations']['exists'], 'youngo_blog_translations table is missing.');
    ybslp1_assert($failures, $dbStatus['youngo_blog_category_translations']['exists'], 'youngo_blog_category_translations table is missing.');
    ybslp1_assert($failures, $dbStatus['youngo_blog_translations']['invalid_language_rows'] === 0, 'Invalid Blog translation language rows found.');
    ybslp1_assert($failures, $dbStatus['youngo_blog_category_translations']['invalid_language_rows'] === 0, 'Invalid Blog category translation language rows found.');

    $searchChecks = array(
        'controller_detects_search_query_param' => ybslp1_has($source['blog_controller'], "\$_GET['search']"),
        'controller_searches_canonical_blog_title' => ybslp1_has($source['blog_controller'], "\$this->db->like('title', \$_GET['search'])"),
        'controller_searches_canonical_blog_description' => ybslp1_has($source['blog_controller'], "\$this->db->or_like('description', \$_GET['search'])"),
        'controller_search_uses_blogs_table' => ybslp1_has($source['blog_controller'], "\$this->db->get('blogs'"),
        'controller_search_does_not_join_blog_translation_tables' => strpos($source['blog_controller'], 'youngo_blog_translations') === false || strpos($source['blog_controller'], 'youngo_localize_blog_details') !== false,
        'controller_detects_category_query_param' => ybslp1_has($source['blog_controller'], "\$_GET['category']"),
        'controller_resolves_category_by_canonical_slug' => ybslp1_has($source['blog_controller'], 'get_blog_category_by_slug($_GET[\'category\'])'),
        'controller_filters_by_canonical_category_id' => ybslp1_has($source['blog_controller'], "\$this->db->where('blog_category_id', \$blog_category_id)"),
        'youngo_view_renders_search_result_state' => ybslp1_has($source['youngo_blogs_view'], 'isset($search_string)'),
        'youngo_view_has_no_visible_search_input' => strpos($source['youngo_blogs_view'], 'name="search"') === false && strpos($source['youngo_blogs_view'], "name='search'") === false,
        'legacy_default_view_has_search_form' => ybslp1_has($source['default_blog_sidebar'], 'name="search"') && ybslp1_has($source['default_blog_sidebar'], "site_url('blogs')"),
        'route_keeps_legacy_blogs_search_path' => ybslp1_has($source['routes'], "\$route['blogs']") && ybslp1_has($source['routes'], 'blog/blogs'),
        'localized_blog_helper_keeps_canonical_blog_route_aliases' => ybslp1_has($source['frontend_language_helper'], 'home/blog') && ybslp1_has($source['frontend_language_helper'], 'en/home/blog'),
    );
    ybslp1_print('Current search/filter source checks', $searchChecks);
    foreach ($searchChecks as $label => $ok) {
        ybslp1_assert($failures, $ok, 'Search/filter source check failed: ' . $label);
    }

    $translationSourceChecks = array(
        'blog_translation_read_helper_exists' => ybslp1_has($source['crud_model'], 'function youngo_get_blog_translation'),
        'blog_listing_reads_blog_translations' => ybslp1_has($source['youngo_blogs_view'], 'youngo_blog_translations'),
        'blog_detail_uses_blog_translation_candidate' => ybslp1_has($source['blog_controller'], 'youngo_get_blog_translation_candidate'),
        'blog_category_translation_read_helper_exists' => ybslp1_has($source['crud_model'], 'function youngo_get_blog_category_translation'),
        'blog_category_frontend_overlay_exists' => ybslp1_has($source['youngo_blogs_view'] . $source['youngo_blog_details_view'], 'youngo_apply_blog_category_translation'),
    );
    ybslp1_print('Available localized source checks', $translationSourceChecks);
    foreach ($translationSourceChecks as $label => $ok) {
        ybslp1_assert($failures, $ok, 'Localized source check failed: ' . $label);
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
        'forbidden_payment_or_paymob_changed_files' => $forbiddenChangedFiles,
        'no_payment_or_paymob_changed_files' => empty($forbiddenChangedFiles),
        'no_arabic_translated_in_blog_search_sources' => stripos($source['blog_controller'] . $source['youngo_blogs_view'] . $source['youngo_blog_details_view'], 'arabic_translated') === false,
    );
    ybslp1_print('Safety checks', $safetyChecks);
    ybslp1_assert($failures, $safetyChecks['no_payment_or_paymob_changed_files'], 'Payment/Paymob/checkout/coupon/cart files changed unexpectedly.');
    ybslp1_assert($failures, $safetyChecks['no_arabic_translated_in_blog_search_sources'], 'Blog search/frontend sources must not use arabic_translated.');

    $mysqli->close();
} catch (Throwable $exception) {
    $failures[] = $exception->getMessage();
}

if (!empty($failures)) {
    ybslp1_print('Failures', $failures);
    exit(1);
}

echo "\nCONTENT.TRANSLATION.BLOG.SEARCH.LOCALIZATION.PLAN.1 diagnostic passed.\n";
