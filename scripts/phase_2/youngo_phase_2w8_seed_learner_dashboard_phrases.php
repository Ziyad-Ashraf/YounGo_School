<?php
/**
 * Phase 2W.8: Learner/instructor dashboard ("user" role) phrase gap fill.
 *
 * Phase 2W.1-2W.7 measured and repaired the *admin* dashboard's phrase
 * coverage. This is the equivalent pass for application/views/backend/user
 * (the shared learner + instructor dashboard, role_id=2). Measuring that
 * key inventory against the live language table found it already 97%
 * covered - most of its keys are shared with admin (status, save, date,
 * etc.) and were fixed there. Only 12 keys were genuinely missing/empty
 * for this dashboard specifically; this script seeds those.
 *
 * Same safety model as 2W.5-2W.7: only inserts new phrase rows or repairs
 * a row that is still empty/corrupt via BINARY compare-and-swap (NULL
 * handled explicitly).
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
$phrases_path = __DIR__ . DIRECTORY_SEPARATOR . 'youngo_phase_2w8_learner_dashboard_phrases.json';

$config = $db['default'];
$mysqli = @new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
if ($mysqli->connect_errno) {
    echo "Database connection failed without exposing credentials.\n";
    exit(2);
}
$mysqli->set_charset('utf8');

function w8_section($title, $payload)
{
    echo "\n== {$title} ==\n";
    echo is_string($payload) ? $payload . "\n" : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}

function w8_is_corrupt($arabic, $english)
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
    w8_section('Result', array('status' => 'FAIL', 'reason' => 'Phrase dictionary not found: ' . $phrases_path));
    exit(1);
}

$phrases = json_decode(file_get_contents($phrases_path), true);
if (!is_array($phrases) || count($phrases) === 0) {
    w8_section('Result', array('status' => 'FAIL', 'reason' => 'Phrase dictionary is empty or invalid JSON.'));
    exit(1);
}

$existing = array();
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
            if (!w8_is_corrupt($row['arabic'], $row['english'])) {
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
    w8_section('Result', array('status' => 'FAIL', 'reason' => $e->getMessage()));
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
        w8_section('Result', array('status' => 'FAIL', 'reason' => 'Could not write application/language/arabic.json.'));
        exit(1);
    }
}

w8_section('Learner dashboard phrase seed summary', $stats);
w8_section('Result', array('status' => 'PASS'));
