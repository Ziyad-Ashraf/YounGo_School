<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!class_exists('Youngo_paymob_config') && is_file(APPPATH . 'libraries/Youngo_paymob_config.php')) {
    require_once APPPATH . 'libraries/Youngo_paymob_config.php';
}

if (!class_exists('Youngo_paymob_adapter') && is_file(APPPATH . 'libraries/Youngo_paymob_adapter.php')) {
    require_once APPPATH . 'libraries/Youngo_paymob_adapter.php';
}

class Youngo_payment_settings extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        date_default_timezone_set(get_settings('timezone'));

        $this->load->database();
        $this->load->library('session');
        if (file_exists(APPPATH . 'helpers/youngo_capability_helper.php')) {
            $this->load->helper('youngo_capability');
        }
        $this->load->model('Youngo_payment_config_model', 'youngo_payment_config_model');
        $this->load->model('Youngo_payment_config_audit_model', 'youngo_payment_config_audit_model');

        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');

        $this->user_model->check_session_data('admin');
        $this->require_root_admin();
    }

    public function index()
    {
        if ($this->request_method() === 'POST') {
            $action = $this->input->post('youngo_payment_settings_action', true);
            if ($action === 'save_credentials') {
                $this->save_secret_credentials();
            } elseif ($action === 'save_instapay_manual') {
                $this->save_instapay_manual_config();
            } else {
                $this->save_non_private_config();
            }
            return;
        }

        if ($this->request_method() !== 'GET') {
            $this->output->set_status_header(405);
            $this->session->set_flashdata('error_message', 'Invalid request method.');
            redirect(site_url('admin/youngo/payment-settings'), 'refresh');
        }

        $paymob_config = class_exists('Youngo_paymob_config') ? new Youngo_paymob_config() : null;
        $paymob_config_summary = $paymob_config ? $paymob_config->get_safe_diagnostic_summary() : array();

        $page_data = array(
            'page_name' => 'youngo_payment_settings',
            'page_title' => 'YounGo Payment Settings',
            'provider_config_result' => $this->youngo_payment_config_model->get_provider_config('paymob', 'sandbox'),
            'safe_config_summary' => $this->youngo_payment_config_model->get_safe_config_summary('paymob', 'sandbox'),
            'readiness_summary' => $this->youngo_payment_config_model->get_hybrid_readiness_summary('paymob', 'sandbox', $paymob_config_summary),
            'hybrid_config_summary' => $this->youngo_payment_config_model->get_safe_hybrid_config_summary('paymob', 'sandbox', $paymob_config_summary),
            'secret_credential_presence' => $this->youngo_payment_config_model->get_secret_credential_presence('paymob', 'sandbox'),
            'recent_audit_logs' => $this->youngo_payment_config_audit_model->get_recent_audit_logs('paymob', 'sandbox', 10),
            'instapay_config_result' => $this->youngo_payment_config_model->get_instapay_config(),
            'instapay_safe_config_summary' => $this->youngo_payment_config_model->get_safe_config_summary('instapay_manual', 'manual'),
            'instapay_target_snapshot' => $this->youngo_payment_config_model->build_instapay_target_snapshot('english'),
            'instapay_recent_audit_logs' => $this->youngo_payment_config_audit_model->get_recent_audit_logs('instapay_manual', 'manual', 10),
            'instapay_schema_ready' => $this->youngo_payment_config_model->instapay_schema_ready(),
            'schema_ready' => $this->youngo_payment_config_model->schema_ready(),
            'secret_schema_ready' => $this->youngo_payment_config_model->secret_schema_ready(),
            'encryption_key_ready' => $this->youngo_payment_config_model->encryption_key_is_ready(),
            'credential_storage_status' => $this->youngo_payment_config_model->encrypted_credential_storage_status('paymob', 'sandbox'),
            'storage_mode_note' => 'Hybrid storage is active: non-private Paymob settings are saved in DB, and Paymob credentials can be saved encrypted when the ignored encryption-key file and encrypted credential schema are ready. Saved credential values are never displayed. Payment execution, network calls, sandbox testing, and checkout CTAs remain disabled until later approval.',
            'page_mode' => 'non_private_and_encrypted_credentials_save',
        );

        $this->load->view('backend/index', $page_data);
    }

    public function test()
    {
        if ($this->request_method() !== 'GET') {
            $this->output->set_status_header(405);
            $this->session->set_flashdata('error_message', 'Invalid request method.');
            redirect(site_url('admin/youngo/payment-settings/test'), 'refresh');
        }

        $paymob_config = class_exists('Youngo_paymob_config') ? new Youngo_paymob_config() : null;
        $paymob_config_summary = $paymob_config ? $paymob_config->get_safe_diagnostic_summary() : array();
        $provider_config_result = $this->youngo_payment_config_model->get_provider_config('paymob', 'sandbox');
        $dashboard_config = !empty($provider_config_result['ok']) && !empty($provider_config_result['data']['config'])
            ? $provider_config_result['data']['config']
            : array();
        $dashboard_config_exists = !empty($provider_config_result['data']['exists']);
        $adapter = class_exists('Youngo_paymob_adapter') && $paymob_config
            ? new Youngo_paymob_adapter(array(
                'config_reader' => $paymob_config,
                'dashboard_config' => $dashboard_config,
                'dashboard_config_exists' => $dashboard_config_exists,
            ))
            : null;

        $safe_config_summary = $this->youngo_payment_config_model->get_safe_config_summary('paymob', 'sandbox');
        $hybrid_config_summary = $this->youngo_payment_config_model->get_safe_hybrid_config_summary('paymob', 'sandbox', $paymob_config_summary);
        $secret_credential_presence = $this->youngo_payment_config_model->get_secret_credential_presence('paymob', 'sandbox');

        $page_data = array(
            'page_name' => 'youngo_payment_test_center',
            'page_title' => 'YounGo Paymob Test Center',
            'test_center_summary' => $this->build_test_center_summary(
                $safe_config_summary,
                $hybrid_config_summary,
                $secret_credential_presence,
                $adapter ? $adapter->get_sandbox_readiness() : array()
            ),
            'route_readiness' => $this->paymob_route_readiness(),
            'test_action_reason' => 'Available after deployment to HTTPS domain and explicit sandbox execution enablement.',
        );

        $this->load->view('backend/index', $page_data);
    }

    protected function save_non_private_config()
    {
        $before_summary = $this->youngo_payment_config_model->get_safe_config_summary('paymob', 'sandbox');

        $this->db->trans_begin();

        $result = $this->youngo_payment_config_model->upsert_dashboard_non_private_config(
            'paymob',
            'sandbox',
            $this->non_private_post_data(),
            $this->current_user_id()
        );

        if (!empty($result['ok'])) {
            $after_summary = $this->youngo_payment_config_model->get_safe_config_summary('paymob', 'sandbox');
            $audit = $this->youngo_payment_config_audit_model->record_config_audit(
                'paymob',
                'sandbox',
                'dashboard_non_private_save',
                $this->audit_summary_data($before_summary),
                $this->audit_summary_data($after_summary),
                $this->current_user_id()
            );

            if (empty($audit['ok']) || $this->db->trans_status() === false) {
                $this->db->trans_rollback();
                $this->session->set_flashdata('error_message', 'YounGo Paymob settings could not be saved because audit logging failed.');
                redirect(site_url('admin/youngo/payment-settings'), 'refresh');
            }

            $this->db->trans_commit();
            $this->session->set_flashdata('flash_message', 'YounGo Paymob non-private settings saved. Payments and checkout activation remain disabled.');
            redirect(site_url('admin/youngo/payment-settings'), 'refresh');
        }

        $this->db->trans_rollback();
        $this->session->set_flashdata('error_message', $this->safe_result_message($result));
        redirect(site_url('admin/youngo/payment-settings'), 'refresh');
    }

    protected function save_instapay_manual_config()
    {
        $before_summary = $this->youngo_payment_config_model->get_safe_config_summary('instapay_manual', 'manual');

        $this->db->trans_begin();

        $result = $this->youngo_payment_config_model->upsert_dashboard_instapay_manual_config(
            $this->instapay_manual_post_data(),
            $this->current_user_id()
        );

        if (!empty($result['ok'])) {
            $after_summary = $this->youngo_payment_config_model->get_safe_config_summary('instapay_manual', 'manual');
            $audit = $this->youngo_payment_config_audit_model->record_config_audit(
                'instapay_manual',
                'manual',
                'dashboard_instapay_manual_config_save',
                $this->audit_summary_data($before_summary),
                $this->audit_summary_data($after_summary),
                $this->current_user_id()
            );

            if (empty($audit['ok']) || $this->db->trans_status() === false) {
                $this->db->trans_rollback();
                $this->session->set_flashdata('error_message', 'Manual Instapay settings could not be saved because audit logging failed.');
                redirect(site_url('admin/youngo/payment-settings'), 'refresh');
            }

            $this->db->trans_commit();
            $this->session->set_flashdata('flash_message', 'Manual Instapay settings saved. Upload, admin review, payment approval, and access issuance remain disabled.');
            redirect(site_url('admin/youngo/payment-settings'), 'refresh');
        }

        $this->db->trans_rollback();
        $this->session->set_flashdata('error_message', $this->safe_result_message($result));
        redirect(site_url('admin/youngo/payment-settings'), 'refresh');
    }

    protected function save_secret_credentials()
    {
        $before_summary = $this->youngo_payment_config_model->get_safe_config_summary('paymob', 'sandbox');
        $credentials = $this->input->post('credentials', false);
        $credentials = is_array($credentials) ? $credentials : array();

        $this->db->trans_begin();

        $result = $this->youngo_payment_config_model->save_encrypted_credentials(
            'paymob',
            'sandbox',
            $credentials,
            $this->current_user_id()
        );

        if (!empty($result['ok'])) {
            if (isset($result['code']) && $result['code'] === 'credentials_unchanged') {
                $this->db->trans_commit();
                $this->session->set_flashdata('flash_message', 'No Paymob credential values were changed. Existing encrypted values were kept.');
                redirect(site_url('admin/youngo/payment-settings'), 'refresh');
            }

            $after_summary = $this->youngo_payment_config_model->get_safe_config_summary('paymob', 'sandbox');
            $audit = $this->youngo_payment_config_audit_model->record_config_audit(
                'paymob',
                'sandbox',
                'dashboard_secret_credentials_save',
                $this->audit_summary_data($before_summary),
                $this->audit_summary_data($after_summary),
                $this->current_user_id()
            );

            if (empty($audit['ok']) || $this->db->trans_status() === false) {
                $this->db->trans_rollback();
                $this->session->set_flashdata('error_message', 'YounGo Paymob credentials could not be saved because audit logging failed.');
                redirect(site_url('admin/youngo/payment-settings'), 'refresh');
            }

            $this->db->trans_commit();
            $this->session->set_flashdata('flash_message', 'YounGo Paymob credentials were encrypted and saved. Payments and checkout activation remain disabled.');
            redirect(site_url('admin/youngo/payment-settings'), 'refresh');
        }

        $this->db->trans_rollback();
        $this->session->set_flashdata('error_message', $this->safe_result_message($result));
        redirect(site_url('admin/youngo/payment-settings'), 'refresh');
    }

    protected function audit_summary_data($result)
    {
        if (is_array($result) && !empty($result['ok']) && isset($result['data']) && is_array($result['data'])) {
            return $result['data'];
        }

        return array();
    }

    protected function require_root_admin()
    {
        if (!function_exists('youngo_is_root_admin')) {
            $this->session->set_flashdata('error_message', 'Access denied.');
            redirect(site_url('admin/dashboard'), 'refresh');
        }

        if (!youngo_is_root_admin($this->current_user_id())) {
            $this->session->set_flashdata('error_message', 'Access denied.');
            redirect(site_url('admin/dashboard'), 'refresh');
        }
    }

    protected function current_user_id()
    {
        return (int) $this->session->userdata('user_id');
    }

    protected function request_method()
    {
        return isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
    }

    protected function non_private_post_data()
    {
        $post = $this->input->post(null, true);
        $post = is_array($post) ? $post : array();
        unset($post['youngo_payment_settings_action'], $post['credentials']);

        return $post;
    }

    protected function instapay_manual_post_data()
    {
        $post = $this->input->post(null, true);
        $post = is_array($post) ? $post : array();
        unset($post['youngo_payment_settings_action'], $post['credentials']);

        return $post;
    }

    protected function safe_result_message($result)
    {
        if (is_array($result) && !empty($result['message'])) {
            return (string) $result['message'];
        }

        return 'YounGo Paymob settings could not be saved.';
    }

    protected function build_test_center_summary($safe_config_summary, $hybrid_config_summary, $secret_credential_presence, $adapter_readiness)
    {
        $safe_data = !empty($safe_config_summary['ok']) && isset($safe_config_summary['data']) && is_array($safe_config_summary['data'])
            ? $safe_config_summary['data']
            : array();
        $hybrid_data = !empty($hybrid_config_summary['ok']) && isset($hybrid_config_summary['data']) && is_array($hybrid_config_summary['data'])
            ? $hybrid_config_summary['data']
            : array();
        $credential_data = !empty($secret_credential_presence['ok']) && isset($secret_credential_presence['data']) && is_array($secret_credential_presence['data'])
            ? $secret_credential_presence['data']
            : array();
        $private_presence = isset($hybrid_data['private_presence']) && is_array($hybrid_data['private_presence'])
            ? $hybrid_data['private_presence']
            : array();

        $checks = array(
            $this->test_center_check('Encryption key', $this->youngo_payment_config_model->encryption_key_is_ready() ? 'ready' : 'missing', 'CodeIgniter encryption key presence only. Value is not displayed.'),
            $this->test_center_check('Encrypted credential DB schema', $this->youngo_payment_config_model->secret_schema_ready() ? 'ready' : 'missing', 'Encrypted credential table/columns readiness only.'),
            $this->test_center_check('API key', $this->credential_presence_status($credential_data, $private_presence, 'api_key'), 'Presence only. Value and encrypted blob are hidden.'),
            $this->test_center_check('Public key', $this->credential_presence_status($credential_data, $private_presence, 'public_key'), 'Presence only. Value and encrypted blob are hidden.'),
            $this->test_center_check('Secret key', $this->credential_presence_status($credential_data, $private_presence, 'secret_key'), 'Presence only. Value and encrypted blob are hidden.'),
            $this->test_center_check('HMAC secret', $this->credential_presence_status($credential_data, $private_presence, 'hmac_secret'), 'Presence only. Value and encrypted blob are hidden.'),
            $this->test_center_check('Card integration ID', $this->safe_config_presence_status($safe_data, 'card_integration_id_egp'), 'Presence only.'),
            $this->test_center_check('Mobile wallet integration ID', $this->safe_config_presence_status($safe_data, 'wallet_integration_id_egp'), 'Presence only.'),
            $this->test_center_check('API base URL', $this->safe_config_presence_status($safe_data, 'api_base_url'), 'Presence only.'),
            $this->test_center_check('Checkout base URL', $this->safe_config_presence_status($safe_data, 'checkout_base_url'), 'Presence only.'),
            $this->test_center_check('Return URL', $this->safe_config_presence_status($safe_data, 'return_url'), 'Presence only.'),
            $this->test_center_check('Notification URL', $this->safe_config_presence_status($safe_data, 'notification_url'), 'Presence only.'),
            $this->test_center_check('Payment execution', empty($safe_data['enabled']) ? 'disabled' : 'enabled_unapproved', 'Must remain disabled in this phase.'),
            $this->test_center_check('Network execution', empty($safe_data['network_enabled']) ? 'disabled' : 'enabled_unapproved', 'Must remain disabled in this phase.'),
            $this->test_center_check('Checkout CTA', empty($safe_data['checkout_cta_enabled']) ? 'disabled' : 'enabled_unapproved', 'Public checkout CTA must remain disabled.'),
            $this->test_center_check('Live mode', empty($safe_data['live_mode_allowed']) ? 'disabled' : 'enabled_unapproved', 'Live mode must remain blocked.'),
        );

        return array(
            'checks' => $checks,
            'adapter_readiness_code' => isset($adapter_readiness['code']) ? (string) $adapter_readiness['code'] : 'paymob_sandbox_gates_not_ready',
            'adapter_ready' => !empty($adapter_readiness['ok']),
            'mode' => isset($safe_data['mode']) ? (string) $safe_data['mode'] : 'sandbox',
            'currency' => isset($safe_data['currency']) ? (string) $safe_data['currency'] : 'EGP',
            'configured_db_row' => !empty($safe_data['exists']) ? 'configured_redacted' : 'missing',
        );
    }

    protected function test_center_check($label, $status, $note)
    {
        return array(
            'label' => (string) $label,
            'status' => (string) $status,
            'note' => (string) $note,
        );
    }

    protected function credential_presence_status($credential_data, $private_presence, $field)
    {
        $status_key = $field . '_status';
        if (isset($credential_data[$status_key]) && (string) $credential_data[$status_key] === 'configured_redacted') {
            return 'configured';
        }

        if (isset($private_presence[$field]) && (string) $private_presence[$field] === 'configured_redacted') {
            return 'configured';
        }

        return 'missing';
    }

    protected function safe_config_presence_status($safe_data, $field)
    {
        return isset($safe_data[$field]) && (string) $safe_data[$field] === 'configured_redacted'
            ? 'configured'
            : 'missing';
    }

    protected function paymob_route_readiness()
    {
        $routes_source = is_file(APPPATH . 'config/routes.php')
            ? file_get_contents(APPPATH . 'config/routes.php')
            : '';
        $webhook_source = is_file(APPPATH . 'controllers/Youngo_payment_webhook.php')
            ? file_get_contents(APPPATH . 'controllers/Youngo_payment_webhook.php')
            : '';
        $return_source = is_file(APPPATH . 'controllers/Youngo_payment_return.php')
            ? file_get_contents(APPPATH . 'controllers/Youngo_payment_return.php')
            : '';

        return array(
            'webhook' => array(
                'route' => '/payment/paymob/webhook',
                'status' => strpos($routes_source, "\$route['payment/paymob/webhook'] = 'youngo_payment_webhook/paymob';") !== false
                    && strpos($webhook_source, 'webhook_testing_disabled') !== false
                    ? 'ready_fail_closed'
                    : 'missing_or_not_fail_closed',
                'note' => 'POST-only webhook skeleton. Disabled unless explicit webhook testing gate is enabled; no payment/access writes in current phase.',
            ),
            'return' => array(
                'route' => '/payment/paymob/return',
                'status' => strpos($routes_source, "\$route['payment/paymob/return'] = 'youngo_payment_return/paymob';") !== false
                    && strpos($return_source, 'paymob_return_disabled_no_write') !== false
                    ? 'ready_disabled_no_write'
                    : 'missing',
                'note' => 'UX-only return skeleton. It never marks paid, never issues access, and never trusts query parameters.',
            ),
        );
    }
}
