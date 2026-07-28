<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Youngo_subscription_model extends CI_Model
{
    protected $plan_table = 'youngo_subscription_plans';
    protected $translation_table = 'youngo_subscription_plan_translations';
    protected $audit_table = 'youngo_subscription_plan_audit_log';
    protected $youngo_commercial_currency = 'EGP';
    protected $schema_cache = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function get_schema_status()
    {
        if ($this->schema_cache !== null) {
            return $this->schema_cache;
        }

        $plan_table_exists = $this->db->table_exists($this->plan_table);
        $archive_columns = array(
            'archived_at' => $plan_table_exists && $this->db->field_exists('archived_at', $this->plan_table),
            'archived_by_user_id' => $plan_table_exists && $this->db->field_exists('archived_by_user_id', $this->plan_table),
        );
        $audit_table_exists = $this->db->table_exists($this->audit_table);

        $this->schema_cache = array(
            'plan_table_exists' => $plan_table_exists,
            'archive_columns' => $archive_columns,
            'audit_table_exists' => $audit_table_exists,
            'translation_table_exists' => $this->subscription_translation_table_exists(),
            'phase_2l_applied' => $plan_table_exists
                && !in_array(false, $archive_columns, true)
                && $audit_table_exists,
        );

        return $this->schema_cache;
    }

    public function subscription_translation_table_exists()
    {
        if (!$this->db->table_exists($this->translation_table)) {
            return false;
        }

        $fields = $this->db->list_fields($this->translation_table);
        foreach (array('id', 'plan_id', 'language_code', 'name') as $field) {
            if (!in_array($field, $fields, true)) {
                return false;
            }
        }

        return true;
    }

    public function normalize_subscription_translation_language($language)
    {
        $language = strtolower(trim((string) $language));

        if ($language === 'en') {
            return 'english';
        }
        if ($language === 'ar') {
            return 'arabic';
        }
        if (in_array($language, array('english', 'arabic'), true)) {
            return $language;
        }

        return null;
    }

    public function get_plan_translations($plan_id)
    {
        if (!$this->valid_id($plan_id) || !$this->subscription_translation_table_exists()) {
            return array();
        }

        $rows = $this->db
            ->where('plan_id', (int) $plan_id)
            ->order_by('language_code', 'ASC')
            ->get($this->translation_table)
            ->result_array();

        $translations = array();
        foreach ($rows as $row) {
            $language = $this->normalize_subscription_translation_language(isset($row['language_code']) ? $row['language_code'] : '');
            if ($language === null) {
                continue;
            }

            $translations[$language] = array(
                'id' => isset($row['id']) ? (int) $row['id'] : 0,
                'plan_id' => isset($row['plan_id']) ? (int) $row['plan_id'] : 0,
                'language_code' => $language,
                'name' => isset($row['name']) ? (string) $row['name'] : '',
                'short_description' => isset($row['short_description']) ? (string) $row['short_description'] : '',
                'description' => isset($row['description']) ? (string) $row['description'] : '',
                'badge_label' => isset($row['badge_label']) ? (string) $row['badge_label'] : '',
                'created_by_user_id' => !empty($row['created_by_user_id']) ? (int) $row['created_by_user_id'] : null,
                'updated_by_user_id' => !empty($row['updated_by_user_id']) ? (int) $row['updated_by_user_id'] : null,
                'created_at' => !empty($row['created_at']) ? (int) $row['created_at'] : null,
                'updated_at' => !empty($row['updated_at']) ? (int) $row['updated_at'] : null,
            );
        }

        return $translations;
    }

    public function get_plan_translation($plan_id, $language)
    {
        $language = $this->normalize_subscription_translation_language($language);
        if ($language === null || !$this->valid_id($plan_id) || !$this->subscription_translation_table_exists()) {
            return array();
        }

        $row = $this->db
            ->where('plan_id', (int) $plan_id)
            ->where('language_code', $language)
            ->limit(1)
            ->get($this->translation_table)
            ->row_array();

        if (empty($row)) {
            return array();
        }

        $translations = $this->get_plan_translations($plan_id);
        return isset($translations[$language]) ? $translations[$language] : array();
    }

    public function save_plan_translation_foundation($plan_id, $language, $input, $actor_user_id = null)
    {
        if (!$this->valid_id($plan_id) || empty($this->get_plan($plan_id))) {
            return $this->failure_result('Subscription plan was not found.');
        }

        if (!$this->subscription_translation_table_exists()) {
            return $this->failure_result('Subscription plan translation schema is not applied.');
        }

        $language = $this->normalize_subscription_translation_language($language);
        if ($language === null) {
            return $this->failure_result('Subscription plan translations support english and arabic only.');
        }

        $validation = $this->validate_plan_translation_data($input);
        if (empty($validation['success'])) {
            return $validation;
        }

        $now = time();
        $existing = $this->get_plan_translation($plan_id, $language);
        $data = $validation['data'];
        $data['plan_id'] = (int) $plan_id;
        $data['language_code'] = $language;
        $data['updated_by_user_id'] = $actor_user_id ? (int) $actor_user_id : null;
        $data['updated_at'] = $now;

        if (empty($existing)) {
            $data['created_by_user_id'] = $actor_user_id ? (int) $actor_user_id : null;
            $data['created_at'] = $now;
            $saved = $this->safe_insert($this->translation_table, $data);
        } else {
            $saved = $this->safe_update(
                $this->translation_table,
                array('plan_id' => (int) $plan_id, 'language_code' => $language),
                $data
            );
        }

        if (!$saved) {
            return $this->failure_result('Could not save the subscription plan translation foundation row.');
        }

        return array('success' => true, 'plan_id' => (int) $plan_id, 'language_code' => $language);
    }

    public function save_plan_translations_from_input($plan_id, $input, $actor_user_id = null)
    {
        if (!$this->valid_id($plan_id)) {
            return $this->failure_result('Subscription plan was not found.');
        }

        if (!is_array($input) || !isset($input['translations']) || !is_array($input['translations'])) {
            return array('success' => true, 'saved_languages' => array(), 'deleted_languages' => array());
        }

        if (!$this->subscription_translation_table_exists()) {
            return $this->failure_result('Subscription plan translation schema is not applied.');
        }

        $saved_languages = array();
        $deleted_languages = array();
        $errors = array();

        foreach (array('english', 'arabic') as $language) {
            $payload = isset($input['translations'][$language]) && is_array($input['translations'][$language])
                ? $input['translations'][$language]
                : array();

            if (!$this->plan_translation_payload_has_content($payload)) {
                if ($this->delete_plan_translation($plan_id, $language)) {
                    $deleted_languages[] = $language;
                }
                continue;
            }

            $result = $this->save_plan_translation_foundation($plan_id, $language, $payload, $actor_user_id);
            if (empty($result['success'])) {
                $errors = array_merge($errors, !empty($result['errors']) ? $result['errors'] : array('Could not save ' . $language . ' subscription plan translation.'));
                continue;
            }

            $saved_languages[] = $language;
        }

        return array(
            'success' => empty($errors),
            'errors' => $errors,
            'saved_languages' => $saved_languages,
            'deleted_languages' => $deleted_languages,
        );
    }

    public function list_plans($include_archived = true)
    {
        if (!$this->db->table_exists($this->plan_table)) {
            return array();
        }

        $schema = $this->get_schema_status();
        if (!$include_archived && !empty($schema['archive_columns']['archived_at'])) {
            $this->db->where('archived_at IS NULL', null, false);
        }

        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('id', 'ASC');
        $rows = $this->db->get($this->plan_table)->result_array();

        foreach ($rows as &$row) {
            $row = $this->with_archive_defaults($row);
        }

        return $rows;
    }

    public function get_public_subscription_plans($language = null)
    {
        if (!$this->db->table_exists($this->plan_table)) {
            return array();
        }

        $fields = $this->plan_fields();
        $required_fields = array('id', 'name', 'slug', 'duration_days', 'price', 'currency', 'is_active', 'is_purchasable');
        foreach ($required_fields as $field) {
            if (!in_array($field, $fields, true)) {
                return array();
            }
        }

        $language = $this->normalize_public_subscription_language($language);
        if ($language === null) {
            return array();
        }

        $select_fields = array('id', 'name', 'slug', 'duration_days', 'price', 'currency', 'is_active', 'is_purchasable');
        foreach (array(
            'is_featured',
            'sort_order',
            'archived_at',
            'deleted_at',
            'is_deleted',
            'short_description',
            'description',
            'summary',
            'english_name',
            'arabic_name',
            'name_en',
            'name_ar',
            'english_short_description',
            'arabic_short_description',
            'short_description_en',
            'short_description_ar',
            'english_description',
            'arabic_description',
            'description_en',
            'description_ar',
            'badge_label',
            'english_badge_label',
            'arabic_badge_label',
            'badge_label_en',
            'badge_label_ar',
        ) as $optional_field) {
            if (in_array($optional_field, $fields, true)) {
                $select_fields[] = $optional_field;
            }
        }

        $this->db->select(implode(', ', array_unique($select_fields)));
        $this->db->from($this->plan_table);
        $this->db->where('is_active', 1);
        $this->db->where('is_purchasable', 1);
        $this->db->where('currency', $this->youngo_commercial_currency);
        $this->db->where('price >', 0);
        $this->db->where('duration_days >', 0);

        if (in_array('archived_at', $fields, true)) {
            $this->db->where('archived_at IS NULL', null, false);
        }
        if (in_array('deleted_at', $fields, true)) {
            $this->db->where('deleted_at IS NULL', null, false);
        }
        if (in_array('is_deleted', $fields, true)) {
            $this->db->where('is_deleted', 0);
        }

        if (in_array('is_featured', $fields, true)) {
            $this->db->order_by('is_featured', 'DESC');
        }
        if (in_array('sort_order', $fields, true)) {
            $this->db->order_by('sort_order', 'ASC');
        }
        $this->db->order_by('id', 'ASC');

        $rows = $this->db->get()->result_array();
        $translations = $this->load_public_plan_translations($this->extract_plan_ids($rows), $language);

        $plans = array();
        foreach ($rows as $row) {
            $plan_id = isset($row['id']) ? (int) $row['id'] : 0;
            if ($plan_id > 0 && isset($translations[$plan_id])) {
                $row = $this->apply_public_plan_translation($row, $translations[$plan_id]);
            }

            $plans[] = $this->normalize_public_plan_row($row, $language);
        }

        return $plans;
    }

    public function format_public_plan_price($price, $currency = null, $language = null)
    {
        $currency = $currency === null ? $this->youngo_commercial_currency : $this->clean_currency($currency);
        if ($currency === '') {
            $currency = $this->youngo_commercial_currency;
        }

        $currency_label = $currency;
        $language = $this->normalize_public_language($language);

        if (file_exists(APPPATH . 'helpers/youngo_frontend_language_helper.php')) {
            $this->load->helper('youngo_frontend_language');
            if (function_exists('youngo_frontend_phrase')) {
                $currency_label = youngo_frontend_phrase(strtolower($currency), $currency, $language);
            }
        }

        $amount = number_format((float) $price, 2, '.', '');
        return $language === 'arabic' ? $amount . ' ' . $currency_label : $currency_label . ' ' . $amount;
    }

    public function format_public_plan_duration($duration_days, $language = null)
    {
        $duration_days = (int) $duration_days;
        if ($duration_days <= 0) {
            return '';
        }

        $phrase_key = $duration_days === 1 ? 'day' : 'days';
        $fallback = $duration_days === 1 ? 'day' : 'days';
        $label = $fallback;

        if (file_exists(APPPATH . 'helpers/youngo_frontend_language_helper.php')) {
            $this->load->helper('youngo_frontend_language');
            if (function_exists('youngo_frontend_phrase')) {
                $label = youngo_frontend_phrase($phrase_key, $fallback, $language);
            }
        }

        return $duration_days . ' ' . $label;
    }

    public function get_plan($plan_id)
    {
        if (!$this->valid_id($plan_id) || !$this->db->table_exists($this->plan_table)) {
            return array();
        }

        $row = $this->db
            ->where('id', (int) $plan_id)
            ->limit(1)
            ->get($this->plan_table)
            ->row_array();

        return !empty($row) ? $this->with_archive_defaults($row) : array();
    }

    public function get_dependency_counts($plan_id)
    {
        $plan_id = (int) $plan_id;
        $tables = array(
            'youngo_user_subscriptions',
            'youngo_checkout_orders',
            'youngo_coupon_subscription_plans',
            'youngo_manual_grants',
        );

        $counts = array();
        foreach ($tables as $table) {
            if (!$this->db->table_exists($table) || !$this->db->field_exists('plan_id', $table)) {
                $counts[$table] = null;
                continue;
            }

            $counts[$table] = (int) $this->db
                ->where('plan_id', $plan_id)
                ->count_all_results($table);
        }

        return $counts;
    }

    public function count_dependencies($plan_id)
    {
        $total = 0;
        foreach ($this->get_dependency_counts($plan_id) as $count) {
            if ($count !== null) {
                $total += (int) $count;
            }
        }

        return $total;
    }

    public function get_expected_commercial_currency()
    {
        return $this->youngo_commercial_currency;
    }

    public function is_system_currency_ready()
    {
        return $this->clean_currency($this->get_system_currency()) === $this->youngo_commercial_currency;
    }

    public function is_plan_currency_ready($currency)
    {
        return $this->clean_currency($currency) === $this->youngo_commercial_currency;
    }

    public function list_non_ready_currency_plans()
    {
        if (!$this->db->table_exists($this->plan_table)) {
            return array();
        }

        $rows = $this->db
            ->select('id, name, slug, currency, is_active, is_purchasable')
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->get($this->plan_table)
            ->result_array();

        $non_ready = array();
        foreach ($rows as $row) {
            if (!$this->is_plan_currency_ready(isset($row['currency']) ? $row['currency'] : '')) {
                $non_ready[] = $row;
            }
        }

        return $non_ready;
    }

    public function get_currency_readiness()
    {
        $system_currency = $this->clean_currency($this->get_system_currency());
        $non_ready_plans = $this->list_non_ready_currency_plans();

        return array(
            'expected_currency' => $this->youngo_commercial_currency,
            'system_currency' => $system_currency,
            'system_currency_ready' => $this->is_system_currency_ready(),
            'non_ready_plan_count' => count($non_ready_plans),
            'non_ready_plans' => $non_ready_plans,
            'commercial_ready' => $this->is_system_currency_ready() && empty($non_ready_plans),
        );
    }

    public function validate_plan_data($input, $existing_plan = array())
    {
        $errors = array();
        $data = array();

        $name = $this->scalar_input($input, 'name', true, $errors, 'Plan name');
        if ($name !== null) {
            $name = trim($name);
            if ($name === '') {
                $errors[] = 'Plan name is required.';
            } elseif (strlen($name) > 255) {
                $errors[] = 'Plan name must be 255 characters or fewer.';
            } else {
                $data['name'] = $name;
            }
        }

        $slug = $this->scalar_input($input, 'slug', true, $errors, 'Slug');
        if ($slug !== null) {
            $slug = strtolower(trim($slug));
            if ($slug === '') {
                $errors[] = 'Slug is required.';
            } elseif (strlen($slug) > 100) {
                $errors[] = 'Slug must be 100 characters or fewer.';
            } elseif (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                $errors[] = 'Slug must use lowercase letters, numbers, and single hyphens only.';
            } elseif (!$this->slug_is_unique($slug, isset($existing_plan['id']) ? (int) $existing_plan['id'] : null)) {
                $errors[] = 'A subscription plan with this slug already exists.';
            } elseif (!empty($existing_plan) && isset($existing_plan['slug']) && $slug !== $existing_plan['slug'] && $this->count_dependencies($existing_plan['id']) > 0) {
                $errors[] = 'Slug cannot be changed after a plan has subscriptions, orders, coupons, or manual grants.';
            } else {
                $data['slug'] = $slug;
            }
        }

        $price = $this->scalar_input($input, 'price', true, $errors, 'Price');
        if ($price !== null) {
            $price = trim($price);
            if (!preg_match('/^(?:0|[1-9][0-9]*)(?:\.[0-9]{1,2})?$/', $price)) {
                $errors[] = 'Price must be a positive decimal with up to two decimal places.';
            } elseif ((float) $price <= 0 || (float) $price > 99999999.99) {
                $errors[] = 'Price must be greater than 0.00 and no more than 99999999.99.';
            } else {
                $data['price'] = number_format((float) $price, 2, '.', '');
            }
        }

        $duration = $this->scalar_input($input, 'duration_days', true, $errors, 'Duration');
        if ($duration !== null) {
            $duration = trim($duration);
            if (!preg_match('/^[1-9][0-9]*$/', $duration)) {
                $errors[] = 'Duration must be a positive whole number of days.';
            } else {
                $data['duration_days'] = (int) $duration;
            }
        }

        $sort_order = $this->scalar_input($input, 'sort_order', false, $errors, 'Sort order');
        if ($sort_order === null || trim($sort_order) === '') {
            $data['sort_order'] = 0;
        } else {
            $sort_order = trim($sort_order);
            if (!preg_match('/^(?:0|[1-9][0-9]*)$/', $sort_order)) {
                $errors[] = 'Sort order must be a non-negative whole number.';
            } else {
                $data['sort_order'] = (int) $sort_order;
            }
        }

        foreach (array('is_active', 'is_purchasable', 'is_featured') as $flag) {
            $value = $this->flag_input($input, $flag, $errors);
            if ($value !== null) {
                $data[$flag] = $value;
            }
        }

        if (isset($data['is_purchasable'], $data['is_active']) && (int) $data['is_purchasable'] === 1 && (int) $data['is_active'] !== 1) {
            $errors[] = 'A purchasable plan must also be active.';
        }

        if (!empty($existing_plan['archived_at'])) {
            $errors[] = 'Archived plans cannot be edited.';
        }

        if (!empty($existing_plan['archived_at']) && (!empty($data['is_active']) || !empty($data['is_purchasable']))) {
            $errors[] = 'Archived plans cannot be active or purchasable.';
        }

        return array(
            'success' => empty($errors),
            'errors' => $errors,
            'data' => $data,
        );
    }

    public function create_plan($input, $actor_user_id)
    {
        $schema = $this->get_schema_status();
        if (empty($schema['phase_2l_applied'])) {
            return $this->mutation_schema_missing_result();
        }

        if (!$this->is_system_currency_ready()) {
            return $this->currency_not_ready_result('create subscription plans');
        }

        $validation = $this->validate_plan_data($input);
        if (empty($validation['success'])) {
            return $validation;
        }

        $now = time();
        $data = $validation['data'];
        $currency = $this->get_system_currency();
        if (!$this->valid_currency_code($currency)) {
            return $this->failure_result('System currency is invalid. Update system currency before saving subscription plans.');
        }
        $data['currency'] = $this->clean_currency($currency);
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $data['archived_at'] = null;
        $data['archived_by_user_id'] = null;

        $this->db->trans_begin();
        $inserted = $this->safe_insert($this->plan_table, $data);
        $plan_id = (int) $this->db->insert_id();
        $after = $this->get_plan($plan_id);

        if (!$inserted || $plan_id <= 0) {
            $this->db->trans_rollback();
            return $this->failure_result('Could not create the subscription plan.');
        }

        if (!$this->write_audit_log($plan_id, $actor_user_id, 'create', null, $after)) {
            $this->db->trans_rollback();
            return $this->failure_result('Could not create the subscription plan audit record.');
        }

        $translation_result = $this->save_plan_translations_from_input($plan_id, $input, $actor_user_id);
        if (empty($translation_result['success'])) {
            $this->db->trans_rollback();
            return $translation_result;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('Could not create the subscription plan.');
        }

        $this->db->trans_commit();
        return array('success' => true, 'plan_id' => $plan_id);
    }

    public function update_plan($plan_id, $input, $actor_user_id)
    {
        $schema = $this->get_schema_status();
        if (empty($schema['phase_2l_applied'])) {
            return $this->mutation_schema_missing_result();
        }

        if (!$this->is_system_currency_ready()) {
            return $this->currency_not_ready_result('edit subscription plans');
        }

        $before = $this->get_plan($plan_id);
        if (empty($before)) {
            return $this->failure_result('Subscription plan was not found.');
        }

        $validation = $this->validate_plan_data($input, $before);
        if (empty($validation['success'])) {
            return $validation;
        }

        $data = $validation['data'];
        $currency = $this->get_system_currency();
        if (!$this->valid_currency_code($currency)) {
            return $this->failure_result('System currency is invalid. Update system currency before saving subscription plans.');
        }
        $data['currency'] = $this->clean_currency($currency);
        $data['updated_at'] = time();

        $this->db->trans_begin();
        $updated = $this->safe_update($this->plan_table, array('id' => (int) $plan_id), $data);
        $after = $this->get_plan($plan_id);

        if (!$updated) {
            $this->db->trans_rollback();
            return $this->failure_result('Could not update the subscription plan.');
        }

        if (!$this->write_audit_log($plan_id, $actor_user_id, 'update', $before, $after)) {
            $this->db->trans_rollback();
            return $this->failure_result('Could not update the subscription plan audit record.');
        }

        $translation_result = $this->save_plan_translations_from_input($plan_id, $input, $actor_user_id);
        if (empty($translation_result['success'])) {
            $this->db->trans_rollback();
            return $translation_result;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('Could not update the subscription plan.');
        }

        $this->db->trans_commit();
        return array('success' => true, 'plan_id' => (int) $plan_id);
    }

    public function set_status($plan_id, $status_action, $actor_user_id)
    {
        $schema = $this->get_schema_status();
        if (empty($schema['phase_2l_applied'])) {
            return $this->mutation_schema_missing_result();
        }

        $before = $this->get_plan($plan_id);
        if (empty($before)) {
            return $this->failure_result('Subscription plan was not found.');
        }
        if (!empty($before['archived_at'])) {
            return $this->failure_result('Archived plans cannot be changed.');
        }

        $data = array('updated_at' => time());
        $action = '';

        if ($status_action === 'activate') {
            if (!$this->is_system_currency_ready()) {
                return $this->currency_not_ready_result('activate subscription plans');
            }
            if (!$this->is_plan_currency_ready(isset($before['currency']) ? $before['currency'] : '')) {
                return $this->plan_currency_not_ready_result('activate');
            }
            if ((float) $before['price'] <= 0 || (int) $before['duration_days'] <= 0) {
                return $this->failure_result('Only plans with a positive price and duration can be activated.');
            }
            $data['is_active'] = 1;
            $action = 'activate';
        } elseif ($status_action === 'deactivate') {
            $data['is_active'] = 0;
            $data['is_purchasable'] = 0;
            $action = 'deactivate';
        } elseif ($status_action === 'make_purchasable') {
            if (!$this->is_system_currency_ready()) {
                return $this->currency_not_ready_result('make subscription plans purchasable');
            }
            if (!$this->is_plan_currency_ready(isset($before['currency']) ? $before['currency'] : '')) {
                return $this->plan_currency_not_ready_result('make purchasable');
            }
            if ((int) $before['is_active'] !== 1) {
                return $this->failure_result('Only active plans can be made purchasable.');
            }
            $data['is_purchasable'] = 1;
            $action = 'make_purchasable';
        } elseif ($status_action === 'hide_from_purchase') {
            $data['is_purchasable'] = 0;
            $action = 'hide_from_purchase';
        } else {
            return $this->failure_result('Invalid status action.');
        }

        return $this->mutate_existing_plan($plan_id, $actor_user_id, $action, $before, $data);
    }

    public function archive_plan($plan_id, $actor_user_id)
    {
        $schema = $this->get_schema_status();
        if (empty($schema['phase_2l_applied'])) {
            return $this->mutation_schema_missing_result();
        }

        $before = $this->get_plan($plan_id);
        if (empty($before)) {
            return $this->failure_result('Subscription plan was not found.');
        }
        if (!empty($before['archived_at'])) {
            return $this->failure_result('Subscription plan is already archived.');
        }

        return $this->mutate_existing_plan($plan_id, $actor_user_id, 'archive', $before, array(
            'is_active' => 0,
            'is_purchasable' => 0,
            'archived_at' => time(),
            'archived_by_user_id' => $actor_user_id ? (int) $actor_user_id : null,
            'updated_at' => time(),
        ));
    }

    public function restore_plan($plan_id, $actor_user_id)
    {
        $schema = $this->get_schema_status();
        if (empty($schema['phase_2l_applied'])) {
            return $this->mutation_schema_missing_result();
        }

        $before = $this->get_plan($plan_id);
        if (empty($before)) {
            return $this->failure_result('Subscription plan was not found.');
        }
        if (empty($before['archived_at'])) {
            return $this->failure_result('Subscription plan is not archived.');
        }

        return $this->mutate_existing_plan($plan_id, $actor_user_id, 'restore', $before, array(
            'is_active' => 0,
            'is_purchasable' => 0,
            'archived_at' => null,
            'archived_by_user_id' => null,
            'updated_at' => time(),
        ));
    }

    public function get_audit_history($plan_id, $limit = 25)
    {
        if (!$this->db->table_exists($this->audit_table)) {
            return array();
        }

        return $this->db
            ->where('plan_id', (int) $plan_id)
            ->order_by('created_at', 'DESC')
            ->limit((int) $limit)
            ->get($this->audit_table)
            ->result_array();
    }

    public function get_system_currency()
    {
        if (function_exists('get_settings')) {
            $currency = get_settings('system_currency');
            if ($currency !== null) {
                return trim((string) $currency);
            }
        }

        if ($this->db->table_exists('settings')) {
            $row = $this->db
                ->select('value')
                ->where('key', 'system_currency')
                ->limit(1)
                ->get('settings')
                ->row_array();
            if (is_array($row) && array_key_exists('value', $row)) {
                return trim((string) $row['value']);
            }
        }

        return 'USD';
    }

    protected function currency_not_ready_result($action)
    {
        return $this->failure_result(
            'YounGo commercial currency is EGP. Current system currency is ' . $this->clean_currency($this->get_system_currency()) . '. Change system currency to EGP through the approved settings flow before you ' . $action . '. Existing inactive USD placeholders are safe but cannot be commercialized.'
        );
    }

    protected function plan_currency_not_ready_result($action)
    {
        return $this->failure_result(
            'This subscription plan is not stored in EGP. YounGo commercial currency is EGP, so this plan cannot be ' . $action . ' until it is corrected through the approved dashboard flow after system currency is EGP.'
        );
    }

    protected function mutate_existing_plan($plan_id, $actor_user_id, $action, $before, $data)
    {
        $this->db->trans_begin();
        $updated = $this->safe_update($this->plan_table, array('id' => (int) $plan_id), $data);
        $after = $this->get_plan($plan_id);

        if (!$updated) {
            $this->db->trans_rollback();
            return $this->failure_result('Could not update the subscription plan.');
        }

        if (!$this->write_audit_log($plan_id, $actor_user_id, $action, $before, $after)) {
            $this->db->trans_rollback();
            return $this->failure_result('Could not write the subscription plan audit record.');
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure_result('Could not update the subscription plan.');
        }

        $this->db->trans_commit();
        return array('success' => true, 'plan_id' => (int) $plan_id);
    }

    protected function write_audit_log($plan_id, $actor_user_id, $action, $before, $after)
    {
        if (!$this->db->table_exists($this->audit_table)) {
            return false;
        }

        return $this->safe_insert($this->audit_table, array(
            'plan_id' => (int) $plan_id,
            'actor_user_id' => $actor_user_id ? (int) $actor_user_id : null,
            'action' => (string) $action,
            'before_data' => $before === null ? null : json_encode($this->audit_safe_snapshot($before), JSON_UNESCAPED_SLASHES),
            'after_data' => $after === null ? null : json_encode($this->audit_safe_snapshot($after), JSON_UNESCAPED_SLASHES),
            'created_at' => time(),
        ));
    }

    protected function safe_insert($table, $data)
    {
        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $result = $this->db->insert($table, $data);
        $this->db->db_debug = $db_debug;

        return (bool) $result;
    }

    protected function safe_update($table, $where, $data)
    {
        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;
        foreach ($where as $field => $value) {
            $this->db->where($field, $value);
        }
        $result = $this->db->update($table, $data);
        $this->db->db_debug = $db_debug;

        return (bool) $result;
    }

    protected function safe_delete($table, $where)
    {
        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;
        foreach ($where as $field => $value) {
            $this->db->where($field, $value);
        }
        $result = $this->db->delete($table);
        $this->db->db_debug = $db_debug;

        return (bool) $result;
    }

    protected function audit_safe_snapshot($plan)
    {
        $allowed = array(
            'id', 'name', 'slug', 'duration_days', 'price', 'currency',
            'is_active', 'is_purchasable', 'is_featured', 'sort_order',
            'created_at', 'updated_at', 'archived_at', 'archived_by_user_id',
        );

        $snapshot = array();
        foreach ($allowed as $key) {
            if (array_key_exists($key, $plan)) {
                $snapshot[$key] = $plan[$key];
            }
        }

        return $snapshot;
    }

    protected function validate_plan_translation_data($input)
    {
        $errors = array();
        $data = array();

        if (!is_array($input)) {
            return array(
                'success' => false,
                'errors' => array('Subscription plan translation input must be an array.'),
                'data' => array(),
            );
        }

        $forbidden_fields = array(
            'slug', 'price', 'currency', 'duration', 'duration_days',
            'is_active', 'is_purchasable', 'is_featured', 'sort_order',
            'archived_at', 'archived_by_user_id', 'payment', 'paymob',
            'checkout', 'order_id', 'enrol_id', 'access_grant',
        );
        foreach ($forbidden_fields as $field) {
            if (array_key_exists($field, $input)) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is not a translatable subscription plan field.';
            }
        }

        $name = $this->scalar_input($input, 'name', true, $errors, 'Translation name');
        if ($name !== null) {
            $name = trim($name);
            if ($name === '') {
                $errors[] = 'Translation name is required.';
            } elseif (strlen($name) > 255) {
                $errors[] = 'Translation name must be 255 characters or fewer.';
            } else {
                $data['name'] = $name;
            }
        }

        foreach (array(
            'short_description' => 500,
            'description' => null,
            'badge_label' => 100,
        ) as $field => $max_length) {
            $value = $this->scalar_input($input, $field, false, $errors, ucfirst(str_replace('_', ' ', $field)));
            if ($value === null) {
                $data[$field] = null;
                continue;
            }

            $value = trim($value);
            if ($value === '') {
                $data[$field] = null;
                continue;
            }
            if ($max_length !== null && strlen($value) > $max_length) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' must be ' . $max_length . ' characters or fewer.';
                continue;
            }

            $data[$field] = $value;
        }

        return array(
            'success' => empty($errors),
            'errors' => $errors,
            'data' => $data,
        );
    }

    protected function plan_translation_payload_has_content($payload)
    {
        if (!is_array($payload)) {
            return false;
        }

        foreach (array('name', 'short_description', 'description', 'badge_label') as $field) {
            if (isset($payload[$field]) && !is_array($payload[$field]) && !is_object($payload[$field]) && trim((string) $payload[$field]) !== '') {
                return true;
            }
        }

        return false;
    }

    protected function delete_plan_translation($plan_id, $language)
    {
        $language = $this->normalize_subscription_translation_language($language);
        if (!$this->valid_id($plan_id) || $language === null || !$this->subscription_translation_table_exists()) {
            return false;
        }

        return $this->safe_delete($this->translation_table, array(
            'plan_id' => (int) $plan_id,
            'language_code' => $language,
        ));
    }

    protected function slug_is_unique($slug, $ignore_id = null)
    {
        if (!$this->db->table_exists($this->plan_table)) {
            return false;
        }

        $this->db->where('slug', $slug);
        if ($ignore_id !== null && (int) $ignore_id > 0) {
            $this->db->where('id !=', (int) $ignore_id);
        }

        return $this->db->count_all_results($this->plan_table) === 0;
    }

    protected function scalar_input($input, $field, $required, &$errors, $label)
    {
        if (!array_key_exists($field, $input)) {
            if ($required) {
                $errors[] = $label . ' is required.';
            }
            return null;
        }

        if (is_array($input[$field]) || is_object($input[$field])) {
            $errors[] = $label . ' must be a scalar value.';
            return null;
        }

        return (string) $input[$field];
    }

    protected function flag_input($input, $field, &$errors)
    {
        if (!array_key_exists($field, $input)) {
            return 0;
        }

        if (is_array($input[$field]) || is_object($input[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' must be a scalar value.';
            return null;
        }

        $value = strtolower(trim((string) $input[$field]));
        if (in_array($value, array('1', 'on', 'true', 'yes'), true)) {
            return 1;
        }
        if (in_array($value, array('0', 'off', 'false', 'no', ''), true)) {
            return 0;
        }

        $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' must be 0 or 1.';
        return null;
    }

    protected function with_archive_defaults($row)
    {
        if (!array_key_exists('archived_at', $row)) {
            $row['archived_at'] = null;
        }
        if (!array_key_exists('archived_by_user_id', $row)) {
            $row['archived_by_user_id'] = null;
        }

        return $row;
    }

    protected function normalize_public_plan_row($row, $language)
    {
        $duration_days = isset($row['duration_days']) ? (int) $row['duration_days'] : 0;
        $currency = isset($row['currency']) ? $this->clean_currency($row['currency']) : $this->youngo_commercial_currency;
        $price = isset($row['price']) ? number_format((float) $row['price'], 2, '.', '') : '0.00';
        $description = $this->localized_public_value($row, 'description', $language);
        if ($description === '') {
            $description = $this->localized_public_value($row, 'summary', $language);
        }

        $short_description = $this->localized_public_value($row, 'short_description', $language);

        if ($short_description === '') {
            $short_description = $description;
        }

        return array(
            'id' => isset($row['id']) ? (int) $row['id'] : 0,
            'name' => $this->localized_public_value($row, 'name', $language),
            'slug' => isset($row['slug']) ? trim((string) $row['slug']) : '',
            'duration' => $duration_days,
            'duration_days' => $duration_days,
            'duration_label' => $this->format_public_plan_duration($duration_days, $language),
            'price' => $price,
            'price_display' => $this->format_public_plan_price($price, $currency, $language),
            'currency' => $currency,
            'featured' => !empty($row['is_featured']) ? 1 : 0,
            'short_description' => $short_description,
            'description' => $description,
            'badge_label' => $this->localized_public_value($row, 'badge_label', $language),
        );
    }

    protected function localized_public_value($row, $field, $language)
    {
        $language = $this->normalize_public_language($language);
        $candidates = array();

        if ($language === 'arabic') {
            $candidates = array('arabic_' . $field, $field . '_ar', 'ar_' . $field, $field . '_arabic');
        } else {
            $candidates = array('english_' . $field, $field . '_en', 'en_' . $field, $field . '_english');
        }

        $candidates[] = $field;
        foreach ($candidates as $candidate) {
            if (array_key_exists($candidate, $row)) {
                $value = trim((string) $row[$candidate]);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    protected function normalize_public_language($language)
    {
        if ($language === null && file_exists(APPPATH . 'helpers/youngo_frontend_language_helper.php')) {
            $this->load->helper('youngo_frontend_language');
            if (function_exists('youngo_frontend_active_language')) {
                $language = youngo_frontend_active_language();
            }
        }

        if (file_exists(APPPATH . 'helpers/youngo_frontend_language_helper.php')) {
            $this->load->helper('youngo_frontend_language');
            if (function_exists('youngo_frontend_normalize_language_code')) {
                return youngo_frontend_normalize_language_code($language);
            }
        }

        $language = strtolower(trim((string) $language));
        return in_array($language, array('arabic', 'ar', 'arabic_translated'), true) ? 'arabic' : 'english';
    }

    protected function normalize_public_subscription_language($language)
    {
        if (is_string($language) && strtolower(trim($language)) === 'arabic_translated') {
            return null;
        }

        if ($language === null) {
            $language = $this->normalize_public_language(null);
        }

        $normalized = $this->normalize_subscription_translation_language($language);
        return $normalized === null ? 'english' : $normalized;
    }

    protected function extract_plan_ids($rows)
    {
        $ids = array();
        foreach ($rows as $row) {
            if (isset($row['id']) && $this->valid_id($row['id'])) {
                $ids[] = (int) $row['id'];
            }
        }

        return array_values(array_unique($ids));
    }

    protected function load_public_plan_translations($plan_ids, $language)
    {
        $language = $this->normalize_subscription_translation_language($language);
        if ($language === null || empty($plan_ids) || !$this->subscription_translation_table_exists()) {
            return array();
        }

        $this->db->select('plan_id, language_code, name, short_description, description, badge_label');
        $this->db->from($this->translation_table);
        $this->db->where('language_code', $language);
        $this->db->where_in('plan_id', array_map('intval', $plan_ids));
        $rows = $this->db->get()->result_array();

        $translations = array();
        foreach ($rows as $row) {
            $plan_id = isset($row['plan_id']) ? (int) $row['plan_id'] : 0;
            if ($plan_id <= 0) {
                continue;
            }

            $translations[$plan_id] = array(
                'name' => isset($row['name']) ? (string) $row['name'] : '',
                'short_description' => isset($row['short_description']) ? (string) $row['short_description'] : '',
                'description' => isset($row['description']) ? (string) $row['description'] : '',
                'badge_label' => isset($row['badge_label']) ? (string) $row['badge_label'] : '',
            );
        }

        return $translations;
    }

    protected function apply_public_plan_translation($row, $translation)
    {
        foreach (array('name', 'short_description', 'description', 'badge_label') as $field) {
            if (array_key_exists($field, $translation)) {
                $value = trim((string) $translation[$field]);
                if ($value !== '') {
                    $row[$field] = $value;
                }
            }
        }

        return $row;
    }

    protected function plan_fields()
    {
        if (!$this->db->table_exists($this->plan_table)) {
            return array();
        }

        return $this->db->list_fields($this->plan_table);
    }

    protected function mutation_schema_missing_result()
    {
        return $this->failure_result('Phase 2L archive/audit schema is not applied. Listing is available, but plan changes are disabled until the migration is applied.');
    }

    protected function failure_result($message)
    {
        return array(
            'success' => false,
            'errors' => array($message),
            'message' => $message,
        );
    }

    protected function valid_id($value)
    {
        return is_numeric($value) && (int) $value > 0;
    }

    protected function valid_currency_code($value)
    {
        return is_string($value) && preg_match('/^[A-Z]{3,10}$/', $this->clean_currency($value));
    }

    protected function clean_currency($value)
    {
        return trim((string) $value);
    }
}
