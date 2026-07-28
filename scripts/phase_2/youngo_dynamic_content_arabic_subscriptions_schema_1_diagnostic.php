<?php
/**
 * DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.SCHEMA.1 diagnostic.
 *
 * Verifies the additive subscription plan translation schema and model
 * foundation. It performs one temporary translation row insert/read/delete
 * and cleans it up. It does not edit real subscription plan data, change
 * public subscription behavior, call payment providers, or expose secrets.
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
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'config' . DIRECTORY_SEPARATOR . 'database.php';

$failures = array();
$translationTable = 'youngo_subscription_plan_translations';
$diagName = '__YOUNGO_SUBSCRIPTION_TRANSLATION_SCHEMA_DIAGNOSTIC__';

function ysp_schema_diag_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function ysp_schema_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function ysp_schema_diag_source($root, $relative)
{
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    return is_file($path) ? file_get_contents($path) : '';
}

function ysp_schema_diag_connect($db, $active_group)
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

function ysp_schema_diag_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return isset($row['total']) && (int) $row['total'] > 0;
}

function ysp_schema_diag_table_fields($mysqli, $table)
{
    if (!ysp_schema_diag_table_exists($mysqli, $table)) {
        return array();
    }

    $fields = array();
    $result = $mysqli->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
    while ($result && ($row = $result->fetch_assoc())) {
        $fields[] = $row['Field'];
    }

    return $fields;
}

function ysp_schema_diag_scalar($mysqli, $sql)
{
    $result = $mysqli->query($sql);
    if (!$result) {
        return null;
    }

    $row = $result->fetch_row();
    return isset($row[0]) ? $row[0] : null;
}

function ysp_schema_diag_changed_files($root)
{
    $output = array();
    $exit_code = 1;
    exec('git -C ' . escapeshellarg($root) . ' status --short', $output, $exit_code);
    if ($exit_code !== 0) {
        return array();
    }

    $files = array();
    foreach ($output as $line) {
        $files[] = trim(substr($line, 3));
    }

    return $files;
}

function ysp_schema_diag_extract_method_body($source, $method)
{
    $needle = 'function ' . $method . '(';
    $start = strpos($source, $needle);
    if ($start === false) {
        return '';
    }

    $brace = strpos($source, '{', $start);
    if ($brace === false) {
        return '';
    }

    $depth = 0;
    $length = strlen($source);
    for ($i = $brace; $i < $length; $i++) {
        if ($source[$i] === '{') {
            $depth++;
        } elseif ($source[$i] === '}') {
            $depth--;
            if ($depth === 0) {
                return substr($source, $start, $i - $start + 1);
            }
        }
    }

    return '';
}

try {
    $mysqli = ysp_schema_diag_connect($db, $active_group);

    $requiredFiles = array(
        'application/models/Youngo_subscription_model.php',
        'application/controllers/Youngo_subscription_plans.php',
        'application/controllers/Home.php',
        'application/views/frontend/youngo/subscriptions.php',
        'scripts/phase_2/dynamic_content_arabic_subscriptions_schema_1_up.sql',
        'scripts/phase_2/dynamic_content_arabic_subscriptions_schema_1_down.sql',
    );

    $fileStatus = array();
    foreach ($requiredFiles as $file) {
        $fileStatus[$file] = is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file));
    }
    ysp_schema_diag_print('Required file status', $fileStatus);
    foreach ($fileStatus as $file => $exists) {
        ysp_schema_diag_assert($failures, $exists, $file . ' is missing.');
    }

    $fields = ysp_schema_diag_table_fields($mysqli, $translationTable);
    $expectedFields = array(
        'id',
        'plan_id',
        'language_code',
        'name',
        'short_description',
        'description',
        'badge_label',
        'created_by_user_id',
        'updated_by_user_id',
        'created_at',
        'updated_at',
    );
    $forbiddenFields = array(
        'slug',
        'price',
        'currency',
        'duration',
        'duration_days',
        'is_active',
        'is_purchasable',
        'payment',
        'paymob',
        'checkout',
        'order_id',
        'enrol_id',
        'access_grant',
    );

    $schemaChecks = array(
        'translation_table_exists' => !empty($fields),
        'fields' => $fields,
        'missing_expected_fields' => array_values(array_diff($expectedFields, $fields)),
        'forbidden_fields_present' => array_values(array_intersect($forbiddenFields, $fields)),
    );

    $uniqueRows = ysp_schema_diag_scalar(
        $mysqli,
        "SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = '" . $mysqli->real_escape_string($translationTable) . "' AND index_name = 'uniq_yspt_plan_language' AND non_unique = 0 AND column_name IN ('plan_id', 'language_code')"
    );
    $schemaChecks['unique_plan_language_key'] = (int) $uniqueRows === 2;

    ysp_schema_diag_print('Schema checks', $schemaChecks);
    ysp_schema_diag_assert($failures, $schemaChecks['translation_table_exists'], 'Translation table does not exist.');
    ysp_schema_diag_assert($failures, empty($schemaChecks['missing_expected_fields']), 'Expected translation columns are missing.');
    ysp_schema_diag_assert($failures, empty($schemaChecks['forbidden_fields_present']), 'Forbidden operational/payment fields exist in translation table.');
    ysp_schema_diag_assert($failures, $schemaChecks['unique_plan_language_key'], 'Unique plan_id + language_code key was not found.');

    $arabicTranslatedRows = ysp_schema_diag_scalar(
        $mysqli,
        "SELECT COUNT(*) FROM `" . str_replace('`', '``', $translationTable) . "` WHERE language_code = 'arabic_translated'"
    );
    ysp_schema_diag_assert($failures, (int) $arabicTranslatedRows === 0, 'arabic_translated translation rows must not exist.');

    $modelSource = ysp_schema_diag_source($root, 'application/models/Youngo_subscription_model.php');
    $publicMethodBody = ysp_schema_diag_extract_method_body($modelSource, 'get_public_subscription_plans');
    $modelChecks = array(
        'translation_table_property' => strpos($modelSource, "protected \$translation_table = 'youngo_subscription_plan_translations';") !== false,
        'table_exists_method' => strpos($modelSource, 'function subscription_translation_table_exists') !== false,
        'language_normalizer_method' => strpos($modelSource, 'function normalize_subscription_translation_language') !== false,
        'get_plan_translations_method' => strpos($modelSource, 'function get_plan_translations') !== false,
        'get_plan_translation_method' => strpos($modelSource, 'function get_plan_translation') !== false,
        'save_foundation_method' => strpos($modelSource, 'function save_plan_translation_foundation') !== false,
        'arabic_translated_not_accepted_by_translation_normalizer' =>
            strpos($modelSource, "in_array(\$language, array('english', 'arabic'), true)") !== false
            && strpos(ysp_schema_diag_extract_method_body($modelSource, 'normalize_subscription_translation_language'), 'arabic_translated') === false,
        'translation_validation_rejects_operational_fields' =>
            strpos($modelSource, "'slug'") !== false
            && strpos($modelSource, "'price'") !== false
            && strpos($modelSource, "'paymob'") !== false
            && strpos($modelSource, "'checkout'") !== false,
        'public_translation_reads_preserve_operational_fields' =>
            strpos($modelSource, 'function load_public_plan_translations') !== false
            && strpos($modelSource, 'function apply_public_plan_translation') !== false
            && strpos(ysp_schema_diag_extract_method_body($modelSource, 'apply_public_plan_translation'), "array('name', 'short_description', 'description', 'badge_label')") !== false,
    );
    ysp_schema_diag_print('Model foundation checks', $modelChecks);
    foreach ($modelChecks as $label => $ok) {
        ysp_schema_diag_assert($failures, $ok, 'Model foundation check failed: ' . $label);
    }

    $planId = (int) ysp_schema_diag_scalar($mysqli, 'SELECT id FROM youngo_subscription_plans ORDER BY id ASC LIMIT 1');
    ysp_schema_diag_assert($failures, $planId > 0, 'No subscription plan row was available for temporary translation diagnostic.');

    $escapedName = $mysqli->real_escape_string($diagName);
    $mysqli->query("DELETE FROM `" . str_replace('`', '``', $translationTable) . "` WHERE name = '" . $escapedName . "'");
    $beforeTempRows = (int) ysp_schema_diag_scalar(
        $mysqli,
        "SELECT COUNT(*) FROM `" . str_replace('`', '``', $translationTable) . "` WHERE name = '" . $escapedName . "'"
    );

    $now = time();
    $insertSql = "INSERT INTO `" . str_replace('`', '``', $translationTable) . "` (plan_id, language_code, name, short_description, description, badge_label, created_at, updated_at) VALUES (" .
        (int) $planId . ", 'arabic', '" . $escapedName . "', 'diagnostic only', NULL, NULL, " . (int) $now . ", " . (int) $now . ")";
    $inserted = $mysqli->query($insertSql);
    $readBackRows = (int) ysp_schema_diag_scalar(
        $mysqli,
        "SELECT COUNT(*) FROM `" . str_replace('`', '``', $translationTable) . "` WHERE plan_id = " . (int) $planId . " AND language_code = 'arabic' AND name = '" . $escapedName . "'"
    );
    $mysqli->query("DELETE FROM `" . str_replace('`', '``', $translationTable) . "` WHERE name = '" . $escapedName . "'");
    $afterCleanupRows = (int) ysp_schema_diag_scalar(
        $mysqli,
        "SELECT COUNT(*) FROM `" . str_replace('`', '``', $translationTable) . "` WHERE name = '" . $escapedName . "'"
    );

    $temporaryRowChecks = array(
        'plan_id_used' => $planId,
        'before_temp_rows' => $beforeTempRows,
        'inserted' => (bool) $inserted,
        'read_back_rows' => $readBackRows,
        'after_cleanup_rows' => $afterCleanupRows,
    );
    ysp_schema_diag_print('Temporary row checks', $temporaryRowChecks);
    ysp_schema_diag_assert($failures, $beforeTempRows === 0, 'Temporary diagnostic rows existed before insert.');
    ysp_schema_diag_assert($failures, (bool) $inserted, 'Temporary diagnostic insert failed.');
    ysp_schema_diag_assert($failures, $readBackRows === 1, 'Temporary diagnostic row was not readable.');
    ysp_schema_diag_assert($failures, $afterCleanupRows === 0, 'Temporary diagnostic row was not cleaned up.');

    $homeSource = ysp_schema_diag_source($root, 'application/controllers/Home.php');
    $publicViewSource = ysp_schema_diag_source($root, 'application/views/frontend/youngo/subscriptions.php');
    $publicChecks = array(
        'home_uses_existing_public_method' => strpos($homeSource, 'get_public_subscription_plans($youngo_frontend_language)') !== false,
        'view_loops_dynamic_plans' => strpos($publicViewSource, 'foreach ($youngo_subscription_plans as $plan)') !== false,
        'view_uses_contact_cta' => strpos($publicViewSource, 'youngo_frontend_contact_url') !== false,
        'view_has_no_paymob' => stripos($publicViewSource, 'paymob') === false,
        'view_has_no_checkout' => stripos($publicViewSource, 'checkout') === false,
    );
    ysp_schema_diag_print('Public behavior checks', $publicChecks);
    foreach ($publicChecks as $label => $ok) {
        ysp_schema_diag_assert($failures, $ok, 'Public behavior check failed: ' . $label);
    }

    $changedFiles = ysp_schema_diag_changed_files($root);
    $protectedChanged = array();
    foreach ($changedFiles as $file) {
        if (preg_match('#(?:paymob|payment|checkout|cart|order|enrol|grant|youngo_security|payment_gateways)#i', $file)) {
            $protectedChanged[] = $file;
        }
    }
    $safetyChecks = array(
        'changed_files' => $changedFiles,
        'protected_payment_or_access_files_changed' => $protectedChanged,
    );
    ysp_schema_diag_print('Safety checks', $safetyChecks);
    ysp_schema_diag_assert($failures, empty($protectedChanged), 'Payment/checkout/access protected files changed in this phase.');

    $mysqli->close();
} catch (Throwable $e) {
    $failures[] = $e->getMessage();
}

if (!empty($failures)) {
    ysp_schema_diag_print('Failures', $failures);
    exit(1);
}

ysp_schema_diag_print('Result', 'PASS: subscription translation schema and model foundation are ready; temporary diagnostic row was cleaned up.');
exit(0);
