<?php
/**
 * Phase 2W.4: DataTables UI phrase seed.
 *
 * DataTables (and its Buttons extension) render their own English-only
 * chrome - "Search:", "Show _MENU_ entries", pagination labels, the
 * "Export as CSV" button - independently of get_phrase(). This seeds
 * english/arabic language rows for that chrome so
 * application/views/backend/includes_bottom.php can route it through the
 * same get_phrase() system as everything else (see the
 * $.fn.dataTable.defaults.language block added there).
 *
 * Idempotent: only inserts phrases that don't already exist; never
 * overwrites an existing row (use youngo_phase_2w3-style repair separately
 * if one of these keys is ever found corrupted).
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
$phrases_path = __DIR__ . DIRECTORY_SEPARATOR . 'youngo_phase_2w4_datatable_phrases.json';

$config = $db['default'];
$mysqli = @new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
if ($mysqli->connect_errno) {
    echo "Database connection failed without exposing credentials.\n";
    exit(2);
}
$mysqli->set_charset('utf8');

function w4_section($title, $payload)
{
    echo "\n== {$title} ==\n";
    echo is_string($payload) ? $payload . "\n" : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}

if (!is_file($phrases_path)) {
    w4_section('Result', array('status' => 'FAIL', 'reason' => 'Phrase dictionary not found: ' . $phrases_path));
    exit(1);
}

$phrases = json_decode(file_get_contents($phrases_path), true);
if (!is_array($phrases) || count($phrases) === 0) {
    w4_section('Result', array('status' => 'FAIL', 'reason' => 'Phrase dictionary is empty or invalid JSON.'));
    exit(1);
}

$existing = array();
$result = $mysqli->query("SELECT phrase FROM language");
while ($row = $result->fetch_assoc()) {
    $existing[$row['phrase']] = true;
}

$stats = array('phrases_available' => count($phrases), 'inserted' => 0, 'skipped_existing' => 0);
$inserts = array();

foreach ($phrases as $key => $pair) {
    if (isset($existing[$key])) {
        $stats['skipped_existing']++;
        continue;
    }
    $inserts[] = array($key, (string) $pair['english'], (string) $pair['arabic']);
}

$mysqli->begin_transaction();
try {
    if (!empty($inserts)) {
        $stmt = $mysqli->prepare("INSERT INTO language (phrase, english, arabic) VALUES (?, ?, ?)");
        foreach ($inserts as $row) {
            $stmt->bind_param('sss', $row[0], $row[1], $row[2]);
            if (!$stmt->execute()) {
                throw new Exception('Could not insert phrase: ' . $row[0]);
            }
            $stats['inserted']++;
        }
    }
    $mysqli->commit();
} catch (Throwable $e) {
    $mysqli->rollback();
    w4_section('Result', array('status' => 'FAIL', 'reason' => $e->getMessage()));
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
        w4_section('Result', array('status' => 'FAIL', 'reason' => 'Could not write application/language/arabic.json.'));
        exit(1);
    }
}

w4_section('DataTables phrase seed summary', $stats);
w4_section('Result', array('status' => 'PASS'));
