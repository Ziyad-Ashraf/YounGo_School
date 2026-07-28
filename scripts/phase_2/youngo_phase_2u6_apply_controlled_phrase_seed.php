<?php

/**
 * Phase 2U.6.6.3 controlled frontend phrase seed runner.
 *
 * Writes only the four owner-approved phrase keys. It never calls phrase
 * helpers because those helpers can create missing rows implicitly.
 */

$approvedPhrases = array(
    'primary_navigation' => array(
        'english' => 'Primary navigation',
        'arabic' => 'التنقل الرئيسي',
    ),
    'language_switcher' => array(
        'english' => 'Language switcher',
        'arabic' => 'مبدّل اللغة',
    ),
    'footer_navigation' => array(
        'english' => 'Footer navigation',
        'arabic' => 'روابط التذييل',
    ),
    'showing_results' => array(
        'english' => 'Showing results',
        'arabic' => 'عرض النتائج',
    ),
);

$deferredPhrases = array(
    'youngo_theme_skeleton',
    'youngo_theme_skeleton_not_implemented',
    'allowed_file_types',
);

$mysqli = new mysqli('localhost', 'root', '', 'youngo_school');
if ($mysqli->connect_error) {
    fwrite(STDERR, "Database connection failed: " . $mysqli->connect_error . PHP_EOL);
    exit(1);
}

$mysqli->set_charset('utf8mb4');

function youngo_phase_2u663_fetch_phrase(mysqli $mysqli, $phrase)
{
    $stmt = $mysqli->prepare('SELECT phrase, english, arabic FROM language WHERE phrase = ? LIMIT 1');
    $stmt->bind_param('s', $phrase);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$conflicts = array();
foreach ($approvedPhrases as $phrase => $values) {
    $row = youngo_phase_2u663_fetch_phrase($mysqli, $phrase);
    if (!$row) {
        continue;
    }

    if ((string) $row['english'] !== $values['english'] || (string) $row['arabic'] !== $values['arabic']) {
        $conflicts[] = $phrase;
    }
}

if (!empty($conflicts)) {
    echo "REFUSED controlled phrase seed because approved keys already exist with different values:\n";
    foreach ($conflicts as $phrase) {
        echo "- " . $phrase . "\n";
    }
    exit(1);
}

$deferredFound = array();
foreach ($deferredPhrases as $phrase) {
    if (youngo_phase_2u663_fetch_phrase($mysqli, $phrase)) {
        $deferredFound[] = $phrase;
    }
}

if (!empty($deferredFound)) {
    echo "Notice: deferred phrase keys already exist and will not be modified:\n";
    foreach ($deferredFound as $phrase) {
        echo "- " . $phrase . "\n";
    }
}

$inserted = 0;
$skipped = 0;
$stmt = $mysqli->prepare('INSERT INTO language (phrase, english, arabic) VALUES (?, ?, ?)');

foreach ($approvedPhrases as $phrase => $values) {
    if (youngo_phase_2u663_fetch_phrase($mysqli, $phrase)) {
        $skipped++;
        echo "SKIP existing exact phrase: " . $phrase . "\n";
        continue;
    }

    $stmt->bind_param('sss', $phrase, $values['english'], $values['arabic']);
    if (!$stmt->execute()) {
        fwrite(STDERR, "Failed inserting " . $phrase . ": " . $stmt->error . PHP_EOL);
        exit(1);
    }

    $inserted++;
    echo "INSERTED " . $phrase . "\n";
}

echo "RESULT inserted=" . $inserted . " skipped=" . $skipped . "\n";

