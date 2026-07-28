<?php
/**
 * CONTENT.TRANSLATION.BLOG.CATEGORY.PLAN.1 diagnostic.
 *
 * Read-only planning inventory for Blog category localization. This script
 * uses source reads, git diff inspection, and SELECT/information_schema reads
 * only. It does not write DB rows, create schema, edit Blog content, change
 * routes, call Paymob, or create checkout/order/access records.
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

function ybcp1_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function ybcp1_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function ybcp1_path($root, $relative)
{
    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function ybcp1_source($root, $relative)
{
    $path = ybcp1_path($root, $relative);
    return is_file($path) ? file_get_contents($path) : '';
}

function ybcp1_has($source, $needle)
{
    return strpos((string) $source, (string) $needle) !== false;
}

function ybcp1_regex($source, $pattern)
{
    return preg_match($pattern, (string) $source) === 1;
}

function ybcp1_connect($db, $active_group)
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

function ybcp1_query($mysqli, $sql)
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

function ybcp1_scalar($mysqli, $sql)
{
    $rows = ybcp1_query($mysqli, $sql);
    if (empty($rows)) {
        return null;
    }

    $row = $rows[0];
    return reset($row);
}

function ybcp1_table_exists($mysqli, $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    return (int) ybcp1_scalar(
        $mysqli,
        "SELECT COUNT(*) AS row_count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '" . $mysqli->real_escape_string($table) . "'"
    ) > 0;
}

function ybcp1_columns($mysqli, $table)
{
    if (!ybcp1_table_exists($mysqli, $table)) {
        return array();
    }

    $rows = ybcp1_query(
        $mysqli,
        "SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '" . $mysqli->real_escape_string($table) . "' ORDER BY ordinal_position"
    );

    $columns = array();
    foreach ($rows as $row) {
        $columns[] = $row['column_name'];
    }

    return $columns;
}

function ybcp1_count($mysqli, $table, $where = '')
{
    if (!ybcp1_table_exists($mysqli, $table)) {
        return null;
    }

    $sql = "SELECT COUNT(*) AS row_count FROM `" . $table . "`";
    if ($where !== '') {
        $sql .= " WHERE " . $where;
    }

    return (int) ybcp1_scalar($mysqli, $sql);
}

try {
    $files = array(
        'blog_controller' => 'application/controllers/Blog.php',
        'admin_controller' => 'application/controllers/Admin.php',
        'crud_model' => 'application/models/Crud_model.php',
        'frontend_blog_missing_probe' => 'application/views/frontend/youngo/blog.php',
        'frontend_blogs' => 'application/views/frontend/youngo/blogs.php',
        'frontend_blog_details' => 'application/views/frontend/youngo/blog_details.php',
        'backend_blog_category_list' => 'application/views/backend/admin/blog_category.php',
        'backend_blog_category_add' => 'application/views/backend/admin/blog_category_add.php',
        'backend_blog_category_edit' => 'application/views/backend/admin/blog_category_edit.php',
        'backend_blog_add' => 'application/views/backend/admin/blog_add.php',
        'backend_blog_edit' => 'application/views/backend/admin/blog_edit.php',
        'backend_blog_list' => 'application/views/backend/admin/blog.php',
        'sitemap_controller' => 'application/controllers/Sitemap.php',
        'routes' => 'application/config/routes.php',
    );

    $source = array();
    $fileStatus = array();
    foreach ($files as $label => $relative) {
        $path = ybcp1_path($root, $relative);
        $fileStatus[$label] = array('path' => $relative, 'exists' => is_file($path));
        $source[$label] = is_file($path) ? file_get_contents($path) : '';
    }
    ybcp1_print('File inventory', $fileStatus);

    foreach (array('blog_controller', 'admin_controller', 'crud_model', 'frontend_blogs', 'frontend_blog_details', 'backend_blog_category_add', 'backend_blog_category_edit') as $required) {
        ybcp1_assert($failures, $fileStatus[$required]['exists'], 'Required Blog category planning file is missing: ' . $files[$required]);
    }

    $mysqli = ybcp1_connect($db, $active_group);
    $translationTables = array(
        'youngo_blog_category_translations',
        'blog_category_translations',
        'blog_categories_translations',
    );
    $translationTableStatus = array();
    foreach ($translationTables as $table) {
        $translationTableStatus[$table] = array(
            'exists' => ybcp1_table_exists($mysqli, $table),
            'columns' => ybcp1_columns($mysqli, $table),
        );
    }

    $dbStatus = array(
        'blog_category' => array(
            'exists' => ybcp1_table_exists($mysqli, 'blog_category'),
            'columns' => ybcp1_columns($mysqli, 'blog_category'),
            'rows' => ybcp1_count($mysqli, 'blog_category'),
        ),
        'blogs' => array(
            'exists' => ybcp1_table_exists($mysqli, 'blogs'),
            'columns' => ybcp1_columns($mysqli, 'blogs'),
            'rows' => ybcp1_count($mysqli, 'blogs'),
            'rows_with_category' => ybcp1_count($mysqli, 'blogs', '`blog_category_id` > 0'),
        ),
        'candidate_translation_tables' => $translationTableStatus,
    );
    ybcp1_print('DB Blog category storage', $dbStatus);
    ybcp1_assert($failures, $dbStatus['blog_category']['exists'], 'blog_category table is missing.');
    ybcp1_assert($failures, in_array('blog_category_id', $dbStatus['blog_category']['columns'], true), 'blog_category.blog_category_id column is missing.');
    ybcp1_assert($failures, in_array('title', $dbStatus['blog_category']['columns'], true), 'blog_category.title column is missing.');
    ybcp1_assert($failures, in_array('slug', $dbStatus['blog_category']['columns'], true), 'blog_category.slug column is missing.');

    $adminChecks = array(
        'admin_add_modal_endpoint' => ybcp1_has($source['admin_controller'], 'public function add_blog_category()') && ybcp1_has($source['admin_controller'], "blog_category_add"),
        'admin_edit_modal_endpoint' => ybcp1_has($source['admin_controller'], 'public function edit_blog_category') && ybcp1_has($source['admin_controller'], "blog_category_edit"),
        'admin_create_action' => ybcp1_has($source['admin_controller'], "\$param1 == 'add'") && ybcp1_has($source['admin_controller'], 'add_blog_category()'),
        'admin_update_action' => ybcp1_has($source['admin_controller'], "\$param1 == 'update'") && ybcp1_has($source['admin_controller'], 'update_blog_category($param2)'),
        'add_form_posts_title_source' => ybcp1_regex($source['backend_blog_category_add'], '/name=["\'](?:title|english_title)["\']/'),
        'add_form_posts_subtitle_source' => ybcp1_regex($source['backend_blog_category_add'], '/name=["\'](?:subtitle|english_subtitle)["\']/'),
        'edit_form_posts_title_source' => ybcp1_regex($source['backend_blog_category_edit'], '/name=["\'](?:title|english_title)["\']/'),
        'edit_form_posts_subtitle_source' => ybcp1_regex($source['backend_blog_category_edit'], '/name=["\'](?:subtitle|english_subtitle)["\']/'),
        'add_form_has_bilingual_fields' => ybcp1_regex($source['backend_blog_category_add'], '/name=["\'](?:english_title|arabic_title|translations\[english\]|translations\[arabic\])/'),
        'edit_form_has_bilingual_fields' => ybcp1_regex($source['backend_blog_category_edit'], '/name=["\'](?:english_title|arabic_title|translations\[english\]|translations\[arabic\])/'),
    );
    ybcp1_print('Admin Blog category form/controller checks', $adminChecks);
    foreach ($adminChecks as $label => $ok) {
        ybcp1_assert($failures, $ok, 'Admin check failed: ' . $label);
    }

    $modelChecks = array(
        'add_blog_category_exists' => ybcp1_has($source['crud_model'], 'function add_blog_category()'),
        'update_blog_category_exists' => ybcp1_has($source['crud_model'], 'function update_blog_category'),
        'delete_blog_category_exists' => ybcp1_has($source['crud_model'], 'function delete_blog_category'),
        'get_blog_categories_exists' => ybcp1_has($source['crud_model'], 'function get_blog_categories'),
        'get_blog_category_by_slug_exists' => ybcp1_has($source['crud_model'], 'function get_blog_category_by_slug'),
        'get_blogs_by_category_id_exists' => ybcp1_has($source['crud_model'], 'function get_blogs_by_category_id'),
        'canonical_slug_generated_from_title' => ybcp1_has($source['crud_model'], "\$data['slug'] = slugify(\$data['title'])"),
        'blog_category_translation_methods_exist' => ybcp1_regex($source['crud_model'], '/youngo_(?:get|save|sync|apply).*blog_category.*translation/i'),
    );
    ybcp1_print('Model Blog category checks', $modelChecks);
    foreach ($modelChecks as $label => $ok) {
        ybcp1_assert($failures, $ok, 'Model check failed: ' . $label);
    }

    $frontendChecks = array(
        'blog_php_view_absent_expected' => !$fileStatus['frontend_blog_missing_probe']['exists'],
        'blogs_view_category_card_uses_raw_title' => ybcp1_has($source['frontend_blogs'], "\$category_title = isset(\$category['title']) ? \$category['title']"),
        'blogs_view_category_card_uses_raw_subtitle' => ybcp1_has($source['frontend_blogs'], "\$category_subtitle = isset(\$category['subtitle'])"),
        'blogs_view_category_card_preserves_slug_filter' => ybcp1_has($source['frontend_blogs'], "'category=' . rawurlencode(\$category_slug)"),
        'blogs_view_blog_card_category_function' => ybcp1_has($source['frontend_blogs'], 'function youngo_blog_category_title'),
        'blogs_view_has_no_local_arabic_category_map' => !ybcp1_has($source['frontend_blogs'], '$arabic_titles'),
        'detail_view_category_eyebrow_uses_category_helper' => ybcp1_has($source['frontend_blog_details'], 'youngo_blog_detail_category_title') && (ybcp1_has($source['frontend_blog_details'], "return \$category->row('title');") || ybcp1_has($source['frontend_blog_details'], 'youngo_apply_blog_category_translation')),
        'blog_filter_uses_canonical_category_slug' => ybcp1_has($source['blog_controller'], 'get_blog_category_by_slug($_GET[\'category\'])'),
        'blog_search_uses_canonical_blog_fields' => ybcp1_has($source['blog_controller'], "\$this->db->like('title', \$_GET['search'])") && ybcp1_has($source['blog_controller'], "\$this->db->or_like('description', \$_GET['search'])"),
        'sitemap_uses_canonical_category_slug' => ybcp1_has($source['sitemap_controller'], "blogs?category=\$slug"),
    );
    ybcp1_print('Frontend Blog category usage checks', $frontendChecks);
    foreach ($frontendChecks as $label => $ok) {
        ybcp1_assert($failures, $ok, 'Frontend usage check failed: ' . $label);
    }

    $existingTranslationSupport = array(
        'translation_table_exists' => ybcp1_table_exists($mysqli, 'youngo_blog_category_translations'),
        'admin_bilingual_inputs_exist' => $adminChecks['add_form_has_bilingual_fields'] || $adminChecks['edit_form_has_bilingual_fields'],
        'crud_translation_methods_exist' => $modelChecks['blog_category_translation_methods_exist'],
        'frontend_reusable_translation_lookup_exists' => ybcp1_regex($source['frontend_blogs'] . $source['frontend_blog_details'], '/youngo_blog_category_translations|youngo_get_blog_category_translation|youngo_apply_blog_category_translation/i'),
        'legacy_view_local_arabic_map_still_present' => !$frontendChecks['blogs_view_has_no_local_arabic_category_map'],
    );
    ybcp1_print('Existing translation support finding', $existingTranslationSupport);
    ybcp1_assert($failures, !$existingTranslationSupport['translation_table_exists'] || ($existingTranslationSupport['admin_bilingual_inputs_exist'] && $existingTranslationSupport['crud_translation_methods_exist'] && $existingTranslationSupport['frontend_reusable_translation_lookup_exists']), 'Detected partial Blog category translation implementation; planning assumptions need review.');

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
        'route_source_has_no_arabic_translated' => stripos($source['routes'], 'arabic_translated') === false,
        'blog_category_sources_have_no_arabic_translated' => stripos($source['blog_controller'] . $source['crud_model'] . $source['frontend_blogs'] . $source['frontend_blog_details'] . $source['backend_blog_category_add'] . $source['backend_blog_category_edit'], 'arabic_translated') === false,
    );
    ybcp1_print('Safety checks', $safetyChecks);
    ybcp1_assert($failures, $safetyChecks['no_payment_or_paymob_changed_files'], 'Payment/Paymob/checkout/coupon/cart files changed unexpectedly.');
    ybcp1_assert($failures, $safetyChecks['route_source_has_no_arabic_translated'], 'Routes must not use arabic_translated.');
    ybcp1_assert($failures, $safetyChecks['blog_category_sources_have_no_arabic_translated'], 'Blog category sources must not use arabic_translated.');

    ybcp1_print('Planning recommendation signal', array(
        'recommended_approach' => $existingTranslationSupport['translation_table_exists'] ? 'Additive youngo_blog_category_translations implementation detected.' : 'Additive youngo_blog_category_translations table in a future implementation phase.',
        'reason' => 'It preserves canonical blog_category IDs/slugs, matches existing YounGo translation-table pattern, and avoids treating CMS categories as static UI phrases.',
        'fallback' => 'Arabic/default and /ar use Arabic translation when present, else canonical title/subtitle. /en uses English translation when present, else canonical title/subtitle.',
    ));

    $mysqli->close();
} catch (Throwable $exception) {
    $failures[] = $exception->getMessage();
}

if (!empty($failures)) {
    ybcp1_print('Failures', $failures);
    ybcp1_print('Result', 'FAIL: Blog category localization planning diagnostic found blocking issues.');
    exit(1);
}

ybcp1_print('Result', 'PASS: Blog category localization planning diagnostic completed read-only.');
exit(0);
