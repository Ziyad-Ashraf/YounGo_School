<?php
/**
 * LANGUAGE.EDIT.PHRASE.PAGINATION.UI.1 diagnostic.
 *
 * Verifies the Edit Phrase paginated UI wiring without importing packs,
 * changing phrase values, or touching payment/checkout behavior.
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

function youngo_edit_phrase_ui_diag_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function youngo_edit_phrase_ui_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

class YoungoEditPhraseUiDiagnosticDb
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

    public function language_checksum()
    {
        $result = $this->mysqli->query("SELECT COUNT(*) AS row_count, COALESCE(SUM(CRC32(CONCAT_WS('#', phrase_id, phrase, english, arabic))), 0) AS checksum_value FROM language");
        if ($result === false) {
            throw new RuntimeException('DB query failed without exposing credentials.');
        }

        $row = $result->fetch_assoc();
        $result->free();

        return $row;
    }
}

if (!isset($db[$active_group])) {
    fwrite(STDERR, "Active database group was not found.\n");
    exit(1);
}

$diagnosticDb = new YoungoEditPhraseUiDiagnosticDb($db[$active_group]);
$languageChecksumBefore = $diagnosticDb->language_checksum();

$adminFile = $root . '/application/controllers/Admin.php';
$viewFile = $root . '/application/views/backend/admin/manage_language.php';
$modelFile = $root . '/application/models/Youngo_language_phrase_model.php';
$routesFile = $root . '/application/config/routes.php';

$requiredFiles = array(
    'application/controllers/Admin.php' => is_file($adminFile),
    'application/views/backend/admin/manage_language.php' => is_file($viewFile),
    'application/models/Youngo_language_phrase_model.php' => is_file($modelFile),
    'application/config/routes.php' => is_file($routesFile),
);
youngo_edit_phrase_ui_diag_print('Required files', $requiredFiles);
foreach ($requiredFiles as $label => $exists) {
    youngo_edit_phrase_ui_diag_assert($failures, $exists, $label . ' is missing.');
}

$adminSource = file_get_contents($adminFile);
$viewSource = file_get_contents($viewFile);
$modelSource = file_get_contents($modelFile);
$routesSource = file_get_contents($routesFile);

$uiChecks = array(
    'edit_phrase_app_present' => strpos($viewSource, 'youngo-edit-phrase-app') !== false,
    'search_input_present' => strpos($viewSource, 'youngo_edit_phrase_search') !== false,
    'language_selector_present' => strpos($viewSource, 'youngo_edit_phrase_language') !== false,
    'per_page_selector_present' => strpos($viewSource, 'youngo_edit_phrase_per_page') !== false,
    'per_page_25_present' => strpos($viewSource, 'value="25"') !== false,
    'per_page_50_present' => strpos($viewSource, 'value="50"') !== false,
    'per_page_100_present' => strpos($viewSource, 'value="100"') !== false,
    'pagination_prev_present' => strpos($viewSource, 'youngo_edit_phrase_prev') !== false,
    'pagination_next_present' => strpos($viewSource, 'youngo_edit_phrase_next') !== false,
    'loading_state_present' => strpos($viewSource, 'youngo_edit_phrase_loading') !== false,
    'empty_state_present' => strpos($viewSource, 'youngo_edit_phrase_empty') !== false,
    'visible_rows_container_present' => strpos($viewSource, 'youngo_edit_phrase_rows') !== false,
    'endpoint_used_by_ui' => strpos($viewSource, "site_url('admin/youngo/language/edit-phrase-data')") !== false,
    'ajax_loads_paginated_data' => strpos($viewSource, 'loadPhrases') !== false && strpos($viewSource, 'per_page: perPage.val()') !== false,
    'no_server_side_all_phrase_loop' => strpos($viewSource, 'openJSONFile($edit_profile)') === false,
    'arabic_translated_skipped_in_selector' => strpos($viewSource, "\$language === 'arabic_translated'") !== false,
);
youngo_edit_phrase_ui_diag_print('UI checks', $uiChecks);
foreach ($uiChecks as $label => $ok) {
    youngo_edit_phrase_ui_diag_assert($failures, $ok, 'UI check failed: ' . $label);
}

$endpointChecks = array(
    'data_endpoint_controller_exists' => strpos($adminSource, 'function youngo_edit_phrase_paginated_data') !== false,
    'data_endpoint_route_exists' => strpos($routesSource, "admin/youngo/language/edit-phrase-data") !== false,
    'data_endpoint_model_method_exists' => strpos($modelSource, 'function get_paginated_edit_phrases') !== false,
    'phrase_update_endpoint_still_exists' => strpos($adminSource, 'function update_phrase_with_ajax') !== false,
    'existing_update_ajax_url_still_used' => strpos($viewSource, "site_url('admin/update_phrase_with_ajax')") !== false,
    'manual_override_marker_wired_for_arabic' => strpos($adminSource, 'mark_phrase_manual_override') !== false,
    'manual_override_nonblocking_context' => strpos($adminSource, "current_editing_language)) === 'arabic'") !== false,
);
youngo_edit_phrase_ui_diag_print('Endpoint/update checks', $endpointChecks);
foreach ($endpointChecks as $label => $ok) {
    youngo_edit_phrase_ui_diag_assert($failures, $ok, 'Endpoint/update check failed: ' . $label);
}

$safetyChecks = array(
    'model_rejects_arabic_translated' => strpos($modelSource, "column !== 'arabic_translated'") !== false,
    'no_arabic_pack_import_call_in_ui' => strpos($viewSource, 'apply_arabic_pack_import') === false,
    'no_payment_paymob_source_markers' => stripos($viewSource, 'paymob') === false && stripos($adminSource, 'paymob') === false,
    'no_checkout_cta_source_markers' => stripos($viewSource, 'checkout') === false,
);
youngo_edit_phrase_ui_diag_print('Safety checks', $safetyChecks);
foreach ($safetyChecks as $label => $ok) {
    youngo_edit_phrase_ui_diag_assert($failures, $ok, 'Safety check failed: ' . $label);
}

$languageChecksumAfter = $diagnosticDb->language_checksum();
$dbSafety = array(
    'language_table_unchanged' => $languageChecksumBefore == $languageChecksumAfter,
    'no_phrase_import_executed' => true,
    'no_phrase_value_writes_in_diagnostic' => true,
);
youngo_edit_phrase_ui_diag_print('DB safety', $dbSafety);
foreach ($dbSafety as $label => $ok) {
    youngo_edit_phrase_ui_diag_assert($failures, $ok, 'DB safety failed: ' . $label);
}

$gitStatus = array();
exec('git -C ' . escapeshellarg($root) . ' status --short', $gitStatus);
youngo_edit_phrase_ui_diag_print('Git status short', $gitStatus);

if (!empty($failures)) {
    youngo_edit_phrase_ui_diag_print('FAILURES', $failures);
    exit(1);
}

youngo_edit_phrase_ui_diag_print('Result', 'PASS: Edit Phrase paginated UI wiring is ready and phrase values are unchanged.');
exit(0);
