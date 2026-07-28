<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| YounGo Paymob local override example
|--------------------------------------------------------------------------
|
| Copying this file is a future owner/developer action only. The real local
| override path is application/config/youngo_paymob.local.php and is ignored by
| Git. Never commit real Paymob values, HMAC secrets, client secrets, live URLs,
| dashboard screenshots, or test card details.
|
| This example intentionally contains empty/null placeholders only.
| For sandbox testing, copy this file to youngo_paymob.local.php and provide
| real values there only. Do not commit the copied file.
|
*/

$config['youngo_paymob_local'] = array(
    'enabled' => false,
    'mode' => 'sandbox',
    'currency' => 'EGP',
    'amount_multiplier' => 100,
    'network_enabled' => false,
    'sandbox_network_testing_enabled' => false,
    'webhook_testing_enabled' => false,
    'checkout_routes_enabled' => false,
    'checkout_local_testing_enabled' => false,
    'checkout_cta_enabled' => false,
    'live_mode_allowed' => false,

    // Private/server-side Paymob values. Keep null in this tracked example.
    'api_key' => null, // PAYMOB_API_KEY, shown by Paymob for auth-token/legacy API paths; server-config-only here.
    'secret_key' => null, // PAYMOB_SECRET_KEY
    'public_key' => null, // PAYMOB_PUBLIC_KEY, account-specific and redacted by diagnostics.
    'hmac_secret' => null, // PAYMOB_HMAC_SECRET
    'integration_id_card_egp' => null,
    'integration_id_wallet_egp' => null,
    'api_base_url' => null,
    'checkout_base_url' => null,
    'return_url' => null,
    'webhook_url' => null,
    'base_url' => null,
);
