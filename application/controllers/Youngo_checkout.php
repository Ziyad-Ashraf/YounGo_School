<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!class_exists('Youngo_paymob_config') && is_file(APPPATH . 'libraries/Youngo_paymob_config.php')) {
    require_once APPPATH . 'libraries/Youngo_paymob_config.php';
}

if (!class_exists('Youngo_paymob_adapter') && is_file(APPPATH . 'libraries/Youngo_paymob_adapter.php')) {
    require_once APPPATH . 'libraries/Youngo_paymob_adapter.php';
}

class Youngo_checkout extends CI_Controller
{
    protected $paymob_config;
    protected $paymob_adapter;
    protected $paymob_dashboard_config = array();
    protected $paymob_dashboard_config_exists = false;

    public function __construct()
    {
        parent::__construct();

        $this->load->database();
        $this->load->library('session');
        $this->load->model('Youngo_checkout_model', 'youngo_checkout_model');
        $this->load->model('Youngo_payment_model', 'youngo_payment_model');
        $this->load->model('Youngo_payment_config_model', 'youngo_payment_config_model');
        $this->load->model('Youngo_instapay_payment_model', 'youngo_instapay_payment_model');

        $this->paymob_config = new Youngo_paymob_config();
        $this->load_paymob_dashboard_config();
        $this->paymob_adapter = new Youngo_paymob_adapter(array(
            'config_reader' => $this->paymob_config,
            'dashboard_config' => $this->paymob_dashboard_config,
            'dashboard_config_exists' => $this->paymob_dashboard_config_exists,
        ));

        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }

    public function start($course_id = null)
    {
        return $this->start_course($course_id);
    }

    public function start_course($course_id = null)
    {
        $availability = $this->checkout_availability();
        if (empty($availability['ok'])) {
            return $this->render_disabled_page('start', array(
                'course_id' => $this->safe_positive_int($course_id),
            ), $availability);
        }

        $learner_id = $this->require_learner_id();
        if (!$learner_id) {
            return;
        }

        $course_id = $this->safe_positive_int($course_id);
        if (!$course_id) {
            return $this->render_order_page($this->failure_result('invalid_course_id', 'A valid course is required for checkout.'), array(), array(), 'start');
        }

        $course = $this->get_course($course_id);
        if (empty($course)) {
            return $this->render_order_page($this->failure_result('course_not_found', 'The course could not be found.'), array(), array(), 'start');
        }

        $amount = $this->checkout_amount_for_course($course);
        if ($amount <= 0) {
            return $this->render_order_page($this->failure_result('invalid_checkout_amount', 'This course does not have a valid EGP checkout amount.'), array(), $course, 'start');
        }

        $result = $this->youngo_checkout_model->create_or_reuse_draft_order($learner_id, $course_id, $amount, 'EGP');
        if (empty($result['ok'])) {
            return $this->render_order_page($result, array(), $course, 'start');
        }

        $order = !empty($result['data']['order_id'])
            ? $this->youngo_checkout_model->get_order((int) $result['data']['order_id'])
            : array();

        if ($this->paymob_config->is_network_enabled()) {
            return $this->start_paymob_sandbox_intention($order, $course, $learner_id, !empty($result['data']['reused']));
        }

        $order_reference = isset($result['data']['order_reference']) ? (string) $result['data']['order_reference'] : '';
        redirect(site_url('youngo/checkout/order/' . rawurlencode($order_reference)), 'refresh');
    }

    public function start_subscription($plan_id = null)
    {
        $availability = $this->checkout_availability();
        if (empty($availability['ok'])) {
            return $this->render_disabled_page('start', array(
                'plan_id' => $this->safe_positive_int($plan_id),
            ), $availability);
        }

        $learner_id = $this->require_learner_id();
        if (!$learner_id) {
            return;
        }

        $plan_id = $this->safe_positive_int($plan_id);
        if (!$plan_id) {
            return $this->render_order_page($this->failure_result('invalid_plan_id', 'A valid subscription plan is required for checkout.'), array(), array(), 'start');
        }

        $plan = $this->get_subscription_plan($plan_id);
        if (empty($plan)) {
            return $this->render_order_page($this->failure_result('subscription_plan_not_found', 'The subscription plan could not be found.'), array(), array(), 'start');
        }

        $amount = $this->checkout_amount_for_plan($plan);
        if ($amount <= 0) {
            return $this->render_order_page($this->failure_result('invalid_checkout_amount', 'This subscription plan does not have a valid EGP checkout amount.'), array(), array(), 'start');
        }

        $result = $this->youngo_checkout_model->create_or_reuse_draft_order_for_subscription($learner_id, $plan_id, $amount, 'EGP');
        if (empty($result['ok'])) {
            return $this->render_order_page($result, array(), array(), 'start');
        }

        $order_reference = isset($result['data']['order_reference']) ? (string) $result['data']['order_reference'] : '';
        redirect(site_url('youngo/checkout/order/' . rawurlencode($order_reference)), 'refresh');
    }

