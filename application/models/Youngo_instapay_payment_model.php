<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Youngo_instapay_payment_model extends CI_Model
{
    public $db;

    protected $table = 'youngo_instapay_payment_submissions';
    protected $table_exists_cache = array();

    public function __construct($params = array())
    {
        parent::__construct();

        if (isset($params['db']) && is_object($params['db'])) {
            $this->db = $params['db'];
        } else {
            $this->db = $this->db_instance();
        }
    }

    public function table_exists()
    {
        return $this->db_instance() ? $this->db_instance()->table_exists($this->table) : false;
    }

    public function allowed_statuses()
    {
        return array('pending_review', 'approved', 'rejected');
    }

    public function normalize_status($status)
    {
        $status = strtolower(trim((string) $status));
        $status = str_replace(array('-', ' '), '_', $status);
        $status = preg_replace('/_+/', '_', $status);
        $status = trim($status, '_');

        return in_array($status, $this->allowed_statuses(), true) ? $status : null;
    }

    public function build_submission_snapshot($order_review_snapshot, $extra = array())
    {
        $order = is_array($order_review_snapshot) ? $order_review_snapshot : array();
        $extra = is_array($extra) ? $extra : array();
        $currency = strtoupper(trim((string) $this->array_value($order, 'currency', 'EGP')));
        $expected_amount = $this->sanitize_expected_amount($this->array_value($order, 'final_amount'));
        $submitted_amount = $this->sanitize_expected_amount($this->array_value($extra, 'submitted_amount'));
        $selected_payment_method = $this->clean_payment_method($this->array_value($order, 'selected_payment_method'));
        if ($selected_payment_method === null) {
            $selected_payment_method = $this->clean_payment_method($this->array_value($extra, 'selected_payment_method'));
        }

        return array(
            'snapshot_version' => 'PAYMENT.MANUAL.INSTAPAY.SCHEMA.1',
            'created_at' => $this->now(),
            'status_model' => $this->allowed_statuses(),
            'order' => array(
                'order_id' => $this->valid_id($this->array_value($order, 'id')) ? (int) $this->array_value($order, 'id') : null,
                'order_reference' => $this->clean_text($this->array_value($order, 'order_reference'), 100),
                'user_id' => $this->valid_id($this->array_value($order, 'user_id')) ? (int) $this->array_value($order, 'user_id') : null,
                'status' => $this->clean_text($this->array_value($order, 'status'), 50),
                'item_type' => $this->clean_item_type($this->array_value($order, 'item_type')),
                'item_id' => $this->valid_id($this->array_value($order, 'item_id')) ? (int) $this->array_value($order, 'item_id') : null,
                'course_id' => $this->valid_id($this->array_value($order, 'course_id')) ? (int) $this->array_value($order, 'course_id') : null,
                'subscription_plan_id' => $this->valid_id($this->array_value($order, 'subscription_plan_id')) ? (int) $this->array_value($order, 'subscription_plan_id') : null,
                'item_title_snapshot' => $this->clean_text($this->array_value($order, 'item_title_snapshot'), 255),
                'original_amount' => $this->sanitize_expected_amount($this->array_value($order, 'original_amount')),
                'coupon_id' => $this->valid_id($this->array_value($order, 'coupon_id')) ? (int) $this->array_value($order, 'coupon_id') : null,
                'coupon_code' => $this->clean_text($this->array_value($order, 'coupon_code'), 255),
                'coupon_discount_type' => $this->clean_discount_type($this->array_value($order, 'coupon_discount_type')),
                'coupon_discount_value' => $this->sanitize_expected_amount($this->array_value($order, 'coupon_discount_value')),
                'discount_amount' => $this->sanitize_expected_amount($this->array_value($order, 'discount_amount')),
                'final_amount' => $expected_amount,
                'currency' => $currency === 'EGP' ? 'EGP' : null,
                'selected_payment_method' => $selected_payment_method,
            ),
            'instapay' => array(
                'expected_amount' => $expected_amount,
                'submitted_amount' => $submitted_amount,
                'currency' => $currency === 'EGP' ? 'EGP' : null,
                'selected_payment_method' => $this->clean_payment_method($this->array_value($extra, 'selected_payment_method')),
                'target_label' => $this->clean_text($this->array_value($extra, 'instapay_target_label'), 255),
                'target_address' => $this->clean_text($this->array_value($extra, 'instapay_target_address'), 255),
                'target_link' => $this->clean_text($this->array_value($extra, 'instapay_target_link'), 500),
                'instructions_ar' => $this->clean_text($this->array_value($extra, 'instapay_instructions_ar'), 5000),
                'instructions_en' => $this->clean_text($this->array_value($extra, 'instapay_instructions_en'), 5000),
                'instructions' => $this->clean_text($this->array_value($extra, 'instapay_instructions'), 5000),
                'language' => $this->clean_text($this->array_value($extra, 'instapay_language'), 20),
                'max_upload_mb' => $this->sanitize_expected_amount($this->array_value($extra, 'instapay_max_upload_mb')),
                'allowed_mimes' => $this->clean_allowed_mimes($this->array_value($extra, 'instapay_allowed_mimes')),
                'screenshot_path' => $this->clean_relative_path($this->array_value($extra, 'screenshot_path')),
                'screenshot_original_name' => $this->clean_text($this->array_value($extra, 'screenshot_original_name'), 255),
                'screenshot_mime' => $this->clean_mime($this->array_value($extra, 'screenshot_mime')),
                'screenshot_size' => $this->positive_int_or_null($this->array_value($extra, 'screenshot_size')),
                'transaction_reference' => $this->clean_text($this->array_value($extra, 'transaction_reference'), 255),
                'user_note' => $this->clean_text($this->array_value($extra, 'user_note'), 2000),
            ),
            'checkout_snapshot' => isset($order['checkout_snapshot']) && is_array($order['checkout_snapshot']) ? $order['checkout_snapshot'] : array(),
            'zero_final_amount_policy_not_enabled' => !empty($order['zero_final_amount_policy_not_enabled']),
        );
    }

    public function get_submission_by_id($id)
    {
        if (!$this->valid_id($id) || !$this->table_exists()) {
            return array();
        }

        $query = $this->db
            ->where('id', (int) $id)
            ->get($this->table, 1);

        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    public function get_submissions_for_order($order_id)
    {
        if (!$this->valid_id($order_id) || !$this->table_exists()) {
            return array();
        }

        $query = $this->db
            ->where('order_id', (int) $order_id)
            ->order_by('id', 'desc')
            ->get($this->table);

        return $query ? $query->result_array() : array();
    }

    public function get_latest_submission_for_order($order_id)
    {
        if (!$this->valid_id($order_id) || !$this->table_exists()) {
            return array();
        }

        $query = $this->db
            ->where('order_id', (int) $order_id)
            ->order_by('id', 'desc')
            ->get($this->table, 1);

        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    public function get_latest_submission_for_order_user($order_id, $user_id)
    {
        if (!$this->valid_id($order_id) || !$this->valid_id($user_id) || !$this->table_exists()) {
            return array();
        }

        $query = $this->db
            ->where('order_id', (int) $order_id)
            ->where('user_id', (int) $user_id)
            ->order_by('id', 'desc')
            ->get($this->table, 1);

        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    public function get_admin_review_list($status = null, $limit = 50, $offset = 0)
    {
        if (!$this->table_exists()) {
            return array();
        }

        $status = $status === 'all' ? null : $this->normalize_status($status);
        $limit = is_numeric($limit) ? max(1, min(200, (int) $limit)) : 50;
        $offset = is_numeric($offset) ? max(0, (int) $offset) : 0;

        $this->db->select('s.*');
        $this->db->select('o.order_reference, o.order_type, o.status AS order_status, o.course_id, o.plan_id, o.subtotal_amount AS order_subtotal_amount, o.discount_amount AS order_discount_amount, o.total_amount AS order_total_amount, o.currency AS order_currency, o.coupon_code AS order_coupon_code, o.coupon_discount_type AS order_coupon_discount_type, o.coupon_discount_value AS order_coupon_discount_value, o.item_title_snapshot AS order_item_title_snapshot, o.entitlement_issued, o.entitlement_issuance_status');
        $this->db->select('u.first_name AS user_first_name, u.last_name AS user_last_name, u.email AS user_email');
        $this->db->from($this->table . ' s');
        $this->db->join('youngo_checkout_orders o', 'o.id = s.order_id', 'left');
        $this->db->join('users u', 'u.id = s.user_id', 'left');
        if ($status !== null) {
            $this->db->where('s.status', $status);
        }
        $this->db->order_by('s.created_at', 'DESC');
        $this->db->order_by('s.id', 'DESC');
        $query = $this->db->get('', $limit, $offset);

        if (!$query) {
            return array();
        }

        $rows = array();
        foreach ($query->result_array() as $row) {
            $rows[] = $this->admin_review_row($row);
        }

        return $rows;
    }

    public function get_admin_review_detail($submission_id)
    {
        if (!$this->valid_id($submission_id) || !$this->table_exists()) {
            return array();
        }

        $this->db->select('s.*');
        $this->db->select('o.order_reference, o.order_type, o.status AS order_status, o.course_id, o.plan_id, o.subtotal_amount AS order_subtotal_amount, o.discount_amount AS order_discount_amount, o.total_amount AS order_total_amount, o.currency AS order_currency, o.coupon_code AS order_coupon_code, o.coupon_discount_type AS order_coupon_discount_type, o.coupon_discount_value AS order_coupon_discount_value, o.item_title_snapshot AS order_item_title_snapshot, o.entitlement_issued, o.entitlement_issuance_status, o.payment_gateway, o.provider_intent_id, o.provider_order_id, o.provider_transaction_id, o.paid_at, o.completed_at');
        $this->db->select('u.first_name AS user_first_name, u.last_name AS user_last_name, u.email AS user_email, u.role_id AS user_role_id, u.status AS user_status');
        $this->db->from($this->table . ' s');
        $this->db->join('youngo_checkout_orders o', 'o.id = s.order_id', 'left');
        $this->db->join('users u', 'u.id = s.user_id', 'left');
        $this->db->where('s.id', (int) $submission_id);
        $query = $this->db->get('', 1);

        if (!$query || $query->num_rows() === 0) {
            return array();
        }

        return $this->admin_review_row($query->row_array(), true);
    }

    public function get_evidence_file_for_admin($submission_id)
    {
        $detail = $this->get_admin_review_detail($submission_id);
        if (empty($detail)) {
            return $this->failure_result('submission_not_found', 'Manual Instapay submission was not found.');
        }

        $relative_path = $this->clean_relative_path($this->array_value($detail, 'screenshot_path'));
        if ($relative_path === '' || strpos($relative_path, 'uploads/youngo/instapay_evidence/') !== 0) {
            return $this->failure_result('invalid_evidence_path', 'Instapay evidence path is invalid.');
        }

        $root = defined('FCPATH') ? rtrim(FCPATH, "\\/") : (defined('APPPATH') ? rtrim(dirname(APPPATH), "\\/") : getcwd());
        $evidence_dir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'youngo' . DIRECTORY_SEPARATOR . 'instapay_evidence';
        $absolute_path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
        $real_dir = realpath($evidence_dir);
        $real_file = realpath($absolute_path);

        if (!$real_dir || !$real_file || strpos($real_file, $real_dir . DIRECTORY_SEPARATOR) !== 0 || !is_file($real_file)) {
            return $this->failure_result('evidence_file_not_available', 'Instapay evidence file is not available.');
        }

        $mime = $this->clean_mime($this->array_value($detail, 'screenshot_mime'));
        if ($mime === null) {
            $mime = $this->detect_file_mime($real_file);
        }

        if (!in_array($mime, array('image/jpeg', 'image/png', 'image/webp'), true)) {
            return $this->failure_result('evidence_mime_not_allowed', 'Instapay evidence file type is not allowed.');
        }

        return $this->success_result('evidence_file_ready', 'Instapay evidence file is ready for admin preview.', array(
            'submission_id' => (int) $detail['id'],
            'absolute_path' => $real_file,
            'relative_path' => $relative_path,
            'mime' => $mime,
            'size' => filesize($real_file),
            'download_name' => $this->safe_download_name($this->array_value($detail, 'screenshot_original_name'), $mime, (int) $detail['id']),
        ));
    }

    public function count_admin_review_by_status()
    {
        $counts = array(
            'pending_review' => 0,
            'approved' => 0,
            'rejected' => 0,
            'all' => 0,
        );

        if (!$this->table_exists()) {
            return $counts;
        }

        $query = $this->db
            ->select('status, COUNT(*) AS total', false)
            ->from($this->table)
            ->group_by('status')
            ->get();

        if (!$query) {
            return $counts;
        }

        foreach ($query->result_array() as $row) {
            $status = $this->normalize_status($this->array_value($row, 'status'));
            if ($status !== null) {
                $counts[$status] = (int) $row['total'];
                $counts['all'] += (int) $row['total'];
            }
        }

        return $counts;
    }

    public function create_pending_submission($order_id, $user_id, $upload_data, $transaction_reference = null, $user_note = null)
    {
        if (!$this->valid_id($order_id) || !$this->valid_id($user_id)) {
            return $this->failure_result('invalid_submission_identity', 'A valid checkout order and learner are required.');
        }

        if (!$this->table_exists()) {
            return $this->failure_result('instapay_submission_table_missing', 'Manual Instapay submission table is not available.');
        }

        $checkout_model = $this->checkout_model();
        if (!$checkout_model || !method_exists($checkout_model, 'get_order') || !method_exists($checkout_model, 'get_safe_order_review_snapshot')) {
            return $this->failure_result('checkout_model_missing', 'YounGo checkout model helpers are not available.');
        }

        $order = $checkout_model->get_order((int) $order_id);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        if ((int) $order['user_id'] !== (int) $user_id) {
            return $this->failure_result('order_owner_mismatch', 'The checkout order does not belong to this learner.');
        }

        $config_model = $this->payment_config_model();
        if (!$config_model || !method_exists($config_model, 'is_instapay_checkout_enabled')) {
            return $this->failure_result('payment_config_model_missing', 'Manual Instapay config helpers are not available.');
        }

        if (!$config_model->is_instapay_checkout_enabled()) {
            return $this->failure_result('instapay_checkout_disabled', 'Manual Instapay checkout is not available yet.');
        }

        $target_result = $config_model->build_instapay_target_snapshot($this->array_value($upload_data, 'language', 'english'));
        if (empty($target_result['ok']) || empty($target_result['data']['snapshot'])) {
            return $this->failure_result('instapay_target_snapshot_failed', 'Manual Instapay target details could not be loaded.', $target_result);
        }

        $target = $target_result['data']['snapshot'];
        if (empty($target['enabled'])) {
            return $this->failure_result('instapay_checkout_disabled', 'Manual Instapay checkout is not available yet.');
        }

        if (!$this->upload_metadata_is_valid($upload_data)) {
            return $this->failure_result('invalid_screenshot_upload', 'A valid Instapay transaction screenshot is required.');
        }

        $eligible = $this->can_create_submission_for_order($order);
        if (empty($eligible['ok'])) {
            return $eligible;
        }

        $review_result = $checkout_model->get_safe_order_review_snapshot((int) $order_id, (int) $user_id);
        if (empty($review_result['ok']) || empty($review_result['data']['review_snapshot'])) {
            return $this->failure_result('order_review_snapshot_failed', 'Checkout snapshot could not be prepared for review.', $review_result);
        }

        $review_snapshot = $review_result['data']['review_snapshot'];
        $expected_amount = $this->sanitize_expected_amount($this->array_value($review_snapshot, 'final_amount', $this->array_value($order, 'total_amount')));
        if ($expected_amount === null) {
            return $this->failure_result('invalid_expected_amount', 'The checkout order final amount is invalid.');
        }

        $submitted_amount = $this->sanitize_expected_amount($this->array_value($upload_data, 'submitted_amount'));
        $now = $this->now();
        $extra = array(
            'submitted_amount' => $submitted_amount,
            'selected_payment_method' => 'instapay_manual',
            'instapay_target_label' => $this->array_value($target, 'label'),
            'instapay_target_address' => $this->array_value($target, 'address'),
            'instapay_target_link' => $this->array_value($target, 'link'),
            'instapay_instructions_ar' => $this->array_value($target, 'instructions_ar'),
            'instapay_instructions_en' => $this->array_value($target, 'instructions_en'),
            'instapay_instructions' => $this->array_value($target, 'instructions'),
            'instapay_language' => $this->array_value($target, 'language'),
            'instapay_max_upload_mb' => $this->array_value($target, 'max_upload_mb'),
            'instapay_allowed_mimes' => $this->array_value($target, 'allowed_mimes'),
            'screenshot_path' => $this->array_value($upload_data, 'screenshot_path'),
            'screenshot_original_name' => $this->array_value($upload_data, 'screenshot_original_name'),
            'screenshot_mime' => $this->array_value($upload_data, 'screenshot_mime'),
            'screenshot_size' => $this->array_value($upload_data, 'screenshot_size'),
            'transaction_reference' => $transaction_reference,
            'user_note' => $user_note,
        );
        $submission_snapshot = $this->build_submission_snapshot($review_snapshot, $extra);
        $submission_snapshot['snapshot_version'] = 'PAYMENT.MANUAL.INSTAPAY.SUBMISSION.UPLOAD.1';
        $submission_snapshot['created_at'] = $now;

        $insert = array(
            'order_id' => (int) $order_id,
            'user_id' => (int) $user_id,
            'status' => 'pending_review',
            'expected_amount' => $expected_amount,
            'submitted_amount' => $submitted_amount,
            'currency' => 'EGP',
            'instapay_target_label' => $this->clean_text($this->array_value($target, 'label'), 255),
            'instapay_target_address' => $this->clean_text($this->array_value($target, 'address'), 255),
            'instapay_target_link' => $this->clean_text($this->array_value($target, 'link'), 500),
            'screenshot_path' => $this->clean_relative_path($this->array_value($upload_data, 'screenshot_path')),
            'screenshot_original_name' => $this->clean_text($this->array_value($upload_data, 'screenshot_original_name'), 255),
            'screenshot_mime' => $this->clean_mime($this->array_value($upload_data, 'screenshot_mime')),
            'screenshot_size' => $this->positive_int_or_null($this->array_value($upload_data, 'screenshot_size')),
            'transaction_reference' => $this->clean_text($transaction_reference, 255),
            'user_note' => $this->clean_text($user_note, 2000),
            'admin_note' => null,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'access_issued' => 0,
            'access_issued_at' => null,
            'access_reference_type' => null,
            'access_reference_id' => null,
            'snapshot_json' => json_encode($submission_snapshot, JSON_UNESCAPED_SLASHES),
            'created_at' => $now,
            'updated_at' => $now,
        );

        $this->db->trans_begin();
        $pending_count = (int) $this->db
            ->where('order_id', (int) $order_id)
            ->where('status', 'pending_review')
            ->count_all_results($this->table);
        if ($pending_count > 0) {
            $this->db->trans_rollback();
            return $this->failure_result('pending_submission_exists', 'This checkout order already has a pending Instapay review submission.');
        }

        $created = $this->db->insert($this->table, $this->filter_columns($this->table, $insert));
        if (!$created || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('pending_submission_create_failed', 'Could not submit Instapay evidence for admin review.');
        }

        $submission_id = (int) $this->db->insert_id();
        $this->db->trans_commit();

        return $this->success_result('pending_review_submission_created', 'Instapay evidence submitted for admin review.', array(
            'submission_id' => $submission_id,
            'order_id' => (int) $order_id,
            'user_id' => (int) $user_id,
            'status' => 'pending_review',
            'expected_amount' => $expected_amount,
            'submission' => $this->get_submission_by_id($submission_id),
        ));
    }

    public function approve_submission($submission_id, $admin_user_id, $admin_note = null)
    {
        if (!$this->valid_id($submission_id)) {
            return $this->failure_result('invalid_submission_id', 'A valid manual Instapay submission is required.');
        }

        $admin = $this->validate_admin_user($admin_user_id);
        if (empty($admin['ok'])) {
            return $admin;
        }

        $submission = $this->get_submission_by_id((int) $submission_id);
        if (empty($submission)) {
            return $this->failure_result('submission_not_found', 'Manual Instapay submission was not found.');
        }

        $status = $this->normalize_status($this->array_value($submission, 'status'));
        if ($status === 'approved') {
            if (!empty($submission['access_issued'])) {
                return $this->success_result('submission_already_approved', 'Manual Instapay submission was already approved.', array(
                    'submission_id' => (int) $submission['id'],
                    'order_id' => (int) $submission['order_id'],
                    'submission' => $this->get_submission_by_id((int) $submission['id']),
                ));
            }

            return $this->failure_result('approved_submission_without_access', 'This approved submission needs manual audit because access was not recorded as issued.');
        }
        if ($status === 'rejected') {
            return $this->failure_result('rejected_submission_cannot_be_approved', 'Rejected manual Instapay submissions cannot be approved. Ask the learner to submit new evidence if needed.');
        }
        if ($status !== 'pending_review') {
            return $this->failure_result('submission_not_pending_review', 'Only pending_review Instapay submissions can be approved.');
        }

        $order = $this->approval_order_for_submission($submission);
        if (empty($order['ok'])) {
            return $order;
        }

        $order_row = $order['data']['order'];
        $approved_at = $this->now();
        $admin_note = $this->clean_text($admin_note, 2000);
        $metadata = $this->approval_order_metadata($order_row, $submission, $admin_user_id, $approved_at);

        $this->db->trans_begin();

        $other_locked = (int) $this->db
            ->where('order_id', (int) $order_row['id'])
            ->where('id !=', (int) $submission['id'])
            ->where_in('status', array('pending_review', 'approved'))
            ->count_all_results($this->table);
        if ($other_locked > 0) {
            $this->db->trans_rollback();
            return $this->failure_result('other_active_submission_exists', 'Another active Instapay review submission exists for this order.');
        }

        $order_update = array(
            'status' => 'paid',
            'payment_gateway' => 'instapay_manual',
            'selected_payment_method' => 'instapay_manual',
            'provider_intent_id' => null,
            'provider_order_id' => null,
            'provider_transaction_id' => null,
            'payment_id' => null,
            'last_hmac_verified' => 0,
            'paid_at' => $approved_at,
            'completed_at' => $approved_at,
            'metadata' => json_encode($metadata, JSON_UNESCAPED_SLASHES),
            'entitlement_issued' => 0,
            'entitlement_issuance_status' => 'not_started',
            'entitlement_issuance_error' => null,
            'updated_at' => $approved_at,
        );

        $order_updated = $this->db
            ->where('id', (int) $order_row['id'])
            ->where('status', 'draft')
            ->group_start()
            ->where('entitlement_issued', 0)
            ->or_where('entitlement_issued IS NULL', null, false)
            ->group_end()
            ->update('youngo_checkout_orders', $this->filter_columns('youngo_checkout_orders', $order_update));

        if (!$order_updated || $this->db->affected_rows() !== 1 || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('instapay_order_approval_update_failed', 'Could not mark the checkout order approved for manual Instapay.');
        }

        $submission_updated = $this->db
            ->where('id', (int) $submission['id'])
            ->where('status', 'pending_review')
            ->update($this->table, $this->filter_columns($this->table, array(
                'status' => 'approved',
                'admin_note' => $admin_note,
                'reviewed_by_user_id' => (int) $admin_user_id,
                'reviewed_at' => $approved_at,
                'approved_at' => $approved_at,
                'rejected_at' => null,
                'updated_at' => $approved_at,
            )));

        if (!$submission_updated || $this->db->affected_rows() !== 1 || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('instapay_submission_approve_failed', 'Could not approve the manual Instapay submission.');
        }

        $order_type = (string) $this->array_value($order_row, 'order_type');
        $is_subscription_order = $order_type === 'subscription_purchase';
        $entitlement_method = $is_subscription_order ? 'issue_instapay_manual_subscription_access' : 'issue_instapay_manual_course_access';

        $entitlement_model = $this->entitlement_write_model();
        if (!$entitlement_model || !method_exists($entitlement_model, $entitlement_method)) {
            $this->db->trans_rollback();
            return $this->failure_result('entitlement_write_model_unavailable', 'The entitlement write service is unavailable.');
        }

        $issued = $entitlement_model->$entitlement_method((int) $order_row['id'], array(
            'approved_at' => $approved_at,
            'submission_id' => (int) $submission['id'],
            'admin_user_id' => (int) $admin_user_id,
            'source' => 'instapay_manual',
        ));

        if (empty($issued['ok'])) {
            $this->db->trans_rollback();
            return $this->failure_result(
                isset($issued['code']) ? $issued['code'] : 'instapay_entitlement_failed',
                isset($issued['message']) ? $issued['message'] : 'Could not issue access for the approved manual Instapay submission.',
                $issued
            );
        }

        $access_reference_type = $is_subscription_order ? 'subscription' : 'course_access';
        $access_reference_key = $is_subscription_order ? 'subscription_id' : 'course_access_id';
        $access_reference_id = isset($issued['ids'][$access_reference_key]) ? (int) $issued['ids'][$access_reference_key] : null;
        $access_updated = $this->db
            ->where('id', (int) $submission['id'])
            ->where('status', 'approved')
            ->update($this->table, $this->filter_columns($this->table, array(
                'access_issued' => 1,
                'access_issued_at' => $approved_at,
                'access_reference_type' => $access_reference_type,
                'access_reference_id' => $access_reference_id,
                'updated_at' => $approved_at,
            )));

        if (!$access_updated || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('instapay_submission_access_audit_failed', 'Could not record access issuance on the approved Instapay submission.');
        }

        $this->db->trans_commit();

        return $this->success_result(
            'instapay_submission_approved_access_issued',
            $is_subscription_order
                ? 'Manual Instapay submission approved and subscription access issued.'
                : 'Manual Instapay submission approved and course access issued.',
            array(
                'submission_id' => (int) $submission['id'],
                'order_id' => (int) $order_row['id'],
                'approved_at' => $approved_at,
                'access_reference_type' => $access_reference_type,
                'access_reference_id' => $access_reference_id,
                'submission' => $this->get_submission_by_id((int) $submission['id']),
                'entitlement' => $issued,
            )
        );
    }

    public function reject_submission($submission_id, $admin_user_id, $admin_note = null)
    {
        if (!$this->valid_id($submission_id)) {
            return $this->failure_result('invalid_submission_id', 'A valid manual Instapay submission is required.');
        }

        $admin = $this->validate_admin_user($admin_user_id);
        if (empty($admin['ok'])) {
            return $admin;
        }

        $submission = $this->get_submission_by_id((int) $submission_id);
        if (empty($submission)) {
            return $this->failure_result('submission_not_found', 'Manual Instapay submission was not found.');
        }

        $status = $this->normalize_status($this->array_value($submission, 'status'));
        if ($status === 'approved') {
            return $this->failure_result('approved_submission_cannot_be_rejected', 'Approved manual Instapay submissions cannot be rejected.');
        }
        if ($status === 'rejected') {
            return $this->failure_result('submission_already_rejected', 'Manual Instapay submission was already rejected.');
        }
        if ($status !== 'pending_review') {
            return $this->failure_result('submission_not_pending_review', 'Only pending_review Instapay submissions can be rejected.');
        }

        $now = $this->now();
        $updated = $this->db
            ->where('id', (int) $submission['id'])
            ->where('status', 'pending_review')
            ->update($this->table, $this->filter_columns($this->table, array(
                'status' => 'rejected',
                'admin_note' => $this->clean_text($admin_note, 2000),
                'reviewed_by_user_id' => (int) $admin_user_id,
                'reviewed_at' => $now,
                'approved_at' => null,
                'rejected_at' => $now,
                'access_issued' => 0,
                'access_issued_at' => null,
                'access_reference_type' => null,
                'access_reference_id' => null,
                'updated_at' => $now,
            )));

        if (!$updated || $this->db->affected_rows() !== 1) {
            return $this->failure_result('instapay_submission_reject_failed', 'Could not reject the manual Instapay submission.');
        }

        return $this->success_result('instapay_submission_rejected', 'Manual Instapay submission rejected. No access was issued.', array(
            'submission_id' => (int) $submission['id'],
            'order_id' => (int) $submission['order_id'],
            'rejected_at' => $now,
            'submission' => $this->get_submission_by_id((int) $submission['id']),
        ));
    }

    public function can_create_submission_for_order($order)
    {
        if (!is_array($order) || empty($order)) {
            return $this->failure_result('order_missing', 'A checkout order is required.');
        }

        if (!$this->table_exists()) {
            return $this->failure_result('instapay_submission_table_missing', 'Manual Instapay submission table is not available.');
        }

        if (!$this->valid_id($this->array_value($order, 'id')) || !$this->valid_id($this->array_value($order, 'user_id'))) {
            return $this->failure_result('invalid_order_identity', 'The checkout order identity is invalid.');
        }

        if ((string) $this->array_value($order, 'status') !== 'draft') {
            return $this->failure_result('order_not_unpaid_checkout_state', 'Manual Instapay submission requires an unpaid checkout order.');
        }

        if (strtoupper(trim((string) $this->array_value($order, 'currency', ''))) !== 'EGP') {
            return $this->failure_result('unsupported_currency', 'Manual Instapay submissions currently support EGP only.');
        }

        $expected_amount = $this->sanitize_expected_amount($this->array_value($order, 'final_amount', $this->array_value($order, 'total_amount')));
        if ($expected_amount === null) {
            return $this->failure_result('invalid_expected_amount', 'The checkout order final amount is invalid.');
        }

        if ((int) round(((float) $expected_amount) * 100) <= 0) {
            return $this->failure_result('zero_amount_coupon_completion_required', 'Manual Instapay submission is not required for a zero-amount coupon checkout order.');
        }

        foreach (array('payment_gateway', 'provider_intent_id', 'provider_order_id', 'provider_transaction_id', 'payment_id', 'paid_at', 'completed_at') as $field) {
            if (isset($order[$field]) && $order[$field] !== null && (string) $order[$field] !== '' && (string) $order[$field] !== '0') {
                return $this->failure_result('order_payment_already_started', 'Manual Instapay submission is blocked after payment/provider activity starts.');
            }
        }

        if (!empty($order['entitlement_issued']) || strtolower((string) $this->array_value($order, 'entitlement_issuance_status')) === 'issued') {
            return $this->failure_result('order_access_already_issued', 'Manual Instapay submission is blocked after access is issued.');
        }

        $pending_count = (int) $this->db
            ->where('order_id', (int) $order['id'])
            ->where('status', 'pending_review')
            ->count_all_results($this->table);

        if ($pending_count > 0) {
            return $this->failure_result('pending_submission_exists', 'This checkout order already has a pending Instapay review submission.');
        }

        return $this->success_result('submission_allowed', 'Manual Instapay submission may be created later.', array(
            'order_id' => (int) $order['id'],
            'user_id' => (int) $order['user_id'],
            'expected_amount' => $expected_amount,
            'currency' => 'EGP',
        ));
    }

    public function sanitize_expected_amount($amount)
    {
        if ($amount === null || $amount === '' || !is_numeric($amount)) {
            return null;
        }

        $amount = round((float) $amount, 2);
        if ($amount < 0) {
            return null;
        }

        return number_format($amount, 2, '.', '');
    }

    public function get_instapay_config()
    {
        $config_model = $this->payment_config_model();
        if (!$config_model || !method_exists($config_model, 'get_instapay_config')) {
            return $this->failure_result('payment_config_model_missing', 'Manual Instapay config helpers are not available.');
        }

        return $config_model->get_instapay_config();
    }

    public function is_instapay_checkout_enabled()
    {
        $config_model = $this->payment_config_model();
        return $config_model
            && method_exists($config_model, 'is_instapay_checkout_enabled')
            && $config_model->is_instapay_checkout_enabled();
    }

    public function build_instapay_target_snapshot($language = 'english')
    {
        $config_model = $this->payment_config_model();
        if (!$config_model || !method_exists($config_model, 'build_instapay_target_snapshot')) {
            return $this->failure_result('payment_config_model_missing', 'Manual Instapay target snapshot helper is not available.');
        }

        return $config_model->build_instapay_target_snapshot($language);
    }

    protected function approval_order_for_submission($submission)
    {
        if (!is_array($submission) || !$this->valid_id($this->array_value($submission, 'order_id'))) {
            return $this->failure_result('submission_order_missing', 'The linked checkout order could not be found.');
        }

        $checkout_model = $this->checkout_model();
        if (!$checkout_model || !method_exists($checkout_model, 'get_order')) {
            return $this->failure_result('checkout_model_missing', 'YounGo checkout model helpers are not available.');
        }

        $order = $checkout_model->get_order((int) $submission['order_id']);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The linked checkout order could not be found.');
        }

        if ((int) $this->array_value($order, 'user_id') !== (int) $this->array_value($submission, 'user_id')) {
            return $this->failure_result('submission_order_owner_mismatch', 'The Instapay submission does not match the checkout order learner.');
        }

        $order_type = (string) $this->array_value($order, 'order_type');
        if (!in_array($order_type, array('course_purchase', 'subscription_purchase'), true)) {
            return $this->failure_result('unsupported_order_type', 'Only course purchase or subscription purchase checkout orders are supported for Instapay approval.');
        }

        if ($order_type === 'course_purchase' && !$this->valid_id($this->array_value($order, 'course_id'))) {
            return $this->failure_result('invalid_checkout_order_target', 'Checkout order course target is invalid.');
        }

        if ($order_type === 'subscription_purchase' && !$this->valid_id($this->array_value($order, 'plan_id'))) {
            return $this->failure_result('invalid_checkout_order_target', 'Checkout order subscription plan target is invalid.');
        }

        if ((string) $this->array_value($order, 'status') !== 'draft') {
            return $this->failure_result('order_not_unpaid_checkout_state', 'Manual Instapay approval requires an unpaid checkout order.');
        }

        if (strtoupper(trim((string) $this->array_value($order, 'currency'))) !== 'EGP' || strtoupper(trim((string) $this->array_value($submission, 'currency'))) !== 'EGP') {
            return $this->failure_result('unsupported_currency', 'Manual Instapay approval currently supports EGP only.');
        }

        $expected = $this->sanitize_expected_amount($this->array_value($submission, 'expected_amount'));
        $final = $this->sanitize_expected_amount($this->array_value($order, 'total_amount'));
        if ($expected === null || $final === null || (int) round(((float) $expected) * 100) !== (int) round(((float) $final) * 100)) {
            return $this->failure_result('expected_amount_mismatch', 'Instapay expected amount must match the checkout final amount before approval.');
        }

        if ((int) round(((float) $final) * 100) <= 0) {
            return $this->failure_result('zero_amount_coupon_flow_required', 'Zero-amount coupon checkout must use the zero_amount_coupon completion flow, not Instapay approval.');
        }

        foreach (array('payment_gateway', 'provider_intent_id', 'provider_order_id', 'provider_transaction_id', 'payment_id', 'paid_at', 'completed_at') as $field) {
            if (isset($order[$field]) && $order[$field] !== null && (string) $order[$field] !== '' && (string) $order[$field] !== '0') {
                return $this->failure_result('order_payment_already_started', 'Manual Instapay approval is blocked after payment/provider activity starts.');
            }
        }

        if ((string) $this->array_value($order, 'selected_payment_method') === 'zero_amount_coupon' || (string) $this->array_value($order, 'payment_gateway') === 'zero_amount_coupon') {
            return $this->failure_result('zero_amount_coupon_order_not_eligible', 'Zero-amount coupon orders are not eligible for Instapay approval.');
        }

        if (!empty($order['last_hmac_verified'])) {
            return $this->failure_result('gateway_hmac_not_allowed', 'Manual Instapay approval must not use Paymob/HMAC verification.');
        }

        if (!empty($order['entitlement_issued']) || strtolower((string) $this->array_value($order, 'entitlement_issuance_status')) === 'issued') {
            return $this->failure_result('order_access_already_issued', 'Manual Instapay approval is blocked after access is issued.');
        }

        if ($order_type === 'course_purchase') {
            if ($this->active_course_access_exists($this->array_value($order, 'user_id'), $this->array_value($order, 'course_id'))) {
                return $this->failure_result('active_course_access_exists', 'This learner already has active YounGo access to the course.');
            }
        } else {
            if ($this->active_subscription_exists($this->array_value($order, 'user_id'))) {
                return $this->failure_result('active_subscription_exists', 'This learner already has an active YounGo subscription.');
            }
        }

        return $this->success_result('instapay_approval_order_ready', 'Checkout order is eligible for manual Instapay approval.', array(
            'order' => $order,
            'expected_amount' => $expected,
        ));
    }

    protected function approval_order_metadata($order, $submission, $admin_user_id, $approved_at)
    {
        $metadata = $this->decode_json_array($this->array_value($order, 'metadata'));
        if (empty($metadata)) {
            $metadata = array();
        }

        $metadata['manual_instapay_approval'] = array(
            'phase' => 'PAYMENT.MANUAL.INSTAPAY.APPROVAL.ACCESS.1',
            'submission_id' => (int) $submission['id'],
            'approved_by_user_id' => (int) $admin_user_id,
            'approved_at' => (int) $approved_at,
            'expected_amount' => $this->sanitize_expected_amount($this->array_value($submission, 'expected_amount')),
            'currency' => 'EGP',
            'payment_method' => 'instapay_manual',
            'paymob_hmac_verified' => false,
        );

        return $metadata;
    }

    protected function active_course_access_exists($user_id, $course_id)
    {
        $db = $this->db_instance();
        if (!$this->valid_id($user_id) || !$this->valid_id($course_id) || !$db || !$db->table_exists('youngo_course_access')) {
            return false;
        }

        $this->db
            ->where('user_id', (int) $user_id)
            ->where('course_id', (int) $course_id)
            ->where('status', 'active')
            ->group_start()
            ->where('is_lifetime', 1)
            ->or_where('expiry_date IS NULL', null, false)
            ->or_where('expiry_date >=', $this->now())
            ->group_end();

        return $this->db->count_all_results('youngo_course_access') > 0;
    }

    protected function active_subscription_exists($user_id)
    {
        $db = $this->db_instance();
        if (!$this->valid_id($user_id) || !$db || !$db->table_exists('youngo_user_subscriptions')) {
            return false;
        }

        $this->db
            ->where('user_id', (int) $user_id)
            ->where('status', 'active')
            ->group_start()
            ->where('revoked_at IS NULL', null, false)
            ->or_where('revoked_at', 0)
            ->group_end()
            ->where('expiry_date >=', $this->now());

        return $this->db->count_all_results('youngo_user_subscriptions') > 0;
    }

    protected function validate_admin_user($admin_user_id)
    {
        $db = $this->db_instance();
        if (!$this->valid_id($admin_user_id) || !$db || !$db->table_exists('users')) {
            return $this->failure_result('invalid_admin_user', 'A valid admin reviewer is required.');
        }

        $row = $this->db
            ->where('id', (int) $admin_user_id)
            ->get('users', 1)
            ->row_array();

        if (empty($row) || (isset($row['role_id']) && (int) $row['role_id'] !== 1)) {
            return $this->failure_result('invalid_admin_user', 'A valid Root Admin reviewer is required.');
        }

        return $this->success_result('admin_user_verified', 'Root Admin reviewer verified.', array(
            'admin_user_id' => (int) $admin_user_id,
        ));
    }

    protected function clean_item_type($value)
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, array('course', 'subscription'), true) ? $value : null;
    }

    protected function clean_discount_type($value)
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, array('fixed', 'percentage'), true) ? $value : null;
    }

    protected function clean_payment_method($method)
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

    protected function upload_metadata_is_valid($upload_data)
    {
        if (!is_array($upload_data)) {
            return false;
        }

        $path = $this->clean_relative_path($this->array_value($upload_data, 'screenshot_path'));
        $mime = $this->clean_mime($this->array_value($upload_data, 'screenshot_mime'));
        $size = $this->positive_int_or_null($this->array_value($upload_data, 'screenshot_size'));

        return $path !== ''
            && strpos($path, 'uploads/youngo/instapay_evidence/') === 0
            && in_array($mime, array('image/jpeg', 'image/png', 'image/webp'), true)
            && $size !== null;
    }

    protected function clean_relative_path($path)
    {
        $path = str_replace('\\', '/', trim((string) $path));
        $path = ltrim($path, '/');
        $path = preg_replace('#/+#', '/', $path);

        if ($path === '' || strpos($path, '..') !== false || !preg_match('#^[A-Za-z0-9_./-]+$#', $path)) {
            return '';
        }

        return strlen($path) > 500 ? substr($path, 0, 500) : $path;
    }

    protected function clean_mime($mime)
    {
        $mime = strtolower(trim((string) $mime));
        return in_array($mime, array('image/jpeg', 'image/png', 'image/webp'), true) ? $mime : null;
    }

    protected function clean_allowed_mimes($mimes)
    {
        if (is_string($mimes)) {
            $mimes = explode(',', $mimes);
        }

        if (!is_array($mimes)) {
            return array('image/jpeg', 'image/png', 'image/webp');
        }

        $allowed = array();
        foreach ($mimes as $mime) {
            $clean = $this->clean_mime($mime);
            if ($clean !== null) {
                $allowed[] = $clean;
            }
        }

        return !empty($allowed) ? array_values(array_unique($allowed)) : array('image/jpeg', 'image/png', 'image/webp');
    }

    protected function positive_int_or_null($value)
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    protected function admin_review_row($row, $include_detail = false)
    {
        $row = is_array($row) ? $row : array();
        $snapshot = $this->decode_json_array($this->array_value($row, 'snapshot_json'));
        $order_snapshot = isset($snapshot['order']) && is_array($snapshot['order']) ? $snapshot['order'] : array();
        $instapay_snapshot = isset($snapshot['instapay']) && is_array($snapshot['instapay']) ? $snapshot['instapay'] : array();
        $checkout_snapshot = isset($snapshot['checkout_snapshot']) && is_array($snapshot['checkout_snapshot']) ? $snapshot['checkout_snapshot'] : array();

        $item_title = $this->array_value($order_snapshot, 'item_title_snapshot', $this->array_value($row, 'order_item_title_snapshot', ''));
        $item_type = $this->array_value($order_snapshot, 'item_type');
        $course_id = $this->array_value($order_snapshot, 'course_id', $this->array_value($row, 'course_id'));
        $plan_id = $this->array_value($order_snapshot, 'subscription_plan_id', $this->array_value($row, 'plan_id'));

        $admin = array(
            'id' => $this->valid_id($this->array_value($row, 'id')) ? (int) $row['id'] : null,
            'order_id' => $this->valid_id($this->array_value($row, 'order_id')) ? (int) $row['order_id'] : null,
            'order_reference' => $this->clean_text($this->array_value($row, 'order_reference'), 100),
            'user_id' => $this->valid_id($this->array_value($row, 'user_id')) ? (int) $row['user_id'] : null,
            'user_name' => $this->admin_user_label($row),
            'user_email' => $this->clean_text($this->array_value($row, 'user_email'), 255),
            'item_type' => $this->clean_item_type($item_type),
            'item_id' => $this->valid_id($this->array_value($order_snapshot, 'item_id')) ? (int) $order_snapshot['item_id'] : null,
            'course_id' => $this->valid_id($course_id) ? (int) $course_id : null,
            'subscription_plan_id' => $this->valid_id($plan_id) ? (int) $plan_id : null,
            'item_title_snapshot' => $this->clean_text($item_title, 255),
            'status' => $this->normalize_status($this->array_value($row, 'status')),
            'order_status' => $this->clean_text($this->array_value($row, 'order_status'), 50),
            'original_amount' => $this->sanitize_expected_amount($this->array_value($order_snapshot, 'original_amount', $this->array_value($row, 'order_subtotal_amount'))),
            'coupon_code' => $this->clean_text($this->array_value($order_snapshot, 'coupon_code', $this->array_value($row, 'order_coupon_code')), 255),
            'coupon_discount_type' => $this->clean_discount_type($this->array_value($order_snapshot, 'coupon_discount_type', $this->array_value($row, 'order_coupon_discount_type'))),
            'coupon_discount_value' => $this->sanitize_expected_amount($this->array_value($order_snapshot, 'coupon_discount_value', $this->array_value($row, 'order_coupon_discount_value'))),
            'discount_amount' => $this->sanitize_expected_amount($this->array_value($order_snapshot, 'discount_amount', $this->array_value($row, 'order_discount_amount'))),
            'final_amount' => $this->sanitize_expected_amount($this->array_value($order_snapshot, 'final_amount', $this->array_value($row, 'expected_amount'))),
            'expected_amount' => $this->sanitize_expected_amount($this->array_value($row, 'expected_amount')),
            'submitted_amount' => $this->sanitize_expected_amount($this->array_value($row, 'submitted_amount')),
            'currency' => strtoupper(trim((string) $this->array_value($row, 'currency', 'EGP'))) === 'EGP' ? 'EGP' : null,
            'transaction_reference' => $this->clean_text($this->array_value($row, 'transaction_reference'), 255),
            'user_note' => $this->clean_text($this->array_value($row, 'user_note'), 2000),
            'admin_note' => $this->clean_text($this->array_value($row, 'admin_note'), 2000),
            'screenshot_path' => $this->clean_relative_path($this->array_value($row, 'screenshot_path')),
            'screenshot_original_name' => $this->clean_text($this->array_value($row, 'screenshot_original_name'), 255),
            'screenshot_mime' => $this->clean_mime($this->array_value($row, 'screenshot_mime')),
            'screenshot_size' => $this->positive_int_or_null($this->array_value($row, 'screenshot_size')),
            'instapay_target_label' => $this->clean_text($this->array_value($row, 'instapay_target_label', $this->array_value($instapay_snapshot, 'target_label')), 255),
            'instapay_target_address' => $this->clean_text($this->array_value($row, 'instapay_target_address', $this->array_value($instapay_snapshot, 'target_address')), 255),
            'instapay_target_link' => $this->clean_text($this->array_value($row, 'instapay_target_link', $this->array_value($instapay_snapshot, 'target_link')), 500),
            'instapay_instructions_ar' => $this->clean_text($this->array_value($instapay_snapshot, 'instructions_ar'), 5000),
            'instapay_instructions_en' => $this->clean_text($this->array_value($instapay_snapshot, 'instructions_en'), 5000),
            'reviewed_by_user_id' => $this->valid_id($this->array_value($row, 'reviewed_by_user_id')) ? (int) $row['reviewed_by_user_id'] : null,
            'reviewed_at' => $this->positive_int_or_null($this->array_value($row, 'reviewed_at')),
            'approved_at' => $this->positive_int_or_null($this->array_value($row, 'approved_at')),
            'rejected_at' => $this->positive_int_or_null($this->array_value($row, 'rejected_at')),
            'access_issued' => !empty($row['access_issued']),
            'access_issued_at' => $this->positive_int_or_null($this->array_value($row, 'access_issued_at')),
            'access_reference_type' => $this->clean_text($this->array_value($row, 'access_reference_type'), 100),
            'access_reference_id' => $this->valid_id($this->array_value($row, 'access_reference_id')) ? (int) $row['access_reference_id'] : null,
            'created_at' => $this->positive_int_or_null($this->array_value($row, 'created_at')),
            'updated_at' => $this->positive_int_or_null($this->array_value($row, 'updated_at')),
        );

        if ($include_detail) {
            $admin['snapshot_json'] = $snapshot;
            $admin['checkout_snapshot'] = $checkout_snapshot;
            $admin['raw_order_snapshot'] = $order_snapshot;
            $admin['raw_instapay_snapshot'] = $instapay_snapshot;
            $admin['order_payment_summary'] = array(
                'payment_gateway' => $this->clean_text($this->array_value($row, 'payment_gateway'), 50),
                'provider_intent_id' => $this->clean_text($this->array_value($row, 'provider_intent_id'), 255),
                'provider_order_id' => $this->clean_text($this->array_value($row, 'provider_order_id'), 255),
                'provider_transaction_id' => $this->clean_text($this->array_value($row, 'provider_transaction_id'), 255),
                'paid_at' => $this->positive_int_or_null($this->array_value($row, 'paid_at')),
                'completed_at' => $this->positive_int_or_null($this->array_value($row, 'completed_at')),
                'entitlement_issued' => !empty($row['entitlement_issued']),
                'entitlement_issuance_status' => $this->clean_text($this->array_value($row, 'entitlement_issuance_status'), 50),
            );
        }

        return $admin;
    }

    protected function admin_user_label($row)
    {
        $name = trim((string) $this->array_value($row, 'user_first_name') . ' ' . (string) $this->array_value($row, 'user_last_name'));
        $email = $this->array_value($row, 'user_email');

        if ($name === '') {
            return $email !== null && $email !== '' ? (string) $email : '-';
        }

        return $name;
    }

    protected function decode_json_array($json)
    {
        if ($json === null || trim((string) $json) === '') {
            return array();
        }

        $decoded = json_decode((string) $json, true);
        return is_array($decoded) ? $decoded : array();
    }

    protected function detect_file_mime($path)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $this->clean_mime($mime);
                }
            }
        }

        $image = @getimagesize($path);
        return !empty($image['mime']) ? $this->clean_mime($image['mime']) : null;
    }

    protected function safe_download_name($original_name, $mime, $submission_id)
    {
        $extension = 'jpg';
        if ($mime === 'image/png') {
            $extension = 'png';
        } elseif ($mime === 'image/webp') {
            $extension = 'webp';
        }

        $base = trim((string) pathinfo((string) $original_name, PATHINFO_FILENAME));
        $base = preg_replace('/[^A-Za-z0-9_-]+/', '-', $base);
        $base = trim($base, '-_');
        if ($base === '') {
            $base = 'instapay-evidence-' . (int) $submission_id;
        }

        return substr($base, 0, 80) . '.' . $extension;
    }

    protected function clean_text($value, $max_length)
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
        $db = $this->db_instance();
        foreach ($data as $field => $value) {
            if ($db && $db->field_exists($field, $table)) {
                $filtered[$field] = $value;
            }
        }

        return $filtered;
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

    protected function payment_config_model()
    {
        if (!class_exists('Youngo_payment_config_model') && defined('APPPATH') && is_file(APPPATH . 'models/Youngo_payment_config_model.php')) {
            require_once APPPATH . 'models/Youngo_payment_config_model.php';
        }

        if (!class_exists('Youngo_payment_config_model')) {
            return null;
        }

        return new Youngo_payment_config_model(array('db' => $this->db_instance()));
    }

    protected function checkout_model()
    {
        if (!class_exists('Youngo_checkout_model') && defined('APPPATH') && is_file(APPPATH . 'models/Youngo_checkout_model.php')) {
            require_once APPPATH . 'models/Youngo_checkout_model.php';
        }

        if (!class_exists('Youngo_checkout_model')) {
            return null;
        }

        return new Youngo_checkout_model(array('db' => $this->db_instance()));
    }

    protected function entitlement_write_model()
    {
        if (!class_exists('Youngo_entitlement_write_model') && defined('APPPATH') && is_file(APPPATH . 'models/Youngo_entitlement_write_model.php')) {
            require_once APPPATH . 'models/Youngo_entitlement_write_model.php';
        }

        if (!class_exists('Youngo_entitlement_write_model')) {
            return null;
        }

        return new Youngo_entitlement_write_model(array('db' => $this->db_instance()));
    }
}
