<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!class_exists('Youngo_coupon_evaluator_model') && defined('APPPATH') && is_file(APPPATH . 'models/Youngo_coupon_evaluator_model.php')) {
    require_once APPPATH . 'models/Youngo_coupon_evaluator_model.php';
}

if (!class_exists('Youngo_entitlement_write_model') && defined('APPPATH') && is_file(APPPATH . 'models/Youngo_entitlement_write_model.php')) {
    require_once APPPATH . 'models/Youngo_entitlement_write_model.php';
}

class Youngo_checkout_model extends CI_Model
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

    public function create_draft_order($user_id, $course_id, $amount, $currency = 'EGP')
    {
        $amount_result = $this->normalize_amount($amount, $currency);
        if (empty($amount_result['ok'])) {
            return $amount_result;
        }

        $schema = $this->get_schema_readiness();
        if (empty($schema['ready'])) {
            return $this->failure_result('schema_not_ready', 'YounGo checkout order schema is not ready.', $schema);
        }

        $checkout = $this->can_start_checkout($user_id, $course_id);
        if (empty($checkout['ok'])) {
            return $checkout;
        }

        $existing_order = $this->get_existing_open_course_order((int) $user_id, (int) $course_id);
        if (!empty($existing_order)) {
            return $this->failure_result('open_order_exists', 'An open checkout order already exists for this course.', array(
                'order' => $this->get_safe_order_summary($existing_order),
            ));
        }

        $now = $this->now();
        $order_reference = $this->generate_order_reference();
        $idempotency_key = hash('sha256', 'youngo_checkout_order|' . $order_reference . '|' . (int) $user_id . '|' . (int) $course_id . '|' . microtime(true));

        $data = array(
            'user_id' => (int) $user_id,
            'order_reference' => $order_reference,
            'order_type' => 'course_purchase',
            'status' => 'draft',
            'course_id' => (int) $course_id,
            'plan_id' => null,
            'subtotal_amount' => $amount_result['amount_decimal'],
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'total_amount' => $amount_result['amount_decimal'],
            'total_amount_cents' => $amount_result['amount_cents'],
            'currency' => 'EGP',
            'payment_gateway' => null,
            'gateway_environment' => $this->gateway_environment(),
            'idempotency_key' => $idempotency_key,
            'last_hmac_verified' => 0,
            'entitlement_issued' => 0,
            'entitlement_issuance_status' => 'not_started',
            'metadata' => json_encode(array(
                'source' => 'youngo_checkout_model',
                'phase' => 'PAYMENT.ORDER.1',
            )),
            'created_at' => $now,
            'updated_at' => $now,
        );

        $this->db->trans_begin();
        $inserted = $this->db->insert('youngo_checkout_orders', $this->filter_columns('youngo_checkout_orders', $data));

        if (!$inserted || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('order_create_failed', 'Could not create the checkout order.');
        }

        $order_id = (int) $this->db->insert_id();
        $this->db->trans_commit();

        $order = $this->get_order($order_id);
        return $this->success_result('draft_order_created', 'Draft checkout order created.', array(
            'order_id' => $order_id,
            'order_reference' => $order_reference,
            'order' => $this->get_safe_order_summary($order),
        ));
    }

    public function create_or_reuse_draft_order($user_id, $course_id, $amount, $currency = 'EGP')
    {
        $checkout = $this->can_start_checkout($user_id, $course_id);
        if (empty($checkout['ok'])) {
            return $checkout;
        }

        $existing_order = $this->get_existing_open_course_order((int) $user_id, (int) $course_id);
        if (!empty($existing_order)) {
            return $this->success_result('open_order_reused', 'An open checkout order already exists for this course.', array(
                'order_id' => (int) $existing_order['id'],
                'order_reference' => (string) $existing_order['order_reference'],
                'order' => $this->get_safe_order_summary($existing_order),
                'reused' => true,
            ));
        }

        return $this->create_draft_order($user_id, $course_id, $amount, $currency);
    }

    public function create_draft_order_for_subscription($user_id, $plan_id, $amount, $currency = 'EGP')
    {
        $amount_result = $this->normalize_amount($amount, $currency);
        if (empty($amount_result['ok'])) {
            return $amount_result;
        }

        $schema = $this->get_schema_readiness();
        if (empty($schema['ready'])) {
            return $this->failure_result('schema_not_ready', 'YounGo checkout order schema is not ready.', $schema);
        }

        $checkout = $this->can_start_subscription_checkout($user_id, $plan_id);
        if (empty($checkout['ok'])) {
            return $checkout;
        }

        $existing_order = $this->get_existing_open_plan_order((int) $user_id, (int) $plan_id);
        if (!empty($existing_order)) {
            return $this->failure_result('open_order_exists', 'An open checkout order already exists for this subscription plan.', array(
                'order' => $this->get_safe_order_summary($existing_order),
            ));
        }

        $now = $this->now();
        $order_reference = $this->generate_order_reference();
        $idempotency_key = hash('sha256', 'youngo_checkout_order|' . $order_reference . '|' . (int) $user_id . '|plan|' . (int) $plan_id . '|' . microtime(true));

        $data = array(
            'user_id' => (int) $user_id,
            'order_reference' => $order_reference,
            'order_type' => 'subscription_purchase',
            'status' => 'draft',
            'course_id' => null,
            'plan_id' => (int) $plan_id,
            'subtotal_amount' => $amount_result['amount_decimal'],
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'total_amount' => $amount_result['amount_decimal'],
            'total_amount_cents' => $amount_result['amount_cents'],
            'currency' => 'EGP',
            'payment_gateway' => null,
            'gateway_environment' => $this->gateway_environment(),
            'idempotency_key' => $idempotency_key,
            'last_hmac_verified' => 0,
            'entitlement_issued' => 0,
            'entitlement_issuance_status' => 'not_started',
            'metadata' => json_encode(array(
                'source' => 'youngo_checkout_model',
                'phase' => 'PAYMENT.ORDER.SUBSCRIPTION.1',
            )),
            'created_at' => $now,
            'updated_at' => $now,
        );

        $this->db->trans_begin();
        $inserted = $this->db->insert('youngo_checkout_orders', $this->filter_columns('youngo_checkout_orders', $data));

        if (!$inserted || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('order_create_failed', 'Could not create the checkout order.');
        }

        $order_id = (int) $this->db->insert_id();
        $this->db->trans_commit();

        $order = $this->get_order($order_id);
        return $this->success_result('draft_order_created', 'Draft subscription checkout order created.', array(
            'order_id' => $order_id,
            'order_reference' => $order_reference,
            'order' => $this->get_safe_order_summary($order),
        ));
    }

    public function create_or_reuse_draft_order_for_subscription($user_id, $plan_id, $amount, $currency = 'EGP')
    {
        $checkout = $this->can_start_subscription_checkout($user_id, $plan_id);
        if (empty($checkout['ok'])) {
            return $checkout;
        }

        $existing_order = $this->get_existing_open_plan_order((int) $user_id, (int) $plan_id);
        if (!empty($existing_order)) {
            return $this->success_result('open_order_reused', 'An open checkout order already exists for this subscription plan.', array(
                'order_id' => (int) $existing_order['id'],
                'order_reference' => (string) $existing_order['order_reference'],
                'order' => $this->get_safe_order_summary($existing_order),
                'reused' => true,
            ));
        }

        return $this->create_draft_order_for_subscription($user_id, $plan_id, $amount, $currency);
    }

    public function get_order_by_reference($order_reference)
    {
        $order_reference = trim((string) $order_reference);
        if ($order_reference === '' || !$this->table_exists('youngo_checkout_orders')) {
            return array();
        }

        $query = $this->db
            ->where('order_reference', $order_reference)
            ->get('youngo_checkout_orders', 1);

        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    public function get_order($order_id)
    {
        if (!$this->valid_id($order_id) || !$this->table_exists('youngo_checkout_orders')) {
            return array();
        }

        $query = $this->db
            ->where('id', (int) $order_id)
            ->get('youngo_checkout_orders', 1);

        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    public function mark_pending_gateway($order_id, $gateway_provider, $gateway_reference = null)
    {
        $order = $this->get_order($order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        if (!$this->status_is($order, array('draft', 'pending_gateway'))) {
            return $this->failure_result('invalid_order_status', 'The checkout order cannot be marked pending gateway from its current status.');
        }

        $provider = strtolower(trim((string) $gateway_provider));
        if ($provider === '' || !preg_match('/^[a-z0-9_]+$/', $provider)) {
            return $this->failure_result('invalid_gateway_provider', 'The gateway provider is invalid.');
        }

        $data = array(
            'status' => 'pending_gateway',
            'payment_gateway' => $provider,
            'gateway_environment' => $this->gateway_environment(),
            'payment_started_at' => $this->now(),
            'updated_at' => $this->now(),
        );

        if ($gateway_reference !== null && trim((string) $gateway_reference) !== '') {
            $data['provider_intent_id'] = $this->clean_gateway_reference($gateway_reference);
        }

        return $this->update_order_status($order, $data, 'order_pending_gateway', 'Checkout order marked pending gateway.');
    }

    public function mark_awaiting_webhook($order_id)
    {
        $order = $this->get_order($order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        if (!$this->status_is($order, array('pending_gateway', 'awaiting_webhook'))) {
            return $this->failure_result('invalid_order_status', 'The checkout order cannot be marked awaiting webhook from its current status.');
        }

        return $this->update_order_status($order, array(
            'status' => 'awaiting_webhook',
            'updated_at' => $this->now(),
        ), 'order_awaiting_webhook', 'Checkout order marked awaiting webhook.');
    }

    public function mark_awaiting_webhook_from_paymob_intention($order_id, $intention_result)
    {
        $order = $this->get_order($order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        if (!$this->status_is($order, array('draft', 'pending_gateway', 'awaiting_webhook'))) {
            return $this->failure_result('invalid_order_status', 'The checkout order cannot be linked to a Paymob sandbox Intention from its current status.');
        }

        if (!is_array($intention_result) || empty($intention_result['ok']) || empty($intention_result['data'])) {
            return $this->failure_result('invalid_intention_result', 'A successful Paymob sandbox Intention result is required.');
        }

        $data = $intention_result['data'];
        $provider_intent_id = isset($data['provider_intent_id']) ? $this->clean_gateway_reference($data['provider_intent_id']) : null;
        $provider_order_id = isset($data['provider_order_id']) ? $this->clean_gateway_reference($data['provider_order_id']) : null;

        if ($provider_intent_id === null || $provider_intent_id === '') {
            return $this->failure_result('missing_provider_intent_id', 'Paymob sandbox Intention response did not include a safe intention reference.');
        }

        $now = $this->now();
        $metadata = array(
            'source' => 'youngo_paymob_adapter',
            'phase' => 'PAYMENT.PAYMOB.SANDBOX.INTENTION.1',
            'paymob_client_secret' => !empty($data['client_secret']) ? 'configured_redacted' : 'missing',
            'paymob_checkout_url' => !empty($data['checkout_url_present']) ? 'configured_redacted' : 'missing',
            'paymob_response_summary' => isset($data['response_summary']) && is_array($data['response_summary'])
                ? $data['response_summary']
                : array(),
        );

        return $this->update_order_status($order, array(
            'status' => 'awaiting_webhook',
            'payment_gateway' => 'paymob',
            'gateway_environment' => 'sandbox',
            'provider_intent_id' => $provider_intent_id,
            'provider_order_id' => $provider_order_id,
            'payment_started_at' => $now,
            'metadata' => json_encode($metadata, JSON_UNESCAPED_SLASHES),
            'updated_at' => $now,
        ), 'order_awaiting_paymob_webhook', 'Checkout order linked to Paymob sandbox Intention and marked awaiting webhook.');
    }

    public function mark_cancelled($order_id, $reason = null)
    {
        $order = $this->get_order($order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        if ($this->status_is($order, array('paid'))) {
            return $this->failure_result('order_already_paid', 'Paid checkout orders cannot be cancelled.');
        }

        return $this->update_order_status($order, array(
            'status' => 'cancelled',
            'failure_message' => $this->clean_reason($reason),
            'cancelled_at' => $this->now(),
            'updated_at' => $this->now(),
        ), 'order_cancelled', 'Checkout order cancelled.');
    }

    public function mark_failed($order_id, $reason = null)
    {
        $order = $this->get_order($order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        if ($this->status_is($order, array('paid'))) {
            return $this->failure_result('order_already_paid', 'Paid checkout orders cannot be marked failed.');
        }

        return $this->update_order_status($order, array(
            'status' => 'failed',
            'failure_message' => $this->clean_reason($reason),
            'failed_at' => $this->now(),
            'updated_at' => $this->now(),
        ), 'order_failed', 'Checkout order marked failed.');
    }

    public function can_start_checkout($user_id, $course_id)
    {
        if (!$this->valid_id($user_id)) {
            return $this->failure_result('invalid_user_id', 'A valid learner user is required.');
        }

        if (!$this->valid_id($course_id)) {
            return $this->failure_result('invalid_course_id', 'A valid course is required.');
        }

        if (!$this->table_exists('users') || !$this->table_exists('course')) {
            return $this->failure_result('base_tables_not_ready', 'Required user/course tables are not available.');
        }

        $user = $this->get_row_by_id('users', (int) $user_id);
        if (empty($user)) {
            return $this->failure_result('user_not_found', 'The learner user could not be found.');
        }

        $course = $this->get_row_by_id('course', (int) $course_id);
        if (empty($course)) {
            return $this->failure_result('course_not_found', 'The course could not be found.');
        }

        if (isset($course['status']) && (string) $course['status'] !== 'active') {
            return $this->failure_result('course_not_active', 'This course is not active for checkout.');
        }

        if ($this->course_is_free($course)) {
            return $this->failure_result('payment_not_required', 'Free courses must not create YounGo payment orders.');
        }

        $mode = isset($course['youngo_access_mode']) ? trim((string) $course['youngo_access_mode']) : '';
        if (!in_array($mode, array('purchase_only', 'subscription_and_purchase'), true)) {
            return $this->failure_result('course_not_purchase_enabled', 'This course is not enabled for direct YounGo purchase checkout.');
        }

        if ($this->has_active_course_access((int) $user_id, (int) $course_id)) {
            return $this->failure_result('active_course_access_exists', 'This learner already has active YounGo access to the course.');
        }

        return $this->success_result('checkout_allowed', 'Checkout may start for this course.', array(
            'user_id' => (int) $user_id,
            'course_id' => (int) $course_id,
            'course_mode' => $mode,
        ));
    }

    public function can_start_subscription_checkout($user_id, $plan_id)
    {
        if (!$this->valid_id($user_id)) {
            return $this->failure_result('invalid_user_id', 'A valid learner user is required.');
        }

        if (!$this->valid_id($plan_id)) {
            return $this->failure_result('invalid_plan_id', 'A valid subscription plan is required.');
        }

        if (!$this->table_exists('users') || !$this->table_exists('youngo_subscription_plans')) {
            return $this->failure_result('base_tables_not_ready', 'Required user/subscription plan tables are not available.');
        }

        $user = $this->get_row_by_id('users', (int) $user_id);
        if (empty($user)) {
            return $this->failure_result('user_not_found', 'The learner user could not be found.');
        }

        $plan = $this->get_row_by_id('youngo_subscription_plans', (int) $plan_id);
        if (empty($plan) || !empty($plan['archived_at'])) {
            return $this->failure_result('subscription_plan_not_found', 'The subscription plan could not be found.');
        }

        if (isset($plan['is_active']) && (int) $plan['is_active'] !== 1) {
            return $this->failure_result('subscription_plan_not_active', 'This subscription plan is not active.');
        }

        if (isset($plan['is_purchasable']) && (int) $plan['is_purchasable'] !== 1) {
            return $this->failure_result('subscription_plan_not_purchasable', 'This subscription plan is not enabled for direct purchase.');
        }

        if (strtoupper(trim((string) $this->array_value($plan, 'currency', ''))) !== 'EGP') {
            return $this->failure_result('unsupported_currency', 'YounGo checkout orders currently support EGP only.');
        }

        if ((int) $this->array_value($plan, 'duration_days', 0) <= 0) {
            return $this->failure_result('invalid_plan_duration', 'The selected subscription plan has an invalid duration.');
        }

        if ($this->has_active_subscription((int) $user_id)) {
            return $this->failure_result('active_subscription_exists', 'This learner already has an active YounGo subscription.');
        }

        return $this->success_result('checkout_allowed', 'Checkout may start for this subscription plan.', array(
            'user_id' => (int) $user_id,
            'plan_id' => (int) $plan_id,
        ));
    }

    public function generate_order_reference()
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $reference = 'YGO-' . gmdate('YmdHis') . '-' . strtoupper($this->random_hex(6));
            if (!$this->table_exists('youngo_checkout_orders') || empty($this->get_order_by_reference($reference))) {
                return $reference;
            }
        }

        return 'YGO-' . gmdate('YmdHis') . '-' . strtoupper($this->random_hex(10));
    }

    public function get_safe_order_summary($order)
    {
        if (empty($order) || !is_array($order)) {
            return array();
        }

        return array(
            'id' => isset($order['id']) ? (int) $order['id'] : null,
            'order_reference' => isset($order['order_reference']) ? (string) $order['order_reference'] : null,
            'order_type' => isset($order['order_type']) ? (string) $order['order_type'] : null,
            'status' => isset($order['status']) ? (string) $order['status'] : null,
            'user_id' => isset($order['user_id']) ? (int) $order['user_id'] : null,
            'course_id' => isset($order['course_id']) && $order['course_id'] !== null ? (int) $order['course_id'] : null,
            'plan_id' => isset($order['plan_id']) && $order['plan_id'] !== null ? (int) $order['plan_id'] : null,
            'total_amount' => isset($order['total_amount']) ? (string) $order['total_amount'] : null,
            'total_amount_cents' => isset($order['total_amount_cents']) ? (int) $order['total_amount_cents'] : null,
            'currency' => isset($order['currency']) ? (string) $order['currency'] : null,
            'payment_gateway' => isset($order['payment_gateway']) ? (string) $order['payment_gateway'] : null,
            'gateway_environment' => isset($order['gateway_environment']) ? (string) $order['gateway_environment'] : null,
            'provider_intent_id' => isset($order['provider_intent_id']) ? (string) $order['provider_intent_id'] : null,
            'provider_order_id' => isset($order['provider_order_id']) ? (string) $order['provider_order_id'] : null,
            'provider_transaction_id' => isset($order['provider_transaction_id']) ? (string) $order['provider_transaction_id'] : null,
            'entitlement_issued' => !empty($order['entitlement_issued']),
            'entitlement_issuance_status' => isset($order['entitlement_issuance_status']) ? (string) $order['entitlement_issuance_status'] : null,
            'created_at' => isset($order['created_at']) ? (int) $order['created_at'] : null,
            'updated_at' => isset($order['updated_at']) ? (int) $order['updated_at'] : null,
        );
    }

    public function get_schema_readiness()
    {
        $required_tables = array('users', 'course', 'youngo_checkout_orders');
        $required_columns = array(
            'id',
            'user_id',
            'order_reference',
            'order_type',
            'status',
            'course_id',
            'total_amount',
            'total_amount_cents',
            'currency',
            'payment_gateway',
            'gateway_environment',
            'provider_intent_id',
            'provider_order_id',
            'provider_transaction_id',
            'idempotency_key',
            'entitlement_issued',
            'entitlement_issuance_status',
            'created_at',
            'updated_at',
        );

        $tables = array();
        foreach ($required_tables as $table) {
            $tables[$table] = $this->table_exists($table);
        }

        $columns = array();
        foreach ($required_columns as $column) {
            $columns['youngo_checkout_orders.' . $column] = $this->field_exists('youngo_checkout_orders', $column);
        }

        return array(
            'tables' => $tables,
            'columns' => $columns,
            'ready' => !in_array(false, $tables, true) && !in_array(false, $columns, true),
        );
    }

    public function checkout_snapshot_fields_available()
    {
        $snapshot_columns = array(
            'coupon_discount_type',
            'coupon_discount_value',
            'selected_payment_method',
            'item_title_snapshot',
            'checkout_snapshot_json',
        );

        $columns = array();
        foreach ($snapshot_columns as $column) {
            $columns['youngo_checkout_orders.' . $column] = $this->field_exists('youngo_checkout_orders', $column);
        }

        return array(
            'columns' => $columns,
            'ready' => !in_array(false, $columns, true),
        );
    }

    public function normalize_selected_payment_method($method)
    {
        $method = strtolower(trim((string) $method));
        if ($method === '') {
            return null;
        }

        $method = str_replace(array('-', ' '), '_', $method);
        $method = preg_replace('/_+/', '_', $method);
        $method = trim($method, '_');

        if ($method === '' || !preg_match('/^[a-z0-9_]+$/', $method)) {
            return null;
        }

        return strlen($method) > 50 ? substr($method, 0, 50) : $method;
    }

    public function prepare_coupon_snapshot_fields($coupon_snapshot = array())
    {
        if (!is_array($coupon_snapshot)) {
            $coupon_snapshot = array();
        }

        $fields = array(
            'coupon_id' => null,
            'coupon_code' => null,
            'coupon_discount_type' => null,
            'coupon_discount_value' => null,
            'discount_amount' => null,
        );

        if (array_key_exists('coupon_id', $coupon_snapshot) && $this->valid_id($coupon_snapshot['coupon_id'])) {
            $fields['coupon_id'] = (int) $coupon_snapshot['coupon_id'];
        }

        $coupon_code = $this->array_value($coupon_snapshot, 'coupon_code', $this->array_value($coupon_snapshot, 'code'));
        if ($coupon_code !== null && trim((string) $coupon_code) !== '') {
            $fields['coupon_code'] = $this->clean_snapshot_text($coupon_code, 255);
        }

        $discount_type = strtolower(trim((string) $this->array_value($coupon_snapshot, 'coupon_discount_type', $this->array_value($coupon_snapshot, 'discount_type'))));
        if (in_array($discount_type, array('fixed', 'percentage'), true)) {
            $fields['coupon_discount_type'] = $discount_type;
        }

        $discount_value = $this->normalize_optional_decimal($this->array_value($coupon_snapshot, 'coupon_discount_value', $this->array_value($coupon_snapshot, 'discount_value')));
        if ($discount_value !== null) {
            $fields['coupon_discount_value'] = $discount_value;
        }

        $discount_amount = $this->normalize_optional_decimal($this->array_value($coupon_snapshot, 'discount_amount'));
        if ($discount_amount !== null) {
            $fields['discount_amount'] = $discount_amount;
        }

        return $fields;
    }

    public function build_checkout_snapshot_array($order = array(), $item = array(), $coupon_snapshot = array(), $selected_payment_method = null, $extra = array())
    {
        $order = is_array($order) ? $order : array();
        $item = is_array($item) ? $item : array();
        $extra = is_array($extra) ? $extra : array();

        $coupon_fields = $this->prepare_coupon_snapshot_fields($coupon_snapshot);

        if ($coupon_fields['coupon_id'] === null && $this->valid_id($this->array_value($order, 'coupon_id'))) {
            $coupon_fields['coupon_id'] = (int) $this->array_value($order, 'coupon_id');
        }

        if ($coupon_fields['coupon_code'] === null && trim((string) $this->array_value($order, 'coupon_code')) !== '') {
            $coupon_fields['coupon_code'] = $this->clean_snapshot_text($this->array_value($order, 'coupon_code'), 255);
        }

        if ($coupon_fields['coupon_discount_type'] === null) {
            $order_discount_type = strtolower(trim((string) $this->array_value($order, 'coupon_discount_type')));
            if (in_array($order_discount_type, array('fixed', 'percentage'), true)) {
                $coupon_fields['coupon_discount_type'] = $order_discount_type;
            }
        }

        if ($coupon_fields['coupon_discount_value'] === null) {
            $coupon_fields['coupon_discount_value'] = $this->normalize_optional_decimal($this->array_value($order, 'coupon_discount_value'));
        }

        if ($coupon_fields['discount_amount'] === null) {
            $coupon_fields['discount_amount'] = $this->normalize_optional_decimal($this->array_value($order, 'discount_amount'));
        }

        $method = $this->normalize_selected_payment_method($selected_payment_method);
        if ($method === null) {
            $method = $this->normalize_selected_payment_method($this->array_value($order, 'selected_payment_method'));
        }

        $item_title = $this->array_value($item, 'title', $this->array_value($item, 'name', $this->array_value($order, 'item_title_snapshot')));
        $order_type = (string) $this->array_value($order, 'order_type');
        $item_type = $this->array_value($extra, 'item_type');
        if (!in_array($item_type, array('course', 'subscription'), true)) {
            $item_type = strpos($order_type, 'subscription') !== false ? 'subscription' : 'course';
        }

        return array(
            'snapshot_version' => 'PAYMENT.COUPON.CHECKOUT.SNAPSHOT.SCHEMA.1',
            'created_at' => $this->now(),
            'checkout_source' => $this->clean_snapshot_text($this->array_value($extra, 'checkout_source', 'youngo_checkout'), 100),
            'order_id' => $this->valid_id($this->array_value($order, 'id')) ? (int) $this->array_value($order, 'id') : null,
            'order_reference' => $this->clean_snapshot_text($this->array_value($order, 'order_reference'), 100),
            'user_id' => $this->valid_id($this->array_value($order, 'user_id')) ? (int) $this->array_value($order, 'user_id') : null,
            'order_type' => $this->clean_snapshot_text($order_type, 50),
            'item_type' => $item_type,
            'course_id' => $this->valid_id($this->array_value($order, 'course_id')) ? (int) $this->array_value($order, 'course_id') : null,
            'subscription_plan_id' => $this->valid_id($this->array_value($order, 'plan_id')) ? (int) $this->array_value($order, 'plan_id') : null,
            'item_title_snapshot' => $this->clean_snapshot_text($item_title, 255),
            'original_amount' => $this->normalize_optional_decimal($this->array_value($order, 'subtotal_amount')),
            'coupon_id' => $coupon_fields['coupon_id'],
            'coupon_code' => $coupon_fields['coupon_code'],
            'coupon_discount_type' => $coupon_fields['coupon_discount_type'],
            'coupon_discount_value' => $coupon_fields['coupon_discount_value'],
            'discount_amount' => $coupon_fields['discount_amount'],
            'final_amount' => $this->normalize_optional_decimal($this->array_value($order, 'total_amount')),
            'currency' => strtoupper(trim((string) $this->array_value($order, 'currency', 'EGP'))) === 'EGP' ? 'EGP' : null,
            'selected_payment_method' => $method,
        );
    }

    public function apply_coupon_snapshot_to_order($order_id, $user_id, $coupon_code)
    {
        $order = $this->get_order($order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        $owner = $this->validate_order_owner($order, $user_id);
        if (empty($owner['ok'])) {
            return $owner;
        }

        $editable = $this->coupon_snapshot_order_is_editable($order);
        if (empty($editable['ok'])) {
            return $editable;
        }

        $fields = $this->checkout_snapshot_fields_available();
        if (empty($fields['ready'])) {
            return $this->failure_result('checkout_snapshot_fields_not_ready', 'Checkout coupon snapshot fields are not available.', $fields);
        }

        $item = $this->checkout_order_item_context($order);
        if (empty($item['ok'])) {
            return $item;
        }

        $evaluator = $this->coupon_evaluator();
        if (!$evaluator) {
            return $this->failure_result('coupon_evaluator_not_available', 'YounGo coupon evaluator is not available.');
        }

        $evaluation = $evaluator->evaluate_coupon_for_checkout(
            (int) $order['user_id'],
            $item['data']['item_type'],
            (int) $item['data']['item_id'],
            isset($order['subtotal_amount']) ? $order['subtotal_amount'] : null,
            $coupon_code,
            isset($order['currency']) ? $order['currency'] : 'EGP'
        );

        if (empty($evaluation['valid'])) {
            return $this->failure_result('coupon_snapshot_invalid', 'Coupon could not be applied to this checkout order.', array(
                'coupon_result' => $evaluation,
            ));
        }

        $now = $this->now();
        $item_title = $this->clean_snapshot_text($item['data']['item_title'], 255);
        $updated_order = $order;
        $updated_order['coupon_id'] = (int) $evaluation['coupon_id'];
        $updated_order['coupon_code'] = (string) $evaluation['coupon_code'];
        $updated_order['discount_amount'] = (string) $evaluation['discount_amount'];
        $updated_order['coupon_discount_type'] = (string) $evaluation['discount_type'];
        $updated_order['coupon_discount_value'] = (string) $evaluation['discount_value'];
        $updated_order['total_amount'] = (string) $evaluation['final_amount'];
        $updated_order['total_amount_cents'] = $this->amount_to_cents($evaluation['final_amount']);
        $updated_order['item_title_snapshot'] = $item_title;

        $snapshot = $this->build_order_coupon_snapshot_json($updated_order, $item['data'], $evaluation, $now, 'apply_coupon_snapshot');

        $data = array(
            'coupon_id' => (int) $evaluation['coupon_id'],
            'coupon_code' => (string) $evaluation['coupon_code'],
            'discount_amount' => (string) $evaluation['discount_amount'],
            'coupon_discount_type' => (string) $evaluation['discount_type'],
            'coupon_discount_value' => (string) $evaluation['discount_value'],
            'total_amount' => (string) $evaluation['final_amount'],
            'total_amount_cents' => $this->amount_to_cents($evaluation['final_amount']),
            'currency' => 'EGP',
            'item_title_snapshot' => $item_title,
            'checkout_snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_SLASHES),
            'updated_at' => $now,
        );

        $this->db->trans_begin();
        $updated = $this->db
            ->where('id', (int) $order['id'])
            ->where('status', 'draft')
            ->update('youngo_checkout_orders', $this->filter_columns('youngo_checkout_orders', $data));

        if (!$updated || $this->db->affected_rows() !== 1 || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('coupon_snapshot_update_failed', 'Could not update the checkout order coupon snapshot.');
        }

        $this->db->trans_commit();
        $reloaded = $this->get_order((int) $order['id']);

        return $this->success_result('coupon_snapshot_applied', 'Coupon snapshot applied to checkout order.', array(
            'order_id' => (int) $order['id'],
            'coupon_result' => $evaluation,
            'review_snapshot' => $this->safe_order_review_snapshot_from_row($reloaded),
        ));
    }

    public function clear_coupon_snapshot_from_order($order_id, $user_id)
    {
        $order = $this->get_order($order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        $owner = $this->validate_order_owner($order, $user_id);
        if (empty($owner['ok'])) {
            return $owner;
        }

        $editable = $this->coupon_snapshot_order_is_editable($order);
        if (empty($editable['ok'])) {
            return $editable;
        }

        $original_amount = $this->normalize_optional_decimal(isset($order['subtotal_amount']) ? $order['subtotal_amount'] : null);
        if ($original_amount === null) {
            return $this->failure_result('invalid_original_amount', 'The checkout order original amount is invalid.');
        }

        $item = $this->checkout_order_item_context($order);
        $item_title = !empty($item['ok']) ? $this->clean_snapshot_text($item['data']['item_title'], 255) : $this->clean_snapshot_text(isset($order['item_title_snapshot']) ? $order['item_title_snapshot'] : '', 255);

        $data = array(
            'coupon_id' => null,
            'coupon_code' => null,
            'discount_amount' => '0.00',
            'coupon_discount_type' => null,
            'coupon_discount_value' => null,
            'total_amount' => $original_amount,
            'total_amount_cents' => $this->amount_to_cents($original_amount),
            'currency' => 'EGP',
            'item_title_snapshot' => $item_title !== '' ? $item_title : null,
            'checkout_snapshot_json' => null,
            'updated_at' => $this->now(),
        );

        $this->db->trans_begin();
        $updated = $this->db
            ->where('id', (int) $order['id'])
            ->where('status', 'draft')
            ->update('youngo_checkout_orders', $this->filter_columns('youngo_checkout_orders', $data));

        if (!$updated || $this->db->affected_rows() !== 1 || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('coupon_snapshot_clear_failed', 'Could not clear the checkout order coupon snapshot.');
        }

        $this->db->trans_commit();
        $reloaded = $this->get_order((int) $order['id']);

        return $this->success_result('coupon_snapshot_cleared', 'Coupon snapshot cleared from checkout order.', array(
            'order_id' => (int) $order['id'],
            'review_snapshot' => $this->safe_order_review_snapshot_from_row($reloaded),
        ));
    }

    public function get_safe_order_review_snapshot($order_id, $user_id = null)
    {
        $order = $this->get_order($order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        $owner = $this->validate_order_owner($order, $user_id);
        if (empty($owner['ok'])) {
            return $owner;
        }

        return $this->success_result('order_review_snapshot_loaded', 'Checkout order review snapshot loaded.', array(
            'review_snapshot' => $this->safe_order_review_snapshot_from_row($order),
        ));
    }

    public function get_zero_amount_coupon_completion_context($order_id, $user_id = null)
    {
        $order = $this->get_order($order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        $owner = $this->validate_order_owner($order, $user_id);
        if (empty($owner['ok'])) {
            return $owner;
        }

        return $this->success_result('zero_amount_coupon_completion_context_loaded', 'Zero-amount coupon completion context loaded.', array(
            'zero_amount_coupon' => $this->zero_amount_coupon_context_from_order($order),
        ));
    }

    public function complete_zero_amount_coupon_order($order_id, $user_id)
    {
        if (!$this->valid_id($order_id) || !$this->valid_id($user_id)) {
            return $this->failure_result('invalid_completion_identity', 'A valid checkout order and learner are required.');
        }

        $order = $this->get_order((int) $order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        $owner = $this->validate_order_owner($order, $user_id);
        if (empty($owner['ok'])) {
            return $owner;
        }

        if ($this->zero_amount_coupon_order_already_completed($order)) {
            return $this->success_result('already_completed', 'Zero-amount coupon checkout was already completed.', array(
                'order_id' => (int) $order['id'],
                'order_reference' => isset($order['order_reference']) ? (string) $order['order_reference'] : null,
                'order' => $this->get_safe_order_summary($order),
                'review_snapshot' => $this->safe_order_review_snapshot_from_row($order),
                'zero_amount_coupon' => $this->zero_amount_coupon_context_from_order($order),
            ));
        }

        $validated = $this->validate_zero_amount_coupon_completion_order($order, $user_id);
        if (empty($validated['ok'])) {
            return $validated;
        }

        $item = $validated['data']['item'];
        $evaluation = $validated['data']['coupon_result'];
        $now = $this->now();
        $snapshot = $this->build_zero_amount_coupon_completion_snapshot($order, $item, $evaluation, $now);
        $metadata = $this->build_zero_amount_coupon_completion_metadata($order, $evaluation, $now);

        $this->db->trans_begin();

        $data = array(
            'status' => 'paid',
            'payment_gateway' => 'zero_amount_coupon',
            'selected_payment_method' => 'zero_amount_coupon',
            'provider_intent_id' => null,
            'provider_order_id' => null,
            'provider_transaction_id' => null,
            'payment_id' => null,
            'last_hmac_verified' => 0,
            'completed_at' => $now,
            'paid_at' => $now,
            'checkout_snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_SLASHES),
            'metadata' => json_encode($metadata, JSON_UNESCAPED_SLASHES),
            'entitlement_issued' => 0,
            'entitlement_issuance_status' => 'not_started',
            'entitlement_issuance_error' => null,
            'updated_at' => $now,
        );

        $updated = $this->db
            ->where('id', (int) $order['id'])
            ->where('status', 'draft')
            ->group_start()
            ->where('entitlement_issued', 0)
            ->or_where('entitlement_issued IS NULL', null, false)
            ->group_end()
            ->update('youngo_checkout_orders', $this->filter_columns('youngo_checkout_orders', $data));

        if (!$updated || $this->db->affected_rows() !== 1 || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $reloaded = $this->get_order((int) $order['id']);
            if ($this->zero_amount_coupon_order_already_completed($reloaded)) {
                return $this->success_result('already_completed', 'Zero-amount coupon checkout was already completed.', array(
                    'order_id' => (int) $reloaded['id'],
                    'order_reference' => isset($reloaded['order_reference']) ? (string) $reloaded['order_reference'] : null,
                    'order' => $this->get_safe_order_summary($reloaded),
                    'review_snapshot' => $this->safe_order_review_snapshot_from_row($reloaded),
                    'zero_amount_coupon' => $this->zero_amount_coupon_context_from_order($reloaded),
                ));
            }

            return $this->failure_result('zero_coupon_order_update_failed', 'Could not complete the zero-amount coupon checkout order.');
        }

        $entitlement_model = $this->entitlement_write_model();
        $issue_method = isset($item['item_type']) && $item['item_type'] === 'subscription'
            ? 'issue_zero_amount_coupon_subscription_access'
            : 'issue_zero_amount_coupon_course_access';
        if (!$entitlement_model || !method_exists($entitlement_model, $issue_method)) {
            $this->db->trans_rollback();
            return $this->failure_result('entitlement_write_model_unavailable', 'The entitlement write service is unavailable.');
        }

        $issued = $entitlement_model->{$issue_method}((int) $order['id'], array(
            'completed_at' => $now,
            'source' => 'zero_amount_coupon',
        ));

        if (empty($issued['ok'])) {
            $this->db->trans_rollback();
            return $this->failure_result(
                isset($issued['code']) ? $issued['code'] : 'zero_coupon_entitlement_failed',
                isset($issued['message']) ? $issued['message'] : 'Could not issue zero-amount coupon access.',
                $issued
            );
        }

        $usage = $this->record_zero_amount_coupon_usage($order, $evaluation, $now);
        if (empty($usage['ok'])) {
            $this->db->trans_rollback();
            return $usage;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('zero_coupon_completion_failed', 'Could not complete zero-amount coupon checkout safely.');
        }

        $this->db->trans_commit();

        $reloaded = $this->get_order((int) $order['id']);

        $access_label = isset($item['item_type']) && $item['item_type'] === 'subscription' ? 'subscription' : 'course access';
        return $this->success_result('zero_amount_coupon_completed', 'Zero-amount coupon checkout completed and ' . $access_label . ' activated.', array(
            'order_id' => (int) $order['id'],
            'order_reference' => isset($order['order_reference']) ? (string) $order['order_reference'] : null,
            'order' => $this->get_safe_order_summary($reloaded),
            'review_snapshot' => $this->safe_order_review_snapshot_from_row($reloaded),
            'zero_amount_coupon' => $this->zero_amount_coupon_context_from_order($reloaded),
            'entitlement' => $issued,
            'coupon_usage' => $usage,
        ));
    }

    public function get_admin_coupon_checkout_usage($filter = 'all', $limit = 100, $offset = 0)
    {
        if (!$this->table_exists('youngo_checkout_orders')) {
            return array();
        }

        $limit = is_numeric($limit) && (int) $limit > 0 ? min((int) $limit, 500) : 100;
        $offset = is_numeric($offset) && (int) $offset > 0 ? (int) $offset : 0;

        $this->admin_coupon_checkout_usage_query($filter);
        $query = $this->db
            ->order_by('o.completed_at', 'desc')
            ->order_by('o.paid_at', 'desc')
            ->order_by('o.created_at', 'desc')
            ->limit($limit, $offset)
            ->get();

        if (!$query) {
            return array();
        }

        $rows = array();
        foreach ($query->result_array() as $row) {
            $rows[] = $this->safe_admin_coupon_checkout_usage_row($row);
        }

        return $rows;
    }

    public function count_admin_coupon_checkout_usage_by_filter()
    {
        $counts = array();
        foreach (array('all', 'zero_amount', 'course', 'subscription') as $filter) {
            if (!$this->table_exists('youngo_checkout_orders')) {
                $counts[$filter] = 0;
                continue;
            }

            $this->admin_coupon_checkout_usage_query($filter);
            $counts[$filter] = (int) $this->db->count_all_results();
        }

        return $counts;
    }

    protected function zero_amount_coupon_context_from_order($order)
    {
        $context = array(
            'completion_available' => false,
            'already_completed' => false,
            'subscription_deferred' => false,
            'is_zero_total_coupon_order' => false,
            'item_type' => null,
            'message' => 'Zero-amount coupon completion is not available for this order.',
            'code' => 'not_available',
        );

        if (empty($order) || !is_array($order)) {
            $context['code'] = 'order_missing';
            return $context;
        }

        $item = $this->checkout_order_item_context($order);
        if (!empty($item['ok']) && !empty($item['data']['item_type'])) {
            $context['item_type'] = $item['data']['item_type'];
        }

        $subtotal_cents = $this->amount_to_cents($this->array_value($order, 'subtotal_amount'));
        $total_cents = $this->amount_to_cents($this->array_value($order, 'total_amount'));
        $has_coupon = $this->valid_id($this->array_value($order, 'coupon_id')) && trim((string) $this->array_value($order, 'coupon_code')) !== '';
        $context['is_zero_total_coupon_order'] = $subtotal_cents > 0 && $total_cents === 0 && $has_coupon;

        if ($this->zero_amount_coupon_order_already_completed($order)) {
            $context['already_completed'] = true;
            $context['message'] = 'Zero-amount coupon checkout is complete and access is available.';
            $context['code'] = 'already_completed';
            return $context;
        }

        $validated = $this->validate_zero_amount_coupon_completion_order($order, $this->array_value($order, 'user_id'));
        if (!empty($validated['ok'])) {
            $context['completion_available'] = true;
            $context['message'] = 'No payment is required because this coupon covers the full amount.';
            $context['code'] = 'zero_amount_coupon_completion_available';
            return $context;
        }

        $context['code'] = isset($validated['code']) ? (string) $validated['code'] : 'not_available';
        $context['message'] = isset($validated['message']) ? (string) $validated['message'] : $context['message'];
        return $context;
    }

    protected function validate_zero_amount_coupon_completion_order($order, $user_id)
    {
        if (empty($order) || !is_array($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        $owner = $this->validate_order_owner($order, $user_id);
        if (empty($owner['ok'])) {
            return $owner;
        }

        $item = $this->checkout_order_item_context($order);
        if (empty($item['ok'])) {
            return $item;
        }

        $is_course = $item['data']['item_type'] === 'course' && (string) $this->array_value($order, 'order_type') === 'course_purchase';
        $is_subscription = $item['data']['item_type'] === 'subscription' && (string) $this->array_value($order, 'order_type') === 'subscription_purchase';
        if (!$is_course && !$is_subscription) {
            return $this->failure_result('unsupported_order_type', 'Only course or subscription checkout orders can use zero-amount coupon completion.');
        }

        $editable = $this->coupon_snapshot_order_is_editable($order);
        if (empty($editable['ok'])) {
            return $editable;
        }

        $currency = strtoupper(trim((string) $this->array_value($order, 'currency')));
        if ($currency !== 'EGP') {
            return $this->failure_result('unsupported_currency', 'Only EGP checkout orders can use zero-amount coupon completion.');
        }

        $subtotal_cents = $this->amount_to_cents($this->array_value($order, 'subtotal_amount'));
        $total_cents = $this->amount_to_cents($this->array_value($order, 'total_amount'));
        $discount_cents = $this->amount_to_cents($this->array_value($order, 'discount_amount'));

        if ($subtotal_cents <= 0) {
            return $this->failure_result('invalid_original_amount', 'Zero-amount coupon completion requires a positive original amount.');
        }

        if ($total_cents !== 0) {
            return $this->failure_result('final_amount_not_zero', 'Only checkout orders with a final amount of 0.00 EGP can be completed without payment.');
        }

        if ($discount_cents !== $subtotal_cents) {
            return $this->failure_result('discount_does_not_cover_total', 'The coupon discount must cover the full original amount.');
        }

        if (!$this->valid_id($this->array_value($order, 'coupon_id')) || trim((string) $this->array_value($order, 'coupon_code')) === '') {
            return $this->failure_result('coupon_snapshot_required', 'A valid coupon snapshot is required before zero-amount completion.');
        }

        if (trim((string) $this->array_value($order, 'checkout_snapshot_json')) === '') {
            return $this->failure_result('checkout_snapshot_required', 'Checkout snapshot data is required before zero-amount completion.');
        }

        if ($is_course && $this->has_active_course_access((int) $order['user_id'], (int) $order['course_id'])) {
            return $this->failure_result('active_course_access_exists', 'This learner already has active YounGo access to the course.');
        }
        if ($is_subscription && $this->has_active_subscription((int) $order['user_id'])) {
            return $this->failure_result('active_subscription_exists', 'This learner already has an active YounGo subscription.');
        }

        $evaluator = $this->coupon_evaluator();
        if (!$evaluator) {
            return $this->failure_result('coupon_evaluator_not_available', 'YounGo coupon evaluator is not available.');
        }

        $evaluation = $evaluator->evaluate_coupon_for_checkout(
            (int) $order['user_id'],
            $is_subscription ? 'subscription' : 'course',
            $is_subscription ? (int) $order['plan_id'] : (int) $order['course_id'],
            $this->array_value($order, 'subtotal_amount'),
            $this->array_value($order, 'coupon_code'),
            'EGP'
        );

        if (empty($evaluation['valid'])) {
            return $this->failure_result('coupon_revalidation_failed', 'Coupon is no longer valid for zero-amount completion.', array(
                'coupon_result' => $evaluation,
            ));
        }

        if ((int) $evaluation['coupon_id'] !== (int) $order['coupon_id'] || (string) $evaluation['coupon_code'] !== (string) $order['coupon_code']) {
            return $this->failure_result('coupon_snapshot_mismatch', 'Coupon snapshot no longer matches the current coupon definition.');
        }

        if ($this->amount_to_cents($evaluation['discount_amount']) !== $discount_cents || $this->amount_to_cents($evaluation['final_amount']) !== 0) {
            return $this->failure_result('coupon_amount_mismatch', 'Coupon revalidation no longer produces a zero final amount.');
        }

        if (isset($order['coupon_discount_type']) && (string) $order['coupon_discount_type'] !== '' && (string) $order['coupon_discount_type'] !== (string) $evaluation['discount_type']) {
            return $this->failure_result('coupon_discount_type_mismatch', 'Coupon discount type changed after the checkout snapshot.');
        }

        if (isset($order['coupon_discount_value']) && $order['coupon_discount_value'] !== null && $this->amount_to_cents($order['coupon_discount_value']) !== $this->amount_to_cents($evaluation['discount_value'])) {
            return $this->failure_result('coupon_discount_value_mismatch', 'Coupon discount value changed after the checkout snapshot.');
        }

        return $this->success_result('zero_amount_coupon_order_eligible', 'Checkout order is eligible for zero-amount coupon completion.', array(
            'item' => $item['data'],
            'coupon_result' => $evaluation,
        ));
    }

    protected function admin_coupon_checkout_usage_query($filter)
    {
        $has_coupon_usages = $this->table_exists('youngo_coupon_usages');
        $has_users = $this->table_exists('users');
        $has_courses = $this->table_exists('course');
        $has_plans = $this->table_exists('youngo_subscription_plans');
        $has_course_access = $this->table_exists('youngo_course_access');
        $has_subscriptions = $this->table_exists('youngo_user_subscriptions');

        $select = array(
            'o.id AS checkout_order_id',
            'o.order_reference',
            'o.order_type',
            'o.status',
            'o.user_id',
            'o.course_id',
            'o.plan_id',
            'o.subtotal_amount',
            'o.discount_amount',
            'o.total_amount',
            'o.currency',
            'o.coupon_id',
            'o.coupon_code AS order_coupon_code',
            'o.selected_payment_method',
            'o.payment_gateway',
            'o.entitlement_issued',
            'o.entitlement_issuance_status',
            'o.entitlement_course_access_id',
            'o.entitlement_subscription_id',
            'o.created_at',
            'o.updated_at',
            'o.paid_at',
            'o.completed_at',
            'o.item_title_snapshot',
        );

        $select[] = $has_users ? 'u.first_name AS user_first_name' : 'NULL AS user_first_name';
        $select[] = $has_users ? 'u.last_name AS user_last_name' : 'NULL AS user_last_name';
        $select[] = $has_users ? 'u.email AS user_email' : 'NULL AS user_email';
        $select[] = $has_coupon_usages ? 'cu.id AS coupon_usage_id' : 'NULL AS coupon_usage_id';
        $select[] = $has_coupon_usages ? 'cu.coupon_code AS usage_coupon_code' : 'NULL AS usage_coupon_code';
        $select[] = $has_coupon_usages ? 'cu.discount_amount AS usage_discount_amount' : 'NULL AS usage_discount_amount';
        $select[] = $has_coupon_usages ? 'cu.used_at AS coupon_used_at' : 'NULL AS coupon_used_at';
        $select[] = $has_courses ? 'c.title AS course_title' : 'NULL AS course_title';
        $select[] = $has_plans ? 'p.name AS subscription_plan_name' : 'NULL AS subscription_plan_name';
        $select[] = $has_course_access ? 'ca.id AS course_access_id' : 'NULL AS course_access_id';
        $select[] = $has_course_access ? 'ca.status AS course_access_status' : 'NULL AS course_access_status';
        $select[] = $has_subscriptions ? 'us.id AS subscription_id' : 'NULL AS subscription_id';
        $select[] = $has_subscriptions ? 'us.status AS subscription_status' : 'NULL AS subscription_status';

        $this->db
            ->select(implode(",\n", $select), false)
            ->from('youngo_checkout_orders o');

        if ($has_coupon_usages) {
            $this->db->join('youngo_coupon_usages cu', 'cu.checkout_order_id = o.id', 'left');
        }
        if ($has_users) {
            $this->db->join('users u', 'u.id = o.user_id', 'left');
        }
        if ($has_courses) {
            $this->db->join('course c', 'c.id = o.course_id', 'left');
        }
        if ($has_plans) {
            $this->db->join('youngo_subscription_plans p', 'p.id = o.plan_id', 'left');
        }
        if ($has_course_access) {
            $this->db->join('youngo_course_access ca', 'ca.checkout_order_id = o.id', 'left');
        }
        if ($has_subscriptions) {
            $this->db->join('youngo_user_subscriptions us', 'us.checkout_order_id = o.id', 'left');
        }

        $this->db->group_start();
        if ($has_coupon_usages) {
            $this->db->where('cu.id IS NOT NULL', null, false);
            $this->db->or_where('o.coupon_id IS NOT NULL', null, false);
        } else {
            $this->db->where('o.coupon_id IS NOT NULL', null, false);
        }
        $this->db->or_where("TRIM(COALESCE(o.coupon_code, '')) <> ''", null, false);
        $this->db->group_end();

        $filter = $this->normalize_admin_coupon_usage_filter($filter);
        if ($filter === 'zero_amount') {
            $this->db->group_start();
            $this->db->where('o.selected_payment_method', 'zero_amount_coupon');
            $this->db->or_where('o.payment_gateway', 'zero_amount_coupon');
            $this->db->or_group_start();
            $this->db->where('o.total_amount', '0.00');
            $this->db->where('o.coupon_id IS NOT NULL', null, false);
            $this->db->group_end();
            $this->db->group_end();
        } elseif ($filter === 'course') {
            $this->db->where('o.order_type', 'course_purchase');
        } elseif ($filter === 'subscription') {
            $this->db->where('o.order_type', 'subscription_purchase');
        }
    }

    protected function normalize_admin_coupon_usage_filter($filter)
    {
        $filter = strtolower(trim((string) $filter));
        return in_array($filter, array('all', 'zero_amount', 'course', 'subscription'), true) ? $filter : 'all';
    }

    protected function safe_admin_coupon_checkout_usage_row($row)
    {
        $row = is_array($row) ? $row : array();
        $item_type = (string) $this->array_value($row, 'order_type') === 'subscription_purchase' ? 'subscription' : 'course';
        $coupon_code = trim((string) $this->array_value($row, 'usage_coupon_code')) !== ''
            ? (string) $this->array_value($row, 'usage_coupon_code')
            : (string) $this->array_value($row, 'order_coupon_code');
        $discount_amount = $this->array_value($row, 'usage_discount_amount') !== null && $this->array_value($row, 'usage_discount_amount') !== ''
            ? $this->array_value($row, 'usage_discount_amount')
            : $this->array_value($row, 'discount_amount');

        $item_name = (string) $this->array_value($row, 'item_title_snapshot');
        if ($item_name === '') {
            $item_name = $item_type === 'subscription'
                ? (string) $this->array_value($row, 'subscription_plan_name')
                : (string) $this->array_value($row, 'course_title');
        }

        $access_reference_type = null;
        $access_reference_id = null;
        $access_status = null;
        if ($this->valid_id($this->array_value($row, 'course_access_id'))) {
            $access_reference_type = 'course_access';
            $access_reference_id = (int) $this->array_value($row, 'course_access_id');
            $access_status = (string) $this->array_value($row, 'course_access_status');
        } elseif ($this->valid_id($this->array_value($row, 'subscription_id'))) {
            $access_reference_type = 'subscription';
            $access_reference_id = (int) $this->array_value($row, 'subscription_id');
            $access_status = (string) $this->array_value($row, 'subscription_status');
        } elseif ($this->valid_id($this->array_value($row, 'entitlement_course_access_id'))) {
            $access_reference_type = 'course_access';
            $access_reference_id = (int) $this->array_value($row, 'entitlement_course_access_id');
        } elseif ($this->valid_id($this->array_value($row, 'entitlement_subscription_id'))) {
            $access_reference_type = 'subscription';
            $access_reference_id = (int) $this->array_value($row, 'entitlement_subscription_id');
        }

        $user_name = trim((string) $this->array_value($row, 'user_first_name') . ' ' . (string) $this->array_value($row, 'user_last_name'));
        if ($user_name === '') {
            $user_name = 'User #' . (int) $this->array_value($row, 'user_id');
        }

        return array(
            'checkout_order_id' => (int) $this->array_value($row, 'checkout_order_id'),
            'order_reference' => (string) $this->array_value($row, 'order_reference'),
            'user_id' => (int) $this->array_value($row, 'user_id'),
            'user_name' => $user_name,
            'user_email' => (string) $this->array_value($row, 'user_email'),
            'item_type' => $item_type,
            'item_id' => $item_type === 'subscription' ? (int) $this->array_value($row, 'plan_id') : (int) $this->array_value($row, 'course_id'),
            'item_name' => $item_name,
            'original_amount' => $this->normalize_optional_decimal($this->array_value($row, 'subtotal_amount')),
            'coupon_code' => $coupon_code,
            'discount_amount' => $this->normalize_optional_decimal($discount_amount),
            'final_amount' => $this->normalize_optional_decimal($this->array_value($row, 'total_amount')),
            'currency' => strtoupper(trim((string) $this->array_value($row, 'currency', 'EGP'))) === 'EGP' ? 'EGP' : (string) $this->array_value($row, 'currency'),
            'selected_payment_method' => (string) $this->array_value($row, 'selected_payment_method'),
            'payment_gateway' => (string) $this->array_value($row, 'payment_gateway'),
            'access_reference_type' => $access_reference_type,
            'access_reference_id' => $access_reference_id,
            'access_status' => $access_status,
            'entitlement_issued' => !empty($row['entitlement_issued']),
            'entitlement_issuance_status' => (string) $this->array_value($row, 'entitlement_issuance_status'),
            'created_at' => $this->valid_timestamp($this->array_value($row, 'created_at')),
            'updated_at' => $this->valid_timestamp($this->array_value($row, 'updated_at')),
            'paid_at' => $this->valid_timestamp($this->array_value($row, 'paid_at')),
            'completed_at' => $this->valid_timestamp($this->array_value($row, 'completed_at')),
            'coupon_used_at' => $this->valid_timestamp($this->array_value($row, 'coupon_used_at')),
            'status' => (string) $this->array_value($row, 'status'),
            'coupon_usage_id' => $this->valid_id($this->array_value($row, 'coupon_usage_id')) ? (int) $this->array_value($row, 'coupon_usage_id') : null,
        );
    }

    protected function zero_amount_coupon_order_already_completed($order)
    {
        return is_array($order)
            && (string) $this->array_value($order, 'status') === 'paid'
            && (string) $this->array_value($order, 'selected_payment_method') === 'zero_amount_coupon'
            && (string) $this->array_value($order, 'payment_gateway') === 'zero_amount_coupon'
            && !empty($order['entitlement_issued'])
            && (string) $this->array_value($order, 'entitlement_issuance_status') === 'issued';
    }

    protected function build_zero_amount_coupon_completion_snapshot($order, $item, $evaluation, $completed_at)
    {
        $snapshot = $this->decode_json_array($this->array_value($order, 'checkout_snapshot_json'));
        if (empty($snapshot)) {
            $snapshot = $this->build_order_coupon_snapshot_json($order, $item, $evaluation, $completed_at, 'zero_amount_coupon_completion');
        }

        $snapshot['snapshot_version'] = 'PAYMENT.ZERO.AMOUNT.COUPON.ACCESS.IMPLEMENT.1';
        $snapshot['zero_final_amount_policy_not_enabled'] = false;
        $snapshot['zero_final_amount_coupon_completion_available'] = false;
        $snapshot['zero_amount_coupon_completion'] = array(
            'completed' => true,
            'completed_at' => (int) $completed_at,
            'selected_payment_method' => 'zero_amount_coupon',
            'currency' => 'EGP',
            'access_start_at' => (int) $completed_at,
            'course_only' => isset($item['item_type']) && $item['item_type'] === 'course',
            'subscription_completion' => isset($item['item_type']) && $item['item_type'] === 'subscription' ? 'activated' : 'not_applicable',
            'gateway_hmac_verified' => false,
        );

        if (isset($snapshot['order']) && is_array($snapshot['order'])) {
            $snapshot['order']['selected_payment_method'] = 'zero_amount_coupon';
            $snapshot['order']['final_amount'] = '0.00';
            $snapshot['order']['currency'] = 'EGP';
        }

        return $snapshot;
    }

    protected function build_zero_amount_coupon_completion_metadata($order, $evaluation, $completed_at)
    {
        $metadata = $this->decode_json_array($this->array_value($order, 'metadata'));
        $metadata['zero_amount_coupon_completion'] = array(
            'phase' => 'PAYMENT.ZERO.AMOUNT.COUPON.ACCESS.IMPLEMENT.1',
            'completed_at' => (int) $completed_at,
            'selected_payment_method' => 'zero_amount_coupon',
            'coupon_id' => isset($evaluation['coupon_id']) ? (int) $evaluation['coupon_id'] : null,
            'coupon_code' => isset($evaluation['coupon_code']) ? (string) $evaluation['coupon_code'] : null,
            'original_amount' => isset($order['subtotal_amount']) ? (string) $order['subtotal_amount'] : null,
            'discount_amount' => isset($evaluation['discount_amount']) ? (string) $evaluation['discount_amount'] : null,
            'final_amount' => isset($evaluation['final_amount']) ? (string) $evaluation['final_amount'] : null,
            'currency' => 'EGP',
            'gateway_hmac_verified' => false,
            'instapay_submission_created' => false,
        );

        return $metadata;
    }

    protected function record_zero_amount_coupon_usage($order, $evaluation, $used_at)
    {
        if (!$this->table_exists('youngo_coupon_usages')) {
            return $this->failure_result('coupon_usage_table_missing', 'Coupon usage table is required for zero-amount coupon completion.');
        }

        $existing = (int) $this->db
            ->where('checkout_order_id', (int) $order['id'])
            ->count_all_results('youngo_coupon_usages');

        if ($existing > 0) {
            return $this->success_result('coupon_usage_already_recorded', 'Coupon usage was already recorded for this checkout order.', array(
                'checkout_order_id' => (int) $order['id'],
                'recorded' => false,
                'already_exists' => true,
            ));
        }

        $data = $this->filter_columns('youngo_coupon_usages', array(
            'coupon_id' => isset($evaluation['coupon_id']) ? (int) $evaluation['coupon_id'] : null,
            'coupon_code' => isset($evaluation['coupon_code']) ? (string) $evaluation['coupon_code'] : null,
            'user_id' => isset($order['user_id']) ? (int) $order['user_id'] : null,
            'checkout_order_id' => isset($order['id']) ? (int) $order['id'] : null,
            'payment_id' => null,
            'discount_amount' => isset($evaluation['discount_amount']) ? (string) $evaluation['discount_amount'] : '0.00',
            'used_at' => (int) $used_at,
        ));

        if (empty($data)) {
            return $this->failure_result('coupon_usage_columns_missing', 'Coupon usage columns are not available.');
        }

        $inserted = $this->db->insert('youngo_coupon_usages', $data);
        if (!$inserted) {
            return $this->failure_result('coupon_usage_record_failed', 'Could not record zero-amount coupon usage.');
        }

        return $this->success_result('coupon_usage_recorded', 'Coupon usage recorded for zero-amount checkout completion.', array(
            'coupon_usage_id' => (int) $this->db->insert_id(),
            'checkout_order_id' => (int) $order['id'],
            'recorded' => true,
        ));
    }

    protected function entitlement_write_model()
    {
        if (!class_exists('Youngo_entitlement_write_model')) {
            return null;
        }

        return new Youngo_entitlement_write_model(array('db' => $this->db));
    }

    protected function update_order_status($order, $data, $code, $message)
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
        $updated_order = $this->get_order((int) $order['id']);

        return $this->success_result($code, $message, array(
            'order_id' => (int) $order['id'],
            'order_reference' => isset($order['order_reference']) ? (string) $order['order_reference'] : null,
            'order' => $this->get_safe_order_summary($updated_order),
        ));
    }

    protected function get_existing_open_course_order($user_id, $course_id)
    {
        if (!$this->table_exists('youngo_checkout_orders')) {
            return array();
        }

        $query = $this->db
            ->where('user_id', (int) $user_id)
            ->where('course_id', (int) $course_id)
            ->where_in('status', array('draft', 'pending_gateway', 'awaiting_webhook'))
            ->order_by('id', 'desc')
            ->get('youngo_checkout_orders', 1);

        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    protected function get_existing_open_plan_order($user_id, $plan_id)
    {
        if (!$this->table_exists('youngo_checkout_orders')) {
            return array();
        }

        $query = $this->db
            ->where('user_id', (int) $user_id)
            ->where('plan_id', (int) $plan_id)
            ->where_in('status', array('draft', 'pending_gateway', 'awaiting_webhook'))
            ->order_by('id', 'desc')
            ->get('youngo_checkout_orders', 1);

        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    protected function has_active_course_access($user_id, $course_id)
    {
        if (!$this->table_exists('youngo_course_access')) {
            return false;
        }

        $this->db->where('user_id', (int) $user_id);
        $this->db->where('course_id', (int) $course_id);
        $this->db->where('status', 'active');
        $this->db->group_start();
        $this->db->where('is_lifetime', 1);
        $this->db->or_where('expiry_date IS NULL', null, false);
        $this->db->or_where('expiry_date >=', $this->now());
        $this->db->group_end();
        $query = $this->db->get('youngo_course_access', 1);

        return $query && $query->num_rows() > 0;
    }

    protected function has_active_subscription($user_id)
    {
        if (!$this->table_exists('youngo_user_subscriptions')) {
            return false;
        }

        $this->db->where('user_id', (int) $user_id);
        $this->db->where('status', 'active');
        $this->db->group_start();
        $this->db->where('revoked_at IS NULL', null, false);
        $this->db->or_where('revoked_at', 0);
        $this->db->group_end();
        $this->db->where('expiry_date >=', $this->now());
        $query = $this->db->get('youngo_user_subscriptions', 1);

        return $query && $query->num_rows() > 0;
    }

    protected function get_row_by_id($table, $id)
    {
        if (!$this->table_exists($table) || !$this->valid_id($id)) {
            return array();
        }

        $query = $this->db->where('id', (int) $id)->get($table, 1);
        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    protected function validate_order_owner($order, $user_id)
    {
        if ($user_id === null || $user_id === '') {
            return $this->success_result('order_owner_not_checked', 'Order owner check was not requested.');
        }

        if (!$this->valid_id($user_id)) {
            return $this->failure_result('invalid_user_id', 'A valid learner user is required.');
        }

        if (!isset($order['user_id']) || (int) $order['user_id'] !== (int) $user_id) {
            return $this->failure_result('order_owner_mismatch', 'The checkout order does not belong to this learner.');
        }

        return $this->success_result('order_owner_verified', 'Order owner verified.');
    }

    protected function coupon_snapshot_order_is_editable($order)
    {
        if (!$this->status_is($order, array('draft'))) {
            return $this->failure_result('order_not_coupon_editable', 'Coupons can only be changed while the checkout order is draft.');
        }

        if (!empty($order['entitlement_issued']) || (isset($order['entitlement_issuance_status']) && (string) $order['entitlement_issuance_status'] === 'issued')) {
            return $this->failure_result('order_entitlement_already_issued', 'Coupons cannot be changed after access has been issued.');
        }

        if ($this->table_exists('youngo_instapay_payment_submissions') && !empty($order['id'])) {
            $locked_count = (int) $this->db
                ->where('order_id', (int) $order['id'])
                ->where_in('status', array('pending_review', 'approved'))
                ->count_all_results('youngo_instapay_payment_submissions');
            if ($locked_count > 0) {
                return $this->failure_result('order_instapay_review_started', 'Coupons cannot be changed after Instapay evidence has been submitted for review.');
            }
        }

        foreach (array('payment_gateway', 'provider_intent_id', 'provider_order_id', 'provider_transaction_id', 'payment_id', 'paid_at', 'completed_at') as $field) {
            if (isset($order[$field]) && $order[$field] !== null && (string) $order[$field] !== '' && (string) $order[$field] !== '0') {
                return $this->failure_result('order_payment_already_started', 'Coupons cannot be changed after payment has started.');
            }
        }

        if (isset($order['currency']) && strtoupper(trim((string) $order['currency'])) !== 'EGP') {
            return $this->failure_result('unsupported_currency', 'Only EGP checkout orders can receive coupon snapshots.');
        }

        return $this->success_result('order_coupon_editable', 'Checkout order may receive coupon snapshot updates.');
    }

    protected function checkout_order_item_context($order)
    {
        $order_type = isset($order['order_type']) ? (string) $order['order_type'] : '';

        if (isset($order['course_id']) && $this->valid_id($order['course_id']) && strpos($order_type, 'subscription') === false) {
            $course = $this->get_row_by_id('course', (int) $order['course_id']);
            if (empty($course)) {
                return $this->failure_result('course_not_found', 'Checkout order course could not be found.');
            }

            return $this->success_result('checkout_order_course_context', 'Checkout order course context loaded.', array(
                'item_type' => 'course',
                'item_id' => (int) $order['course_id'],
                'course_id' => (int) $order['course_id'],
                'subscription_plan_id' => null,
                'item_title' => isset($course['title']) ? (string) $course['title'] : '',
            ));
        }

        if (isset($order['plan_id']) && $this->valid_id($order['plan_id']) && $this->table_exists('youngo_subscription_plans')) {
            $plan = $this->get_row_by_id('youngo_subscription_plans', (int) $order['plan_id']);
            if (empty($plan)) {
                return $this->failure_result('subscription_plan_not_found', 'Checkout order subscription plan could not be found.');
            }

            return $this->success_result('checkout_order_subscription_context', 'Checkout order subscription context loaded.', array(
                'item_type' => 'subscription',
                'item_id' => (int) $order['plan_id'],
                'course_id' => null,
                'subscription_plan_id' => (int) $order['plan_id'],
                'item_title' => isset($plan['name']) ? (string) $plan['name'] : '',
            ));
        }

        return $this->failure_result('unsupported_order_item', 'Checkout order item type is not supported for coupon snapshots.');
    }

    protected function coupon_evaluator()
    {
        if (!class_exists('Youngo_coupon_evaluator_model')) {
            return null;
        }

        return new Youngo_coupon_evaluator_model(array('db' => $this->db));
    }

    protected function build_order_coupon_snapshot_json($order, $item, $coupon_result, $created_at, $action)
    {
        $base_snapshot = $this->build_checkout_snapshot_array(
            $order,
            array('title' => isset($item['item_title']) ? $item['item_title'] : ''),
            array(
                'coupon_id' => isset($coupon_result['coupon_id']) ? $coupon_result['coupon_id'] : null,
                'coupon_code' => isset($coupon_result['coupon_code']) ? $coupon_result['coupon_code'] : null,
                'coupon_discount_type' => isset($coupon_result['discount_type']) ? $coupon_result['discount_type'] : null,
                'coupon_discount_value' => isset($coupon_result['discount_value']) ? $coupon_result['discount_value'] : null,
                'discount_amount' => isset($coupon_result['discount_amount']) ? $coupon_result['discount_amount'] : null,
            ),
            isset($order['selected_payment_method']) ? $order['selected_payment_method'] : null,
            array(
                'checkout_source' => 'youngo_checkout_coupon_snapshot',
                'item_type' => isset($item['item_type']) ? $item['item_type'] : null,
            )
        );

        $zero_final = !empty($coupon_result['zero_final_amount_policy_not_enabled']);
        $course_zero_completion_candidate = $zero_final
            && isset($item['item_type'])
            && $item['item_type'] === 'course';

        return array(
            'snapshot_version' => 'PAYMENT.COUPON.CHECKOUT.SNAPSHOT.WRITE.1',
            'snapshot_action' => $action,
            'created_at' => (int) $created_at,
            'order' => $base_snapshot,
            'coupon_evaluator' => isset($coupon_result['snapshot']) && is_array($coupon_result['snapshot']) ? $coupon_result['snapshot'] : array(),
            'zero_final_amount_policy_not_enabled' => $zero_final && !$course_zero_completion_candidate,
            'zero_final_amount_coupon_completion_available' => $course_zero_completion_candidate,
        );
    }

    protected function safe_order_review_snapshot_from_row($order)
    {
        if (empty($order) || !is_array($order)) {
            return array();
        }

        $item = $this->checkout_order_item_context($order);
        $item_data = !empty($item['ok']) ? $item['data'] : array(
            'item_type' => null,
            'item_id' => null,
            'course_id' => isset($order['course_id']) && $order['course_id'] !== null ? (int) $order['course_id'] : null,
            'subscription_plan_id' => isset($order['plan_id']) && $order['plan_id'] !== null ? (int) $order['plan_id'] : null,
            'item_title' => isset($order['item_title_snapshot']) ? (string) $order['item_title_snapshot'] : '',
        );

        $decoded_snapshot = $this->decode_json_array(isset($order['checkout_snapshot_json']) ? $order['checkout_snapshot_json'] : null);
        $zero_policy = false;
        if (isset($decoded_snapshot['zero_final_amount_policy_not_enabled'])) {
            $zero_policy = !empty($decoded_snapshot['zero_final_amount_policy_not_enabled']);
        }
        $zero_coupon_completion_available = false;
        if (isset($decoded_snapshot['zero_final_amount_coupon_completion_available'])) {
            $zero_coupon_completion_available = !empty($decoded_snapshot['zero_final_amount_coupon_completion_available']);
        }

        return array(
            'id' => isset($order['id']) ? (int) $order['id'] : null,
            'order_reference' => isset($order['order_reference']) ? (string) $order['order_reference'] : null,
            'order_type' => isset($order['order_type']) ? (string) $order['order_type'] : null,
            'status' => isset($order['status']) ? (string) $order['status'] : null,
            'user_id' => isset($order['user_id']) ? (int) $order['user_id'] : null,
            'item_type' => isset($item_data['item_type']) ? $item_data['item_type'] : null,
            'item_id' => isset($item_data['item_id']) ? $item_data['item_id'] : null,
            'course_id' => isset($item_data['course_id']) ? $item_data['course_id'] : null,
            'subscription_plan_id' => isset($item_data['subscription_plan_id']) ? $item_data['subscription_plan_id'] : null,
            'item_title_snapshot' => isset($order['item_title_snapshot']) && (string) $order['item_title_snapshot'] !== ''
                ? (string) $order['item_title_snapshot']
                : (isset($item_data['item_title']) ? (string) $item_data['item_title'] : ''),
            'original_amount' => isset($order['subtotal_amount']) ? (string) $order['subtotal_amount'] : null,
            'coupon_id' => isset($order['coupon_id']) && $order['coupon_id'] !== null ? (int) $order['coupon_id'] : null,
            'coupon_code' => isset($order['coupon_code']) ? (string) $order['coupon_code'] : null,
            'coupon_discount_type' => isset($order['coupon_discount_type']) ? (string) $order['coupon_discount_type'] : null,
            'coupon_discount_value' => isset($order['coupon_discount_value']) ? (string) $order['coupon_discount_value'] : null,
            'discount_amount' => isset($order['discount_amount']) ? (string) $order['discount_amount'] : null,
            'final_amount' => isset($order['total_amount']) ? (string) $order['total_amount'] : null,
            'total_amount_cents' => isset($order['total_amount_cents']) ? (int) $order['total_amount_cents'] : null,
            'currency' => isset($order['currency']) ? (string) $order['currency'] : null,
            'selected_payment_method' => isset($order['selected_payment_method']) ? (string) $order['selected_payment_method'] : null,
            'payment_gateway' => isset($order['payment_gateway']) ? (string) $order['payment_gateway'] : null,
            'gateway_environment' => isset($order['gateway_environment']) ? (string) $order['gateway_environment'] : null,
            'entitlement_issued' => !empty($order['entitlement_issued']),
            'entitlement_issuance_status' => isset($order['entitlement_issuance_status']) ? (string) $order['entitlement_issuance_status'] : null,
            'zero_final_amount_policy_not_enabled' => $zero_policy,
            'zero_final_amount_coupon_completion_available' => $zero_coupon_completion_available,
            'checkout_snapshot' => $decoded_snapshot,
            'created_at' => isset($order['created_at']) ? (int) $order['created_at'] : null,
            'updated_at' => isset($order['updated_at']) ? (int) $order['updated_at'] : null,
        );
    }

    protected function normalize_amount($amount, $currency)
    {
        if (strtoupper(trim((string) $currency)) !== 'EGP') {
            return $this->failure_result('unsupported_currency', 'YounGo checkout orders currently support EGP only.');
        }

        if (!is_numeric($amount)) {
            return $this->failure_result('invalid_amount', 'Checkout amount must be numeric.');
        }

        $amount_float = (float) $amount;
        if ($amount_float <= 0) {
            return $this->failure_result('invalid_amount', 'Checkout amount must be positive.');
        }

        $amount_decimal = number_format(round($amount_float, 2), 2, '.', '');
        $amount_cents = (int) round($amount_float * 100);
        if ($amount_cents <= 0) {
            return $this->failure_result('invalid_amount', 'Checkout amount must be at least one piaster.');
        }

        return array(
            'ok' => true,
            'amount_decimal' => $amount_decimal,
            'amount_cents' => $amount_cents,
        );
    }

    protected function normalize_optional_decimal($amount)
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        if (!is_numeric($amount)) {
            return null;
        }

        $amount_float = (float) $amount;
        if ($amount_float < 0) {
            return null;
        }

        return number_format(round($amount_float, 2), 2, '.', '');
    }

    protected function amount_to_cents($amount)
    {
        if (!is_numeric($amount)) {
            return 0;
        }

        return (int) round(((float) $amount) * 100);
    }

    protected function decode_json_array($json)
    {
        if ($json === null || trim((string) $json) === '') {
            return array();
        }

        $decoded = json_decode((string) $json, true);
        return is_array($decoded) ? $decoded : array();
    }

    protected function gateway_environment()
    {
        if (!class_exists('Youngo_paymob_config') && defined('APPPATH') && is_file(APPPATH . 'libraries/Youngo_paymob_config.php')) {
            require_once APPPATH . 'libraries/Youngo_paymob_config.php';
        }

        if (class_exists('Youngo_paymob_config')) {
            $config = new Youngo_paymob_config(array('load_local_override' => true));
            return $config->get_mode() === 'live' ? 'live' : 'sandbox';
        }

        return 'sandbox';
    }

    protected function course_is_free($course)
    {
        return isset($course['is_free_course']) && (int) $course['is_free_course'] === 1;
    }

    protected function status_is($order, $statuses)
    {
        return isset($order['status']) && in_array((string) $order['status'], $statuses, true);
    }

    protected function clean_gateway_reference($reference)
    {
        $reference = trim((string) $reference);
        return strlen($reference) > 255 ? substr($reference, 0, 255) : $reference;
    }

    protected function clean_reason($reason)
    {
        $reason = trim((string) $reason);
        return strlen($reason) > 2000 ? substr($reason, 0, 2000) : $reason;
    }

    protected function clean_snapshot_text($value, $max_length)
    {
        $value = trim(strip_tags((string) $value));
        $value = preg_replace('/\s+/', ' ', $value);

        return strlen($value) > (int) $max_length ? substr($value, 0, (int) $max_length) : $value;
    }

    protected function array_value($array, $key, $default = null)
    {
        return is_array($array) && array_key_exists($key, $array) ? $array[$key] : $default;
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

    protected function safe_identifier($value)
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9_]+$/', $value);
    }

    protected function valid_id($value)
    {
        return is_numeric($value) && (int) $value > 0;
    }

    protected function random_hex($bytes)
    {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes((int) $bytes));
        }

        $hex = '';
        for ($i = 0; $i < (int) $bytes; $i++) {
            $hex .= sprintf('%02x', mt_rand(0, 255));
        }

        return $hex;
    }

    protected function now()
    {
        return time();
    }

    protected function valid_timestamp($value)
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
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