    public function order($order_reference = '')
    {
        return $this->render_existing_order($order_reference, 'order');
    }

    public function return($order_reference = '')
    {
        return $this->render_existing_order($order_reference, 'return');
    }

    public function status($order_reference = '')
    {
        $availability = $this->checkout_availability();
        if (empty($availability['ok'])) {
            return $this->json_response(403, array(
                'ok' => false,
                'status' => 'disabled',
                'code' => $availability['code'],
                'message' => $availability['message'],
                'data' => $this->safe_status_context($order_reference),
            ));
        }

        $learner_id = $this->current_learner_id();
        if (!$learner_id) {
            return $this->json_response(401, array(
                'ok' => false,
                'status' => 'rejected',
                'code' => 'learner_login_required',
                'message' => 'Please sign in as a learner to view checkout status.',
                'data' => $this->safe_status_context($order_reference),
            ));
        }

        $order = $this->load_owned_order($order_reference, $learner_id);
        if (empty($order['ok'])) {
            return $this->json_response(404, array(
                'ok' => false,
                'status' => 'rejected',
                'code' => $order['code'],
                'message' => $order['message'],
                'data' => $this->safe_status_context($order_reference),
            ));
        }

        $summary = $this->youngo_payment_model->get_payment_status_summary((int) $order['order']['id']);

        return $this->json_response(200, array(
            'ok' => true,
            'status' => 'ok',
            'code' => 'checkout_status_summary',
            'message' => 'YounGo checkout status loaded.',
            'data' => array(
                'order' => $this->youngo_checkout_model->get_safe_order_summary($order['order']),
                'payment_summary' => !empty($summary['ok']) && isset($summary['data']) ? $summary['data'] : array(),
                'paymob_network' => $this->paymob_config->is_network_enabled() ? 'sandbox_gated' : 'disabled',
                'return_url_behavior' => 'ux_only',
            ),
        ));
    }

    public function apply_coupon($order_reference = '')
    {
        return $this->handle_coupon_snapshot_action($order_reference, 'apply');
    }

    public function clear_coupon($order_reference = '')
    {
        return $this->handle_coupon_snapshot_action($order_reference, 'clear');
    }

    public function complete_zero_amount_coupon($order_reference = '')
    {
        $order_reference = $this->safe_reference($order_reference);

        if (strtoupper($this->input->method(true)) !== 'POST') {
            $this->output->set_status_header(405);
            $this->session->set_flashdata('error_message', get_phrase('Zero-amount checkout completion requires a form submission.'));
            return $this->redirect_to_checkout_order($order_reference);
        }

        $availability = $this->checkout_availability();
        if (empty($availability['ok'])) {
            $this->session->set_flashdata('error_message', isset($availability['message']) ? $availability['message'] : get_phrase('Checkout is not available.'));
            return $this->redirect_to_checkout_order($order_reference);
        }

        $learner_id = $this->require_learner_id();
        if (!$learner_id) {
            return;
        }

        $owned_order = $this->load_owned_order($order_reference, $learner_id);
        if (empty($owned_order['ok']) || empty($owned_order['order']['id'])) {
            $this->session->set_flashdata('error_message', isset($owned_order['message']) ? $owned_order['message'] : get_phrase('The checkout order could not be loaded.'));
            return $this->redirect_to_checkout_order($order_reference);
        }

        $result = $this->youngo_checkout_model->complete_zero_amount_coupon_order((int) $owned_order['order']['id'], $learner_id);

        if (!empty($result['ok'])) {
            $message = isset($result['code']) && $result['code'] === 'already_completed'
                ? get_phrase('Checkout is already complete and access is available.')
                : get_phrase('Checkout completed. Your course access is active.');
            $this->session->set_flashdata('flash_message', $message);
        } else {
            $this->session->set_flashdata('error_message', isset($result['message']) ? $result['message'] : get_phrase('Zero-amount checkout could not be completed.'));
        }

        return $this->redirect_to_checkout_order($order_reference);
    }

