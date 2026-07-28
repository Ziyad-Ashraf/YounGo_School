<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Youngo_payment_config_model extends CI_Model
{
    public $db;

    protected $table = 'youngo_payment_provider_configs';
    protected $secret_table = 'youngo_payment_provider_secret_configs';
    protected $private_fields = array(
        'public_key',
        'secret_key',
        'hmac_secret',
        'api_key',
        'client_secret',
        'authorization',
        'auth_header',
    );
    protected $activation_flags = array(
        'enabled',
        'network_enabled',
        'sandbox_network_testing_enabled',
        'webhook_testing_enabled',
        'checkout_routes_enabled',
        'checkout_local_testing_enabled',
        'checkout_cta_enabled',
        'live_mode_allowed',
        'transaction_inquiry_enabled',
    );

    public function __construct($params = array())
    {
        parent::__construct();

        if (isset($params['db']) && is_object($params['db'])) {
            $this->db = $params['db'];
        } elseif (function_exists('get_instance')) {
            $CI = get_instance();
            if (isset($CI->db)) {
                $this->db = $CI->db;
            }
        }
    }

    public function get_provider_config($provider, $mode)
    {
        $provider = $this->normalize_provider($provider);
        $mode = $this->normalize_mode($mode);

        if ($provider === '' || $mode === '') {
            return $this->failure_result('invalid_provider_or_mode', 'Provider and mode are required.');
        }

        if (!$this->schema_ready()) {
            return $this->failure_result('schema_not_ready', 'YounGo payment provider config schema is not ready.');
        }

        $row = $this->db
            ->where('provider', $provider)
            ->where('mode', $mode)
            ->get($this->table, 1)
            ->row_array();

        if (empty($row)) {
            return $this->success_result('config_defaults', 'Provider config defaults returned.', array(
                'config' => $this->default_config($provider, $mode),
                'exists' => false,
            ));
        }

        return $this->success_result('config_loaded', 'Provider config loaded.', array(
            'config' => $this->normalize_row($row),
            'exists' => true,
        ));
    }

    public function upsert_non_private_config($provider, $mode, $data)
    {
        $provider = $this->normalize_provider($provider);
        $mode = $this->normalize_mode($mode);

        if ($provider === '' || $mode === '') {
            return $this->failure_result('invalid_provider_or_mode', 'Provider and mode are required.');
        }

        if (!$this->schema_ready()) {
            return $this->failure_result('schema_not_ready', 'YounGo payment provider config schema is not ready.');
        }

        if (!is_array($data)) {
            return $this->failure_result('invalid_config_data', 'Config data must be an array.');
        }

        $private_input = $this->private_fields_present($data);
        if (!empty($private_input)) {
            $code = $this->encryption_key_is_ready() && $this->secret_schema_ready()
                ? 'private_fields_must_use_credential_save'
                : 'private_storage_blocked_encryption_key_or_schema_missing';
            return $this->failure_result($code, 'Private Paymob values must be saved through the encrypted credential flow.', array(
                'private_fields_rejected' => $private_input,
            ));
        }

        $existing = $this->get_provider_config($provider, $mode);
        $current = !empty($existing['ok']) && !empty($existing['data']['config'])
            ? $existing['data']['config']
            : $this->default_config($provider, $mode);

        $normalized = $this->normalize_non_private_input($data, $current, $provider, $mode);
        if (empty($normalized['ok'])) {
            return $normalized;
        }

        $config = $normalized['data']['config'];
        $readiness = $this->build_readiness_summary($config);
        $now = time();

        $save = array_merge($config, array(
            'readiness_status' => $readiness['status'],
            'readiness_errors' => json_encode($readiness['errors'], JSON_UNESCAPED_SLASHES),
            'private_storage_status' => $this->encrypted_credential_storage_status($provider, $mode),
            'last_readiness_checked_at' => $now,
            'updated_at' => $now,
        ));

        if (!$this->encryption_key_is_ready()) {
            $save['secret_key_present'] = 0;
            $save['hmac_secret_present'] = 0;
            $save['api_key_present'] = 0;
        }

        $exists = !empty($existing['data']['exists']);
        if ($exists) {
            $this->db->where('provider', $provider)->where('mode', $mode);
            $updated = $this->db->update($this->table, $this->filter_columns($save));
            if (!$updated) {
                return $this->failure_result('config_update_failed', 'Could not update payment provider config.');
            }

            return $this->success_result('config_updated', 'Payment provider config updated.', array(
                'config' => $this->get_safe_config_summary($provider, $mode),
            ));
        }

        $save['created_at'] = $now;
        $inserted = $this->db->insert($this->table, $this->filter_columns($save));
        if (!$inserted) {
            return $this->failure_result('config_insert_failed', 'Could not insert payment provider config.');
        }

        return $this->success_result('config_inserted', 'Payment provider config inserted.', array(
            'config' => $this->get_safe_config_summary($provider, $mode),
        ));
    }

    public function upsert_dashboard_non_private_config($provider, $mode, $data, $actor_user_id = null)
    {
        $provider = $this->normalize_provider($provider);
        $mode = $this->normalize_mode($mode);

        if ($provider !== 'paymob') {
            return $this->failure_result('provider_must_be_paymob', 'Only Paymob configuration can be saved here.');
        }

        if ($mode !== 'sandbox') {
            return $this->failure_result('sandbox_only_in_this_phase', 'Only sandbox Paymob configuration can be saved in this phase.');
        }

        if (!is_array($data)) {
            return $this->failure_result('invalid_config_data', 'Config data must be an array.');
        }

        $allowed_fields = array(
            'mode',
            'currency',
            'amount_multiplier',
            'card_integration_id_egp',
            'wallet_integration_id_egp',
            'api_base_url',
            'checkout_base_url',
            'return_url',
            'notification_url',
        );

        $blocked_fields = array_merge($this->private_fields, array(
            'public_key',
            'enabled',
            'network_enabled',
            'sandbox_network_testing_enabled',
            'webhook_testing_enabled',
            'checkout_routes_enabled',
            'checkout_local_testing_enabled',
            'checkout_cta_enabled',
            'live_mode_allowed',
            'transaction_inquiry_enabled',
        ));

        $blocked_present = array();
        foreach ($blocked_fields as $field) {
            if (array_key_exists($field, $data) && trim((string) $data[$field]) !== '') {
                $blocked_present[] = $field;
            }
        }

        if (!empty($blocked_present)) {
            return $this->failure_result('blocked_fields_rejected', 'Private values and activation gates cannot be saved in this phase.', array(
                'fields' => $blocked_present,
            ));
        }

        $unexpected = array();
        foreach ($data as $field => $value) {
            if (!in_array($field, $allowed_fields, true) && trim((string) $value) !== '') {
                $unexpected[] = $field;
            }
        }

        if (!empty($unexpected)) {
            return $this->failure_result('unsupported_fields_rejected', 'Unsupported Paymob config fields cannot be saved in this phase.', array(
                'fields' => $unexpected,
            ));
        }

        $input_mode = isset($data['mode']) ? $this->normalize_mode($data['mode']) : 'sandbox';
        if ($input_mode !== 'sandbox') {
            return $this->failure_result('sandbox_only_in_this_phase', 'Only sandbox Paymob configuration can be saved in this phase.');
        }

        $currency = isset($data['currency']) ? strtoupper(trim((string) $data['currency'])) : 'EGP';
        if ($currency !== 'EGP') {
            return $this->failure_result('unsupported_currency', 'YounGo Paymob config supports EGP only.');
        }

        $amount_multiplier = isset($data['amount_multiplier']) ? (int) $data['amount_multiplier'] : 100;
        if ($amount_multiplier !== 100) {
            return $this->failure_result('invalid_amount_multiplier', 'YounGo Paymob config requires amount multiplier 100.');
        }

        $card_integration_id = isset($data['card_integration_id_egp']) ? trim((string) $data['card_integration_id_egp']) : '';
        if ($card_integration_id !== '' && !preg_match('/^[0-9]+$/', $card_integration_id)) {
            return $this->failure_result('invalid_card_integration_id_egp', 'Card integration ID must be numeric when provided.');
        }

        $wallet_integration_id = isset($data['wallet_integration_id_egp']) ? trim((string) $data['wallet_integration_id_egp']) : '';
        if ($wallet_integration_id !== '' && !preg_match('/^[0-9]+$/', $wallet_integration_id)) {
            return $this->failure_result('invalid_wallet_integration_id_egp', 'Mobile wallet integration ID must be numeric when provided.');
        }

        $url_fields = array('api_base_url', 'checkout_base_url', 'return_url', 'notification_url');
        foreach ($url_fields as $url_field) {
            $url = isset($data[$url_field]) ? trim((string) $data[$url_field]) : '';
            if ($url !== '' && (!$this->valid_http_url($url))) {
                return $this->failure_result('invalid_' . $url_field, 'URLs must be valid http or https URLs.');
            }
        }

        $save = array(
            'mode' => 'sandbox',
            'currency' => 'EGP',
            'amount_multiplier' => 100,
            'card_integration_id_egp' => $card_integration_id,
            'wallet_integration_id_egp' => $wallet_integration_id,
            'api_base_url' => isset($data['api_base_url']) ? trim((string) $data['api_base_url']) : '',
            'checkout_base_url' => isset($data['checkout_base_url']) ? trim((string) $data['checkout_base_url']) : '',
            'return_url' => isset($data['return_url']) ? trim((string) $data['return_url']) : '',
            'notification_url' => isset($data['notification_url']) ? trim((string) $data['notification_url']) : '',
            'updated_by_user_id' => $actor_user_id,
        );

        return $this->upsert_non_private_config($provider, 'sandbox', $save);
    }

    public function upsert_dashboard_instapay_manual_config($data, $actor_user_id = null)
    {
        if (!is_array($data)) {
            return $this->failure_result('invalid_config_data', 'Instapay config data must be an array.');
        }

        if (!$this->instapay_schema_ready()) {
            return $this->failure_result('instapay_schema_not_ready', 'Manual Instapay config schema is not ready.');
        }

        $allowed_fields = array(
            'instapay_enabled_for_checkout',
            'instapay_target_label',
            'instapay_target_address',
            'instapay_target_link',
            'instapay_instructions_ar',
            'instapay_instructions_en',
            'instapay_max_upload_mb',
            'instapay_allowed_mimes',
        );

        $blocked_fields = array_merge($this->private_fields, array(
            'enabled',
            'network_enabled',
            'sandbox_network_testing_enabled',
            'webhook_testing_enabled',
            'checkout_routes_enabled',
            'checkout_local_testing_enabled',
            'checkout_cta_enabled',
            'live_mode_allowed',
            'transaction_inquiry_enabled',
            'provider',
            'mode',
            'currency',
            'amount_multiplier',
        ));

        $blocked_present = array();
        foreach ($blocked_fields as $field) {
            if (array_key_exists($field, $data) && trim((string) $data[$field]) !== '') {
                $blocked_present[] = $field;
            }
        }

        if (!empty($blocked_present)) {
            return $this->failure_result('blocked_fields_rejected', 'Private values and payment activation gates cannot be saved with manual Instapay config.', array(
                'fields' => $blocked_present,
            ));
        }

        $unexpected = array();
        foreach ($data as $field => $value) {
            if (!in_array($field, $allowed_fields, true) && trim((string) $value) !== '') {
                $unexpected[] = $field;
            }
        }

        if (!empty($unexpected)) {
            return $this->failure_result('unsupported_fields_rejected', 'Unsupported Instapay config fields cannot be saved.', array(
                'fields' => $unexpected,
            ));
        }

        $target_link = isset($data['instapay_target_link']) ? trim((string) $data['instapay_target_link']) : '';
        if ($target_link !== '' && !$this->valid_http_url($target_link)) {
            return $this->failure_result('invalid_instapay_target_link', 'Instapay link must be a valid http or https URL when provided.');
        }

        $max_upload_mb = isset($data['instapay_max_upload_mb']) && trim((string) $data['instapay_max_upload_mb']) !== ''
            ? (float) $data['instapay_max_upload_mb']
            : 5.0;
        if ($max_upload_mb < 1.0 || $max_upload_mb > 20.0) {
            return $this->failure_result('invalid_instapay_max_upload_mb', 'Instapay screenshot max upload size must be between 1 and 20 MB.');
        }

        $save = array(
            'provider' => 'instapay_manual',
            'mode' => 'manual',
            'currency' => 'EGP',
            'amount_multiplier' => 100,
            'instapay_enabled_for_checkout' => !empty($data['instapay_enabled_for_checkout']) ? 1 : 0,
            'instapay_target_label' => isset($data['instapay_target_label']) ? $this->clean_text($data['instapay_target_label'], 255) : null,
            'instapay_target_address' => isset($data['instapay_target_address']) ? $this->clean_text($data['instapay_target_address'], 255) : null,
            'instapay_target_link' => $target_link !== '' ? $this->clean_text($target_link, 500) : null,
            'instapay_instructions_ar' => isset($data['instapay_instructions_ar']) ? $this->clean_long_text($data['instapay_instructions_ar'], 5000) : null,
            'instapay_instructions_en' => isset($data['instapay_instructions_en']) ? $this->clean_long_text($data['instapay_instructions_en'], 5000) : null,
            'instapay_max_upload_mb' => number_format($max_upload_mb, 2, '.', ''),
            'instapay_allowed_mimes' => 'image/jpeg,image/png,image/webp',
            'updated_by_user_id' => $actor_user_id,
        );

        return $this->upsert_non_private_config('instapay_manual', 'manual', $save);
    }

    public function get_instapay_config()
    {
        if (!$this->instapay_schema_ready()) {
            return $this->failure_result('instapay_schema_not_ready', 'Manual Instapay config schema is not ready.');
        }

        return $this->get_provider_config('instapay_manual', 'manual');
    }

    public function is_instapay_checkout_enabled()
    {
        $config = $this->get_instapay_config();
        return !empty($config['ok'])
            && !empty($config['data']['exists'])
            && !empty($config['data']['config']['instapay_enabled_for_checkout']);
    }

    public function build_instapay_target_snapshot($language = 'english')
    {
        $config = $this->get_instapay_config();
        if (empty($config['ok'])) {
            return $config;
        }

        $row = !empty($config['data']['config']) && is_array($config['data']['config'])
            ? $config['data']['config']
            : $this->default_config('instapay_manual', 'manual');
        $language = $this->normalize_instruction_language($language);
        $instructions_ar = isset($row['instapay_instructions_ar']) ? (string) $row['instapay_instructions_ar'] : '';
        $instructions_en = isset($row['instapay_instructions_en']) ? (string) $row['instapay_instructions_en'] : '';

        return $this->success_result('instapay_target_snapshot_built', 'Manual Instapay target snapshot built.', array(
            'snapshot' => array(
                'provider' => 'instapay_manual',
                'mode' => 'manual',
                'enabled' => !empty($row['instapay_enabled_for_checkout']),
                'label' => isset($row['instapay_target_label']) ? (string) $row['instapay_target_label'] : '',
                'address' => isset($row['instapay_target_address']) ? (string) $row['instapay_target_address'] : '',
                'link' => isset($row['instapay_target_link']) ? (string) $row['instapay_target_link'] : '',
                'instructions_ar' => $instructions_ar,
                'instructions_en' => $instructions_en,
                'instructions' => $language === 'arabic' ? $instructions_ar : $instructions_en,
                'language' => $language,
                'currency' => 'EGP',
                'max_upload_mb' => isset($row['instapay_max_upload_mb']) ? (string) $row['instapay_max_upload_mb'] : '5.00',
                'allowed_mimes' => $this->instapay_allowed_mimes_from_row($row),
                'config_exists' => !empty($config['data']['exists']),
            ),
        ));
    }

    public function get_readiness_summary($provider, $mode)
    {
        $config = $this->get_provider_config($provider, $mode);
        if (empty($config['ok'])) {
            return $config;
        }

        $summary = $this->build_readiness_summary($config['data']['config']);

        return $this->success_result('readiness_summary', 'Payment provider readiness summary loaded.', array(
            'readiness' => $summary,
        ));
    }

    public function get_hybrid_readiness_summary($provider, $mode, $server_config_summary = array())
    {
        $config = $this->get_provider_config($provider, $mode);
        if (empty($config['ok'])) {
            return $config;
        }

        $summary = $this->build_hybrid_readiness_summary($config['data']['config'], $server_config_summary);

        return $this->success_result('hybrid_readiness_summary', 'Hybrid payment provider readiness summary loaded.', array(
            'readiness' => $summary,
        ));
    }

    public function get_safe_config_summary($provider, $mode)
    {
        $config = $this->get_provider_config($provider, $mode);
        if (empty($config['ok'])) {
            return $config;
        }

        $row = $config['data']['config'];
        return $this->success_result('safe_config_summary', 'Safe payment provider config summary loaded.', array(
            'exists' => !empty($config['data']['exists']),
            'provider' => $row['provider'],
            'mode' => $row['mode'],
            'currency' => $row['currency'],
            'amount_multiplier' => (int) $row['amount_multiplier'],
            'enabled' => !empty($row['enabled']),
            'network_enabled' => !empty($row['network_enabled']),
            'sandbox_network_testing_enabled' => !empty($row['sandbox_network_testing_enabled']),
            'webhook_testing_enabled' => !empty($row['webhook_testing_enabled']),
            'checkout_routes_enabled' => !empty($row['checkout_routes_enabled']),
            'checkout_local_testing_enabled' => !empty($row['checkout_local_testing_enabled']),
            'checkout_cta_enabled' => !empty($row['checkout_cta_enabled']),
            'live_mode_allowed' => !empty($row['live_mode_allowed']),
            'transaction_inquiry_enabled' => !empty($row['transaction_inquiry_enabled']),
            'public_key' => $this->secret_presence_for_field($row['provider'], $row['mode'], 'public_key'),
            'secret_key' => $this->secret_presence_for_field($row['provider'], $row['mode'], 'secret_key'),
            'hmac_secret' => $this->secret_presence_for_field($row['provider'], $row['mode'], 'hmac_secret'),
            'api_key' => $this->secret_presence_for_field($row['provider'], $row['mode'], 'api_key'),
            'card_integration_id_egp' => $this->presence_label(isset($row['card_integration_id_egp']) ? $row['card_integration_id_egp'] : null),
            'wallet_integration_id_egp' => $this->presence_label(isset($row['wallet_integration_id_egp']) ? $row['wallet_integration_id_egp'] : null),
            'api_base_url' => $this->presence_label(isset($row['api_base_url']) ? $row['api_base_url'] : null),
            'checkout_base_url' => $this->presence_label(isset($row['checkout_base_url']) ? $row['checkout_base_url'] : null),
            'return_url' => $this->presence_label(isset($row['return_url']) ? $row['return_url'] : null),
            'notification_url' => $this->presence_label(isset($row['notification_url']) ? $row['notification_url'] : null),
            'instapay_enabled_for_checkout' => !empty($row['instapay_enabled_for_checkout']),
            'instapay_target_label' => isset($row['instapay_target_label']) ? (string) $row['instapay_target_label'] : null,
            'instapay_target_address' => isset($row['instapay_target_address']) ? (string) $row['instapay_target_address'] : null,
            'instapay_target_link' => isset($row['instapay_target_link']) ? (string) $row['instapay_target_link'] : null,
            'instapay_instructions_ar' => isset($row['instapay_instructions_ar']) ? (string) $row['instapay_instructions_ar'] : null,
            'instapay_instructions_en' => isset($row['instapay_instructions_en']) ? (string) $row['instapay_instructions_en'] : null,
            'instapay_max_upload_mb' => isset($row['instapay_max_upload_mb']) ? (string) $row['instapay_max_upload_mb'] : '5.00',
            'instapay_allowed_mimes' => $this->instapay_allowed_mimes_from_row($row),
            'readiness_status' => isset($row['readiness_status']) ? $row['readiness_status'] : 'not_configured',
            'private_storage_status' => $this->encrypted_credential_storage_status($row['provider'], $row['mode']),
            'updated_by_user_id' => isset($row['updated_by_user_id']) ? $row['updated_by_user_id'] : null,
        ));
    }

    public function get_safe_hybrid_config_summary($provider, $mode, $server_config_summary = array())
    {
        $db_summary = $this->get_safe_config_summary($provider, $mode);
        if (empty($db_summary['ok'])) {
            return $db_summary;
        }

        $server_presence = $this->safe_server_private_presence($server_config_summary);

        return $this->success_result('safe_hybrid_config_summary', 'Safe hybrid Paymob config summary loaded.', array(
            'storage_mode' => 'hybrid',
            'non_private_db_config' => !empty($db_summary['data']['exists']) ? 'configured_redacted' : 'missing',
            'private_values_source' => isset($server_presence['private_values_source']) ? $server_presence['private_values_source'] : 'server_config_required',
            'private_db_storage' => $this->encrypted_credential_storage_status($provider, $mode),
            'server_config_loaded' => !empty($server_presence['local_override_loaded']),
            'private_presence' => $server_presence,
            'encrypted_credential_presence' => $this->get_secret_credential_presence($provider, $mode),
            'db_summary' => $db_summary['data'],
        ));
    }

    public function schema_ready()
    {
        return $this->db->table_exists($this->table)
            && $this->db->field_exists('provider', $this->table)
            && $this->db->field_exists('mode', $this->table)
            && $this->db->field_exists('currency', $this->table)
            && $this->db->field_exists('enabled', $this->table)
            && $this->db->field_exists('wallet_integration_id_egp', $this->table)
            && $this->db->field_exists('private_storage_status', $this->table);
    }

    public function instapay_schema_ready()
    {
        if (!$this->schema_ready()) {
            return false;
        }

        foreach (array(
            'instapay_enabled_for_checkout',
            'instapay_target_label',
            'instapay_target_address',
            'instapay_target_link',
            'instapay_instructions_ar',
            'instapay_instructions_en',
            'instapay_max_upload_mb',
            'instapay_allowed_mimes',
        ) as $column) {
            if (!$this->db->field_exists($column, $this->table)) {
                return false;
            }
        }

        return true;
    }

    public function secret_schema_ready()
    {
        $required_columns = array(
            'provider',
            'mode',
            'encrypted_api_key',
            'encrypted_public_key',
            'encrypted_secret_key',
            'encrypted_hmac_secret',
            'has_api_key',
            'has_public_key',
            'has_secret_key',
            'has_hmac_secret',
            'encryption_version',
            'key_fingerprint',
            'storage_status',
        );

        if (!$this->db->table_exists($this->secret_table)) {
            return false;
        }

        foreach ($required_columns as $column) {
            if (!$this->db->field_exists($column, $this->secret_table)) {
                return false;
            }
        }

        return true;
    }

    public function encryption_key_is_ready()
    {
        if (!function_exists('get_instance')) {
            return false;
        }

        $CI = get_instance();
        return isset($CI->config) && trim((string) $CI->config->item('encryption_key')) !== '';
    }

    public function encrypted_credential_storage_status($provider = 'paymob', $mode = 'sandbox')
    {
        if (!$this->encryption_key_is_ready()) {
            return 'db_private_storage_blocked';
        }

        if (!$this->secret_schema_ready()) {
            return 'key_ready_schema_pending';
        }

        $presence = $this->get_secret_credential_presence($provider, $mode);
        if (empty($presence['ok']) || empty($presence['data']['exists'])) {
            return 'key_ready_schema_ready_no_values';
        }

        $data = $presence['data'];
        $required_present = !empty($data['public_key'])
            && !empty($data['secret_key'])
            && !empty($data['hmac_secret']);

        if ($required_present) {
            return 'key_ready_schema_ready_configured_redacted';
        }

        return 'key_ready_schema_ready_partial_values';
    }

    public function get_secret_credential_presence($provider, $mode)
    {
        $provider = $this->normalize_provider($provider);
        $mode = $this->normalize_mode($mode);

        $empty = array(
            'exists' => false,
            'provider' => $provider,
            'mode' => $mode,
            'api_key' => false,
            'public_key' => false,
            'secret_key' => false,
            'hmac_secret' => false,
            'api_key_status' => 'missing',
            'public_key_status' => 'missing',
            'secret_key_status' => 'missing',
            'hmac_secret_status' => 'missing',
            'storage_status' => $this->encrypted_credential_storage_status_without_presence_lookup(),
            'encryption_version' => null,
            'key_fingerprint' => 'missing',
            'updated_by_user_id' => null,
        );

        if ($provider === '' || $mode === '') {
            return $this->failure_result('invalid_provider_or_mode', 'Provider and mode are required.');
        }

        if (!$this->secret_schema_ready()) {
            return $this->success_result('secret_schema_missing', 'Encrypted credential schema is not ready.', $empty);
        }

        $row = $this->db
            ->select('provider, mode, has_api_key, has_public_key, has_secret_key, has_hmac_secret, encryption_version, key_fingerprint, storage_status, updated_by_user_id')
            ->where('provider', $provider)
            ->where('mode', $mode)
            ->get($this->secret_table, 1)
            ->row_array();

        if (empty($row)) {
            $empty['storage_status'] = $this->encryption_key_is_ready() ? 'key_ready_schema_ready_no_values' : 'db_private_storage_blocked';
            return $this->success_result('secret_presence_empty', 'Encrypted credential presence is empty.', $empty);
        }

        $data = array(
            'exists' => true,
            'provider' => $provider,
            'mode' => $mode,
            'api_key' => !empty($row['has_api_key']),
            'public_key' => !empty($row['has_public_key']),
            'secret_key' => !empty($row['has_secret_key']),
            'hmac_secret' => !empty($row['has_hmac_secret']),
            'api_key_status' => !empty($row['has_api_key']) ? 'configured_redacted' : 'missing',
            'public_key_status' => !empty($row['has_public_key']) ? 'configured_redacted' : 'missing',
            'secret_key_status' => !empty($row['has_secret_key']) ? 'configured_redacted' : 'missing',
            'hmac_secret_status' => !empty($row['has_hmac_secret']) ? 'configured_redacted' : 'missing',
            'storage_status' => isset($row['storage_status']) ? (string) $row['storage_status'] : 'schema_ready_no_values',
            'encryption_version' => isset($row['encryption_version']) ? (string) $row['encryption_version'] : null,
            'key_fingerprint' => !empty($row['key_fingerprint']) ? 'configured_redacted' : 'missing',
            'updated_by_user_id' => isset($row['updated_by_user_id']) ? $row['updated_by_user_id'] : null,
        );

        return $this->success_result('secret_presence_loaded', 'Encrypted credential presence loaded.', $data);
    }

    public function save_encrypted_credentials($provider, $mode, $credential_input, $actor_user_id = null, $options = array())
    {
        $provider = $this->normalize_provider($provider);
        $mode = $this->normalize_mode($mode);
        $options = is_array($options) ? $options : array();

        if ($provider !== 'paymob') {
            return $this->failure_result('provider_must_be_paymob', 'Only Paymob credentials can be saved here.');
        }

        if ($mode !== 'sandbox') {
            return $this->failure_result('sandbox_only_in_this_phase', 'Only sandbox Paymob credentials can be saved in this phase.');
        }

        if (!$this->encryption_key_is_ready()) {
            return $this->failure_result('encryption_key_missing', 'Encrypted credential storage requires a configured encryption key.');
        }

        if (!$this->secret_schema_ready()) {
            return $this->failure_result('secret_schema_not_ready', 'Encrypted credential storage schema is not ready.');
        }

        if (!is_array($credential_input)) {
            return $this->failure_result('invalid_credential_input', 'Credential input must be an array.');
        }

        $existing = $this->get_raw_secret_config_row($provider, $mode);
        $row = $this->default_secret_config_row($provider, $mode);
        if (!empty($existing)) {
            $row = array_merge($row, $existing);
        }

        $changed_fields = array();
        foreach ($this->credential_fields() as $field) {
            if (!array_key_exists($field, $credential_input)) {
                continue;
            }

            $value = trim((string) $credential_input[$field]);
            if ($value === '') {
                continue;
            }

            $validated = $this->validate_credential_value($field, $value, $options);
            if (empty($validated['ok'])) {
                return $validated;
            }

            $encrypted = $this->encrypt_private_value($value);
            if ($encrypted === false || $encrypted === '') {
                return $this->failure_result('credential_encryption_failed', 'Credential could not be encrypted.');
            }

            $row['encrypted_' . $field] = $encrypted;
            $row['has_' . $field] = 1;
            $changed_fields[] = $field;
        }

        if (empty($changed_fields)) {
            return $this->success_result('credentials_unchanged', 'No credential values were changed.', array(
                'changed_fields' => array(),
                'presence' => $this->get_secret_credential_presence($provider, $mode),
            ));
        }

        $row['provider'] = $provider;
        $row['mode'] = $mode;
        $row['encryption_version'] = 'youngo_openssl_aes_256_gcm_v1';
        $row['key_fingerprint'] = $this->encryption_key_fingerprint();
        $row['storage_status'] = $this->secret_row_storage_status($row);
        $row['updated_by_user_id'] = $this->positive_int_or_null($actor_user_id);
        $row['updated_at'] = time();

        $save = $this->filter_columns_for_table($row, $this->secret_table);
        unset($save['id']);

        if (!empty($existing)) {
            $this->db->where('provider', $provider)->where('mode', $mode);
            $saved = $this->db->update($this->secret_table, $save);
            if (!$saved) {
                return $this->failure_result('credential_update_failed', 'Encrypted credentials could not be updated.');
            }
        } else {
            $save['created_at'] = time();
            $saved = $this->db->insert($this->secret_table, $save);
            if (!$saved) {
                return $this->failure_result('credential_insert_failed', 'Encrypted credentials could not be saved.');
            }
        }

        return $this->success_result('credentials_saved', 'Paymob credentials were encrypted and saved.', array(
            'changed_fields' => array_values(array_unique($changed_fields)),
            'presence' => $this->get_secret_credential_presence($provider, $mode),
        ));
    }

    public function decrypt_credentials_for_runtime($provider, $mode)
    {
        $provider = $this->normalize_provider($provider);
        $mode = $this->normalize_mode($mode);

        if ($provider !== 'paymob' || $mode === '') {
            return $this->failure_result('invalid_provider_or_mode', 'Provider and mode are required.');
        }

        if (!$this->encryption_key_is_ready()) {
            return $this->failure_result('encryption_key_missing', 'Encrypted credential storage requires a configured encryption key.');
        }

        if (!$this->secret_schema_ready()) {
            return $this->failure_result('secret_schema_not_ready', 'Encrypted credential storage schema is not ready.');
        }

        $row = $this->get_raw_secret_config_row($provider, $mode);
        if (empty($row)) {
            return $this->failure_result('credentials_missing', 'Paymob credentials are not configured.');
        }

        $credentials = array();
        foreach ($this->credential_fields() as $field) {
            $encrypted_field = 'encrypted_' . $field;
            if (empty($row['has_' . $field]) || empty($row[$encrypted_field])) {
                $credentials[$field] = null;
                continue;
            }

            $decrypted = $this->decrypt_private_value($row[$encrypted_field]);
            if ($decrypted === false) {
                return $this->failure_result('credential_decryption_failed', 'Configured credentials could not be decrypted.');
            }

            $credentials[$field] = $decrypted;
        }

        return $this->success_result('credentials_decrypted_for_runtime', 'Paymob credentials loaded for internal runtime use.', array(
            'credentials' => $credentials,
        ));
    }

    protected function normalize_non_private_input($data, $current, $provider, $mode)
    {
        $config = $this->default_config($provider, $mode);
        if (is_array($current)) {
            $config = array_merge($config, $current);
        }

        $config['provider'] = $provider;
        $config['mode'] = $mode;

        if (array_key_exists('currency', $data)) {
            $currency = strtoupper(trim((string) $data['currency']));
            if ($currency !== 'EGP') {
                return $this->failure_result('unsupported_currency', 'YounGo Paymob config supports EGP only.');
            }
            $config['currency'] = 'EGP';
        }

        if (array_key_exists('amount_multiplier', $data)) {
            $multiplier = (int) $data['amount_multiplier'];
            if ($multiplier !== 100) {
                return $this->failure_result('invalid_amount_multiplier', 'YounGo Paymob config requires amount multiplier 100.');
            }
            $config['amount_multiplier'] = 100;
        }

        foreach ($this->activation_flags as $flag) {
            if (array_key_exists($flag, $data) && !empty($data[$flag])) {
                return $this->failure_result('activation_flags_disabled_in_this_phase', 'Payment activation/testing flags cannot be enabled in this phase.', array(
                    'flag' => $flag,
                ));
            }
            $config[$flag] = 0;
        }

        $string_fields = array(
            'card_integration_id_egp' => 100,
            'wallet_integration_id_egp' => 100,
            'api_base_url' => 255,
            'checkout_base_url' => 255,
            'return_url' => 500,
            'notification_url' => 500,
            'last_sandbox_test_status' => 50,
        );

        foreach ($string_fields as $field => $max_length) {
            if (array_key_exists($field, $data)) {
                $config[$field] = $this->clean_text($data[$field], $max_length);
            }
        }

        $instapay_string_fields = array(
            'instapay_target_label' => 255,
            'instapay_target_address' => 255,
            'instapay_target_link' => 500,
            'instapay_allowed_mimes' => 255,
        );

        foreach ($instapay_string_fields as $field => $max_length) {
            if (array_key_exists($field, $data)) {
                $config[$field] = $this->clean_text($data[$field], $max_length);
            }
        }

        foreach (array('instapay_instructions_ar', 'instapay_instructions_en') as $field) {
            if (array_key_exists($field, $data)) {
                $config[$field] = $this->clean_long_text($data[$field], 5000);
            }
        }

        if (array_key_exists('instapay_enabled_for_checkout', $data)) {
            $config['instapay_enabled_for_checkout'] = !empty($data['instapay_enabled_for_checkout']) ? 1 : 0;
        }

        if (array_key_exists('instapay_max_upload_mb', $data)) {
            $config['instapay_max_upload_mb'] = $this->decimal_or_default($data['instapay_max_upload_mb'], '5.00');
        }

        $config['public_key'] = null;
        $config['public_key_present'] = 0;
        $config['secret_key_present'] = 0;
        $config['hmac_secret_present'] = 0;
        $config['api_key_present'] = 0;

        if (array_key_exists('updated_by_user_id', $data)) {
            $config['updated_by_user_id'] = $this->positive_int_or_null($data['updated_by_user_id']);
        }

        return $this->success_result('config_normalized', 'Payment provider config normalized.', array(
            'config' => $config,
        ));
    }

    protected function build_readiness_summary($config)
    {
        if ($this->normalize_provider($config['provider']) === 'instapay_manual') {
            return $this->build_instapay_manual_readiness_summary($config);
        }

        $errors = array();

        if ($this->normalize_provider($config['provider']) !== 'paymob') {
            $errors[] = 'provider_must_be_paymob';
        }

        if (!in_array($this->normalize_mode($config['mode']), array('sandbox', 'live'), true)) {
            $errors[] = 'invalid_mode';
        }

        if (strtoupper((string) $config['currency']) !== 'EGP') {
            $errors[] = 'currency_must_be_egp';
        }

        if ((int) $config['amount_multiplier'] !== 100) {
            $errors[] = 'amount_multiplier_must_be_100';
        }

        if ($this->secret_presence_for_field($config['provider'], $config['mode'], 'public_key') !== 'configured_redacted') {
            $errors[] = 'public_key_missing';
        }

        if ($this->secret_presence_for_field($config['provider'], $config['mode'], 'secret_key') !== 'configured_redacted') {
            $errors[] = 'secret_key_missing';
        }

        if ($this->secret_presence_for_field($config['provider'], $config['mode'], 'hmac_secret') !== 'configured_redacted') {
            $errors[] = 'hmac_secret_missing';
        }

        if (empty($config['card_integration_id_egp']) || !preg_match('/^[0-9]+$/', (string) $config['card_integration_id_egp'])) {
            $errors[] = 'card_integration_id_egp_missing_or_invalid';
        }

        if (empty($config['wallet_integration_id_egp']) || !preg_match('/^[0-9]+$/', (string) $config['wallet_integration_id_egp'])) {
            $errors[] = 'wallet_integration_id_egp_missing_or_invalid';
        }

        foreach (array('api_base_url', 'checkout_base_url', 'return_url', 'notification_url') as $url_field) {
            if (empty($config[$url_field]) || !filter_var($config[$url_field], FILTER_VALIDATE_URL)) {
                $errors[] = $url_field . '_missing_or_invalid';
            }
        }

        if (!$this->encryption_key_is_ready()) {
            $errors[] = 'private_storage_blocked_encryption_key_missing';
        }

        if (!$this->secret_schema_ready()) {
            $errors[] = 'encrypted_credential_schema_missing';
        }

        if (!empty($config['enabled']) || !empty($config['network_enabled']) || !empty($config['checkout_cta_enabled'])) {
            $errors[] = 'activation_flags_must_remain_disabled_in_this_phase';
        }

        $status = empty($errors) ? 'configured_disabled' : 'not_configured';

        return array(
            'status' => $status,
            'ready_for_network' => false,
            'ready_for_cta' => false,
            'errors' => $errors,
        );
    }

    protected function build_hybrid_readiness_summary($config, $server_config_summary)
    {
        if ($this->normalize_provider($config['provider']) === 'instapay_manual') {
            return $this->build_instapay_manual_readiness_summary($config);
        }

        $errors = array();
        $server_presence = $this->safe_server_private_presence($server_config_summary);
        $private_presence = $this->private_presence_for_readiness(
            isset($config['provider']) ? $config['provider'] : 'paymob',
            isset($config['mode']) ? $config['mode'] : 'sandbox',
            $server_presence
        );

        if ($this->normalize_provider($config['provider']) !== 'paymob') {
            $errors[] = 'provider_must_be_paymob';
        }

        if (!in_array($this->normalize_mode($config['mode']), array('sandbox', 'live'), true)) {
            $errors[] = 'invalid_mode';
        }

        if (strtoupper((string) $config['currency']) !== 'EGP') {
            $errors[] = 'currency_must_be_egp';
        }

        if ((int) $config['amount_multiplier'] !== 100) {
            $errors[] = 'amount_multiplier_must_be_100';
        }

        if ($private_presence['public_key'] !== 'configured_redacted') {
            $errors[] = 'public_key_required';
        }

        if ($private_presence['secret_key'] !== 'configured_redacted') {
            $errors[] = 'secret_key_required';
        }

        if ($private_presence['hmac_secret'] !== 'configured_redacted') {
            $errors[] = 'hmac_secret_required';
        }

        if (empty($config['card_integration_id_egp']) || !preg_match('/^[0-9]+$/', (string) $config['card_integration_id_egp'])) {
            $errors[] = 'card_integration_id_egp_missing_or_invalid';
        }

        if (empty($config['wallet_integration_id_egp']) || !preg_match('/^[0-9]+$/', (string) $config['wallet_integration_id_egp'])) {
            $errors[] = 'wallet_integration_id_egp_missing_or_invalid';
        }

        foreach (array('api_base_url', 'checkout_base_url', 'return_url', 'notification_url') as $url_field) {
            if (empty($config[$url_field]) || !filter_var($config[$url_field], FILTER_VALIDATE_URL)) {
                $errors[] = $url_field . '_missing_or_invalid';
            }
        }

        if (!empty($config['enabled']) || !empty($config['network_enabled']) || !empty($config['checkout_cta_enabled'])) {
            $errors[] = 'activation_flags_must_remain_disabled_in_this_phase';
        }

        $storage_notes = array(
            'hybrid_storage_active',
            $this->encrypted_credential_storage_status(
                isset($config['provider']) ? $config['provider'] : 'paymob',
                isset($config['mode']) ? $config['mode'] : 'sandbox'
            ),
            isset($server_presence['private_values_source']) ? $server_presence['private_values_source'] : 'server_config_required',
        );
        $status = empty($errors) ? 'hybrid_configured_disabled' : 'hybrid_not_ready';

        return array(
            'status' => $status,
            'storage_mode' => 'hybrid',
            'private_db_storage' => $this->encrypted_credential_storage_status(
                isset($config['provider']) ? $config['provider'] : 'paymob',
                isset($config['mode']) ? $config['mode'] : 'sandbox'
            ),
            'private_values_source' => isset($private_presence['private_values_source']) ? $private_presence['private_values_source'] : 'encrypted_db_or_server_config',
            'private_presence' => $private_presence,
            'ready_for_network' => false,
            'ready_for_cta' => false,
            'errors' => $errors,
            'storage_notes' => $storage_notes,
        );
    }

    protected function default_config($provider, $mode)
    {
        return array(
            'provider' => $provider,
            'mode' => $mode,
            'currency' => 'EGP',
            'amount_multiplier' => 100,
            'enabled' => 0,
            'network_enabled' => 0,
            'sandbox_network_testing_enabled' => 0,
            'webhook_testing_enabled' => 0,
            'checkout_routes_enabled' => 0,
            'checkout_local_testing_enabled' => 0,
            'checkout_cta_enabled' => 0,
            'live_mode_allowed' => 0,
            'public_key' => null,
            'public_key_present' => 0,
            'secret_key_present' => 0,
            'hmac_secret_present' => 0,
            'api_key_present' => 0,
            'card_integration_id_egp' => null,
            'wallet_integration_id_egp' => null,
            'api_base_url' => null,
            'checkout_base_url' => null,
            'return_url' => null,
            'notification_url' => null,
            'transaction_inquiry_enabled' => 0,
            'instapay_enabled_for_checkout' => 0,
            'instapay_target_label' => null,
            'instapay_target_address' => null,
            'instapay_target_link' => null,
            'instapay_instructions_ar' => null,
            'instapay_instructions_en' => null,
            'instapay_max_upload_mb' => '5.00',
            'instapay_allowed_mimes' => 'image/jpeg,image/png,image/webp',
            'readiness_status' => 'not_configured',
            'readiness_errors' => null,
            'private_storage_status' => $this->encrypted_credential_storage_status($provider, $mode),
            'last_readiness_checked_at' => null,
            'last_sandbox_test_at' => null,
            'last_sandbox_test_status' => null,
            'updated_by_user_id' => null,
            'created_at' => null,
            'updated_at' => null,
        );
    }

    protected function normalize_row($row)
    {
        $row = array_merge($this->default_config(
            $this->normalize_provider(isset($row['provider']) ? $row['provider'] : 'paymob'),
            $this->normalize_mode(isset($row['mode']) ? $row['mode'] : 'sandbox')
        ), is_array($row) ? $row : array());

        foreach ($this->activation_flags as $flag) {
            $row[$flag] = !empty($row[$flag]) ? 1 : 0;
        }

        foreach (array('public_key_present', 'secret_key_present', 'hmac_secret_present', 'api_key_present') as $flag) {
            $row[$flag] = !empty($row[$flag]) ? 1 : 0;
        }

        $row['instapay_enabled_for_checkout'] = !empty($row['instapay_enabled_for_checkout']) ? 1 : 0;
        $row['instapay_max_upload_mb'] = $this->decimal_or_default(isset($row['instapay_max_upload_mb']) ? $row['instapay_max_upload_mb'] : null, '5.00');

        return $row;
    }

    protected function secret_presence_for_field($provider, $mode, $field)
    {
        $presence = $this->get_secret_credential_presence($provider, $mode);
        $status_key = $field . '_status';

        return !empty($presence['ok']) && isset($presence['data'][$status_key])
            ? $presence['data'][$status_key]
            : 'missing';
    }

    protected function credential_fields()
    {
        return array('api_key', 'public_key', 'secret_key', 'hmac_secret');
    }

    protected function get_raw_secret_config_row($provider, $mode)
    {
        if (!$this->secret_schema_ready()) {
            return array();
        }

        $row = $this->db
            ->where('provider', $provider)
            ->where('mode', $mode)
            ->get($this->secret_table, 1)
            ->row_array();

        return is_array($row) ? $row : array();
    }

    protected function default_secret_config_row($provider, $mode)
    {
        return array(
            'provider' => $provider,
            'mode' => $mode,
            'encrypted_api_key' => null,
            'encrypted_public_key' => null,
            'encrypted_secret_key' => null,
            'encrypted_hmac_secret' => null,
            'has_api_key' => 0,
            'has_public_key' => 0,
            'has_secret_key' => 0,
            'has_hmac_secret' => 0,
            'encryption_version' => 'youngo_openssl_aes_256_gcm_v1',
            'key_fingerprint' => null,
            'storage_status' => 'schema_ready_no_values',
            'last_rotated_at' => null,
            'updated_by_user_id' => null,
            'created_at' => null,
            'updated_at' => null,
        );
    }

    protected function validate_credential_value($field, $value, $options)
    {
        if (!in_array($field, $this->credential_fields(), true)) {
            return $this->failure_result('unsupported_credential_field', 'Unsupported credential field.');
        }

        if (!is_string($value) || trim($value) === '') {
            return $this->failure_result('empty_credential_value', 'Credential value cannot be empty.');
        }

        $value = trim($value);
        if (strlen($value) < 12) {
            return $this->failure_result('credential_value_too_short', 'Credential value is too short.');
        }

        if (strlen($value) > 4096) {
            return $this->failure_result('credential_value_too_long', 'Credential value is too long.');
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value)) {
            return $this->failure_result('credential_value_invalid_characters', 'Credential value contains invalid characters.');
        }

        return $this->success_result('credential_value_valid', 'Credential value is valid.');
    }

    protected function encrypt_private_value($value)
    {
        $key = $this->configured_encryption_key();
        if ($key === '' || !function_exists('openssl_encrypt') || !in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
            return false;
        }

        $iv = random_bytes(12);
        $tag = '';
        $cipher_key = hash('sha256', 'youngo-paymob-credentials|' . $key, true);
        $cipher_text = openssl_encrypt(
            (string) $value,
            'aes-256-gcm',
            $cipher_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            'youngo_payment_provider_secret_configs'
        );

        if ($cipher_text === false || $tag === '') {
            return false;
        }

        return json_encode(array(
            'v' => 1,
            'cipher' => 'aes-256-gcm',
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'data' => base64_encode($cipher_text),
        ), JSON_UNESCAPED_SLASHES);
    }

    protected function decrypt_private_value($encrypted_value)
    {
        $key = $this->configured_encryption_key();
        if ($key === '' || !is_string($encrypted_value) || trim($encrypted_value) === '') {
            return false;
        }

        $payload = json_decode($encrypted_value, true);
        if (!is_array($payload) || empty($payload['iv']) || empty($payload['tag']) || empty($payload['data'])) {
            return false;
        }

        $iv = base64_decode($payload['iv'], true);
        $tag = base64_decode($payload['tag'], true);
        $cipher_text = base64_decode($payload['data'], true);
        if ($iv === false || $tag === false || $cipher_text === false) {
            return false;
        }

        $cipher_key = hash('sha256', 'youngo-paymob-credentials|' . $key, true);
        return openssl_decrypt(
            $cipher_text,
            'aes-256-gcm',
            $cipher_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            'youngo_payment_provider_secret_configs'
        );
    }

    protected function configured_encryption_key()
    {
        $CI = get_instance();
        return isset($CI->config) ? trim((string) $CI->config->item('encryption_key')) : '';
    }

    protected function encryption_key_fingerprint()
    {
        $key = $this->configured_encryption_key();
        return $key === '' ? null : hash('sha256', 'youngo-paymob-key-fingerprint|' . $key);
    }

    protected function secret_row_storage_status($row)
    {
        $required = !empty($row['has_public_key']) && !empty($row['has_secret_key']) && !empty($row['has_hmac_secret']);
        if ($required) {
            return 'encrypted_configured_redacted';
        }

        foreach ($this->credential_fields() as $field) {
            if (!empty($row['has_' . $field])) {
                return 'partial_encrypted_configured_redacted';
            }
        }

        return 'schema_ready_no_values';
    }

    protected function private_presence_for_readiness($provider, $mode, $server_presence)
    {
        $server_presence = is_array($server_presence) ? $server_presence : array();
        $result = array(
            'storage_mode' => 'encrypted_db',
            'private_values_source' => 'encrypted_db',
            'private_db_storage' => $this->encrypted_credential_storage_status($provider, $mode),
            'local_override_loaded' => !empty($server_presence['local_override_loaded']),
        );

        $db_presence = $this->get_secret_credential_presence($provider, $mode);
        foreach ($this->credential_fields() as $field) {
            $status = 'missing';
            if (!empty($db_presence['ok']) && isset($db_presence['data'][$field . '_status'])) {
                $status = (string) $db_presence['data'][$field . '_status'];
            }

            if ($status !== 'configured_redacted' && isset($server_presence[$field]) && $server_presence[$field] === 'configured_redacted') {
                $status = 'configured_redacted';
                $result['private_values_source'] = 'server_config_fallback';
            }

            $result[$field] = $status;
        }

        return $result;
    }

    protected function encrypted_credential_storage_status_without_presence_lookup()
    {
        if (!$this->encryption_key_is_ready()) {
            return 'db_private_storage_blocked';
        }

        return $this->secret_schema_ready() ? 'key_ready_schema_ready_no_values' : 'key_ready_schema_pending';
    }

    protected function private_fields_present($data)
    {
        $present = array();
        foreach ($this->private_fields as $field) {
            if (array_key_exists($field, $data) && trim((string) $data[$field]) !== '') {
                $present[] = $field;
            }
        }

        return $present;
    }

    protected function filter_columns($data)
    {
        return $this->filter_columns_for_table($data, $this->table);
    }

    protected function filter_columns_for_table($data, $table)
    {
        $filtered = array();
        foreach ($data as $field => $value) {
            if ($this->db->field_exists($field, $table)) {
                $filtered[$field] = $value;
            }
        }

        return $filtered;
    }

    protected function normalize_provider($provider)
    {
        $provider = strtolower(trim((string) $provider));
        $provider = preg_replace('/[^a-z0-9_\\-]/', '', $provider);
        return strlen($provider) > 50 ? substr($provider, 0, 50) : $provider;
    }

    protected function normalize_mode($mode)
    {
        $mode = strtolower(trim((string) $mode));
        return in_array($mode, array('sandbox', 'live', 'manual'), true) ? $mode : '';
    }

    protected function clean_text($value, $max_length)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return strlen($value) > $max_length ? substr($value, 0, $max_length) : $value;
    }

    protected function clean_long_text($value, $max_length)
    {
        $value = trim(strip_tags((string) $value));
        $value = str_replace(array("\r\n", "\r"), "\n", $value);
        $value = preg_replace("/\n{4,}/", "\n\n\n", $value);

        if ($value === '') {
            return null;
        }

        return strlen($value) > $max_length ? substr($value, 0, $max_length) : $value;
    }

    protected function decimal_or_default($value, $default)
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return $default;
        }

        $value = round((float) $value, 2);
        if ($value < 0) {
            return $default;
        }

        return number_format($value, 2, '.', '');
    }

    protected function build_instapay_manual_readiness_summary($config)
    {
        $errors = array();

        if ($this->normalize_mode(isset($config['mode']) ? $config['mode'] : '') !== 'manual') {
            $errors[] = 'manual_mode_required';
        }

        if (strtoupper((string) $config['currency']) !== 'EGP') {
            $errors[] = 'currency_must_be_egp';
        }

        if (empty($config['instapay_target_address']) && empty($config['instapay_target_link'])) {
            $errors[] = 'instapay_target_address_or_link_required';
        }

        if (empty($config['instapay_instructions_ar'])) {
            $errors[] = 'instapay_instructions_ar_missing';
        }

        if (empty($config['instapay_instructions_en'])) {
            $errors[] = 'instapay_instructions_en_missing';
        }

        $max_upload_mb = isset($config['instapay_max_upload_mb']) ? (float) $config['instapay_max_upload_mb'] : 5.0;
        if ($max_upload_mb < 1.0 || $max_upload_mb > 20.0) {
            $errors[] = 'instapay_max_upload_mb_invalid';
        }

        if (!empty($config['enabled']) || !empty($config['network_enabled']) || !empty($config['checkout_cta_enabled'])) {
            $errors[] = 'payment_activation_flags_must_remain_disabled';
        }

        return array(
            'status' => empty($errors) ? 'manual_instapay_configured' : 'manual_instapay_not_ready',
            'ready_for_network' => false,
            'ready_for_cta' => false,
            'errors' => $errors,
            'storage_notes' => array('manual_instapay_config_only', 'no_online_gateway_credentials_required'),
        );
    }

    protected function normalize_instruction_language($language)
    {
        $language = strtolower(trim((string) $language));
        if (in_array($language, array('ar', 'arabic'), true)) {
            return 'arabic';
        }

        return 'english';
    }

    protected function instapay_allowed_mimes_from_row($row)
    {
        $value = isset($row['instapay_allowed_mimes']) ? (string) $row['instapay_allowed_mimes'] : '';
        $parts = array_filter(array_map('trim', explode(',', $value)));
        $allowed = array();
        foreach ($parts as $part) {
            if (in_array($part, array('image/jpeg', 'image/png', 'image/webp'), true)) {
                $allowed[] = $part;
            }
        }

        return !empty($allowed) ? array_values(array_unique($allowed)) : array('image/jpeg', 'image/png', 'image/webp');
    }

    protected function positive_int_or_null($value)
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    protected function valid_http_url($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, array('http', 'https'), true);
    }

    protected function presence_label($value)
    {
        return ($value === null || $value === '') ? 'missing' : 'configured_redacted';
    }

    protected function safe_server_private_presence($server_config_summary)
    {
        $summary = is_array($server_config_summary) ? $server_config_summary : array();
        $presence = isset($summary['hybrid_private_presence']) && is_array($summary['hybrid_private_presence'])
            ? $summary['hybrid_private_presence']
            : array();

        $allowed_statuses = array('missing', 'configured_redacted', 'server_config_required', 'db_private_storage_blocked');
        $result = array(
            'storage_mode' => 'hybrid',
            'private_values_source' => !empty($presence['private_values_source']) ? (string) $presence['private_values_source'] : 'server_config_required',
            'private_db_storage' => 'db_private_storage_blocked',
            'local_override_loaded' => !empty($presence['local_override_loaded']),
        );

        foreach (array('api_key', 'secret_key', 'public_key', 'hmac_secret') as $field) {
            $status = isset($presence[$field]) ? (string) $presence[$field] : 'server_config_required';
            $result[$field] = in_array($status, $allowed_statuses, true) ? $status : 'server_config_required';
        }

        return $result;
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
