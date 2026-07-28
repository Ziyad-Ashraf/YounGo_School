<?php
    $youngo_section = is_array($youngo_section) ? $youngo_section : array();
    $youngo_section_type = isset($youngo_section['type']) ? $youngo_section['type'] : '';
    $youngo_section_content = isset($youngo_section['content']) && is_array($youngo_section['content']) ? $youngo_section['content'] : array();

    if (!in_array($youngo_section_type, youngo_homepage_allowed_additional_section_types())) {
        return;
    }

    $youngo_section_title = isset($youngo_section_content['title']) ? $youngo_section_content['title'] : '';
    $youngo_section_subtitle = isset($youngo_section_content['subtitle']) ? $youngo_section_content['subtitle'] : '';
    $youngo_section_items = isset($youngo_section_content['items']) && is_array($youngo_section_content['items']) ? $youngo_section_content['items'] : array();
    $youngo_section_cta = isset($youngo_section_content['cta']) && is_array($youngo_section_content['cta']) ? $youngo_section_content['cta'] : array('label' => '', 'url' => '');
?>

<?php if ($youngo_section_type === 'text_image'): ?>
    <?php $youngo_image = isset($youngo_section_content['image']) && is_array($youngo_section_content['image']) ? $youngo_section_content['image'] : array('url' => '', 'alt' => ''); ?>
    <section class="youngo-section youngo-about">
        <div class="youngo-container youngo-about__grid">
            <div class="youngo-about__visual" aria-hidden="true">
                <?php if (!empty($youngo_image['url'])): ?>
                    <img src="<?php echo youngo_homepage_image_url($youngo_image['url'], 'assets/frontend/youngo/images/demo-hero-learning.jpg'); ?>" alt="<?php echo youngo_homepage_e(isset($youngo_image['alt']) ? $youngo_image['alt'] : 'YounGo learning'); ?>">
                <?php else: ?>
                    <div class="youngo-about-tile youngo-about-tile--large">Learn</div>
                    <div class="youngo-about-tile youngo-about-tile--small">Play</div>
                    <div class="youngo-about-tile youngo-about-tile--small youngo-about-tile--warm">Grow</div>
                    <div class="youngo-about-tile youngo-about-tile--large youngo-about-tile--purple">Create</div>
                <?php endif; ?>
            </div>
            <div>
                <p class="youngo-eyebrow"><?php echo youngo_homepage_e(isset($youngo_section['label']) ? $youngo_section['label'] : 'YounGo'); ?></p>
                <h2><?php echo youngo_homepage_e($youngo_section_title); ?></h2>
                <?php if ($youngo_section_subtitle !== ''): ?><p><?php echo youngo_homepage_e($youngo_section_subtitle); ?></p><?php endif; ?>
                <?php if (!empty($youngo_section_content['body'])): ?><p><?php echo youngo_homepage_e($youngo_section_content['body']); ?></p><?php endif; ?>
                <?php if (!empty($youngo_section_cta['label'])): ?>
                    <div class="youngo-actions">
                        <a class="youngo-button" href="<?php echo youngo_homepage_link(isset($youngo_section_cta['url']) ? $youngo_section_cta['url'] : 'home'); ?>"><?php echo youngo_homepage_e($youngo_section_cta['label']); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php elseif ($youngo_section_type === 'feature_cards'): ?>
    <section class="youngo-section youngo-section--trust">
        <div class="youngo-container">
            <div class="youngo-section__heading youngo-section__heading--center">
                <p class="youngo-eyebrow"><?php echo youngo_homepage_e(isset($youngo_section['label']) ? $youngo_section['label'] : 'YounGo'); ?></p>
                <h2><?php echo youngo_homepage_e($youngo_section_title); ?></h2>
                <?php if ($youngo_section_subtitle !== ''): ?><p><?php echo youngo_homepage_e($youngo_section_subtitle); ?></p><?php endif; ?>
            </div>
            <div class="youngo-benefit-grid">
                <?php foreach ($youngo_section_items as $index => $youngo_item): ?>
                    <article class="youngo-benefit-card">
                        <span class="youngo-number-badge"><?php echo youngo_homepage_e(isset($youngo_item['icon_key']) && $youngo_item['icon_key'] !== '' ? $youngo_item['icon_key'] : sprintf('%02d', $index + 1)); ?></span>
                        <h3><?php echo youngo_homepage_e(isset($youngo_item['title']) ? $youngo_item['title'] : 'Feature'); ?></h3>
                        <p><?php echo youngo_homepage_e(isset($youngo_item['description']) ? $youngo_item['description'] : ''); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php elseif ($youngo_section_type === 'cta_band'): ?>
    <?php
        $youngo_primary_cta = isset($youngo_section_content['primary_cta']) && is_array($youngo_section_content['primary_cta']) ? $youngo_section_content['primary_cta'] : array('label' => '', 'url' => '');
        $youngo_secondary_cta = isset($youngo_section_content['secondary_cta']) && is_array($youngo_section_content['secondary_cta']) ? $youngo_section_content['secondary_cta'] : array('label' => '', 'url' => '');
    ?>
    <section class="youngo-section youngo-final-cta">
        <div class="youngo-container">
            <div class="youngo-final-cta__panel">
                <p class="youngo-eyebrow"><?php echo youngo_homepage_e(isset($youngo_section['label']) ? $youngo_section['label'] : 'YounGo'); ?></p>
                <h2><?php echo youngo_homepage_e($youngo_section_title); ?></h2>
                <?php if ($youngo_section_subtitle !== ''): ?><p><?php echo youngo_homepage_e($youngo_section_subtitle); ?></p><?php endif; ?>
                <div class="youngo-actions youngo-actions--center">
                    <?php if (!empty($youngo_primary_cta['label'])): ?><a class="youngo-button youngo-button--light" href="<?php echo youngo_homepage_link(isset($youngo_primary_cta['url']) ? $youngo_primary_cta['url'] : 'home'); ?>"><?php echo youngo_homepage_e($youngo_primary_cta['label']); ?></a><?php endif; ?>
                    <?php if (!empty($youngo_secondary_cta['label'])): ?><a class="youngo-button youngo-button--ghost-light" href="<?php echo youngo_homepage_link(isset($youngo_secondary_cta['url']) ? $youngo_secondary_cta['url'] : 'home'); ?>"><?php echo youngo_homepage_e($youngo_secondary_cta['label']); ?></a><?php endif; ?>
                </div>
            </div>
        </div>
    </section>
