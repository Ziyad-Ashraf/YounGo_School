<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('youngo_frontend_content_language')) {
    function youngo_frontend_content_language($uri_string = null)
    {
        youngo_frontend_content_load_language_helper();

        if (function_exists('youngo_frontend_active_language')) {
            return youngo_frontend_active_language($uri_string);
        }

        return 'english';
    }
}

if (!function_exists('youngo_frontend_translate_course_row')) {
    function youngo_frontend_translate_course_row($course, $language_code = null)
    {
        if (!is_array($course) || empty($course['id'])) {
            return $course;
        }

        $language_code = youngo_frontend_content_normalize_language($language_code);
        $translation_model = youngo_frontend_content_translation_model();
        if ($translation_model === null || !method_exists($translation_model, 'get_course_translation_with_fallback')) {
            return $course;
        }

        $translation = $translation_model->get_course_translation_with_fallback((int) $course['id'], $language_code);
        if (!is_array($translation)) {
            return $course;
        }

        $translated = youngo_frontend_preserve_original_fields($course, array(
            'title',
            'short_description',
            'description',
            'outcomes',
            'requirements',
            'faqs',
            'seo_title',
            'meta_keywords',
            'meta_description',
        ));

        $translated = youngo_frontend_apply_translation_fields($translated, $translation, array(
            'title',
            'short_description',
            'description',
            'outcomes',
            'requirements',
            'faqs',
            'seo_title',
            'meta_keywords',
            'meta_description',
        ));

        if (array_key_exists('slug', $translation) && trim((string) $translation['slug']) !== '') {
            $translated['youngo_translation_slug'] = trim((string) $translation['slug']);
        }

        return youngo_frontend_apply_translation_meta($translated, $translation, 'course');
    }
}

if (!function_exists('youngo_frontend_translate_course_rows')) {
    function youngo_frontend_translate_course_rows($courses, $language_code = null)
    {
        if (!is_array($courses)) {
            return $courses;
        }

        foreach ($courses as $key => $course) {
            $courses[$key] = youngo_frontend_translate_course_row($course, $language_code);
        }

        return $courses;
    }
}

if (!function_exists('youngo_frontend_translate_category_row')) {
    function youngo_frontend_translate_category_row($category, $language_code = null)
    {
        if (!is_array($category) || empty($category['id'])) {
            return $category;
        }

        $language_code = youngo_frontend_content_normalize_language($language_code);
        $translation_model = youngo_frontend_content_translation_model();
        if ($translation_model === null || !method_exists($translation_model, 'get_category_translation_with_fallback')) {
            return $category;
        }

        $translation = $translation_model->get_category_translation_with_fallback((int) $category['id'], $language_code);
        if (!is_array($translation)) {
            return $category;
        }

        $translated = youngo_frontend_preserve_original_fields($category, array('name', 'description'));
        $translated = youngo_frontend_apply_translation_fields($translated, $translation, array('name', 'description'));

        if (array_key_exists('slug', $translation) && trim((string) $translation['slug']) !== '') {
            $translated['youngo_translation_slug'] = trim((string) $translation['slug']);
        }

        return youngo_frontend_apply_translation_meta($translated, $translation, 'category');
    }
}

if (!function_exists('youngo_frontend_translate_category_rows')) {
    function youngo_frontend_translate_category_rows($categories, $language_code = null)
    {
        if (!is_array($categories)) {
            return $categories;
        }

        foreach ($categories as $key => $category) {
            $categories[$key] = youngo_frontend_translate_category_row($category, $language_code);
        }

        return $categories;
    }
}

if (!function_exists('youngo_frontend_translate_section_row')) {
    function youngo_frontend_translate_section_row($section, $language_code = null)
    {
        if (!is_array($section) || empty($section['id'])) {
            return $section;
        }

        $language_code = youngo_frontend_content_normalize_language($language_code);
        $translation_model = youngo_frontend_content_translation_model();
        if ($translation_model === null || !method_exists($translation_model, 'get_section_translation_with_fallback')) {
            return $section;
        }

        $translation = $translation_model->get_section_translation_with_fallback((int) $section['id'], $language_code);
        if (!is_array($translation)) {
            return $section;
        }

        $translated = youngo_frontend_preserve_original_fields($section, array('title'));
        $translated = youngo_frontend_apply_translation_fields($translated, $translation, array('title'));

        return youngo_frontend_apply_translation_meta($translated, $translation, 'section');
    }
}

if (!function_exists('youngo_frontend_translate_section_rows')) {
    function youngo_frontend_translate_section_rows($sections, $language_code = null)
    {
        if (!is_array($sections)) {
            return $sections;
        }

        foreach ($sections as $key => $section) {
            $sections[$key] = youngo_frontend_translate_section_row($section, $language_code);
        }

        return $sections;
    }
}

