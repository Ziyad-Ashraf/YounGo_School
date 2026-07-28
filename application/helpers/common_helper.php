<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package     CodeIgniter
 * @author      ExpressionEngine Dev Team
 * @copyright   Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license     http://codeigniter.com/user_guide/license.html
 * @link        http://codeigniter.com
 * @since       Version 1.0
 * @filesource
 */
//phpinfo();
if (! function_exists('remove_js')) {
    function remove_js($description = '', $convert_string = false) {

        if ($convert_string == true) {
            $description = nl2br(htmlspecialchars($description));
        } else {
            //make script to string
            $description = str_replace("&lt;script&gt;", "", $description);
            $description = str_replace("&lt;/script&gt;", "", $description);

            //removing <script> tags
            $description = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $description);
            $description = preg_replace("/[<][^<]*script.*[>].*[<].*[\/].*script*[>]/i", "", $description);

            //removing inline js events
            $description = preg_replace("/([ ]on[a-zA-Z0-9_-]{1,}=\".*\")|([ ]on[a-zA-Z0-9_-]{1,}='.*')|([ ]on[a-zA-Z0-9_-]{1,}=.*[.].*)/", "", $description);
            $description = preg_replace('/(<.+?)(?<=\s)on[a-z]+\s*=\s*(?:([\'"])(?!\2).+?\2|(?:\S+?\(.*?\)(?=[\s>])))(.*?>)/i', "$1 $3", $description);

            //removing inline js
            $description = preg_replace("/([ ]href.*=\".*javascript:.*\")|([ ]href.*='.*javascript:.*')|([ ]href.*=.*javascript:.*)/i", "", $description);
        }

        return $description;
    }
}


if (! function_exists('htmlspecialchars_')) {
    function htmlspecialchars_($description = '') {
        return htmlspecialchars($description ?? "");
    }
}
if (! function_exists('htmlspecialchars_decode_')) {
    function htmlspecialchars_decode_($description = '') {
        return htmlspecialchars_decode($description ?? "");
    }
}

if (! function_exists('youtube_embed_url')) {
    function youtube_embed_url($url = '') {
        $url = trim((string) $url);
        if ($url === '') return '';
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $path = trim($parts['path'] ?? '', '/');
        $video_id = '';
        if (in_array($host, array('youtu.be', 'www.youtu.be'), true)) {
            $video_id = explode('/', $path)[0];
        } elseif (strpos($host, 'youtube.com') !== false) {
            parse_str($parts['query'] ?? '', $query);
            if (strpos($path, 'embed/') === 0 || strpos($path, 'shorts/') === 0 || strpos($path, 'v/') === 0) {
                $video_id = explode('/', $path)[1] ?? '';
            } else {
                $video_id = $query['v'] ?? '';
            }
        }
        $video_id = preg_replace('/[^a-zA-Z0-9_-].*$/', '', (string) $video_id);
        return $video_id ? 'https://www.youtube-nocookie.com/embed/' . rawurlencode($video_id) : '';
    }
}

if (!function_exists('isJson')) {
    function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}

if (!function_exists('set_url_history')) {
    function set_url_history($url) {
        $CI    = &get_instance();
        $CI->session->set_userdata('url_history', $url);
    }
}

if (!function_exists('upload_description_images')) {
    function upload_description_images($description = "", $path = ""){
        // Find all the image tags in the Summernote content
        preg_match_all('/<img[^>]+src="data:image\/([a-zA-Z0-9]+);base64,([^"]+)"/', $description, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            // Define the path to where you want to save the image
            $imagePath = $path.'/' . time().random(20).'.'.$match[1];
            $image_tag = str_replace('data:image/png;base64,'.$match[2], base_url($imagePath), $match[0]);
            $description = str_replace($match[0], $image_tag, $description);


            file_put_contents($imagePath, base64_decode($match[2]));
        }
        return $description;
    }
}

if (!function_exists('remove_description_images')) {
    function remove_description_images($description = ""){
        // Find all the image tags in the Summernote content
        preg_match_all('/<img[^>]+>/i', $description, $matches);
        foreach ($matches[0] as $match) {
            //$match this is image tag
            preg_match('/src=[\'"]([^\'"]+)[\'"]/i', $match, $srcMatches);
            $image_path_arr = explode('uploads/', $srcMatches[1]);
            $image_path = 'uploads/'.$image_path_arr[1];
            if(file_exists($image_path)){
                unlink($image_path);
            }
        }
    }
}

if (!function_exists('has_permission')) {
    function has_permission($permission_for = '', $admin_id = '')
    {
        $CI    = &get_instance();
        $CI->load->database();

        // GET THE LOGGEDIN IN ADMIN ID
        if (empty($admin_id)) {
            $admin_id = $CI->session->userdata('user_id');
        }

        $CI->db->where('admin_id', $admin_id);
        $get_admin_permissions = $CI->db->get('permissions');
        if ($get_admin_permissions->num_rows() == 0) {
            return true;
        } else {
            $get_admin_permissions = $get_admin_permissions->row_array();
            $permissions = json_decode($get_admin_permissions['permissions']);
            if (in_array($permission_for, $permissions)) {
                return true;
            } else {
                return false;
            }
        }
    }
}

if (!function_exists('check_permission')) {
    function check_permission($permission_for)
    {
        $CI    = &get_instance();
        $CI->load->database();

        if (!has_permission($permission_for)) {
            $CI->session->set_flashdata('error_message', get_phrase('you_are_not_authorized_to_access_this_page'));
            redirect(site_url('admin/dashboard'), 'refresh');
        }
    }
}



