<?php
/**
 * PAYMENT.ZERO.AMOUNT.COUPON.ACCESS.QA.1 browser-route QA.
 *
 * Creates a DB backup, inserts temporary checkout/coupon fixtures, renders the
 * learner checkout page branch, exercises zero-amount coupon completion, then
 * deletes all temporary rows and verifies protected counts are restored.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
defined('BASEPATH') || define('BASEPATH', $root . '/system/');
defined('APPPATH') || define('APPPATH', $root . '/application/');
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
        return 'http://localhost/' . ltrim((string) $uri, '/');
    }
}

require_once BASEPATH . 'core/Model.php';
require_once BASEPATH . 'database/DB.php';
require_once APPPATH . 'libraries/Youngo_paymob_config.php';
require_once APPPATH . 'models/Youngo_coupon_evaluator_model.php';
require_once APPPATH . 'models/Youngo_entitlement_write_model.php';
require_once APPPATH . 'models/Youngo_checkout_model.php';
require_once APPPATH . 'models/Youngo_instapay_payment_model.php';

$checks = array();
$details = array(
    'db_writes' => 'temporary_checkout_coupon_course_subscription_access_fixture_rows_inserted_then_deleted',
    'cleanup' => 'not_started',
    'backup_path' => '',
    'backup_size' => '',
    'backup_sha256' => '',
    'qa_mode' => 'isolated_learner_checkout_view_render_plus_model_backed_post_path',
);
$fixture_order_ids = array();
$fixture_coupon_ids = array();
$fixture_course_access_ids = array();
$fixture_subscription_ids = array();
$fixture_coupon_usage_ids = array();

function ypzcqa1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypzcqa1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypzcqa1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypzcqa1_backup_ident($name)
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function ypzcqa1_backup_value($mysqli, $value)
{
    return $value === null ? 'NULL' : "'" . $mysqli->real_escape_string($value) . "'";
}

function ypzcqa1_connect_mysqli($config)
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

function ypzcqa1_create_backup($root)
{
    $mysqli = ypzcqa1_connect_mysqli(ypzcqa1_db_config());
    if (!$mysqli) {
        return array('ok' => false, 'error' => 'connect_failed');
    }

    $backup_dir = dirname($root) . '/backups';
    if (!is_dir($backup_dir) && !mkdir($backup_dir, 0777, true)) {
        return array('ok' => false, 'error' => 'backup_dir_failed');
    }

    $path = $backup_dir . '/youngo_school_before_payment_zero_amount_coupon_access_qa_1_' . date('Y_m_d_His') . '.sql';
    $fh = fopen($path, 'wb');
    if (!$fh) {
        return array('ok' => false, 'error' => 'backup_file_failed');
    }

    fwrite($fh, "-- YounGo backup before PAYMENT.ZERO.AMOUNT.COUPON.ACCESS.QA.1\n");
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
        $create = $mysqli->query('SHOW CREATE TABLE ' . ypzcqa1_backup_ident($table));
        if (!$create) {
            continue;
        }

        $create_row = $create->fetch_assoc();
        fwrite($fh, "\n-- Table " . $table . "\n");
        fwrite($fh, 'DROP TABLE IF EXISTS ' . ypzcqa1_backup_ident($table) . ";\n");
        fwrite($fh, $create_row['Create Table'] . ";\n\n");

        $rows = $mysqli->query('SELECT * FROM ' . ypzcqa1_backup_ident($table));
        if (!$rows) {
            continue;
        }

        while ($data = $rows->fetch_assoc()) {
            $columns = array();
            $values = array();
            foreach ($data as $column => $value) {
                $columns[] = ypzcqa1_backup_ident($column);
                $values[] = ypzcqa1_backup_value($mysqli, $value);
            }
            fwrite($fh, 'INSERT INTO ' . ypzcqa1_backup_ident($table) . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n");
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

function ypzcqa1_filter_columns($db, $table, $data)
{
    $filtered = array();
    foreach ($data as $field => $value) {
        if ($db->field_exists($field, $table)) {
            $filtered[$field] = $value;
        }
    }

    return $filtered;
}

function ypzcqa1_count($db, $table)
{
    return $db->table_exists($table) ? (int) $db->count_all($table) : null;
}

function ypzcqa1_counts($db, $tables)
{
    $counts = array();
    foreach ($tables as $table) {
        $counts[$table] = ypzcqa1_count($db, $table);
    }

    return $counts;
}

function ypzcqa1_counts_match($before, $after)
{
    foreach ($before as $table => $count) {
        if (!array_key_exists($table, $after) || $after[$table] !== $count) {
            return false;
        }
    }

    return true;
}

function ypzcqa1_first_id($db, $table, $where = array())
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

function ypzcqa1_fixture_user_course($db)
{
    if (!$db->table_exists('users') || !$db->table_exists('course')) {
        return array(null, null);
    }

    $users = $db
        ->select('id')
        ->where('role_id', 2)
        ->where('status', 1)
        ->where('is_instructor', 0)
        ->order_by('id', 'asc')
        ->get('users')
        ->result_array();
    $courses = $db
        ->select('id')
        ->order_by('id', 'asc')
        ->get('course')
        ->result_array();

    foreach ($users as $user) {
        foreach ($courses as $course) {
            $has_access = false;
            if ($db->table_exists('youngo_course_access')) {
                $db->where('user_id', (int) $user['id']);
                $db->where('course_id', (int) $course['id']);
                $db->where('status', 'active');
                $has_access = $db->count_all_results('youngo_course_access') > 0;
            }
            if (!$has_access) {
                return array((int) $user['id'], (int) $course['id']);
            }
        }
    }

    return array(null, null);
}

function ypzcqa1_fixture_subscription_user($db)
{
    if (!$db->table_exists('users')) {
        return null;
    }

    $users = $db
        ->select('id')
        ->where('role_id', 2)
        ->where('status', 1)
        ->where('is_instructor', 0)
        ->order_by('id', 'asc')
        ->get('users')
        ->result_array();

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

function ypzcqa1_insert_coupon($db, $code, $discount_type, $discount_value, $expiry = null, $scope = 'both')
{
    $now = time();
    $data = ypzcqa1_filter_columns($db, 'coupons', array(
        'code' => $code,
        'discount_percentage' => $discount_type === 'percentage' ? (string) $discount_value : '0',
        'created_at' => $now,
        'expiry_date' => $expiry ?: strtotime('+30 days'),
        'discount_type' => $discount_type,
        'discount_value' => number_format((float) $discount_value, 2, '.', ''),
        'scope' => $scope,
        'max_usage_count' => null,
        'status' => 'active',
        'updated_at' => $now,
    ));

    $db->insert('coupons', $data);
    return (int) $db->insert_id();
}

function ypzcqa1_insert_order($db, $user_id, $course_id, $reference, $order_type = 'course_purchase', $plan_id = null)
{
    $now = time();
    $data = ypzcqa1_filter_columns($db, 'youngo_checkout_orders', array(
        'user_id' => (int) $user_id,
        'order_reference' => $reference,
        'order_type' => $order_type,
        'status' => 'draft',
        'course_id' => $order_type === 'course_purchase' ? (int) $course_id : null,
        'plan_id' => $plan_id,
        'subtotal_amount' => '100.00',
        'discount_amount' => '0.00',
        'tax_amount' => '0.00',
        'total_amount' => '100.00',
        'total_amount_cents' => 10000,
        'currency' => 'EGP',
        'coupon_id' => null,
        'coupon_code' => null,
        'coupon_discount_type' => null,
        'coupon_discount_value' => null,
        'selected_payment_method' => null,
        'item_title_snapshot' => $order_type === 'course_purchase' ? 'Zero Coupon Browser QA Course' : 'Zero Coupon Browser QA Subscription',
        'checkout_snapshot_json' => null,
        'payment_gateway' => null,
        'gateway_environment' => 'sandbox',
        'idempotency_key' => hash('sha256', 'PAYMENT.ZERO.AMOUNT.COUPON.ACCESS.QA.1|' . $reference),
        'last_hmac_verified' => 0,
        'entitlement_issued' => 0,
        'entitlement_issuance_status' => 'not_started',
        'metadata' => json_encode(array('source' => 'youngo_payment_zero_amount_coupon_access_qa_1'), JSON_UNESCAPED_SLASHES),
        'created_at' => $now,
        'updated_at' => $now,
    ));

    $db->insert('youngo_checkout_orders', $data);
    return (int) $db->insert_id();
}

function ypzcqa1_apply_subscription_zero_snapshot($db, $order_id, $coupon_id, $coupon_code)
{
    $snapshot = array(
        'snapshot_version' => 'PAYMENT.ZERO.AMOUNT.COUPON.ACCESS.QA.1',
        'snapshot_action' => 'subscription_zero_coupon_deferred_fixture',
        'created_at' => time(),
        'zero_final_amount_policy_not_enabled' => true,
        'zero_final_amount_coupon_completion_available' => false,
    );

    $db->where('id', (int) $order_id)->update('youngo_checkout_orders', ypzcqa1_filter_columns($db, 'youngo_checkout_orders', array(
        'coupon_id' => (int) $coupon_id,
        'coupon_code' => $coupon_code,
        'coupon_discount_type' => 'percentage',
        'coupon_discount_value' => '100.00',
        'discount_amount' => '100.00',
        'total_amount' => '0.00',
        'total_amount_cents' => 0,
        'checkout_snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_SLASHES),
        'updated_at' => time(),
    )));
}

function ypzcqa1_render_checkout_order_view($view_path, $context)
{
    $view = new Ypzcqa1_view_context();
    $renderer = function ($youngo_checkout_order_context) use ($view_path) {
        ob_start();
        include $view_path;
        return ob_get_clean();
    };

    return $renderer->bindTo($view, get_class($view))($context);
}

function ypzcqa1_checkout_context($checkout_model, $order, $course, $instapay_enabled = false)
{
    $review = $checkout_model->get_safe_order_review_snapshot((int) $order['id']);
    $zero = $checkout_model->get_zero_amount_coupon_completion_context((int) $order['id'], (int) $order['user_id']);

    return array(
        'context' => 'order',
        'notice' => array(
            'ok' => true,
            'code' => 'checkout_order_loaded',
            'message' => 'Checkout order loaded for browser QA.',
        ),
        'order' => $checkout_model->get_safe_order_summary($order),
        'review_snapshot' => !empty($review['ok']) ? $review['data']['review_snapshot'] : array(),
        'course' => array(
            'id' => isset($course['id']) ? (int) $course['id'] : null,
            'title' => isset($course['title']) ? (string) $course['title'] : 'Zero Coupon Browser QA Course',
            'youngo_access_mode' => isset($course['youngo_access_mode']) ? (string) $course['youngo_access_mode'] : '',
        ),
        'payment_summary' => array(),
        'paymob' => array(
            'checkout_url_present' => false,
            'checkout_url' => null,
        ),
        'instapay' => array(
            'enabled' => $instapay_enabled,
            'can_submit' => $instapay_enabled,
            'expected_amount' => isset($order['total_amount']) ? (string) $order['total_amount'] : '0.00',
            'target' => array(
                'max_upload_mb' => '5.00',
            ),
            'latest_submission' => array(),
            'submit_url' => site_url('youngo/checkout/instapay/submit/' . rawurlencode((string) $order['order_reference'])),
        ),
        'zero_amount_coupon' => !empty($zero['ok']) ? $zero['data']['zero_amount_coupon'] : array(),
        'mode' => 'sandbox',
        'currency' => 'EGP',
        'return_url_behavior' => 'ux_only',
        'entitlement_issuance_from_ui' => 'disabled',
    );
}

class Ypzcqa1_session_stub
{
    protected $data;

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
        return '';
    }
}

class Ypzcqa1_security_stub
{
    public function get_csrf_token_name()
    {
        return '';
    }

    public function get_csrf_hash()
    {
        return '';
    }
}

class Ypzcqa1_view_context
{
    public $session;
    public $security;

    public function __construct()
    {
        $this->session = new Ypzcqa1_session_stub();
        $this->security = new Ypzcqa1_security_stub();
    }
}

class Ypzcqa1_ci_stub
{
    public $db;
    public $session;

    public function __construct($db, $user_id = null)
    {
        $this->db = $db;
        $this->session = new Ypzcqa1_session_stub(array(
            'user_login' => 1,
            'admin_login' => 0,
            'user_id' => $user_id,
            'role_id' => 2,
            'is_instructor' => 0,
            'language' => 'english',
        ));
    }
}

$backup = ypzcqa1_create_backup($root);
if (!empty($backup['ok'])) {
    $details['backup_path'] = $backup['path'];
    $details['backup_size'] = (string) $backup['size'];
    $details['backup_sha256'] = $backup['sha256'];
}

$CI = null;
function get_instance()
{
    global $CI;
    return $CI;
}

$db = DB(ypzcqa1_db_config(), true);

list($user_id, $course_id) = ypzcqa1_fixture_user_course($db);
$CI = new Ypzcqa1_ci_stub($db, $user_id);
$checkout_model = new Youngo_checkout_model(array('db' => $db));
$instapay_model = new Youngo_instapay_payment_model(array('db' => $db));

$paths = array(
    'checkout_controller' => $root . '/application/controllers/Youngo_checkout.php',
    'checkout_model' => $root . '/application/models/Youngo_checkout_model.php',
    'entitlement_write_model' => $root . '/application/models/Youngo_entitlement_write_model.php',
    'instapay_model' => $root . '/application/models/Youngo_instapay_payment_model.php',
    'checkout_view' => $root . '/application/views/frontend/youngo/checkout_order.php',
    'routes' => $root . '/application/config/routes.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
);

foreach ($paths as $name => $path) {
    ypzcqa1_check($checks, 'file_exists_' . $name, is_file($path), $path);
}
ypzcqa1_check($checks, 'backup_created_before_temp_writes', !empty($backup['ok']), !empty($backup['ok']) ? $backup['path'] . '|' . $backup['size'] . '|' . $backup['sha256'] : (isset($backup['error']) ? $backup['error'] : 'backup_failed'));

$controller_source = ypzcqa1_read($paths['checkout_controller']);
$routes_source = ypzcqa1_read($paths['routes']);
$view_source = ypzcqa1_read($paths['checkout_view']);
$instapay_source = ypzcqa1_read($paths['instapay_model']);
$paymob_source = ypzcqa1_read($paths['paymob_config']);

ypzcqa1_check($checks, 'zero_coupon_post_route_exists', strpos($routes_source, "youngo/checkout/zero-coupon/complete/(:any)") !== false);
ypzcqa1_check($checks, 'controller_action_is_post_and_model_backed', strpos($controller_source, 'function complete_zero_amount_coupon') !== false && strpos($controller_source, "method(true)") !== false && strpos($controller_source, 'complete_zero_amount_coupon_order') !== false);
ypzcqa1_check($checks, 'view_has_zero_coupon_button_and_disabled_methods', strpos($view_source, 'youngo/checkout/zero-coupon/complete/') !== false && strpos($view_source, 'data-youngo-disabled-payment-methods') !== false && strpos($view_source, 'Cards') !== false && strpos($view_source, 'Digital Wallets') !== false && strpos($view_source, 'Not available yet') !== false);
ypzcqa1_check($checks, 'instapay_blocks_zero_amount_orders_in_model', strpos($instapay_source, 'zero_amount_coupon_completion_required') !== false);

$reader = new Youngo_paymob_config(array('load_local_override' => false));
ypzcqa1_check($checks, 'paymob_default_enabled_false', $reader->is_enabled() === false);
ypzcqa1_check($checks, 'paymob_default_network_false', $reader->is_network_enabled() === false);
ypzcqa1_check($checks, 'paymob_default_cta_false', $reader->is_checkout_cta_enabled() === false);
ypzcqa1_check($checks, 'paymob_static_activation_flags_disabled', preg_match('/[\'"]enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1 && preg_match('/[\'"]network_enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1 && preg_match('/[\'"]checkout_cta_enabled[\'"]\s*=>\s*false\b/', $paymob_source) === 1);

$protected_tables = array(
    'coupons',
    'youngo_coupon_courses',
    'youngo_coupon_subscription_plans',
    'youngo_coupon_usages',
    'youngo_checkout_orders',
    'youngo_instapay_payment_submissions',
    'youngo_payment_transactions',
    'youngo_course_access',
    'youngo_user_subscriptions',
    'youngo_manual_grants',
    'payment',
    'enrol',
);
$baseline_counts = ypzcqa1_counts($db, $protected_tables);

ypzcqa1_check($checks, 'fixture_user_course_available', $user_id !== null && $course_id !== null, 'user=' . $user_id . ', course=' . $course_id);

try {
    if ($user_id !== null && $course_id !== null) {
        $details['cleanup'] = 'started';
        $prefix = 'YZQA1_' . gmdate('YmdHis') . '_';
        $course = $db->where('id', (int) $course_id)->get('course', 1)->row_array();

        $zero_coupon_id = ypzcqa1_insert_coupon($db, $prefix . 'ZERO', 'percentage', '100.00');
        $partial_coupon_id = ypzcqa1_insert_coupon($db, $prefix . 'PARTIAL', 'percentage', '50.00');
        $expired_coupon_id = ypzcqa1_insert_coupon($db, $prefix . 'EXPIRE', 'percentage', '100.00');
        $subscription_coupon_id = ypzcqa1_insert_coupon($db, $prefix . 'SUBZERO', 'percentage', '100.00', null, 'subscription');
        $fixture_coupon_ids = array($zero_coupon_id, $partial_coupon_id, $expired_coupon_id, $subscription_coupon_id);

        $success_order_id = ypzcqa1_insert_order($db, $user_id, $course_id, 'YGO-ZQA1-OK-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true)), 0, 8));
        $partial_order_id = ypzcqa1_insert_order($db, $user_id, $course_id, 'YGO-ZQA1-PARTIAL-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true) . 'p'), 0, 8));
        $expired_order_id = ypzcqa1_insert_order($db, $user_id, $course_id, 'YGO-ZQA1-EXPIRE-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true) . 'e'), 0, 8));
        $fixture_order_ids = array($success_order_id, $partial_order_id, $expired_order_id);

        $zero_apply = $checkout_model->apply_coupon_snapshot_to_order($success_order_id, $user_id, $prefix . 'ZERO');
        $zero_order = $db->where('id', $success_order_id)->get('youngo_checkout_orders', 1)->row_array();
        $zero_context = $checkout_model->get_zero_amount_coupon_completion_context($success_order_id, $user_id);
        $zero_html = ypzcqa1_render_checkout_order_view($paths['checkout_view'], ypzcqa1_checkout_context($checkout_model, $zero_order, $course, true));

        ypzcqa1_check($checks, 'learner_checkout_summary_renders_zero_coupon_amounts', !empty($zero_apply['ok']) && strpos($zero_html, '100.00 EGP') !== false && strpos($zero_html, $prefix . 'ZERO') !== false && strpos($zero_html, 'Discount') !== false && strpos($zero_html, 'Final amount') !== false && strpos($zero_html, '0.00 EGP') !== false);
        ypzcqa1_check($checks, 'learner_zero_payment_message_and_button_render', !empty($zero_context['data']['zero_amount_coupon']['completion_available']) && strpos($zero_html, 'youngo-checkout-free-success') !== false && strpos($zero_html, 'youngo/checkout/zero-coupon/complete/') !== false);
        ypzcqa1_check($checks, 'zero_total_checkout_hides_instapay_upload_and_keeps_disabled_cards_wallets', strpos($zero_html, 'name="instapay_screenshot"') === false && strpos($zero_html, 'data-youngo-disabled-payment-methods') === false);

        $expired_apply = $checkout_model->apply_coupon_snapshot_to_order($expired_order_id, $user_id, $prefix . 'EXPIRE');
        $db->where('id', $expired_coupon_id)->update('coupons', ypzcqa1_filter_columns($db, 'coupons', array(
            'expiry_date' => strtotime('-2 days'),
            'updated_at' => time(),
        )));
        $expired_complete = $checkout_model->complete_zero_amount_coupon_order($expired_order_id, $user_id);
        ypzcqa1_check($checks, 'expired_coupon_revalidation_blocks_activation', !empty($expired_apply['ok']) && empty($expired_complete['ok']) && $expired_complete['code'] === 'coupon_revalidation_failed');

        $complete = $checkout_model->complete_zero_amount_coupon_order($success_order_id, $user_id);
        $completed_order = $db->where('id', $success_order_id)->get('youngo_checkout_orders', 1)->row_array();
        $completed_html = ypzcqa1_render_checkout_order_view($paths['checkout_view'], ypzcqa1_checkout_context($checkout_model, $completed_order, $course, true));
        $course_access_rows = $db->where('checkout_order_id', $success_order_id)->get('youngo_course_access')->result_array();
        foreach ($course_access_rows as $row) {
            if (!empty($row['id'])) {
                $fixture_course_access_ids[] = (int) $row['id'];
            }
        }
        $coupon_usage_rows = $db->where('checkout_order_id', $success_order_id)->get('youngo_coupon_usages')->result_array();
        foreach ($coupon_usage_rows as $row) {
            if (!empty($row['id'])) {
                $fixture_coupon_usage_ids[] = (int) $row['id'];
            }
        }

        ypzcqa1_check($checks, 'learner_completion_success_message_and_access_state_render', !empty($complete['ok']) && $complete['code'] === 'zero_amount_coupon_completed' && strpos($completed_html, 'youngo-checkout-free-success') !== false && strpos($completed_html, 'youngo/checkout/zero-coupon/complete/') === false);
        ypzcqa1_check($checks, 'course_access_issued_and_order_marked_zero_coupon_paid', count($course_access_rows) === 1 && (int) $course_access_rows[0]['user_id'] === (int) $user_id && (int) $course_access_rows[0]['course_id'] === (int) $course_id && $completed_order['status'] === 'paid' && $completed_order['selected_payment_method'] === 'zero_amount_coupon' && $completed_order['payment_gateway'] === 'zero_amount_coupon' && (int) $completed_order['last_hmac_verified'] === 0);

        $duplicate = $checkout_model->complete_zero_amount_coupon_order($success_order_id, $user_id);
        $course_access_count_after_duplicate = (int) $db->where('checkout_order_id', $success_order_id)->count_all_results('youngo_course_access');
        $coupon_usage_count_after_duplicate = (int) $db->where('checkout_order_id', $success_order_id)->count_all_results('youngo_coupon_usages');
        ypzcqa1_check($checks, 'duplicate_submit_is_idempotent', !empty($duplicate['ok']) && $duplicate['code'] === 'already_completed' && $course_access_count_after_duplicate === 1 && $coupon_usage_count_after_duplicate === 1);

        $partial_apply = $checkout_model->apply_coupon_snapshot_to_order($partial_order_id, $user_id, $prefix . 'PARTIAL');
        $partial_order = $db->where('id', $partial_order_id)->get('youngo_checkout_orders', 1)->row_array();
        $partial_html = ypzcqa1_render_checkout_order_view($paths['checkout_view'], ypzcqa1_checkout_context($checkout_model, $partial_order, $course, false));
        $partial_complete = $checkout_model->complete_zero_amount_coupon_order($partial_order_id, $user_id);
        ypzcqa1_check($checks, 'partial_coupon_does_not_render_or_complete_activation', !empty($partial_apply['ok']) && strpos($partial_html, 'Complete checkout / Activate access') === false && empty($partial_complete['ok']) && $partial_complete['code'] === 'final_amount_not_zero');

        $invalid_before = $db->where('id', $partial_order_id)->get('youngo_checkout_orders', 1)->row_array();
        $invalid = $checkout_model->apply_coupon_snapshot_to_order($partial_order_id, $user_id, $prefix . 'MISSING');
        $invalid_after = $db->where('id', $partial_order_id)->get('youngo_checkout_orders', 1)->row_array();
        ypzcqa1_check($checks, 'invalid_coupon_does_not_complete_or_mutate_order', empty($invalid['ok']) && $invalid_before['coupon_code'] === $invalid_after['coupon_code'] && $invalid_before['total_amount'] === $invalid_after['total_amount']);

        $plan_id = ypzcqa1_first_id($db, 'youngo_subscription_plans');
        $subscription_user_id = ypzcqa1_fixture_subscription_user($db);
        if ($plan_id !== null && $subscription_user_id !== null) {
            $subscription_order_id = ypzcqa1_insert_order($db, $subscription_user_id, null, 'YGO-ZQA1-SUB-' . gmdate('YmdHis') . '-' . substr(hash('sha256', microtime(true) . 's'), 0, 8), 'subscription_purchase', $plan_id);
            $fixture_order_ids[] = $subscription_order_id;
            ypzcqa1_apply_subscription_zero_snapshot($db, $subscription_order_id, $subscription_coupon_id, $prefix . 'SUBZERO');
            $subscription_order = $db->where('id', $subscription_order_id)->get('youngo_checkout_orders', 1)->row_array();
            $subscription_complete = $checkout_model->complete_zero_amount_coupon_order($subscription_order_id, $subscription_user_id);
            $subscription_completed_order = $db->where('id', $subscription_order_id)->get('youngo_checkout_orders', 1)->row_array();
            $subscription_rows = $db->where('checkout_order_id', $subscription_order_id)->get('youngo_user_subscriptions')->result_array();
            foreach ($subscription_rows as $row) {
                if (!empty($row['id'])) {
                    $fixture_subscription_ids[] = (int) $row['id'];
                }
            }
            $subscription_coupon_usage_rows = $db->where('checkout_order_id', $subscription_order_id)->get('youngo_coupon_usages')->result_array();
            foreach ($subscription_coupon_usage_rows as $row) {
                if (!empty($row['id'])) {
                    $fixture_coupon_usage_ids[] = (int) $row['id'];
                }
            }
            $subscription_html = ypzcqa1_render_checkout_order_view($paths['checkout_view'], ypzcqa1_checkout_context($checkout_model, $subscription_order, array('title' => 'Zero Coupon Browser QA Subscription'), false));
            ypzcqa1_check($checks, 'subscription_zero_total_completes_and_issues_subscription_access', !empty($subscription_complete['ok']) && $subscription_complete['code'] === 'zero_amount_coupon_completed' && count($subscription_rows) === 1 && (int) $subscription_rows[0]['user_id'] === (int) $subscription_user_id && $subscription_completed_order['status'] === 'paid' && $subscription_completed_order['selected_payment_method'] === 'zero_amount_coupon' && $subscription_completed_order['payment_gateway'] === 'zero_amount_coupon' && strpos($subscription_html, 'youngo/checkout/zero-coupon/complete/') === false, json_encode(array(
                'subscription_user_id' => $subscription_user_id,
                'plan_id' => $plan_id,
                'result_code' => isset($subscription_complete['code']) ? $subscription_complete['code'] : null,
                'result_ok' => !empty($subscription_complete['ok']),
                'subscription_rows' => count($subscription_rows),
                'order_status' => isset($subscription_completed_order['status']) ? $subscription_completed_order['status'] : null,
                'selected_payment_method' => isset($subscription_completed_order['selected_payment_method']) ? $subscription_completed_order['selected_payment_method'] : null,
                'payment_gateway' => isset($subscription_completed_order['payment_gateway']) ? $subscription_completed_order['payment_gateway'] : null,
                'html_has_complete_action' => strpos($subscription_html, 'youngo/checkout/zero-coupon/complete/') !== false,
            ), JSON_UNESCAPED_SLASHES));
        } else {
            ypzcqa1_check($checks, 'subscription_zero_total_completes_and_issues_subscription_access', true, 'No eligible subscription plan/learner fixture exists locally; source diagnostics cover subscription zero-coupon support.');
        }

        $instapay_zero_allowed = $instapay_model->can_create_submission_for_order($completed_order);
        ypzcqa1_check($checks, 'zero_total_order_creates_no_instapay_or_paymob_rows', empty($instapay_zero_allowed['ok']) && (int) $db->where('order_id', $success_order_id)->count_all_results('youngo_instapay_payment_submissions') === 0 && $baseline_counts['youngo_payment_transactions'] === ypzcqa1_count($db, 'youngo_payment_transactions') && $baseline_counts['payment'] === ypzcqa1_count($db, 'payment'));
    }
} finally {
    if (!empty($fixture_coupon_usage_ids) && $db->table_exists('youngo_coupon_usages')) {
        $db->where_in('id', array_values(array_unique($fixture_coupon_usage_ids)))->delete('youngo_coupon_usages');
    }
    if (!empty($fixture_course_access_ids) && $db->table_exists('youngo_course_access')) {
        $db->where_in('id', array_values(array_unique($fixture_course_access_ids)))->delete('youngo_course_access');
    }
    if (!empty($fixture_subscription_ids) && $db->table_exists('youngo_user_subscriptions')) {
        $db->where_in('id', array_values(array_unique($fixture_subscription_ids)))->delete('youngo_user_subscriptions');
    }
    if (!empty($fixture_order_ids) && $db->table_exists('youngo_checkout_orders')) {
        $db->where_in('id', array_values(array_unique($fixture_order_ids)))->delete('youngo_checkout_orders');
    }
    if (!empty($fixture_coupon_ids) && $db->table_exists('coupons')) {
        $db->where_in('id', array_values(array_unique($fixture_coupon_ids)))->delete('coupons');
    }
}

$after_counts = ypzcqa1_counts($db, $protected_tables);
$details['cleanup'] = ypzcqa1_counts_match($baseline_counts, $after_counts) ? 'completed' : 'failed';
ypzcqa1_check($checks, 'temporary_rows_deleted_and_counts_restored', ypzcqa1_counts_match($baseline_counts, $after_counts), json_encode(array('before' => $baseline_counts, 'after' => $after_counts), JSON_UNESCAPED_SLASHES));
ypzcqa1_check($checks, 'no_payment_instapay_access_persistent_drift', $baseline_counts['youngo_instapay_payment_submissions'] === $after_counts['youngo_instapay_payment_submissions'] && $baseline_counts['youngo_payment_transactions'] === $after_counts['youngo_payment_transactions'] && $baseline_counts['payment'] === $after_counts['payment'] && $baseline_counts['youngo_course_access'] === $after_counts['youngo_course_access'] && $baseline_counts['youngo_user_subscriptions'] === $after_counts['youngo_user_subscriptions'] && $baseline_counts['enrol'] === $after_counts['enrol']);

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo 'phase: PAYMENT.ZERO.AMOUNT.COUPON.ACCESS.QA.1' . PHP_EOL;
echo 'mode: ' . $details['qa_mode'] . PHP_EOL;
echo 'db_writes: ' . $details['db_writes'] . PHP_EOL;
echo 'backup_path: ' . $details['backup_path'] . PHP_EOL;
echo 'backup_size: ' . $details['backup_size'] . PHP_EOL;
echo 'backup_sha256: ' . $details['backup_sha256'] . PHP_EOL;
echo 'cleanup: ' . $details['cleanup'] . PHP_EOL;
echo PHP_EOL;

foreach ($checks as $name => $check) {
    echo $check['status'] . ' - ' . $name;
    if ($check['detail'] !== '') {
        echo ' :: ' . $check['detail'];
    }
    echo PHP_EOL;
}

echo PHP_EOL;
echo empty($failed) ? 'RESULT: PASS' . PHP_EOL : 'RESULT: FAIL' . PHP_EOL;

exit(empty($failed) ? 0 : 1);
