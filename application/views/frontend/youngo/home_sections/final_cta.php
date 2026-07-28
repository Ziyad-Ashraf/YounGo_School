<?php
    $youngo_section_content = is_array($youngo_section_content) ? $youngo_section_content : array();
    $youngo_primary_cta = isset($youngo_section_content['primary_cta']) && is_array($youngo_section_content['primary_cta']) ? $youngo_section_content['primary_cta'] : array('label' => 'Explore Courses', 'url' => 'home/courses');
    $youngo_secondary_cta = isset($youngo_section_content['secondary_cta']) && is_array($youngo_section_content['secondary_cta']) ? $youngo_section_content['secondary_cta'] : array('label' => 'Contact Us', 'url' => 'home/contact_us');
?>

<section class="youngo-section youngo-final-cta">
    <div class="youngo-container">
        <div class="youngo-final-cta__panel">
            <p class="youngo-eyebrow"><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('Start learning') : 'Start learning'); ?></p>
            <h2><?php echo youngo_homepage_e(isset($youngo_section_content['title']) ? $youngo_section_content['title'] : "Ready to start your child's learning journey?"); ?></h2>
            <p><?php echo youngo_homepage_e(isset($youngo_section_content['subtitle']) ? $youngo_section_content['subtitle'] : 'Explore safe, creative, and engaging courses built for young learners and the families supporting them.'); ?></p>
            <div class="youngo-actions youngo-actions--center">
                <a class="youngo-button youngo-button--light" href="<?php echo youngo_homepage_link(isset($youngo_primary_cta['url']) ? $youngo_primary_cta['url'] : 'home/courses'); ?>"><?php echo youngo_homepage_e(isset($youngo_primary_cta['label']) ? $youngo_primary_cta['label'] : 'Explore Courses'); ?></a>
                <a class="youngo-button youngo-button--ghost-light" href="<?php echo youngo_homepage_link(isset($youngo_secondary_cta['url']) ? $youngo_secondary_cta['url'] : 'home/contact_us'); ?>"><?php echo youngo_homepage_e(isset($youngo_secondary_cta['label']) ? $youngo_secondary_cta['label'] : 'Contact Us'); ?></a>
            </div>
        </div>
    </div>
</section>
