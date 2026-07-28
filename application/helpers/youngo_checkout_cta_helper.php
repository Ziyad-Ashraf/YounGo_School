<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('youngo_checkout_cta_decision')) {
    function youngo_checkout_cta_decision($course, $user_id = 0, $options = array())
    {
        $course = is_array($course) ? $course : array();
        $options = is_array($options) ? $options : array();
        $config = isset($options['config_reader']) && is_object($options['config_reader'])
            ? $options['config_reader']
            : youngo_checkout_cta_config_reader();

        if (!$config || !method_exists($config, 'is_checkout_cta_enabled')) {
            return youngo_checkout_cta_result(false, 'config_unavailable', 'payment_disabled', 'subscription_checkout_is_not_available_yet', null, 'Checkout configuration is not available.');
        }

        if (!$config->is_checkout_cta_enabled()) {
            return youngo_checkout_cta_result(false, 'checkout_cta_disabled', 'disabled', 'subscription_checkout_is_not_available_yet', null, 'Checkout CTAs are disabled.');
        }

        if (!$config->is_checkout_routes_enabled()) {
            return youngo_checkout_cta_result(false, 'checkout_routes_disabled', 'disabled', 'subscription_checkout_is_not_available_yet', null, 'Checkout routes are disabled.');
        }

        if (!$config->is_checkout_local_testing_enabled()) {
            return youngo_checkout_cta_result(false, 'checkout_local_testing_disabled', 'disabled', 'subscription_checkout_is_not_available_yet', null, 'Local checkout testing is disabled.');
        }

        if ($config->get_mode() !== 'sandbox' || $config->get_currency() !== 'EGP') {
            return youngo_checkout_cta_result(false, 'checkout_config_not_local_egp_sandbox', 'payment_disabled', 'subscription_checkout_is_not_available_yet', null, 'Local checkout requires sandbox mode and EGP.');
        }

        $network_gate = youngo_checkout_cta_sandbox_network_gate($config, $options);
        if (empty($network_gate['ok'])) {
            return youngo_checkout_cta_result(false, $network_gate['code'], 'payment_disabled', 'subscription_checkout_is_not_available_yet', null, $network_gate['message']);
        }

        $course_id = youngo_checkout_cta_positive_int(isset($course['id']) ? $course['id'] : null);
        if (!$course_id) {
            return youngo_checkout_cta_result(false, 'invalid_course', 'disabled', 'course_not_found', null, 'A valid course is required.');
        }

        if (isset($course['status']) && (string) $course['status'] !== 'active') {
            return youngo_checkout_cta_result(false, 'course_not_active', 'disabled', 'subscription_checkout_is_not_available_yet', null, 'This course is not active for checkout.');
        }

        $mode = isset($course['youngo_access_mode']) ? trim((string) $course['youngo_access_mode']) : '';
        if ($mode === 'subscription_only') {
            return youngo_checkout_cta_result(false, 'course_subscription_only', 'subscription_only', 'subscription_checkout_is_not_available_yet', null, 'Subscription checkout is not available yet.');
        }

        if (!in_array($mode, array('purchase_only', 'subscription_and_purchase'), true)) {
            return youngo_checkout_cta_result(false, 'course_not_youngo_purchase_enabled', 'disabled', 'access_for_this_course_is_managed_by_your_school/admin.', null, 'This course is not enabled for YounGo purchase checkout.');
        }

        if (!empty($course['is_free_course'])) {
            return youngo_checkout_cta_result(false, 'free_course_no_purchase_checkout', 'free_course', 'access_for_this_course_is_managed_by_your_school/admin.', null, 'Free courses must not use YounGo purchase checkout.');
        }

        $amount = youngo_checkout_cta_course_amount($course);
        if ($amount <= 0) {
            return youngo_checkout_cta_result(false, 'invalid_checkout_amount', 'payment_disabled', 'subscription_checkout_is_not_available_yet', null, 'This course does not have a valid EGP checkout amount.');
        }

        $access_state = isset($options['access_state']) && is_array($options['access_state'])
            ? $options['access_state']
            : youngo_checkout_cta_access_state((int) $user_id, $course_id);

        if (!empty($access_state['has_access'])) {
            return youngo_checkout_cta_result(false, 'active_course_access_exists', 'already_has_access', 'start_now', null, 'This learner already has active access.');
        }

        if (!youngo_checkout_cta_current_user_is_learner((int) $user_id, $options)) {
            $result = youngo_checkout_cta_result(false, 'learner_login_required', 'login_required', 'log_in', null, 'Please sign in as a learner to continue checkout.');
            $result['login_url'] = youngo_checkout_cta_site_url('login');
            return $result;
        }

        $network_enabled = !empty($network_gate['network_enabled']);
        return youngo_checkout_cta_result(true, $network_enabled ? 'checkout_cta_available_sandbox_network' : 'checkout_cta_available_local', $network_enabled ? 'sandbox_network_ready' : 'local_only', 'continue_to_checkout', youngo_checkout_cta_site_url('youngo/checkout/start/' . $course_id), $network_enabled ? 'Sandbox Paymob checkout is available for local testing.' : 'Local sandbox checkout is available.', array(
            'course_id' => $course_id,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'EGP',
            'paymob_network' => $network_enabled ? 'sandbox_gated' : 'disabled',
        ));
    }
}

