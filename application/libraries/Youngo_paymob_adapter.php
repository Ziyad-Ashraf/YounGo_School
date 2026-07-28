<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!class_exists('Youngo_paymob_config') && defined('APPPATH') && is_file(APPPATH . 'libraries/Youngo_paymob_config.php')) {
    require_once APPPATH . 'libraries/Youngo_paymob_config.php';
}

class Youngo_paymob_adapter
{
    protected $config;
    protected $dashboard_config = array();
    protected $dashboard_config_exists = false;

    public function __construct($params = array())
    {
        if (isset($params['config_reader']) && $params['config_reader'] instanceof Youngo_paymob_config) {
            $this->config = $params['config_reader'];
        } else {
            $this->config = new Youngo_paymob_config(isset($params['config_params']) && is_array($params['config_params'])
                ? $params['config_params']
                : array());
        }

        if (isset($params['dashboard_config']) && is_array($params['dashboard_config'])) {
            $this->dashboard_config = $params['dashboard_config'];
        }

        $this->dashboard_config_exists = !empty($params['dashboard_config_exists']);
    }

    public function is_ready_for_sandbox()
    {
        return $this->get_sandbox_readiness();
    }

    public function get_sandbox_readiness()
    {
        $summary = $this->config->get_safe_diagnostic_summary();
        $required = array(
            'enabled' => $this->config->is_enabled(),
            'mode_sandbox' => $this->config->get_mode() === 'sandbox',
            'currency_egp' => $this->config->get_currency() === 'EGP',
            'network_enabled' => $this->config->is_network_enabled(),
            'sandbox_network_testing_enabled' => $this->config->is_sandbox_network_testing_enabled(),
            'checkout_routes_enabled' => $this->config->is_checkout_routes_enabled(),
            'checkout_local_testing_enabled' => $this->config->is_checkout_local_testing_enabled(),
            'dashboard_config_exists' => $this->dashboard_config_exists,
            'secret_key_present' => $this->configured($this->config->get_secret_key_for_runtime()),
            'public_key_present' => $this->configured($this->config->get_public_key_for_runtime()),
            'hmac_secret_present' => $this->configured($this->config->get_hmac_secret_for_runtime()),
            'integration_id_card_egp_present' => $this->configured($this->dashboard_value('card_integration_id_egp')),
            'integration_id_wallet_egp_present' => $this->configured($this->dashboard_value('wallet_integration_id_egp')),
            'api_base_url_present' => $this->configured($this->dashboard_value('api_base_url')),
            'api_base_url_allowed' => $this->paymob_host_allowed($this->dashboard_value('api_base_url')),
            'checkout_base_url_present' => $this->configured($this->dashboard_value('checkout_base_url')),
            'checkout_base_url_allowed' => $this->paymob_host_allowed($this->dashboard_value('checkout_base_url')),
            'return_url_present' => $this->configured($this->dashboard_value('return_url')),
            'webhook_url_present' => $this->configured($this->dashboard_value('notification_url')),
        );

        $missing = array();
        foreach ($required as $name => $ok) {
            if (!$ok) {
                $missing[] = $name;
            }
        }

        $code = 'paymob_sandbox_ready';
        $message = 'Paymob sandbox Intention execution gates are ready.';
        if (!empty($missing)) {
            $code = $this->readiness_code($missing);
            $message = 'Paymob sandbox Intention execution is blocked by missing or disabled gates.';
        }

        return array(
            'ok' => empty($missing),
            'status' => empty($missing) ? 'ready' : 'blocked',
            'code' => $code,
            'message' => $message,
            'readiness' => $required,
            'missing' => $missing,
            'safe_config_summary' => $summary,
        );
    }

