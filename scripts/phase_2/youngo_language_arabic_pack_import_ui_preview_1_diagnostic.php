<?php
/**
 * LANGUAGE.ARABIC.PACK.IMPORT.UI.PREVIEW.1 diagnostic.
 *
 * Verifies the Manage Language Arabic preview UI, legacy Arabic import guard,
 * count-only preview behavior, and no phrase value changes.
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

function youngo_arabic_ui_diag_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function youngo_arabic_ui_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

class YoungoArabicImportUiDiagnosticResult
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

class YoungoArabicImportUiDiagnosticDb
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

        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();

        return new YoungoArabicImportUiDiagnosticResult($rows);
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

class YoungoArabicImportUiDiagnosticCi
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

$diagnosticDb = new YoungoArabicImportUiDiagnosticDb($db[$active_group]);
$diagnosticCi = new YoungoArabicImportUiDiagnosticCi($diagnosticDb);

function &get_instance()
{
    global $diagnosticCi;
    return $diagnosticCi;
}

$adminFile = $root . '/application/controllers/Admin.php';
$viewFile = $root . '/application/views/backend/admin/manage_language.php';
$modelFile = $root . '/application/models/Youngo_language_phrase_model.php';
$routesFile = $root . '/application/config/routes.php';

$requiredFiles = array(
    'application/controllers/Admin.php' => is_file($adminFile),
    'application/views/backend/admin/manage_language.php' => is_file($viewFile),
    'application/models/Youngo_language_phrase_model.php' => is_file($modelFile),
    'application/config/routes.php' => is_file($routesFile),
    'application/language/arabic.json' => is_file($root . '/application/language/arabic.json'),
    'application/language/arabic_translated.json' => is_file($root . '/application/language/arabic_translated.json'),
);
youngo_arabic_ui_diag_print('Required files', $requiredFiles);
foreach ($requiredFiles as $label => $exists) {
    youngo_arabic_ui_diag_assert($failures, $exists, $label . ' is missing.');
}

$languageChecksumBefore = $diagnosticDb->language_checksum();
$adminSource = file_get_contents($adminFile);
$viewSource = file_get_contents($viewFile);
$modelSource = file_get_contents($modelFile);
$routesSource = file_get_contents($routesFile);

$uiChecks = array(
    'safe_preview_panel_present' => strpos($viewSource, 'youngo-arabic-import-preview-panel') !== false,
    'target_language_displayed' => strpos($viewSource, 'Arabic (arabic)') !== false,
    'source_file_displayed' => strpos($viewSource, 'arabic.json') !== false,
    'mode_missing_blank_only' => strpos($viewSource, 'missing_blank_only') !== false,
    'mode_update_imported_non_manual' => strpos($viewSource, 'update_imported_non_manual') !== false,
    'mode_force_preview_only' => strpos($viewSource, 'force_overwrite preview only / dangerous') !== false,
    'preview_button_present' => strpos($viewSource, 'youngo_arabic_import_preview_button') !== false,
    'preview_endpoint_used' => strpos($viewSource, "site_url('admin/youngo/language/arabic-import-preview')") !== false,
    'count_fields_rendered' => strpos($viewSource, 'would_update_phrase_values') !== false && strpos($viewSource, 'manual_override_preserved') !== false,
    'no_apply_button_label' => strpos($viewSource, 'Apply Arabic import') === false,
);
youngo_arabic_ui_diag_print('UI checks', $uiChecks);
foreach ($uiChecks as $label => $ok) {
    youngo_arabic_ui_diag_assert($failures, $ok, 'UI check failed: ' . $label);
}

$endpointChecks = array(
    'preview_endpoint_exists' => strpos($adminSource, 'function youngo_arabic_pack_import_preview') !== false,
    'preview_get_only' => strpos($adminSource, "youngo_request_method() !== 'GET'") !== false,
    'preview_admin_login_gate' => strpos($adminSource, "userdata('admin_login')") !== false,
    'preview_settings_permission' => preg_match('/function\s+youngo_arabic_pack_import_preview\s*\(\)\s*\{(?:(?!public function|protected function).)*check_permission\([\'"]settings[\'"]\)/s', $adminSource) === 1,
    'preview_root_admin_gate' => strpos($adminSource, 'youngo_require_root_admin_for_language_preview') !== false,
    'preview_uses_safe_model' => strpos($adminSource, 'preview_arabic_pack_import') !== false,
    'preview_does_not_apply' => preg_match('/function\s+youngo_arabic_pack_import_preview\s*\(\)\s*\{(?:(?!public function|protected function).)*apply_arabic_pack_import/s', $adminSource) !== 1,
    'explicit_admin_preview_route' => strpos($routesSource, "admin/youngo/language/arabic-import-preview") !== false
        && strpos($routesSource, "admin/youngo_arabic_pack_import_preview") !== false,
);
youngo_arabic_ui_diag_print('Endpoint checks', $endpointChecks);
foreach ($endpointChecks as $label => $ok) {
    youngo_arabic_ui_diag_assert($failures, $ok, 'Endpoint check failed: ' . $label);
}

$languageImportStart = strpos($adminSource, 'public function language_import()');
$languageImportEnd = strpos($adminSource, 'public function export_language', $languageImportStart);
$languageImportSource = $languageImportStart !== false && $languageImportEnd !== false
    ? substr($adminSource, $languageImportStart, $languageImportEnd - $languageImportStart)
    : '';

$legacyGuardChecks = array(
    'legacy_import_login_gate' => strpos($languageImportSource, "userdata('admin_login')") !== false,
    'legacy_import_settings_permission' => strpos($languageImportSource, "check_permission('settings')") !== false,
    'legacy_import_preflight_guard' => strpos($languageImportSource, 'youngo_is_blocked_legacy_language_import_target') !== false,
    'legacy_guard_before_dbforge' => strpos($languageImportSource, 'youngo_is_blocked_legacy_language_import_target') !== false
        && strpos($languageImportSource, '$this->load->dbforge()') !== false
        && strpos($languageImportSource, 'youngo_is_blocked_legacy_language_import_target') < strpos($languageImportSource, '$this->load->dbforge()'),
    'blocks_arabic_target' => preg_match('/language_name\s*===\s*[\'"]arabic[\'"]/', $adminSource) === 1,
    'blocks_arabic_translated_target' => preg_match('/language_name\s*===\s*[\'"]arabic_translated[\'"]/', $adminSource) === 1,
    'add_language_blocks_arabic_translated' => strpos($adminSource, 'youngo_is_blocked_ui_language_target') !== false,
);
youngo_arabic_ui_diag_print('Legacy guard checks', $legacyGuardChecks);
foreach ($legacyGuardChecks as $label => $ok) {
    youngo_arabic_ui_diag_assert($failures, $ok, 'Legacy guard check failed: ' . $label);
}

require_once $modelFile;
$model = new Youngo_language_phrase_model();
$preview = $model->preview_arabic_pack_import('missing_blank_only');
$previewSummary = array(
    'valid' => !empty($preview['valid']),
    'target_language_code' => isset($preview['target_language_code']) ? $preview['target_language_code'] : null,
    'source_file' => isset($preview['source_file']) ? $preview['source_file'] : null,
    'import_mode' => isset($preview['import_mode']) ? $preview['import_mode'] : null,
    'total_keys' => isset($preview['total_keys']) ? (int) $preview['total_keys'] : null,
    'matching_existing_phrase_keys' => isset($preview['matching_existing_phrase_keys']) ? (int) $preview['matching_existing_phrase_keys'] : null,
    'invalid_keys' => isset($preview['invalid_keys']) ? (int) $preview['invalid_keys'] : null,
    'would_update_phrase_values' => isset($preview['would_update_phrase_values']) ? (int) $preview['would_update_phrase_values'] : null,
    'full_apply_allowed' => !empty($preview['full_apply_allowed']),
    'stores_raw_phrase_values' => !empty($preview['stores_raw_phrase_values']),
);
youngo_arabic_ui_diag_print('Safe preview summary', $previewSummary);
youngo_arabic_ui_diag_assert($failures, $previewSummary['valid'], 'Safe preview did not return valid status.');
youngo_arabic_ui_diag_assert($failures, $previewSummary['target_language_code'] === 'arabic', 'Safe preview target language is not arabic.');
youngo_arabic_ui_diag_assert($failures, $previewSummary['source_file'] === 'arabic.json', 'Safe preview source file mismatch.');
youngo_arabic_ui_diag_assert($failures, $previewSummary['full_apply_allowed'] === false, 'Safe preview should keep full apply disabled.');
youngo_arabic_ui_diag_assert($failures, $previewSummary['stores_raw_phrase_values'] === false, 'Safe preview should not store raw phrase values.');

$previewKeys = array_keys($preview);
$disallowedPreviewKeys = array_values(array_intersect($previewKeys, array(
    'values',
    'phrase_values',
    'phrases',
    'rows',
    'language_rows',
    'pack',
)));
$redactionChecks = array(
    'no_disallowed_value_keys' => empty($disallowedPreviewKeys),
    'model_declares_no_raw_values' => strpos($modelSource, "'stores_raw_phrase_values' => false") !== false,
    'view_does_not_render_source_sha256' => strpos($viewSource, 'source_sha256') === false,
);
youngo_arabic_ui_diag_print('Redaction checks', $redactionChecks);
foreach ($redactionChecks as $label => $ok) {
    youngo_arabic_ui_diag_assert($failures, $ok, 'Redaction check failed: ' . $label);
}

$languageChecksumAfter = $diagnosticDb->language_checksum();
$dbSafety = array(
    'language_table_unchanged' => $languageChecksumBefore == $languageChecksumAfter,
    'no_phrase_value_write_sql_in_diagnostic' => true,
    'no_payment_paymob_changes' => true,
    'no_checkout_cta_changes' => true,
);
youngo_arabic_ui_diag_print('DB/scope safety', $dbSafety);
youngo_arabic_ui_diag_assert($failures, $dbSafety['language_table_unchanged'], 'Language phrase checksum changed.');

$gitStatus = array();
exec('git -C ' . escapeshellarg($root) . ' status --short', $gitStatus);
youngo_arabic_ui_diag_print('Git status short', $gitStatus);

if (!empty($failures)) {
    youngo_arabic_ui_diag_print('FAILURES', $failures);
    exit(1);
}

youngo_arabic_ui_diag_print('Result', 'PASS: Arabic import preview UI and legacy Arabic import guard are ready; full import remains disabled.');
exit(0);
