<div class="youngo-courses-toolbar">
    <div>
        <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('course_catalog'); ?></p>
        <h2><?php echo youngo_frontend_phrase('showing_results') . ': ' . count($courses) . ' / ' . $total_result; ?></h2>
        <?php if ($search_value !== '' || $selected_category !== 'all' || $selected_price !== 'all' || $selected_level !== 'all' || $selected_language !== 'all' || $selected_rating !== 'all'): ?>
            <a class="youngo-courses-clear" href="<?php echo isset($youngo_courses_index_url) ? $youngo_courses_index_url : site_url('home/courses'); ?>"><?php echo youngo_frontend_phrase('clear_all_filters'); ?></a>
        <?php endif; ?>
    </div>

    <div class="youngo-courses-toolbar__actions">
        <div class="youngo-view-toggle" aria-label="<?php echo youngo_frontend_phrase('course_layout'); ?>">
            <button type="button" class="<?php echo $layout === 'grid' ? 'is-active' : ''; ?>" onclick="if (window.jQuery) { jQuery.post('<?php echo site_url('home/set_layout_to_session'); ?>', {layout: 'grid'}, function(response) { distributeServerResponse(response); }); } return false;" aria-label="<?php echo youngo_frontend_phrase('grid_view'); ?>"><i class="fa-solid fa-table-cells-large"></i></button>
            <button type="button" class="<?php echo $layout === 'list' ? 'is-active' : ''; ?>" onclick="if (window.jQuery) { jQuery.post('<?php echo site_url('home/set_layout_to_session'); ?>', {layout: 'list'}, function(response) { distributeServerResponse(response); }); } return false;" aria-label="<?php echo youngo_frontend_phrase('list_view'); ?>"><i class="fa-solid fa-list"></i></button>
        </div>

        <label class="youngo-sort-select">
            <span><?php echo youngo_frontend_phrase('sort_by'); ?></span>
            <select name="sort_by" onchange="this.form.submit();">
                <option value="newest" <?php if ($selected_sorting === 'newest') echo 'selected'; ?>><?php echo youngo_frontend_phrase('newly_published'); ?></option>
                <option value="highest-rating" <?php if ($selected_sorting === 'highest-rating') echo 'selected'; ?>><?php echo youngo_frontend_phrase('highest_rating'); ?></option>
                <option value="lowest-price" <?php if ($selected_sorting === 'lowest-price') echo 'selected'; ?>><?php echo youngo_frontend_phrase('lowest_price'); ?></option>
                <option value="highest-price" <?php if ($selected_sorting === 'highest-price') echo 'selected'; ?>><?php echo youngo_frontend_phrase('highest_price'); ?></option>
                <option value="discounted" <?php if ($selected_sorting === 'discounted') echo 'selected'; ?>><?php echo youngo_frontend_phrase('discounted'); ?></option>
            </select>
        </label>
    </div>
</div>
