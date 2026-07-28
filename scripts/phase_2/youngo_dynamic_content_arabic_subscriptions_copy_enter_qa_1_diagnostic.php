<?php
/**
 * DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.COPY.ENTER.QA.1 diagnostic.
 *
 * Read-only verification for real bilingual subscription plan display copy.
 * This script does not write DB rows, change plan fields, create checkout
 * records, call Paymob, or expose credentials.
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
$translationTable = 'youngo_subscription_plan_translations';
$planTable = 'youngo_subscription_plans';

$expectedPlans = array(
    'monthly' => array(
        'canonical_name' => 'Monthly',
        'duration_days' => 30,
        'price' => '100.00',
        'currency' => 'EGP',
        'english_name' => 'Monthly',
        'arabic_name' => 'الاشتراك الشهري',
        'arabic_short_description' => 'مناسب للتجربة والمتابعة الشهرية.',
        'arabic_description' => 'وصول شهري مرن لمحتوى YounGo المتاح حسب خطة المدرسة.',
        'arabic_badge_label' => 'شهري',
    ),
    '3-months' => array(
        'canonical_name' => '3 Months',
        'duration_days' => 90,
        'price' => '250.00',
        'currency' => 'EGP',
        'english_name' => '3 Months',
        'arabic_name' => 'اشتراك 3 أشهر',
        'arabic_short_description' => 'خطة متوسطة مناسبة لفصل دراسي قصير.',
        'arabic_description' => 'وصول لمدة ثلاثة أشهر لمحتوى YounGo المتاح حسب خطة المدرسة.',
        'arabic_badge_label' => '3 أشهر',
    ),
    'yearly' => array(
        'canonical_name' => 'Yearly',
        'duration_days' => 365,
        'price' => '900.00',
        'currency' => 'EGP',
        'english_name' => 'Yearly',
        'arabic_name' => 'الاشتراك السنوي',
        'arabic_short_description' => 'أفضل اختيار للتعلم المستمر طوال العام.',
        'arabic_description' => 'وصول سنوي لمحتوى YounGo المتاح مع تجربة تعليمية مستقرة وطويلة المدى.',
        'arabic_badge_label' => 'سنوي',
    ),
);

function ysp_copy_diag_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function ysp_copy_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function ysp_copy_diag_source($root, $relative)
{
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    return is_file($path) ? file_get_contents($path) : '';
}

function ysp_copy_diag_changed_files($root)
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

function ysp_copy_diag_connect($db, $active_group)
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

function ysp_copy_diag_fetch_all($mysqli, $sql)
{
    $result = $mysqli->query($sql);
    if (!$result) {
        throw new RuntimeException('DB read failed without exposing credentials.');
    }

    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();

    return $rows;
}

function ysp_copy_diag_scalar($mysqli, $sql)
{
    $result = $mysqli->query($sql);
    if (!$result) {
        throw new RuntimeException('DB read failed without exposing credentials.');
    }

    $row = $result->fetch_row();
    $result->free();
    return isset($row[0]) ? $row[0] : null;
}

function ysp_copy_diag_by_slug($plans)
{
    $mapped = array();
    foreach ($plans as $plan) {
        if (isset($plan['slug'])) {
            $mapped[$plan['slug']] = $plan;
        }
    }

    return $mapped;
}

class YoungoSubscriptionCopyDiagResult
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

    public function row_array()
    {
        return isset($this->rows[0]) ? $this->rows[0] : array();
    }

    public function row($field = null)
    {
        $row = isset($this->rows[0]) ? $this->rows[0] : array();
        if ($field !== null) {
            return isset($row[$field]) ? $row[$field] : null;
        }

        return (object) $row;
    }

    public function num_rows()
    {
        return count($this->rows);
    }
}

class YoungoSubscriptionCopyDiagDb
{
    public $db_debug = false;
    protected $mysqli;
    protected $select = '*';
    protected $from = '';
    protected $where = array();
    protected $order_by = array();
    protected $limit = null;

    public function __construct($config)
    {
        $this->mysqli = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
        if ($this->mysqli->connect_errno) {
            throw new RuntimeException('DB connection failed without exposing credentials.');
        }

        $this->mysqli->set_charset('utf8mb4');
    }

    public function close()
    {
        $this->mysqli->close();
    }

    public function query($sql, $binds = array())
    {
        if (!empty($binds)) {
            foreach ($binds as $bind) {
                $sql = preg_replace('/\?/', $this->escape_value($bind), $sql, 1);
            }
        }

        if (preg_match('/^\s*(INSERT|UPDATE|DELETE|ALTER|DROP|CREATE|TRUNCATE|REPLACE|GRANT|REVOKE|LOAD|CALL|OPTIMIZE|ANALYZE)\b/i', $sql)) {
            throw new RuntimeException('Blocked non-read SQL in diagnostic.');
        }

        $result = $this->mysqli->query($sql);
        if ($result === false) {
            throw new RuntimeException('DB query failed without exposing credentials.');
        }

        if ($result === true) {
            return new YoungoSubscriptionCopyDiagResult(array());
        }

        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();

        return new YoungoSubscriptionCopyDiagResult($rows);
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

    public function select($select, $escape = null)
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

    public function where_in($field, $values)
    {
        $values = is_array($values) ? $values : array($values);
        if (empty($values)) {
            $this->where[] = '0 = 1';
            return $this;
        }

        $escaped = array();
        foreach ($values as $value) {
            $escaped[] = $this->escape_value($value);
        }

        $this->where[] = '`' . str_replace('`', '``', $field) . '` IN (' . implode(', ', $escaped) . ')';
        return $this;
    }

    public function order_by($field, $direction = 'ASC')
    {
        $direction = strtoupper((string) $direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->order_by[] = '`' . str_replace('`', '``', $field) . '` ' . $direction;
        return $this;
    }

    public function limit($limit)
    {
        $this->limit = max(0, (int) $limit);
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
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . (int) $this->limit;
        }

        $this->select = '*';
        $this->from = '';
        $this->where = array();
        $this->order_by = array();
        $this->limit = null;

        return $this->query($sql);
    }

    protected function escape_value($value)
    {
        if ($value === null) {
            return 'NULL';
        }

        return "'" . $this->mysqli->real_escape_string((string) $value) . "'";
    }
}

class YoungoSubscriptionCopyDiagLoader
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

class YoungoSubscriptionCopyDiagCi
{
    public $db;
    public $load;

    public function __construct($db)
    {
        $this->db = $db;
        $this->load = new YoungoSubscriptionCopyDiagLoader();
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

$diagnosticDb = new YoungoSubscriptionCopyDiagDb($db[$active_group]);
$diagnosticCi = new YoungoSubscriptionCopyDiagCi($diagnosticDb);

function &get_instance()
{
    global $diagnosticCi;
    return $diagnosticCi;
}

try {
    $mysqli = ysp_copy_diag_connect($db, $active_group);

    $requiredFiles = array(
        'application/models/Youngo_subscription_model.php',
        'application/controllers/Youngo_subscription_plans.php',
        'application/views/backend/admin/youngo_subscription_plan_form.php',
        'application/views/frontend/youngo/subscriptions.php',
    );
    $fileStatus = array();
    foreach ($requiredFiles as $file) {
        $fileStatus[$file] = is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file));
    }
    ysp_copy_diag_print('Required file status', $fileStatus);
    foreach ($fileStatus as $file => $exists) {
        ysp_copy_diag_assert($failures, $exists, $file . ' is missing.');
    }

    $publicRows = ysp_copy_diag_fetch_all(
        $mysqli,
        "SELECT id, name, slug, duration_days, price, currency, is_active, is_purchasable, is_featured, sort_order, archived_at FROM {$planTable} WHERE is_active = 1 AND is_purchasable = 1 AND currency = 'EGP' AND price > 0 AND duration_days > 0 AND archived_at IS NULL ORDER BY sort_order ASC, id ASC"
    );
    $publicBySlug = ysp_copy_diag_by_slug($publicRows);
    $planChecks = array(
        'public_plan_count' => count($publicRows),
        'expected_slugs_present' => array_values(array_intersect(array_keys($expectedPlans), array_keys($publicBySlug))),
        'unexpected_public_slugs' => array_values(array_diff(array_keys($publicBySlug), array_keys($expectedPlans))),
    );

    foreach ($expectedPlans as $slug => $expected) {
        $plan = isset($publicBySlug[$slug]) ? $publicBySlug[$slug] : array();
        $planChecks[$slug . '_canonical_name_ok'] = isset($plan['name']) && $plan['name'] === $expected['canonical_name'];
        $planChecks[$slug . '_duration_ok'] = isset($plan['duration_days']) && (int) $plan['duration_days'] === $expected['duration_days'];
        $planChecks[$slug . '_price_ok'] = isset($plan['price']) && number_format((float) $plan['price'], 2, '.', '') === $expected['price'];
        $planChecks[$slug . '_currency_ok'] = isset($plan['currency']) && $plan['currency'] === $expected['currency'];
    }
    ysp_copy_diag_print('Public plan/shared-field checks', $planChecks);
    ysp_copy_diag_assert($failures, count($publicRows) === 3, 'Expected exactly 3 public subscription plans.');
    ysp_copy_diag_assert($failures, empty($planChecks['unexpected_public_slugs']), 'Unexpected public subscription plan slug detected.');
    foreach ($planChecks as $label => $ok) {
        if (is_bool($ok)) {
            ysp_copy_diag_assert($failures, $ok, 'Public plan/shared-field check failed: ' . $label);
        }
    }

    $translationRows = ysp_copy_diag_fetch_all(
        $mysqli,
        "SELECT p.slug, t.language_code, t.name, t.short_description, t.description, t.badge_label FROM {$translationTable} t INNER JOIN {$planTable} p ON p.id = t.plan_id ORDER BY p.sort_order ASC, p.id ASC, t.language_code ASC"
    );
    $translations = array();
    foreach ($translationRows as $row) {
        $translations[$row['slug']][$row['language_code']] = $row;
    }

    $translationChecks = array(
        'translation_row_count' => count($translationRows),
        'arabic_translated_rows' => (int) ysp_copy_diag_scalar($mysqli, "SELECT COUNT(*) FROM {$translationTable} WHERE language_code = 'arabic_translated'"),
    );
    foreach ($expectedPlans as $slug => $expected) {
        $english = isset($translations[$slug]['english']) ? $translations[$slug]['english'] : array();
        $arabic = isset($translations[$slug]['arabic']) ? $translations[$slug]['arabic'] : array();
        $translationChecks[$slug . '_english_row_exists'] = !empty($english);
        $translationChecks[$slug . '_arabic_row_exists'] = !empty($arabic);
        $translationChecks[$slug . '_english_name_ok'] = isset($english['name']) && $english['name'] === $expected['english_name'];
        $translationChecks[$slug . '_arabic_name_ok'] = isset($arabic['name']) && $arabic['name'] === $expected['arabic_name'];
        $translationChecks[$slug . '_arabic_short_description_ok'] = isset($arabic['short_description']) && $arabic['short_description'] === $expected['arabic_short_description'];
        $translationChecks[$slug . '_arabic_description_ok'] = isset($arabic['description']) && $arabic['description'] === $expected['arabic_description'];
        $translationChecks[$slug . '_arabic_badge_label_ok'] = isset($arabic['badge_label']) && $arabic['badge_label'] === $expected['arabic_badge_label'];
        $translationChecks[$slug . '_arabic_name_contains_arabic_chars'] = isset($arabic['name']) && preg_match('/\p{Arabic}/u', $arabic['name']) === 1;
    }
    ysp_copy_diag_print('Translation row checks', $translationChecks);
    ysp_copy_diag_assert($failures, count($translationRows) === 6, 'Expected 6 translation rows for 3 plans.');
    ysp_copy_diag_assert($failures, $translationChecks['arabic_translated_rows'] === 0, 'arabic_translated translation rows must not exist.');
    foreach ($translationChecks as $label => $ok) {
        if (is_bool($ok)) {
            ysp_copy_diag_assert($failures, $ok, 'Translation row check failed: ' . $label);
        }
    }

    require_once $root . '/application/models/Youngo_subscription_model.php';
    $model = new Youngo_subscription_model();
    $arabicPlans = ysp_copy_diag_by_slug($model->get_public_subscription_plans('arabic'));
    $arabicCompatPlans = ysp_copy_diag_by_slug($model->get_public_subscription_plans('ar'));
    $englishPlans = ysp_copy_diag_by_slug($model->get_public_subscription_plans('english'));
    $forbiddenPlans = $model->get_public_subscription_plans('arabic_translated');

    $modelChecks = array(
        'arabic_plan_count' => count($arabicPlans),
        'arabic_compat_plan_count' => count($arabicCompatPlans),
        'english_plan_count' => count($englishPlans),
        'arabic_translated_returns_empty' => empty($forbiddenPlans),
    );
    foreach ($expectedPlans as $slug => $expected) {
        $modelChecks[$slug . '_arabic_model_name_ok'] = isset($arabicPlans[$slug]['name']) && $arabicPlans[$slug]['name'] === $expected['arabic_name'];
        $modelChecks[$slug . '_ar_compat_model_name_ok'] = isset($arabicCompatPlans[$slug]['name']) && $arabicCompatPlans[$slug]['name'] === $expected['arabic_name'];
        $modelChecks[$slug . '_english_model_name_ok'] = isset($englishPlans[$slug]['name']) && $englishPlans[$slug]['name'] === $expected['english_name'];
        $modelChecks[$slug . '_arabic_description_ok'] = isset($arabicPlans[$slug]['description']) && $arabicPlans[$slug]['description'] === $expected['arabic_description'];
        $modelChecks[$slug . '_slug_unchanged_in_arabic_model'] = isset($arabicPlans[$slug]['slug']) && $arabicPlans[$slug]['slug'] === $slug;
        $modelChecks[$slug . '_price_unchanged_in_arabic_model'] = isset($arabicPlans[$slug]['price']) && $arabicPlans[$slug]['price'] === $expected['price'];
        $modelChecks[$slug . '_duration_unchanged_in_arabic_model'] = isset($arabicPlans[$slug]['duration_days']) && (int) $arabicPlans[$slug]['duration_days'] === $expected['duration_days'];
    }
    ysp_copy_diag_print('Public model resolution checks', $modelChecks);
    foreach ($modelChecks as $label => $ok) {
        if (is_bool($ok)) {
            ysp_copy_diag_assert($failures, $ok, 'Public model resolution check failed: ' . $label);
        }
    }

    $publicViewSource = ysp_copy_diag_source($root, 'application/views/frontend/youngo/subscriptions.php');
    $changedFiles = ysp_copy_diag_changed_files($root);
    $protectedChanged = array();
    foreach ($changedFiles as $file) {
        if (preg_match('#(?:paymob|payment|checkout|cart|order|enrol|grant|youngo_security|payment_gateways)#i', $file)) {
            $protectedChanged[] = $file;
        }
    }
    $safetyChecks = array(
        'public_view_has_no_paymob' => stripos($publicViewSource, 'paymob') === false,
        'public_view_has_no_checkout' => stripos($publicViewSource, 'checkout') === false,
        'public_view_has_no_buy_now' => stripos($publicViewSource, 'buy now') === false,
        'protected_payment_or_access_files_changed' => $protectedChanged,
    );
    ysp_copy_diag_print('Payment/CTA safety checks', $safetyChecks);
    foreach ($safetyChecks as $label => $ok) {
        if ($label === 'protected_payment_or_access_files_changed') {
            ysp_copy_diag_assert($failures, empty($ok), 'Payment/checkout/access protected files changed in this phase.');
        } else {
            ysp_copy_diag_assert($failures, $ok, 'Payment/CTA safety check failed: ' . $label);
        }
    }

    $diagnosticDb->close();
    $mysqli->close();
} catch (Throwable $e) {
    $failures[] = $e->getMessage();
}

if (!empty($failures)) {
    ysp_copy_diag_print('Failures', $failures);
    exit(1);
}

ysp_copy_diag_print('Result', 'PASS: real English/Arabic subscription plan display copy is present, public model resolves it, and payment/CTA safety remains intact.');
exit(0);
