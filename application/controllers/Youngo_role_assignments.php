<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Youngo_role_assignments extends CI_Controller
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
        $this->load->model('Youngo_role_assignment_model', 'youngo_role_assignment_model');

        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');

        $this->user_model->check_session_data('admin');
        if (!function_exists('youngo_require_capability')) {
            $this->session->set_flashdata('error_message', 'Access denied.');
            redirect(site_url('admin/dashboard'), 'refresh');
        }

        youngo_require_capability('manage_roles', array('redirect_to' => 'admin/dashboard'));
    }

    public function index()
    {
        $page_data = array(
            'page_name' => 'youngo_role_assignments',
            'page_title' => 'YounGo Role Assignments',
            'users' => $this->youngo_role_assignment_model->list_role_assignment_rows(),
            'capability_status' => $this->youngo_role_assignment_model->get_required_capability_status(),
        );
        $this->load->view('backend/index', $page_data);
    }

    public function update()
    {
        $this->require_post();

        $target_user_id = $this->input->post('user_id', true);
        $requested_roles = $this->input->post('roles', true);
        $result = $this->youngo_role_assignment_model->update_assignments(
            $target_user_id,
            is_array($requested_roles) ? $requested_roles : array(),
            $this->current_user_id()
        );

        if ($this->is_ajax()) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($result));
            return;
        }

        if (!empty($result['ok'])) {
            $this->session->set_flashdata('flash_message', 'Role assignments updated.');
        } else {
            $this->session->set_flashdata('error_message', $this->result_message($result));
        }

        redirect(site_url('admin/youngo/role-assignments'), 'refresh');
    }

    protected function require_post()
    {
        if (!$this->is_post()) {
            $this->session->set_flashdata('error_message', 'Invalid request method.');
            redirect(site_url('admin/youngo/role-assignments'), 'refresh');
        }
    }

    protected function is_post()
    {
        return isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';
    }

    protected function is_ajax()
    {
        return isset($this->input) && method_exists($this->input, 'is_ajax_request') && $this->input->is_ajax_request();
    }

    protected function current_user_id()
    {
        return (int) $this->session->userdata('user_id');
    }

    protected function result_message($result)
    {
        return !empty($result['message']) ? $result['message'] : 'Role assignment update failed.';
    }
}
