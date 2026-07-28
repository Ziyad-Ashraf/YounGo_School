<?php
/**
 * DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.PUBLIC.QA.1 diagnostic.
 *
 * Read-only final public QA checks for localized subscription plans. This
 * script does not write DB rows, edit plan data, create checkout/order/access
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
$planTable = 'youngo_subscription_plans';
$translationTable = 'youngo_subscription_plan_translations';

$expectedPlans = array(
    'monthly' => array(
        'canonical_name' => 'Monthly',
        'duration_days' => 30,
        'price' => '100.00',
        'currency' => 'EGP',
        'english_name' => 'Monthly',
    ),
    '3-months' => array(
        'canonical_name' => '3 Months',
        'duration_days' => 90,
        'price' => '250.00',
        'currency' => 'EGP',
        'english_name' => '3 Months',
    ),
    'yearly' => array(
        'canonical_name' => 'Yearly',
        'duration_days' => 365,
        'price' => '900.00',
        'currency' => 'EGP',
        'english_name' => 'Yearly',
    ),
);

function ysp_public_qa_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function ysp_public_qa_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function ysp_public_qa_connect($db, $activeGroup)
{
    if (!isset($db[$activeGroup])) {
        throw new RuntimeException('Active database group was not found.');
    }

    $config = $db[$activeGroup];
    $mysqli = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
    if ($mysqli->connect_errno) {
        throw new RuntimeException('DB connection failed without exposing credentials.');
    }

    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

function ysp_public_qa_fetch_all($mysqli, $sql)
{
    if (preg_match('/^\s*(INSERT|UPDATE|DELETE|ALTER|DROP|CREATE|TRUNCATE|REPLACE|GRANT|REVOKE|LOAD|CALL|OPTIMIZE|ANALYZE)\b/i', $sql)) {
        throw new RuntimeException('Blocked non-read SQL in diagnostic.');
    }

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

function ysp_public_qa_scalar($mysqli, $sql)
{
    $rows = ysp_public_qa_fetch_all($mysqli, $sql);
    if (empty($rows)) {
        return null;
    }

    $row = $rows[0];
    return reset($row);
}

function ysp_public_qa_by_slug($plans)
{
    $mapped = array();
    foreach ($plans as $plan) {
        if (isset($plan['slug'])) {
            $mapped[$plan['slug']] = $plan;
        }
    }

    return $mapped;
}

function ysp_public_qa_has_arabic($value)
{
    return preg_match('/\p{Arabic}/u', (string) $value) === 1;
}

function ysp_public_qa_http_get($url)
{
    $headers = array();
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'ignore_errors' => true,
            'timeout' => 15,
            'header' => "User-Agent: YounGoPublicQaDiagnostic/1.0\r\n",
        ),
    ));

    $html = @file_get_contents($url, false, $context);
    if (isset($http_response_header) && is_array($http_response_header)) {
        $headers = $http_response_header;
    }

    $status = 0;
    foreach ($headers as $header) {
        if (preg_match('#^HTTP/\S+\s+([0-9]{3})#', $header, $matches)) {
            $status = (int) $matches[1];
            break;
        }
    }

    return array(
        'url' => $url,
        'status' => $status,
        'html' => $html === false ? '' : $html,
    );
}

function ysp_public_qa_html_has_lang_dir($html, $lang, $dir)
{
    return preg_match('/<html\b[^>]*\blang=["\']' . preg_quote($lang, '/') . '["\'][^>]*\bdir=["\']' . preg_quote($dir, '/') . '["\']/i', $html) === 1
        || preg_match('/<html\b[^>]*\bdir=["\']' . preg_quote($dir, '/') . '["\'][^>]*\blang=["\']' . preg_quote($lang, '/') . '["\']/i', $html) === 1;
}

function ysp_public_qa_has_forbidden_payment_link($html)
{
    $patterns = array(
        '#<(?:a|form)\b[^>]*(?:href|action)=["\'][^"\']*(?:payment/paymob|paymob|home/course_payment|home/shopping_cart|youngo/checkout|checkout|order|enrol|grant)[^"\']*["\']#i',
        '#\b(?:Buy Now|Add to cart|Checkout|Pay now|Pay with Paymob|Subscribe now)\b#i',
    );

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html) === 1) {
            return true;
        }
    }

    return false;
}

class YoungoSubscriptionPublicQaResult
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

class YoungoSubscriptionPublicQaDb
{
    public $db_debug = false;
    protected $mysqli;
    protected $select = '*';
    protected $from = '';
    protected $where = array();
    protected $orderBy = array();
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
            return new YoungoSubscriptionPublicQaResult(array());
        }

        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();

        return new YoungoSubscriptionPublicQaResult($rows);
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
        $this->orderBy[] = '`' . str_replace('`', '``', $field) . '` ' . $direction;
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
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . (int) $this->limit;
        }

        $this->select = '*';
        $this->from = '';
        $this->where = array();
        $this->orderBy = array();
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

class YoungoSubscriptionPublicQaLoader
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

class YoungoSubscriptionPublicQaCi
{
    public $db;
    public $load;

    public function __construct($db)
    {
        $this->db = $db;
        $this->load = new YoungoSubscriptionPublicQaLoader();
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

$diagnosticDb = new YoungoSubscriptionPublicQaDb($db[$active_group]);
$diagnosticCi = new YoungoSubscriptionPublicQaCi($diagnosticDb);

function &get_instance()
{
    global $diagnosticCi;
    return $diagnosticCi;
}

try {
    $mysqli = ysp_public_qa_connect($db, $active_group);

    $requiredFiles = array(
        'application/models/Youngo_subscription_model.php',
        'application/views/frontend/youngo/subscriptions.php',
        'application/controllers/Home.php',
        'application/helpers/youngo_frontend_language_helper.php',
        'application/config/routes.php',
    );
    $fileChecks = array();
    foreach ($requiredFiles as $file) {
        $fileChecks[$file] = is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file));
    }
    ysp_public_qa_print('Required files', $fileChecks);
    foreach ($fileChecks as $file => $exists) {
        ysp_public_qa_assert($failures, $exists, $file . ' is missing.');
    }

    $publicRows = ysp_public_qa_fetch_all(
        $mysqli,
        "SELECT id, name, slug, duration_days, price, currency, is_active, is_purchasable, is_featured, sort_order, archived_at FROM {$planTable} WHERE is_active = 1 AND is_purchasable = 1 AND currency = 'EGP' AND price > 0 AND duration_days > 0 AND archived_at IS NULL ORDER BY sort_order ASC, id ASC"
    );
    $publicBySlug = ysp_public_qa_by_slug($publicRows);
    $sharedChecks = array(
        'public_plan_count' => count($publicRows),
        'expected_public_slugs_present' => array_values(array_intersect(array_keys($expectedPlans), array_keys($publicBySlug))),
        'unexpected_public_slugs' => array_values(array_diff(array_keys($publicBySlug), array_keys($expectedPlans))),
    );
    foreach ($expectedPlans as $slug => $expected) {
        $plan = isset($publicBySlug[$slug]) ? $publicBySlug[$slug] : array();
        $sharedChecks[$slug . '_canonical_name_ok'] = isset($plan['name']) && $plan['name'] === $expected['canonical_name'];
        $sharedChecks[$slug . '_slug_ok'] = isset($plan['slug']) && $plan['slug'] === $slug;
        $sharedChecks[$slug . '_duration_ok'] = isset($plan['duration_days']) && (int) $plan['duration_days'] === $expected['duration_days'];
        $sharedChecks[$slug . '_price_ok'] = isset($plan['price']) && number_format((float) $plan['price'], 2, '.', '') === $expected['price'];
        $sharedChecks[$slug . '_currency_ok'] = isset($plan['currency']) && $plan['currency'] === $expected['currency'];
        $sharedChecks[$slug . '_active_ok'] = isset($plan['is_active']) && (int) $plan['is_active'] === 1;
        $sharedChecks[$slug . '_purchasable_ok'] = isset($plan['is_purchasable']) && (int) $plan['is_purchasable'] === 1;
        $sharedChecks[$slug . '_not_archived'] = array_key_exists('archived_at', $plan) && ($plan['archived_at'] === null || $plan['archived_at'] === '');
    }
    ysp_public_qa_print('DB shared-field checks', $sharedChecks);
    ysp_public_qa_assert($failures, count($publicRows) === 3, 'Expected exactly 3 public subscription plans.');
    ysp_public_qa_assert($failures, empty($sharedChecks['unexpected_public_slugs']), 'Unexpected public subscription plan slug detected.');
    foreach ($sharedChecks as $label => $ok) {
        if (is_bool($ok)) {
            ysp_public_qa_assert($failures, $ok, 'Shared-field check failed: ' . $label);
        }
    }

    $translationRows = ysp_public_qa_fetch_all(
        $mysqli,
        "SELECT p.slug, t.language_code, t.name, t.short_description, t.description, t.badge_label FROM {$translationTable} t INNER JOIN {$planTable} p ON p.id = t.plan_id ORDER BY p.sort_order ASC, p.id ASC, t.language_code ASC"
    );
    $translations = array();
    foreach ($translationRows as $row) {
        $translations[$row['slug']][$row['language_code']] = $row;
    }

    $languageCodes = ysp_public_qa_fetch_all($mysqli, "SELECT language_code, COUNT(*) AS row_count FROM {$translationTable} GROUP BY language_code ORDER BY language_code ASC");
    $languageCodeSummary = array();
    foreach ($languageCodes as $row) {
        $languageCodeSummary[$row['language_code']] = (int) $row['row_count'];
    }

    $translationChecks = array(
        'translation_row_count' => count($translationRows),
        'language_code_counts' => $languageCodeSummary,
        'only_english_and_arabic' => array_keys($languageCodeSummary) === array('arabic', 'english'),
        'arabic_translated_rows' => (int) ysp_public_qa_scalar($mysqli, "SELECT COUNT(*) FROM {$translationTable} WHERE language_code = 'arabic_translated'"),
    );
    foreach ($expectedPlans as $slug => $expected) {
        $english = isset($translations[$slug]['english']) ? $translations[$slug]['english'] : array();
        $arabic = isset($translations[$slug]['arabic']) ? $translations[$slug]['arabic'] : array();
        $translationChecks[$slug . '_english_row_exists'] = !empty($english);
        $translationChecks[$slug . '_arabic_row_exists'] = !empty($arabic);
        $translationChecks[$slug . '_english_name_ok'] = isset($english['name']) && $english['name'] === $expected['english_name'];
        $translationChecks[$slug . '_arabic_name_has_arabic'] = isset($arabic['name']) && ysp_public_qa_has_arabic($arabic['name']);
        $translationChecks[$slug . '_arabic_short_description_has_arabic'] = isset($arabic['short_description']) && ysp_public_qa_has_arabic($arabic['short_description']);
        $translationChecks[$slug . '_arabic_description_has_arabic'] = isset($arabic['description']) && ysp_public_qa_has_arabic($arabic['description']);
    }
    ysp_public_qa_print('DB translation checks', $translationChecks);
    ysp_public_qa_assert($failures, count($translationRows) === 6, 'Expected exactly 6 subscription translation rows.');
    ysp_public_qa_assert($failures, $translationChecks['only_english_and_arabic'], 'Translation language codes must be english and arabic only.');
    ysp_public_qa_assert($failures, $translationChecks['arabic_translated_rows'] === 0, 'arabic_translated translation rows must not exist.');
    foreach ($translationChecks as $label => $ok) {
        if (is_bool($ok)) {
            ysp_public_qa_assert($failures, $ok, 'Translation check failed: ' . $label);
        }
    }

    require_once $root . '/application/models/Youngo_subscription_model.php';
    $model = new Youngo_subscription_model();
    $arabicPlans = ysp_public_qa_by_slug($model->get_public_subscription_plans('arabic'));
    $arabicCompatPlans = ysp_public_qa_by_slug($model->get_public_subscription_plans('ar'));
    $englishPlans = ysp_public_qa_by_slug($model->get_public_subscription_plans('english'));
    $forbiddenPlans = $model->get_public_subscription_plans('arabic_translated');

    $modelChecks = array(
        'arabic_plan_count' => count($arabicPlans),
        'arabic_compat_plan_count' => count($arabicCompatPlans),
        'english_plan_count' => count($englishPlans),
        'arabic_translated_returns_empty' => empty($forbiddenPlans),
    );
    foreach ($expectedPlans as $slug => $expected) {
        $arabicPlan = isset($arabicPlans[$slug]) ? $arabicPlans[$slug] : array();
        $compatPlan = isset($arabicCompatPlans[$slug]) ? $arabicCompatPlans[$slug] : array();
        $englishPlan = isset($englishPlans[$slug]) ? $englishPlans[$slug] : array();
        $modelChecks[$slug . '_arabic_name_has_arabic'] = isset($arabicPlan['name']) && ysp_public_qa_has_arabic($arabicPlan['name']);
        $modelChecks[$slug . '_arabic_description_has_arabic'] = isset($arabicPlan['description']) && ysp_public_qa_has_arabic($arabicPlan['description']);
        $modelChecks[$slug . '_compat_uses_arabic'] = isset($compatPlan['name']) && ysp_public_qa_has_arabic($compatPlan['name']);
        $modelChecks[$slug . '_english_name_ok'] = isset($englishPlan['name']) && $englishPlan['name'] === $expected['english_name'];
        $modelChecks[$slug . '_english_name_not_arabic'] = isset($englishPlan['name']) && !ysp_public_qa_has_arabic($englishPlan['name']);
        $modelChecks[$slug . '_slug_preserved'] = isset($arabicPlan['slug'], $englishPlan['slug']) && $arabicPlan['slug'] === $slug && $englishPlan['slug'] === $slug;
        $modelChecks[$slug . '_price_preserved'] = isset($arabicPlan['price'], $englishPlan['price']) && $arabicPlan['price'] === $expected['price'] && $englishPlan['price'] === $expected['price'];
        $modelChecks[$slug . '_duration_preserved'] = isset($arabicPlan['duration_days'], $englishPlan['duration_days']) && (int) $arabicPlan['duration_days'] === $expected['duration_days'] && (int) $englishPlan['duration_days'] === $expected['duration_days'];
        $modelChecks[$slug . '_currency_preserved'] = isset($arabicPlan['currency'], $englishPlan['currency']) && $arabicPlan['currency'] === 'EGP' && $englishPlan['currency'] === 'EGP';
    }
    ysp_public_qa_print('Public model checks', $modelChecks);
    foreach ($modelChecks as $label => $ok) {
        if (is_bool($ok)) {
            ysp_public_qa_assert($failures, $ok, 'Public model check failed: ' . $label);
        }
    }

    $urls = array(
        'arabic_default' => array('url' => 'http://localhost/subscriptions', 'lang' => 'ar', 'dir' => 'rtl', 'expects_arabic' => true),
        'arabic_compat' => array('url' => 'http://localhost/ar/subscriptions', 'lang' => 'ar', 'dir' => 'rtl', 'expects_arabic' => true),
        'english' => array('url' => 'http://localhost/en/subscriptions', 'lang' => 'en', 'dir' => 'ltr', 'expects_arabic' => false),
    );
    $httpChecks = array();
    foreach ($urls as $label => $expectation) {
        $response = ysp_public_qa_http_get($expectation['url']);
        $html = $response['html'];
        $hrefs = array();
        if (preg_match_all('/href=["\']([^"\']+)["\']/i', $html, $matches)) {
            $hrefs = $matches[1];
        }

        $subscriptionsHrefs = array_values(array_filter($hrefs, function ($href) {
            return strpos($href, 'subscriptions') !== false;
        }));

        $htmlHasArabic = ysp_public_qa_has_arabic($html);
        $httpChecks[$label] = array(
            'status' => $response['status'],
            'lang_dir_ok' => ysp_public_qa_html_has_lang_dir($html, $expectation['lang'], $expectation['dir']),
            'has_arabic_copy' => $htmlHasArabic,
            'has_no_forbidden_payment_link' => !ysp_public_qa_has_forbidden_payment_link($html),
            'subscription_hrefs' => $subscriptionsHrefs,
            'arabic_links_unprefixed' => $label === 'english' ? true : in_array('http://localhost/subscriptions', $subscriptionsHrefs, true),
            'english_links_prefixed' => in_array('http://localhost/en/subscriptions', $subscriptionsHrefs, true),
            'ar_compat_not_generated_as_canonical' => !in_array('http://localhost/ar/subscriptions', $subscriptionsHrefs, true),
        );

        ysp_public_qa_assert($failures, $response['status'] === 200, $label . ' did not return HTTP 200.');
        ysp_public_qa_assert($failures, $httpChecks[$label]['lang_dir_ok'], $label . ' did not render expected html lang/dir.');
        ysp_public_qa_assert($failures, $httpChecks[$label]['has_no_forbidden_payment_link'], $label . ' rendered a forbidden payment/checkout/order/enrol/grant CTA or link.');
        ysp_public_qa_assert($failures, $httpChecks[$label]['english_links_prefixed'], $label . ' did not render an /en subscriptions link.');
        ysp_public_qa_assert($failures, $httpChecks[$label]['ar_compat_not_generated_as_canonical'], $label . ' generated /ar/subscriptions as canonical.');
        if (!empty($expectation['expects_arabic'])) {
            ysp_public_qa_assert($failures, $htmlHasArabic, $label . ' did not render Arabic copy.');
            ysp_public_qa_assert($failures, $httpChecks[$label]['arabic_links_unprefixed'], $label . ' did not render unprefixed Arabic subscriptions link.');
        } else {
            ysp_public_qa_assert($failures, !$htmlHasArabic, $label . ' rendered Arabic copy on English subscriptions page.');
        }
    }
    ysp_public_qa_print('HTTP/rendered HTML checks', $httpChecks);

    $routesSource = is_file($root . '/application/config/routes.php') ? file_get_contents($root . '/application/config/routes.php') : '';
    $helperSource = is_file($root . '/application/helpers/youngo_frontend_language_helper.php') ? file_get_contents($root . '/application/helpers/youngo_frontend_language_helper.php') : '';
    $viewSource = is_file($root . '/application/views/frontend/youngo/subscriptions.php') ? file_get_contents($root . '/application/views/frontend/youngo/subscriptions.php') : '';
    $sourceChecks = array(
        'default_route_exists' => strpos($routesSource, "\$route['subscriptions'] = 'home/subscriptions';") !== false,
        'english_route_exists' => strpos($routesSource, "\$route['en/subscriptions'] = 'home/subscriptions';") !== false,
        'ar_compat_route_exists' => strpos($routesSource, "\$route['ar/subscriptions'] = 'home/subscriptions';") !== false,
        'helper_generates_arabic_unprefixed' => strpos($helperSource, "return \$target_language === 'arabic' ? 'subscriptions' : 'en/subscriptions';") !== false,
        'view_contact_cta_only' => strpos($viewSource, 'youngo_frontend_contact_url') !== false,
        'view_has_no_paymob_reference' => stripos($viewSource, 'paymob') === false,
        'view_has_no_checkout_reference' => stripos($viewSource, 'checkout') === false,
    );
    ysp_public_qa_print('Source safety checks', $sourceChecks);
    foreach ($sourceChecks as $label => $ok) {
        ysp_public_qa_assert($failures, $ok, 'Source safety check failed: ' . $label);
    }

    $diagnosticDb->close();
    $mysqli->close();
} catch (Throwable $exception) {
    $failures[] = $exception->getMessage();
}

if (!empty($failures)) {
    ysp_public_qa_print('Failures', $failures);
    exit(1);
}

ysp_public_qa_print('Result', 'PASS: localized subscription plan public QA checks passed read-only.');
exit(0);
