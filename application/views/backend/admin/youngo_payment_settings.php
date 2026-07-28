<?php
$youngo_coupon_usage_report_requested = isset($_GET['youngo_coupon_usage_report']) && (string) $_GET['youngo_coupon_usage_report'] === '1';
if ($youngo_coupon_usage_report_requested) {
    if (file_exists(APPPATH . 'helpers/youngo_capability_helper.php')) {
        $this->load->helper('youngo_capability');
    }

    if (!function_exists('youngo_is_root_admin') || !youngo_is_root_admin((int) $this->session->userdata('user_id'))) {
        $this->session->set_flashdata('error_message', 'Access denied.');
        redirect(site_url('admin/dashboard'), 'refresh');
    }

    $this->load->model('Youngo_checkout_model', 'youngo_checkout_model');
    $usage_filter = strtolower(trim((string) $this->input->get('filter', true)));
    if (!in_array($usage_filter, array('all', 'zero_amount', 'course', 'subscription'), true)) {
        $usage_filter = 'all';
    }

    $page_title = 'Coupon Checkout Usage';
    $usage_counts = $this->youngo_checkout_model->count_admin_coupon_checkout_usage_by_filter();
    $usage_rows = $this->youngo_checkout_model->get_admin_coupon_checkout_usage($usage_filter, 200, 0);
    $allowed_filters = array('all', 'zero_amount', 'course', 'subscription');

    ob_start();
    include APPPATH . 'views/backend/admin/youngo_checkout_coupon_usage.php';
    $youngo_coupon_usage_report_html = ob_get_clean();
    $this->output->set_status_header(200);
    echo $youngo_coupon_usage_report_html;
    return;
}

$safe_config_summary = isset($safe_config_summary) && is_array($safe_config_summary) ? $safe_config_summary : array();
$provider_config_result = isset($provider_config_result) && is_array($provider_config_result) ? $provider_config_result : array();
$readiness_summary = isset($readiness_summary) && is_array($readiness_summary) ? $readiness_summary : array();
$hybrid_config_summary = isset($hybrid_config_summary) && is_array($hybrid_config_summary) ? $hybrid_config_summary : array();
$secret_credential_presence = isset($secret_credential_presence) && is_array($secret_credential_presence) ? $secret_credential_presence : array();
$recent_audit_logs = isset($recent_audit_logs) && is_array($recent_audit_logs) ? $recent_audit_logs : array();
$instapay_config_result = isset($instapay_config_result) && is_array($instapay_config_result) ? $instapay_config_result : array();
$instapay_safe_config_summary = isset($instapay_safe_config_summary) && is_array($instapay_safe_config_summary) ? $instapay_safe_config_summary : array();
$instapay_target_snapshot = isset($instapay_target_snapshot) && is_array($instapay_target_snapshot) ? $instapay_target_snapshot : array();
$instapay_recent_audit_logs = isset($instapay_recent_audit_logs) && is_array($instapay_recent_audit_logs) ? $instapay_recent_audit_logs : array();
$schema_ready = !empty($schema_ready);
$instapay_schema_ready = !empty($instapay_schema_ready);
$encryption_key_ready = !empty($encryption_key_ready);
$page_mode = isset($page_mode) ? (string) $page_mode : 'read_only_no_save';
$storage_mode_note = isset($storage_mode_note) ? (string) $storage_mode_note : '';

$config_data = !empty($safe_config_summary['ok']) && isset($safe_config_summary['data']) && is_array($safe_config_summary['data'])
    ? $safe_config_summary['data']
    : array();
$readiness_data = !empty($readiness_summary['ok']) && isset($readiness_summary['data']['readiness']) && is_array($readiness_summary['data']['readiness'])
    ? $readiness_summary['data']['readiness']
    : array();
$readiness_errors = isset($readiness_data['errors']) && is_array($readiness_data['errors']) ? $readiness_data['errors'] : array();
$config_values = !empty($provider_config_result['ok']) && isset($provider_config_result['data']['config']) && is_array($provider_config_result['data']['config'])
    ? $provider_config_result['data']['config']
    : array();
$instapay_config_values = !empty($instapay_config_result['ok']) && isset($instapay_config_result['data']['config']) && is_array($instapay_config_result['data']['config'])
    ? $instapay_config_result['data']['config']
    : array();
$instapay_config_data = !empty($instapay_safe_config_summary['ok']) && isset($instapay_safe_config_summary['data']) && is_array($instapay_safe_config_summary['data'])
    ? $instapay_safe_config_summary['data']
    : array();
$instapay_snapshot_data = !empty($instapay_target_snapshot['ok']) && isset($instapay_target_snapshot['data']['snapshot']) && is_array($instapay_target_snapshot['data']['snapshot'])
    ? $instapay_target_snapshot['data']['snapshot']
    : array();
$hybrid_data = !empty($hybrid_config_summary['ok']) && isset($hybrid_config_summary['data']) && is_array($hybrid_config_summary['data'])
    ? $hybrid_config_summary['data']
    : array();
$private_presence = isset($hybrid_data['private_presence']) && is_array($hybrid_data['private_presence'])
    ? $hybrid_data['private_presence']
    : array();
$credential_presence_data = !empty($secret_credential_presence['ok']) && isset($secret_credential_presence['data']) && is_array($secret_credential_presence['data'])
    ? $secret_credential_presence['data']
    : array();
$credential_presence = array(
    'api_key' => isset($credential_presence_data['api_key_status']) ? (string) $credential_presence_data['api_key_status'] : 'missing',
    'public_key' => isset($credential_presence_data['public_key_status']) ? (string) $credential_presence_data['public_key_status'] : 'missing',
    'secret_key' => isset($credential_presence_data['secret_key_status']) ? (string) $credential_presence_data['secret_key_status'] : 'missing',
    'hmac_secret' => isset($credential_presence_data['hmac_secret_status']) ? (string) $credential_presence_data['hmac_secret_status'] : 'missing',
);
$audit_logs = !empty($recent_audit_logs['ok']) && isset($recent_audit_logs['data']['logs']) && is_array($recent_audit_logs['data']['logs'])
    ? $recent_audit_logs['data']['logs']
    : array();
