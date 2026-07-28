<?php
/**
 * SUBSCRIPTIONS.PAGE.DYNAMIC.MODEL.1 diagnostic.
 *
 * Read-only checks for public subscription plan model support. This script
 * does not execute DB writes, create checkout records, call payment providers,
 * or create a public subscriptions page.
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

function youngo_subscriptions_dynamic_diag_print($title, $payload)
{
    echo "\n== {$title} ==\n";
    echo is_string($payload) ? $payload . "\n" : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

function youngo_subscriptions_dynamic_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function youngo_subscriptions_dynamic_diag_changed_files($root)
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

function youngo_subscriptions_dynamic_diag_model_added_lines($root)
{
    $output = array();
    $exit_code = 1;
    exec('git -C ' . escapeshellarg($root) . ' diff -- application/models/Youngo_subscription_model.php', $output, $exit_code);
    if ($exit_code !== 0) {
        return array();
    }

    $added = array();
    foreach ($output as $line) {
        if ($line !== '' && $line[0] === '+' && strpos($line, '+++') !== 0) {
            $added[] = substr($line, 1);
        }
    }

    return $added;
}

class YoungoSubscriptionsDynamicDiagnosticResult
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

    public function row($field = null)
    {
        $row = isset($this->rows[0]) ? $this->rows[0] : array();
        if ($field !== null) {
            return isset($row[$field]) ? $row[$field] : null;
        }

        return (object) $row;
    }
}

class YoungoSubscriptionsDynamicDiagnosticDb
{
    protected $mysqli;
    protected $select = '*';
    protected $from = '';
    protected $where = array();
    protected $order_by = array();

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
                $sql = preg_replace('/\?/', $this->escape_value($bind), $sql, 1);
            }
        }

        $this->assert_read_only_sql($sql);
        $result = $this->mysqli->query($sql);
        if ($result === false) {
            throw new RuntimeException('Read query failed.');
        }

        if ($result === true) {
            return new YoungoSubscriptionsDynamicDiagnosticResult(array());
        }

        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();

        return new YoungoSubscriptionsDynamicDiagnosticResult($rows);
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

    public function list_fields($table)
    {
        $rows = $this->query(
            'SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? ORDER BY ordinal_position',
            array($table)
        )->result_array();

        $fields = array();
        foreach ($rows as $row) {
            $fields[] = $row['column_name'];
        }

        return $fields;
    }

    public function select($select)
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

        $operator = '=';
        $field = $key;
        if (preg_match('/^(.+?)\s+(>=|<=|<>|!=|>|<)$/', $key, $matches)) {
            $field = trim($matches[1]);
            $operator = $matches[2];
        }

        $this->where[] = '`' . str_replace('`', '``', $field) . '` ' . $operator . ' ' . $this->escape_value($value);
        return $this;
    }

    public function order_by($field, $direction = 'ASC')
    {
        $direction = strtoupper((string) $direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->order_by[] = '`' . str_replace('`', '``', $field) . '` ' . $direction;
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
        if (!empty($this->order_by)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->order_by);
        }

        $this->select = '*';
        $this->from = '';
        $this->where = array();
        $this->order_by = array();

        return $this->query($sql);
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

class YoungoSubscriptionsDynamicDiagnosticLoader
{
    public function helper($helper)
    {
        $path = APPPATH . 'helpers' . DIRECTORY_SEPARATOR . $helper . '_helper.php';
        if (is_file($path)) {
            require_once $path;
        }

        return $this;
    }
}

class YoungoSubscriptionsDynamicDiagnosticCi
{
    public $db;
    public $load;

    public function __construct($db)
    {
        $this->db = $db;
        $this->load = new YoungoSubscriptionsDynamicDiagnosticLoader();
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

$diagnosticDb = new YoungoSubscriptionsDynamicDiagnosticDb($db[$active_group]);
$diagnosticCi = new YoungoSubscriptionsDynamicDiagnosticCi($diagnosticDb);

function &get_instance()
{
    global $diagnosticCi;
    return $diagnosticCi;
}

$model_file = $root . '/application/models/Youngo_subscription_model.php';
$language_helper_file = $root . '/application/helpers/youngo_frontend_language_helper.php';
$routes_file = $root . '/application/config/routes.php';
$frontend_view_dir = $root . '/application/views/frontend/youngo';

$required_files = array(
    'application/models/Youngo_subscription_model.php' => is_file($model_file),
    'application/helpers/youngo_frontend_language_helper.php' => is_file($language_helper_file),
    'application/config/routes.php' => is_file($routes_file),
    'application/views/frontend/youngo/' => is_dir($frontend_view_dir),
);
youngo_subscriptions_dynamic_diag_print('Required files', $required_files);
foreach ($required_files as $label => $exists) {
    youngo_subscriptions_dynamic_diag_assert($failures, $exists, $label . ' is missing.');
}

$model_source = is_file($model_file) ? file_get_contents($model_file) : '';
$source_checks = array(
    'public_method_exists' => strpos($model_source, 'function get_public_subscription_plans') !== false,
    'price_formatter_exists' => strpos($model_source, 'function format_public_plan_price') !== false,
    'duration_formatter_exists' => strpos($model_source, 'function format_public_plan_duration') !== false,
    'normalizes_public_rows' => strpos($model_source, 'function normalize_public_plan_row') !== false,
    'normalizes_language' => strpos($model_source, 'function normalize_public_language') !== false,
    'filters_active' => strpos($model_source, "where('is_active', 1)") !== false,
    'filters_purchasable' => strpos($model_source, "where('is_purchasable', 1)") !== false,
    'filters_egp' => strpos($model_source, "where('currency', \$this->youngo_commercial_currency)") !== false,
    'filters_positive_price' => strpos($model_source, "where('price >', 0)") !== false,
    'filters_positive_duration' => strpos($model_source, "where('duration_days >', 0)") !== false,
    'excludes_archived_when_supported' => strpos($model_source, "where('archived_at IS NULL', null, false)") !== false,
    'sorts_featured_first' => strpos($model_source, "order_by('is_featured', 'DESC')") !== false,
    'sorts_sort_order_then_id' => strpos($model_source, "order_by('sort_order', 'ASC')") !== false
        && strpos($model_source, "order_by('id', 'ASC')") !== false,
);
youngo_subscriptions_dynamic_diag_print('Model source checks', $source_checks);
foreach ($source_checks as $label => $ok) {
    youngo_subscriptions_dynamic_diag_assert($failures, $ok, 'Model source check failed: ' . $label);
}

if (is_file($language_helper_file)) {
    require_once $language_helper_file;
}

$language_checks = array();
if (function_exists('youngo_frontend_normalize_language_code')) {
    $language_examples = array(
        'ar_to_arabic' => array('input' => 'ar', 'expected' => 'arabic'),
        'en_to_english' => array('input' => 'en', 'expected' => 'english'),
        'arabic_translated_to_arabic' => array('input' => 'arabic_translated', 'expected' => 'arabic'),
        'arabic_to_arabic' => array('input' => 'arabic', 'expected' => 'arabic'),
        'english_to_english' => array('input' => 'english', 'expected' => 'english'),
    );

    foreach ($language_examples as $label => $example) {
        $actual = youngo_frontend_normalize_language_code($example['input']);
        $language_checks[$label] = array('expected' => $example['expected'], 'actual' => $actual, 'ok' => $actual === $example['expected']);
        youngo_subscriptions_dynamic_diag_assert($failures, $actual === $example['expected'], 'Language normalization failed: ' . $label);
    }
} else {
    youngo_subscriptions_dynamic_diag_assert($failures, false, 'Frontend language normalizer is unavailable.');
}
youngo_subscriptions_dynamic_diag_print('Language helper compatibility', $language_checks);

$schema_checks = array();
$table = 'youngo_subscription_plans';
$schema_checks['table_exists'] = $diagnosticDb->table_exists($table);
$fields = $schema_checks['table_exists'] ? $diagnosticDb->list_fields($table) : array();
$required_fields = array('id', 'name', 'slug', 'duration_days', 'price', 'currency', 'is_active', 'is_purchasable');
foreach ($required_fields as $field) {
    $schema_checks['field_' . $field] = in_array($field, $fields, true);
}
$schema_checks['field_is_featured'] = in_array('is_featured', $fields, true);
$schema_checks['field_sort_order'] = in_array('sort_order', $fields, true);
$schema_checks['field_archived_at'] = in_array('archived_at', $fields, true);
$schema_checks['has_bilingual_fields'] = count(array_intersect($fields, array(
    'english_name',
    'arabic_name',
    'name_en',
    'name_ar',
    'english_description',
    'arabic_description',
    'description_en',
    'description_ar',
))) > 0;
youngo_subscriptions_dynamic_diag_print('Plan schema checks', $schema_checks);
foreach ($schema_checks as $label => $ok) {
    if (strpos($label, 'field_') === 0 || $label === 'table_exists') {
        youngo_subscriptions_dynamic_diag_assert($failures, $ok, 'Plan schema check failed: ' . $label);
    }
}

require_once $model_file;
$model = new Youngo_subscription_model();
$arabic_plans = $model->get_public_subscription_plans('arabic');
$english_plans = $model->get_public_subscription_plans('english');

$returned_row_checks = array();
foreach (array('arabic' => $arabic_plans, 'english' => $english_plans) as $language => $plans) {
    foreach ($plans as $index => $plan) {
        $valid = isset($plan['id'], $plan['name'], $plan['slug'], $plan['duration_days'], $plan['price'], $plan['currency'], $plan['featured'], $plan['price_display'], $plan['duration_label'])
            && (int) $plan['id'] > 0
            && trim((string) $plan['name']) !== ''
            && trim((string) $plan['slug']) !== ''
            && (int) $plan['duration_days'] > 0
            && (float) $plan['price'] > 0
            && $plan['currency'] === 'EGP';
        $returned_row_checks[$language . '_' . $index] = array('plan_id' => isset($plan['id']) ? $plan['id'] : null, 'ok' => $valid);
        youngo_subscriptions_dynamic_diag_assert($failures, $valid, 'Invalid normalized public plan row for ' . $language . ' index ' . $index);
    }
}

$returned_ids = array();
foreach ($english_plans as $plan) {
    $returned_ids[] = (int) $plan['id'];
}

$db_plan_rows = array();
if (!empty($returned_ids)) {
    $db_plan_rows = $diagnosticDb->query(
        'SELECT id, is_active, is_purchasable, currency, price, duration_days, archived_at'
        . ' FROM youngo_subscription_plans WHERE id IN (' . implode(',', array_map('intval', $returned_ids)) . ')'
    )->result_array();
}

$db_filter_checks = array(
    'arabic_count' => count($arabic_plans),
    'english_count' => count($english_plans),
    'counts_match' => count($arabic_plans) === count($english_plans),
    'empty_state_supported' => count($english_plans) === 0,
    'returned_row_checks' => $returned_row_checks,
);
foreach ($db_plan_rows as $row) {
    $row_ok = (int) $row['is_active'] === 1
        && (int) $row['is_purchasable'] === 1
        && $row['currency'] === 'EGP'
        && (float) $row['price'] > 0
        && (int) $row['duration_days'] > 0
        && (!array_key_exists('archived_at', $row) || $row['archived_at'] === null || $row['archived_at'] === '');
    $db_filter_checks['db_row_' . $row['id']] = $row_ok;
    youngo_subscriptions_dynamic_diag_assert($failures, $row_ok, 'Returned plan failed DB eligibility check: ' . $row['id']);
}
youngo_subscriptions_dynamic_diag_print('Public method return checks', $db_filter_checks);
youngo_subscriptions_dynamic_diag_assert($failures, $db_filter_checks['counts_match'], 'Arabic and English public plan counts differ.');

$format_checks = array(
    'egp_price' => array('actual' => $model->format_public_plan_price(100, 'EGP'), 'expected' => 'EGP 100.00'),
    'duration_one' => array('actual' => $model->format_public_plan_duration(1), 'expected' => '1 day'),
    'duration_many' => array('actual' => $model->format_public_plan_duration(30), 'expected' => '30 days'),
    'duration_zero' => array('actual' => $model->format_public_plan_duration(0), 'expected' => ''),
);
foreach ($format_checks as $label => &$check) {
    $check['ok'] = $check['actual'] === $check['expected'];
    youngo_subscriptions_dynamic_diag_assert($failures, $check['ok'], 'Formatter check failed: ' . $label);
}
unset($check);
youngo_subscriptions_dynamic_diag_print('Formatter checks', $format_checks);

$routes_source = is_file($routes_file) ? file_get_contents($routes_file) : '';
$route_map = array();
if (preg_match_all('/\\$route\\[[\'"]([^\'"]+)[\'"]\\]\\s*=\\s*[\'"]([^\'"]+)[\'"]\\s*;/', $routes_source, $route_matches, PREG_SET_ORDER)) {
    foreach ($route_matches as $route_match) {
        $route_map[$route_match[1]] = $route_match[2];
    }
}
$subscriptions_routes_absent = !isset($route_map['subscriptions']) && !isset($route_map['en/subscriptions']) && !isset($route_map['ar/subscriptions']);
$subscriptions_routes_safe = isset($route_map['subscriptions'], $route_map['en/subscriptions'], $route_map['ar/subscriptions'])
    && $route_map['subscriptions'] === 'home/subscriptions'
    && $route_map['en/subscriptions'] === 'home/subscriptions'
    && $route_map['ar/subscriptions'] === 'home/subscriptions';
$route_safety = array(
    'subscriptions_public_route_absent_or_safe_display_only' => $subscriptions_routes_absent || $subscriptions_routes_safe,
    'payment_routes_not_localized_for_subscription' => preg_match('#(?:en|ar)/payment|(?:en|ar)/paymob#i', $routes_source) !== 1,
);
youngo_subscriptions_dynamic_diag_print('Route/page safety checks', $route_safety);
foreach ($route_safety as $label => $ok) {
    youngo_subscriptions_dynamic_diag_assert($failures, $ok, 'Route/page safety check failed: ' . $label);
}

$model_added_lines = youngo_subscriptions_dynamic_diag_model_added_lines($root);
$forbidden_added_patterns = array(
    '/youngo\\/checkout/i',
    '/payment\\/paymob/i',
    '/home\\/course_payment/i',
    '/payment_gateways?/i',
    '/checkout_order/i',
    '/\\$this->db->(?:insert|update|delete)\\s*\\(/i',
    '/->insert\\s*\\(/i',
    '/->update\\s*\\(/i',
    '/->delete\\s*\\(/i',
    '/INSERT\\s+INTO/i',
    '/UPDATE\\s+[a-z_]/i',
    '/DELETE\\s+FROM/i',
);
$forbidden_added_lines = array();
foreach ($model_added_lines as $line) {
    foreach ($forbidden_added_patterns as $pattern) {
        if (preg_match($pattern, $line)) {
            $forbidden_added_lines[] = $line;
            break;
        }
    }
}
$write_safety = array(
    'model_added_lines_checked' => count($model_added_lines),
    'forbidden_added_lines' => $forbidden_added_lines,
);
youngo_subscriptions_dynamic_diag_print('Payment/write safety checks', $write_safety);
youngo_subscriptions_dynamic_diag_assert($failures, empty($forbidden_added_lines), 'Model added payment/checkout/write behavior.');

$changed_files = youngo_subscriptions_dynamic_diag_changed_files($root);
$allowed_changed_files = array(
    'application/config/routes.php',
    'application/controllers/Home.php',
    'application/helpers/youngo_frontend_language_helper.php',
    'application/models/Youngo_subscription_model.php',
    'application/views/frontend/youngo/header.php',
    'application/views/frontend/youngo/footer.php',
    'application/views/frontend/youngo/subscriptions.php',
    'assets/frontend/youngo/css/youngo.css',
    'scripts/phase_2/youngo_subscriptions_page_dynamic_model_1_diagnostic.php',
    'scripts/phase_2/youngo_subscriptions_page_dynamic_ui_1_diagnostic.php',
    'scripts/phase_2/youngo_localization_ar_default_links_1_diagnostic.php',
    'docs/qa/youngo_subscriptions_page_dynamic_model_1_report.md',
    'docs/qa/youngo_subscriptions_page_dynamic_ui_1_report.md',
);
$unexpected_changed_files = array_values(array_diff($changed_files, $allowed_changed_files));
youngo_subscriptions_dynamic_diag_print('Changed file scope', array(
    'changed_files' => $changed_files,
    'unexpected_changed_files' => $unexpected_changed_files,
));
youngo_subscriptions_dynamic_diag_assert($failures, empty($unexpected_changed_files), 'Unexpected files changed.');

if (!empty($failures)) {
    youngo_subscriptions_dynamic_diag_print('FAILURES', $failures);
    exit(1);
}

echo "\nSUBSCRIPTIONS.PAGE.DYNAMIC.MODEL.1 diagnostic passed.\n";
exit(0);
