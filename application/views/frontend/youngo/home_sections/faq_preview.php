<?php
    $youngo_section_content = is_array($youngo_section_content) ? $youngo_section_content : array();
    $youngo_faqs = isset($youngo_section_content['items']) && is_array($youngo_section_content['items']) ? $youngo_section_content['items'] : array();
    $youngo_faq_limit = isset($youngo_section_content['limit']) ? (int) $youngo_section_content['limit'] : 3;
    $youngo_faqs = array_slice($youngo_faqs, 0, max(1, $youngo_faq_limit));
    $youngo_cta = isset($youngo_section_content['cta']) && is_array($youngo_section_content['cta']) ? $youngo_section_content['cta'] : array('label' => 'View all FAQs', 'url' => 'home/faq');
?>

<section class="youngo-section youngo-faq">
    <div class="youngo-container">
        <div class="youngo-faq__grid">
            <div class="youngo-faq__intro">
                <p class="youngo-eyebrow"><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('Parent questions') : 'Parent questions'); ?></p>
                <h2><?php echo youngo_homepage_e(isset($youngo_section_content['title']) ? $youngo_section_content['title'] : 'Questions parents often ask'); ?></h2>
                <p><?php echo youngo_homepage_e(isset($youngo_section_content['subtitle']) ? $youngo_section_content['subtitle'] : 'Quick answers before your child starts learning.'); ?></p>
                <div class="youngo-faq__support">
                    <strong><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('Need help choosing?') : 'Need help choosing?'); ?></strong>
                    <span><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text('Send a message and the YounGo team can guide families to a good starting point.') : 'Send a message and the YounGo team can guide families to a good starting point.'); ?></span>
                    <a class="youngo-text-link" href="<?php echo youngo_homepage_link(isset($youngo_cta['url']) ? $youngo_cta['url'] : 'home/contact_us'); ?>"><?php echo youngo_homepage_e(isset($youngo_cta['label']) ? $youngo_cta['label'] : 'Contact support'); ?></a>
                </div>
            </div>

            <div class="youngo-faq-list">
                <?php foreach ($youngo_faqs as $index => $youngo_faq): ?>
                    <details class="youngo-faq-item" <?php echo $index === 0 ? 'open' : ''; ?>>
                        <summary>
                            <span><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text(isset($youngo_faq['question']) ? $youngo_faq['question'] : 'How does YounGo work?') : (isset($youngo_faq['question']) ? $youngo_faq['question'] : 'How does YounGo work?')); ?></span>
                            <span class="youngo-faq-icon" aria-hidden="true"></span>
                        </summary>
                        <p><?php echo youngo_homepage_e(function_exists('youngo_frontend_text') ? youngo_frontend_text(isset($youngo_faq['answer']) ? $youngo_faq['answer'] : 'YounGo provides safe, structured learning for kids.') : (isset($youngo_faq['answer']) ? $youngo_faq['answer'] : 'YounGo provides safe, structured learning for kids.')); ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
