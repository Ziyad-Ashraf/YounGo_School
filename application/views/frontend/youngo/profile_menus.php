<?php
$youngo_account_user_id = (int) $this->session->userdata('user_id');
$youngo_account_user = $youngo_account_user_id > 0 ? $this->user_model->get_all_user($youngo_account_user_id)->row_array() : array();
$youngo_account_name = trim((isset($youngo_account_user['first_name']) ? $youngo_account_user['first_name'] : '') . ' ' . (isset($youngo_account_user['last_name']) ? $youngo_account_user['last_name'] : ''));
if ($youngo_account_name === '') {
    $youngo_account_name = 'YounGo learner';
}
$youngo_account_email = isset($youngo_account_user['email']) ? $youngo_account_user['email'] : '';
$youngo_account_language = function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english';
$youngo_account_my_courses_url = function_exists('youngo_frontend_my_courses_url') ? youngo_frontend_my_courses_url($youngo_account_language) : site_url('home/my_courses');
$youngo_account_my_access_url = function_exists('youngo_frontend_my_access_url') ? youngo_frontend_my_access_url($youngo_account_language) : site_url('home/my_access');
$youngo_account_wishlist_url = function_exists('youngo_frontend_wishlist_url') ? youngo_frontend_wishlist_url($youngo_account_language) : site_url('home/my_wishlist');

if (!function_exists('youngo_account_menu_active')) {
    function youngo_account_menu_active($current_page, $pages) {
        return in_array($current_page, (array) $pages, true) ? ' is-active' : '';
    }
}
?>

<aside class="youngo-account-menu" aria-label="<?php echo youngo_frontend_phrase('account_navigation'); ?>">
    <div class="youngo-account-menu__profile">
        <img loading="lazy" src="<?php echo $this->user_model->get_user_image_url($youngo_account_user_id); ?>" alt="<?php echo htmlspecialchars($youngo_account_name, ENT_QUOTES, 'UTF-8'); ?>">
        <div>
            <span><?php echo youngo_frontend_phrase('signed_in_as'); ?></span>
            <strong><?php echo htmlspecialchars($youngo_account_name, ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php if ($youngo_account_email !== ''): ?>
                <small><?php echo htmlspecialchars($youngo_account_email, ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>
    </div>

    <nav class="youngo-account-menu__links">
        <a class="youngo-account-menu__link<?php echo youngo_account_menu_active($page_name, array('my_courses')); ?>" href="<?php echo $youngo_account_my_courses_url; ?>">
            <i class="fa-regular fa-circle-play"></i>
            <span><?php echo youngo_frontend_phrase('my_courses'); ?></span>
        </a>
        <a class="youngo-account-menu__link<?php echo youngo_account_menu_active($page_name, array('my_access')); ?>" href="<?php echo $youngo_account_my_access_url; ?>">
            <i class="fa-regular fa-id-badge"></i>
            <span><?php echo youngo_frontend_phrase('my_access'); ?></span>
        </a>
        <a class="youngo-account-menu__link<?php echo youngo_account_menu_active($page_name, array('my_wishlist')); ?>" href="<?php echo $youngo_account_wishlist_url; ?>">
            <i class="fa-regular fa-heart"></i>
            <span><?php echo youngo_frontend_phrase('my_wishlist'); ?></span>
        </a>
        <a class="youngo-account-menu__link<?php echo youngo_account_menu_active($page_name, array('purchase_history', 'invoice')); ?>" href="<?php echo site_url('home/purchase_history'); ?>">
            <i class="fa-solid fa-receipt"></i>
            <span><?php echo youngo_frontend_phrase('purchase_history'); ?></span>
        </a>
        <a class="youngo-account-menu__link<?php echo youngo_account_menu_active($page_name, array('user_profile')); ?>" href="<?php echo site_url('home/profile/user_profile'); ?>">
            <i class="fa-regular fa-user"></i>
            <span><?php echo youngo_frontend_phrase('Profile'); ?></span>
        </a>
        <a class="youngo-account-menu__link<?php echo youngo_account_menu_active($page_name, array('update_user_photo')); ?>" href="<?php echo site_url('home/profile/user_photo'); ?>">
            <i class="fa-regular fa-image"></i>
            <span><?php echo youngo_frontend_phrase('profile_photo'); ?></span>
        </a>
        <a class="youngo-account-menu__link<?php echo youngo_account_menu_active($page_name, array('user_credentials')); ?>" href="<?php echo site_url('home/profile/user_credentials'); ?>">
            <i class="fa-solid fa-key"></i>
            <span><?php echo youngo_frontend_phrase('Account'); ?></span>
        </a>
        <a class="youngo-account-menu__link" href="<?php echo site_url('login/logout'); ?>">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span><?php echo youngo_frontend_phrase('Logout'); ?></span>
        </a>
    </nav>
</aside>