    public function submit_instapay($order_reference = '')
    {
        $order_reference = $this->safe_reference($order_reference);

        if (strtoupper($this->input->method(true)) !== 'POST') {
            $this->output->set_status_header(405);
            $this->session->set_flashdata('error_message', get_phrase('Instapay submissions require a form submission.'));
            return $this->redirect_to_checkout_order($order_reference);
        }

        $availability = $this->checkout_availability();
        if (empty($availability['ok'])) {
            $this->session->set_flashdata('error_message', isset($availability['message']) ? $availability['message'] : get_phrase('Checkout is not available.'));
            return $this->redirect_to_checkout_order($order_reference);
        }

        $learner_id = $this->require_learner_id();
        if (!$learner_id) {
            return;
        }

        $owned_order = $this->load_owned_order($order_reference, $learner_id);
        if (empty($owned_order['ok']) || empty($owned_order['order']['id'])) {
            $this->session->set_flashdata('error_message', isset($owned_order['message']) ? $owned_order['message'] : get_phrase('The checkout order could not be loaded.'));
            return $this->redirect_to_checkout_order($order_reference);
        }

        $target = $this->instapay_target_for_checkout();
        if (empty($target['enabled'])) {
            $this->session->set_flashdata('error_message', get_phrase('Manual Instapay checkout is not available yet.'));
            return $this->redirect_to_checkout_order($order_reference);
        }

        $upload = $this->store_instapay_screenshot((int) $owned_order['order']['id'], $target);
        if (empty($upload['ok'])) {
            $this->session->set_flashdata('error_message', isset($upload['message']) ? $upload['message'] : get_phrase('A valid transaction screenshot is required.'));
            return $this->redirect_to_checkout_order($order_reference);
        }

        $transaction_reference = $this->input->post('transaction_reference', true);
        $user_note = $this->input->post('user_note', true);
        $upload_data = $upload['data'];
        $upload_data['language'] = $this->current_instapay_language();

        $result = $this->youngo_instapay_payment_model->create_pending_submission(
            (int) $owned_order['order']['id'],
            $learner_id,
            $upload_data,
            $transaction_reference,
            $user_note
        );

        if (!empty($result['ok'])) {
            $this->session->set_flashdata('flash_message', get_phrase('Instapay payment evidence submitted for admin review.'));
        } else {
            $this->delete_uploaded_instapay_file(isset($upload_data['screenshot_path']) ? $upload_data['screenshot_path'] : '');
            $this->session->set_flashdata('error_message', isset($result['message']) ? $result['message'] : get_phrase('Instapay evidence could not be submitted.'));
        }

        return $this->redirect_to_checkout_order($order_reference);
    }

    protected function render_existing_order($order_reference, $context)
    {
        $availability = $this->checkout_availability();
        if (empty($availability['ok'])) {
            return $this->render_disabled_page($context, array(
                'order_reference' => $this->safe_reference($order_reference),
            ), $availability);
        }

        $learner_id = $this->require_learner_id();
        if (!$learner_id) {
            return;
        }

        $order = $this->load_owned_order($order_reference, $learner_id);
        if (empty($order['ok'])) {
            return $this->render_order_page($order, array(), array(), $context);
        }

        $course = !empty($order['order']['course_id']) ? $this->get_course((int) $order['order']['course_id']) : array();
        $payment_summary = $this->youngo_payment_model->get_payment_status_summary((int) $order['order']['id']);
        $notice = array(
            'ok' => true,
            'code' => $context === 'return' ? 'return_url_ux_only' : 'checkout_order_loaded',
            'message' => $context === 'return'
                ? 'Return URL is UX-only. Verified webhook processing remains the source of truth.'
                : 'Checkout order loaded for local testing.',
        );

        return $this->render_order_page($notice, $order['order'], $course, $context, !empty($payment_summary['ok']) ? $payment_summary['data'] : array());
    }

    protected function render_disabled_page($context, $context_data = array(), $availability = null)
    {
        if ($availability === null) {
            $availability = $this->checkout_availability();
        }

        $page_data = array(
            'page_name' => null,
            'page_title' => get_phrase('YounGo checkout'),
            'path' => APPPATH . 'views/frontend/youngo/checkout_disabled.php',
            'youngo_checkout_disabled_context' => array(
                'context' => $this->safe_context($context),
                'route_enabled' => $this->paymob_config->is_checkout_routes_enabled(),
                'local_testing_enabled' => $this->paymob_config->is_checkout_local_testing_enabled(),
                'cta_enabled' => $this->paymob_config->is_checkout_cta_enabled(),
                'payment_enabled' => $this->paymob_config->is_enabled(),
                'network_enabled' => (bool) $this->paymob_config->get('network_enabled', false),
                'mode' => $this->paymob_config->get_mode(),
                'currency' => $this->paymob_config->get_currency(),
                'availability' => $availability,
                'context_data' => is_array($context_data) ? $context_data : array(),
            ),
        );

        $this->output->set_status_header(empty($availability['ok']) ? 403 : 503);
        $this->load->view('frontend/youngo/index', $page_data);
    }

