<?php
    $youngo_homepage = youngo_get_homepage_content(true);
    $youngo_sections = $youngo_homepage['sections'];
    $youngo_source_types = youngo_homepage_allowed_source_types();
    $youngo_additional_types = youngo_homepage_allowed_additional_section_types();

    $youngo_section_labels = array(
        'hero' => 'Hero Section',
        'featured_categories' => 'Featured Categories',
        'featured_courses' => 'Featured Courses',
        'why_choose' => 'Why Choose YounGo',
        'about_teaser' => 'About Teaser',
        'testimonials' => 'Testimonials',
        'faq_preview' => 'FAQ Preview',
        'blog_preview' => 'Blog Preview',
        'final_cta' => 'Final CTA',
    );

    $youngo_section_descriptions = array(
        'hero' => 'Main introductory section with hero image and primary CTA.',
        'featured_categories' => 'Grid of top subject areas for families to browse.',
        'featured_courses' => 'Course highlights sourced from the existing LMS.',
        'why_choose' => 'Key benefits and parent-trust value points.',
        'about_teaser' => 'Brief overview of YounGo mission and value.',
        'testimonials' => 'Social proof from families and young learners.',
        'faq_preview' => 'Common parent questions shown on the homepage.',
        'blog_preview' => 'Latest articles and updates for families.',
        'final_cta' => 'Bottom call to action that closes the page.',
    );

    $youngo_section_icons = array(
        'hero' => 'mdi-view-carousel',
        'featured_categories' => 'mdi-shape-outline',
        'featured_courses' => 'mdi-book-open-page-variant',
        'why_choose' => 'mdi-shield-check',
        'about_teaser' => 'mdi-information-outline',
        'testimonials' => 'mdi-comment-quote-outline',
        'faq_preview' => 'mdi-help-circle-outline',
        'blog_preview' => 'mdi-newspaper-variant-outline',
        'final_cta' => 'mdi-cursor-default-click-outline',
    );

    $youngo_additional_type_labels = array(
        'text_image' => 'Text and Image',
        'feature_cards' => 'Feature Cards',
        'cta_band' => 'CTA Band',
        'testimonial_block' => 'Testimonial Block',
        'faq_block' => 'FAQ Block',
        'course_highlight' => 'Course Highlight',
        'category_highlight' => 'Category Highlight',
    );

    $youngo_additional_type_descriptions = array(
        'text_image' => 'A controlled text block with image and CTA.',
        'feature_cards' => 'A small group of feature or benefit cards.',
        'cta_band' => 'A focused call-to-action section.',
        'testimonial_block' => 'A controlled testimonial group.',
        'faq_block' => 'A controlled FAQ group.',
        'course_highlight' => 'Highlights selected LMS courses.',
        'category_highlight' => 'Highlights selected LMS categories.',
    );

    $youngo_cta_suggestions = array(
        array('label' => 'Courses', 'url' => '/home/courses'),
        array('label' => 'Sign up', 'url' => '/sign_up'),
        array('label' => 'Contact', 'url' => '/home/contact_us'),
        array('label' => 'FAQ', 'url' => '/home/faq'),
        array('label' => 'Blog', 'url' => '/blog'),
    );

    $youngo_source_type_descriptions = array(
        'manual' => 'Use only content entered in this editor.',
        'existing_cms' => 'Use existing LMS/CMS records where supported.',
        'mixed' => 'Blend selected CMS records with editable section content.',
        'auto' => 'Let YounGo choose safe defaults from available content.',
    );

    if (!function_exists('youngo_admin_collect_assets')) {
        function youngo_admin_collect_assets()
        {
            $folders = array(
                'assets/frontend/youngo/images/',
                'assets/frontend/youngo/images/home/',
                'assets/frontend/youngo/images/categories/',
                'assets/frontend/youngo/images/courses/',
                'assets/frontend/youngo/images/about/',
                'uploads/thumbnails/category_thumbnails/',
                'uploads/thumbnails/course_thumbnails/',
                'uploads/blog/banner/',
                'uploads/blog/thumbnail/',
            );

            $assets = array();
            foreach ($folders as $folder) {
                if (!is_dir($folder)) {
                    continue;
                }

                foreach (glob($folder . '*.{png,jpg,jpeg,webp,svg}', GLOB_BRACE) as $path) {
                    if (!is_file($path)) {
                        continue;
                    }

                    $normalized = str_replace('\\', '/', $path);
                    $assets[] = array(
                        'path' => $normalized,
                        'url' => base_url($normalized),
                        'name' => basename($normalized),
                        'group' => trim(str_replace(array('assets/frontend/youngo/images/', 'uploads/'), '', dirname($normalized)), '/'),
                    );
                }
            }

            return $assets;
        }
    }

    $youngo_asset_picker_items = youngo_admin_collect_assets();

    $youngo_category_picker_items = array();
    $youngo_category_counts = array();
    $this->db->select('category_id, COUNT(*) AS total', false);
    $this->db->where('status', 'active');
    $this->db->where('category_id >', 0);
    $this->db->group_by('category_id');
    foreach ($this->db->get('course')->result_array() as $count_row) {
        $youngo_category_counts[(int) $count_row['category_id']] = (int) $count_row['total'];
    }
    $this->db->select('sub_category_id, COUNT(*) AS total', false);
    $this->db->where('status', 'active');
    $this->db->where('sub_category_id >', 0);
    $this->db->group_by('sub_category_id');
    foreach ($this->db->get('course')->result_array() as $count_row) {
        $id = (int) $count_row['sub_category_id'];
        $youngo_category_counts[$id] = isset($youngo_category_counts[$id]) ? $youngo_category_counts[$id] + (int) $count_row['total'] : (int) $count_row['total'];
    }
    $this->db->order_by('parent', 'asc');
    $this->db->order_by('name', 'asc');
    foreach ($this->db->get('category')->result_array() as $category_row) {
        $image_path = youngo_homepage_category_image_path($category_row);
        $youngo_category_picker_items[] = array(
            'id' => (int) $category_row['id'],
            'name' => $category_row['name'],
            'parent' => (int) $category_row['parent'],
            'count' => isset($youngo_category_counts[(int) $category_row['id']]) ? $youngo_category_counts[(int) $category_row['id']] : 0,
            'image' => base_url($image_path),
        );
    }

    $youngo_course_picker_items = array();
    $youngo_course_category_ids = array();
    $this->db->where_in('status', array('active', 'private'));
    $this->db->order_by('status', 'asc');
    $this->db->order_by('id', 'desc');
    $course_picker_rows = $this->db->get('course', 150)->result_array();
    foreach ($course_picker_rows as $course_row) {
        if (!empty($course_row['category_id'])) {
            $youngo_course_category_ids[] = (int) $course_row['category_id'];
        }
        if (!empty($course_row['sub_category_id'])) {
            $youngo_course_category_ids[] = (int) $course_row['sub_category_id'];
        }
    }
    $youngo_course_category_names = array();
    $youngo_course_category_ids = array_values(array_unique($youngo_course_category_ids));
    if (!empty($youngo_course_category_ids)) {
        $this->db->where_in('id', $youngo_course_category_ids);
        foreach ($this->db->get('category')->result_array() as $category_row) {
            $youngo_course_category_names[(int) $category_row['id']] = $category_row['name'];
        }
    }
    foreach ($course_picker_rows as $course_row) {
        $category_id = !empty($course_row['sub_category_id']) ? (int) $course_row['sub_category_id'] : (int) $course_row['category_id'];
        $youngo_course_picker_items[] = array(
            'id' => (int) $course_row['id'],
            'title' => $course_row['title'],
            'status' => $course_row['status'],
            'category' => isset($youngo_course_category_names[$category_id]) ? $youngo_course_category_names[$category_id] : '',
            'price' => youngo_homepage_price_label($course_row),
            'image' => $this->crud_model->get_course_thumbnail_url((int) $course_row['id']),
        );
    }

    if (!function_exists('youngo_admin_item_has_content')) {
        function youngo_admin_item_has_content($item)
        {
            if (is_array($item)) {
                foreach ($item as $value) {
                    if (youngo_admin_item_has_content($value)) {
                        return true;
                    }
                }
                return false;
            }

            return trim((string) $item) !== '';
        }
    }

    if (!function_exists('youngo_admin_disabled_attr')) {
        function youngo_admin_disabled_attr($disabled)
        {
            return $disabled ? ' disabled' : '';
        }
    }

    if (!function_exists('youngo_admin_text_input')) {
        function youngo_admin_text_input($name, $label, $value, $required = false, $disabled = false)
        {
            $is_id_picker = strpos($name, '[category_ids]') !== false || strpos($name, '[course_ids]') !== false;
            if ($is_id_picker) {
                $picker_type = strpos($name, '[course_ids]') !== false ? 'courses' : 'categories';
                $picker_label = $picker_type === 'courses' ? get_phrase('choose_courses') : get_phrase('choose_categories');
                ?>
                <div class="form-group youngo-field youngo-picker-field" data-id-picker="<?php echo $picker_type; ?>">
                    <div class="youngo-field-head">
                        <div>
                            <label class="youngo-field-label"><?php echo get_phrase($label); ?><?php if ($required): ?><span class="required">*</span><?php endif; ?></label>
                            <small class="youngo-help-text"><?php echo get_phrase($picker_type === 'courses' ? 'Search existing LMS courses, then save the selected IDs into the current homepage field.' : 'Search existing LMS categories, then save the selected IDs into the current homepage field.'); ?></small>
                        </div>
                        <button type="button" class="youngo-secondary-btn youngo-picker-open" data-picker-open>
                            <i class="mdi mdi-magnify"></i>
                            <?php echo get_phrase($picker_label); ?>
                        </button>
                    </div>
                    <div class="youngo-selected-chips" data-selected-chips></div>
                    <div class="youngo-picker-drawer" data-picker-drawer>
                        <input type="search" class="form-control youngo-picker-search" placeholder="<?php echo get_phrase('Search by title, name, or ID'); ?>" data-picker-search>
                        <div class="youngo-picker-results" data-picker-results></div>
                    </div>
                    <details class="youngo-advanced-field">
                        <summary><?php echo get_phrase('Advanced raw ID field'); ?></summary>
                        <input type="text" class="form-control youngo-picker-source" name="<?php echo $name; ?>" value="<?php echo youngo_homepage_e($value); ?>" <?php echo $required ? 'required' : ''; ?><?php echo youngo_admin_disabled_attr($disabled); ?>>
                        <small class="youngo-help-text"><?php echo get_phrase('Comma-separated IDs are preserved for compatibility with the current save logic.'); ?></small>
                    </details>
                </div>
                <?php
                return;
            }
            ?>
            <div class="form-group youngo-field">
                <label class="youngo-field-label"><?php echo get_phrase($label); ?><?php if ($required): ?><span class="required">*</span><?php endif; ?></label>
                <input type="text" class="form-control youngo-cms-control" name="<?php echo $name; ?>" value="<?php echo youngo_homepage_e($value); ?>" <?php echo $required ? 'required' : ''; ?><?php echo youngo_admin_disabled_attr($disabled); ?>>
            </div>
            <?php
        }
    }

    if (!function_exists('youngo_admin_textarea')) {
        function youngo_admin_textarea($name, $label, $value, $disabled = false)
        {
            ?>
            <div class="form-group youngo-field">
                <label class="youngo-field-label"><?php echo get_phrase($label); ?></label>
                <textarea class="form-control youngo-cms-control" rows="3" name="<?php echo $name; ?>"<?php echo youngo_admin_disabled_attr($disabled); ?>><?php echo youngo_homepage_e($value); ?></textarea>
                <small class="youngo-help-text"><?php echo get_phrase('Plain text only. HTML and styling controls are not supported.'); ?></small>
            </div>
            <?php
        }
    }

    if (!function_exists('youngo_admin_cta_fields')) {
        function youngo_admin_cta_fields($prefix, $cta, $title, $disabled = false)
        {
            $cta = is_array($cta) ? $cta : array('label' => '', 'url' => '');
            ?>
            <div class="youngo-cms-card youngo-cta-builder">
                <div class="youngo-card-head">
                    <div>
                        <h4><?php echo get_phrase($title); ?></h4>
                        <p><?php echo get_phrase('CTA label and destination are managed together. Styling stays in the YounGo theme.'); ?></p>
                    </div>
                    <i class="mdi mdi-cursor-default-click-outline"></i>
                </div>
                <div class="row">
                    <div class="col-md-5">
                        <?php youngo_admin_text_input($prefix . '[label]', 'Button label', isset($cta['label']) ? $cta['label'] : '', false, $disabled); ?>
                    </div>
                    <div class="col-md-7">
                        <?php youngo_admin_text_input($prefix . '[url]', 'Button URL', isset($cta['url']) ? $cta['url'] : '', false, $disabled); ?>
                    </div>
                </div>
                <div class="youngo-route-suggestions" data-route-suggestions>
                    <?php global $youngo_cta_suggestions; ?>
                    <?php foreach ($youngo_cta_suggestions as $suggestion): ?>
                        <button type="button" class="youngo-chip" data-route-value="<?php echo youngo_homepage_e($suggestion['url']); ?>">
                            <?php echo get_phrase($suggestion['label']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
        }
    }

    if (!function_exists('youngo_admin_image_fields')) {
        function youngo_admin_image_fields($prefix, $image, $title, $disabled = false)
        {
            $image = is_array($image) ? $image : array('url' => '', 'alt' => '');
            $image_url = isset($image['url']) ? $image['url'] : '';
            $preview = $image_url !== '' ? base_url($image_url) : '';
            ?>
            <div class="youngo-cms-card youngo-media-picker" data-media-picker>
                <div class="youngo-card-head">
                    <div>
                        <h4><?php echo get_phrase($title); ?></h4>
                        <p><?php echo get_phrase('Choose an approved local image asset. Raw paths remain available as an advanced fallback.'); ?></p>
                    </div>
                    <i class="mdi mdi-image-outline"></i>
                </div>
                <div class="youngo-media-layout">
                    <div class="youngo-media-preview" data-media-preview style="<?php echo $preview !== '' ? 'background-image:url(' . $preview . ');' : ''; ?>">
                        <span><?php echo get_phrase('No image selected'); ?></span>
                    </div>
                    <div class="youngo-media-controls">
                        <div class="youngo-media-actions">
                            <button type="button" class="youngo-secondary-btn" data-media-open><?php echo get_phrase('Select / Change'); ?></button>
                            <button type="button" class="youngo-text-btn" data-media-clear><?php echo get_phrase('Clear'); ?></button>
                        </div>
                        <?php youngo_admin_text_input($prefix . '[alt]', 'Image alt text', isset($image['alt']) ? $image['alt'] : '', false, $disabled); ?>
                    </div>
                </div>
                <div class="youngo-asset-drawer" data-media-drawer>
                    <input type="search" class="form-control youngo-picker-search" placeholder="<?php echo get_phrase('Search local assets'); ?>" data-asset-search>
                    <div class="youngo-asset-grid" data-asset-grid></div>
                </div>
                <details class="youngo-advanced-field">
                    <summary><?php echo get_phrase('Advanced raw image path'); ?></summary>
                    <?php youngo_admin_text_input($prefix . '[url]', 'Image path', $image_url, false, $disabled); ?>
                </details>
            </div>
            <?php
        }
    }

    if (!function_exists('youngo_admin_show_toggle')) {
        function youngo_admin_show_toggle($visible_name, $published_name, $is_visible, $is_published, $label = 'Show on homepage')
        {
            $checked = !empty($is_visible) && !empty($is_published);
            ?>
            <input type="hidden" name="<?php echo $visible_name; ?>" value="0">
            <input type="hidden" name="<?php echo $published_name; ?>" value="0">
            <label class="youngo-toggle">
                <input type="checkbox" class="youngo-show-toggle" name="<?php echo $visible_name; ?>" value="1" <?php echo $checked ? 'checked' : ''; ?>>
                <input type="checkbox" class="youngo-sync-published" name="<?php echo $published_name; ?>" value="1" <?php echo $checked ? 'checked' : ''; ?> tabindex="-1" aria-hidden="true">
                <span class="youngo-toggle-track"></span>
                <span class="youngo-toggle-label"><?php echo get_phrase($label); ?></span>
            </label>
            <?php
        }
    }

    if (!function_exists('youngo_admin_source_select')) {
        function youngo_admin_source_select($name, $current, $source_types, $disabled = false)
        {
            ?>
            <div class="form-group youngo-field youngo-source-field" data-source-field>
                <label class="youngo-field-label"><?php echo get_phrase('Source type'); ?></label>
                <div class="youngo-source-tiles">
                    <?php global $youngo_source_type_descriptions; ?>
                    <?php foreach ($source_types as $source_type): ?>
                        <button type="button" class="youngo-source-tile <?php echo $current === $source_type ? 'is-selected' : ''; ?>" data-source-value="<?php echo $source_type; ?>">
                            <strong><?php echo get_phrase(ucwords(str_replace('_', ' ', $source_type))); ?></strong>
                            <span><?php echo get_phrase(isset($youngo_source_type_descriptions[$source_type]) ? $youngo_source_type_descriptions[$source_type] : ''); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <details class="youngo-advanced-field">
                    <summary><?php echo get_phrase('Advanced source value'); ?></summary>
                    <select class="form-control youngo-source-select" name="<?php echo $name; ?>"<?php echo youngo_admin_disabled_attr($disabled); ?>>
                        <?php foreach ($source_types as $source_type): ?>
                            <option value="<?php echo $source_type; ?>" <?php echo $current === $source_type ? 'selected' : ''; ?>>
                                <?php echo get_phrase($source_type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </details>
            </div>
            <?php
        }
    }

    if (!function_exists('youngo_admin_repeat_item_state')) {
        function youngo_admin_repeat_item_state($item, $index, $default_visible_count = 0)
        {
            return $index < $default_visible_count || youngo_admin_item_has_content($item);
        }
    }
?>

<style>
    .youngo-admin-manager {
        background: #faf8ff;
        border-radius: 18px;
        color: #191b22;
        font-family: 'Plus Jakarta Sans', Arial, sans-serif;
        margin-bottom: 32px;
        padding: 24px;
    }

    .youngo-admin-manager * {
        letter-spacing: 0;
    }

    .youngo-admin-manager .form-control {
        border: 1px solid #cdc3d3;
        border-radius: 12px;
        box-shadow: none;
        color: #191b22;
        min-height: 42px;
    }

    .youngo-admin-manager textarea.form-control {
        min-height: 96px;
    }

    .youngo-admin-manager .form-control:focus {
        border-color: #7e42ab;
        box-shadow: 0 0 0 3px rgba(126, 66, 171, 0.14);
    }

    .youngo-admin-manager .row {
        row-gap: 12px;
    }

    .youngo-shell {
        margin: 0 auto;
        max-width: 1180px;
    }

    .youngo-page-head,
    .youngo-manager-list,
    .youngo-notice,
    .youngo-editor-panel,
    .youngo-additional-panel {
        background: #ffffff;
        border: 1px solid #e2d9ea;
        border-radius: 20px;
        box-shadow: 0 12px 30px rgba(90, 45, 145, 0.08);
    }

    .youngo-page-head {
        align-items: center;
        display: flex;
        gap: 24px;
        justify-content: space-between;
        margin-bottom: 18px;
        padding: 28px;
    }

    .youngo-kicker {
        align-items: center;
        color: #5a2d91;
        display: inline-flex;
        font-size: 12px;
        font-weight: 800;
        gap: 8px;
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .youngo-page-title {
        color: #191b22;
        font-size: 30px;
        font-weight: 800;
        line-height: 1.2;
        margin: 0 0 8px;
    }

    .youngo-page-copy,
    .youngo-row-copy,
    .youngo-help-text,
    .youngo-muted {
        color: #4b4451;
    }

    .youngo-page-copy {
        font-size: 15px;
        line-height: 1.55;
        margin: 0;
        max-width: 760px;
    }

    .youngo-head-actions {
        align-items: flex-end;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .youngo-save-btn,
    .youngo-secondary-btn,
    .youngo-text-btn,
    .youngo-danger-btn {
        align-items: center;
        border-radius: 12px;
        display: inline-flex;
        font-weight: 800;
        gap: 8px;
        justify-content: center;
        min-height: 42px;
        padding: 0 16px;
    }

    .youngo-save-btn {
        background: #420f79;
        border: 0;
        box-shadow: 0 12px 24px rgba(66, 15, 121, 0.18);
        color: #ffffff;
    }

    .youngo-save-btn:hover,
    .youngo-save-btn:focus {
        background: #5a2d91;
        color: #ffffff;
    }

    .youngo-secondary-btn {
        background: #ffffff;
        border: 1px solid #cdc3d3;
        color: #420f79;
    }

    .youngo-secondary-btn:hover,
    .youngo-secondary-btn:focus {
        background: #f3f3fc;
        color: #420f79;
    }

    .youngo-text-btn {
        background: transparent;
        border: 0;
        color: #420f79;
        padding: 0 8px;
    }

    .youngo-danger-btn {
        background: #fff4e5;
        border: 1px solid #f2bd76;
        color: #633f00;
    }

    .youngo-chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }

    .youngo-chip {
        align-items: center;
        background: #f3f3fc;
        border: 1px solid #cdc3d3;
        border-radius: 999px;
        color: #4b4451;
        display: inline-flex;
        font-size: 12px;
        font-weight: 800;
        gap: 6px;
        line-height: 1;
        padding: 8px 12px;
        text-transform: uppercase;
    }

    .youngo-chip.is-on {
        background: #eedcff;
        border-color: #d9b9ff;
        color: #420f79;
    }

    .youngo-chip.is-warm {
        background: #fff4e5;
        border-color: #f2bd76;
        color: #633f00;
    }

    .youngo-notice {
        align-items: flex-start;
        display: flex;
        gap: 14px;
        margin-bottom: 18px;
        padding: 16px 18px;
    }

    .youngo-notice-icon {
        align-items: center;
        background: #eedcff;
        border-radius: 12px;
        color: #5a2d91;
        display: inline-flex;
        flex: 0 0 36px;
        height: 36px;
        justify-content: center;
        width: 36px;
    }

    .youngo-notice-title {
        color: #191b22;
        font-weight: 800;
        margin-bottom: 3px;
    }

    .youngo-manager-list {
        margin-bottom: 20px;
        overflow: hidden;
        padding: 10px;
    }

    .youngo-list-heading {
        align-items: center;
        display: flex;
        justify-content: space-between;
        padding: 12px 12px 16px;
    }

    .youngo-list-heading h3 {
        color: #191b22;
        font-size: 18px;
        font-weight: 800;
        margin: 0;
    }

    .youngo-manager-item {
        border: 1px solid #e2d9ea;
        border-radius: 16px;
        margin-bottom: 10px;
        overflow: hidden;
    }

    .youngo-manager-item.is-hidden-by-toggle {
        background: #f3f3fc;
        border-style: dashed;
        opacity: 0.78;
    }

    .youngo-manager-row {
        align-items: center;
        background: #ffffff;
        display: grid;
        gap: 18px;
        grid-template-columns: 150px minmax(320px, 1fr) minmax(190px, 0.62fr) 158px;
        min-height: 92px;
        padding: 18px;
    }

    .youngo-order-cell {
        align-items: center;
        display: flex;
        gap: 10px;
        justify-content: flex-start;
        min-width: 0;
    }

    .youngo-drag-handle,
    .youngo-order-move {
        align-items: center;
        background: #f3f3fc;
        border: 1px solid #e2d9ea;
        border-radius: 12px;
        color: #7e42ab;
        display: inline-flex;
        flex: 0 0 38px;
        font-size: 21px;
        height: 42px;
        justify-content: center;
        line-height: 1;
        width: 38px;
    }

    .youngo-order-move {
        background: #ffffff;
        color: #420f79;
        cursor: pointer;
        font-size: 18px;
        padding: 0;
    }

    .youngo-order-move:disabled {
        cursor: not-allowed;
        opacity: 0.42;
    }

    .youngo-position-badge {
        align-items: center;
        background: #420f79;
        border-radius: 999px;
        color: #ffffff;
        display: inline-flex;
        flex: 0 0 44px;
        font-size: 14px;
        font-weight: 900;
        height: 36px;
        justify-content: center;
        min-width: 44px;
        padding: 0 10px;
    }

    .youngo-order-input {
        display: none;
    }

    .youngo-status-cell {
        align-items: center;
        display: flex;
        justify-content: flex-start;
    }

    .youngo-row-main {
        align-items: center;
        display: flex;
        gap: 14px;
        min-width: 0;
    }

    .youngo-section-icon {
        align-items: center;
        background: #eedcff;
        border-radius: 14px;
        color: #5a2d91;
        display: inline-flex;
        flex: 0 0 52px;
        font-size: 23px;
        height: 52px;
        justify-content: center;
        width: 52px;
    }

    .youngo-section-icon.is-warm {
        background: #fff4e5;
        color: #633f00;
    }

    .youngo-row-title {
        color: #191b22;
        font-size: 20px;
        font-weight: 800;
        line-height: 1.2;
        margin: 0 0 4px;
    }

    .youngo-row-copy {
        font-size: 13px;
        line-height: 1.45;
        margin: 0;
    }

    .youngo-row-actions {
        align-items: center;
        display: flex;
        justify-content: flex-end;
    }

    .youngo-editor-panel {
        border-radius: 0;
        border-width: 1px 0 0;
        box-shadow: none;
        display: none;
        padding: 22px;
    }

    .youngo-manager-item.is-open .youngo-editor-panel {
        display: block;
    }

    .youngo-editor-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: minmax(0, 1fr);
    }

    .youngo-settings-grid {
        align-items: end;
        background: #faf8ff;
        border: 1px solid #e2d9ea;
        border-radius: 16px;
        display: grid;
        gap: 14px;
        grid-template-columns: minmax(190px, 0.8fr) minmax(160px, 0.55fr) minmax(220px, 1fr);
        margin-bottom: 18px;
        padding: 14px;
    }

    .youngo-field-label {
        color: #191b22;
        display: block;
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 7px;
    }

    .youngo-help-text {
        display: block;
        font-size: 12px;
        line-height: 1.5;
        margin-top: 6px;
    }

    .youngo-subsection-title {
        border-top: 1px solid #ede7f2;
        color: #420f79;
        font-size: 13px;
        font-weight: 800;
        margin: 18px 0 14px;
        padding-top: 18px;
        text-transform: uppercase;
    }

    .youngo-toggle {
        align-items: center;
        color: #191b22;
        cursor: pointer;
        display: inline-flex;
        font-weight: 800;
        gap: 10px;
        margin: 0;
        min-height: 42px;
    }

    .youngo-toggle input.youngo-show-toggle,
    .youngo-toggle input.youngo-homepage-toggle {
        height: 1px;
        opacity: 0;
        position: absolute;
        width: 1px;
    }

    .youngo-sync-published,
    .youngo-sync-homepage-published {
        display: none;
    }

    .youngo-toggle-track {
        background: #e7e7f0;
        border: 1px solid #cdc3d3;
        border-radius: 999px;
        display: inline-block;
        flex: 0 0 44px;
        height: 24px;
        position: relative;
        transition: background 0.2s ease, border-color 0.2s ease;
        width: 44px;
    }

    .youngo-toggle-track:after {
        background: #ffffff;
        border-radius: 50%;
        box-shadow: 0 2px 5px rgba(25, 27, 34, 0.16);
        content: "";
        height: 18px;
        left: 2px;
        position: absolute;
        top: 2px;
        transition: transform 0.2s ease;
        width: 18px;
    }

    .youngo-show-toggle:checked ~ .youngo-toggle-track,
    .youngo-homepage-toggle:checked ~ .youngo-toggle-track {
        background: #420f79;
        border-color: #420f79;
    }

    .youngo-show-toggle:checked ~ .youngo-toggle-track:after,
    .youngo-homepage-toggle:checked ~ .youngo-toggle-track:after {
        transform: translateX(20px);
    }

    .youngo-repeat-list {
        display: grid;
        gap: 12px;
    }

    .youngo-repeat-item {
        background: #ffffff;
        border: 1px solid #e2d9ea;
        border-radius: 16px;
        padding: 16px;
    }

    .youngo-repeat-item.is-template,
    .youngo-additional-slot.is-template,
    .youngo-type-fields.is-hidden,
    .youngo-is-hidden {
        display: none;
    }

    .youngo-repeat-head,
    .youngo-additional-head {
        align-items: center;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .youngo-repeat-title,
    .youngo-additional-title {
        color: #420f79;
        font-size: 14px;
        font-weight: 800;
        margin: 0;
    }

    .youngo-additional-panel {
        margin-top: 18px;
        padding: 10px;
    }

    .youngo-additional-toolbar {
        align-items: center;
        display: flex;
        justify-content: space-between;
        padding: 12px 12px 16px;
    }

    .youngo-additional-slot {
        border: 1px solid #e2d9ea;
        border-radius: 16px;
        margin-bottom: 10px;
        overflow: hidden;
    }

    .youngo-type-fields {
        background: #ffffff;
        border: 1px solid #ede7f2;
        border-radius: 16px;
        margin-top: 14px;
        padding: 16px;
    }

    .youngo-footer-actions {
        background: rgba(250, 248, 255, 0.96);
        border-top: 1px solid #e2d9ea;
        bottom: 0;
        margin: 24px -24px -24px;
        padding: 16px 24px;
        position: sticky;
        z-index: 5;
    }

    .youngo-footer-actions-inner {
        align-items: center;
        display: flex;
        gap: 16px;
        justify-content: space-between;
        margin: 0 auto;
        max-width: 1180px;
    }

    @media (max-width: 991px) {
        .youngo-admin-manager {
            padding: 16px;
        }

        .youngo-page-head,
        .youngo-footer-actions-inner,
        .youngo-additional-toolbar {
            align-items: stretch;
            flex-direction: column;
        }

        .youngo-head-actions,
        .youngo-chip-row {
            align-items: flex-start;
            justify-content: flex-start;
        }

        .youngo-manager-row {
            grid-template-columns: 150px minmax(0, 1fr);
        }

        .youngo-status-cell {
            grid-column: 2 / -1;
        }

        .youngo-row-actions {
            grid-column: 2 / -1;
            justify-content: flex-start;
        }

        .youngo-settings-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575px) {
        .youngo-page-head,
        .youngo-editor-panel {
            padding: 18px;
        }

        .youngo-row-main {
            align-items: flex-start;
        }

        .youngo-manager-row {
            gap: 14px;
            grid-template-columns: 1fr;
            min-height: 0;
        }

        .youngo-order-cell,
        .youngo-status-cell,
        .youngo-row-actions {
            grid-column: auto;
        }

        .youngo-order-cell {
            width: 100%;
        }

        .youngo-page-title {
            font-size: 25px;
        }
    }

    .youngo-editor-panel {
        background: linear-gradient(180deg, #ffffff 0%, #faf8ff 100%);
    }

    .youngo-editor-header {
        align-items: flex-start;
        background: #ffffff;
        border: 1px solid #e2d9ea;
        border-radius: 18px;
        display: flex;
        gap: 18px;
        justify-content: space-between;
        margin-bottom: 18px;
        padding: 18px;
    }

    .youngo-editor-header h4 {
        color: #191b22;
        font-size: 20px;
        font-weight: 800;
        margin: 0 0 6px;
    }

    .youngo-editor-header p {
        color: #4b4451;
        font-size: 13px;
        line-height: 1.5;
        margin: 0;
    }

    .youngo-editor-actions {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
    }

    .youngo-editor-grid {
        gap: 18px;
    }

    .youngo-settings-grid {
        align-items: stretch;
    }

    .youngo-cms-card {
        background: #ffffff;
        border: 1px solid #e2d9ea;
        border-radius: 18px;
        box-shadow: 0 10px 24px rgba(90, 45, 145, 0.06);
        padding: 18px;
    }

    .youngo-card-head,
    .youngo-field-head {
        align-items: flex-start;
        display: flex;
        gap: 16px;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .youngo-card-head h4 {
        color: #420f79;
        font-size: 16px;
        font-weight: 800;
        margin: 0 0 4px;
    }

    .youngo-card-head p {
        color: #4b4451;
        font-size: 12px;
        line-height: 1.5;
        margin: 0;
    }

    .youngo-card-head > i {
        align-items: center;
        background: #eedcff;
        border-radius: 14px;
        color: #5a2d91;
        display: inline-flex;
        flex: 0 0 44px;
        font-size: 22px;
        height: 44px;
        justify-content: center;
        width: 44px;
    }

    .youngo-cms-control {
        background: #ffffff;
    }

    .youngo-editor-grid > .youngo-field,
    .youngo-type-fields > .youngo-field {
        background: #ffffff;
        border: 1px solid #e2d9ea;
        border-radius: 16px;
        box-shadow: 0 8px 20px rgba(90, 45, 145, 0.045);
        padding: 16px;
    }

    .youngo-editor-grid > .youngo-subsection-title,
    .youngo-type-fields > .youngo-subsection-title {
        background: #f3f3fc;
        border: 1px solid #e2d9ea;
        border-radius: 14px;
        margin: 6px 0 0;
        padding: 12px 14px;
    }

    .youngo-advanced-field {
        background: #faf8ff;
        border: 1px dashed #cdc3d3;
        border-radius: 14px;
        margin-top: 12px;
        padding: 12px;
    }

    .youngo-advanced-field summary {
        color: #420f79;
        cursor: pointer;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .youngo-source-tiles {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .youngo-source-tile {
        background: #ffffff;
        border: 1px solid #e2d9ea;
        border-radius: 14px;
        color: #191b22;
        min-height: 96px;
        padding: 12px;
        text-align: left;
        transition: border-color 0.16s ease, box-shadow 0.16s ease, background 0.16s ease;
    }

    .youngo-source-tile strong,
    .youngo-source-tile span {
        display: block;
    }

    .youngo-source-tile strong {
        color: #420f79;
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .youngo-source-tile span {
        color: #4b4451;
        font-size: 12px;
        line-height: 1.35;
    }

    .youngo-source-tile.is-selected {
        background: #f3f3fc;
        border-color: #7e42ab;
        box-shadow: 0 0 0 3px rgba(126, 66, 171, 0.12);
    }

    .youngo-route-suggestions,
    .youngo-selected-chips,
    .youngo-media-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .youngo-route-suggestions {
        margin-top: 4px;
    }

    .youngo-picker-field {
        background: #ffffff;
        border: 1px solid #e2d9ea;
        border-radius: 18px;
        padding: 16px;
    }

    .youngo-selected-chips {
        min-height: 42px;
        margin-bottom: 12px;
    }

    .youngo-selected-chip {
        align-items: center;
        background: #eedcff;
        border: 1px solid #d9b9ff;
        border-radius: 999px;
        color: #420f79;
        display: inline-flex;
        font-size: 12px;
        font-weight: 800;
        gap: 8px;
        padding: 8px 10px;
    }

    .youngo-selected-chip button {
        background: transparent;
        border: 0;
        color: #420f79;
        font-weight: 900;
        line-height: 1;
        padding: 0;
    }

    .youngo-picker-drawer,
    .youngo-asset-drawer {
        display: none;
        margin-top: 12px;
    }

    .youngo-picker-field.is-open .youngo-picker-drawer,
    .youngo-media-picker.is-open .youngo-asset-drawer {
        display: block;
    }

    .youngo-picker-results,
    .youngo-asset-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-top: 12px;
        max-height: 360px;
        overflow: auto;
        padding-right: 4px;
    }

    .youngo-picker-option,
    .youngo-asset-option {
        align-items: center;
        background: #ffffff;
        border: 1px solid #e2d9ea;
        border-radius: 14px;
        color: #191b22;
        display: grid;
        gap: 10px;
        grid-template-columns: 58px minmax(0, 1fr);
        min-height: 76px;
        padding: 10px;
        text-align: left;
    }

    .youngo-picker-option.is-selected,
    .youngo-asset-option.is-selected {
        border-color: #7e42ab;
        box-shadow: 0 0 0 3px rgba(126, 66, 171, 0.12);
    }

    .youngo-picker-thumb,
    .youngo-asset-thumb {
        background: #f3f3fc center / cover no-repeat;
        border-radius: 12px;
        height: 58px;
        width: 58px;
    }

    .youngo-picker-option strong,
    .youngo-asset-option strong {
        color: #191b22;
        display: block;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.25;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .youngo-picker-option small,
    .youngo-asset-option small {
        color: #4b4451;
        display: block;
        font-size: 11px;
        line-height: 1.35;
        margin-top: 4px;
    }

    .youngo-media-layout {
        display: grid;
        gap: 16px;
        grid-template-columns: 210px minmax(0, 1fr);
        align-items: start;
    }

    .youngo-media-preview {
        align-items: center;
        background: #f3f3fc center / cover no-repeat;
        border: 1px solid #e2d9ea;
        border-radius: 16px;
        color: #4b4451;
        display: flex;
        font-size: 12px;
        font-weight: 800;
        height: 132px;
        justify-content: center;
        overflow: hidden;
        text-align: center;
    }

    .youngo-media-preview[style*="background-image"] span {
        display: none;
    }

    .youngo-media-controls {
        display: grid;
        gap: 12px;
    }

    .youngo-repeat-item {
        transition: border-color 0.16s ease, box-shadow 0.16s ease;
    }

    .youngo-repeat-item.is-collapsed {
        background: #ffffff;
    }

    .youngo-repeat-item.is-collapsed > :not(.youngo-repeat-head) {
        display: none;
    }

    .youngo-repeat-summary {
        color: #4b4451;
        font-size: 12px;
        font-weight: 700;
        margin-left: 8px;
    }

    .youngo-repeat-head-actions {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }

    .youngo-empty-hint {
        color: #4b4451;
        font-size: 13px;
        line-height: 1.5;
        padding: 10px 0;
    }

    @media (max-width: 991px) {
        .youngo-editor-header,
        .youngo-card-head,
        .youngo-field-head {
            flex-direction: column;
        }

        .youngo-editor-actions {
            justify-content: flex-start;
        }

        .youngo-source-tiles,
        .youngo-picker-results,
        .youngo-asset-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .youngo-media-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575px) {
        .youngo-source-tiles,
        .youngo-picker-results,
        .youngo-asset-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="youngo-admin-manager" data-youngo-manager>
    <form action="<?php echo site_url('admin/youngo_homepage/update'); ?>" method="post">
        <div class="youngo-shell">
            <div class="youngo-page-head">
                <div>
                    <div class="youngo-kicker">
                        <i class="mdi mdi-home-heart"></i>
                        <?php echo get_phrase('Homepage manager'); ?>
                    </div>
                    <h2 class="youngo-page-title"><?php echo get_phrase('YounGo Homepage Content'); ?></h2>
                    <p class="youngo-page-copy">
                        <?php echo get_phrase('Manage and arrange the sections displayed on the public landing page. Edit content only when a section needs changes.'); ?>
                    </p>
                </div>
                <div class="youngo-head-actions">
                    <div class="youngo-chip-row">
                        <span class="youngo-chip <?php echo !empty($youngo_homepage['is_active']) && !empty($youngo_homepage['is_published']) ? 'is-on' : 'is-warm'; ?>">
                            <i class="mdi mdi-earth"></i>
                            <?php echo !empty($youngo_homepage['is_active']) && !empty($youngo_homepage['is_published']) ? get_phrase('Homepage enabled') : get_phrase('Homepage hidden'); ?>
                        </span>
                    </div>
                    <button type="submit" class="youngo-save-btn">
                        <i class="mdi mdi-content-save-outline"></i>
                        <?php echo get_phrase('Save changes'); ?>
                    </button>
                </div>
            </div>

            <div class="youngo-notice">
                <span class="youngo-notice-icon"><i class="mdi mdi-information-outline"></i></span>
                <div>
                    <div class="youngo-notice-title"><?php echo get_phrase('Compact section manager'); ?></div>
                    <p class="youngo-page-copy">
                        <?php echo get_phrase('Use the rows below to show, hide, reorder, and edit sections. YounGo theme styling stays fixed and cannot be customized here.'); ?>
                    </p>
                </div>
            </div>

            <div class="youngo-manager-list">
                <div class="youngo-list-heading">
                    <div>
                        <h3><?php echo get_phrase('Global homepage state'); ?></h3>
                        <p class="youngo-row-copy"><?php echo get_phrase('One simple switch controls whether this YounGo homepage content set is enabled.'); ?></p>
                    </div>
                </div>
                <div class="youngo-manager-item">
                    <div class="youngo-manager-row">
                        <div class="youngo-order-cell">
                            <span class="youngo-drag-handle"><i class="mdi mdi-home"></i></span>
                        </div>
                        <div class="youngo-row-main">
                            <span class="youngo-section-icon"><i class="mdi mdi-publish"></i></span>
                            <div>
                                <h3 class="youngo-row-title"><?php echo get_phrase('Homepage publishing'); ?></h3>
                                <p class="youngo-row-copy"><?php echo get_phrase('Controls whether this homepage content set can appear publicly.'); ?></p>
                            </div>
                        </div>
                        <div class="youngo-status-cell">
                            <input type="hidden" name="homepage[is_active]" value="0">
                            <input type="hidden" name="homepage[is_published]" value="0">
                            <label class="youngo-toggle">
                                <input type="checkbox" class="youngo-homepage-toggle" name="homepage[is_active]" value="1" <?php echo !empty($youngo_homepage['is_active']) && !empty($youngo_homepage['is_published']) ? 'checked' : ''; ?>>
                                <input type="checkbox" class="youngo-sync-homepage-published" name="homepage[is_published]" value="1" <?php echo !empty($youngo_homepage['is_active']) && !empty($youngo_homepage['is_published']) ? 'checked' : ''; ?> tabindex="-1" aria-hidden="true">
                                <span class="youngo-toggle-track"></span>
                                <span class="youngo-toggle-label"><?php echo get_phrase('Homepage enabled'); ?></span>
                            </label>
                        </div>
                        <div class="youngo-row-actions">
                            <span class="youngo-chip <?php echo !empty($youngo_homepage['is_active']) && !empty($youngo_homepage['is_published']) ? 'is-on' : 'is-warm'; ?>">
                                <?php echo !empty($youngo_homepage['is_active']) && !empty($youngo_homepage['is_published']) ? get_phrase('Enabled') : get_phrase('Hidden'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="youngo-manager-list">
                <div class="youngo-list-heading">
                    <div>
                        <h3><?php echo get_phrase('Fixed homepage sections'); ?></h3>
                        <p class="youngo-row-copy"><?php echo get_phrase('Use the arrows to reorder sections. Changes are saved when you click Save changes.'); ?></p>
                    </div>
                </div>

                <?php foreach (youngo_homepage_fixed_section_keys() as $section_key): ?>
                    <?php
                        $section = $youngo_sections[$section_key];
                        $content = $section['content'];
                        $section_is_shown = !empty($section['is_visible']) && !empty($section['is_published']);
                    ?>
                    <div class="youngo-manager-item <?php echo $section_is_shown ? '' : 'is-hidden-by-toggle'; ?>" data-editor-wrapper>
                        <div class="youngo-manager-row">
                            <div class="youngo-order-cell">
                                <button type="button" class="youngo-order-move" data-order-move="up" aria-label="<?php echo get_phrase('Move up'); ?>"><i class="mdi mdi-chevron-up"></i></button>
                                <span class="youngo-position-badge" data-position-badge>#1</span>
                                <button type="button" class="youngo-order-move" data-order-move="down" aria-label="<?php echo get_phrase('Move down'); ?>"><i class="mdi mdi-chevron-down"></i></button>
                                <input type="number" class="form-control youngo-order-input" min="0" max="999" name="sections[<?php echo $section_key; ?>][sort_order]" value="<?php echo (int) $section['sort_order']; ?>">
                            </div>
                            <div class="youngo-row-main">
                                <span class="youngo-section-icon <?php echo $section_key === 'featured_courses' || $section_key === 'faq_preview' ? 'is-warm' : ''; ?>">
                                    <i class="mdi <?php echo $youngo_section_icons[$section_key]; ?>"></i>
                                </span>
                                <div>
                                    <h3 class="youngo-row-title"><?php echo get_phrase($youngo_section_labels[$section_key]); ?></h3>
                                    <p class="youngo-row-copy"><?php echo get_phrase($youngo_section_descriptions[$section_key]); ?></p>
                                </div>
                            </div>
                            <div class="youngo-status-cell">
                                <?php youngo_admin_show_toggle("sections[$section_key][is_visible]", "sections[$section_key][is_published]", $section['is_visible'], $section['is_published']); ?>
                            </div>
                            <div class="youngo-row-actions">
                                <button type="button" class="youngo-secondary-btn" data-editor-toggle>
                                    <i class="mdi mdi-pencil-outline"></i>
                                    <span><?php echo get_phrase('Edit Content'); ?></span>
                                </button>
                            </div>
                        </div>

                        <div class="youngo-editor-panel">
                            <div class="youngo-settings-grid">
                                <?php youngo_admin_source_select("sections[$section_key][source_type]", $section['source_type'], $youngo_source_types); ?>
                                <div>
                                    <label class="youngo-field-label"><?php echo get_phrase('Section key'); ?></label>
                                    <div class="youngo-chip"><?php echo $section_key; ?></div>
                                </div>
                                <p class="youngo-row-copy"><?php echo get_phrase('Admins manage content only. Visual styling is controlled by the YounGo theme.'); ?></p>
                            </div>

                            <div class="youngo-editor-grid">
                                <?php if ($section_key === 'hero'): ?>
                                    <?php youngo_admin_text_input("sections[$section_key][content][eyebrow]", 'Eyebrow', $content['eyebrow']); ?>
                                    <?php youngo_admin_text_input("sections[$section_key][content][title]", 'Title', $content['title']); ?>
                                    <?php youngo_admin_textarea("sections[$section_key][content][subtitle]", 'Subtitle', $content['subtitle']); ?>
                                    <?php youngo_admin_cta_fields("sections[$section_key][content][primary_cta]", $content['primary_cta'], 'Primary CTA'); ?>
                                    <?php youngo_admin_cta_fields("sections[$section_key][content][secondary_cta]", $content['secondary_cta'], 'Secondary CTA'); ?>
                                    <?php youngo_admin_image_fields("sections[$section_key][content][image]", $content['image'], 'Hero image'); ?>
                                    <div class="youngo-subsection-title"><?php echo get_phrase('Trust chips'); ?></div>
                                    <div class="youngo-repeat-list">
                                        <?php for ($i = 0; $i < 6; $i++): ?>
                                            <?php $trust_value = isset($content['trust_items'][$i]) ? $content['trust_items'][$i] : ''; ?>
                                            <div class="youngo-repeat-item">
                                                <?php youngo_admin_text_input("sections[$section_key][content][trust_items][$i]", 'Trust chip text', $trust_value); ?>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="youngo-subsection-title"><?php echo get_phrase('Hero stats'); ?></div>
                                    <div class="youngo-repeat-list">
                                        <?php for ($i = 0; $i < 4; $i++): ?>
                                            <?php $stat = isset($content['stats'][$i]) && is_array($content['stats'][$i]) ? $content['stats'][$i] : array('value' => '', 'label' => ''); ?>
                                            <div class="youngo-repeat-item">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <?php youngo_admin_text_input("sections[$section_key][content][stats][$i][value]", 'Stat value', isset($stat['value']) ? $stat['value'] : ''); ?>
                                                    </div>
                                                    <div class="col-md-8">
                                                        <?php youngo_admin_text_input("sections[$section_key][content][stats][$i][label]", 'Stat label', isset($stat['label']) ? $stat['label'] : ''); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                <?php elseif ($section_key === 'featured_categories'): ?>
                                    <?php youngo_admin_text_input("sections[$section_key][content][title]", 'Title', $content['title']); ?>
                                    <?php youngo_admin_textarea("sections[$section_key][content][subtitle]", 'Subtitle', $content['subtitle']); ?>
                                    <?php youngo_admin_text_input("sections[$section_key][content][limit]", 'Limit', $content['limit']); ?>
                                    <?php youngo_admin_text_input("sections[$section_key][content][category_ids]", 'Selected category IDs', isset($content['category_ids']) && is_array($content['category_ids']) ? implode(',', $content['category_ids']) : ''); ?>
                                    <p class="youngo-row-copy"><?php echo get_phrase('Use comma-separated existing LMS category or subcategory IDs. Leave empty to let YounGo use available LMS categories.'); ?></p>
                                    <?php youngo_admin_cta_fields("sections[$section_key][content][cta]", $content['cta'], 'CTA'); ?>
                                    <div class="youngo-subsection-title"><?php echo get_phrase('Optional category image overrides'); ?></div>
                                    <p class="youngo-row-copy"><?php echo get_phrase('Optional content-only overrides. These do not edit LMS category records or theme styling.'); ?></p>
                                    <?php
                                        $category_overrides = isset($content['overrides']) && is_array($content['overrides']) ? $content['overrides'] : array();
                                        $category_override_slots = max(count($category_overrides) + 2, 4);
                                    ?>
                                    <?php for ($i = 0; $i < $category_override_slots; $i++): ?>
                                        <?php $override = isset($category_overrides[$i]) && is_array($category_overrides[$i]) ? $category_overrides[$i] : array('category_id' => '', 'description' => '', 'image' => array('url' => '', 'alt' => '')); ?>
                                        <div class="youngo-repeat-item">
                                            <div class="youngo-repeat-head">
                                                <h4 class="youngo-repeat-title"><?php echo get_phrase('Category override'); ?> <?php echo $i + 1; ?></h4>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <?php youngo_admin_text_input("sections[$section_key][content][overrides][$i][category_id]", 'Category ID', isset($override['category_id']) ? $override['category_id'] : ''); ?>
                                                </div>
                                                <div class="col-md-8">
                                                    <?php youngo_admin_text_input("sections[$section_key][content][overrides][$i][description]", 'Short description', isset($override['description']) ? $override['description'] : ''); ?>
                                                </div>
                                            </div>
                                            <?php youngo_admin_image_fields("sections[$section_key][content][overrides][$i][image]", isset($override['image']) ? $override['image'] : array('url' => '', 'alt' => ''), 'Override image'); ?>
                                        </div>
                                    <?php endfor; ?>
                                <?php elseif ($section_key === 'featured_courses'): ?>
                                    <?php youngo_admin_text_input("sections[$section_key][content][title]", 'Title', $content['title']); ?>
                                    <?php youngo_admin_textarea("sections[$section_key][content][subtitle]", 'Subtitle', $content['subtitle']); ?>
                                    <?php youngo_admin_text_input("sections[$section_key][content][limit]", 'Limit', $content['limit']); ?>
                                    <?php youngo_admin_text_input("sections[$section_key][content][course_ids]", 'Selected course IDs', isset($content['course_ids']) && is_array($content['course_ids']) ? implode(',', $content['course_ids']) : ''); ?>
                                    <?php youngo_admin_text_input("sections[$section_key][content][category_ids]", 'Filter category IDs', isset($content['category_ids']) && is_array($content['category_ids']) ? implode(',', $content['category_ids']) : ''); ?>
                                    <p class="youngo-row-copy"><?php echo get_phrase('Use comma-separated existing LMS course IDs. Optional category IDs filter automatic course selection only.'); ?></p>
                                    <?php youngo_admin_cta_fields("sections[$section_key][content][cta]", $content['cta'], 'CTA'); ?>
                                    <div class="youngo-subsection-title"><?php echo get_phrase('Optional course image overrides'); ?></div>
                                    <p class="youngo-row-copy"><?php echo get_phrase('Optional content-only overrides. These do not edit LMS courses, pricing, instructors, or theme styling.'); ?></p>
                                    <?php
                                        $course_overrides = isset($content['overrides']) && is_array($content['overrides']) ? $content['overrides'] : array();
                                        $course_override_slots = max(count($course_overrides) + 2, 4);
                                    ?>
                                    <?php for ($i = 0; $i < $course_override_slots; $i++): ?>
                                        <?php $override = isset($course_overrides[$i]) && is_array($course_overrides[$i]) ? $course_overrides[$i] : array('course_id' => '', 'badge' => '', 'image' => array('url' => '', 'alt' => '')); ?>
                                        <div class="youngo-repeat-item">
                                            <div class="youngo-repeat-head">
                                                <h4 class="youngo-repeat-title"><?php echo get_phrase('Course override'); ?> <?php echo $i + 1; ?></h4>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <?php youngo_admin_text_input("sections[$section_key][content][overrides][$i][course_id]", 'Course ID', isset($override['course_id']) ? $override['course_id'] : ''); ?>
                                                </div>
                                                <div class="col-md-8">
                                                    <?php youngo_admin_text_input("sections[$section_key][content][overrides][$i][badge]", 'Badge label', isset($override['badge']) ? $override['badge'] : ''); ?>
                                                </div>
                                            </div>
                                            <?php youngo_admin_image_fields("sections[$section_key][content][overrides][$i][image]", isset($override['image']) ? $override['image'] : array('url' => '', 'alt' => ''), 'Override image'); ?>
                                        </div>
                                    <?php endfor; ?>
                                <?php elseif ($section_key === 'blog_preview'): ?>
                                    <?php youngo_admin_text_input("sections[$section_key][content][title]", 'Title', $content['title']); ?>
                                    <?php youngo_admin_textarea("sections[$section_key][content][subtitle]", 'Subtitle', $content['subtitle']); ?>
                                    <?php youngo_admin_text_input("sections[$section_key][content][limit]", 'Limit', $content['limit']); ?>
                                    <?php youngo_admin_cta_fields("sections[$section_key][content][cta]", $content['cta'], 'CTA'); ?>
                                <?php elseif ($section_key === 'why_choose'): ?>
                                    <?php youngo_admin_text_input("sections[$section_key][content][title]", 'Title', $content['title']); ?>
                                    <?php youngo_admin_textarea("sections[$section_key][content][subtitle]", 'Subtitle', $content['subtitle']); ?>
                                    <div class="youngo-subsection-title"><?php echo get_phrase('Benefits'); ?></div>
                                    <div class="youngo-repeat-list" data-repeat-container data-max-items="6">
                                        <?php for ($i = 0; $i < 6; $i++): ?>
                                            <?php
                                                $item = isset($content['items'][$i]) ? $content['items'][$i] : array('icon_key' => '', 'title' => '', 'description' => '');
                                                $item_active = youngo_admin_repeat_item_state($item, $i, count($content['items']));
                                            ?>
                                            <div class="youngo-repeat-item <?php echo $item_active ? '' : 'is-template'; ?>" data-repeat-item>
                                                <div class="youngo-repeat-head">
                                                    <h4 class="youngo-repeat-title"><?php echo get_phrase('Benefit'); ?> <?php echo $i + 1; ?></h4>
                                                    <button type="button" class="youngo-danger-btn" data-repeat-remove><?php echo get_phrase('Remove item'); ?></button>
                                                </div>
                                                <?php youngo_admin_text_input("sections[$section_key][content][items][$i][icon_key]", 'Icon key', isset($item['icon_key']) ? $item['icon_key'] : '', false, !$item_active); ?>
                                                <?php youngo_admin_text_input("sections[$section_key][content][items][$i][title]", 'Title', isset($item['title']) ? $item['title'] : '', false, !$item_active); ?>
                                                <?php youngo_admin_textarea("sections[$section_key][content][items][$i][description]", 'Description', isset($item['description']) ? $item['description'] : '', !$item_active); ?>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                    <button type="button" class="youngo-secondary-btn" data-repeat-add><?php echo get_phrase('Add item'); ?></button>
                                <?php elseif ($section_key === 'about_teaser'): ?>
                                    <?php youngo_admin_text_input("sections[$section_key][content][title]", 'Title', $content['title']); ?>
                                    <?php youngo_admin_textarea("sections[$section_key][content][body]", 'Body', $content['body']); ?>
                                    <?php youngo_admin_cta_fields("sections[$section_key][content][cta]", $content['cta'], 'CTA'); ?>
                                    <?php youngo_admin_image_fields("sections[$section_key][content][image]", isset($content['image']) ? $content['image'] : array('url' => '', 'alt' => ''), 'About image'); ?>
                                    <div class="youngo-subsection-title"><?php echo get_phrase('About stats'); ?></div>
                                    <div class="youngo-repeat-list">
                                        <?php for ($i = 0; $i < 4; $i++): ?>
                                            <?php $stat = isset($content['stats'][$i]) && is_array($content['stats'][$i]) ? $content['stats'][$i] : array('value' => '', 'label' => ''); ?>
                                            <div class="youngo-repeat-item">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <?php youngo_admin_text_input("sections[$section_key][content][stats][$i][value]", 'Stat value', isset($stat['value']) ? $stat['value'] : ''); ?>
                                                    </div>
                                                    <div class="col-md-8">
                                                        <?php youngo_admin_text_input("sections[$section_key][content][stats][$i][label]", 'Stat label', isset($stat['label']) ? $stat['label'] : ''); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="youngo-subsection-title"><?php echo get_phrase('Checklist items'); ?></div>
                                    <div class="youngo-repeat-list" data-repeat-container data-max-items="6">
                                        <?php for ($i = 0; $i < 6; $i++): ?>
                                            <?php
                                                $item_value = isset($content['items'][$i]) ? $content['items'][$i] : '';
                                                $item_active = youngo_admin_repeat_item_state($item_value, $i, count($content['items']));
                                            ?>
                                            <div class="youngo-repeat-item <?php echo $item_active ? '' : 'is-template'; ?>" data-repeat-item>
                                                <div class="youngo-repeat-head">
                                                    <h4 class="youngo-repeat-title"><?php echo get_phrase('Checklist item'); ?> <?php echo $i + 1; ?></h4>
                                                    <button type="button" class="youngo-danger-btn" data-repeat-remove><?php echo get_phrase('Remove item'); ?></button>
                                                </div>
                                                <?php youngo_admin_text_input("sections[$section_key][content][items][$i]", 'Text', $item_value, false, !$item_active); ?>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                    <button type="button" class="youngo-secondary-btn" data-repeat-add><?php echo get_phrase('Add item'); ?></button>
                                <?php elseif ($section_key === 'testimonials'): ?>
                                    <?php youngo_admin_text_input("sections[$section_key][content][title]", 'Title', $content['title']); ?>
                                    <?php youngo_admin_textarea("sections[$section_key][content][subtitle]", 'Subtitle', $content['subtitle']); ?>
                                    <div class="youngo-subsection-title"><?php echo get_phrase('Testimonials'); ?></div>
                                    <div class="youngo-repeat-list" data-repeat-container data-max-items="6">
                                        <?php for ($i = 0; $i < 6; $i++): ?>
                                            <?php
                                                $item = isset($content['items'][$i]) ? $content['items'][$i] : array('quote' => '', 'name' => '', 'role' => '', 'rating' => 5);
                                                $item_active = youngo_admin_repeat_item_state($item, $i, count($content['items']));
                                            ?>
                                            <div class="youngo-repeat-item <?php echo $item_active ? '' : 'is-template'; ?>" data-repeat-item>
                                                <div class="youngo-repeat-head">
                                                    <h4 class="youngo-repeat-title"><?php echo get_phrase('Testimonial'); ?> <?php echo $i + 1; ?></h4>
                                                    <button type="button" class="youngo-danger-btn" data-repeat-remove><?php echo get_phrase('Remove item'); ?></button>
                                                </div>
                                                <?php youngo_admin_textarea("sections[$section_key][content][items][$i][quote]", 'Quote', isset($item['quote']) ? $item['quote'] : '', !$item_active); ?>
                                                <?php youngo_admin_text_input("sections[$section_key][content][items][$i][name]", 'Name', isset($item['name']) ? $item['name'] : '', false, !$item_active); ?>
                                                <?php youngo_admin_text_input("sections[$section_key][content][items][$i][role]", 'Role', isset($item['role']) ? $item['role'] : '', false, !$item_active); ?>
                                                <?php youngo_admin_text_input("sections[$section_key][content][items][$i][rating]", 'Rating', isset($item['rating']) ? $item['rating'] : 5, false, !$item_active); ?>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                    <button type="button" class="youngo-secondary-btn" data-repeat-add><?php echo get_phrase('Add item'); ?></button>
                                <?php elseif ($section_key === 'faq_preview'): ?>
                                    <?php youngo_admin_text_input("sections[$section_key][content][title]", 'Title', $content['title']); ?>
                                    <?php youngo_admin_textarea("sections[$section_key][content][subtitle]", 'Subtitle', $content['subtitle']); ?>
                                    <?php youngo_admin_text_input("sections[$section_key][content][limit]", 'Limit', $content['limit']); ?>
                                    <?php youngo_admin_cta_fields("sections[$section_key][content][cta]", $content['cta'], 'CTA'); ?>
                                    <div class="youngo-subsection-title"><?php echo get_phrase('FAQ items'); ?></div>
                                    <div class="youngo-repeat-list" data-repeat-container data-max-items="10">
                                        <?php for ($i = 0; $i < 10; $i++): ?>
                                            <?php
                                                $item = isset($content['items'][$i]) ? $content['items'][$i] : array('question' => '', 'answer' => '');
                                                $item_active = youngo_admin_repeat_item_state($item, $i, count($content['items']));
                                            ?>
                                            <div class="youngo-repeat-item <?php echo $item_active ? '' : 'is-template'; ?>" data-repeat-item>
                                                <div class="youngo-repeat-head">
                                                    <h4 class="youngo-repeat-title"><?php echo get_phrase('FAQ'); ?> <?php echo $i + 1; ?></h4>
                                                    <button type="button" class="youngo-danger-btn" data-repeat-remove><?php echo get_phrase('Remove item'); ?></button>
                                                </div>
                                                <?php youngo_admin_text_input("sections[$section_key][content][items][$i][question]", 'Question', isset($item['question']) ? $item['question'] : '', false, !$item_active); ?>
                                                <?php youngo_admin_textarea("sections[$section_key][content][items][$i][answer]", 'Answer', isset($item['answer']) ? $item['answer'] : '', !$item_active); ?>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                    <button type="button" class="youngo-secondary-btn" data-repeat-add><?php echo get_phrase('Add item'); ?></button>
                                <?php elseif ($section_key === 'final_cta'): ?>
                                    <?php youngo_admin_text_input("sections[$section_key][content][title]", 'Title', $content['title']); ?>
                                    <?php youngo_admin_textarea("sections[$section_key][content][subtitle]", 'Subtitle', $content['subtitle']); ?>
                                    <?php youngo_admin_cta_fields("sections[$section_key][content][primary_cta]", $content['primary_cta'], 'Primary CTA'); ?>
                                    <?php youngo_admin_cta_fields("sections[$section_key][content][secondary_cta]", $content['secondary_cta'], 'Secondary CTA'); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="youngo-additional-panel">
                <div class="youngo-additional-toolbar">
                    <div>
                        <h3 class="youngo-row-title"><?php echo get_phrase('Additional controlled sections'); ?></h3>
                        <p class="youngo-row-copy"><?php echo get_phrase('Add approved section types and use the arrows to place them in order.'); ?></p>
                    </div>
                    <button type="button" class="youngo-secondary-btn" data-add-section>
                        <i class="mdi mdi-plus-circle-outline"></i>
                        <?php echo get_phrase('Add Section'); ?>
                    </button>
                </div>

                <?php
                    $additional_sections = $youngo_homepage['additional_sections'];
                    $slot_count = max(count($additional_sections) + 3, 8);
                ?>

                <?php for ($i = 0; $i < $slot_count; $i++): ?>
                    <?php
                        $additional = isset($additional_sections[$i]) ? $additional_sections[$i] : array(
                            'id' => '',
                            'type' => '',
                            'label' => '',
                            'is_visible' => true,
                            'is_published' => true,
                            'sort_order' => 100 + $i,
                            'source_type' => 'manual',
                            'content' => array(),
                        );
                        $additional_type = isset($additional['type']) ? $additional['type'] : '';
                        $additional_active = in_array($additional_type, $youngo_additional_types);
                        $additional_content = isset($additional['content']) && is_array($additional['content']) ? $additional['content'] : array();
                        $additional_label = !empty($additional['label']) ? $additional['label'] : ($additional_active ? $youngo_additional_type_labels[$additional_type] : 'New controlled section');
                    ?>
                    <div class="youngo-additional-slot youngo-manager-item <?php echo $additional_active ? '' : 'is-template'; ?>" data-additional-slot data-editor-wrapper>
                        <div class="youngo-manager-row">
                            <div class="youngo-order-cell">
                                <button type="button" class="youngo-order-move" data-order-move="up" aria-label="<?php echo get_phrase('Move up'); ?>"><i class="mdi mdi-chevron-up"></i></button>
                                <span class="youngo-position-badge" data-position-badge>#1</span>
                                <button type="button" class="youngo-order-move" data-order-move="down" aria-label="<?php echo get_phrase('Move down'); ?>"><i class="mdi mdi-chevron-down"></i></button>
                                <input type="number" class="form-control youngo-order-input" min="0" max="999" name="additional_sections[<?php echo $i; ?>][sort_order]" value="<?php echo (int) $additional['sort_order']; ?>">
                            </div>
                            <div class="youngo-row-main">
                                <span class="youngo-section-icon is-warm"><i class="mdi mdi-plus-box-outline"></i></span>
                                <div>
                                    <h3 class="youngo-row-title" data-additional-row-title><?php echo get_phrase($additional_label); ?></h3>
                                    <p class="youngo-row-copy" data-additional-row-copy>
                                        <?php echo $additional_active ? get_phrase($youngo_additional_type_descriptions[$additional_type]) : get_phrase('Choose an approved type, then edit its content.'); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="youngo-status-cell">
                                <?php youngo_admin_show_toggle("additional_sections[$i][is_visible]", "additional_sections[$i][is_published]", $additional['is_visible'], $additional['is_published']); ?>
                            </div>
                            <div class="youngo-row-actions">
                                <button type="button" class="youngo-secondary-btn" data-editor-toggle>
                                    <i class="mdi mdi-pencil-outline"></i>
                                    <?php echo get_phrase('Edit Content'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="youngo-editor-panel">
                            <input type="hidden" name="additional_sections[<?php echo $i; ?>][id]" value="<?php echo youngo_homepage_e($additional['id']); ?>">
                            <input type="checkbox" class="youngo-remove-section-input" name="additional_sections[<?php echo $i; ?>][remove]" value="1" tabindex="-1" aria-hidden="true" style="display:none;">
                            <div class="youngo-settings-grid">
                                <div class="form-group youngo-field">
                                    <label class="youngo-field-label"><?php echo get_phrase('Type'); ?></label>
                                    <select class="form-control" name="additional_sections[<?php echo $i; ?>][type]" data-additional-type-select>
                                        <option value=""><?php echo get_phrase('Choose type'); ?></option>
                                        <?php foreach ($youngo_additional_types as $type): ?>
                                            <option value="<?php echo $type; ?>" <?php echo $additional_type === $type ? 'selected' : ''; ?>>
                                                <?php echo get_phrase($youngo_additional_type_labels[$type]); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php youngo_admin_text_input("additional_sections[$i][label]", 'Label', $additional['label']); ?>
                                <?php youngo_admin_source_select("additional_sections[$i][source_type]", $additional['source_type'], $youngo_source_types); ?>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <?php youngo_admin_text_input("additional_sections[$i][content][title]", 'Title', isset($additional_content['title']) ? $additional_content['title'] : ''); ?>
                                </div>
                                <div class="col-md-6">
                                    <?php youngo_admin_textarea("additional_sections[$i][content][subtitle]", 'Subtitle', isset($additional_content['subtitle']) ? $additional_content['subtitle'] : ''); ?>
                                </div>
                            </div>

                            <div class="youngo-type-fields" data-type-fields="text_image">
                                <?php youngo_admin_textarea("additional_sections[$i][content][body]", 'Body', isset($additional_content['body']) ? $additional_content['body'] : ''); ?>
                                <?php youngo_admin_image_fields("additional_sections[$i][content][image]", isset($additional_content['image']) ? $additional_content['image'] : array(), 'Image'); ?>
                                <?php youngo_admin_cta_fields("additional_sections[$i][content][cta]", isset($additional_content['cta']) ? $additional_content['cta'] : array(), 'CTA'); ?>
                            </div>

                            <div class="youngo-type-fields" data-type-fields="cta_band">
                                <?php youngo_admin_cta_fields("additional_sections[$i][content][primary_cta]", isset($additional_content['primary_cta']) ? $additional_content['primary_cta'] : array(), 'Primary CTA'); ?>
                                <?php youngo_admin_cta_fields("additional_sections[$i][content][secondary_cta]", isset($additional_content['secondary_cta']) ? $additional_content['secondary_cta'] : array(), 'Secondary CTA'); ?>
                            </div>

                            <div class="youngo-type-fields" data-type-fields="course_highlight">
                                <?php youngo_admin_text_input("additional_sections[$i][content][course_ids]", 'Course IDs comma-separated', isset($additional_content['course_ids']) && is_array($additional_content['course_ids']) ? implode(',', $additional_content['course_ids']) : ''); ?>
                                <?php youngo_admin_text_input("additional_sections[$i][content][limit]", 'Limit', isset($additional_content['limit']) ? $additional_content['limit'] : ''); ?>
                                <?php youngo_admin_cta_fields("additional_sections[$i][content][cta]", isset($additional_content['cta']) ? $additional_content['cta'] : array(), 'CTA'); ?>
                            </div>

                            <div class="youngo-type-fields" data-type-fields="category_highlight">
                                <?php youngo_admin_text_input("additional_sections[$i][content][category_ids]", 'Category IDs comma-separated', isset($additional_content['category_ids']) && is_array($additional_content['category_ids']) ? implode(',', $additional_content['category_ids']) : ''); ?>
                                <?php youngo_admin_text_input("additional_sections[$i][content][limit]", 'Limit', isset($additional_content['limit']) ? $additional_content['limit'] : ''); ?>
                                <?php youngo_admin_cta_fields("additional_sections[$i][content][cta]", isset($additional_content['cta']) ? $additional_content['cta'] : array(), 'CTA'); ?>
                            </div>

                            <div class="youngo-type-fields" data-type-fields="feature_cards testimonial_block faq_block">
                                <div class="youngo-subsection-title"><?php echo get_phrase('Items'); ?></div>
                                <div class="youngo-repeat-list" data-repeat-container data-max-items="10">
                                    <?php for ($j = 0; $j < 10; $j++): ?>
                                        <?php
                                            $item = isset($additional_content['items'][$j]) ? $additional_content['items'][$j] : array();
                                            $item_active = youngo_admin_item_has_content($item);
                                        ?>
                                        <div class="youngo-repeat-item <?php echo $item_active ? '' : 'is-template'; ?>" data-repeat-item>
                                            <div class="youngo-repeat-head">
                                                <h5 class="youngo-repeat-title"><?php echo get_phrase('Item'); ?> <?php echo $j + 1; ?></h5>
                                                <button type="button" class="youngo-danger-btn" data-repeat-remove><?php echo get_phrase('Remove item'); ?></button>
                                            </div>
                                            <div data-type-fields="feature_cards">
                                                <?php youngo_admin_text_input("additional_sections[$i][content][items][$j][icon_key]", 'Icon key', isset($item['icon_key']) ? $item['icon_key'] : '', false, !$item_active); ?>
                                                <?php youngo_admin_text_input("additional_sections[$i][content][items][$j][title]", 'Item title', isset($item['title']) ? $item['title'] : '', false, !$item_active); ?>
                                                <?php youngo_admin_textarea("additional_sections[$i][content][items][$j][description]", 'Description', isset($item['description']) ? $item['description'] : '', !$item_active); ?>
                                            </div>
                                            <div data-type-fields="testimonial_block">
                                                <?php youngo_admin_text_input("additional_sections[$i][content][items][$j][name]", 'Name', isset($item['name']) ? $item['name'] : '', false, !$item_active); ?>
                                                <?php youngo_admin_text_input("additional_sections[$i][content][items][$j][role]", 'Role', isset($item['role']) ? $item['role'] : '', false, !$item_active); ?>
                                                <?php youngo_admin_textarea("additional_sections[$i][content][items][$j][quote]", 'Quote', isset($item['quote']) ? $item['quote'] : '', !$item_active); ?>
                                                <?php youngo_admin_text_input("additional_sections[$i][content][items][$j][rating]", 'Rating', isset($item['rating']) ? $item['rating'] : '', false, !$item_active); ?>
                                                <?php youngo_admin_image_fields("additional_sections[$i][content][items][$j][image]", isset($item['image']) ? $item['image'] : array(), 'Image', !$item_active); ?>
                                            </div>
                                            <div data-type-fields="faq_block">
                                                <?php youngo_admin_text_input("additional_sections[$i][content][items][$j][question]", 'Question', isset($item['question']) ? $item['question'] : '', false, !$item_active); ?>
                                                <?php youngo_admin_textarea("additional_sections[$i][content][items][$j][answer]", 'Answer', isset($item['answer']) ? $item['answer'] : '', !$item_active); ?>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                <button type="button" class="youngo-secondary-btn" data-repeat-add><?php echo get_phrase('Add item'); ?></button>
                            </div>

                            <div class="youngo-subsection-title"><?php echo get_phrase('Section actions'); ?></div>
                            <button type="button" class="youngo-danger-btn" data-remove-section>
                                <i class="mdi mdi-trash-can-outline"></i>
                                <?php echo get_phrase('Remove section'); ?>
                            </button>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="youngo-footer-actions">
                <div class="youngo-footer-actions-inner">
                    <p class="youngo-page-copy">
                        <?php echo get_phrase('Save to update the CMS-managed homepage content. Existing storage and frontend rendering behavior are unchanged.'); ?>
                    </p>
                    <button type="submit" class="youngo-save-btn">
                        <i class="mdi mdi-content-save-outline"></i>
                        <?php echo get_phrase('Save changes'); ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
(function () {
    var manager = document.querySelector('[data-youngo-manager]');
    if (!manager) {
        return;
    }

    var pickerData = {
        assets: <?php echo json_encode($youngo_asset_picker_items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>,
        categories: <?php echo json_encode($youngo_category_picker_items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>,
        courses: <?php echo json_encode($youngo_course_picker_items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>
    };

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (character) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[character];
        });
    }

    function splitIds(value) {
        return String(value || '').split(',').map(function (id) {
            return parseInt(id, 10);
        }).filter(function (id, index, list) {
            return id > 0 && list.indexOf(id) === index;
        });
    }

    function setIds(input, ids) {
        input.value = ids.join(',');
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function getItemSummary(item) {
        var fields = Array.prototype.slice.call(item.querySelectorAll('input, textarea, select')).filter(function (field) {
            return !field.disabled && field.type !== 'hidden' && field.type !== 'checkbox';
        });
        for (var index = 0; index < fields.length; index++) {
            if (String(fields[index].value || '').trim() !== '') {
                return String(fields[index].value).trim().slice(0, 80);
            }
        }
        return '<?php echo get_phrase('Empty item'); ?>';
    }

    function refreshRepeatSummary(item) {
        var summary = item.querySelector('.youngo-repeat-summary');
        if (summary) {
            summary.textContent = getItemSummary(item);
        }
    }

    function enhanceEditorPanels() {
        manager.querySelectorAll('.youngo-editor-panel').forEach(function (panel) {
            if (panel.querySelector('.youngo-editor-header')) {
                return;
            }
            var wrapper = panel.closest('[data-editor-wrapper]');
            var title = wrapper ? wrapper.querySelector('.youngo-row-title') : null;
            var copy = wrapper ? wrapper.querySelector('.youngo-row-copy') : null;
            var header = document.createElement('div');
            header.className = 'youngo-editor-header';
            header.innerHTML =
                '<div>' +
                    '<h4>' + escapeHtml(title ? title.textContent.trim() : '<?php echo get_phrase('Section editor'); ?>') + '</h4>' +
                    '<p>' + escapeHtml(copy ? copy.textContent.trim() : '<?php echo get_phrase('Edit content fields for this section.'); ?>') + ' <?php echo get_phrase('Changes are applied when you use Save & close or the main Save changes button.'); ?></p>' +
                '</div>' +
                '<div class="youngo-editor-actions">' +
                    '<button type="submit" class="youngo-secondary-btn" data-save-close><i class="mdi mdi-check-circle-outline"></i><?php echo get_phrase('Save & close'); ?></button>' +
                    '<button type="button" class="youngo-text-btn" data-editor-close><?php echo get_phrase('Close'); ?></button>' +
                '</div>';
            panel.insertBefore(header, panel.firstChild);
        });
    }

    function renderSelectedChips(block, input, data) {
        var chips = block.querySelector('[data-selected-chips]');
        if (!chips) {
            return;
        }
        var selected = splitIds(input.value);
        chips.innerHTML = '';
        if (!selected.length) {
            chips.innerHTML = '<span class="youngo-empty-hint"><?php echo get_phrase('No manual selections yet. Automatic or fallback content can still be used.'); ?></span>';
            return;
        }
        selected.forEach(function (id) {
            var item = data.find(function (entry) { return parseInt(entry.id, 10) === id; });
            var chip = document.createElement('span');
            chip.className = 'youngo-selected-chip';
            chip.innerHTML = '<span>' + escapeHtml(item ? (item.title || item.name) : ('ID ' + id)) + ' <small>#' + id + '</small></span><button type="button" aria-label="<?php echo get_phrase('Remove'); ?>" data-remove-id="' + id + '">&times;</button>';
            chips.appendChild(chip);
        });
    }

    function renderPickerResults(block) {
        var type = block.getAttribute('data-id-picker');
        var data = type === 'courses' ? pickerData.courses : pickerData.categories;
        var source = block.querySelector('.youngo-picker-source');
        var results = block.querySelector('[data-picker-results]');
        var search = block.querySelector('[data-picker-search]');
        if (!source || !results) {
            return;
        }
        var selected = splitIds(source.value);
        var query = search ? search.value.toLowerCase().trim() : '';
        var filtered = data.filter(function (item) {
            var haystack = [
                item.id,
                item.title,
                item.name,
                item.status,
                item.category,
                item.price
            ].join(' ').toLowerCase();
            return query === '' || haystack.indexOf(query) !== -1;
        }).slice(0, 60);
        results.innerHTML = '';
        filtered.forEach(function (item) {
            var id = parseInt(item.id, 10);
            var isSelected = selected.indexOf(id) !== -1;
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'youngo-picker-option' + (isSelected ? ' is-selected' : '');
            button.setAttribute('data-picker-id', id);
            button.innerHTML =
                '<span class="youngo-picker-thumb" style="background-image:url(' + escapeHtml(item.image || '') + ')"></span>' +
                '<span><strong>' + escapeHtml(item.title || item.name || ('ID ' + id)) + '</strong>' +
                '<small>#' + id + (item.status ? ' | ' + escapeHtml(item.status) : '') + (item.category ? ' | ' + escapeHtml(item.category) : '') + (item.count !== undefined ? ' | ' + item.count + ' <?php echo get_phrase('courses'); ?>' : '') + (item.price ? ' | ' + escapeHtml(item.price) : '') + '</small></span>';
            results.appendChild(button);
        });
        if (!filtered.length) {
            results.innerHTML = '<div class="youngo-empty-hint"><?php echo get_phrase('No matching records found.'); ?></div>';
        }
        renderSelectedChips(block, source, data);
    }

    function initIdPickers() {
        manager.querySelectorAll('[data-id-picker]').forEach(function (block) {
            renderPickerResults(block);
        });
    }

    function renderAssets(block) {
        var input = block.querySelector('input[name$="[url]"]');
        var grid = block.querySelector('[data-asset-grid]');
        var search = block.querySelector('[data-asset-search]');
        if (!input || !grid) {
            return;
        }
        var current = input.value;
        var query = search ? search.value.toLowerCase().trim() : '';
        var filtered = pickerData.assets.filter(function (asset) {
            var haystack = [asset.path, asset.name, asset.group].join(' ').toLowerCase();
            return query === '' || haystack.indexOf(query) !== -1;
        }).slice(0, 80);
        grid.innerHTML = '';
        filtered.forEach(function (asset) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'youngo-asset-option' + (current === asset.path ? ' is-selected' : '');
            button.setAttribute('data-asset-path', asset.path);
            button.setAttribute('data-asset-url', asset.url);
            button.innerHTML =
                '<span class="youngo-asset-thumb" style="background-image:url(' + escapeHtml(asset.url) + ')"></span>' +
                '<span><strong>' + escapeHtml(asset.name) + '</strong><small>' + escapeHtml(asset.group || asset.path) + '</small></span>';
            grid.appendChild(button);
        });
        if (!filtered.length) {
            grid.innerHTML = '<div class="youngo-empty-hint"><?php echo get_phrase('No matching local assets found.'); ?></div>';
        }
    }

    function updateMediaPreview(block) {
        var input = block.querySelector('input[name$="[url]"]');
        var preview = block.querySelector('[data-media-preview]');
        if (!input || !preview) {
            return;
        }
        var value = input.value.trim();
        if (value === '') {
            preview.style.backgroundImage = '';
            return;
        }
        var previewUrl = /^https?:\/\//i.test(value) ? value : <?php echo json_encode(base_url()); ?> + value.replace(/^\/+/, '');
        preview.style.backgroundImage = 'url(' + previewUrl + ')';
    }

    function initMediaPickers() {
        manager.querySelectorAll('[data-media-picker]').forEach(function (block) {
            updateMediaPreview(block);
            renderAssets(block);
        });
    }

    function initSourceTiles() {
        manager.querySelectorAll('[data-source-field]').forEach(function (field) {
            var select = field.querySelector('.youngo-source-select');
            if (!select) {
                return;
            }
            field.querySelectorAll('[data-source-value]').forEach(function (tile) {
                tile.classList.toggle('is-selected', tile.getAttribute('data-source-value') === select.value);
            });
        });
    }

    function enhanceRepeatItems() {
        manager.querySelectorAll('[data-repeat-item]').forEach(function (item) {
            if (item.getAttribute('data-repeat-enhanced') === '1') {
                refreshRepeatSummary(item);
                return;
            }
            item.setAttribute('data-repeat-enhanced', '1');
            var head = item.querySelector('.youngo-repeat-head');
            if (!head) {
                return;
            }
            var title = head.querySelector('.youngo-repeat-title');
            if (title && !title.querySelector('.youngo-repeat-summary')) {
                var summary = document.createElement('span');
                summary.className = 'youngo-repeat-summary';
                title.appendChild(summary);
            }
            var actions = document.createElement('div');
            actions.className = 'youngo-repeat-head-actions';
            actions.innerHTML =
                '<button type="button" class="youngo-text-btn" data-repeat-collapse><?php echo get_phrase('Edit'); ?></button>' +
                '<button type="button" class="youngo-text-btn" data-repeat-duplicate><?php echo get_phrase('Duplicate'); ?></button>';
            var remove = head.querySelector('[data-repeat-remove]');
            if (remove) {
                actions.appendChild(remove);
            }
            head.appendChild(actions);
            if (!item.classList.contains('is-template')) {
                item.classList.add('is-collapsed');
            }
            refreshRepeatSummary(item);
        });
    }

    function getOrderItems(scope) {
        if (!scope) {
            return [];
        }
        return Array.prototype.slice.call(scope.querySelectorAll('[data-editor-wrapper]')).filter(function (item) {
            return item.querySelector('.youngo-order-input') && !item.classList.contains('is-template');
        });
    }

    function updateOrderScope(scope) {
        var items = getOrderItems(scope);
        items.forEach(function (item, index) {
            var badge = item.querySelector('[data-position-badge]');
            var input = item.querySelector('.youngo-order-input');
            var up = item.querySelector('[data-order-move="up"]');
            var down = item.querySelector('[data-order-move="down"]');

            if (badge) {
                badge.textContent = '#' + (index + 1);
            }
            if (input) {
                input.value = String((index + 1) * 10);
            }
            if (up) {
                up.disabled = index === 0;
            }
            if (down) {
                down.disabled = index === items.length - 1;
            }
        });
    }

    function sortOrderScope(scope) {
        var items = getOrderItems(scope);
        items.sort(function (a, b) {
            var aInput = a.querySelector('.youngo-order-input');
            var bInput = b.querySelector('.youngo-order-input');
            var aOrder = aInput && aInput.value !== '' ? parseInt(aInput.value, 10) : 999;
            var bOrder = bInput && bInput.value !== '' ? parseInt(bInput.value, 10) : 999;
            return aOrder - bOrder;
        });
        items.forEach(function (item) {
            item.parentNode.appendChild(item);
        });
        updateOrderScope(scope);
    }

    function updateAllOrderScopes() {
        manager.querySelectorAll('.youngo-manager-list, .youngo-additional-panel').forEach(function (scope) {
            updateOrderScope(scope);
        });
    }

    function sortAllOrderScopes() {
        manager.querySelectorAll('.youngo-manager-list, .youngo-additional-panel').forEach(function (scope) {
            sortOrderScope(scope);
        });
    }

    function moveOrderItem(button) {
        var item = button.closest('[data-editor-wrapper]');
        var scope = button.closest('.youngo-manager-list') || button.closest('.youngo-additional-panel');
        if (!item || !scope) {
            return;
        }
        var items = getOrderItems(scope);
        var index = items.indexOf(item);
        var direction = button.getAttribute('data-order-move');
        if (direction === 'up' && index > 0) {
            item.parentNode.insertBefore(item, items[index - 1]);
        }
        if (direction === 'down' && index >= 0 && index < items.length - 1) {
            item.parentNode.insertBefore(items[index + 1], item);
        }
        updateOrderScope(scope);
    }

    function duplicateRepeatItem(button) {
        var item = button.closest('[data-repeat-item]');
        var container = item ? item.closest('[data-repeat-container]') : null;
        if (!item || !container) {
            return;
        }
        var next = Array.prototype.slice.call(container.querySelectorAll('[data-repeat-item]')).find(function (candidate) {
            return candidate.classList.contains('is-template');
        });
        if (!next) {
            return;
        }
        var sourceFields = Array.prototype.slice.call(item.querySelectorAll('input, textarea, select'));
        var targetFields = Array.prototype.slice.call(next.querySelectorAll('input, textarea, select'));
        sourceFields.forEach(function (field, index) {
            if (!targetFields[index]) {
                return;
            }
            if (field.type === 'checkbox' || field.type === 'radio') {
                targetFields[index].checked = field.checked;
            } else {
                targetFields[index].value = field.value;
            }
            targetFields[index].disabled = false;
        });
        next.classList.remove('is-template');
        next.classList.remove('is-collapsed');
        refreshRepeatSummary(next);
    }

    function setInputsDisabled(container, disabled) {
        var inputs = container.querySelectorAll('input, textarea, select');
        inputs.forEach(function (input) {
            if (input.classList.contains('youngo-show-toggle') || input.classList.contains('youngo-sync-published')) {
                return;
            }
            input.disabled = disabled;
        });
    }

    function syncShowToggle(input) {
        var label = input.closest('.youngo-toggle');
        var sync = label ? label.querySelector('.youngo-sync-published') : null;
        var item = input.closest('.youngo-manager-item');
        if (sync) {
            sync.checked = input.checked;
        }
        if (item) {
            item.classList.toggle('is-hidden-by-toggle', !input.checked);
        }
    }

    function syncHomepageToggle(input) {
        var label = input.closest('.youngo-toggle');
        var sync = label ? label.querySelector('.youngo-sync-homepage-published') : null;
        if (sync) {
            sync.checked = input.checked;
        }
    }

    function initRepeatContainer(container) {
        var items = Array.prototype.slice.call(container.querySelectorAll('[data-repeat-item]'));
        items.forEach(function (item) {
            if (item.classList.contains('is-template')) {
                setInputsDisabled(item, true);
            }
        });
    }

    function addRepeatItem(button) {
        var editor = button.closest('.youngo-editor-panel') || button.closest('.youngo-type-fields');
        var container = editor ? editor.querySelector('[data-repeat-container]') : null;
        if (!container) {
            return;
        }
        var slot = button.closest('[data-additional-slot]');
        var typeSelect = slot ? slot.querySelector('[data-additional-type-select]') : null;
        var type = typeSelect ? typeSelect.value : '';
        var max = parseInt(container.getAttribute('data-max-items'), 10) || 6;
        if (type === 'feature_cards' || type === 'testimonial_block') {
            max = 6;
        }
        if (type === 'faq_block') {
            max = 10;
        }
        var allItems = Array.prototype.slice.call(container.querySelectorAll('[data-repeat-item]'));
        var visibleCount = allItems.filter(function (item) {
            return !item.classList.contains('is-template');
        }).length;
        if (visibleCount >= max) {
            return;
        }
        var next = null;
        for (var index = 0; index < allItems.length && index < max; index++) {
            if (allItems[index].classList.contains('is-template')) {
                next = allItems[index];
                break;
            }
        }
        if (!next) {
            return;
        }
        next.classList.remove('is-template');
        setInputsDisabled(next, false);
        updateAdditionalTypeVisibility(next.closest('[data-additional-slot]'));
    }

    function removeRepeatItem(button) {
        var item = button.closest('[data-repeat-item]');
        if (!item) {
            return;
        }
        var fields = item.querySelectorAll('input, textarea, select');
        fields.forEach(function (field) {
            if (field.type === 'checkbox' || field.type === 'radio') {
                field.checked = false;
            } else {
                field.value = '';
            }
            field.disabled = true;
        });
        item.classList.add('is-template');
    }

    function updateAdditionalTypeVisibility(slot) {
        if (!slot) {
            return;
        }
        var select = slot.querySelector('[data-additional-type-select]');
        var type = select ? select.value : '';
        var rowTitle = slot.querySelector('[data-additional-row-title]');
        var rowCopy = slot.querySelector('[data-additional-row-copy]');
        var typeLabels = {
            text_image: '<?php echo get_phrase('Text and Image'); ?>',
            feature_cards: '<?php echo get_phrase('Feature Cards'); ?>',
            cta_band: '<?php echo get_phrase('CTA Band'); ?>',
            testimonial_block: '<?php echo get_phrase('Testimonial Block'); ?>',
            faq_block: '<?php echo get_phrase('FAQ Block'); ?>',
            course_highlight: '<?php echo get_phrase('Course Highlight'); ?>',
            category_highlight: '<?php echo get_phrase('Category Highlight'); ?>'
        };
        var typeCopy = {
            text_image: '<?php echo get_phrase('A controlled text block with image and CTA.'); ?>',
            feature_cards: '<?php echo get_phrase('A small group of feature or benefit cards.'); ?>',
            cta_band: '<?php echo get_phrase('A focused call-to-action section.'); ?>',
            testimonial_block: '<?php echo get_phrase('A controlled testimonial group.'); ?>',
            faq_block: '<?php echo get_phrase('A controlled FAQ group.'); ?>',
            course_highlight: '<?php echo get_phrase('Highlights selected LMS courses.'); ?>',
            category_highlight: '<?php echo get_phrase('Highlights selected LMS categories.'); ?>'
        };

        if (rowTitle) {
            rowTitle.textContent = typeLabels[type] || '<?php echo get_phrase('New controlled section'); ?>';
        }
        if (rowCopy) {
            rowCopy.textContent = typeCopy[type] || '<?php echo get_phrase('Choose an approved type, then edit its content.'); ?>';
        }

        slot.querySelectorAll('[data-type-fields]').forEach(function (group) {
            var allowed = (group.getAttribute('data-type-fields') || '').split(/\s+/);
            var shouldShow = allowed.indexOf(type) !== -1;
            group.classList.toggle('is-hidden', !shouldShow);
            setInputsDisabled(group, !shouldShow);
        });

        slot.querySelectorAll('[data-repeat-container]').forEach(initRepeatContainer);
    }

    manager.querySelectorAll('.youngo-show-toggle').forEach(function (input) {
        syncShowToggle(input);
    });

    manager.querySelectorAll('.youngo-homepage-toggle').forEach(function (input) {
        syncHomepageToggle(input);
    });

    manager.querySelectorAll('[data-repeat-container]').forEach(initRepeatContainer);

    manager.querySelectorAll('[data-additional-slot]').forEach(function (slot) {
        updateAdditionalTypeVisibility(slot);
    });

    enhanceEditorPanels();
    sortAllOrderScopes();
    initIdPickers();
    initMediaPickers();
    initSourceTiles();
    enhanceRepeatItems();

    var homepageForm = manager.querySelector('form');
    if (homepageForm) {
        homepageForm.addEventListener('submit', updateAllOrderScopes);
    }

    manager.addEventListener('change', function (event) {
        if (event.target.classList.contains('youngo-show-toggle')) {
            syncShowToggle(event.target);
        }
        if (event.target.classList.contains('youngo-homepage-toggle')) {
            syncHomepageToggle(event.target);
        }
        if (event.target.matches('[data-additional-type-select]')) {
            updateAdditionalTypeVisibility(event.target.closest('[data-additional-slot]'));
        }
        if (event.target.matches('.youngo-picker-source')) {
            renderPickerResults(event.target.closest('[data-id-picker]'));
        }
        if (event.target.matches('[data-asset-search]')) {
            renderAssets(event.target.closest('[data-media-picker]'));
        }
        if (event.target.matches('[data-picker-search]')) {
            renderPickerResults(event.target.closest('[data-id-picker]'));
        }
        if (event.target.matches('[data-media-picker] input[name$="[url]"]')) {
            updateMediaPreview(event.target.closest('[data-media-picker]'));
            renderAssets(event.target.closest('[data-media-picker]'));
        }
        if (event.target.closest('[data-repeat-item]')) {
            refreshRepeatSummary(event.target.closest('[data-repeat-item]'));
        }
    });

    manager.addEventListener('input', function (event) {
        if (event.target.matches('[data-asset-search]')) {
            renderAssets(event.target.closest('[data-media-picker]'));
        }
        if (event.target.matches('[data-picker-search]')) {
            renderPickerResults(event.target.closest('[data-id-picker]'));
        }
        if (event.target.closest('[data-repeat-item]')) {
            refreshRepeatSummary(event.target.closest('[data-repeat-item]'));
        }
    });

    manager.addEventListener('click', function (event) {
        var orderButton = event.target.closest('[data-order-move]');
        if (orderButton) {
            moveOrderItem(orderButton);
            return;
        }

        var editorClose = event.target.closest('[data-editor-close]');
        if (editorClose) {
            var editorWrapper = editorClose.closest('[data-editor-wrapper]');
            if (editorWrapper) {
                editorWrapper.classList.remove('is-open');
            }
            return;
        }

        if (event.target.closest('[data-save-close]')) {
            var saveWrapper = event.target.closest('[data-editor-wrapper]');
            if (saveWrapper) {
                saveWrapper.classList.remove('is-open');
            }
            return;
        }

        var routeButton = event.target.closest('[data-route-value]');
        if (routeButton) {
            var ctaCard = routeButton.closest('.youngo-cta-builder');
            var urlInput = ctaCard ? ctaCard.querySelector('input[name$="[url]"]') : null;
            if (urlInput) {
                urlInput.value = routeButton.getAttribute('data-route-value');
                urlInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            return;
        }

        var sourceTile = event.target.closest('[data-source-value]');
        if (sourceTile) {
            var sourceField = sourceTile.closest('[data-source-field]');
            var select = sourceField ? sourceField.querySelector('.youngo-source-select') : null;
            if (select) {
                select.value = sourceTile.getAttribute('data-source-value');
                select.dispatchEvent(new Event('change', { bubbles: true }));
                initSourceTiles();
            }
            return;
        }

        var pickerOpen = event.target.closest('[data-picker-open]');
        if (pickerOpen) {
            var pickerBlock = pickerOpen.closest('[data-id-picker]');
            if (pickerBlock) {
                pickerBlock.classList.toggle('is-open');
                renderPickerResults(pickerBlock);
            }
            return;
        }

        var pickerOption = event.target.closest('[data-picker-id]');
        if (pickerOption) {
            var idBlock = pickerOption.closest('[data-id-picker]');
            var idSource = idBlock ? idBlock.querySelector('.youngo-picker-source') : null;
            if (idSource) {
                var ids = splitIds(idSource.value);
                var id = parseInt(pickerOption.getAttribute('data-picker-id'), 10);
                var existing = ids.indexOf(id);
                if (existing === -1) {
                    ids.push(id);
                } else {
                    ids.splice(existing, 1);
                }
                setIds(idSource, ids);
                renderPickerResults(idBlock);
            }
            return;
        }

        var removeId = event.target.closest('[data-remove-id]');
        if (removeId) {
            var removeBlock = removeId.closest('[data-id-picker]');
            var removeSource = removeBlock ? removeBlock.querySelector('.youngo-picker-source') : null;
            if (removeSource) {
                var removeIds = splitIds(removeSource.value).filter(function (id) {
                    return id !== parseInt(removeId.getAttribute('data-remove-id'), 10);
                });
                setIds(removeSource, removeIds);
                renderPickerResults(removeBlock);
            }
            return;
        }

        var mediaOpen = event.target.closest('[data-media-open]');
        if (mediaOpen) {
            var mediaBlock = mediaOpen.closest('[data-media-picker]');
            if (mediaBlock) {
                mediaBlock.classList.toggle('is-open');
                renderAssets(mediaBlock);
            }
            return;
        }

        var mediaClear = event.target.closest('[data-media-clear]');
        if (mediaClear) {
            var clearBlock = mediaClear.closest('[data-media-picker]');
            var clearInput = clearBlock ? clearBlock.querySelector('input[name$="[url]"]') : null;
            if (clearInput) {
                clearInput.value = '';
                clearInput.dispatchEvent(new Event('change', { bubbles: true }));
                updateMediaPreview(clearBlock);
            }
            return;
        }

        var assetOption = event.target.closest('[data-asset-path]');
        if (assetOption) {
            var assetBlock = assetOption.closest('[data-media-picker]');
            var assetInput = assetBlock ? assetBlock.querySelector('input[name$="[url]"]') : null;
            if (assetInput) {
                assetInput.value = assetOption.getAttribute('data-asset-path');
                assetInput.dispatchEvent(new Event('change', { bubbles: true }));
                updateMediaPreview(assetBlock);
                renderAssets(assetBlock);
            }
            return;
        }

        var repeatCollapse = event.target.closest('[data-repeat-collapse]');
        if (repeatCollapse) {
            var collapseItem = repeatCollapse.closest('[data-repeat-item]');
            if (collapseItem) {
                collapseItem.classList.toggle('is-collapsed');
                repeatCollapse.textContent = collapseItem.classList.contains('is-collapsed') ? '<?php echo get_phrase('Edit'); ?>' : '<?php echo get_phrase('Collapse'); ?>';
            }
            return;
        }

        var repeatDuplicate = event.target.closest('[data-repeat-duplicate]');
        if (repeatDuplicate) {
            duplicateRepeatItem(repeatDuplicate);
            return;
        }

        var toggleButton = event.target.closest('[data-editor-toggle]');
        if (toggleButton) {
            var wrapper = toggleButton.closest('[data-editor-wrapper]');
            if (wrapper) {
                wrapper.classList.toggle('is-open');
            }
            return;
        }

        var addItem = event.target.closest('[data-repeat-add]');
        if (addItem) {
            addRepeatItem(addItem);
            enhanceRepeatItems();
            return;
        }

        var removeItem = event.target.closest('[data-repeat-remove]');
        if (removeItem) {
            removeRepeatItem(removeItem);
            enhanceRepeatItems();
            return;
        }

        var addSection = event.target.closest('[data-add-section]');
        if (addSection) {
            var nextSlot = manager.querySelector('[data-additional-slot].is-template');
            if (nextSlot) {
                nextSlot.classList.remove('is-template');
                nextSlot.classList.add('is-open');
                nextSlot.parentNode.appendChild(nextSlot);
                var typeSelect = nextSlot.querySelector('[data-additional-type-select]');
                if (typeSelect) {
                    typeSelect.focus();
                }
                updateAdditionalTypeVisibility(nextSlot);
                enhanceEditorPanels();
                initIdPickers();
                initMediaPickers();
                initSourceTiles();
                enhanceRepeatItems();
                updateAllOrderScopes();
            }
            return;
        }

        var removeSection = event.target.closest('[data-remove-section]');
        if (removeSection) {
            var slot = removeSection.closest('[data-additional-slot]');
            if (slot) {
                var removeInput = slot.querySelector('.youngo-remove-section-input');
                if (removeInput) {
                    removeInput.checked = true;
                }
                slot.classList.add('is-template');
                slot.classList.remove('is-open');
                updateAllOrderScopes();
            }
        }
    });
})();
</script>
