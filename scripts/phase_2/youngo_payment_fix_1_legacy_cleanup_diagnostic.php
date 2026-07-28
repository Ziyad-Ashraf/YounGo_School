<?php
/**
 * PAYMENT.FIX.1 read-only diagnostic.
 *
 * Checks legacy cart/payment cleanup boundaries without printing secrets or
 * modifying the database.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
$checks = array();
$details = array();

function ypfix_add_check(&$checks, $name, $passed, $detail = '')
{
    $checks[$name] = array(
        'status' => $passed ? 'PASS' : 'FAIL',
        'detail' => $detail,
    );
}

function ypfix_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypfix_scalar(mysqli $mysqli, $sql)
{
    $result = $mysqli->query($sql);
    if (!$result) {
        return null;
    }

    $row = $result->fetch_row();
    return $row ? $row[0] : null;
}

function ypfix_table_exists(mysqli $mysqli, $table)
{
    $table = $mysqli->real_escape_string($table);
    return (int) ypfix_scalar(
        $mysqli,
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$table}'"
    ) > 0;
}

$required_files = array(
    'home_controller' => $root . '/application/controllers/Home.php',
    'payment_controller' => $root . '/application/controllers/Payment.php',
    'gateway_view' => $root . '/application/views/payment-global/payment_gateway.php',
    'course_page_view' => $root . '/application/views/frontend/youngo/course_page.php',
    'course_card_view' => $root . '/application/views/frontend/youngo/course_listing/course_card.php',
    'cart_view' => $root . '/application/views/frontend/youngo/shopping_cart_inner_view.php',
    'entitlement_model' => $root . '/application/models/Youngo_entitlement_model.php',
    'entitlement_helper' => $root . '/application/helpers/youngo_entitlement_helper.php',
    'proposed_sql' => $root . '/scripts/phase_2/payment_db_1_gateway_hygiene_proposed.sql',
);

foreach ($required_files as $name => $path) {
    ypfix_add_check($checks, 'file_exists_' . $name, is_file($path), $path);
}

$home_source = ypfix_read($required_files['home_controller']);
$payment_source = ypfix_read($required_files['payment_controller']);
$gateway_source = ypfix_read($required_files['gateway_view']);
$course_page_source = ypfix_read($required_files['course_page_view']);
$course_card_source = ypfix_read($required_files['course_card_view']);
$cart_source = ypfix_read($required_files['cart_view']);
$sql_source = ypfix_read($required_files['proposed_sql']);

ypfix_add_check(
    $checks,
    'managed_courses_blocked_from_legacy_cart',
    strpos($home_source, 'youngo_course_is_legacy_cart_allowed') !== false
        && strpos($home_source, 'youngo_remove_managed_access_courses_from_cart') !== false
        && strpos($home_source, 'handle_buy_now') !== false
);

ypfix_add_check(
    $checks,
    'coupon_direct_enrol_quarantined',
    strpos($home_source, 'Coupon-based checkout is not available yet') !== false
        && strpos($home_source, 'coupon_offer_100_percent') !== false
        && strpos($home_source, 'youngo_remove_managed_access_courses_from_cart') !== false
);

ypfix_add_check(
    $checks,
    'gateway_view_hides_non_egp_active_rows',
    strpos($gateway_source, 'PAYMENT.FIX.1') !== false
        && strpos($gateway_source, '$youngo_gateway_is_currency_safe') !== false
        && strpos($gateway_source, '$gateway_currency === $system_currency') !== false
        && strpos($gateway_source, 'No safe payment gateway is available') !== false
        && strpos($gateway_source, '$$empty_key_of_instructor') === false
);

ypfix_add_check(
    $checks,
    'payment_callback_defensive_gateway_check',
    strpos($payment_source, 'youngo_gateway_payment_check') !== false
        && strpos($payment_source, 'method_exists') !== false
        && strpos($payment_source, '$gateway_currency !== $system_currency') !== false
);

ypfix_add_check(
    $checks,
    'public_youngo_course_detail_cta_suppressed',
    strpos($course_page_source, '$youngo_is_managed_access') !== false
        && strpos($course_page_source, 'subscription_checkout_is_not_available_yet') !== false
        && strpos($course_page_source, "site_url('home/contact_us')") !== false
);

ypfix_add_check(
    $checks,
    'public_youngo_card_cta_suppressed',
    strpos($course_card_source, '$youngo_card_is_managed_access') !== false
        && strpos($course_card_source, 'subscription_not_available_yet') !== false
);

ypfix_add_check(
    $checks,
    'cart_view_keeps_demo_notice',
    strpos($cart_source, 'Demo checkout notice') !== false
        && strpos($cart_source, 'Real payments are not active') !== false
);

ypfix_add_check(
    $checks,
    'proposed_sql_present_not_executed_marker',
    strpos($sql_source, 'STATUS: NOT EXECUTED') !== false
        && strpos($sql_source, 'ROLLBACK') !== false
);

$url_scan_files = array(
    $required_files['payment_controller'],
    $required_files['gateway_view'],
);

$payment_form_dir = $root . '/application/views/payment-global';
if (is_dir($payment_form_dir)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($payment_form_dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (substr($file->getFilename(), -4) === '.php') {
            $url_scan_files[] = $file->getPathname();
        }
    }
}

$hardcoded_url_hits = array();
foreach (array_unique($url_scan_files) as $path) {
    $content = ypfix_read($path);
    if ($content === '') {
        continue;
    }

    if (preg_match_all('#https?://[^\'"\s<>)]+#i', $content, $matches)) {
        $hardcoded_url_hits[$path] = count($matches[0]);
    }
}

$details['hardcoded_payment_url_file_count'] = count($hardcoded_url_hits);
$details['hardcoded_payment_url_hits'] = array_sum($hardcoded_url_hits);
ypfix_add_check($checks, 'hardcoded_payment_urls_noted', count($hardcoded_url_hits) > 0, 'count=' . count($hardcoded_url_hits));

define('BASEPATH', $root . DIRECTORY_SEPARATOR);
defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');
include $root . '/application/config/database.php';

$cfg = $db[$active_group];
$mysqli = @new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database']);
if ($mysqli->connect_errno) {
    ypfix_add_check($checks, 'db_connect_read_only', false, 'connect_failed');
} else {
    $mysqli->set_charset('utf8mb4');
    ypfix_add_check($checks, 'db_connect_read_only', true, 'connected');

    if (ypfix_table_exists($mysqli, 'payment_gateways')) {
        $gateway_total = (int) ypfix_scalar($mysqli, 'SELECT COUNT(*) FROM payment_gateways');
        $gateway_active = (int) ypfix_scalar($mysqli, 'SELECT COUNT(*) FROM payment_gateways WHERE status = 1');
        $gateway_non_egp = (int) ypfix_scalar($mysqli, "SELECT COUNT(*) FROM payment_gateways WHERE status = 1 AND UPPER(COALESCE(currency, '')) <> 'EGP'");
        $gateway_egp_active = (int) ypfix_scalar($mysqli, "SELECT COUNT(*) FROM payment_gateways WHERE status = 1 AND UPPER(COALESCE(currency, '')) = 'EGP'");

        $details['gateway_total'] = $gateway_total;
        $details['gateway_active'] = $gateway_active;
        $details['gateway_active_non_egp'] = $gateway_non_egp;
        $details['gateway_active_egp'] = $gateway_egp_active;

        ypfix_add_check($checks, 'gateway_counts_read', $gateway_total >= 0, 'total=' . $gateway_total . ', active=' . $gateway_active);
        ypfix_add_check($checks, 'unsafe_active_gateways_would_be_hidden', $gateway_non_egp === $gateway_active && $gateway_egp_active === 0, 'active_non_egp=' . $gateway_non_egp);
    } else {
        ypfix_add_check($checks, 'gateway_counts_read', false, 'payment_gateways_missing');
        ypfix_add_check($checks, 'unsafe_active_gateways_would_be_hidden', false, 'payment_gateways_missing');
    }

    if (ypfix_table_exists($mysqli, 'course')) {
        $managed_courses = (int) ypfix_scalar($mysqli, "SELECT COUNT(*) FROM course WHERE youngo_access_mode IN ('subscription_only', 'subscription_and_purchase', 'purchase_only')");
        $course_count = (int) ypfix_scalar($mysqli, 'SELECT COUNT(*) FROM course');
        $details['course_count'] = $course_count;
        $details['managed_course_count'] = $managed_courses;
        ypfix_add_check($checks, 'youngo_courses_managed_in_db', $course_count > 0 && $managed_courses === $course_count, 'managed=' . $managed_courses . ', total=' . $course_count);
    }

    foreach (array('payment', 'coupons', 'youngo_checkout_orders', 'youngo_coupon_usages', 'youngo_course_access', 'youngo_user_subscriptions', 'youngo_manual_grants') as $table) {
        if (ypfix_table_exists($mysqli, $table)) {
            $details[$table . '_count'] = (int) ypfix_scalar($mysqli, 'SELECT COUNT(*) FROM `' . $table . '`');
        }
    }
}

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[] = $name;
    }
}

$result = array(
    'phase' => 'PAYMENT.FIX.1',
    'ok' => empty($failed),
    'checks' => $checks,
    'details' => $details,
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(empty($failed) ? 0 : 1);
