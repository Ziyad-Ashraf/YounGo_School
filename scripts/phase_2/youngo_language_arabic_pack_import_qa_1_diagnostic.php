<?php
/**
 * LANGUAGE.ARABIC.PACK.IMPORT.QA.1 diagnostic.
 *
 * Verifies the safe Arabic pack import QA result after apply. This diagnostic
 * is read-only: it does not import phrases, edit phrase values, or write
 * metadata.
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

function youngo_arabic_import_qa_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function youngo_arabic_import_qa_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

class YoungoArabicImportQaResult
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

class YoungoArabicImportQaDb
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

        if ($result === true) {
            return new YoungoArabicImportQaResult(array());
        }

        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();

        return new YoungoArabicImportQaResult($rows);
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

    public function latest_import_batch()
    {
        return $this->query(
            "SELECT id, language_code, source_file, import_mode, total_keys, inserted_count, updated_count, skipped_count, manual_preserved_count, invalid_count, status, summary_json, created_by_user_id FROM youngo_language_import_batches ORDER BY id DESC LIMIT 1"
        )->row_array();
    }

    public function count_rows($sql)
    {
        return (int) $this->query($sql)->row('row_count');
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

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'" . $this->mysqli->real_escape_string((string) $value) . "'";
    }
}

class YoungoArabicImportQaCi
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

$diagnosticDb = new YoungoArabicImportQaDb($db[$active_group]);
$diagnosticCi = new YoungoArabicImportQaCi($diagnosticDb);

function &get_instance()
{
    global $diagnosticCi;
    return $diagnosticCi;
}

$adminFile = $root . '/application/controllers/Admin.php';
$modelFile = $root . '/application/models/Youngo_language_phrase_model.php';
$routesFile = $root . '/application/config/routes.php';
$viewFile = $root . '/application/views/backend/admin/manage_language.php';
$arabicJsonFile = $root . '/application/language/arabic.json';

$requiredFiles = array(
    'application/controllers/Admin.php' => is_file($adminFile),
    'application/models/Youngo_language_phrase_model.php' => is_file($modelFile),
    'application/config/routes.php' => is_file($routesFile),
    'application/views/backend/admin/manage_language.php' => is_file($viewFile),
    'application/language/arabic.json' => is_file($arabicJsonFile),
);
youngo_arabic_import_qa_print('Required files', $requiredFiles);
foreach ($requiredFiles as $label => $exists) {
    youngo_arabic_import_qa_assert($failures, $exists, $label . ' is missing.');
}

$adminSource = file_get_contents($adminFile);
$modelSource = file_get_contents($modelFile);
$routesSource = file_get_contents($routesFile);

$sourceChecks = array(
    'apply_controller_exists' => strpos($adminSource, 'function youngo_arabic_pack_import_apply') !== false,
    'apply_route_exists' => strpos($routesSource, 'admin/youngo/language/arabic-import-apply') !== false,
    'apply_is_post_only' => preg_match('/function\s+youngo_arabic_pack_import_apply\s*\(\)\s*\{(?:(?!public function|protected function).)*youngo_request_method\(\)\s*!==\s*[\'"]POST[\'"]/s', $adminSource) === 1,
    'apply_requires_admin_login' => preg_match('/function\s+youngo_arabic_pack_import_apply\s*\(\)\s*\{(?:(?!public function|protected function).)*userdata\([\'"]admin_login[\'"]\)/s', $adminSource) === 1,
    'apply_requires_settings_permission' => preg_match('/function\s+youngo_arabic_pack_import_apply\s*\(\)\s*\{(?:(?!public function|protected function).)*check_permission\([\'"]settings[\'"]\)/s', $adminSource) === 1,
    'apply_requires_root_guard' => preg_match('/function\s+youngo_arabic_pack_import_apply\s*\(\)\s*\{(?:(?!public function|protected function).)*youngo_require_root_admin_for_language_preview/s', $adminSource) === 1,
    'apply_fixed_to_missing_blank_only' => strpos($adminSource, "apply_arabic_pack_import('missing_blank_only'") !== false,
    'apply_model_blocks_non_default_mode' => strpos($modelSource, 'Only missing_blank_only is enabled for the safe Arabic pack import QA phase.') !== false,
    'apply_source_fixed_to_arabic_json' => strpos($modelSource, "'source_file' => \$this->arabic_import_source_file") !== false,
    'apply_target_fixed_to_arabic' => strpos($modelSource, "'language_code' => \$this->arabic_import_language_code") !== false,
    'manual_override_preserve_branch' => strpos($modelSource, 'is_manual_override_meta($meta_row)') !== false
        && strpos($modelSource, "manual_preserved_count") !== false,
    'no_raw_phrase_values_in_meta_model' => strpos($modelSource, 'last_imported_value_hash') !== false
        && strpos($modelSource, 'current_value_hash') !== false,
    'no_payment_paymob_source_markers' => stripos($modelSource, 'paymob') === false && stripos($adminSource, 'paymob') === false,
    'no_checkout_source_markers' => stripos($modelSource, 'checkout') === false,
);
youngo_arabic_import_qa_print('Source checks', $sourceChecks);
foreach ($sourceChecks as $label => $ok) {
    youngo_arabic_import_qa_assert($failures, $ok, 'Source check failed: ' . $label);
}

require_once $modelFile;
$model = new Youngo_language_phrase_model();
$validation = $model->validate_arabic_pack_file($arabicJsonFile);
$preview = $model->preview_arabic_pack_import('missing_blank_only');
$dryRun = $model->apply_arabic_pack_import('missing_blank_only', 1, true);
$forceDryRun = $model->apply_arabic_pack_import('force_overwrite', 1, true);
$forceBlocked = $model->apply_arabic_pack_import('force_overwrite', 1, false);
$pagination = $model->get_paginated_edit_phrases('arabic', 1, 25, 'login');

$modelChecks = array(
    'arabic_json_valid' => !empty($validation['valid']),
    'target_language_arabic' => isset($validation['target_language_code']) && $validation['target_language_code'] === 'arabic',
    'preview_valid' => !empty($preview['valid']),
    'preview_mode_missing_blank_only' => isset($preview['import_mode']) && $preview['import_mode'] === 'missing_blank_only',
    'dry_run_does_not_apply' => !empty($dryRun['dry_run']) && empty($dryRun['applied']) && empty($dryRun['blocked']),
    'force_overwrite_dry_run_preview_only' => !empty($forceDryRun['dry_run']) && empty($forceDryRun['applied']),
    'force_overwrite_real_apply_blocked' => empty($forceBlocked['applied']) && !empty($forceBlocked['blocked']),
    'arabic_translated_rejected' => $model->normalize_ui_language_code('arabic_translated') === null,
    'edit_phrase_pagination_works' => !empty($pagination['valid']) && $pagination['language'] === 'arabic' && $pagination['per_page'] === 25,
);
youngo_arabic_import_qa_print('Model/read checks', $modelChecks);
foreach ($modelChecks as $label => $ok) {
    youngo_arabic_import_qa_assert($failures, $ok, 'Model/read check failed: ' . $label);
}

$latestBatch = $diagnosticDb->latest_import_batch();
$summary = !empty($latestBatch['summary_json']) ? json_decode($latestBatch['summary_json'], true) : array();
$batchChecks = array(
    'batch_exists' => !empty($latestBatch['id']),
    'language_code_arabic' => isset($latestBatch['language_code']) && $latestBatch['language_code'] === 'arabic',
    'source_file_arabic_json' => isset($latestBatch['source_file']) && $latestBatch['source_file'] === 'arabic.json',
    'mode_missing_blank_only' => isset($latestBatch['import_mode']) && $latestBatch['import_mode'] === 'missing_blank_only',
    'status_applied_or_noop' => isset($latestBatch['status']) && in_array($latestBatch['status'], array('applied', 'applied_noop'), true),
    'total_keys_matches_preview' => isset($latestBatch['total_keys']) && (int) $latestBatch['total_keys'] === (int) $preview['total_keys'],
    'invalid_count_matches_preview' => isset($latestBatch['invalid_count']) && (int) $latestBatch['invalid_count'] === (int) $preview['invalid_keys'],
    'updated_count_matches_preview' => isset($latestBatch['updated_count']) && (int) $latestBatch['updated_count'] === (int) $preview['would_update_phrase_values'],
    'actor_recorded' => !empty($latestBatch['created_by_user_id']),
    'summary_json_valid' => is_array($summary),
    'summary_no_raw_values' => is_array($summary) && !array_key_exists('values', $summary) && !array_key_exists('phrases', $summary) && !array_key_exists('rows', $summary),
);
youngo_arabic_import_qa_print('Latest import batch checks', $batchChecks);
foreach ($batchChecks as $label => $ok) {
    youngo_arabic_import_qa_assert($failures, $ok, 'Batch check failed: ' . $label);
}

$dbChecks = array(
    'metadata_tables_exist' => $diagnosticDb->table_exists('youngo_language_import_batches') && $diagnosticDb->table_exists('youngo_language_phrase_meta'),
    'no_arabic_translated_metadata_rows' => $diagnosticDb->count_rows("SELECT COUNT(*) AS row_count FROM youngo_language_phrase_meta WHERE language_code = 'arabic_translated'") === 0,
    'no_raw_value_metadata_columns' => $diagnosticDb->count_rows("SELECT COUNT(*) AS row_count FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'youngo_language_phrase_meta' AND column_name IN ('value', 'phrase_value', 'translation', 'raw_value', 'imported_value', 'current_value')") === 0,
    'manual_override_rows_not_relabelled' => $diagnosticDb->count_rows("SELECT COUNT(*) AS row_count FROM youngo_language_phrase_meta WHERE language_code = 'arabic' AND source = 'manual_override' AND last_import_batch_id = " . (int) $latestBatch['id']) === 0,
);
youngo_arabic_import_qa_print('DB safety checks', $dbChecks);
foreach ($dbChecks as $label => $ok) {
    youngo_arabic_import_qa_assert($failures, $ok, 'DB safety check failed: ' . $label);
}

$countSummary = array(
    'preview_total_keys' => (int) $preview['total_keys'],
    'preview_matching_existing_phrase_keys' => (int) $preview['matching_existing_phrase_keys'],
    'preview_invalid_keys' => (int) $preview['invalid_keys'],
    'preview_would_update_phrase_values' => (int) $preview['would_update_phrase_values'],
    'latest_batch_updated_count' => isset($latestBatch['updated_count']) ? (int) $latestBatch['updated_count'] : null,
    'latest_batch_skipped_count' => isset($latestBatch['skipped_count']) ? (int) $latestBatch['skipped_count'] : null,
    'latest_batch_manual_preserved_count' => isset($latestBatch['manual_preserved_count']) ? (int) $latestBatch['manual_preserved_count'] : null,
);
youngo_arabic_import_qa_print('Count-only summary', $countSummary);

$gitStatus = array();
exec('git -C ' . escapeshellarg($root) . ' status --short', $gitStatus);
youngo_arabic_import_qa_print('Git status short', $gitStatus);

if (!empty($failures)) {
    youngo_arabic_import_qa_print('FAILURES', $failures);
    exit(1);
}

youngo_arabic_import_qa_print('Result', 'PASS: safe Arabic pack import QA state is valid, count-only, and bounded to arabic missing_blank_only.');
exit(0);
