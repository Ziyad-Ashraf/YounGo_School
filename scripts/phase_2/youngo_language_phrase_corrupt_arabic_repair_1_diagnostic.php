<?php
/**
 * LANGUAGE.PHRASE.CORRUPT.ARABIC.REPAIR.1 diagnostic.
 *
 * Read-only verification for the controlled Arabic public phrase repair.
 * It does not import language packs, write phrases, edit dynamic content,
 * change routes, call payment providers, or create checkout/order/access rows.
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
defined('YLPCAR1_LIBRARY_ONLY') || define('YLPCAR1_LIBRARY_ONLY', true);

require APPPATH . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'phase_2' . DIRECTORY_SEPARATOR . 'youngo_language_phrase_corrupt_arabic_repair_1.php';

$failures = array();

function ylpcar1d_print($title, $payload)
{
    echo "\n== " . $title . " ==\n";
    echo is_string($payload)
        ? $payload . "\n"
        : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

function ylpcar1d_assert(&$failures, $condition, $message)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function ylpcar1d_scalar($mysqli, $sql)
{
    if (preg_match('/^\s*(INSERT|UPDATE|DELETE|ALTER|DROP|CREATE|TRUNCATE|REPLACE|GRANT|REVOKE|LOAD|CALL|OPTIMIZE|ANALYZE)\b/i', $sql)) {
        throw new RuntimeException('Blocked non-read SQL in diagnostic.');
    }

    $result = $mysqli->query($sql);
    if (!$result) {
        throw new RuntimeException('DB read failed without exposing credentials.');
    }

    $row = $result->fetch_row();
    $result->free();

    return isset($row[0]) ? $row[0] : null;
}

function ylpcar1d_table_exists($mysqli, $table)
{
    $stmt = $mysqli->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    $stmt->close();

    return isset($row[0]) && (int) $row[0] > 0;
}

function ylpcar1d_fetch_phrase_rows($mysqli, $phrase_key)
{
    $stmt = $mysqli->prepare('SELECT phrase_id, phrase, english, arabic, HEX(COALESCE(arabic, \'\')) AS arabic_hex FROM language WHERE BINARY phrase = BINARY ? ORDER BY phrase_id ASC');
    $stmt->bind_param('s', $phrase_key);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();

    return $rows;
}

function ylpcar1d_payment_phrase_hashes($mysqli)
{
    $keys = array(
        'add_to_cart',
        'apply_coupon',
        'buy_now',
        'checkout',
        'shopping_cart',
        'subscription_checkout_is_not_available_yet',
    );
    $hashes = array();
    $stmt = $mysqli->prepare('SELECT phrase_id, phrase, english, HEX(COALESCE(arabic, \'\')) AS arabic_hex FROM language WHERE BINARY phrase = BINARY ? ORDER BY phrase_id ASC');
    foreach ($keys as $key) {
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $hashes[] = $row['phrase'] . '#' . $row['phrase_id'] . '#' . $row['arabic_hex'];
        }
    }
    $stmt->close();

    sort($hashes);
    return $hashes;
}

class YoungoPhraseRepairDiagnosticResult
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

    public function result_array()
    {
        return $this->rows;
    }
}

class YoungoPhraseRepairDiagnosticDb
{
    protected $mysqli;
    protected $select = '*';
    protected $from = '';
    protected $whereIn = array();
    protected $limit = null;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function table_exists($table)
    {
        return ylpcar1d_table_exists($this->mysqli, $table);
    }

    public function field_exists($field, $table)
    {
        $stmt = $this->mysqli->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
        $stmt->bind_param('ss', $table, $field);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_row();
        $stmt->close();

        return isset($row[0]) && (int) $row[0] > 0;
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

    public function where_in($field, $values)
    {
        $this->whereIn = array($field, array_values($values));
        return $this;
    }

    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    public function get()
    {
        if ($this->from !== 'language' || empty($this->whereIn)) {
            throw new RuntimeException('Unsupported diagnostic DB query.');
        }

        list($field, $values) = $this->whereIn;
        $rows = array();
        if ($field !== 'phrase') {
            throw new RuntimeException('Unsupported diagnostic where_in field.');
        }

        $stmt = $this->mysqli->prepare('SELECT phrase, english, arabic FROM language WHERE phrase = ? LIMIT 1');
        foreach ($values as $value) {
            $stmt->bind_param('s', $value);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $stmt->close();

        $this->select = '*';
        $this->from = '';
        $this->whereIn = array();
        $this->limit = null;

        return new YoungoPhraseRepairDiagnosticResult($rows);
    }
}

class YoungoPhraseRepairDiagnosticCi
{
    public $db;
    public $uri;

    public function __construct($db)
    {
        $this->db = $db;
        $this->uri = new YoungoPhraseRepairDiagnosticUri();
    }
}

class YoungoPhraseRepairDiagnosticUri
{
    public function uri_string()
    {
        return '';
    }
}

try {
    $mysqli = ylpcar1_connect($db, $active_group);
    $allowlist = ylpcar1_repair_allowlist();
    $blockedPattern = '/(?:paymob|payment|checkout|shopping_cart|cart|coupon|buy_now|add_to_cart|pay_now)/i';

    $summary = array(
        'allowlisted_keys' => count($allowlist),
        'rows_checked' => 0,
        'missing_rows' => 0,
        'english_mismatch' => 0,
        'arabic_mismatch' => 0,
        'valid_preexisting_arabic_rows_preserved' => 0,
        'question_mark_placeholders' => 0,
        'mojibake_markers' => 0,
        'arabic_without_arabic_script' => 0,
        'blocked_payment_checkout_allowlist_keys' => 0,
        'manual_override_rows_for_allowlist' => 0,
        'arabic_translated_metadata_rows' => 0,
    );
    $areaSummary = array();
    $sampleResolved = array();

    foreach ($allowlist as $entry) {
        $areaSummary[$entry['area']] = isset($areaSummary[$entry['area']]) ? $areaSummary[$entry['area']] + 1 : 1;
        if (preg_match($blockedPattern, $entry['key'])) {
            $summary['blocked_payment_checkout_allowlist_keys']++;
        }

        $rows = ylpcar1d_fetch_phrase_rows($mysqli, $entry['key']);
        if (empty($rows)) {
            $summary['missing_rows']++;
            $failures[] = 'Missing allowlisted phrase row: ' . $entry['key'];
            continue;
        }

        foreach ($rows as $row) {
            $summary['rows_checked']++;
            if ((string) $row['english'] !== (string) $entry['english']) {
                $summary['english_mismatch']++;
                $failures[] = 'English value mismatch for: ' . $entry['key'];
            }
            if (preg_match('/\?{4,}/', (string) $row['arabic'])) {
                $summary['question_mark_placeholders']++;
                $failures[] = 'Question-mark placeholder remains for: ' . $entry['key'];
            }
            if (strpos((string) $row['arabic'], 'Ø') !== false || strpos((string) $row['arabic'], 'Ù') !== false) {
                $summary['mojibake_markers']++;
                $failures[] = 'Mojibake marker remains for: ' . $entry['key'];
            }
            if (!ylpcar1_has_arabic_script($row['arabic'])) {
                $summary['arabic_without_arabic_script']++;
                $failures[] = 'Arabic script missing for: ' . $entry['key'];
            }
            if ((string) $row['arabic'] !== (string) $entry['arabic']) {
                if (ylpcar1_has_arabic_script($row['arabic']) && !ylpcar1_is_corrupt_arabic_value($row['arabic'], $row['english'])) {
                    $summary['valid_preexisting_arabic_rows_preserved']++;
                } else {
                    $summary['arabic_mismatch']++;
                    $failures[] = 'Arabic repair value mismatch for: ' . $entry['key'];
                }
            }
        }
    }

    if (ylpcar1d_table_exists($mysqli, 'youngo_language_phrase_meta')) {
        $quotedKeys = array();
        foreach ($allowlist as $entry) {
            $quotedKeys[] = "'" . $mysqli->real_escape_string($entry['key']) . "'";
        }
        $summary['manual_override_rows_for_allowlist'] = (int) ylpcar1d_scalar(
            $mysqli,
            "SELECT COUNT(*) FROM youngo_language_phrase_meta WHERE language_code = 'arabic' AND phrase_key IN (" . implode(',', $quotedKeys) . ") AND (source = 'manual_override' OR manually_overridden_at IS NOT NULL)"
        );
        $summary['arabic_translated_metadata_rows'] = (int) ylpcar1d_scalar(
            $mysqli,
            "SELECT COUNT(*) FROM youngo_language_phrase_meta WHERE language_code = 'arabic_translated'"
        );
    }

    $expectedPaymentHashes = array(
        'add_to_cart#1188#3F3F3F203F3F3F203F3F3F3F3F',
        'apply_coupon#1246#3F3F3F3F3F203F3F3F3F3F3F3F',
        'buy_now#1189#3F3F3F3F3F203F3F3F3F',
        'checkout#19#3F3F3F3F3F203F3F3F3F3F',
        'checkout#20#3F3F3F3F3F203F3F3F3F3F',
        'checkout#21#3F3F3F3F3F203F3F3F3F3F',
        'shopping_cart#1223#3F3F3F203F3F3F3F3F3F',
        'subscription_checkout_is_not_available_yet#1397#3F3F3F203F3F3F3F3F3F3F3F203F3F3F203F3F3F3F203F3F3F',
    );
    $paymentHashes = ylpcar1d_payment_phrase_hashes($mysqli);

    $diagnosticDb = new YoungoPhraseRepairDiagnosticDb($mysqli);
    $diagnosticCi = new YoungoPhraseRepairDiagnosticCi($diagnosticDb);
    function &get_instance()
    {
        global $diagnosticCi;
        return $diagnosticCi;
    }

    require APPPATH . 'helpers' . DIRECTORY_SEPARATOR . 'youngo_frontend_language_helper.php';
    foreach (array('hours', 'login', 'my_wishlist', 'course_access', 'view_details') as $sampleKey) {
        $sampleResolved[$sampleKey] = array(
            'arabic' => youngo_frontend_phrase($sampleKey, '', 'arabic'),
            'english' => youngo_frontend_phrase($sampleKey, '', 'english'),
        );
        ylpcar1d_assert($failures, ylpcar1_has_arabic_script($sampleResolved[$sampleKey]['arabic']), 'Helper Arabic resolution failed for: ' . $sampleKey);
        ylpcar1d_assert($failures, !ylpcar1_is_corrupt_arabic_value($sampleResolved[$sampleKey]['arabic'], $sampleResolved[$sampleKey]['english']), 'Helper Arabic value remains corrupt for: ' . $sampleKey);
        ylpcar1d_assert($failures, $sampleResolved[$sampleKey]['english'] !== '', 'Helper English resolution failed for: ' . $sampleKey);
    }

    ylpcar1d_assert($failures, $summary['blocked_payment_checkout_allowlist_keys'] === 0, 'Payment/checkout/cart/coupon key present in allowlist.');
    ylpcar1d_assert($failures, $summary['missing_rows'] === 0, 'One or more allowlisted phrase rows are missing.');
    ylpcar1d_assert($failures, $summary['english_mismatch'] === 0, 'One or more English values changed or do not match expected baseline.');
    ylpcar1d_assert($failures, $summary['arabic_mismatch'] === 0, 'One or more repaired Arabic values do not match the allowlist.');
    ylpcar1d_assert($failures, $summary['question_mark_placeholders'] === 0, 'One or more repaired Arabic values still contain question-mark placeholders.');
    ylpcar1d_assert($failures, $summary['mojibake_markers'] === 0, 'One or more repaired Arabic values still contain mojibake markers.');
    ylpcar1d_assert($failures, $summary['arabic_without_arabic_script'] === 0, 'One or more repaired Arabic values lacks Arabic script.');
    ylpcar1d_assert($failures, $summary['arabic_translated_metadata_rows'] === 0, 'arabic_translated metadata rows exist.');
    ylpcar1d_assert($failures, $paymentHashes === $expectedPaymentHashes, 'Payment/checkout/cart/coupon phrase hashes changed.');

    $protectedTables = array(
        'payment',
        'enrol',
        'youngo_checkout_orders',
        'youngo_coupon_usages',
        'youngo_course_access',
        'youngo_user_subscriptions',
        'youngo_manual_grants',
        'youngo_course_translations',
        'youngo_category_translations',
        'youngo_section_translations',
        'youngo_lesson_translations',
    );
    $protectedCounts = array();
    foreach ($protectedTables as $table) {
        $protectedCounts[$table] = ylpcar1d_table_exists($mysqli, $table)
            ? (int) ylpcar1d_scalar($mysqli, 'SELECT COUNT(*) FROM `' . $mysqli->real_escape_string($table) . '`')
            : null;
    }
    $mysqli->close();

    ksort($areaSummary);
    ylpcar1d_print('Repair allowlist by public area', $areaSummary);
    ylpcar1d_print('Repair diagnostic summary', $summary);
    ylpcar1d_print('Helper resolution samples', $sampleResolved);
    ylpcar1d_print('Payment checkout phrase hash guard', array('unchanged' => $paymentHashes === $expectedPaymentHashes, 'rows_checked' => count($paymentHashes)));
    ylpcar1d_print('Protected/dynamic table count snapshot', $protectedCounts);

    if (!empty($failures)) {
        ylpcar1d_print('Result', array('status' => 'failed', 'failures' => $failures));
        exit(1);
    }

    ylpcar1d_print('Result', 'PASS: controlled Arabic public phrase repair is clean and scoped.');
    exit(0);
} catch (Throwable $exception) {
    ylpcar1d_print('Result', array('status' => 'failed', 'error' => $exception->getMessage()));
    exit(1);
}
