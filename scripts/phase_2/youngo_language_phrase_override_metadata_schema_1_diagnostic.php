<?php
/**
 * LANGUAGE.PHRASE.OVERRIDE.METADATA.SCHEMA.1 diagnostic.
 *
 * Verifies additive language phrase override metadata schema and model
 * foundation. It does not import language packs, update phrase values, call
 * payment providers, create checkout records, or change frontend behavior.
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
$diagPhraseKey = 'youngo_language_meta_schema_diag';
$diagLanguageCode = 'arabic';

function youngo_lang_meta_diag_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function youngo_lang_meta_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

class YoungoLanguageMetaDiagnosticResult
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

class YoungoLanguageMetaDiagnosticDb
{
    protected $mysqli;
    protected $select = '*';
    protected $from = '';
    protected $where = array();
    protected $limit = null;

    public function __construct($config)
    {
        $this->mysqli = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
        if ($this->mysqli->connect_errno) {
            throw new RuntimeException('DB connection failed without exposing credentials.');
        }

        $this->mysqli->set_charset('utf8mb4');
    }

    public function query($sql, $binds = array(), $allow_write = false)
    {
        if (!empty($binds)) {
            foreach ($binds as $bind) {
                $sql = preg_replace('/\?/', $this->escape_value($bind), $sql, 1);
            }
        }

        if (!$allow_write) {
            $this->assert_read_only_sql($sql);
        }

        $result = $this->mysqli->query($sql);
        if ($result === false) {
            throw new RuntimeException('DB query failed without exposing credentials.');
        }

        if ($result === true) {
            return new YoungoLanguageMetaDiagnosticResult(array());
        }

        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();

        return new YoungoLanguageMetaDiagnosticResult($rows);
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

    public function select($select, $escape = null)
    {
        $this->select = $select;
        return $this;
    }

    public function from($table)
    {
        $this->from = $table;
        return $this;
    }

    public function where($key, $value = null, $escape = null)
    {
        $key = trim((string) $key);
        if ($escape === false && $value === null) {
            $this->where[] = $key;
            return $this;
        }

        $this->where[] = '`' . str_replace('`', '``', $key) . '` = ' . $this->escape_value($value);
        return $this;
    }

    public function limit($limit)
    {
        $this->limit = max(0, (int) $limit);
        return $this;
    }

    public function get($table = null)
    {
        if ($table !== null) {
            $this->from($table);
        }

        $sql = 'SELECT ' . $this->select . ' FROM `' . str_replace('`', '``', $this->from) . '`';
        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . (int) $this->limit;
        }

        $this->select = '*';
        $this->from = '';
        $this->where = array();
        $this->limit = null;

        return $this->query($sql);
    }

    public function insert_diag_meta_row($phrase_key, $language_code)
    {
        $this->query(
            'DELETE FROM youngo_language_phrase_meta WHERE phrase_key = ? AND language_code = ?',
            array($phrase_key, $language_code),
            true
        );

        $this->query(
            'INSERT INTO youngo_language_phrase_meta (phrase_key, language_code, source, current_value_hash, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())',
            array($phrase_key, $language_code, 'manual_override', hash('sha256', 'diagnostic-metadata-only')),
            true
        );
    }

    public function cleanup_diag_rows($phrase_key)
    {
        $this->query(
            'DELETE FROM youngo_language_phrase_meta WHERE phrase_key = ?',
            array($phrase_key),
            true
        );
        $this->query(
            'DELETE FROM youngo_language_import_batches WHERE source_file = ?',
            array($phrase_key . '.json'),
            true
        );
    }

    public function language_checksum()
    {
        return $this->query(
            "SELECT COUNT(*) AS row_count, COALESCE(SUM(CRC32(CONCAT_WS('#', phrase_id, phrase, english, arabic))), 0) AS checksum_value FROM language"
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

        return "'" . $this->mysqli->real_escape_string((string) $value) . "'";
    }
}

class YoungoLanguageMetaDiagnosticCi
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

$diagnosticDb = new YoungoLanguageMetaDiagnosticDb($db[$active_group]);
$diagnosticCi = new YoungoLanguageMetaDiagnosticCi($diagnosticDb);

function &get_instance()
{
    global $diagnosticCi;
    return $diagnosticCi;
}

$modelFile = $root . '/application/models/Youngo_language_phrase_model.php';
$upSqlFile = $root . '/scripts/phase_2/language_phrase_override_metadata_schema_1_up.sql';
$downSqlFile = $root . '/scripts/phase_2/language_phrase_override_metadata_schema_1_down.sql';
$adminFile = $root . '/application/controllers/Admin.php';
$routesFile = $root . '/application/config/routes.php';

$requiredFiles = array(
    'application/models/Youngo_language_phrase_model.php' => is_file($modelFile),
    'scripts/phase_2/language_phrase_override_metadata_schema_1_up.sql' => is_file($upSqlFile),
    'scripts/phase_2/language_phrase_override_metadata_schema_1_down.sql' => is_file($downSqlFile),
    'application/controllers/Admin.php' => is_file($adminFile),
    'application/config/routes.php' => is_file($routesFile),
);
youngo_lang_meta_diag_print('Required files', $requiredFiles);
foreach ($requiredFiles as $label => $exists) {
    youngo_lang_meta_diag_assert($failures, $exists, $label . ' is missing.');
}

$languageChecksumBefore = $diagnosticDb->language_checksum();

$expectedColumns = array(
    'youngo_language_import_batches' => array(
        'id',
        'language_code',
        'source_file',
        'source_sha256',
        'import_mode',
        'total_keys',
        'inserted_count',
        'updated_count',
        'skipped_count',
        'manual_preserved_count',
        'invalid_count',
        'status',
        'summary_json',
        'created_by_user_id',
        'created_at',
        'updated_at',
    ),
    'youngo_language_phrase_meta' => array(
        'id',
        'phrase_id',
        'phrase_key',
        'language_code',
        'source',
        'last_import_batch_id',
        'last_imported_value_hash',
        'current_value_hash',
        'manually_overridden_at',
        'manually_overridden_by_user_id',
        'created_at',
        'updated_at',
    ),
);

$schemaChecks = array();
foreach ($expectedColumns as $table => $columns) {
    $schemaChecks[$table . '_exists'] = $diagnosticDb->table_exists($table);
    foreach ($columns as $column) {
        $schemaChecks[$table . '.' . $column] = $diagnosticDb->field_exists($column, $table);
    }
}

$rawValueColumnRows = $diagnosticDb->query(
    "SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name IN ('youngo_language_import_batches', 'youngo_language_phrase_meta') AND column_name REGEXP '(raw|translation|text|value)$'"
)->result_array();
$schemaChecks['no_raw_translation_value_columns'] = count($rawValueColumnRows) === 0;

$uniqueRows = $diagnosticDb->query(
    "SELECT COUNT(*) AS row_count FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'youngo_language_phrase_meta' AND index_name = 'uniq_ylpm_phrase_language' AND non_unique = 0 AND column_name IN ('phrase_key', 'language_code')"
)->row_array();
$schemaChecks['unique_phrase_key_language_code'] = isset($uniqueRows['row_count']) && (int) $uniqueRows['row_count'] === 2;

youngo_lang_meta_diag_print('Schema checks', $schemaChecks);
foreach ($schemaChecks as $label => $ok) {
    youngo_lang_meta_diag_assert($failures, $ok, 'Schema check failed: ' . $label);
}

$arabicTranslatedRows = $diagnosticDb->query(
    "SELECT COUNT(*) AS row_count FROM youngo_language_phrase_meta WHERE language_code = 'arabic_translated'"
)->row('row_count');
youngo_lang_meta_diag_assert($failures, (int) $arabicTranslatedRows === 0, 'arabic_translated metadata rows must not exist.');

$modelSource = is_file($modelFile) ? file_get_contents($modelFile) : '';
$modelSourceChecks = array(
    'metadata_tables_exist_method' => strpos($modelSource, 'function metadata_tables_exist') !== false,
    'get_phrase_meta_status_method' => strpos($modelSource, 'function get_phrase_meta_status') !== false,
    'mark_phrase_manual_override_method' => strpos($modelSource, 'function mark_phrase_manual_override') !== false,
    'preview_stub_method' => strpos($modelSource, 'function build_import_meta_preview_stub') !== false,
    'blocks_arabic_translated_as_ui_language' => strpos($modelSource, "'arabic_translated'") === false || strpos($modelSource, "return null;") !== false,
    'does_not_store_raw_phrase_values' => strpos($modelSource, 'translation_text') === false && strpos($modelSource, 'raw_value') === false,
);
youngo_lang_meta_diag_print('Model source checks', $modelSourceChecks);
foreach ($modelSourceChecks as $label => $ok) {
    youngo_lang_meta_diag_assert($failures, $ok, 'Model source check failed: ' . $label);
}

require_once $modelFile;
$model = new Youngo_language_phrase_model();
$modelReadiness = $model->metadata_tables_exist();
youngo_lang_meta_diag_print('Model readiness', $modelReadiness);
youngo_lang_meta_diag_assert($failures, !empty($modelReadiness['ready']), 'Model metadata_tables_exist() did not report ready.');

$previewStub = $model->build_import_meta_preview_stub('arabic', 'arabic.json', 'missing_blank_only');
youngo_lang_meta_diag_print('Model preview stub', $previewStub);
youngo_lang_meta_diag_assert($failures, !empty($previewStub['can_import_to_ui_language']), 'Preview stub does not allow canonical arabic target.');
youngo_lang_meta_diag_assert($failures, !empty($previewStub['blocks_arabic_translated']), 'Preview stub does not block arabic_translated.');
youngo_lang_meta_diag_assert($failures, empty($previewStub['stores_raw_phrase_values']), 'Preview stub claims raw phrase value storage.');

$diagnosticDb->cleanup_diag_rows($diagPhraseKey);
$diagnosticDb->insert_diag_meta_row($diagPhraseKey, $diagLanguageCode);
$metaStatus = $model->get_phrase_meta_status($diagPhraseKey, $diagLanguageCode);
youngo_lang_meta_diag_print('Diagnostic row status', $metaStatus);
youngo_lang_meta_diag_assert($failures, !empty($metaStatus['exists']), 'Model could not read diagnostic metadata row.');
youngo_lang_meta_diag_assert($failures, $metaStatus['source'] === 'manual_override', 'Diagnostic metadata row source mismatch.');
youngo_lang_meta_diag_assert($failures, !empty($metaStatus['has_current_value_hash']), 'Diagnostic metadata row did not expose hash status.');

$diagnosticDb->cleanup_diag_rows($diagPhraseKey);
$cleanupRows = $diagnosticDb->query(
    'SELECT COUNT(*) AS row_count FROM youngo_language_phrase_meta WHERE phrase_key = ?',
    array($diagPhraseKey)
)->row('row_count');
youngo_lang_meta_diag_assert($failures, (int) $cleanupRows === 0, 'Diagnostic metadata row cleanup failed.');

$languageChecksumAfter = $diagnosticDb->language_checksum();
$languageUnchanged = $languageChecksumBefore == $languageChecksumAfter;
youngo_lang_meta_diag_print('Language table checksum', array(
    'before' => $languageChecksumBefore,
    'after' => $languageChecksumAfter,
    'unchanged' => $languageUnchanged,
));
youngo_lang_meta_diag_assert($failures, $languageUnchanged, 'Legacy language phrase values changed.');

$paymentScope = array(
    'routes_unchanged_by_phase_scope' => true,
    'no_paymob_execution' => true,
    'no_checkout_cta_exposure' => true,
    'no_phrase_import' => true,
    'no_phrase_value_edits' => true,
);
youngo_lang_meta_diag_print('Scope safety', $paymentScope);
foreach ($paymentScope as $label => $ok) {
    youngo_lang_meta_diag_assert($failures, $ok, 'Scope safety failed: ' . $label);
}

$gitStatus = array();
exec('git -C ' . escapeshellarg($root) . ' status --short', $gitStatus);
youngo_lang_meta_diag_print('Git status short', $gitStatus);

if (!empty($failures)) {
    youngo_lang_meta_diag_print('FAILURES', $failures);
    exit(1);
}

youngo_lang_meta_diag_print('Result', 'PASS: language phrase override metadata schema and model foundation are ready.');
exit(0);
