<?php
    $youngo_section_content = is_array($youngo_section_content) ? $youngo_section_content : array();
    $youngo_benefits = isset($youngo_section_content['items']) && is_array($youngo_section_content['items']) ? $youngo_section_content['items'] : array();
?>

<section class="youngo-section youngo-section--trust">
    <div class="youngo-container">
        <div class="youngo-section__heading youngo-section__heading--center">
            <p class="youngo-eyebrow"><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('Why families choose YounGo') : 'Why families choose YounGo'); ?></p>
            <h2><?php echo youngo_homepage_e(isset($youngo_section_content['title']) ? $youngo_section_content['title'] : 'Trusted learning that still feels joyful'); ?></h2>
            <p><?php echo youngo_homepage_e(isset($youngo_section_content['subtitle']) ? $youngo_section_content['subtitle'] : 'Built to feel safe for parents, friendly for kids, and practical for everyday learning.'); ?></p>
        </div>

        <div class="youngo-trust-layout">
            <div class="youngo-trust-panel">
                <span class="youngo-trust-panel__label"><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('Parent view') : 'Parent view'); ?></span>
                <strong><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('Clear courses, calm navigation, and child-friendly topics.') : 'Clear courses, calm navigation, and child-friendly topics.'); ?></strong>
                <p><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('YounGo is designed to make course discovery feel reassuring before a family ever opens a lesson.') : 'YounGo is designed to make course discovery feel reassuring before a family ever opens a lesson.'); ?></p>
            </div>
            <div class="youngo-benefit-grid">
            <?php foreach ($youngo_benefits as $index => $youngo_benefit): ?>
                <article class="youngo-benefit-card">
                    <span class="youngo-number-badge"><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text(isset($youngo_benefit['icon_key']) && $youngo_benefit['icon_key'] !== '' ? $youngo_benefit['icon_key'] : sprintf('%02d', $index + 1)) : (isset($youngo_benefit['icon_key']) && $youngo_benefit['icon_key'] !== '' ? $youngo_benefit['icon_key'] : sprintf('%02d', $index + 1))); ?></span>
                    <h3><?php echo youngo_homepage_e(isset($youngo_benefit['title']) ? $youngo_benefit['title'] : 'YounGo benefit'); ?></h3>
                    <p><?php echo youngo_homepage_e(isset($youngo_benefit['description']) ? $youngo_benefit['description'] : 'A safe and friendly learning experience for kids.'); ?></p>
                </article>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