    public function build_intention_payload($order, $customer = array())
    {
        $validation = $this->validate_order_for_payload($order);
        if (empty($validation['ok'])) {
            return $validation;
        }

        $order = $validation['order'];
        $billing = $this->build_billing_data($customer);
        $placeholder_names = $this->config->get('placeholder_names', array());

        $payment_methods = array($this->placeholder_or_value(
            $this->dashboard_value('card_integration_id_egp'),
            isset($placeholder_names['integration_id_card_egp']) ? $placeholder_names['integration_id_card_egp'] : 'PAYMOB_INTEGRATION_ID_CARD_EGP'
        ));
        $wallet_integration_id = $this->dashboard_value('wallet_integration_id_egp');
        if ($this->configured($wallet_integration_id)) {
            $payment_methods[] = $wallet_integration_id;
        } elseif (isset($placeholder_names['integration_id_wallet_egp']) && $placeholder_names['integration_id_wallet_egp'] !== '') {
            $payment_methods[] = $placeholder_names['integration_id_wallet_egp'];
        }

        $payload = array(
            'amount' => (int) $order['total_amount_cents'],
            'currency' => 'EGP',
            'payment_methods' => $payment_methods,
            'merchant_order_id' => $order['order_reference'],
            'notification_url' => $this->placeholder_or_value(
                $this->replace_url_placeholders($this->dashboard_value('notification_url'), $order),
                isset($placeholder_names['webhook_url']) ? $placeholder_names['webhook_url'] : 'PAYMOB_WEBHOOK_URL'
            ),
            'redirection_url' => $this->placeholder_or_value(
                $this->replace_url_placeholders($this->dashboard_value('return_url'), $order),
                isset($placeholder_names['return_url']) ? $placeholder_names['return_url'] : 'PAYMOB_RETURN_URL'
            ),
            'billing_data' => $billing,
            'extras' => array(
                'youngo_order_reference' => $order['order_reference'],
                'youngo_checkout_order_id' => isset($order['id']) ? (int) $order['id'] : null,
                'phase' => 'PAYMENT.PAYMOB.ADAPTER.1',
            ),
        );

        return $this->success_result('intention_payload_built', 'Paymob intention payload shape built without network execution.', array(
            'payload' => $payload,
        ));
    }

    public function create_sandbox_intention($order, $customer = array())
    {
        $readiness = $this->get_sandbox_readiness();
        if (empty($readiness['ok'])) {
            return $this->failure_result($readiness['code'], $readiness['message'], array(
                'missing' => isset($readiness['missing']) ? $readiness['missing'] : array(),
                'readiness' => isset($readiness['readiness']) ? $readiness['readiness'] : array(),
            ));
        }

        $payload_result = $this->build_intention_payload($order, $customer);
        if (empty($payload_result['ok'])) {
            return $payload_result;
        }

        $endpoint_result = $this->build_create_intention_endpoint();
        if (empty($endpoint_result['ok'])) {
            return $endpoint_result;
        }

        if (!function_exists('curl_init')) {
            return $this->failure_result('curl_extension_missing', 'PHP curl is required for Paymob sandbox Intention execution.');
        }

        $payload = $payload_result['data']['payload'];
        $secret_key = $this->config->get_secret_key_for_runtime();
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return $this->failure_result('paymob_payload_encode_failed', 'Could not encode Paymob Intention payload.');
        }

