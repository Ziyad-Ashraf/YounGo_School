<?php
/**
 * PAYMENT.ZERO.AMOUNT.COUPON.ACCESS.PLAN.1 diagnostic.
 *
 * Static/read-only diagnostic. It inspects source and SQL artifact text only;
 * it does not connect to the database, execute SQL, or mutate checkout,
 * payment, coupon, user, subscription, enrolment, entitlement, Paymob, or
 * Instapay data.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
$checks = array();

function ypzc1_check(&$checks, $name, $ok, $detail = '')
{
    $checks[$name] = array(
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => (string) $detail,
    );
}

function ypzc1_read($root, $relative)
{
    $path = $root . '/' . str_replace('\\', '/', $relative);
    return is_file($path) ? file_get_contents($path) : null;
}

function ypzc1_has($text, $needle)
{
    return is_string($text) && strpos($text, $needle) !== false;
}

function ypzc1_regex($text, $pattern)
{
    return is_string($text) && preg_match($pattern, $text) === 1;
}

function ypzc1_absent($text, $needle)
{
    return is_string($text) && stripos($text, $needle) === false;
}

$paths = array(
    'coupon_evaluator' => 'application/models/Youngo_coupon_evaluator_model.php',
    'checkout_model' => 'application/models/Youngo_checkout_model.php',
    'entitlement_write_model' => 'application/models/Youngo_entitlement_write_model.php',
    'subscription_model' => 'application/models/Youngo_subscription_model.php',
    'checkout_controller' => 'application/controllers/Youngo_checkout.php',
    'checkout_view' => 'application/views/frontend/youngo/checkout_order.php',
    'routes' => 'application/config/routes.php',
    'paymob_config' => 'application/config/youngo_paymob.php',
    'payment_schema' => 'scripts/phase_2/payment_config_2_youngo_payment_schema_proposed.sql',
    'phase_2e_schema' => 'database/phase_2/youngo_phase_2e_schema_up.sql',
    'coupon_snapshot_schema' => 'scripts/phase_2/payment_coupon_checkout_snapshot_schema_1_up.sql',
);

$source = array();
foreach ($paths as $name => $relative) {
    $source[$name] = ypzc1_read($root, $relative);
    ypzc1_check($checks, 'file_exists_' . $name, $source[$name] !== null, $relative);
}

$all_source = implode("\n", array_filter($source, 'is_string'));
$diagnostic_source = file_get_contents(__FILE__);
$forbidden_runtime_tokens = array(
    'my' . 'sqli',
    'D' . 'B(',
    '->' . 'query(',
    'SE' . 'LECT ',
    'IN' . 'SERT ',
    'UP' . 'DATE ',
    'DE' . 'LETE ',
);
$diagnostic_static = true;
foreach ($forbidden_runtime_tokens as $token) {
    if (stripos($diagnostic_source, $token) !== false) {
        $diagnostic_static = false;
        break;
    }
}

ypzc1_check($checks, 'diagnostic_is_static_no_db_connection', $diagnostic_static);

ypzc1_check($checks, 'evaluator_sets_zero_final_policy_flag', ypzc1_has($source['coupon_evaluator'], 'zero_final_amount_policy_not_enabled') && ypzc1_has($source['coupon_evaluator'], '$zero_policy') && ypzc1_has($source['coupon_evaluator'], "'final_amount' => \$final_amount"));
ypzc1_check($checks, 'evaluator_supports_fixed_and_percentage_clamping', ypzc1_has($source['coupon_evaluator'], "'fixed'") && ypzc1_has($source['coupon_evaluator'], "'percentage'") && ypzc1_has($source['coupon_evaluator'], 'discount_clamped'));
ypzc1_check($checks, 'evaluator_revalidates_status_expiry_scope_usage', ypzc1_has($source['coupon_evaluator'], 'coupon_is_active') && ypzc1_has($source['coupon_evaluator'], 'coupon_not_expired') && ypzc1_has($source['coupon_evaluator'], 'coupon_scope_allows_item') && ypzc1_has($source['coupon_evaluator'], 'coupon_usage_available'));
ypzc1_check($checks, 'evaluator_rejects_non_egp', ypzc1_has($source['coupon_evaluator'], "unsupported_currency") && ypzc1_has($source['coupon_evaluator'], "currency !== 'EGP'"));

ypzc1_check($checks, 'checkout_model_writes_coupon_snapshot_fields', ypzc1_has($source['checkout_model'], 'apply_coupon_snapshot_to_order') && ypzc1_has($source['checkout_model'], 'coupon_discount_type') && ypzc1_has($source['checkout_model'], 'coupon_discount_value') && ypzc1_has($source['checkout_model'], 'checkout_snapshot_json'));
ypzc1_check($checks, 'checkout_model_zero_flag_in_review_snapshot', ypzc1_has($source['checkout_model'], 'zero_final_amount_policy_not_enabled') && ypzc1_has($source['checkout_model'], 'safe_order_review_snapshot_from_row'));
ypzc1_check($checks, 'checkout_order_fields_available_in_schema_artifacts', ypzc1_has($source['phase_2e_schema'], '`subtotal_amount`') && ypzc1_has($source['phase_2e_schema'], '`total_amount`') && ypzc1_has($source['phase_2e_schema'], '`currency`') && ypzc1_has($source['phase_2e_schema'], '`completed_at`') && ypzc1_has($source['payment_schema'], '`paid_at`') && ypzc1_has($source['payment_schema'], '`entitlement_course_access_id`') && ypzc1_has($source['coupon_snapshot_schema'], '`selected_payment_method`') && ypzc1_has($source['coupon_snapshot_schema'], '`checkout_snapshot_json`'));

ypzc1_check($checks, 'checkout_ui_current_zero_warning_present', ypzc1_has($source['checkout_view'], 'This coupon brings the final amount to zero, but access is not granted automatically.'));
ypzc1_check($checks, 'checkout_ui_no_zero_completion_button_yet', !ypzc1_has($source['checkout_view'], 'Complete checkout') && !ypzc1_has($source['checkout_view'], 'Activate access') && !ypzc1_has($source['checkout_controller'], 'complete_zero_amount_coupon'));
ypzc1_check($checks, 'instapay_upload_currently_not_zero_blocked', ypzc1_has($source['checkout_view'], 'data-youngo-instapay-checkout-panel') && ypzc1_has($source['checkout_view'], 'Submit payment for review') && ypzc1_has($source['checkout_controller'], 'build_instapay_checkout_context'));

ypzc1_check($checks, 'entitlement_course_purchase_issuance_exists', ypzc1_has($source['entitlement_write_model'], 'issue_course_purchase_access') && ypzc1_has($source['entitlement_write_model'], "'access_source' => 'course_purchase'") && ypzc1_has($source['entitlement_write_model'], "'checkout_order_id' => (int) \$order['id']"));
ypzc1_check($checks, 'entitlement_course_purchase_requires_paid_hmac_gateway_guard', ypzc1_has($source['entitlement_write_model'], "status') !== 'paid'") && ypzc1_has($source['entitlement_write_model'], 'last_hmac_verified') && ypzc1_has($source['entitlement_write_model'], 'checkout_order_not_verified'));
ypzc1_check($checks, 'entitlement_subscription_purchase_still_stubbed', ypzc1_has($source['entitlement_write_model'], 'issue_subscription_purchase') && ypzc1_has($source['entitlement_write_model'], 'checkout_issuance_not_implemented'));
ypzc1_check($checks, 'entitlement_duplicate_prevention_exists', ypzc1_has($source['entitlement_write_model'], 'has_active_course_access') && ypzc1_has($source['entitlement_write_model'], 'entitlement_already_issued') && ypzc1_has($source['phase_2e_schema'], '`checkout_order_id`'));

ypzc1_check($checks, 'coupon_usage_table_available_in_schema', ypzc1_has($source['phase_2e_schema'], 'CREATE TABLE IF NOT EXISTS `youngo_coupon_usages`') && ypzc1_has($source['phase_2e_schema'], '`checkout_order_id`') && ypzc1_has($source['phase_2e_schema'], '`discount_amount`'));
ypzc1_check($checks, 'no_zero_amount_routes_added_yet', !ypzc1_regex($source['routes'], '/zero[_-]?amount|zero[_-]?coupon|activate[_-]?access|complete[_-]?checkout/i'));
ypzc1_check($checks, 'no_instapay_approval_behavior_present', ypzc1_absent($source['routes'], 'approve') && ypzc1_absent($source['routes'], 'reject') && ypzc1_absent($source['checkout_controller'], 'approve_instapay') && ypzc1_absent($source['checkout_controller'], 'reject_instapay'));
ypzc1_check($checks, 'paymob_static_gates_remain_disabled', ypzc1_regex($source['paymob_config'], '/[\'"]enabled[\'"]\s*=>\s*false\b/') && ypzc1_regex($source['paymob_config'], '/[\'"]network_enabled[\'"]\s*=>\s*false\b/'));
ypzc1_check($checks, 'no_checkout_behavior_change_in_this_plan', !ypzc1_regex($all_source, '/complete_zero_amount_coupon|zero_amount_coupon_access|zero_amount_coupon_complete/i'));

$failed = array();
foreach ($checks as $name => $check) {
    if ($check['status'] !== 'PASS') {
        $failed[$name] = $check;
    }
}

echo 'phase: PAYMENT.ZERO.AMOUNT.COUPON.ACCESS.PLAN.1' . PHP_EOL;
echo 'mode: static_read_only_no_db_no_sql' . PHP_EOL;
echo 'db_writes: none' . PHP_EOL;
echo 'sql_execution: none' . PHP_EOL;
echo 'paymob_activation: none' . PHP_EOL;
echo 'instapay_approval_behavior: none' . PHP_EOL;
echo PHP_EOL;

foreach ($checks as $name => $check) {
    echo $check['status'] . ' - ' . $name;
    if ($check['detail'] !== '') {
        echo ' :: ' . $check['detail'];
    }
    echo PHP_EOL;
}

echo PHP_EOL;
echo empty($failed) ? 'RESULT: PASS' . PHP_EOL : 'RESULT: FAIL' . PHP_EOL;

exit(empty($failed) ? 0 : 1);