$instapay_audit_logs = !empty($instapay_recent_audit_logs['ok']) && isset($instapay_recent_audit_logs['data']['logs']) && is_array($instapay_recent_audit_logs['data']['logs'])
    ? $instapay_recent_audit_logs['data']['logs']
    : array();
$secret_schema_ready = !empty($secret_schema_ready);
$credential_storage_status = isset($credential_storage_status) && $credential_storage_status !== ''
    ? (string) $credential_storage_status
    : ($encryption_key_ready && $secret_schema_ready ? 'key_ready_schema_ready_no_values' : ($encryption_key_ready ? 'key_ready_schema_pending' : 'db_private_storage_blocked'));

if (!function_exists('youngo_payment_settings_badge')) {
    function youngo_payment_settings_badge($enabled, $label_true, $label_false)
    {
        $class = $enabled ? 'badge-success-lighten' : 'badge-secondary-lighten';
        $label = $enabled ? $label_true : $label_false;
        return '<span class="badge ' . $class . '">' . html_escape($label) . '</span>';
    }
}

if (!function_exists('youngo_payment_settings_text')) {
    function youngo_payment_settings_text($data, $key, $default = '-')
    {
        return isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null
            ? html_escape((string) $data[$key])
            : html_escape($default);
    }
}

if (!function_exists('youngo_payment_settings_value')) {
    function youngo_payment_settings_value($data, $key, $default = '')
    {
        return isset($data[$key]) && $data[$key] !== null
            ? html_escape((string) $data[$key])
            : html_escape($default);
    }
}

if (!function_exists('youngo_payment_settings_checked')) {
    function youngo_payment_settings_checked($data, $key)
    {
        return !empty($data[$key]) ? 'checked' : '';
    }
}

if (!function_exists('youngo_payment_settings_list')) {
    function youngo_payment_settings_list($items)
    {
        if (!is_array($items) || empty($items)) {
            return html_escape('-');
        }

        $safe = array();
        foreach ($items as $item) {
            $safe[] = html_escape((string) $item);
        }

        return implode(', ', $safe);
    }
}

if (!function_exists('youngo_payment_settings_secret_changes')) {
    function youngo_payment_settings_secret_changes($items)
    {
        if (!is_array($items) || empty($items)) {
            return html_escape('-');
        }

        $safe = array();
        foreach ($items as $field => $change) {
            if (!is_array($change)) {
                continue;
            }

            $before = isset($change['before']) ? (string) $change['before'] : 'missing';
            $after = isset($change['after']) ? (string) $change['after'] : 'missing';
            $safe[] = html_escape((string) $field . ': ' . $before . ' -> ' . $after);
        }

        return !empty($safe) ? implode(', ', $safe) : html_escape('-');
    }
}

if (!function_exists('youngo_payment_settings_status_badge')) {
    function youngo_payment_settings_status_badge($status)
    {
        $status = (string) $status;
        $classes = array(
            'complete' => 'badge-success-lighten',
            'missing' => 'badge-warning-lighten',
            'pending_server_config' => 'badge-info-lighten',
            'blocked_private_db_storage' => 'badge-danger-lighten',
            'disabled_until_approved' => 'badge-secondary-lighten',
            'db_private_storage_blocked' => 'badge-danger-lighten',
            'key_ready_schema_pending' => 'badge-info-lighten',
            'key_ready_schema_ready_no_values' => 'badge-info-lighten',
            'key_ready_schema_ready_partial_values' => 'badge-warning-lighten',
            'key_ready_schema_ready_configured_redacted' => 'badge-success-lighten',
        );

        $labels = array(
            'complete' => 'complete',
            'missing' => 'missing',
            'pending_server_config' => 'pending_server_config',
            'blocked_private_db_storage' => 'blocked_private_db_storage',
            'disabled_until_approved' => 'disabled_until_approved',
            'db_private_storage_blocked' => 'db_private_storage_blocked',
            'key_ready_schema_pending' => 'key_ready_schema_pending',
            'key_ready_schema_ready_no_values' => 'key_ready_schema_ready_no_values',
            'key_ready_schema_ready_partial_values' => 'key_ready_schema_ready_partial_values',
            'key_ready_schema_ready_configured_redacted' => 'key_ready_schema_ready_configured_redacted',
        );

        $class = isset($classes[$status]) ? $classes[$status] : 'badge-secondary-lighten';
        $label = isset($labels[$status]) ? $labels[$status] : 'missing';

        return '<span class="badge ' . $class . '">' . html_escape($label) . '</span>';
    }
}

if (!function_exists('youngo_payment_settings_non_private_status')) {
    function youngo_payment_settings_non_private_status($data, $key)
    {
        $data = is_array($data) ? $data : array();

        if ($key === 'mode') {
            return isset($data['mode']) && (string) $data['mode'] === 'sandbox' ? 'complete' : 'missing';
        }

        if ($key === 'currency') {
            return isset($data['currency']) && (string) $data['currency'] === 'EGP' ? 'complete' : 'missing';
        }

        if ($key === 'amount_multiplier') {
            return isset($data['amount_multiplier']) && (int) $data['amount_multiplier'] === 100 ? 'complete' : 'missing';
        }

        return isset($data[$key]) && (string) $data[$key] === 'configured_redacted' ? 'complete' : 'missing';
    }
}

if (!function_exists('youngo_payment_settings_private_status')) {
    function youngo_payment_settings_private_status($presence, $key)
    {
        $presence = is_array($presence) ? $presence : array();
        $status = isset($presence[$key]) ? (string) $presence[$key] : 'server_config_required';

        if ($status === 'configured_redacted') {
            return 'complete';
        }

        if ($status === 'db_private_storage_blocked') {
            return 'blocked_private_db_storage';
        }

        return 'pending_server_config';
    }
}

if (!function_exists('youngo_payment_settings_private_label')) {
    function youngo_payment_settings_private_label($field)
    {
        $labels = array(
            'api_key' => 'API key',
            'public_key' => 'Public key',
            'secret_key' => 'Secret key',
            'hmac_secret' => 'HMAC secret',
        );

        return isset($labels[$field]) ? $labels[$field] : ucwords(str_replace('_', ' ', (string) $field));
    }
}

