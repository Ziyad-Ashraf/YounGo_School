<?php
/**
 * DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.MODEL.1 diagnostic.
 *
 * Verifies localized public subscription model output using transaction-scoped
 * temporary translation rows. The transaction is rolled back, so no plan data
 * or translation seed data remains. This script does not call payment
 * providers, create checkout/order/access rows, or expose secrets.
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

function ysp_model_diag_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function ysp_model_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function ysp_model_diag_source($root, $relative)
{
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    return is_file($path) ? file_get_contents($path) : '';
}

function ysp_model_diag_changed_files($root)
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

class YoungoSubscriptionModelDiagResult
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

class YoungoSubscriptionModelDiagDb
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

    public function begin()
    {
        $this->mysqli->begin_transaction();
    }

    public function rollback()
    {
        $this->mysqli->rollback();
    }

    public function close()
    {
        $this->mysqli->close();
    }

    public function query($sql, $binds = array(), $allow_write = false)
    {
        if (!empty($binds)) {
            foreach ($binds as $bind) {
                $sql = preg_replace('/\?/', $this->escape_value($bind), $sql, 1);
            }
        }

        if (!$allow_write) {
            $this->assert_read_only_sql($sql);
        }

        $result = $this->mysqli->query($sql);
        if ($result === false) {
            throw new RuntimeException('DB query failed without exposing credentials.');
        }

        if ($result === true) {
            return new YoungoSubscriptionModelDiagResult(array());
        }

        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();

        return new YoungoSubscriptionModelDiagResult($rows);
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

    public function delete_plan_translations($table, $plan_id)
    {
        return $this->query(
            'DELETE FROM `' . str_replace('`', '``', $table) . '` WHERE plan_id = ?',
            array($plan_id),
            true
        );
    }

    public function insert_translation($table, $data)
    {
        $columns = array();
        $values = array();
        foreach ($data as $column => $value) {
            $columns[] = '`' . str_replace('`', '``', $column) . '`';
            $values[] = $this->escape_value($value);
        }

        return $this->query(
            'INSERT INTO `' . str_replace('`', '``', $table) . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')',
            array(),
            true
        );
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

class YoungoSubscriptionModelDiagLoader
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

class YoungoSubscriptionModelDiagCi
{
    public $db;
    public $load;

    public function __construct($db)
    {
        $this->db = $db;
        $this->load = new YoungoSubscriptionModelDiagLoader();
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

function ysp_model_diag_find_plan($plans, $plan_id)
{
    foreach ($plans as $plan) {
        if (isset($plan['id']) && (int) $plan['id'] === (int) $plan_id) {
            return $plan;
        }
    }

    return array();
}

if (!isset($db[$active_group])) {
    fwrite(STDERR, "Active database group was not found.\n");
    exit(1);
}

$diagnosticDb = new YoungoSubscriptionModelDiagDb($db[$active_group]);
$diagnosticCi = new YoungoSubscriptionModelDiagCi($diagnosticDb);

function &get_instance()
{
    global $diagnosticCi;
    return $diagnosticCi;
}

try {
    $modelFile = $root . '/application/models/Youngo_subscription_model.php';
    $homeFile = $root . '/application/controllers/Home.php';
    $viewFile = $root . '/application/views/frontend/youngo/subscriptions.php';

    $requiredFiles = array(
        'application/models/Youngo_subscription_model.php' => is_file($modelFile),
        'application/controllers/Home.php' => is_file($homeFile),
        'application/views/frontend/youngo/subscriptions.php' => is_file($viewFile),
    );
    ysp_model_diag_print('Required file status', $requiredFiles);
    foreach ($requiredFiles as $label => $exists) {
        ysp_model_diag_assert($failures, $exists, $label . ' is missing.');
    }

    $fields = $diagnosticDb->list_fields($translationTable);
    $schemaChecks = array(
        'translation_table_exists' => $diagnosticDb->table_exists($translationTable),
        'fields' => $fields,
        'has_plan_id' => in_array('plan_id', $fields, true),
        'has_language_code' => in_array('language_code', $fields, true),
        'has_name' => in_array('name', $fields, true),
        'has_short_description' => in_array('short_description', $fields, true),
        'has_description' => in_array('description', $fields, true),
        'has_badge_label' => in_array('badge_label', $fields, true),
    );
    ysp_model_diag_print('Schema checks', $schemaChecks);
    foreach ($schemaChecks as $label => $ok) {
        if ($label !== 'fields') {
            ysp_model_diag_assert($failures, $ok, 'Schema check failed: ' . $label);
        }
    }

    $modelSource = ysp_model_diag_source($root, 'application/models/Youngo_subscription_model.php');
    $sourceChecks = array(
        'bulk_translation_loader_exists' => strpos($modelSource, 'function load_public_plan_translations') !== false,
        'translation_overlay_exists' => strpos($modelSource, 'function apply_public_plan_translation') !== false,
        'plan_id_extractor_exists' => strpos($modelSource, 'function extract_plan_ids') !== false,
        'public_method_loads_bulk_translations' => strpos($modelSource, '$translations = $this->load_public_plan_translations($this->extract_plan_ids($rows), $language);') !== false,
        'normalizer_rejects_arabic_translated' => strpos($modelSource, "strtolower(trim(\$language)) === 'arabic_translated'") !== false,
        'normalized_output_has_description' => strpos($modelSource, "'description' => \$description") !== false,
        'normalized_output_has_badge_label' => strpos($modelSource, "'badge_label' => \$this->localized_public_value(\$row, 'badge_label', \$language)") !== false,
    );
    ysp_model_diag_print('Model source checks', $sourceChecks);
    foreach ($sourceChecks as $label => $ok) {
        ysp_model_diag_assert($failures, $ok, 'Model source check failed: ' . $label);
    }

    require_once $modelFile;
    $model = new Youngo_subscription_model();

    $languageChecks = array(
        'english' => $model->normalize_subscription_translation_language('english') === 'english',
        'en' => $model->normalize_subscription_translation_language('en') === 'english',
        'arabic' => $model->normalize_subscription_translation_language('arabic') === 'arabic',
        'ar' => $model->normalize_subscription_translation_language('ar') === 'arabic',
        'arabic_translated_rejected' => $model->normalize_subscription_translation_language('arabic_translated') === null,
    );
    ysp_model_diag_print('Language checks', $languageChecks);
    foreach ($languageChecks as $label => $ok) {
        ysp_model_diag_assert($failures, $ok, 'Language check failed: ' . $label);
    }

    $basePlans = $model->get_public_subscription_plans('english');
    ysp_model_diag_assert($failures, !empty($basePlans), 'No public subscription plan was available for localized model diagnostic.');
    $basePlan = !empty($basePlans) ? $basePlans[0] : array();
    $planId = isset($basePlan['id']) ? (int) $basePlan['id'] : 0;

    $beforeTranslationCount = (int) $diagnosticDb->query(
        'SELECT COUNT(*) AS row_count FROM youngo_subscription_plan_translations'
    )->row('row_count');

    $arabicName = 'خطة تشخيص مؤقتة';
    $arabicDescription = 'وصف عربي مؤقت للتشخيص';
    $arabicBadge = 'شارة عربية مؤقتة';
    $englishName = 'Temporary Diagnostic Plan';
    $englishShort = 'Temporary English diagnostic summary';
    $englishDescription = 'Temporary English diagnostic description';
    $englishBadge = 'Temporary badge';

    $diagnosticDb->begin();
    try {
        $diagnosticDb->delete_plan_translations($translationTable, $planId);
        $now = time();
        $diagnosticDb->insert_translation($translationTable, array(
            'plan_id' => $planId,
            'language_code' => 'arabic',
            'name' => $arabicName,
            'short_description' => null,
            'description' => $arabicDescription,
            'badge_label' => $arabicBadge,
            'created_at' => $now,
            'updated_at' => $now,
        ));
        $diagnosticDb->insert_translation($translationTable, array(
            'plan_id' => $planId,
            'language_code' => 'english',
            'name' => $englishName,
            'short_description' => $englishShort,
            'description' => $englishDescription,
            'badge_label' => $englishBadge,
            'created_at' => $now,
            'updated_at' => $now,
        ));

        $arabicPlan = ysp_model_diag_find_plan($model->get_public_subscription_plans('arabic'), $planId);
        $arabicCompatPlan = ysp_model_diag_find_plan($model->get_public_subscription_plans('ar'), $planId);
        $englishPlan = ysp_model_diag_find_plan($model->get_public_subscription_plans('english'), $planId);
        $forbiddenPlans = $model->get_public_subscription_plans('arabic_translated');

        $overlayChecks = array(
            'arabic_name_overlay' => isset($arabicPlan['name']) && $arabicPlan['name'] === $arabicName,
            'arabic_description_overlay' => isset($arabicPlan['description']) && $arabicPlan['description'] === $arabicDescription,
            'arabic_short_description_falls_to_description' => isset($arabicPlan['short_description']) && $arabicPlan['short_description'] === $arabicDescription,
            'arabic_badge_overlay' => isset($arabicPlan['badge_label']) && $arabicPlan['badge_label'] === $arabicBadge,
            'ar_compat_uses_arabic' => isset($arabicCompatPlan['name']) && $arabicCompatPlan['name'] === $arabicName,
            'english_name_overlay' => isset($englishPlan['name']) && $englishPlan['name'] === $englishName,
            'english_short_description_overlay' => isset($englishPlan['short_description']) && $englishPlan['short_description'] === $englishShort,
            'english_description_overlay' => isset($englishPlan['description']) && $englishPlan['description'] === $englishDescription,
            'english_badge_overlay' => isset($englishPlan['badge_label']) && $englishPlan['badge_label'] === $englishBadge,
            'arabic_translated_returns_no_public_rows' => empty($forbiddenPlans),
        );

        foreach (array('arabic' => $arabicPlan, 'english' => $englishPlan) as $language => $plan) {
            $overlayChecks[$language . '_slug_stays_base'] = isset($plan['slug'], $basePlan['slug']) && $plan['slug'] === $basePlan['slug'];
            $overlayChecks[$language . '_price_stays_base'] = isset($plan['price'], $basePlan['price']) && $plan['price'] === $basePlan['price'];
            $overlayChecks[$language . '_currency_stays_base'] = isset($plan['currency'], $basePlan['currency']) && $plan['currency'] === $basePlan['currency'];
            $overlayChecks[$language . '_duration_stays_base'] = isset($plan['duration_days'], $basePlan['duration_days']) && (int) $plan['duration_days'] === (int) $basePlan['duration_days'];
            $overlayChecks[$language . '_featured_stays_base'] = isset($plan['featured'], $basePlan['featured']) && (int) $plan['featured'] === (int) $basePlan['featured'];
        }

        $diagnosticDb->query(
            'DELETE FROM youngo_subscription_plan_translations WHERE plan_id = ? AND language_code = ?',
            array($planId, 'english'),
            true
        );
        $fallbackPlan = ysp_model_diag_find_plan($model->get_public_subscription_plans('english'), $planId);
        $overlayChecks['english_missing_translation_falls_back_to_base_name'] = isset($fallbackPlan['name'], $basePlan['name']) && $fallbackPlan['name'] === $basePlan['name'];
        $overlayChecks['english_missing_translation_keeps_base_slug'] = isset($fallbackPlan['slug'], $basePlan['slug']) && $fallbackPlan['slug'] === $basePlan['slug'];

        ysp_model_diag_print('Overlay and fallback checks', $overlayChecks);
        foreach ($overlayChecks as $label => $ok) {
            ysp_model_diag_assert($failures, $ok, 'Overlay/fallback check failed: ' . $label);
        }
    } finally {
        $diagnosticDb->rollback();
    }

    $afterTranslationCount = (int) $diagnosticDb->query(
        'SELECT COUNT(*) AS row_count FROM youngo_subscription_plan_translations'
    )->row('row_count');
    $cleanupChecks = array(
        'before_translation_count' => $beforeTranslationCount,
        'after_translation_count' => $afterTranslationCount,
        'counts_match_after_rollback' => $beforeTranslationCount === $afterTranslationCount,
    );
    ysp_model_diag_print('Cleanup checks', $cleanupChecks);
    ysp_model_diag_assert($failures, $cleanupChecks['counts_match_after_rollback'], 'Translation row count changed after diagnostic rollback.');

    $homeSource = ysp_model_diag_source($root, 'application/controllers/Home.php');
    $viewSource = ysp_model_diag_source($root, 'application/views/frontend/youngo/subscriptions.php');
    $publicChecks = array(
        'home_still_uses_public_model_method' => strpos($homeSource, 'get_public_subscription_plans($youngo_frontend_language)') !== false,
        'view_still_dynamic_loop' => strpos($viewSource, 'foreach ($youngo_subscription_plans as $plan)') !== false,
        'view_contact_cta_only' => strpos($viewSource, 'youngo_frontend_contact_url') !== false,
        'view_has_no_paymob' => stripos($viewSource, 'paymob') === false,
        'view_has_no_checkout' => stripos($viewSource, 'checkout') === false,
    );
    ysp_model_diag_print('Public/payment safety checks', $publicChecks);
    foreach ($publicChecks as $label => $ok) {
        ysp_model_diag_assert($failures, $ok, 'Public/payment safety check failed: ' . $label);
    }

    $changedFiles = ysp_model_diag_changed_files($root);
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
    ysp_model_diag_print('Changed-file safety checks', $safetyChecks);
    ysp_model_diag_assert($failures, empty($protectedChanged), 'Payment/checkout/access protected files changed in this phase.');

    $diagnosticDb->close();
} catch (Throwable $e) {
    $failures[] = $e->getMessage();
}

if (!empty($failures)) {
    ysp_model_diag_print('Failures', $failures);
    exit(1);
}

ysp_model_diag_print('Result', 'PASS: localized subscription model output overlays temporary translations and rolls back cleanly.');
exit(0);