<?php elseif ($youngo_section_type === 'testimonial_block'): ?>
    <section class="youngo-section youngo-section--soft">
        <div class="youngo-container">
            <div class="youngo-section__heading youngo-section__heading--center">
                <p class="youngo-eyebrow"><?php echo youngo_homepage_e(isset($youngo_section['label']) ? $youngo_section['label'] : 'Testimonials'); ?></p>
                <h2><?php echo youngo_homepage_e($youngo_section_title); ?></h2>
                <?php if ($youngo_section_subtitle !== ''): ?><p><?php echo youngo_homepage_e($youngo_section_subtitle); ?></p><?php endif; ?>
            </div>
            <div class="youngo-testimonial-grid">
                <?php foreach ($youngo_section_items as $youngo_item): ?>
                    <article class="youngo-testimonial-card">
                        <div class="youngo-rating" aria-label="Rating"><?php echo youngo_homepage_e(isset($youngo_item['rating']) ? $youngo_item['rating'] : '5'); ?> rating</div>
                        <blockquote><?php echo youngo_homepage_e(isset($youngo_item['quote']) ? $youngo_item['quote'] : ''); ?></blockquote>
                        <div class="youngo-person">
                            <span aria-hidden="true"></span>
                            <div>
                                <strong><?php echo youngo_homepage_e(isset($youngo_item['name']) ? $youngo_item['name'] : 'YounGo parent'); ?></strong>
                                <small><?php echo youngo_homepage_e(isset($youngo_item['role']) ? $youngo_item['role'] : 'Parent'); ?></small>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php elseif ($youngo_section_type === 'faq_block'): ?>
    <section class="youngo-section youngo-faq">
        <div class="youngo-container youngo-container--narrow">
            <div class="youngo-section__heading youngo-section__heading--center">
                <p class="youngo-eyebrow"><?php echo youngo_homepage_e(isset($youngo_section['label']) ? $youngo_section['label'] : 'FAQ'); ?></p>
                <h2><?php echo youngo_homepage_e($youngo_section_title); ?></h2>
                <?php if ($youngo_section_subtitle !== ''): ?><p><?php echo youngo_homepage_e($youngo_section_subtitle); ?></p><?php endif; ?>
            </div>
            <div class="youngo-faq-list">
                <?php foreach ($youngo_section_items as $index => $youngo_item): ?>
                    <details class="youngo-faq-item" <?php echo $index === 0 ? 'open' : ''; ?>>
                        <summary>
                            <span><?php echo youngo_homepage_e(isset($youngo_item['question']) ? $youngo_item['question'] : 'Question'); ?></span>
                            <span class="youngo-faq-icon" aria-hidden="true"></span>
                        </summary>
                        <p><?php echo youngo_homepage_e(isset($youngo_item['answer']) ? $youngo_item['answer'] : ''); ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($youngo_section_cta['label'])): ?>
                <div class="youngo-section__actions">
                    <a class="youngo-text-link" href="<?php echo youngo_homepage_link(isset($youngo_section_cta['url']) ? $youngo_section_cta['url'] : 'home'); ?>"><?php echo youngo_homepage_e($youngo_section_cta['label']); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php elseif ($youngo_section_type === 'course_highlight' || $youngo_section_type === 'category_highlight'): ?>
    <?php
        $youngo_ids = $youngo_section_type === 'course_highlight'
            ? (isset($youngo_section_content['course_ids']) && is_array($youngo_section_content['course_ids']) ? $youngo_section_content['course_ids'] : array())
            : (isset($youngo_section_content['category_ids']) && is_array($youngo_section_content['category_ids']) ? $youngo_section_content['category_ids'] : array());
    ?>
    <section class="youngo-section youngo-section--white">
        <div class="youngo-container">
            <div class="youngo-section__heading youngo-section__heading--split">
                <div>
                    <p class="youngo-eyebrow"><?php echo youngo_homepage_e(isset($youngo_section['label']) ? $youngo_section['label'] : 'YounGo'); ?></p>
                    <h2><?php echo youngo_homepage_e($youngo_section_title); ?></h2>
                    <?php if ($youngo_section_subtitle !== ''): ?><p><?php echo youngo_homepage_e($youngo_section_subtitle); ?></p><?php endif; ?>
                </div>
                <?php if (!empty($youngo_section_cta['label'])): ?><a class="youngo-text-link" href="<?php echo youngo_homepage_link(isset($youngo_section_cta['url']) ? $youngo_section_cta['url'] : 'home/courses'); ?>"><?php echo youngo_homepage_e($youngo_section_cta['label']); ?></a><?php endif; ?>
            </div>
            <?php if (!empty($youngo_ids)): ?>
                <div class="youngo-category-grid">
                    <?php foreach ($youngo_ids as $youngo_id): ?>
                        <article class="youngo-category-card">
                            <span class="youngo-icon-badge" aria-hidden="true"><?php echo youngo_homepage_e($youngo_section_type === 'course_highlight' ? 'CR' : 'CA'); ?></span>
                            <strong><?php echo youngo_homepage_e($youngo_section_type === 'course_highlight' ? 'Course #' . (int) $youngo_id : 'Category #' . (int) $youngo_id); ?></strong>
                            <small><?php echo youngo_homepage_e('Selected in CMS'); ?></small>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
