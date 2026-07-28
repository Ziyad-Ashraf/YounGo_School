<?php
/**
 * Phase 2W.5: Seed phrase rows for hardcoded admin-page strings.
 *
 * Companion to the 2W.5 wiring pass that wraps hardcoded English text in
 * the newer YounGo-specific admin pages (payment settings, manual grants,
 * subscription plans, InstaPay review, entitlement summaries, role
 * assignments, manage_language, etc.) with get_phrase() calls. Those pages
 * were built without translation wrapping at all, so their labels never
 * went through get_phrase()'s lazy-insert path.
 *
 * This dictionary was cross-checked against the live language table before
 * being written: keys that already existed with a clean value were
 * dropped in favor of reuse. It still inserts genuinely new phrase rows,
 * but some of these pages had already been visited once (lazy-inserting
 * an empty or corrupt row via get_phrase()'s fallback) before this pass
 * wrapped their markup, so it also repairs any matching row that is still
 * empty/corrupt using the same BINARY compare-and-swap safety pattern as
 * youngo_phase_2w3_admin_corrupt_arabic_repair.php. A row with a clean,
 * different existing value is left untouched.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$root = dirname(dirname(__DIR__));
define('ENVIRONMENT', 'development');
define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'config/database.php';

$write_file = !in_array('--no-file', $argv, true);
$phrases_path = __DIR__ . DIRECTORY_SEPARATOR . 'youngo_phase_2w5_hardcoded_admin_translations.json';

$config = $db['default'];
$mysqli = @new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
if ($mysqli->connect_errno) {
    echo "Database connection failed without exposing credentials.\n";
    exit(2);
}
$mysqli->set_charset('utf8');

function w5_section($title, $payload)
{
    echo "\n== {$title} ==\n";
    echo is_string($payload) ? $payload . "\n" : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}

function w5_is_corrupt($arabic, $english)
{
    $arabic = trim((string) $arabic);
    if ($arabic === '') {
        return true;
    }
    if (preg_match('/\?{4,}/', $arabic)) {
        return true;
    }
    if (strcasecmp($arabic, trim((string) $english)) === 0) {
        return true;
    }
    return false;
}

if (!is_file($phrases_path)) {
    w5_section('Result', array('status' => 'FAIL', 'reason' => 'Phrase dictionary not found: ' . $phrases_path));
    exit(1);
}

$phrases = json_decode(file_get_contents($phrases_path), true);
if (!is_array($phrases) || count($phrases) === 0) {
    w5_section('Result', array('status' => 'FAIL', 'reason' => 'Phrase dictionary is empty or invalid JSON.'));
    exit(1);
}

$existing = array(); // phrase => array of rows (phrase_id, arabic, english)
$result = $mysqli->query("SELECT phrase_id, phrase, arabic, english FROM language");
while ($row = $result->fetch_assoc()) {
    $existing[$row['phrase']][] = $row;
}

$stats = array(
    'phrases_available' => count($phrases),
    'inserted' => 0,
    'repaired_existing_empty_or_corrupt' => 0,
    'skipped_existing_clean' => 0,
);

$mysqli->begin_transaction();
try {
    $insert_stmt = $mysqli->prepare("INSERT INTO language (phrase, english, arabic) VALUES (?, ?, ?)");
    // arabic can be SQL NULL (not just ''); NULL never satisfies
    // "BINARY arabic = BINARY ''", so the CAS must special-case it.
    $update_stmt = $mysqli->prepare("UPDATE language SET arabic = ? WHERE phrase_id = ? AND (arabic IS NULL OR BINARY arabic = BINARY ?)");

    foreach ($phrases as $key => $pair) {
        $english = (string) $pair['english'];
        $arabic = (string) $pair['arabic'];

        if (!isset($existing[$key])) {
            $insert_stmt->bind_param('sss', $key, $english, $arabic);
            if (!$insert_stmt->execute()) {
                throw new Exception('Could not insert phrase: ' . $key);
            }
            $stats['inserted']++;
            continue;
        }

        foreach ($existing[$key] as $row) {
            if (!w5_is_corrupt($row['arabic'], $row['english'])) {
                $stats['skipped_existing_clean']++;
                continue;
            }
            $phrase_id = (int) $row['phrase_id'];
            $current = (string) $row['arabic'];
            $update_stmt->bind_param('sis', $arabic, $phrase_id, $current);
            $update_stmt->execute();
            if ($update_stmt->affected_rows > 0) {
                $stats['repaired_existing_empty_or_corrupt']++;
            }
        }
    }

    $mysqli->commit();
} catch (Throwable $e) {
    $mysqli->rollback();
    w5_section('Result', array('status' => 'FAIL', 'reason' => $e->getMessage()));
    exit(1);
}

if ($write_file) {
    $refreshed = $mysqli->query("SELECT phrase, arabic, english FROM language ORDER BY phrase_id");
    $json_phrases = array();
    while ($row = $refreshed->fetch_assoc()) {
        $arabic = trim((string) $row['arabic']);
        $json_phrases[(string) $row['phrase']] = $arabic !== '' ? $arabic : (string) $row['english'];
    }
    $language_file = $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'arabic.json';
    $json = json_encode($json_phrases, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents($language_file, $json . PHP_EOL) === false) {
        w5_section('Result', array('status' => 'FAIL', 'reason' => 'Could not write application/language/arabic.json.'));
        exit(1);
    }
}

w5_section('Hardcoded admin phrase seed summary', $stats);
w5_section('Result', array('status' => 'PASS'));
