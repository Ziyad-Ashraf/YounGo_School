<?php
    $youngo_section_content = is_array($youngo_section_content) ? $youngo_section_content : array();
    $youngo_primary_cta = isset($youngo_section_content['primary_cta']) && is_array($youngo_section_content['primary_cta']) ? $youngo_section_content['primary_cta'] : array();
    $youngo_secondary_cta = isset($youngo_section_content['secondary_cta']) && is_array($youngo_section_content['secondary_cta']) ? $youngo_section_content['secondary_cta'] : array();
    $youngo_hero_image = isset($youngo_section_content['image']) && is_array($youngo_section_content['image']) ? $youngo_section_content['image'] : array();
    $youngo_trust_items = isset($youngo_section_content['trust_items']) && is_array($youngo_section_content['trust_items']) ? $youngo_section_content['trust_items'] : array();
    $youngo_stats = isset($youngo_section_content['stats']) && is_array($youngo_section_content['stats']) ? $youngo_section_content['stats'] : array();
?>

<section class="youngo-hero">
    <div class="youngo-container youngo-hero__grid">
        <div class="youngo-hero__content">
            <p class="youngo-eyebrow"><?php echo youngo_homepage_e(isset($youngo_section_content['eyebrow']) ? $youngo_section_content['eyebrow'] : 'Safe, creative learning for kids'); ?></p>
            <h1><?php echo youngo_homepage_e(isset($youngo_section_content['title']) ? $youngo_section_content['title'] : "Spark your child's imagination with YounGo"); ?></h1>
            <p class="youngo-hero__lead"><?php echo youngo_homepage_e(isset($youngo_section_content['subtitle']) ? $youngo_section_content['subtitle'] : 'A friendly learning platform where children explore useful skills through structured, playful courses that parents can trust.'); ?></p>
            <div class="youngo-actions">
                <a class="youngo-button" href="<?php echo youngo_homepage_link(isset($youngo_primary_cta['url']) ? $youngo_primary_cta['url'] : 'home/courses'); ?>"><?php echo youngo_homepage_e(isset($youngo_primary_cta['label']) ? $youngo_primary_cta['label'] : 'Explore Courses'); ?></a>
                <a class="youngo-button youngo-button--secondary" href="<?php echo youngo_homepage_link(isset($youngo_secondary_cta['url']) ? $youngo_secondary_cta['url'] : 'home/contact_us'); ?>"><?php echo youngo_homepage_e(isset($youngo_secondary_cta['label']) ? $youngo_secondary_cta['label'] : 'Talk to Us'); ?></a>
            </div>
            <div class="youngo-trust-row" aria-label="<?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('YounGo highlights') : 'YounGo highlights'); ?>">
                <?php foreach ($youngo_trust_items as $youngo_trust_item): ?>
                    <span><?php echo youngo_homepage_e(is_array($youngo_trust_item) && isset($youngo_trust_item['label']) ? $youngo_trust_item['label'] : $youngo_trust_item); ?></span>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($youngo_stats)): ?>
                <div class="youngo-hero-stats" aria-label="<?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('YounGo learning stats') : 'YounGo learning stats'); ?>">
                    <?php foreach ($youngo_stats as $youngo_stat): ?>
                        <div>
                            <strong><?php echo youngo_homepage_e(isset($youngo_stat['value']) ? $youngo_stat['value'] : ''); ?></strong>
                            <span><?php echo youngo_homepage_e(isset($youngo_stat['label']) ? $youngo_stat['label'] : ''); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="youngo-hero__visual">
            <img src="<?php echo youngo_homepage_image_url(isset($youngo_hero_image['url']) ? $youngo_hero_image['url'] : '', 'assets/frontend/youngo/images/demo-hero-learning.jpg'); ?>" alt="<?php echo youngo_homepage_e(isset($youngo_hero_image['alt']) ? $youngo_hero_image['alt'] : 'Children learning coding and robotics with a YounGo instructor'); ?>">
            <div class="youngo-hero-card youngo-hero-card--top">
                <span><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('Today') : 'Today'); ?></span>
                <strong><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('Build a project') : 'Build a project'); ?></strong>
            </div>
            <div class="youngo-hero-card youngo-hero-card--bottom">
                <span><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('For families') : 'For families'); ?></span>
                <strong><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('Clear paths') : 'Clear paths'); ?></strong>
            </div>
        </div>
    </div>
</section>
