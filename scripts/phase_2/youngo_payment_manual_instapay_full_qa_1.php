<?php
/**
 * PAYMENT.MANUAL.INSTAPAY.FULL.QA.1
 *
 * Full local authenticated-session QA for manual Instapay checkout approval.
 * Uses temporary fixtures only, avoids credential handling, and restores DB
 * counts plus Instapay config after cleanup.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
defined('BASEPATH') || define('BASEPATH', $root . '/system/');
defined('APPPATH') || define('APPPATH', $root . '/application/');
defined('FCPATH') || define('FCPATH', $root . '/');
defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');

if (!function_exists('log_message')) {
    function log_message($level, $message)
    {
    }
}

if (!function_exists('is_php')) {
    function is_php($version)
    {
        return version_compare(PHP_VERSION, $version, '>=');
    }
}

if (!function_exists('show_error')) {
    function show_error($message, $status_code = 500, $heading = 'Error')
    {
        throw new RuntimeException(is_array($message) ? implode("\n", $message) : (string) $message);
    }
}

if (!function_exists('get_phrase')) {
    function get_phrase($phrase)
    {
        return (string) $phrase;
    }
}

if (!function_exists('site_url')) {
    function site_url($uri = '')
    {
        return 'http://school.local/' . ltrim((string) $uri, '/');
    }
}

if (!function_exists('base_url')) {
    function base_url($uri = '')
    {
        return 'http://school.local/' . ltrim((string) $uri, '/');
    }
}

if (!function_exists('html_escape')) {
    function html_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

require_once BASEPATH . 'core/Model.php';
require_once BASEPATH . 'database/DB.php';
require_once APPPATH . 'libraries/Youngo_paymob_config.php';
require_once APPPATH . 'models/Youngo_checkout_model.php';
require_once APPPATH . 'models/Youngo_payment_config_model.php';
require_once APPPATH . 'models/Youngo_instapay_payment_model.php';
require_once APPPATH . 'models/Youngo_entitlement_write_model.php';

$checks = array();
$details = array(
    'backup_path' => '',
    'backup_size' => '',
    'backup_sha256' => '',
    'cleanup' => 'not_started',
    'qa_mode' => 'authenticated_session_stub_model_view_route_qa',
    'learner_upload_result' => '',
    'admin_approval_result' => '',
    'rejection_result' => '',
    'regression_result' => '',
);
$fixture_order_ids = array();
$fixture_submission_ids = array();
$fixture_course_access_ids = array();
$fixture_subscription_ids = array();
$fixture_files = array();
$fixture_session_ids = array();

function ymifq1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ymifq1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ymifq1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ymifq1_backup_ident($name)
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function ymifq1_backup_value($mysqli, $value)
{
    return $value === null ? 'NULL' : "'" . $mysqli->real_escape_string($value) . "'";
}

function ymifq1_connect_mysqli($config)
{
    $host = isset($config['hostname']) ? $config['hostname'] : 'localhost';
    $port = null;
    if (strpos($host, ':') !== false && substr_count($host, ':') === 1) {
        list($host, $port) = explode(':', $host, 2);
        $port = (int) $port;
    }

    $mysqli = @new mysqli(
        $host,
        isset($config['username']) ? $config['username'] : '',
        isset($config['password']) ? $config['password'] : '',
        isset($config['database']) ? $config['database'] : '',
        $port ?: null
    );

    if ($mysqli->connect_errno) {
        return null;
    }

    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

function ymifq1_create_backup($root)
{
    $mysqli = ymifq1_connect_mysqli(ymifq1_db_config());
    if (!$mysqli) {
        return array('ok' => false, 'error' => 'connect_failed');
    }

    $backup_dir = dirname($root) . '/backups';
    if (!is_dir($backup_dir) && !mkdir($backup_dir, 0777, true)) {
        return array('ok' => false, 'error' => 'backup_dir_failed');
    }

    $path = $backup_dir . '/youngo_school_before_payment_manual_instapay_full_qa_1_' . date('Y_m_d_His') . '.sql';
    $fh = fopen($path, 'wb');
    if (!$fh) {
        return array('ok' => false, 'error' => 'backup_file_failed');
    }

    fwrite($fh, "-- YounGo backup before PAYMENT.MANUAL.INSTAPAY.FULL.QA.1\n");
    fwrite($fh, "-- Created at: " . date('c') . "\n");
    fwrite($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    $result = $mysqli->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    if (!$result) {
        fclose($fh);
        return array('ok' => false, 'error' => 'table_list_failed');
    }

    $tables = array();
    while ($row = $result->fetch_array(MYSQLI_NUM)) {
        $tables[] = $row[0];
    }

    foreach ($tables as $table) {
        $create = $mysqli->query('SHOW CREATE TABLE ' . ymifq1_backup_ident($table));
        if (!$create) {
            continue;
        }

        $create_row = $create->fetch_assoc();
        fwrite($fh, "\n-- Table " . $table . "\n");
        fwrite($fh, 'DROP TABLE IF EXISTS ' . ymifq1_backup_ident($table) . ";\n");
        fwrite($fh, $create_row['Create Table'] . ";\n\n");

        $rows = $mysqli->query('SELECT * FROM ' . ymifq1_backup_ident($table));
        if (!$rows) {
            continue;
        }

        while ($data = $rows->fetch_assoc()) {
            $columns = array();
            $values = array();
            foreach ($data as $column => $value) {
                $columns[] = ymifq1_backup_ident($column);
                $values[] = ymifq1_backup_value($mysqli, $value);
            }
            fwrite($fh, 'INSERT INTO ' . ymifq1_backup_ident($table) . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n");
        }
    }

    fwrite($fh, "\nSET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fh);

    return array(
        'ok' => true,
        'path' => $path,
        'size' => filesize($path),
        'sha256' => hash_file('sha256', $path),
    );
}

function ymifq1_filter_columns($db, $table, $data)
{
    $filtered = array();
    foreach ($data as $field => $value) {
        if ($db->field_exists($field, $table)) {
            $filtered[$field] = $value;
        }
    }

    return $filtered;
}

function ymifq1_count($db, $table)
{
    return $db->table_exists($table) ? (int) $db->count_all($table) : null;
}

function ymifq1_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ymifq1_count($db, $table);
    }

    return $counts;
}

function ymifq1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ymifq1_config_row($db)
{
    if (!$db->table_exists('youngo_payment_provider_configs')) {
        return array();
    }

    $row = $db
        ->where('provider', 'instapay_manual')
        ->where('mode', 'manual')
        ->get('youngo_payment_provider_configs', 1)
        ->row_array();

    return is_array($row) ? $row : array();
}

function ymifq1_restore_config_row($db, $row)
{
    if (!$db->table_exists('youngo_payment_provider_configs')) {
        return false;
    }

    $db->where('provider', 'instapay_manual')
        ->where('mode', 'manual')
        ->delete('youngo_payment_provider_configs');

    if (!empty($row)) {
        return $db->insert('youngo_payment_provider_configs', ymifq1_filter_columns($db, 'youngo_payment_provider_configs', $row));
    }

    return true;
}

function ymifq1_first_id($db, $table, $where = array())
{
    if (!$db->table_exists($table) || !$db->field_exists('id', $table)) {
        return null;
    }

    $builder = $db->select('id')->from($table);
    foreach ($where as $field => $value) {
        if ($db->field_exists($field, $table)) {
            $builder->where($field, $value);
        }
    }

    $row = $builder->order_by('id', 'asc')->get('', 1)->row_array();
    return !empty($row['id']) ? (int) $row['id'] : null;
}

function ymifq1_fixture_user_course($db)
{
    $users = $db->table_exists('users') ? $db->select('id')->where('role_id', 2)->where('status', 1)->order_by('id', 'asc')->get('users')->result_array() : array();
    $courses = $db->table_exists('course') ? $db->select('id')->order_by('id', 'asc')->get('course')->result_array() : array();
    foreach ($users as $user) {
        foreach ($courses as $course) {
            $has_access = $db->table_exists('youngo_course_access')
                ? (int) $db->where('user_id', (int) $user['id'])->where('course_id', (int) $course['id'])->where('status', 'active')->count_all_results('youngo_course_access') > 0
                : false;
            if (!$has_access) {
                return array((int) $user['id'], (int) $course['id']);
            }
        }
    }

    return array(null, null);
}

function ymifq1_fixture_subscription_user($db)
{
    $users = $db->table_exists('users') ? $db->select('id')->where('role_id', 2)->where('status', 1)->order_by('id', 'asc')->get('users')->result_array() : array();
    foreach ($users as $user) {
        $has_subscription = false;
        if ($db->table_exists('youngo_user_subscriptions')) {
            $db->where('user_id', (int) $user['id']);
            $db->where('status', 'active');
            $db->group_start();
            $db->where('revoked_at IS NULL', null, false);
            $db->or_where('revoked_at', 0);
            $db->group_end();
            $db->where('expiry_date >=', time());
            $has_subscription = $db->count_all_results('youngo_user_subscriptions') > 0;
        }

        if (!$has_subscription) {
            return (int) $user['id'];
        }
    }

    return null;
}

function ymifq1_create_png_fixture($root, $suffix)
{
    $relative_dir = 'uploads/youngo/instapay_evidence';
    $absolute_dir = $root . '/' . $relative_dir;
    if (!is_dir($absolute_dir)) {
        mkdir($absolute_dir, 0755, true);
    }
    if (!is_file($absolute_dir . '/.htaccess')) {
        file_put_contents($absolute_dir . '/.htaccess', "Require all denied\nDeny from all\n<FilesMatch \"\\.(php|phtml|phar)$\">\n    Deny from all\n</FilesMatch>\n");
    }
    if (!is_file($absolute_dir . '/index.html')) {
        file_put_contents($absolute_dir . '/index.html', '');
    }

    $filename = 'full_qa_instapay_' . preg_replace('/[^A-Za-z0-9_-]/', '', $suffix) . '_' . substr(hash('sha256', microtime(true)), 0, 8) . '.png';
    $absolute_path = $absolute_dir . '/' . $filename;
    file_put_contents($absolute_path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));

    return array(
        'relative_path' => $relative_dir . '/' . $filename,
        'absolute_path' => $absolute_path,
        'size' => filesize($absolute_path),
    );
}

function ymifq1_insert_order($db, $user_id, $course_id, $reference, $amount = '75.00', $order_type = 'course_purchase', $plan_id = null)
{
    $now = time();
    $item_type = $order_type === 'course_purchase' ? 'course' : 'subscription';
    $snapshot = array(
        'snapshot_version' => 'PAYMENT.COUPON.CHECKOUT.SNAPSHOT.WRITE.1',
        'snapshot_action' => 'full_qa_coupon_snapshot',
        'created_at' => $now,
        'order' => array(
            'order_reference' => $reference,
            'user_id' => (int) $user_id,
            'item_type' => $item_type,
            'course_id' => $item_type === 'course' ? (int) $course_id : null,
            'subscription_plan_id' => $item_type === 'subscription' ? (int) $plan_id : null,
            'item_title_snapshot' => $item_type === 'course' ? 'Full QA Manual Instapay Course' : 'Full QA Manual Instapay Subscription',
            'original_amount' => '100.00',
            'coupon_code' => 'FULLQA25',
            'coupon_discount_type' => 'percentage',
            'coupon_discount_value' => '25.00',
            'discount_amount' => '25.00',
            'final_amount' => $amount,
            'currency' => 'EGP',
            'selected_payment_method' => 'instapay_manual',
        ),
    );

    $db->insert('youngo_checkout_orders', ymifq1_filter_columns($db, 'youngo_checkout_orders', array(
        'user_id' => (int) $user_id,
        'order_reference' => $reference,
        'order_type' => $order_type,
        'status' => 'draft',
        'course_id' => $item_type === 'course' ? (int) $course_id : null,
        'plan_id' => $item_type === 'subscription' ? (int) $plan_id : null,
        'subtotal_amount' => '100.00',
        'discount_amount' => '25.00',
        'tax_amount' => '0.00',
        'total_amount' => $amount,
        'total_amount_cents' => (int) round(((float) $amount) * 100),
        'currency' => 'EGP',
        'coupon_id' => null,
        'coupon_code' => 'FULLQA25',
        'coupon_discount_type' => 'percentage',
        'coupon_discount_value' => '25.00',
        'selected_payment_method' => null,
        'item_title_snapshot' => $item_type === 'course' ? 'Full QA Manual Instapay Course' : 'Full QA Manual Instapay Subscription',
        'checkout_snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_SLASHES),
        'payment_gateway' => null,
        'gateway_environment' => 'sandbox',
        'idempotency_key' => hash('sha256', 'PAYMENT.MANUAL.INSTAPAY.FULL.QA.1|' . $reference),
        'last_hmac_verified' => 0,
        'entitlement_issued' => 0,
        'entitlement_issuance_status' => 'not_started',
        'metadata' => json_encode(array('source' => 'youngo_payment_manual_instapay_full_qa_1'), JSON_UNESCAPED_SLASHES),
        'created_at' => $now,
        'updated_at' => $now,
    )));

    return (int) $db->insert_id();
}

function ymifq1_insert_submission($db, $order_id, $user_id, $file, $amount = '75.00', $item_type = 'course', $course_id = null)
{
    $now = time();
    $snapshot = array(
        'snapshot_version' => 'PAYMENT.MANUAL.INSTAPAY.SUBMISSION.UPLOAD.1',
        'created_at' => $now,
        'status_model' => array('pending_review', 'approved', 'rejected'),
        'order' => array(
            'order_id' => (int) $order_id,
            'user_id' => (int) $user_id,
            'status' => 'draft',
            'item_type' => $item_type,
            'course_id' => $item_type === 'course' ? (int) $course_id : null,
            'item_title_snapshot' => $item_type === 'course' ? 'Full QA Manual Instapay Course' : 'Full QA Manual Instapay Subscription',
            'original_amount' => '100.00',
            'coupon_code' => 'FULLQA25',
            'coupon_discount_type' => 'percentage',
            'coupon_discount_value' => '25.00',
            'discount_amount' => '25.00',
            'final_amount' => $amount,
            'currency' => 'EGP',
            'selected_payment_method' => 'instapay_manual',
        ),
        'instapay' => array(
            'expected_amount' => $amount,
            'submitted_amount' => $amount,
            'currency' => 'EGP',
            'target_label' => 'Full QA Instapay Target',
            'target_address' => 'full.qa@instapay',
            'target_link' => 'https://example.com/full-qa-instapay',
            'instructions_en' => 'Full QA Instapay instructions.',
            'instructions_ar' => 'Full QA Instapay instructions.',
            'screenshot_path' => $file['relative_path'],
            'screenshot_original_name' => 'full-qa-instapay.png',
            'screenshot_mime' => 'image/png',
            'screenshot_size' => $file['size'],
            'transaction_reference' => 'FULL-QA-REF',
            'user_note' => 'Full QA pending review fixture.',
        ),
    );

    $db->insert('youngo_instapay_payment_submissions', ymifq1_filter_columns($db, 'youngo_instapay_payment_submissions', array(
        'order_id' => (int) $order_id,
        'user_id' => (int) $user_id,
        'status' => 'pending_review',
        'expected_amount' => $amount,
        'submitted_amount' => $amount,
        'currency' => 'EGP',
        'instapay_target_label' => 'Full QA Instapay Target',
        'instapay_target_address' => 'full.qa@instapay',
        'instapay_target_link' => 'https://example.com/full-qa-instapay',
        'screenshot_path' => $file['relative_path'],
        'screenshot_original_name' => 'full-qa-instapay.png',
        'screenshot_mime' => 'image/png',
        'screenshot_size' => $file['size'],
        'transaction_reference' => 'FULL-QA-REF',
        'user_note' => 'Full QA pending review fixture.',
        'admin_note' => null,
        'reviewed_by_user_id' => null,
        'reviewed_at' => null,
        'approved_at' => null,
        'rejected_at' => null,
        'access_issued' => 0,
        'access_issued_at' => null,
        'access_reference_type' => null,
        'access_reference_id' => null,
        'snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_SLASHES),
        'created_at' => $now,
        'updated_at' => $now,
    )));

    return (int) $db->insert_id();
}

class Ymifq1_session_stub
{
    protected $data = array();
    protected $flash = array();

    public function __construct($data = array())
    {
        $this->data = $data;
    }

    public function userdata($key)
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : null;
    }

    public function flashdata($key)
    {
        return array_key_exists($key, $this->flash) ? $this->flash[$key] : '';
    }

    public function set_flashdata($key, $value)
    {
        $this->flash[$key] = $value;
    }
}

class Ymifq1_security_stub
{
    public function get_csrf_token_name()
    {
        return 'csrf_test_name';
    }

    public function get_csrf_hash()
    {
        return 'fullqacsrf';
    }
}

class Ymifq1_view_renderer
{
    public $session;
    public $security;

    public function __construct($session)
    {
        $this->session = $session;
        $this->security = new Ymifq1_security_stub();
    }

    public function render($path, $vars)
    {
        extract($vars);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}

$backup = ymifq1_create_backup($root);
if (!empty($backup['ok'])) {
    $details['backup_path'] = $backup['path'];
    $details['backup_size'] = (string) $backup['size'];
    $details['backup_sha256'] = $backup['sha256'];
}

$CI = new stdClass();
$CI->session = new Ymifq1_session_stub();
$CI->security = new Ymifq1_security_stub();
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ymifq1_db_config(), true);
$CI->db = $db;
$checkout_model = new Youngo_checkout_model(array('db' => $db));
$payment_config_model = new Youngo_payment_config_model(array('db' => $db));
$instapay_model = new Youngo_instapay_payment_model(array('db' => $db));

$paths = array(
    'checkout_view' => $root . '/application/views/frontend/youngo/checkout_order.php',
    'admin_inbox_view' => $root . '/application/views/backend/admin/youngo_instapay_payments.php',
    'admin_detail_view' => $root . '/application/views/backend/admin/youngo_instapay_payment_view.php',
    'checkout_controller' => $root . '/application/controllers/Youngo_checkout.php',
    'admin_controller' => $root . '/application/controllers/Youngo_instapay_payments.php',
    'routes' => $root . '/application/config/routes.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
);
foreach ($paths as $name => $path) {
    ymifq1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}
ymifq1_check($checks, 'backup_created_before_full_qa_fixtures', !empty($backup['ok']), !empty($backup['ok']) ? $backup['path'] . '|' . $backup['size'] . '|' . $backup['sha256'] : (isset($backup['error']) ? $backup['error'] : 'backup_failed'));

$routes_source = ymifq1_read($paths['routes']);
$admin_controller_source = ymifq1_read($paths['admin_controller']);
$checkout_controller_source = ymifq1_read($paths['checkout_controller']);
$paymob_source = ymifq1_read($paths['paymob_config']);
$reader = new Youngo_paymob_config(array('load_local_override' => false));

ymifq1_check($checks, 'post_routes_present', strpos($routes_source, 'youngo/checkout/instapay/submit/(:any)') !== false && strpos($routes_source, 'admin/youngo/instapay-payments/(:num)/approve') !== false && strpos($routes_source, 'admin/youngo/instapay-payments/(:num)/reject') !== false);
ymifq1_check($checks, 'state_changing_routes_require_post_in_source', strpos($checkout_controller_source, "input->method(true)) !== 'POST'") !== false && strpos($admin_controller_source, 'require_post') !== false && strpos($admin_controller_source, 'external_payment_confirmed') !== false);
ymifq1_check($checks, 'learner_admin_denial_source_guard', strpos($admin_controller_source, "check_session_data('admin')") !== false && strpos($admin_controller_source, 'require_root_admin') !== false && strpos($admin_controller_source, 'youngo_is_root_admin') !== false);
ymifq1_check($checks, 'paymob_disabled_static_config', $reader->is_enabled() === false && $reader->is_network_enabled() === false && preg_match('/[\'"]enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1 && preg_match('/[\'"]network_enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1);

$protected_tables = array(
    'ci_sessions',
    'youngo_payment_provider_configs',
    'youngo_instapay_payment_submissions',
    'youngo_checkout_orders',
    'youngo_payment_transactions',
    'youngo_course_access',
    'youngo_user_subscriptions',
    'youngo_manual_grants',
    'youngo_coupon_usages',
    'coupons',
    'youngo_coupon_courses',
    'youngo_coupon_subscription_plans',
    'payment',
    'enrol',
);
$baseline_counts = ymifq1_counts($db, $protected_tables);
$before_config = ymifq1_config_row($db);

$admin_id = ymifq1_first_id($db, 'users', array('id' => 1, 'role_id' => 1));
list($learner_id, $course_id) = ymifq1_fixture_user_course($db);
$plan_id = ymifq1_first_id($db, 'youngo_subscription_plans');
ymifq1_check($checks, 'fixture_admin_learner_course_available', $admin_id === 1 && $learner_id !== null && $course_id !== null, 'admin=' . $admin_id . ', learner=' . $learner_id . ', course=' . $course_id);

try {
    if ($admin_id === 1 && $learner_id !== null && $course_id !== null) {
        $details['cleanup'] = 'started';

        $config_save = $payment_config_model->upsert_dashboard_instapay_manual_config(array(
            'instapay_enabled_for_checkout' => 1,
            'instapay_target_label' => 'Full QA Instapay Target',
            'instapay_target_address' => 'full.qa@instapay',
            'instapay_target_link' => 'https://example.com/full-qa-instapay',
            'instapay_instructions_en' => 'Full QA Instapay instructions.',
            'instapay_instructions_ar' => 'Full QA Instapay instructions.',
            'instapay_max_upload_mb' => '5',
        ), $admin_id);
        $target_snapshot = $payment_config_model->build_instapay_target_snapshot('english');
        ymifq1_check($checks, 'temporary_instapay_config_enabled', !empty($config_save['ok']) && $payment_config_model->is_instapay_checkout_enabled() && !empty($target_snapshot['data']['snapshot']['enabled']));

        $order_reference = 'YGO-FULL-INSTAPAY-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true)), 0, 6);
        $order_id = ymifq1_insert_order($db, $learner_id, $course_id, $order_reference);
        $fixture_order_ids[] = $order_id;
        $order = $checkout_model->get_order($order_id);
        $review_result = $checkout_model->get_safe_order_review_snapshot($order_id, $learner_id);
        $review_snapshot = !empty($review_result['data']['review_snapshot']) ? $review_result['data']['review_snapshot'] : array();
        $target = !empty($target_snapshot['data']['snapshot']) ? $target_snapshot['data']['snapshot'] : array();
        $learner_session = new Ymifq1_session_stub(array('user_id' => (string) $learner_id, 'role_id' => '2', 'user_login' => '1'));
        $learner_renderer = new Ymifq1_view_renderer($learner_session);
        $checkout_html = $learner_renderer->render($paths['checkout_view'], array(
            'youngo_checkout_order_context' => array(
                'context' => 'order',
                'notice' => array('ok' => true, 'code' => 'full_qa_checkout_loaded', 'message' => 'Full QA checkout fixture loaded.'),
                'order' => $checkout_model->get_safe_order_summary($order),
                'review_snapshot' => $review_snapshot,
                'course' => array('id' => $course_id, 'title' => 'Full QA Manual Instapay Course', 'youngo_access_mode' => 'purchase_only'),
                'payment_summary' => array(),
                'paymob' => array('checkout_url_present' => false, 'checkout_url' => null),
                'instapay' => array(
                    'enabled' => true,
                    'target' => $target,
                    'can_submit' => true,
                    'latest_submission' => array(),
                    'latest_status' => null,
                    'expected_amount' => '75.00',
                    'currency' => 'EGP',
                    'submit_url' => site_url('youngo/checkout/instapay/submit/' . rawurlencode($order_reference)),
                ),
                'zero_amount_coupon' => array('completion_available' => false, 'already_completed' => false, 'subscription_deferred' => false, 'is_zero_total_coupon_order' => false),
                'mode' => 'sandbox',
                'currency' => 'EGP',
            ),
        ));

        ymifq1_check($checks, 'learner_checkout_displays_coupon_final_instapay', strpos($checkout_html, 'FULLQA25') !== false && strpos($checkout_html, '25.00') !== false && strpos($checkout_html, '75.00') !== false && strpos($checkout_html, 'full.qa@instapay') !== false && strpos($checkout_html, 'name="instapay_screenshot"') !== false && strpos($checkout_html, 'youngo/checkout/instapay/submit/') !== false);
        ymifq1_check($checks, 'cards_wallets_disabled_in_checkout', strpos($checkout_html, 'Cards') !== false && strpos($checkout_html, 'Digital Wallets') !== false && substr_count($checkout_html, 'Not available yet') >= 2);

        $file = ymifq1_create_png_fixture($root, 'learner_upload');
        $fixture_files[] = $file['absolute_path'];
        $create = $instapay_model->create_pending_submission($order_id, $learner_id, array(
            'screenshot_path' => $file['relative_path'],
            'screenshot_original_name' => 'full-qa-instapay.png',
            'screenshot_mime' => 'image/png',
            'screenshot_size' => $file['size'],
            'submitted_amount' => '75.00',
            'language' => 'english',
        ), 'FULL-QA-REF', 'Full QA learner upload note.');

        $submission_id = !empty($create['data']['submission_id']) ? (int) $create['data']['submission_id'] : 0;
        if ($submission_id > 0) {
            $fixture_submission_ids[] = $submission_id;
        }
        $pending_submission = $instapay_model->get_submission_by_id($submission_id);
        $duplicate_pending = $instapay_model->create_pending_submission($order_id, $learner_id, array(
            'screenshot_path' => $file['relative_path'],
            'screenshot_original_name' => 'full-qa-instapay.png',
            'screenshot_mime' => 'image/png',
            'screenshot_size' => $file['size'],
            'submitted_amount' => '75.00',
            'language' => 'english',
        ), 'FULL-QA-DUP', 'Duplicate should be blocked.');
        $pre_approval_access_count = (int) $db->where('checkout_order_id', $order_id)->count_all_results('youngo_course_access');
        $details['learner_upload_result'] = !empty($create['ok']) ? 'pending_review_created' : 'failed';
        ymifq1_check($checks, 'learner_upload_creates_pending_review_only', !empty($create['ok']) && $pending_submission['status'] === 'pending_review' && $pre_approval_access_count === 0 && empty($pending_submission['access_issued']));
        ymifq1_check($checks, 'duplicate_pending_submission_blocked', empty($duplicate_pending['ok']) && isset($duplicate_pending['code']) && $duplicate_pending['code'] === 'pending_submission_exists');

        $pending_checkout_html = $learner_renderer->render($paths['checkout_view'], array(
            'youngo_checkout_order_context' => array(
                'context' => 'order',
                'notice' => array('ok' => true, 'code' => 'full_qa_pending', 'message' => 'Pending submission loaded.'),
                'order' => $checkout_model->get_safe_order_summary($order),
                'review_snapshot' => $review_snapshot,
                'course' => array('id' => $course_id, 'title' => 'Full QA Manual Instapay Course', 'youngo_access_mode' => 'purchase_only'),
                'payment_summary' => array(),
                'paymob' => array('checkout_url_present' => false, 'checkout_url' => null),
                'instapay' => array('enabled' => true, 'target' => $target, 'can_submit' => false, 'latest_submission' => $pending_submission, 'latest_status' => 'pending_review', 'expected_amount' => '75.00', 'currency' => 'EGP'),
                'zero_amount_coupon' => array('completion_available' => false, 'already_completed' => false, 'subscription_deferred' => false, 'is_zero_total_coupon_order' => false),
                'mode' => 'sandbox',
                'currency' => 'EGP',
            ),
        ));
        ymifq1_check($checks, 'learner_pending_status_displays_no_upload_form', strpos($pending_checkout_html, 'Pending review') !== false && strpos($pending_checkout_html, 'waiting for admin review') !== false && strpos($pending_checkout_html, 'name="instapay_screenshot"') === false);

        $list = $instapay_model->get_admin_review_list('pending_review', 50, 0);
        $detail = $instapay_model->get_admin_review_detail($submission_id);
        $evidence = $instapay_model->get_evidence_file_for_admin($submission_id);
        $admin_session = new Ymifq1_session_stub(array('user_id' => '1', 'role_id' => '1', 'admin_login' => '1'));
        $admin_renderer = new Ymifq1_view_renderer($admin_session);
        $inbox_html = $admin_renderer->render($paths['admin_inbox_view'], array(
            'page_title' => 'Manual Instapay Payments',
            'submissions' => $list,
            'status_counts' => $instapay_model->count_admin_review_by_status(),
            'status_filter' => 'pending_review',
        ));
        $detail_html = $admin_renderer->render($paths['admin_detail_view'], array(
            'page_title' => 'Manual Instapay Payment Review',
            'detail' => $detail,
        ));
        $approve_label_visible = strpos($detail_html, 'approve_payment_and_issue_access') !== false || strpos($detail_html, 'Approve payment and issue access') !== false;
        ymifq1_check($checks, 'admin_inbox_detail_show_review_snapshot', strpos($inbox_html, 'FULLQA25') !== false && strpos($detail_html, 'FULLQA25') !== false && strpos($detail_html, '75.00') !== false && strpos($detail_html, 'FULL-QA-REF') !== false && $approve_label_visible && strpos($detail_html, 'external_payment_confirmed') !== false);
        ymifq1_check($checks, 'evidence_preview_download_model_protected', !empty($evidence['ok']) && $evidence['data']['mime'] === 'image/png' && is_file($evidence['data']['absolute_path']));

        $approve = $instapay_model->approve_submission($submission_id, $admin_id, 'Full QA approval note.');
        $approved_submission = $instapay_model->get_submission_by_id($submission_id);
        $approved_order = $checkout_model->get_order($order_id);
        $access_rows = $db->where('checkout_order_id', $order_id)->get('youngo_course_access')->result_array();
        foreach ($access_rows as $row) {
            $fixture_course_access_ids[] = (int) $row['id'];
        }
        $access_row = !empty($access_rows) ? $access_rows[0] : array();
        $approved_at = !empty($approved_submission['approved_at']) ? (int) $approved_submission['approved_at'] : 0;
        $duplicate_approve = $instapay_model->approve_submission($submission_id, $admin_id, 'Full QA duplicate approval note.');
        $access_count_after_duplicate = (int) $db->where('checkout_order_id', $order_id)->count_all_results('youngo_course_access');
        $reject_approved = $instapay_model->reject_submission($submission_id, $admin_id, 'Should not reject approved.');
        $details['admin_approval_result'] = !empty($approve['ok']) ? 'approved_access_issued' : 'failed';
        ymifq1_check($checks, 'admin_approval_sets_audit_order_and_access', !empty($approve['ok']) && $approved_submission['status'] === 'approved' && (int) $approved_submission['reviewed_by_user_id'] === $admin_id && !empty($approved_submission['reviewed_at']) && !empty($approved_submission['approved_at']) && $approved_order['selected_payment_method'] === 'instapay_manual' && $approved_order['payment_gateway'] === 'instapay_manual' && empty($approved_order['last_hmac_verified']) && count($access_rows) === 1 && (int) $access_row['start_date'] === $approved_at);
        ymifq1_check($checks, 'duplicate_approve_idempotent', !empty($duplicate_approve['ok']) && $duplicate_approve['code'] === 'submission_already_approved' && $access_count_after_duplicate === 1);
        ymifq1_check($checks, 'approved_cannot_be_rejected', empty($reject_approved['ok']) && $reject_approved['code'] === 'approved_submission_cannot_be_rejected');

        $reject_file = ymifq1_create_png_fixture($root, 'reject');
        $fixture_files[] = $reject_file['absolute_path'];
        $reject_order_id = ymifq1_insert_order($db, $learner_id, $course_id, 'YGO-FULL-REJECT-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true)), 0, 6));
        $fixture_order_ids[] = $reject_order_id;
        $reject_submission_id = ymifq1_insert_submission($db, $reject_order_id, $learner_id, $reject_file, '75.00', 'course', $course_id);
        $fixture_submission_ids[] = $reject_submission_id;
        $reject = $instapay_model->reject_submission($reject_submission_id, $admin_id, 'Full QA rejection note.');
        $rejected_submission = $instapay_model->get_submission_by_id($reject_submission_id);
        $rejected_order = $checkout_model->get_order($reject_order_id);
        $approve_rejected = $instapay_model->approve_submission($reject_submission_id, $admin_id, 'Should not approve rejected.');
        $details['rejection_result'] = !empty($reject['ok']) ? 'rejected_no_access' : 'failed';
        ymifq1_check($checks, 'rejection_grants_no_access', !empty($reject['ok']) && $rejected_submission['status'] === 'rejected' && !empty($rejected_submission['rejected_at']) && empty($rejected_submission['access_issued']) && $rejected_order['status'] === 'draft' && (int) $db->where('checkout_order_id', $reject_order_id)->count_all_results('youngo_course_access') === 0);
        ymifq1_check($checks, 'rejected_cannot_be_approved', empty($approve_rejected['ok']) && $approve_rejected['code'] === 'rejected_submission_cannot_be_approved');

        $bad_file = ymifq1_create_png_fixture($root, 'badpath');
        $fixture_files[] = $bad_file['absolute_path'];
        $bad_order_id = ymifq1_insert_order($db, $learner_id, $course_id, 'YGO-FULL-BADPATH-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true)), 0, 6));
        $fixture_order_ids[] = $bad_order_id;
        $bad_submission_id = ymifq1_insert_submission($db, $bad_order_id, $learner_id, $bad_file, '75.00', 'course', $course_id);
        $fixture_submission_ids[] = $bad_submission_id;
        $db->where('id', $bad_submission_id)->update('youngo_instapay_payment_submissions', array('screenshot_path' => 'uploads/youngo/instapay_evidence/../../system/index.php'));
        $bad_evidence = $instapay_model->get_evidence_file_for_admin($bad_submission_id);
        ymifq1_check($checks, 'path_traversal_evidence_rejected', empty($bad_evidence['ok']) && in_array($bad_evidence['code'], array('invalid_evidence_path', 'evidence_file_not_available'), true));

        $subscription_user_id = ymifq1_fixture_subscription_user($db);
        if ($plan_id !== null && $subscription_user_id !== null) {
            $subscription_file = ymifq1_create_png_fixture($root, 'subscription');
            $fixture_files[] = $subscription_file['absolute_path'];
            $subscription_order_id = ymifq1_insert_order($db, $subscription_user_id, null, 'YGO-FULL-SUB-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true)), 0, 6), '75.00', 'subscription_purchase', $plan_id);
            $fixture_order_ids[] = $subscription_order_id;
            $subscription_submission_id = ymifq1_insert_submission($db, $subscription_order_id, $subscription_user_id, $subscription_file, '75.00', 'subscription', null);
            $fixture_submission_ids[] = $subscription_submission_id;
            $subscription_approve = $instapay_model->approve_submission($subscription_submission_id, $admin_id, 'Subscription Instapay approval QA.');
            $subscription_submission = $instapay_model->get_submission_by_id($subscription_submission_id);
            $subscription_order = $checkout_model->get_order($subscription_order_id);
            $subscription_rows = $db->where('checkout_order_id', $subscription_order_id)->get('youngo_user_subscriptions')->result_array();
            foreach ($subscription_rows as $row) {
                if (!empty($row['id'])) {
                    $fixture_subscription_ids[] = (int) $row['id'];
                }
            }
            ymifq1_check($checks, 'subscription_instapay_approval_issues_subscription_access', !empty($subscription_approve['ok']) && $subscription_submission['status'] === 'approved' && count($subscription_rows) === 1 && (int) $subscription_rows[0]['user_id'] === (int) $subscription_user_id && $subscription_order['selected_payment_method'] === 'instapay_manual' && $subscription_order['payment_gateway'] === 'instapay_manual');
        } else {
            ymifq1_check($checks, 'subscription_instapay_approval_issues_subscription_access', true, 'no eligible local subscription plan/learner fixture available');
        }

        $zero_order = array(
            'id' => 999999,
            'user_id' => $learner_id,
            'status' => 'draft',
            'currency' => 'EGP',
            'total_amount' => '0.00',
            'payment_gateway' => null,
            'entitlement_issued' => 0,
        );
        $zero_submit_check = $instapay_model->can_create_submission_for_order($zero_order);
        $details['regression_result'] = 'zero_coupon_and_payment_safety_checked';
        ymifq1_check($checks, 'zero_amount_coupon_still_blocks_instapay', empty($zero_submit_check['ok']) && $zero_submit_check['code'] === 'zero_amount_coupon_completion_required');
        ymifq1_check($checks, 'no_paymob_or_legacy_payment_created_during_run', $baseline_counts['youngo_payment_transactions'] === ymifq1_count($db, 'youngo_payment_transactions') && $baseline_counts['payment'] === ymifq1_count($db, 'payment'));
        ymifq1_check($checks, 'card_wallet_behavior_not_exposed', strpos($checkout_html, 'Continue to Paymob sandbox') === false && strpos($checkout_html, 'Not available yet') !== false);
    }
} finally {
    if (!empty($fixture_course_access_ids) && $db->table_exists('youngo_course_access')) {
        $db->where_in('id', array_values(array_unique($fixture_course_access_ids)))->delete('youngo_course_access');
    }
    if (!empty($fixture_subscription_ids) && $db->table_exists('youngo_user_subscriptions')) {
        $db->where_in('id', array_values(array_unique($fixture_subscription_ids)))->delete('youngo_user_subscriptions');
    }
    if (!empty($fixture_submission_ids) && $db->table_exists('youngo_instapay_payment_submissions')) {
        $db->where_in('id', array_values(array_unique($fixture_submission_ids)))->delete('youngo_instapay_payment_submissions');
    }
    if (!empty($fixture_order_ids) && $db->table_exists('youngo_checkout_orders')) {
        $db->where_in('id', array_values(array_unique($fixture_order_ids)))->delete('youngo_checkout_orders');
    }
    if (!empty($fixture_session_ids) && $db->table_exists('ci_sessions')) {
        $db->where_in('id', array_values(array_unique($fixture_session_ids)))->delete('ci_sessions');
    }
    foreach ($fixture_files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    ymifq1_restore_config_row($db, $before_config);
}

$after_counts = ymifq1_counts($db, $protected_tables);
$after_config = ymifq1_config_row($db);
$details['cleanup'] = ymifq1_counts_match($baseline_counts, $after_counts) && $before_config == $after_config ? 'completed' : 'failed';
ymifq1_check($checks, 'temporary_rows_files_config_cleaned', ymifq1_counts_match($baseline_counts, $after_counts) && $before_config == $after_config && empty(array_filter($fixture_files, 'is_file')), json_encode(array('before' => $baseline_counts, 'after' => $after_counts), JSON_UNESCAPED_SLASHES));
ymifq1_check($checks, 'no_access_payment_enrolment_persistent_drift', $baseline_counts['youngo_course_access'] === $after_counts['youngo_course_access'] && $baseline_counts['youngo_user_subscriptions'] === $after_counts['youngo_user_subscriptions'] && $baseline_counts['youngo_manual_grants'] === $after_counts['youngo_manual_grants'] && $baseline_counts['payment'] === $after_counts['payment'] && $baseline_counts['enrol'] === $after_counts['enrol'] && $baseline_counts['youngo_payment_transactions'] === $after_counts['youngo_payment_transactions']);

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo 'phase: PAYMENT.MANUAL.INSTAPAY.FULL.QA.1' . PHP_EOL;
echo 'mode: ' . $details['qa_mode'] . PHP_EOL;
echo 'backup_path: ' . $details['backup_path'] . PHP_EOL;
echo 'backup_size: ' . $details['backup_size'] . PHP_EOL;
echo 'backup_sha256: ' . $details['backup_sha256'] . PHP_EOL;
echo 'learner_upload_result: ' . $details['learner_upload_result'] . PHP_EOL;
echo 'admin_approval_result: ' . $details['admin_approval_result'] . PHP_EOL;
echo 'rejection_result: ' . $details['rejection_result'] . PHP_EOL;
echo 'regression_result: ' . $details['regression_result'] . PHP_EOL;
echo 'cleanup: ' . $details['cleanup'] . PHP_EOL;
echo PHP_EOL;

foreach ($checks as $name => $check) {
    echo $check['status'] . ' - ' . $name;
    if ($check['detail'] !== '') {
        echo ' :: ' . $check['detail'];
    }
    echo PHP_EOL;
}

echo PHP_EOL . 'RESULT: ' . (empty($failed) ? 'PASS' : 'FAIL') . PHP_EOL;
if (!empty($failed)) {
    exit(1);
}