if (!function_exists('is_root_admin')) {
    function is_root_admin($admin_id = '')
    {
        $CI    = &get_instance();
        $CI->load->database();

        // GET THE LOGGEDIN IN ADMIN ID
        if (empty($admin_id)) {
            $admin_id = $CI->session->userdata('user_id');
        }

        $CI->db->where('admin_id', $admin_id);
        $get_admin_permissions = $CI->db->get('permissions');
        if ($get_admin_permissions->num_rows() == 0) {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('custom_date')) {
    function custom_date($strtotime = "", $format = "")
    {
        if ($format == "") {
            return date('d', $strtotime) . ' ' . site_phrase(date('M', $strtotime)) . ' ' . date('Y', $strtotime);
        } elseif ($format == 1) {
            return site_phrase(date('D', $strtotime)) . ', ' . date('d', $strtotime) . ' ' . site_phrase(date('M', $strtotime)) . ' ' . date('Y', $strtotime);
        }
    }
}

if (!function_exists('nice_number')) {
    function nice_number($n) {
        // first strip any formatting;
        $n = (0+str_replace(",", "", $n));

        // is this a number?
        if (!is_numeric($n)) return false;

        // now filter it;
        if($n <= 1000) return number_format($n);
        elseif ($n > 1000000000000) return round(($n/1000000000000), 1).'T';
        elseif ($n > 1000000000) return round(($n/1000000000), 1).'M';
        elseif ($n > 1000000) return round(($n/1000000), 1).'M';
        elseif ($n > 1000) return round(($n/1000), 1).'k';

        return number_format($n);
    }
}

if (!function_exists('nice_number')) {
    function nice_number($n) {
        // first strip any formatting;
        $n = (0+str_replace(",", "", $n));

        // is this a number?
        if (!is_numeric($n)) return false;

        // now filter it;
        if($n <= 1000) return number_format($n);
        elseif ($n > 1000000000000) return round(($n/1000000000000), 1).'T';
        elseif ($n > 1000000000) return round(($n/1000000000), 1).'M';
        elseif ($n > 1000000) return round(($n/1000000), 1).'M';
        elseif ($n > 1000) return round(($n/1000), 1).'k';

        return number_format($n);
    }
}

if (! function_exists('get_past_time')) {
    function get_past_time( $time = "" ) {
        $time_difference = time() - $time;

        if( $time_difference < 1 ) { return 'less than 1 second ago'; }

        //864000 = 10 days
        if($time_difference > 864000){ return custom_date($time, 1); }

        $condition = array( 12 * 30 * 24 * 60 * 60 =>  site_phrase('year'),
                    30 * 24 * 60 * 60       =>  site_phrase('month'),
                    24 * 60 * 60            =>  site_phrase('day'),
                    60 * 60                 =>  site_phrase('hour'),
                    60                      =>  site_phrase('minute'),
                    1                       =>  site_phrase('second')
        );

        foreach( $condition as $secs => $str )
        {
            $d = $time_difference / $secs;

            if( $d >= 1 )
            {
                $t = round( $d );
                return $t . ' ' . $str . ( $t > 1 ? 's' : '' ) .' '. site_phrase('ago');
            }
        }
    }
}

if (! function_exists('resizeImage')) {
    function resizeImage($filelocation = "", $target_path = "", $width = "", $height = "") {
        $CI =&  get_instance();
        $CI->load->database();
        
        if($width == ""){
            $width = 200;
        }

        if($height == ""){
            $maintain_ratio = TRUE;
        }else{
            $maintain_ratio = FALSE;
        }

        $config_manip = array(
            'image_library' => 'gd2',
            'source_image' => $filelocation,
            'new_image' => $target_path,
            'maintain_ratio' => $maintain_ratio,
            'create_thumb' => TRUE,
            'thumb_marker' => '',
            'width' => $width,
            'height' => $height
        );
        $CI->load->library('image_lib', $config_manip);

        if ($CI->image_lib->resize()) {
            return true;
        }else{
            $CI->image_lib->display_errors();
            return false;
        }
        $CI->image_lib->clear();
   }
}

if (!function_exists('get_settings')) {
    function get_settings($key = '', $type = "")
    {
        $CI    = &get_instance();
        $CI->load->database();

        $CI->db->where('key', $key);
        $result = $CI->db->get('settings')->row('value');

        if($type){
            return json_decode($result, true);
        }else{
            return $result;
        }
    }
}

if (!function_exists('currency')) {
    function currency($price = "")
    {
        $CI    = &get_instance();
        $CI->load->database();
        $youngo_currency_is_arabic = function_exists('youngo_frontend_active_language') && youngo_frontend_active_language() === 'arabic';
        if (!$youngo_currency_is_arabic && !empty($_SERVER['REQUEST_URI'])) {
            $youngo_currency_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $youngo_currency_is_arabic = is_string($youngo_currency_path) && preg_match('#/(?:index\.php/)?ar(?:/|$)#', $youngo_currency_path);
        }

        if ($price != "") {
            $CI->db->where('key', 'system_currency');
            $currency_code = $CI->db->get('settings')->row('value');

            $CI->db->where('code', $currency_code);
            $symbol = $CI->db->get('currency')->row('symbol');
            $is_egp_currency = strtoupper((string) $currency_code) === 'EGP';
            if ($is_egp_currency) {
                $symbol = $youngo_currency_is_arabic ? 'ج.م' : 'EGP';
            }

            $CI->db->where('key', 'currency_position');
            $position = $CI->db->get('settings')->row('value');
            if ($is_egp_currency) {
                $position = 'right-space';
            } elseif (!in_array($position, array('right', 'right-space', 'left', 'left-space'), true)) {
                $position = 'left';
            }

            if ($position == 'right') {
                return $price . $symbol;
            } elseif ($position == 'right-space') {
                return $price . ' ' . $symbol;
            } elseif ($position == 'left') {
                return $symbol . $price;
            } elseif ($position == 'left-space') {
                return $symbol . ' ' . $price;
            }
        }else{
            $CI->db->where('key', 'system_currency');
            $currency_code = $CI->db->get('settings')->row('value');

            $CI->db->where('code', $currency_code);
            if (strtoupper((string) $currency_code) === 'EGP') {
                return $youngo_currency_is_arabic ? 'ج.م' : 'EGP';
            }
            return $CI->db->get('currency')->row()->symbol;
        }
    }
}

if (!function_exists('currency_code_and_symbol')) {
    function currency_code_and_symbol($type = "")
    {
        $CI    = &get_instance();
        $CI->load->database();
        $youngo_currency_is_arabic = function_exists('youngo_frontend_active_language') && youngo_frontend_active_language() === 'arabic';
        if (!$youngo_currency_is_arabic && !empty($_SERVER['REQUEST_URI'])) {
            $youngo_currency_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $youngo_currency_is_arabic = is_string($youngo_currency_path) && preg_match('#/(?:index\.php/)?ar(?:/|$)#', $youngo_currency_path);
        }

        $CI->db->where('key', 'system_currency');
        $currency_code = $CI->db->get('settings')->row('value');

        $CI->db->where('code', $currency_code);
        $symbol = $CI->db->get('currency')->row()->symbol;
        if (strtoupper((string) $currency_code) === 'EGP') {
            $symbol = $youngo_currency_is_arabic ? 'ج.م' : 'EGP';
        }
        if ($type == "") {
            return $symbol;
        } else {
            return $currency_code;
        }
    }
}

if (!function_exists('get_frontend_settings')) {
    function get_frontend_settings($key = '')
    {
        $CI    = &get_instance();
        $CI->load->database();

        $CI->db->where('key', $key);
        $result = $CI->db->get('frontend_settings')->row('value');



        if($key == 'banner_image'){
            $banner_images = json_decode($result, true);
            return $banner_images[get_frontend_settings('home_page')];
        }
        return $result;
    }
}

if (!function_exists('get_current_banner')) {
    function get_current_banner($key = '')
    {
        $CI    = &get_instance();
        $CI->load->database();

        $CI->db->where('key', $key);
        $result = $CI->db->get('frontend_settings')->row('value');

        $banner_images = json_decode($result, true);
        $active_home_page = get_frontend_settings('home_page');
        if(array_key_exists($active_home_page, $banner_images))
        return $banner_images[$active_home_page];
    }
}

if (!function_exists('slugify')) {
    function slugify($text)
    {
        if (empty($text))
            return 'n-a';

        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
        $text = trim($text, '-');
        $text = strtolower($text);
        //$text = preg_replace('~[^-\w]+~', '', $text);
        return $text;
    }
}

if (!function_exists('get_video_extension')) {
    // Checks if a video is youtube, vimeo or any other
    function get_video_extension($url)
    {
        if (strpos($url, '.mp4') > 0) {
            return 'mp4';
        } elseif (strpos($url, '.webm') > 0) {
            return 'webm';
        } else {
            return 'unknown';
        }
    }
}

if (!function_exists('ellipsis')) {
    // Checks if a video is youtube, vimeo or any other
    function ellipsis($long_string, $max_character = 30)
    {
        $short_string = strlen($long_string) > $max_character ? mb_substr($long_string, 0, $max_character) . "..." : $long_string;
        return $short_string;
    }
}

// This function helps us to decode the theme configuration json file and return that array to us
if (!function_exists('themeConfiguration')) {
    function themeConfiguration($theme, $key = "")
    {
        $themeConfigs = [];
        if (file_exists('assets/frontend/' . $theme . '/config/theme-config.json')) {
            $themeConfigs = file_get_contents('assets/frontend/' . $theme . '/config/theme-config.json');
            $themeConfigs = json_decode($themeConfigs, true);
            if ($key != "") {
                if (array_key_exists($key, $themeConfigs)) {
                    return $themeConfigs[$key];
                } else {
                    return false;
                }
            } else {
                return $themeConfigs;
            }
        } else {
            return false;
        }
    }
}

// Human readable time
if (!function_exists('readable_time_for_humans')) {
    function readable_time_for_humans($duration)
    {
        if ($duration) {
            $duration_array = explode(':', $duration);
            $hour   = $duration_array[0];
            $minute = $duration_array[1];
            $second = $duration_array[2];
            if ($hour > 0) {
                $duration = $hour . ' ' . get_phrase('hr') . ' ' . $minute . ' ' . get_phrase('min');
            } elseif ($minute > 0) {
                if ($second > 0) {
                    $duration = ($minute + 1) . ' ' . get_phrase('min');
                } else {
                    $duration = $minute . ' ' . get_phrase('min');
                }
            } elseif ($second > 0) {
                $duration = $second . ' ' . get_phrase('sec');
            } else {
                $duration = '00:00';
            }
        } else {
            $duration = '00:00';
        }
        return $duration;
    }
}

// Human readable time
if (!function_exists('seconds_to_time_format')) {
    function seconds_to_time_format($seconds = "0")
    {
        if ($seconds) {
            $hours = floor($seconds / 3600); // Calculate the number of hours
            $minutes = floor(($seconds % 3600) / 60); // Calculate the number of minutes
            $totalSeconds = $seconds % 60; // Calculate the number of seconds

            return sprintf("%02d:%02d:%02d", $hours, $minutes, $totalSeconds); // Format the time as HH:MM:SS
        } else {
            $duration = '00:00:00';
        }
        return $duration;
    }
}

// Human readable time
if (!function_exists('time_to_seconds')) {
    function time_to_seconds($time)
    {
        $time = explode(':', $time);
        $seconds = $time[0] * 3600;
        $seconds = $seconds + ($time[1] * 60);
        return $seconds = $seconds + $time[2];
    }
}

if (!function_exists('trimmer')) {
    function trimmer($text)
    {
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
        $text = trim($text, '-');
        $text = strtolower($text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        if (empty($text))
            return 'n-a';
        return $text;
    }
}

if (!function_exists('lesson_progress')) {
    function lesson_progress($lesson_id = "", $user_id = "", $course_id = "")
    {
        $CI    = &get_instance();
        $CI->load->database();
        if ($user_id == "") {
            $user_id = $CI->session->userdata('user_id');
        }
        if ($course_id == "") {
            $course_id = $CI->db->get_where('lesson', array('id' => $lesson_id))->row('course_id');
        }

        $query = $CI->db->get_where('watch_histories', array('course_id' => $course_id, 'student_id' => $user_id));

        if($query->num_rows() > 0){
            $lesson_ids = json_decode($query->row('completed_lesson'), true);
            if(is_array($lesson_ids) && in_array($lesson_id, $lesson_ids)){
                return 1;
            }else{
                return 0;
            }
        }
    }
}
if (!function_exists('course_progress')) {
    function course_progress($course_id = "", $user_id = "", $return_type = "")
    {
        $CI = &get_instance();
        $CI->load->database();

        // Get user ID if not provided
        if ($user_id == "") {
            $user_id = $CI->session->userdata('user_id');
        }

        // Fetch watch history for the user and course
        $watch_history = $CI->crud_model->get_watch_histories($user_id, $course_id)->row_array();
        $completed_lessons = isset($watch_history['completed_lesson']) ? json_decode($watch_history['completed_lesson'], true) : [];

        // Ensure $completed_lessons is always an array
        if (!is_array($completed_lessons)) {
            $completed_lessons = [];
        }

        // Get all valid lesson IDs for the course
        $lesson_ids = $CI->db->select('id')
            ->where('course_id', $course_id)
            ->get('lesson') // Assuming 'lessons' is the table name
            ->result_array();

        $lesson_ids = array_column($lesson_ids, 'id'); // Extract lesson IDs into a flat array

        // Filter out completed lessons that are no longer valid
        $filtered_completed_lessons = array_intersect($completed_lessons, $lesson_ids);

        // If return type is "completed_lesson_ids", return the filtered list
        if ($return_type == "completed_lesson_ids") {
            return $filtered_completed_lessons;
        }

        // Recalculate course progress
        $total_lessons = count($lesson_ids);
        $completed_count = count($filtered_completed_lessons);
        $calculated_progress = $total_lessons > 0 ? ($completed_count / $total_lessons) * 100 : 0;

        // Update the watch history if it exists and there are changes
        if (!empty($watch_history)) {
            $existing_progress = isset($watch_history['course_progress']) ? $watch_history['course_progress'] : 0;
            $watch_history_id = isset($watch_history['watch_history_id']) ? $watch_history['watch_history_id'] : null;

            if ($completed_lessons !== $filtered_completed_lessons || $calculated_progress != $existing_progress) {
                $updated_data = [
                    'completed_lesson' => json_encode($filtered_completed_lessons),
                    'course_progress'  => $calculated_progress
                ];

                if ($watch_history_id !== null) {
                    $CI->db->where('watch_history_id', $watch_history_id);
                    $CI->db->update('watch_histories', $updated_data);
                }
            }
        }

        // Return the course progress
        return $calculated_progress;
    }
}


// RANDOM NUMBER GENERATOR FOR ELSEWHERE
if (!function_exists('random')) {
    function random($length_of_string)
    {
        // String of all alphanumeric character
        $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        // Shufle the $str_result and returns substring
        // of specified length
        return substr(str_shuffle($str_result), 0, $length_of_string);
    }
}

// RANDOM NUMBER GENERATOR FOR ELSEWHERE
if (!function_exists('phpFileUploadErrors')) {
    function phpFileUploadErrors($error_code)
    {
        $phpFileUploadErrorsArray = array(
            0 => 'There is no error, the file uploaded with success',
            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk.',
            8 => 'A PHP extension stopped the file upload.',
        );
        return $phpFileUploadErrorsArray[$error_code];
    }
}


// course bundle subscription data
if (!function_exists('get_bundle_validity')) {
    function get_bundle_validity($bundle_id = "", $user_id = "")
    {
        $CI = &get_instance();
        $CI->load->database();
        if ($user_id == "") {
            $user_id = $CI->session->userdata('user_id');
        }


        $result = $CI->db->get_where('addons', array('unique_identifier' => 'course_bundle'));
        if($bundle_id == "" || $result->num_rows() == 0){
            return "invalid";
        }

        $today = strtotime(date('d M Y'));

        $course_bundle = $CI->db->get_where('course_bundle', array('id' => $bundle_id))->row_array();

        $CI->db->limit(1);
        $CI->db->order_by('id', 'desc');
        $bundle_payment = $CI->db->get_where('bundle_payment', array('bundle_id' => $bundle_id, 'user_id' => $user_id));

        //convert day to seconds
        $subscription_limit_timestamp = $course_bundle['subscription_limit'] * 86400;

        if ($bundle_payment->num_rows() > 0) {
            $max_valid_date = $bundle_payment->row_array()['date_added'] + $subscription_limit_timestamp;
            if ($today <= $max_valid_date) {
                //validate
                return 'valid';
            } else {
                //expire
                return 'expire';
            }
        } else {
            return 'invalid';
        }
    }
}


if (!function_exists('get_lesson_type')) {
    function get_lesson_type($lesson_id = "")
    {
        $CI = &get_instance();
        $CI->load->database();
        $lesson = $CI->db->get_where('lesson', ['id' => $lesson_id]);
        if($lesson->num_rows() > 0){
            $lesson = $lesson->row_array();
            if($lesson['lesson_type'] == 'video' && $lesson['video_type'] == 'YouTube' || $lesson['video_type'] == 'youtube'){
                return 'youtube_video_url';
            }elseif($lesson['lesson_type'] == 'video' && $lesson['video_type'] == 'google_drive'){
                return 'google_drive_video_url';
            }elseif($lesson['lesson_type'] == 'video' && $lesson['video_type'] == 'Vimeo' || $lesson['video_type'] == 'vimeo'){
                return 'vimeo_video_url';
            }elseif($lesson['lesson_type'] == 'video' && $lesson['video_type'] == 'amazon'){
                return 'amazon_video_url';
            }elseif($lesson['lesson_type'] == 'video' && $lesson['video_type'] == 'system'){
                return 'video_file';
            }elseif($lesson['lesson_type'] == 'audio'){
                return 'audio_file';
            }elseif($lesson['lesson_type'] == 'video' && $lesson['video_type'] == 'academy_cloud'){
                return 'academy_cloud';
            }elseif($lesson['lesson_type'] == 'video' && $lesson['video_type'] == 'html5'){
                return 'html5_video_url';
            }elseif($lesson['lesson_type'] == 'quiz'){
                return 'quiz';
            }elseif($lesson['lesson_type'] == 'text'){
                return 'text';
            }elseif($lesson['lesson_type'] == 'other' && $lesson['attachment_type'] == 'txt'){
                return 'text_file';
            }elseif($lesson['lesson_type'] == 'other' && $lesson['attachment_type'] == 'pdf'){
                return 'pdf_file';
            }elseif($lesson['lesson_type'] == 'other' && $lesson['attachment_type'] == 'doc'){
                return 'doc_file';
            }elseif($lesson['lesson_type'] == 'other' && $lesson['attachment_type'] == 'img'){
                return 'image_file';
            }elseif($lesson['lesson_type'] == 'wasabi' && $lesson['attachment_type'] == 'video'){
                return 'wasabi_video_url';
            }elseif($lesson['lesson_type'] == 'wasabi' && $lesson['attachment_type'] == 'image'){
                return 'wasabi_image_file';
            }elseif($lesson['lesson_type'] == 'wasabi' && $lesson['attachment_type'] == 'document'){
                return 'wasabi_document_file';
            }elseif($lesson['lesson_type'] == 'wasabi' && $lesson['attachment_type'] == 'text'){
                return 'wasabi_text_file';
            }else{
                return 'iframe';
            }

            //'image_file' || 'doc_file' || 'pdf_file' || 'text_file' || 
        }
    }
}

if (!function_exists('next_lesson')) {
    function next_lesson($course_id = "", $lesson_id = "")
    {
        $CI = &get_instance();
        $CI->load->database();

        // Get lessons for the given course
        $lesson_list = $CI->crud_model->get_lessons('course', $course_id)->result_array();

        // Find the current lesson position in the list
        $current_index = -1;
        foreach ($lesson_list as $index => $lesson) {
            if ($lesson['id'] == $lesson_id) {
                $current_index = $index;
                break;
            }
        }

        // If the lesson is found and there's a next lesson
        if ($current_index != -1 && isset($lesson_list[$current_index + 1])) {
            // Return the next lesson's ID
            return $lesson_list[$current_index + 1]['id'];
        } else {
            // Return null or a message indicating there's no next lesson
            return null;  // or 'No next lesson'
        }
    }
}

if (!function_exists('get_seo_data')) {
    function get_seo_data() {
        $CI =& get_instance();
        $route = $CI->uri->uri_string;


        // If no segment is found (i.e., we are at the root page), default to 'home'
        if (empty($route)) {
            $route = 'home';  // Default to home if no route is provided
        } elseif($route == 'home/courses') {
            $route = 'courses'; 
        } elseif($route == 'home/contact_us') {
            $route = 'contact_us'; 
        } elseif($route == 'home/about_us') {
            $route = 'about_us'; 
        } elseif($route == 'home/privacy_policy') {
            $route = 'privacy_policy'; 
        } elseif($route == 'home/terms_and_condition') {
            $route = 'terms_and_condition'; 
        } elseif($route == 'home/refund_policy') {
            $route = 'refund_policy'; 
        }  elseif($route == 'addons/bootcamp/bootcamp_list') {
            $route = 'bootcamps'; 
        }
        
        // Load the Seo_model to fetch SEO data
        $seo_data = $CI->crud_model->get_seo_by_route($route);
        
        // If no data is found, fall back to default SEO or empty array
        if ($seo_data) {
            return (array) $seo_data;
        }
    }
}

if (!function_exists('validate_cart_items')) {
    function validate_cart_items() {
        $CI    = &get_instance();
        $CI->load->database();

        $cart_items = $CI->session->userdata('cart_items');
        
        if (!is_array($cart_items) || empty($cart_items)) {
            return; // No items to validate
        } else {
            $cart_items[] = 0; 
        }

        // Get valid course IDs from the database
        $CI->db->select('id');
        $CI->db->where_in('id', $cart_items);
        $query = $CI->db->get('course');
        $valid_course_ids = array_column($query->result_array(), 'id');

        // Filter valid cart items
        $filtered_cart_items = array_filter($cart_items, function($course_id) use ($valid_course_ids) {
            return in_array($course_id, $valid_course_ids);
        });

        // Check if there is any difference between cart_items and filtered_cart_items
        if ($cart_items !== array_values($filtered_cart_items)) {
            // Only update session if there is a change
            $CI->session->set_userdata('cart_items', $filtered_cart_items);
        }
    }
}

if (!function_exists('youngo_homepage_fixed_section_keys')) {
    function youngo_homepage_fixed_section_keys()
    {
        return array(
            'hero',
            'featured_categories',
            'featured_courses',
            'why_choose',
            'about_teaser',
            'testimonials',
            'faq_preview',
            'blog_preview',
            'final_cta',
        );
    }
}

if (!function_exists('youngo_homepage_allowed_source_types')) {
    function youngo_homepage_allowed_source_types()
    {
        return array('manual', 'existing_cms', 'mixed', 'auto');
    }
}

if (!function_exists('youngo_homepage_allowed_additional_section_types')) {
    function youngo_homepage_allowed_additional_section_types()
    {
        return array(
            'text_image',
            'feature_cards',
            'cta_band',
            'testimonial_block',
            'faq_block',
            'course_highlight',
            'category_highlight',
        );
    }
}

if (!function_exists('youngo_homepage_is_assoc')) {
    function youngo_homepage_is_assoc($array)
    {
        if (!is_array($array) || empty($array)) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}

if (!function_exists('youngo_homepage_deep_merge')) {
    function youngo_homepage_deep_merge($defaults, $stored)
    {
        if (!is_array($stored)) {
            return $defaults;
        }

        if (!youngo_homepage_is_assoc($defaults)) {
            return is_array($stored) && count($stored) > 0 ? $stored : $defaults;
        }

        foreach ($stored as $key => $value) {
            if (array_key_exists($key, $defaults) && is_array($defaults[$key]) && is_array($value)) {
                $defaults[$key] = youngo_homepage_deep_merge($defaults[$key], $value);
            } else {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }
}

if (!function_exists('youngo_homepage_section_default')) {
    function youngo_homepage_section_default($key, $label, $sort_order, $source_type, $content)
    {
        return array(
            'key' => $key,
            'label' => $label,
            'is_visible' => true,
            'is_published' => true,
            'sort_order' => $sort_order,
            'source_type' => $source_type,
            'content' => $content,
            'fallback' => array(
                'use_default_if_empty' => true,
            ),
        );
    }
}

if (!function_exists('youngo_homepage_additional_content_default')) {
    function youngo_homepage_additional_content_default($type)
    {
        $defaults = array(
            'text_image' => array(
                'title' => '',
                'subtitle' => '',
                'body' => '',
                'image' => array('url' => '', 'alt' => ''),
                'cta' => array('label' => '', 'url' => ''),
            ),
            'feature_cards' => array(
                'title' => '',
                'subtitle' => '',
                'items' => array(),
            ),
            'cta_band' => array(
                'title' => '',
                'subtitle' => '',
                'primary_cta' => array('label' => '', 'url' => ''),
                'secondary_cta' => array('label' => '', 'url' => ''),
            ),
            'testimonial_block' => array(
                'title' => '',
                'subtitle' => '',
                'items' => array(),
            ),
            'faq_block' => array(
                'title' => '',
                'subtitle' => '',
                'items' => array(),
                'cta' => array('label' => '', 'url' => ''),
            ),
            'course_highlight' => array(
                'title' => '',
                'subtitle' => '',
                'course_ids' => array(),
                'limit' => 3,
                'cta' => array('label' => 'View All Courses', 'url' => '/home/courses'),
            ),
            'category_highlight' => array(
                'title' => '',
                'subtitle' => '',
                'category_ids' => array(),
                'limit' => 4,
                'cta' => array('label' => 'View All Categories', 'url' => '/home/courses'),
            ),
        );

        return array_key_exists($type, $defaults) ? $defaults[$type] : array();
    }
}

if (!function_exists('youngo_homepage_default_content')) {
    function youngo_homepage_default_content()
    {
        return array(
            'version' => 2,
            'homepage_key' => 'youngo_homepage',
            'is_active' => true,
            'is_published' => true,
            'last_updated_at' => null,
            'sections_order' => youngo_homepage_fixed_section_keys(),
            'sections' => array(
                'hero' => youngo_homepage_section_default('hero', 'Hero', 10, 'manual', array(
                    'eyebrow' => 'Safe creative learning for kids',
                    'title' => 'A brighter way for children to learn, create, and grow',
                    'subtitle' => 'YounGo brings guided online courses, creative projects, and parent-friendly structure into one calm learning space built for curious young minds.',
                    'primary_cta' => array('label' => 'Explore courses', 'url' => '/home/courses'),
                    'secondary_cta' => array('label' => 'Talk to us', 'url' => '/home/contact_us'),
                    'image' => array('url' => 'assets/frontend/youngo/images/demo-hero-learning.jpg', 'alt' => 'Children learning coding and robotics with a YounGo instructor'),
                    'trust_items' => array('Parent-trusted topics', 'Guided creative projects', 'Flexible after-school learning'),
                    'stats' => array(
                        array('value' => '6+', 'label' => 'learning paths'),
                        array('value' => '4-12', 'label' => 'age-friendly range'),
                        array('value' => '100%', 'label' => 'focused on kids'),
                    ),
                )),
                'featured_categories' => youngo_homepage_section_default('featured_categories', 'Featured Categories', 20, 'mixed', array(
                    'title' => 'Explore learning paths',
                    'subtitle' => 'Help your child choose a topic that feels exciting today and useful tomorrow.',
                    'category_ids' => array(),
                    'limit' => 6,
                    'cta' => array('label' => 'View all learning paths', 'url' => '/home/courses'),
                    'overrides' => array(),
                    'items' => array(
                        array('image' => array('url' => 'assets/frontend/youngo/images/demo-course-coding.jpg', 'alt' => 'Coding for Kids'), 'icon_key' => 'CK', 'title' => 'Coding for Kids', 'description' => 'Build logic through friendly projects.', 'meta' => 'Creative tech skills', 'count' => 3),
                        array('image' => array('url' => 'assets/frontend/youngo/images/demo-course-robotics.jpg', 'alt' => 'Science Explorers'), 'icon_key' => 'SE', 'title' => 'Science Explorers', 'description' => 'Discover space, nature, and experiments.', 'meta' => 'Curiosity and discovery', 'count' => 3),
                        array('image' => array('url' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg', 'alt' => 'Creative Arts'), 'icon_key' => 'CA', 'title' => 'Creative Arts', 'description' => 'Draw, design, and make with confidence.', 'meta' => 'Expression and imagination', 'count' => 2),
                        array('image' => array('url' => 'assets/frontend/youngo/images/demo-family-project.jpg', 'alt' => 'Reading and Storytelling'), 'icon_key' => 'RS', 'title' => 'Reading & Storytelling', 'description' => 'Grow vocabulary, fluency, and voice.', 'meta' => 'Language confidence', 'count' => 2),
                        array('image' => array('url' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg', 'alt' => 'Math Adventures'), 'icon_key' => 'MA', 'title' => 'Math Adventures', 'description' => 'Make numbers feel playful and practical.', 'meta' => 'Problem solving', 'count' => 2),
                        array('image' => array('url' => 'assets/frontend/youngo/images/demo-family-project.jpg', 'alt' => 'Life Skills'), 'icon_key' => 'LS', 'title' => 'Life Skills', 'description' => 'Build habits, teamwork, and confidence.', 'meta' => 'Everyday growth', 'count' => 2),
                    ),
                )),
                'featured_courses' => youngo_homepage_section_default('featured_courses', 'Featured Courses', 30, 'mixed', array(
                    'title' => 'Featured courses for curious learners',
                    'subtitle' => 'Course journeys with clear outcomes, warm visuals, and practical skill-building for young learners.',
                    'course_ids' => array(),
                    'category_ids' => array(),
                    'limit' => 6,
                    'cta' => array('label' => 'See all courses', 'url' => '/home/courses'),
                    'overrides' => array(),
                    'items' => array(
                        array('image' => array('url' => 'assets/frontend/youngo/images/demo-course-coding.jpg', 'alt' => 'Scratch Coding for Young Creators'), 'badge' => 'Ages 8-12', 'title' => 'Scratch Coding for Young Creators', 'description' => 'Kids build animations and first games while learning logic, sequencing, and creative problem solving.', 'rating' => '4.9', 'review_count' => 24, 'category' => 'Coding for Kids', 'instructor' => 'YounGo Studio', 'price_label' => 'Free preview'),
                        array('image' => array('url' => 'assets/frontend/youngo/images/demo-course-robotics.jpg', 'alt' => 'Space Science Adventures'), 'badge' => 'Ages 6-10', 'title' => 'Space Science Adventures', 'description' => 'A guided mission through planets, rockets, gravity, and observation activities for young explorers.', 'rating' => '4.8', 'review_count' => 18, 'category' => 'Science Explorers', 'instructor' => 'YounGo Science Lab', 'price_label' => 'Free'),
                        array('image' => array('url' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg', 'alt' => 'Creative Drawing Basics'), 'badge' => 'Ages 5-9', 'title' => 'Creative Drawing Basics', 'description' => 'Children practice shapes, color, character ideas, and visual storytelling through short creative prompts.', 'rating' => '5.0', 'review_count' => 16, 'category' => 'Creative Arts', 'instructor' => 'YounGo Art Room', 'price_label' => 'Free'),
                        array('image' => array('url' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg', 'alt' => 'Fun Math Problem Solving'), 'badge' => 'Ages 7-11', 'title' => 'Fun Math Problem Solving', 'description' => 'Playful number challenges help kids build confidence with patterns, reasoning, and everyday math.', 'rating' => '4.9', 'review_count' => 20, 'category' => 'Math Adventures', 'instructor' => 'YounGo Math Coach', 'price_label' => 'Free'),
                        array('image' => array('url' => 'assets/frontend/youngo/images/demo-family-project.jpg', 'alt' => 'Storytelling and Reading Confidence'), 'badge' => 'Ages 6-10', 'title' => 'Storytelling and Reading Confidence', 'description' => 'Young readers practice expression, story structure, and speaking confidence with guided activities.', 'rating' => '4.8', 'review_count' => 14, 'category' => 'Reading & Storytelling', 'instructor' => 'YounGo Reading Club', 'price_label' => 'Free'),
                        array('image' => array('url' => 'assets/frontend/youngo/images/demo-family-project.jpg', 'alt' => 'Young Innovators Lab'), 'badge' => 'Ages 8-12', 'title' => 'Young Innovators Lab', 'description' => 'A project-led course where kids imagine useful ideas, sketch solutions, and present their thinking.', 'rating' => 'New', 'review_count' => 0, 'category' => 'Life Skills', 'instructor' => 'YounGo Studio', 'price_label' => 'Free'),
                    ),
                )),
                'why_choose' => youngo_homepage_section_default('why_choose', 'Why Choose YounGo', 40, 'manual', array(
                    'title' => 'Why families choose YounGo',
                    'subtitle' => 'A premium learning space that balances child-friendly creativity with the structure parents expect.',
                    'items' => array(
                        array('icon_key' => 'Safe', 'title' => 'A focused place to learn', 'description' => 'YounGo keeps the experience centered on useful topics, calm navigation, and age-aware learning moments.'),
                        array('icon_key' => 'Guide', 'title' => 'Guided steps, not random browsing', 'description' => 'Courses are shaped as clear journeys so kids know what to do next and parents can understand the path.'),
                        array('icon_key' => 'Make', 'title' => 'Creative work with real outcomes', 'description' => 'Projects, prompts, and practice activities help children turn curiosity into visible progress.'),
                        array('icon_key' => 'Grow', 'title' => 'Confidence families can see', 'description' => 'Friendly pacing and achievable challenges help kids feel capable while building useful habits.'),
                    ),
                )),
                'about_teaser' => youngo_homepage_section_default('about_teaser', 'About Teaser', 50, 'manual', array(
                    'title' => 'Built for the way kids discover new skills',
                    'body' => 'YounGo exists to make online learning feel warmer, clearer, and more meaningful for families. Children get playful lessons and creative challenges. Parents get a platform that feels organized, trustworthy, and easy to understand.',
                    'cta' => array('label' => 'About YounGo', 'url' => '/home/about_us'),
                    'image' => array('url' => 'assets/frontend/youngo/images/demo-family-project.jpg', 'alt' => 'Children presenting a creative learning project to family and teachers'),
                    'stats' => array(
                        array('value' => '15 min', 'label' => 'short focused lessons'),
                        array('value' => '3 steps', 'label' => 'watch, make, reflect'),
                    ),
                    'items' => array(
                        'Short lessons designed for busy family routines',
                        'Creative projects that help kids show what they learned',
                        'Friendly course paths parents can explain in seconds',
                    ),
                )),
                'testimonials' => youngo_homepage_section_default('testimonials', 'Testimonials', 60, 'manual', array(
                    'title' => 'What parents want from YounGo',
                    'subtitle' => 'Family-focused learning should feel clear, safe, and exciting from the very first visit.',
                    'items' => array(
                        array('quote' => 'The course cards make it easy to choose something my child will actually enjoy, and the tone feels safe for families.', 'name' => 'Nadine A.', 'role' => 'Parent of a young learner', 'rating' => 5, 'image' => array('url' => '', 'alt' => '')),
                        array('quote' => 'It feels playful without becoming messy. I can see the learning path before my child starts.', 'name' => 'Omar K.', 'role' => 'Parent reviewer', 'rating' => 5, 'image' => array('url' => '', 'alt' => '')),
                    ),
                )),
                'faq_preview' => youngo_homepage_section_default('faq_preview', 'FAQ Preview', 70, 'mixed', array(
                    'title' => 'Questions parents ask before starting',
                    'subtitle' => 'Clear answers about safety, course fit, and how YounGo supports learning at home.',
                    'limit' => 4,
                    'cta' => array('label' => 'Visit support', 'url' => '/home/contact_us'),
                    'items' => array(
                        array('question' => 'Is YounGo designed specifically for children?', 'answer' => 'Yes. The homepage, course discovery, and content tone are shaped around young learners and parent trust.'),
                        array('question' => 'What can my child learn on YounGo?', 'answer' => 'YounGo can present coding, science, art, reading, math, and life-skill courses using the existing LMS course catalog.'),
                        array('question' => 'Can we start with a small learning commitment?', 'answer' => 'Yes. The experience is designed around short, focused lessons and clear course paths that fit after-school routines.'),
                        array('question' => 'Who manages the homepage content?', 'answer' => 'Admins can manage text, images, CTAs, selected courses, selected categories, visibility, and order from the YounGo homepage manager.'),
                    ),
                )),
                'blog_preview' => youngo_homepage_section_default('blog_preview', 'Blog Preview', 80, 'auto', array(
                    'title' => 'Helpful notes for families',
                    'subtitle' => 'Tips, updates, and learning ideas for parents.',
                    'blog_ids' => array(),
                    'limit' => 3,
                    'cta' => array('label' => 'Read more', 'url' => '/blog'),
                    'items' => array(
                        array('image' => array('url' => 'assets/frontend/youngo/images/demo-family-project.jpg', 'alt' => 'Family learning preview'), 'label' => 'Parenting Tips', 'title' => 'Simple ways to keep kids engaged', 'description' => 'Short ideas for turning everyday moments into useful learning routines.'),
                        array('image' => array('url' => 'assets/frontend/youngo/images/demo-course-coding.jpg', 'alt' => 'Coding learning preview'), 'label' => 'Platform News', 'title' => 'Creative coding starts with curiosity', 'description' => 'A parent-friendly look at how kids can begin building logic and confidence.'),
                        array('image' => array('url' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg', 'alt' => 'Science learning preview'), 'label' => 'Learning Ideas', 'title' => 'Safe science activities at home', 'description' => 'Easy experiments and guided questions that make discovery feel approachable.'),
                    ),
                )),
                'final_cta' => youngo_homepage_section_default('final_cta', 'Final CTA', 90, 'manual', array(
                    'title' => "Give your child a learning space that feels safe, creative, and worth returning to",
                    'subtitle' => 'Explore YounGo courses and help your child start with a topic that matches their curiosity.',
                    'primary_cta' => array('label' => 'Explore courses', 'url' => '/home/courses'),
                    'secondary_cta' => array('label' => 'Contact us', 'url' => '/home/contact_us'),
                )),
            ),
            'additional_sections' => array(),
        );
    }
}

if (!function_exists('youngo_homepage_normalize_source_type')) {
    function youngo_homepage_normalize_source_type($value, $default = 'manual')
    {
        return in_array($value, youngo_homepage_allowed_source_types()) ? $value : $default;
    }
}

if (!function_exists('youngo_homepage_normalize_bool')) {
    function youngo_homepage_normalize_bool($value)
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'on' || $value === 'true';
    }
}

if (!function_exists('youngo_homepage_normalize_int')) {
    function youngo_homepage_normalize_int($value, $default = 0, $min = 0, $max = 999)
    {
        $value = is_numeric($value) ? (int) $value : $default;
        return max($min, min($max, $value));
    }
}

if (!function_exists('youngo_homepage_plain_text')) {
    function youngo_homepage_plain_text($value)
    {
        return trim(strip_tags((string) $value));
    }
}

if (!function_exists('youngo_homepage_safe_url')) {
    function youngo_homepage_safe_url($value, $default = '')
    {
        $value = trim(strip_tags((string) $value));
        if ($value === '') {
            return $default;
        }

        if (strpos($value, '#') === 0 || strpos($value, '/') === 0) {
            return $value;
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        if (preg_match('/^[a-z0-9_%\/.-]+(\?[a-z0-9_=&%+.,~-]*)?$/i', $value)) {
            return '/' . ltrim($value, '/');
        }

        return $default;
    }
}

if (!function_exists('youngo_homepage_safe_image_path')) {
    function youngo_homepage_safe_image_path($value, $default = '')
    {
        $value = trim(strip_tags((string) $value));
        if ($value === '') {
            return $default;
        }

        $normalized_value = ltrim(str_replace('\\', '/', $value), '/');
        $demo_image_replacements = array(
            'assets/frontend/youngo/images/home/hero-learning-studio.svg' => 'assets/frontend/youngo/images/demo-hero-learning.jpg',
            'assets/frontend/youngo/images/hero-learning.webp' => 'assets/frontend/youngo/images/demo-hero-learning.jpg',
            'assets/frontend/youngo/images/about/family-learning-lab.svg' => 'assets/frontend/youngo/images/demo-family-project.jpg',
            'assets/frontend/youngo/images/blog-family.webp' => 'assets/frontend/youngo/images/demo-family-project.jpg',
            'assets/frontend/youngo/images/blog-coding.webp' => 'assets/frontend/youngo/images/demo-course-coding.jpg',
            'assets/frontend/youngo/images/blog-science.webp' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg',
            'assets/frontend/youngo/images/course-coding.webp' => 'assets/frontend/youngo/images/demo-course-coding.jpg',
            'assets/frontend/youngo/images/course-creative.webp' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg',
            'assets/frontend/youngo/images/course-space.webp' => 'assets/frontend/youngo/images/demo-course-robotics.jpg',
            'assets/frontend/youngo/images/courses/scratch-creators.svg' => 'assets/frontend/youngo/images/demo-course-coding.jpg',
            'assets/frontend/youngo/images/courses/space-science.svg' => 'assets/frontend/youngo/images/demo-course-robotics.jpg',
            'assets/frontend/youngo/images/courses/drawing-basics.svg' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg',
            'assets/frontend/youngo/images/courses/math-solving.svg' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg',
            'assets/frontend/youngo/images/courses/storytelling-confidence.svg' => 'assets/frontend/youngo/images/demo-family-project.jpg',
            'assets/frontend/youngo/images/courses/innovators-lab.svg' => 'assets/frontend/youngo/images/demo-family-project.jpg',
            'assets/frontend/youngo/images/categories/coding-kids.svg' => 'assets/frontend/youngo/images/demo-course-coding.jpg',
            'assets/frontend/youngo/images/categories/science-explorers.svg' => 'assets/frontend/youngo/images/demo-course-robotics.jpg',
            'assets/frontend/youngo/images/categories/creative-arts.svg' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg',
            'assets/frontend/youngo/images/categories/reading-storytelling.svg' => 'assets/frontend/youngo/images/demo-family-project.jpg',
            'assets/frontend/youngo/images/categories/math-adventures.svg' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg',
            'assets/frontend/youngo/images/categories/life-skills.svg' => 'assets/frontend/youngo/images/demo-family-project.jpg',
        );
        if (isset($demo_image_replacements[$normalized_value])) {
            return $demo_image_replacements[$normalized_value];
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        if (preg_match('/^[a-z0-9_\/.-]+\.(png|jpg|jpeg|webp|svg)$/i', $value)) {
            return ltrim($value, '/');
        }

        return $default;
    }
}

if (!function_exists('youngo_homepage_normalize_id_list')) {
    function youngo_homepage_normalize_id_list($value)
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (!is_array($value)) {
            return array();
        }

        $ids = array();
        foreach ($value as $id) {
            if (is_numeric($id) && (int) $id > 0) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }
}

if (!function_exists('youngo_homepage_normalize_additional_sections')) {
    function youngo_homepage_normalize_additional_sections($sections)
    {
        if (!is_array($sections)) {
            return array();
        }

        $allowed_types = youngo_homepage_allowed_additional_section_types();
        $normalized = array();

        foreach ($sections as $index => $section) {
            if (!is_array($section) || empty($section['type']) || !in_array($section['type'], $allowed_types)) {
                continue;
            }

            $type = $section['type'];
            $content = isset($section['content']) && is_array($section['content']) ? $section['content'] : array();
            $normalized[] = array(
                'id' => !empty($section['id']) ? youngo_homepage_plain_text($section['id']) : 'additional_' . ($index + 1),
                'type' => $type,
                'label' => !empty($section['label']) ? youngo_homepage_plain_text($section['label']) : ucwords(str_replace('_', ' ', $type)),
                'is_visible' => array_key_exists('is_visible', $section) ? youngo_homepage_normalize_bool($section['is_visible']) : true,
                'is_published' => array_key_exists('is_published', $section) ? youngo_homepage_normalize_bool($section['is_published']) : true,
                'sort_order' => youngo_homepage_normalize_int(isset($section['sort_order']) ? $section['sort_order'] : 100 + $index, 100 + $index, 0, 999),
                'source_type' => youngo_homepage_normalize_source_type(isset($section['source_type']) ? $section['source_type'] : 'manual'),
                'content' => youngo_homepage_deep_merge(youngo_homepage_additional_content_default($type), $content),
                'fallback' => isset($section['fallback']) && is_array($section['fallback']) ? $section['fallback'] : array('use_default_if_empty' => false),
            );
        }

        return $normalized;
    }
}

if (!function_exists('youngo_get_homepage_content')) {
    function youngo_get_homepage_content($include_unpublished = false)
    {
        $defaults = youngo_homepage_default_content();
        $raw_content = get_frontend_settings('youngo_homepage_content');

        if (!$raw_content) {
            return $defaults;
        }

        $stored = json_decode($raw_content, true);
        if (!is_array($stored)) {
            return $defaults;
        }

        $content = youngo_homepage_deep_merge($defaults, $stored);
        $stored_version = isset($stored['version']) && is_numeric($stored['version']) ? (int) $stored['version'] : 1;
        if ($stored_version < 2 && isset($content['sections']) && is_array($content['sections'])) {
            foreach (youngo_homepage_fixed_section_keys() as $section_key) {
                if (!isset($defaults['sections'][$section_key]['content'])) {
                    continue;
                }

                $legacy_content = isset($content['sections'][$section_key]['content']) && is_array($content['sections'][$section_key]['content']) ? $content['sections'][$section_key]['content'] : array();
                $content['sections'][$section_key]['content'] = $defaults['sections'][$section_key]['content'];

                if ($section_key === 'featured_categories') {
                    $content['sections'][$section_key]['content']['category_ids'] = isset($legacy_content['category_ids']) ? youngo_homepage_normalize_id_list($legacy_content['category_ids']) : array();
                    $content['sections'][$section_key]['content']['overrides'] = isset($legacy_content['overrides']) && is_array($legacy_content['overrides']) ? $legacy_content['overrides'] : array();
                }

                if ($section_key === 'featured_courses') {
                    $content['sections'][$section_key]['content']['course_ids'] = isset($legacy_content['course_ids']) ? youngo_homepage_normalize_id_list($legacy_content['course_ids']) : array();
                    $content['sections'][$section_key]['content']['category_ids'] = isset($legacy_content['category_ids']) ? youngo_homepage_normalize_id_list($legacy_content['category_ids']) : array();
                    $content['sections'][$section_key]['content']['overrides'] = isset($legacy_content['overrides']) && is_array($legacy_content['overrides']) ? $legacy_content['overrides'] : array();
                }
            }
            $content['version'] = $defaults['version'];
        }
        $content['additional_sections'] = youngo_homepage_normalize_additional_sections(isset($content['additional_sections']) ? $content['additional_sections'] : array());

        foreach (youngo_homepage_fixed_section_keys() as $section_key) {
            if (!isset($content['sections'][$section_key]) || !is_array($content['sections'][$section_key])) {
                $content['sections'][$section_key] = $defaults['sections'][$section_key];
            }
            $content['sections'][$section_key]['source_type'] = youngo_homepage_normalize_source_type($content['sections'][$section_key]['source_type'], $defaults['sections'][$section_key]['source_type']);
            $content['sections'][$section_key]['sort_order'] = youngo_homepage_normalize_int($content['sections'][$section_key]['sort_order'], $defaults['sections'][$section_key]['sort_order'], 0, 999);
        }

        if (!$include_unpublished && (!youngo_homepage_normalize_bool($content['is_active']) || !youngo_homepage_normalize_bool($content['is_published']))) {
            return $defaults;
        }

        return $content;
    }
}

if (!function_exists('youngo_homepage_ordered_sections')) {
    function youngo_homepage_ordered_sections($content)
    {
        $ordered = array();

        if (!isset($content['sections']) || !is_array($content['sections'])) {
            $content = youngo_homepage_default_content();
        }

        foreach (youngo_homepage_fixed_section_keys() as $key) {
            if (!isset($content['sections'][$key]) || !is_array($content['sections'][$key])) {
                continue;
            }
            $section = $content['sections'][$key];
            if (!youngo_homepage_normalize_bool($section['is_visible']) || !youngo_homepage_normalize_bool($section['is_published'])) {
                continue;
            }
            $ordered[] = array(
                'render_type' => 'fixed',
                'key' => $key,
                'sort_order' => youngo_homepage_normalize_int($section['sort_order'], 0, 0, 999),
                'section' => $section,
            );
        }

        $additional_sections = isset($content['additional_sections']) ? youngo_homepage_normalize_additional_sections($content['additional_sections']) : array();
        foreach ($additional_sections as $section) {
            if (!youngo_homepage_normalize_bool($section['is_visible']) || !youngo_homepage_normalize_bool($section['is_published'])) {
                continue;
            }
            $ordered[] = array(
                'render_type' => 'additional',
                'key' => $section['type'],
                'sort_order' => youngo_homepage_normalize_int($section['sort_order'], 100, 0, 999),
                'section' => $section,
            );
        }

        usort($ordered, function ($a, $b) {
            if ($a['sort_order'] == $b['sort_order']) {
                return strcmp($a['key'], $b['key']);
            }
            return $a['sort_order'] < $b['sort_order'] ? -1 : 1;
        });

        return $ordered;
    }
}

if (!function_exists('youngo_homepage_short_text')) {
    function youngo_homepage_short_text($value, $limit = 120)
    {
        $value = preg_replace('/\s+/', ' ', youngo_homepage_plain_text($value));
        if (strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(substr($value, 0, $limit - 1)) . '...';
    }
}

if (!function_exists('youngo_homepage_override_map')) {
    function youngo_homepage_override_map($overrides, $id_key)
    {
        $overrides = is_array($overrides) ? $overrides : array();
        $map = array();

        foreach ($overrides as $override) {
            if (!is_array($override) || empty($override[$id_key]) || !is_numeric($override[$id_key])) {
                continue;
            }

            $map[(int) $override[$id_key]] = $override;
        }

        return $map;
    }
}

if (!function_exists('youngo_homepage_demo_image_for_text')) {
    function youngo_homepage_demo_image_for_text($text, $type = 'category')
    {
        $text = strtolower(html_entity_decode((string) $text, ENT_QUOTES, 'UTF-8'));
        $specific_map = array(
            'creative coding' => 'assets/frontend/youngo/images/demo-category-creative-coding.jpg',
            'robotics and ai projects' => 'assets/frontend/youngo/images/demo-category-robotics-ai.jpg',
            'digital design' => 'assets/frontend/youngo/images/demo-category-digital-design.jpg',
            'stem challenges' => 'assets/frontend/youngo/images/demo-category-stem-challenges.jpg',
            'future skills' => 'assets/frontend/youngo/images/demo-category-future-skills.jpg',
        );
        foreach ($specific_map as $needle => $filename) {
            if (strpos($text, $needle) !== false) {
                return $filename;
            }
        }

        $map = array(
            'robot' => 'assets/frontend/youngo/images/demo-course-robotics.jpg',
            'ai' => 'assets/frontend/youngo/images/demo-course-robotics.jpg',
            'coding' => 'assets/frontend/youngo/images/demo-course-coding.jpg',
            'scratch' => 'assets/frontend/youngo/images/demo-course-coding.jpg',
            'science' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg',
            'stem' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg',
            'space' => 'assets/frontend/youngo/images/demo-course-robotics.jpg',
            'art' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg',
            'drawing' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg',
            'creative' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg',
            'reading' => 'assets/frontend/youngo/images/demo-family-project.jpg',
            'story' => 'assets/frontend/youngo/images/demo-family-project.jpg',
            'math' => 'assets/frontend/youngo/images/demo-course-creative-stem.jpg',
            'life' => 'assets/frontend/youngo/images/demo-family-project.jpg',
            'entrepreneur' => 'assets/frontend/youngo/images/demo-family-project.jpg',
            'innovator' => 'assets/frontend/youngo/images/demo-family-project.jpg',
        );

        foreach ($map as $needle => $filename) {
            if (strpos($text, $needle) !== false) {
                return $filename;
            }
        }

        return $type === 'course' ? 'assets/frontend/youngo/images/demo-course-coding.jpg' : 'assets/frontend/youngo/images/demo-hero-learning.jpg';
    }
}

if (!function_exists('youngo_homepage_category_image_path')) {
    function youngo_homepage_category_image_path($category)
    {
        $filename = '';
        if (!empty($category['parent']) && !empty($category['sub_category_thumbnail'])) {
            $filename = $category['sub_category_thumbnail'];
        } elseif (!empty($category['thumbnail'])) {
            $filename = $category['thumbnail'];
        } elseif (!empty($category['sub_category_thumbnail'])) {
            $filename = $category['sub_category_thumbnail'];
        }

        if ($filename !== '' && file_exists('uploads/thumbnails/category_thumbnails/' . $filename)) {
            return 'uploads/thumbnails/category_thumbnails/' . $filename;
        }

        $image_text = isset($category['youngo_canonical_name']) && $category['youngo_canonical_name'] !== '' ? $category['youngo_canonical_name'] : (isset($category['name']) ? $category['name'] : '');
        return youngo_homepage_demo_image_for_text($image_text, 'category');
    }
}

if (!function_exists('youngo_homepage_resolve_featured_categories')) {
    function youngo_homepage_resolve_featured_categories($section)
    {
        $section = is_array($section) ? $section : array();
        $content = isset($section['content']) && is_array($section['content']) ? $section['content'] : $section;
        $source_type = isset($section['source_type']) ? $section['source_type'] : 'mixed';
        $source_type = youngo_homepage_normalize_source_type($source_type, 'mixed');

        if ($source_type === 'manual') {
            return array();
        }

        $CI = &get_instance();
        $limit = youngo_homepage_normalize_int(isset($content['limit']) ? $content['limit'] : 4, 4, 1, 12);
        $selected_ids = youngo_homepage_normalize_id_list(isset($content['category_ids']) ? $content['category_ids'] : array());
        $overrides = youngo_homepage_override_map(isset($content['overrides']) ? $content['overrides'] : array(), 'category_id');

        $rows = array();
        if (!empty($selected_ids)) {
            $CI->db->where_in('id', $selected_ids);
            $query = $CI->db->get('category');
            $by_id = array();
            foreach ($query->result_array() as $row) {
                $by_id[(int) $row['id']] = $row;
            }
            foreach ($selected_ids as $id) {
                if (isset($by_id[$id])) {
                    $rows[] = $by_id[$id];
                }
            }
        }

        if (empty($rows)) {
            $active_counts = array();

            $CI->db->select('category_id, COUNT(*) AS total', false);
            $CI->db->where('status', 'active');
            $CI->db->where('category_id >', 0);
            $CI->db->group_by('category_id');
            foreach ($CI->db->get('course')->result_array() as $count_row) {
                $active_counts[(int) $count_row['category_id']] = (int) $count_row['total'];
            }

            $CI->db->select('sub_category_id, COUNT(*) AS total', false);
            $CI->db->where('status', 'active');
            $CI->db->where('sub_category_id >', 0);
            $CI->db->group_by('sub_category_id');
            foreach ($CI->db->get('course')->result_array() as $count_row) {
                $id = (int) $count_row['sub_category_id'];
                $active_counts[$id] = isset($active_counts[$id]) ? $active_counts[$id] + (int) $count_row['total'] : (int) $count_row['total'];
            }

            if (!empty($active_counts)) {
                arsort($active_counts);
                $active_category_ids = array_slice(array_keys($active_counts), 0, $limit);
                $CI->db->where_in('id', $active_category_ids);
                $query = $CI->db->get('category');
                $by_id = array();
                foreach ($query->result_array() as $row) {
                    $by_id[(int) $row['id']] = $row;
                }
                foreach ($active_category_ids as $id) {
                    if (isset($by_id[$id])) {
                        $rows[] = $by_id[$id];
                    }
                }
            }

            if (empty($rows)) {
                $CI->db->order_by('parent', 'asc');
                $CI->db->order_by('id', 'desc');
                $query = $CI->db->get('category', $limit);
                $rows = $query->result_array();
            }
        }

        if (empty($rows)) {
            return array();
        }

        if (function_exists('youngo_frontend_translate_category_rows')) {
            $rows = youngo_frontend_translate_category_rows($rows);
        }

        $category_ids = array();
        foreach ($rows as $row) {
            $category_ids[] = (int) $row['id'];
        }

        $counts = array();
        if (!empty($category_ids)) {
            $CI->db->select('category_id, COUNT(*) AS total', false);
            $CI->db->where('status', 'active');
            $CI->db->where_in('category_id', $category_ids);
            $CI->db->group_by('category_id');
            foreach ($CI->db->get('course')->result_array() as $count_row) {
                $counts[(int) $count_row['category_id']] = (int) $count_row['total'];
            }

            $CI->db->select('sub_category_id, COUNT(*) AS total', false);
            $CI->db->where('status', 'active');
            $CI->db->where_in('sub_category_id', $category_ids);
            $CI->db->group_by('sub_category_id');
            foreach ($CI->db->get('course')->result_array() as $count_row) {
                $id = (int) $count_row['sub_category_id'];
                $counts[$id] = isset($counts[$id]) ? $counts[$id] + (int) $count_row['total'] : (int) $count_row['total'];
            }
        }

        $resolved = array();
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $override = isset($overrides[$id]) ? $overrides[$id] : array();
            $course_count = isset($counts[$id]) ? $counts[$id] : 0;
            $description = isset($override['description']) && trim($override['description']) !== '' ? $override['description'] : ($course_count > 0 ? $course_count . ' courses' : 'Explore courses');
            $image = isset($override['image']) && is_array($override['image']) ? $override['image'] : array();
            $image_url = !empty($image['url']) ? $image['url'] : youngo_homepage_category_image_path($row);
            $slug = !empty($row['slug']) ? $row['slug'] : slugify($row['name']);
            $url = function_exists('youngo_frontend_courses_path') ? youngo_frontend_courses_path(null, 'category=' . rawurlencode($slug)) : '/home/courses?category=' . rawurlencode($slug);
            $icon_source = isset($row['youngo_canonical_name']) ? $row['youngo_canonical_name'] : $row['name'];

            $resolved[] = array(
                'id' => $id,
                'title' => $row['name'],
                'description' => $description,
                'meta' => $description,
                'icon_key' => strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $icon_source), 0, 2)),
                'url' => $url,
                'count' => $course_count,
                'image' => array(
                    'url' => $image_url,
                    'alt' => !empty($image['alt']) ? $image['alt'] : $row['name'],
                ),
            );

            if (count($resolved) >= $limit) {
                break;
            }
        }

        return $resolved;
    }
}

if (!function_exists('youngo_homepage_price_label')) {
    function youngo_homepage_price_label($course)
    {
        if (isset($course['youngo_access_mode']) && $course['youngo_access_mode'] === 'subscription_only' && empty($course['is_free_course'])) {
            return function_exists('youngo_frontend_phrase') ? youngo_frontend_phrase('subscription_access') : 'Subscription access';
        }

        if (!empty($course['is_free_course'])) {
            return get_phrase('Free');
        }

        $price = !empty($course['discount_flag']) && $course['discounted_price'] !== null ? $course['discounted_price'] : $course['price'];
        if (function_exists('currency')) {
            return currency($price);
        }

        return '$' . $price;
    }
}

if (!function_exists('youngo_homepage_resolve_featured_courses')) {
    function youngo_homepage_resolve_featured_courses($section)
    {
        $section = is_array($section) ? $section : array();
        $content = isset($section['content']) && is_array($section['content']) ? $section['content'] : $section;
        $source_type = isset($section['source_type']) ? $section['source_type'] : 'mixed';
        $source_type = youngo_homepage_normalize_source_type($source_type, 'mixed');

        if ($source_type === 'manual') {
            return array();
        }

        $CI = &get_instance();
        $limit = youngo_homepage_normalize_int(isset($content['limit']) ? $content['limit'] : 3, 3, 1, 12);
        $selected_ids = youngo_homepage_normalize_id_list(isset($content['course_ids']) ? $content['course_ids'] : array());
        $category_ids = youngo_homepage_normalize_id_list(isset($content['category_ids']) ? $content['category_ids'] : array());
        $overrides = youngo_homepage_override_map(isset($content['overrides']) ? $content['overrides'] : array(), 'course_id');

        $fetch_courses = function ($ids) use ($CI, $limit, $category_ids) {
            $CI->db->where('status', 'active');
            if (!empty($ids)) {
                $CI->db->where_in('id', $ids);
            }
            if (!empty($category_ids)) {
                $CI->db->group_start();
                $CI->db->where_in('category_id', $category_ids);
                $CI->db->or_where_in('sub_category_id', $category_ids);
                $CI->db->group_end();
            }
            $CI->db->order_by('is_top_course', 'desc');
            $CI->db->order_by('id', 'desc');
            if (empty($ids)) {
                $CI->db->limit($limit);
            }
            return $CI->db->get('course')->result_array();
        };

        $rows = $fetch_courses($selected_ids);
        if (!empty($selected_ids) && !empty($rows)) {
            $by_id = array();
            foreach ($rows as $row) {
                $by_id[(int) $row['id']] = $row;
            }
            $rows = array();
            foreach ($selected_ids as $id) {
                if (isset($by_id[$id])) {
                    $rows[] = $by_id[$id];
                }
            }
        }

        if (empty($rows)) {
            $rows = $fetch_courses(array());
        }

        if (empty($rows)) {
            return array();
        }

        if (function_exists('youngo_frontend_translate_course_rows')) {
            $rows = youngo_frontend_translate_course_rows($rows);
        }

        $course_ids = array();
        $user_ids = array();
        $course_category_ids = array();
        foreach ($rows as $row) {
            $course_ids[] = (int) $row['id'];
            $creator_id = !empty($row['creator']) ? (int) $row['creator'] : (int) $row['user_id'];
            if ($creator_id > 0) {
                $user_ids[] = $creator_id;
            }
            if (!empty($row['category_id'])) {
                $course_category_ids[] = (int) $row['category_id'];
            }
            if (!empty($row['sub_category_id'])) {
                $course_category_ids[] = (int) $row['sub_category_id'];
            }
        }
        $user_ids = array_values(array_unique($user_ids));
        $course_category_ids = array_values(array_unique($course_category_ids));

        $ratings = array();
        if (!empty($course_ids)) {
            $CI->db->select('ratable_id, COUNT(*) AS total_reviews, SUM(rating) AS total_rating', false);
            $CI->db->where('ratable_type', 'course');
            $CI->db->where_in('ratable_id', $course_ids);
            $CI->db->group_by('ratable_id');
            foreach ($CI->db->get('rating')->result_array() as $rating_row) {
                $review_count = (int) $rating_row['total_reviews'];
                $ratings[(int) $rating_row['ratable_id']] = array(
                    'rating' => $review_count > 0 ? round(((float) $rating_row['total_rating']) / $review_count, 1) : 0,
                    'review_count' => $review_count,
                );
            }
        }

        $users = array();
        if (!empty($user_ids)) {
            $CI->db->where_in('id', $user_ids);
            foreach ($CI->db->get('users')->result_array() as $user) {
                $users[(int) $user['id']] = trim($user['first_name'] . ' ' . $user['last_name']);
            }
        }

        $categories = array();
        if (!empty($course_category_ids)) {
            $CI->db->where_in('id', $course_category_ids);
            $category_rows = $CI->db->get('category')->result_array();
            if (function_exists('youngo_frontend_translate_category_rows')) {
                $category_rows = youngo_frontend_translate_category_rows($category_rows);
            }
            foreach ($category_rows as $category) {
                $categories[(int) $category['id']] = $category['name'];
            }
        }

        $resolved = array();
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $override = isset($overrides[$id]) ? $overrides[$id] : array();
            $image = isset($override['image']) && is_array($override['image']) ? $override['image'] : array();
            $creator_id = !empty($row['creator']) ? (int) $row['creator'] : (int) $row['user_id'];
            $rating = isset($ratings[$id]) ? $ratings[$id] : array('rating' => 0, 'review_count' => 0);
            $category_id = !empty($row['sub_category_id']) ? (int) $row['sub_category_id'] : (int) $row['category_id'];

            $course_image_url = !empty($image['url']) ? $image['url'] : $CI->crud_model->get_course_thumbnail_url($id);
            if (preg_match('/placeholder/i', $course_image_url) || !preg_match('/\.(png|jpg|jpeg|webp|svg)(\?.*)?$/i', $course_image_url)) {
                $course_image_url = youngo_homepage_demo_image_for_text($row['title'] . ' ' . (isset($categories[$category_id]) ? $categories[$category_id] : ''), 'course');
            }

            $resolved[] = array(
                'id' => $id,
                'title' => $row['title'],
                'description' => youngo_homepage_short_text($row['short_description'] !== '' ? $row['short_description'] : $row['description'], 130),
                'summary' => youngo_homepage_short_text($row['short_description'] !== '' ? $row['short_description'] : $row['description'], 130),
                'url' => function_exists('youngo_frontend_course_detail_path') ? youngo_frontend_course_detail_path(array_merge($row, array('title' => isset($row['youngo_canonical_title']) && $row['youngo_canonical_title'] !== '' ? $row['youngo_canonical_title'] : $row['title']))) : '/home/course/' . rawurlencode(slugify(isset($row['youngo_canonical_title']) && $row['youngo_canonical_title'] !== '' ? $row['youngo_canonical_title'] : $row['title'])) . '/' . $id,
                'image' => array(
                    'url' => $course_image_url,
                    'alt' => !empty($image['alt']) ? $image['alt'] : $row['title'],
                ),
                'badge' => !empty($override['badge']) ? $override['badge'] : (!empty($row['level']) ? $row['level'] : 'Course'),
                'age_range' => !empty($override['badge']) ? $override['badge'] : (!empty($row['level']) ? $row['level'] : 'Course'),
                'rating' => $rating['rating'] > 0 ? $rating['rating'] : 'New',
                'review_count' => $rating['review_count'],
                'instructor' => isset($users[$creator_id]) && $users[$creator_id] !== '' ? $users[$creator_id] : 'YounGo',
                'category' => isset($categories[$category_id]) ? $categories[$category_id] : '',
                'price_label' => youngo_homepage_price_label($row),
                'status' => isset($row['status']) ? $row['status'] : null,
                'youngo_access_mode' => isset($row['youngo_access_mode']) ? $row['youngo_access_mode'] : null,
                'is_free_course' => isset($row['is_free_course']) ? $row['is_free_course'] : null,
                'price' => isset($row['price']) ? $row['price'] : null,
                'discount_flag' => isset($row['discount_flag']) ? $row['discount_flag'] : null,
                'discounted_price' => isset($row['discounted_price']) ? $row['discounted_price'] : null,
            );

            if (count($resolved) >= $limit) {
                break;
            }
        }

        return $resolved;
    }
}

if (!function_exists('youngo_homepage_e')) {
    function youngo_homepage_e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('youngo_homepage_link')) {
    function youngo_homepage_link($value, $default = 'home')
    {
        $url = youngo_homepage_safe_url($value, '/' . ltrim($default, '/'));
        if (preg_match('/^https?:\/\//i', $url) || strpos($url, '#') === 0) {
            return $url;
        }

        if (!function_exists('youngo_frontend_public_url') && function_exists('get_instance') && file_exists(APPPATH . 'helpers/youngo_frontend_language_helper.php')) {
            $CI = &get_instance();
            if (isset($CI->load)) {
                $CI->load->helper('youngo_frontend_language');
            }
        }

        if (function_exists('youngo_frontend_public_url')) {
            return youngo_frontend_public_url(ltrim($url, '/'));
        }

        return site_url(ltrim($url, '/'));
    }
}

if (!function_exists('youngo_homepage_image_url')) {
    function youngo_homepage_image_url($value, $default)
    {
        $path = youngo_homepage_safe_image_path($value, $default);
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }
        return base_url(ltrim($path, '/'));
    }
}

// ------------------------------------------------------------------------
/* End of file common_helper.php */
/* Location: ./system/helpers/common.php */
