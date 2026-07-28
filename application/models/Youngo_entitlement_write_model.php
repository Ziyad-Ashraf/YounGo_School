<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Youngo_entitlement_write_model extends CI_Model
{
    public $db;

    protected $table_exists_cache = array();
    protected $field_exists_cache = array();
    protected $index_exists_cache = array();

    public function __construct($params = array())
    {
        parent::__construct();

        if (isset($params['db']) && is_object($params['db'])) {
            $this->db = $params['db'];
        } else {
            $CI = get_instance();
            if (isset($CI->db) && is_object($CI->db)) {
                $this->db = $CI->db;
            }
        }
    }

    public function get_schema_readiness()
    {
        $tables = array(
            'users',
            'course',
            'youngo_course_access',
            'youngo_user_subscriptions',
            'youngo_manual_grants',
            'youngo_checkout_orders',
            'youngo_subscription_plans',
        );

        $table_status = array();
        foreach ($tables as $table) {
            $table_status[$table] = $this->table_exists($table);
        }

        $columns = array(
            'youngo_course_access.revoked_by_user_id' => $this->field_exists('youngo_course_access', 'revoked_by_user_id'),
            'youngo_course_access.revoke_note' => $this->field_exists('youngo_course_access', 'revoke_note'),
            'youngo_user_subscriptions.revoked_by_user_id' => $this->field_exists('youngo_user_subscriptions', 'revoked_by_user_id'),
            'youngo_user_subscriptions.revoke_note' => $this->field_exists('youngo_user_subscriptions', 'revoke_note'),
        );

        $indexes = array(
            'youngo_course_access.idx_yca_checkout_order_id' => $this->index_exists('youngo_course_access', 'idx_yca_checkout_order_id'),
            'youngo_course_access.idx_yca_revoked_by_user_id' => $this->index_exists('youngo_course_access', 'idx_yca_revoked_by_user_id'),
            'youngo_user_subscriptions.idx_yus_revoked_by_user_id' => $this->index_exists('youngo_user_subscriptions', 'idx_yus_revoked_by_user_id'),
        );

        return array(
            'base_tables' => $table_status,
            'phase_2m_columns' => $columns,
            'phase_2m_indexes' => $indexes,
            'base_ready' => !in_array(false, $table_status, true),
            'phase_2m_columns_ready' => !in_array(false, $columns, true),
            'phase_2m_indexes_ready' => !in_array(false, $indexes, true),
            'phase_2m_applied' => !in_array(false, $columns, true) && !in_array(false, $indexes, true),
        );
    }

    public function grant_course_access($input, $actor_user_id)
    {
        $schema = $this->get_schema_readiness();
        if (empty($schema['base_ready'])) {
            return $this->failure_result('schema_not_ready', 'YounGo entitlement write tables are not ready.');
        }

        $actor = $this->validate_actor($actor_user_id);
        if (empty($actor['ok'])) {
            return $actor;
        }

        if ($this->input_value($input, 'source', 'manual') !== 'manual') {
            return $this->failure_result('unsupported_source', 'Only manual course grants are supported in this phase.');
        }

        $user = $this->validate_user($this->input_value($input, 'user_id'));
        if (empty($user['ok'])) {
            return $user;
        }

        $course = $this->validate_course($this->input_value($input, 'course_id'));
        if (empty($course['ok'])) {
            return $course;
        }

        $dates = $this->normalize_access_dates($input, true);
        if (empty($dates['ok'])) {
            return $dates;
        }

        if ($this->has_active_course_access((int) $user['row']['id'], (int) $course['row']['id'])) {
            return $this->failure_result('active_course_access_exists', 'This user already has active YounGo course access for the selected course.');
        }

        $now = $this->now();
        $this->db->trans_begin();

        $manual_grant_id = $this->create_manual_grant(array(
            'granted_by_user_id' => (int) $actor['row']['id'],
            'granted_to_user_id' => (int) $user['row']['id'],
            'grant_type' => 'course',
            'course_id' => (int) $course['row']['id'],
            'plan_id' => null,
            'custom_duration_days' => $dates['duration_days'],
            'start_date' => $dates['start_date'],
            'expiry_date' => $dates['expiry_date'],
            'is_lifetime' => $dates['is_lifetime'],
            'status' => 'active',
            'note' => $this->clean_note($this->input_value($input, 'note', '')),
            'created_at' => $now,
        ));

        if ((int) $manual_grant_id <= 0) {
            $this->db->trans_rollback();
            return $this->failure_result('manual_grant_create_failed', 'Could not create the manual grant record.');
        }

        $course_access_id = $this->insert_course_access(array(
            'user_id' => (int) $user['row']['id'],
            'course_id' => (int) $course['row']['id'],
            'access_source' => 'manual_grant',
            'status' => 'active',
            'start_date' => $dates['start_date'],
            'expiry_date' => $dates['expiry_date'],
            'is_lifetime' => $dates['is_lifetime'],
            'manual_grant_id' => (int) $manual_grant_id,
            'created_at' => $now,
            'updated_at' => $now,
        ));

        if ((int) $course_access_id <= 0 || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('course_access_create_failed', 'Could not create the course access record.');
        }

        $this->db->trans_commit();
        return $this->success_result('course_access_granted', 'Course access granted.', array(
            'manual_grant_id' => (int) $manual_grant_id,
            'course_access_id' => (int) $course_access_id,
        ));
    }

    public function revoke_course_access($course_access_id, $actor_user_id, $note = '')
    {
        $schema = $this->get_schema_readiness();
        if (empty($schema['phase_2m_columns_ready'])) {
            return $this->failure_result('phase_2m_schema_not_applied', 'Phase 2M revoke schema is not applied.');
        }

        $actor = $this->validate_actor($actor_user_id);
        if (empty($actor['ok'])) {
            return $actor;
        }

        if (!$this->valid_id($course_access_id)) {
            return $this->failure_result('invalid_course_access', 'The selected course access record could not be found.');
        }

        $row = $this->get_row_by_id('youngo_course_access', (int) $course_access_id);
        if (empty($row)) {
            return $this->failure_result('course_access_not_found', 'The selected course access record could not be found.');
        }
        if ($this->row_is_revoked($row)) {
            return $this->failure_result('course_access_already_revoked', 'This course access record is already revoked.');
        }

        $now = $this->now();
        $this->db->trans_begin();

        $updated = $this->update_row('youngo_course_access', (int) $course_access_id, array(
            'status' => 'revoked',
            'revoked_at' => $now,
            'revoked_by_user_id' => (int) $actor['row']['id'],
            'revoke_note' => $this->clean_note($note),
            'updated_at' => $now,
        ));

        if (!$updated) {
            $this->db->trans_rollback();
            return $this->failure_result('course_access_revoke_failed', 'Could not revoke the course access record.');
        }

        if (!empty($row['manual_grant_id'])) {
            if (!$this->update_manual_grant_revoked((int) $row['manual_grant_id'], (int) $actor['row']['id'], $note, $now)) {
                $this->db->trans_rollback();
                return $this->failure_result('manual_grant_revoke_failed', 'Could not update the linked manual grant record.');
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('course_access_revoke_failed', 'Could not revoke the course access record.');
        }

        $this->db->trans_commit();
        return $this->success_result('course_access_revoked', 'Course access revoked.', array(
            'course_access_id' => (int) $course_access_id,
            'manual_grant_id' => !empty($row['manual_grant_id']) ? (int) $row['manual_grant_id'] : null,
        ));
    }

    public function grant_subscription($input, $actor_user_id)
    {
        $schema = $this->get_schema_readiness();
        if (empty($schema['base_ready'])) {
            return $this->failure_result('schema_not_ready', 'YounGo entitlement write tables are not ready.');
        }

        $actor = $this->validate_actor($actor_user_id);
        if (empty($actor['ok'])) {
            return $actor;
        }

        if ($this->input_value($input, 'source', 'manual') !== 'manual') {
            return $this->failure_result('unsupported_source', 'Only manual subscription grants are supported in this phase.');
        }

        $user = $this->validate_user($this->input_value($input, 'user_id'));
        if (empty($user['ok'])) {
            return $user;
        }

        $plan = $this->validate_plan($this->input_value($input, 'plan_id'));
        if (empty($plan['ok'])) {
            return $plan;
        }
        if ($this->clean_currency($plan['row']['currency']) !== 'EGP') {
            return $this->failure_result('plan_currency_not_ready', 'The selected subscription plan is not stored in EGP.');
        }

        $input_with_plan_duration = $input;
        if ($this->input_value($input_with_plan_duration, 'duration_days') === null || $this->input_value($input_with_plan_duration, 'duration_days') === '') {
            $input_with_plan_duration['duration_days'] = (int) $plan['row']['duration_days'];
        }

        $dates = $this->normalize_access_dates($input_with_plan_duration, false);
        if (empty($dates['ok'])) {
            return $dates;
        }

        if ($this->has_active_subscription((int) $user['row']['id'])) {
            return $this->failure_result('active_subscription_exists', 'This user already has an active YounGo subscription.');
        }

        $now = $this->now();
        $this->db->trans_begin();

        $manual_grant_id = $this->create_manual_grant(array(
            'granted_by_user_id' => (int) $actor['row']['id'],
            'granted_to_user_id' => (int) $user['row']['id'],
            'grant_type' => 'subscription',
            'course_id' => null,
            'plan_id' => (int) $plan['row']['id'],
            'custom_duration_days' => $dates['duration_days'],
            'start_date' => $dates['start_date'],
            'expiry_date' => $dates['expiry_date'],
            'is_lifetime' => 0,
            'status' => 'active',
            'note' => $this->clean_note($this->input_value($input, 'note', '')),
            'created_at' => $now,
        ));

        if ((int) $manual_grant_id <= 0) {
            $this->db->trans_rollback();
            return $this->failure_result('manual_grant_create_failed', 'Could not create the manual grant record.');
        }

        $subscription_id = $this->insert_subscription(array(
            'user_id' => (int) $user['row']['id'],
            'plan_id' => (int) $plan['row']['id'],
            'source' => 'manual_grant',
            'status' => 'active',
            'start_date' => $dates['start_date'],
            'expiry_date' => $dates['expiry_date'],
            'duration_days' => $dates['duration_days'],
            'price_paid' => number_format((float) $plan['row']['price'], 2, '.', ''),
            'currency' => $this->clean_currency($plan['row']['currency']),
            'manual_grant_id' => (int) $manual_grant_id,
            'created_at' => $now,
            'updated_at' => $now,
        ));

        if ((int) $subscription_id <= 0 || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('subscription_create_failed', 'Could not create the subscription record.');
        }

        $this->db->trans_commit();
        return $this->success_result('subscription_granted', 'Subscription granted.', array(
            'manual_grant_id' => (int) $manual_grant_id,
            'subscription_id' => (int) $subscription_id,
        ));
    }

    public function revoke_subscription($subscription_id, $actor_user_id, $note = '')
    {
        $schema = $this->get_schema_readiness();
        if (empty($schema['phase_2m_columns_ready'])) {
            return $this->failure_result('phase_2m_schema_not_applied', 'Phase 2M revoke schema is not applied.');
        }

        $actor = $this->validate_actor($actor_user_id);
        if (empty($actor['ok'])) {
            return $actor;
        }

        if (!$this->valid_id($subscription_id)) {
            return $this->failure_result('invalid_subscription', 'The selected subscription record could not be found.');
        }

        $row = $this->get_row_by_id('youngo_user_subscriptions', (int) $subscription_id);
        if (empty($row)) {
            return $this->failure_result('subscription_not_found', 'The selected subscription record could not be found.');
        }
        if ($this->row_is_revoked($row)) {
            return $this->failure_result('subscription_already_revoked', 'This subscription record is already revoked.');
        }

        $now = $this->now();
        $this->db->trans_begin();

        $updated = $this->update_row('youngo_user_subscriptions', (int) $subscription_id, array(
            'status' => 'revoked',
            'revoked_at' => $now,
            'revoked_by_user_id' => (int) $actor['row']['id'],
            'revoke_note' => $this->clean_note($note),
            'updated_at' => $now,
        ));

        if (!$updated) {
            $this->db->trans_rollback();
            return $this->failure_result('subscription_revoke_failed', 'Could not revoke the subscription record.');
        }

        if (!empty($row['manual_grant_id'])) {
            if (!$this->update_manual_grant_revoked((int) $row['manual_grant_id'], (int) $actor['row']['id'], $note, $now)) {
                $this->db->trans_rollback();
                return $this->failure_result('manual_grant_revoke_failed', 'Could not update the linked manual grant record.');
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('subscription_revoke_failed', 'Could not revoke the subscription record.');
        }

        $this->db->trans_commit();
        return $this->success_result('subscription_revoked', 'Subscription revoked.', array(
            'subscription_id' => (int) $subscription_id,
            'manual_grant_id' => !empty($row['manual_grant_id']) ? (int) $row['manual_grant_id'] : null,
        ));
    }

    public function issue_course_purchase_access($checkout_order_id, $actor_context = array())
    {
        $schema = $this->get_schema_readiness();
        if (empty($schema['base_ready'])) {
            return $this->failure_result('schema_not_ready', 'YounGo entitlement write tables are not ready.');
        }

        if (!$this->valid_id($checkout_order_id)) {
            return $this->failure_result('invalid_checkout_order', 'The checkout order could not be found.');
        }

        $order = $this->get_row_by_id('youngo_checkout_orders', (int) $checkout_order_id);
        if (empty($order)) {
            return $this->failure_result('checkout_order_not_found', 'The checkout order could not be found.');
        }

        $can_issue = $this->can_issue_course_purchase_order($order);
        if (empty($can_issue['ok'])) {
            return $can_issue;
        }

        $user = $this->validate_user($order['user_id']);
        if (empty($user['ok'])) {
            return $user;
        }
        if ((int) $user['row']['role_id'] === 1) {
            return $this->failure_result('root_admin_recipient_blocked', 'Checkout entitlement issuance must not target Root Admin.');
        }

        $course = $this->validate_course($order['course_id']);
        if (empty($course['ok'])) {
            return $course;
        }

        if ($this->has_active_course_access((int) $user['row']['id'], (int) $course['row']['id'])) {
            return $this->failure_result('active_course_access_exists', 'This user already has active YounGo course access for the checkout course.');
        }

        $now = $this->now();
        $this->db->trans_begin();

        $started = $this->update_row('youngo_checkout_orders', (int) $order['id'], array(
            'entitlement_issuance_status' => 'in_progress',
            'entitlement_issuance_error' => null,
            'updated_at' => $now,
        ));

        if (!$started) {
            $this->db->trans_rollback();
            return $this->failure_result('entitlement_start_failed', 'Could not mark checkout entitlement issuance in progress.');
        }

        $course_access_id = $this->insert_course_access(array(
            'user_id' => (int) $user['row']['id'],
            'course_id' => (int) $course['row']['id'],
            'access_source' => 'course_purchase',
            'status' => 'active',
            'start_date' => $now,
            'expiry_date' => null,
            'is_lifetime' => 1,
            'checkout_order_id' => (int) $order['id'],
            'manual_grant_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ));

        if ((int) $course_access_id <= 0) {
            $this->db->trans_rollback();
            return $this->failure_result('course_access_create_failed', 'Could not create the checkout course access record.');
        }

        $issued = $this->update_row('youngo_checkout_orders', (int) $order['id'], array(
            'entitlement_issued' => 1,
            'entitlement_issuance_status' => 'issued',
            'entitlement_course_access_id' => (int) $course_access_id,
            'entitlement_subscription_id' => null,
            'entitlement_issued_at' => $now,
            'entitlement_issuance_error' => null,
            'updated_at' => $now,
        ));

        if (!$issued || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('entitlement_order_update_failed', 'Could not mark checkout entitlement issued.');
        }

        $this->db->trans_commit();

        return $this->success_result('course_purchase_access_issued', 'Course purchase access issued for paid checkout order.', array(
            'checkout_order_id' => (int) $order['id'],
            'course_access_id' => (int) $course_access_id,
            'user_id' => (int) $user['row']['id'],
            'course_id' => (int) $course['row']['id'],
            'source' => 'course_purchase',
            'payment_transaction_id' => $this->valid_id($this->input_value($actor_context, 'payment_transaction_id'))
                ? (int) $this->input_value($actor_context, 'payment_transaction_id')
                : null,
        ));
    }

    public function issue_subscription_purchase($checkout_order_id, $actor_context = array())
    {
        return $this->failure_result('checkout_issuance_not_implemented', 'Checkout subscription issuance is not implemented in this phase.');
    }

    public function issue_instapay_manual_course_access($checkout_order_id, $actor_context = array())
    {
        $schema = $this->get_schema_readiness();
        if (empty($schema['base_ready'])) {
            return $this->failure_result('schema_not_ready', 'YounGo entitlement write tables are not ready.');
        }

        if (!$this->valid_id($checkout_order_id)) {
            return $this->failure_result('invalid_checkout_order', 'The checkout order could not be found.');
        }

        $order = $this->get_row_by_id('youngo_checkout_orders', (int) $checkout_order_id);
        if (empty($order)) {
            return $this->failure_result('checkout_order_not_found', 'The checkout order could not be found.');
        }

        $can_issue = $this->can_issue_instapay_manual_course_order($order);
        if (empty($can_issue['ok'])) {
            return $can_issue;
        }

        $user = $this->validate_user($order['user_id']);
        if (empty($user['ok'])) {
            return $user;
        }
        if ((int) $user['row']['role_id'] === 1) {
            return $this->failure_result('root_admin_recipient_blocked', 'Checkout entitlement issuance must not target Root Admin.');
        }

        $course = $this->validate_course($order['course_id']);
        if (empty($course['ok'])) {
            return $course;
        }

        if ($this->has_active_course_access((int) $user['row']['id'], (int) $course['row']['id'])) {
            return $this->failure_result('active_course_access_exists', 'This user already has active YounGo course access for the checkout course.');
        }

        $now = $this->now();
        $approved_at = $this->timestamp_or_null($this->input_value($actor_context, 'approved_at'));
        if (empty($approved_at)) {
            return $this->failure_result('approved_at_required', 'Manual Instapay course access requires an approval timestamp.');
        }

        $this->db->trans_begin();

        $started = $this->update_row('youngo_checkout_orders', (int) $order['id'], array(
            'entitlement_issuance_status' => 'in_progress',
            'entitlement_issuance_error' => null,
            'updated_at' => $now,
        ));

        if (!$started) {
            $this->db->trans_rollback();
            return $this->failure_result('entitlement_start_failed', 'Could not mark checkout entitlement issuance in progress.');
        }

        $course_access_id = $this->insert_course_access(array(
            'user_id' => (int) $user['row']['id'],
            'course_id' => (int) $course['row']['id'],
            'access_source' => 'course_purchase',
            'status' => 'active',
            'start_date' => $approved_at,
            'expiry_date' => null,
            'is_lifetime' => 1,
            'checkout_order_id' => (int) $order['id'],
            'manual_grant_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ));

        if ((int) $course_access_id <= 0) {
            $this->db->trans_rollback();
            return $this->failure_result('course_access_create_failed', 'Could not create the manual Instapay course access record.');
        }

        $issued = $this->update_row('youngo_checkout_orders', (int) $order['id'], array(
            'entitlement_issued' => 1,
            'entitlement_issuance_status' => 'issued',
            'entitlement_course_access_id' => (int) $course_access_id,
            'entitlement_subscription_id' => null,
            'entitlement_issued_at' => $approved_at,
            'entitlement_issuance_error' => null,
            'updated_at' => $now,
        ));

        if (!$issued || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('entitlement_order_update_failed', 'Could not mark manual Instapay entitlement issued.');
        }

        $this->db->trans_commit();

        return $this->success_result('instapay_manual_course_access_issued', 'Course access issued for approved manual Instapay checkout order.', array(
            'checkout_order_id' => (int) $order['id'],
            'course_access_id' => (int) $course_access_id,
            'user_id' => (int) $user['row']['id'],
            'course_id' => (int) $course['row']['id'],
            'source' => 'instapay_manual',
            'access_start_at' => $approved_at,
            'submission_id' => $this->valid_id($this->input_value($actor_context, 'submission_id'))
                ? (int) $this->input_value($actor_context, 'submission_id')
                : null,
        ));
    }

    public function issue_instapay_manual_subscription_access($checkout_order_id, $actor_context = array())
    {
        $schema = $this->get_schema_readiness();
        if (empty($schema['base_ready'])) {
            return $this->failure_result('schema_not_ready', 'YounGo entitlement write tables are not ready.');
        }

        if (!$this->valid_id($checkout_order_id)) {
            return $this->failure_result('invalid_checkout_order', 'The checkout order could not be found.');
        }

        $order = $this->get_row_by_id('youngo_checkout_orders', (int) $checkout_order_id);
        if (empty($order)) {
            return $this->failure_result('checkout_order_not_found', 'The checkout order could not be found.');
        }

        $can_issue = $this->can_issue_instapay_manual_subscription_order($order);
        if (empty($can_issue['ok'])) {
            return $can_issue;
        }

        $user = $this->validate_user($order['user_id']);
        if (empty($user['ok'])) {
            return $user;
        }
        if ((int) $user['row']['role_id'] === 1) {
            return $this->failure_result('root_admin_recipient_blocked', 'Checkout entitlement issuance must not target Root Admin.');
        }

        $plan = $this->validate_plan($order['plan_id']);
        if (empty($plan['ok'])) {
            return $plan;
        }
        if ($this->clean_currency($plan['row']['currency']) !== 'EGP') {
            return $this->failure_result('plan_currency_not_ready', 'The selected subscription plan is not stored in EGP.');
        }

        if ($this->has_active_subscription((int) $user['row']['id'])) {
            return $this->failure_result('active_subscription_exists', 'This user already has an active YounGo subscription.');
        }

        $now = $this->now();
        $approved_at = $this->timestamp_or_null($this->input_value($actor_context, 'approved_at'));
        if (empty($approved_at)) {
            return $this->failure_result('approved_at_required', 'Manual Instapay subscription access requires an approval timestamp.');
        }

        $duration_days = (int) $plan['row']['duration_days'];
        $expiry_date = $approved_at + ($duration_days * 86400);

        $this->db->trans_begin();

        $started = $this->update_row('youngo_checkout_orders', (int) $order['id'], array(
            'entitlement_issuance_status' => 'in_progress',
            'entitlement_issuance_error' => null,
            'updated_at' => $now,
        ));

        if (!$started) {
            $this->db->trans_rollback();
            return $this->failure_result('entitlement_start_failed', 'Could not mark checkout entitlement issuance in progress.');
        }

        $subscription_id = $this->insert_subscription(array(
            'user_id' => (int) $user['row']['id'],
            'plan_id' => (int) $plan['row']['id'],
            'source' => 'checkout',
            'status' => 'active',
            'start_date' => $approved_at,
            'expiry_date' => $expiry_date,
            'duration_days' => $duration_days,
            'price_paid' => number_format((float) $plan['row']['price'], 2, '.', ''),
            'currency' => $this->clean_currency($plan['row']['currency']),
            'checkout_order_id' => (int) $order['id'],
            'manual_grant_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ));

        if ((int) $subscription_id <= 0) {
            $this->db->trans_rollback();
            return $this->failure_result('subscription_create_failed', 'Could not create the manual Instapay subscription record.');
        }

        $issued = $this->update_row('youngo_checkout_orders', (int) $order['id'], array(
            'entitlement_issued' => 1,
            'entitlement_issuance_status' => 'issued',
            'entitlement_course_access_id' => null,
            'entitlement_subscription_id' => (int) $subscription_id,
            'entitlement_issued_at' => $approved_at,
            'entitlement_issuance_error' => null,
            'updated_at' => $now,
        ));

        if (!$issued || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('entitlement_order_update_failed', 'Could not mark manual Instapay entitlement issued.');
        }

        $this->db->trans_commit();

        return $this->success_result('instapay_manual_subscription_access_issued', 'Subscription access issued for approved manual Instapay checkout order.', array(
            'checkout_order_id' => (int) $order['id'],
            'subscription_id' => (int) $subscription_id,
            'user_id' => (int) $user['row']['id'],
            'plan_id' => (int) $plan['row']['id'],
            'source' => 'instapay_manual',
            'access_start_at' => $approved_at,
            'submission_id' => $this->valid_id($this->input_value($actor_context, 'submission_id'))
                ? (int) $this->input_value($actor_context, 'submission_id')
                : null,
        ));
    }

    public function issue_zero_amount_coupon_course_access($checkout_order_id, $actor_context = array())
    {
        $schema = $this->get_schema_readiness();
        if (empty($schema['base_ready'])) {
            return $this->failure_result('schema_not_ready', 'YounGo entitlement write tables are not ready.');
        }

        if (!$this->valid_id($checkout_order_id)) {
            return $this->failure_result('invalid_checkout_order', 'The checkout order could not be found.');
        }

        $order = $this->get_row_by_id('youngo_checkout_orders', (int) $checkout_order_id);
        if (empty($order)) {
            return $this->failure_result('checkout_order_not_found', 'The checkout order could not be found.');
        }

        $can_issue = $this->can_issue_zero_amount_coupon_order($order);
        if (empty($can_issue['ok'])) {
            return $can_issue;
        }

        $user = $this->validate_user($order['user_id']);
        if (empty($user['ok'])) {
            return $user;
        }
        if ((int) $user['row']['role_id'] === 1) {
            return $this->failure_result('root_admin_recipient_blocked', 'Checkout entitlement issuance must not target Root Admin.');
        }

        $course = $this->validate_course($order['course_id']);
        if (empty($course['ok'])) {
            return $course;
        }

        if ($this->has_active_course_access((int) $user['row']['id'], (int) $course['row']['id'])) {
            return $this->failure_result('active_course_access_exists', 'This user already has active YounGo course access for the checkout course.');
        }

        $now = $this->now();
        $start_at = $this->timestamp_or_null($this->input_value($actor_context, 'completed_at'));
        if (empty($start_at)) {
            $start_at = $now;
        }

        $this->db->trans_begin();

        $started = $this->update_row('youngo_checkout_orders', (int) $order['id'], array(
            'entitlement_issuance_status' => 'in_progress',
            'entitlement_issuance_error' => null,
            'updated_at' => $now,
        ));

        if (!$started) {
            $this->db->trans_rollback();
            return $this->failure_result('entitlement_start_failed', 'Could not mark checkout entitlement issuance in progress.');
        }

        $course_access_id = $this->insert_course_access(array(
            'user_id' => (int) $user['row']['id'],
            'course_id' => (int) $course['row']['id'],
            'access_source' => 'course_purchase',
            'status' => 'active',
            'start_date' => $start_at,
            'expiry_date' => null,
            'is_lifetime' => 1,
            'checkout_order_id' => (int) $order['id'],
            'manual_grant_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ));

        if ((int) $course_access_id <= 0) {
            $this->db->trans_rollback();
            return $this->failure_result('course_access_create_failed', 'Could not create the zero-amount coupon course access record.');
        }

        $issued = $this->update_row('youngo_checkout_orders', (int) $order['id'], array(
            'entitlement_issued' => 1,
            'entitlement_issuance_status' => 'issued',
            'entitlement_course_access_id' => (int) $course_access_id,
            'entitlement_subscription_id' => null,
            'entitlement_issued_at' => $start_at,
            'entitlement_issuance_error' => null,
            'updated_at' => $now,
        ));

        if (!$issued || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('entitlement_order_update_failed', 'Could not mark zero-amount coupon entitlement issued.');
        }

        $this->db->trans_commit();

        return $this->success_result('zero_amount_coupon_course_access_issued', 'Course access issued for zero-amount coupon checkout order.', array(
            'checkout_order_id' => (int) $order['id'],
            'course_access_id' => (int) $course_access_id,
            'user_id' => (int) $user['row']['id'],
            'course_id' => (int) $course['row']['id'],
            'source' => 'zero_amount_coupon',
            'access_start_at' => $start_at,
        ));
    }

    public function issue_zero_amount_coupon_subscription_access($checkout_order_id, $actor_context = array())
    {
        $schema = $this->get_schema_readiness();
        if (empty($schema['base_ready'])) {
            return $this->failure_result('schema_not_ready', 'YounGo entitlement write tables are not ready.');
        }

        $order = $this->valid_id($checkout_order_id) ? $this->get_row_by_id('youngo_checkout_orders', (int) $checkout_order_id) : array();
        if (empty($order)) {
            return $this->failure_result('checkout_order_not_found', 'The checkout order could not be found.');
        }

        $can_issue = $this->can_issue_zero_amount_coupon_subscription_order($order);
        if (empty($can_issue['ok'])) {
            return $can_issue;
        }

        $user = $this->validate_user($order['user_id']);
        $plan = $this->validate_plan($order['plan_id']);
        if (empty($user['ok']) || empty($plan['ok'])) {
            return !empty($user['ok']) ? $plan : $user;
        }
        if ((int) $user['row']['role_id'] === 1) {
            return $this->failure_result('root_admin_recipient_blocked', 'Checkout entitlement issuance must not target Root Admin.');
        }
        if ($this->clean_currency($plan['row']['currency']) !== 'EGP') {
            return $this->failure_result('plan_currency_not_ready', 'The selected subscription plan is not stored in EGP.');
        }
        if ($this->has_active_subscription((int) $user['row']['id'])) {
            return $this->failure_result('active_subscription_exists', 'This user already has an active YounGo subscription.');
        }

        $now = $this->now();
        $start_at = $this->timestamp_or_null($this->input_value($actor_context, 'completed_at')) ?: $now;
        $expiry_date = $start_at + ((int) $plan['row']['duration_days'] * 86400);
        $this->db->trans_begin();
        if (!$this->update_row('youngo_checkout_orders', (int) $order['id'], array('entitlement_issuance_status' => 'in_progress', 'entitlement_issuance_error' => null, 'updated_at' => $now))) {
            $this->db->trans_rollback();
            return $this->failure_result('entitlement_start_failed', 'Could not mark checkout entitlement issuance in progress.');
        }

        $subscription_id = $this->insert_subscription(array(
            'user_id' => (int) $user['row']['id'], 'plan_id' => (int) $plan['row']['id'], 'source' => 'checkout',
            'status' => 'active', 'start_date' => $start_at, 'expiry_date' => $expiry_date,
            'duration_days' => (int) $plan['row']['duration_days'], 'price_paid' => '0.00',
            'currency' => $this->clean_currency($plan['row']['currency']), 'checkout_order_id' => (int) $order['id'],
            'manual_grant_id' => null, 'created_at' => $now, 'updated_at' => $now,
        ));
        if ((int) $subscription_id <= 0 || !$this->update_row('youngo_checkout_orders', (int) $order['id'], array(
            'entitlement_issued' => 1, 'entitlement_issuance_status' => 'issued', 'entitlement_course_access_id' => null,
            'entitlement_subscription_id' => (int) $subscription_id, 'entitlement_issued_at' => $start_at,
            'entitlement_issuance_error' => null, 'updated_at' => $now,
        )) || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('subscription_create_failed', 'Could not activate the zero-amount coupon subscription.');
        }
        $this->db->trans_commit();
        return $this->success_result('zero_amount_coupon_subscription_access_issued', 'Subscription activated for zero-amount coupon checkout.', array(
            'checkout_order_id' => (int) $order['id'], 'subscription_id' => (int) $subscription_id,
            'user_id' => (int) $user['row']['id'], 'plan_id' => (int) $plan['row']['id'],
            'source' => 'zero_amount_coupon', 'access_start_at' => $start_at,
        ));
    }

    protected function validate_actor($actor_user_id)
    {
        $result = $this->validate_user($actor_user_id);
        if (empty($result['ok'])) {
            return $this->failure_result('invalid_actor', 'The acting admin user could not be found.');
        }

        return $result;
    }

    protected function can_issue_course_purchase_order($order)
    {
        if (empty($order) || !is_array($order)) {
            return $this->failure_result('invalid_checkout_order', 'A valid checkout order is required.');
        }

        if ((string) $this->input_value($order, 'status') !== 'paid') {
            return $this->failure_result('checkout_order_not_paid', 'Only paid checkout orders can issue course access.');
        }

        if ((string) $this->input_value($order, 'order_type') !== 'course_purchase') {
            return $this->failure_result('unsupported_order_type', 'Only course purchase checkout orders are supported for this issuance path.');
        }

        if ($this->clean_currency($this->input_value($order, 'currency')) !== 'EGP') {
            return $this->failure_result('unsupported_currency', 'Only EGP checkout orders can issue access.');
        }

        if (empty($order['last_hmac_verified'])) {
            return $this->failure_result('checkout_order_not_verified', 'The checkout order has not been verified by HMAC.');
        }

        if (!empty($order['entitlement_issued']) || (string) $this->input_value($order, 'entitlement_issuance_status') === 'issued') {
            return $this->failure_result('entitlement_already_issued', 'Checkout entitlement has already been issued.');
        }

        if (!$this->valid_id($this->input_value($order, 'user_id')) || !$this->valid_id($this->input_value($order, 'course_id'))) {
            return $this->failure_result('invalid_checkout_order_target', 'Checkout order user or course target is invalid.');
        }

        return $this->success_result('checkout_order_can_issue_course_access', 'Checkout order can issue course access.');
    }

    protected function can_issue_zero_amount_coupon_order($order)
    {
        if (empty($order) || !is_array($order)) {
            return $this->failure_result('invalid_checkout_order', 'A valid checkout order is required.');
        }

        if ((string) $this->input_value($order, 'status') !== 'paid') {
            return $this->failure_result('checkout_order_not_completed', 'Zero-amount coupon access requires a completed checkout order.');
        }

        if ((string) $this->input_value($order, 'order_type') !== 'course_purchase') {
            return $this->failure_result('unsupported_order_type', 'Only course purchase checkout orders are supported for zero-amount coupon access.');
        }

        if ($this->clean_currency($this->input_value($order, 'currency')) !== 'EGP') {
            return $this->failure_result('unsupported_currency', 'Only EGP checkout orders can issue access.');
        }

        if ((string) $this->input_value($order, 'selected_payment_method') !== 'zero_amount_coupon') {
            return $this->failure_result('unsupported_payment_method', 'Zero-amount coupon access requires the zero_amount_coupon method.');
        }

        if ((string) $this->input_value($order, 'payment_gateway') !== 'zero_amount_coupon') {
            return $this->failure_result('unsupported_payment_gateway', 'Zero-amount coupon access requires a zero_amount_coupon order marker.');
        }

        if (!empty($order['last_hmac_verified'])) {
            return $this->failure_result('gateway_hmac_not_allowed', 'Zero-amount coupon access must not be marked as gateway-HMAC verified.');
        }

        if ((float) $this->input_value($order, 'subtotal_amount') <= 0 || (float) $this->input_value($order, 'total_amount') != 0.0) {
            return $this->failure_result('invalid_zero_amount_total', 'Zero-amount coupon access requires a positive original amount and zero final amount.');
        }

        if (!$this->valid_id($this->input_value($order, 'coupon_id')) || trim((string) $this->input_value($order, 'coupon_code')) === '') {
            return $this->failure_result('coupon_snapshot_required', 'Zero-amount coupon access requires a coupon snapshot.');
        }

        if (!empty($order['entitlement_issued']) || (string) $this->input_value($order, 'entitlement_issuance_status') === 'issued') {
            return $this->failure_result('entitlement_already_issued', 'Checkout entitlement has already been issued.');
        }

        if (!$this->valid_id($this->input_value($order, 'user_id')) || !$this->valid_id($this->input_value($order, 'course_id'))) {
            return $this->failure_result('invalid_checkout_order_target', 'Checkout order user or course target is invalid.');
        }

        return $this->success_result('zero_amount_coupon_order_can_issue_course_access', 'Zero-amount coupon checkout order can issue course access.');
    }

    protected function can_issue_zero_amount_coupon_subscription_order($order)
    {
        if (empty($order) || !is_array($order)) return $this->failure_result('invalid_checkout_order', 'A valid checkout order is required.');
        if ((string) $this->input_value($order, 'status') !== 'paid' || (string) $this->input_value($order, 'order_type') !== 'subscription_purchase') return $this->failure_result('unsupported_order_type', 'Only completed subscription checkout orders can issue this access.');
        if ($this->clean_currency($this->input_value($order, 'currency')) !== 'EGP') return $this->failure_result('unsupported_currency', 'Only EGP checkout orders can issue access.');
        if ((string) $this->input_value($order, 'selected_payment_method') !== 'zero_amount_coupon' || (string) $this->input_value($order, 'payment_gateway') !== 'zero_amount_coupon') return $this->failure_result('unsupported_payment_method', 'Subscription activation requires the zero-amount coupon method.');
        if (!empty($order['last_hmac_verified']) || (float) $this->input_value($order, 'subtotal_amount') <= 0 || (float) $this->input_value($order, 'total_amount') != 0.0) return $this->failure_result('invalid_zero_amount_total', 'Subscription activation requires a positive original amount and a zero final amount.');
        if (!$this->valid_id($this->input_value($order, 'coupon_id')) || trim((string) $this->input_value($order, 'coupon_code')) === '') return $this->failure_result('coupon_snapshot_required', 'Subscription activation requires a coupon snapshot.');
        if (!empty($order['entitlement_issued']) || (string) $this->input_value($order, 'entitlement_issuance_status') === 'issued') return $this->failure_result('entitlement_already_issued', 'Checkout entitlement has already been issued.');
        if (!$this->valid_id($this->input_value($order, 'user_id')) || !$this->valid_id($this->input_value($order, 'plan_id'))) return $this->failure_result('invalid_checkout_order_target', 'Checkout order user or subscription plan target is invalid.');
        return $this->success_result('zero_amount_coupon_order_can_issue_subscription_access', 'Zero-amount coupon subscription checkout order can issue access.');
    }

    protected function can_issue_instapay_manual_course_order($order)
    {
        if (empty($order) || !is_array($order)) {
            return $this->failure_result('invalid_checkout_order', 'A valid checkout order is required.');
        }

        if ((string) $this->input_value($order, 'status') !== 'paid') {
            return $this->failure_result('checkout_order_not_approved_paid', 'Approved manual Instapay access requires a paid-equivalent checkout order.');
        }

        if ((string) $this->input_value($order, 'order_type') !== 'course_purchase') {
            return $this->failure_result('unsupported_order_type', 'Only course purchase checkout orders are supported for manual Instapay approval.');
        }

        if ($this->clean_currency($this->input_value($order, 'currency')) !== 'EGP') {
            return $this->failure_result('unsupported_currency', 'Only EGP checkout orders can issue access.');
        }

        if ((string) $this->input_value($order, 'selected_payment_method') !== 'instapay_manual') {
            return $this->failure_result('unsupported_payment_method', 'Manual Instapay approval requires the instapay_manual method.');
        }

        if ((string) $this->input_value($order, 'payment_gateway') !== 'instapay_manual') {
            return $this->failure_result('unsupported_payment_gateway', 'Manual Instapay approval requires an instapay_manual order marker.');
        }

        if (!empty($order['last_hmac_verified'])) {
            return $this->failure_result('gateway_hmac_not_allowed', 'Manual Instapay approval must not be marked as gateway-HMAC verified.');
        }

        if ((float) $this->input_value($order, 'total_amount') <= 0) {
            return $this->failure_result('invalid_instapay_total', 'Manual Instapay approval requires a positive checkout final amount.');
        }

        if (!empty($order['entitlement_issued']) || (string) $this->input_value($order, 'entitlement_issuance_status') === 'issued') {
            return $this->failure_result('entitlement_already_issued', 'Checkout entitlement has already been issued.');
        }

        if (!$this->valid_id($this->input_value($order, 'user_id')) || !$this->valid_id($this->input_value($order, 'course_id'))) {
            return $this->failure_result('invalid_checkout_order_target', 'Checkout order user or course target is invalid.');
        }

        return $this->success_result('instapay_manual_order_can_issue_course_access', 'Approved manual Instapay checkout order can issue course access.');
    }

    protected function can_issue_instapay_manual_subscription_order($order)
    {
        if (empty($order) || !is_array($order)) {
            return $this->failure_result('invalid_checkout_order', 'A valid checkout order is required.');
        }

        if ((string) $this->input_value($order, 'status') !== 'paid') {
            return $this->failure_result('checkout_order_not_approved_paid', 'Approved manual Instapay access requires a paid-equivalent checkout order.');
        }

        if ((string) $this->input_value($order, 'order_type') !== 'subscription_purchase') {
            return $this->failure_result('unsupported_order_type', 'Only subscription purchase checkout orders are supported for manual Instapay subscription approval.');
        }

        if ($this->clean_currency($this->input_value($order, 'currency')) !== 'EGP') {
            return $this->failure_result('unsupported_currency', 'Only EGP checkout orders can issue access.');
        }

        if ((string) $this->input_value($order, 'selected_payment_method') !== 'instapay_manual') {
            return $this->failure_result('unsupported_payment_method', 'Manual Instapay approval requires the instapay_manual method.');
        }

        if ((string) $this->input_value($order, 'payment_gateway') !== 'instapay_manual') {
            return $this->failure_result('unsupported_payment_gateway', 'Manual Instapay approval requires an instapay_manual order marker.');
        }

        if (!empty($order['last_hmac_verified'])) {
            return $this->failure_result('gateway_hmac_not_allowed', 'Manual Instapay approval must not be marked as gateway-HMAC verified.');
        }

        if ((float) $this->input_value($order, 'total_amount') <= 0) {
            return $this->failure_result('invalid_instapay_total', 'Manual Instapay approval requires a positive checkout final amount.');
        }

        if (!empty($order['entitlement_issued']) || (string) $this->input_value($order, 'entitlement_issuance_status') === 'issued') {
            return $this->failure_result('entitlement_already_issued', 'Checkout entitlement has already been issued.');
        }

        if (!$this->valid_id($this->input_value($order, 'user_id')) || !$this->valid_id($this->input_value($order, 'plan_id'))) {
            return $this->failure_result('invalid_checkout_order_target', 'Checkout order user or subscription plan target is invalid.');
        }

        return $this->success_result('instapay_manual_order_can_issue_subscription_access', 'Approved manual Instapay checkout order can issue subscription access.');
    }

    protected function validate_user($user_id)
    {
        if (!$this->valid_id($user_id) || !$this->table_exists('users')) {
            return $this->failure_result('invalid_user', 'The selected user could not be found.');
        }

        $row = $this->get_row_by_id('users', (int) $user_id);
        if (empty($row)) {
            return $this->failure_result('invalid_user', 'The selected user could not be found.');
        }

        return array('ok' => true, 'row' => $row);
    }

    protected function validate_course($course_id)
    {
        if (!$this->valid_id($course_id) || !$this->table_exists('course')) {
            return $this->failure_result('invalid_course', 'The selected course could not be found.');
        }

        $row = $this->get_row_by_id('course', (int) $course_id);
        if (empty($row)) {
            return $this->failure_result('invalid_course', 'The selected course could not be found.');
        }

        return array('ok' => true, 'row' => $row);
    }

    protected function validate_plan($plan_id)
    {
        if (!$this->valid_id($plan_id) || !$this->table_exists('youngo_subscription_plans')) {
            return $this->failure_result('invalid_plan', 'The selected subscription plan could not be found.');
        }

        $row = $this->get_row_by_id('youngo_subscription_plans', (int) $plan_id);
        if (empty($row) || !empty($row['archived_at'])) {
            return $this->failure_result('invalid_plan', 'The selected subscription plan could not be found.');
        }
        if ((int) $row['duration_days'] <= 0) {
            return $this->failure_result('invalid_plan_duration', 'The selected subscription plan has an invalid duration.');
        }

        return array('ok' => true, 'row' => $row);
    }

    protected function normalize_access_dates($input, $allow_lifetime)
    {
        $is_lifetime = (int) $this->input_value($input, 'is_lifetime', 0) === 1 ? 1 : 0;
        if ($is_lifetime && !$allow_lifetime) {
            return $this->failure_result('lifetime_not_allowed', 'Lifetime access is not allowed for this grant type.');
        }

        $start_date = $this->timestamp_or_null($this->input_value($input, 'start_date'));
        $expiry_date = $this->timestamp_or_null($this->input_value($input, 'expiry_date'));
        $duration_days = $this->positive_int_or_null($this->input_value($input, 'duration_days'));

        if ($this->input_value($input, 'duration_days') !== null && $this->input_value($input, 'duration_days') !== '' && $duration_days === null) {
            return $this->failure_result('invalid_duration', 'Duration must be a positive whole number of days.');
        }

        if ($is_lifetime) {
            return array(
                'ok' => true,
                'is_lifetime' => 1,
                'start_date' => $start_date ?: $this->now(),
                'expiry_date' => null,
                'duration_days' => null,
            );
        }

        if (empty($start_date)) {
            $start_date = $this->now();
        }
        if (empty($expiry_date) && $duration_days !== null) {
            $expiry_date = $start_date + ($duration_days * 86400);
        }
        if (empty($expiry_date)) {
            return $this->failure_result('expiry_required', 'Timed access requires an expiry date or duration.');
        }
        if ($expiry_date <= $start_date) {
            return $this->failure_result('invalid_date_range', 'Expiry date must be later than start date.');
        }
        if ($expiry_date <= $this->now()) {
            return $this->failure_result('expired_date_range', 'Expiry date must be in the future.');
        }

        if ($duration_days === null) {
            $duration_days = (int) ceil(($expiry_date - $start_date) / 86400);
        }

        return array(
            'ok' => true,
            'is_lifetime' => 0,
            'start_date' => $start_date,
            'expiry_date' => $expiry_date,
            'duration_days' => $duration_days,
        );
    }

    protected function has_active_course_access($user_id, $course_id)
    {
        if (!$this->table_exists('youngo_course_access')) {
            return false;
        }

        $now = $this->now();
        $this->db->where('user_id', (int) $user_id);
        $this->db->where('course_id', (int) $course_id);
        $this->db->where('status', 'active');
        $this->db->group_start();
        $this->db->where('revoked_at IS NULL', null, false);
        $this->db->or_where('revoked_at', 0);
        $this->db->group_end();
        $this->db->group_start();
        $this->db->where('is_lifetime', 1);
        $this->db->or_where('expiry_date IS NULL', null, false);
        $this->db->or_where('expiry_date >=', $now);
        $this->db->group_end();

        return $this->db->count_all_results('youngo_course_access') > 0;
    }

    protected function has_active_subscription($user_id)
    {
        if (!$this->table_exists('youngo_user_subscriptions')) {
            return false;
        }

        $now = $this->now();
        $this->db->where('user_id', (int) $user_id);
        $this->db->where('status', 'active');
        $this->db->group_start();
        $this->db->where('revoked_at IS NULL', null, false);
        $this->db->or_where('revoked_at', 0);
        $this->db->group_end();
        $this->db->where('expiry_date >=', $now);

        return $this->db->count_all_results('youngo_user_subscriptions') > 0;
    }

    protected function create_manual_grant($data)
    {
        return $this->insert_row('youngo_manual_grants', $data);
    }

    protected function update_manual_grant_revoked($manual_grant_id, $actor_user_id, $note, $now)
    {
        if (!$this->valid_id($manual_grant_id) || !$this->table_exists('youngo_manual_grants')) {
            return false;
        }

        $data = array(
            'status' => 'revoked',
            'revoked_at' => $now,
            'revoked_by_user_id' => (int) $actor_user_id,
            'revoke_note' => $this->clean_note($note),
        );

        return $this->update_row('youngo_manual_grants', (int) $manual_grant_id, $data);
    }

    protected function insert_course_access($data)
    {
        return $this->insert_row('youngo_course_access', $data);
    }

    protected function insert_subscription($data)
    {
        return $this->insert_row('youngo_user_subscriptions', $data);
    }

    protected function insert_row($table, $data)
    {
        if (!$this->table_exists($table)) {
            return 0;
        }

        $data = $this->filter_columns($table, $data);
        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $result = $this->db->insert($table, $data);
        $insert_id = (int) $this->db->insert_id();
        $this->db->db_debug = $db_debug;

        return $result ? $insert_id : 0;
    }

    protected function update_row($table, $id, $data)
    {
        if (!$this->table_exists($table) || !$this->valid_id($id)) {
            return false;
        }

        $data = $this->filter_columns($table, $data);
        if (empty($data)) {
            return false;
        }

        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->where('id', (int) $id);
        $result = $this->db->update($table, $data);
        $this->db->db_debug = $db_debug;

        return (bool) $result;
    }

    protected function get_row_by_id($table, $id)
    {
        if (!$this->table_exists($table) || !$this->valid_id($id)) {
            return array();
        }

        $query = $this->db->where('id', (int) $id)->get($table, 1);
        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
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

    protected function row_is_revoked($row)
    {
        return !empty($row['revoked_at']) || (isset($row['status']) && $row['status'] === 'revoked');
    }

    protected function input_value($input, $field, $default = null)
    {
        if (!is_array($input) || !array_key_exists($field, $input)) {
            return $default;
        }

        return $input[$field];
    }

    protected function timestamp_or_null($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value) && (int) $value > 0) {
            return (int) $value;
        }

        $parsed = strtotime((string) $value);
        return $parsed && $parsed > 0 ? (int) $parsed : null;
    }

    protected function positive_int_or_null($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) && preg_match('/^[1-9][0-9]*$/', (string) $value) ? (int) $value : null;
    }

    protected function clean_note($note)
    {
        $note = trim((string) $note);
        return strlen($note) > 2000 ? substr($note, 0, 2000) : $note;
    }

    protected function clean_currency($value)
    {
        return strtoupper(trim((string) $value));
    }

    protected function now()
    {
        return time();
    }

    protected function success_result($code, $message, $ids = array())
    {
        return array(
            'ok' => true,
            'code' => $code,
            'message' => $message,
            'ids' => is_array($ids) ? $ids : array(),
            'errors' => array(),
        );
    }

    protected function failure_result($code, $message, $errors = array())
    {
        return array(
            'ok' => false,
            'code' => $code,
            'message' => $message,
            'ids' => array(),
            'errors' => is_array($errors) ? $errors : array($errors),
        );
    }

    protected function valid_id($value)
    {
        return is_numeric($value) && (int) $value > 0;
    }

    protected function table_exists($table)
    {
        if (!$this->safe_identifier($table)) {
            return false;
        }

        if (!array_key_exists($table, $this->table_exists_cache)) {
            $this->table_exists_cache[$table] = $this->db->table_exists($table);
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
            $this->field_exists_cache[$cache_key] = $this->db->field_exists($field, $table);
        }

        return $this->field_exists_cache[$cache_key];
    }

    protected function index_exists($table, $index_name)
    {
        if (!$this->safe_identifier($table) || !$this->safe_identifier($index_name) || !$this->table_exists($table)) {
            return false;
        }

        $cache_key = $table . '.' . $index_name;
        if (!array_key_exists($cache_key, $this->index_exists_cache)) {
            $query = $this->db->query(
                'SHOW INDEX FROM `' . $table . '` WHERE `Key_name` = ?',
                array($index_name)
            );
            $this->index_exists_cache[$cache_key] = $query && $query->num_rows() > 0;
        }

        return $this->index_exists_cache[$cache_key];
    }

    protected function safe_identifier($value)
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9_]+$/', $value);
    }
}