        $ch = curl_init($endpoint_result['data']['endpoint']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Token ' . $secret_key,
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int) $this->config->get('network_timeout_seconds', 15));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $body = curl_exec($ch);
        $curl_error = curl_error($ch);
        $http_status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            return $this->failure_result('paymob_intention_network_error', 'Paymob sandbox Intention request failed before receiving a response.', array(
                'curl_error_code' => $curl_error !== '' ? 'configured_redacted' : '',
            ));
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return $this->failure_result('paymob_intention_invalid_json', 'Paymob sandbox Intention response was not valid JSON.', array(
                'http_status' => $http_status,
                'response_hash' => hash('sha256', (string) $body),
            ));
        }

        if ($http_status < 200 || $http_status >= 300) {
            return $this->failure_result('paymob_intention_http_error', 'Paymob sandbox Intention request returned an error response.', array(
                'http_status' => $http_status,
                'response_summary' => $this->safe_response_summary($decoded),
            ));
        }

        $checkout = $this->get_unified_checkout_url_from_response($decoded);
        if (empty($checkout['ok'])) {
            return $this->failure_result($checkout['code'], $checkout['message'], array(
                'http_status' => $http_status,
                'response_summary' => $this->safe_response_summary($decoded),
            ));
        }

        return $this->success_result('paymob_sandbox_intention_created', 'Paymob sandbox Intention created.', array(
            'http_status' => $http_status,
            'provider_intent_id' => $this->first_response_value($decoded, array('id', 'intention_id', 'intention.id')),
            'provider_order_id' => $this->first_response_value($decoded, array('order.id', 'order_id', 'order')),
            'checkout_url' => $checkout['data']['checkout_url'],
            'checkout_url_present' => true,
            'client_secret' => 'configured_redacted',
            'response_summary' => $this->safe_response_summary($decoded),
        ));
    }

    public function create_intention_disabled($order, $customer = array())
    {
        $payload_result = $this->build_intention_payload($order, $customer);

        return $this->failure_result('paymob_network_disabled_in_this_phase', 'Creating Paymob intentions is disabled in this phase.', array(
            'payload_shape_valid' => !empty($payload_result['ok']),
            'payload_error_code' => isset($payload_result['code']) ? $payload_result['code'] : null,
        ));
    }

    public function get_unified_checkout_url_from_response($response)
    {
        if (!is_array($response)) {
            return $this->failure_result('invalid_paymob_response', 'Paymob response must be an array.');
        }

        $client_secret = isset($response['client_secret']) ? trim((string) $response['client_secret']) : '';
        if ($client_secret === '') {
            return $this->failure_result('missing_client_secret', 'Paymob response does not include a client secret.');
        }

        $checkout_url = $this->build_unified_checkout_url($client_secret);
        if (!$this->configured($checkout_url)) {
            return $this->failure_result('checkout_url_build_failed', 'Could not build a Paymob Unified Checkout URL from the sandbox response.');
        }

        return $this->success_result('unified_checkout_response_shape_valid', 'Unified Checkout response shape is valid; sandbox URL was built.', array(
            'client_secret' => 'configured_redacted',
            'public_key' => $this->configured($this->config->get_public_key_for_runtime()) ? 'configured_redacted' : 'missing',
            'checkout_url' => $checkout_url,
            'url_construction' => 'sandbox_enabled',
        ));
    }

    public function verify_hmac_payload_shape($payload)
    {
        if (!is_array($payload)) {
            return $this->failure_result('invalid_hmac_payload', 'HMAC payload must be an array.');
        }

        $required_fields = array(
            'hmac',
            'id',
            'amount_cents',
            'currency',
            'success',
            'pending',
            'order',
            'integration_id',
        );

        $missing = array();
        foreach ($required_fields as $field) {
            if (!array_key_exists($field, $payload) || $payload[$field] === '') {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            return $this->failure_result('hmac_payload_shape_incomplete', 'HMAC payload shape is missing required fields.', array(
                'missing_fields' => $missing,
            ));
        }

        return $this->success_result('hmac_payload_shape_valid', 'HMAC payload shape contains the expected transaction callback fields.', array(
            'hmac_present' => true,
            'verification' => 'not_executed_in_this_phase',
        ));
    }

    public function get_safe_diagnostic_summary()
    {
        $summary = $this->config->get_safe_diagnostic_summary();

        return array(
            'adapter' => 'youngo_paymob_adapter',
            'phase' => 'PAYMENT.PAYMOB.SANDBOX.INTENTION.1',
            'enabled' => $this->config->is_enabled(),
            'network_execution' => $this->config->is_network_enabled() ? 'sandbox_gated' : 'disabled',
            'intention_creation' => $this->config->is_network_enabled() ? 'sandbox_gated' : 'disabled',
            'sandbox_readiness' => $this->safe_readiness_summary($this->get_sandbox_readiness()),
            'config' => $summary,
        );
    }

    protected function build_create_intention_endpoint()
    {
        $api_base_url = $this->dashboard_value('api_base_url');
        if (!$this->valid_https_url($api_base_url)) {
            return $this->failure_result('invalid_paymob_api_base_url', 'Paymob API base URL must be a valid HTTPS URL.');
        }

        $host = parse_url($api_base_url, PHP_URL_HOST);
        if (!in_array(strtolower((string) $host), array('accept.paymob.com'), true)) {
            return $this->failure_result('paymob_api_host_not_allowed', 'Paymob API base URL host is not allowed for sandbox testing.');
        }

        $endpoint = rtrim($api_base_url, '/') . '/v1/intention/';

        return $this->success_result('paymob_intention_endpoint_ready', 'Paymob Intention endpoint is ready.', array(
            'endpoint' => $endpoint,
        ));
    }

    protected function build_unified_checkout_url($client_secret)
    {
        $checkout_base_url = $this->dashboard_value('checkout_base_url');
        $public_key = $this->config->get_public_key_for_runtime();

        if (!$this->valid_https_url($checkout_base_url) || !$this->configured($public_key)) {
            return null;
        }

        $host = parse_url($checkout_base_url, PHP_URL_HOST);
        if (!in_array(strtolower((string) $host), array('accept.paymob.com'), true)) {
            return null;
        }

        $base = rtrim($checkout_base_url, '/');
        if (stripos($base, 'unifiedcheckout') === false) {
            $base .= '/unifiedcheckout';
        }

        return $base . '/?' . http_build_query(array(
            'publicKey' => $public_key,
            'clientSecret' => $client_secret,
        ), '', '&', PHP_QUERY_RFC3986);
    }

    protected function validate_order_for_payload($order)
    {
        if (!is_array($order)) {
            return $this->failure_result('invalid_order', 'Order must be an array.');
        }

        $reference = isset($order['order_reference']) ? trim((string) $order['order_reference']) : '';
        if ($reference === '') {
            return $this->failure_result('missing_order_reference', 'Order reference is required.');
        }

        $currency = isset($order['currency']) ? strtoupper(trim((string) $order['currency'])) : '';
        if ($currency !== 'EGP') {
            return $this->failure_result('unsupported_currency', 'Paymob sandbox payloads currently support EGP only.');
        }

        $amount_cents = isset($order['total_amount_cents']) ? (int) $order['total_amount_cents'] : 0;
        if ($amount_cents <= 0 && isset($order['total_amount']) && is_numeric($order['total_amount'])) {
            $amount_cents = (int) round(((float) $order['total_amount']) * (int) $this->config->get('amount_multiplier', 100));
        }

        if ($amount_cents <= 0) {
            return $this->failure_result('invalid_amount', 'Order amount must be positive and expressed in EGP smallest units.');
        }

        $order['order_reference'] = $reference;
        $order['currency'] = 'EGP';
        $order['total_amount_cents'] = $amount_cents;

        return array(
            'ok' => true,
            'status' => 'valid',
            'code' => 'order_payload_valid',
            'message' => 'Order is valid for Paymob payload shape building.',
            'order' => $order,
        );
    }

    protected function build_billing_data($customer)
    {
        $customer = is_array($customer) ? $customer : array();

        return array(
            'first_name' => $this->clean_text(isset($customer['first_name']) ? $customer['first_name'] : 'YounGo'),
            'last_name' => $this->clean_text(isset($customer['last_name']) ? $customer['last_name'] : 'Learner'),
            'email' => $this->clean_text(isset($customer['email']) ? $customer['email'] : 'learner@example.invalid'),
            'phone_number' => $this->clean_text(isset($customer['phone_number']) ? $customer['phone_number'] : 'NA'),
            'apartment' => 'NA',
            'floor' => 'NA',
            'street' => 'NA',
            'building' => 'NA',
            'city' => 'NA',
            'country' => 'EG',
        );
    }

    protected function clean_text($value)
    {
        $value = trim((string) $value);
        $value = preg_replace('/[\r\n\t]+/', ' ', $value);
        return strlen($value) > 255 ? substr($value, 0, 255) : $value;
    }

    protected function placeholder_or_value($value, $placeholder)
    {
        return $this->configured($value) ? $value : $placeholder;
    }

    protected function dashboard_value($key)
    {
        if (array_key_exists($key, $this->dashboard_config)) {
            return $this->dashboard_config[$key];
        }

        if ($key === 'card_integration_id_egp') {
            return $this->config->get_integration_id_card_egp();
        }

        if ($key === 'wallet_integration_id_egp') {
            return $this->config->get_integration_id_wallet_egp();
        }

        if ($key === 'notification_url') {
            return $this->config->get_webhook_url();
        }

        return $this->config->get($key);
    }

    protected function replace_url_placeholders($value, $order)
    {
        $value = trim((string) $value);
        if ($value === '' || !is_array($order)) {
            return $value;
        }

        $reference = isset($order['order_reference']) ? rawurlencode((string) $order['order_reference']) : '';
        return str_replace(array('{order_reference}', ':order_reference'), $reference, $value);
    }

    protected function readiness_code($missing)
    {
        if (in_array('network_enabled', $missing, true)) {
            return 'paymob_network_disabled_in_this_phase';
        }

        if (in_array('sandbox_network_testing_enabled', $missing, true)) {
            return 'paymob_network_must_remain_disabled';
        }

        if (in_array('dashboard_config_exists', $missing, true)
            || in_array('integration_id_card_egp_present', $missing, true)
            || in_array('integration_id_wallet_egp_present', $missing, true)
            || in_array('api_base_url_present', $missing, true)
            || in_array('api_base_url_allowed', $missing, true)
            || in_array('checkout_base_url_present', $missing, true)
            || in_array('checkout_base_url_allowed', $missing, true)
            || in_array('return_url_present', $missing, true)
            || in_array('webhook_url_present', $missing, true)) {
            return 'blocked_missing_dashboard_config';
        }

        if (in_array('secret_key_present', $missing, true)
            || in_array('public_key_present', $missing, true)
            || in_array('hmac_secret_present', $missing, true)) {
            return 'blocked_missing_private_config';
        }

        return 'paymob_sandbox_gates_not_ready';
    }

    protected function safe_readiness_summary($readiness)
    {
        return array(
            'ok' => !empty($readiness['ok']),
            'code' => isset($readiness['code']) ? $readiness['code'] : null,
            'missing' => isset($readiness['missing']) && is_array($readiness['missing']) ? $readiness['missing'] : array(),
        );
    }

    protected function safe_response_summary($response)
    {
        $summary = array(
            'keys' => array(),
            'provider_intent_id' => $this->presence_label($this->first_response_value($response, array('id', 'intention_id', 'intention.id'))),
            'provider_order_id' => $this->presence_label($this->first_response_value($response, array('order.id', 'order_id', 'order'))),
            'client_secret' => $this->presence_label($this->first_response_value($response, array('client_secret'))),
        );

        foreach (array_keys($response) as $key) {
            $summary['keys'][] = preg_replace('/[^A-Za-z0-9_\\-]/', '', (string) $key);
        }

        return $summary;
    }

    protected function first_response_value($response, $paths)
    {
        foreach ($paths as $path) {
            $value = $this->response_path_value($response, $path);
            if ($this->configured($value)) {
                return is_scalar($value) ? (string) $value : null;
            }
        }

        return null;
    }

    protected function response_path_value($response, $path)
    {
        $current = $response;
        foreach (explode('.', $path) as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return null;
            }
            $current = $current[$part];
        }

        return $current;
    }

    protected function valid_https_url($url)
    {
        $url = trim((string) $url);
        return $url !== '' && filter_var($url, FILTER_VALIDATE_URL) && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https';
    }

    protected function paymob_host_allowed($url)
    {
        if (!$this->valid_https_url($url)) {
            return false;
        }

        return strtolower((string) parse_url($url, PHP_URL_HOST)) === 'accept.paymob.com';
    }

    protected function presence_label($value)
    {
        return $this->configured($value) ? 'configured_redacted' : 'missing';
    }

    protected function configured($value)
    {
        return $value !== null && trim((string) $value) !== '';
    }

    protected function success_result($code, $message, $data = array())
    {
        return array(
            'ok' => true,
            'status' => 'ok',
            'code' => $code,
            'message' => $message,
            'data' => is_array($data) ? $data : array(),
            'errors' => array(),
        );
    }

    protected function failure_result($code, $message, $errors = array())
    {
        return array(
            'ok' => false,
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'data' => array(),
            'errors' => is_array($errors) ? $errors : array($errors),
        );
    }
}
