<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Youngo_translation_model extends CI_Model
{
    protected $table_exists_cache = array();
    protected $field_exists_cache = array();

    protected $translation_config = array(
        'course' => array(
            'translation_table' => 'youngo_course_translations',
            'translation_key' => 'course_id',
            'canonical_table' => 'course',
            'canonical_key' => 'id',
            'label_field' => 'title',
            'fields' => array('title', 'slug', 'short_description', 'description', 'outcomes', 'requirements', 'faqs', 'seo_title', 'meta_keywords', 'meta_description'),
        ),
        'category' => array(
            'translation_table' => 'youngo_category_translations',
            'translation_key' => 'category_id',
            'canonical_table' => 'category',
            'canonical_key' => 'id',
            'label_field' => 'name',
            'fields' => array('name', 'slug', 'description'),
        ),
        'section' => array(
            'translation_table' => 'youngo_section_translations',
            'translation_key' => 'section_id',
            'canonical_table' => 'section',
            'canonical_key' => 'id',
            'label_field' => 'title',
            'fields' => array('title'),
        ),
        'lesson' => array(
            'translation_table' => 'youngo_lesson_translations',
            'translation_key' => 'lesson_id',
            'canonical_table' => 'lesson',
            'canonical_key' => 'id',
            'label_field' => 'title',
            'fields' => array('title', 'summary', 'text_content'),
        ),
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function normalize_language_code($language_code)
    {
        if ($language_code === null || $language_code === '') {
            return 'english';
        }

        $language_code = strtolower(trim((string) $language_code));
        $language_code = str_replace('-', '_', $language_code);

        if ($language_code === 'en' || $language_code === 'eng' || $language_code === 'english') {
            return 'english';
        }

        if ($language_code === 'ar' || $language_code === 'ara' || $language_code === 'arabic' || $language_code === 'arabic_translated') {
            return 'arabic';
        }

        return 'english';
    }

    public function get_supported_language_codes()
    {
        return array('english', 'arabic');
    }

    public function get_course_translation($course_id, $language_code = 'english')
    {
        return $this->get_translation('course', $course_id, $language_code);
    }

    public function get_category_translation($category_id, $language_code = 'english')
    {
        return $this->get_translation('category', $category_id, $language_code);
    }

    public function get_section_translation($section_id, $language_code = 'english')
    {
        return $this->get_translation('section', $section_id, $language_code);
    }

    public function get_lesson_translation($lesson_id, $language_code = 'english')
    {
        return $this->get_translation('lesson', $lesson_id, $language_code);
    }

    public function get_course_translation_with_fallback($course_id, $language_code = 'english')
    {
        return $this->get_translation_with_fallback('course', $course_id, $language_code);
    }

    public function get_category_translation_with_fallback($category_id, $language_code = 'english')
    {
        return $this->get_translation_with_fallback('category', $category_id, $language_code);
    }

    public function get_section_translation_with_fallback($section_id, $language_code = 'english')
    {
        return $this->get_translation_with_fallback('section', $section_id, $language_code);
    }

    public function get_lesson_translation_with_fallback($lesson_id, $language_code = 'english')
    {
        return $this->get_translation_with_fallback('lesson', $lesson_id, $language_code);
    }

    public function upsert_course_translation($course_id, $language_code, array $data)
    {
        return $this->upsert_translation('course', $course_id, $language_code, $data);
    }

    public function upsert_category_translation($category_id, $language_code, array $data)
    {
        return $this->upsert_translation('category', $category_id, $language_code, $data);
    }

    public function upsert_section_translation($section_id, $language_code, array $data)
    {
        return $this->upsert_translation('section', $section_id, $language_code, $data);
    }

    public function upsert_lesson_translation($lesson_id, $language_code, array $data)
    {
        return $this->upsert_translation('lesson', $lesson_id, $language_code, $data);
    }

    public function generate_slug($text, $language_code = 'english')
    {
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }

        $language_code = $this->normalize_language_code($language_code);

        if (function_exists('slugify')) {
            $slug = slugify($text);
        } else {
            $slug = preg_replace('~[^\\pL\\d]+~u', '-', $text);
            $slug = trim($slug, '-');
            $slug = strtolower($slug);
        }

        $slug = trim((string) $slug);
        if ($slug === '') {
            $slug = $language_code . '-' . substr(md5($text), 0, 8);
        }

        return $slug;
    }

    public function is_slug_available($entity_type, $slug, $language_code, $exclude_entity_id = null)
    {
        $config = $this->get_entity_config($entity_type);
        if ($config === null) {
            return false;
        }

        $slug = trim((string) $slug);
        if ($slug === '') {
            return true;
        }

        if (!in_array('slug', $config['fields'], true) || !$this->table_exists($config['translation_table'])) {
            return true;
        }

        $language_code = $this->normalize_language_code($language_code);
        $this->db->where('slug', $slug);
        $this->db->where('language_code', $language_code);

        $exclude_entity_id = (int) $exclude_entity_id;
        if ($exclude_entity_id > 0) {
            $this->db->where($config['translation_key'] . ' !=', $exclude_entity_id);
        }

        return $this->db->count_all_results($config['translation_table']) === 0;
    }

    public function has_translation($entity_type, $entity_id, $language_code)
    {
        $translation = $this->get_translation($entity_type, $entity_id, $language_code);
        if (empty($translation)) {
            return false;
        }

        $config = $this->get_entity_config($entity_type);
        if ($config === null) {
            return false;
        }

        return !$this->translation_is_incomplete($translation, $config);
    }

    public function get_missing_translation_summary($entity_type, $entity_id)
    {
        $config = $this->get_entity_config($entity_type);
        $entity_id = (int) $entity_id;

        if ($config === null || $entity_id <= 0) {
            return array(
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'exists' => false,
                'languages' => array(),
                'missing_languages' => array('english', 'arabic'),
            );
        }

        $summary = array(
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'exists' => $this->canonical_entity_exists($config, $entity_id),
            'languages' => array(),
            'missing_languages' => array(),
        );

        foreach ($this->get_supported_language_codes() as $language_code) {
            $has_translation = $this->has_translation($entity_type, $entity_id, $language_code);
            $summary['languages'][$language_code] = array(
                'has_translation' => $has_translation,
            );

            if (!$has_translation) {
                $summary['missing_languages'][] = $language_code;
            }
        }

        return $summary;
    }

    protected function get_translation($entity_type, $entity_id, $language_code)
    {
        $config = $this->get_entity_config($entity_type);
        $entity_id = (int) $entity_id;
        $language_code = $this->normalize_language_code($language_code);

        if ($config === null || $entity_id <= 0 || !$this->table_exists($config['translation_table'])) {
            return null;
        }

        $this->db->where($config['translation_key'], $entity_id);
        $this->db->where('language_code', $language_code);
        $query = $this->db->get($config['translation_table'], 1);

        if ($query->num_rows() === 0) {
            return null;
        }

        return $query->row_array();
    }

    protected function get_translation_with_fallback($entity_type, $entity_id, $language_code)
    {
        $config = $this->get_entity_config($entity_type);
        $entity_id = (int) $entity_id;
        $requested_language = $this->normalize_language_code($language_code);

        if ($config === null || $entity_id <= 0) {
            return null;
        }

        $requested_translation = $this->get_translation($entity_type, $entity_id, $requested_language);
        if (!empty($requested_translation) && !$this->translation_is_incomplete($requested_translation, $config)) {
            return $this->append_translation_meta($requested_translation, $requested_language, $requested_language, false, false, 'translation');
        }

        if ($requested_language !== 'english') {
            $english_translation = $this->get_translation($entity_type, $entity_id, 'english');
            if (!empty($english_translation) && !$this->translation_is_incomplete($english_translation, $config)) {
                return $this->append_translation_meta($english_translation, $requested_language, 'english', true, true, 'translation');
            }
        }

        $canonical = $this->get_canonical_fallback($entity_type, $entity_id);
        if (!empty($canonical)) {
            return $this->append_translation_meta($canonical, $requested_language, 'english', true, true, 'canonical');
        }

        return null;
    }

    protected function upsert_translation($entity_type, $entity_id, $language_code, array $data)
    {
        $config = $this->get_entity_config($entity_type);
        $entity_id = (int) $entity_id;
        $language_code = $this->normalize_language_code($language_code);

        if ($config === null) {
            return $this->result(false, 'invalid_entity_type', 'Unsupported translation entity type.');
        }

        if ($entity_id <= 0 || !$this->canonical_entity_exists($config, $entity_id)) {
            return $this->result(false, 'invalid_entity_id', 'Canonical entity was not found.');
        }

        if (!$this->is_supported_language($language_code)) {
            return $this->result(false, 'invalid_language', 'Unsupported language code.');
        }

        if (!$this->table_exists($config['translation_table'])) {
            return $this->result(false, 'schema_not_ready', 'Translation table is not available.');
        }

        $filtered = $this->filter_translation_data($data, $config['fields']);
        if (empty($filtered)) {
            return $this->result(false, 'empty_data', 'No supported translation fields were provided.');
        }

        $existing = $this->get_translation($entity_type, $entity_id, $language_code);
        $now = time();

        if (empty($existing)) {
            $filtered[$config['translation_key']] = $entity_id;
            $filtered['language_code'] = $language_code;
            $filtered['created_at'] = $now;
            $filtered['updated_at'] = $now;

            $inserted = $this->db->insert($config['translation_table'], $filtered);
            if (!$inserted) {
                return $this->result(false, 'insert_failed', 'Translation row could not be created.');
            }

            return $this->result(true, 'inserted', 'Translation row created.', $this->db->insert_id(), 1);
        }

        $filtered['updated_at'] = $now;
        $this->db->where('id', (int) $existing['id']);
        $updated = $this->db->update($config['translation_table'], $filtered);

        if (!$updated) {
            return $this->result(false, 'update_failed', 'Translation row could not be updated.', (int) $existing['id']);
        }

        return $this->result(true, 'updated', 'Translation row updated.', (int) $existing['id'], $this->db->affected_rows());
    }

    protected function get_canonical_fallback($entity_type, $entity_id)
    {
        $config = $this->get_entity_config($entity_type);
        $entity_id = (int) $entity_id;

        if ($config === null || $entity_id <= 0 || !$this->table_exists($config['canonical_table'])) {
            return null;
        }

        $this->db->where($config['canonical_key'], $entity_id);
        $query = $this->db->get($config['canonical_table'], 1);

        if ($query->num_rows() === 0) {
            return null;
        }

        $row = $query->row_array();

        if ($entity_type === 'course') {
            return array(
                'course_id' => $entity_id,
                'language_code' => 'english',
                'title' => isset($row['title']) ? $row['title'] : null,
                'slug' => null,
                'short_description' => isset($row['short_description']) ? $row['short_description'] : null,
                'description' => isset($row['description']) ? $row['description'] : null,
                'outcomes' => isset($row['outcomes']) ? $row['outcomes'] : null,
                'requirements' => isset($row['requirements']) ? $row['requirements'] : null,
                'faqs' => isset($row['faqs']) ? $row['faqs'] : null,
                'seo_title' => null,
                'meta_keywords' => isset($row['meta_keywords']) ? $row['meta_keywords'] : null,
                'meta_description' => isset($row['meta_description']) ? $row['meta_description'] : null,
            );
        }

        if ($entity_type === 'category') {
            return array(
                'category_id' => $entity_id,
                'language_code' => 'english',
                'name' => isset($row['name']) ? $row['name'] : null,
                'slug' => isset($row['slug']) ? $row['slug'] : null,
                'description' => null,
            );
        }

        if ($entity_type === 'section') {
            return array(
                'section_id' => $entity_id,
                'language_code' => 'english',
                'title' => isset($row['title']) ? $row['title'] : null,
            );
        }

        if ($entity_type === 'lesson') {
            $is_text_lesson = isset($row['lesson_type'], $row['attachment_type'])
                && $row['lesson_type'] === 'text'
                && $row['attachment_type'] === 'description';

            return array(
                'lesson_id' => $entity_id,
                'language_code' => 'english',
                'title' => isset($row['title']) ? $row['title'] : null,
                'summary' => isset($row['summary']) ? $row['summary'] : null,
                'text_content' => $is_text_lesson && isset($row['attachment']) ? $row['attachment'] : null,
            );
        }

        return null;
    }

    protected function get_entity_config($entity_type)
    {
        $entity_type = strtolower(trim((string) $entity_type));
        return isset($this->translation_config[$entity_type]) ? $this->translation_config[$entity_type] : null;
    }

    protected function is_supported_language($language_code)
    {
        return in_array($language_code, $this->get_supported_language_codes(), true);
    }

    protected function filter_translation_data(array $data, array $allowed_fields)
    {
        $filtered = array();

        foreach ($allowed_fields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];
            if (is_array($value)) {
                $value = json_encode($value);
            } elseif ($value !== null && !is_scalar($value)) {
                continue;
            }

            if (is_string($value)) {
                $value = trim($value);
            }

            $filtered[$field] = $value;
        }

        return $filtered;
    }

    protected function append_translation_meta(array $row, $requested_language, $resolved_language, $is_fallback, $missing_translation, $source)
    {
        $row['requested_language'] = $requested_language;
        $row['resolved_language'] = $resolved_language;
        $row['is_fallback'] = $is_fallback ? true : false;
        $row['missing_translation'] = $missing_translation ? true : false;
        $row['translation_source'] = $source;

        return $row;
    }

    protected function translation_is_incomplete(array $row, array $config)
    {
        $label_field = $config['label_field'];
        return !isset($row[$label_field]) || trim((string) $row[$label_field]) === '';
    }

    protected function canonical_entity_exists(array $config, $entity_id)
    {
        $entity_id = (int) $entity_id;
        if ($entity_id <= 0 || !$this->table_exists($config['canonical_table'])) {
            return false;
        }

        $this->db->where($config['canonical_key'], $entity_id);
        return $this->db->count_all_results($config['canonical_table']) > 0;
    }

    protected function table_exists($table)
    {
        if (isset($this->table_exists_cache[$table])) {
            return $this->table_exists_cache[$table];
        }

        $this->table_exists_cache[$table] = $this->db->table_exists($table);
        return $this->table_exists_cache[$table];
    }

    protected function result($success, $code, $message, $row_id = null, $affected_rows = null)
    {
        return array(
            'success' => $success ? true : false,
            'code' => $code,
            'message' => $message,
            'row_id' => $row_id,
            'affected_rows' => $affected_rows,
        );
    }
}
