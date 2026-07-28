<?php

/**
 * PAYMENT.MANUAL.INSTAPAY.COUPON.CHECKOUT.PLAN.1 diagnostic.
 *
 * This is intentionally static and read-only. It does not bootstrap
 * CodeIgniter, connect to a database, execute SQL, or write files.
 */

$root_path = dirname(__DIR__, 2);
$checks = array();

function ymicp1_path($relative_path)
{
    global $root_path;

    return $root_path . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
}

function ymicp1_read($relative_path)
{
    $path = ymicp1_path($relative_path);

    if (!is_file($path)) {
        return null;
    }

    return file_get_contents($path);
}

function ymicp1_add_check($name, $passed, $detail = '')
{
    global $checks;

    $checks[] = array(
        'name' => $name,
        'passed' => (bool) $passed,
        'detail' => $detail,
    );
}

function ymicp1_contains($content, $needle)
{
    return is_string($content) && strpos($content, $needle) !== false;
}

function ymicp1_matches($content, $pattern)
{
    return is_string($content) && preg_match($pattern, $content) === 1;
}

function ymicp1_scan_for_pattern($relative_directories, $pattern)
{
    $hits = array();
    $allowed_extensions = array('php', 'sql', 'json', 'md', 'txt');
    $root_prefix = rtrim(ymicp1_path(''), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    foreach ($relative_directories as $relative_directory) {
        $directory = ymicp1_path($relative_directory);

        if (!is_dir($directory)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $extension = strtolower($file->getExtension());

            if (!in_array($extension, $allowed_extensions, true)) {
                continue;
            }

            if ($file->getSize() > 2097152) {
                continue;
            }

            $path = $file->getPathname();
            $content = file_get_contents($path);

            if ($content !== false && preg_match($pattern, $content) === 1) {
                $hits[] = str_replace($root_prefix, '', $path);
            }
        }
    }

    return $hits;
}

$checkout_model = ymicp1_read('application/models/Youngo_checkout_model.php');
$payment_model = ymicp1_read('application/models/Youngo_payment_model.php');
$entitlement_model = ymicp1_read('application/models/Youngo_entitlement_write_model.php');
$subscription_model = ymicp1_read('application/models/Youngo_subscription_model.php');
$crud_model = ymicp1_read('application/models/Crud_model.php');
$admin_controller = ymicp1_read('application/controllers/Admin.php');
$checkout_controller = ymicp1_read('application/controllers/Youngo_checkout.php');
$webhook_controller = ymicp1_read('application/controllers/Youngo_payment_webhook.php');
$paymob_config = ymicp1_read('application/config/youngo_paymob.php');
$routes = ymicp1_read('application/config/routes.php');
$checkout_order_view = ymicp1_read('application/views/frontend/youngo/checkout_order.php');
$phase_2e_schema = ymicp1_read('database/phase_2/youngo_phase_2e_schema_up.sql');

ymicp1_add_check('checkout model file exists', $checkout_model !== null);
ymicp1_add_check('payment model file exists', $payment_model !== null);
ymicp1_add_check('entitlement write model file exists', $entitlement_model !== null);
ymicp1_add_check('subscription model file exists', $subscription_model !== null);
ymicp1_add_check('checkout controller file exists', $checkout_controller !== null);
ymicp1_add_check('payment webhook controller file exists', $webhook_controller !== null);

if ($checkout_model !== null) {
    ymicp1_add_check('checkout model references youngo_checkout_orders', ymicp1_contains($checkout_model, 'youngo_checkout_orders'));
    ymicp1_add_check('checkout model creates course purchase draft orders', ymicp1_contains($checkout_model, 'create_draft_order') && ymicp1_contains($checkout_model, 'course_purchase'));
    ymicp1_add_check('checkout model has EGP amount and cents handling', ymicp1_contains($checkout_model, 'EGP') && ymicp1_contains($checkout_model, 'total_amount_cents'));
    ymicp1_add_check('checkout model initializes order discount amount field', ymicp1_contains($checkout_model, 'discount_amount'));
    ymicp1_add_check('checkout model does not implement Instapay yet', stripos($checkout_model, 'instapay') === false);
}

if ($payment_model !== null) {
    ymicp1_add_check('payment model references payment transactions', ymicp1_contains($payment_model, 'youngo_payment_transactions'));
    ymicp1_add_check('payment model marks verified orders paid', ymicp1_contains($payment_model, 'mark_paid_from_verified_transaction'));
    ymicp1_add_check('payment model delegates entitlement issuance', ymicp1_contains($payment_model, 'issue_paid_order_entitlement'));
    ymicp1_add_check('payment model remains Paymob/HMAC oriented', ymicp1_contains($payment_model, 'hmac') || ymicp1_contains($payment_model, 'HMAC'));
}

if ($entitlement_model !== null) {
    ymicp1_add_check('entitlement model has course purchase issuance method', ymicp1_contains($entitlement_model, 'issue_course_purchase_access'));
    ymicp1_add_check('entitlement model keeps subscription checkout issuance stubbed', ymicp1_contains($entitlement_model, 'issue_subscription_purchase') && ymicp1_contains($entitlement_model, 'checkout_issuance_not_implemented'));
    ymicp1_add_check('entitlement model references course access table', ymicp1_contains($entitlement_model, 'youngo_course_access'));
    ymicp1_add_check('entitlement model references user subscription table', ymicp1_contains($entitlement_model, 'youngo_user_subscriptions'));
}

if ($subscription_model !== null) {
    ymicp1_add_check('subscription model references subscription plans', ymicp1_contains($subscription_model, 'youngo_subscription_plans'));
    ymicp1_add_check('subscription model enforces EGP plan readiness', ymicp1_contains($subscription_model, 'EGP'));
}

if ($crud_model !== null) {
    ymicp1_add_check('legacy coupon lookup method exists', ymicp1_contains($crud_model, 'get_coupon_details_by_code'));
    ymicp1_add_check('legacy coupon validity method exists', ymicp1_contains($crud_model, 'check_coupon_validity'));
    ymicp1_add_check('legacy coupon calculation is cart/session based', ymicp1_contains($crud_model, 'get_discounted_price_after_applying_coupon') && ymicp1_contains($crud_model, 'cart_items'));
}

if ($admin_controller !== null) {
    ymicp1_add_check('legacy admin coupon controller exists', ymicp1_contains($admin_controller, 'function coupons') && ymicp1_contains($admin_controller, 'coupon_form'));
}

ymicp1_add_check('legacy coupon add view exists', is_file(ymicp1_path('application/views/backend/admin/coupon_add.php')));
ymicp1_add_check('legacy coupon edit view exists', is_file(ymicp1_path('application/views/backend/admin/coupon_edit.php')));
ymicp1_add_check('legacy coupon list view exists', is_file(ymicp1_path('application/views/backend/admin/coupons.php')));

if ($phase_2e_schema !== null) {
    ymicp1_add_check('phase 2 checkout schema documents order coupon fields', ymicp1_contains($phase_2e_schema, 'coupon_id') && ymicp1_contains($phase_2e_schema, 'coupon_code') && ymicp1_contains($phase_2e_schema, 'discount_amount'));
    ymicp1_add_check('phase 2 coupon usage table is documented in schema', ymicp1_contains($phase_2e_schema, 'youngo_coupon_usages'));
    ymicp1_add_check('phase 2 coupon scope tables are documented in schema', ymicp1_contains($phase_2e_schema, 'youngo_coupon_courses') && ymicp1_contains($phase_2e_schema, 'youngo_coupon_subscription_plans'));
    ymicp1_add_check('phase 2 coupon fixed/percentage fields are documented in schema', ymicp1_contains($phase_2e_schema, 'discount_type') && ymicp1_contains($phase_2e_schema, 'discount_value'));
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
        ymicp1_add_check(
            'Paymob gate remains false: ' . $gate,
            ymicp1_matches($paymob_config, '/[\'"]' . preg_quote($gate, '/') . '[\'"]\s*=>\s*false\b/')
        );
    }

    ymicp1_add_check('Paymob config remains sandbox mode', ymicp1_matches($paymob_config, '/[\'"]mode[\'"]\s*=>\s*[\'"]sandbox[\'"]/'));
    ymicp1_add_check('Paymob config remains EGP currency', ymicp1_matches($paymob_config, '/[\'"]currency[\'"]\s*=>\s*[\'"]EGP[\'"]/'));
}

