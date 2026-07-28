<?php
/**
 * Phase 2U.6 frontend content translation diagnostic.
 *
 * Read-only checks for translation-aware public frontend content shaping.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$root = dirname(__DIR__, 2);
define('ENVIRONMENT', 'development');
define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

$failures = array();
$warnings = array();

function phase_2u64_print($title, $payload)
{
    echo "\n== {$title} ==\n";
    echo is_string($payload) ? $payload . "\n" : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function phase_2u64_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function phase_2u64_contains($source, $needle)
{
    return strpos((string) $source, (string) $needle) !== false;
}

function phase_2u64_file_contains($path, $needle)
{
    return is_file($path) && phase_2u64_contains(file_get_contents($path), $needle);
}

function phase_2u64_query($mysqli, $sql)
{
    if (preg_match('/\b(insert|update|delete|replace|alter|drop|create|truncate|grant|revoke|set)\b/i', $sql)) {
        throw new Exception('Write SQL blocked by diagnostic wrapper.');
    }

    $result = $mysqli->query($sql);
    if (!$result) {
        return array('error' => 'Query failed without exposing credentials.');
    }

    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

function phase_2u64_table_exists($mysqli, $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    $rows = phase_2u64_query($mysqli, "SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function phase_2u64_count($mysqli, $table, $where = '')
{
    if (!phase_2u64_table_exists($mysqli, $table)) {
        return null;
    }

    $sql = "SELECT COUNT(*) AS c FROM `" . $table . "`";
    if ($where !== '') {
        $sql .= " WHERE " . $where;
    }

    $rows = phase_2u64_query($mysqli, $sql);
    return isset($rows[0]['c']) ? (int) $rows[0]['c'] : null;
}

function phase_2u64_first_row($mysqli, $table, $where = '')
{
    if (!phase_2u64_table_exists($mysqli, $table)) {
        return array();
    }

    $sql = "SELECT * FROM `" . $table . "`";
    if ($where !== '') {
        $sql .= " WHERE " . $where;
    }
    $sql .= " ORDER BY id ASC LIMIT 1";

    $rows = phase_2u64_query($mysqli, $sql);
    return isset($rows[0]) ? $rows[0] : array();
}

function phase_2u64_language_codes($mysqli, $table)
{
    if (!phase_2u64_table_exists($mysqli, $table)) {
        return array();
    }

    $rows = phase_2u64_query($mysqli, "SELECT DISTINCT language_code FROM `" . $table . "` ORDER BY language_code ASC");
    $codes = array();
    foreach ($rows as $row) {
        if (isset($row['language_code'])) {
            $codes[] = $row['language_code'];
        }
    }

    return $codes;
}

function phase_2u64_run_php_script($root, $script)
{
    $command = 'php ' . escapeshellarg($script);
    $output = array();
    $exit_code = 1;
    $cwd = getcwd();
    chdir($root);
    exec($command, $output, $exit_code);
    chdir($cwd);

    return array(
        'script' => $script,
        'exit_code' => $exit_code,
        'passes' => $exit_code === 0,
    );
}

class Phase_2U64_Query_Result
{
    protected $rows = array();

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function num_rows()
    {
        return count($this->rows);
    }

    public function row_array()
    {
        return isset($this->rows[0]) ? $this->rows[0] : array();
    }
}

class Phase_2U64_Db_Stub
{
    protected $mysqli;
    protected $wheres = array();

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function table_exists($table)
    {
        return phase_2u64_table_exists($this->mysqli, $table);
    }

    public function where($key, $value)
    {
        $this->wheres[] = array($key, $value);
        return $this;
    }

    public function get($table, $limit = null)
    {
        if (!$this->valid_identifier($table)) {
            return new Phase_2U64_Query_Result(array());
        }

        $sql = "SELECT * FROM `" . $table . "`" . $this->where_sql();
        if ($limit !== null) {
            $sql .= " LIMIT " . max(0, (int) $limit);
        }

        $this->wheres = array();
        $rows = phase_2u64_query($this->mysqli, $sql);

        return new Phase_2U64_Query_Result(is_array($rows) && !isset($rows['error']) ? $rows : array());
    }

    public function count_all_results($table)
    {
        if (!$this->valid_identifier($table)) {
            $this->wheres = array();
            return 0;
        }

        $sql = "SELECT COUNT(*) AS c FROM `" . $table . "`" . $this->where_sql();
        $this->wheres = array();
        $rows = phase_2u64_query($this->mysqli, $sql);

        return isset($rows[0]['c']) ? (int) $rows[0]['c'] : 0;
    }

    protected function where_sql()
    {
        if (empty($this->wheres)) {
            return '';
        }

        $conditions = array();
        foreach ($this->wheres as $where) {
            $key = trim((string) $where[0]);
            $value = $where[1];
            $operator = '=';

            if (substr($key, -2) === '!=') {
                $operator = '!=';
                $key = trim(substr($key, 0, -2));
            }

            if (!$this->valid_identifier($key)) {
                continue;
            }

            if ($value === null) {
                $conditions[] = "`" . $key . "` IS NULL";
            } elseif (is_int($value) || is_float($value) || (is_string($value) && preg_match('/^-?[0-9]+$/', $value))) {
                $conditions[] = "`" . $key . "` " . $operator . " " . (int) $value;
            } else {
                $conditions[] = "`" . $key . "` " . $operator . " '" . $this->mysqli->real_escape_string((string) $value) . "'";
            }
        }

        return empty($conditions) ? '' : ' WHERE ' . implode(' AND ', $conditions);
    }

    protected function valid_identifier($identifier)
    {
        return is_string($identifier) && preg_match('/^[A-Za-z0-9_]+$/', $identifier);
    }
}

class Phase_2U64_Load_Stub
{
    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    public function helper($name)
    {
        $file = APPPATH . 'helpers/' . $name . '_helper.php';
        if (is_file($file)) {
            require_once $file;
        }
    }

    public function model($name, $alias = null)
    {
        $file = APPPATH . 'models/' . $name . '.php';
        if (is_file($file)) {
            require_once $file;
            $property = $alias !== null ? $alias : $name;
            $this->ci->{$property} = new $name();
            $this->ci->{$property}->db = $this->ci->db;
        }
    }
}

class Phase_2U64_CI_Stub
{
    public $db;
    public $load;
    public $youngo_translation_model;
}

if (!function_exists('site_url')) {
    function site_url($path = '')
    {
        return '/' . ltrim((string) $path, '/');
    }
}

if (!function_exists('slugify')) {
    function slugify($text)
    {
        $text = trim((string) $text);
        $text = preg_replace('/[^A-Za-z0-9]+/', '-', $text);
        $text = strtolower(trim($text, '-'));

        return $text === '' ? 'course' : $text;
    }
}

$ci_stub = null;
if (!function_exists('get_instance')) {
    function &get_instance()
    {
        global $ci_stub;
        return $ci_stub;
    }
}

if (!class_exists('CI_Model')) {
    class CI_Model
    {
        public $db;

        public function __construct()
        {
        }
    }
}

$files = array(
    'content_helper' => $root . '/application/helpers/youngo_frontend_content_helper.php',
    'language_helper' => $root . '/application/helpers/youngo_frontend_language_helper.php',
    'translation_model' => $root . '/application/models/Youngo_translation_model.php',
    'home_controller' => $root . '/application/controllers/Home.php',
    'common_helper' => $root . '/application/helpers/common_helper.php',
    'routes' => $root . '/application/config/routes.php',
    'course_page' => $root . '/application/views/frontend/youngo/course_page.php',
    'courses_page' => $root . '/application/views/frontend/youngo/courses_page.php',
    'course_card' => $root . '/application/views/frontend/youngo/course_listing/course_card.php',
    'filter_panel' => $root . '/application/views/frontend/youngo/course_listing/filter_panel.php',
    'sorting_bar' => $root . '/application/views/frontend/youngo/course_listing/sorting_bar.php',
    'my_wishlist' => $root . '/application/views/frontend/youngo/my_wishlist.php',
    'wishlist_items' => $root . '/application/views/frontend/youngo/wishlist_items.php',
);

$file_status = array();
foreach ($files as $label => $path) {
    $file_status[$label] = is_file($path);
    phase_2u64_assert($failures, $file_status[$label], 'Expected file missing: ' . $path);
}
phase_2u64_print('Required file availability', $file_status);

if (is_file($files['language_helper'])) {
    require_once $files['language_helper'];
}

$config_file = $root . '/application/config/database.php';
$mysqli = null;
if (is_file($config_file)) {
    require $config_file;
    $config = isset($db['default']) ? $db['default'] : array();
    $mysqli = @new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
    if ($mysqli->connect_errno) {
        $warnings[] = 'Database connection failed without exposing credentials; sample shaping checks skipped.';
    } else {
        $mysqli->set_charset('utf8');
        $ci_stub = new Phase_2U64_CI_Stub();
        $ci_stub->db = new Phase_2U64_Db_Stub($mysqli);
        $ci_stub->load = new Phase_2U64_Load_Stub($ci_stub);
    }
} else {
    $warnings[] = 'database.php missing; sample shaping checks skipped.';
}

if (is_file($files['content_helper'])) {
    require_once $files['content_helper'];
}

$expected_functions = array(
    'youngo_frontend_content_language',
    'youngo_frontend_translate_course_row',
    'youngo_frontend_translate_course_rows',
    'youngo_frontend_translate_category_row',
    'youngo_frontend_translate_category_rows',
    'youngo_frontend_translate_section_row',
    'youngo_frontend_translate_section_rows',
    'youngo_frontend_translate_lesson_row',
    'youngo_frontend_translate_lesson_rows',
    'youngo_frontend_translate_learner_course_item',
    'youngo_frontend_translate_learner_course_items',
    'youngo_frontend_course_detail_url',
    'youngo_frontend_courses_url',
    'youngo_frontend_search_url',
);

$function_status = array();
foreach ($expected_functions as $function) {
    $function_status[$function] = function_exists($function);
    phase_2u64_assert($failures, $function_status[$function], 'Expected content helper function missing: ' . $function);
}
phase_2u64_print('Content helper function availability', $function_status);

$content_helper_source = is_file($files['content_helper']) ? file_get_contents($files['content_helper']) : '';
$home_source = is_file($files['home_controller']) ? file_get_contents($files['home_controller']) : '';
$common_source = is_file($files['common_helper']) ? file_get_contents($files['common_helper']) : '';
$routes_source = is_file($files['routes']) ? file_get_contents($files['routes']) : '';

$helper_source_checks = array(
    'uses_youngo_translation_model_marker' => phase_2u64_contains($content_helper_source, 'Youngo_translation_model'),
    'uses_course_fallback' => phase_2u64_contains($content_helper_source, 'get_course_translation_with_fallback'),
    'uses_category_fallback' => phase_2u64_contains($content_helper_source, 'get_category_translation_with_fallback'),
    'uses_section_fallback' => phase_2u64_contains($content_helper_source, 'get_section_translation_with_fallback'),
    'uses_lesson_fallback' => phase_2u64_contains($content_helper_source, 'get_lesson_translation_with_fallback'),
    'preserves_canonical_fields' => phase_2u64_contains($content_helper_source, 'youngo_canonical_'),
    'does_not_call_get_phrase' => !phase_2u64_contains($content_helper_source, 'get_phrase('),
    'does_not_call_db_write_methods' => !preg_match('/->\s*(insert|update|delete|replace)\s*\(/i', $content_helper_source),
    'does_not_emit_arabic_translated' => !preg_match('/arabic_translated/', $content_helper_source),
);
phase_2u64_print('Content helper source checks', $helper_source_checks);
foreach ($helper_source_checks as $label => $ok) {
    phase_2u64_assert($failures, $ok, 'Content helper source check failed: ' . $label);
}

$home_integration_checks = array(
    'loads_content_helper' => phase_2u64_contains($home_source, "helper('youngo_frontend_content')"),
    'home_sets_language_context' => phase_2u64_contains($home_source, "\$page_data['youngo_frontend_language']") && phase_2u64_contains($home_source, 'public function home()'),
    'courses_shape_course_rows' => phase_2u64_contains($home_source, 'public function courses()') && phase_2u64_contains($home_source, 'youngo_frontend_translate_course_rows'),
    'course_detail_sets_language_context' => phase_2u64_contains($home_source, 'public function course($slug = "", $course_id = "")') && phase_2u64_contains($home_source, "\$page_data['course_id']"),
    'my_courses_shape_learner_items' => phase_2u64_contains($home_source, 'public function my_courses()') && phase_2u64_contains($home_source, 'youngo_frontend_translate_learner_course_items'),
    'my_access_shape_learner_items' => phase_2u64_contains($home_source, 'public function my_access()') && phase_2u64_contains($home_source, 'youngo_frontend_translate_learner_course_items'),
    'wishlist_shapes_course_rows' => phase_2u64_contains($home_source, 'public function my_wishlist()') && phase_2u64_contains($home_source, 'youngo_frontend_translate_course_rows'),
    'search_shapes_course_rows' => phase_2u64_contains($home_source, 'public function search($search_string = "")') && phase_2u64_contains($home_source, 'youngo_frontend_search_url'),
);
phase_2u64_print('Home.php integration checks', $home_integration_checks);
foreach ($home_integration_checks as $label => $ok) {
    phase_2u64_assert($failures, $ok, 'Home.php integration check failed: ' . $label);
}

$view_integration_checks = array(
    'homepage_featured_categories_shape_rows' => phase_2u64_contains($common_source, 'youngo_frontend_translate_category_rows'),
    'homepage_featured_courses_shape_rows' => phase_2u64_contains($common_source, 'youngo_frontend_translate_course_rows'),
    'course_page_shapes_course' => phase_2u64_file_contains($files['course_page'], 'youngo_frontend_translate_course_row'),
    'course_page_shapes_sections' => phase_2u64_file_contains($files['course_page'], 'youngo_frontend_translate_section_rows'),
    'course_page_shapes_lessons' => phase_2u64_file_contains($files['course_page'], 'youngo_frontend_translate_lesson_rows'),
    'course_page_shapes_related_courses' => phase_2u64_file_contains($files['course_page'], 'youngo_frontend_translate_course_rows'),
    'course_card_shapes_category' => phase_2u64_file_contains($files['course_card'], 'youngo_frontend_translate_category_row'),
    'filter_panel_shapes_categories' => phase_2u64_file_contains($files['filter_panel'], 'youngo_frontend_translate_category_rows'),
    'wishlist_shapes_course_rows' => phase_2u64_file_contains($files['my_wishlist'], 'youngo_frontend_translate_course_rows'),
    'wishlist_items_shapes_course_rows' => phase_2u64_file_contains($files['wishlist_items'], 'youngo_frontend_translate_course_row'),
);
phase_2u64_print('View/helper integration checks', $view_integration_checks);
foreach ($view_integration_checks as $label => $ok) {
    phase_2u64_assert($failures, $ok, 'View/helper integration check failed: ' . $label);
}

$route_boundary_checks = array(
    'routes_not_changed_in_git_diff' => true,
    'no_en_routes' => is_file($files['routes']) && !preg_match("/\\\$route\\[['\\\"]en(?:\\/|['\\\"])/", $routes_source),
    'ar_aliases_remain' => phase_2u64_contains($routes_source, "\$route['ar/courses'] = 'home/courses';") && phase_2u64_contains($routes_source, "\$route['ar/course/(:any)/(:num)'] = 'home/course/\$1/\$2';"),
    'no_arabic_translated_route_usage' => !phase_2u64_contains($routes_source, 'arabic_translated'),
    'no_ar_payment_checkout_cart_coupon_aliases' => !preg_match("/\\\$route\\[['\\\"]ar\\/(?:home\\/)?(?:payment|paypal|stripe|paymob|razorpay|paystack|flutterwave|course_payment|shopping_cart|update_cart|apply_coupon|remove_coupon|checkout|confirm_payment|webhook|handle_cart_items|coupon)/i", $routes_source),
);
$diff_files = array();
exec('git -C ' . escapeshellarg($root) . ' diff --name-only', $diff_files);
if (in_array('application/config/routes.php', $diff_files, true)) {
    $route_boundary_checks['routes_not_changed_in_git_diff'] = false;
}
phase_2u64_print('Route and protected endpoint checks', $route_boundary_checks);
foreach ($route_boundary_checks as $label => $ok) {
    phase_2u64_assert($failures, $ok, 'Route boundary check failed: ' . $label);
}

$changed_file_status = array(
    'changed_files' => $diff_files,
    'forbidden_files' => array(),
);
$allowed_files = array(
    '.gitignore',
    'application/controllers/Home.php',
    'application/helpers/common_helper.php',
    'application/helpers/youngo_frontend_content_helper.php',
    'application/views/frontend/youngo/course_listing/course_card.php',
    'application/views/frontend/youngo/course_listing/filter_panel.php',
    'application/views/frontend/youngo/course_listing/sorting_bar.php',
    'application/views/frontend/youngo/course_page.php',
    'application/views/frontend/youngo/courses_page.php',
    'application/views/frontend/youngo/my_wishlist.php',
    'application/views/frontend/youngo/wishlist_items.php',
    'scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php',
    'scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php',
    'scripts/phase_2/youngo_phase_2u6_frontend_language_context_diagnostic.php',
    'scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php',
    'application/helpers/youngo_frontend_language_helper.php',
    'application/views/frontend/youngo/index.php',
    'application/views/frontend/youngo/header.php',
    'assets/frontend/youngo/css/youngo.css',
    'scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php',
);
foreach ($diff_files as $file) {
    if (!in_array($file, $allowed_files, true)) {
        $changed_file_status['forbidden_files'][] = $file;
    }
}
phase_2u64_print('Git file scope checks', $changed_file_status);
phase_2u64_assert($failures, empty($changed_file_status['forbidden_files']), 'Unexpected files changed: ' . implode(', ', $changed_file_status['forbidden_files']));

$sample_status = array();
if ($mysqli !== null && $ci_stub !== null && function_exists('youngo_frontend_translate_course_row')) {
    $course = phase_2u64_first_row($mysqli, 'course');
    $category = phase_2u64_first_row($mysqli, 'category');
    $section = phase_2u64_first_row($mysqli, 'section');
    $lesson = phase_2u64_first_row($mysqli, 'lesson');

    $translated_course = youngo_frontend_translate_course_row($course, 'arabic');
    $translated_category = youngo_frontend_translate_category_row($category, 'arabic');
    $translated_section = youngo_frontend_translate_section_row($section, 'arabic');
    $translated_lesson = youngo_frontend_translate_lesson_row($lesson, 'arabic');

    $sample_status['course'] = array(
        'sample_id' => isset($course['id']) ? (int) $course['id'] : 0,
        'id_preserved' => isset($course['id'], $translated_course['id']) && (int) $course['id'] === (int) $translated_course['id'],
        'media_thumbnail_preserved' => !isset($course['thumbnail']) || (isset($translated_course['thumbnail']) && $translated_course['thumbnail'] === $course['thumbnail']),
        'access_mode_preserved' => !isset($course['youngo_access_mode']) || (isset($translated_course['youngo_access_mode']) && $translated_course['youngo_access_mode'] === $course['youngo_access_mode']),
        'price_preserved' => !isset($course['price']) || (isset($translated_course['price']) && $translated_course['price'] === $course['price']),
        'fallback_meta_present' => isset($translated_course['youngo_translation_requested_language'], $translated_course['youngo_translation_resolved_language'], $translated_course['youngo_translation_source']),
    );
    $sample_status['category'] = array(
        'sample_id' => isset($category['id']) ? (int) $category['id'] : 0,
        'id_preserved' => isset($category['id'], $translated_category['id']) && (int) $category['id'] === (int) $translated_category['id'],
        'canonical_slug_preserved' => !isset($category['slug']) || (isset($translated_category['slug']) && $translated_category['slug'] === $category['slug']),
        'fallback_meta_present' => isset($translated_category['youngo_translation_requested_language'], $translated_category['youngo_translation_resolved_language']),
    );
    $sample_status['section'] = array(
        'sample_id' => isset($section['id']) ? (int) $section['id'] : 0,
        'id_preserved' => isset($section['id'], $translated_section['id']) && (int) $section['id'] === (int) $translated_section['id'],
        'course_id_preserved' => !isset($section['course_id']) || (isset($translated_section['course_id']) && $translated_section['course_id'] === $section['course_id']),
        'fallback_meta_present' => isset($translated_section['youngo_translation_requested_language'], $translated_section['youngo_translation_resolved_language']),
    );
    $sample_status['lesson'] = array(
        'sample_id' => isset($lesson['id']) ? (int) $lesson['id'] : 0,
        'id_preserved' => isset($lesson['id'], $translated_lesson['id']) && (int) $lesson['id'] === (int) $translated_lesson['id'],
        'course_id_preserved' => !isset($lesson['course_id']) || (isset($translated_lesson['course_id']) && $translated_lesson['course_id'] === $lesson['course_id']),
        'section_id_preserved' => !isset($lesson['section_id']) || (isset($translated_lesson['section_id']) && $translated_lesson['section_id'] === $lesson['section_id']),
        'lesson_type_preserved' => !isset($lesson['lesson_type']) || (isset($translated_lesson['lesson_type']) && $translated_lesson['lesson_type'] === $lesson['lesson_type']),
        'fallback_meta_present' => isset($translated_lesson['youngo_translation_requested_language'], $translated_lesson['youngo_translation_resolved_language']),
    );

    foreach ($sample_status as $entity => $checks) {
        foreach ($checks as $check => $ok) {
            if ($check === 'sample_id') {
                continue;
            }
            phase_2u64_assert($failures, $ok, 'Sample shaping failed for ' . $entity . ': ' . $check);
        }
    }
} else {
    $warnings[] = 'Sample shaping checks skipped because DB/model/helper setup was unavailable.';
}
phase_2u64_print('Read-only sample shaping checks', $sample_status);

$translation_language_status = array();
if ($mysqli !== null) {
    foreach (array('youngo_course_translations', 'youngo_category_translations', 'youngo_section_translations', 'youngo_lesson_translations') as $table) {
        $codes = phase_2u64_language_codes($mysqli, $table);
        $unexpected = array_values(array_diff($codes, array('english', 'arabic')));
        $translation_language_status[$table] = array(
            'codes' => $codes,
            'unexpected_codes' => $unexpected,
            'ok' => empty($unexpected),
        );
        phase_2u64_assert($failures, empty($unexpected), $table . ' has unexpected translation language codes.');
    }
}
phase_2u64_print('Translation table language-code checks', $translation_language_status);

$protected_counts = array();
if ($mysqli !== null) {
    foreach (array('payment', 'watch_histories', 'watched_duration', 'youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants', 'youngo_checkout_orders', 'youngo_coupon_usages', 'youngo_coupon_subscription_plans', 'youngo_coupon_courses') as $table) {
        $protected_counts[$table] = phase_2u64_count($mysqli, $table);
        phase_2u64_assert($failures, $protected_counts[$table] === 0, $table . ' should remain 0 after frontend content shaping.');
    }
}
phase_2u64_print('Protected table count checks', $protected_counts);

$subprocess_checks = array(
    'arabic_route_alias' => phase_2u64_run_php_script($root, 'scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php'),
    'phase_2s_route_cta_boundary' => phase_2u64_run_php_script($root, 'scripts/phase_2/youngo_phase_2s_route_cta_boundary_diagnostic.php'),
    'phase_2p_learner_access_visibility' => phase_2u64_run_php_script($root, 'scripts/phase_2/youngo_phase_2p_learner_access_visibility_diagnostic.php'),
    'phase_2r_admin_entitlement_summary' => phase_2u64_run_php_script($root, 'scripts/phase_2/youngo_phase_2r_admin_entitlement_summary_diagnostic.php'),
);
phase_2u64_print('Compatibility diagnostic subprocess checks', $subprocess_checks);
foreach ($subprocess_checks as $label => $status) {
    phase_2u64_assert($failures, $status['passes'], 'Compatibility diagnostic failed: ' . $label);
}

phase_2u64_print('Warnings', $warnings);
phase_2u64_print('Read-only safety', array(
    'result' => 'Diagnostic used file reads, helper pure-function calls, git diff inspection, SELECT/SHOW-only queries, and read-only diagnostic subprocesses. It did not call get_phrase(), submit forms, run SQL writes, or modify data.',
));
phase_2u64_print('Result', array(
    'status' => empty($failures) ? 'PASS' : 'FAIL',
    'failures' => $failures,
));

exit(empty($failures) ? 0 : 1);
