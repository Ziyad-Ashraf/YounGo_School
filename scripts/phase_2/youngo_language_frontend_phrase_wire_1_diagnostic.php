<?php
/**
 * LANGUAGE.FRONTEND.PHRASE.WIRE.1 diagnostic.
 *
 * Read-only checks for public frontend phrase wiring. This diagnostic does not
 * edit phrase rows, import language packs, call payment providers, or create
 * checkout/order/enrol/access records.
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

function youngo_frontend_phrase_wire_diag_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

function youngo_frontend_phrase_wire_diag_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

class YoungoFrontendPhraseWireResult
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

class YoungoFrontendPhraseWireDb
{
    protected $mysqli;
    protected $select = '*';
    protected $from = '';
    protected $whereIn = array();
    protected $limit = null;

    public function __construct($config)
    {
        $this->mysqli = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
        if ($this->mysqli->connect_errno) {
            throw new RuntimeException('DB connection failed without exposing credentials.');
        }

        $this->mysqli->set_charset('utf8mb4');
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
            throw new RuntimeException('DB query failed without exposing credentials.');
        }

        if ($result === true) {
            return new YoungoFrontendPhraseWireResult(array());
        }

        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();

        return new YoungoFrontendPhraseWireResult($rows);
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

    public function where_in($field, $values)
    {
        $this->whereIn[] = array($field, array_values((array) $values));
        return $this;
    }

    public function limit($limit)
    {
        $this->limit = max(0, (int) $limit);
        return $this;
    }

    public function get()
    {
        $sql = 'SELECT ' . $this->select . ' FROM `' . str_replace('`', '``', $this->from) . '`';
        $where = array();
        foreach ($this->whereIn as $condition) {
            $field = $condition[0];
            $values = $condition[1];
            if (empty($values)) {
                $where[] = '1 = 0';
                continue;
            }
            $where[] = '`' . str_replace('`', '``', $field) . '` IN (' . implode(', ', array_map(array($this, 'escape_value'), $values)) . ')';
        }
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . (int) $this->limit;
        }

        $this->select = '*';
        $this->from = '';
        $this->whereIn = array();
        $this->limit = null;

        return $this->query($sql);
    }

    public function phrase_presence($keys)
    {
        $escaped = array_map(array($this, 'escape_value'), $keys);
        return $this->query(
            'SELECT phrase, english, arabic FROM language WHERE phrase IN (' . implode(', ', $escaped) . ')'
        )->result_array();
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

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'" . $this->mysqli->real_escape_string((string) $value) . "'";
    }
}

class YoungoFrontendPhraseWireUri
{
    protected $uriString = '';

    public function set_uri_string($uriString)
    {
        $this->uriString = trim((string) $uriString, '/');
    }

    public function uri_string()
    {
        return $this->uriString;
    }
}

class YoungoFrontendPhraseWireCi
{
    public $db;
    public $uri;

    public function __construct($db)
    {
        $this->db = $db;
        $this->uri = new YoungoFrontendPhraseWireUri();
    }
}

if (!isset($db[$active_group])) {
    fwrite(STDERR, "Active database group was not found.\n");
    exit(1);
}

$diagnosticDb = new YoungoFrontendPhraseWireDb($db[$active_group]);
$diagnosticCi = new YoungoFrontendPhraseWireCi($diagnosticDb);

function &get_instance()
{
    global $diagnosticCi;
    return $diagnosticCi;
}

$helperFile = $root . '/application/helpers/youngo_frontend_language_helper.php';
$headerFile = $root . '/application/views/frontend/youngo/header.php';
$footerFile = $root . '/application/views/frontend/youngo/footer.php';
$subscriptionsFile = $root . '/application/views/frontend/youngo/subscriptions.php';
$subscriptionModelFile = $root . '/application/models/Youngo_subscription_model.php';

$requiredFiles = array(
    'application/helpers/youngo_frontend_language_helper.php' => is_file($helperFile),
    'application/views/frontend/youngo/header.php' => is_file($headerFile),
    'application/views/frontend/youngo/footer.php' => is_file($footerFile),
    'application/views/frontend/youngo/subscriptions.php' => is_file($subscriptionsFile),
    'application/models/Youngo_subscription_model.php' => is_file($subscriptionModelFile),
);
youngo_frontend_phrase_wire_diag_print('Required files', $requiredFiles);
foreach ($requiredFiles as $label => $exists) {
    youngo_frontend_phrase_wire_diag_assert($failures, $exists, $label . ' is missing.');
}

require_once $helperFile;

$helperSource = file_get_contents($helperFile);
$headerSource = file_get_contents($headerFile);
$footerSource = file_get_contents($footerFile);
$subscriptionsSource = file_get_contents($subscriptionsFile);
$subscriptionModelSource = file_get_contents($subscriptionModelFile);

$phraseFunctionOffset = strpos($helperSource, 'function youngo_frontend_phrase(');
$dbLookupOffset = strpos($helperSource, 'youngo_frontend_phrase_db_row($phrase_key)', $phraseFunctionOffset);
$localFallbackOffset = strpos($helperSource, 'youngo_frontend_local_phrase_value($phrase_key', $phraseFunctionOffset);

$sourceChecks = array(
    'frontend_phrase_helper_exists' => function_exists('youngo_frontend_phrase'),
    'escaped_phrase_helper_exists' => function_exists('youngo_frontend_phrase_e'),
    'dedicated_phrase_language_resolver_exists' => function_exists('youngo_frontend_phrase_language_code'),
    'db_lookup_helper_exists' => function_exists('youngo_frontend_phrase_db_row'),
    'db_lookup_before_local_fallback' => $phraseFunctionOffset !== false && $dbLookupOffset !== false && $localFallbackOffset !== false && $dbLookupOffset < $localFallbackOffset,
    'helper_queries_language_table' => strpos($helperSource, "from('language')") !== false,
    'helper_uses_uri_active_language' => strpos($helperSource, '$language_code === null || trim((string) $language_code) ===') !== false && strpos($helperSource, 'youngo_frontend_active_language()') !== false,
    'arabic_translated_not_phrase_language' => youngo_frontend_phrase_language_code('arabic_translated') === null,
    'helper_no_db_writes' => preg_match('/->(?:insert|update|delete|replace)\s*\(/i', $helperSource) !== 1,
);
youngo_frontend_phrase_wire_diag_print('Helper source checks', $sourceChecks);
foreach ($sourceChecks as $label => $ok) {
    youngo_frontend_phrase_wire_diag_assert($failures, $ok, 'Helper source check failed: ' . $label);
}

$labelKeys = array(
    'home',
    'courses',
    'subscriptions',
    'blog',
    'contact',
    'subscription_plans',
    'no_subscription_plans_available_yet',
    'contact_us',
    'featured_plan',
    'duration',
    'days',
    'egp',
);
$phraseRows = $diagnosticDb->phrase_presence($labelKeys);
$phrasePresence = array();
foreach ($phraseRows as $row) {
    $phrasePresence[$row['phrase']] = array(
        'has_english' => trim((string) $row['english']) !== '',
        'has_arabic' => trim((string) $row['arabic']) !== '',
    );
}
youngo_frontend_phrase_wire_diag_print('Phrase DB presence', $phrasePresence);
foreach (array('home', 'courses', 'subscriptions', 'subscription_plans', 'contact_us') as $requiredKey) {
    if (!isset($phrasePresence[$requiredKey])) {
        $phrasePresence[$requiredKey] = array(
            'has_english' => false,
            'has_arabic' => false,
            'uses_fallback' => true,
        );
    }
}

$diagnosticCi->uri->set_uri_string('subscriptions');
$arabicDefaultLabel = youngo_frontend_phrase('subscriptions', 'Subscriptions');
$diagnosticCi->uri->set_uri_string('ar/subscriptions');
$arabicCompatLabel = youngo_frontend_phrase('subscriptions', 'Subscriptions');
$diagnosticCi->uri->set_uri_string('en/subscriptions');
$englishLabel = youngo_frontend_phrase('subscriptions', 'Subscriptions');
$invalidLanguageLabel = youngo_frontend_phrase('subscriptions', 'Subscriptions', 'arabic_translated');

$runtimeChecks = array(
    'default_uri_resolves_arabic' => youngo_frontend_active_language('subscriptions') === 'arabic',
    'ar_uri_resolves_arabic' => youngo_frontend_active_language('ar/subscriptions') === 'arabic',
    'en_uri_resolves_english' => youngo_frontend_active_language('en/subscriptions') === 'english',
    'arabic_default_label_non_empty' => trim($arabicDefaultLabel) !== '',
    'arabic_compat_label_non_empty' => trim($arabicCompatLabel) !== '',
    'english_label_non_empty' => trim($englishLabel) !== '',
    'arabic_default_uses_db_or_fallback_without_write' => trim($arabicDefaultLabel) !== '',
    'arabic_translated_falls_back_safely' => trim($invalidLanguageLabel) !== '',
);
youngo_frontend_phrase_wire_diag_print('Runtime phrase checks', $runtimeChecks);
foreach ($runtimeChecks as $label => $ok) {
    youngo_frontend_phrase_wire_diag_assert($failures, $ok, 'Runtime phrase check failed: ' . $label);
}

$viewChecks = array(
    'header_nav_uses_escaped_phrase_helper' => substr_count($headerSource, 'youngo_frontend_phrase_e(') >= 8,
    'footer_uses_escaped_phrase_helper' => substr_count($footerSource, 'youngo_frontend_phrase_e(') >= 5,
    'footer_tagline_is_phrase_based' => strpos($footerSource, 'safe,_joyful_online_learning_for_curious_kids_and_confident_families.') !== false,
    'subscriptions_title_phrase_based' => strpos($subscriptionsSource, "youngo_frontend_phrase('subscription_plans'") !== false,
    'subscriptions_empty_state_phrase_based' => strpos($subscriptionsSource, 'no_subscription_plans_available_yet') !== false,
    'subscriptions_duration_label_phrase_based' => strpos($subscriptionsSource, "youngo_frontend_phrase('duration', 'Duration'") !== false,
    'subscriptions_currency_label_phrase_based' => strpos($subscriptionsSource, "youngo_frontend_phrase(strtolower(\$plan_currency)") !== false,
    'subscription_model_days_phrase_based' => strpos($subscriptionModelSource, "youngo_frontend_phrase(\$phrase_key, \$fallback, \$language)") !== false,
    'arabic_translated_not_rendered_in_views' => stripos($headerSource . $footerSource . $subscriptionsSource, 'arabic_translated') === false,
);
youngo_frontend_phrase_wire_diag_print('View/model wiring checks', $viewChecks);
foreach ($viewChecks as $label => $ok) {
    youngo_frontend_phrase_wire_diag_assert($failures, $ok, 'View/model wiring check failed: ' . $label);
}

$forbiddenLinkPatterns = array(
    '#href\s*=\s*["\'][^"\']*(?:payment/paymob|home/course_payment|home/shopping_cart|checkout|order|enrol|grant)[^"\']*["\']#i',
    '#site_url\s*\(\s*["\'](?:payment/paymob|home/course_payment|home/shopping_cart|checkout|order|enrol|grant)#i',
);
$forbiddenLinks = array();
foreach (array('header' => $headerSource, 'footer' => $footerSource, 'subscriptions' => $subscriptionsSource) as $label => $source) {
    foreach ($forbiddenLinkPatterns as $pattern) {
        if (preg_match($pattern, $source, $match)) {
            $forbiddenLinks[] = $label . ': ' . $match[0];
        }
    }
}
youngo_frontend_phrase_wire_diag_print('Payment/CTA link checks', array(
    'forbidden_links_found' => count($forbiddenLinks),
));
youngo_frontend_phrase_wire_diag_assert($failures, empty($forbiddenLinks), 'Payment/checkout/order/enrol/grant link was exposed.');

$gitStatus = array();
exec('git -C ' . escapeshellarg($root) . ' status --short', $gitStatus);
youngo_frontend_phrase_wire_diag_print('Git status short', $gitStatus);

if (!empty($failures)) {
    youngo_frontend_phrase_wire_diag_print('FAILURES', $failures);
    exit(1);
}

youngo_frontend_phrase_wire_diag_print('Result', 'PASS: public frontend phrase helper is DB-first, URI-language-aware, and wired to header/footer/subscriptions without payment CTA exposure.');
exit(0);
