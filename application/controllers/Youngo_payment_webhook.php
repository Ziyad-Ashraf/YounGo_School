<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!class_exists('Youngo_paymob_config') && is_file(APPPATH . 'libraries/Youngo_paymob_config.php')) {
    require_once APPPATH . 'libraries/Youngo_paymob_config.php';
}

if (!class_exists('Youngo_paymob_webhook') && is_file(APPPATH . 'libraries/Youngo_paymob_webhook.php')) {
    require_once APPPATH . 'libraries/Youngo_paymob_webhook.php';
}

class Youngo_payment_webhook extends CI_Controller
{
    const MAX_PAYLOAD_BYTES = 65536;

    protected $paymob_config;
    protected $paymob_webhook;

    public function __construct()
    {
        parent::__construct();

        $this->paymob_config = new Youngo_paymob_config();
        $this->paymob_webhook = new Youngo_paymob_webhook(array(
            'config_reader' => $this->paymob_config,
        ));

        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }

    public function paymob()
    {
        if ($this->request_method() !== 'POST') {
            return $this->json_response(405, array(
                'ok' => false,
                'status' => 'rejected',
                'code' => 'method_not_allowed',
                'message' => 'Paymob webhook endpoint accepts POST only.',
            ));
        }

        $payload_result = $this->read_json_payload();
        if (empty($payload_result['ok'])) {
            return $this->json_response(400, $payload_result);
        }

        $payload = $payload_result['payload'];
        if (!$this->webhook_testing_enabled()) {
            return $this->json_response(403, array(
                'ok' => false,
                'status' => 'disabled',
                'code' => 'webhook_testing_disabled',
                'message' => 'YounGo Paymob webhook processing is disabled.',
                'data' => array(
                    'provider' => 'paymob',
                    'mode' => $this->paymob_config->get_mode(),
                    'payload_received' => is_array($payload) && !empty($payload),
                ),
            ));
        }

        $normalized = $this->paymob_webhook->normalize_payload($payload);
        if (empty($normalized['ok'])) {
            return $this->json_response(422, $this->safe_result($normalized));
        }

        $payload = $normalized['data']['payload'];
        $provided_hmac = isset($payload['hmac']) ? $payload['hmac'] : '';
        $verification = $this->paymob_webhook->verify_hmac($payload, $provided_hmac);
        if (empty($verification['ok'])) {
            return $this->json_response(422, $this->safe_result($verification));
        }

        $summary = $this->paymob_webhook->get_safe_payload_summary($payload);

        return $this->json_response(202, array(
            'ok' => true,
            'status' => 'validated_no_write',
            'code' => 'webhook_validated_no_write',
            'message' => 'Paymob webhook payload validated. Transaction recording is disabled in this phase.',
            'data' => !empty($summary['data']) ? $summary['data'] : array(),
        ));
    }

    protected function webhook_testing_enabled()
    {
        return (bool) $this->paymob_config->get('webhook_testing_enabled', false);
    }

    protected function read_json_payload()
    {
        $raw_input = file_get_contents('php://input');
        if (!is_string($raw_input)) {
            $raw_input = '';
        }

        if (strlen($raw_input) > self::MAX_PAYLOAD_BYTES) {
            return array(
                'ok' => false,
                'status' => 'rejected',
                'code' => 'payload_too_large',
                'message' => 'Webhook payload is too large.',
            );
        }

        if (trim($raw_input) === '') {
            return array(
                'ok' => true,
                'status' => 'ok',
                'code' => 'empty_payload',
                'message' => 'Empty webhook payload received.',
                'payload' => array(),
            );
        }

        $payload = json_decode($raw_input, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
            return array(
                'ok' => false,
                'status' => 'rejected',
                'code' => 'invalid_json',
                'message' => 'Webhook payload must be valid JSON.',
            );
        }

        return array(
            'ok' => true,
            'status' => 'ok',
            'code' => 'payload_read',
            'message' => 'Webhook payload read.',
            'payload' => $payload,
        );
    }

    protected function request_method()
    {
        return isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
    }

    protected function safe_result($result)
    {
        return array(
            'ok' => !empty($result['ok']),
            'status' => isset($result['status']) ? $result['status'] : 'error',
            'code' => isset($result['code']) ? $result['code'] : 'unknown_error',
            'message' => isset($result['message']) ? $result['message'] : 'Webhook validation failed.',
            'data' => isset($result['data']) && is_array($result['data']) ? $result['data'] : array(),
            'errors' => isset($result['errors']) && is_array($result['errors']) ? $result['errors'] : array(),
        );
    }

    protected function json_response($http_status, $body)
    {
        $this->output
            ->set_status_header((int) $http_status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($body, JSON_UNESCAPED_SLASHES));

        return;
    }
}
