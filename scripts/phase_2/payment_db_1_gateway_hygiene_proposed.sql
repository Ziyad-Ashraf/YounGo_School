-- PAYMENT.DB.1 proposed gateway hygiene SQL
-- STATUS: NOT EXECUTED.
-- PURPOSE: Future local-only cleanup proposal before any checkout/payment testing.
-- WARNING: Do not run this file until a fresh DB backup exists and the owner approves
-- the exact sandbox gateway strategy. This file is documentation/planning only.

START TRANSACTION;

-- 1. Hide every inherited legacy gateway from frontend selection by default.
-- payment_gateway.php renders rows where payment_gateways.status = 1.
UPDATE `payment_gateways`
SET
    `status` = 0,
    `enabled_test_mode` = 1,
    `updated_at` = UNIX_TIMESTAMP()
WHERE `identifier` IN (
    'paypal',
    'stripe',
    'razorpay',
    'xendit',
    'payu',
    'pagseguro',
    'sslcommerz',
    'skrill',
    'doku',
    'bkash',
    'cashfree',
    'maxicash',
    'aamarpay',
    'flutterwave',
    'tazapay'
);

-- 2. Normalize stored gateway currency labels to the YounGo commercial currency.
-- Keep disabled until each provider is individually proven EGP-compatible in sandbox.
UPDATE `payment_gateways`
SET
    `currency` = 'EGP',
    `updated_at` = UNIX_TIMESTAMP()
WHERE `identifier` IN (
    'paypal',
    'stripe',
    'razorpay',
    'xendit',
    'payu',
    'pagseguro',
    'sslcommerz',
    'skrill',
    'doku',
    'bkash',
    'cashfree',
    'maxicash',
    'aamarpay',
    'flutterwave',
    'tazapay'
);

-- 3. Optional credential hygiene.
-- Leave this disabled until the admin payment settings form has been checked with
-- empty gateway JSON. Some inherited forms may expect gateway-specific key names.
-- UPDATE `payment_gateways`
-- SET
--     `keys` = '{}',
--     `updated_at` = UNIX_TIMESTAMP()
-- WHERE `identifier` IN (
--     'paypal',
--     'stripe',
--     'razorpay',
--     'xendit',
--     'payu',
--     'pagseguro',
--     'sslcommerz',
--     'skrill',
--     'doku',
--     'bkash',
--     'cashfree',
--     'maxicash',
--     'aamarpay',
--     'flutterwave',
--     'tazapay'
-- );

-- 4. Optional legacy settings hygiene for old gateway configuration records.
-- Leave this disabled until code paths using settings.paypal, stripe_keys, and
-- razorpay_keys are either removed, guarded, or replaced by the current
-- payment_gateways table flow.
-- UPDATE `settings`
-- SET `value` = ''
-- WHERE `key` IN ('paypal', 'stripe_keys', 'razorpay_keys');
--
-- UPDATE `settings`
-- SET `value` = 'EGP'
-- WHERE `key` IN (
--     'paypal_currency',
--     'stripe_currency',
--     'razorpay_currency',
--     'paystack_currency',
--     'ccavenue_currency',
--     'iyzico_currency'
-- );

-- 5. Verification queries to run after any approved future execution.
-- SELECT identifier, status, enabled_test_mode, currency,
--        CASE WHEN COALESCE(`keys`, '') = '' OR `keys` = '{}' THEN 0 ELSE 1 END AS has_keys
-- FROM `payment_gateways`
-- ORDER BY id;
--
-- SELECT `key`, `value`
-- FROM `settings`
-- WHERE `key` IN ('system_currency', 'currency_position');
--
-- SELECT COUNT(*) AS payment_rows FROM `payment`;
-- SELECT COUNT(*) AS enrol_rows FROM `enrol`;
-- SELECT COUNT(*) AS checkout_order_rows FROM `youngo_checkout_orders`;
-- SELECT COUNT(*) AS coupon_usage_rows FROM `youngo_coupon_usages`;

ROLLBACK;

-- This file intentionally ends with ROLLBACK because it is a proposal only.
-- Replace ROLLBACK with COMMIT only in a future approved execution copy.
