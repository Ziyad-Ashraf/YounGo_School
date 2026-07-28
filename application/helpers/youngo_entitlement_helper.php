<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('youngo_get_course_access_state')) {
    function youngo_get_course_access_state($user_id, $course_id, $context = array())
    {
        $CI = &get_instance();
        $CI->load->database();
        $CI->load->model('Youngo_entitlement_model', 'youngo_entitlement_model');

        $state = youngo_default_course_access_state($course_id);
        $state['course_access_mode'] = $CI->youngo_entitlement_model->get_course_access_mode($course_id);
        $state['subscription_eligible'] = $CI->youngo_entitlement_model->course_is_subscription_eligible($course_id);

        if ((int) $course_id <= 0) {
            $state['lock_reason'] = 'invalid_course';
            $state['message_key'] = 'access_none';
            return $state;
        }

        if ((int) $user_id <= 0) {
            $state['lock_reason'] = 'login_required';
            $state['message_key'] = 'access_login_required';
            return $state;
        }

        $allow_admin_bypass = !array_key_exists('allow_admin_bypass', $context) || (bool) $context['allow_admin_bypass'];
        $allow_instructor_bypass = !array_key_exists('allow_instructor_bypass', $context) || (bool) $context['allow_instructor_bypass'];

        if ($allow_admin_bypass && youngo_user_is_legacy_admin($user_id)) {
            return youngo_merge_course_access_state($state, array(
                'has_access' => true,
                'lesson_access_allowed' => true,
                'course_visible_in_my_courses' => false,
                'access_source' => 'admin',
                'status' => 'active',
                'is_lifetime' => true,
                'lock_reason' => null,
                'message_key' => 'access_admin',
            ));
        }

        if ($allow_instructor_bypass && $CI->youngo_entitlement_model->user_is_assigned_instructor($user_id, $course_id)) {
            return youngo_merge_course_access_state($state, array(
                'has_access' => true,
                'lesson_access_allowed' => true,
                'course_visible_in_my_courses' => false,
                'access_source' => 'instructor',
                'status' => 'active',
                'is_lifetime' => true,
                'lock_reason' => null,
                'message_key' => 'access_instructor',
            ));
        }

        $expired_state = null;

        $legacy_state = $CI->youngo_entitlement_model->get_legacy_enrol_state($user_id, $course_id);
        if ($legacy_state['status'] === 'active') {
            return youngo_state_from_source($state, $legacy_state, true);
        }
        $expired_state = youngo_prefer_inactive_state($expired_state, $legacy_state, true);

        $course_access_state = $CI->youngo_entitlement_model->get_course_access_state($user_id, $course_id);
        if ($course_access_state['status'] === 'active') {
            return youngo_state_from_source($state, $course_access_state, true);
        }
        $expired_state = youngo_prefer_inactive_state($expired_state, $course_access_state, true);

        if ($state['subscription_eligible']) {
            $subscription_state = $CI->youngo_entitlement_model->get_active_subscription_state($user_id);
            if ($subscription_state['status'] === 'active') {
                return youngo_state_from_source($state, $subscription_state, false);
            }
            $expired_state = youngo_prefer_inactive_state($expired_state, $subscription_state, false);
        }

        if ($expired_state !== null) {
            return youngo_state_from_source($state, $expired_state, $expired_state['course_visible_in_my_courses']);
        }

        if ($state['course_access_mode'] === 'purchase_only' && !$state['subscription_eligible']) {
            $state['lock_reason'] = 'purchase_only_not_in_subscription';
            $state['message_key'] = 'access_purchase_only_not_in_subscription';
        }

        return $state;
    }
}

if (!function_exists('youngo_can_access_course')) {
    function youngo_can_access_course($user_id, $course_id, $context = array())
    {
        $access_state = youngo_get_course_access_state($user_id, $course_id, $context);
        return !empty($access_state['has_access']);
    }
}

if (!function_exists('youngo_course_is_subscription_eligible')) {
    function youngo_course_is_subscription_eligible($course_id)
    {
        $CI = &get_instance();
        $CI->load->database();
        $CI->load->model('Youngo_entitlement_model', 'youngo_entitlement_model');

        return $CI->youngo_entitlement_model->course_is_subscription_eligible($course_id);
    }
}

if (!function_exists('youngo_get_course_access_message')) {
    function youngo_get_course_access_message($access_state)
    {
        $message_key = isset($access_state['message_key']) ? $access_state['message_key'] : 'access_none';
        $messages = array(
            'access_active' => 'Access is active.',
            'access_admin' => 'Access allowed for admin.',
            'access_instructor' => 'Access allowed for assigned instructor.',
            'access_expired' => 'Access has expired.',
            'access_subscription_expired' => 'Subscription access has expired.',
            'access_purchase_expired' => 'Course purchase access has expired.',
            'access_purchase_only_not_in_subscription' => 'This course is not included in subscription access.',
            'access_none' => 'No active access was found.',
            'access_login_required' => 'Log in to access this course.',
            'access_revoked' => 'Access has been revoked.',
        );

        return isset($messages[$message_key]) ? $messages[$message_key] : $messages['access_none'];
    }
}