if (!function_exists('youngo_checkout_cta_sandbox_network_gate')) {
    function youngo_checkout_cta_sandbox_network_gate($config, $options = array())
    {
        $network_enabled = youngo_checkout_cta_config_bool($config, 'is_network_enabled', 'network_enabled');
        if (!$network_enabled) {
            return array(
                'ok' => true,
                'code' => 'checkout_local_no_network',
                'message' => 'Local checkout CTA can be shown without Paymob network execution.',
                'network_enabled' => false,
            );
        }

        if (!youngo_checkout_cta_config_bool($config, 'is_enabled', 'enabled')) {
            return array(
                'ok' => false,
                'code' => 'payment_enabled_required_for_sandbox_network',
                'message' => 'Sandbox network checkout requires payment enabled in ignored local/server config.',
                'network_enabled' => true,
            );
        }

        if (!youngo_checkout_cta_config_bool($config, 'is_sandbox_network_testing_enabled', 'sandbox_network_testing_enabled')) {
            return array(
                'ok' => false,
                'code' => 'paymob_network_must_remain_disabled',
                'message' => 'Sandbox network checkout requires the explicit sandbox network testing gate.',
                'network_enabled' => true,
            );
        }

        $readiness = isset($options['sandbox_readiness']) && is_array($options['sandbox_readiness'])
            ? $options['sandbox_readiness']
            : youngo_checkout_cta_paymob_sandbox_readiness($config);

        if (empty($readiness['ok'])) {
            return array(
                'ok' => false,
                'code' => isset($readiness['code']) ? (string) $readiness['code'] : 'paymob_sandbox_gates_not_ready',
                'message' => isset($readiness['message']) ? (string) $readiness['message'] : 'Paymob sandbox readiness is blocked.',
                'network_enabled' => true,
            );
        }

        return array(
            'ok' => true,
            'code' => 'paymob_sandbox_ready',
            'message' => 'Paymob sandbox network gates are ready.',
            'network_enabled' => true,
        );
    }
}

if (!function_exists('youngo_checkout_cta_config_bool')) {
    function youngo_checkout_cta_config_bool($config, $method, $key)
    {
        if (is_object($config) && method_exists($config, $method)) {
            return (bool) $config->$method();
        }

        if (is_object($config) && method_exists($config, 'get')) {
            return (bool) $config->get($key, false);
        }

        return false;
    }
}

if (!function_exists('youngo_checkout_cta_paymob_sandbox_readiness')) {
    function youngo_checkout_cta_paymob_sandbox_readiness($config)
    {
        if (!class_exists('Youngo_paymob_config') && defined('APPPATH') && is_file(APPPATH . 'libraries/Youngo_paymob_config.php')) {
            require_once APPPATH . 'libraries/Youngo_paymob_config.php';
        }

        if (!class_exists('Youngo_paymob_adapter') && defined('APPPATH') && is_file(APPPATH . 'libraries/Youngo_paymob_adapter.php')) {
            require_once APPPATH . 'libraries/Youngo_paymob_adapter.php';
        }

        if (!class_exists('Youngo_paymob_adapter') || !($config instanceof Youngo_paymob_config)) {
            return array(
                'ok' => false,
                'code' => 'paymob_sandbox_readiness_unavailable',
                'message' => 'Paymob sandbox readiness could not be loaded.',
            );
        }

        $dashboard_config = array();
        $dashboard_config_exists = false;

        if (function_exists('get_instance')) {
            try {
                $CI = &get_instance();
                if (!isset($CI->youngo_payment_config_model) && isset($CI->load) && is_object($CI->load)) {
                    $CI->load->model('Youngo_payment_config_model', 'youngo_payment_config_model');
                }

                if (isset($CI->youngo_payment_config_model) && is_object($CI->youngo_payment_config_model)) {
                    $result = $CI->youngo_payment_config_model->get_provider_config('paymob', 'sandbox');
                    if (!empty($result['ok']) && !empty($result['data']['config']) && !empty($result['data']['exists'])) {
                        $dashboard_config = $result['data']['config'];
                        $dashboard_config_exists = true;
                    }
                }
            } catch (Throwable $exception) {
                return array(
                    'ok' => false,
                    'code' => 'paymob_sandbox_readiness_unavailable',
                    'message' => 'Paymob sandbox readiness could not be loaded.',
                );
            }
        }

        $adapter = new Youngo_paymob_adapter(array(
            'config_reader' => $config,
            'dashboard_config' => $dashboard_config,
            'dashboard_config_exists' => $dashboard_config_exists,
        ));

        return $adapter->get_sandbox_readiness();
    }
}

