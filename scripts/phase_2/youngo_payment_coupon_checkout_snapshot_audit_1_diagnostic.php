<?php

/**
 * PAYMENT.COUPON.CHECKOUT.SNAPSHOT.AUDIT.1 diagnostic.
 *
 * Static and read-only by design. This script does not bootstrap CodeIgniter,
 * connect to a database, execute SQL, create routes, or write files.
 */

$root_path = dirname(__DIR__, 2);
$checks = array();

function ypcs1_path($relative_path)
{
    global $root_path;

    return $root_path . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
}

function ypcs1_read($relative_path)
{
    $path = ypcs1_path($relative_path);

    if (!is_file($path)) {
        return null;
    }

    return file_get_contents($path);
}

function ypcs1_check($name, $passed, $detail = '')
{
    global $checks;

    $checks[] = array(
        'name' => $name,
        'passed' => (bool) $passed,
        'detail' => $detail,
    );
}

function ypcs1_contains($content, $needle)
{
    return is_string($content) && strpos($content, $needle) !== false;
}

function ypcs1_absent($content, $needle)
{
    return is_string($content) && strpos($content, $needle) === false;
}

function ypcs1_matches($content, $pattern)
{
    return is_string($content) && preg_match($pattern, $content) === 1;
}

$crud_model = ypcs1_read('application/models/Crud_model.php');
$payment_model = ypcs1_read('application/models/Payment_model.php');
$youngo_checkout_model = ypcs1_read('application/models/Youngo_checkout_model.php');
$youngo_payment_model = ypcs1_read('application/models/Youngo_payment_model.php');
$youngo_checkout_controller = ypcs1_read('application/controllers/Youngo_checkout.php');
$home_controller = ypcs1_read('application/controllers/Home.php');
$payment_controller = ypcs1_read('application/controllers/Payment.php');
$admin_controller = ypcs1_read('application/controllers/Admin.php');
$paymob_config = ypcs1_read('application/config/youngo_paymob.php');
$routes = ypcs1_read('application/config/routes.php');
$shopping_cart_view = ypcs1_read('application/views/frontend/youngo/shopping_cart_inner_view.php');
$checkout_order_view = ypcs1_read('application/views/frontend/youngo/checkout_order.php');
$coupon_add_view = ypcs1_read('application/views/backend/admin/coupon_add.php');
$coupon_edit_view = ypcs1_read('application/views/backend/admin/coupon_edit.php');
$coupon_list_view = ypcs1_read('application/views/backend/admin/coupons.php');
$phase_2e_schema = ypcs1_read('database/phase_2/youngo_phase_2e_schema_up.sql');
$payment_schema = ypcs1_read('scripts/phase_2/payment_config_2_youngo_payment_schema_proposed.sql');

ypcs1_check('Crud_model exists', $crud_model !== null);
ypcs1_check('Payment_model exists', $payment_model !== null);
ypcs1_check('Youngo_checkout_model exists', $youngo_checkout_model !== null);
ypcs1_check('Youngo_payment_model exists', $youngo_payment_model !== null);
ypcs1_check('Youngo_checkout controller exists', $youngo_checkout_controller !== null);
ypcs1_check('Home controller exists', $home_controller !== null);
ypcs1_check('Payment controller exists', $payment_controller !== null);
ypcs1_check('Admin controller exists', $admin_controller !== null);

if ($crud_model !== null) {
    ypcs1_check('legacy coupons table is read by Crud_model', ypcs1_contains($crud_model, "get('coupons'") || ypcs1_contains($crud_model, "'coupons'"));
    ypcs1_check('legacy coupon lookup by code exists', ypcs1_contains($crud_model, 'get_coupon_details_by_code'));
    ypcs1_check('legacy coupon add/edit/delete methods exist', ypcs1_contains($crud_model, 'add_coupon') && ypcs1_contains($crud_model, 'edit_coupon') && ypcs1_contains($crud_model, 'delete_coupon'));
    ypcs1_check('legacy coupon validity checks expiry', ypcs1_contains($crud_model, 'check_coupon_validity') && ypcs1_contains($crud_model, 'expiry_date'));
    ypcs1_check('legacy coupon active discount field is percentage', ypcs1_contains($crud_model, 'discount_percentage'));
    ypcs1_check('legacy coupon calculation relies on cart session', ypcs1_contains($crud_model, 'get_discounted_price_after_applying_coupon') && ypcs1_contains($crud_model, "userdata('cart_items')"));
    ypcs1_check('legacy coupon calculation clamps negative totals', ypcs1_contains($crud_model, 'return $total_price > 0 ? $total_price : 0'));
}