if ($routes !== null) {
    ymicp1_add_check('existing checkout start route is present', ymicp1_contains($routes, "\$route['youngo/checkout/start/(:num)']"));
    ymicp1_add_check('existing checkout order route is present', ymicp1_contains($routes, "\$route['youngo/checkout/order/(:any)']"));
    ymicp1_add_check('existing Paymob webhook route is present', ymicp1_contains($routes, "\$route['payment/paymob/webhook']"));
    ymicp1_add_check('Instapay routes remain staged without approval routes', !ymicp1_matches($routes, '/\$route\[[^\]]*(approve|reject)[^\]]*instapay/i'));
    ymicp1_add_check('admin Instapay review route is read-only staged when present', stripos($routes, 'admin/youngo/instapay-payments') === false || !ymicp1_matches($routes, '/\$route\[[^\]]*admin\/youngo\/instapay-payments[^\]]*(approve|reject)/i'));
}

if ($checkout_order_view !== null) {
    ymicp1_add_check('checkout order view exists without Instapay UI yet', stripos($checkout_order_view, 'instapay') === false);
    ymicp1_add_check('checkout order view still shows no gateway selection copy', ymicp1_contains($checkout_order_view, 'There is no gateway selection'));
}

$application_instapay_hits = ymicp1_scan_for_pattern(
    array(
        'application/config',
        'application/controllers',
        'application/helpers',
        'application/models',
        'application/views',
    ),
    '/instapay/i'
);
ymicp1_add_check(
    'application source has no Instapay implementation yet',
    count($application_instapay_hits) === 0,
    count($application_instapay_hits) . ' hit(s)'
);

