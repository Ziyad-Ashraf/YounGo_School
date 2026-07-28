<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!defined('YOUNGO_PROTECTED_ROOT_ADMIN_USER_ID')) {
    define('YOUNGO_PROTECTED_ROOT_ADMIN_USER_ID', 1);
}

if (!function_exists('youngo_is_root_admin')) {
    function youngo_is_root_admin($user_id = null)
    {
        $user_id = youngo_capability_resolve_user_id($user_id);
        if ($user_id === null || (int) $user_id !== (int) YOUNGO_PROTECTED_ROOT_ADMIN_USER_ID) {
            return false;
        }

        $model = youngo_capability_model_instance();
        if (!$model) {
            return false;
        }

        return $model->user_exists((int) $user_id);
    }
}

if (!function_exists('youngo_get_user_role_slugs')) {
    function youngo_get_user_role_slugs($user_id = null)
    {
        $user_id = youngo_capability_resolve_user_id($user_id);
        if ($user_id === null) {
            return array();
        }

        $model = youngo_capability_model_instance();
        if (!$model || !$model->user_exists((int) $user_id)) {
            return array();
        }

        $roles = array();
        if (youngo_is_root_admin((int) $user_id)) {
            $roles[] = 'root_admin';
        }

        foreach ($model->get_user_role_slugs((int) $user_id) as $role_slug) {
            $roles[] = $role_slug;
        }

        $roles[] = 'learner';
        return youngo_capability_unique_values($roles);
    }
}

if (!function_exists('youngo_user_has_role')) {
    function youngo_user_has_role($user_id, $role_slug)
    {
        $model = youngo_capability_model_instance();
        if (!$model) {
            return false;
        }

        $role_slug = $model->normalize_slug($role_slug);
        if ($role_slug === '') {
            return false;
        }

        return in_array($role_slug, youngo_get_user_role_slugs($user_id), true);
    }
}

if (!function_exists('youngo_get_user_capability_slugs')) {
    function youngo_get_user_capability_slugs($user_id = null)
    {
        $user_id = youngo_capability_resolve_user_id($user_id);
        if ($user_id === null) {
            return array();
        }

        $model = youngo_capability_model_instance();
        if (!$model || !$model->user_exists((int) $user_id)) {
            return array();
        }

        if (youngo_is_root_admin((int) $user_id)) {
            return $model->get_all_capability_slugs();
        }

        return $model->get_user_capability_slugs((int) $user_id);
    }
}

if (!function_exists('youngo_user_has_capability')) {
    function youngo_user_has_capability($user_id, $capability_slug, $context = array())
    {
        $model = youngo_capability_model_instance();
        if (!$model) {
            return false;
        }

        $capability_slug = $model->normalize_slug($capability_slug);
        if ($capability_slug === '' || !$model->capability_exists($capability_slug)) {
            return false;
        }

        $user_id = youngo_capability_resolve_user_id($user_id);
        if ($user_id === null || !$model->user_exists((int) $user_id)) {
            return false;
        }

        if (youngo_is_root_admin((int) $user_id)) {
            return true;
        }

        return in_array($capability_slug, youngo_get_user_capability_slugs((int) $user_id), true);
    }
}

if (!function_exists('youngo_require_capability')) {
    function youngo_require_capability($capability_slug, $context = array())
    {
        $CI = &get_instance();
        $user_id = youngo_capability_resolve_user_id(null);

        if ($user_id !== null && youngo_user_has_capability((int) $user_id, $capability_slug, $context)) {
            return true;
        }

        if (PHP_SAPI === 'cli') {
            return false;
        }

        $is_ajax = !empty($context['ajax']);
        if (!$is_ajax && isset($CI->input) && method_exists($CI->input, 'is_ajax_request')) {
            $is_ajax = $CI->input->is_ajax_request();
        }

        if ($is_ajax) {
            if (isset($CI->output)) {
                $CI->output
                    ->set_status_header(403)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array('success' => false, 'message' => 'Access denied.')));
                $CI->output->_display();
            } else {
                header('HTTP/1.1 403 Forbidden');
                header('Content-Type: application/json');
                echo json_encode(array('success' => false, 'message' => 'Access denied.'));
            }
            exit;
        }

        if (isset($CI->session)) {
            $CI->session->set_flashdata('error_message', 'Access denied.');
        }

        if ($user_id === null) {
            redirect(site_url('login'), 'refresh');
        }

        $redirect_to = !empty($context['redirect_to']) ? $context['redirect_to'] : 'admin/dashboard';
        redirect(site_url($redirect_to), 'refresh');
    }
}

if (!function_exists('youngo_capability_model_instance')) {
    function youngo_capability_model_instance()
    {
        static $model = null;

        if ($model !== null) {
            return $model;
        }

        $CI = &get_instance();
        if (!isset($CI)) {
            return false;
        }

        $CI->load->database();
        $CI->load->model('Youngo_capability_model', 'youngo_capability_model');

        if (!isset($CI->youngo_capability_model)) {
            return false;
        }

        $model = $CI->youngo_capability_model;
        return $model;
    }
}

if (!function_exists('youngo_capability_resolve_user_id')) {
    function youngo_capability_resolve_user_id($user_id = null)
    {
        if ($user_id === null || $user_id === '') {
            $CI = &get_instance();
            if (isset($CI->session) && method_exists($CI->session, 'userdata')) {
                $user_id = $CI->session->userdata('user_id');
            }
        }

        if ($user_id === null || $user_id === '' || !is_numeric($user_id) || (int) $user_id <= 0) {
            return null;
        }

        return (int) $user_id;
    }
}

if (!function_exists('youngo_capability_unique_values')) {
    function youngo_capability_unique_values($values)
    {
        $unique = array();
        foreach ($values as $value) {
            if ($value !== '' && !in_array($value, $unique, true)) {
                $unique[] = $value;
            }
        }

        return $unique;
    }
}