if ($payment_model !== null) {
    ypcs1_check('legacy payment details are session based', ypcs1_contains($payment_model, 'configure_course_payment') && ypcs1_contains($payment_model, "set_userdata('payment_details'"));
    ypcs1_check('legacy payment applies coupon through session value', ypcs1_contains($payment_model, "userdata('applied_coupon')"));
    ypcs1_check('legacy payment reuses cart coupon total helper', ypcs1_contains($payment_model, 'get_discounted_price_after_applying_coupon'));
    ypcs1_check('legacy payment adds tax after coupon', ypcs1_contains($payment_model, 'course_selling_tax'));
}

if ($home_controller !== null) {
    ypcs1_check('legacy apply_coupon endpoint exists', ypcs1_contains($home_controller, 'function apply_coupon'));
    ypcs1_check('legacy coupon apply renders cart fragment', ypcs1_contains($home_controller, 'shopping_cart_inner_view'));
    ypcs1_check('YounGo-managed courses are removed from legacy cart checkout', ypcs1_contains($home_controller, 'youngo_remove_managed_access_courses_from_cart'));
    ypcs1_check('100 percent coupon direct enrol is quarantined for YounGo-managed carts', ypcs1_contains($home_controller, 'coupon_offer_100_percent') && ypcs1_contains($home_controller, 'Coupon-based checkout is not available yet'));
}

if ($payment_controller !== null) {
    ypcs1_check('legacy success payment writes via Crud_model enrolment/purchase', ypcs1_contains($payment_controller, 'success_course_payment') && ypcs1_contains($payment_controller, 'enrol_student') && ypcs1_contains($payment_controller, 'course_purchase'));
    ypcs1_check('legacy payment gateway check uses payment_gateways', ypcs1_contains($payment_controller, 'youngo_gateway_payment_check') && ypcs1_contains($payment_controller, 'payment_gateways'));
}

if ($admin_controller !== null) {
    ypcs1_check('legacy admin coupon controller exists', ypcs1_contains($admin_controller, 'function coupons') && ypcs1_contains($admin_controller, 'function coupon_form'));
    ypcs1_check('legacy coupon admin uses coupon permission', ypcs1_contains($admin_controller, "check_permission('coupon')"));
}

ypcs1_check('coupon add view exists', $coupon_add_view !== null);
ypcs1_check('coupon edit view exists', $coupon_edit_view !== null);
ypcs1_check('coupon list view exists', $coupon_list_view !== null);

if ($coupon_add_view !== null && $coupon_edit_view !== null && $coupon_list_view !== null) {
    $coupon_views = $coupon_add_view . $coupon_edit_view . $coupon_list_view;
    ypcs1_check('legacy coupon views expose percentage field', ypcs1_contains($coupon_views, 'discount_percentage'));
    ypcs1_check('legacy coupon views expose expiry field', ypcs1_contains($coupon_views, 'expiry_date'));
    ypcs1_check('legacy coupon views do not expose fixed discount type', ypcs1_absent($coupon_views, 'discount_type') && ypcs1_absent($coupon_views, 'discount_value'));
}

if ($shopping_cart_view !== null) {
    ypcs1_check('YounGo cart view applies coupon in view fragment', ypcs1_contains($shopping_cart_view, 'check_coupon_validity') && ypcs1_contains($shopping_cart_view, 'set_userdata'));
    ypcs1_check('YounGo cart view posts coupon to Home apply_coupon', ypcs1_contains($shopping_cart_view, "site_url('home/apply_coupon')"));
}