if (!function_exists('youngo_is_access_warning_due')) {
    function youngo_is_access_warning_due($start_date, $expiry_date)
    {
        $start_date = youngo_timestamp_or_null($start_date);
        $expiry_date = youngo_timestamp_or_null($expiry_date);

        if (empty($start_date) || empty($expiry_date) || $expiry_date <= $start_date || time() >= $expiry_date) {
            return false;
        }

        return ((time() - $start_date) / ($expiry_date - $start_date)) >= 0.8;
    }
}

if (!function_exists('youngo_default_course_access_state')) {
    function youngo_default_course_access_state($course_id = null)
    {
        return array(
            'has_access' => false,
            'lesson_access_allowed' => false,
            'course_visible_in_my_courses' => false,
            'access_source' => 'none',
            'status' => 'none',
            'is_lifetime' => false,
            'start_date' => null,
            'expiry_date' => null,
            'warning_80_percent' => false,
            'course_access_mode' => null,
            'subscription_eligible' => false,
            'lock_reason' => null,
            'message_key' => 'access_none',
            'source_record_id' => null,
            'legacy_enrol_id' => null,
        );
    }
}

if (!function_exists('youngo_state_from_source')) {
    function youngo_state_from_source($base_state, $source_state, $visible_in_my_courses)
    {
        $message_key = youngo_message_key_for_source_state($source_state);

        return youngo_merge_course_access_state($base_state, array(
            'has_access' => !empty($source_state['has_access']),
            'lesson_access_allowed' => !empty($source_state['has_access']),
            'course_visible_in_my_courses' => (bool) $visible_in_my_courses,
            'access_source' => $source_state['access_source'],
            'status' => $source_state['status'],
            'is_lifetime' => !empty($source_state['is_lifetime']),
            'start_date' => $source_state['start_date'],
            'expiry_date' => $source_state['expiry_date'],
            'warning_80_percent' => !empty($source_state['warning_80_percent']),
            'lock_reason' => !empty($source_state['has_access']) ? null : $source_state['status'],
            'message_key' => $message_key,
            'source_record_id' => $source_state['source_record_id'],
            'legacy_enrol_id' => $source_state['legacy_enrol_id'],
        ));
    }
}

if (!function_exists('youngo_merge_course_access_state')) {
    function youngo_merge_course_access_state($base_state, $overrides)
    {
        foreach ($overrides as $key => $value) {
            $base_state[$key] = $value;
        }

        return $base_state;
    }
}

if (!function_exists('youngo_prefer_inactive_state')) {
    function youngo_prefer_inactive_state($current_state, $candidate_state, $visible_in_my_courses)
    {
        if (empty($candidate_state['found']) || $candidate_state['status'] === 'none') {
            return $current_state;
        }

        $candidate_state['course_visible_in_my_courses'] = (bool) $visible_in_my_courses;

        if ($current_state === null) {
            return $candidate_state;
        }

        return youngo_inactive_state_rank($candidate_state['status']) > youngo_inactive_state_rank($current_state['status'])
            ? $candidate_state
            : $current_state;
    }
}

if (!function_exists('youngo_inactive_state_rank')) {
    function youngo_inactive_state_rank($status)
    {
        if ($status === 'expired') {
            return 3;
        }

        if ($status === 'locked') {
            return 2;
        }

        if ($status === 'revoked') {
            return 1;
        }

        return 0;
    }
}

if (!function_exists('youngo_message_key_for_source_state')) {
    function youngo_message_key_for_source_state($source_state)
    {
        if (!empty($source_state['has_access'])) {
            return 'access_active';
        }

        if ($source_state['status'] === 'revoked') {
            return 'access_revoked';
        }

        if ($source_state['access_source'] === 'subscription' || $source_state['access_source'] === 'manual_grant') {
            return $source_state['status'] === 'expired' ? 'access_subscription_expired' : 'access_none';
        }

        if ($source_state['access_source'] === 'course_purchase') {
            return $source_state['status'] === 'expired' ? 'access_purchase_expired' : 'access_none';
        }

        return $source_state['status'] === 'expired' ? 'access_expired' : 'access_none';
    }
}

if (!function_exists('youngo_user_is_legacy_admin')) {
    function youngo_user_is_legacy_admin($user_id)
    {
        if ((int) $user_id <= 0) {
            return false;
        }

        $CI = &get_instance();
        $CI->load->database();
        $CI->db->select('role_id, status');
        $CI->db->where('id', (int) $user_id);
        $query = $CI->db->get('users', 1);

        if ($query->num_rows() === 0) {
            return false;
        }

        $user = $query->row_array();
        return (int) $user['role_id'] === 1 && (!isset($user['status']) || (int) $user['status'] === 1);
    }
}

if (!function_exists('youngo_timestamp_or_null')) {
    function youngo_timestamp_or_null($value)
    {
        if ($value === null || $value === '' || (int) $value <= 0) {
            return null;
        }

        return (int) $value;
    }
}