    protected function render_order_page($notice, $order = array(), $course = array(), $context = 'order', $payment_summary = array())
    {
        $adapter_summary = $this->paymob_adapter->get_safe_diagnostic_summary();
        $paymob_context = array(
            'ready_for_sandbox' => !empty($adapter_summary['sandbox_readiness']['ok']),
            'network_enabled' => $this->paymob_config->is_network_enabled(),
            'status' => isset($adapter_summary['network_execution']) ? $adapter_summary['network_execution'] : 'disabled',
            'checkout_url' => null,
            'checkout_url_present' => false,
        );

        if (isset($notice['data']['checkout_url']) && $this->safe_checkout_url($notice['data']['checkout_url'])) {
            $paymob_context['checkout_url'] = $notice['data']['checkout_url'];
            $paymob_context['checkout_url_present'] = true;
            $paymob_context['status'] = 'sandbox_checkout_ready';
        }

        $review_snapshot = array();
        if (is_array($order) && !empty($order['id'])) {
            $snapshot_result = $this->youngo_checkout_model->get_safe_order_review_snapshot((int) $order['id']);
            if (!empty($snapshot_result['ok']) && !empty($snapshot_result['data']['review_snapshot'])) {
                $review_snapshot = $snapshot_result['data']['review_snapshot'];
            }
        }
        $instapay_context = is_array($order) && !empty($order['id'])
            ? $this->build_instapay_checkout_context($order, $review_snapshot)
            : $this->build_instapay_checkout_context(array(), array());
        $zero_coupon_context = array(
            'completion_available' => false,
            'already_completed' => false,
            'subscription_deferred' => false,
            'is_zero_total_coupon_order' => false,
        );
        if (is_array($order) && !empty($order['id'])) {
            $zero_coupon_result = $this->youngo_checkout_model->get_zero_amount_coupon_completion_context((int) $order['id'], isset($order['user_id']) ? (int) $order['user_id'] : null);
            if (!empty($zero_coupon_result['ok']) && isset($zero_coupon_result['data']['zero_amount_coupon']) && is_array($zero_coupon_result['data']['zero_amount_coupon'])) {
                $zero_coupon_context = $zero_coupon_result['data']['zero_amount_coupon'];
            }
        }

        $page_data = array(
            'page_name' => null,
            'page_title' => get_phrase('YounGo checkout'),
            'path' => APPPATH . 'views/frontend/youngo/checkout_order.php',
            'youngo_checkout_order_context' => array(
                'context' => $this->safe_context($context),
                'notice' => is_array($notice) ? $notice : array(),
                'order' => is_array($order) ? $this->youngo_checkout_model->get_safe_order_summary($order) : array(),
                'review_snapshot' => $review_snapshot,
                'course' => is_array($course) ? $this->safe_course_summary($course) : array(),
                'payment_summary' => is_array($payment_summary) ? $payment_summary : array(),
                'paymob' => $paymob_context,
                'instapay' => $instapay_context,
                'zero_amount_coupon' => $zero_coupon_context,
                'mode' => $this->paymob_config->get_mode(),
                'currency' => $this->paymob_config->get_currency(),
                'return_url_behavior' => 'ux_only',
                'entitlement_issuance_from_ui' => 'disabled',
            ),
        );

        $this->output->set_status_header(empty($notice['ok']) ? 422 : 200);
        $this->load->view('frontend/youngo/index', $page_data);
    }

    protected function handle_coupon_snapshot_action($order_reference, $action)
    {
        $order_reference = $this->safe_reference($order_reference);

        if (strtoupper($this->input->method(true)) !== 'POST') {
            $this->output->set_status_header(405);
            $this->session->set_flashdata('error_message', get_phrase('Coupon changes require a form submission.'));
            return $this->redirect_to_checkout_order($order_reference);
        }

        $availability = $this->checkout_availability();
        if (empty($availability['ok'])) {
            $this->session->set_flashdata('error_message', isset($availability['message']) ? $availability['message'] : get_phrase('Checkout is not available.'));
            return $this->redirect_to_checkout_order($order_reference);
        }

        $learner_id = $this->require_learner_id();
        if (!$learner_id) {
            return;
        }

        $order = $this->load_owned_order($order_reference, $learner_id);
        if (empty($order['ok']) || empty($order['order']['id'])) {
            $this->session->set_flashdata('error_message', isset($order['message']) ? $order['message'] : get_phrase('The checkout order could not be loaded.'));
            return $this->redirect_to_checkout_order($order_reference);
        }

        if ($action === 'apply') {
            $coupon_code = $this->input->post('coupon_code', true);
            $result = $this->youngo_checkout_model->apply_coupon_snapshot_to_order((int) $order['order']['id'], $learner_id, $coupon_code);
        } else {
            $result = $this->youngo_checkout_model->clear_coupon_snapshot_from_order((int) $order['order']['id'], $learner_id);
        }

        if (!empty($result['ok'])) {
            // A full-discount subscription never reaches a gateway or payment transaction.
            if ($action === 'apply' && isset($result['data']['coupon_result']['final_amount']) && (float) $result['data']['coupon_result']['final_amount'] === 0.0
                && isset($order['order']['order_type']) && $order['order']['order_type'] === 'subscription_purchase') {
                $completion = $this->youngo_checkout_model->complete_zero_amount_coupon_order((int) $order['order']['id'], $learner_id);
                if (!empty($completion['ok'])) {
                    $this->session->set_flashdata('flash_message', get_phrase('Coupon applied. Your subscription is now active.'));
                    return $this->redirect_to_checkout_order($order_reference);
                }
                $this->session->set_flashdata('error_message', isset($completion['message']) ? $completion['message'] : get_phrase('Coupon was applied, but subscription activation could not be completed.'));
                return $this->redirect_to_checkout_order($order_reference);
            }
            $this->session->set_flashdata('flash_message', isset($result['message']) ? $result['message'] : get_phrase('Checkout coupon updated.'));
        } else {
            $this->session->set_flashdata('error_message', $this->coupon_action_error_message($result));
        }

        return $this->redirect_to_checkout_order($order_reference);
    }

