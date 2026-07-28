<?php
    if (!function_exists('youngo_checkout_cta_decision') && file_exists(APPPATH . 'helpers/youngo_checkout_cta_helper.php')) {
        $this->load->helper('youngo_checkout_cta');
    }

    $youngo_section_content = is_array($youngo_section_content) ? $youngo_section_content : array();
    $youngo_resolved_courses = function_exists('youngo_homepage_resolve_featured_courses') ? youngo_homepage_resolve_featured_courses(isset($youngo_section) ? $youngo_section : $youngo_section_content) : array();
    $youngo_courses = count($youngo_resolved_courses) > 0 ? $youngo_resolved_courses : (isset($youngo_section_content['items']) && is_array($youngo_section_content['items']) ? $youngo_section_content['items'] : array());
    $youngo_course_limit = isset($youngo_section_content['limit']) ? (int) $youngo_section_content['limit'] : 3;
    $youngo_courses = array_slice($youngo_courses, 0, max(1, $youngo_course_limit));
    $youngo_cta = isset($youngo_section_content['cta']) && is_array($youngo_section_content['cta']) ? $youngo_section_content['cta'] : array('label' => 'See All Courses', 'url' => 'home/courses');
?>

<section class="youngo-section">
    <div class="youngo-container">
        <div class="youngo-section__heading youngo-section__heading--center">
            <p class="youngo-eyebrow"><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('Featured courses') : 'Featured courses'); ?></p>
            <h2><?php echo youngo_homepage_e(isset($youngo_section_content['title']) ? $youngo_section_content['title'] : 'Popular courses for young learners'); ?></h2>
            <p><?php echo youngo_homepage_e(isset($youngo_section_content['subtitle']) ? $youngo_section_content['subtitle'] : 'Start with calm, structured learning experiences that feel playful and useful.'); ?></p>
        </div>

        <div class="youngo-course-grid">
            <?php foreach ($youngo_courses as $youngo_course): ?>
                <?php
                    $youngo_course_image = isset($youngo_course['image']) && is_array($youngo_course['image']) ? $youngo_course['image'] : array('url' => isset($youngo_course['image']) ? 'assets/frontend/youngo/images/' . $youngo_course['image'] : '', 'alt' => '');
                    $youngo_course_title = isset($youngo_course['title']) ? $youngo_course['title'] : 'YounGo Course';
                    $youngo_course_url = isset($youngo_course['url']) && $youngo_course['url'] !== '' ? $youngo_course['url'] : (isset($youngo_cta['url']) ? $youngo_cta['url'] : 'home/courses');
                    $youngo_course_badge = isset($youngo_course['badge']) ? $youngo_course['badge'] : (isset($youngo_course['age_range']) ? $youngo_course['age_range'] : (isset($youngo_course['age']) ? $youngo_course['age'] : 'Ages 4-12'));
                    if (function_exists('youngo_frontend_text')) {
                        $youngo_course_badge = youngo_frontend_text($youngo_course_badge);
                    }
                    $youngo_course_rating = isset($youngo_course['rating']) ? $youngo_course['rating'] : 'New';
                    $youngo_review_count = isset($youngo_course['review_count']) ? (int) $youngo_course['review_count'] : 0;
                    $youngo_featured_checkout_decision = array('show_cta' => false, 'target_url' => null, 'label_text' => '');
                    if (function_exists('youngo_checkout_cta_decision')) {
                        try {
                            $youngo_featured_checkout_decision = youngo_checkout_cta_decision($youngo_course, (int) $this->session->userdata('user_id'));
                        } catch (Throwable $exception) {
                            $youngo_featured_checkout_decision = array('show_cta' => false, 'target_url' => null, 'label_text' => '');
                        }
                    }
                    $youngo_featured_show_checkout = !empty($youngo_featured_checkout_decision['show_cta']) && !empty($youngo_featured_checkout_decision['target_url']);
                    $youngo_featured_action_url = $youngo_featured_show_checkout ? $youngo_featured_checkout_decision['target_url'] : $youngo_course_url;
                    $youngo_featured_action_label = $youngo_featured_show_checkout && !empty($youngo_featured_checkout_decision['label_text'])
                        ? $youngo_featured_checkout_decision['label_text']
                        : (function_exists('youngo_frontend_text') ? youngo_frontend_text('View details') : 'View details');
                ?>
                <article class="youngo-course-card youngo-course-card--linked">
                    <a class="youngo-course-card__link-overlay" href="<?php echo youngo_homepage_link($youngo_course_url); ?>" aria-label="<?php echo youngo_homepage_e((function_exists('youngo_frontend_text') ? youngo_frontend_text('View details') : 'View details') . ': ' . $youngo_course_title); ?>"></a>
                    <div class="youngo-course-card__media">
                        <img src="<?php echo youngo_homepage_image_url(isset($youngo_course_image['url']) ? $youngo_course_image['url'] : '', function_exists('youngo_homepage_demo_image_for_text') ? youngo_homepage_demo_image_for_text($youngo_course_title . ' ' . (isset($youngo_course['category']) ? $youngo_course['category'] : ''), 'course') : 'assets/frontend/youngo/images/demo-course-coding.jpg'); ?>" alt="<?php echo youngo_homepage_e(isset($youngo_course_image['alt']) && $youngo_course_image['alt'] !== '' ? $youngo_course_image['alt'] : $youngo_course_title); ?>">
                        <span><?php echo youngo_homepage_e($youngo_course_badge); ?></span>
                    </div>
                    <div class="youngo-course-card__body">
                        <?php if (!empty($youngo_course['category'])): ?>
                            <div class="youngo-course-card__category"><?php echo youngo_homepage_e($youngo_course['category']); ?></div>
                        <?php endif; ?>
                        <h3><?php echo youngo_homepage_e($youngo_course_title); ?></h3>
                        <p><?php echo youngo_homepage_e(isset($youngo_course['description']) ? $youngo_course['description'] : (isset($youngo_course['summary']) ? $youngo_course['summary'] : 'A calm, structured learning experience for young learners.')); ?></p>
                        <div class="youngo-course-card__facts">
                            <?php if (!empty($youngo_course['instructor'])): ?>
                                <span><?php echo youngo_homepage_e($youngo_course['instructor']); ?></span>
                            <?php endif; ?>
                            <span class="youngo-price-label"><?php echo youngo_homepage_e(isset($youngo_course['price_label']) ? $youngo_course['price_label'] : 'Free preview'); ?></span>
                        </div>
                        <div class="youngo-card-meta">
                            <span>
                                <?php if ($youngo_course_rating === 'New' || $youngo_review_count <= 0): ?>
                                    <?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('New course') : 'New course'); ?>
                                <?php else: ?>
                                    <?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text($youngo_course_rating . ' rating') : ($youngo_course_rating . ' rating')); ?>
                                <?php endif; ?>
                            </span>
                            <a href="<?php echo $youngo_featured_show_checkout ? html_escape($youngo_featured_action_url) : youngo_homepage_link($youngo_featured_action_url); ?>" <?php echo $youngo_featured_show_checkout ? 'data-youngo-checkout-cta="local-featured-course"' : ''; ?>><?php echo youngo_homepage_e($youngo_featured_action_label); ?></a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="youngo-section__actions">
            <a class="youngo-button youngo-button--secondary" href="<?php echo youngo_homepage_link(isset($youngo_cta['url']) ? $youngo_cta['url'] : 'home/courses'); ?>"><?php echo youngo_homepage_e(isset($youngo_cta['label']) ? $youngo_cta['label'] : 'See All Courses'); ?></a>
        </div>
    </div>
</section>