$instapay_schema_hits = ymicp1_scan_for_pattern(
    array(
        'database/phase_2',
        'application/config',
        'application/controllers',
        'application/helpers',
        'application/models',
        'application/views',
    ),
    '/youngo_instapay_payment_submissions/i'
);
ymicp1_add_check(
    'no Instapay submission schema exists yet',
    count($instapay_schema_hits) === 0,
    count($instapay_schema_hits) . ' hit(s)'
);

$approved_instapay_review_statuses = array('pending_review', 'approved', 'rejected');
$forbidden_instapay_review_statuses = array('draft', 'cancelled', 'expired');
ymicp1_add_check(
    'diagnostic-approved Instapay review statuses are limited to pending_review/approved/rejected',
    $approved_instapay_review_statuses === array('pending_review', 'approved', 'rejected')
);
ymicp1_add_check(
    'diagnostic does not approve forbidden Instapay review statuses',
    count(array_intersect($approved_instapay_review_statuses, $forbidden_instapay_review_statuses)) === 0
);

$failed_checks = array();

foreach ($checks as $check) {
    if (!$check['passed']) {
        $failed_checks[] = $check;
    }
}

echo 'phase: PAYMENT.MANUAL.INSTAPAY.COUPON.CHECKOUT.PLAN.1' . PHP_EOL;
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
echo 'summary: ' . (count($failed_checks) === 0 ? 'PASS' : 'FAIL') . PHP_EOL;
echo 'checks_total: ' . count($checks) . PHP_EOL;
echo 'checks_failed: ' . count($failed_checks) . PHP_EOL;

exit(count($failed_checks) === 0 ? 0 : 1);