    protected function build_instapay_checkout_context($order, $review_snapshot)
    {
        $target = $this->instapay_target_for_checkout();
        $latest_submission = array();
        if (is_array($order) && !empty($order['id']) && !empty($order['user_id'])) {
            $latest_submission = $this->youngo_instapay_payment_model->get_latest_submission_for_order_user((int) $order['id'], (int) $order['user_id']);
        }

        $latest_status = isset($latest_submission['status']) ? (string) $latest_submission['status'] : null;
        $eligible = array('ok' => false, 'code' => 'order_missing');
        if (is_array($order) && !empty($order['id'])) {
            $eligible = $this->youngo_instapay_payment_model->can_create_submission_for_order($order);
        }

        $can_submit = !empty($target['enabled'])
            && !empty($eligible['ok'])
            && ($latest_status === null || $latest_status === 'rejected');

        return array(
            'enabled' => !empty($target['enabled']),
            'target' => $target,
            'can_submit' => $can_submit,
            'eligibility' => $eligible,
            'latest_submission' => $this->safe_instapay_submission_summary($latest_submission),
            'latest_status' => $latest_status,
            'review_statuses' => $this->youngo_instapay_payment_model->allowed_statuses(),
            'expected_amount' => isset($review_snapshot['final_amount']) ? (string) $review_snapshot['final_amount'] : (isset($order['total_amount']) ? (string) $order['total_amount'] : '0.00'),
            'currency' => 'EGP',
            'upload_input_name' => 'instapay_screenshot',
            'submit_url' => !empty($order['order_reference']) ? site_url('youngo/checkout/instapay/submit/' . rawurlencode((string) $order['order_reference'])) : '',
        );
    }

    protected function instapay_target_for_checkout()
    {
        $result = $this->youngo_instapay_payment_model->build_instapay_target_snapshot($this->current_instapay_language());
        if (empty($result['ok']) || empty($result['data']['snapshot'])) {
            return array(
                'enabled' => false,
                'label' => '',
                'address' => '',
                'link' => '',
                'instructions' => '',
                'instructions_ar' => '',
                'instructions_en' => '',
                'currency' => 'EGP',
                'max_upload_mb' => '5.00',
                'allowed_mimes' => array('image/jpeg', 'image/png', 'image/webp'),
            );
        }

        return $result['data']['snapshot'];
    }

    protected function safe_instapay_submission_summary($submission)
    {
        if (empty($submission) || !is_array($submission)) {
            return array();
        }

        return array(
            'id' => isset($submission['id']) ? (int) $submission['id'] : null,
            'order_id' => isset($submission['order_id']) ? (int) $submission['order_id'] : null,
            'status' => isset($submission['status']) ? (string) $submission['status'] : null,
            'expected_amount' => isset($submission['expected_amount']) ? (string) $submission['expected_amount'] : null,
            'submitted_amount' => isset($submission['submitted_amount']) ? (string) $submission['submitted_amount'] : null,
            'currency' => isset($submission['currency']) ? (string) $submission['currency'] : 'EGP',
            'transaction_reference' => isset($submission['transaction_reference']) ? (string) $submission['transaction_reference'] : null,
            'submitted_at' => isset($submission['created_at']) ? (int) $submission['created_at'] : null,
        );
    }

    protected function store_instapay_screenshot($order_id, $target)
    {
        $field = 'instapay_screenshot';
        if (empty($_FILES[$field]) || !is_array($_FILES[$field])) {
            return $this->failure_result('screenshot_required', 'Please upload a successful Instapay transaction screenshot.');
        }

        $file = $_FILES[$field];
        if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
            return $this->failure_result('screenshot_upload_failed', 'The screenshot upload could not be received.');
        }

        $size = isset($file['size']) ? (int) $file['size'] : 0;
        $max_mb = isset($target['max_upload_mb']) && is_numeric($target['max_upload_mb']) ? (float) $target['max_upload_mb'] : 5.0;
        if ($max_mb < 1.0 || $max_mb > 20.0) {
            $max_mb = 5.0;
        }
        $max_bytes = (int) round($max_mb * 1024 * 1024);
        if ($size <= 0 || $size > $max_bytes) {
            return $this->failure_result('screenshot_size_invalid', 'Screenshot size must be within the configured upload limit.');
        }

