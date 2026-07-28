<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Youngo_coupon_evaluator_model extends CI_Model
{
    public $db;

    protected $table_exists_cache = array();
    protected $field_exists_cache = array();

    public function __construct($params = array())
    {
        parent::__construct();

        if (isset($params['db']) && is_object($params['db'])) {
            $this->db = $params['db'];
        } else {
            $this->db = $this->db_instance();
        }
    }

    public function evaluate_coupon_for_checkout($user_id, $item_type, $item_id, $original_amount, $coupon_code, $currency = 'EGP')
    {
        $currency = strtoupper(trim((string) $currency));
        $amount_result = $this->normalize_amount($original_amount);
        $normalized_code = $this->normalize_coupon_code($coupon_code);
        $item_type = $this->normalize_item_type($item_type);

        if ($currency !== 'EGP') {
            return $this->failure_result('unsupported_currency', 'YounGo coupons currently support EGP checkout only.', $normalized_code, null, null, null, $amount_result);
        }

        if (empty($amount_result['ok'])) {
            return $this->failure_result('invalid_original_amount', 'Original amount must be a positive numeric EGP value.', $normalized_code, null, null, null, $amount_result);
        }

        if (!$this->valid_id($user_id) || !$this->record_exists('users', (int) $user_id)) {
            return $this->failure_result('invalid_user', 'A valid checkout learner is required.', $normalized_code, null, null, null, $amount_result);
        }

        if (!in_array($item_type, array('course', 'subscription'), true)) {
            return $this->failure_result('invalid_item_type', 'Coupon checkout item type must be course or subscription.', $normalized_code, null, null, null, $amount_result);
        }

        if (!$this->valid_id($item_id) || !$this->item_exists($item_type, (int) $item_id)) {
            return $this->failure_result('invalid_item', 'A valid checkout item is required.', $normalized_code, null, null, null, $amount_result);
        }

        if ($normalized_code === '') {
            return $this->failure_result('empty_coupon_code', 'Coupon code is required.', $normalized_code, null, $item_type, (int) $item_id, $amount_result);
        }

        if (!$this->table_exists('coupons')) {
            return $this->failure_result('coupon_table_missing', 'Coupon table is not available.', $normalized_code, null, $item_type, (int) $item_id, $amount_result);
        }

        $coupon = $this->get_coupon_by_code($normalized_code);
        if (empty($coupon)) {
            return $this->failure_result('coupon_not_found', 'Coupon code was not found.', $normalized_code, null, $item_type, (int) $item_id, $amount_result);
        }

        $active = $this->coupon_is_active($coupon);
        if (empty($active['ok'])) {
            return $this->failure_result($active['error_code'], $active['message'], $normalized_code, $coupon, $item_type, (int) $item_id, $amount_result);
        }

        $expiry = $this->coupon_not_expired($coupon);
        if (empty($expiry['ok'])) {
            return $this->failure_result($expiry['error_code'], $expiry['message'], $normalized_code, $coupon, $item_type, (int) $item_id, $amount_result);
        }

        $start = $this->coupon_has_started($coupon);
        if (empty($start['ok'])) {
            return $this->failure_result($start['error_code'], $start['message'], $normalized_code, $coupon, $item_type, (int) $item_id, $amount_result);
        }

        $scope = $this->coupon_scope_allows_item($coupon, $item_type, (int) $item_id);
        if (empty($scope['ok'])) {
            return $this->failure_result($scope['error_code'], $scope['message'], $normalized_code, $coupon, $item_type, (int) $item_id, $amount_result, $scope);
        }

        $usage = $this->coupon_usage_available($coupon);
        if (empty($usage['ok'])) {
            return $this->failure_result($usage['error_code'], $usage['message'], $normalized_code, $coupon, $item_type, (int) $item_id, $amount_result, $scope, $usage);
        }

        $discount = $this->coupon_discount_definition($coupon);
        if (empty($discount['ok'])) {
            return $this->failure_result($discount['error_code'], $discount['message'], $normalized_code, $coupon, $item_type, (int) $item_id, $amount_result, $scope, $usage);
        }

        $calculation = $this->calculate_discount($amount_result['amount_float'], $discount['discount_type'], $discount['discount_value']);
        $final_amount = number_format($amount_result['amount_float'] - $calculation['discount_amount_float'], 2, '.', '');
        $zero_policy = ((float) $final_amount) <= 0.0;

        return array(
            'valid' => true,
            'coupon_id' => isset($coupon['id']) ? (int) $coupon['id'] : null,
            'coupon_code' => $normalized_code,
            'discount_type' => $discount['discount_type'],
            'discount_value' => number_format($discount['discount_value'], 2, '.', ''),
            'discount_amount' => number_format($calculation['discount_amount_float'], 2, '.', ''),
            'final_amount' => $final_amount,
            'currency' => 'EGP',
            'message' => 'Coupon evaluated successfully.',
            'error_code' => null,
            'zero_final_amount_policy_not_enabled' => $zero_policy,
            'snapshot' => $this->snapshot_array(true, null, 'Coupon evaluated successfully.', $normalized_code, $coupon, $item_type, (int) $item_id, $amount_result, $scope, $usage, $discount, $calculation, $final_amount, $zero_policy),
        );
    }

    protected function get_coupon_by_code($coupon_code)
    {
        $query = $this->db
            ->where('UPPER(`code`) = ' . $this->db->escape(strtoupper($coupon_code)), null, false)
            ->get('coupons', 1);

        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    protected function coupon_is_active($coupon)
    {
        if (!$this->field_exists('coupons', 'status')) {
            return $this->ok_result();
        }

        $status = strtolower(trim((string) $this->array_value($coupon, 'status', '')));
        if ($status !== 'active') {
            return $this->invalid_result('coupon_inactive', 'Coupon is not active.');
        }

        return $this->ok_result();
    }

    protected function coupon_not_expired($coupon)
    {
        if (!$this->field_exists('coupons', 'expiry_date')) {
            return $this->ok_result();
        }

        $expiry = $this->array_value($coupon, 'expiry_date');
        if (!is_numeric($expiry) || (int) $expiry <= 0) {
            return $this->invalid_result('coupon_expiry_missing', 'Coupon expiry date is missing.');
        }

        $today_start = strtotime(date('D, d-M-Y'));
        if ((int) $expiry < $today_start) {
            return $this->invalid_result('coupon_expired', 'Coupon is expired.');
        }

        return $this->ok_result();
    }

    protected function coupon_has_started($coupon)
    {
        if (!$this->field_exists('coupons', 'start_date')) {
            return $this->ok_result();
        }

        $start = $this->array_value($coupon, 'start_date');
        if ($start === null || $start === '' || (int) $start <= 0) {
            return $this->ok_result();
        }

        if ((int) $start > time()) {
            return $this->invalid_result('coupon_not_started', 'Coupon is not active yet.');
        }

        return $this->ok_result();
    }

    protected function coupon_scope_allows_item($coupon, $item_type, $item_id)
    {
        $scope = $this->field_exists('coupons', 'scope')
            ? strtolower(trim((string) $this->array_value($coupon, 'scope', 'both')))
            : 'both';

        if ($scope === '') {
            $scope = 'both';
        }

        $allowed_scopes = $item_type === 'course'
            ? array('both', 'all', 'course', 'course_purchase')
            : array('both', 'all', 'subscription', 'subscription_purchase');

        if (!in_array($scope, $allowed_scopes, true)) {
            return $this->invalid_result('coupon_scope_mismatch', 'Coupon scope does not apply to this checkout item.', array('scope' => $scope));
        }

        if ($item_type === 'course') {
            return $this->course_scope_allows_item($coupon, $item_id, $scope);
        }

        return $this->subscription_scope_allows_item($coupon, $item_id, $scope);
    }

    protected function course_scope_allows_item($coupon, $course_id, $scope)
    {
        $details = array(
            'scope' => $scope,
            'course_targeting' => 'not_configured',
        );

        if (!$this->table_exists('youngo_coupon_courses')) {
            return $this->ok_result($details);
        }

        $coupon_id = (int) $coupon['id'];
        $rows = $this->db
            ->where('coupon_id', $coupon_id)
            ->get('youngo_coupon_courses')
            ->result_array();

        if (empty($rows)) {
            return $this->ok_result($details);
        }

        $has_include_rows = false;
        $included = false;
        foreach ($rows as $row) {
            $rule_type = strtolower(trim((string) $this->array_value($row, 'rule_type')));
            if (!in_array($rule_type, array('include', 'exclude'), true)) {
                return $this->invalid_result('unsupported_course_scope_rule', 'Coupon course scope rule is unsupported.', array('rule_type' => $rule_type));
            }

            if ($rule_type === 'exclude' && (int) $row['course_id'] === (int) $course_id) {
                return $this->invalid_result('coupon_course_excluded', 'Coupon excludes this course.', array('scope' => $scope, 'course_targeting' => 'excluded'));
            }

            if ($rule_type === 'include') {
                $has_include_rows = true;
                if ((int) $row['course_id'] === (int) $course_id) {
                    $included = true;
                }
            }
        }

        if ($has_include_rows && !$included) {
            return $this->invalid_result('coupon_course_not_included', 'Coupon is not assigned to this course.', array('scope' => $scope, 'course_targeting' => 'include_miss'));
        }

        $details['course_targeting'] = $has_include_rows ? 'included' : 'exclude_checked';
        return $this->ok_result($details);
    }

    protected function subscription_scope_allows_item($coupon, $plan_id, $scope)
    {
        $details = array(
            'scope' => $scope,
            'plan_targeting' => 'not_configured',
        );

        if (!$this->table_exists('youngo_coupon_subscription_plans')) {
            return $this->ok_result($details);
        }

        $coupon_id = (int) $coupon['id'];
        $total = (int) $this->db
            ->where('coupon_id', $coupon_id)
            ->count_all_results('youngo_coupon_subscription_plans');

        if ($total <= 0) {
            return $this->ok_result($details);
        }

        $matched = (int) $this->db
            ->where('coupon_id', $coupon_id)
            ->where('plan_id', (int) $plan_id)
            ->count_all_results('youngo_coupon_subscription_plans');

        if ($matched <= 0) {
            return $this->invalid_result('coupon_plan_not_included', 'Coupon is not assigned to this subscription plan.', array('scope' => $scope, 'plan_targeting' => 'include_miss'));
        }

        $details['plan_targeting'] = 'included';
        return $this->ok_result($details);
    }

    protected function coupon_usage_available($coupon)
    {
        if (!$this->field_exists('coupons', 'max_usage_count')) {
            return $this->ok_result(array('usage_count' => null, 'max_usage_count' => null));
        }

        $max_usage = $this->array_value($coupon, 'max_usage_count');
        if ($max_usage === null || $max_usage === '' || (int) $max_usage <= 0) {
            return $this->ok_result(array('usage_count' => null, 'max_usage_count' => $max_usage));
        }

        if (!$this->table_exists('youngo_coupon_usages')) {
            return $this->invalid_result('coupon_usage_table_missing', 'Coupon usage table is required for this coupon.');
        }

        $count = (int) $this->db
            ->group_start()
            ->where('coupon_id', (int) $coupon['id'])
            ->or_where('coupon_code', (string) $coupon['code'])
            ->group_end()
            ->count_all_results('youngo_coupon_usages');

        if ($count >= (int) $max_usage) {
            return $this->invalid_result('coupon_usage_limit_reached', 'Coupon usage limit has been reached.', array('usage_count' => $count, 'max_usage_count' => (int) $max_usage));
        }

        return $this->ok_result(array('usage_count' => $count, 'max_usage_count' => (int) $max_usage));
    }

    protected function coupon_discount_definition($coupon)
    {
        $type = $this->field_exists('coupons', 'discount_type')
            ? strtolower(trim((string) $this->array_value($coupon, 'discount_type', 'percentage')))
            : 'percentage';

        if ($type === '') {
            $type = 'percentage';
        }

        if (!in_array($type, array('percentage', 'fixed'), true)) {
            return $this->invalid_result('unsupported_discount_type', 'Coupon discount type is unsupported.');
        }

        $value = null;
        if ($this->field_exists('coupons', 'discount_value') && $this->array_value($coupon, 'discount_value') !== null && $this->array_value($coupon, 'discount_value') !== '') {
            $value = $this->array_value($coupon, 'discount_value');
        } elseif ($type === 'percentage' && $this->array_value($coupon, 'discount_percentage') !== null && $this->array_value($coupon, 'discount_percentage') !== '') {
            $value = $this->array_value($coupon, 'discount_percentage');
        }

        if (!is_numeric($value) || (float) $value <= 0) {
            return $this->invalid_result('invalid_discount_value', 'Coupon discount value is invalid.');
        }

        return array(
            'ok' => true,
            'discount_type' => $type,
            'discount_value' => (float) $value,
            'message' => 'Coupon discount definition is valid.',
            'error_code' => null,
        );
    }

    protected function calculate_discount($original_amount, $discount_type, $discount_value)
    {
        $raw_discount = $discount_type === 'fixed'
            ? (float) $discount_value
            : ((float) $original_amount * (float) $discount_value) / 100;

        $clamped = false;
        if ($raw_discount > (float) $original_amount) {
            $raw_discount = (float) $original_amount;
            $clamped = true;
        }

        if ($raw_discount < 0) {
            $raw_discount = 0.0;
            $clamped = true;
        }

        return array(
            'discount_amount_float' => round($raw_discount, 2),
            'discount_clamped' => $clamped,
            'formula' => $discount_type === 'fixed'
                ? number_format((float) $discount_value, 2, '.', '') . ' fixed'
                : number_format((float) $original_amount, 2, '.', '') . ' * ' . number_format((float) $discount_value, 2, '.', '') . '%',
        );
    }

    protected function snapshot_array($valid, $error_code, $message, $coupon_code, $coupon, $item_type, $item_id, $amount_result, $scope = array(), $usage = array(), $discount = array(), $calculation = array(), $final_amount = null, $zero_policy = false)
    {
        return array(
            'evaluator_version' => 'PAYMENT.COUPON.EVALUATOR.1',
            'valid' => (bool) $valid,
            'error_code' => $error_code,
            'message' => $message,
            'coupon_id' => isset($coupon['id']) ? (int) $coupon['id'] : null,
            'coupon_code' => $coupon_code,
            'item_type' => $item_type,
            'item_id' => $item_id,
            'original_amount' => !empty($amount_result['amount_decimal']) ? $amount_result['amount_decimal'] : null,
            'discount_type' => isset($discount['discount_type']) ? $discount['discount_type'] : null,
            'discount_value' => isset($discount['discount_value']) ? number_format((float) $discount['discount_value'], 2, '.', '') : null,
            'discount_amount' => isset($calculation['discount_amount_float']) ? number_format((float) $calculation['discount_amount_float'], 2, '.', '') : '0.00',
            'final_amount' => $final_amount,
            'currency' => 'EGP',
            'scope' => isset($scope['data']) ? $scope['data'] : array(),
            'usage' => isset($usage['data']) ? $usage['data'] : array(),
            'calculation' => array(
                'formula' => isset($calculation['formula']) ? $calculation['formula'] : null,
                'discount_clamped' => !empty($calculation['discount_clamped']),
            ),
            'zero_final_amount_policy_not_enabled' => (bool) $zero_policy,
        );
    }

    protected function failure_result($error_code, $message, $coupon_code, $coupon = null, $item_type = null, $item_id = null, $amount_result = array(), $scope = array(), $usage = array())
    {
        $final_amount = !empty($amount_result['amount_decimal']) ? $amount_result['amount_decimal'] : null;

        return array(
            'valid' => false,
            'coupon_id' => is_array($coupon) && isset($coupon['id']) ? (int) $coupon['id'] : null,
            'coupon_code' => $coupon_code,
            'discount_type' => null,
            'discount_value' => null,
            'discount_amount' => '0.00',
            'final_amount' => $final_amount,
            'currency' => 'EGP',
            'message' => $message,
            'error_code' => $error_code,
            'zero_final_amount_policy_not_enabled' => false,
            'snapshot' => $this->snapshot_array(false, $error_code, $message, $coupon_code, is_array($coupon) ? $coupon : array(), $item_type, $item_id, $amount_result, $scope, $usage),
        );
    }

    protected function ok_result($data = array())
    {
        return array(
            'ok' => true,
            'message' => 'OK',
            'error_code' => null,
            'data' => is_array($data) ? $data : array(),
        );
    }

    protected function invalid_result($error_code, $message, $data = array())
    {
        return array(
            'ok' => false,
            'message' => $message,
            'error_code' => $error_code,
            'data' => is_array($data) ? $data : array(),
        );
    }

    protected function normalize_amount($amount)
    {
        if (!is_numeric($amount)) {
            return array('ok' => false);
        }

        $amount_float = (float) $amount;
        if ($amount_float <= 0) {
            return array('ok' => false);
        }

        return array(
            'ok' => true,
            'amount_float' => round($amount_float, 2),
            'amount_decimal' => number_format(round($amount_float, 2), 2, '.', ''),
        );
    }

    protected function normalize_coupon_code($coupon_code)
    {
        $coupon_code = trim((string) $coupon_code);
        $coupon_code = preg_replace('/\s+/', '', $coupon_code);
        $coupon_code = strtoupper($coupon_code);

        return strlen($coupon_code) > 255 ? substr($coupon_code, 0, 255) : $coupon_code;
    }

    protected function normalize_item_type($item_type)
    {
        $item_type = strtolower(trim((string) $item_type));
        if (in_array($item_type, array('course_purchase', 'course'), true)) {
            return 'course';
        }

        if (in_array($item_type, array('subscription_purchase', 'subscription', 'subscription_plan'), true)) {
            return 'subscription';
        }

        return $item_type;
    }

    protected function item_exists($item_type, $item_id)
    {
        if ($item_type === 'course') {
            return $this->record_exists('course', $item_id);
        }

        return $this->record_exists('youngo_subscription_plans', $item_id);
    }

    protected function record_exists($table, $id)
    {
        if (!$this->table_exists($table) || !$this->valid_id($id)) {
            return false;
        }

        return (int) $this->db
            ->where('id', (int) $id)
            ->count_all_results($table) > 0;
    }

    protected function valid_id($value)
    {
        return is_numeric($value) && (int) $value > 0;
    }

    protected function array_value($array, $key, $default = null)
    {
        return is_array($array) && array_key_exists($key, $array) ? $array[$key] : $default;
    }

    protected function table_exists($table)
    {
        if (!$this->safe_identifier($table)) {
            return false;
        }

        if (!array_key_exists($table, $this->table_exists_cache)) {
            $db = $this->db_instance();
            $this->table_exists_cache[$table] = $db ? $db->table_exists($table) : false;
        }

        return $this->table_exists_cache[$table];
    }

    protected function field_exists($table, $field)
    {
        if (!$this->safe_identifier($table) || !$this->safe_identifier($field) || !$this->table_exists($table)) {
            return false;
        }

        $cache_key = $table . '.' . $field;
        if (!array_key_exists($cache_key, $this->field_exists_cache)) {
            $db = $this->db_instance();
            $this->field_exists_cache[$cache_key] = $db ? $db->field_exists($field, $table) : false;
        }

        return $this->field_exists_cache[$cache_key];
    }

    protected function safe_identifier($value)
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9_]+$/', $value);
    }

    protected function db_instance()
    {
        if (isset($this->db) && is_object($this->db)) {
            return $this->db;
        }

        if (function_exists('get_instance')) {
            $ci = get_instance();
            if (isset($ci->db) && is_object($ci->db)) {
                return $ci->db;
            }
        }

        return null;
    }
}
