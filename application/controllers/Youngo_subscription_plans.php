<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Youngo_subscription_plans extends CI_Controller
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
        $this->load->model('Youngo_subscription_model', 'youngo_subscription_model');

        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');

        $this->user_model->check_session_data('admin');
        if (!function_exists('youngo_require_capability')) {
            $this->session->set_flashdata('error_message', 'Access denied.');
            redirect(site_url('admin/dashboard'), 'refresh');
        }

        youngo_require_capability('manage_subscriptions', array('redirect_to' => 'admin/dashboard'));
    }

    public function index()
    {
        $page_data = $this->base_page_data('youngo_subscription_plans', 'YounGo Subscription Plans');
        $page_data['plans'] = $this->youngo_subscription_model->list_plans(true);
        $page_data['schema_status'] = $this->youngo_subscription_model->get_schema_status();
        $this->load->view('backend/index', $page_data);
    }

    public function create()
    {
        if ($this->is_post()) {
            $result = $this->youngo_subscription_model->create_plan($this->input->post(null, false), $this->current_user_id());
            if (!empty($result['success'])) {
                $this->session->set_flashdata('flash_message', 'Subscription plan created successfully.');
                redirect(site_url('admin/youngo/subscription-plans/' . (int) $result['plan_id']), 'refresh');
            }

            $this->session->set_flashdata('error_message', $this->result_message($result));
            redirect(site_url('admin/youngo/subscription-plans/create'), 'refresh');
        }

        $page_data = $this->base_page_data('youngo_subscription_plan_form', 'Create Subscription Plan');
        $page_data['mode'] = 'create';
        $page_data['plan'] = array();
        $page_data['plan_translations'] = array();
        $page_data['dependency_counts'] = array();
        $page_data['schema_status'] = $this->youngo_subscription_model->get_schema_status();
        $this->load->view('backend/index', $page_data);
    }

    public function view($plan_id = null)
    {
        $plan_id = $this->valid_plan_id_or_redirect($plan_id);
        $plan = $this->youngo_subscription_model->get_plan($plan_id);
        if (empty($plan)) {
            $this->session->set_flashdata('error_message', 'Subscription plan was not found.');
            redirect(site_url('admin/youngo/subscription-plans'), 'refresh');
        }

        $page_data = $this->base_page_data('youngo_subscription_plan_view', 'Subscription Plan Details');
        $page_data['plan'] = $plan;
        $page_data['dependency_counts'] = $this->youngo_subscription_model->get_dependency_counts($plan_id);
        $page_data['audit_history'] = $this->youngo_subscription_model->get_audit_history($plan_id);
        $page_data['schema_status'] = $this->youngo_subscription_model->get_schema_status();
        $this->load->view('backend/index', $page_data);
    }

    public function edit($plan_id = null)
    {
        $plan_id = $this->valid_plan_id_or_redirect($plan_id);
        $plan = $this->youngo_subscription_model->get_plan($plan_id);
        if (empty($plan)) {
            $this->session->set_flashdata('error_message', 'Subscription plan was not found.');
            redirect(site_url('admin/youngo/subscription-plans'), 'refresh');
        }

        if ($this->is_post()) {
            $result = $this->youngo_subscription_model->update_plan($plan_id, $this->input->post(null, false), $this->current_user_id());
            if (!empty($result['success'])) {
                $this->session->set_flashdata('flash_message', 'Subscription plan updated successfully.');
                redirect(site_url('admin/youngo/subscription-plans/' . $plan_id), 'refresh');
            }

            $this->session->set_flashdata('error_message', $this->result_message($result));
            redirect(site_url('admin/youngo/subscription-plans/' . $plan_id . '/edit'), 'refresh');
        }

        $page_data = $this->base_page_data('youngo_subscription_plan_form', 'Edit Subscription Plan');
        $page_data['mode'] = 'edit';
        $page_data['plan'] = $plan;
        $page_data['plan_translations'] = $this->youngo_subscription_model->get_plan_translations($plan_id);
        $page_data['dependency_counts'] = $this->youngo_subscription_model->get_dependency_counts($plan_id);
        $page_data['schema_status'] = $this->youngo_subscription_model->get_schema_status();
        $this->load->view('backend/index', $page_data);
    }

    public function status($plan_id = null)
    {
        $this->require_post();
        $plan_id = $this->valid_plan_id_or_redirect($plan_id);

        $action = $this->input->post('status_action', true);
        $result = $this->youngo_subscription_model->set_status($plan_id, $action, $this->current_user_id());
        $this->flash_result($result, 'Subscription plan status updated.');
        redirect(site_url('admin/youngo/subscription-plans/' . $plan_id), 'refresh');
    }

    public function archive($plan_id = null)
    {
        $this->require_post();
        $plan_id = $this->valid_plan_id_or_redirect($plan_id);

        $result = $this->youngo_subscription_model->archive_plan($plan_id, $this->current_user_id());
        $this->flash_result($result, 'Subscription plan archived.');
        redirect(site_url('admin/youngo/subscription-plans/' . $plan_id), 'refresh');
    }

    public function restore($plan_id = null)
    {
        $this->require_post();
        $plan_id = $this->valid_plan_id_or_redirect($plan_id);

        $result = $this->youngo_subscription_model->restore_plan($plan_id, $this->current_user_id());
        $this->flash_result($result, 'Subscription plan restored.');
        redirect(site_url('admin/youngo/subscription-plans/' . $plan_id), 'refresh');
    }

    protected function base_page_data($page_name, $page_title)
    {
        return array(
            'page_name' => $page_name,
            'page_title' => $page_title,
            'currency_readiness' => $this->youngo_subscription_model->get_currency_readiness(),
        );
    }

    protected function valid_plan_id_or_redirect($plan_id)
    {
        if (!is_numeric($plan_id) || (int) $plan_id <= 0) {
            $this->session->set_flashdata('error_message', 'Invalid subscription plan ID.');
            redirect(site_url('admin/youngo/subscription-plans'), 'refresh');
        }

        return (int) $plan_id;
    }

    protected function require_post()
    {
        if (!$this->is_post()) {
            $this->session->set_flashdata('error_message', 'Invalid request method.');
            redirect(site_url('admin/youngo/subscription-plans'), 'refresh');
        }
    }

    protected function is_post()
    {
        return isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';
    }

    protected function current_user_id()
    {
        return (int) $this->session->userdata('user_id');
    }

    protected function flash_result($result, $success_message)
    {
        if (!empty($result['success'])) {
            $this->session->set_flashdata('flash_message', $success_message);
            return;
        }

        $this->session->set_flashdata('error_message', $this->result_message($result));
    }

    protected function result_message($result)
    {
        if (!empty($result['errors']) && is_array($result['errors'])) {
            return implode(' ', $result['errors']);
        }

        return !empty($result['message']) ? $result['message'] : 'Subscription plan action failed.';
    }
}
