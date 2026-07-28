<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Youngo_paymob_config
{
    protected $config = array();
    protected $default_config_path;
    protected $local_override_path;
    protected $local_override_loaded = false;

    public function __construct($params = array())
    {
        $this->default_config_path = isset($params['default_config_path'])
            ? $params['default_config_path']
            : APPPATH . 'config/youngo_paymob.php';

        $this->local_override_path = isset($params['local_override_path'])
            ? $params['local_override_path']
            : APPPATH . 'config/youngo_paymob.local.php';

        $load_local_override = !array_key_exists('load_local_override', $params) || (bool) $params['load_local_override'];

        $this->config = $this->load_config_array($this->default_config_path, 'youngo_paymob');

        if ($load_local_override && is_file($this->local_override_path)) {
            $local_config = $this->load_config_array($this->local_override_path, 'youngo_paymob_local');
            if (empty($local_config)) {
                $local_config = $this->load_config_array($this->local_override_path, 'youngo_paymob');
            }

            $this->config = $this->merge_config($this->config, $local_config);
            $this->local_override_loaded = true;
        }

        if (isset($params['config']) && is_array($params['config'])) {
            $this->config = $this->merge_config($this->config, $params['config']);
        }

        $this->config = $this->normalize_config($this->config);
    }

    public function is_enabled()
    {
        return (bool) $this->get('enabled', false);
    }

    public function get_mode()
    {
        return (string) $this->get('mode', 'sandbox');
    }

    public function get_currency()
    {
        return (string) $this->get('currency', 'EGP');
    }

    public function get_integration_id_card_egp()
    {
        return $this->get('integration_id_card_egp');
    }

    public function get_integration_id_wallet_egp()
    {
        return $this->get('integration_id_wallet_egp');
    }

    public function get_return_url()
    {
        return $this->get('return_url');
    }

    public function get_webhook_url()
    {
        return $this->get('webhook_url');
    }

    public function is_network_enabled()
    {
        return (bool) $this->get('network_enabled', false);
    }

    public function is_sandbox_network_testing_enabled()
    {
        return (bool) $this->get('sandbox_network_testing_enabled', false);
    }

    public function is_webhook_testing_enabled()
    {
        return (bool) $this->get('webhook_testing_enabled', false);
    }

    public function is_checkout_routes_enabled()
    {
        return (bool) $this->get('checkout_routes_enabled', false);
    }

    public function is_checkout_local_testing_enabled()
    {
        return (bool) $this->get('checkout_local_testing_enabled', false);
    }

    public function is_checkout_cta_enabled()
    {
        return (bool) $this->get('checkout_cta_enabled', false);
    }

    public function get_secret_key_for_runtime()
    {
        return $this->local_override_loaded ? $this->get('secret_key') : null;
    }

    public function get_api_key_for_runtime()
    {
        return $this->local_override_loaded ? $this->get('api_key') : null;
    }

    public function get_public_key_for_runtime()
    {
        return $this->local_override_loaded ? $this->get('public_key') : null;
    }

    public function get_hmac_secret_for_runtime()
    {
        return $this->local_override_loaded ? $this->get('hmac_secret') : null;
    }

    public function get_private_presence_summary()
    {
        $source = $this->local_override_loaded ? 'ignored_local_config' : 'server_config_required';

        return array(
            'storage_mode' => 'hybrid',
            'private_values_source' => $source,
            'private_db_storage' => 'db_private_storage_blocked',
            'local_override_loaded' => $this->local_override_loaded,
            'api_key' => $this->server_presence_label('api_key'),
            'secret_key' => $this->server_presence_label('secret_key'),
            'public_key' => $this->server_presence_label('public_key'),
            'hmac_secret' => $this->server_presence_label('hmac_secret'),
        );
    }

    public function has_required_sandbox_placeholders()
    {
        $required_keys = array(
            'enabled',
            'provider',
            'mode',
            'currency',
            'amount_multiplier',
            'api_key',
            'secret_key',
            'public_key',
            'hmac_secret',
            'integration_id_card_egp',
            'integration_id_wallet_egp',
            'api_base_url',
            'checkout_base_url',
            'return_url',
            'webhook_url',
        );

        foreach ($required_keys as $key) {
            if (!array_key_exists($key, $this->config)) {
                return false;
            }
        }

        $placeholder_names = $this->get('placeholder_names', array());
        foreach (array('api_key', 'secret_key', 'public_key', 'hmac_secret', 'integration_id_card_egp', 'integration_id_wallet_egp', 'api_base_url', 'checkout_base_url', 'return_url', 'webhook_url') as $key) {
            if (!isset($placeholder_names[$key]) || $placeholder_names[$key] === '') {
                return false;
            }
        }

        return true;
    }

    public function get_safe_diagnostic_summary()
    {
        return array(
            'provider' => $this->get('provider', 'paymob'),
            'enabled' => $this->is_enabled(),
            'mode' => $this->get_mode(),
            'currency' => $this->get_currency(),
            'amount_multiplier' => (int) $this->get('amount_multiplier', 100),
            'network_enabled' => $this->is_network_enabled(),
            'sandbox_network_testing_enabled' => $this->is_sandbox_network_testing_enabled(),
            'webhook_testing_enabled' => $this->is_webhook_testing_enabled(),
            'checkout_routes_enabled' => $this->is_checkout_routes_enabled(),
            'checkout_local_testing_enabled' => $this->is_checkout_local_testing_enabled(),
            'checkout_cta_enabled' => $this->is_checkout_cta_enabled(),
            'live_mode_allowed' => (bool) $this->get('live_mode_allowed', false),
            'local_override_loaded' => $this->local_override_loaded,
            'required_placeholders_present' => $this->has_required_sandbox_placeholders(),
            'hybrid_private_presence' => $this->get_private_presence_summary(),
            'api_key' => $this->presence_label($this->get('api_key')),
            'secret_key' => $this->presence_label($this->get('secret_key')),
            'public_key' => $this->presence_label($this->get('public_key')),
            'hmac_secret' => $this->presence_label($this->get('hmac_secret')),
            'integration_id_card_egp' => $this->presence_label($this->get('integration_id_card_egp')),
            'integration_id_wallet_egp' => $this->presence_label($this->get('integration_id_wallet_egp')),
            'api_base_url' => $this->presence_label($this->get('api_base_url')),
            'checkout_base_url' => $this->presence_label($this->get('checkout_base_url')),
            'return_url' => $this->presence_label($this->get('return_url')),
            'webhook_url' => $this->presence_label($this->get('webhook_url')),
            'base_url' => $this->presence_label($this->get('base_url')),
        );
    }

    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->config) ? $this->config[$key] : $default;
    }

    protected function load_config_array($path, $config_key)
    {
        if (!is_file($path)) {
            return array();
        }

        $config = array();
        include $path;

        return isset($config[$config_key]) && is_array($config[$config_key])
            ? $config[$config_key]
            : array();
    }

    protected function normalize_config($config)
    {
        $config['enabled'] = !empty($config['enabled']);
        $config['provider'] = isset($config['provider']) && $config['provider'] !== '' ? strtolower((string) $config['provider']) : 'paymob';
        $config['mode'] = isset($config['mode']) && strtolower((string) $config['mode']) === 'live' ? 'live' : 'sandbox';
        $config['currency'] = isset($config['currency']) && $config['currency'] !== '' ? strtoupper((string) $config['currency']) : 'EGP';
        $config['amount_multiplier'] = isset($config['amount_multiplier']) ? (int) $config['amount_multiplier'] : 100;
        $config['network_enabled'] = !empty($config['network_enabled']);
        $config['sandbox_network_testing_enabled'] = !empty($config['sandbox_network_testing_enabled']);
        $config['webhook_testing_enabled'] = !empty($config['webhook_testing_enabled']);
        $config['checkout_routes_enabled'] = !empty($config['checkout_routes_enabled']);
        $config['checkout_local_testing_enabled'] = !empty($config['checkout_local_testing_enabled']);
        $config['checkout_cta_enabled'] = !empty($config['checkout_cta_enabled']);
        $config['live_mode_allowed'] = !empty($config['live_mode_allowed']);

        if ($config['mode'] === 'live' && empty($config['live_mode_allowed'])) {
            $config['enabled'] = false;
        }

        if ($config['currency'] !== 'EGP') {
            $config['enabled'] = false;
        }

        return $config;
    }

    protected function merge_config($base, $override)
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->merge_config($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    protected function presence_label($value)
    {
        return ($value === null || $value === '') ? 'missing' : 'configured_redacted';
    }

    protected function server_presence_label($key)
    {
        if (!$this->local_override_loaded) {
            return 'server_config_required';
        }

        return $this->presence_label($this->get($key));
    }
}
