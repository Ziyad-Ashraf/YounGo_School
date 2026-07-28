<?php
    $youngo_section_content = is_array($youngo_section_content) ? $youngo_section_content : array();
    $youngo_items = isset($youngo_section_content['items']) && is_array($youngo_section_content['items']) ? $youngo_section_content['items'] : array();
    $youngo_cta = isset($youngo_section_content['cta']) && is_array($youngo_section_content['cta']) ? $youngo_section_content['cta'] : array('label' => 'About YounGo', 'url' => 'home/about_us');
    $youngo_about_image = isset($youngo_section_content['image']) && is_array($youngo_section_content['image']) ? $youngo_section_content['image'] : array();
    $youngo_about_stats = isset($youngo_section_content['stats']) && is_array($youngo_section_content['stats']) ? $youngo_section_content['stats'] : array();
?>

<section class="youngo-section youngo-about">
    <div class="youngo-container youngo-about__grid">
        <div class="youngo-about__visual">
            <img src="<?php echo youngo_homepage_image_url(isset($youngo_about_image['url']) ? $youngo_about_image['url'] : '', 'assets/frontend/youngo/images/demo-family-project.jpg'); ?>" alt="<?php echo youngo_homepage_e(isset($youngo_about_image['alt']) && $youngo_about_image['alt'] !== '' ? $youngo_about_image['alt'] : 'Family learning with YounGo'); ?>">
            <?php if (!empty($youngo_about_stats)): ?>
                <div class="youngo-about-stats">
                    <?php foreach ($youngo_about_stats as $youngo_stat): ?>
                        <div>
                            <strong><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text(isset($youngo_stat['value']) ? $youngo_stat['value'] : '') : (isset($youngo_stat['value']) ? $youngo_stat['value'] : '')); ?></strong>
                            <span><?php echo youngo_homepage_e(isset($youngo_stat['label']) ? $youngo_stat['label'] : ''); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <p class="youngo-eyebrow"><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('About YounGo') : 'About YounGo'); ?></p>
            <h2><?php echo youngo_homepage_e(isset($youngo_section_content['title']) ? $youngo_section_content['title'] : 'Learning that feels friendly, safe, and exciting'); ?></h2>
            <p><?php echo youngo_homepage_e(isset($youngo_section_content['body']) ? $youngo_section_content['body'] : 'YounGo helps children explore new skills through a modern learning experience built for curiosity, confidence, and steady growth.'); ?></p>
            <ul class="youngo-check-list">
                <?php foreach ($youngo_items as $youngo_item): ?>
                    <li><?php echo youngo_homepage_e(is_array($youngo_item) && isset($youngo_item['title']) ? $youngo_item['title'] : $youngo_item); ?></li>
                <?php endforeach; ?>
            </ul>
            <div class="youngo-actions">
                <a class="youngo-button" href="<?php echo youngo_homepage_link(isset($youngo_cta['url']) ? $youngo_cta['url'] : 'home/about_us'); ?>"><?php echo youngo_homepage_e(isset($youngo_cta['label']) ? $youngo_cta['label'] : 'About YounGo'); ?></a>
            </div>
        </div>
    </div>
</section>
