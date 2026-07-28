<?php
/**
 * PAYMENT.CHECKOUT.COVERAGE.FIX.1 diagnostic.
 *
 * Read-only coverage checks for admin coupon checkout visibility and website
 * course checkout CTA exposure. This script performs no DB writes.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$root = dirname(__DIR__, 2);
defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');
defined('BASEPATH') || define('BASEPATH', $root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', $root . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

$checks = array();
$warnings = array();
$details = array(
    'db_writes' => 'none',
    'browser' => 'in_app_browser_unavailable_in_this_session',
);

function ypccf1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypccf1_warn(&$warnings, $name, $detail)
{
    $warnings[$name] = (string) $detail;
}

function ypccf1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function ypccf1_contains($source, $needle)
{
    return is_string($source) && strpos($source, $needle) !== false;
}

function ypccf1_contains_all($source, $needles)
{
    foreach ($needles as $needle) {
        if (!ypccf1_contains($source, $needle)) {
            return false;
        }
    }
    return true;
}

function ypccf1_db_config()
{
    $db = array();
    $active_group = 'default';
    require APPPATH . 'config/database.php';

    return isset($db[$active_group]) ? $db[$active_group] : array();
}

function ypccf1_connect()
{
    $config = ypccf1_db_config();
    $host = isset($config['hostname']) ? $config['hostname'] : 'localhost';
    $port = null;
    if (strpos($host, ':') !== false && substr_count($host, ':') === 1) {
        list($host, $port) = explode(':', $host, 2);
        $port = (int) $port;
    }

    $mysqli = @new mysqli(
        $host,
        isset($config['username']) ? $config['username'] : '',
        isset($config['password']) ? $config['password'] : '',
        isset($config['database']) ? $config['database'] : '',
        $port ?: null
    );

    if ($mysqli->connect_errno) {
        return null;
    }

    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

function ypccf1_query($mysqli, $sql)
{
    if (!$mysqli) {
        return array('error' => 'db_unavailable');
    }

    if (preg_match('/\b(insert|update|delete|replace|alter|drop|create|truncate|grant|revoke|set)\b/i', $sql)) {
        throw new RuntimeException('Write SQL blocked by diagnostic wrapper.');
    }

    $result = $mysqli->query($sql);
    if (!$result) {
        return array('error' => $mysqli->error);
    }

    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

function ypccf1_table_exists($mysqli, $table)
{
    if (!$mysqli || !preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    $rows = ypccf1_query($mysqli, "SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return is_array($rows) && !isset($rows['error']) && count($rows) > 0;
}

function ypccf1_count($mysqli, $table)
{
    if (!ypccf1_table_exists($mysqli, $table)) {
        return null;
    }

    $rows = ypccf1_query($mysqli, 'SELECT COUNT(*) AS c FROM `' . $table . '`');
    return isset($rows[0]['c']) ? (int) $rows[0]['c'] : null;
}

function ypccf1_print_section($title, $data)
{
    echo "\n== " . $title . " ==\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

$files = array(
    'routes' => $root . '/application/config/routes.php',
    'htaccess' => $root . '/.htaccess',
    'main_admin_controller' => $root . '/application/controllers/Admin.php',
    'admin_controller' => $root . '/application/controllers/Youngo_checkout_coupon_usage.php',
    'backend_index' => $root . '/application/views/backend/index.php',
    'admin_view' => $root . '/application/views/backend/admin/youngo_checkout_coupon_usage.php',
    'admin_navigation' => $root . '/application/views/backend/admin/navigation.php',
    'checkout_model' => $root . '/application/models/Youngo_checkout_model.php',
    'checkout_controller' => $root . '/application/controllers/Youngo_checkout.php',
    'instapay_model' => $root . '/application/models/Youngo_instapay_payment_model.php',
    'entitlement_write_model' => $root . '/application/models/Youngo_entitlement_write_model.php',
    'payment_webhook_controller' => $root . '/application/controllers/Youngo_payment_webhook.php',
    'payment_return_controller' => $root . '/application/controllers/Youngo_payment_return.php',
    'paymob_config' => $root . '/application/config/youngo_paymob.php',
    'paymob_config_local' => $root . '/application/config/youngo_paymob.local.php',
    'paymob_config_library' => $root . '/application/libraries/Youngo_paymob_config.php',
    'cta_helper' => $root . '/application/helpers/youngo_checkout_cta_helper.php',
    'course_card_view' => $root . '/application/views/frontend/youngo/course_listing/course_card.php',
    'wishlist_view' => $root . '/application/views/frontend/youngo/my_wishlist.php',
    'wishlist_items_view' => $root . '/application/views/frontend/youngo/wishlist_items.php',
    'featured_courses_view' => $root . '/application/views/frontend/youngo/home_sections/featured_courses.php',
    'common_helper' => $root . '/application/helpers/common_helper.php',
    'subscriptions_view' => $root . '/application/views/frontend/youngo/subscriptions.php',
    'checkout_order_view' => $root . '/application/views/frontend/youngo/checkout_order.php',
);

$source = array();
foreach ($files as $key => $path) {
    $source[$key] = ypccf1_read($path);
    ypccf1_check($checks, 'file_exists_' . $key, $source[$key] !== '', $path);
}

ypccf1_check($checks, 'admin_coupon_usage_route_exists', ypccf1_contains($source['routes'], "admin/youngo/checkout-coupon-usage"), 'Expected /admin/youngo/checkout-coupon-usage route.');
ypccf1_check($checks, 'admin_coupon_usage_route_targets_real_admin_layout', ypccf1_contains($source['routes'], "\$route['admin/youngo/checkout-coupon-usage'] = 'youngo_checkout_coupon_usage/index';"), 'Route targets the dedicated controller, which renders the page through backend/index.');
ypccf1_check($checks, 'admin_coupon_usage_no_physical_fallback', !is_file($root . '/admin/youngo/checkout-coupon-usage/index.php') && !is_dir($root . '/admin/youngo/checkout-coupon-usage'), 'No physical fallback endpoint can bypass CodeIgniter admin layout.');
ypccf1_check($checks, 'backend_index_loads_admin_shell', ypccf1_contains_all($source['backend_index'], array(
    "include 'header.php'",
    "include \$logged_in_user_role.'/'.'navigation.php'",
    "include \$logged_in_user_role.'/'.\$page_name.'.php'",
    'content-page',
)), 'backend/index provides the real admin header, sidebar, and content include path.');
ypccf1_check($checks, 'admin_coupon_usage_controller_uses_backend_index', ypccf1_contains_all($source['admin_controller'], array(
    'class Youngo_checkout_coupon_usage',
    'public function index()',
    "'page_name' => 'youngo_checkout_coupon_usage'",
    "'page_title' => 'Coupon Checkout Usage'",
    "\$this->load->view('backend/index'",
)), 'Dedicated controller loads the report through the normal backend layout.');
ypccf1_check($checks, 'admin_coupon_usage_controller_read_only', ypccf1_contains_all($source['admin_controller'], array(
    'class Youngo_checkout_coupon_usage',
    "request_method() !== 'GET'",
    'get_admin_coupon_checkout_usage',
    'count_admin_coupon_checkout_usage_by_filter',
    'require_root_admin',
)), 'Controller is root-admin GET-only and uses read-only model helpers.');
ypccf1_check($checks, 'admin_coupon_usage_view_exists', ypccf1_contains_all($source['admin_view'], array(
    'All coupon checkout usage',
    'Zero amount coupon only',
    'Course purchases',
    'Subscription purchases',
    'access_reference',
    'selected_payment_method',
    'payment_gateway',
)) && !preg_match('/<(?:!doctype|html|body)\b/i', $source['admin_view']) && !ypccf1_contains($source['admin_view'], 'YounGo Admin'), 'Report inner view contains columns/filters and no standalone HTML shell.');
ypccf1_check($checks, 'admin_navigation_link_exists', ypccf1_contains_all($source['admin_navigation'], array(
    'youngo_checkout_coupon_usage',
    'admin/youngo/checkout-coupon-usage',
    'Coupon Checkout Usage',
)), 'Root-admin YounGo menu links the report.');
ypccf1_check($checks, 'checkout_model_report_helpers_exist', ypccf1_contains_all($source['checkout_model'], array(
    'get_admin_coupon_checkout_usage',
    'count_admin_coupon_checkout_usage_by_filter',
    'admin_coupon_checkout_usage_query',
    'safe_admin_coupon_checkout_usage_row',
)), 'Checkout model includes read-only report helpers.');

ypccf1_check($checks, 'backend_course_and_subscription_orders_supported', ypccf1_contains_all($source['checkout_model'], array(
    "order_type' => 'course_purchase'",
    "order_type' => 'subscription_purchase'",
    'create_draft_order_for_subscription',
    'can_start_checkout',
)), 'Backend still supports course and subscription checkout order creation.');
ypccf1_check($checks, 'zero_coupon_flow_still_available', ypccf1_contains_all($source['routes'] . $source['checkout_controller'] . $source['checkout_model'], array(
    'zero-coupon/complete',
    'complete_zero_amount_coupon',
    'complete_zero_amount_coupon_order',
    'selected_payment_method',
    'zero_amount_coupon',
)), 'Zero-amount coupon route, controller action, and model path are present.');
ypccf1_check($checks, 'instapay_pending_upload_safety_present', ypccf1_contains_all($source['instapay_model'], array(
    "status' => 'pending_review'",
    'pending_review_submission_created',
    'can_create_submission_for_order',
    'zero_amount_coupon',
)), 'Instapay upload creates pending review and rejects zero-coupon orders.');
ypccf1_check($checks, 'instapay_approval_safety_present', ypccf1_contains_all($source['instapay_model'] . $source['entitlement_write_model'], array(
    'approve_submission',
    "where('status', 'pending_review')",
    'instapay_manual',
    'issue_instapay_manual_course_access',
)), 'Instapay approval remains pending-review gated and entitlement-backed.');
ypccf1_check($checks, 'paymob_fail_closed_sources_present', ypccf1_contains_all($source['payment_webhook_controller'] . $source['payment_return_controller'], array(
    'webhook_testing_enabled',
    'validated_no_write',
    'marks_paid',
    'false',
)), 'Paymob webhook/return remain no-write/fail-closed.');
ypccf1_check($checks, 'card_wallet_placeholders_disabled', ypccf1_contains_all($source['checkout_order_view'], array(
    'data-youngo-disabled-payment-methods',
    'Cards',
    'Digital Wallets',
    'disabled',
    'Not available yet',
)), 'Checkout page still renders disabled card/wallet placeholders.');

ypccf1_check($checks, 'course_listing_uses_checkout_cta_helper', ypccf1_contains_all($source['course_card_view'], array(
    'youngo_checkout_cta_decision',
    'data-youngo-checkout-cta="local-course-card"',
    '$youngo_card_show_subscription_cta',
)), 'Course listing card uses helper-backed checkout before subscription fallback.');
ypccf1_check($checks, 'wishlist_uses_checkout_cta_helper', ypccf1_contains_all($source['wishlist_view'], array(
    'youngo_checkout_cta_decision',
    'data-youngo-checkout-cta="local-wishlist"',
    '$wishlist_show_subscription_cta',
)), 'Wishlist page uses helper-backed checkout before subscription fallback.');
ypccf1_check($checks, 'wishlist_partial_uses_checkout_cta_helper', ypccf1_contains_all($source['wishlist_items_view'], array(
    'youngo_checkout_cta_decision',
    'data-youngo-checkout-cta="local-wishlist-item"',
    '$wishlist_item_show_subscription_cta',
)), 'Wishlist partial uses helper-backed checkout before subscription fallback.');
ypccf1_check($checks, 'featured_courses_uses_checkout_cta_helper', ypccf1_contains_all($source['featured_courses_view'] . $source['common_helper'], array(
    'youngo_checkout_cta_decision',
    'data-youngo-checkout-cta="local-featured-course"',
    "'youngo_access_mode'",
    "'discounted_price'",
)), 'Homepage featured course cards retain eligibility fields and use the CTA helper.');
ypccf1_check($checks, 'subscription_checkout_cta_remains', ypccf1_contains_all($source['subscriptions_view'], array(
    'youngo/checkout/subscription/start/',
    'data-youngo-checkout-cta="local-subscription-plan"',
)), 'Subscription plan checkout CTA remains available.');

if ($source['paymob_config_library'] !== '') {
    require_once $files['paymob_config_library'];
}
if (class_exists('Youngo_paymob_config')) {
    $paymob = new Youngo_paymob_config(array('load_local_override' => true));
    $summary = $paymob->get_safe_diagnostic_summary();
    $details['paymob_summary'] = array(
        'enabled' => !empty($summary['enabled']),
        'network_enabled' => !empty($summary['network_enabled']),
        'sandbox_network_testing_enabled' => !empty($summary['sandbox_network_testing_enabled']),
        'webhook_testing_enabled' => !empty($summary['webhook_testing_enabled']),
        'checkout_routes_enabled' => !empty($summary['checkout_routes_enabled']),
        'checkout_local_testing_enabled' => !empty($summary['checkout_local_testing_enabled']),
        'checkout_cta_enabled' => !empty($summary['checkout_cta_enabled']),
        'local_override_loaded' => !empty($summary['local_override_loaded']),
    );
    ypccf1_check($checks, 'paymob_runtime_network_disabled', empty($summary['enabled']) && empty($summary['network_enabled']) && empty($summary['sandbox_network_testing_enabled']), 'Paymob execution and network flags remain disabled.');
} else {
    ypccf1_check($checks, 'paymob_runtime_network_disabled', false, 'Youngo_paymob_config could not be loaded.');
}

$mysqli = ypccf1_connect();
ypccf1_check($checks, 'db_connected', $mysqli !== null, $mysqli ? 'Connected without printing credentials.' : 'DB connection unavailable.');

$protected_tables = array(
    'youngo_checkout_orders',
    'youngo_coupon_usages',
    'youngo_course_access',
    'youngo_user_subscriptions',
    'youngo_instapay_payment_submissions',
    'youngo_payment_transactions',
    'payment',
    'enrol',
);
$before_counts = array();
$after_counts = array();
foreach ($protected_tables as $table) {
    $before_counts[$table] = ypccf1_count($mysqli, $table);
}

if ($mysqli) {
    $details['tables'] = array();
    foreach (array('youngo_checkout_orders', 'youngo_coupon_usages', 'youngo_course_access', 'youngo_user_subscriptions', 'youngo_instapay_payment_submissions') as $table) {
        $details['tables'][$table] = ypccf1_table_exists($mysqli, $table);
    }

    $order_types = ypccf1_query($mysqli, "SELECT order_type, status, COUNT(*) AS total FROM youngo_checkout_orders GROUP BY order_type, status ORDER BY order_type, status");
    $details['checkout_orders_by_type_status'] = is_array($order_types) && !isset($order_types['error']) ? $order_types : array();

    $coupon_usage_sql = "SELECT COUNT(*) AS total FROM youngo_checkout_orders o LEFT JOIN youngo_coupon_usages cu ON cu.checkout_order_id = o.id WHERE (cu.id IS NOT NULL OR o.coupon_id IS NOT NULL OR TRIM(COALESCE(o.coupon_code, '')) <> '')";
    $zero_coupon_sql = "SELECT COUNT(*) AS total FROM youngo_checkout_orders o LEFT JOIN youngo_coupon_usages cu ON cu.checkout_order_id = o.id WHERE (cu.id IS NOT NULL OR o.coupon_id IS NOT NULL OR TRIM(COALESCE(o.coupon_code, '')) <> '') AND (o.selected_payment_method = 'zero_amount_coupon' OR o.payment_gateway = 'zero_amount_coupon' OR (o.total_amount = '0.00' AND o.coupon_id IS NOT NULL))";
    $linked_usage_sql = "SELECT COUNT(*) AS total FROM youngo_coupon_usages WHERE checkout_order_id IS NOT NULL AND checkout_order_id > 0";
    $course_access_sql = "SELECT COUNT(*) AS total FROM youngo_course_access WHERE checkout_order_id IS NOT NULL AND checkout_order_id > 0";
    $subscription_access_sql = "SELECT COUNT(*) AS total FROM youngo_user_subscriptions WHERE checkout_order_id IS NOT NULL AND checkout_order_id > 0";

    $coupon_rows = ypccf1_query($mysqli, $coupon_usage_sql);
    $zero_rows = ypccf1_query($mysqli, $zero_coupon_sql);
    $linked_usage_rows = ypccf1_query($mysqli, $linked_usage_sql);
    $course_access_rows = ypccf1_query($mysqli, $course_access_sql);
    $subscription_access_rows = ypccf1_query($mysqli, $subscription_access_sql);

    $details['report_query_counts'] = array(
        'coupon_checkout_usage' => isset($coupon_rows[0]['total']) ? (int) $coupon_rows[0]['total'] : null,
        'zero_amount_coupon_usage' => isset($zero_rows[0]['total']) ? (int) $zero_rows[0]['total'] : null,
        'coupon_usage_rows_with_checkout_order' => isset($linked_usage_rows[0]['total']) ? (int) $linked_usage_rows[0]['total'] : null,
        'course_access_rows_with_checkout_order' => isset($course_access_rows[0]['total']) ? (int) $course_access_rows[0]['total'] : null,
        'subscription_rows_with_checkout_order' => isset($subscription_access_rows[0]['total']) ? (int) $subscription_access_rows[0]['total'] : null,
    );

    ypccf1_check($checks, 'admin_report_query_executable', !isset($coupon_rows['error']) && !isset($zero_rows['error']), 'Coupon usage report queries execute read-only.');
    if (isset($details['report_query_counts']['coupon_checkout_usage']) && $details['report_query_counts']['coupon_checkout_usage'] > 0) {
        ypccf1_check($checks, 'admin_report_query_returns_existing_fixture_records', $details['report_query_counts']['zero_amount_coupon_usage'] > 0, 'Existing live coupon fixtures include zero-amount coupon records.');
    } else {
        ypccf1_warn($warnings, 'admin_report_query_no_live_coupon_fixtures', 'No live coupon checkout rows were found; the report query executed but had no data to display.');
        ypccf1_check($checks, 'admin_report_query_returns_existing_fixture_records', true, 'Skipped because no live coupon fixtures exist.');
    }
}

foreach ($protected_tables as $table) {
    $after_counts[$table] = ypccf1_count($mysqli, $table);
}
$details['protected_counts_before'] = $before_counts;
$details['protected_counts_after'] = $after_counts;
ypccf1_check($checks, 'no_db_writes_detected', $before_counts === $after_counts, 'Protected counts were identical before and after read-only checks.');

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

ypccf1_print_section('checks', $checks);
ypccf1_print_section('warnings', $warnings);
ypccf1_print_section('details', $details);

if (!empty($failed)) {
    echo "\nRESULT: FAIL\n";
    exit(1);
}

echo "\nRESULT: PASS\n";
exit(0);
