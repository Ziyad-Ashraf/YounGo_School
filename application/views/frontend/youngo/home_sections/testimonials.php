<?php
    $youngo_section_content = is_array($youngo_section_content) ? $youngo_section_content : array();
    $youngo_testimonials = isset($youngo_section_content['items']) && is_array($youngo_section_content['items']) ? $youngo_section_content['items'] : array();
?>

<section class="youngo-section youngo-section--soft">
    <div class="youngo-container">
        <div class="youngo-section__heading youngo-section__heading--center">
            <p class="youngo-eyebrow"><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('Testimonials') : 'Testimonials'); ?></p>
            <h2><?php echo youngo_homepage_e(isset($youngo_section_content['title']) ? $youngo_section_content['title'] : 'Happy parents and kids'); ?></h2>
            <p><?php echo youngo_homepage_e(isset($youngo_section_content['subtitle']) ? $youngo_section_content['subtitle'] : 'What families notice when learning feels safe, structured, and joyful.'); ?></p>
        </div>

        <div class="youngo-testimonial-grid">
            <?php foreach ($youngo_testimonials as $youngo_testimonial): ?>
                <article class="youngo-testimonial-card">
                    <div class="youngo-rating" aria-label="<?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('Rating') : 'Rating'); ?>"><?php echo youngo_homepage_e(isset($youngo_testimonial['rating']) ? $youngo_testimonial['rating'] : '5'); ?>/5 <?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('parent confidence') : 'parent confidence'); ?></div>
                    <blockquote><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text(isset($youngo_testimonial['quote']) ? $youngo_testimonial['quote'] : 'YounGo makes online learning feel calm, guided, and genuinely child-friendly.') : (isset($youngo_testimonial['quote']) ? $youngo_testimonial['quote'] : 'YounGo makes online learning feel calm, guided, and genuinely child-friendly.')); ?></blockquote>
                    <div class="youngo-person">
                        <span aria-hidden="true"><?php echo youngo_homepage_e(!empty($youngo_testimonial['name']) ? strtoupper(substr($youngo_testimonial['name'], 0, 1)) : 'Y'); ?></span>
                        <div>
                            <strong><?php echo youngo_homepage_e(isset($youngo_testimonial['name']) ? $youngo_testimonial['name'] : 'YounGo parent'); ?></strong>
                            <small><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text(isset($youngo_testimonial['role']) ? $youngo_testimonial['role'] : 'Parent') : (isset($youngo_testimonial['role']) ? $youngo_testimonial['role'] : 'Parent')); ?></small>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
