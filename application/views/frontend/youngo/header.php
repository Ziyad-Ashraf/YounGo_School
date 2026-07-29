<?php
if (file_exists(APPPATH . 'helpers/youngo_frontend_language_helper.php')) {
    $this->load->helper('youngo_frontend_language');
}

$youngo_header_language = function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english';
$youngo_header_home_url = function_exists('youngo_frontend_home_url') ? youngo_frontend_home_url($youngo_header_language) : site_url('home');
$youngo_header_courses_url = function_exists('youngo_frontend_courses_url') ? youngo_frontend_courses_url($youngo_header_language) : site_url('home/courses');
$youngo_header_subscriptions_url = function_exists('youngo_frontend_subscriptions_url') ? youngo_frontend_subscriptions_url($youngo_header_language) : site_url('subscriptions');
$youngo_header_blog_url = function_exists('youngo_frontend_blog_url') ? youngo_frontend_blog_url($youngo_header_language) : site_url('blog');
$youngo_header_contact_url = function_exists('youngo_frontend_contact_url') ? youngo_frontend_contact_url($youngo_header_language) : site_url('contact');
$youngo_header_wishlist_url = function_exists('youngo_frontend_wishlist_url') ? youngo_frontend_wishlist_url($youngo_header_language) : site_url('home/my_wishlist');
$youngo_header_login_url = function_exists('youngo_frontend_login_url') ? youngo_frontend_login_url($youngo_header_language) : site_url('login');
$youngo_header_sign_up_url = function_exists('youngo_frontend_sign_up_url') ? youngo_frontend_sign_up_url($youngo_header_language) : site_url('sign_up');
$youngo_header_my_courses_url = function_exists('youngo_frontend_my_courses_url') ? youngo_frontend_my_courses_url($youngo_header_language) : site_url('home/my_courses');
$youngo_header_profile_url = site_url('home/profile/user_profile');
$youngo_header_english_url = function_exists('youngo_frontend_language_switch_url') ? youngo_frontend_language_switch_url('english') : site_url('home');
$youngo_header_arabic_url = function_exists('youngo_frontend_language_switch_url') ? youngo_frontend_language_switch_url('arabic') : site_url('');

$youngo_header_wishlist_items = array();

if ($this->session->userdata('user_login') == true && ($youngo_header_user_id = $this->session->userdata('user_id'))) {
    $youngo_header_wishlist = $this->user_model->get_all_user($youngo_header_user_id)->row('wishlist');
    if ($youngo_header_wishlist != '') {
        $youngo_header_decoded_wishlist = json_decode($youngo_header_wishlist, true);
        $youngo_header_wishlist_items = is_array($youngo_header_decoded_wishlist) ? $youngo_header_decoded_wishlist : array();
    }
}
?>

