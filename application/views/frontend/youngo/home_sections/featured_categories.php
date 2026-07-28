<?php
    $youngo_section_content = is_array($youngo_section_content) ? $youngo_section_content : array();
    $youngo_resolved_categories = function_exists('youngo_homepage_resolve_featured_categories') ? youngo_homepage_resolve_featured_categories(isset($youngo_section) ? $youngo_section : $youngo_section_content) : array();
    $youngo_categories = count($youngo_resolved_categories) > 0 ? $youngo_resolved_categories : (isset($youngo_section_content['items']) && is_array($youngo_section_content['items']) ? $youngo_section_content['items'] : array());
    $youngo_category_limit = isset($youngo_section_content['limit']) ? (int) $youngo_section_content['limit'] : 4;
    $youngo_categories = array_slice($youngo_categories, 0, max(1, $youngo_category_limit));
    $youngo_cta = isset($youngo_section_content['cta']) && is_array($youngo_section_content['cta']) ? $youngo_section_content['cta'] : array('label' => 'View all courses', 'url' => 'home/courses');
?>

<section class="youngo-section youngo-section--white">
    <div class="youngo-container">
        <div class="youngo-section__heading youngo-section__heading--split">
            <div>
            <p class="youngo-eyebrow"><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('Learning paths') : 'Learning paths'); ?></p>
                <h2><?php echo youngo_homepage_e(isset($youngo_section_content['title']) ? $youngo_section_content['title'] : 'Explore by category'); ?></h2>
                <p><?php echo youngo_homepage_e(isset($youngo_section_content['subtitle']) ? $youngo_section_content['subtitle'] : "Find the right starting point for your child's interests."); ?></p>
            </div>
            <a class="youngo-text-link" href="<?php echo youngo_homepage_link(isset($youngo_cta['url']) ? $youngo_cta['url'] : 'home/courses'); ?>"><?php echo youngo_homepage_e(isset($youngo_cta['label']) ? $youngo_cta['label'] : 'View all courses'); ?></a>
        </div>

        <div class="youngo-category-grid">
            <?php foreach ($youngo_categories as $youngo_category): ?>
                <?php
                    $youngo_category_url = isset($youngo_category['url']) && $youngo_category['url'] !== '' ? $youngo_category['url'] : (isset($youngo_cta['url']) ? $youngo_cta['url'] : 'home/courses');
                    $youngo_category_image = isset($youngo_category['image']) && is_array($youngo_category['image']) ? $youngo_category['image'] : array();
                    $youngo_category_title = isset($youngo_category['title']) ? $youngo_category['title'] : 'YounGo';
                    $youngo_category_count = isset($youngo_category['count']) ? (int) $youngo_category['count'] : 0;
                    $youngo_category_meta = $youngo_category_count > 0 ? $youngo_category_count . ' ' . ($youngo_category_count === 1 ? 'course' : 'courses') : (isset($youngo_category['meta']) ? $youngo_category['meta'] : 'Learning path');
                    if (function_exists('youngo_frontend_text')) {
                        $youngo_category_meta = youngo_frontend_text($youngo_category_meta);
                    }
                ?>
                <a class="youngo-category-card" href="<?php echo youngo_homepage_link($youngo_category_url); ?>">
                    <span class="youngo-category-card__media">
                        <img src="<?php echo youngo_homepage_image_url(isset($youngo_category_image['url']) ? $youngo_category_image['url'] : '', function_exists('youngo_homepage_demo_image_for_text') ? youngo_homepage_demo_image_for_text($youngo_category_title, 'category') : 'assets/frontend/youngo/images/demo-hero-learning.jpg'); ?>" alt="<?php echo youngo_homepage_e(isset($youngo_category_image['alt']) && $youngo_category_image['alt'] !== '' ? $youngo_category_image['alt'] : $youngo_category_title); ?>">
                        <span class="youngo-icon-badge" aria-hidden="true"><?php echo youngo_homepage_e(isset($youngo_category['icon_key']) ? $youngo_category['icon_key'] : (isset($youngo_category['icon']) ? $youngo_category['icon'] : 'YG')); ?></span>
                    </span>
                    <span class="youngo-category-card__body">
                        <strong><?php echo youngo_homepage_e($youngo_category_title); ?></strong>
                        <small><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text(isset($youngo_category['description']) ? $youngo_category['description'] : 'Explore a focused learning path.') : (isset($youngo_category['description']) ? $youngo_category['description'] : 'Explore a focused learning path.')); ?></small>
                        <span class="youngo-category-card__meta"><?php echo youngo_homepage_e($youngo_category_meta); ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
