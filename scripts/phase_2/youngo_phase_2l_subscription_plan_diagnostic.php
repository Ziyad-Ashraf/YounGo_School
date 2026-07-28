<?php
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This diagnostic must be run from CLI only.\n");
    exit(1);
}

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

$projectRoot = dirname(__DIR__, 2);
chdir($projectRoot);

defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');
defined('FCPATH') || define('FCPATH', $projectRoot . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', $projectRoot . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);
defined('BASEPATH') || define('BASEPATH', $projectRoot . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);

require APPPATH . 'config/database.php';

if (!isset($db[$active_group])) {
    fwrite(STDERR, "Active database group was not found.\n");
    exit(1);
}

class YoungoPhase2LDiagnosticResult
{
    protected $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function num_rows()
    {
        return count($this->rows);
    }

    public function row_array()
    {
        return isset($this->rows[0]) ? $this->rows[0] : array();
    }

    public function result_array()
    {
        return $this->rows;
    }

    public function row($field = null)
    {
        $row = $this->row_array();
        if ($field !== null) {
            return isset($row[$field]) ? $row[$field] : null;
        }

        return (object) $row;
    }
}

class YoungoPhase2LDiagnosticDb
{
    protected $mysqli;

    public function __construct($config)
    {
        $this->mysqli = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
        if ($this->mysqli->connect_errno) {
            throw new RuntimeException('DB connection failed.');
        }

        $this->mysqli->set_charset(!empty($config['char_set']) ? $config['char_set'] : 'utf8');
    }

    public function query($sql, $binds = array())
    {
        if (!empty($binds)) {
            foreach ($binds as $bind) {
                $sql = preg_replace('/\?/', $this->escapeValue($bind), $sql, 1);
            }
        }

        $this->assertReadOnlySql($sql);
        $result = $this->mysqli->query($sql);
        if ($result === false) {
            throw new RuntimeException('Read query failed: ' . $this->mysqli->error);
        }

        if ($result === true) {
            return new YoungoPhase2LDiagnosticResult(array());
        }

        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();

        return new YoungoPhase2LDiagnosticResult($rows);
    }

    public function table_exists($table)
    {
        return (int) $this->query(
            "SELECT COUNT(*) AS row_count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
            array($table)
        )->row('row_count') > 0;
    }

    public function field_exists($field, $table)
    {
        return (int) $this->query(
            "SELECT COUNT(*) AS row_count FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?",
            array($table, $field)
        )->row('row_count') > 0;
    }

    public function server_version()
    {
        return $this->mysqli->server_info;
    }

    protected function assertReadOnlySql($sql)
    {
        $trimmed = ltrim($sql);
        if (preg_match('/^(INSERT|UPDATE|DELETE|ALTER|DROP|CREATE|TRUNCATE|REPLACE|GRANT|REVOKE|LOAD|CALL|OPTIMIZE|ANALYZE)\b/i', $trimmed)) {
            throw new RuntimeException('Blocked non-read SQL in diagnostic.');
        }
    }

    protected function escapeValue($value)
    {
        if ($value === null) {
            return 'NULL';
        }

        return "'" . $this->mysqli->real_escape_string((string) $value) . "'";
    }
}

class YoungoPhase2LDiagnosticLoader
{
    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    public function database()
    {
        return $this;
    }

    public function model($model, $alias = null)
    {
        $modelPath = APPPATH . 'models' . DIRECTORY_SEPARATOR . $model . '.php';
        if (!file_exists($modelPath)) {
            throw new RuntimeException('Model file not found: ' . $model);
        }

        require_once $modelPath;
        $object = new $model();
        $this->ci->{$alias ? $alias : $model} = $object;
        return $this;
    }
}

class YoungoPhase2LDiagnosticSession
{
    public function userdata($key)
    {
        return null;
    }

    public function set_flashdata($key, $value)
    {
        return false;
    }
}

class YoungoPhase2LDiagnosticCi
{
    public $db;
    public $load;
    public $session;
    public $youngo_capability_model;

    public function __construct($db)
    {
        $this->db = $db;
        $this->load = new YoungoPhase2LDiagnosticLoader($this);
        $this->session = new YoungoPhase2LDiagnosticSession();
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

$diagnosticDb = new YoungoPhase2LDiagnosticDb($db[$active_group]);
$diagnosticCi = new YoungoPhase2LDiagnosticCi($diagnosticDb);
$failures = array();

function &get_instance()
{
    global $diagnosticCi;
    return $diagnosticCi;
}

require_once APPPATH . 'helpers' . DIRECTORY_SEPARATOR . 'youngo_capability_helper.php';
$diagnosticCi->load->model('Youngo_capability_model', 'youngo_capability_model');

function phase2l_heading($label)
{
    echo "\n== " . $label . " ==\n";
}

function phase2l_print($label, $payload)
{
    echo $label . ': ' . json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n";
}

function phase2l_assert($condition, $message)
{
    global $failures;
    if (!$condition) {
        $failures[] = $message;
    }

    return (bool) $condition;
}

function phase2l_count_table($table)
{
    global $diagnosticDb;
    if (!$diagnosticDb->table_exists($table)) {
        return null;
    }

    return (int) $diagnosticDb->query('SELECT COUNT(*) AS row_count FROM `' . $table . '`')->row('row_count');
}

function phase2l_plan_dependency_count($table)
{
    global $diagnosticDb;
    if (!$diagnosticDb->table_exists($table) || !$diagnosticDb->field_exists('plan_id', $table)) {
        return null;
    }

    return (int) $diagnosticDb->query('SELECT COUNT(*) AS row_count FROM `' . $table . '` WHERE `plan_id` IS NOT NULL')->row('row_count');
}

function phase2l_orphan_count($table)
{
    global $diagnosticDb;
    if (!$diagnosticDb->table_exists($table) || !$diagnosticDb->field_exists('plan_id', $table) || !$diagnosticDb->table_exists('youngo_subscription_plans')) {
        return null;
    }

    return (int) $diagnosticDb->query(
        'SELECT COUNT(*) AS row_count
         FROM `' . $table . '` t
         LEFT JOIN `youngo_subscription_plans` p ON p.`id` = t.`plan_id`
         WHERE t.`plan_id` IS NOT NULL AND p.`id` IS NULL'
    )->row('row_count');
}

phase2l_heading('Connection');
phase2l_print('db', array(
    'connected' => true,
    'database_name' => $diagnosticDb->query('SELECT DATABASE() AS database_name')->row('database_name'),
    'server_version' => $diagnosticDb->server_version(),
));

phase2l_heading('Subscription plan table');
$plan_table_exists = $diagnosticDb->table_exists('youngo_subscription_plans');
$plan_count = $plan_table_exists ? phase2l_count_table('youngo_subscription_plans') : null;
phase2l_print('plans', array(
    'youngo_subscription_plans_exists' => $plan_table_exists,
    'plan_count' => $plan_count,
));
phase2l_assert($plan_table_exists, 'youngo_subscription_plans table is missing.');

$current_plans = array();
if ($plan_table_exists) {
    $current_plans = $diagnosticDb->query(
        'SELECT * FROM `youngo_subscription_plans` ORDER BY `sort_order` ASC, `id` ASC'
    )->result_array();
}
phase2l_print('current_seeded_or_existing_plans', $current_plans);

phase2l_heading('Phase 2L archive and audit schema');
$archive_columns = array(
    'archived_at' => $plan_table_exists && $diagnosticDb->field_exists('archived_at', 'youngo_subscription_plans'),
    'archived_by_user_id' => $plan_table_exists && $diagnosticDb->field_exists('archived_by_user_id', 'youngo_subscription_plans'),
);
$audit_table_exists = $diagnosticDb->table_exists('youngo_subscription_plan_audit_log');
$audit_indexes = $audit_table_exists ? $diagnosticDb->query(
    "SELECT `INDEX_NAME`, GROUP_CONCAT(`COLUMN_NAME` ORDER BY `SEQ_IN_INDEX` ASC) AS columns
     FROM information_schema.statistics
     WHERE table_schema = DATABASE() AND table_name = 'youngo_subscription_plan_audit_log'
     GROUP BY `INDEX_NAME`
     ORDER BY `INDEX_NAME` ASC"
)->result_array() : array();
$phase2l_schema_applied = $archive_columns['archived_at'] && $archive_columns['archived_by_user_id'] && $audit_table_exists;
phase2l_print('archive_columns', $archive_columns);
phase2l_print('audit_table', array('exists' => $audit_table_exists, 'indexes' => $audit_indexes));
phase2l_print('phase_2l_schema', $phase2l_schema_applied ? 'applied' : 'not applied');

phase2l_heading('Dependencies and orphan checks');
$dependency_tables = array(
    'youngo_user_subscriptions',
    'youngo_checkout_orders',
    'youngo_coupon_subscription_plans',
    'youngo_manual_grants',
);
$dependency_counts = array();
$orphan_counts = array();
foreach ($dependency_tables as $table) {
    $dependency_counts[$table] = phase2l_plan_dependency_count($table);
    $orphan_counts[$table] = phase2l_orphan_count($table);
}
phase2l_print('dependency_counts_with_plan_id', $dependency_counts);
phase2l_print('orphan_counts', $orphan_counts);

phase2l_heading('Currency');
$expected_currency = 'EGP';
$system_currency = null;
if ($diagnosticDb->table_exists('settings')) {
    $system_currency = $diagnosticDb->query("SELECT `value` FROM `settings` WHERE `key` = 'system_currency' LIMIT 1")->row('value');
}
$system_currency = trim((string) $system_currency);
$system_currency_is_expected = $system_currency === $expected_currency;
$non_egp_plans = array();
foreach ($current_plans as $plan) {
    $currency = isset($plan['currency']) ? trim((string) $plan['currency']) : '';
    if ($currency !== $expected_currency) {
        $non_egp_plans[] = array(
            'id' => isset($plan['id']) ? $plan['id'] : null,
            'slug' => isset($plan['slug']) ? $plan['slug'] : null,
            'currency' => $currency,
            'is_active' => isset($plan['is_active']) ? $plan['is_active'] : null,
            'is_purchasable' => isset($plan['is_purchasable']) ? $plan['is_purchasable'] : null,
        );
    }
}
phase2l_print('expected_currency', $expected_currency);
phase2l_print('system_currency', $system_currency);
phase2l_print('system_currency_is_expected', $system_currency_is_expected);
phase2l_print('non_egp_subscription_plan_count', count($non_egp_plans));
phase2l_print('non_egp_subscription_plans', $non_egp_plans);

phase2l_heading('Capability checks');
$manage_subscriptions_exists = $diagnosticDb->table_exists('youngo_capabilities')
    && (int) $diagnosticDb->query(
        "SELECT COUNT(*) AS row_count FROM `youngo_capabilities` WHERE `capability_key` = 'manage_subscriptions'"
    )->row('row_count') > 0;
$root_has_manage_subscriptions = youngo_user_has_capability(1, 'manage_subscriptions');
$user7 = $diagnosticDb->table_exists('users') ? $diagnosticDb->query('SELECT `id`, `role_id`, `is_instructor`, `status` FROM `users` WHERE `id` = 7 LIMIT 1')->row_array() : array();
$user7_has_manage_subscriptions = !empty($user7) ? youngo_user_has_capability(7, 'manage_subscriptions') : false;
phase2l_print('manage_subscriptions', array(
    'capability_exists' => $manage_subscriptions_exists,
    'root_admin_has_capability' => $root_has_manage_subscriptions,
    'user_7_exists' => !empty($user7),
    'user_7_has_capability' => $user7_has_manage_subscriptions,
));
phase2l_assert($manage_subscriptions_exists, 'manage_subscriptions capability is missing.');
phase2l_assert($root_has_manage_subscriptions, 'Root Admin does not have manage_subscriptions through the YounGo helper.');
if (!empty($user7)) {
    phase2l_assert(!$user7_has_manage_subscriptions, 'User 7 has manage_subscriptions without explicit assignment.');
}

phase2l_heading('YounGo commercial subscription readiness');
$readiness_block_reasons = array();
if (!$phase2l_schema_applied) {
    $readiness_block_reasons[] = 'schema missing';
}
if (!$system_currency_is_expected) {
    $readiness_block_reasons[] = 'system currency not EGP';
}
if (!empty($non_egp_plans)) {
    $readiness_block_reasons[] = 'non-EGP plans exist';
}
if (!$manage_subscriptions_exists || !$root_has_manage_subscriptions || (!empty($user7) && $user7_has_manage_subscriptions)) {
    $readiness_block_reasons[] = 'capability issue';
}
$commercial_ready = empty($readiness_block_reasons);
phase2l_print('readiness', array(
    'commercial_subscription_ready' => $commercial_ready,
    'commercial_subscription_blocked' => !$commercial_ready,
    'block_reasons' => $readiness_block_reasons,
    'safe_for_real_commercial_activation' => $commercial_ready,
));

phase2l_heading('Read-only safety');
phase2l_print('result', 'Diagnostic used SELECT-only queries, blocked write SQL verbs, created no sessions/cookies, and did not modify data.');

if (!empty($failures)) {
    phase2l_heading('Failures');
    phase2l_print('failures', $failures);
    exit(1);
}

phase2l_heading('Result');
phase2l_print('status', 'PASS');
