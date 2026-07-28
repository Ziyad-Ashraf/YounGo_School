<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sign_up extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        
        date_default_timezone_set(get_settings('timezone'));

        // Your own constructor code
        $this->load->database();
        $this->load->library('session');
        /*cache control*/
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');

        //Check custom session data
        $this->user_model->check_session_data();
    }

    private function youngo_public_auth_page_title($phrase_key, $fallback = '')
    {
        if (file_exists(APPPATH . 'helpers/youngo_frontend_language_helper.php')) {
            $this->load->helper('youngo_frontend_language');
            if (function_exists('youngo_frontend_active_language') && function_exists('youngo_frontend_phrase')) {
                $language = youngo_frontend_active_language($this->uri->uri_string());
                $title = youngo_frontend_phrase($phrase_key, $fallback, $language);
                if (trim((string) $title) !== '') {
                    return $title;
                }
            }
        }

        return site_phrase($phrase_key);
    }

    public function index()
    {
         if (get_settings('public_signup') != 'enable') {
             redirect(site_url(), 'refresh');
            return;
        }

        if ($this->session->userdata('admin_login')) {
            redirect(site_url('admin'), 'refresh');
        } elseif ($this->session->userdata('user_login')) {
            redirect(site_url('user'), 'refresh');
        }
        $page_data['page_name'] = 'sign_up';
        $page_data['page_title'] = $this->youngo_public_auth_page_title('sign_up', 'Sign up');
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    public function verification_code()
    {
        if (!$this->session->userdata('register_email')) {
            redirect(site_url('sign_up'), 'refresh');
        }
        $page_data['page_name'] = "verification_code";
        $page_data['page_title'] = $this->youngo_public_auth_page_title('verification_code', 'Verification code');
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

}
