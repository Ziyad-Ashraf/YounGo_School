<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Blog extends CI_Controller
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


        $this->user_model->check_session_data();       
    }

    function index(){
        $page_data['popular_blogs'] = $this->crud_model->get_popular_blogs(6);
        $page_data['latest_blogs'] = $this->crud_model->get_latest_blogs(6);
        $page_data['included_page'] = 'blog_latest_and_popular.php';
        $page_data['page_title'] = $this->youngo_frontend_public_phrase('blog');
        $page_data['page_name'] = 'blogs';
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    //all blogs
    function blogs($param1 = ''){

        $uri_segment = $param1;

        if(isset($_GET['search']) && !empty($_GET['search'])){
            $config = array();
            $this->db->like('title', $_GET['search']);
            $this->db->or_like('description', $_GET['search']);
            $this->db->where('status', 1);
            $total_rows = $this->db->get('blogs')->num_rows();
            $config = pagintaion($total_rows, 9);
            $config['reuse_query_string'] = TRUE;
            $config['base_url']  = site_url('blogs/');
            $this->pagination->initialize($config);

            $this->db->order_by('added_date', 'asc');
            $this->db->like('title', $_GET['search']);
            $this->db->or_like('description', $_GET['search']);
            $this->db->where('status', 1);
            $page_data['blogs'] = $this->db->get('blogs', $config['per_page'], $uri_segment);
            $page_data['total_rows'] = $total_rows;
            $page_data['search_string'] = $_GET['search'];
            $page_data['page_title'] = $this->youngo_frontend_public_phrase('search_result');
        }elseif(isset($_GET['category']) && !empty($_GET['category'])){
            $config = array();
            
            $blog_category_id = $this->crud_model->get_blog_category_by_slug($_GET['category'])->row('blog_category_id');
            $this->db->where('blog_category_id', $blog_category_id);
            $this->db->where('status', 1);
            $total_rows = $this->db->get('blogs')->num_rows();
            $config = pagintaion($total_rows, 9);
            $config['reuse_query_string'] = TRUE;
            $config['base_url']  = site_url('blogs/');
            $this->pagination->initialize($config);

            $this->db->order_by('added_date', 'asc');
            $this->db->where('blog_category_id', $blog_category_id);
            $this->db->where('status', 1);
            $page_data['blogs'] = $this->db->get('blogs', $config['per_page'], $uri_segment);
            $page_data['total_rows'] = $total_rows;
            $page_data['page_title'] = $this->youngo_frontend_public_phrase('search_result');
        }else{
            $config = array();
            $this->db->where('status', 1);
            $total_rows = $this->db->get('blogs')->num_rows();
            $config = pagintaion($total_rows, 9);
            $config['base_url']  = site_url('blogs/');
            $this->pagination->initialize($config);

            $this->db->order_by('added_date', 'asc');
            $this->db->where('status', 1);
            $page_data['blogs'] = $this->db->get('blogs', $config['per_page'], $uri_segment);
            $page_data['total_rows'] = $total_rows;
            $page_data['page_title'] = $this->youngo_frontend_public_phrase('blogs');
        }
        $page_data['included_page'] = 'blogs_all.php';
        $page_data['page_name'] = 'blogs';
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    function categories(){
        $page_data['included_page'] = 'blog_categories.php';
        $page_data['page_name'] = 'blogs';
        $page_data['page_title'] = $this->youngo_frontend_public_phrase('categories');
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    //blog details page
    function details($blog_slug = "", $blog_id = ""){
        $blog_row = $this->crud_model->get_all_blogs($blog_id);
        $isYoungoTheme = get_frontend_settings('theme') === 'youngo';
        
        if($blog_row->num_rows() == 0){
            $this->session->set_flashdata('error_message', $isYoungoTheme ? 'This blog is not available' : site_phrase('This blog is not available'));
            redirect($_SERVER['HTTP_REFERER'], 'refresh');
        }
        
        $blog_details = $blog_row->row_array();
        if ($isYoungoTheme) {
            $youngo_frontend_language = $this->youngo_frontend_blog_language();
            $blog_details = $this->youngo_localize_blog_details($blog_details, $youngo_frontend_language);
            $page_data['youngo_frontend_language'] = $youngo_frontend_language;
        }

        $page_data['blog_details'] = $blog_details;
        $page_data['blog_id'] = $blog_id;
        $page_data['page_name'] = 'blog_details';
        $page_data['page_title'] = $isYoungoTheme && !empty($blog_details['title']) ? $blog_details['title'] : site_phrase('blog_details');
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    private function youngo_frontend_blog_language()
    {
        if (file_exists(APPPATH . 'helpers/youngo_frontend_language_helper.php')) {
            $this->load->helper('youngo_frontend_language');
            if (function_exists('youngo_frontend_active_language')) {
                return youngo_frontend_active_language();
            }
        }

        return 'english';
    }

    private function youngo_localize_blog_details($blog_details, $language_code)
    {
        if (empty($blog_details) || !is_array($blog_details) || empty($blog_details['blog_id'])) {
            return $blog_details;
        }

        $language_code = $language_code === 'arabic' ? 'arabic' : 'english';
        $blog_id = (int) $blog_details['blog_id'];
        $candidates = array();

        $requested_translation = $this->youngo_get_blog_translation_candidate($blog_id, $language_code);
        if (!empty($requested_translation)) {
            $candidates[] = array('language' => $language_code, 'row' => $requested_translation);
        }

        if ($language_code === 'arabic') {
            $english_translation = $this->youngo_get_blog_translation_candidate($blog_id, 'english');
            if (!empty($english_translation)) {
                $candidates[] = array('language' => 'english', 'row' => $english_translation);
            }
        }

        $resolved_fields = array();
        foreach (array('title', 'description', 'excerpt') as $field) {
            foreach ($candidates as $candidate) {
                if (isset($candidate['row'][$field]) && trim((string) $candidate['row'][$field]) !== '') {
                    $blog_details[$field] = $candidate['row'][$field];
                    $resolved_fields[$field] = $candidate['language'];
                    break;
                }
            }
        }

        $title_language = isset($resolved_fields['title']) ? $resolved_fields['title'] : 'canonical';
        $blog_details['youngo_translation_requested_language'] = $language_code;
        $blog_details['youngo_translation_resolved_language'] = $title_language;
        $blog_details['youngo_translation_source'] = $title_language === 'canonical' ? 'blogs' : 'youngo_blog_translations';
        $blog_details['youngo_translation_is_fallback'] = $title_language !== $language_code;
        $blog_details['youngo_translation_fields'] = $resolved_fields;

        return $blog_details;
    }

    private function youngo_get_blog_translation_candidate($blog_id, $language_code)
    {
        if (!method_exists($this->crud_model, 'youngo_get_blog_translation')) {
            return array();
        }

        $translation = $this->crud_model->youngo_get_blog_translation($blog_id, $language_code);
        if (empty($translation) || !is_array($translation)) {
            return array();
        }

        foreach (array('title', 'description', 'excerpt') as $field) {
            if (isset($translation[$field]) && trim((string) $translation[$field]) !== '') {
                return $translation;
            }
        }

        return array();
    }

    function add_blog_comment($blog_id = ""){
        $user_id = $this->session->userdata('user_id');
        if($blog_id > 0 && $user_id > 0){
            $this->crud_model->add_blog_comment($blog_id, $user_id);
            $this->session->set_flashdata('flash_message', site_phrase('your_reply_has_been_successfully_published'));
            redirect($_SERVER['HTTP_REFERER'], 'refresh');
        }else{
            $this->session->set_flashdata('error_message', site_phrase('make_sure_you_have_logged_in'));
            redirect($_SERVER['HTTP_REFERER'], 'refresh');
        }
    }

    function update_blog_comment($blog_comment_id = ""){
        $user_id = $this->session->userdata('user_id');
        if($blog_comment_id > 0 && $user_id > 0){
            $this->crud_model->update_blog_comment($blog_comment_id, $user_id);
            $this->session->set_flashdata('flash_message', site_phrase('your_reply_has_been_successfully_published'));
            redirect($_SERVER['HTTP_REFERER'], 'refresh');
        }else{
            $this->session->set_flashdata('error_message', site_phrase('make_sure_you_have_logged_in'));
            redirect($_SERVER['HTTP_REFERER'], 'refresh');
        }
    }

    function delete_comment($blog_comment_id = "", $blog_id = ""){
        $blog_details = $this->crud_model->get_blogs($blog_id)->row_array();
        $user_id = $this->session->userdata('user_id');
        $this->crud_model->delete_comment($blog_comment_id, $user_id);
        $this->session->set_flashdata('flash_message', site_phrase('your_comment_has_been_deleted_successfully'));
        redirect(site_url('blog/details/'.slugify($blog_details['title']).'/'.$blog_id), 'refresh');
    }

    private function youngo_frontend_public_phrase($phrase_key, $fallback = '')
    {
        if (get_frontend_settings('theme') === 'youngo' && file_exists(APPPATH . 'helpers/youngo_frontend_language_helper.php')) {
            $this->load->helper('youngo_frontend_language');
            if (function_exists('youngo_frontend_phrase')) {
                return youngo_frontend_phrase($phrase_key, $fallback);
            }
        }

        return site_phrase($phrase_key);
    }
}
