<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| YounGo Paymob configuration defaults
|--------------------------------------------------------------------------
|
| Safe tracked defaults only. Do not place real Paymob credentials, HMAC
| secrets, live URLs, dashboard values, client secrets, or test card data in
| this file. Local sandbox values belong only in an ignored local override.
|
*/

$config['youngo_paymob'] = array(
    'enabled' => false,
    'provider' => 'paymob',
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

    'api_key' => null,
    'secret_key' => null,
    'public_key' => null,
    'hmac_secret' => null,
    'integration_id_card_egp' => null,
    'integration_id_wallet_egp' => null,
    'api_base_url' => null,
    'checkout_base_url' => null,
    'return_url' => null,
    'webhook_url' => null,
    'base_url' => null,

    'placeholder_names' => array(
        'api_key' => 'PAYMOB_API_KEY',
        'secret_key' => 'PAYMOB_SECRET_KEY',
        'public_key' => 'PAYMOB_PUBLIC_KEY',
        'hmac_secret' => 'PAYMOB_HMAC_SECRET',
        'integration_id_card_egp' => 'PAYMOB_INTEGRATION_ID_CARD_EGP',
        'integration_id_wallet_egp' => 'PAYMOB_INTEGRATION_ID_WALLET_EGP',
        'api_base_url' => 'PAYMOB_API_BASE_URL',
        'checkout_base_url' => 'PAYMOB_CHECKOUT_BASE_URL',
        'return_url' => 'PAYMOB_RETURN_URL',
        'webhook_url' => 'PAYMOB_WEBHOOK_URL',
    ),
);
