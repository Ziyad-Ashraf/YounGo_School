<?php
/**
 * DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.SCHEMA.PLAN.1 diagnostic.
 *
 * Read-only planning diagnostic for Arabic/English subscription plan
 * translation schema readiness. This script does not execute SQL writes,
 * edit plan data, change routes, call payment providers, or expose secrets.
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

require $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

$failures = array();

function ysp_schema_plan_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function ysp_schema_plan_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function ysp_schema_plan_source($root, $relative)
{
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    return is_file($path) ? file_get_contents($path) : '';
}

function ysp_schema_plan_connect($db, $active_group)
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

function ysp_schema_plan_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return isset($row['total']) && (int) $row['total'] > 0;
}

function ysp_schema_plan_table_fields($mysqli, $table)
{
    if (!ysp_schema_plan_table_exists($mysqli, $table)) {
        return array();
    }

    $fields = array();
    $result = $mysqli->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
    while ($result && ($row = $result->fetch_assoc())) {
        $fields[] = $row['Field'];
    }

    return $fields;
}

function ysp_schema_plan_count_rows($mysqli, $table, $where = '')
{
    if (!ysp_schema_plan_table_exists($mysqli, $table)) {
        return null;
    }

    $sql = 'SELECT COUNT(*) AS total FROM `' . str_replace('`', '``', $table) . '`';
    if ($where !== '') {
        $sql .= ' WHERE ' . $where;
    }

    $result = $mysqli->query($sql);
    if (!$result) {
        return null;
    }

    $row = $result->fetch_assoc();
    return isset($row['total']) ? (int) $row['total'] : null;
}

function ysp_schema_plan_route_map($source)
{
    $routes = array();
    if (preg_match_all('/\\$route\\[[\'"]([^\'"]+)[\'"]\\]\\s*=\\s*[\'"]([^\'"]+)[\'"]\\s*;/', $source, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $routes[$match[1]] = $match[2];
        }
    }

    return $routes;
}

function ysp_schema_plan_changed_files($root)
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

try {
    $required_files = array(
        'application/models/Youngo_subscription_model.php',
        'application/controllers/Home.php',
        'application/views/frontend/youngo/subscriptions.php',
        'application/controllers/Youngo_subscription_plans.php',
        'application/views/backend/admin/youngo_subscription_plans.php',
        'application/views/backend/admin/youngo_subscription_plan_form.php',
        'application/views/backend/admin/youngo_subscription_plan_view.php',
        'application/config/routes.php',
    );

    $file_status = array();
    foreach ($required_files as $file) {
        $file_status[$file] = is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file));
    }
    ysp_schema_plan_print('Required file status', $file_status);
    foreach ($file_status as $file => $exists) {
        ysp_schema_plan_assert($failures, $exists, $file . ' is missing.');
    }

    $mysqli = ysp_schema_plan_connect($db, $active_group);

    $plan_table = 'youngo_subscription_plans';
    $translation_table = 'youngo_subscription_plan_translations';
    $plan_fields = ysp_schema_plan_table_fields($mysqli, $plan_table);
    $translation_fields = ysp_schema_plan_table_fields($mysqli, $translation_table);

    $public_where_parts = array(
        'is_active = 1',
        'is_purchasable = 1',
        "currency = 'EGP'",
        'price > 0',
        'duration_days > 0',
    );
    if (in_array('archived_at', $plan_fields, true)) {
        $public_where_parts[] = 'archived_at IS NULL';
    }
    if (in_array('deleted_at', $plan_fields, true)) {
        $public_where_parts[] = 'deleted_at IS NULL';
    }
    if (in_array('is_deleted', $plan_fields, true)) {
        $public_where_parts[] = 'is_deleted = 0';
    }

    $schema_summary = array(
        'plan_table_exists' => !empty($plan_fields),
        'plan_fields' => $plan_fields,
        'translation_table_exists' => !empty($translation_fields),
        'translation_fields' => $translation_fields,
        'total_plan_rows' => ysp_schema_plan_count_rows($mysqli, $plan_table),
        'public_eligible_plan_rows' => !empty($plan_fields) ? ysp_schema_plan_count_rows($mysqli, $plan_table, implode(' AND ', $public_where_parts)) : null,
        'arabic_translated_translation_rows' => in_array('language_code', $translation_fields, true) ? ysp_schema_plan_count_rows($mysqli, $translation_table, "language_code = 'arabic_translated'") : null,
    );
    ysp_schema_plan_print('Schema summary', $schema_summary);

    foreach (array('id', 'name', 'slug', 'duration_days', 'price', 'currency', 'is_active', 'is_purchasable') as $field) {
        ysp_schema_plan_assert($failures, in_array($field, $plan_fields, true), 'Required plan field missing: ' . $field);
    }
    ysp_schema_plan_assert($failures, $schema_summary['public_eligible_plan_rows'] !== null, 'Public subscription plan count could not be detected.');
    ysp_schema_plan_assert($failures, $schema_summary['arabic_translated_translation_rows'] === null || $schema_summary['arabic_translated_translation_rows'] === 0, 'arabic_translated rows exist in subscription plan translations.');

    if (!empty($translation_fields)) {
        foreach (array('plan_id', 'language_code', 'name') as $field) {
            ysp_schema_plan_assert($failures, in_array($field, $translation_fields, true), 'Existing translation table is missing expected field: ' . $field);
        }
    }

    $sources = array(
        'model' => ysp_schema_plan_source($root, 'application/models/Youngo_subscription_model.php'),
        'home' => ysp_schema_plan_source($root, 'application/controllers/Home.php'),
        'public_view' => ysp_schema_plan_source($root, 'application/views/frontend/youngo/subscriptions.php'),
        'admin_controller' => ysp_schema_plan_source($root, 'application/controllers/Youngo_subscription_plans.php'),
        'admin_form' => ysp_schema_plan_source($root, 'application/views/backend/admin/youngo_subscription_plan_form.php'),
        'routes' => ysp_schema_plan_source($root, 'application/config/routes.php'),
    );

    $routes = ysp_schema_plan_route_map($sources['routes']);
    $route_checks = array(
        'default_public_route' => isset($routes['subscriptions']) && $routes['subscriptions'] === 'home/subscriptions',
        'english_public_route' => isset($routes['en/subscriptions']) && $routes['en/subscriptions'] === 'home/subscriptions',
        'arabic_compat_public_route' => isset($routes['ar/subscriptions']) && $routes['ar/subscriptions'] === 'home/subscriptions',
        'admin_index_route' => isset($routes['admin/youngo/subscription-plans']) && $routes['admin/youngo/subscription-plans'] === 'youngo_subscription_plans/index',
        'admin_create_route' => isset($routes['admin/youngo/subscription-plans/create']),
        'admin_edit_route' => isset($routes['admin/youngo/subscription-plans/(:num)/edit']),
    );
    ysp_schema_plan_print('Route checks', $route_checks);
    foreach ($route_checks as $label => $ok) {
        ysp_schema_plan_assert($failures, $ok, 'Route check failed: ' . $label);
    }

    $model_checks = array(
        'public_method_exists' => strpos($sources['model'], 'public function get_public_subscription_plans($language = null)') !== false,
        'public_filters_active' => strpos($sources['model'], "where('is_active', 1)") !== false,
        'public_filters_purchasable' => strpos($sources['model'], "where('is_purchasable', 1)") !== false,
        'public_filters_egp' => strpos($sources['model'], '$this->youngo_commercial_currency') !== false,
        'public_sorts_featured_first' => strpos($sources['model'], "order_by('is_featured', 'DESC')") !== false,
        'public_normalized_fields' => strpos($sources['model'], "'price_display'") !== false && strpos($sources['model'], "'duration_label'") !== false,
        'slug_dependency_guard_exists' => strpos($sources['model'], 'Slug cannot be changed after a plan has subscriptions, orders, coupons, or manual grants.') !== false,
        'no_translation_table_model_wiring_yet' => strpos($sources['model'], $translation_table) === false,
    );
    ysp_schema_plan_print('Model checks', $model_checks);
    foreach (array('public_method_exists', 'public_filters_active', 'public_filters_purchasable', 'public_filters_egp', 'public_normalized_fields') as $label) {
        ysp_schema_plan_assert($failures, !empty($model_checks[$label]), 'Model check failed: ' . $label);
    }

    $admin_checks = array(
        'capability_guarded' => strpos($sources['admin_controller'], "youngo_require_capability('manage_subscriptions'") !== false,
        'form_name_field' => strpos($sources['admin_form'], 'name="name"') !== false,
        'form_slug_field' => strpos($sources['admin_form'], 'name="slug"') !== false,
        'form_duration_field' => strpos($sources['admin_form'], 'name="duration_days"') !== false,
        'form_price_field' => strpos($sources['admin_form'], 'name="price"') !== false,
        'form_active_field' => strpos($sources['admin_form'], 'name="is_active"') !== false,
        'form_purchasable_field' => strpos($sources['admin_form'], 'name="is_purchasable"') !== false,
        'form_featured_field' => strpos($sources['admin_form'], 'name="is_featured"') !== false,
        'form_sort_field' => strpos($sources['admin_form'], 'name="sort_order"') !== false,
        'no_bilingual_fields_yet' => strpos($sources['admin_form'], 'arabic_name') === false && strpos($sources['admin_form'], 'english_name') === false,
    );
    ysp_schema_plan_print('Admin form checks', $admin_checks);
    foreach (array('capability_guarded', 'form_name_field', 'form_slug_field', 'form_duration_field', 'form_price_field') as $label) {
        ysp_schema_plan_assert($failures, !empty($admin_checks[$label]), 'Admin check failed: ' . $label);
    }

    $public_checks = array(
        'home_method_exists' => strpos($sources['home'], 'public function subscriptions()') !== false,
        'home_uses_public_model' => strpos($sources['home'], 'get_public_subscription_plans($youngo_frontend_language)') !== false,
        'view_loops_dynamic_plans' => strpos($sources['public_view'], 'foreach ($youngo_subscription_plans as $plan)') !== false,
        'view_renders_model_name' => strpos($sources['public_view'], '$plan_name') !== false,
        'view_renders_model_description' => strpos($sources['public_view'], '$plan_description') !== false,
        'static_labels_use_phrase_helper' => strpos($sources['public_view'], 'youngo_frontend_phrase(') !== false,
        'contact_cta_only' => strpos($sources['public_view'], 'youngo_frontend_contact_url') !== false,
    );
    ysp_schema_plan_print('Public wiring checks', $public_checks);
    foreach ($public_checks as $label => $ok) {
        ysp_schema_plan_assert($failures, $ok, 'Public wiring check failed: ' . $label);
    }

    $changed_files = ysp_schema_plan_changed_files($root);
    $protected_changed = array();
    foreach ($changed_files as $file) {
        if (preg_match('#(?:application/(?:controllers|models|views|libraries)/.*(?:paymob|payment|checkout|cart|order|enrol|grant)|application/config/youngo_security|payment_gateways)#i', $file)) {
            $protected_changed[] = $file;
        }
    }

    $safety_checks = array(
        'changed_files' => $changed_files,
        'protected_payment_or_access_files_changed' => $protected_changed,
        'public_view_has_no_paymob_link' => stripos($sources['public_view'], 'paymob') === false,
        'public_view_has_no_checkout_link' => stripos($sources['public_view'], 'checkout') === false,
        'public_view_has_no_order_or_enrol_link' => stripos($sources['public_view'], 'youngo_checkout_orders') === false && stripos($sources['public_view'], 'enrol') === false,
    );
    ysp_schema_plan_print('Safety checks', $safety_checks);
    ysp_schema_plan_assert($failures, empty($protected_changed), 'Payment/checkout/access protected files changed in this phase.');
    ysp_schema_plan_assert($failures, $safety_checks['public_view_has_no_paymob_link'], 'Public subscriptions view contains Paymob text/link.');
    ysp_schema_plan_assert($failures, $safety_checks['public_view_has_no_checkout_link'], 'Public subscriptions view contains checkout text/link.');

    $mysqli->close();
} catch (Throwable $e) {
    $failures[] = $e->getMessage();
}

if (!empty($failures)) {
    ysp_schema_plan_print('Failures', $failures);
    exit(1);
}

ysp_schema_plan_print('Result', 'PASS: subscription translation schema planning diagnostic completed without DB writes.');
exit(0);
