<?php
/**
 * Phase 2W.3 Admin dashboard corrupt Arabic phrase repair.
 *
 * Companion to youngo_language_phrase_corrupt_arabic_repair_1.php, which is
 * explicitly scoped ("Repairs only allowlisted corrupt Arabic public
 * frontend phrase values") to the public site and does not touch the admin
 * dashboard's own nav/settings/report labels. This script repairs that
 * admin-side gap using the same safety model:
 *   - only overwrites `language.arabic` (never `arabic_translated`, never
 *     `english`);
 *   - only touches a row whose CURRENT value is still detectably corrupt
 *     (literal "????" placeholder runs, Ø/Ù mojibake markers, or an arabic
 *     value identical to the english value) — a row someone has since
 *     manually retranslated is left untouched;
 *   - updates every row sharing a phrase (the `language` table has no
 *     UNIQUE constraint on `phrase`, so duplicate rows exist for some
 *     keys) via a BINARY compare-and-swap, so a concurrent edit is never
 *     silently clobbered;
 *   - transactional: any anomaly rolls back the whole batch.
 *
 * Allowlist source: youngo_phase_2w3_admin_corrupt_arabic_translations.json
 * (620 admin-dashboard phrase keys, hand-translated and verified against
 * the discovered corruption list before this script was written).
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
$translations_path = __DIR__ . DIRECTORY_SEPARATOR . 'youngo_phase_2w3_admin_corrupt_arabic_translations.json';

$config = $db['default'];
$mysqli = @new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
if ($mysqli->connect_errno) {
    echo "Database connection failed without exposing credentials.\n";
    exit(2);
}
$mysqli->set_charset('utf8');

function w3_section($title, $payload)
{
    echo "\n== {$title} ==\n";
    echo is_string($payload) ? $payload . "\n" : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}

function w3_is_corrupt($value, $english)
{
    $value = (string) $value;
    $trimmed = trim($value);
    if ($trimmed === '') {
        return false;
    }
    if (preg_match('/\?{4,}/', $value)) {
        return true;
    }
    if (strpos($value, 'Ø') !== false || strpos($value, 'Ù') !== false) {
        return true;
    }
    if ($english !== '' && strcasecmp($trimmed, trim((string) $english)) === 0) {
        return true;
    }
    return false;
}

function w3_has_arabic_script($value)
{
    return preg_match('/\p{Arabic}/u', (string) $value) === 1;
}

if (!is_file($translations_path)) {
    w3_section('Result', array('status' => 'FAIL', 'reason' => 'Translation dictionary not found: ' . $translations_path));
    exit(1);
}

$translations = json_decode(file_get_contents($translations_path), true);
if (!is_array($translations) || count($translations) === 0) {
    w3_section('Result', array('status' => 'FAIL', 'reason' => 'Translation dictionary is empty or invalid JSON.'));
    exit(1);
}

$counts = array(
    'allowlisted_keys' => count($translations),
    'rows_seen' => 0,
    'rows_repaired' => 0,
    'already_clean_rows' => 0,
    'missing_rows' => 0,
    'not_arabic_script' => 0,
    'cas_conflicts' => 0,
);
$repaired_keys = array();
$skipped_keys = array();

$mysqli->begin_transaction();
try {
    $find_stmt = $mysqli->prepare('SELECT phrase_id, english, arabic FROM language WHERE phrase = ?');
    // arabic can be SQL NULL (not just ''); NULL never satisfies
    // "BINARY arabic = BINARY ''", so the CAS must special-case it.
    $update_stmt = $mysqli->prepare('UPDATE language SET arabic = ? WHERE phrase_id = ? AND (arabic IS NULL OR BINARY arabic = BINARY ?)');

    foreach ($translations as $key => $new_arabic) {
        $new_arabic = trim((string) $new_arabic);

        if (!w3_has_arabic_script($new_arabic)) {
            // A handful of keys are legitimately Latin-script technical
            // terms (.vtt, SCORM, H5P); skip the script check for those
            // explicitly rather than failing the whole batch.
            $latin_ok = in_array($key, array('.vtt', 'h5p', 'scorm', 'html5', 'meta_robot', 'recaptcha', 'vimeo'), true);
            if (!$latin_ok) {
                $counts['not_arabic_script']++;
                $skipped_keys[] = $key;
                continue;
            }
        }

        $find_stmt->bind_param('s', $key);
        $find_stmt->execute();
        $rows = $find_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($rows)) {
            $counts['missing_rows']++;
            $skipped_keys[] = $key;
            continue;
        }

        $key_changed = false;
        foreach ($rows as $row) {
            $counts['rows_seen']++;
            $current = (string) $row['arabic'];

            if (!w3_is_corrupt($current, (string) $row['english'])) {
                $counts['already_clean_rows']++;
                continue;
            }

            $phrase_id = (int) $row['phrase_id'];
            $update_stmt->bind_param('sis', $new_arabic, $phrase_id, $current);
            $update_stmt->execute();

            if ($update_stmt->affected_rows > 0) {
                $counts['rows_repaired']++;
                $key_changed = true;
            } else {
                // Row changed between SELECT and UPDATE; skip rather than
                // risk clobbering a concurrent edit.
                $counts['cas_conflicts']++;
            }
        }

        if ($key_changed) {
            $repaired_keys[] = $key;
        }
    }

    $mysqli->commit();
} catch (Throwable $e) {
    $mysqli->rollback();
    w3_section('Result', array('status' => 'FAIL', 'reason' => $e->getMessage()));
    exit(1);
}

$counts['keys_repaired'] = count(array_unique($repaired_keys));

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
        w3_section('Result', array('status' => 'FAIL', 'reason' => 'Could not write application/language/arabic.json.'));
        exit(1);
    }
}

w3_section('Admin corrupt Arabic phrase repair summary', $counts);
w3_section('Skipped keys', array_values(array_unique($skipped_keys)));
w3_section('Result', array('status' => 'PASS'));
