<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Youngo_manual_grants extends CI_Controller
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
        if (file_exists(APPPATH . 'helpers/youngo_entitlement_helper.php')) {
            $this->load->helper('youngo_entitlement');
        }
        $this->load->model('Youngo_entitlement_write_model', 'youngo_entitlement_write_model');
        $this->load->model('Youngo_entitlement_model', 'youngo_entitlement_model');

        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');

        $this->user_model->check_session_data('admin');
        if (!function_exists('youngo_require_capability')) {
            $this->session->set_flashdata('error_message', 'Access denied.');
            redirect(site_url('admin/dashboard'), 'refresh');
        }

        youngo_require_capability('grant_manual_access', array('redirect_to' => 'admin/dashboard'));
    }

    public function index()
    {
        $filters = $this->list_filters();
        $page_data = $this->base_page_data('youngo_manual_grants', 'YounGo Manual Grants');
        $page_data['grants'] = $this->list_manual_grants($filters);
        $page_data['filters'] = $filters;
        $page_data['users'] = $this->list_receiver_users();
        $page_data['courses'] = $this->list_active_courses();
        $page_data['plans'] = $this->list_eligible_plans();
        $this->load->view('backend/index', $page_data);
    }

    public function create()
    {
        if ($this->is_post()) {
            $grant_type = $this->input->post('grant_type', true);
            if ($grant_type === 'course') {
                $result = $this->youngo_entitlement_write_model->grant_course_access($this->course_grant_input(), $this->current_user_id());
            } elseif ($grant_type === 'subscription') {
                $result = $this->youngo_entitlement_write_model->grant_subscription($this->subscription_grant_input(), $this->current_user_id());
            } else {
                $result = $this->service_failure('invalid_grant_type', 'Select a valid grant type.');
            }

            if (!empty($result['ok'])) {
                $grant_id = isset($result['ids']['manual_grant_id']) ? (int) $result['ids']['manual_grant_id'] : 0;
                $this->session->set_flashdata('flash_message', 'Manual grant created successfully.');
                redirect(site_url('admin/youngo/manual-grants/' . $grant_id), 'refresh');
            }

            $this->session->set_flashdata('error_message', $this->result_message($result));
            redirect(site_url('admin/youngo/manual-grants/create'), 'refresh');
        }

        $page_data = $this->base_page_data('youngo_manual_grant_form', 'Create Manual Grant');
        $page_data['users'] = $this->list_receiver_users();
        $page_data['courses'] = $this->list_active_courses();
        $page_data['plans'] = $this->list_eligible_plans();
        $page_data['schema_readiness'] = $this->youngo_entitlement_write_model->get_schema_readiness();
        $this->load->view('backend/index', $page_data);
    }

    public function view($grant_id = null)
    {
        $grant_id = $this->valid_grant_id_or_redirect($grant_id);
        $detail = $this->get_manual_grant_detail($grant_id);
        if (empty($detail['grant'])) {
            $this->session->set_flashdata('error_message', 'Manual grant was not found.');
            redirect(site_url('admin/youngo/manual-grants'), 'refresh');
        }

        $page_data = $this->base_page_data('youngo_manual_grant_view', 'Manual Grant Details');
        $page_data['detail'] = $detail;
        $page_data['schema_readiness'] = $this->youngo_entitlement_write_model->get_schema_readiness();
        $this->load->view('backend/index', $page_data);
    }

    public function revoke($grant_id = null)
    {
        $this->require_post();
        $grant_id = $this->valid_grant_id_or_redirect($grant_id);
        $detail = $this->get_manual_grant_detail($grant_id);
        if (empty($detail['grant'])) {
            $this->session->set_flashdata('error_message', 'Manual grant was not found.');
            redirect(site_url('admin/youngo/manual-grants'), 'refresh');
        }

        $grant = $detail['grant'];
        if ($this->row_is_revoked($grant)) {
            $this->session->set_flashdata('error_message', 'This manual grant is already revoked.');
            redirect(site_url('admin/youngo/manual-grants/' . $grant_id), 'refresh');
        }

        $note = $this->input->post('revoke_note', true);
        if ($grant['grant_type'] === 'course') {
            if (empty($detail['course_access']['id'])) {
                $result = $this->service_failure('linked_course_access_missing', 'Linked course access record was not found.');
            } else {
                $result = $this->youngo_entitlement_write_model->revoke_course_access((int) $detail['course_access']['id'], $this->current_user_id(), $note);
            }
        } elseif ($grant['grant_type'] === 'subscription') {
            if (empty($detail['subscription']['id'])) {
                $result = $this->service_failure('linked_subscription_missing', 'Linked subscription record was not found.');
            } else {
                $result = $this->youngo_entitlement_write_model->revoke_subscription((int) $detail['subscription']['id'], $this->current_user_id(), $note);
            }
        } else {
            $result = $this->service_failure('invalid_grant_type', 'This manual grant cannot be revoked by this action.');
        }

        if (!empty($result['ok'])) {
            $this->session->set_flashdata('flash_message', 'Manual grant revoked successfully.');
        } else {
            $this->session->set_flashdata('error_message', $this->result_message($result));
        }

        redirect(site_url('admin/youngo/manual-grants/' . $grant_id), 'refresh');
    }

    protected function base_page_data($page_name, $page_title)
    {
        return array(
            'page_name' => $page_name,
            'page_title' => $page_title,
        );
    }

    protected function course_grant_input()
    {
        $access_type = $this->input->post('course_access_type', true);
        return array(
            'user_id' => $this->input->post('user_id', true),
            'course_id' => $this->input->post('course_id', true),
            'source' => 'manual',
            'is_lifetime' => $access_type === 'timed' ? 0 : 1,
            'start_date' => $this->input->post('start_date', true),
            'expiry_date' => $this->input->post('expiry_date', true),
            'duration_days' => $this->input->post('duration_days', true),
            'note' => $this->input->post('note', false),
        );
    }

    protected function subscription_grant_input()
    {
        return array(
            'user_id' => $this->input->post('user_id', true),
            'plan_id' => $this->input->post('plan_id', true),
            'source' => 'manual',
            'is_lifetime' => 0,
            'start_date' => $this->input->post('start_date', true),
            'expiry_date' => $this->input->post('expiry_date', true),
            'duration_days' => $this->input->post('duration_days', true),
            'note' => $this->input->post('note', false),
        );
    }

    protected function list_filters()
    {
        return array(
            'grant_type' => $this->safe_filter($this->input->get('grant_type', true), array('course', 'subscription')),
            'status' => $this->safe_filter($this->input->get('status', true), array('active', 'revoked')),
            'user_id' => $this->valid_id_or_zero($this->input->get('user_id', true)),
            'course_id' => $this->valid_id_or_zero($this->input->get('course_id', true)),
            'plan_id' => $this->valid_id_or_zero($this->input->get('plan_id', true)),
        );
    }

    protected function list_manual_grants($filters)
    {
        if (!$this->db->table_exists('youngo_manual_grants')) {
            return array();
        }

        $this->db->select('g.*, receiver.first_name AS receiver_first_name, receiver.last_name AS receiver_last_name, receiver.email AS receiver_email');
        $this->db->select('actor.first_name AS actor_first_name, actor.last_name AS actor_last_name, actor.email AS actor_email');
        $this->db->select('course.title AS course_title, plan.name AS plan_name, plan.slug AS plan_slug');
        $this->db->from('youngo_manual_grants g');
        $this->db->join('users receiver', 'receiver.id = g.granted_to_user_id', 'left');
        $this->db->join('users actor', 'actor.id = g.granted_by_user_id', 'left');
        $this->db->join('course', 'course.id = g.course_id', 'left');
        $this->db->join('youngo_subscription_plans plan', 'plan.id = g.plan_id', 'left');

        if (!empty($filters['grant_type'])) {
            $this->db->where('g.grant_type', $filters['grant_type']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('g.status', $filters['status']);
        }
        if (!empty($filters['user_id'])) {
            $this->db->where('g.granted_to_user_id', (int) $filters['user_id']);
        }
        if (!empty($filters['course_id'])) {
            $this->db->where('g.course_id', (int) $filters['course_id']);
        }
        if (!empty($filters['plan_id'])) {
            $this->db->where('g.plan_id', (int) $filters['plan_id']);
        }

        $this->db->order_by('g.id', 'DESC');
        $query = $this->db->get();
        return $query ? $query->result_array() : array();
    }

    protected function get_manual_grant_detail($grant_id)
    {
        $grant = $this->get_joined_grant($grant_id);
        if (empty($grant)) {
            return array();
        }

        $course_access = array();
        $subscription = array();
        $read_state = array();
        if ($grant['grant_type'] === 'course') {
            $course_access = $this->get_child_row('youngo_course_access', 'manual_grant_id', $grant_id);
            if (function_exists('youngo_get_course_access_state') && !empty($grant['course_id'])) {
                $read_state = youngo_get_course_access_state((int) $grant['granted_to_user_id'], (int) $grant['course_id'], array(
                    'allow_admin_bypass' => false,
                    'allow_instructor_bypass' => false,
                ));
            }
        } elseif ($grant['grant_type'] === 'subscription') {
            $subscription = $this->get_child_row('youngo_user_subscriptions', 'manual_grant_id', $grant_id);
            if (method_exists($this->youngo_entitlement_model, 'get_active_subscription_state')) {
                $read_state = $this->youngo_entitlement_model->get_active_subscription_state((int) $grant['granted_to_user_id']);
            }
        }

        return array(
            'grant' => $grant,
            'course_access' => $course_access,
            'subscription' => $subscription,
            'read_state' => $read_state,
        );
    }

    protected function get_joined_grant($grant_id)
    {
        if (!$this->db->table_exists('youngo_manual_grants')) {
            return array();
        }

        $this->db->select('g.*, receiver.first_name AS receiver_first_name, receiver.last_name AS receiver_last_name, receiver.email AS receiver_email');
        $this->db->select('actor.first_name AS actor_first_name, actor.last_name AS actor_last_name, actor.email AS actor_email');
        $this->db->select('revoker.first_name AS revoker_first_name, revoker.last_name AS revoker_last_name, revoker.email AS revoker_email');
        $this->db->select('course.title AS course_title, plan.name AS plan_name, plan.slug AS plan_slug, plan.currency AS plan_currency');
        $this->db->from('youngo_manual_grants g');
        $this->db->join('users receiver', 'receiver.id = g.granted_to_user_id', 'left');
        $this->db->join('users actor', 'actor.id = g.granted_by_user_id', 'left');
        $this->db->join('users revoker', 'revoker.id = g.revoked_by_user_id', 'left');
        $this->db->join('course', 'course.id = g.course_id', 'left');
        $this->db->join('youngo_subscription_plans plan', 'plan.id = g.plan_id', 'left');
        $this->db->where('g.id', (int) $grant_id);
        $query = $this->db->get('', 1);
        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    protected function list_receiver_users()
    {
        if (!$this->db->table_exists('users')) {
            return array();
        }

        $this->db->select('id, first_name, last_name, email, role_id, status');
        $this->db->from('users');
        $this->db->where('status', 1);
        $this->db->where('id !=', 1);
        $this->db->order_by('first_name', 'ASC');
        $this->db->order_by('email', 'ASC');
        $query = $this->db->get();
        return $query ? $query->result_array() : array();
    }

    protected function list_active_courses()
    {
        if (!$this->db->table_exists('course')) {
            return array();
        }

        $this->db->select('id, title, status');
        $this->db->from('course');
        $this->db->where('status', 'active');
        $this->db->order_by('title', 'ASC');
        $query = $this->db->get();
        return $query ? $query->result_array() : array();
    }

    protected function list_eligible_plans()
    {
        if (!$this->db->table_exists('youngo_subscription_plans')) {
            return array();
        }

        $this->db->select('id, name, slug, duration_days, price, currency, is_active, is_purchasable, archived_at');
        $this->db->from('youngo_subscription_plans');
        $this->db->where('currency', 'EGP');
        $this->db->group_start();
        $this->db->where('archived_at IS NULL', null, false);
        $this->db->or_where('archived_at', 0);
        $this->db->group_end();
        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('id', 'ASC');
        $query = $this->db->get();
        return $query ? $query->result_array() : array();
    }

    protected function get_child_row($table, $field, $value)
    {
        if (!$this->safe_identifier($table) || !$this->safe_identifier($field) || !$this->db->table_exists($table)) {
            return array();
        }

        $query = $this->db->where($field, (int) $value)->order_by('id', 'DESC')->get($table, 1);
        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    protected function valid_grant_id_or_redirect($grant_id)
    {
        if (!is_numeric($grant_id) || (int) $grant_id <= 0) {
            $this->session->set_flashdata('error_message', 'Invalid manual grant ID.');
            redirect(site_url('admin/youngo/manual-grants'), 'refresh');
        }

        return (int) $grant_id;
    }

    protected function require_post()
    {
        if (!$this->is_post()) {
            $this->session->set_flashdata('error_message', 'Invalid request method.');
            redirect(site_url('admin/youngo/manual-grants'), 'refresh');
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

    protected function result_message($result)
    {
        if (!empty($result['errors']) && is_array($result['errors'])) {
            return implode(' ', $result['errors']);
        }

        return !empty($result['message']) ? $result['message'] : 'Manual grant action failed.';
    }

    protected function service_failure($code, $message)
    {
        return array(
            'ok' => false,
            'code' => $code,
            'message' => $message,
            'ids' => array(),
            'errors' => array(),
        );
    }

    protected function safe_filter($value, $allowed)
    {
        $value = trim((string) $value);
        return in_array($value, $allowed, true) ? $value : '';
    }

    protected function valid_id_or_zero($value)
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : 0;
    }

    protected function row_is_revoked($row)
    {
        return !empty($row['revoked_at']) || (isset($row['status']) && $row['status'] === 'revoked');
    }

    protected function safe_identifier($value)
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9_]+$/', $value);
    }
}
