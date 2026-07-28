<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!class_exists('Youngo_paymob_config') && defined('APPPATH') && is_file(APPPATH . 'libraries/Youngo_paymob_config.php')) {
    require_once APPPATH . 'libraries/Youngo_paymob_config.php';
}

class Youngo_paymob_webhook
{
    protected $config;
    protected $diagnostic_hmac_secret;

    protected $hmac_fields = array(
        'amount_cents',
        'created_at',
        'currency',
        'error_occured',
        'has_parent_transaction',
        'id',
        'integration_id',
        'is_3d_secure',
        'is_auth',
        'is_capture',
        'is_refunded',
        'is_standalone_payment',
        'is_voided',
        'order.id',
        'owner',
        'pending',
        'source_data.pan',
        'source_data.sub_type',
        'source_data.type',
        'success',
    );

    public function __construct($params = array())
    {
        if (isset($params['config_reader']) && $params['config_reader'] instanceof Youngo_paymob_config) {
            $this->config = $params['config_reader'];
        } else {
            $this->config = new Youngo_paymob_config(isset($params['config_params']) && is_array($params['config_params'])
                ? $params['config_params']
                : array());
        }

        $this->diagnostic_hmac_secret = isset($params['diagnostic_hmac_secret'])
            ? (string) $params['diagnostic_hmac_secret']
            : null;
    }

    public function normalize_payload($payload)
    {
        if (is_object($payload)) {
            $payload = json_decode(json_encode($payload), true);
        }

        if (!is_array($payload)) {
            return $this->failure_result('invalid_payload', 'Paymob payload must be an array.');
        }

        $normalized = $payload;
        foreach ($normalized as $key => $value) {
            if (is_string($value)) {
                $normalized[$key] = trim($value);
            }
        }

        if (isset($normalized['obj']) && is_array($normalized['obj'])) {
            $normalized = array_merge($normalized['obj'], array_diff_key($normalized, array('obj' => true)));
        }

        if (isset($normalized['hmac'])) {
            $normalized['hmac'] = strtolower(trim((string) $normalized['hmac']));
        }

        return $this->success_result('payload_normalized', 'Paymob payload normalized.', array(
            'payload' => $normalized,
        ));
    }

    public function get_hmac_source_fields($payload)
    {
        $normalized = $this->payload_or_failure($payload);
        if (empty($normalized['ok'])) {
            return $normalized;
        }

        $payload = $normalized['payload'];
        $fields = array();
        foreach ($this->hmac_fields as $field) {
            $fields[$field] = $this->string_value($this->array_get_path($payload, $field));
        }

        return $this->success_result('hmac_source_fields_built', 'HMAC source fields built in Paymob transaction callback order.', array(
            'fields' => $fields,
            'source_string' => implode('', array_values($fields)),
        ));
    }

    public function calculate_hmac($payload, $hmac_secret)
    {
        $hmac_secret = (string) $hmac_secret;
        if (trim($hmac_secret) === '') {
            return $this->failure_result('missing_hmac_secret', 'HMAC secret is required for verification.');
        }

        $source = $this->get_hmac_source_fields($payload);
        if (empty($source['ok'])) {
            return $source;
        }

        return $this->success_result('hmac_calculated', 'HMAC calculated for fixture verification.', array(
            'hmac' => hash_hmac('sha512', $source['data']['source_string'], $hmac_secret),
        ));
    }

    public function verify_hmac($payload, $provided_hmac)
    {
        $normalized = $this->payload_or_failure($payload);
        if (empty($normalized['ok'])) {
            return $normalized;
        }

        $provided_hmac = strtolower(trim((string) $provided_hmac));
        if ($provided_hmac === '') {
            return $this->failure_result('missing_hmac', 'Paymob callback HMAC is required.');
        }

        $secret = $this->diagnostic_hmac_secret !== null
            ? $this->diagnostic_hmac_secret
            : $this->config->get('hmac_secret');

        $calculated = $this->calculate_hmac($normalized['payload'], $secret);
        if (empty($calculated['ok'])) {
            return $calculated;
        }

        $expected = strtolower((string) $calculated['data']['hmac']);
        $verified = function_exists('hash_equals')
            ? hash_equals($expected, $provided_hmac)
            : $expected === $provided_hmac;

        if (!$verified) {
            return $this->failure_result('hmac_verification_failed', 'Paymob callback HMAC verification failed.');
        }

        return $this->success_result('hmac_verified', 'Paymob callback HMAC verified for fixture payload.', array(
            'hmac_verified' => true,
        ));
    }

