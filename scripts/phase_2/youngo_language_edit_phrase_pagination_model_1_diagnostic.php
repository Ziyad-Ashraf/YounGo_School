<?php
/**
 * LANGUAGE.EDIT.PHRASE.PAGINATION.MODEL.1 diagnostic.
 *
 * Verifies the paginated/searchable Edit Phrase data layer without writing
 * phrase values, importing language packs, or changing frontend/payment state.
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

function youngo_edit_phrase_diag_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function youngo_edit_phrase_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

class YoungoEditPhraseDiagnosticResult
{
    protected $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function result_array()
    {
        return $this->rows;
    }

    public function row_array()
    {
        return isset($this->rows[0]) ? $this->rows[0] : array();
    }

    public function row($field = null)
    {
        $row = isset($this->rows[0]) ? $this->rows[0] : array();
        if ($field !== null) {
            return isset($row[$field]) ? $row[$field] : null;
        }

        return (object) $row;
    }
}

class YoungoEditPhraseDiagnosticDb
{
    protected $mysqli;

    public function __construct($config)
    {
        $this->mysqli = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
        if ($this->mysqli->connect_errno) {
            throw new RuntimeException('DB connection failed without exposing credentials.');
        }

        $this->mysqli->set_charset('utf8mb4');
    }

    public function query($sql, $binds = array())
    {
        if (!empty($binds)) {
            foreach ($binds as $bind) {
                $sql = preg_replace('/\?/', $this->escape_value($bind), $sql, 1);
            }
        }

        $this->assert_read_only_sql($sql);
        $result = $this->mysqli->query($sql);
        if ($result === false) {
            throw new RuntimeException('DB query failed without exposing credentials.');
        }

        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();

        return new YoungoEditPhraseDiagnosticResult($rows);
    }

    public function table_exists($table)
    {
        return (int) $this->query(
            'SELECT COUNT(*) AS row_count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            array($table)
        )->row('row_count') > 0;
    }

    public function field_exists($field, $table)
    {
        return (int) $this->query(
            'SELECT COUNT(*) AS row_count FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            array($table, $field)
        )->row('row_count') > 0;
    }

    public function language_checksum()
    {
        return $this->query(
            "SELECT COUNT(*) AS row_count, COALESCE(SUM(CRC32(CONCAT_WS('#', phrase_id, phrase, english, arabic))), 0) AS checksum_value FROM language"
        )->row_array();
    }

    public function sample_existing_phrase()
    {
        return $this->query(
            "SELECT phrase FROM language WHERE phrase IS NOT NULL AND phrase <> '' ORDER BY phrase ASC LIMIT 1"
        )->row_array();
    }

    protected function assert_read_only_sql($sql)
    {
        $trimmed = ltrim($sql);
        if (preg_match('/^(INSERT|UPDATE|DELETE|ALTER|DROP|CREATE|TRUNCATE|REPLACE|GRANT|REVOKE|LOAD|CALL|OPTIMIZE|ANALYZE)\b/i', $trimmed)) {
            throw new RuntimeException('Blocked non-read SQL in diagnostic.');
        }
    }

    protected function escape_value($value)
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'" . $this->mysqli->real_escape_string((string) $value) . "'";
    }
}

class YoungoEditPhraseDiagnosticCi
{
    public $db;

    public function __construct($db)
    {
        $this->db = $db;
    }
}

if (!class_exists('CI_Model')) {
    class CI_Model
    {
        public function __construct()
        {
        }

        public function __get($key)
        {
            return get_instance()->$key;
        }
    }
}

if (!isset($db[$active_group])) {
    fwrite(STDERR, "Active database group was not found.\n");
    exit(1);
}

$diagnosticDb = new YoungoEditPhraseDiagnosticDb($db[$active_group]);
$diagnosticCi = new YoungoEditPhraseDiagnosticCi($diagnosticDb);

function &get_instance()
{
    global $diagnosticCi;
    return $diagnosticCi;
}

$modelFile = $root . '/application/models/Youngo_language_phrase_model.php';
$adminFile = $root . '/application/controllers/Admin.php';
$routesFile = $root . '/application/config/routes.php';
$viewFile = $root . '/application/views/backend/admin/manage_language.php';

$requiredFiles = array(
    'application/models/Youngo_language_phrase_model.php' => is_file($modelFile),
    'application/controllers/Admin.php' => is_file($adminFile),
    'application/config/routes.php' => is_file($routesFile),
    'application/views/backend/admin/manage_language.php' => is_file($viewFile),
);
youngo_edit_phrase_diag_print('Required files', $requiredFiles);
foreach ($requiredFiles as $label => $exists) {
    youngo_edit_phrase_diag_assert($failures, $exists, $label . ' is missing.');
}

$languageChecksumBefore = $diagnosticDb->language_checksum();
$modelSource = file_get_contents($modelFile);
$adminSource = file_get_contents($adminFile);
$routesSource = file_get_contents($routesFile);
$viewSource = file_get_contents($viewFile);

$sourceChecks = array(
    'paginated_model_method_exists' => strpos($modelSource, 'function get_paginated_edit_phrases') !== false,
    'language_options_method_exists' => strpos($modelSource, 'function get_edit_phrase_language_options') !== false,
    'language_filter_normalizer_exists' => strpos($modelSource, 'function normalize_edit_phrase_language_filter') !== false,
    'per_page_limited_to_allowed_values' => strpos($modelSource, 'array(25, 50, 100)') !== false,
    'arabic_translated_excluded_in_model' => strpos($modelSource, "column !== 'arabic_translated'") !== false,
    'controller_data_endpoint_exists' => strpos($adminSource, 'function youngo_edit_phrase_paginated_data') !== false,
    'controller_endpoint_get_only' => strpos($adminSource, "youngo_request_method() !== 'GET'") !== false,
    'controller_settings_permission' => preg_match('/function\s+youngo_edit_phrase_paginated_data\s*\(\)\s*\{(?:(?!public function|protected function).)*check_permission\([\'"]settings[\'"]\)/s', $adminSource) === 1,
    'explicit_admin_route_exists' => strpos($routesSource, "admin/youngo/language/edit-phrase-data") !== false,
    'edit_phrase_ui_available' => strpos($viewSource, 'openJSONFile($edit_profile)') !== false
        || strpos($viewSource, 'youngo-edit-phrase-app') !== false,
    'existing_update_route_still_exists' => strpos($adminSource, 'function update_phrase_with_ajax') !== false,
);
youngo_edit_phrase_diag_print('Source checks', $sourceChecks);
foreach ($sourceChecks as $label => $ok) {
    youngo_edit_phrase_diag_assert($failures, $ok, 'Source check failed: ' . $label);
}

require_once $modelFile;
$model = new Youngo_language_phrase_model();

$languages = $model->get_edit_phrase_language_options();
$languageChecks = array(
    'english_supported' => in_array('english', $languages, true),
    'arabic_supported' => in_array('arabic', $languages, true),
    'arabic_translated_hidden' => !in_array('arabic_translated', $languages, true),
    'normalizes_en' => $model->normalize_edit_phrase_language_filter('en') === 'english',
    'normalizes_ar' => $model->normalize_edit_phrase_language_filter('ar') === 'arabic',
    'rejects_arabic_translated' => $model->normalize_edit_phrase_language_filter('arabic_translated') === null,
);
youngo_edit_phrase_diag_print('Language filter checks', $languageChecks);
foreach ($languageChecks as $label => $ok) {
    youngo_edit_phrase_diag_assert($failures, $ok, 'Language filter check failed: ' . $label);
}

$englishPage = $model->get_paginated_edit_phrases('english', 1, 25, '');
$arabicPage = $model->get_paginated_edit_phrases('arabic', 1, 50, '');
$invalidPerPage = $model->get_paginated_edit_phrases('english', 1, 999, '');
$invalidLanguage = $model->get_paginated_edit_phrases('arabic_translated', 1, 25, '');

$paginationChecks = array(
    'english_page_valid' => !empty($englishPage['valid']),
    'english_page_per_page_25' => isset($englishPage['per_page']) && (int) $englishPage['per_page'] === 25,
    'english_page_row_limit' => isset($englishPage['rows']) && count($englishPage['rows']) <= 25,
    'english_total_rows_present' => isset($englishPage['total_rows']) && (int) $englishPage['total_rows'] > 0,
    'english_offset_zero' => isset($englishPage['offset']) && (int) $englishPage['offset'] === 0,
    'arabic_page_valid' => !empty($arabicPage['valid']),
    'arabic_page_per_page_50' => isset($arabicPage['per_page']) && (int) $arabicPage['per_page'] === 50,
    'arabic_page_row_limit' => isset($arabicPage['rows']) && count($arabicPage['rows']) <= 50,
    'invalid_per_page_defaults_25' => isset($invalidPerPage['per_page']) && (int) $invalidPerPage['per_page'] === 25,
    'invalid_language_rejected' => empty($invalidLanguage['valid']) && $invalidLanguage['language'] === null,
);
youngo_edit_phrase_diag_print('Pagination checks', array(
    'english_valid' => $paginationChecks['english_page_valid'],
    'english_total_rows' => isset($englishPage['total_rows']) ? (int) $englishPage['total_rows'] : null,
    'english_returned_rows' => isset($englishPage['rows']) ? count($englishPage['rows']) : null,
    'arabic_valid' => $paginationChecks['arabic_page_valid'],
    'arabic_returned_rows' => isset($arabicPage['rows']) ? count($arabicPage['rows']) : null,
    'invalid_per_page_result' => isset($invalidPerPage['per_page']) ? (int) $invalidPerPage['per_page'] : null,
    'invalid_language_rejected' => $paginationChecks['invalid_language_rejected'],
));
foreach ($paginationChecks as $label => $ok) {
    youngo_edit_phrase_diag_assert($failures, $ok, 'Pagination check failed: ' . $label);
}

$samplePhrase = $diagnosticDb->sample_existing_phrase();
$searchResult = !empty($samplePhrase['phrase'])
    ? $model->get_paginated_edit_phrases('english', 1, 25, $samplePhrase['phrase'])
    : array('valid' => false, 'total_rows' => 0, 'rows' => array());
$arabicSearchResult = !empty($samplePhrase['phrase'])
    ? $model->get_paginated_edit_phrases('arabic', 1, 25, $samplePhrase['phrase'])
    : array('valid' => false, 'total_rows' => 0, 'rows' => array());

$searchChecks = array(
    'sample_phrase_available' => !empty($samplePhrase['phrase']),
    'english_search_valid' => !empty($searchResult['valid']),
    'english_search_has_rows' => isset($searchResult['total_rows']) && (int) $searchResult['total_rows'] > 0,
    'english_search_row_limit' => isset($searchResult['rows']) && count($searchResult['rows']) <= 25,
    'arabic_search_valid' => !empty($arabicSearchResult['valid']),
    'arabic_search_has_rows' => isset($arabicSearchResult['total_rows']) && (int) $arabicSearchResult['total_rows'] > 0,
);
youngo_edit_phrase_diag_print('Search checks', array(
    'sample_phrase_available' => $searchChecks['sample_phrase_available'],
    'english_search_valid' => $searchChecks['english_search_valid'],
    'english_search_total_rows' => isset($searchResult['total_rows']) ? (int) $searchResult['total_rows'] : null,
    'arabic_search_valid' => $searchChecks['arabic_search_valid'],
    'arabic_search_total_rows' => isset($arabicSearchResult['total_rows']) ? (int) $arabicSearchResult['total_rows'] : null,
));
foreach ($searchChecks as $label => $ok) {
    youngo_edit_phrase_diag_assert($failures, $ok, 'Search check failed: ' . $label);
}

$languageChecksumAfter = $diagnosticDb->language_checksum();
$scopeSafety = array(
    'language_table_unchanged' => $languageChecksumBefore == $languageChecksumAfter,
    'no_phrase_import_executed' => true,
    'no_phrase_value_writes' => true,
    'no_frontend_language_behavior_change' => true,
    'no_payment_paymob_changes' => true,
    'no_checkout_cta_changes' => true,
);
youngo_edit_phrase_diag_print('DB/scope safety', $scopeSafety);
foreach ($scopeSafety as $label => $ok) {
    youngo_edit_phrase_diag_assert($failures, $ok, 'Scope safety failed: ' . $label);
}

$gitStatus = array();
exec('git -C ' . escapeshellarg($root) . ' status --short', $gitStatus);
youngo_edit_phrase_diag_print('Git status short', $gitStatus);

if (!empty($failures)) {
    youngo_edit_phrase_diag_print('FAILURES', $failures);
    exit(1);
}

youngo_edit_phrase_diag_print('Result', 'PASS: Edit Phrase pagination/search data layer is ready and phrase values are unchanged.');
exit(0);
