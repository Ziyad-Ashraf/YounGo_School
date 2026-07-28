<?php
/**
 * Phase 2W.2 Admin dashboard Arabic phrase seed.
 *
 * Fills the admin-dashboard phrase gap identified by measuring every
 * get_phrase() key used under application/views/backend/** (+ Admin.php)
 * against the live `language` table: rows that don't exist yet (never
 * lazy-inserted by a page visit) and rows whose `arabic` column is empty.
 *
 * Idempotent by default: only writes phrases that are still missing/empty
 * unless --force is passed. Mirrors youngo_phase_2u4_seed_arabic_phrases.php
 * (transactional update, then regenerate application/language/arabic.json
 * from the full table so the file stays in sync with the DB).
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

$force = in_array('--force', $argv, true);
$write_file = !in_array('--no-file', $argv, true);
$translations_path = in_array('--translations', $argv, true)
    ? $argv[array_search('--translations', $argv, true) + 1]
    : __DIR__ . DIRECTORY_SEPARATOR . 'youngo_phase_2w2_admin_arabic_translations.json';

$config = $db['default'];
$mysqli = @new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
if ($mysqli->connect_errno) {
    echo "Database connection failed without exposing credentials.\n";
    exit(2);
}
$mysqli->set_charset('utf8');

function w2_section($title, $payload)
{
    echo "\n== {$title} ==\n";
    echo is_string($payload) ? $payload . "\n" : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}

if (!is_file($translations_path)) {
    w2_section('Result', array('status' => 'FAIL', 'reason' => 'Translation dictionary not found: ' . $translations_path));
    exit(1);
}

$translations = json_decode(file_get_contents($translations_path), true);
if (!is_array($translations) || count($translations) === 0) {
    w2_section('Result', array('status' => 'FAIL', 'reason' => 'Translation dictionary is empty or invalid JSON.'));
    exit(1);
}

$result = $mysqli->query("SELECT phrase_id, phrase, english, arabic FROM language");
if (!$result) {
    w2_section('Result', array('status' => 'FAIL', 'reason' => 'Could not read language rows.'));
    exit(1);
}

$existing = array();
while ($row = $result->fetch_assoc()) {
    $existing[$row['phrase']] = $row;
}

$stats = array(
    'translations_available' => count($translations),
    'inserted' => 0,
    'updated' => 0,
    'skipped_non_empty' => 0,
);

$inserts = array(); // phrase => array(english, arabic)
$updates = array(); // phrase_id => arabic

foreach ($translations as $phrase => $arabic) {
    $arabic = trim((string) $arabic);
    if ($arabic === '') {
        continue;
    }

    if (!isset($existing[$phrase])) {
        $inserts[] = array($phrase, ucfirst(str_replace('_', ' ', $phrase)), $arabic);
        continue;
    }

    $current = trim((string) $existing[$phrase]['arabic']);
    if ($current !== '' && !$force) {
        $stats['skipped_non_empty']++;
        continue;
    }

    $updates[] = array((int) $existing[$phrase]['phrase_id'], $arabic);
}

$mysqli->begin_transaction();
try {
    if (!empty($inserts)) {
        $insert_stmt = $mysqli->prepare("INSERT INTO language (phrase, english, arabic) VALUES (?, ?, ?)");
        if (!$insert_stmt) {
            throw new Exception('Could not prepare phrase insert.');
        }
        foreach ($inserts as $row) {
            $insert_stmt->bind_param('sss', $row[0], $row[1], $row[2]);
            if (!$insert_stmt->execute()) {
                throw new Exception('Could not insert phrase: ' . $row[0]);
            }
            $stats['inserted']++;
        }
    }

    if (!empty($updates)) {
        $update_stmt = $mysqli->prepare("UPDATE language SET arabic = ? WHERE phrase_id = ?");
        if (!$update_stmt) {
            throw new Exception('Could not prepare phrase update.');
        }
        foreach ($updates as $row) {
            $update_stmt->bind_param('si', $row[1], $row[0]);
            if (!$update_stmt->execute()) {
                throw new Exception('Could not update phrase id: ' . $row[0]);
            }
            $stats['updated']++;
        }
    }

    $mysqli->commit();
} catch (Throwable $e) {
    $mysqli->rollback();
    w2_section('Result', array('status' => 'FAIL', 'reason' => $e->getMessage()));
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
        w2_section('Result', array('status' => 'FAIL', 'reason' => 'Could not write application/language/arabic.json.'));
        exit(1);
    }
}

w2_section('Admin Arabic phrase seed summary', $stats);
w2_section('Result', array('status' => 'PASS'));