    public function classify_event($payload)
    {
        $normalized = $this->payload_or_failure($payload);
        if (empty($normalized['ok'])) {
            return $normalized;
        }

        $payload = $normalized['payload'];
        $success = $this->truthy($this->array_get_path($payload, 'success'));
        $pending = $this->truthy($this->array_get_path($payload, 'pending'));
        $error = $this->truthy($this->array_get_path($payload, 'error_occured'));
        $voided = $this->truthy($this->array_get_path($payload, 'is_voided'));
        $refunded = $this->truthy($this->array_get_path($payload, 'is_refunded'));

        if ($refunded) {
            $status = 'refunded';
        } elseif ($voided) {
            $status = 'voided';
        } elseif ($pending) {
            $status = 'pending';
        } elseif ($success && !$error) {
            $status = 'success';
        } elseif ($error || !$success) {
            $status = 'failed';
        } else {
            $status = 'unknown';
        }

        return $this->success_result('event_classified', 'Paymob event classified from normalized payload.', array(
            'event_type' => 'transaction_processed',
            'status' => $status,
            'trusted' => false,
        ));
    }

    public function extract_gateway_refs($payload)
    {
        $normalized = $this->payload_or_failure($payload);
        if (empty($normalized['ok'])) {
            return $normalized;
        }

        $payload = $normalized['payload'];

        return $this->success_result('gateway_refs_extracted', 'Gateway references extracted from payload.', array(
            'provider_transaction_id' => $this->string_or_null($this->array_get_path($payload, 'id')),
            'provider_order_id' => $this->string_or_null($this->array_get_path($payload, 'order.id')),
            'merchant_order_reference' => $this->string_or_null($this->array_get_path($payload, 'order.merchant_order_id')),
            'provider_integration_id' => $this->string_or_null($this->array_get_path($payload, 'integration_id')),
            'currency' => $this->string_or_null($this->array_get_path($payload, 'currency')),
            'amount_cents' => (int) $this->array_get_path($payload, 'amount_cents'),
        ));
    }

    public function get_safe_payload_summary($payload)
    {
        $normalized = $this->payload_or_failure($payload);
        if (empty($normalized['ok'])) {
            return $normalized;
        }

        $payload = $normalized['payload'];
        $refs = $this->extract_gateway_refs($payload);
        $event = $this->classify_event($payload);

        return $this->success_result('safe_payload_summary_built', 'Safe Paymob payload summary built.', array(
            'gateway_provider' => 'paymob',
            'event_type' => !empty($event['data']['event_type']) ? $event['data']['event_type'] : null,
            'status' => !empty($event['data']['status']) ? $event['data']['status'] : null,
            'refs' => !empty($refs['data']) ? $refs['data'] : array(),
            'hmac_present' => isset($payload['hmac']) && trim((string) $payload['hmac']) !== '',
            'hmac' => isset($payload['hmac']) && trim((string) $payload['hmac']) !== '' ? 'configured_redacted' : 'missing',
            'source_data' => array(
                'type' => $this->string_or_null($this->array_get_path($payload, 'source_data.type')),
                'sub_type' => $this->string_or_null($this->array_get_path($payload, 'source_data.sub_type')),
                'pan' => $this->redact_pan($this->array_get_path($payload, 'source_data.pan')),
            ),
        ));
    }

    protected function payload_or_failure($payload)
    {
        $normalized = $this->normalize_payload($payload);
        if (empty($normalized['ok'])) {
            return $normalized;
        }

        $payload = $normalized['data']['payload'];
        $required = array(
            'amount_cents',
            'created_at',
            'currency',
            'id',
            'integration_id',
            'order.id',
            'pending',
            'success',
        );

        $missing = array();
        foreach ($required as $field) {
            $value = $this->array_get_path($payload, $field);
            if ($value === null || $value === '') {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            return $this->failure_result('payload_shape_invalid', 'Paymob payload is missing required transaction fields.', array(
                'missing_fields' => $missing,
            ));
        }

        return array(
            'ok' => true,
            'payload' => $payload,
        );
    }

    protected function array_get_path($array, $path)
    {
        if (!is_array($array)) {
            return null;
        }

        $current = $array;
        foreach (explode('.', $path) as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return null;
            }
            $current = $current[$part];
        }

        return $current;
    }

    protected function string_value($value)
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return trim((string) $value);
    }

    protected function string_or_null($value)
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    protected function truthy($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), array('1', 'true', 'yes'), true);
    }

    protected function redact_pan($value)
    {
        $value = preg_replace('/\D+/', '', (string) $value);
        if ($value === '') {
            return 'missing';
        }

        return strlen($value) > 4 ? 'redacted_' . substr($value, -4) : 'redacted';
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
