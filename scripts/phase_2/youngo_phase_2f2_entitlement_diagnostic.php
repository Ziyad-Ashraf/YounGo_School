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

class YoungoDiagnosticResult
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
        return $this->rows[0] ?? array();
    }

    public function result_array()
    {
        return $this->rows;
    }

    public function row($field = null)
    {
        $row = $this->row_array();
        if ($field !== null) {
            return $row[$field] ?? null;
        }

        return (object) $row;
    }
}

class YoungoDiagnosticDb
{
    protected $mysqli;
    protected $select = '*';
    protected $where = array();
    protected $orderBy = null;

    public function __construct($config)
    {
        $this->mysqli = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
        if ($this->mysqli->connect_errno) {
            throw new RuntimeException('DB connection failed: ' . $this->mysqli->connect_error);
        }

        $this->mysqli->set_charset(!empty($config['char_set']) ? $config['char_set'] : 'utf8');
    }

    public function query($sql)
    {
        $this->assertReadOnlySql($sql);
        $result = $this->mysqli->query($sql);
        if ($result === false) {
            throw new RuntimeException('Read query failed: ' . $this->mysqli->error);
        }

        if ($result === true) {
            return new YoungoDiagnosticResult(array());
        }

        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();

        return new YoungoDiagnosticResult($rows);
    }

    public function select($fields)
    {
        $parts = array_map('trim', explode(',', $fields));
        $safe = array();
        foreach ($parts as $part) {
            $safe[] = $this->quoteIdentifier($part);
        }
        $this->select = implode(', ', $safe);
        return $this;
    }

    public function where($field, $value)
    {
        $this->where[] = $this->quoteIdentifier($field) . ' = ' . $this->escapeValue($value);
        return $this;
    }

    public function order_by($field, $direction = 'asc')
    {
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
        $this->orderBy = $this->quoteIdentifier($field) . ' ' . $direction;
        return $this;
    }

    public function get($table, $limit = null)
    {
        $sql = 'SELECT ' . $this->select . ' FROM ' . $this->quoteIdentifier($table);
        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }
        if ($this->orderBy !== null) {
            $sql .= ' ORDER BY ' . $this->orderBy;
        }
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $this->resetBuilder();
        return $this->query($sql);
    }

    public function get_where($table, $where)
    {
        foreach ($where as $field => $value) {
            $this->where($field, $value);
        }

        return $this->get($table);
    }

    public function table_exists($table)
    {
        $result = $this->query(
            "SELECT COUNT(*) AS row_count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = " .
            $this->escapeValue($table)
        )->row_array();

        return (int) ($result['row_count'] ?? 0) > 0;
    }

    public function field_exists($field, $table)
    {
        $result = $this->query(
            "SELECT COUNT(*) AS row_count FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = " .
            $this->escapeValue($table) . " AND column_name = " . $this->escapeValue($field)
        )->row_array();

        return (int) ($result['row_count'] ?? 0) > 0;
    }

    protected function resetBuilder()
    {
        $this->select = '*';
        $this->where = array();
        $this->orderBy = null;
    }

    protected function assertReadOnlySql($sql)
    {
        $trimmed = ltrim($sql);
        if (preg_match('/^(INSERT|UPDATE|DELETE|ALTER|DROP|CREATE|TRUNCATE|REPLACE|GRANT|REVOKE|LOAD|CALL)\b/i', $trimmed)) {
            throw new RuntimeException('Blocked non-read SQL in diagnostic.');
        }
    }

    protected function quoteIdentifier($identifier)
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new InvalidArgumentException('Unsafe identifier: ' . $identifier);
        }

        return '`' . $identifier . '`';
    }

    protected function escapeValue($value)
    {
        if ($value === null) {
            return 'NULL';
        }

        return "'" . $this->mysqli->real_escape_string((string) $value) . "'";
    }
}

class YoungoDiagnosticLoader
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
        $this->ci->{$alias ?: $model} = $object;
        return $this;
    }
}

class YoungoDiagnosticCi
{
    public $db;
    public $load;
    public $youngo_entitlement_model;

