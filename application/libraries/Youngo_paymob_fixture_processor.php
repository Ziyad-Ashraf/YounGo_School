<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!class_exists('Youngo_paymob_webhook') && defined('APPPATH') && is_file(APPPATH . 'libraries/Youngo_paymob_webhook.php')) {
    require_once APPPATH . 'libraries/Youngo_paymob_webhook.php';
}

class Youngo_paymob_fixture_processor
{
    protected $webhook;
    protected $checkout_model;
    protected $payment_model;
    protected $apply_order_status_transitions = false;

    public function __construct($params = array())
    {
        $this->webhook = isset($params['webhook']) ? $params['webhook'] : null;
        $this->checkout_model = isset($params['checkout_model']) ? $params['checkout_model'] : null;
        $this->payment_model = isset($params['payment_model']) ? $params['payment_model'] : null;
        $this->apply_order_status_transitions = !empty($params['apply_order_status_transitions']);
    }

    public function process_fixture_payload($payload)
    {
        if (!$this->webhook || !$this->checkout_model || !$this->payment_model) {
            return $this->failure_result('processor_not_ready', 'Fixture processor dependencies are not ready.');
        }

        $normalized = $this->webhook->normalize_payload($payload);
        if (empty($normalized['ok'])) {
            return $this->safe_failure($normalized);
        }

        $payload = $normalized['data']['payload'];
        $provided_hmac = isset($payload['hmac']) ? $payload['hmac'] : '';
        $verification = $this->webhook->verify_hmac($payload, $provided_hmac);
        if (empty($verification['ok'])) {
            return $this->safe_failure($verification);
        }

        $classification = $this->webhook->classify_event($payload);
        if (empty($classification['ok'])) {
            return $this->safe_failure($classification);
        }

        $refs = $this->webhook->extract_gateway_refs($payload);
        if (empty($refs['ok'])) {
            return $this->safe_failure($refs);
        }

        $summary = $this->webhook->get_safe_payload_summary($payload);
        if (empty($summary['ok'])) {
            return $this->safe_failure($summary);
        }

        $gateway_refs = $refs['data'];
        $payload_summary = $summary['data'];
        $order_reference = isset($gateway_refs['merchant_order_reference']) ? trim((string) $gateway_refs['merchant_order_reference']) : '';
        if ($order_reference === '') {
            return $this->failure_result('missing_order_reference', 'Fixture payload does not include a merchant order reference.');
        }

        $order = $this->checkout_model->get_order_by_reference($order_reference);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'No local checkout order matches the fixture payload.');
        }

        if (isset($classification['data']['event_type'])) {
            $payload_summary['event_type'] = $classification['data']['event_type'];
        }
        if (isset($classification['data']['status'])) {
            $payload_summary['status'] = $classification['data']['status'];
        }

        $record = $this->payment_model->record_received_transaction((int) $order['id'], 'paymob', $payload_summary, $gateway_refs);
        if (empty($record['ok'])) {
            return $record;
        }

        if (!empty($record['data']['duplicate'])) {
            return $this->success_result('fixture_duplicate_detected', 'Fixture duplicate gateway event detected.', array(
                'order_id' => (int) $order['id'],
                'order_reference' => $order_reference,
                'duplicate' => true,
                'existing_transaction_id' => isset($record['data']['existing_transaction_id']) ? (int) $record['data']['existing_transaction_id'] : null,
            ));
        }

        $transaction_id = isset($record['data']['transaction_id']) ? (int) $record['data']['transaction_id'] : 0;
        $event_status = isset($classification['data']['status']) ? (string) $classification['data']['status'] : 'unknown';

        $order_update = $this->payment_model->mark_order_webhook_seen((int) $order['id'], $gateway_refs, true);

        if ($event_status === 'success') {
            $final = $this->payment_model->mark_transaction_verified($transaction_id, array(
                'verification_source' => 'fixture_hmac',
            ));
        } else {
            $final = $this->payment_model->mark_transaction_rejected($transaction_id, 'Fixture gateway status: ' . $event_status);
        }

        if (empty($final['ok'])) {
            return $final;
        }

        $order_status = array();
        if ($this->apply_order_status_transitions) {
            if ($event_status === 'success') {
                $order_status = $this->payment_model->mark_paid_from_verified_transaction((int) $order['id'], $transaction_id);
            } else {
                $order_status = $this->payment_model->mark_failed_from_rejected_transaction((int) $order['id'], $transaction_id, 'Fixture gateway status: ' . $event_status);
            }

            if (empty($order_status['ok'])) {
                return $order_status;
            }
        }

        return $this->success_result('fixture_webhook_processed', 'Fixture webhook payload processed without access issuance.', array(
            'order_id' => (int) $order['id'],
            'order_reference' => $order_reference,
            'transaction_id' => $transaction_id,
            'event_status' => $event_status,
            'transaction' => isset($final['data']['transaction']) ? $final['data']['transaction'] : array(),
            'order_update' => !empty($order_update['ok']) ? $order_update['data'] : array(),
            'order_status_transition' => !empty($order_status['ok']) ? $order_status['data'] : array(),
        ));
    }

    protected function safe_failure($result)
    {
        return $this->failure_result(
            isset($result['code']) ? $result['code'] : 'fixture_processing_failed',
            isset($result['message']) ? $result['message'] : 'Fixture processing failed.',
            isset($result['errors']) ? $result['errors'] : array()
        );
    }

    protected function success_result($code, $message, $data = array())
    {
        return array(
            'ok' => true,
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
            'code' => $code,
            'message' => $message,
            'data' => array(),
            'errors' => is_array($errors) ? $errors : array($errors),
        );
    }
}
