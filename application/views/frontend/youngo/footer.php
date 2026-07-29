<?php
if (file_exists(APPPATH . 'helpers/youngo_frontend_language_helper.php')) {
    $this->load->helper('youngo_frontend_language');
}

$youngo_footer_language = function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english';
$youngo_footer_home_url = function_exists('youngo_frontend_home_url') ? youngo_frontend_home_url($youngo_footer_language) : site_url('home');
$youngo_footer_courses_url = function_exists('youngo_frontend_courses_url') ? youngo_frontend_courses_url($youngo_footer_language) : site_url('home/courses');
$youngo_footer_subscriptions_url = function_exists('youngo_frontend_subscriptions_url') ? youngo_frontend_subscriptions_url($youngo_footer_language) : site_url('subscriptions');
$youngo_footer_blog_url = function_exists('youngo_frontend_blog_url') ? youngo_frontend_blog_url($youngo_footer_language) : site_url('blog');
$youngo_footer_contact_url = function_exists('youngo_frontend_contact_url') ? youngo_frontend_contact_url($youngo_footer_language) : site_url('contact');
$youngo_footer_whatsapp_number = function_exists('youngo_whatsapp_visible_number') ? youngo_whatsapp_visible_number() : '';
$youngo_footer_whatsapp_url = function_exists('youngo_whatsapp_url') ? youngo_whatsapp_url($youngo_footer_whatsapp_number) : '';
?>

<footer class="youngo-footer">
    <div class="youngo-container youngo-footer__inner">
        <div class="youngo-footer__brand">
            <a href="<?php echo htmlspecialchars($youngo_footer_home_url, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo get_settings('system_name'); ?>">
                <img src="<?php echo base_url('assets/frontend/youngo/images/logo_small_c.png'); ?>" alt="<?php echo get_settings('system_name'); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                <span class="youngo-logo-fallback"><?php echo get_settings('system_name'); ?></span>
            </a>
            <p><?php echo youngo_frontend_phrase_e('safe,_joyful_online_learning_for_curious_kids_and_confident_families.', 'Safe, joyful online learning for curious kids and confident families.', $youngo_footer_language); ?></p>
        </div>
        <nav class="youngo-footer__links" aria-label="<?php echo youngo_frontend_phrase_e('footer_navigation', 'Footer navigation', $youngo_footer_language); ?>">
            <a href="<?php echo htmlspecialchars($youngo_footer_courses_url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo youngo_frontend_phrase_e('courses', 'Courses', $youngo_footer_language); ?></a>
            <a href="<?php echo htmlspecialchars($youngo_footer_subscriptions_url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo youngo_frontend_phrase_e('subscriptions', 'Subscriptions', $youngo_footer_language); ?></a>
            <a href="<?php echo htmlspecialchars($youngo_footer_blog_url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo youngo_frontend_phrase_e('blog', 'Blog', $youngo_footer_language); ?></a>
            <a href="<?php echo htmlspecialchars($youngo_footer_contact_url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo youngo_frontend_phrase_e('contact', 'Contact', $youngo_footer_language); ?></a>
        </nav>
        <p class="youngo-footer__copy">&copy; <?php echo date('Y'); ?> <?php echo get_settings('system_name'); ?></p>
    </div>
</footer>

<?php if ($youngo_footer_whatsapp_url !== ''): ?>
    <a class="youngo-whatsapp-float" href="<?php echo htmlspecialchars($youngo_footer_whatsapp_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" aria-label="<?php echo youngo_frontend_phrase_e('open_whatsapp', 'Open WhatsApp', $youngo_footer_language); ?>" title="<?php echo youngo_frontend_phrase_e('whatsapp', 'WhatsApp', $youngo_footer_language); ?>">
        <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
    </a>
<?php endif; ?>
