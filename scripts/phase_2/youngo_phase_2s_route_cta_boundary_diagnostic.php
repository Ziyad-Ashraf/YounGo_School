<?php
/**
 * Phase 2S route / CTA boundary diagnostic.
 *
 * Read-only checks for the immediate legacy free-enrol / YounGo CTA boundary.
 */

$root = dirname(__DIR__, 2);

function phase_2s_print($title, $payload)
{
    echo "\n== {$title} ==\n";
    echo is_string($payload) ? $payload . "\n" : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

function phase_2s_file_contains($path, $needle)
{
    return is_file($path) && strpos(file_get_contents($path), $needle) !== false;
}

$failures = array();
$warnings = array();

$home = $root . '/application/controllers/Home.php';
$course_page = $root . '/application/views/frontend/youngo/course_page.php';
$course_card = $root . '/application/views/frontend/youngo/course_listing/course_card.php';
$my_wishlist = $root . '/application/views/frontend/youngo/my_wishlist.php';
$wishlist_items = $root . '/application/views/frontend/youngo/wishlist_items.php';

$files = array(
    'Home.php' => is_file($home),
    'course_page.php' => is_file($course_page),
    'course_card.php' => is_file($course_card),
    'my_wishlist.php' => is_file($my_wishlist),
    'wishlist_items.php' => is_file($wishlist_items),
);
phase_2s_print('Required file availability', $files);
foreach ($files as $label => $exists) {
    if (!$exists) {
        $failures[] = "{$label} is missing.";
    }
}

$source_checks = array(
    'free_enrol_guard_helper' => phase_2s_file_contains($home, 'youngo_course_uses_managed_access'),
    'free_enrol_guard_route' => phase_2s_file_contains($home, '$this->youngo_course_uses_managed_access($course_details)'),
    'course_page_managed_access_cta' => phase_2s_file_contains($course_page, '$youngo_is_managed_access'),
    'course_card_managed_access_cta' => phase_2s_file_contains($course_card, '$youngo_card_is_managed_access'),
    'my_wishlist_boundary_state' => phase_2s_file_contains($my_wishlist, 'youngo_wishlist_course_boundary_state'),
    'wishlist_items_boundary_state' => phase_2s_file_contains($wishlist_items, 'youngo_wishlist_course_boundary_state'),
);
phase_2s_print('Source boundary checks', $source_checks);
foreach ($source_checks as $label => $ok) {
    if (!$ok) {
        $failures[] = "{$label} was not found.";
    }
}

$db = @new mysqli('localhost', 'root', '', 'youngo_school');
if ($db->connect_errno) {
    $failures[] = 'Database connection failed: ' . $db->connect_error;
} else {
    $course = array();
    $result = $db->query("SELECT id, title, is_free_course, youngo_access_mode FROM course WHERE id = 1 LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $course = $result->fetch_assoc();
    }
    phase_2s_print('Course 1 access fixture', $course);
    if (empty($course)) {
        $warnings[] = 'Course 1 was not found; fixture-specific CTA check skipped.';
    } elseif ($course['youngo_access_mode'] !== 'subscription_only') {
        $warnings[] = 'Course 1 is not currently subscription_only; fixture expectation changed.';
    }

    $counts = array();
    foreach (array('enrol', 'payment', 'watch_histories', 'watched_duration', 'youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants', 'youngo_checkout_orders', 'youngo_coupon_usages') as $table) {
        $table_result = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($table) . "'");
        if (!$table_result || $table_result->num_rows === 0) {
            $counts[$table] = 'MISSING';
            continue;
        }
        $count_result = $db->query("SELECT COUNT(*) AS c FROM `{$table}`");
        $counts[$table] = $count_result ? (int) $count_result->fetch_assoc()['c'] : 'ERR';
    }
    phase_2s_print('Boundary table counts', $counts);

    foreach (array('payment', 'watch_histories', 'watched_duration', 'youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants', 'youngo_checkout_orders', 'youngo_coupon_usages') as $table) {
        if (isset($counts[$table]) && $counts[$table] !== 0) {
            $warnings[] = "{$table} count is not zero ({$counts[$table]}).";
        }
    }
}

phase_2s_print('Warnings', $warnings);
phase_2s_print('Result', array(
    'status' => empty($failures) ? 'PASS' : 'FAIL',
    'failures' => $failures,
));

exit(empty($failures) ? 0 : 1);