        $tmp_name = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
        if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
            return $this->failure_result('screenshot_upload_invalid', 'The uploaded screenshot is invalid.');
        }

        $mime = $this->detect_upload_mime($tmp_name);
        $allowed_mimes = isset($target['allowed_mimes']) && is_array($target['allowed_mimes'])
            ? $target['allowed_mimes']
            : array('image/jpeg', 'image/png', 'image/webp');
        $allowed_mimes = array_values(array_intersect($allowed_mimes, array('image/jpeg', 'image/png', 'image/webp')));
        if (empty($allowed_mimes)) {
            $allowed_mimes = array('image/jpeg', 'image/png', 'image/webp');
        }
        if (!in_array($mime, $allowed_mimes, true)) {
            return $this->failure_result('screenshot_mime_not_allowed', 'Screenshot must be a JPG, PNG, or WebP image.');
        }

        $extension = $this->safe_image_extension(isset($file['name']) ? $file['name'] : '', $mime);
        if ($extension === '') {
            return $this->failure_result('screenshot_extension_not_allowed', 'Screenshot file extension must match an allowed image type.');
        }

        $root = defined('FCPATH') ? rtrim(FCPATH, "\\/") : rtrim(dirname(APPPATH), "\\/");
        $relative_dir = 'uploads/youngo/instapay_evidence';
        $absolute_dir = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_dir);
        if (!is_dir($absolute_dir) && !mkdir($absolute_dir, 0755, true)) {
            return $this->failure_result('screenshot_storage_unavailable', 'Screenshot storage is not available.');
        }
        $this->ensure_instapay_upload_directory_guards($absolute_dir);

        $filename = 'instapay_' . (int) $order_id . '_' . time() . '_' . $this->random_hex(12) . '.' . $extension;
        $absolute_path = $absolute_dir . DIRECTORY_SEPARATOR . $filename;
        if (file_exists($absolute_path)) {
            return $this->failure_result('screenshot_filename_collision', 'Screenshot storage could not allocate a safe filename.');
        }

        if (!move_uploaded_file($tmp_name, $absolute_path)) {
            return $this->failure_result('screenshot_store_failed', 'The screenshot could not be stored.');
        }

        @chmod($absolute_path, 0644);

        return array(
            'ok' => true,
            'code' => 'screenshot_stored',
            'message' => 'Screenshot stored.',
            'data' => array(
                'screenshot_path' => $relative_dir . '/' . $filename,
                'screenshot_original_name' => isset($file['name']) ? basename((string) $file['name']) : '',
                'screenshot_mime' => $mime,
                'screenshot_size' => $size,
                'submitted_amount' => null,
            ),
        );
    }

    protected function detect_upload_mime($path)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return strtolower($mime);
                }
            }
        }

        $image = @getimagesize($path);
        return !empty($image['mime']) ? strtolower((string) $image['mime']) : '';
    }

    protected function safe_image_extension($original_name, $mime)
    {
        $extension = strtolower((string) pathinfo((string) $original_name, PATHINFO_EXTENSION));
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;

        $map = array(
            'image/jpeg' => array('jpg'),
            'image/png' => array('png'),
            'image/webp' => array('webp'),
        );

        if (empty($map[$mime]) || !in_array($extension, $map[$mime], true)) {
            return '';
        }

        return $extension;
    }

    protected function ensure_instapay_upload_directory_guards($absolute_dir)
    {
        $htaccess = rtrim($absolute_dir, "\\/") . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Require all denied\nDeny from all\n<FilesMatch \"\\.(php|phtml|phar)$\">\n    Deny from all\n</FilesMatch>\n");
        }

        $index = rtrim($absolute_dir, "\\/") . DIRECTORY_SEPARATOR . 'index.html';
        if (!is_file($index)) {
            @file_put_contents($index, '');
        }
    }

    protected function delete_uploaded_instapay_file($relative_path)
    {
        $relative_path = str_replace('\\', '/', trim((string) $relative_path));
        if ($relative_path === '' || strpos($relative_path, 'uploads/youngo/instapay_evidence/') !== 0 || strpos($relative_path, '..') !== false) {
            return false;
        }

        $root = defined('FCPATH') ? rtrim(FCPATH, "\\/") : rtrim(dirname(APPPATH), "\\/");
        $absolute_path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
        if (is_file($absolute_path)) {
            return @unlink($absolute_path);
        }

        return true;
    }

    protected function checkout_availability()
    {
        if (!$this->paymob_config->is_checkout_routes_enabled()) {
            return $this->failure_result('checkout_routes_disabled', 'YounGo checkout routes are disabled.');
        }

        if (!$this->paymob_config->is_checkout_local_testing_enabled()) {
            return $this->failure_result('checkout_local_testing_disabled', 'YounGo local checkout testing is disabled.');
        }

        if ($this->paymob_config->get_mode() !== 'sandbox' || $this->paymob_config->get_currency() !== 'EGP') {
            return $this->failure_result('checkout_config_not_local_egp_sandbox', 'YounGo local checkout requires sandbox mode and EGP.');
        }

        if ($this->paymob_config->is_network_enabled() && !$this->paymob_config->is_sandbox_network_testing_enabled()) {
            return $this->failure_result('paymob_network_must_remain_disabled', 'Paymob network execution requires the explicit sandbox network testing gate.');
        }

        return array(
            'ok' => true,
            'code' => 'checkout_local_testing_available',
            'message' => 'YounGo local checkout testing is available.',
        );
    }

    protected function require_learner_id()
    {
        $learner_id = $this->current_learner_id();
        if ($learner_id) {
            return $learner_id;
        }

        $this->session->set_flashdata('error_message', get_phrase('Please sign in as a learner to continue checkout.'));
        redirect(site_url('login'), 'refresh');
        return null;
    }

    protected function current_learner_id()
    {
        if ((int) $this->session->userdata('user_login') !== 1 || (int) $this->session->userdata('admin_login') === 1) {
            return null;
        }

        $user_id = (int) $this->session->userdata('user_id');
        if ($user_id <= 0 || (int) $this->session->userdata('role_id') === 1) {
            return null;
        }

        $user = $this->db
            ->select('id, role_id, status, is_instructor')
            ->where('id', $user_id)
            ->get('users', 1)
            ->row_array();

        if (empty($user) || (int) $user['id'] === 1 || (int) $user['role_id'] !== 2 || (int) $user['status'] !== 1 || (int) $user['is_instructor'] !== 0) {
            return null;
        }

        return $user_id;
    }

    protected function load_owned_order($order_reference, $learner_id)
    {
        $order_reference = $this->safe_reference($order_reference);
        if ($order_reference === '') {
            return $this->failure_result('invalid_order_reference', 'A valid checkout order reference is required.');
        }

        $order = $this->youngo_checkout_model->get_order_by_reference($order_reference);
        if (empty($order)) {
            return $this->failure_result('order_not_found', 'The checkout order could not be found.');
        }

        if ((int) $order['user_id'] !== (int) $learner_id) {
            return $this->failure_result('order_not_available', 'The checkout order is not available for this learner.');
        }

        return array(
            'ok' => true,
            'code' => 'owned_order_loaded',
            'message' => 'Owned checkout order loaded.',
            'order' => $order,
        );
    }

    protected function redirect_to_checkout_order($order_reference)
    {
        if ($order_reference !== '') {
            redirect(site_url('youngo/checkout/order/' . rawurlencode($order_reference)), 'refresh');
            return;
        }

        redirect(site_url('home/courses'), 'refresh');
    }

    protected function get_course($course_id)
    {
        if (!$this->safe_positive_int($course_id)) {
            return array();
        }

        $query = $this->db
            ->where('id', (int) $course_id)
            ->get('course', 1);

        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    protected function checkout_amount_for_course($course)
    {
        if (empty($course) || !is_array($course)) {
            return 0.0;
        }

        if (!empty($course['discount_flag']) && isset($course['discounted_price']) && (float) $course['discounted_price'] > 0) {
            return (float) $course['discounted_price'];
        }

        return isset($course['price']) ? (float) $course['price'] : 0.0;
    }

    protected function get_subscription_plan($plan_id)
    {
        if (!$this->safe_positive_int($plan_id)) {
            return array();
        }

        $query = $this->db
            ->where('id', (int) $plan_id)
            ->where('is_active', 1)
            ->where('is_purchasable', 1)
            ->get('youngo_subscription_plans', 1);

        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    protected function checkout_amount_for_plan($plan)
    {
        if (empty($plan) || !is_array($plan)) {
            return 0.0;
        }

        return isset($plan['price']) ? (float) $plan['price'] : 0.0;
    }

    protected function safe_course_summary($course)
    {
        if (empty($course) || !is_array($course)) {
            return array();
        }

        return array(
            'id' => isset($course['id']) ? (int) $course['id'] : null,
            'title' => isset($course['title']) ? (string) $course['title'] : '',
            'status' => isset($course['status']) ? (string) $course['status'] : '',
            'youngo_access_mode' => isset($course['youngo_access_mode']) ? (string) $course['youngo_access_mode'] : '',
            'is_free_course' => !empty($course['is_free_course']),
            'amount' => number_format($this->checkout_amount_for_course($course), 2, '.', ''),
            'currency' => 'EGP',
        );
    }

    protected function safe_status_context($order_reference)
    {
        return array(
            'order_reference' => $this->safe_reference($order_reference),
            'provider' => 'paymob',
            'mode' => $this->paymob_config->get_mode(),
            'currency' => $this->paymob_config->get_currency(),
            'db_writes' => 'disabled_for_status_and_return',
            'paymob_network' => $this->paymob_config->is_network_enabled() ? 'sandbox_gated' : 'disabled',
        );
    }

    protected function load_paymob_dashboard_config()
    {
        $this->paymob_dashboard_config = array();
        $this->paymob_dashboard_config_exists = false;

        if (!isset($this->youngo_payment_config_model)) {
            return;
        }

        $result = $this->youngo_payment_config_model->get_provider_config('paymob', 'sandbox');
        if (!empty($result['ok']) && !empty($result['data']['config']) && !empty($result['data']['exists'])) {
            $this->paymob_dashboard_config = $result['data']['config'];
            $this->paymob_dashboard_config_exists = true;
        }
    }

    protected function start_paymob_sandbox_intention($order, $course, $learner_id, $reused = false)
    {
        if (empty($order) || empty($order['id'])) {
            return $this->render_order_page($this->failure_result('order_not_found', 'The checkout order could not be loaded for Paymob sandbox start.'), array(), $course, 'start');
        }

        $customer = $this->safe_customer_for_paymob($learner_id);
        $intention = $this->paymob_adapter->create_sandbox_intention($order, $customer);
        if (empty($intention['ok'])) {
            return $this->render_order_page($intention, $order, $course, 'start');
        }

        $mark = $this->youngo_checkout_model->mark_awaiting_webhook_from_paymob_intention((int) $order['id'], $intention);
        if (empty($mark['ok'])) {
            return $this->render_order_page($mark, $order, $course, 'start');
        }

        $updated_order = $this->youngo_checkout_model->get_order((int) $order['id']);
        $notice = array(
            'ok' => true,
            'code' => $reused ? 'paymob_sandbox_intention_reused_order_created' : 'paymob_sandbox_intention_created',
            'message' => 'Paymob sandbox checkout is ready. Return URL remains UX-only and payment status depends on verified webhook processing.',
            'data' => array(
                'checkout_url' => isset($intention['data']['checkout_url']) ? $intention['data']['checkout_url'] : null,
                'provider_intent_id' => isset($intention['data']['provider_intent_id']) ? $intention['data']['provider_intent_id'] : null,
                'provider_order_id' => isset($intention['data']['provider_order_id']) ? $intention['data']['provider_order_id'] : null,
            ),
        );

        return $this->render_order_page($notice, $updated_order, $course, 'start');
    }

    protected function safe_customer_for_paymob($learner_id)
    {
        $user = $this->db
            ->select('first_name, last_name, email')
            ->where('id', (int) $learner_id)
            ->get('users', 1)
            ->row_array();

        if (empty($user)) {
            return array();
        }

        return array(
            'first_name' => isset($user['first_name']) ? (string) $user['first_name'] : 'YounGo',
            'last_name' => isset($user['last_name']) ? (string) $user['last_name'] : 'Learner',
            'email' => isset($user['email']) ? (string) $user['email'] : 'learner@example.invalid',
            'phone_number' => 'NA',
        );
    }

    protected function safe_checkout_url($url)
    {
        $url = trim((string) $url);
        return $url !== ''
            && filter_var($url, FILTER_VALIDATE_URL)
            && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https'
            && strtolower((string) parse_url($url, PHP_URL_HOST)) === 'accept.paymob.com';
    }

    protected function current_instapay_language()
    {
        $language = strtolower(trim((string) $this->session->userdata('language')));
        return in_array($language, array('arabic', 'ar'), true) ? 'arabic' : 'english';
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

    protected function safe_positive_int($value)
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    protected function safe_reference($value)
    {
        $value = trim((string) $value);
        $value = preg_replace('/[^A-Za-z0-9_\\-]/', '', $value);
        return strlen($value) > 80 ? substr($value, 0, 80) : $value;
    }

    protected function safe_context($value)
    {
        $value = trim((string) $value);
        return preg_match('/^[a-z_]+$/', $value) ? $value : 'checkout';
    }

    protected function coupon_action_error_message($result)
    {
        if (isset($result['errors']['coupon_result']['message']) && $result['errors']['coupon_result']['message'] !== '') {
            return (string) $result['errors']['coupon_result']['message'];
        }

        if (isset($result['message']) && $result['message'] !== '') {
            return (string) $result['message'];
        }

        return get_phrase('Coupon could not be updated.');
    }

    protected function failure_result($code, $message)
    {
        return array(
            'ok' => false,
            'code' => $code,
            'message' => $message,
        );
    }

    protected function json_response($http_status, $body)
    {
        $this->output
            ->set_status_header((int) $http_status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($body, JSON_UNESCAPED_SLASHES));

        return;
    }
}
