<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Youngo_language_phrase_model extends CI_Model
{
    protected $language_table = 'language';
    protected $batch_table = 'youngo_language_import_batches';
    protected $meta_table = 'youngo_language_phrase_meta';
    protected $allowed_language_codes = array('english', 'arabic');
    protected $arabic_import_language_code = 'arabic';
    protected $arabic_import_source_file = 'arabic.json';
    protected $allowed_arabic_import_modes = array(
        'missing_blank_only',
        'update_imported_non_manual',
        'force_overwrite',
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function metadata_tables_exist()
    {
        return array(
            'import_batches_table_exists' => $this->db->table_exists($this->batch_table),
            'phrase_meta_table_exists' => $this->db->table_exists($this->meta_table),
            'ready' => $this->db->table_exists($this->batch_table) && $this->db->table_exists($this->meta_table),
        );
    }

    public function normalize_ui_language_code($language_code)
    {
        $language_code = strtolower(trim((string) $language_code));

        if ($language_code === 'en' || $language_code === 'english') {
            return 'english';
        }

        if ($language_code === 'ar' || $language_code === 'arabic') {
            return 'arabic';
        }

        return null;
    }

    public function is_supported_ui_language($language_code)
    {
        $language_code = $this->normalize_ui_language_code($language_code);

        return $language_code !== null && in_array($language_code, $this->allowed_language_codes, true);
    }

    public function get_phrase_meta_status($phrase_key, $language_code)
    {
        $language_code = $this->normalize_ui_language_code($language_code);
        $phrase_key = $this->normalize_phrase_key($phrase_key);

        if ($language_code === null || $phrase_key === '' || !$this->metadata_tables_exist()['phrase_meta_table_exists']) {
            return array(
                'exists' => false,
                'phrase_key' => $phrase_key,
                'language_code' => $language_code,
                'source' => null,
                'has_current_value_hash' => false,
                'has_last_imported_value_hash' => false,
                'last_import_batch_id' => null,
                'manually_overridden' => false,
            );
        }

        $row = $this->db
            ->select('phrase_key, language_code, source, last_import_batch_id, last_imported_value_hash, current_value_hash, manually_overridden_at, manually_overridden_by_user_id, created_at, updated_at')
            ->from($this->meta_table)
            ->where('phrase_key', $phrase_key)
            ->where('language_code', $language_code)
            ->limit(1)
            ->get()
            ->row_array();

        if (!$row) {
            return array(
                'exists' => false,
                'phrase_key' => $phrase_key,
                'language_code' => $language_code,
                'source' => null,
                'has_current_value_hash' => false,
                'has_last_imported_value_hash' => false,
                'last_import_batch_id' => null,
                'manually_overridden' => false,
            );
        }

        return array(
            'exists' => true,
            'phrase_key' => $row['phrase_key'],
            'language_code' => $row['language_code'],
            'source' => $row['source'],
            'has_current_value_hash' => !empty($row['current_value_hash']),
            'has_last_imported_value_hash' => !empty($row['last_imported_value_hash']),
            'last_import_batch_id' => $row['last_import_batch_id'],
            'manually_overridden' => !empty($row['manually_overridden_at']),
            'manually_overridden_by_user_id' => $row['manually_overridden_by_user_id'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        );
    }

    public function mark_phrase_manual_override($phrase_key, $language_code, $actor_id = null)
    {
        $language_code = $this->normalize_ui_language_code($language_code);
        $phrase_key = $this->normalize_phrase_key($phrase_key);

        if ($language_code === null || $language_code === 'arabic_translated' || $phrase_key === '' || !$this->metadata_tables_exist()['ready']) {
            return false;
        }

        if (!$this->db->field_exists($language_code, $this->language_table)) {
            return false;
        }

        $phrase = $this->db
            ->select('phrase_id, phrase, `' . $language_code . '` AS current_value', false)
            ->from($this->language_table)
            ->where('phrase', $phrase_key)
            ->limit(1)
            ->get()
            ->row_array();

        if (!$phrase) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $data = array(
            'phrase_id' => isset($phrase['phrase_id']) ? (int) $phrase['phrase_id'] : null,
            'phrase_key' => $phrase_key,
            'language_code' => $language_code,
            'source' => 'manual_override',
            'current_value_hash' => $this->hash_phrase_value($phrase['current_value']),
            'manually_overridden_at' => $now,
            'manually_overridden_by_user_id' => $actor_id === null ? null : (int) $actor_id,
            'updated_at' => $now,
        );

        $existing = $this->db
            ->select('id')
            ->from($this->meta_table)
            ->where('phrase_key', $phrase_key)
            ->where('language_code', $language_code)
            ->limit(1)
            ->get()
            ->row_array();

        if ($existing) {
            $this->db->where('id', (int) $existing['id']);
            return (bool) $this->db->update($this->meta_table, $data);
        }

        $data['created_at'] = $now;
        return (bool) $this->db->insert($this->meta_table, $data);
    }

    public function build_import_meta_preview_stub($language_code, $source_file, $import_mode)
    {
        $language_code = $this->normalize_ui_language_code($language_code);
        $import_mode = trim((string) $import_mode);

        return array(
            'ready' => $this->metadata_tables_exist()['ready'],
            'language_code' => $language_code,
            'source_file' => basename((string) $source_file),
            'import_mode' => $import_mode,
            'can_import_to_ui_language' => $language_code === 'arabic',
            'blocks_arabic_translated' => $this->normalize_ui_language_code('arabic_translated') === null,
            'stores_raw_phrase_values' => false,
        );
    }

    public function get_edit_phrase_language_options()
    {
        $columns = $this->get_language_table_columns();
        $languages = array();

        foreach ($columns as $column) {
            if ($this->is_edit_phrase_language_column($column)) {
                $languages[] = $column;
            }
        }

        sort($languages);

        return $languages;
    }

    public function normalize_edit_phrase_language_filter($language_code)
    {
        $language_code = strtolower(trim((string) $language_code));

        if ($language_code === 'en') {
            $language_code = 'english';
        } elseif ($language_code === 'ar') {
            $language_code = 'arabic';
        }

        if (!$this->is_edit_phrase_language_column($language_code)) {
            return null;
        }

        if (!$this->db->field_exists($language_code, $this->language_table)) {
            return null;
        }

        return $language_code;
    }

    public function get_paginated_edit_phrases($language_code = 'english', $page = 1, $per_page = 25, $search = '')
    {
        $language_code = $this->normalize_edit_phrase_language_filter($language_code);
        if ($language_code === null) {
            return array(
                'valid' => false,
                'error' => 'Invalid or unsupported UI language.',
                'language' => null,
                'page' => 1,
                'per_page' => 25,
                'total_rows' => 0,
                'total_pages' => 0,
                'offset' => 0,
                'rows' => array(),
                'supported_languages' => $this->get_edit_phrase_language_options(),
            );
        }

        $page = max(1, (int) $page);
        $per_page = $this->normalize_edit_phrase_per_page($per_page);
        $offset = ($page - 1) * $per_page;
        $search = trim((string) $search);
        $whereSql = '';
        $binds = array();

        if ($search !== '') {
            $like = '%' . $this->escape_like_value($search) . '%';
            $whereSql = ' WHERE (`phrase` LIKE ? ESCAPE \'!\' OR `' . $language_code . '` LIKE ? ESCAPE \'!\'';
            $binds[] = $like;
            $binds[] = $like;

            if ($language_code !== 'english' && $this->db->field_exists('english', $this->language_table)) {
                $whereSql .= ' OR `english` LIKE ? ESCAPE \'!\'';
                $binds[] = $like;
            }

            $whereSql .= ')';
        }

        $countRow = $this->db
            ->query('SELECT COUNT(*) AS total_rows FROM `' . $this->language_table . '`' . $whereSql, $binds)
            ->row_array();
        $totalRows = isset($countRow['total_rows']) ? (int) $countRow['total_rows'] : 0;
        $totalPages = $totalRows > 0 ? (int) ceil($totalRows / $per_page) : 0;

        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $per_page;
        }

        $selectSql = 'SELECT `phrase_id`, `phrase`, `' . $language_code . '` AS `selected_value`';
        if ($language_code !== 'english' && $this->db->field_exists('english', $this->language_table)) {
            $selectSql .= ', `english` AS `english_value`';
        } else {
            $selectSql .= ', NULL AS `english_value`';
        }
        $selectSql .= ' FROM `' . $this->language_table . '`' . $whereSql . ' ORDER BY `phrase` ASC LIMIT ? OFFSET ?';

        $rowBinds = $binds;
        $rowBinds[] = $per_page;
        $rowBinds[] = $offset;
        $rows = $this->db->query($selectSql, $rowBinds)->result_array();

        return array(
            'valid' => true,
            'language' => $language_code,
            'page' => $page,
            'per_page' => $per_page,
            'total_rows' => $totalRows,
            'total_pages' => $totalPages,
            'offset' => $offset,
            'search' => $search,
            'rows' => $this->normalize_edit_phrase_rows($rows),
            'supported_languages' => $this->get_edit_phrase_language_options(),
        );
    }

    protected function get_language_table_columns()
    {
        $rows = $this->db
            ->query(
                'SELECT COLUMN_NAME AS column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? ORDER BY ORDINAL_POSITION',
                array($this->language_table)
            )
            ->result_array();

        $columns = array();
        foreach ($rows as $row) {
            if (!empty($row['column_name'])) {
                $columns[] = (string) $row['column_name'];
            }
        }

        return $columns;
    }

    protected function is_edit_phrase_language_column($column)
    {
        $column = strtolower(trim((string) $column));

        return $column !== ''
            && $column !== 'phrase_id'
            && $column !== 'phrase'
            && $column !== 'arabic_translated'
            && preg_match('/^[a-z][a-z0-9_]*$/', $column);
    }

    protected function normalize_edit_phrase_per_page($per_page)
    {
        $per_page = (int) $per_page;
        $allowed = array(25, 50, 100);

        return in_array($per_page, $allowed, true) ? $per_page : 25;
    }

    protected function escape_like_value($value)
    {
        return str_replace(array('!', '%', '_'), array('!!', '!%', '!_'), (string) $value);
    }

    protected function normalize_edit_phrase_rows($rows)
    {
        $normalized = array();

        foreach ($rows as $row) {
            $normalized[] = array(
                'phrase_id' => isset($row['phrase_id']) ? (int) $row['phrase_id'] : null,
                'phrase' => isset($row['phrase']) ? (string) $row['phrase'] : '',
                'selected_value' => isset($row['selected_value']) ? (string) $row['selected_value'] : '',
                'english_value' => isset($row['english_value']) ? (string) $row['english_value'] : '',
            );
        }

        return $normalized;
    }

    public function validate_arabic_pack_file($path)
    {
        $result = array(
            'valid' => false,
            'target_language_code' => $this->arabic_import_language_code,
            'source_file' => basename((string) $path),
            'source_file_allowed' => basename((string) $path) === $this->arabic_import_source_file,
            'file_exists' => is_file($path),
            'valid_json' => false,
            'flat_object' => false,
            'total_keys' => 0,
            'valid_keys' => 0,
            'invalid_keys' => 0,
            'invalid_values' => 0,
            'blank_values' => 0,
            'duplicate_normalized_keys' => 0,
            'source_sha256' => null,
            'stores_raw_phrase_values' => false,
            'errors' => array(),
            'warnings' => array(),
        );

        if (!$result['source_file_allowed']) {
            $result['errors'][] = 'Only arabic.json is accepted for the Arabic UI language pack.';
        }

        if (!$result['file_exists']) {
            $result['errors'][] = 'Arabic language pack file does not exist.';
            return $result;
        }

        $raw = file_get_contents($path);
        $data = json_decode($raw, true);
        $result['valid_json'] = json_last_error() === JSON_ERROR_NONE && is_array($data);
        $result['source_sha256'] = hash_file('sha256', $path);

        if (!$result['valid_json']) {
            $result['errors'][] = 'Arabic language pack is not valid JSON.';
            return $result;
        }

        $result['flat_object'] = !$this->is_list_array($data);
        if (!$result['flat_object']) {
            $result['errors'][] = 'Arabic language pack must be a flat JSON object.';
            return $result;
        }

        $normalized_keys = array();
        foreach ($data as $key => $value) {
            $result['total_keys']++;
            $normalized_key = $this->normalize_phrase_key($key);

            if (!$this->is_safe_phrase_key($normalized_key)) {
                $result['invalid_keys']++;
                continue;
            }

            if (isset($normalized_keys[$normalized_key])) {
                $result['duplicate_normalized_keys']++;
                continue;
            }
            $normalized_keys[$normalized_key] = true;

            if (is_array($value) || is_object($value) || $value === null) {
                $result['invalid_values']++;
                continue;
            }

            $string_value = $this->stringify_phrase_value($value);
            if (trim($string_value) === '') {
                $result['blank_values']++;
            }

            $result['valid_keys']++;
        }

        if ($result['invalid_keys'] > 0) {
            $result['warnings'][] = 'Arabic language pack contains unsafe phrase keys that will be skipped.';
        }
        if ($result['invalid_values'] > 0) {
            $result['warnings'][] = 'Arabic language pack contains nested, null, or otherwise invalid values that will be skipped.';
        }
        if ($result['duplicate_normalized_keys'] > 0) {
            $result['warnings'][] = 'Arabic language pack contains duplicate normalized phrase keys that will be skipped.';
        }
        if ($result['blank_values'] > 0) {
            $result['warnings'][] = 'Arabic language pack contains blank values.';
        }

        $result['valid'] = empty($result['errors']);
        return $result;
    }

    public function preview_arabic_pack_import($mode = 'missing_blank_only')
    {
        return $this->build_arabic_pack_import_preview($mode, false);
    }

    public function apply_arabic_pack_import($mode, $actor_id, $dry_run = true)
    {
        $mode = $this->normalize_arabic_import_mode($mode);
        $preview = $this->build_arabic_pack_import_preview($mode, true);

        if ($dry_run) {
            $preview['dry_run'] = true;
            $preview['applied'] = false;
            $preview['blocked'] = false;
            return $preview;
        }

        if ($mode !== 'missing_blank_only') {
            $preview['dry_run'] = false;
            $preview['applied'] = false;
            $preview['blocked'] = true;
            $preview['blocked_reason'] = 'Only missing_blank_only is enabled for the safe Arabic pack import QA phase.';
            $preview['actor_id_present'] = $actor_id !== null && (int) $actor_id > 0;

            return $preview;
        }

        if (empty($preview['valid'])) {
            $preview['dry_run'] = false;
            $preview['applied'] = false;
            $preview['blocked'] = true;
            $preview['blocked_reason'] = 'Arabic pack import preview is not valid.';
            $preview['actor_id_present'] = $actor_id !== null && (int) $actor_id > 0;

            return $preview;
        }

        $path = APPPATH . 'language' . DIRECTORY_SEPARATOR . $this->arabic_import_source_file;
        $pack = $this->load_valid_arabic_pack_values($path);
        $language_rows = $this->load_language_rows_for_preview();
        $meta_rows = $this->load_phrase_meta_rows_for_preview($this->arabic_import_language_code);
        $now = date('Y-m-d H:i:s');

        $result = $preview;
        $result['dry_run'] = false;
        $result['applied'] = false;
        $result['blocked'] = false;
        $result['actor_id_present'] = $actor_id !== null && (int) $actor_id > 0;
        $result['batch_id'] = null;
        $result['updated_count'] = 0;
        $result['inserted_count'] = 0;
        $result['skipped_count'] = 0;
        $result['manual_preserved_count'] = 0;
        $result['legacy_preserved_count'] = 0;
        $result['metadata_inserted_count'] = 0;
        $result['metadata_updated_count'] = 0;

        $this->db->trans_start();

        $batchData = array(
            'language_code' => $this->arabic_import_language_code,
            'source_file' => $this->arabic_import_source_file,
            'source_sha256' => isset($preview['source_sha256']) ? $preview['source_sha256'] : null,
            'import_mode' => $mode,
            'total_keys' => isset($preview['total_keys']) ? (int) $preview['total_keys'] : 0,
            'inserted_count' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
            'manual_preserved_count' => 0,
            'invalid_count' => isset($preview['invalid_keys']) ? (int) $preview['invalid_keys'] : 0,
            'status' => 'running',
            'summary_json' => null,
            'created_by_user_id' => $actor_id === null ? null : (int) $actor_id,
            'created_at' => $now,
            'updated_at' => $now,
        );
        $this->db->insert($this->batch_table, $batchData);
        $batchId = (int) $this->db->insert_id();

        foreach ($pack as $phrase_key => $value) {
            if (!isset($language_rows[$phrase_key])) {
                $result['skipped_count']++;
                continue;
            }

            $language_row = $language_rows[$phrase_key];
            $meta_row = isset($meta_rows[$phrase_key]) ? $meta_rows[$phrase_key] : null;
            $arabic_value = isset($language_row['arabic']) ? (string) $language_row['arabic'] : '';

            if ($this->is_manual_override_meta($meta_row)) {
                $result['manual_preserved_count']++;
                $result['skipped_count']++;
                continue;
            }

            if (trim($arabic_value) !== '') {
                $result['legacy_preserved_count']++;
                $result['skipped_count']++;
                continue;
            }

            $this->db
                ->where('phrase', $phrase_key)
                ->update($this->language_table, array($this->arabic_import_language_code => $value));

            $result['updated_count']++;
            $metaResult = $this->upsert_imported_phrase_meta($language_row, $phrase_key, $value, $batchId, $now);
            if (!empty($metaResult['inserted'])) {
                $result['metadata_inserted_count']++;
            } elseif (!empty($metaResult['updated'])) {
                $result['metadata_updated_count']++;
            }
        }

        $status = $result['updated_count'] > 0 ? 'applied' : 'applied_noop';
        $summary = array(
            'target_language_code' => $this->arabic_import_language_code,
            'source_file' => $this->arabic_import_source_file,
            'import_mode' => $mode,
            'total_keys' => isset($preview['total_keys']) ? (int) $preview['total_keys'] : 0,
            'matching_existing_phrase_keys' => isset($preview['matching_existing_phrase_keys']) ? (int) $preview['matching_existing_phrase_keys'] : 0,
            'invalid_keys' => isset($preview['invalid_keys']) ? (int) $preview['invalid_keys'] : 0,
            'updated_count' => $result['updated_count'],
            'inserted_count' => $result['inserted_count'],
            'skipped_count' => $result['skipped_count'],
            'manual_preserved_count' => $result['manual_preserved_count'],
            'legacy_preserved_count' => $result['legacy_preserved_count'],
            'metadata_inserted_count' => $result['metadata_inserted_count'],
            'metadata_updated_count' => $result['metadata_updated_count'],
            'stores_raw_phrase_values' => false,
        );

        $this->db
            ->where('id', $batchId)
            ->update($this->batch_table, array(
                'inserted_count' => $result['inserted_count'],
                'updated_count' => $result['updated_count'],
                'skipped_count' => $result['skipped_count'],
                'manual_preserved_count' => $result['manual_preserved_count'],
                'invalid_count' => isset($preview['invalid_keys']) ? (int) $preview['invalid_keys'] : 0,
                'status' => $status,
                'summary_json' => json_encode($summary),
                'updated_at' => $now,
            ));

        $this->db->trans_complete();

        if (!$this->db->trans_status()) {
            $result['applied'] = false;
            $result['blocked'] = true;
            $result['blocked_reason'] = 'Arabic pack import transaction failed.';
            return $result;
        }

        $result['applied'] = true;
        $result['status'] = $status;
        $result['batch_id'] = $batchId;
        $result['summary'] = $summary;

        return $result;
    }

    protected function upsert_imported_phrase_meta($language_row, $phrase_key, $value, $batch_id, $now)
    {
        $hash = $this->hash_phrase_value($value);
        $data = array(
            'phrase_id' => isset($language_row['phrase_id']) ? (int) $language_row['phrase_id'] : null,
            'phrase_key' => $phrase_key,
            'language_code' => $this->arabic_import_language_code,
            'source' => 'imported',
            'last_import_batch_id' => (int) $batch_id,
            'last_imported_value_hash' => $hash,
            'current_value_hash' => $hash,
            'manually_overridden_at' => null,
            'manually_overridden_by_user_id' => null,
            'updated_at' => $now,
        );

        $existing = $this->db
            ->select('id')
            ->from($this->meta_table)
            ->where('phrase_key', $phrase_key)
            ->where('language_code', $this->arabic_import_language_code)
            ->limit(1)
            ->get()
            ->row_array();

        if ($existing) {
            $this->db->where('id', (int) $existing['id']);
            $this->db->update($this->meta_table, $data);
            return array('inserted' => false, 'updated' => true);
        }

        $data['created_at'] = $now;
        $this->db->insert($this->meta_table, $data);
        return array('inserted' => true, 'updated' => false);
    }

    protected function build_arabic_pack_import_preview($mode, $called_from_apply)
    {
        $mode = $this->normalize_arabic_import_mode($mode);
        $path = APPPATH . 'language' . DIRECTORY_SEPARATOR . $this->arabic_import_source_file;
        $validation = $this->validate_arabic_pack_file($path);

        $preview = array(
            'valid' => $validation['valid'],
            'target_language_code' => $this->arabic_import_language_code,
            'source_file' => $validation['source_file'],
            'source_sha256' => $validation['source_sha256'],
            'import_mode' => $mode,
            'default_mode' => 'missing_blank_only',
            'called_from_apply' => (bool) $called_from_apply,
            'full_apply_allowed' => false,
            'stores_raw_phrase_values' => false,
            'total_keys' => $validation['total_keys'],
            'matching_existing_phrase_keys' => 0,
            'missing_phrase_keys' => 0,
            'blank_arabic_values' => 0,
            'non_blank_arabic_values' => 0,
            'manual_override_preserved' => 0,
            'legacy_existing_preserved' => 0,
            'imported_non_manual_updatable' => 0,
            'invalid_keys' => $validation['invalid_keys'] + $validation['invalid_values'] + $validation['duplicate_normalized_keys'],
            'would_insert_meta' => 0,
            'would_update_phrase_values' => 0,
            'would_skip' => 0,
            'would_force_overwrite' => 0,
            'errors' => $validation['errors'],
            'warnings' => $validation['warnings'],
        );

        if (!$validation['valid'] || !$this->metadata_tables_exist()['ready']) {
            if (!$this->metadata_tables_exist()['ready']) {
                $preview['errors'][] = 'Language phrase metadata tables are not ready.';
                $preview['valid'] = false;
            }
            return $preview;
        }

        $pack = $this->load_valid_arabic_pack_values($path);
        $language_rows = $this->load_language_rows_for_preview();
        $meta_rows = $this->load_phrase_meta_rows_for_preview($this->arabic_import_language_code);

        foreach ($pack as $phrase_key => $value) {
            if (!isset($language_rows[$phrase_key])) {
                $preview['missing_phrase_keys']++;
                $preview['would_skip']++;
                continue;
            }

            $preview['matching_existing_phrase_keys']++;

            $language_row = $language_rows[$phrase_key];
            $meta_row = isset($meta_rows[$phrase_key]) ? $meta_rows[$phrase_key] : null;
            $arabic_value = isset($language_row['arabic']) ? (string) $language_row['arabic'] : '';
            $has_arabic_value = trim($arabic_value) !== '';
            $is_manual_override = $this->is_manual_override_meta($meta_row);
            $is_imported_non_manual = $this->is_imported_non_manual_meta($meta_row);
            $has_meta = is_array($meta_row);

            if ($has_arabic_value) {
                $preview['non_blank_arabic_values']++;
            } else {
                $preview['blank_arabic_values']++;
            }

            if ($mode !== 'force_overwrite' && $is_manual_override) {
                $preview['manual_override_preserved']++;
                $preview['would_skip']++;
                continue;
            }

            if ($mode === 'missing_blank_only') {
                if (!$has_arabic_value) {
                    $preview['would_update_phrase_values']++;
                    if (!$has_meta) {
                        $preview['would_insert_meta']++;
                    }
                    continue;
                }

                $preview['legacy_existing_preserved']++;
                $preview['would_skip']++;
                continue;
            }

            if ($mode === 'update_imported_non_manual') {
                if (!$has_arabic_value || $is_imported_non_manual) {
                    if ($is_imported_non_manual) {
                        $preview['imported_non_manual_updatable']++;
                    }
                    $preview['would_update_phrase_values']++;
                    if (!$has_meta) {
                        $preview['would_insert_meta']++;
                    }
                    continue;
                }

                $preview['legacy_existing_preserved']++;
                $preview['would_skip']++;
                continue;
            }

            if ($mode === 'force_overwrite') {
                if ($is_manual_override) {
                    $preview['would_force_overwrite']++;
                }
                if ($is_imported_non_manual) {
                    $preview['imported_non_manual_updatable']++;
                }
                $preview['would_update_phrase_values']++;
                if (!$has_meta) {
                    $preview['would_insert_meta']++;
                }
            }
        }

        return $preview;
    }

    protected function normalize_arabic_import_mode($mode)
    {
        $mode = strtolower(trim((string) $mode));

        return in_array($mode, $this->allowed_arabic_import_modes, true) ? $mode : 'missing_blank_only';
    }

    protected function load_valid_arabic_pack_values($path)
    {
        $data = json_decode(file_get_contents($path), true);
        $values = array();

        if (!is_array($data) || $this->is_list_array($data)) {
            return $values;
        }

        foreach ($data as $key => $value) {
            $normalized_key = $this->normalize_phrase_key($key);
            if (!$this->is_safe_phrase_key($normalized_key) || isset($values[$normalized_key]) || is_array($value) || is_object($value) || $value === null) {
                continue;
            }

            $values[$normalized_key] = $this->stringify_phrase_value($value);
        }

        return $values;
    }

    protected function load_language_rows_for_preview()
    {
        $rows = $this->db
            ->select('phrase_id, phrase, arabic')
            ->from($this->language_table)
            ->get()
            ->result_array();

        $indexed = array();
        foreach ($rows as $row) {
            $indexed[$this->normalize_phrase_key($row['phrase'])] = $row;
        }

        return $indexed;
    }

    protected function load_phrase_meta_rows_for_preview($language_code)
    {
        if (!$this->metadata_tables_exist()['phrase_meta_table_exists']) {
            return array();
        }

        $rows = $this->db
            ->select('phrase_key, language_code, source, last_import_batch_id, last_imported_value_hash, current_value_hash, manually_overridden_at')
            ->from($this->meta_table)
            ->where('language_code', $language_code)
            ->get()
            ->result_array();

        $indexed = array();
        foreach ($rows as $row) {
            $indexed[$this->normalize_phrase_key($row['phrase_key'])] = $row;
        }

        return $indexed;
    }

    protected function is_manual_override_meta($meta_row)
    {
        return is_array($meta_row)
            && ((isset($meta_row['source']) && $meta_row['source'] === 'manual_override')
                || !empty($meta_row['manually_overridden_at']));
    }

    protected function is_imported_non_manual_meta($meta_row)
    {
        if (!is_array($meta_row) || $this->is_manual_override_meta($meta_row)) {
            return false;
        }

        return isset($meta_row['source']) && in_array($meta_row['source'], array('imported', 'force_imported'), true);
    }

    protected function normalize_phrase_key($phrase_key)
    {
        $phrase_key = strtolower(preg_replace('/\s+/', '_', trim((string) $phrase_key)));
        $phrase_key = preg_replace('/_+/', '_', $phrase_key);

        return trim($phrase_key, '_');
    }

    protected function is_safe_phrase_key($phrase_key)
    {
        $phrase_key = (string) $phrase_key;

        return $phrase_key !== ''
            && strlen($phrase_key) <= 255
            && !preg_match('/[\x00-\x1F\x7F<>\\\\]/', $phrase_key)
            && strpos($phrase_key, '..') === false;
    }

    protected function stringify_phrase_value($value)
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return (string) $value;
    }

    protected function is_list_array($array)
    {
        if (!is_array($array)) {
            return false;
        }

        if (function_exists('array_is_list')) {
            return array_is_list($array);
        }

        return array_keys($array) === range(0, count($array) - 1);
    }

    protected function hash_phrase_value($value)
    {
        return hash('sha256', (string) $value);
    }
}
