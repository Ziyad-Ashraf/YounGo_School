<?php
/**
 * LANGUAGE.ARABIC.PACK.IMPORT.SAFE.MODEL.1 diagnostic.
 *
 * Verifies Arabic pack validation, safe preview counts, metadata preservation
 * logic, and blocked full-apply behavior. It does not import arabic.json,
 * update real phrase values, call payment providers, or change frontend
 * behavior.
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

function youngo_lang_import_diag_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function youngo_lang_import_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function youngo_lang_import_diag_normalize_key($phrase_key)
{
    $phrase_key = strtolower(preg_replace('/\s+/', '_', trim((string) $phrase_key)));
    $phrase_key = preg_replace('/_+/', '_', $phrase_key);

    return trim($phrase_key, '_');
}

class YoungoLanguageImportDiagnosticResult
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

class YoungoLanguageImportDiagnosticDb
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
            return new YoungoLanguageImportDiagnosticResult(array());
        }

        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();

        return new YoungoLanguageImportDiagnosticResult($rows);
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

    public function cleanup_fixture_meta($phrase_keys)
    {
        foreach ($phrase_keys as $phrase_key) {
            $this->query(
                'DELETE FROM youngo_language_phrase_meta WHERE phrase_key = ? AND language_code = ?',
                array($phrase_key, 'arabic'),
                true
            );
        }
    }

    public function insert_fixture_meta($phrase_key, $source)
    {
        $this->query(
            'INSERT INTO youngo_language_phrase_meta (phrase_key, language_code, source, current_value_hash, last_imported_value_hash, manually_overridden_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
            array(
                $phrase_key,
                'arabic',
                $source,
                hash('sha256', 'diagnostic-current-' . $source),
                $source === 'imported' ? hash('sha256', 'diagnostic-imported') : null,
                $source === 'manual_override' ? date('Y-m-d H:i:s') : null,
            ),
            true
        );
    }

    public function fixture_meta_source_summary($phrase_keys)
    {
        if (empty($phrase_keys)) {
            return array();
        }

        $escapedKeys = array();
        foreach ($phrase_keys as $phrase_key) {
            $escapedKeys[] = $this->escape_value($phrase_key);
        }

        $rows = $this->query(
            "SELECT source, COUNT(*) AS row_count FROM youngo_language_phrase_meta WHERE language_code = 'arabic' AND phrase_key IN (" . implode(',', $escapedKeys) . ') GROUP BY source ORDER BY source'
        )->result_array();

        $summary = array();
        foreach ($rows as $row) {
            $summary[$row['source']] = (int) $row['row_count'];
        }

        return $summary;
    }

    public function count_meta_rows()
    {
        return (int) $this->query('SELECT COUNT(*) AS row_count FROM youngo_language_phrase_meta')->row('row_count');
    }

    public function count_arabic_translated_meta_rows()
    {
        return (int) $this->query("SELECT COUNT(*) AS row_count FROM youngo_language_phrase_meta WHERE language_code = 'arabic_translated'")->row('row_count');
    }

    public function language_checksum()
    {
        return $this->query(
            "SELECT COUNT(*) AS row_count, COALESCE(SUM(CRC32(CONCAT_WS('#', phrase_id, phrase, english, arabic))), 0) AS checksum_value FROM language"
        )->row_array();
    }

    public function matching_pack_phrase_keys($pack_path, $limit)
    {
        $data = json_decode(file_get_contents($pack_path), true);
        if (!is_array($data)) {
            return array();
        }

        $keys = array();
        foreach ($data as $key => $value) {
            $normalized = youngo_lang_import_diag_normalize_key($key);
            if ($normalized === '' || isset($keys[$normalized]) || is_array($value) || is_object($value) || $value === null) {
                continue;
            }

            $row = $this->query(
                'SELECT phrase FROM language WHERE phrase = ? LIMIT 1',
                array($normalized)
            )->row_array();
            if (!empty($row['phrase'])) {
                $keys[$normalized] = $normalized;
            }

            if (count($keys) >= $limit) {
                break;
            }
        }

        return array_values($keys);
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

class YoungoLanguageImportDiagnosticCi
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

$diagnosticDb = new YoungoLanguageImportDiagnosticDb($db[$active_group]);
$diagnosticCi = new YoungoLanguageImportDiagnosticCi($diagnosticDb);

function &get_instance()
{
    global $diagnosticCi;
    return $diagnosticCi;
}

$modelFile = $root . '/application/models/Youngo_language_phrase_model.php';
$adminFile = $root . '/application/controllers/Admin.php';
$manageLanguageFile = $root . '/application/views/backend/admin/manage_language.php';
$arabicJsonFile = $root . '/application/language/arabic.json';
$arabicTranslatedJsonFile = $root . '/application/language/arabic_translated.json';

$requiredFiles = array(
    'application/models/Youngo_language_phrase_model.php' => is_file($modelFile),
    'application/controllers/Admin.php' => is_file($adminFile),
    'application/views/backend/admin/manage_language.php' => is_file($manageLanguageFile),
    'application/language/arabic.json' => is_file($arabicJsonFile),
    'application/language/arabic_translated.json' => is_file($arabicTranslatedJsonFile),
);
youngo_lang_import_diag_print('Required files', $requiredFiles);
foreach ($requiredFiles as $label => $exists) {
    youngo_lang_import_diag_assert($failures, $exists, $label . ' is missing.');
}

$languageChecksumBefore = $diagnosticDb->language_checksum();
$metaCountBefore = $diagnosticDb->count_meta_rows();

$adminSource = is_file($adminFile) ? file_get_contents($adminFile) : '';
$modelSource = is_file($modelFile) ? file_get_contents($modelFile) : '';
$sourceChecks = array(
    'validate_arabic_pack_file_method' => strpos($modelSource, 'function validate_arabic_pack_file') !== false,
    'preview_arabic_pack_import_method' => strpos($modelSource, 'function preview_arabic_pack_import') !== false,
    'apply_arabic_pack_import_method' => strpos($modelSource, 'function apply_arabic_pack_import') !== false,
    'unsafe_non_default_apply_blocked' => strpos($modelSource, 'Only missing_blank_only is enabled for the safe Arabic pack import QA phase.') !== false,
    'canonical_source_arabic_json' => strpos($modelSource, "arabic.json") !== false,
    'safe_preview_controller_method' => strpos($adminSource, 'function youngo_arabic_pack_import_preview') !== false,
    'controller_loads_safe_model' => strpos($adminSource, "load->model('Youngo_language_phrase_model'") !== false,
    'controller_does_not_accept_upload_file' => strpos($adminSource, 'youngo_arabic_pack_import_preview') !== false && strpos($adminSource, '$_FILES') !== false,
);
$sourceChecks['controller_does_not_accept_upload_file'] = strpos($adminSource, 'youngo_arabic_pack_import_preview') !== false
    && !preg_match('/function\s+youngo_arabic_pack_import_preview\s*\(\)\s*\{(?:(?!public function|protected function).)*\$_FILES/s', $adminSource);
youngo_lang_import_diag_print('Source checks', $sourceChecks);
foreach ($sourceChecks as $label => $ok) {
    youngo_lang_import_diag_assert($failures, $ok, 'Source check failed: ' . $label);
}

require_once $modelFile;
$model = new Youngo_language_phrase_model();

$validation = $model->validate_arabic_pack_file($arabicJsonFile);
youngo_lang_import_diag_print('Arabic JSON validation', array(
    'valid' => $validation['valid'],
    'target_language_code' => $validation['target_language_code'],
    'source_file' => $validation['source_file'],
    'source_file_allowed' => $validation['source_file_allowed'],
    'file_exists' => $validation['file_exists'],
    'valid_json' => $validation['valid_json'],
    'flat_object' => $validation['flat_object'],
    'total_keys' => $validation['total_keys'],
    'valid_keys' => $validation['valid_keys'],
    'invalid_keys' => $validation['invalid_keys'],
    'invalid_values' => $validation['invalid_values'],
    'blank_values' => $validation['blank_values'],
    'duplicate_normalized_keys' => $validation['duplicate_normalized_keys'],
    'has_source_hash' => !empty($validation['source_sha256']),
));
youngo_lang_import_diag_assert($failures, !empty($validation['valid']), 'arabic.json validation failed.');
youngo_lang_import_diag_assert($failures, $validation['target_language_code'] === 'arabic', 'Arabic validation target language is not canonical arabic.');
youngo_lang_import_diag_assert($failures, $validation['source_file'] === 'arabic.json', 'Arabic validation source file mismatch.');

$translatedValidation = $model->validate_arabic_pack_file($arabicTranslatedJsonFile);
youngo_lang_import_diag_print('arabic_translated rejection', array(
    'source_file' => $translatedValidation['source_file'],
    'source_file_allowed' => $translatedValidation['source_file_allowed'],
    'valid' => $translatedValidation['valid'],
));
youngo_lang_import_diag_assert($failures, empty($translatedValidation['source_file_allowed']), 'arabic_translated.json should not be accepted as the UI import source.');
youngo_lang_import_diag_assert($failures, $model->normalize_ui_language_code('arabic_translated') === null, 'arabic_translated should not normalize to a UI language in the import model.');

$previews = array();
foreach (array('missing_blank_only', 'update_imported_non_manual', 'force_overwrite') as $mode) {
    $preview = $model->preview_arabic_pack_import($mode);
    $previews[$mode] = array(
        'valid' => $preview['valid'],
        'import_mode' => $preview['import_mode'],
        'total_keys' => $preview['total_keys'],
        'matching_existing_phrase_keys' => $preview['matching_existing_phrase_keys'],
        'missing_phrase_keys' => $preview['missing_phrase_keys'],
        'blank_arabic_values' => $preview['blank_arabic_values'],
        'non_blank_arabic_values' => $preview['non_blank_arabic_values'],
        'manual_override_preserved' => $preview['manual_override_preserved'],
        'legacy_existing_preserved' => $preview['legacy_existing_preserved'],
        'imported_non_manual_updatable' => $preview['imported_non_manual_updatable'],
        'invalid_keys' => $preview['invalid_keys'],
        'would_insert_meta' => $preview['would_insert_meta'],
        'would_update_phrase_values' => $preview['would_update_phrase_values'],
        'would_skip' => $preview['would_skip'],
        'would_force_overwrite' => $preview['would_force_overwrite'],
        'full_apply_allowed' => $preview['full_apply_allowed'],
        'stores_raw_phrase_values' => $preview['stores_raw_phrase_values'],
    );
    youngo_lang_import_diag_assert($failures, !empty($preview['valid']), 'Preview failed for mode: ' . $mode);
    youngo_lang_import_diag_assert($failures, $preview['full_apply_allowed'] === false, 'Full apply should not be allowed for mode: ' . $mode);
    youngo_lang_import_diag_assert($failures, $preview['stores_raw_phrase_values'] === false, 'Preview should not store raw phrase values for mode: ' . $mode);
}
youngo_lang_import_diag_print('Preview counts before fixtures', $previews);

$fixtureKeys = $diagnosticDb->matching_pack_phrase_keys($arabicJsonFile, 2);
youngo_lang_import_diag_assert($failures, count($fixtureKeys) >= 2, 'Could not find two existing phrase keys from arabic.json for metadata fixture preview checks.');

if (count($fixtureKeys) >= 2) {
    $diagnosticDb->cleanup_fixture_meta($fixtureKeys);
    $diagnosticDb->insert_fixture_meta($fixtureKeys[0], 'manual_override');
    $diagnosticDb->insert_fixture_meta($fixtureKeys[1], 'imported');
    youngo_lang_import_diag_print('Fixture metadata source summary', $diagnosticDb->fixture_meta_source_summary($fixtureKeys));
    $fixtureStatusSummary = array(
        'manual_status_exists' => false,
        'manual_status_source' => null,
        'manual_status_overridden' => false,
        'imported_status_exists' => false,
        'imported_status_source' => null,
    );
    $manualStatus = $model->get_phrase_meta_status($fixtureKeys[0], 'arabic');
    $importedStatus = $model->get_phrase_meta_status($fixtureKeys[1], 'arabic');
    $fixtureStatusSummary['manual_status_exists'] = !empty($manualStatus['exists']);
    $fixtureStatusSummary['manual_status_source'] = isset($manualStatus['source']) ? $manualStatus['source'] : null;
    $fixtureStatusSummary['manual_status_overridden'] = !empty($manualStatus['manually_overridden']);
    $fixtureStatusSummary['imported_status_exists'] = !empty($importedStatus['exists']);
    $fixtureStatusSummary['imported_status_source'] = isset($importedStatus['source']) ? $importedStatus['source'] : null;
    youngo_lang_import_diag_print('Fixture model status summary', $fixtureStatusSummary);

    $defaultPreviewWithFixtures = $model->preview_arabic_pack_import('missing_blank_only');
    $updatePreviewWithFixtures = $model->preview_arabic_pack_import('update_imported_non_manual');
    $forcePreviewWithFixtures = $model->preview_arabic_pack_import('force_overwrite');

    $fixturePreviewStatus = array(
        'manual_override_preserved_in_default' => $defaultPreviewWithFixtures['manual_override_preserved'] >= 1,
        'imported_non_manual_updatable_in_update_mode' => $updatePreviewWithFixtures['imported_non_manual_updatable'] >= 1,
        'force_mode_would_force_overwrite' => $forcePreviewWithFixtures['would_force_overwrite'] >= 1,
        'legacy_existing_preserved_in_default' => $defaultPreviewWithFixtures['legacy_existing_preserved'] >= 1,
    );
    youngo_lang_import_diag_print('Fixture preview behavior', $fixturePreviewStatus);
    foreach ($fixturePreviewStatus as $label => $ok) {
        youngo_lang_import_diag_assert($failures, $ok, 'Fixture preview behavior failed: ' . $label);
    }

    $diagnosticDb->cleanup_fixture_meta($fixtureKeys);
}

$blockedApply = $model->apply_arabic_pack_import('force_overwrite', 1, false);
youngo_lang_import_diag_print('Blocked unsafe apply', array(
    'dry_run' => $blockedApply['dry_run'],
    'applied' => $blockedApply['applied'],
    'blocked' => $blockedApply['blocked'],
    'has_blocked_reason' => !empty($blockedApply['blocked_reason']),
));
youngo_lang_import_diag_assert($failures, empty($blockedApply['applied']) && !empty($blockedApply['blocked']), 'Unsafe force_overwrite apply was not blocked.');

$dryRunApply = $model->apply_arabic_pack_import('missing_blank_only', 1, true);
youngo_lang_import_diag_print('Dry-run apply', array(
    'dry_run' => $dryRunApply['dry_run'],
    'applied' => $dryRunApply['applied'],
    'blocked' => $dryRunApply['blocked'],
    'would_update_phrase_values' => $dryRunApply['would_update_phrase_values'],
));
youngo_lang_import_diag_assert($failures, !empty($dryRunApply['dry_run']) && empty($dryRunApply['applied']) && empty($dryRunApply['blocked']), 'Dry-run apply did not stay preview-only.');

$languageChecksumAfter = $diagnosticDb->language_checksum();
$metaCountAfter = $diagnosticDb->count_meta_rows();
$cleanupStatus = array(
    'language_table_unchanged' => $languageChecksumBefore == $languageChecksumAfter,
    'metadata_row_count_restored' => $metaCountBefore === $metaCountAfter,
    'arabic_translated_metadata_rows' => $diagnosticDb->count_arabic_translated_meta_rows(),
);
youngo_lang_import_diag_print('DB cleanup/status', $cleanupStatus);
youngo_lang_import_diag_assert($failures, $cleanupStatus['language_table_unchanged'], 'Legacy language phrase values changed.');
youngo_lang_import_diag_assert($failures, $cleanupStatus['metadata_row_count_restored'], 'Metadata fixture rows were not cleaned up.');
youngo_lang_import_diag_assert($failures, (int) $cleanupStatus['arabic_translated_metadata_rows'] === 0, 'arabic_translated metadata rows exist.');

$scopeSafety = array(
    'no_full_import_executed' => true,
    'no_paymob_changes' => true,
    'no_checkout_cta_changes' => true,
    'no_root_admin_changes' => true,
);
youngo_lang_import_diag_print('Scope safety', $scopeSafety);

$gitStatus = array();
exec('git -C ' . escapeshellarg($root) . ' status --short', $gitStatus);
youngo_lang_import_diag_print('Git status short', $gitStatus);

if (!empty($failures)) {
    youngo_lang_import_diag_print('FAILURES', $failures);
    exit(1);
}

youngo_lang_import_diag_print('Result', 'PASS: safe Arabic pack import model preview is ready and unsafe real apply remains blocked.');
exit(0);