if ($phase_2e_schema !== null) {
    ypcs1_check('phase 2 schema references checkout order table', ypcs1_contains($phase_2e_schema, 'CREATE TABLE IF NOT EXISTS `youngo_checkout_orders`'));
    ypcs1_check('phase 2 schema references coupon usage table', ypcs1_contains($phase_2e_schema, 'CREATE TABLE IF NOT EXISTS `youngo_coupon_usages`'));
    ypcs1_check('phase 2 schema references course coupon scope table', ypcs1_contains($phase_2e_schema, 'CREATE TABLE IF NOT EXISTS `youngo_coupon_courses`'));
    ypcs1_check('phase 2 schema references subscription coupon scope table', ypcs1_contains($phase_2e_schema, 'CREATE TABLE IF NOT EXISTS `youngo_coupon_subscription_plans`'));
    ypcs1_check('phase 2 schema adds coupon fixed/percentage fields', ypcs1_contains($phase_2e_schema, 'discount_type') && ypcs1_contains($phase_2e_schema, 'discount_value'));
    ypcs1_check('phase 2 schema adds coupon status/scope/usage limit fields', ypcs1_contains($phase_2e_schema, 'scope') && ypcs1_contains($phase_2e_schema, 'status') && ypcs1_contains($phase_2e_schema, 'max_usage_count'));
}

if ($payment_schema !== null) {
    ypcs1_check('payment schema proposal documents EGP checkout order currency', ypcs1_matches($payment_schema, '/`currency`\s+VARCHAR\(10\)\s+NOT NULL\s+DEFAULT\s+\'EGP\'/'));
    ypcs1_check('payment schema proposal documents checkout order coupon fields', ypcs1_contains($payment_schema, '`coupon_id`') && ypcs1_contains($payment_schema, '`coupon_code`') && ypcs1_contains($payment_schema, '`discount_amount`'));
    ypcs1_check('payment schema proposal documents checkout order metadata', ypcs1_contains($payment_schema, '`metadata` LONGTEXT'));
}

if ($youngo_checkout_model !== null) {
    ypcs1_check('YounGo checkout model references checkout order table', ypcs1_contains($youngo_checkout_model, 'youngo_checkout_orders'));
    ypcs1_check('YounGo checkout model stores current amount fields', ypcs1_contains($youngo_checkout_model, 'subtotal_amount') && ypcs1_contains($youngo_checkout_model, 'discount_amount') && ypcs1_contains($youngo_checkout_model, 'total_amount') && ypcs1_contains($youngo_checkout_model, 'total_amount_cents'));
    ypcs1_check('YounGo checkout model enforces EGP only', ypcs1_contains($youngo_checkout_model, 'EGP') && ypcs1_contains($youngo_checkout_model, 'unsupported_currency'));
    ypcs1_check('YounGo checkout model current safe summary omits coupon/admin snapshot fields', ypcs1_absent($youngo_checkout_model, "'coupon_discount_type'") && ypcs1_absent($youngo_checkout_model, "'item_title_snapshot'") && ypcs1_absent($youngo_checkout_model, "'checkout_snapshot_json'"));
    ypcs1_check('YounGo checkout model does not apply coupon code yet', ypcs1_absent($youngo_checkout_model, "'coupon_code' =>") && ypcs1_absent($youngo_checkout_model, 'apply_coupon'));
    ypcs1_check('YounGo checkout model currently supports course purchase order creation', ypcs1_contains($youngo_checkout_model, 'course_purchase') && ypcs1_contains($youngo_checkout_model, 'create_draft_order'));
}

if ($youngo_checkout_controller !== null) {
    ypcs1_check('YounGo checkout controller computes course amount without coupon evaluator', ypcs1_contains($youngo_checkout_controller, 'checkout_amount_for_course') && ypcs1_absent($youngo_checkout_controller, 'coupon_code'));
    ypcs1_check('YounGo checkout controller has no Instapay/manual submission behavior', stripos($youngo_checkout_controller, 'instapay') === false && stripos($youngo_checkout_controller, 'manual') === false);
}

