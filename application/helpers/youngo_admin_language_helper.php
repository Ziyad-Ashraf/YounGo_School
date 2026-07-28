<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Phase 2W.1: Admin backend language/direction resolution.
 *
 * Mirrors the pattern already used by youngo_frontend_language_helper.php
 * (youngo_frontend_html_lang / youngo_frontend_html_dir), but reads the
 * canonical settings.language_dirs map instead of a hardcoded whitelist, so
 * any language added later (not just Arabic) gets correct RTL handling for
 * free once its direction is recorded in settings.
 */

if (!function_exists('youngo_admin_active_language')) {
    function youngo_admin_active_language()
    {
        $CI = get_instance();
        $CI->load->database();

        $session_language = $CI->session->userdata('language');
        if (!empty($session_language)) {
            return strtolower($session_language);
        }

        $row = $CI->db->get_where('settings', array('key' => 'language'))->row();
        if ($row && !empty($row->value)) {
            return strtolower($row->value);
        }

        return 'english';
    }
}

if (!function_exists('youngo_admin_language_dirs_default_map')) {
    function youngo_admin_language_dirs_default_map()
    {
        // Safe fallback when settings.language_dirs is unreadable (e.g. no
        // CI instance yet, or the row is missing). Kept in sync with the
        // canonical values Phase 2U.4 wrote into settings.language_dirs.
        return array('english' => 'ltr', 'arabic' => 'rtl');
    }
}

if (!function_exists('youngo_admin_language_dirs_map')) {
    function youngo_admin_language_dirs_map()
    {
        if (!function_exists('get_instance')) {
            return youngo_admin_language_dirs_default_map();
        }

        $CI = get_instance();
        $CI->load->database();

        $row = $CI->db->get_where('settings', array('key' => 'language_dirs'))->row();
        if (!$row || empty($row->value)) {
            return youngo_admin_language_dirs_default_map();
        }

        $decoded = json_decode($row->value, true);
        return is_array($decoded) ? $decoded : youngo_admin_language_dirs_default_map();
    }
}

if (!function_exists('youngo_admin_html_dir')) {
    function youngo_admin_html_dir($language = null)
    {
        $language = $language !== null ? strtolower($language) : youngo_admin_active_language();
        $dirs = youngo_admin_language_dirs_map();

        if (isset($dirs[$language]) && strtolower($dirs[$language]) === 'rtl') {
            return 'rtl';
        }

        return 'ltr';
    }
}

if (!function_exists('youngo_admin_html_lang')) {
    function youngo_admin_html_lang($language = null)
    {
        $language = $language !== null ? strtolower($language) : youngo_admin_active_language();
        $iso = function_exists('getIsoCode') ? getIsoCode($language) : false;

        return $iso ? $iso : 'en';
    }
}

/* End of file youngo_admin_language_helper.php */
