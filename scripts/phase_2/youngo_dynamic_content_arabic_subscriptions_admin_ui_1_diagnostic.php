<?php
/**
 * DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.ADMIN.UI.1 diagnostic.
 *
 * Verifies admin bilingual subscription translation wiring and model save
 * behavior with transaction-scoped temporary rows. The transaction is rolled
 * back, so no subscription plan, translation, checkout, payment, or access
 * data remains changed.
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

function ysp_admin_ui_diag_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function ysp_admin_ui_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function ysp_admin_ui_diag_source($root, $relative)
{
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    return is_file($path) ? file_get_contents($path) : '';
}

function ysp_admin_ui_diag_changed_files($root)
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

function ysp_admin_ui_diag_find_plan($plans, $plan_id)
{
    foreach ($plans as $plan) {
        if (isset($plan['id']) && (int) $plan['id'] === (int) $plan_id) {
            return $plan;
        }
    }

    return array();
}

class YoungoSubscriptionAdminUiDiagResult
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

class YoungoSubscriptionAdminUiDiagDb
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
            return new YoungoSubscriptionAdminUiDiagResult(array());
        }

        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();

        return new YoungoSubscriptionAdminUiDiagResult($rows);
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

        $this->reset_builder();
        return $this->query($sql);
    }

    public function insert($table, $data)
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

    public function update($table, $data)
    {
        $sets = array();
        foreach ($data as $column => $value) {
            $sets[] = '`' . str_replace('`', '``', $column) . '` = ' . $this->escape_value($value);
        }

        $sql = 'UPDATE `' . str_replace('`', '``', $table) . '` SET ' . implode(', ', $sets);
        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }

        $this->reset_builder();
        return $this->query($sql, array(), true);
    }

    public function delete($table)
    {
        $sql = 'DELETE FROM `' . str_replace('`', '``', $table) . '`';
        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }

        $this->reset_builder();
        return $this->query($sql, array(), true);
    }

    protected function reset_builder()
    {
        $this->select = '*';
        $this->from = '';
        $this->where = array();
        $this->order_by = array();
        $this->limit = null;
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

class YoungoSubscriptionAdminUiDiagLoader
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

class YoungoSubscriptionAdminUiDiagCi
{
    public $db;
    public $load;

    public function __construct($db)
    {
        $this->db = $db;
        $this->load = new YoungoSubscriptionAdminUiDiagLoader();
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

$diagnosticDb = new YoungoSubscriptionAdminUiDiagDb($db[$active_group]);
$diagnosticCi = new YoungoSubscriptionAdminUiDiagCi($diagnosticDb);

function &get_instance()
{
    global $diagnosticCi;
    return $diagnosticCi;
}

try {
    $modelFile = $root . '/application/models/Youngo_subscription_model.php';
    $controllerFile = $root . '/application/controllers/Youngo_subscription_plans.php';
    $formFile = $root . '/application/views/backend/admin/youngo_subscription_plan_form.php';
    $publicViewFile = $root . '/application/views/frontend/youngo/subscriptions.php';

    $requiredFiles = array(
        'application/models/Youngo_subscription_model.php' => is_file($modelFile),
        'application/controllers/Youngo_subscription_plans.php' => is_file($controllerFile),
        'application/views/backend/admin/youngo_subscription_plan_form.php' => is_file($formFile),
        'application/views/frontend/youngo/subscriptions.php' => is_file($publicViewFile),
    );
    ysp_admin_ui_diag_print('Required file status', $requiredFiles);
    foreach ($requiredFiles as $file => $exists) {
        ysp_admin_ui_diag_assert($failures, $exists, $file . ' is missing.');
    }

    $fields = $diagnosticDb->list_fields($translationTable);
    $schemaChecks = array(
        'translation_table_exists' => $diagnosticDb->table_exists($translationTable),
        'has_plan_id' => in_array('plan_id', $fields, true),
        'has_language_code' => in_array('language_code', $fields, true),
        'has_name' => in_array('name', $fields, true),
        'has_short_description' => in_array('short_description', $fields, true),
        'has_description' => in_array('description', $fields, true),
        'has_badge_label' => in_array('badge_label', $fields, true),
    );
    ysp_admin_ui_diag_print('Schema checks', $schemaChecks);
    foreach ($schemaChecks as $label => $ok) {
        ysp_admin_ui_diag_assert($failures, $ok, 'Schema check failed: ' . $label);
    }

    $modelSource = ysp_admin_ui_diag_source($root, 'application/models/Youngo_subscription_model.php');
    $controllerSource = ysp_admin_ui_diag_source($root, 'application/controllers/Youngo_subscription_plans.php');
    $formSource = ysp_admin_ui_diag_source($root, 'application/views/backend/admin/youngo_subscription_plan_form.php');
    $publicViewSource = ysp_admin_ui_diag_source($root, 'application/views/frontend/youngo/subscriptions.php');

    $sourceChecks = array(
        'form_has_english_translation_name' => strpos($formSource, 'translations[english][name]') !== false,
        'form_has_english_translation_short_description' => strpos($formSource, 'translations[english][short_description]') !== false,
        'form_has_english_translation_description' => strpos($formSource, 'translations[english][description]') !== false,
        'form_has_english_translation_badge_label' => strpos($formSource, 'translations[english][badge_label]') !== false,
        'form_has_arabic_translation_name' => strpos($formSource, 'translations[arabic][name]') !== false,
        'form_has_arabic_translation_short_description' => strpos($formSource, 'translations[arabic][short_description]') !== false,
        'form_has_arabic_translation_description' => strpos($formSource, 'translations[arabic][description]') !== false,
        'form_has_arabic_translation_badge_label' => strpos($formSource, 'translations[arabic][badge_label]') !== false,
        'form_marks_arabic_fields_rtl' => strpos($formSource, 'dir="rtl"') !== false,
        'controller_loads_plan_translations' => strpos($controllerSource, 'get_plan_translations($plan_id)') !== false,
        'controller_create_defaults_translations' => strpos($controllerSource, "\$page_data['plan_translations'] = array();") !== false,
        'model_translation_input_save_exists' => strpos($modelSource, 'function save_plan_translations_from_input') !== false,
        'model_create_calls_translation_save' => substr_count($modelSource, 'save_plan_translations_from_input($plan_id, $input, $actor_user_id)') >= 2,
        'model_has_blank_translation_delete' => strpos($modelSource, 'function delete_plan_translation') !== false,
        'model_rejects_arabic_translated' => strpos($modelSource, "in_array(\$language, array('english', 'arabic'), true)") !== false,
        'public_view_has_no_paymob' => stripos($publicViewSource, 'paymob') === false,
        'public_view_has_no_checkout' => stripos($publicViewSource, 'checkout') === false,
    );
    ysp_admin_ui_diag_print('Source wiring checks', $sourceChecks);
    foreach ($sourceChecks as $label => $ok) {
        ysp_admin_ui_diag_assert($failures, $ok, 'Source wiring check failed: ' . $label);
    }

    require_once $modelFile;
    $model = new Youngo_subscription_model();

    $languageChecks = array(
        'english_accepted' => $model->normalize_subscription_translation_language('english') === 'english',
        'arabic_accepted' => $model->normalize_subscription_translation_language('arabic') === 'arabic',
        'en_maps_to_english' => $model->normalize_subscription_translation_language('en') === 'english',
        'ar_maps_to_arabic' => $model->normalize_subscription_translation_language('ar') === 'arabic',
        'arabic_translated_rejected' => $model->normalize_subscription_translation_language('arabic_translated') === null,
    );
    ysp_admin_ui_diag_print('Language checks', $languageChecks);
    foreach ($languageChecks as $label => $ok) {
        ysp_admin_ui_diag_assert($failures, $ok, 'Language check failed: ' . $label);
    }

    $basePlans = $model->get_public_subscription_plans('english');
    ysp_admin_ui_diag_assert($failures, !empty($basePlans), 'No public subscription plan was available for admin UI diagnostic.');
    $basePlan = !empty($basePlans) ? $basePlans[0] : array();
    $planId = isset($basePlan['id']) ? (int) $basePlan['id'] : 0;

    $beforeTranslationCount = (int) $diagnosticDb->query(
        'SELECT COUNT(*) AS row_count FROM ' . $translationTable
    )->row('row_count');
    $beforeArabicTranslatedRows = (int) $diagnosticDb->query(
        'SELECT COUNT(*) AS row_count FROM ' . $translationTable . ' WHERE language_code = ?',
        array('arabic_translated')
    )->row('row_count');
    $beforeSharedPlan = $diagnosticDb->query(
        'SELECT id, name, slug, duration_days, price, currency, is_active, is_purchasable, is_featured, sort_order FROM ' . $planTable . ' WHERE id = ? LIMIT 1',
        array($planId)
    )->row_array();

    $diagnosticDb->begin();
    try {
        $diagnosticDb->query(
            'DELETE FROM ' . $translationTable . ' WHERE plan_id = ?',
            array($planId),
            true
        );

        $result = $model->save_plan_translations_from_input($planId, array(
            'translations' => array(
                'english' => array(
                    'name' => '__YOUNGO_ADMIN_UI_DIAG_EN__',
                    'short_description' => '__YOUNGO_ADMIN_UI_DIAG_EN_SHORT__',
                    'description' => '__YOUNGO_ADMIN_UI_DIAG_EN_DESCRIPTION__',
                    'badge_label' => '__YOUNGO_ADMIN_UI_DIAG_EN_BADGE__',
                ),
                'arabic' => array(
                    'name' => '__YOUNGO_ADMIN_UI_DIAG_AR__',
                    'short_description' => '__YOUNGO_ADMIN_UI_DIAG_AR_SHORT__',
                    'description' => '__YOUNGO_ADMIN_UI_DIAG_AR_DESCRIPTION__',
                    'badge_label' => '__YOUNGO_ADMIN_UI_DIAG_AR_BADGE__',
                ),
                'arabic_translated' => array(
                    'name' => '__YOUNGO_ADMIN_UI_DIAG_FORBIDDEN__',
                ),
            ),
        ), 1);

        $savedRows = $diagnosticDb->query(
            'SELECT language_code FROM ' . $translationTable . ' WHERE plan_id = ? ORDER BY language_code ASC',
            array($planId)
        )->result_array();
        $savedLanguages = array();
        foreach ($savedRows as $row) {
            $savedLanguages[] = $row['language_code'];
        }

        $arabicPlan = ysp_admin_ui_diag_find_plan($model->get_public_subscription_plans('arabic'), $planId);
        $englishPlan = ysp_admin_ui_diag_find_plan($model->get_public_subscription_plans('english'), $planId);
        $afterSharedPlan = $diagnosticDb->query(
            'SELECT id, name, slug, duration_days, price, currency, is_active, is_purchasable, is_featured, sort_order FROM ' . $planTable . ' WHERE id = ? LIMIT 1',
            array($planId)
        )->row_array();

        $blankDeleteResult = $model->save_plan_translations_from_input($planId, array(
            'translations' => array(
                'english' => array('name' => ''),
                'arabic' => array('name' => ''),
            ),
        ), 1);
        $afterBlankDeleteRows = (int) $diagnosticDb->query(
            'SELECT COUNT(*) AS row_count FROM ' . $translationTable . ' WHERE plan_id = ?',
            array($planId)
        )->row('row_count');

        $modelBehaviorChecks = array(
            'save_result_success' => !empty($result['success']),
            'saved_languages_are_english_arabic_only' => $savedLanguages === array('arabic', 'english') || $savedLanguages === array('english', 'arabic'),
            'arabic_overlay_from_saved_translation' => isset($arabicPlan['name']) && $arabicPlan['name'] === '__YOUNGO_ADMIN_UI_DIAG_AR__',
            'english_overlay_from_saved_translation' => isset($englishPlan['name']) && $englishPlan['name'] === '__YOUNGO_ADMIN_UI_DIAG_EN__',
            'shared_slug_unchanged' => isset($beforeSharedPlan['slug'], $afterSharedPlan['slug']) && $beforeSharedPlan['slug'] === $afterSharedPlan['slug'],
            'shared_price_unchanged' => isset($beforeSharedPlan['price'], $afterSharedPlan['price']) && (string) $beforeSharedPlan['price'] === (string) $afterSharedPlan['price'],
            'shared_duration_unchanged' => isset($beforeSharedPlan['duration_days'], $afterSharedPlan['duration_days']) && (int) $beforeSharedPlan['duration_days'] === (int) $afterSharedPlan['duration_days'],
            'shared_currency_unchanged' => isset($beforeSharedPlan['currency'], $afterSharedPlan['currency']) && $beforeSharedPlan['currency'] === $afterSharedPlan['currency'],
            'shared_status_unchanged' => isset($beforeSharedPlan['is_active'], $afterSharedPlan['is_active'], $beforeSharedPlan['is_purchasable'], $afterSharedPlan['is_purchasable']) && (int) $beforeSharedPlan['is_active'] === (int) $afterSharedPlan['is_active'] && (int) $beforeSharedPlan['is_purchasable'] === (int) $afterSharedPlan['is_purchasable'],
            'blank_translation_payload_deletes_rows' => !empty($blankDeleteResult['success']) && $afterBlankDeleteRows === 0,
        );
        ysp_admin_ui_diag_print('Model save/public overlay checks', $modelBehaviorChecks);
        foreach ($modelBehaviorChecks as $label => $ok) {
            ysp_admin_ui_diag_assert($failures, $ok, 'Model save/public overlay check failed: ' . $label);
        }
    } finally {
        $diagnosticDb->rollback();
    }

    $afterTranslationCount = (int) $diagnosticDb->query(
        'SELECT COUNT(*) AS row_count FROM ' . $translationTable
    )->row('row_count');
    $afterArabicTranslatedRows = (int) $diagnosticDb->query(
        'SELECT COUNT(*) AS row_count FROM ' . $translationTable . ' WHERE language_code = ?',
        array('arabic_translated')
    )->row('row_count');
    $cleanupChecks = array(
        'before_translation_count' => $beforeTranslationCount,
        'after_translation_count' => $afterTranslationCount,
        'translation_count_restored' => $beforeTranslationCount === $afterTranslationCount,
        'arabic_translated_rows_before' => $beforeArabicTranslatedRows,
        'arabic_translated_rows_after' => $afterArabicTranslatedRows,
        'arabic_translated_rows_unchanged_zero' => $beforeArabicTranslatedRows === 0 && $afterArabicTranslatedRows === 0,
    );
    ysp_admin_ui_diag_print('Cleanup checks', $cleanupChecks);
    ysp_admin_ui_diag_assert($failures, $cleanupChecks['translation_count_restored'], 'Translation row count changed after rollback.');
    ysp_admin_ui_diag_assert($failures, $cleanupChecks['arabic_translated_rows_unchanged_zero'], 'arabic_translated translation rows exist or changed.');

    $changedFiles = ysp_admin_ui_diag_changed_files($root);
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
    ysp_admin_ui_diag_print('Changed-file safety checks', $safetyChecks);
    ysp_admin_ui_diag_assert($failures, empty($protectedChanged), 'Payment/checkout/access protected files changed in this phase.');

    $diagnosticDb->close();
} catch (Throwable $e) {
    $failures[] = $e->getMessage();
}

if (!empty($failures)) {
    ysp_admin_ui_diag_print('Failures', $failures);
    exit(1);
}

ysp_admin_ui_diag_print('Result', 'PASS: admin bilingual subscription translation UI/save wiring is ready and temporary rows rolled back cleanly.');
exit(0);
