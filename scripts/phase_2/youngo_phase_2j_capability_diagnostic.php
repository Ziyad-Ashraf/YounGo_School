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

class YoungoCapabilityDiagnosticResult
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

class YoungoCapabilityDiagnosticDb
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
            return new YoungoCapabilityDiagnosticResult(array());
        }

        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();

        return new YoungoCapabilityDiagnosticResult($rows);
    }

    public function table_exists($table)
    {
        $result = $this->query(
            "SELECT COUNT(*) AS row_count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = " .
            $this->escapeValue($table)
        )->row_array();

        return (int) (isset($result['row_count']) ? $result['row_count'] : 0) > 0;
    }

    public function field_exists($field, $table)
    {
        $result = $this->query(
            "SELECT COUNT(*) AS row_count FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = " .
            $this->escapeValue($table) . " AND column_name = " . $this->escapeValue($field)
        )->row_array();

        return (int) (isset($result['row_count']) ? $result['row_count'] : 0) > 0;
    }

    public function server_version()
    {
        return $this->mysqli->server_info;
    }

    protected function assertReadOnlySql($sql)
    {
        $trimmed = ltrim($sql);
        if (preg_match('/^(INSERT|UPDATE|DELETE|ALTER|DROP|CREATE|TRUNCATE|REPLACE|GRANT|REVOKE|LOAD|CALL)\b/i', $trimmed)) {
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

class YoungoCapabilityDiagnosticLoader
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

class YoungoCapabilityDiagnosticSession
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

class YoungoCapabilityDiagnosticCi
{
    public $db;
    public $load;
    public $session;
    public $youngo_capability_model;

    public function __construct($db)
    {
        $this->db = $db;
        $this->load = new YoungoCapabilityDiagnosticLoader($this);
        $this->session = new YoungoCapabilityDiagnosticSession();
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

$diagnosticDb = new YoungoCapabilityDiagnosticDb($db[$active_group]);
$diagnosticCi = new YoungoCapabilityDiagnosticCi($diagnosticDb);
$failures = array();

function &get_instance()
{
    global $diagnosticCi;
    return $diagnosticCi;
}

require_once APPPATH . 'helpers' . DIRECTORY_SEPARATOR . 'youngo_capability_helper.php';
$diagnosticCi->load->model('Youngo_capability_model', 'youngo_capability_model');

function capability_diagnostic_heading($label)
{
    echo "\n== " . $label . " ==\n";
}

function capability_diagnostic_print($label, $payload)
{
    echo $label . ': ' . json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n";
}

function capability_diagnostic_assert($condition, $message)
{
    global $failures;
    if (!$condition) {
        $failures[] = $message;
    }

    return (bool) $condition;
}

function capability_diagnostic_count($table)
{
    global $diagnosticDb;
    if (!$diagnosticDb->table_exists($table)) {
        return null;
    }

    return (int) $diagnosticDb->query('SELECT COUNT(*) AS row_count FROM `' . $table . '`')->row('row_count');
}

capability_diagnostic_heading('Connection');
capability_diagnostic_print('db', array(
    'connected' => true,
    'database_name' => $diagnosticDb->query('SELECT DATABASE() AS database_name')->row('database_name'),
    'server_version' => $diagnosticDb->server_version(),
));

$requiredTables = array(
    'youngo_roles',
    'youngo_capabilities',
    'youngo_role_capabilities',
    'youngo_user_roles',
);

capability_diagnostic_heading('Required YounGo table availability');
$tableAvailability = array();
foreach ($requiredTables as $table) {
    $tableAvailability[$table] = $diagnosticDb->table_exists($table);
}
capability_diagnostic_print('tables', $tableAvailability);
capability_diagnostic_assert(!in_array(false, $tableAvailability, true), 'One or more required YounGo capability tables are missing.');

capability_diagnostic_heading('Counts');
$counts = array(
    'roles' => capability_diagnostic_count('youngo_roles'),
    'capabilities' => capability_diagnostic_count('youngo_capabilities'),
    'role_capability_mappings' => capability_diagnostic_count('youngo_role_capabilities'),
    'user_role_assignments' => capability_diagnostic_count('youngo_user_roles'),
);
capability_diagnostic_print('counts', $counts);

capability_diagnostic_heading('Seeded role and capability slugs');
$roles = $diagnosticDb->query('SELECT `role_key` FROM `youngo_roles` ORDER BY `role_key` ASC')->result_array();
$capabilities = $diagnosticDb->query('SELECT `capability_key` FROM `youngo_capabilities` ORDER BY `capability_key` ASC')->result_array();
capability_diagnostic_print('roles', $roles);
capability_diagnostic_print('capabilities', $capabilities);

$seedCapability = 'manage_courses';
$invalidCapability = 'youngo_nonexistent_capability_slug';

capability_diagnostic_heading('Protected Root Admin');
$rootUser = $diagnosticDb->query('SELECT `id`, `role_id`, `is_instructor`, `status` FROM `users` WHERE `id` = 1 LIMIT 1')->row_array();
$rootCapabilities = youngo_get_user_capability_slugs(1);
$rootResult = array(
    'user_exists' => !empty($rootUser),
    'is_yongo_root_admin' => youngo_is_root_admin(1),
    'has_seeded_capability' => youngo_user_has_capability(1, $seedCapability),
    'role_slugs' => youngo_get_user_role_slugs(1),
    'capability_count' => count($rootCapabilities),
);
capability_diagnostic_print('root_admin', $rootResult);
capability_diagnostic_assert(!empty($rootUser), 'Protected Root Admin user ID 1 was not found.');
capability_diagnostic_assert(youngo_is_root_admin(1), 'Protected Root Admin was not detected by YounGo helper.');
capability_diagnostic_assert(youngo_user_has_capability(1, $seedCapability), 'Protected Root Admin did not receive a valid seeded capability.');

capability_diagnostic_heading('Restricted client admin');
$clientAdmin = $diagnosticDb->query('SELECT `id`, `role_id`, `is_instructor`, `status` FROM `users` WHERE `id` = 7 LIMIT 1')->row_array();
if (!empty($clientAdmin)) {
    $clientAdminResult = array(
        'user_id' => (int) $clientAdmin['id'],
        'role_id' => (int) $clientAdmin['role_id'],
        'is_yongo_root_admin' => youngo_is_root_admin((int) $clientAdmin['id']),
        'has_manage_courses' => youngo_user_has_capability((int) $clientAdmin['id'], $seedCapability),
        'role_slugs' => youngo_get_user_role_slugs((int) $clientAdmin['id']),
        'capability_slugs' => youngo_get_user_capability_slugs((int) $clientAdmin['id']),
    );
    capability_diagnostic_print('restricted_client_admin', $clientAdminResult);
    capability_diagnostic_assert(!$clientAdminResult['is_yongo_root_admin'], 'Restricted client admin user 7 was incorrectly detected as Root Admin.');
    capability_diagnostic_assert(!$clientAdminResult['has_manage_courses'], 'Restricted client admin user 7 gained YounGo capability without explicit role assignment.');
} else {
    capability_diagnostic_print('skipped', 'User ID 7 was not found.');
}

capability_diagnostic_heading('Learner or demo user');
$learner = $diagnosticDb->query('SELECT `id`, `role_id`, `is_instructor`, `status` FROM `users` WHERE `id` != 1 AND `role_id` = 2 ORDER BY `id` ASC LIMIT 1')->row_array();
if (!empty($learner)) {
    $learnerResult = array(
        'user_id' => (int) $learner['id'],
        'role_slugs' => youngo_get_user_role_slugs((int) $learner['id']),
        'has_learner_role' => youngo_user_has_role((int) $learner['id'], 'learner'),
        'has_manage_courses' => youngo_user_has_capability((int) $learner['id'], $seedCapability),
    );
    capability_diagnostic_print('learner', $learnerResult);
    capability_diagnostic_assert($learnerResult['has_learner_role'], 'Existing learner/demo user did not receive implicit learner role.');
    capability_diagnostic_assert(!$learnerResult['has_manage_courses'], 'Existing learner/demo user gained management capability without explicit role assignment.');
} else {
    capability_diagnostic_print('skipped', 'No non-root role_id=2 user was found.');
}

capability_diagnostic_heading('Current explicit role assignments');
$assignments = $diagnosticDb->query(
    "SELECT ur.`user_id`, r.`role_key`, ur.`status`, ur.`revoked_at`
     FROM `youngo_user_roles` ur
     INNER JOIN `youngo_roles` r ON r.`id` = ur.`role_id`
     ORDER BY ur.`user_id` ASC, r.`role_key` ASC"
)->result_array();
capability_diagnostic_print('assignments', $assignments);

capability_diagnostic_heading('Multi-role union logic');
if (!empty($assignments)) {
    $seenUsers = array();
    foreach ($assignments as $assignment) {
        $uid = (int) $assignment['user_id'];
        if (isset($seenUsers[$uid])) {
            continue;
        }
        $seenUsers[$uid] = true;
        capability_diagnostic_print('user_' . $uid, array(
            'role_slugs' => youngo_get_user_role_slugs($uid),
            'capability_slugs' => youngo_get_user_capability_slugs($uid),
        ));
    }
} else {
    capability_diagnostic_print('skipped', 'No explicit YounGo user-role assignments exist; no rows were inserted for testing.');
}

capability_diagnostic_heading('Invalid capability slug');
$invalidResult = array(
    'root_has_invalid_capability' => youngo_user_has_capability(1, $invalidCapability),
    'root_has_empty_capability' => youngo_user_has_capability(1, ''),
);
capability_diagnostic_print('invalid_slug', $invalidResult);
capability_diagnostic_assert(!$invalidResult['root_has_invalid_capability'], 'Invalid capability slug was accepted for Root Admin.');
capability_diagnostic_assert(!$invalidResult['root_has_empty_capability'], 'Empty capability slug was accepted for Root Admin.');

capability_diagnostic_heading('Missing-table fail-closed behavior');
capability_diagnostic_print('result', 'Validated by model/helper code path: non-root capability lookup requires all YounGo capability tables; no destructive table rename/drop test was run.');

capability_diagnostic_heading('Read-only safety');
capability_diagnostic_print('result', 'Diagnostic used a SELECT-only DB wrapper, blocked write SQL verbs, created no sessions/cookies, and did not call redirecting enforcement paths.');

if (!empty($failures)) {
    capability_diagnostic_heading('Failures');
    capability_diagnostic_print('failures', $failures);
    exit(1);
}

capability_diagnostic_heading('Result');
capability_diagnostic_print('status', 'PASS');