if (!function_exists('youngo_checkout_cta_config_reader')) {
    function youngo_checkout_cta_config_reader()
    {
        if (!class_exists('Youngo_paymob_config') && defined('APPPATH') && is_file(APPPATH . 'libraries/Youngo_paymob_config.php')) {
            require_once APPPATH . 'libraries/Youngo_paymob_config.php';
        }

        return class_exists('Youngo_paymob_config') ? new Youngo_paymob_config(array('load_local_override' => true)) : null;
    }
}

if (!function_exists('youngo_checkout_cta_access_state')) {
    function youngo_checkout_cta_access_state($user_id, $course_id)
    {
        if ((int) $user_id <= 0 || (int) $course_id <= 0) {
            return array('has_access' => false);
        }

        if (!function_exists('youngo_get_course_access_state') && defined('APPPATH') && is_file(APPPATH . 'helpers/youngo_entitlement_helper.php')) {
            require_once APPPATH . 'helpers/youngo_entitlement_helper.php';
        }

        if (function_exists('youngo_get_course_access_state')) {
            try {
                return youngo_get_course_access_state((int) $user_id, (int) $course_id, array(
                    'allow_admin_bypass' => false,
                    'allow_instructor_bypass' => false,
                ));
            } catch (Throwable $exception) {
                return array('has_access' => false, 'error' => 'access_check_failed');
            }
        }

        return array('has_access' => false);
    }
}

if (!function_exists('youngo_checkout_cta_current_user_is_learner')) {
    function youngo_checkout_cta_current_user_is_learner($user_id, $options = array())
    {
        if ((int) $user_id <= 0) {
            return false;
        }

        if (array_key_exists('is_learner', $options)) {
            return (bool) $options['is_learner'];
        }

        if (!function_exists('get_instance')) {
            return false;
        }

        $CI = &get_instance();
        if (!isset($CI->session) || !is_object($CI->session)) {
            return false;
        }

        return (int) $CI->session->userdata('user_login') === 1
            && (int) $CI->session->userdata('admin_login') !== 1
            && (int) $CI->session->userdata('user_id') === (int) $user_id
            && (int) $CI->session->userdata('role_id') === 2;
    }
}

if (!function_exists('youngo_checkout_cta_course_amount')) {
    function youngo_checkout_cta_course_amount($course)
    {
        if (!is_array($course)) {
            return 0.0;
        }

        if (!empty($course['discount_flag']) && isset($course['discounted_price']) && (float) $course['discounted_price'] > 0) {
            return (float) $course['discounted_price'];
        }

        return isset($course['price']) ? (float) $course['price'] : 0.0;
    }
}

if (!function_exists('youngo_checkout_cta_result')) {
    function youngo_checkout_cta_result($show_cta, $reason_code, $state, $label, $target_url = null, $disabled_message = '', $extra = array())
    {
        $result = array(
            'show_cta' => (bool) $show_cta,
            'reason_code' => (string) $reason_code,
            'state' => (string) $state,
            'label' => (string) $label,
            'label_text' => youngo_checkout_cta_label_text($label),
            'target_url' => $show_cta ? (string) $target_url : null,
            'disabled_message' => (string) $disabled_message,
        );

        foreach ($extra as $key => $value) {
            $result[$key] = $value;
        }

        return $result;
    }
}

if (!function_exists('youngo_checkout_cta_label_text')) {
    function youngo_checkout_cta_label_text($label)
    {
        $labels = array(
            'continue_to_checkout' => 'Continue to checkout',
            'log_in' => 'Log in',
            'start_now' => 'Start now',
            'subscription_checkout_is_not_available_yet' => 'Subscription checkout is not available yet',
            'access_for_this_course_is_managed_by_your_school/admin.' => 'Access for this course is managed by your school/admin.',
            'course_not_found' => 'Course not found',
        );

        return isset($labels[$label]) ? $labels[$label] : ucfirst(str_replace('_', ' ', (string) $label));
    }
}

if (!function_exists('youngo_checkout_cta_site_url')) {
    function youngo_checkout_cta_site_url($path)
    {
        return function_exists('site_url') ? site_url($path) : '/' . ltrim((string) $path, '/');
    }
}

if (!function_exists('youngo_checkout_cta_positive_int')) {
    function youngo_checkout_cta_positive_int($value)
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
