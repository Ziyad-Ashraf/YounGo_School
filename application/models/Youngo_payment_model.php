<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!class_exists('Youngo_paymob_config') && defined('APPPATH') && is_file(APPPATH . 'libraries/Youngo_paymob_config.php')) {
    require_once APPPATH . 'libraries/Youngo_paymob_config.php';
}

if (!class_exists('Youngo_entitlement_write_model') && defined('APPPATH') && is_file(APPPATH . 'models/Youngo_entitlement_write_model.php')) {
    require_once APPPATH . 'models/Youngo_entitlement_write_model.php';
}

class Youngo_payment_model extends CI_Model
{
    public $db;

    protected $table_exists_cache = array();
    protected $field_exists_cache = array();

    public function __construct($params = array())
    {
        parent::__construct();

        if (isset($params['db']) && is_object($params['db'])) {
            $this->db = $params['db'];
        } else {
            $this->db = $this->db_instance();
        }
    }

    public function record_received_transaction($order_id, $provider, $payload_summary, $gateway_refs)
    {
        $schema = $this->get_schema_readiness();
        if (empty($schema['ready'])) {
            return $this->failure_result('schema_not_ready', 'YounGo payment transaction schema is not ready.', $schema);
        }

        $order = $this->get_order($order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        $provider = $this->clean_provider($provider);
        if ($provider === '') {
            return $this->failure_result('invalid_provider', 'The gateway provider is invalid.');
        }

        if (!is_array($payload_summary)) {
            $payload_summary = array();
        }
        if (!is_array($gateway_refs)) {
            $gateway_refs = array();
        }

        $currency = strtoupper(trim((string) $this->array_value($gateway_refs, 'currency', $this->array_value($payload_summary, 'currency', ''))));
        if ($currency !== 'EGP' || strtoupper((string) $this->array_value($order, 'currency')) !== 'EGP') {
            return $this->failure_result('unsupported_currency', 'YounGo payment transactions currently support EGP only.');
        }

        $event_type = $this->clean_event_type($this->array_value($payload_summary, 'event_type', 'transaction_processed'));
        $gateway_transaction_id = $this->clean_reference($this->array_value($gateway_refs, 'provider_transaction_id'));
        if ($gateway_transaction_id === '') {
            return $this->failure_result('missing_gateway_transaction_id', 'Provider transaction ID is required for idempotency.');
        }

        $merchant_reference = $this->clean_reference($this->array_value($gateway_refs, 'merchant_order_reference'));
        if ($merchant_reference !== '' && $merchant_reference !== (string) $order['order_reference']) {
            return $this->failure_result('order_reference_mismatch', 'Gateway merchant order reference does not match the checkout order.');
        }

        $amount_cents = (int) $this->array_value($gateway_refs, 'amount_cents', 0);
        if ($amount_cents <= 0) {
            return $this->failure_result('invalid_amount', 'Gateway amount must be positive.');
        }

        $duplicate = $this->is_duplicate_gateway_event($provider, $gateway_transaction_id, $event_type);
        if (!empty($duplicate['duplicate'])) {
            return $this->success_result('transaction_duplicate_detected', 'Duplicate gateway event detected.', array(
                'duplicate' => true,
                'existing_transaction_id' => (int) $duplicate['transaction_id'],
                'transaction' => $this->get_safe_transaction_summary($this->get_transaction((int) $duplicate['transaction_id'])),
            ));
        }

        $environment = $this->gateway_environment();
        $now = $this->now();
        $payload = $this->redacted_payload_storage($payload_summary, $gateway_refs);
        $payload_json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $idempotency_key = hash('sha256', implode('|', array(
            'youngo_payment_transaction',
            $provider,
            $environment,
            $gateway_transaction_id,
            $event_type,
        )));

        $data = array(
            'checkout_order_id' => (int) $order['id'],
            'user_id' => (int) $order['user_id'],
            'order_reference' => (string) $order['order_reference'],
            'gateway_provider' => $provider,
            'gateway_environment' => $environment,
            'event_type' => $event_type,
            'status' => 'received',
            'gateway_status' => $this->clean_status($this->array_value($payload_summary, 'status')),
            'currency' => 'EGP',
            'amount_cents' => $amount_cents,
            'amount_decimal' => number_format($amount_cents / 100, 2, '.', ''),
            'provider_intent_id' => $this->clean_reference($this->array_value($gateway_refs, 'provider_intent_id')),
            'provider_order_id' => $this->clean_reference($this->array_value($gateway_refs, 'provider_order_id')),
            'provider_transaction_id' => $gateway_transaction_id,
            'provider_integration_id' => $this->clean_reference($this->array_value($gateway_refs, 'provider_integration_id')),
            'merchant_order_reference' => $merchant_reference,
            'hmac_received' => !empty($payload_summary['hmac_present']) ? 1 : 0,
            'hmac_verified' => 0,
            'verification_source' => null,
            'idempotency_key' => $idempotency_key,
            'payload_hash' => hash('sha256', $payload_json),
            'raw_payload_redacted' => $payload_json,
            'received_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        );

        $this->db->trans_begin();
        $inserted = $this->db->insert('youngo_payment_transactions', $this->filter_columns('youngo_payment_transactions', $data));
        if (!$inserted || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $existing = $this->get_transaction_by_gateway_reference($provider, $gateway_transaction_id);
            if (!empty($existing) && (string) $existing['event_type'] === $event_type) {
                return $this->success_result('transaction_duplicate_detected', 'Duplicate gateway event detected.', array(
                    'duplicate' => true,
                    'existing_transaction_id' => (int) $existing['id'],
                    'transaction' => $this->get_safe_transaction_summary($existing),
                ));
            }

            return $this->failure_result('transaction_record_failed', 'Could not record the payment transaction.');
        }

        $transaction_id = (int) $this->db->insert_id();
        $this->db->trans_commit();

        return $this->success_result('transaction_recorded', 'Payment transaction recorded.', array(
            'transaction_id' => $transaction_id,
            'transaction' => $this->get_safe_transaction_summary($this->get_transaction($transaction_id)),
        ));
    }

    public function mark_transaction_verified($transaction_id, $verification_data = array())
    {
        $transaction = $this->get_transaction($transaction_id);
        if (empty($transaction)) {
            return $this->failure_result('transaction_not_found', 'The payment transaction could not be found.');
        }

        if ((string) $transaction['status'] === 'duplicate') {
            return $this->failure_result('duplicate_transaction_cannot_verify', 'Duplicate transactions cannot be verified.');
        }

        $data = array(
            'status' => 'verified',
            'hmac_verified' => 1,
            'verification_source' => $this->clean_status($this->array_value($verification_data, 'verification_source', 'fixture_hmac')),
            'verified_at' => $this->now(),
            'updated_at' => $this->now(),
        );

        return $this->update_transaction($transaction, $data, 'transaction_verified', 'Payment transaction marked verified.');
    }

    public function mark_transaction_rejected($transaction_id, $reason)
    {
        $transaction = $this->get_transaction($transaction_id);
        if (empty($transaction)) {
            return $this->failure_result('transaction_not_found', 'The payment transaction could not be found.');
        }

        $data = array(
            'status' => 'rejected',
            'error_code' => 'fixture_rejected',
            'error_message' => $this->clean_message($reason),
            'processed_at' => $this->now(),
            'updated_at' => $this->now(),
        );

        return $this->update_transaction($transaction, $data, 'transaction_rejected', 'Payment transaction marked rejected.');
    }

    public function mark_transaction_duplicate($transaction_id, $original_transaction_id = null)
    {
        $transaction = $this->get_transaction($transaction_id);
        if (empty($transaction)) {
            return $this->failure_result('transaction_not_found', 'The payment transaction could not be found.');
        }

        $message = 'Duplicate gateway event.';
        if ($original_transaction_id !== null && (int) $original_transaction_id > 0) {
            $message .= ' Original transaction ID: ' . (int) $original_transaction_id . '.';
        }

        $data = array(
            'status' => 'duplicate',
            'error_code' => 'duplicate_gateway_event',
            'error_message' => $message,
            'processed_at' => $this->now(),
            'updated_at' => $this->now(),
        );

        return $this->update_transaction($transaction, $data, 'transaction_marked_duplicate', 'Payment transaction marked duplicate.');
    }

    public function mark_paid_from_verified_transaction($order_id, $transaction_id)
    {
        $order = $this->get_order($order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        $transaction = $this->get_transaction($transaction_id);
        if (empty($transaction)) {
            return $this->failure_result('transaction_not_found', 'The payment transaction could not be found.');
        }

        $can_mark = $this->can_mark_paid($order);
        if (empty($can_mark['ok'])) {
            return $can_mark;
        }

        $transaction_check = $this->transaction_matches_order($transaction, $order);
        if (empty($transaction_check['ok'])) {
            return $transaction_check;
        }

        if ((string) $transaction['status'] !== 'verified' || (int) $transaction['hmac_verified'] !== 1) {
            return $this->failure_result('transaction_not_verified', 'Only verified HMAC transactions can mark an order paid.');
        }

        if ((string) $transaction['currency'] !== 'EGP' || (int) $transaction['amount_cents'] !== (int) $order['total_amount_cents']) {
            return $this->failure_result('transaction_amount_mismatch', 'Verified transaction amount or currency does not match the checkout order.');
        }

        $now = $this->now();
        $data = array(
            'status' => 'paid',
            'payment_gateway' => 'paymob',
            'gateway_environment' => $this->gateway_environment(),
            'provider_order_id' => $this->clean_reference($this->array_value($transaction, 'provider_order_id')),
            'provider_transaction_id' => $this->clean_reference($this->array_value($transaction, 'provider_transaction_id')),
            'last_hmac_verified' => 1,
            'completed_at' => $now,
            'paid_at' => $now,
            'updated_at' => $now,
            'entitlement_issued' => 0,
            'entitlement_issuance_status' => 'not_started',
        );

        return $this->update_order($order, $data, 'order_marked_paid', 'Checkout order marked paid from verified transaction.');
    }

    public function mark_failed_from_rejected_transaction($order_id, $transaction_id, $reason)
    {
        $order = $this->get_order($order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        $transaction = $this->get_transaction($transaction_id);
        if (empty($transaction)) {
            return $this->failure_result('transaction_not_found', 'The payment transaction could not be found.');
        }

        $can_mark = $this->can_mark_failed($order);
        if (empty($can_mark['ok'])) {
            return $can_mark;
        }

        $transaction_check = $this->transaction_matches_order($transaction, $order);
        if (empty($transaction_check['ok'])) {
            return $transaction_check;
        }

        if ((string) $transaction['status'] !== 'rejected') {
            return $this->failure_result('transaction_not_rejected', 'Only rejected transactions can mark an order failed.');
        }

        $now = $this->now();
        $data = array(
            'status' => 'failed',
            'payment_gateway' => 'paymob',
            'gateway_environment' => $this->gateway_environment(),
            'provider_order_id' => $this->clean_reference($this->array_value($transaction, 'provider_order_id')),
            'provider_transaction_id' => $this->clean_reference($this->array_value($transaction, 'provider_transaction_id')),
            'last_hmac_verified' => !empty($transaction['hmac_verified']) ? 1 : 0,
            'failure_code' => 'fixture_transaction_rejected',
            'failure_message' => $this->clean_message($reason),
            'failed_at' => $now,
            'updated_at' => $now,
            'entitlement_issued' => 0,
            'entitlement_issuance_status' => 'not_started',
        );

        return $this->update_order($order, $data, 'order_marked_failed', 'Checkout order marked failed from rejected transaction.');
    }

    public function can_mark_paid($order)
    {
        if (empty($order) || !is_array($order)) {
            return $this->failure_result('invalid_order', 'A valid checkout order is required.');
        }

        $status = isset($order['status']) ? (string) $order['status'] : '';
        if ($status === 'paid') {
            return $this->failure_result('order_already_paid', 'Paid checkout orders cannot be marked paid again.');
        }

        if (in_array($status, array('failed', 'cancelled', 'expired'), true)) {
            return $this->failure_result('invalid_order_status', 'Failed, cancelled, or expired checkout orders cannot be marked paid.');
        }

        if (!in_array($status, array('draft', 'pending_gateway', 'awaiting_webhook'), true)) {
            return $this->failure_result('invalid_order_status', 'The checkout order cannot be marked paid from its current status.');
        }

        if (strtoupper((string) $this->array_value($order, 'currency')) !== 'EGP') {
            return $this->failure_result('unsupported_currency', 'Only EGP checkout orders can be marked paid.');
        }

        if (!empty($order['entitlement_issued'])) {
            return $this->failure_result('entitlement_already_issued', 'Orders with issued entitlements cannot be changed by this payment status transition.');
        }

        return $this->success_result('order_can_mark_paid', 'Checkout order can be marked paid.');
    }

    public function can_mark_failed($order)
    {
        if (empty($order) || !is_array($order)) {
            return $this->failure_result('invalid_order', 'A valid checkout order is required.');
        }

        $status = isset($order['status']) ? (string) $order['status'] : '';
        if ($status === 'paid') {
            return $this->failure_result('order_already_paid', 'Paid checkout orders cannot be marked failed.');
        }

        if (in_array($status, array('failed', 'cancelled', 'expired'), true)) {
            return $this->failure_result('invalid_order_status', 'Failed, cancelled, or expired checkout orders cannot be marked failed again.');
        }

        if (!in_array($status, array('draft', 'pending_gateway', 'awaiting_webhook'), true)) {
            return $this->failure_result('invalid_order_status', 'The checkout order cannot be marked failed from its current status.');
        }

        return $this->success_result('order_can_mark_failed', 'Checkout order can be marked failed.');
    }

    public function get_payment_status_summary($order_id)
    {
        $order = $this->get_order($order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        $transactions = array();
        foreach ($this->get_transactions_for_order((int) $order['id']) as $transaction) {
            $transactions[] = $this->get_safe_transaction_summary($transaction);
        }

        return $this->success_result('payment_status_summary_built', 'Payment status summary built.', array(
            'order' => $this->get_safe_order_summary($order),
            'transactions' => $transactions,
            'transaction_count' => count($transactions),
        ));
    }

    public function issue_paid_order_entitlement($order_id, $transaction_id)
    {
        $order = $this->get_order($order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        $transaction = $this->get_transaction($transaction_id);
        if (empty($transaction)) {
            return $this->failure_result('transaction_not_found', 'The payment transaction could not be found.');
        }

        $can_issue = $this->can_issue_entitlement_for_order($order);
        if (empty($can_issue['ok'])) {
            return $can_issue;
        }

        $transaction_check = $this->transaction_matches_order($transaction, $order);
        if (empty($transaction_check['ok'])) {
            return $transaction_check;
        }

        if ((string) $transaction['status'] !== 'verified' || (int) $transaction['hmac_verified'] !== 1) {
            return $this->failure_result('transaction_not_verified', 'Only verified HMAC transactions can issue checkout entitlements.');
        }

        if ((string) $transaction['currency'] !== 'EGP' || (int) $transaction['amount_cents'] !== (int) $order['total_amount_cents']) {
            return $this->failure_result('transaction_amount_mismatch', 'Verified transaction amount or currency does not match the checkout order.');
        }

        $entitlement_write_model = $this->entitlement_write_model();
        if (!$entitlement_write_model) {
            return $this->failure_result('entitlement_write_model_unavailable', 'The entitlement write service is unavailable.');
        }

        $issued = $entitlement_write_model->issue_course_purchase_access((int) $order['id'], array(
            'payment_transaction_id' => (int) $transaction['id'],
            'gateway_provider' => 'paymob',
            'gateway_environment' => $this->gateway_environment(),
        ));

        if (empty($issued['ok'])) {
            return $this->failure_result(
                isset($issued['code']) ? $issued['code'] : 'entitlement_issuance_failed',
                isset($issued['message']) ? $issued['message'] : 'Could not issue paid order entitlement.',
                $issued
            );
        }

        return $this->success_result('paid_order_entitlement_issued', 'Paid checkout order entitlement issued.', array(
            'order_id' => (int) $order['id'],
            'transaction_id' => (int) $transaction['id'],
            'issuance' => $issued,
            'summary' => $this->get_entitlement_issuance_summary((int) $order['id']),
        ));
    }

    public function can_issue_entitlement_for_order($order)
    {
        if (empty($order) || !is_array($order)) {
            return $this->failure_result('invalid_order', 'A valid checkout order is required.');
        }

        if ((string) $this->array_value($order, 'status') !== 'paid') {
            return $this->failure_result('order_not_paid', 'Only paid checkout orders can issue entitlements.');
        }

        if ((string) $this->array_value($order, 'order_type') !== 'course_purchase') {
            return $this->failure_result('unsupported_order_type', 'Only course purchase checkout orders are supported in this phase.');
        }

        if (strtoupper((string) $this->array_value($order, 'currency')) !== 'EGP') {
            return $this->failure_result('unsupported_currency', 'Only EGP checkout orders can issue entitlements.');
        }

        if (empty($order['last_hmac_verified'])) {
            return $this->failure_result('order_not_verified', 'Checkout order HMAC verification is required before entitlement issuance.');
        }

        if (!empty($order['entitlement_issued']) || (string) $this->array_value($order, 'entitlement_issuance_status') === 'issued') {
            return $this->failure_result('entitlement_already_issued', 'Checkout entitlement has already been issued.');
        }

        if (!$this->valid_id($this->array_value($order, 'user_id')) || !$this->valid_id($this->array_value($order, 'course_id'))) {
            return $this->failure_result('invalid_order_target', 'Checkout order user or course target is invalid.');
        }

        return $this->success_result('order_can_issue_entitlement', 'Checkout order can issue entitlement.');
    }

    public function get_entitlement_issuance_summary($order_id)
    {
        $order = $this->get_order($order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        $course_access = array();
        $course_access_id = isset($order['entitlement_course_access_id']) ? (int) $order['entitlement_course_access_id'] : 0;
        if ($course_access_id > 0 && $this->table_exists('youngo_course_access')) {
            $query = $this->db
                ->where('id', $course_access_id)
                ->get('youngo_course_access', 1);
            $row = $query && $query->num_rows() > 0 ? $query->row_array() : array();
            if (!empty($row)) {
                $course_access = array(
                    'id' => (int) $row['id'],
                    'user_id' => (int) $row['user_id'],
                    'course_id' => (int) $row['course_id'],
                    'access_source' => (string) $row['access_source'],
                    'status' => (string) $row['status'],
                    'checkout_order_id' => isset($row['checkout_order_id']) ? (int) $row['checkout_order_id'] : null,
                    'is_lifetime' => !empty($row['is_lifetime']),
                    'created_at' => isset($row['created_at']) ? (int) $row['created_at'] : null,
                );
            }
        }

        return $this->success_result('entitlement_issuance_summary_built', 'Entitlement issuance summary built.', array(
            'order' => $this->get_safe_order_summary($order),
            'course_access' => $course_access,
        ));
    }

    public function mark_order_webhook_seen($order_id, $gateway_refs = array(), $hmac_verified = false)
    {
        $order = $this->get_order($order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        if ((string) $order['status'] === 'paid') {
            return $this->failure_result('order_already_paid', 'Paid checkout orders are not updated by fixture processing.');
        }

        $data = array(
            'last_webhook_at' => $this->now(),
            'last_hmac_verified' => $hmac_verified ? 1 : 0,
            'payment_gateway' => 'paymob',
            'gateway_environment' => $this->gateway_environment(),
            'updated_at' => $this->now(),
        );

        if (in_array((string) $order['status'], array('draft', 'pending_gateway'), true)) {
            $data['status'] = 'awaiting_webhook';
        }

        foreach (array(
            'provider_order_id' => 'provider_order_id',
            'provider_transaction_id' => 'provider_transaction_id',
        ) as $target => $source) {
            $value = $this->clean_reference($this->array_value($gateway_refs, $source));
            if ($value !== '') {
                $data[$target] = $value;
            }
        }

        $this->db->trans_begin();
        $updated = $this->db
            ->where('id', (int) $order['id'])
            ->update('youngo_checkout_orders', $this->filter_columns('youngo_checkout_orders', $data));

        if (!$updated || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('order_webhook_update_failed', 'Could not update checkout order webhook metadata.');
        }

        $this->db->trans_commit();

        return $this->success_result('order_webhook_seen', 'Checkout order webhook metadata updated.', array(
            'order_id' => (int) $order['id'],
            'order' => $this->get_safe_order_summary($this->get_order((int) $order['id'])),
        ));
    }

    public function get_transaction($transaction_id)
    {
        if (!$this->valid_id($transaction_id) || !$this->table_exists('youngo_payment_transactions')) {
            return array();
        }

        $query = $this->db
            ->where('id', (int) $transaction_id)
            ->get('youngo_payment_transactions', 1);

        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    public function get_transaction_by_gateway_reference($provider, $gateway_transaction_id)
    {
        $provider = $this->clean_provider($provider);
        $gateway_transaction_id = $this->clean_reference($gateway_transaction_id);
        if ($provider === '' || $gateway_transaction_id === '' || !$this->table_exists('youngo_payment_transactions')) {
            return array();
        }

        $query = $this->db
            ->where('gateway_provider', $provider)
            ->where('gateway_environment', $this->gateway_environment())
            ->where('provider_transaction_id', $gateway_transaction_id)
            ->order_by('id', 'asc')
            ->get('youngo_payment_transactions', 1);

        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    public function get_transactions_for_order($order_id)
    {
        if (!$this->valid_id($order_id) || !$this->table_exists('youngo_payment_transactions')) {
            return array();
        }

        $query = $this->db
            ->where('checkout_order_id', (int) $order_id)
            ->order_by('id', 'asc')
            ->get('youngo_payment_transactions');

        return $query ? $query->result_array() : array();
    }

    public function is_duplicate_gateway_event($provider, $gateway_transaction_id, $event_type)
    {
        $provider = $this->clean_provider($provider);
        $gateway_transaction_id = $this->clean_reference($gateway_transaction_id);
        $event_type = $this->clean_event_type($event_type);
        if ($provider === '' || $gateway_transaction_id === '' || $event_type === '' || !$this->table_exists('youngo_payment_transactions')) {
            return array('duplicate' => false, 'transaction_id' => null);
        }

        $query = $this->db
            ->select('id')
            ->where('gateway_provider', $provider)
            ->where('gateway_environment', $this->gateway_environment())
            ->where('provider_transaction_id', $gateway_transaction_id)
            ->where('event_type', $event_type)
            ->order_by('id', 'asc')
            ->get('youngo_payment_transactions', 1);

        $row = $query && $query->num_rows() > 0 ? $query->row_array() : array();

        return array(
            'duplicate' => !empty($row),
            'transaction_id' => !empty($row['id']) ? (int) $row['id'] : null,
        );
    }

    public function get_safe_transaction_summary($transaction)
    {
        if (empty($transaction) || !is_array($transaction)) {
            return array();
        }

        return array(
            'id' => isset($transaction['id']) ? (int) $transaction['id'] : null,
            'checkout_order_id' => isset($transaction['checkout_order_id']) ? (int) $transaction['checkout_order_id'] : null,
            'user_id' => isset($transaction['user_id']) ? (int) $transaction['user_id'] : null,
            'order_reference' => isset($transaction['order_reference']) ? (string) $transaction['order_reference'] : null,
            'gateway_provider' => isset($transaction['gateway_provider']) ? (string) $transaction['gateway_provider'] : null,
            'gateway_environment' => isset($transaction['gateway_environment']) ? (string) $transaction['gateway_environment'] : null,
            'event_type' => isset($transaction['event_type']) ? (string) $transaction['event_type'] : null,
            'status' => isset($transaction['status']) ? (string) $transaction['status'] : null,
            'gateway_status' => isset($transaction['gateway_status']) ? (string) $transaction['gateway_status'] : null,
            'currency' => isset($transaction['currency']) ? (string) $transaction['currency'] : null,
            'amount_cents' => isset($transaction['amount_cents']) ? (int) $transaction['amount_cents'] : null,
            'amount_decimal' => isset($transaction['amount_decimal']) ? (string) $transaction['amount_decimal'] : null,
            'provider_order_id' => isset($transaction['provider_order_id']) ? (string) $transaction['provider_order_id'] : null,
            'provider_transaction_id' => isset($transaction['provider_transaction_id']) ? (string) $transaction['provider_transaction_id'] : null,
            'provider_integration_id' => isset($transaction['provider_integration_id']) ? (string) $transaction['provider_integration_id'] : null,
            'merchant_order_reference' => isset($transaction['merchant_order_reference']) ? (string) $transaction['merchant_order_reference'] : null,
            'hmac_received' => !empty($transaction['hmac_received']),
            'hmac_verified' => !empty($transaction['hmac_verified']),
            'verification_source' => isset($transaction['verification_source']) ? (string) $transaction['verification_source'] : null,
            'payload_hash' => isset($transaction['payload_hash']) ? (string) $transaction['payload_hash'] : null,
            'received_at' => isset($transaction['received_at']) ? (int) $transaction['received_at'] : null,
            'verified_at' => isset($transaction['verified_at']) ? (int) $transaction['verified_at'] : null,
            'processed_at' => isset($transaction['processed_at']) ? (int) $transaction['processed_at'] : null,
            'created_at' => isset($transaction['created_at']) ? (int) $transaction['created_at'] : null,
            'updated_at' => isset($transaction['updated_at']) ? (int) $transaction['updated_at'] : null,
        );
    }

    public function get_schema_readiness()
    {
        $required_tables = array('youngo_checkout_orders', 'youngo_payment_transactions');
        $required_columns = array(
            'id',
            'checkout_order_id',
            'gateway_provider',
            'gateway_environment',
            'event_type',
            'status',
            'currency',
            'amount_cents',
            'provider_transaction_id',
            'hmac_received',
            'hmac_verified',
            'idempotency_key',
            'raw_payload_redacted',
            'created_at',
            'updated_at',
        );

        $tables = array();
        foreach ($required_tables as $table) {
            $tables[$table] = $this->table_exists($table);
        }

        $columns = array();
        foreach ($required_columns as $column) {
            $columns['youngo_payment_transactions.' . $column] = $this->field_exists('youngo_payment_transactions', $column);
        }

        return array(
            'tables' => $tables,
            'columns' => $columns,
            'ready' => !in_array(false, $tables, true) && !in_array(false, $columns, true),
        );
    }

    protected function update_transaction($transaction, $data, $code, $message)
    {
        $this->db->trans_begin();
        $updated = $this->db
            ->where('id', (int) $transaction['id'])
            ->update('youngo_payment_transactions', $this->filter_columns('youngo_payment_transactions', $data));

        if (!$updated || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('transaction_update_failed', 'Could not update the payment transaction.');
        }

        $this->db->trans_commit();

        return $this->success_result($code, $message, array(
            'transaction_id' => (int) $transaction['id'],
            'transaction' => $this->get_safe_transaction_summary($this->get_transaction((int) $transaction['id'])),
        ));
    }

    protected function update_order($order, $data, $code, $message)
    {
        $this->db->trans_begin();
        $updated = $this->db
            ->where('id', (int) $order['id'])
            ->update('youngo_checkout_orders', $this->filter_columns('youngo_checkout_orders', $data));

        if (!$updated || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('order_update_failed', 'Could not update the checkout order.');
        }

        $this->db->trans_commit();

        return $this->success_result($code, $message, array(
            'order_id' => (int) $order['id'],
            'order' => $this->get_safe_order_summary($this->get_order((int) $order['id'])),
        ));
    }

    protected function transaction_matches_order($transaction, $order)
    {
        if ((int) $transaction['checkout_order_id'] !== (int) $order['id']) {
            return $this->failure_result('transaction_order_mismatch', 'Transaction does not belong to the checkout order.');
        }

        if ((string) $transaction['order_reference'] !== (string) $order['order_reference']) {
            return $this->failure_result('transaction_order_reference_mismatch', 'Transaction order reference does not match the checkout order.');
        }

        return $this->success_result('transaction_matches_order', 'Transaction matches checkout order.');
    }

    protected function entitlement_write_model()
    {
        if (!class_exists('Youngo_entitlement_write_model')) {
            return null;
        }

        return new Youngo_entitlement_write_model(array('db' => $this->db_instance()));
    }

    protected function get_order($order_id)
    {
        if (!$this->valid_id($order_id) || !$this->table_exists('youngo_checkout_orders')) {
            return array();
        }

        $query = $this->db
            ->where('id', (int) $order_id)
            ->get('youngo_checkout_orders', 1);

        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    protected function get_safe_order_summary($order)
    {
        if (empty($order) || !is_array($order)) {
            return array();
        }

        return array(
            'id' => isset($order['id']) ? (int) $order['id'] : null,
            'order_reference' => isset($order['order_reference']) ? (string) $order['order_reference'] : null,
            'status' => isset($order['status']) ? (string) $order['status'] : null,
            'last_hmac_verified' => !empty($order['last_hmac_verified']),
            'last_webhook_at' => isset($order['last_webhook_at']) ? (int) $order['last_webhook_at'] : null,
            'provider_order_id' => isset($order['provider_order_id']) ? (string) $order['provider_order_id'] : null,
            'provider_transaction_id' => isset($order['provider_transaction_id']) ? (string) $order['provider_transaction_id'] : null,
            'entitlement_issued' => !empty($order['entitlement_issued']),
            'entitlement_issuance_status' => isset($order['entitlement_issuance_status']) ? (string) $order['entitlement_issuance_status'] : null,
            'paid_at' => isset($order['paid_at']) ? (int) $order['paid_at'] : null,
            'failed_at' => isset($order['failed_at']) ? (int) $order['failed_at'] : null,
        );
    }

    protected function redacted_payload_storage($payload_summary, $gateway_refs)
    {
        return array(
            'payload_summary' => $this->redact_recursive($payload_summary),
            'gateway_refs' => $this->redact_recursive($gateway_refs),
            'storage' => 'redacted_safe_summary_only',
        );
    }

    protected function redact_recursive($value)
    {
        if (is_array($value)) {
            $redacted = array();
            foreach ($value as $key => $item) {
                $key_string = strtolower((string) $key);
                if (strpos($key_string, 'secret') !== false || strpos($key_string, 'hmac') !== false || strpos($key_string, 'token') !== false || strpos($key_string, 'key') !== false) {
                    $redacted[$key] = $item === null || $item === '' ? 'missing' : 'configured_redacted';
                } else {
                    $redacted[$key] = $this->redact_recursive($item);
                }
            }

            return $redacted;
        }

        return $value;
    }

    protected function filter_columns($table, $data)
    {
        $filtered = array();
        foreach ($data as $field => $value) {
            if ($this->field_exists($table, $field)) {
                $filtered[$field] = $value;
            }
        }

        return $filtered;
    }

    protected function table_exists($table)
    {
        if (!$this->safe_identifier($table)) {
            return false;
        }

        if (!array_key_exists($table, $this->table_exists_cache)) {
            $db = $this->db_instance();
            $this->table_exists_cache[$table] = $db ? $db->table_exists($table) : false;
        }

        return $this->table_exists_cache[$table];
    }

    protected function field_exists($table, $field)
    {
        if (!$this->safe_identifier($table) || !$this->safe_identifier($field) || !$this->table_exists($table)) {
            return false;
        }

        $cache_key = $table . '.' . $field;
        if (!array_key_exists($cache_key, $this->field_exists_cache)) {
            $db = $this->db_instance();
            $this->field_exists_cache[$cache_key] = $db ? $db->field_exists($field, $table) : false;
        }

        return $this->field_exists_cache[$cache_key];
    }

    protected function db_instance()
    {
        if (isset($this->db) && is_object($this->db)) {
            return $this->db;
        }

        if (function_exists('get_instance')) {
            $ci = get_instance();
            if (isset($ci->db) && is_object($ci->db)) {
                return $ci->db;
            }
        }

        return null;
    }

    protected function gateway_environment()
    {
        if (class_exists('Youngo_paymob_config')) {
            $config = new Youngo_paymob_config(array('load_local_override' => true));
            return $config->get_mode() === 'live' ? 'live' : 'sandbox';
        }

        return 'sandbox';
    }

    protected function array_value($array, $key, $default = null)
    {
        return is_array($array) && array_key_exists($key, $array) ? $array[$key] : $default;
    }

    protected function clean_provider($value)
    {
        $value = strtolower(trim((string) $value));
        return preg_match('/^[a-z0-9_]+$/', $value) ? $value : '';
    }

    protected function clean_event_type($value)
    {
        $value = strtolower(trim((string) $value));
        return preg_match('/^[a-z0-9_]+$/', $value) ? $value : 'transaction_processed';
    }

    protected function clean_status($value)
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^a-z0-9_\\-]/', '_', $value);
        return strlen($value) > 100 ? substr($value, 0, 100) : $value;
    }

    protected function clean_reference($value)
    {
        $value = trim((string) $value);
        return strlen($value) > 255 ? substr($value, 0, 255) : $value;
    }

    protected function clean_message($value)
    {
        $value = trim((string) $value);
        return strlen($value) > 2000 ? substr($value, 0, 2000) : $value;
    }

    protected function safe_identifier($value)
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9_]+$/', $value);
    }

    protected function valid_id($value)
    {
        return is_numeric($value) && (int) $value > 0;
    }

    protected function now()
    {
        return time();
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
