<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Youngo_checkout_coupon_usage extends CI_Controller
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
        $this->load->model('Youngo_checkout_model', 'youngo_checkout_model');

        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');

        $this->user_model->check_session_data('admin');
        $this->require_root_admin();
    }

    public function index()
    {
        if ($this->request_method() !== 'GET') {
            $this->output->set_status_header(405);
            $this->session->set_flashdata('error_message', 'Invalid request method.');
            redirect(site_url('admin/youngo/checkout-coupon-usage'), 'refresh');
        }

        $filter = $this->safe_filter($this->input->get('filter', true));
        $page_data = array(
            'page_name' => 'youngo_checkout_coupon_usage',
            'page_title' => 'Coupon Checkout Usage',
            'usage_filter' => $filter,
            'usage_counts' => $this->youngo_checkout_model->count_admin_coupon_checkout_usage_by_filter(),
            'usage_rows' => $this->youngo_checkout_model->get_admin_coupon_checkout_usage($filter, 200, 0),
            'allowed_filters' => array('all', 'zero_amount', 'course', 'subscription'),
        );

        $this->load->view('backend/index', $page_data);
    }

    protected function require_root_admin()
    {
        if (!function_exists('youngo_is_root_admin') || !youngo_is_root_admin($this->current_user_id())) {
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

    protected function safe_filter($filter)
    {
        $filter = strtolower(trim((string) $filter));
        return in_array($filter, array('all', 'zero_amount', 'course', 'subscription'), true) ? $filter : 'all';
    }
}