    public function __construct($db)
    {
        $this->db = $db;
        $this->load = new YoungoDiagnosticLoader($this);
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

$diagnosticDb = new YoungoDiagnosticDb($db[$active_group]);
$diagnosticCi = new YoungoDiagnosticCi($diagnosticDb);

function &get_instance()
{
    global $diagnosticCi;
    return $diagnosticCi;
}

require_once APPPATH . 'helpers' . DIRECTORY_SEPARATOR . 'youngo_entitlement_helper.php';
$diagnosticCi->load->model('Youngo_entitlement_model', 'youngo_entitlement_model');

$requiredStateKeys = array(
    'has_access',
    'lesson_access_allowed',
    'course_visible_in_my_courses',
    'access_source',
    'status',
    'is_lifetime',
    'start_date',
    'expiry_date',
    'warning_80_percent',
    'course_access_mode',
    'subscription_eligible',
    'lock_reason',
    'message_key',
    'source_record_id',
    'legacy_enrol_id',
);

function diagnostic_heading($label)
{
    echo "\n== " . $label . " ==\n";
}

function diagnostic_print($label, $payload)
{
    echo $label . ': ' . json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n";
}

function diagnostic_state_summary($state)
{
    return array(
        'has_access' => $state['has_access'] ?? null,
        'lesson_access_allowed' => $state['lesson_access_allowed'] ?? null,
        'course_visible_in_my_courses' => $state['course_visible_in_my_courses'] ?? null,
        'access_source' => $state['access_source'] ?? null,
        'status' => $state['status'] ?? null,
        'is_lifetime' => $state['is_lifetime'] ?? null,
        'start_date' => $state['start_date'] ?? null,
        'expiry_date' => $state['expiry_date'] ?? null,
        'warning_80_percent' => $state['warning_80_percent'] ?? null,
        'course_access_mode' => $state['course_access_mode'] ?? null,
        'subscription_eligible' => $state['subscription_eligible'] ?? null,
        'lock_reason' => $state['lock_reason'] ?? null,
        'message_key' => $state['message_key'] ?? null,
        'source_record_id' => $state['source_record_id'] ?? null,
        'legacy_enrol_id' => $state['legacy_enrol_id'] ?? null,
    );
}

function diagnostic_missing_state_keys($state, $requiredStateKeys)
{
    return array_values(array_diff($requiredStateKeys, array_keys($state)));
}

$statesForStructureCheck = array();

diagnostic_heading('Connection');
diagnostic_print('db', $diagnosticDb->query('SELECT DATABASE() AS database_name, VERSION() AS server_version')->row_array());

diagnostic_heading('Phase 2 table availability');
$phase2Available = $diagnosticCi->youngo_entitlement_model->phase_2_tables_available();
diagnostic_print('phase_2_tables_available', array('actual' => $phase2Available, 'expected' => true));

$courseOne = $diagnosticDb->query('SELECT id, title, youngo_access_mode, youngo_subscription_excluded FROM course WHERE id = 1 LIMIT 1')->row_array();
$student = $diagnosticDb->query("SELECT id, email, role_id, is_instructor, status FROM users WHERE role_id = 2 ORDER BY id ASC LIMIT 1")->row_array();

diagnostic_heading('A. Existing course with no user access');
if (!empty($courseOne) && !empty($student)) {
    $state = youngo_get_course_access_state((int) $student['id'], 1, array('allow_admin_bypass' => false));
    $statesForStructureCheck['student_no_access'] = $state;
    diagnostic_print('input', array('course_id' => 1, 'user_id' => (int) $student['id'], 'user_email' => $student['email']));
    diagnostic_print('state', diagnostic_state_summary($state));
    diagnostic_print('expectation', array('expected_without_existing_records' => 'has_access=false, access_source=none'));
} else {
    diagnostic_print('skipped', 'Course ID 1 or a normal role_id=2 user was not found.');
}

diagnostic_heading('B. Root admin bypass');
if ($diagnosticDb->query('SELECT COUNT(*) AS row_count FROM users WHERE id = 1')->row('row_count') > 0 && !empty($courseOne)) {
    $state = youngo_get_course_access_state(1, 1, array('allow_admin_bypass' => true));
    $statesForStructureCheck['root_admin'] = $state;
    diagnostic_print('state', diagnostic_state_summary($state));
    diagnostic_print('expectation', array('has_access' => true, 'access_source' => 'admin'));
} else {
    diagnostic_print('skipped', 'Root admin user ID 1 or course ID 1 was not found.');
}

diagnostic_heading('C. Client admin bypass');
$clientAdmin = $diagnosticDb->query("SELECT id, email, role_id, is_instructor, status FROM users WHERE email = 'client@gmail.com' LIMIT 1")->row_array();
if (!empty($clientAdmin) && !empty($courseOne)) {
    $state = youngo_get_course_access_state((int) $clientAdmin['id'], 1, array('allow_admin_bypass' => true));
    $statesForStructureCheck['client_admin'] = $state;
    diagnostic_print('input', $clientAdmin);
    diagnostic_print('state', diagnostic_state_summary($state));
    diagnostic_print('expectation', array('has_access' => true, 'access_source' => 'admin'));
} else {
    diagnostic_print('skipped', 'client@gmail.com or course ID 1 was not found.');
}

diagnostic_heading('D. Assigned instructor compatibility');
$instructorCase = $diagnosticDb->query(
    "SELECT c.id AS course_id, c.title, c.creator AS user_id, u.email, u.is_instructor " .
    "FROM course c INNER JOIN users u ON u.id = c.creator " .
    "WHERE c.creator IS NOT NULL AND c.creator > 0 AND u.is_instructor = 1 " .
    "ORDER BY c.id ASC LIMIT 1"
)->row_array();

if (empty($instructorCase)) {
    $courses = $diagnosticDb->query("SELECT id, title, user_id FROM course WHERE user_id IS NOT NULL AND user_id != '' ORDER BY id ASC")->result_array();
    foreach ($courses as $course) {
        $ids = array_filter(array_map('trim', explode(',', (string) $course['user_id'])));
        foreach ($ids as $possibleId) {
            $user = $diagnosticDb->query('SELECT id, email, is_instructor FROM users WHERE id = ' . (int) $possibleId . ' AND is_instructor = 1 LIMIT 1')->row_array();
            if (!empty($user)) {
                $instructorCase = array(
                    'course_id' => $course['id'],
                    'title' => $course['title'],
                    'user_id' => $user['id'],
                    'email' => $user['email'],
                    'is_instructor' => $user['is_instructor'],
                );
                break 2;
            }
        }
    }
}

if (!empty($instructorCase)) {
    $state = youngo_get_course_access_state((int) $instructorCase['user_id'], (int) $instructorCase['course_id'], array('allow_admin_bypass' => false));
    $statesForStructureCheck['assigned_instructor'] = $state;
    diagnostic_print('input', $instructorCase);
    diagnostic_print('state', diagnostic_state_summary($state));
    diagnostic_print('expectation', array('has_access' => true, 'access_source' => 'instructor'));
} else {
    diagnostic_print('skipped', 'No course.creator or course.user_id assignment to an existing is_instructor=1 user was found.');
}

diagnostic_heading('E. Legacy enrol compatibility');
$legacyEnrol = $diagnosticDb->query('SELECT id, user_id, course_id, expiry_date FROM enrol ORDER BY id ASC LIMIT 1')->row_array();
if (!empty($legacyEnrol)) {
    $state = youngo_get_course_access_state((int) $legacyEnrol['user_id'], (int) $legacyEnrol['course_id'], array('allow_admin_bypass' => false));
    $statesForStructureCheck['legacy_enrol'] = $state;
    diagnostic_print('input', $legacyEnrol);
    diagnostic_print('state', diagnostic_state_summary($state));
} else {
    diagnostic_print('skipped', 'The local enrol table has no rows.');
}

diagnostic_heading('G. Course subscription eligibility');
if (!empty($courseOne)) {
    diagnostic_print('course_1', array(
        'course' => $courseOne,
        'subscription_eligible' => youngo_course_is_subscription_eligible(1),
    ));
}

$purchaseOnlyCourse = $diagnosticDb->query(
    "SELECT id, title, youngo_access_mode, youngo_subscription_excluded FROM course " .
    "WHERE youngo_access_mode = 'purchase_only' OR youngo_subscription_excluded = 1 ORDER BY id ASC LIMIT 1"
)->row_array();
if (!empty($purchaseOnlyCourse)) {
    $state = !empty($student)
        ? youngo_get_course_access_state((int) $student['id'], (int) $purchaseOnlyCourse['id'], array('allow_admin_bypass' => false))
        : array();
    if (!empty($state)) {
        $statesForStructureCheck['purchase_only'] = $state;
    }
    diagnostic_print('purchase_only_or_excluded_course', array(
        'course' => $purchaseOnlyCourse,
        'subscription_eligible' => youngo_course_is_subscription_eligible((int) $purchaseOnlyCourse['id']),
        'state' => !empty($state) ? diagnostic_state_summary($state) : 'No normal student user available for state check.',
    ));
} else {
    diagnostic_print('skipped_purchase_only', 'No course is currently configured as purchase_only or subscription_excluded.');
}

diagnostic_heading('H. Access state structure');
$structureResults = array();
foreach ($statesForStructureCheck as $caseName => $state) {
    $structureResults[$caseName] = array(
        'missing_keys' => diagnostic_missing_state_keys($state, $requiredStateKeys),
        'valid' => count(diagnostic_missing_state_keys($state, $requiredStateKeys)) === 0,
    );
}
diagnostic_print('structure_results', $structureResults);

diagnostic_heading('Read-only safety');
diagnostic_print('result', 'Diagnostic completed with SELECT-only DB wrapper and no application behavior integration.');