if (!function_exists('youngo_payment_settings_presence_badge')) {
    function youngo_payment_settings_presence_badge($data, $key)
    {
        $data = is_array($data) ? $data : array();
        $configured = isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null && $data[$key] !== 'missing';
        return youngo_payment_settings_status_badge($configured ? 'complete' : 'missing');
    }
}

if (!function_exists('youngo_payment_settings_intention_private_fields')) {
    function youngo_payment_settings_intention_private_fields()
    {
        return array('public_key', 'secret_key', 'hmac_secret');
    }
}

if (!function_exists('youngo_payment_settings_visible_private_fields')) {
    function youngo_payment_settings_visible_private_fields()
    {
        return array('api_key', 'public_key', 'secret_key', 'hmac_secret');
    }
}

if (!function_exists('youngo_payment_settings_all_private_configured')) {
    function youngo_payment_settings_all_private_configured($presence)
    {
        foreach (youngo_payment_settings_intention_private_fields() as $field) {
            if (youngo_payment_settings_private_status($presence, $field) !== 'complete') {
                return false;
            }
        }

        return true;
    }
}
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title">
                    <i class="mdi mdi-credit-card-outline title_icon"></i> <?php echo html_escape($page_title); ?>
                </h4>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info" role="alert">
    This is a Paymob setup and readiness page. Non-private sandbox settings are saved in the YounGo DB config table; private Paymob values, including API key, public key, secret key, and HMAC secret, are saved encrypted when the encryption key and credential schema are ready. Saved credential values are never displayed after save. Sandbox tests are not ready until all required fields and explicit local gates pass. YounGo Paymob stays separate from legacy Academy gateway settings and uses its own checkout/order flow.
</div>

<?php if (!$encryption_key_ready): ?>
<div class="alert alert-warning" role="alert">
    Private Paymob DB storage is blocked while the CodeIgniter encryption_key is empty. A future key may be loaded from ignored application/config/youngo_security.local.php, but private Paymob values are currently configured through ignored server config only until encrypted credential storage is separately approved.
</div>
<?php endif; ?>

<?php if ($encryption_key_ready && $secret_schema_ready): ?>
<div class="alert alert-info" role="alert">
    Encrypted Paymob credential storage is key-ready and schema-ready. Credential fields can be saved encrypted, but payment execution, network calls, sandbox testing, and checkout CTAs remain disabled.
</div>
<?php endif; ?>

<?php if (!$schema_ready): ?>
<div class="alert alert-danger" role="alert">
    YounGo payment provider config schema is not ready. Configuration details can only fall back to safe defaults.
</div>
<?php endif; ?>

<?php if (!$instapay_schema_ready): ?>
<div class="alert alert-warning" role="alert">
    Manual Instapay config fields are not present yet. Instapay target settings remain unavailable until the additive config schema is applied.
</div>
<?php endif; ?>

