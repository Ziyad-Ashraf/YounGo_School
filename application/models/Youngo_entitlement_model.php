<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Youngo_entitlement_model extends CI_Model
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
            $CI = get_instance();
            if (isset($CI->db) && is_object($CI->db)) {
                $this->db = $CI->db;
            }
        }
    }

    public function phase_2_tables_available()
    {
        return $this->table_exists('youngo_course_access')
            && $this->table_exists('youngo_user_subscriptions')
            && $this->table_exists('youngo_subscription_plans');
    }

    public function get_legacy_enrol_state($user_id, $course_id)
    {
        $state = $this->empty_source_state('legacy_enrol');

        if (!$this->valid_id($user_id) || !$this->valid_id($course_id) || !$this->table_exists('enrol')) {
            return $state;
        }

        $this->db->where('user_id', (int) $user_id);
        $this->db->where('course_id', (int) $course_id);
        $this->db->order_by('id', 'desc');
        $query = $this->db->get('enrol', 1);

        if ($query->num_rows() === 0) {
            return $state;
        }

        $row = $query->row_array();
        $expiry_date = $this->timestamp_or_null(isset($row['expiry_date']) ? $row['expiry_date'] : null);
        $start_date = $this->timestamp_or_null(isset($row['date_added']) ? $row['date_added'] : null);
        $is_lifetime = empty($expiry_date);

        $state['found'] = true;
        $state['source_record_id'] = isset($row['id']) ? (int) $row['id'] : null;
        $state['legacy_enrol_id'] = isset($row['id']) ? (int) $row['id'] : null;
        $state['start_date'] = $start_date;
        $state['expiry_date'] = $expiry_date;
        $state['is_lifetime'] = $is_lifetime;
        $state['status'] = ($is_lifetime || $expiry_date >= time()) ? 'active' : 'expired';
        $state['has_access'] = ($state['status'] === 'active');
        $state['warning_80_percent'] = (!$is_lifetime && $state['has_access'])
            ? $this->warning_due($start_date, $expiry_date)
            : false;

        return $state;
    }

    public function get_course_access_state($user_id, $course_id)
    {
        $state = $this->empty_source_state('course_purchase');

        if (!$this->valid_id($user_id) || !$this->valid_id($course_id) || !$this->table_exists('youngo_course_access')) {
            return $state;
        }

        $this->db->where('user_id', (int) $user_id);
        $this->db->where('course_id', (int) $course_id);
        $this->db->order_by('id', 'desc');
        $query = $this->db->get('youngo_course_access');

        if ($query->num_rows() === 0) {
            return $state;
        }

        $fallback = null;
        foreach ($query->result_array() as $row) {
            $candidate = $this->normalize_course_access_row($row);
            if ($candidate['status'] === 'active') {
                return $candidate;
            }

            if ($fallback === null || $this->source_status_rank($candidate['status']) > $this->source_status_rank($fallback['status'])) {
                $fallback = $candidate;
            }
        }

        return $fallback ?: $state;
    }

    public function get_active_subscription_state($user_id)
    {
        $state = $this->empty_source_state('subscription');

        if (!$this->valid_id($user_id) || !$this->table_exists('youngo_user_subscriptions')) {
            return $state;
        }

        $this->db->where('user_id', (int) $user_id);
        $this->db->order_by('id', 'desc');
        $query = $this->db->get('youngo_user_subscriptions');

        if ($query->num_rows() === 0) {
            return $state;
        }

        $fallback = null;
        foreach ($query->result_array() as $row) {
            $candidate = $this->normalize_subscription_row($row);
            if ($candidate['status'] === 'active') {
                return $candidate;
            }

            if ($fallback === null || $this->source_status_rank($candidate['status']) > $this->source_status_rank($fallback['status'])) {
                $fallback = $candidate;
            }
        }

        return $fallback ?: $state;
    }

    public function course_is_subscription_eligible($course_id)
    {
        if (!$this->valid_id($course_id) || !$this->table_exists('course')) {
            return false;
        }

        $this->db->where('id', (int) $course_id);
        $query = $this->db->get('course', 1);

        if ($query->num_rows() === 0) {
            return false;
        }

        $course = $query->row_array();

        if ($this->field_exists('course', 'youngo_subscription_excluded') && (int) $course['youngo_subscription_excluded'] === 1) {
            return false;
        }

        if ($this->field_exists('course', 'youngo_access_mode')) {
            return isset($course['youngo_access_mode']) && $course['youngo_access_mode'] !== 'purchase_only';
        }

        return false;
    }

    public function user_is_assigned_instructor($user_id, $course_id)
    {
        if (!$this->valid_id($user_id) || !$this->valid_id($course_id) || !$this->table_exists('course')) {
            return false;
        }

        $this->db->select('creator, user_id');
        $this->db->where('id', (int) $course_id);
        $query = $this->db->get('course', 1);

        if ($query->num_rows() === 0) {
            return false;
        }

        $course = $query->row_array();
        if (isset($course['creator']) && (string) $course['creator'] === (string) $user_id) {
            return true;
        }

        $assigned_ids = array_filter(array_map('trim', explode(',', (string) $course['user_id'])));
        return in_array((string) $user_id, $assigned_ids, true);
    }

    public function get_course_access_mode($course_id)
    {
        if (!$this->valid_id($course_id) || !$this->table_exists('course') || !$this->field_exists('course', 'youngo_access_mode')) {
            return null;
        }

        $this->db->select('youngo_access_mode');
        $this->db->where('id', (int) $course_id);
        $query = $this->db->get('course', 1);

        if ($query->num_rows() === 0) {
            return null;
        }

        return $query->row('youngo_access_mode');
    }

    public function get_learner_course_access_items($user_id)
    {
        $items = array();

        if (!$this->valid_id($user_id) || !$this->table_exists('course')) {
            return $items;
        }

        $legacy_rows = $this->get_learner_legacy_enrol_rows($user_id);
        foreach ($legacy_rows as $row) {
            $course_id = isset($row['course_id']) ? (int) $row['course_id'] : 0;
            if (!$this->valid_id($course_id)) {
                continue;
            }

            $state = $this->normalize_legacy_enrol_row($row);
            if ($state['status'] !== 'active') {
                continue;
            }

            $course = $this->get_course_row($course_id);
            if (empty($course)) {
                continue;
            }

            $items[$course_id] = $this->build_learner_course_item($user_id, $course, $state);
        }

        $course_access_rows = $this->get_learner_youngo_course_access_rows($user_id);
        foreach ($course_access_rows as $row) {
            $course_id = isset($row['course_id']) ? (int) $row['course_id'] : 0;
            if (!$this->valid_id($course_id) || isset($items[$course_id])) {
                continue;
            }

            $state = $this->normalize_course_access_row($row);
            if ($state['status'] !== 'active') {
                continue;
            }

            $course = $this->get_course_row($course_id);
            if (empty($course)) {
                continue;
            }

            $items[$course_id] = $this->build_learner_course_item($user_id, $course, $state);
        }

        return array_values($items);
    }

    public function get_learner_subscription_summary($user_id)
    {
        $summary = array(
            'active' => array(),
            'latest_inactive' => null,
            'total_count' => 0,
            'active_count' => 0,
            'has_active' => false,
        );

        if (!$this->valid_id($user_id) || !$this->table_exists('youngo_user_subscriptions')) {
            return $summary;
        }

        $this->db->select('s.*, p.name AS plan_name, p.slug AS plan_slug');
        $this->db->from('youngo_user_subscriptions s');
        $this->db->join('youngo_subscription_plans p', 'p.id = s.plan_id', 'left');
        $this->db->where('s.user_id', (int) $user_id);
        $this->db->order_by('s.id', 'desc');
        $query = $this->db->get();

        if (!$query) {
            return $summary;
        }

        foreach ($query->result_array() as $row) {
            $item = $this->build_learner_subscription_item($row);
            $summary['total_count']++;

            if ($item['status'] === 'active') {
                $summary['active'][] = $item;
                $summary['active_count']++;
                $summary['has_active'] = true;
            } elseif ($summary['latest_inactive'] === null) {
                $summary['latest_inactive'] = $item;
            }
        }

        return $summary;
    }

    public function get_learner_access_counts($user_id)
    {
        $course_items = $this->get_learner_course_access_items($user_id);
        $subscription_summary = $this->get_learner_subscription_summary($user_id);
        $counts = array(
            'active_course_count' => count($course_items),
            'legacy_enrol_count' => 0,
            'manual_course_grant_count' => 0,
            'course_access_count' => 0,
            'active_subscription_count' => isset($subscription_summary['active_count']) ? (int) $subscription_summary['active_count'] : 0,
            'total_subscription_count' => isset($subscription_summary['total_count']) ? (int) $subscription_summary['total_count'] : 0,
        );

        foreach ($course_items as $item) {
            $source = isset($item['access_source']) ? $item['access_source'] : '';
            if ($source === 'legacy_enrol') {
                $counts['legacy_enrol_count']++;
            } elseif ($source === 'manual_grant') {
                $counts['manual_course_grant_count']++;
            } else {
                $counts['course_access_count']++;
            }
        }

        return $counts;
    }

    public function get_admin_user_entitlement_summary($user_id)
    {
        $summary = array(
            'user_id' => (int) $user_id,
            'legacy_enrol_count' => 0,
            'active_course_access_count' => 0,
            'revoked_course_access_count' => 0,
            'expired_course_access_count' => 0,
            'active_subscription_count' => 0,
            'revoked_subscription_count' => 0,
            'expired_subscription_count' => 0,
            'latest_active_subscription' => null,
            'recent_manual_grants' => array(),
            'manual_grants_url' => site_url('admin/youngo/manual-grants?user_id=' . (int) $user_id),
        );

        if (!$this->valid_id($user_id)) {
            return $summary;
        }

        if ($this->table_exists('enrol')) {
            $this->db->where('user_id', (int) $user_id);
            $summary['legacy_enrol_count'] = (int) $this->db->count_all_results('enrol');
        }

        if ($this->table_exists('youngo_course_access')) {
            $rows = $this->get_admin_course_access_rows_for_user($user_id);
            foreach ($rows as $row) {
                $state = $this->normalize_course_access_row($row);
                if ($state['status'] === 'active') {
                    $summary['active_course_access_count']++;
                } elseif ($state['status'] === 'revoked') {
                    $summary['revoked_course_access_count']++;
                } elseif ($state['status'] === 'expired') {
                    $summary['expired_course_access_count']++;
                }
            }
        }

        if ($this->table_exists('youngo_user_subscriptions')) {
            $rows = $this->get_admin_subscription_rows_for_user($user_id);
            foreach ($rows as $row) {
                $item = $this->build_admin_subscription_summary_item($row);
                if ($item['status'] === 'active') {
                    $summary['active_subscription_count']++;
                    if ($summary['latest_active_subscription'] === null) {
                        $summary['latest_active_subscription'] = $item;
                    }
                } elseif ($item['status'] === 'revoked') {
                    $summary['revoked_subscription_count']++;
                } elseif ($item['status'] === 'expired') {
                    $summary['expired_subscription_count']++;
                }
            }
        }

        $summary['recent_manual_grants'] = $this->get_admin_recent_manual_grants_for_user($user_id, 5);

        return $summary;
    }

    public function get_admin_course_entitlement_summary($course_id)
    {
        $summary = array(
            'course_id' => (int) $course_id,
            'legacy_enrol_count' => 0,
            'active_course_access_count' => 0,
            'revoked_course_access_count' => 0,
            'expired_course_access_count' => 0,
            'youngo_access_mode' => null,
            'subscription_eligible' => false,
            'recent_manual_grants' => array(),
            'manual_grants_url' => site_url('admin/youngo/manual-grants?course_id=' . (int) $course_id),
        );

        if (!$this->valid_id($course_id)) {
            return $summary;
        }

        if ($this->table_exists('enrol')) {
            $this->db->where('course_id', (int) $course_id);
            $summary['legacy_enrol_count'] = (int) $this->db->count_all_results('enrol');
        }

        if ($this->table_exists('youngo_course_access')) {
            $rows = $this->get_admin_course_access_rows_for_course($course_id);
            foreach ($rows as $row) {
                $state = $this->normalize_course_access_row($row);
                if ($state['status'] === 'active') {
                    $summary['active_course_access_count']++;
                } elseif ($state['status'] === 'revoked') {
                    $summary['revoked_course_access_count']++;
                } elseif ($state['status'] === 'expired') {
                    $summary['expired_course_access_count']++;
                }
            }
        }

        $summary['youngo_access_mode'] = $this->get_course_access_mode($course_id);
        $summary['subscription_eligible'] = $this->course_is_subscription_eligible($course_id);
        $summary['recent_manual_grants'] = $this->get_admin_recent_manual_grants_for_course($course_id, 5);

        return $summary;
    }

    public function get_admin_recent_manual_grants_for_user($user_id, $limit = 5)
    {
        if (!$this->valid_id($user_id) || !$this->table_exists('youngo_manual_grants')) {
            return array();
        }

        return $this->get_admin_recent_manual_grants(array('g.granted_to_user_id' => (int) $user_id), $limit);
    }

    public function get_admin_recent_manual_grants_for_course($course_id, $limit = 5)
    {
        if (!$this->valid_id($course_id) || !$this->table_exists('youngo_manual_grants')) {
            return array();
        }

        return $this->get_admin_recent_manual_grants(array('g.course_id' => (int) $course_id), $limit);
    }

    protected function normalize_course_access_row($row)
    {
        $state = $this->empty_source_state($this->course_access_source($row));
        $expiry_date = $this->timestamp_or_null(isset($row['expiry_date']) ? $row['expiry_date'] : null);
        $start_date = $this->timestamp_or_null(isset($row['start_date']) ? $row['start_date'] : null);
        $is_lifetime = isset($row['is_lifetime']) && (int) $row['is_lifetime'] === 1;
        $status = isset($row['status']) ? $row['status'] : 'active';

        if (!empty($row['revoked_at']) || $status === 'revoked') {
            $normalized_status = 'revoked';
        } elseif ($status !== 'active') {
            $normalized_status = $status;
        } elseif (!$is_lifetime && !empty($expiry_date) && $expiry_date < time()) {
            $normalized_status = 'expired';
        } else {
            $normalized_status = 'active';
        }

        $state['found'] = true;
        $state['status'] = $normalized_status;
        $state['has_access'] = ($normalized_status === 'active');
        $state['is_lifetime'] = $is_lifetime;
        $state['start_date'] = $start_date;
        $state['expiry_date'] = $expiry_date;
        $state['source_record_id'] = isset($row['id']) ? (int) $row['id'] : null;
        $state['legacy_enrol_id'] = isset($row['enrol_id']) && $row['enrol_id'] !== null ? (int) $row['enrol_id'] : null;
        $state['warning_80_percent'] = (!$is_lifetime && $state['has_access'])
            ? $this->warning_due($start_date, $expiry_date)
            : false;

        return $state;
    }

    protected function normalize_legacy_enrol_row($row)
    {
        $state = $this->empty_source_state('legacy_enrol');
        $expiry_date = $this->timestamp_or_null(isset($row['expiry_date']) ? $row['expiry_date'] : null);
        $start_date = $this->timestamp_or_null(isset($row['date_added']) ? $row['date_added'] : null);
        $is_lifetime = empty($expiry_date);

        $state['found'] = true;
        $state['status'] = ($is_lifetime || $expiry_date >= time()) ? 'active' : 'expired';
        $state['has_access'] = ($state['status'] === 'active');
        $state['is_lifetime'] = $is_lifetime;
        $state['start_date'] = $start_date;
        $state['expiry_date'] = $expiry_date;
        $state['source_record_id'] = isset($row['id']) ? (int) $row['id'] : null;
        $state['legacy_enrol_id'] = isset($row['id']) ? (int) $row['id'] : null;
        $state['warning_80_percent'] = (!$is_lifetime && $state['has_access'])
            ? $this->warning_due($start_date, $expiry_date)
            : false;

        return $state;
    }

    protected function normalize_subscription_row($row)
    {
        $state = $this->empty_source_state($this->subscription_access_source($row));
        $expiry_date = $this->timestamp_or_null(isset($row['expiry_date']) ? $row['expiry_date'] : null);
        $start_date = $this->timestamp_or_null(isset($row['start_date']) ? $row['start_date'] : null);
        $status = isset($row['status']) ? $row['status'] : 'active';

        if (!empty($row['revoked_at']) || $status === 'revoked') {
            $normalized_status = 'revoked';
        } elseif ($status !== 'active') {
            $normalized_status = $status;
        } elseif (empty($expiry_date) || $expiry_date < time()) {
            $normalized_status = 'expired';
        } else {
            $normalized_status = 'active';
        }

        $state['found'] = true;
        $state['status'] = $normalized_status;
        $state['has_access'] = ($normalized_status === 'active');
        $state['is_lifetime'] = false;
        $state['start_date'] = $start_date;
        $state['expiry_date'] = $expiry_date;
        $state['source_record_id'] = isset($row['id']) ? (int) $row['id'] : null;
        $state['warning_80_percent'] = $state['has_access'] ? $this->warning_due($start_date, $expiry_date) : false;

        return $state;
    }

    protected function build_learner_course_item($user_id, $course, $state)
    {
        $course_id = isset($course['id']) ? (int) $course['id'] : 0;
        $creator_id = !empty($course['creator']) ? (int) $course['creator'] : (int) trim(strtok((string) $course['user_id'], ','));
        $instructor = $creator_id > 0 ? $this->get_user_row($creator_id) : array();
        $category_name = $this->get_course_category_name($course);
        $progress = $this->learner_course_progress($course_id, $user_id);
        $last_lesson_id = $this->get_last_watched_lesson_id($user_id, $course_id);

        return array(
            'course_id' => $course_id,
            'id' => $course_id,
            'course' => $course,
            'title' => isset($course['title']) ? $course['title'] : '',
            'category_name' => $category_name,
            'instructor_name' => !empty($instructor) ? trim((isset($instructor['first_name']) ? $instructor['first_name'] : '') . ' ' . (isset($instructor['last_name']) ? $instructor['last_name'] : '')) : '',
            'lesson_count' => $this->count_course_lessons($course_id, false),
            'quiz_count' => $this->count_course_lessons($course_id, true),
            'duration_label' => $this->learner_duration_label($course_id),
            'progress' => $progress,
            'last_lesson_id' => $last_lesson_id,
            'course_url' => site_url('home/course/' . rawurlencode(slugify(isset($course['title']) ? $course['title'] : 'course')) . '/' . $course_id),
            'lesson_url' => site_url('home/lesson/' . slugify(isset($course['title']) ? $course['title'] : 'course') . '/' . $course_id . ($last_lesson_id > 0 ? '/' . $last_lesson_id : '')),
            'access_state' => $state,
            'access_source' => isset($state['access_source']) ? $state['access_source'] : 'none',
            'access_status' => isset($state['status']) ? $state['status'] : 'none',
            'access_label' => $this->learner_access_source_label(isset($state['access_source']) ? $state['access_source'] : 'none'),
            'access_status_label' => $this->learner_access_status_label(isset($state['status']) ? $state['status'] : 'none', !empty($state['warning_80_percent'])),
            'access_status_class' => $this->learner_access_status_class(isset($state['status']) ? $state['status'] : 'none', !empty($state['warning_80_percent'])),
            'access_message' => $this->learner_access_message($state),
            'has_access' => !empty($state['has_access']),
            'locked' => empty($state['has_access']),
            'start_date' => isset($state['start_date']) ? $state['start_date'] : null,
            'expiry_date' => isset($state['expiry_date']) ? $state['expiry_date'] : null,
            'is_lifetime' => !empty($state['is_lifetime']),
            'warning_80_percent' => !empty($state['warning_80_percent']),
        );
    }

    protected function build_learner_subscription_item($row)
    {
        $state = $this->normalize_subscription_row($row);

        return array(
            'plan_name' => !empty($row['plan_name']) ? $row['plan_name'] : 'Subscription plan',
            'plan_slug' => isset($row['plan_slug']) ? $row['plan_slug'] : '',
            'status' => $state['status'],
            'status_label' => $this->learner_access_status_label($state['status'], !empty($state['warning_80_percent'])),
            'status_class' => $this->learner_access_status_class($state['status'], !empty($state['warning_80_percent'])),
            'source_label' => $this->learner_access_source_label($state['access_source']),
            'start_date' => $state['start_date'],
            'expiry_date' => $state['expiry_date'],
            'duration_days' => isset($row['duration_days']) ? (int) $row['duration_days'] : null,
            'price_paid' => isset($row['price_paid']) ? $row['price_paid'] : '0.00',
            'currency' => isset($row['currency']) ? $row['currency'] : '',
            'warning_80_percent' => !empty($state['warning_80_percent']),
            'message' => $this->learner_access_message($state),
        );
    }

    protected function get_learner_legacy_enrol_rows($user_id)
    {
        if (!$this->valid_id($user_id) || !$this->table_exists('enrol')) {
            return array();
        }

        $this->db->where('user_id', (int) $user_id);
        $this->db->order_by('id', 'desc');
        $query = $this->db->get('enrol');

        return $query ? $query->result_array() : array();
    }

    protected function get_learner_youngo_course_access_rows($user_id)
    {
        if (!$this->valid_id($user_id) || !$this->table_exists('youngo_course_access')) {
            return array();
        }

        $this->db->where('user_id', (int) $user_id);
        $this->db->where('status', 'active');
        $this->db->group_start();
        $this->db->where('revoked_at IS NULL', null, false);
        $this->db->or_where('revoked_at', 0);
        $this->db->group_end();
        $this->db->order_by('id', 'desc');
        $query = $this->db->get('youngo_course_access');

        return $query ? $query->result_array() : array();
    }

    protected function get_admin_course_access_rows_for_user($user_id)
    {
        if (!$this->valid_id($user_id) || !$this->table_exists('youngo_course_access')) {
            return array();
        }

        $this->db->where('user_id', (int) $user_id);
        $this->db->order_by('id', 'desc');
        $query = $this->db->get('youngo_course_access');

        return $query ? $query->result_array() : array();
    }

    protected function get_admin_course_access_rows_for_course($course_id)
    {
        if (!$this->valid_id($course_id) || !$this->table_exists('youngo_course_access')) {
            return array();
        }

        $this->db->where('course_id', (int) $course_id);
        $this->db->order_by('id', 'desc');
        $query = $this->db->get('youngo_course_access');

        return $query ? $query->result_array() : array();
    }

    protected function get_admin_subscription_rows_for_user($user_id)
    {
        if (!$this->valid_id($user_id) || !$this->table_exists('youngo_user_subscriptions')) {
            return array();
        }

        $this->db->select('s.*, p.name AS plan_name, p.slug AS plan_slug');
        $this->db->from('youngo_user_subscriptions s');
        $this->db->join('youngo_subscription_plans p', 'p.id = s.plan_id', 'left');
        $this->db->where('s.user_id', (int) $user_id);
        $this->db->order_by('s.id', 'desc');
        $query = $this->db->get();

        return $query ? $query->result_array() : array();
    }

    protected function build_admin_subscription_summary_item($row)
    {
        $state = $this->normalize_subscription_row($row);

        return array(
            'plan_name' => !empty($row['plan_name']) ? $row['plan_name'] : 'Subscription plan',
            'plan_slug' => isset($row['plan_slug']) ? $row['plan_slug'] : '',
            'status' => $state['status'],
            'start_date' => $state['start_date'],
            'expiry_date' => $state['expiry_date'],
            'duration_days' => isset($row['duration_days']) ? (int) $row['duration_days'] : null,
            'price_paid' => isset($row['price_paid']) ? $row['price_paid'] : '0.00',
            'currency' => isset($row['currency']) ? $row['currency'] : '',
            'source_label' => $this->learner_access_source_label($state['access_source']),
        );
    }

    protected function get_admin_recent_manual_grants($where, $limit)
    {
        $limit = (int) $limit;
        if ($limit <= 0) {
            $limit = 5;
        }
        if ($limit > 25) {
            $limit = 25;
        }

        $this->db->select('g.id, g.grant_type, g.status, g.course_id, g.plan_id, g.is_lifetime, g.start_date, g.expiry_date, g.created_at, g.revoked_at');
        $this->db->select('course.title AS course_title, plan.name AS plan_name, plan.slug AS plan_slug');
        $this->db->from('youngo_manual_grants g');
        $this->db->join('course', 'course.id = g.course_id', 'left');
        $this->db->join('youngo_subscription_plans plan', 'plan.id = g.plan_id', 'left');
        foreach ($where as $field => $value) {
            $this->db->where($field, $value);
        }
        $this->db->order_by('g.id', 'desc');
        $query = $this->db->get('', $limit);

        if (!$query) {
            return array();
        }

        $items = array();
        foreach ($query->result_array() as $row) {
            $target = '-';
            if ($row['grant_type'] === 'course') {
                $target = !empty($row['course_title']) ? $row['course_title'] : ('Course #' . (int) $row['course_id']);
            } elseif ($row['grant_type'] === 'subscription') {
                $target = !empty($row['plan_name']) ? $row['plan_name'] : ('Plan #' . (int) $row['plan_id']);
            }

            $items[] = array(
                'id' => (int) $row['id'],
                'grant_type' => isset($row['grant_type']) ? $row['grant_type'] : '',
                'status' => isset($row['status']) ? $row['status'] : '',
                'target_label' => $target,
                'is_lifetime' => !empty($row['is_lifetime']),
                'start_date' => $this->timestamp_or_null(isset($row['start_date']) ? $row['start_date'] : null),
                'expiry_date' => $this->timestamp_or_null(isset($row['expiry_date']) ? $row['expiry_date'] : null),
                'created_at' => $this->timestamp_or_null(isset($row['created_at']) ? $row['created_at'] : null),
                'revoked_at' => $this->timestamp_or_null(isset($row['revoked_at']) ? $row['revoked_at'] : null),
                'url' => site_url('admin/youngo/manual-grants/' . (int) $row['id']),
            );
        }

        return $items;
    }

    protected function get_course_row($course_id)
    {
        if (!$this->valid_id($course_id) || !$this->table_exists('course')) {
            return array();
        }

        $this->db->where('id', (int) $course_id);
        $query = $this->db->get('course', 1);

        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    protected function get_user_row($user_id)
    {
        if (!$this->valid_id($user_id) || !$this->table_exists('users')) {
            return array();
        }

        $this->db->select('id, first_name, last_name');
        $this->db->where('id', (int) $user_id);
        $query = $this->db->get('users', 1);

        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    protected function get_course_category_name($course)
    {
        if (!$this->table_exists('category')) {
            return 'Course';
        }

        $category_id = 0;
        if (!empty($course['sub_category_id'])) {
            $category_id = (int) $course['sub_category_id'];
        } elseif (!empty($course['category_id'])) {
            $category_id = (int) $course['category_id'];
        }

        if ($category_id <= 0) {
            return 'Course';
        }

        $this->db->select('name');
        $this->db->where('id', $category_id);
        $query = $this->db->get('category', 1);

        return $query && $query->num_rows() > 0 ? $query->row('name') : 'Course';
    }

    protected function count_course_lessons($course_id, $quiz_only)
    {
        if (!$this->valid_id($course_id) || !$this->table_exists('lesson')) {
            return 0;
        }

        $this->db->where('course_id', (int) $course_id);
        if ($quiz_only) {
            $this->db->where('lesson_type', 'quiz');
        } else {
            $this->db->where('lesson_type !=', 'quiz');
        }

        return (int) $this->db->count_all_results('lesson');
    }

    protected function get_last_watched_lesson_id($user_id, $course_id)
    {
        if (!$this->valid_id($user_id) || !$this->valid_id($course_id) || !$this->table_exists('watched_duration')) {
            return 0;
        }

        $this->db->select('watched_lesson_id');
        $this->db->where('watched_student_id', (int) $user_id);
        $this->db->where('watched_course_id', (int) $course_id);
        $this->db->order_by('watched_id', 'desc');
        $query = $this->db->get('watched_duration', 1);

        return $query && $query->num_rows() > 0 ? (int) $query->row('watched_lesson_id') : 0;
    }

    protected function learner_course_progress($course_id, $user_id)
    {
        if (!$this->valid_id($course_id) || !$this->valid_id($user_id) || !function_exists('course_progress')) {
            return 0;
        }

        $progress = (int) round(course_progress($course_id, $user_id));
        return max(0, min(100, $progress));
    }

    protected function learner_duration_label($course_id)
    {
        if (!$this->valid_id($course_id)) {
            return '';
        }

        $this->load->model('crud_model');
        $duration = $this->crud_model->get_total_duration_of_lesson_by_course_id($course_id);
        $original_duration = trim((string) $duration);
        if ($original_duration === '') {
            return '';
        }

        $normalized_duration = preg_replace('/\s*hours?$/i', '', $original_duration);
        $normalized_duration = preg_replace('/\.\d+/', '', trim($normalized_duration));

        if (!preg_match('/^\d{1,3}:\d{1,2}(:\d{1,2})?$/', $normalized_duration)) {
            return preg_match('/^0\s+hours?$/i', $original_duration) ? '' : $original_duration;
        }

        $parts = array_map('intval', explode(':', $normalized_duration));
        if (count($parts) === 2) {
            $hours = 0;
            $minutes = $parts[0];
            $seconds = $parts[1];
        } else {
            $hours = $parts[0];
            $minutes = $parts[1];
            $seconds = $parts[2];
        }

        if ($seconds >= 30) {
            $minutes++;
        }
        if ($minutes >= 60) {
            $hours += (int) floor($minutes / 60);
            $minutes = $minutes % 60;
        }

        $labels = array();
        if ($hours > 0) {
            $labels[] = $hours . 'h';
        }
        if ($minutes > 0) {
            $labels[] = $minutes . 'm';
        }

        return empty($labels) ? '' : implode(' ', $labels);
    }

    protected function learner_access_source_label($source)
    {
        $labels = array(
            'legacy_enrol' => 'Enrolled',
            'manual_grant' => 'School-granted access',
            'course_purchase' => 'Course access',
            'subscription' => 'Subscription access',
            'admin' => 'Admin access',
            'instructor' => 'Instructor access',
            'none' => 'Access',
        );

        return isset($labels[$source]) ? $labels[$source] : 'Access';
    }

    protected function learner_access_status_label($status, $warning_due = false)
    {
        if ($warning_due && $status === 'active') {
            return 'Access ending soon';
        }

        $labels = array(
            'active' => 'Access active',
            'expired' => 'Access expired',
            'revoked' => 'Access ended',
            'locked' => 'Access locked',
            'none' => 'No active access',
        );

        return isset($labels[$status]) ? $labels[$status] : 'Access status';
    }

    protected function learner_access_status_class($status, $warning_due = false)
    {
        if ($warning_due && $status === 'active') {
            return 'is-warning';
        }

        return $status === 'active' ? 'is-active' : 'is-locked';
    }

    protected function learner_access_message($state)
    {
        $status = isset($state['status']) ? $state['status'] : 'none';
        if ($status === 'expired') {
            return 'Lessons are locked, but progress stays saved.';
        }
        if ($status === 'revoked') {
            return 'Access has ended. Progress stays saved.';
        }
        if (!empty($state['warning_80_percent'])) {
            return 'Access is near its end. Keep going while it is active.';
        }

        return '';
    }

    protected function course_access_source($row)
    {
        if (!empty($row['manual_grant_id']) || (isset($row['access_source']) && $row['access_source'] === 'manual_grant')) {
            return 'manual_grant';
        }

        return 'course_purchase';
    }

    protected function subscription_access_source($row)
    {
        if (!empty($row['manual_grant_id']) || (isset($row['source']) && $row['source'] === 'manual_grant')) {
            return 'manual_grant';
        }

        return 'subscription';
    }

    protected function empty_source_state($access_source)
    {
        return array(
            'found' => false,
            'has_access' => false,
            'access_source' => $access_source,
            'status' => 'none',
            'is_lifetime' => false,
            'start_date' => null,
            'expiry_date' => null,
            'warning_80_percent' => false,
            'source_record_id' => null,
            'legacy_enrol_id' => null,
        );
    }

    protected function warning_due($start_date, $expiry_date)
    {
        $start_date = $this->timestamp_or_null($start_date);
        $expiry_date = $this->timestamp_or_null($expiry_date);

        if (empty($start_date) || empty($expiry_date) || $expiry_date <= $start_date || time() >= $expiry_date) {
            return false;
        }

        return ((time() - $start_date) / ($expiry_date - $start_date)) >= 0.8;
    }

    protected function source_status_rank($status)
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

    protected function timestamp_or_null($value)
    {
        if ($value === null || $value === '' || (int) $value <= 0) {
            return null;
        }

        return (int) $value;
    }

    protected function valid_id($value)
    {
        return (int) $value > 0;
    }

    protected function table_exists($table)
    {
        if (!array_key_exists($table, $this->table_exists_cache)) {
            $this->table_exists_cache[$table] = $this->db->table_exists($table);
        }

        return $this->table_exists_cache[$table];
    }

    protected function field_exists($table, $field)
    {
        $cache_key = $table . '.' . $field;
        if (!array_key_exists($cache_key, $this->field_exists_cache)) {
            $this->field_exists_cache[$cache_key] = $this->db->field_exists($field, $table);
        }

        return $this->field_exists_cache[$cache_key];
    }
}