if (!function_exists('youngo_frontend_translate_lesson_row')) {
    function youngo_frontend_translate_lesson_row($lesson, $language_code = null)
    {
        if (!is_array($lesson) || empty($lesson['id'])) {
            return $lesson;
        }

        $language_code = youngo_frontend_content_normalize_language($language_code);
        $translation_model = youngo_frontend_content_translation_model();
        if ($translation_model === null || !method_exists($translation_model, 'get_lesson_translation_with_fallback')) {
            return $lesson;
        }

        $translation = $translation_model->get_lesson_translation_with_fallback((int) $lesson['id'], $language_code);
        if (!is_array($translation)) {
            return $lesson;
        }

        $translated = youngo_frontend_preserve_original_fields($lesson, array('title', 'summary', 'attachment'));
        $translated = youngo_frontend_apply_translation_fields($translated, $translation, array('title', 'summary'));

        $is_text_lesson = isset($lesson['lesson_type'], $lesson['attachment_type'])
            && $lesson['lesson_type'] === 'text'
            && $lesson['attachment_type'] === 'description';

        if ($is_text_lesson && array_key_exists('text_content', $translation) && $translation['text_content'] !== null) {
            $translated['attachment'] = $translation['text_content'];
            $translated['text_content'] = $translation['text_content'];
        }

        return youngo_frontend_apply_translation_meta($translated, $translation, 'lesson');
    }
}

if (!function_exists('youngo_frontend_translate_lesson_rows')) {
    function youngo_frontend_translate_lesson_rows($lessons, $language_code = null)
    {
        if (!is_array($lessons)) {
            return $lessons;
        }

        foreach ($lessons as $key => $lesson) {
            $lessons[$key] = youngo_frontend_translate_lesson_row($lesson, $language_code);
        }

        return $lessons;
    }
}

if (!function_exists('youngo_frontend_translate_learner_course_item')) {
    function youngo_frontend_translate_learner_course_item($item, $language_code = null)
    {
        if (!is_array($item)) {
            return $item;
        }

        $course = isset($item['course']) && is_array($item['course']) ? $item['course'] : array();
        if (empty($course) && !empty($item['course_id'])) {
            $CI = &get_instance();
            if (isset($CI->crud_model)) {
                $course = $CI->crud_model->get_course_by_id((int) $item['course_id'])->row_array();
            }
        }

        if (empty($course)) {
            return $item;
        }

        $language_code = youngo_frontend_content_normalize_language($language_code);
        $translated_course = youngo_frontend_translate_course_row($course, $language_code);
        $item['course'] = $translated_course;

        if (isset($translated_course['title'])) {
            $item['title'] = $translated_course['title'];
        }

        if (isset($translated_course['id'])) {
            $item['course_url'] = youngo_frontend_course_detail_url($translated_course, $language_code);
        }

        $category_id = 0;
        if (!empty($translated_course['sub_category_id'])) {
            $category_id = (int) $translated_course['sub_category_id'];
        } elseif (!empty($translated_course['category_id'])) {
            $category_id = (int) $translated_course['category_id'];
        }

        if ($category_id > 0) {
            $CI = &get_instance();
            if (isset($CI->crud_model)) {
                $category = $CI->crud_model->get_category_details_by_id($category_id)->row_array();
                $category = youngo_frontend_translate_category_row($category, $language_code);
                if (!empty($category['name'])) {
                    $item['category_name'] = $category['name'];
                }
            }
        }

        return $item;
    }
}

if (!function_exists('youngo_frontend_translate_learner_course_items')) {
    function youngo_frontend_translate_learner_course_items($items, $language_code = null)
    {
        if (!is_array($items)) {
            return $items;
        }

        foreach ($items as $key => $item) {
            $items[$key] = youngo_frontend_translate_learner_course_item($item, $language_code);
        }

        return $items;
    }
}

if (!function_exists('youngo_frontend_course_detail_path')) {
    function youngo_frontend_course_detail_path($course, $language_code = null)
    {
        $language_code = youngo_frontend_content_normalize_language($language_code);
        $course_id = is_array($course) && !empty($course['id']) ? (int) $course['id'] : 0;
        $title = is_array($course) && isset($course['title']) ? $course['title'] : 'course';
        $slug = rawurlencode(slugify($title));

        if ($language_code === 'english') {
            return 'en/home/course/' . $slug . '/' . $course_id;
        }

        return 'home/course/' . $slug . '/' . $course_id;
    }
}

