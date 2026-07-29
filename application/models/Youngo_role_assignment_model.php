<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Youngo_role_assignment_model extends CI_Model
{
    const ROOT_ADMIN_USER_ID = 1;

    protected $managed_role_keys = array('admin', 'content_manager', 'course_manager', 'instructor');
    protected $role_toggle_map = array(
        'admin' => 'admin',
        'content' => 'content_manager',
        'course' => 'course_manager',
        'instructor' => 'instructor',
    );
    protected $managed_legacy_permissions = array(
        'admin',
        'admins',
        'settings',
        'category',
        'course',
        'user',
        'instructor',
        'student',
        'enrolment',
        'revenue',
        'messaging',
        'blog',
        'coupon',
        'newsletter',
        'contact',
    );
    protected $admin_legacy_permissions = array(
        'admin',
        'admins',
        'settings',
        'category',
        'course',
        'user',
        'instructor',
        'student',
        'enrolment',
        'revenue',
        'messaging',
        'blog',
        'coupon',
        'newsletter',
        'contact',
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function list_role_assignment_rows()
    {
        if (!$this->db->table_exists('users')) {
            return array();
        }

        $this->db->select('id, first_name, last_name, email, role_id, is_instructor, status, image');
        $this->db->from('users');
        $this->db->order_by('role_id', 'ASC');
        $this->db->order_by('first_name', 'ASC');
        $this->db->order_by('email', 'ASC');
        $query = $this->db->get();
        $users = $query ? $query->result_array() : array();

        $active_roles = $this->get_active_role_map_for_users();
        $legacy_permissions = $this->get_legacy_permission_map();

        foreach ($users as &$user) {
            $user_id = (int) $user['id'];
            $roles = isset($active_roles[$user_id]) ? $active_roles[$user_id] : array();
            $user_permissions = isset($legacy_permissions[$user_id]) ? $legacy_permissions[$user_id] : array();
            $user['is_protected_root'] = $this->is_protected_root($user_id);
            $admin_on = in_array('admin', $roles, true);
            $user['toggle_admin'] = $admin_on;
            $user['toggle_course'] = !$admin_on && (in_array('course_manager', $roles, true) || in_array('course', $user_permissions, true));
            $user['toggle_content'] = !$admin_on && (in_array('content_manager', $roles, true) || (in_array('category', $user_permissions, true) && !in_array('course', $user_permissions, true)));
            $user['toggle_instructor'] = !$admin_on && in_array('instructor', $roles, true);
            $user['legacy_permissions'] = $user_permissions;
            $user['has_permission_row'] = array_key_exists($user_id, $legacy_permissions);
        }
        unset($user);

        return $users;
    }

    public function update_assignments($target_user_id, $requested_toggles, $actor_user_id)
    {
        $target_user_id = (int) $target_user_id;
        $actor_user_id = (int) $actor_user_id;

        if ($target_user_id <= 0 || !$this->user_exists($target_user_id)) {
            return $this->failure('invalid_user', 'Select a valid user.');
        }

        if ($this->is_protected_root($target_user_id)) {
            return $this->failure('protected_root_admin', 'Root Admin role assignments are protected and cannot be changed.');
        }

        $normalized = $this->normalize_requested_toggles($requested_toggles);
        if (!$normalized['ok']) {
            return $this->failure($normalized['code'], $normalized['message']);
        }

        $role_keys = $this->toggles_to_role_keys($normalized['toggles']);
        $asset_check = $this->validate_required_role_assets($role_keys);
        if (!$asset_check['ok']) {
            return $asset_check;
        }

        $this->db->trans_start();
        $this->sync_yourgo_roles($target_user_id, $role_keys, $actor_user_id);
        $this->sync_legacy_user_flags($target_user_id, $normalized['toggles']);
        $this->sync_legacy_permissions($target_user_id, $normalized['toggles']);
        $this->db->trans_complete();

        if (!$this->db->trans_status()) {
            return $this->failure('update_failed', 'Role assignment update failed.');
        }

        return array(
            'ok' => true,
            'message' => 'Role assignments updated.',
            'toggles' => $normalized['toggles'],
        );
    }

    public function get_required_capability_status()
    {
        $required = array(
            'manage_courses',
            'manage_course_categories',
            'manage_lessons',
            'publish_courses',
            'manage_homepage_content',
            'manage_static_content',
            'manage_media',
            'manage_assigned_course_lessons',
            'manage_roles',
        );
        $status = array();
        foreach ($required as $capability_key) {
            $status[$capability_key] = $this->capability_exists($capability_key);
        }
        return $status;
    }

    protected function normalize_requested_toggles($requested_toggles)
    {
        $requested_toggles = is_array($requested_toggles) ? $requested_toggles : array();
        $allowed = array('admin', 'content', 'course', 'instructor');
        $toggles = array();

        foreach ($requested_toggles as $toggle) {
            $toggle = trim((string) $toggle);
            if (!in_array($toggle, $allowed, true)) {
                return array(
                    'ok' => false,
                    'code' => 'invalid_toggle',
                    'message' => 'Role assignment request contains an invalid toggle.',
                );
            }
            if (!in_array($toggle, $toggles, true)) {
                $toggles[] = $toggle;
            }
        }

        if (in_array('admin', $toggles, true) && count($toggles) > 1) {
            return array(
                'ok' => false,
                'code' => 'admin_mutual_exclusion',
                'message' => 'Admin cannot be combined with Content, Course, or Instructor.',
            );
        }

        return array('ok' => true, 'toggles' => $toggles);
    }

    protected function toggles_to_role_keys($toggles)
    {
        $role_keys = array();
        foreach ($toggles as $toggle) {
            if (isset($this->role_toggle_map[$toggle])) {
                $role_keys[] = $this->role_toggle_map[$toggle];
            }
        }
        return $role_keys;
    }

    protected function validate_required_role_assets($role_keys)
    {
        foreach ($role_keys as $role_key) {
            if (!$this->role_exists($role_key)) {
                return $this->failure('missing_role_' . $role_key, 'Required YounGo role is missing: ' . $role_key);
            }
        }

        if (in_array('course_manager', $role_keys, true)) {
            foreach (array('manage_courses', 'manage_course_categories', 'manage_lessons', 'publish_courses') as $capability_key) {
                if (!$this->capability_exists($capability_key)) {
                    return $this->failure('missing_capability_' . $capability_key, 'Required YounGo capability is missing: ' . $capability_key);
                }
            }
        }

        if (in_array('content_manager', $role_keys, true)) {
            foreach (array('manage_homepage_content', 'manage_static_content', 'manage_media') as $capability_key) {
                if (!$this->capability_exists($capability_key)) {
                    return $this->failure('missing_capability_' . $capability_key, 'Required YounGo capability is missing: ' . $capability_key);
                }
            }
        }

        if (in_array('instructor', $role_keys, true) && !$this->capability_exists('manage_assigned_course_lessons')) {
            return $this->failure('missing_capability_manage_assigned_course_lessons', 'Required YounGo capability is missing: manage_assigned_course_lessons');
        }

        return array('ok' => true);
    }

    protected function sync_yourgo_roles($target_user_id, $role_keys, $actor_user_id)
    {
        $role_ids = $this->get_role_ids_by_keys($this->managed_role_keys);
        $desired_role_ids = array();

        foreach ($role_keys as $role_key) {
            if (isset($role_ids[$role_key])) {
                $desired_role_ids[] = (int) $role_ids[$role_key];
            }
        }

        foreach ($role_ids as $role_id) {
            $role_id = (int) $role_id;
            if (in_array($role_id, $desired_role_ids, true)) {
                $this->activate_user_role($target_user_id, $role_id, $actor_user_id);
            } else {
                $this->revoke_user_role($target_user_id, $role_id);
            }
        }
    }

    protected function activate_user_role($target_user_id, $role_id, $actor_user_id)
    {
        $active = $this->get_user_role_row($target_user_id, $role_id, 'active');
        if (!empty($active)) {
            return;
        }

        $revoked = $this->get_user_role_row($target_user_id, $role_id, 'revoked');
        $data = array(
            'user_id' => $target_user_id,
            'role_id' => $role_id,
            'assigned_by_user_id' => $actor_user_id > 0 ? $actor_user_id : null,
            'created_at' => time(),
            'revoked_at' => null,
            'status' => 'active',
        );

        if (!empty($revoked)) {
            $this->db->where('id', (int) $revoked['id'])->update('youngo_user_roles', $data);
            return;
        }

        $this->db->insert('youngo_user_roles', $data);
    }

    protected function revoke_user_role($target_user_id, $role_id)
    {
        $active = $this->get_user_role_row($target_user_id, $role_id, 'active');
        if (empty($active)) {
            return;
        }

        $this->db->where('user_id', $target_user_id);
        $this->db->where('role_id', $role_id);
        $this->db->where('status', 'revoked');
        $this->db->delete('youngo_user_roles');

        $this->db->where('id', (int) $active['id']);
        $this->db->update('youngo_user_roles', array(
            'status' => 'revoked',
            'revoked_at' => time(),
        ));
    }

    protected function sync_legacy_user_flags($target_user_id, $toggles)
    {
        $admin_on = in_array('admin', $toggles, true);
        $content_on = in_array('content', $toggles, true);
        $course_on = in_array('course', $toggles, true);
        $instructor_on = in_array('instructor', $toggles, true);

        $data = array(
            'role_id' => ($admin_on || $content_on || $course_on) ? 1 : 2,
            'is_instructor' => (!$admin_on && $instructor_on) ? 1 : 0,
            'last_modified' => time(),
        );

        $this->db->where('id', $target_user_id);
        $this->db->update('users', $data);
    }

    protected function sync_legacy_permissions($target_user_id, $toggles)
    {
        $permissions = $this->get_existing_legacy_permissions($target_user_id);
        $permissions = array_values(array_diff($permissions, $this->managed_legacy_permissions));

        if (in_array('admin', $toggles, true)) {
            $permissions = array_merge($permissions, $this->admin_legacy_permissions);
        } else {
            if (in_array('course', $toggles, true)) {
                $permissions[] = 'course';
                $permissions[] = 'category';
            }
            if (in_array('content', $toggles, true)) {
                $permissions[] = 'category';
            }
        }

        $permissions = $this->unique_values($permissions);
        sort($permissions);
        $this->upsert_legacy_permission_row($target_user_id, $permissions);
    }

    protected function upsert_legacy_permission_row($target_user_id, $permissions)
    {
        $data = array(
            'admin_id' => $target_user_id,
            'permissions' => json_encode(array_values($permissions)),
        );

        $this->db->where('admin_id', $target_user_id);
        $query = $this->db->get('permissions');
        if ($query && $query->num_rows() > 0) {
            $this->db->where('admin_id', $target_user_id);
            $this->db->update('permissions', $data);
            return;
        }

        $this->db->insert('permissions', $data);
    }

    protected function get_active_role_map_for_users()
    {
        $map = array();
        if (!$this->db->table_exists('youngo_user_roles') || !$this->db->table_exists('youngo_roles')) {
            return $map;
        }

        $this->db->select('ur.user_id, r.role_key');
        $this->db->from('youngo_user_roles ur');
        $this->db->join('youngo_roles r', 'r.id = ur.role_id', 'inner');
        $this->db->where('ur.status', 'active');
        $this->db->group_start();
        $this->db->where('ur.revoked_at IS NULL', null, false);
        $this->db->or_where('ur.revoked_at', 0);
        $this->db->group_end();
        $query = $this->db->get();

        if ($query) {
            foreach ($query->result_array() as $row) {
                $user_id = (int) $row['user_id'];
                if (!isset($map[$user_id])) {
                    $map[$user_id] = array();
                }
                $map[$user_id][] = $row['role_key'];
            }
        }

        return $map;
    }

    protected function get_legacy_permission_map()
    {
        $map = array();
        if (!$this->db->table_exists('permissions')) {
            return $map;
        }

        $query = $this->db->select('admin_id, permissions')->from('permissions')->get();
        if ($query) {
            foreach ($query->result_array() as $row) {
                $map[(int) $row['admin_id']] = $this->decode_permissions($row['permissions']);
            }
        }

        return $map;
    }

    protected function get_existing_legacy_permissions($target_user_id)
    {
        $query = $this->db->where('admin_id', $target_user_id)->get('permissions', 1);
        if (!$query || $query->num_rows() === 0) {
            return array();
        }

        $row = $query->row_array();
        return $this->decode_permissions($row['permissions']);
    }

    protected function decode_permissions($permissions_json)
    {
        $permissions = json_decode((string) $permissions_json, true);
        return is_array($permissions) ? $this->unique_values($permissions) : array();
    }

    protected function get_role_ids_by_keys($role_keys)
    {
        $map = array();
        if (!$this->db->table_exists('youngo_roles')) {
            return $map;
        }

        $this->db->select('id, role_key');
        $this->db->from('youngo_roles');
        $this->db->where_in('role_key', $role_keys);
        $query = $this->db->get();
        if ($query) {
            foreach ($query->result_array() as $row) {
                $map[$row['role_key']] = (int) $row['id'];
            }
        }
        return $map;
    }

    protected function get_user_role_row($target_user_id, $role_id, $status)
    {
        $this->db->where('user_id', $target_user_id);
        $this->db->where('role_id', $role_id);
        $this->db->where('status', $status);
        $query = $this->db->get('youngo_user_roles', 1);
        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    protected function user_exists($user_id)
    {
        $query = $this->db->select('id')->where('id', (int) $user_id)->get('users', 1);
        return $query && $query->num_rows() > 0;
    }

    protected function role_exists($role_key)
    {
        if (!$this->db->table_exists('youngo_roles')) {
            return false;
        }
        $query = $this->db->select('id')->where('role_key', $role_key)->get('youngo_roles', 1);
        return $query && $query->num_rows() > 0;
    }

    protected function capability_exists($capability_key)
    {
        if (!$this->db->table_exists('youngo_capabilities')) {
            return false;
        }
        $query = $this->db->select('id')->where('capability_key', $capability_key)->get('youngo_capabilities', 1);
        return $query && $query->num_rows() > 0;
    }

    protected function is_protected_root($user_id)
    {
        $root_id = defined('YOUNGO_PROTECTED_ROOT_ADMIN_USER_ID') ? (int) YOUNGO_PROTECTED_ROOT_ADMIN_USER_ID : self::ROOT_ADMIN_USER_ID;
        return (int) $user_id === $root_id;
    }

    protected function unique_values($values)
    {
        $unique = array();
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '' && !in_array($value, $unique, true)) {
                $unique[] = $value;
            }
        }
        return $unique;
    }

    protected function failure($code, $message)
    {
        return array(
            'ok' => false,
            'code' => $code,
            'message' => $message,
        );
    }
}
