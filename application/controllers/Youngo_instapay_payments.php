<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Youngo_instapay_payments extends CI_Controller
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
        $this->load->model('Youngo_instapay_payment_model', 'youngo_instapay_payment_model');

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
            redirect(site_url('admin/youngo/instapay-payments'), 'refresh');
        }

        $status = $this->safe_status_filter($this->input->get('status', true));
        $page_data = array(
            'page_name' => 'youngo_instapay_payments',
            'page_title' => 'Manual Instapay Payments',
            'status_filter' => $status === null ? 'all' : $status,
            'status_counts' => $this->youngo_instapay_payment_model->count_admin_review_by_status(),
            'submissions' => $this->youngo_instapay_payment_model->get_admin_review_list($status, 100, 0),
            'allowed_statuses' => $this->youngo_instapay_payment_model->allowed_statuses(),
        );

        $this->load->view('backend/index', $page_data);
    }

    public function view($submission_id = null)
    {
        if ($this->request_method() !== 'GET') {
            $this->output->set_status_header(405);
            $this->session->set_flashdata('error_message', 'Invalid request method.');
            redirect(site_url('admin/youngo/instapay-payments'), 'refresh');
        }

        $submission_id = $this->valid_submission_id_or_redirect($submission_id);
        $detail = $this->youngo_instapay_payment_model->get_admin_review_detail($submission_id);
        if (empty($detail)) {
            $this->session->set_flashdata('error_message', 'Manual Instapay submission was not found.');
            redirect(site_url('admin/youngo/instapay-payments'), 'refresh');
        }

        $page_data = array(
            'page_name' => 'youngo_instapay_payment_view',
            'page_title' => 'Manual Instapay Payment Review',
            'detail' => $detail,
            'allowed_statuses' => $this->youngo_instapay_payment_model->allowed_statuses(),
        );

        $this->load->view('backend/index', $page_data);
    }

    public function evidence($submission_id = null, $mode = 'preview')
    {
        if ($this->request_method() !== 'GET') {
            $this->output->set_status_header(405);
            return;
        }

        $submission_id = $this->safe_positive_int($submission_id);
        if (!$submission_id) {
            $this->output->set_status_header(404);
            return;
        }

        $file = $this->youngo_instapay_payment_model->get_evidence_file_for_admin($submission_id);
        if (empty($file['ok']) || empty($file['data']['absolute_path'])) {
            $this->output->set_status_header(404);
            return;
        }

        $data = $file['data'];
        $disposition = $mode === 'download' ? 'attachment' : 'inline';
        $download_name = $this->safe_header_filename(isset($data['download_name']) ? $data['download_name'] : ('instapay-evidence-' . $submission_id));

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $data['mime']);
        header('Content-Length: ' . (int) $data['size']);
        header('Content-Disposition: ' . $disposition . '; filename="' . $download_name . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        readfile($data['absolute_path']);
        exit;
    }

    public function approve($submission_id = null)
    {
        $this->require_post();

        $submission_id = $this->valid_submission_id_or_redirect($submission_id);
        $confirmed = $this->input->post('external_payment_confirmed', true);
        if ((string) $confirmed !== '1') {
            $this->session->set_flashdata('error_message', 'Confirm that the Instapay payment was received externally before approval.');
            redirect(site_url('admin/youngo/instapay-payments/' . $submission_id), 'refresh');
        }

        $result = $this->youngo_instapay_payment_model->approve_submission(
            $submission_id,
            $this->current_user_id(),
            $this->input->post('admin_note', false)
        );

        if (!empty($result['ok'])) {
            $this->session->set_flashdata('flash_message', $this->result_message($result));
        } else {
            $this->session->set_flashdata('error_message', $this->result_message($result));
        }

        redirect(site_url('admin/youngo/instapay-payments/' . $submission_id), 'refresh');
    }

    public function reject($submission_id = null)
    {
        $this->require_post();

        $submission_id = $this->valid_submission_id_or_redirect($submission_id);
        $result = $this->youngo_instapay_payment_model->reject_submission(
            $submission_id,
            $this->current_user_id(),
            $this->input->post('admin_note', false)
        );

        if (!empty($result['ok'])) {
            $this->session->set_flashdata('flash_message', $this->result_message($result));
        } else {
            $this->session->set_flashdata('error_message', $this->result_message($result));
        }

        redirect(site_url('admin/youngo/instapay-payments/' . $submission_id), 'refresh');
    }

    protected function require_root_admin()
    {
        if (!function_exists('youngo_is_root_admin') || !youngo_is_root_admin($this->current_user_id())) {
            $this->session->set_flashdata('error_message', 'Access denied.');
            redirect(site_url('admin/dashboard'), 'refresh');
        }
    }

    protected function require_post()
    {
        if ($this->request_method() !== 'POST') {
            $this->output->set_status_header(405);
            $this->session->set_flashdata('error_message', 'Invalid request method.');
            redirect(site_url('admin/youngo/instapay-payments'), 'refresh');
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

    protected function safe_status_filter($status)
    {
        $status = trim((string) $status);
        if ($status === '' || $status === 'all') {
            return null;
        }

        return $this->youngo_instapay_payment_model->normalize_status($status);
    }

    protected function valid_submission_id_or_redirect($submission_id)
    {
        $submission_id = $this->safe_positive_int($submission_id);
        if (!$submission_id) {
            $this->session->set_flashdata('error_message', 'A valid Instapay submission is required.');
            redirect(site_url('admin/youngo/instapay-payments'), 'refresh');
        }

        return $submission_id;
    }

    protected function safe_positive_int($value)
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    protected function safe_header_filename($filename)
    {
        $filename = preg_replace('/[^A-Za-z0-9_.-]+/', '-', (string) $filename);
        $filename = trim($filename, '.-');

        return $filename !== '' ? substr($filename, 0, 120) : 'instapay-evidence';
    }

    protected function result_message($result)
    {
        if (is_array($result) && isset($result['message']) && trim((string) $result['message']) !== '') {
            return (string) $result['message'];
        }

        return 'Manual Instapay review action could not be completed.';
    }
}