<header class="youngo-header">
    <div class="youngo-container youngo-header__inner">
        <a class="youngo-brand" href="<?php echo htmlspecialchars($youngo_header_home_url, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo get_settings('system_name'); ?>">
            <img src="<?php echo base_url('assets/frontend/youngo/images/logo_small_c.png'); ?>" alt="<?php echo get_settings('system_name'); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
            <span class="youngo-logo-fallback"><?php echo get_settings('system_name'); ?></span>
        </a>

        <button class="youngo-menu-toggle" type="button" aria-controls="youngo-primary-nav" aria-expanded="false">
            <span class="sr-only"><?php echo youngo_frontend_phrase_e('toggle_navigation', 'Toggle navigation', $youngo_header_language); ?></span>
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>

        <?php if ($this->session->userdata('user_login') == true): ?>
            <a class="youngo-mobile-account-link youngo-profile-link" href="<?php echo htmlspecialchars($youngo_header_profile_url, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo youngo_frontend_phrase_e('my_profile', 'My Profile', $youngo_header_language); ?>" title="<?php echo youngo_frontend_phrase_e('my_profile', 'My Profile', $youngo_header_language); ?>">
                <i class="fa-regular fa-user" aria-hidden="true"></i>
            </a>
        <?php else: ?>
            <a class="youngo-mobile-account-link" href="<?php echo htmlspecialchars($youngo_header_login_url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo youngo_frontend_phrase_e('login', 'Login', $youngo_header_language); ?></a>
        <?php endif; ?>

        <nav class="youngo-nav" id="youngo-primary-nav" aria-label="<?php echo youngo_frontend_phrase_e('primary_navigation', 'Primary navigation', $youngo_header_language); ?>">
            <a href="<?php echo htmlspecialchars($youngo_header_home_url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo youngo_frontend_phrase_e('home', 'Home', $youngo_header_language); ?></a>
            <a href="<?php echo htmlspecialchars($youngo_header_courses_url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo youngo_frontend_phrase_e('courses', 'Courses', $youngo_header_language); ?></a>
            <a href="<?php echo htmlspecialchars($youngo_header_subscriptions_url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo youngo_frontend_phrase_e('subscriptions', 'Subscriptions', $youngo_header_language); ?></a>
            <a href="<?php echo htmlspecialchars($youngo_header_blog_url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo youngo_frontend_phrase_e('blog', 'Blog', $youngo_header_language); ?></a>
            <a href="<?php echo htmlspecialchars($youngo_header_contact_url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo youngo_frontend_phrase_e('contact', 'Contact', $youngo_header_language); ?></a>
            <div class="youngo-nav__mobile-language">
                <a href="<?php echo htmlspecialchars($youngo_header_english_url, ENT_QUOTES, 'UTF-8'); ?>" lang="en">EN</a>
                <a href="<?php echo htmlspecialchars($youngo_header_arabic_url, ENT_QUOTES, 'UTF-8'); ?>" lang="ar" dir="rtl">العربية</a>
            </div>
            <div class="youngo-nav__mobile-account">
                <?php if ($this->session->userdata('user_login') == true): ?>
                    <a href="<?php echo htmlspecialchars($youngo_header_profile_url, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa-regular fa-user" aria-hidden="true"></i>
                        <span><?php echo youngo_frontend_phrase_e('my_profile', 'My Profile', $youngo_header_language); ?></span>
                    </a>
                    <a href="<?php echo htmlspecialchars($youngo_header_my_courses_url, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
                        <span><?php echo youngo_frontend_phrase_e('my_courses', 'My courses', $youngo_header_language); ?></span>
                    </a>
                    <a href="<?php echo htmlspecialchars($youngo_header_wishlist_url, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa-regular fa-heart" aria-hidden="true"></i>
                        <span><?php echo youngo_frontend_phrase_e('my_wishlist', 'My wishlist', $youngo_header_language); ?></span>
                    </a>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($youngo_header_login_url, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa-regular fa-user" aria-hidden="true"></i>
                        <span><?php echo youngo_frontend_phrase_e('login', 'Login', $youngo_header_language); ?></span>
                    </a>
                    <?php if (get_settings('public_signup') == 'enable'): ?>
                        <a href="<?php echo htmlspecialchars($youngo_header_sign_up_url, ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                            <span><?php echo youngo_frontend_phrase_e('sign_up', 'Sign up', $youngo_header_language); ?></span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </nav>

        <div class="youngo-header__actions">
            <nav class="youngo-language-switcher" aria-label="<?php echo youngo_frontend_phrase_e('language_switcher', 'Language switcher', $youngo_header_language); ?>">
                <a class="<?php echo $youngo_header_language === 'english' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($youngo_header_english_url, ENT_QUOTES, 'UTF-8'); ?>" lang="en" hreflang="en"<?php echo $youngo_header_language === 'english' ? ' aria-current="true"' : ''; ?>>EN</a>
                <span aria-hidden="true">|</span>
                <a class="<?php echo $youngo_header_language === 'arabic' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($youngo_header_arabic_url, ENT_QUOTES, 'UTF-8'); ?>" lang="ar" dir="rtl" hreflang="ar"<?php echo $youngo_header_language === 'arabic' ? ' aria-current="true"' : ''; ?>>&#1593;&#1585;&#1576;&#1610;</a>
            </nav>
            <?php if ($this->session->userdata('user_login') == true): ?>
                <a class="youngo-header-icon-link" href="<?php echo htmlspecialchars($youngo_header_wishlist_url, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo youngo_frontend_phrase_e('my_wishlist', 'My wishlist', $youngo_header_language); ?>">
                    <i class="fa-regular fa-heart"></i>
                    <span id="wishlistItemsCounter"><?php echo count($youngo_header_wishlist_items); ?></span>
                </a>
                <a class="youngo-header-icon-link youngo-profile-link" href="<?php echo htmlspecialchars($youngo_header_profile_url, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo youngo_frontend_phrase_e('my_profile', 'My Profile', $youngo_header_language); ?>" title="<?php echo youngo_frontend_phrase_e('my_profile', 'My Profile', $youngo_header_language); ?>">
                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                </a>
                <a class="youngo-link" href="<?php echo htmlspecialchars($youngo_header_my_courses_url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo youngo_frontend_phrase_e('my_courses', 'My courses', $youngo_header_language); ?></a>
            <?php else: ?>
                <a class="youngo-link" href="<?php echo htmlspecialchars($youngo_header_login_url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo youngo_frontend_phrase_e('login', 'Login', $youngo_header_language); ?></a>
                <?php if (get_settings('public_signup') == 'enable'): ?>
                    <a class="youngo-button youngo-button--small" href="<?php echo htmlspecialchars($youngo_header_sign_up_url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo youngo_frontend_phrase_e('sign_up', 'Sign up', $youngo_header_language); ?></a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</header>
