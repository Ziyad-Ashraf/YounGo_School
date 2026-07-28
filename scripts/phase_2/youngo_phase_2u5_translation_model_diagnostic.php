<?php
/**
 * Phase 2U.5 translation model diagnostic.
 *
 * Read-only checks for the YounGo bilingual translation model foundation.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$root = dirname(dirname(__DIR__));
define('ENVIRONMENT', 'development');
define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'config/database.php';

$config = $db['default'];
$mysqli = @new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
if ($mysqli->connect_errno) {
    echo "== Connection ==\n";
    echo json_encode(array('connected' => false, 'error' => 'DB connection failed without exposing credentials.')) . "\n";
    exit(2);
}
$mysqli->set_charset('utf8');

$failures = array();
$warnings = array();

class U5_Diag_Query_Result
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

class U5_Diag_Db_Stub
{
    protected $mysqli;
    protected $wheres = array();

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function table_exists($table)
    {
        return u5_diag_table_exists($this->mysqli, $table);
    }

    public function where($key, $value)
    {
        $this->wheres[] = array($key, $value);
        return $this;
    }

    public function get($table, $limit = null)
    {
        if (!$this->valid_identifier($table)) {
            return new U5_Diag_Query_Result(array());
        }

        $sql = "SELECT * FROM `" . $table . "`" . $this->where_sql();
        if ($limit !== null) {
            $sql .= " LIMIT " . max(0, (int) $limit);
        }

        $this->wheres = array();
        $rows = u5_diag_query($this->mysqli, $sql);

        return new U5_Diag_Query_Result(is_array($rows) && !isset($rows['error']) ? $rows : array());
    }

    public function count_all_results($table)
    {
        if (!$this->valid_identifier($table)) {
            $this->wheres = array();
            return 0;
        }

        $sql = "SELECT COUNT(*) AS c FROM `" . $table . "`" . $this->where_sql();
        $this->wheres = array();
        $rows = u5_diag_query($this->mysqli, $sql);

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

function u5_diag_section($title, $data)
{
    echo "\n== " . $title . " ==\n";
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function u5_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function u5_diag_query($mysqli, $sql)
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

function u5_diag_table_exists($mysqli, $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    $rows = u5_diag_query($mysqli, "SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function u5_diag_column_exists($mysqli, $table, $column)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column) || !u5_diag_table_exists($mysqli, $table)) {
        return false;
    }

    $rows = u5_diag_query($mysqli, "SHOW COLUMNS FROM `" . $table . "` LIKE '" . $mysqli->real_escape_string($column) . "'");
    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

function u5_diag_count($mysqli, $table, $where = '')
{
    if (!u5_diag_table_exists($mysqli, $table)) {
        return null;
    }

    $sql = "SELECT COUNT(*) AS c FROM `" . $table . "`";
    if ($where !== '') {
        $sql .= " WHERE " . $where;
    }

    $rows = u5_diag_query($mysqli, $sql);
    return isset($rows[0]['c']) ? (int) $rows[0]['c'] : null;
}

function u5_diag_setting($mysqli, $key)
{
    if (!u5_diag_table_exists($mysqli, 'settings')) {
        return null;
    }

    $rows = u5_diag_query($mysqli, "SELECT `value` FROM `settings` WHERE `key` = '" . $mysqli->real_escape_string($key) . "' LIMIT 1");
    return isset($rows[0]['value']) ? $rows[0]['value'] : null;
}

function u5_diag_first_id($mysqli, $table)
{
    if (!u5_diag_table_exists($mysqli, $table)) {
        return null;
    }

    $rows = u5_diag_query($mysqli, "SELECT id FROM `" . $table . "` ORDER BY id ASC LIMIT 1");
    return isset($rows[0]['id']) ? (int) $rows[0]['id'] : null;
}

function u5_diag_translation_row_exists($mysqli, $table, $entity_column, $entity_id, $language_code)
{
    if (!u5_diag_table_exists($mysqli, $table)) {
        return false;
    }

    $rows = u5_diag_query($mysqli, "
        SELECT id
        FROM `" . $table . "`
        WHERE `" . $entity_column . "` = " . (int) $entity_id . "
          AND language_code = '" . $mysqli->real_escape_string($language_code) . "'
        LIMIT 1
    ");

    return is_array($rows) && count($rows) > 0 && !isset($rows['error']);
}

$model_file = APPPATH . 'models' . DIRECTORY_SEPARATOR . 'Youngo_translation_model.php';
$model_exists = file_exists($model_file);

$expected_methods = array(
    'normalize_language_code',
    'get_supported_language_codes',
    'get_course_translation',
    'get_category_translation',
    'get_section_translation',
    'get_lesson_translation',
    'get_course_translation_with_fallback',
    'get_category_translation_with_fallback',
    'get_section_translation_with_fallback',
    'get_lesson_translation_with_fallback',
    'upsert_course_translation',
    'upsert_category_translation',
    'upsert_section_translation',
    'upsert_lesson_translation',
    'generate_slug',
    'is_slug_available',
    'has_translation',
    'get_missing_translation_summary',
);

$model_load = array(
    'file_exists' => $model_exists,
    'class_loaded' => false,
    'instantiated_with_cli_stub' => false,
);
$method_status = array();
$normalize_status = array();
$slug_status = array();
$model_read_status = array();

if ($model_exists) {
    if (!class_exists('CI_Model')) {
        class CI_Model
        {
            public $db;

            public function __construct()
            {
            }
        }
    }

    require_once $model_file;

    $model_load['class_loaded'] = class_exists('Youngo_translation_model');
    if ($model_load['class_loaded']) {
        $translation_model = new Youngo_translation_model();
        $translation_model->db = new U5_Diag_Db_Stub($mysqli);
        $model_load['instantiated_with_cli_stub'] = is_object($translation_model);

        foreach ($expected_methods as $method) {
            $method_status[$method] = method_exists($translation_model, $method);
        }

        $normalize_inputs = array(
            'en' => 'english',
            'ar' => 'arabic',
            'arabic_translated' => 'arabic',
            '' => 'english',
            'unknown' => 'english',
        );

        foreach ($normalize_inputs as $input => $expected) {
            $actual = $translation_model->normalize_language_code($input);
            $normalize_status[$input === '' ? '(empty)' : $input] = array(
                'expected' => $expected,
                'actual' => $actual,
                'ok' => $actual === $expected,
            );
        }

        $slug_status['english'] = $translation_model->generate_slug('Scratch Coding for Young Creators', 'english');
        $slug_status['arabic'] = $translation_model->generate_slug('تعلم البرمجة للأطفال', 'arabic');

        $model_read_samples = array(
            'course' => array('id' => u5_diag_first_id($mysqli, 'course'), 'get' => 'get_course_translation', 'fallback' => 'get_course_translation_with_fallback'),
            'category' => array('id' => u5_diag_first_id($mysqli, 'category'), 'get' => 'get_category_translation', 'fallback' => 'get_category_translation_with_fallback'),
            'section' => array('id' => u5_diag_first_id($mysqli, 'section'), 'get' => 'get_section_translation', 'fallback' => 'get_section_translation_with_fallback'),
            'lesson' => array('id' => u5_diag_first_id($mysqli, 'lesson'), 'get' => 'get_lesson_translation', 'fallback' => 'get_lesson_translation_with_fallback'),
        );

        foreach ($model_read_samples as $entity_type => $sample) {
            $sample_id = (int) $sample['id'];
            $get_method = $sample['get'];
            $fallback_method = $sample['fallback'];

            $english = $sample_id > 0 ? $translation_model->{$get_method}($sample_id, 'english') : null;
            $arabic_fallback = $sample_id > 0 ? $translation_model->{$fallback_method}($sample_id, 'arabic') : null;
            $missing = $translation_model->{$fallback_method}(999999999, 'arabic');

            $model_read_status[$entity_type] = array(
                'sample_id' => $sample_id,
                'english_read_ok' => is_array($english) && !empty($english),
                'arabic_fallback_ok' => is_array($arabic_fallback)
                    && isset($arabic_fallback['requested_language'], $arabic_fallback['resolved_language'], $arabic_fallback['is_fallback'], $arabic_fallback['missing_translation'], $arabic_fallback['translation_source'])
                    && $arabic_fallback['requested_language'] === 'arabic'
                    && $arabic_fallback['resolved_language'] === 'english'
                    && $arabic_fallback['is_fallback'] === true
                    && $arabic_fallback['missing_translation'] === true,
                'missing_entity_safe_null' => $missing === null,
            );
        }
    }
}

$translation_tables = array(
    'youngo_course_translations' => array('canonical_table' => 'course', 'entity_column' => 'course_id'),
    'youngo_category_translations' => array('canonical_table' => 'category', 'entity_column' => 'category_id'),
    'youngo_section_translations' => array('canonical_table' => 'section', 'entity_column' => 'section_id'),
    'youngo_lesson_translations' => array('canonical_table' => 'lesson', 'entity_column' => 'lesson_id'),
);

$translation_state = array();
$fallback_state = array();
foreach ($translation_tables as $table => $table_config) {
    $canonical_table = $table_config['canonical_table'];
    $entity_column = $table_config['entity_column'];
    $sample_id = u5_diag_first_id($mysqli, $canonical_table);

    $translation_state[$table] = array(
        'exists' => u5_diag_table_exists($mysqli, $table),
        'canonical_table' => $canonical_table,
        'canonical_count' => u5_diag_count($mysqli, $canonical_table),
        'english_rows' => u5_diag_count($mysqli, $table, "language_code = 'english'"),
        'arabic_rows' => u5_diag_count($mysqli, $table, "language_code = 'arabic'"),
        'sample_entity_id' => $sample_id,
        'sample_english_exists' => $sample_id ? u5_diag_translation_row_exists($mysqli, $table, $entity_column, $sample_id, 'english') : false,
        'sample_arabic_exists' => $sample_id ? u5_diag_translation_row_exists($mysqli, $table, $entity_column, $sample_id, 'arabic') : false,
    );

    $fallback_state[$table] = array(
        'existing_english_translation_available' => $translation_state[$table]['sample_english_exists'],
        'missing_arabic_would_fallback_to_english' => $translation_state[$table]['sample_english_exists'] && !$translation_state[$table]['sample_arabic_exists'],
        'missing_entity_safe_null_expected' => true,
    );
}

$language_state = array(
    'language_table_exists' => u5_diag_table_exists($mysqli, 'language'),
    'english_column_exists' => u5_diag_column_exists($mysqli, 'language', 'english'),
    'arabic_column_exists' => u5_diag_column_exists($mysqli, 'language', 'arabic'),
    'settings_language' => u5_diag_setting($mysqli, 'language'),
);

$protected_counts = array(
    'users' => u5_diag_count($mysqli, 'users'),
    'course' => u5_diag_count($mysqli, 'course'),
    'category' => u5_diag_count($mysqli, 'category'),
    'section' => u5_diag_count($mysqli, 'section'),
    'lesson' => u5_diag_count($mysqli, 'lesson'),
    'enrol' => u5_diag_count($mysqli, 'enrol'),
    'payment' => u5_diag_count($mysqli, 'payment'),
    'watch_histories' => u5_diag_count($mysqli, 'watch_histories'),
    'watched_duration' => u5_diag_count($mysqli, 'watched_duration'),
    'youngo_course_access' => u5_diag_count($mysqli, 'youngo_course_access'),
    'youngo_user_subscriptions' => u5_diag_count($mysqli, 'youngo_user_subscriptions'),
    'youngo_manual_grants' => u5_diag_count($mysqli, 'youngo_manual_grants'),
    'youngo_checkout_orders' => u5_diag_count($mysqli, 'youngo_checkout_orders'),
    'youngo_coupon_usages' => u5_diag_count($mysqli, 'youngo_coupon_usages'),
);

u5_diag_assert($failures, $model_exists, 'Youngo_translation_model.php is missing.');
u5_diag_assert($failures, !empty($model_load['class_loaded']), 'Youngo_translation_model class could not be loaded.');
u5_diag_assert($failures, !empty($model_load['instantiated_with_cli_stub']), 'Youngo_translation_model could not be instantiated with CLI stub.');

foreach ($expected_methods as $method) {
    u5_diag_assert($failures, !empty($method_status[$method]), 'Expected model method missing: ' . $method);
}

foreach ($normalize_status as $input => $status) {
    u5_diag_assert($failures, !empty($status['ok']), 'Language normalization failed for input: ' . $input);
}

foreach ($model_read_status as $entity_type => $status) {
    u5_diag_assert($failures, !empty($status['english_read_ok']), $entity_type . ' English read method did not return a seeded row.');
    u5_diag_assert($failures, !empty($status['arabic_fallback_ok']), $entity_type . ' fallback method did not return expected English fallback metadata for missing Arabic.');
    u5_diag_assert($failures, !empty($status['missing_entity_safe_null']), $entity_type . ' fallback method did not return safe null for a missing entity.');
}

u5_diag_assert($failures, !empty($slug_status['english']), 'English slug helper returned an empty value.');
u5_diag_assert($failures, !empty($slug_status['arabic']), 'Arabic slug helper returned an empty value.');
u5_diag_assert($failures, !empty($language_state['arabic_column_exists']), 'language.arabic column is missing.');
u5_diag_assert($failures, $language_state['settings_language'] === 'english', 'Default settings.language is not english.');

foreach ($translation_state as $table => $state) {
    u5_diag_assert($failures, !empty($state['exists']), $table . ' is missing.');
    u5_diag_assert($failures, (int) $state['english_rows'] >= (int) $state['canonical_count'], $table . ' English coverage is lower than canonical count.');
    if ($table !== 'youngo_course_translations') {
        u5_diag_assert($failures, (int) $state['arabic_rows'] === 0, $table . ' has Arabic content rows outside its scoped form phase.');
    }
    u5_diag_assert($failures, !empty($state['sample_english_exists']), $table . ' does not have a sample English row to read.');
}

u5_diag_assert($failures, (int) $protected_counts['youngo_course_access'] === 0, 'youngo_course_access is not empty.');
u5_diag_assert($failures, (int) $protected_counts['youngo_user_subscriptions'] === 0, 'youngo_user_subscriptions is not empty.');
u5_diag_assert($failures, (int) $protected_counts['youngo_manual_grants'] === 0, 'youngo_manual_grants is not empty.');
u5_diag_assert($failures, (int) $protected_counts['youngo_checkout_orders'] === 0, 'youngo_checkout_orders is not empty.');
u5_diag_assert($failures, (int) $protected_counts['payment'] === 0, 'payment table is not empty.');

u5_diag_section('Connection', array(
    'connected' => true,
    'database_name' => $config['database'],
    'server_version' => $mysqli->server_info,
));
u5_diag_section('Model load', $model_load);
u5_diag_section('Expected method availability', $method_status);
u5_diag_section('Language normalization', $normalize_status);
u5_diag_section('Model read and fallback method smoke', $model_read_status);
u5_diag_section('Slug helper smoke', $slug_status);
u5_diag_section('Translation table state', $translation_state);
u5_diag_section('Fallback readiness checks', $fallback_state);
u5_diag_section('Language state', $language_state);
u5_diag_section('Protected table counts', $protected_counts);
u5_diag_section('Warnings', $warnings);
u5_diag_section('Read-only safety', array(
    'result' => 'Diagnostic used file reads and SELECT/SHOW-only queries, did not call translation upsert methods, created no sessions/cookies, and did not modify data.',
));
u5_diag_section('Result', array(
    'status' => empty($failures) ? 'PASS' : 'FAIL',
    'failures' => $failures,
));

exit(empty($failures) ? 0 : 1);
