<?php
/**
 * PAYMENT.CHECKOUT.COVERAGE.AUDIT.1 diagnostic.
 *
 * Read-only checkout/payment coverage audit for YounGo course/subscription
 * checkout, coupon snapshots, zero-amount coupon completion, and manual
 * Instapay review availability. This script performs no DB writes.
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

$db_config = isset($db['default']) ? $db['default'] : array();
$mysqli = @new mysqli(
    isset($db_config['hostname']) ? $db_config['hostname'] : 'localhost',
    isset($db_config['username']) ? $db_config['username'] : '',
    isset($db_config['password']) ? $db_config['password'] : '',
    isset($db_config['database']) ? $db_config['database'] : ''
);

$checks = array();
$warnings = array();
$details = array();

function yccca1_add_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function yccca1_add_warning(&$warnings, $name, $detail)
{
    $warnings[$name] = (string) $detail;
}

function yccca1_read($path)
{
    return is_file($path) ? file_get_contents($path) : '';
}

function yccca1_contains($source, $needle)
{
    return is_string($source) && strpos($source, $needle) !== false;
}

function yccca1_contains_all($source, $needles)
{
    foreach ($needles as $needle) {
        if (!yccca1_contains($source, $needle)) {
            return false;
        }
    }
    return true;
}

function yccca1_query($mysqli, $sql, $params = array())
{
    if (!$mysqli || $mysqli->connect_errno) {
        return array('error' => 'DB connection unavailable.');
    }

    if (preg_match('/\b(insert|update|delete|replace|alter|drop|create|truncate|grant|revoke|set)\b/i', $sql)) {
        throw new Exception('Write SQL blocked by diagnostic wrapper.');
    }

    if (empty($params)) {
        $result = $mysqli->query($sql);
    } else {
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            return array('error' => $mysqli->error);
        }
        $types = str_repeat('s', count($params));
        $refs = array($types);
        foreach ($params as $key => $value) {
            $refs[] = &$params[$key];
        }
        call_user_func_array(array($stmt, 'bind_param'), $refs);
        $stmt->execute();
        $result = $stmt->get_result();
    }

    if (!$result) {
        return array('error' => $mysqli->error);
    }

    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function yccca1_table_exists($mysqli, $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }
    $rows = yccca1_query($mysqli, "SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return is_array($rows) && empty($rows['error']) && count($rows) > 0;
}

function yccca1_columns($mysqli, $table)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !yccca1_table_exists($mysqli, $table)) {
        return array();
    }
    $rows = yccca1_query($mysqli, 'SHOW COLUMNS FROM `' . $table . '`');
    if (!is_array($rows) || isset($rows['error'])) {
        return array();
    }
    $columns = array();
    foreach ($rows as $row) {
        if (isset($row['Field'])) {
            $columns[] = $row['Field'];
        }
    }
    return $columns;
}

function yccca1_has_columns($columns, $required)
{
    foreach ($required as $column) {
        if (!in_array($column, $columns, true)) {
            return false;
        }
    }
    return true;
}

function yccca1_count($mysqli, $table)
{
    if (!yccca1_table_exists($mysqli, $table)) {
        return null;
    }
    $rows = yccca1_query($mysqli, 'SELECT COUNT(*) AS c FROM `' . $table . '`');
    return isset($rows[0]['c']) ? (int) $rows[0]['c'] : null;
}

function yccca1_group_count($mysqli, $table, $fields)
{
    if (!yccca1_table_exists($mysqli, $table)) {
        return array();
    }

    $columns = yccca1_columns($mysqli, $table);
    $safe_fields = array();
    foreach ($fields as $field) {
        if (in_array($field, $columns, true)) {
            $safe_fields[] = $field;
        }
    }
    if (empty($safe_fields)) {
        return array();
    }

    $select_parts = array();
    $group_parts = array();
    foreach ($safe_fields as $field) {
        $select_parts[] = '`' . $field . '`';
        $group_parts[] = '`' . $field . '`';
    }
    $sql = 'SELECT ' . implode(', ', $select_parts) . ', COUNT(*) AS total FROM `' . $table . '` GROUP BY ' . implode(', ', $group_parts) . ' ORDER BY total DESC';
    $rows = yccca1_query($mysqli, $sql);
    return is_array($rows) && !isset($rows['error']) ? $rows : array();
}

function yccca1_print_section($title, $data)
{
    echo "\n== " . $title . " ==\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

$connected = !$mysqli->connect_errno;
if ($connected) {
    $mysqli->set_charset('utf8');
}

yccca1_add_check($checks, 'db_connected', $connected, $connected ? 'Connected without printing credentials.' : 'DB connection failed without printing credentials.');

$files = array(
    'routes' => $root . '/application/config/routes.php',
    'checkout_controller' => $root . '/application/controllers/Youngo_checkout.php',
    'instapay_admin_controller' => $root . '/application/controllers/Youngo_instapay_payments.php',
    'payment_webhook_controller' => $root . '/application/controllers/Youngo_payment_webhook.php',
    'payment_return_controller' => $root . '/application/controllers/Youngo_payment_return.php',
    'checkout_model' => $root . '/application/models/Youngo_checkout_model.php',
    'coupon_evaluator_model' => $root . '/application/models/Youngo_coupon_evaluator_model.php',
    'instapay_model' => $root . '/application/models/Youngo_instapay_payment_model.php',
    'payment_model' => $root . '/application/models/Youngo_payment_model.php',
    'entitlement_write_model' => $root . '/application/models/Youngo_entitlement_write_model.php',
    'cta_helper' => $root . '/application/helpers/youngo_checkout_cta_helper.php',
    'checkout_order_view' => $root . '/application/views/frontend/youngo/checkout_order.php',
    'subscriptions_view' => $root . '/application/views/frontend/youngo/subscriptions.php',
    'course_page_view' => $root . '/application/views/frontend/youngo/course_page.php',
    'course_card_view' => $root . '/application/views/frontend/youngo/course_listing/course_card.php',
    'wishlist_view' => $root . '/application/views/frontend/youngo/my_wishlist.php',
    'wishlist_items_view' => $root . '/application/views/frontend/youngo/wishlist_items.php',
    'instapay_admin_list_view' => $root . '/application/views/backend/admin/youngo_instapay_payments.php',
    'instapay_admin_detail_view' => $root . '/application/views/backend/admin/youngo_instapay_payment_view.php',
    'course_entitlement_summary_view' => $root . '/application/views/backend/admin/youngo_course_entitlement_summary.php',
    'user_entitlement_summary_view' => $root . '/application/views/backend/admin/youngo_user_entitlement_summary.php',
);

$source = array();
foreach ($files as $key => $path) {
    $source[$key] = yccca1_read($path);
    yccca1_add_check($checks, 'file_exists_' . $key, is_file($path), $path);
}

$route_needles = array(
    'course_start' => "\$route['youngo/checkout/start/(:num)'] = 'youngo_checkout/start/$1';",
    'subscription_start' => "\$route['youngo/checkout/subscription/start/(:num)'] = 'youngo_checkout/start_subscription/$1';",
    'order' => "\$route['youngo/checkout/order/(:any)'] = 'youngo_checkout/order/$1';",
    'coupon_apply' => "\$route['youngo/checkout/coupon/apply/(:any)'] = 'youngo_checkout/apply_coupon/$1';",
    'coupon_clear' => "\$route['youngo/checkout/coupon/clear/(:any)'] = 'youngo_checkout/clear_coupon/$1';",
    'zero_coupon_complete' => "\$route['youngo/checkout/zero-coupon/complete/(:any)'] = 'youngo_checkout/complete_zero_amount_coupon/$1';",
    'instapay_submit' => "\$route['youngo/checkout/instapay/submit/(:any)'] = 'youngo_checkout/submit_instapay/$1';",
    'admin_instapay_list' => "\$route['admin/youngo/instapay-payments'] = 'youngo_instapay_payments/index';",
    'admin_instapay_approve' => "\$route['admin/youngo/instapay-payments/(:num)/approve'] = 'youngo_instapay_payments/approve/$1';",
    'admin_instapay_reject' => "\$route['admin/youngo/instapay-payments/(:num)/reject'] = 'youngo_instapay_payments/reject/$1';",
    'paymob_webhook' => "\$route['payment/paymob/webhook'] = 'youngo_payment_webhook/paymob';",
    'paymob_return' => "\$route['payment/paymob/return'] = 'youngo_payment_return/paymob';",
);

$route_status = array();
foreach ($route_needles as $name => $needle) {
    $route_status[$name] = yccca1_contains($source['routes'], $needle);
}
yccca1_add_check($checks, 'checkout_routes_present', !in_array(false, $route_status, true), json_encode($route_status));

$checkout_methods = array('start_course', 'start_subscription', 'order', 'return', 'status', 'apply_coupon', 'clear_coupon', 'complete_zero_amount_coupon', 'submit_instapay');
$method_status = array();
foreach ($checkout_methods as $method) {
    $method_status[$method] = preg_match('/function\s+' . preg_quote($method, '/') . '\s*\(/', $source['checkout_controller']) === 1;
}
yccca1_add_check($checks, 'checkout_controller_actions_present', !in_array(false, $method_status, true), json_encode($method_status));

$supported_types = array(
    'checkout_order_creation' => array(
        'course_purchase' => yccca1_contains($source['checkout_model'], "'order_type' => 'course_purchase'"),
        'subscription_purchase' => yccca1_contains($source['checkout_model'], "'order_type' => 'subscription_purchase'"),
    ),
    'coupon_evaluator_item_types' => array(
        'course' => yccca1_contains($source['coupon_evaluator_model'], "array('course', 'subscription')"),
        'subscription' => yccca1_contains($source['coupon_evaluator_model'], "array('course', 'subscription')"),
    ),
    'zero_coupon_completion' => array(
        'course_purchase' => yccca1_contains($source['checkout_model'], 'issue_zero_amount_coupon_course_access'),
        'subscription_purchase' => yccca1_contains($source['checkout_model'], 'issue_zero_amount_coupon_subscription_access'),
    ),
    'instapay_approval' => array(
        'course_purchase' => yccca1_contains($source['instapay_model'], 'issue_instapay_manual_course_access'),
        'subscription_purchase' => yccca1_contains($source['instapay_model'], 'issue_instapay_manual_subscription_access'),
    ),
    'paymob_entitlement' => array(
        'course_purchase' => yccca1_contains($source['payment_model'], 'issue_course_purchase_access'),
        'subscription_purchase' => yccca1_contains($source['payment_model'], 'issue_subscription_purchase') && yccca1_contains($source['payment_model'], "Only course purchase checkout orders are supported in this phase.") === false,
    ),
);
yccca1_add_check($checks, 'backend_course_and_subscription_order_creation_present', $supported_types['checkout_order_creation']['course_purchase'] && $supported_types['checkout_order_creation']['subscription_purchase']);
yccca1_add_check($checks, 'coupon_evaluator_course_and_subscription_present', $supported_types['coupon_evaluator_item_types']['course'] && $supported_types['coupon_evaluator_item_types']['subscription']);
yccca1_add_check($checks, 'zero_coupon_course_and_subscription_issuance_present', $supported_types['zero_coupon_completion']['course_purchase'] && $supported_types['zero_coupon_completion']['subscription_purchase']);
yccca1_add_check($checks, 'instapay_course_and_subscription_approval_present', $supported_types['instapay_approval']['course_purchase'] && $supported_types['instapay_approval']['subscription_purchase']);

if (!$supported_types['paymob_entitlement']['subscription_purchase']) {
    yccca1_add_warning($warnings, 'paymob_subscription_entitlement_gap', 'Paymob paid entitlement issuance still routes only to course purchase access.');
}

$website_coverage = array(
    'subscriptions_view_links_to_subscription_checkout' => yccca1_contains($source['subscriptions_view'], 'youngo/checkout/subscription/start/'),
    'course_detail_uses_checkout_cta_helper' => yccca1_contains_all($source['course_page_view'], array('youngo_checkout_cta_decision($course_details', 'data-youngo-checkout-cta="local-course-detail"')),
    'course_cta_helper_targets_course_checkout' => yccca1_contains($source['cta_helper'], "youngo/checkout/start/' . \$course_id"),
    'course_listing_links_to_course_checkout' => yccca1_contains($source['course_card_view'], 'youngo/checkout/start'),
    'wishlist_links_to_course_checkout' => yccca1_contains($source['wishlist_view'], 'youngo/checkout/start') || yccca1_contains($source['wishlist_items_view'], 'youngo/checkout/start'),
    'checkout_view_has_coupon_apply_clear' => yccca1_contains_all($source['checkout_order_view'], array('/checkout/coupon/apply/', '/checkout/coupon/clear/')),
    'checkout_view_has_zero_coupon_complete' => yccca1_contains($source['checkout_order_view'], '/checkout/zero-coupon/complete/'),
    'checkout_view_has_instapay_upload' => yccca1_contains_all($source['checkout_order_view'], array('data-youngo-instapay-checkout-panel', 'instapay_screenshot')),
    'checkout_view_has_disabled_card_wallet_placeholders' => yccca1_contains_all($source['checkout_order_view'], array('Cards', 'Digital Wallets', 'Not available yet')),
);
if (!$website_coverage['course_listing_links_to_course_checkout']) {
    yccca1_add_warning($warnings, 'course_listing_checkout_cta_gap', 'Course listing cards do not link purchase-capable courses directly to /youngo/checkout/start/{course_id}.');
}
if (!$website_coverage['wishlist_links_to_course_checkout']) {
    yccca1_add_warning($warnings, 'wishlist_checkout_cta_gap', 'Wishlist surfaces do not link purchase-capable courses directly to /youngo/checkout/start/{course_id}.');
}
yccca1_add_check($checks, 'checkout_order_view_payment_controls_present', $website_coverage['checkout_view_has_coupon_apply_clear'] && $website_coverage['checkout_view_has_zero_coupon_complete'] && $website_coverage['checkout_view_has_instapay_upload']);

$table_names = array(
    'youngo_checkout_orders',
    'youngo_coupon_usages',
    'youngo_course_access',
    'youngo_user_subscriptions',
    'youngo_instapay_payment_submissions',
    'youngo_payment_transactions',
);

$table_status = array();
$columns = array();
$before_counts = array();
$after_counts = array();
foreach ($table_names as $table) {
    $table_status[$table] = yccca1_table_exists($mysqli, $table);
    $columns[$table] = yccca1_columns($mysqli, $table);
    $before_counts[$table] = yccca1_count($mysqli, $table);
}

$checkout_required_columns = array('id', 'user_id', 'order_reference', 'order_type', 'status', 'course_id', 'plan_id', 'subtotal_amount', 'discount_amount', 'total_amount', 'total_amount_cents', 'currency', 'coupon_id', 'coupon_code', 'coupon_discount_type', 'coupon_discount_value', 'selected_payment_method', 'item_title_snapshot', 'checkout_snapshot_json', 'payment_gateway', 'completed_at', 'paid_at', 'entitlement_issued', 'entitlement_issuance_status', 'entitlement_course_access_id', 'entitlement_subscription_id', 'entitlement_issued_at');
$coupon_usage_required_columns = array('id', 'coupon_id', 'coupon_code', 'user_id', 'checkout_order_id', 'payment_id', 'discount_amount', 'used_at');
$course_access_required_columns = array('id', 'user_id', 'course_id', 'access_source', 'status', 'checkout_order_id', 'payment_id', 'manual_grant_id');
$subscription_required_columns = array('id', 'user_id', 'plan_id', 'source', 'status', 'checkout_order_id', 'payment_id', 'manual_grant_id', 'price_paid', 'currency');
$instapay_required_columns = array('id', 'order_id', 'user_id', 'status', 'expected_amount', 'submitted_amount', 'currency', 'screenshot_path', 'admin_note', 'reviewed_by_user_id', 'access_issued', 'access_reference_type', 'access_reference_id', 'snapshot_json');

yccca1_add_check($checks, 'checkout_order_tracking_columns_present', yccca1_has_columns($columns['youngo_checkout_orders'], $checkout_required_columns));
yccca1_add_check($checks, 'coupon_usage_checkout_reference_columns_present', yccca1_has_columns($columns['youngo_coupon_usages'], $coupon_usage_required_columns));
yccca1_add_check($checks, 'course_access_checkout_reference_columns_present', yccca1_has_columns($columns['youngo_course_access'], $course_access_required_columns));
yccca1_add_check($checks, 'subscription_checkout_reference_columns_present', yccca1_has_columns($columns['youngo_user_subscriptions'], $subscription_required_columns));
yccca1_add_check($checks, 'instapay_submission_review_columns_present', yccca1_has_columns($columns['youngo_instapay_payment_submissions'], $instapay_required_columns));

$db_samples = array(
    'checkout_orders_by_order_type_status' => yccca1_group_count($mysqli, 'youngo_checkout_orders', array('order_type', 'status')),
    'checkout_orders_by_payment_method' => yccca1_group_count($mysqli, 'youngo_checkout_orders', array('selected_payment_method', 'payment_gateway')),
    'coupon_usages_by_coupon_code' => yccca1_group_count($mysqli, 'youngo_coupon_usages', array('coupon_code')),
    'course_access_by_source' => yccca1_group_count($mysqli, 'youngo_course_access', array('access_source')),
    'subscriptions_by_source' => yccca1_group_count($mysqli, 'youngo_user_subscriptions', array('source')),
    'instapay_submissions_by_status' => yccca1_group_count($mysqli, 'youngo_instapay_payment_submissions', array('status')),
);

$zero_coupon_counts = array(
    'orders_marked_zero_amount_coupon' => array(),
    'coupon_usage_rows_with_checkout_order' => array(),
    'course_access_rows_with_checkout_order' => array(),
    'subscription_rows_with_checkout_order' => array(),
);
if ($table_status['youngo_checkout_orders']) {
    $zero_coupon_counts['orders_marked_zero_amount_coupon'] = yccca1_query($mysqli, "SELECT COUNT(*) AS c FROM `youngo_checkout_orders` WHERE (`selected_payment_method` = 'zero_amount_coupon' OR `payment_gateway` = 'zero_amount_coupon' OR (`total_amount` = 0 AND `coupon_id` IS NOT NULL AND `coupon_code` IS NOT NULL))");
}
if ($table_status['youngo_coupon_usages']) {
    $zero_coupon_counts['coupon_usage_rows_with_checkout_order'] = yccca1_query($mysqli, 'SELECT COUNT(*) AS c FROM `youngo_coupon_usages` WHERE `checkout_order_id` IS NOT NULL');
}
if ($table_status['youngo_course_access']) {
    $zero_coupon_counts['course_access_rows_with_checkout_order'] = yccca1_query($mysqli, 'SELECT COUNT(*) AS c FROM `youngo_course_access` WHERE `checkout_order_id` IS NOT NULL');
}
if ($table_status['youngo_user_subscriptions']) {
    $zero_coupon_counts['subscription_rows_with_checkout_order'] = yccca1_query($mysqli, 'SELECT COUNT(*) AS c FROM `youngo_user_subscriptions` WHERE `checkout_order_id` IS NOT NULL');
}

foreach ($table_names as $table) {
    $after_counts[$table] = yccca1_count($mysqli, $table);
}
yccca1_add_check($checks, 'no_db_writes_detected_by_count_compare', $before_counts === $after_counts, json_encode(array('before' => $before_counts, 'after' => $after_counts)));

$details['routes'] = $route_status;
$details['checkout_controller_actions'] = $method_status;
$details['supported_item_types'] = $supported_types;
$details['website_coverage'] = $website_coverage;
$details['table_status'] = $table_status;
$details['columns'] = $columns;
$details['db_group_counts'] = $db_samples;
$details['zero_coupon_tracking_counts'] = $zero_coupon_counts;
$details['protected_table_counts'] = $after_counts;

$failures = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failures[$name] = $check['detail'];
    }
}

yccca1_print_section('Checks', $checks);
yccca1_print_section('Warnings', $warnings);
yccca1_print_section('Details', $details);

if (empty($failures)) {
    echo "\nRESULT: PASS\n";
    exit(0);
}

echo "\nRESULT: FAIL\n";
yccca1_print_section('Failures', $failures);
exit(1);