if (!function_exists('youngo_frontend_course_detail_url')) {
    function youngo_frontend_course_detail_url($course, $language_code = null)
    {
        return youngo_frontend_site_url(youngo_frontend_course_detail_path($course, $language_code));
    }
}

if (!function_exists('youngo_frontend_courses_path')) {
    function youngo_frontend_courses_path($language_code = null, $query_string = '')
    {
        $language_code = youngo_frontend_content_normalize_language($language_code);
        $path = $language_code === 'english' ? 'en/home/courses' : 'home/courses';
        $query_string = ltrim((string) $query_string, '?');

        return $query_string === '' ? $path : $path . '?' . $query_string;
    }
}

if (!function_exists('youngo_frontend_courses_url')) {
    function youngo_frontend_courses_url($language_code = null, $query_string = '')
    {
        return youngo_frontend_site_url(youngo_frontend_courses_path($language_code, $query_string));
    }
}

if (!function_exists('youngo_frontend_search_path')) {
    function youngo_frontend_search_path($language_code = null, $query_string = '')
    {
        $language_code = youngo_frontend_content_normalize_language($language_code);
        $path = $language_code === 'english' ? 'en/home/search' : 'home/search';
        $query_string = ltrim((string) $query_string, '?');

        return $query_string === '' ? $path : $path . '?' . $query_string;
    }
}

if (!function_exists('youngo_frontend_search_url')) {
    function youngo_frontend_search_url($language_code = null, $query_string = '')
    {
        return youngo_frontend_site_url(youngo_frontend_search_path($language_code, $query_string));
    }
}

if (!function_exists('youngo_frontend_site_url')) {
    function youngo_frontend_site_url($path)
    {
        return function_exists('site_url') ? site_url($path) : '/' . ltrim((string) $path, '/');
    }
}

if (!function_exists('youngo_frontend_content_load_language_helper')) {
    function youngo_frontend_content_load_language_helper()
    {
        if (function_exists('youngo_frontend_active_language')) {
            return;
        }

        if (function_exists('get_instance') && file_exists(APPPATH . 'helpers/youngo_frontend_language_helper.php')) {
            $CI = &get_instance();
            if (isset($CI->load)) {
                $CI->load->helper('youngo_frontend_language');
            }
        }
    }
}

if (!function_exists('youngo_frontend_content_normalize_language')) {
    function youngo_frontend_content_normalize_language($language_code = null)
    {
        youngo_frontend_content_load_language_helper();

        if ($language_code === null) {
            return function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english';
        }

        if (function_exists('youngo_frontend_normalize_language_code')) {
            return youngo_frontend_normalize_language_code($language_code);
        }

        return strtolower(trim((string) $language_code)) === 'arabic' ? 'arabic' : 'english';
    }
}

if (!function_exists('youngo_frontend_content_translation_model')) {
    function youngo_frontend_content_translation_model()
    {
        if (!function_exists('get_instance') || !file_exists(APPPATH . 'models/Youngo_translation_model.php')) {
            return null;
        }

        $CI = &get_instance();
        if (!isset($CI->youngo_translation_model)) {
            $CI->load->model('Youngo_translation_model', 'youngo_translation_model');
        }

        return isset($CI->youngo_translation_model) ? $CI->youngo_translation_model : null;
    }
}

if (!function_exists('youngo_frontend_preserve_original_fields')) {
    function youngo_frontend_preserve_original_fields(array $row, array $fields)
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $row) && !array_key_exists('youngo_canonical_' . $field, $row)) {
                $row['youngo_canonical_' . $field] = $row[$field];
            }
        }

        return $row;
    }
}

if (!function_exists('youngo_frontend_apply_translation_fields')) {
    function youngo_frontend_apply_translation_fields(array $row, array $translation, array $fields)
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $translation) || $translation[$field] === null) {
                continue;
            }

            $row[$field] = $translation[$field];
        }

        return $row;
    }
}

if (!function_exists('youngo_frontend_apply_translation_meta')) {
    function youngo_frontend_apply_translation_meta(array $row, array $translation, $entity_type)
    {
        $row['youngo_translation_entity_type'] = $entity_type;
        $row['youngo_translation_requested_language'] = isset($translation['requested_language']) ? $translation['requested_language'] : 'english';
        $row['youngo_translation_resolved_language'] = isset($translation['resolved_language']) ? $translation['resolved_language'] : 'english';
        $row['youngo_translation_is_fallback'] = !empty($translation['is_fallback']);
        $row['youngo_translation_missing'] = !empty($translation['missing_translation']);
        $row['youngo_translation_source'] = isset($translation['translation_source']) ? $translation['translation_source'] : 'unknown';

        return $row;
    }
}
