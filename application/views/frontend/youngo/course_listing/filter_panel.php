<?php
$categories = $this->crud_model->get_categories()->result_array();
if (function_exists('youngo_frontend_translate_category_rows')) {
    $categories = youngo_frontend_translate_category_rows($categories, isset($youngo_frontend_language) ? $youngo_frontend_language : null);
}
$category_option_count = 1;
foreach ($categories as $category_for_count) {
    $category_option_count++;
    $category_option_count += count($this->crud_model->get_sub_categories($category_for_count['id']));
}
$languages = $this->crud_model->get_all_languages();
?>

<aside class="youngo-courses-filter" aria-label="<?php echo youngo_frontend_phrase('course_filters'); ?>">
    <div class="youngo-courses-filter__header">
        <div>
            <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('filters'); ?></p>
            <h2><?php echo youngo_frontend_phrase('find_a_course'); ?></h2>
        </div>
        <a href="<?php echo isset($youngo_courses_index_url) ? $youngo_courses_index_url : site_url('home/courses'); ?>"><?php echo youngo_frontend_phrase('Reset'); ?></a>
    </div>

    <label class="youngo-courses-search">
        <span><?php echo youngo_frontend_phrase('search_by_keyword'); ?></span>
        <span>
            <input type="search" name="title" value="<?php echo youngo_courses_e($search_value); ?>" placeholder="<?php echo youngo_frontend_phrase('search_courses'); ?>">
            <button type="submit" aria-label="<?php echo youngo_frontend_phrase('Search'); ?>"><i class="fa-solid fa-magnifying-glass"></i></button>
        </span>
    </label>

    <details class="youngo-filter-group" open>
        <summary><?php echo youngo_frontend_phrase('Categories'); ?></summary>
        <div class="youngo-filter-options <?php echo $category_option_count > 12 ? 'is-scrollable' : ''; ?>">
            <label class="<?php echo $selected_category === 'all' ? 'is-active' : ''; ?>">
                <input type="radio" name="category" value="all" <?php if ($selected_category === 'all') echo 'checked'; ?> onchange="this.form.submit();">
                <span><?php echo youngo_frontend_phrase('all_categories'); ?></span>
                <small><?php echo $this->crud_model->get_active_course()->num_rows(); ?></small>
            </label>

            <?php foreach ($categories as $category): ?>
                <?php $course_number = $this->crud_model->get_active_course_by_category_id($category['id'], 'category_id')->num_rows(); ?>
                <label class="<?php echo $selected_category === $category['slug'] ? 'is-active' : ''; ?>">
                    <input type="radio" name="category" value="<?php echo youngo_courses_e($category['slug']); ?>" <?php if ($selected_category === $category['slug']) echo 'checked'; ?> onchange="this.form.submit();">
                    <span><?php echo youngo_courses_e($category['name']); ?></span>
                    <small><?php echo $course_number; ?></small>
                </label>

                <?php foreach ($this->crud_model->get_sub_categories($category['id']) as $sub_category): ?>
                    <?php if (function_exists('youngo_frontend_translate_category_row')) $sub_category = youngo_frontend_translate_category_row($sub_category, isset($youngo_frontend_language) ? $youngo_frontend_language : null); ?>
                    <?php $course_number = $this->crud_model->get_active_course_by_category_id($sub_category['id'], 'sub_category_id')->num_rows(); ?>
                    <label class="is-child <?php echo $selected_category === $sub_category['slug'] ? 'is-active' : ''; ?>">
                        <input type="radio" name="category" value="<?php echo youngo_courses_e($sub_category['slug']); ?>" <?php if ($selected_category === $sub_category['slug']) echo 'checked'; ?> onchange="this.form.submit();">
                        <span><?php echo youngo_courses_e($sub_category['name']); ?></span>
                        <small><?php echo $course_number; ?></small>
                    </label>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </details>

    <details class="youngo-filter-group" open>
        <summary><?php echo youngo_frontend_phrase('Price'); ?></summary>
        <div class="youngo-filter-options">
            <?php foreach (array('all' => youngo_frontend_phrase('All'), 'free' => youngo_frontend_phrase('Free'), 'paid' => youngo_frontend_phrase('Paid')) as $value => $label): ?>
                <label class="<?php echo $selected_price === $value ? 'is-active' : ''; ?>">
                    <input type="radio" name="price" value="<?php echo $value; ?>" <?php if ($selected_price === $value) echo 'checked'; ?> onchange="this.form.submit();">
                    <span><?php echo $label; ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </details>

    <details class="youngo-filter-group" open>
        <summary><?php echo youngo_frontend_phrase('Level'); ?></summary>
        <div class="youngo-filter-options">
            <?php foreach (array('all' => youngo_frontend_phrase('All'), 'beginner' => youngo_frontend_phrase('Beginner'), 'intermediate' => youngo_frontend_phrase('Intermediate'), 'advanced' => youngo_frontend_phrase('Advanced')) as $value => $label): ?>
                <label class="<?php echo $selected_level === $value ? 'is-active' : ''; ?>">
                    <input type="radio" name="level" value="<?php echo $value; ?>" <?php if ($selected_level === $value) echo 'checked'; ?> onchange="this.form.submit();">
                    <span><?php echo $label; ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </details>

    <details class="youngo-filter-group">
        <summary><?php echo youngo_frontend_phrase('Language'); ?></summary>
        <div class="youngo-filter-options <?php echo count($languages) > 8 ? 'is-scrollable' : ''; ?>">
            <label class="<?php echo $selected_language === 'all' ? 'is-active' : ''; ?>">
                <input type="radio" name="language" value="all" <?php if ($selected_language === 'all') echo 'checked'; ?> onchange="this.form.submit();">
                <span><?php echo youngo_frontend_phrase('All'); ?></span>
            </label>
            <?php foreach ($languages as $language): ?>
                <?php $language_value = strtolower($language); ?>
                <?php
                    $filter_language = isset($youngo_frontend_language) ? $youngo_frontend_language : (function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english');
                    if ($language_value === 'english') {
                        $language_label = $filter_language === 'arabic' ? 'الإنجليزية' : 'English';
                    } elseif ($language_value === 'arabic') {
                        $language_label = $filter_language === 'arabic' ? 'العربية' : 'Arabic';
                    } elseif ($language_value === 'arabic' . '_translated') {
                        $language_label = $filter_language === 'arabic' ? 'عربي مترجم' : 'Arabic translated';
                    } else {
                        $language_label = ucwords(str_replace('_', ' ', $language));
                    }
                ?>
                <label class="<?php echo $selected_language === $language_value ? 'is-active' : ''; ?>">
                    <input type="radio" name="language" value="<?php echo youngo_courses_e($language_value); ?>" <?php if ($selected_language === $language_value) echo 'checked'; ?> onchange="this.form.submit();">
                    <span><?php echo youngo_courses_e($language_label); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </details>

    <details class="youngo-filter-group">
        <summary><?php echo youngo_frontend_phrase('Ratings'); ?></summary>
        <div class="youngo-filter-options">
            <label class="<?php echo $selected_rating === 'all' ? 'is-active' : ''; ?>">
                <input type="radio" name="rating" value="all" <?php if ($selected_rating === 'all') echo 'checked'; ?> onchange="this.form.submit();">
                <span><?php echo youngo_frontend_phrase('All'); ?></span>
            </label>
            <?php for ($rating_option = 5; $rating_option >= 1; $rating_option--): ?>
                <label class="<?php echo (string) $selected_rating === (string) $rating_option ? 'is-active' : ''; ?>">
                    <input type="radio" name="rating" value="<?php echo $rating_option; ?>" <?php if ((string) $selected_rating === (string) $rating_option) echo 'checked'; ?> onchange="this.form.submit();">
                    <span class="youngo-filter-stars">
                        <?php for ($star = 1; $star <= 5; $star++): ?><i class="fa-solid fa-star <?php echo $star <= $rating_option ? 'is-filled' : ''; ?>"></i><?php endfor; ?>
                    </span>
                </label>
            <?php endfor; ?>
        </div>
    </details>

    <button class="youngo-button youngo-courses-filter__apply" type="submit"><?php echo youngo_frontend_phrase('apply_filters'); ?></button>
</aside>
