<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Youngo_payment_config_audit_model extends CI_Model
{
    public $db;

    protected $table = 'youngo_payment_config_audit_logs';

    protected $private_fields = array(
        'public_key',
        'secret_key',
        'hmac_secret',
        'api_key',
        'client_secret',
        'authorization',
        'auth_header',
    );

    protected $presence_fields = array(
        'public_key',
        'secret_key',
        'hmac_secret',
        'api_key',
        'card_integration_id_egp',
        'wallet_integration_id_egp',
        'api_base_url',
        'checkout_base_url',
        'return_url',
        'notification_url',
        'instapay_target_label',
        'instapay_target_address',
        'instapay_target_link',
        'instapay_instructions_ar',
        'instapay_instructions_en',
    );

    protected $summary_fields = array(
        'provider',
        'mode',
        'currency',
        'amount_multiplier',
        'enabled',
        'network_enabled',
        'sandbox_network_testing_enabled',
        'webhook_testing_enabled',
        'checkout_routes_enabled',
        'checkout_local_testing_enabled',
        'checkout_cta_enabled',
        'live_mode_allowed',
        'transaction_inquiry_enabled',
        'instapay_enabled_for_checkout',
        'instapay_max_upload_mb',
        'instapay_allowed_mimes',
        'readiness_status',
        'private_storage_status',
        'updated_by_user_id',
    );

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

    public function record_config_audit($provider, $mode, $action, $before, $after, $actor_user_id = null)
    {
        $provider = $this->normalize_provider($provider);
        $mode = $this->normalize_mode($mode);
        $action = $this->clean_action($action);

        if ($provider === '' || $mode === '' || $action === '') {
            return $this->failure_result('invalid_audit_identity', 'Provider, mode, and action are required.');
        }

        if (!$this->schema_ready()) {
            return $this->failure_result('audit_schema_not_ready', 'YounGo payment config audit schema is not ready.');
        }

        $unsafe = array_merge(
            $this->find_unsafe_private_fields($before),
            $this->find_unsafe_private_fields($after)
        );
        if (!empty($unsafe)) {
            return $this->failure_result('private_values_rejected', 'Raw private Paymob values cannot be stored in audit logs.', array(
                'fields' => array_values(array_unique($unsafe)),
            ));
        }

        $before_summary = $this->redacted_config_summary($before);
        $after_summary = $this->redacted_config_summary($after);
        $changed_fields = $this->changed_fields($before_summary, $after_summary);
        $secret_presence_changes = $this->secret_presence_changes($before_summary, $after_summary);
        $now = time();

        $data = array(
            'provider' => $provider,
            'mode' => $mode,
            'action' => $action,
            'actor_user_id' => $this->positive_int_or_null($actor_user_id),
            'actor_role' => $this->current_actor_role(),
            'actor_type' => $this->current_actor_type(),
            'changed_fields_json' => json_encode($changed_fields, JSON_UNESCAPED_SLASHES),
            'before_summary_json' => json_encode($before_summary, JSON_UNESCAPED_SLASHES),
            'after_summary_json' => json_encode($after_summary, JSON_UNESCAPED_SLASHES),
            'secret_presence_changes_json' => json_encode($secret_presence_changes, JSON_UNESCAPED_SLASHES),
            'ip_address' => $this->safe_ip_address(),
            'user_agent' => $this->safe_user_agent(),
            'created_at' => $now,
        );

        $inserted = $this->db->insert($this->table, $this->filter_columns($data));
        if (!$inserted) {
            return $this->failure_result('audit_insert_failed', 'Could not record payment config audit log.');
        }

        return $this->success_result('audit_recorded', 'Payment config audit log recorded.', array(
            'audit_id' => (int) $this->db->insert_id(),
            'summary' => array(
                'provider' => $provider,
                'mode' => $mode,
                'action' => $action,
                'changed_fields' => $changed_fields,
                'secret_presence_changes' => $secret_presence_changes,
            ),
        ));
    }

    public function get_recent_audit_logs($provider, $mode, $limit = 20)
    {
        $provider = $this->normalize_provider($provider);
        $mode = $this->normalize_mode($mode);
        $limit = min(max((int) $limit, 1), 100);

        if ($provider === '' || $mode === '') {
            return $this->failure_result('invalid_provider_or_mode', 'Provider and mode are required.');
        }

        if (!$this->schema_ready()) {
            return $this->failure_result('audit_schema_not_ready', 'YounGo payment config audit schema is not ready.');
        }

        $query = $this->db
            ->where('provider', $provider)
            ->where('mode', $mode)
            ->order_by('id', 'desc')
            ->get($this->table, $limit);

        $logs = array();
        foreach ($query->result_array() as $row) {
            $logs[] = $this->get_safe_audit_summary($row);
        }

        return $this->success_result('audit_logs_loaded', 'Recent payment config audit logs loaded.', array(
            'logs' => $logs,
        ));
    }

    public function get_safe_audit_summary($row)
    {
        $row = is_array($row) ? $row : array();

        return array(
            'id' => isset($row['id']) ? (int) $row['id'] : null,
            'provider' => isset($row['provider']) ? $this->normalize_provider($row['provider']) : '',
            'mode' => isset($row['mode']) ? $this->normalize_mode($row['mode']) : '',
            'action' => isset($row['action']) ? $this->clean_action($row['action']) : '',
            'actor_user_id' => isset($row['actor_user_id']) ? $this->positive_int_or_null($row['actor_user_id']) : null,
            'actor_role' => isset($row['actor_role']) ? $this->clean_text($row['actor_role'], 80) : null,
            'actor_type' => isset($row['actor_type']) ? $this->clean_text($row['actor_type'], 80) : null,
            'changed_fields' => $this->decode_json_array(isset($row['changed_fields_json']) ? $row['changed_fields_json'] : null),
            'before_summary' => $this->redacted_config_summary($this->decode_json_array(isset($row['before_summary_json']) ? $row['before_summary_json'] : null)),
            'after_summary' => $this->redacted_config_summary($this->decode_json_array(isset($row['after_summary_json']) ? $row['after_summary_json'] : null)),
            'secret_presence_changes' => $this->decode_json_array(isset($row['secret_presence_changes_json']) ? $row['secret_presence_changes_json'] : null),
            'ip_address' => isset($row['ip_address']) && $row['ip_address'] !== '' ? 'configured_redacted' : 'missing',
            'user_agent' => isset($row['user_agent']) && $row['user_agent'] !== '' ? 'configured_redacted' : 'missing',
            'created_at' => isset($row['created_at']) ? (int) $row['created_at'] : null,
        );
    }

    public function schema_ready()
    {
        return isset($this->db)
            && is_object($this->db)
            && $this->db->table_exists($this->table)
            && $this->db->field_exists('provider', $this->table)
            && $this->db->field_exists('mode', $this->table)
            && $this->db->field_exists('action', $this->table)
            && $this->db->field_exists('before_summary_json', $this->table)
            && $this->db->field_exists('secret_presence_changes_json', $this->table);
    }

    protected function redacted_config_summary($config)
    {
        $config = is_array($config) ? $config : array();
        $summary = array();

        foreach ($this->summary_fields as $field) {
            if (array_key_exists($field, $config)) {
                $summary[$field] = $this->safe_scalar($config[$field]);
            }
        }

        foreach ($this->presence_fields as $field) {
            $summary[$field] = $this->presence_label(isset($config[$field]) ? $config[$field] : null);
        }

        foreach (array('secret_key_present', 'hmac_secret_present', 'api_key_present', 'public_key_present') as $field) {
            if (array_key_exists($field, $config)) {
                $summary[$field] = !empty($config[$field]) ? 1 : 0;
            }
        }

        return $summary;
    }

    protected function changed_fields($before, $after)
    {
        $fields = array_unique(array_merge(array_keys($before), array_keys($after)));
        sort($fields);

        $changed = array();
        $secret_presence_fields = array('public_key', 'secret_key', 'hmac_secret', 'api_key');
        foreach ($fields as $field) {
            if (in_array($field, $secret_presence_fields, true)) {
                continue;
            }

            $before_value = array_key_exists($field, $before) ? $before[$field] : null;
            $after_value = array_key_exists($field, $after) ? $after[$field] : null;
            if ($before_value !== $after_value) {
                $changed[] = $field;
            }
        }

        return $changed;
    }

    protected function secret_presence_changes($before, $after)
    {
        $changes = array();
        foreach (array('public_key', 'secret_key', 'hmac_secret', 'api_key') as $field) {
            $before_value = array_key_exists($field, $before) ? $before[$field] : 'missing';
            $after_value = array_key_exists($field, $after) ? $after[$field] : 'missing';
            if ($before_value !== $after_value) {
                $changes[$field] = array(
                    'before' => $before_value,
                    'after' => $after_value,
                    'presence_changed' => true,
                );
            }
        }

        return $changes;
    }

    protected function find_unsafe_private_fields($value, $path = '')
    {
        $unsafe = array();
        if (!is_array($value)) {
            return $this->value_contains_secret_shape($value) ? array($path === '' ? 'value' : $path) : array();
        }

        foreach ($value as $key => $child) {
            $field = strtolower((string) $key);
            $child_path = $path === '' ? $field : $path . '.' . $field;

            if (in_array($field, $this->private_fields, true)) {
                if (is_array($child) || is_object($child) || !in_array((string) $child, array('', '0', 'missing', 'configured_redacted'), true)) {
                    $unsafe[] = $child_path;
                    continue;
                }
            }

            if ($this->value_contains_secret_shape($child)) {
                $unsafe[] = $child_path;
            }

            if (is_array($child)) {
                $unsafe = array_merge($unsafe, $this->find_unsafe_private_fields($child, $child_path));
            }
        }

        return $unsafe;
    }

    protected function value_contains_secret_shape($value)
    {
        if (is_array($value) || is_object($value)) {
            return false;
        }

        $value = (string) $value;
        if ($value === '') {
            return false;
        }

        return (bool) preg_match('/(sk_live|sk_test|Bearer\s+|[A-Fa-f0-9]{64,})/', $value);
    }

    protected function filter_columns($data)
    {
        $filtered = array();
        foreach ($data as $field => $value) {
            if ($this->db->field_exists($field, $this->table)) {
                $filtered[$field] = $value;
            }
        }

        return $filtered;
    }

    protected function decode_json_array($value)
    {
        if (!is_string($value) || trim($value) === '') {
            return array();
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : array();
    }

    protected function normalize_provider($provider)
    {
        $provider = strtolower(trim((string) $provider));
        $provider = preg_replace('/[^a-z0-9_\-]/', '', $provider);
        return strlen($provider) > 50 ? substr($provider, 0, 50) : $provider;
    }

    protected function normalize_mode($mode)
    {
        $mode = strtolower(trim((string) $mode));
        return in_array($mode, array('sandbox', 'live', 'manual'), true) ? $mode : '';
    }

    protected function clean_action($value)
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9_\-]/', '', $value);
        return strlen($value) > 80 ? substr($value, 0, 80) : $value;
    }

    protected function clean_text($value, $max_length)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return strlen($value) > $max_length ? substr($value, 0, $max_length) : $value;
    }

    protected function safe_scalar($value)
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return $this->clean_text($value, 500);
    }

    protected function presence_label($value)
    {
        if ($value === null || $value === '' || $value === false || $value === 0 || $value === '0' || $value === 'missing') {
            return 'missing';
        }

        return 'configured_redacted';
    }

    protected function positive_int_or_null($value)
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    protected function current_actor_role()
    {
        if (!function_exists('get_instance')) {
            return null;
        }

        $CI = get_instance();
        if (!isset($CI->session) || !is_object($CI->session)) {
            return null;
        }

        $role = $CI->session->userdata('role_id');
        return $role !== null && $role !== '' ? 'role_id_' . (int) $role : null;
    }

    protected function current_actor_type()
    {
        if (!function_exists('get_instance')) {
            return null;
        }

        $CI = get_instance();
        if (!isset($CI->session) || !is_object($CI->session)) {
            return null;
        }

        $type = $CI->session->userdata('role');
        return $type !== null && $type !== '' ? $this->clean_text($type, 80) : null;
    }

    protected function safe_ip_address()
    {
        if (isset($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return null;
    }

    protected function safe_user_agent()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $this->clean_text($_SERVER['HTTP_USER_AGENT'], 255) : null;
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
