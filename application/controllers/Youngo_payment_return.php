<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!class_exists('Youngo_paymob_config') && is_file(APPPATH . 'libraries/Youngo_paymob_config.php')) {
    require_once APPPATH . 'libraries/Youngo_paymob_config.php';
}

class Youngo_payment_return extends CI_Controller
{
    protected $paymob_config;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->paymob_config = new Youngo_paymob_config();

        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }

    public function paymob()
    {
        if ($this->request_method() !== 'GET') {
            $this->output->set_status_header(405);
            $this->output
                ->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode(array(
                    'ok' => false,
                    'status' => 'disabled',
                    'code' => 'method_not_allowed',
                    'message' => 'YounGo Paymob return accepts GET only and does not process payments.',
                ), JSON_UNESCAPED_SLASHES));
            return;
        }

        $page_data = array(
            'page_name' => null,
            'page_title' => get_phrase('YounGo payment return'),
            'path' => APPPATH . 'views/frontend/youngo/payment_return_disabled.php',
            'youngo_paymob_return_context' => array(
                'code' => 'paymob_return_disabled_no_write',
                'message' => 'Paymob return URL is pending and disabled. Verified webhook processing will be the source of truth in a later approved phase.',
                'mode' => $this->paymob_config->get_mode(),
                'currency' => $this->paymob_config->get_currency(),
                'query_params_trusted' => false,
                'marks_paid' => false,
                'issues_access' => false,
                'legacy_payment_writes' => false,
            ),
        );

        $this->output->set_status_header(200);
        $this->load->view('frontend/youngo/index', $page_data);
    }

    protected function request_method()
    {
        return isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
    }
}