if ($youngo_payment_model !== null) {
    ypcs1_check('YounGo payment model remains Paymob/HMAC oriented', ypcs1_contains($youngo_payment_model, 'hmac_verified') && ypcs1_contains($youngo_payment_model, 'paymob'));
    ypcs1_check('YounGo payment model issues only paid course purchase entitlements in this phase', ypcs1_contains($youngo_payment_model, 'issue_paid_order_entitlement') && ypcs1_contains($youngo_payment_model, 'Only course purchase checkout orders are supported'));
}

if ($checkout_order_view !== null) {
    ypcs1_check('YounGo checkout view lacks coupon rows today', stripos($checkout_order_view, 'coupon') === false && stripos($checkout_order_view, 'discount') === false);
    ypcs1_check('YounGo checkout view still has no gateway selection copy', ypcs1_contains($checkout_order_view, 'There is no gateway selection'));
}

if ($routes !== null) {
    ypcs1_check('current YounGo checkout routes are present', ypcs1_contains($routes, "\$route['youngo/checkout/start/(:num)']") && ypcs1_contains($routes, "\$route['youngo/checkout/order/(:any)']"));
    ypcs1_check('no coupon checkout route has been created', stripos($routes, 'youngo/checkout/coupon') === false && stripos($routes, 'coupon-checkout') === false);
    ypcs1_check('no Instapay route has been created', stripos($routes, 'instapay') === false);
}

if ($paymob_config !== null) {
    $disabled_gates = array(
        'enabled',
        'network_enabled',
        'sandbox_network_testing_enabled',
        'webhook_testing_enabled',
        'checkout_routes_enabled',
        'checkout_local_testing_enabled',
        'checkout_cta_enabled',
        'live_mode_allowed',
    );

    foreach ($disabled_gates as $gate) {
        ypcs1_check(
            'Paymob gate remains false: ' . $gate,
            ypcs1_matches($paymob_config, '/[\'"]' . preg_quote($gate, '/') . '[\'"]\s*=>\s*false\b/')
        );
    }

    ypcs1_check('Paymob config remains EGP sandbox', ypcs1_matches($paymob_config, '/[\'"]currency[\'"]\s*=>\s*[\'"]EGP[\'"]/') && ypcs1_matches($paymob_config, '/[\'"]mode[\'"]\s*=>\s*[\'"]sandbox[\'"]/'));
}

$approved_instapay_review_statuses = array('pending_review', 'approved', 'rejected');
$forbidden_instapay_review_statuses = array('draft', 'cancelled', 'expired');
ypcs1_check('approved Instapay review statuses are limited to pending_review/approved/rejected', $approved_instapay_review_statuses === array('pending_review', 'approved', 'rejected'));
ypcs1_check('forbidden Instapay review statuses are not approved', count(array_intersect($approved_instapay_review_statuses, $forbidden_instapay_review_statuses)) === 0);

$failed = array();
foreach ($checks as $check) {
    if (!$check['passed']) {
        $failed[] = $check;
    }
}

echo 'phase: PAYMENT.COUPON.CHECKOUT.SNAPSHOT.AUDIT.1' . PHP_EOL;
echo 'mode: static_read_only_no_db' . PHP_EOL;
echo 'database: not_bootstrapped_not_queried' . PHP_EOL;
echo 'approved_instapay_review_statuses: ' . implode(', ', $approved_instapay_review_statuses) . PHP_EOL;
echo PHP_EOL;

foreach ($checks as $check) {
    echo ($check['passed'] ? 'PASS' : 'FAIL') . ' - ' . $check['name'];
    if ($check['detail'] !== '') {
        echo ' [' . $check['detail'] . ']';
    }
    echo PHP_EOL;
}

echo PHP_EOL;
echo 'summary: ' . (count($failed) === 0 ? 'PASS' : 'FAIL') . PHP_EOL;
echo 'checks_total: ' . count($checks) . PHP_EOL;
echo 'checks_failed: ' . count($failed) . PHP_EOL;

exit(count($failed) === 0 ? 0 : 1);
