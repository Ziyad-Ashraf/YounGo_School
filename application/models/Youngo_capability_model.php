<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Youngo_capability_model extends CI_Model
{
    protected $table_exists_cache = array();
    protected $user_exists_cache = array();
    protected $role_slug_cache = array();
    protected $capability_slug_cache = array();
    protected $capability_exists_cache = array();
    protected $all_capability_slugs_cache = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function phase_2_capability_tables_available()
    {
        return $this->table_exists('youngo_roles')
            && $this->table_exists('youngo_capabilities')
            && $this->table_exists('youngo_role_capabilities')
            && $this->table_exists('youngo_user_roles');
    }

    public function user_exists($user_id)
    {
        if (!$this->valid_id($user_id) || !$this->table_exists('users')) {
            return false;
        }

        $user_id = (int) $user_id;
        if (!array_key_exists($user_id, $this->user_exists_cache)) {
            $query = $this->db->query(
                'SELECT `id` FROM `users` WHERE `id` = ? LIMIT 1',
                array($user_id)
            );
            $this->user_exists_cache[$user_id] = $query && $query->num_rows() > 0;
        }

        return $this->user_exists_cache[$user_id];
    }

    public function get_user_row($user_id)
    {
        if (!$this->valid_id($user_id) || !$this->table_exists('users')) {
            return array();
        }

        $query = $this->db->query(
            'SELECT `id`, `role_id`, `is_instructor`, `status` FROM `users` WHERE `id` = ? LIMIT 1',
            array((int) $user_id)
        );

        return $query && $query->num_rows() > 0 ? $query->row_array() : array();
    }

    public function get_user_role_slugs($user_id)
    {
        if (!$this->valid_id($user_id) || !$this->phase_2_capability_tables_available()) {
            return array();
        }

        $user_id = (int) $user_id;
        if (array_key_exists($user_id, $this->role_slug_cache)) {
            return $this->role_slug_cache[$user_id];
        }

        $query = $this->db->query(
            "SELECT DISTINCT r.`role_key`
             FROM `youngo_user_roles` ur
             INNER JOIN `youngo_roles` r ON r.`id` = ur.`role_id`
             WHERE ur.`user_id` = ?
               AND ur.`status` = 'active'
               AND (ur.`revoked_at` IS NULL OR ur.`revoked_at` = 0)
             ORDER BY r.`role_key` ASC",
            array($user_id)
        );

        $slugs = array();
        if ($query) {
            foreach ($query->result_array() as $row) {
                $slug = $this->normalize_slug(isset($row['role_key']) ? $row['role_key'] : '');
                if ($slug !== '') {
                    $slugs[] = $slug;
                }
            }
        }

        $this->role_slug_cache[$user_id] = $this->unique_values($slugs);
        return $this->role_slug_cache[$user_id];
    }

    public function get_user_capability_slugs($user_id)
    {
        if (!$this->valid_id($user_id) || !$this->phase_2_capability_tables_available()) {
            return array();
        }

        $user_id = (int) $user_id;
        if (array_key_exists($user_id, $this->capability_slug_cache)) {
            return $this->capability_slug_cache[$user_id];
        }

        $query = $this->db->query(
            "SELECT DISTINCT c.`capability_key`
             FROM `youngo_user_roles` ur
             INNER JOIN `youngo_roles` r ON r.`id` = ur.`role_id`
             INNER JOIN `youngo_role_capabilities` rc ON rc.`role_id` = r.`id`
             INNER JOIN `youngo_capabilities` c ON c.`id` = rc.`capability_id`
             WHERE ur.`user_id` = ?
               AND ur.`status` = 'active'
               AND (ur.`revoked_at` IS NULL OR ur.`revoked_at` = 0)
             ORDER BY c.`capability_key` ASC",
            array($user_id)
        );

        $slugs = array();
        if ($query) {
            foreach ($query->result_array() as $row) {
                $slug = $this->normalize_slug(isset($row['capability_key']) ? $row['capability_key'] : '');
                if ($slug !== '') {
                    $slugs[] = $slug;
                }
            }
        }

        $this->capability_slug_cache[$user_id] = $this->unique_values($slugs);
        return $this->capability_slug_cache[$user_id];
    }

    public function get_all_capability_slugs()
    {
        if ($this->all_capability_slugs_cache !== null) {
            return $this->all_capability_slugs_cache;
        }

        if (!$this->table_exists('youngo_capabilities')) {
            $this->all_capability_slugs_cache = array();
            return $this->all_capability_slugs_cache;
        }

        $query = $this->db->query(
            'SELECT `capability_key` FROM `youngo_capabilities` ORDER BY `capability_key` ASC'
        );

        $slugs = array();
        if ($query) {
            foreach ($query->result_array() as $row) {
                $slug = $this->normalize_slug(isset($row['capability_key']) ? $row['capability_key'] : '');
                if ($slug !== '') {
                    $slugs[] = $slug;
                }
            }
        }

        $this->all_capability_slugs_cache = $this->unique_values($slugs);
        return $this->all_capability_slugs_cache;
    }

    public function capability_exists($capability_slug)
    {
        $capability_slug = $this->normalize_slug($capability_slug);
        if ($capability_slug === '' || !$this->table_exists('youngo_capabilities')) {
            return false;
        }

        if (!array_key_exists($capability_slug, $this->capability_exists_cache)) {
            $query = $this->db->query(
                'SELECT `id` FROM `youngo_capabilities` WHERE `capability_key` = ? LIMIT 1',
                array($capability_slug)
            );
            $this->capability_exists_cache[$capability_slug] = $query && $query->num_rows() > 0;
        }

        return $this->capability_exists_cache[$capability_slug];
    }

    public function count_table_rows($table)
    {
        if (!$this->safe_identifier($table) || !$this->table_exists($table)) {
            return null;
        }

        $query = $this->db->query('SELECT COUNT(*) AS `row_count` FROM `' . $table . '`');
        $row = $query ? $query->row_array() : array();
        return isset($row['row_count']) ? (int) $row['row_count'] : null;
    }

    public function normalize_slug($slug)
    {
        $slug = strtolower(trim((string) $slug));
        return preg_match('/^[a-z0-9_:-]+$/', $slug) ? $slug : '';
    }

    protected function table_exists($table)
    {
        if (!$this->safe_identifier($table)) {
            return false;
        }

        if (!array_key_exists($table, $this->table_exists_cache)) {
            $this->table_exists_cache[$table] = $this->db->table_exists($table);
        }

        return $this->table_exists_cache[$table];
    }

    protected function valid_id($value)
    {
        return is_numeric($value) && (int) $value > 0;
    }

    protected function safe_identifier($value)
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9_]+$/', $value);
    }

    protected function unique_values($values)
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
