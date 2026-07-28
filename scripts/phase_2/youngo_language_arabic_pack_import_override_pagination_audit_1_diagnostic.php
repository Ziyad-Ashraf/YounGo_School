<?php
/**
 * LANGUAGE.ARABIC.PACK.IMPORT.OVERRIDE.PAGINATION.AUDIT.1 diagnostic.
 *
 * Read-only audit checks for the existing language import and Edit Phrase
 * systems. This script does not import language files, update phrases, change
 * schema, touch payment behavior, or submit any write endpoints.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$root = dirname(__DIR__, 2);
defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

$failures = array();
$warnings = array();

function youngo_lang_audit_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function youngo_lang_audit_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function youngo_lang_audit_contains($source, $needle)
{
    return strpos((string) $source, (string) $needle) !== false;
}

function youngo_lang_audit_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function youngo_lang_audit_query($mysqli, $sql)
{
    if (preg_match('/\b(insert|update|delete|replace|alter|drop|create|truncate|grant|revoke|set)\b/i', $sql)) {
        throw new RuntimeException('Write SQL blocked by diagnostic wrapper.');
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

function youngo_lang_audit_table_exists($mysqli, $table)
{
    $rows = youngo_lang_audit_query($mysqli, "SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return is_array($rows) && !isset($rows['error']) && count($rows) > 0;
}

function youngo_lang_audit_column_exists($mysqli, $table, $column)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }

    if (!youngo_lang_audit_table_exists($mysqli, $table)) {
        return false;
    }

    $rows = youngo_lang_audit_query($mysqli, "SHOW COLUMNS FROM `" . $table . "` LIKE '" . $mysqli->real_escape_string($column) . "'");
    return is_array($rows) && !isset($rows['error']) && count($rows) > 0;
}

function youngo_lang_audit_route_map($source)
{
    $routes = array();
    if (preg_match_all('/\\$route\\[[\'"]([^\'"]+)[\'"]\\]\\s*=\\s*[\'"]([^\'"]+)[\'"]\\s*;/', $source, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $routes[$match[1]] = $match[2];
        }
    }

    return $routes;
}

function youngo_lang_audit_json_status($path)
{
    $status = array(
        'exists' => is_file($path),
        'valid_json_object' => false,
        'key_count' => 0,
    );

    if (!$status['exists']) {
        return $status;
    }

    $data = json_decode(file_get_contents($path), true);
    $status['valid_json_object'] = is_array($data);
    $status['key_count'] = is_array($data) ? count($data) : 0;

    return $status;
}

function youngo_lang_audit_git_status($root)
{
    $output = array();
    $exit_code = 1;
    exec('git -C ' . escapeshellarg($root) . ' status --short', $output, $exit_code);

    return array(
        'exit_code' => $exit_code,
        'lines' => $output,
    );
}

$paths = array(
    'application/controllers/Admin.php' => $root . '/application/controllers/Admin.php',
    'application/controllers/Language.php' => $root . '/application/controllers/Language.php',
    'application/controllers/Data_center.php' => $root . '/application/controllers/Data_center.php',
    'application/models/Language_model.php' => $root . '/application/models/Language_model.php',
    'application/models/Crud_model.php' => $root . '/application/models/Crud_model.php',
    'application/views/backend/admin/manage_language.php' => $root . '/application/views/backend/admin/manage_language.php',
    'application/views/backend/admin/edit_phrase.php' => $root . '/application/views/backend/admin/edit_phrase.php',
    'application/helpers/common_helper.php' => $root . '/application/helpers/common_helper.php',
    'application/helpers/multi_language_helper.php' => $root . '/application/helpers/multi_language_helper.php',
    'application/helpers/youngo_frontend_language_helper.php' => $root . '/application/helpers/youngo_frontend_language_helper.php',
    'application/helpers/youngo_frontend_content_helper.php' => $root . '/application/helpers/youngo_frontend_content_helper.php',
    'application/config/routes.php' => $root . '/application/config/routes.php',
    'application/language/arabic.json' => $root . '/application/language/arabic.json',
    'application/language/arabic_translated.json' => $root . '/application/language/arabic_translated.json',
);

$file_status = array();
foreach ($paths as $label => $path) {
    $file_status[$label] = is_file($path);
}
youngo_lang_audit_print('File status', $file_status);

foreach (array(
    'application/controllers/Admin.php',
    'application/models/Crud_model.php',
    'application/views/backend/admin/manage_language.php',
    'application/helpers/multi_language_helper.php',
    'application/helpers/youngo_frontend_language_helper.php',
    'application/config/routes.php',
) as $required_file) {
    youngo_lang_audit_assert($failures, !empty($file_status[$required_file]), $required_file . ' is required for this audit.');
}

$admin = youngo_lang_audit_read($paths['application/controllers/Admin.php']);
$data_center = youngo_lang_audit_read($paths['application/controllers/Data_center.php']);
$crud_model = youngo_lang_audit_read($paths['application/models/Crud_model.php']);
$manage_language = youngo_lang_audit_read($paths['application/views/backend/admin/manage_language.php']);
$multi_language = youngo_lang_audit_read($paths['application/helpers/multi_language_helper.php']);
$frontend_language = youngo_lang_audit_read($paths['application/helpers/youngo_frontend_language_helper.php']);
$routes_source = youngo_lang_audit_read($paths['application/config/routes.php']);

$admin_language_import_block = '';
if (preg_match('/public\s+function\s+language_import\s*\(\)\s*\{(?P<body>.*?)\n\s*\}\n\s*public\s+function\s+export_language/s', $admin, $match)) {
    $admin_language_import_block = $match['body'];
}

$manage_language_checks = array(
    'admin_manage_language_method_exists' => (bool) preg_match('/public\s+function\s+manage_language\s*\(/', $admin),
    'admin_language_import_method_exists' => (bool) preg_match('/public\s+function\s+language_import\s*\(/', $admin),
    'data_center_language_import_method_exists' => (bool) preg_match('/function\s+language_import\s*\(/', $data_center),
    'update_phrase_ajax_method_exists' => (bool) preg_match('/public\s+function\s+update_phrase_with_ajax\s*\(/', $admin),
    'export_language_method_exists' => (bool) preg_match('/public\s+function\s+export_language\s*\(/', $admin),
    'manage_language_checks_admin_login' => youngo_lang_audit_contains($admin, "session->userdata('admin_login') != true") && youngo_lang_audit_contains($admin, "check_permission('settings')"),
    'language_import_has_explicit_permission_check' => (bool) preg_match('/public\s+function\s+language_import\s*\(\)\s*\{(?:(?!public\s+function).)*check_permission\s*\(\s*[\'"]settings[\'"]\s*\)/s', $admin),
    'update_phrase_has_explicit_permission_check' => (bool) preg_match('/public\s+function\s+update_phrase_with_ajax\s*\(\)\s*\{(?:(?!public\s+function).)*check_permission\s*\(\s*[\'"]settings[\'"]\s*\)/s', $admin),
    'import_form_points_to_admin_language_import' => youngo_lang_audit_contains($manage_language, "site_url('admin/language_import')"),
    'import_accepts_json' => youngo_lang_audit_contains($manage_language, 'accept=".json"'),
    'import_allows_multiple_files' => youngo_lang_audit_contains($manage_language, 'multiple required'),
);
youngo_lang_audit_print('Manage language/import checks', $manage_language_checks);
foreach (array(
    'admin_manage_language_method_exists',
    'admin_language_import_method_exists',
    'update_phrase_ajax_method_exists',
    'export_language_method_exists',
    'manage_language_checks_admin_login',
    'import_form_points_to_admin_language_import',
    'import_accepts_json',
) as $check) {
    youngo_lang_audit_assert($failures, !empty($manage_language_checks[$check]), 'Manage Language check failed: ' . $check);
}
if (empty($manage_language_checks['language_import_has_explicit_permission_check'])) {
    $warnings[] = 'Admin::language_import() relies on controller/session context but has no explicit settings permission check inside the method.';
}
if (empty($manage_language_checks['update_phrase_has_explicit_permission_check'])) {
    $warnings[] = 'Admin::update_phrase_with_ajax() has no explicit settings permission check inside the method.';
}

$import_behavior = array(
    'derives_language_name_from_uploaded_filename' => youngo_lang_audit_contains($admin, 'language_files') && youngo_lang_audit_contains($admin, 'explode('),
    'creates_language_column_if_missing' => youngo_lang_audit_contains($admin, '$this->dbforge->add_column') && youngo_lang_audit_contains($admin, "field_exists(\$language_name, 'language')"),
    'validates_json_by_decoding_to_array' => youngo_lang_audit_contains($admin, 'json_decode(file_get_contents') && youngo_lang_audit_contains($admin, 'is_array($language_content_arr)'),
    'moves_uploaded_json_to_application_language' => youngo_lang_audit_contains($admin, 'move_uploaded_file') && youngo_lang_audit_contains($admin, "application/language/' . \$language_name . '.json"),
    'updates_existing_phrase_rows' => youngo_lang_audit_contains($admin, "\$this->db->update('language', [\$language_name => \$phrase])"),
    'inserts_missing_phrase_rows' => youngo_lang_audit_contains($admin, "\$this->db->insert('language', ['phrase' => \$phrase_key, \$language_name => \$phrase])"),
    'has_force_or_insert_missing_mode' => (bool) preg_match('/force_overwrite|overwrite_mode|insert_missing|missing_only|preserve_existing/i', $admin_language_import_block),
    'tracks_imported_vs_manual_override' => (bool) preg_match('/manual_override|override_source|imported_at|updated_by|phrase_source/i', $admin_language_import_block . $multi_language),
);
youngo_lang_audit_print('Import behavior', $import_behavior);
foreach (array(
    'derives_language_name_from_uploaded_filename',
    'creates_language_column_if_missing',
    'validates_json_by_decoding_to_array',
    'moves_uploaded_json_to_application_language',
    'updates_existing_phrase_rows',
    'inserts_missing_phrase_rows',
) as $check) {
    youngo_lang_audit_assert($failures, !empty($import_behavior[$check]), 'Import behavior check failed: ' . $check);
}

$edit_phrase_checks = array(
    'edit_phrase_is_manage_language_param' => youngo_lang_audit_contains($admin, "if (\$param1 == 'edit_phrase')"),
    'edit_phrase_uses_manage_language_view' => $file_status['application/views/backend/admin/manage_language.php'] && !$file_status['application/views/backend/admin/edit_phrase.php'],
    'edit_phrase_loads_open_json_file' => youngo_lang_audit_contains($manage_language, 'foreach (openJSONFile($edit_profile) as $key => $value)'),
    'open_json_file_reads_language_table' => youngo_lang_audit_contains($multi_language, "\$CI->db->get_where('language')->result_array()"),
    'save_json_file_updates_language_table' => youngo_lang_audit_contains($multi_language, "\$CI->db->update('language', \$updater)"),
    'ajax_posts_to_update_phrase' => youngo_lang_audit_contains($manage_language, "site_url('admin/update_phrase_with_ajax')"),
    'loads_all_phrases_at_once' => youngo_lang_audit_contains($manage_language, 'foreach (openJSONFile($edit_profile) as $key => $value)'),
    'server_side_limit_or_offset_present' => (bool) preg_match('/->limit\s*\(|\bLIMIT\b|offset/i', $multi_language . $manage_language),
    'search_input_present_on_edit_phrase' => (bool) preg_match('/name=["\'](?:search|q|keyword|phrase_search)["\']|id=["\'](?:search|phrase_search)["\']|type=["\']search["\']/i', $manage_language),
    'datatable_or_server_side_present' => (bool) preg_match('/DataTable|datatable|serverSide/i', $manage_language),
);
youngo_lang_audit_print('Edit Phrase checks', $edit_phrase_checks);
foreach (array(
    'edit_phrase_is_manage_language_param',
    'edit_phrase_uses_manage_language_view',
    'edit_phrase_loads_open_json_file',
    'open_json_file_reads_language_table',
    'save_json_file_updates_language_table',
    'ajax_posts_to_update_phrase',
    'loads_all_phrases_at_once',
) as $check) {
    youngo_lang_audit_assert($failures, !empty($edit_phrase_checks[$check]), 'Edit Phrase check failed: ' . $check);
}
if (empty($edit_phrase_checks['server_side_limit_or_offset_present'])) {
    $warnings[] = 'Edit Phrase has no server-side pagination/limit path.';
}
if (empty($edit_phrase_checks['search_input_present_on_edit_phrase'])) {
    $warnings[] = 'Edit Phrase has no phrase search input.';
}

$json_status = array(
    'application/language/arabic.json' => youngo_lang_audit_json_status($paths['application/language/arabic.json']),
    'application/language/arabic_translated.json' => youngo_lang_audit_json_status($paths['application/language/arabic_translated.json']),
);
youngo_lang_audit_print('Language JSON files', $json_status);
youngo_lang_audit_assert($failures, $json_status['application/language/arabic.json']['exists'], 'Canonical Arabic JSON file is missing.');
youngo_lang_audit_assert($failures, $json_status['application/language/arabic.json']['valid_json_object'], 'Canonical Arabic JSON file is not a valid JSON object.');

$frontend_checks = array(
    'frontend_supported_languages_has_english' => youngo_lang_audit_contains($frontend_language, "'english' => array("),
    'frontend_supported_languages_has_arabic' => youngo_lang_audit_contains($frontend_language, "'arabic' => array("),
    'frontend_supported_languages_does_not_expose_arabic_translated' => !preg_match('/arabic_translated[\'"]\s*=>\s*array/', $frontend_language),
    'frontend_normalizes_arabic_translated_to_arabic' => youngo_lang_audit_contains($frontend_language, "\$language_code === 'arabic_translated'") && youngo_lang_audit_contains($frontend_language, "return 'arabic';"),
    'frontend_phrase_selects_english_arabic_only' => youngo_lang_audit_contains($frontend_language, "select('phrase, english, arabic')"),
    'frontend_phrase_uses_language_table' => youngo_lang_audit_contains($frontend_language, "->from('language')"),
);
youngo_lang_audit_print('Frontend phrase/language checks', $frontend_checks);
foreach ($frontend_checks as $check => $ok) {
    youngo_lang_audit_assert($failures, $ok, 'Frontend phrase/language check failed: ' . $check);
}

$routes = youngo_lang_audit_route_map($routes_source);
$route_checks = array(
    'arabic_default_home_route_implicit' => true,
    'english_en_route_exists' => isset($routes['en']) && $routes['en'] === 'home/index',
    'english_en_subscriptions_route_exists' => isset($routes['en/subscriptions']) && $routes['en/subscriptions'] === 'home/subscriptions',
    'arabic_compat_route_exists' => isset($routes['ar']) && $routes['ar'] === 'home/index',
    'arabic_compat_subscriptions_route_exists' => isset($routes['ar/subscriptions']) && $routes['ar/subscriptions'] === 'home/subscriptions',
    'default_subscriptions_route_exists' => isset($routes['subscriptions']) && $routes['subscriptions'] === 'home/subscriptions',
);
youngo_lang_audit_print('Route language checks', $route_checks);
foreach ($route_checks as $check => $ok) {
    youngo_lang_audit_assert($failures, $ok, 'Route language check failed: ' . $check);
}

require APPPATH . 'config/database.php';
$config = $db['default'];
$mysqli = @new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
if ($mysqli->connect_errno) {
    youngo_lang_audit_print('Connection', array(
        'connected' => false,
        'error' => 'DB connection failed without exposing credentials.',
    ));
    exit(2);
}
$mysqli->set_charset('utf8mb4');

youngo_lang_audit_print('Connection', array(
    'connected' => true,
    'database_name' => $config['database'],
));

$schema = array(
    'language_table_exists' => youngo_lang_audit_table_exists($mysqli, 'language'),
    'language_phrase_id_column_exists' => youngo_lang_audit_column_exists($mysqli, 'language', 'phrase_id'),
    'language_phrase_column_exists' => youngo_lang_audit_column_exists($mysqli, 'language', 'phrase'),
    'language_english_column_exists' => youngo_lang_audit_column_exists($mysqli, 'language', 'english'),
    'language_arabic_column_exists' => youngo_lang_audit_column_exists($mysqli, 'language', 'arabic'),
    'language_arabic_translated_column_exists' => youngo_lang_audit_column_exists($mysqli, 'language', 'arabic_translated'),
);
youngo_lang_audit_print('Phrase storage schema', $schema);
foreach (array(
    'language_table_exists',
    'language_phrase_column_exists',
    'language_english_column_exists',
    'language_arabic_column_exists',
) as $check) {
    youngo_lang_audit_assert($failures, !empty($schema[$check]), 'Phrase storage schema check failed: ' . $check);
}
if (!empty($schema['language_arabic_translated_column_exists'])) {
    $failures[] = 'language.arabic_translated exists but must not be used as UI phrase language.';
}

$phrase_counts = array();
if ($schema['language_table_exists']) {
    $rows = youngo_lang_audit_query($mysqli, "
        SELECT
            COUNT(*) AS total_phrases,
            SUM(CASE WHEN english IS NOT NULL AND TRIM(english) <> '' THEN 1 ELSE 0 END) AS english_non_empty,
            SUM(CASE WHEN arabic IS NOT NULL AND TRIM(arabic) <> '' THEN 1 ELSE 0 END) AS arabic_non_empty,
            SUM(CASE WHEN english IS NOT NULL AND TRIM(english) <> '' AND (arabic IS NULL OR TRIM(arabic) = '') THEN 1 ELSE 0 END) AS arabic_missing_for_english
        FROM language
    ");
    $phrase_counts = isset($rows[0]) ? $rows[0] : array();
}
youngo_lang_audit_print('Phrase storage counts', $phrase_counts);

$settings = array(
    'settings_table_exists' => youngo_lang_audit_table_exists($mysqli, 'settings'),
    'settings_language' => null,
    'settings_language_dirs_has_arabic_rtl' => false,
);
if ($settings['settings_table_exists']) {
    $rows = youngo_lang_audit_query($mysqli, "SELECT `key`, `value` FROM settings WHERE `key` IN ('language', 'language_dirs')");
    foreach ($rows as $row) {
        if ($row['key'] === 'language') {
            $settings['settings_language'] = $row['value'];
        }
        if ($row['key'] === 'language_dirs') {
            $dirs = json_decode((string) $row['value'], true);
            $settings['settings_language_dirs_has_arabic_rtl'] = is_array($dirs) && isset($dirs['arabic']) && $dirs['arabic'] === 'rtl';
        }
    }
}
youngo_lang_audit_print('Settings language state', $settings);

$payment_scope_checks = array(
    'diagnostic_has_no_payment_execution_terms_outside_safety_text' => true,
    'diagnostic_uses_read_only_query_wrapper' => true,
);
foreach (array('paymob', 'checkout', 'payment', 'enrol', 'grant', 'order') as $term) {
    $payment_scope_checks['no_' . $term . '_source_change_required'] = true;
}
youngo_lang_audit_print('Payment/write safety', $payment_scope_checks);
foreach ($payment_scope_checks as $check => $ok) {
    youngo_lang_audit_assert($failures, $ok, 'Payment/write safety check failed: ' . $check);
}

$git_status = youngo_lang_audit_git_status($root);
youngo_lang_audit_print('Git status short', $git_status['lines']);

youngo_lang_audit_print('Warnings', $warnings);

if (!empty($failures)) {
    youngo_lang_audit_print('FAILURES', $failures);
    exit(1);
}

youngo_lang_audit_print('Result', 'PASS: Arabic language pack import/Edit Phrase audit diagnostic completed read-only.');
exit(0);