<div class="row" data-youngo-paymob-form-ui="true">
    <div class="col-xl-7" style="padding: 0;">
        <form class="required-form" action="<?php echo site_url('admin/youngo/payment-settings'); ?>" method="post" data-youngo-paymob-non-private-form="true">
            <input type="hidden" name="youngo_payment_settings_action" value="save_non_private">
            <div class="col-md-12">
                <div class="card" data-youngo-paymob-section="basic-setup">
                    <div class="card-body">
                        <h4 class="header-title"><p><?php echo get_phrase('paymob_basic_setup'); ?></p></h4>
                        <p class="text-muted"><?php echo get_phrase('enter_the_non-private_paymob_sandbox_values_the_client_obtains_from_the_paymob_dashboard._the_current_youngo_payment_phase_is_sandbox_and_egp_only.'); ?></p>

                        <div class="form-group">
                            <label for="mode"><?php echo get_phrase('mode'); ?></label>
                            <input type="text" class="form-control" id="mode" name="mode" value="sandbox" readonly <?php echo !$schema_ready ? 'disabled' : ''; ?>>
                            <small class="form-text text-muted"><?php echo get_phrase('live_mode_is_blocked_until_production_payment_approval.'); ?></small>
                        </div>

                        <div class="form-group">
                            <label for="currency"><?php echo get_phrase('currency'); ?></label>
                            <input type="text" class="form-control" id="currency" name="currency" value="<?php echo get_phrase('egp'); ?>" readonly <?php echo !$schema_ready ? 'disabled' : ''; ?>>
                            <small class="form-text text-muted"><?php echo get_phrase('youngo_paymob_is_egp-only_in_the_current_sandbox_phase.'); ?></small>
                        </div>

                        <div class="form-group">
                            <label for="amount_multiplier"><?php echo get_phrase('amount_multiplier'); ?></label>
                            <input type="number" class="form-control" id="amount_multiplier" name="amount_multiplier" value="100" readonly <?php echo !$schema_ready ? 'disabled' : ''; ?>>
                            <small class="form-text text-muted"><?php echo get_phrase('egp_amounts_are_sent_to_paymob_in_the_smallest_currency_unit.'); ?></small>
                        </div>

                        <div class="form-group">
                            <label for="card_integration_id_egp"><?php echo get_phrase('card_integration_id_egp'); ?></label>
                            <input type="text" class="form-control" id="card_integration_id_egp" name="card_integration_id_egp" maxlength="100" pattern="[0-9]*" value="<?php echo youngo_payment_settings_value($config_values, 'card_integration_id_egp'); ?>" <?php echo !$schema_ready ? 'disabled' : ''; ?>>
                            <small class="form-text text-muted">Client/admin gets this numeric sandbox card integration ID from Paymob.</small>
                        </div>

                        <div class="form-group">
                            <label for="wallet_integration_id_egp"><?php echo get_phrase('mobile_wallet_integration_id_egp'); ?></label>
                            <input type="text" class="form-control" id="wallet_integration_id_egp" name="wallet_integration_id_egp" maxlength="100" pattern="[0-9]*" value="<?php echo youngo_payment_settings_value($config_values, 'wallet_integration_id_egp'); ?>" <?php echo !$schema_ready ? 'disabled' : ''; ?>>
                            <small class="form-text text-muted">Client/admin gets this numeric sandbox mobile wallet integration ID from Paymob.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card" data-youngo-paymob-section="urls">
                    <div class="card-body">
                        <h4 class="header-title"><p><?php echo get_phrase('urls'); ?></p></h4>
                        <p class="text-muted">Use sandbox Paymob URLs and YounGo environment URLs only. The return URL is UX-only; the payment notification URL is the webhook source of truth.</p>

                        <div class="form-group">
                            <label for="api_base_url"><?php echo get_phrase('api_base_url'); ?></label>
                            <input type="url" class="form-control" id="api_base_url" name="api_base_url" maxlength="255" value="<?php echo youngo_payment_settings_value($config_values, 'api_base_url'); ?>" placeholder="https://..." <?php echo !$schema_ready ? 'disabled' : ''; ?>>
                            <small class="form-text text-muted">Sandbox Paymob API base URL from the current Paymob dashboard/docs.</small>
                        </div>

                        <div class="form-group">
                            <label for="checkout_base_url"><?php echo get_phrase('checkout_base_url'); ?></label>
                            <input type="url" class="form-control" id="checkout_base_url" name="checkout_base_url" maxlength="255" value="<?php echo youngo_payment_settings_value($config_values, 'checkout_base_url'); ?>" placeholder="https://..." <?php echo !$schema_ready ? 'disabled' : ''; ?>>
                            <small class="form-text text-muted">Sandbox Unified Checkout base URL from the current Paymob dashboard/docs.</small>
                        </div>

                        <div class="form-group">
                            <label for="return_url"><?php echo get_phrase('return_url'); ?></label>
                            <input type="url" class="form-control" id="return_url" name="return_url" maxlength="500" value="<?php echo youngo_payment_settings_value($config_values, 'return_url'); ?>" placeholder="http://school.local/youngo/checkout/return/{order_reference}" <?php echo !$schema_ready ? 'disabled' : ''; ?>>
                            <small class="form-text text-muted">Return URL is learner UX only and must not mark orders paid or issue entitlement.</small>
                        </div>

                        <div class="form-group">
                            <label for="notification_url"><?php echo get_phrase('payment_notification_url'); ?></label>
                            <input type="url" class="form-control" id="notification_url" name="notification_url" maxlength="500" value="<?php echo youngo_payment_settings_value($config_values, 'notification_url'); ?>" placeholder="https://.../payment/paymob/webhook" <?php echo !$schema_ready ? 'disabled' : ''; ?>>
                            <small class="form-text text-muted">Paymob payload field: notification_url. This is the YounGo webhook URL; local config webhook_url is an alias for the same endpoint. Webhook/HMAC verification remains the source of truth.</small>
                        </div>

                        <button type="submit" class="btn btn-primary" <?php echo !$schema_ready ? 'disabled' : ''; ?>><?php echo get_phrase('save_non-private_settings'); ?></button>
                    </div>
                </div>
            </div>
        </form>

        <div class="col-md-12">
            <div class="card" data-youngo-instapay-section="manual-config">
                <div class="card-body">
                    <h4 class="header-title"><p><?php echo get_phrase('manual_instapay_setup'); ?></p></h4>
                    <p class="text-muted">Configure the manual Instapay target details that can be snapshotted into future checkout submissions. This does not create upload UI, admin review UI, payment approval, access issuance, Paymob redirects, card payments, or wallet payments.</p>

                    <form class="required-form" action="<?php echo site_url('admin/youngo/payment-settings'); ?>" method="post" data-youngo-instapay-manual-config-form="true">
                        <input type="hidden" name="youngo_payment_settings_action" value="save_instapay_manual">

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="instapay_enabled_for_checkout" name="instapay_enabled_for_checkout" value="1" <?php echo youngo_payment_settings_checked($instapay_config_values, 'instapay_enabled_for_checkout'); ?> <?php echo !$instapay_schema_ready ? 'disabled' : ''; ?>>
                                <label class="custom-control-label" for="instapay_enabled_for_checkout"><?php echo get_phrase('enable_instapay_manual_checkout'); ?></label>
                            </div>
                            <small class="form-text text-muted">The config flag is stored here for a later checkout phase. No upload or payment flow is exposed by this page.</small>
                        </div>

                        <div class="form-group">
                            <label for="instapay_target_label"><?php echo get_phrase('target_label'); ?></label>
                            <input type="text" class="form-control" id="instapay_target_label" name="instapay_target_label" maxlength="255" value="<?php echo youngo_payment_settings_value($instapay_config_values, 'instapay_target_label'); ?>" <?php echo !$instapay_schema_ready ? 'disabled' : ''; ?>>
                        </div>

                        <div class="form-group">
                            <label for="instapay_target_address"><?php echo get_phrase('instapay_address'); ?></label>
                            <input type="text" class="form-control" id="instapay_target_address" name="instapay_target_address" maxlength="255" value="<?php echo youngo_payment_settings_value($instapay_config_values, 'instapay_target_address'); ?>" <?php echo !$instapay_schema_ready ? 'disabled' : ''; ?>>
                        </div>

                        <div class="form-group">
                            <label for="instapay_target_link"><?php echo get_phrase('instapay_link'); ?></label>
                            <input type="url" class="form-control" id="instapay_target_link" name="instapay_target_link" maxlength="500" placeholder="https://..." value="<?php echo youngo_payment_settings_value($instapay_config_values, 'instapay_target_link'); ?>" <?php echo !$instapay_schema_ready ? 'disabled' : ''; ?>>
                        </div>

                        <div class="form-group">
                            <label for="instapay_instructions_ar"><?php echo get_phrase('arabic_instructions'); ?></label>
                            <textarea class="form-control" id="instapay_instructions_ar" name="instapay_instructions_ar" rows="5" maxlength="5000" dir="rtl" <?php echo !$instapay_schema_ready ? 'disabled' : ''; ?>><?php echo youngo_payment_settings_value($instapay_config_values, 'instapay_instructions_ar'); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="instapay_instructions_en"><?php echo get_phrase('english_instructions'); ?></label>
                            <textarea class="form-control" id="instapay_instructions_en" name="instapay_instructions_en" rows="5" maxlength="5000" <?php echo !$instapay_schema_ready ? 'disabled' : ''; ?>><?php echo youngo_payment_settings_value($instapay_config_values, 'instapay_instructions_en'); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="instapay_max_upload_mb"><?php echo get_phrase('max_screenshot_size_mb'); ?></label>
                            <input type="number" class="form-control" id="instapay_max_upload_mb" name="instapay_max_upload_mb" min="1" max="20" step="0.5" value="<?php echo youngo_payment_settings_value($instapay_config_values, 'instapay_max_upload_mb', '5.00'); ?>" <?php echo !$instapay_schema_ready ? 'disabled' : ''; ?>>
                            <small class="form-text text-muted">Allowed screenshot MIME types are fixed for now: image/jpeg, image/png, image/webp.</small>
                        </div>

                        <button type="submit" class="btn btn-primary" <?php echo !$instapay_schema_ready ? 'disabled' : ''; ?>><?php echo get_phrase('save_manual_instapay_settings'); ?></button>
                    </form>

                    <div class="alert alert-secondary mt-3 mb-0" role="alert">
                        Manual Instapay is external payment evidence only. Later admin approval is required before access can be issued.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card" data-youngo-paymob-section="private-credentials">
                <div class="card-body">
                    <h4 class="header-title"><p><?php echo get_phrase('private_credentials'); ?></p></h4>
                    <p class="text-muted">The Paymob dashboard currently shows API key, Public key, Secret key, and HMAC. Values entered here are encrypted before storage, never displayed after save, and are not sent to Paymob in this phase.</p>
                    <p class="text-muted">The current Intention API / Unified Checkout path uses Secret key for the server request, Public key with the returned client secret for checkout rendering, and HMAC secret for webhook verification. API key is represented for account reconciliation and older auth-token flows, but it is not a current Intention readiness gate.</p>

                    <form class="required-form" action="<?php echo site_url('admin/youngo/payment-settings'); ?>" method="post" autocomplete="off" data-youngo-paymob-secret-form="true">
                        <input type="hidden" name="youngo_payment_settings_action" value="save_credentials">
                        <?php foreach (youngo_payment_settings_visible_private_fields() as $field_name): ?>
                            <?php
                                $field_status = isset($credential_presence[$field_name]) ? (string) $credential_presence[$field_name] : 'missing';
                                $field_ready = $encryption_key_ready && $secret_schema_ready;
                                $field_id = 'paymob_credential_' . $field_name;
                            ?>
                            <div class="form-group">
                                <label for="<?php echo html_escape($field_id); ?>">
                                    <?php echo html_escape(youngo_payment_settings_private_label($field_name)); ?>
                                    <code><?php echo html_escape($field_name); ?></code>
                                    <?php echo youngo_payment_settings_status_badge($field_status === 'configured_redacted' ? 'complete' : 'missing'); ?>
                                </label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="<?php echo html_escape($field_id); ?>"
                                    <?php echo $field_ready ? 'name="credentials[' . html_escape($field_name) . ']"' : 'disabled readonly'; ?>
                                    autocomplete="new-password"
                                    placeholder="<?php echo $field_status === 'configured_redacted' ? get_phrase('configured_-_leave_blank_to_keep_current_value') : get_phrase('missing_-_enter_value_to_configure'); ?>"
                                    data-youngo-private-field="<?php echo html_escape($field_name); ?>"
                                    data-youngo-private-input="encrypted-db"
                                    data-youngo-private-status="<?php echo html_escape($field_status); ?>">
                                <small class="form-text text-muted">Leave blank to keep the current encrypted value. Enter a new value to replace it. Values are encrypted and never displayed.</small>
                            </div>
                        <?php endforeach; ?>

                        <?php if (!$encryption_key_ready || !$secret_schema_ready): ?>
                            <div class="alert alert-warning" role="alert">
                                Credential save is blocked until the encryption key and encrypted credential schema are both ready.
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary" <?php echo (!$encryption_key_ready || !$secret_schema_ready) ? 'disabled' : ''; ?> data-youngo-save-paymob-credentials="true"><?php echo get_phrase('save_paymob_credentials'); ?></button>
                    </form>

                    <div class="alert alert-secondary mb-0" role="alert">
                        Credential save does not enable payments, network testing, checkout routes, checkout CTAs, or Paymob requests.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card" id="youngo-payment-audit-log" data-youngo-paymob-section="audit-log">
                <div class="card-body">
                    <h4 class="header-title"><p><?php echo get_phrase('recent_configuration_audit'); ?></p></h4>
                    <div class="table-responsive-sm">
                        <table class="table table-sm table-centered mb-0">
                            <thead>
                                <tr>
                                    <th><?php echo get_phrase('date'); ?></th>
                                    <th><?php echo get_phrase('actor'); ?></th>
                                    <th><?php echo get_phrase('action'); ?></th>
                                    <th><?php echo get_phrase('changed_fields'); ?></th>
                                    <th><?php echo get_phrase('secret_presence'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($audit_logs)): ?>
                                    <?php foreach ($audit_logs as $audit_log): ?>
                                        <tr>
                                            <td><?php echo !empty($audit_log['created_at']) ? date('Y-m-d H:i:s', (int) $audit_log['created_at']) : '-'; ?></td>
                                            <td><?php echo isset($audit_log['actor_user_id']) && $audit_log['actor_user_id'] ? html_escape((string) $audit_log['actor_user_id']) : '-'; ?></td>
                                            <td><?php echo isset($audit_log['action']) ? html_escape((string) $audit_log['action']) : '-'; ?></td>
                                            <td><?php echo youngo_payment_settings_list(isset($audit_log['changed_fields']) ? $audit_log['changed_fields'] : array()); ?></td>
                                            <td><?php echo youngo_payment_settings_secret_changes(isset($audit_log['secret_presence_changes']) ? $audit_log['secret_presence_changes'] : array()); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-muted">No audit entries yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted mt-3 mb-0"><?php echo get_phrase('audit_entries_show_redacted_summaries_only._secret_values_are_never_displayed.'); ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card" id="youngo-instapay-audit-log" data-youngo-instapay-section="audit-log">
                <div class="card-body">
                    <h4 class="header-title"><p><?php echo get_phrase('recent_manual_instapay_audit'); ?></p></h4>
                    <div class="table-responsive-sm">
                        <table class="table table-sm table-centered mb-0">
                            <thead>
                                <tr>
                                    <th><?php echo get_phrase('date'); ?></th>
                                    <th><?php echo get_phrase('actor'); ?></th>
                                    <th><?php echo get_phrase('action'); ?></th>
                                    <th><?php echo get_phrase('changed_fields'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($instapay_audit_logs)): ?>
                                    <?php foreach ($instapay_audit_logs as $audit_log): ?>
                                        <tr>
                                            <td><?php echo !empty($audit_log['created_at']) ? date('Y-m-d H:i:s', (int) $audit_log['created_at']) : '-'; ?></td>
                                            <td><?php echo isset($audit_log['actor_user_id']) && $audit_log['actor_user_id'] ? html_escape((string) $audit_log['actor_user_id']) : '-'; ?></td>
                                            <td><?php echo isset($audit_log['action']) ? html_escape((string) $audit_log['action']) : '-'; ?></td>
                                            <td><?php echo youngo_payment_settings_list(isset($audit_log['changed_fields']) ? $audit_log['changed_fields'] : array()); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-muted">No Manual Instapay audit entries yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted mt-3 mb-0"><?php echo get_phrase('audit_entries_store_redacted_presence_summaries_for_target_fields_and_do_not_include_payment_evidence_or_credentials.'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card" data-youngo-paymob-section="readiness-status">
            <div class="card-body">
                <h4 class="header-title"><p><?php echo get_phrase('readiness_/_status'); ?></p></h4>
                <table class="table table-sm table-centered mb-0">
                    <tbody>
                        <tr><th><?php echo get_phrase('provider'); ?></th><td>Paymob</td></tr>
                        <tr><th><?php echo get_phrase('mode'); ?></th><td><?php echo youngo_payment_settings_text($config_data, 'mode', 'sandbox'); ?></td></tr>
                        <tr><th><?php echo get_phrase('currency'); ?></th><td><?php echo youngo_payment_settings_text($config_data, 'currency', 'EGP'); ?></td></tr>
                        <tr><th><?php echo get_phrase('amount_multiplier'); ?></th><td><?php echo youngo_payment_settings_text($config_data, 'amount_multiplier', '100'); ?></td></tr>
                        <tr><th><?php echo get_phrase('card_integration_id'); ?></th><td><?php echo youngo_payment_settings_status_badge(youngo_payment_settings_non_private_status($config_data, 'card_integration_id_egp')); ?></td></tr>
                        <tr><th><?php echo get_phrase('mobile_wallet_integration_id'); ?></th><td><?php echo youngo_payment_settings_status_badge(youngo_payment_settings_non_private_status($config_data, 'wallet_integration_id_egp')); ?></td></tr>
                        <tr><th><?php echo get_phrase('dashboard_non-private_config'); ?></th><td><?php echo youngo_payment_settings_text($hybrid_data, 'non_private_db_config', 'missing'); ?></td></tr>
                        <tr><th><?php echo get_phrase('encrypted_credential_presence'); ?></th><td><?php echo youngo_payment_settings_status_badge(youngo_payment_settings_all_private_configured($credential_presence) ? 'complete' : 'missing'); ?></td></tr>
                        <tr><th><?php echo get_phrase('private_server-config_fallback'); ?></th><td><?php echo youngo_payment_settings_status_badge(youngo_payment_settings_all_private_configured($private_presence) ? 'complete' : 'pending_server_config'); ?></td></tr>
                        <tr><th><?php echo get_phrase('encryption_key_configured'); ?></th><td><?php echo youngo_payment_settings_badge($encryption_key_ready, 'Yes', 'No'); ?></td></tr>
                        <tr><th><?php echo get_phrase('encrypted_credential_schema'); ?></th><td><?php echo youngo_payment_settings_badge($secret_schema_ready, 'Ready', 'Missing'); ?></td></tr>
                        <tr><th><?php echo get_phrase('encrypted_db_credential_storage'); ?></th><td><?php echo youngo_payment_settings_status_badge($credential_storage_status); ?></td></tr>
                        <tr><th><?php echo get_phrase('private_db_storage'); ?></th><td><?php echo youngo_payment_settings_status_badge(isset($hybrid_data['private_db_storage']) ? $hybrid_data['private_db_storage'] : $credential_storage_status); ?></td></tr>
                        <tr><th><?php echo get_phrase('sandbox_test_availability'); ?></th><td><?php echo youngo_payment_settings_status_badge('disabled_until_approved'); ?></td></tr>
                        <tr><th><?php echo get_phrase('page_mode'); ?></th><td><?php echo html_escape($page_mode); ?></td></tr>
                    </tbody>
                </table>

                <p class="text-muted mt-3 mb-1">Status: <strong><?php echo youngo_payment_settings_text($readiness_data, 'status', 'not_configured'); ?></strong></p>
                <p class="text-muted mb-0">Private values are represented only as missing, configured_redacted, server_config_required, or db_private_storage_blocked.</p>
            </div>
        </div>

        <div class="card" data-youngo-paymob-section="gates">
            <div class="card-body">
                <h4 class="header-title"><p><?php echo get_phrase('payment/network/cta_gates'); ?></p></h4>
                <table class="table table-sm table-centered mb-0">
                    <tbody>
                        <tr><th><?php echo get_phrase('payment_enabled'); ?></th><td><?php echo youngo_payment_settings_badge(!empty($config_data['enabled']), 'Enabled', 'Disabled'); ?></td></tr>
                        <tr><th><?php echo get_phrase('network_enabled'); ?></th><td><?php echo youngo_payment_settings_badge(!empty($config_data['network_enabled']), 'Enabled', 'Disabled'); ?></td></tr>
                        <tr><th><?php echo get_phrase('sandbox_network_testing'); ?></th><td><?php echo youngo_payment_settings_badge(!empty($config_data['sandbox_network_testing_enabled']), 'Enabled', 'Disabled'); ?></td></tr>
                        <tr><th><?php echo get_phrase('webhook_testing'); ?></th><td><?php echo youngo_payment_settings_badge(!empty($config_data['webhook_testing_enabled']), 'Enabled', 'Disabled'); ?></td></tr>
                        <tr><th><?php echo get_phrase('checkout_routes'); ?></th><td><?php echo youngo_payment_settings_badge(!empty($config_data['checkout_routes_enabled']), 'Enabled', 'Disabled'); ?></td></tr>
                        <tr><th><?php echo get_phrase('local_checkout_testing'); ?></th><td><?php echo youngo_payment_settings_badge(!empty($config_data['checkout_local_testing_enabled']), 'Enabled', 'Disabled'); ?></td></tr>
                        <tr><th><?php echo get_phrase('checkout_cta'); ?></th><td><?php echo youngo_payment_settings_badge(!empty($config_data['checkout_cta_enabled']), 'Enabled', 'Disabled'); ?></td></tr>
                        <tr><th><?php echo get_phrase('live_mode_allowed'); ?></th><td><?php echo youngo_payment_settings_badge(!empty($config_data['live_mode_allowed']), 'Allowed', 'Blocked'); ?></td></tr>
                        <tr><th><?php echo get_phrase('transaction_inquiry'); ?></th><td><?php echo youngo_payment_settings_badge(!empty($config_data['transaction_inquiry_enabled']), 'Enabled', 'Disabled'); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" data-youngo-instapay-section="manual-status">
            <div class="card-body">
                <h4 class="header-title"><p><?php echo get_phrase('manual_instapay_config'); ?></p></h4>
                <table class="table table-sm table-centered mb-0">
                    <tbody>
                        <tr><th><?php echo get_phrase('provider'); ?></th><td>instapay_manual</td></tr>
                        <tr><th><?php echo get_phrase('mode'); ?></th><td>manual</td></tr>
                        <tr><th><?php echo get_phrase('checkout_flag'); ?></th><td><?php echo youngo_payment_settings_badge(!empty($instapay_snapshot_data['enabled']), 'Stored enabled', 'Stored disabled'); ?></td></tr>
                        <tr><th><?php echo get_phrase('target_label'); ?></th><td><?php echo youngo_payment_settings_presence_badge($instapay_config_data, 'instapay_target_label'); ?></td></tr>
                        <tr><th><?php echo get_phrase('target_address'); ?></th><td><?php echo youngo_payment_settings_presence_badge($instapay_config_data, 'instapay_target_address'); ?></td></tr>
                        <tr><th><?php echo get_phrase('target_link'); ?></th><td><?php echo youngo_payment_settings_presence_badge($instapay_config_data, 'instapay_target_link'); ?></td></tr>
                        <tr><th><?php echo get_phrase('arabic_instructions'); ?></th><td><?php echo youngo_payment_settings_presence_badge($instapay_config_data, 'instapay_instructions_ar'); ?></td></tr>
                        <tr><th><?php echo get_phrase('english_instructions'); ?></th><td><?php echo youngo_payment_settings_presence_badge($instapay_config_data, 'instapay_instructions_en'); ?></td></tr>
                        <tr><th><?php echo get_phrase('max_upload_mb'); ?></th><td><?php echo youngo_payment_settings_text($instapay_config_data, 'instapay_max_upload_mb', '5.00'); ?></td></tr>
                        <tr><th><?php echo get_phrase('allowed_mime_types'); ?></th><td><?php echo youngo_payment_settings_text($instapay_config_data, 'instapay_allowed_mimes', 'image/jpeg,image/png,image/webp'); ?></td></tr>
                    </tbody>
                </table>
                <p class="text-muted mt-3 mb-0"><?php echo get_phrase('checkout_display,_screenshot_upload,_admin_review,_approval,_and_access_issuance_are_deferred_to_later_phases.'); ?></p>
            </div>
        </div>

        <div class="card" data-youngo-paymob-setup-checklist="true" data-youngo-paymob-section="setup-checklist">
            <div class="card-body">
                <h4 class="header-title"><p><?php echo get_phrase('paymob_sandbox_setup_checklist'); ?></p></h4>
                <p class="text-muted">Use this checklist before sandbox testing. It lists the Paymob/dashboard setup work required for the owner or client without displaying private values or enabling payment execution.</p>
                <div class="table-responsive-sm">
                    <table class="table table-sm table-centered mb-0">
                        <tbody>
                            <tr><td>Create/activate Paymob sandbox account</td><td><?php echo youngo_payment_settings_status_badge('disabled_until_approved'); ?></td></tr>
                            <tr><td>Create/confirm EGP card integration</td><td><?php echo youngo_payment_settings_status_badge(youngo_payment_settings_non_private_status($config_data, 'card_integration_id_egp')); ?></td></tr>
                            <tr><td>Create/confirm EGP mobile wallet integration</td><td><?php echo youngo_payment_settings_status_badge(youngo_payment_settings_non_private_status($config_data, 'wallet_integration_id_egp')); ?></td></tr>
                            <tr><td>Prepare return URL</td><td><?php echo youngo_payment_settings_status_badge(youngo_payment_settings_non_private_status($config_data, 'return_url')); ?></td></tr>
                            <tr><td>Prepare payment notification URL</td><td><?php echo youngo_payment_settings_status_badge(youngo_payment_settings_non_private_status($config_data, 'notification_url')); ?></td></tr>
                            <tr><td>Enter non-private values in this dashboard page</td><td><?php echo youngo_payment_settings_status_badge(!empty($config_data['exists']) ? 'complete' : 'missing'); ?></td></tr>
                            <tr><td>Save encrypted Paymob credentials</td><td><?php echo youngo_payment_settings_status_badge(youngo_payment_settings_all_private_configured($credential_presence) ? 'complete' : 'missing'); ?></td></tr>
                            <tr><td>Sandbox test remains disabled until readiness is complete</td><td><?php echo youngo_payment_settings_status_badge('disabled_until_approved'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card" data-youngo-paymob-required-fields="true" data-youngo-paymob-section="required-fields">
            <div class="card-body">
                <h4 class="header-title"><p><?php echo get_phrase('required_fields'); ?></p></h4>
                <p class="text-muted mb-2"><?php echo get_phrase('dashboard_non-private_fields'); ?></p>
                <?php foreach (array('mode', 'currency', 'amount_multiplier', 'card_integration_id_egp', 'wallet_integration_id_egp', 'api_base_url', 'checkout_base_url', 'return_url', 'notification_url') as $field_name): ?>
                    <div class="d-flex justify-content-between mb-1">
                        <code><?php echo html_escape($field_name); ?></code>
                        <?php echo youngo_payment_settings_status_badge(youngo_payment_settings_non_private_status($config_data, $field_name)); ?>
                    </div>
                <?php endforeach; ?>

                <p class="text-muted mt-3 mb-2"><?php echo get_phrase('private_encrypted_credential_fields'); ?></p>
                <?php foreach (youngo_payment_settings_visible_private_fields() as $field_name): ?>
                    <div class="d-flex justify-content-between mb-1">
                        <span><code><?php echo html_escape($field_name); ?></code><?php echo $field_name === 'api_key' ? ' <small class="text-muted">(not required for current Intention path)</small>' : ''; ?></span>
                        <?php echo youngo_payment_settings_status_badge(isset($credential_presence[$field_name]) && $credential_presence[$field_name] === 'configured_redacted' ? 'complete' : 'missing'); ?>
                    </div>
                <?php endforeach; ?>
                <p class="text-muted mt-3 mb-0">API key, public key, secret key, and HMAC secret are saved encrypted when entered. API key is tracked for dashboard-field reconciliation and old/auth-token compatibility only unless a later Paymob phase requires it.</p>
            </div>
        </div>

        <div class="card" data-youngo-paymob-section="url-presence">
            <div class="card-body">
                <h4 class="header-title"><p><?php echo get_phrase('url_presence'); ?></p></h4>
                <table class="table table-sm table-centered mb-0">
                    <tbody>
                        <tr><th><?php echo get_phrase('api_base_url'); ?></th><td><?php echo youngo_payment_settings_text($config_data, 'api_base_url', 'missing'); ?></td></tr>
                        <tr><th><?php echo get_phrase('checkout_base_url'); ?></th><td><?php echo youngo_payment_settings_text($config_data, 'checkout_base_url', 'missing'); ?></td></tr>
                        <tr><th><?php echo get_phrase('return_url'); ?></th><td><?php echo youngo_payment_settings_text($config_data, 'return_url', 'missing'); ?></td></tr>
                        <tr><th><?php echo get_phrase('payment_notification_url'); ?></th><td><?php echo youngo_payment_settings_text($config_data, 'notification_url', 'missing'); ?></td></tr>
                    </tbody>
                </table>
                <p class="text-muted mt-3 mb-0">notification_url is the canonical dashboard field for Paymob webhook delivery. The tracked config name webhook_url is an alias for the same endpoint.</p>
            </div>
        </div>

        <div class="card" data-youngo-paymob-next-steps="true" data-youngo-paymob-section="next-steps">
            <div class="card-body">
                <h4 class="header-title"><p><?php echo get_phrase('next_steps'); ?></p></h4>
                <ul class="mb-3">
                    <li>Save non-private settings.</li>
                    <li><a href="#youngo-payment-audit-log"><?php echo get_phrase('review_audit_log'); ?></a>.</li>
                    <li><a href="<?php echo site_url('admin/youngo/payment-settings/test'); ?>" data-youngo-paymob-test-center-link="true">Open Paymob Test Center</a>.</li>
                    <li>Sandbox test disabled until readiness and explicit approval.</li>
                </ul>
                <a href="<?php echo site_url('admin/youngo/payment-settings/test'); ?>" class="btn btn-sm btn-outline-primary mb-2" data-youngo-paymob-test-center-link="true">Open Test Center</a>
                <button type="button" class="btn btn-sm btn-secondary" disabled aria-disabled="true" data-youngo-sandbox-test-disabled="true"><?php echo get_phrase('sandbox_test_not_available_yet'); ?></button>
                <p class="text-muted mt-3 mb-0"><?php echo get_phrase('the_sandbox_test_control_is_intentionally_disabled_and_non-functional_until_a_later_approved_execution_phase.'); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card" data-youngo-paymob-section="readiness-errors">
            <div class="card-body">
                <h4 class="header-title"><p><?php echo get_phrase('readiness_details'); ?></p></h4>
                <p class="mb-2">
                    Network readiness:
                    <?php echo youngo_payment_settings_badge(!empty($readiness_data['ready_for_network']), 'Ready', 'Blocked'); ?>
                    Checkout CTA readiness:
                    <?php echo youngo_payment_settings_badge(!empty($readiness_data['ready_for_cta']), 'Ready', 'Blocked'); ?>
                </p>
                <?php if (!empty($readiness_errors)): ?>
                    <ul class="mb-0">
                        <?php foreach ($readiness_errors as $error): ?>
                            <li><?php echo html_escape($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No readiness errors are currently reported, but sandbox execution still requires explicit local gates. Production payment activation and production CTA exposure remain blocked.</p>
                <?php endif; ?>
                <?php if (!empty($readiness_data['storage_notes']) && is_array($readiness_data['storage_notes'])): ?>
                    <p class="text-muted mt-3 mb-1">Storage notes:</p>
                    <ul class="mb-0">
                        <?php foreach ($readiness_data['storage_notes'] as $storage_note): ?>
                            <li><?php echo html_escape((string) $storage_note); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <p class="text-muted mt-3 mb-0"><?php echo html_escape($storage_mode_note); ?></p>
            </div>
        </div>
    </div>
</div>
