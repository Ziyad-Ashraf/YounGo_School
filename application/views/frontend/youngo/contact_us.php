<?php
if (file_exists(APPPATH . 'helpers/youngo_frontend_language_helper.php')) {
    $this->load->helper('youngo_frontend_language');
}

if (!function_exists('youngo_contact_e')) {
    function youngo_contact_e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('youngo_contact_first_line')) {
    function youngo_contact_first_line($value)
    {
        $lines = preg_split('/\r\n|\r|\n/', trim((string) $value));
        return isset($lines[0]) ? trim($lines[0]) : '';
    }
}

$youngo_contact_language = function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english';
$youngo_contact_home_url = function_exists('youngo_frontend_home_url') ? youngo_frontend_home_url($youngo_contact_language) : site_url('home');
$youngo_contact_courses_url = function_exists('youngo_frontend_courses_url') ? youngo_frontend_courses_url($youngo_contact_language) : site_url('home/courses');
$youngo_contact_info = function_exists('youngo_contact_info') ? youngo_contact_info() : array();

$youngo_contact_email = !empty($youngo_contact_info['email']) ? $youngo_contact_info['email'] : get_settings('system_email');
$youngo_contact_phone = !empty($youngo_contact_info['phone']) ? $youngo_contact_info['phone'] : get_settings('phone');
$youngo_contact_whatsapp = !empty($youngo_contact_info['whatsapp_number']) ? $youngo_contact_info['whatsapp_number'] : '';
$youngo_contact_whatsapp_url = function_exists('youngo_whatsapp_url') ? youngo_whatsapp_url($youngo_contact_whatsapp) : '';
$youngo_contact_address = !empty($youngo_contact_info['address']) ? $youngo_contact_info['address'] : get_settings('address');
$youngo_contact_hours = !empty($youngo_contact_info['office_hours']) ? $youngo_contact_info['office_hours'] : '10:00 AM - 6:00 PM';
$youngo_contact_email_line = youngo_contact_first_line($youngo_contact_email);
$youngo_contact_mailto = $youngo_contact_email_line !== '' ? 'mailto:' . str_replace(array("\r", "\n"), '', $youngo_contact_email_line) : '#';
$youngo_contact_socials = array(
    'facebook' => array('label' => 'Facebook', 'icon' => 'fa-brands fa-facebook-f', 'url' => get_frontend_settings('facebook')),
    'twitter' => array('label' => 'Twitter', 'icon' => 'fa-brands fa-x-twitter', 'url' => get_frontend_settings('twitter')),
    'linkedin' => array('label' => 'LinkedIn', 'icon' => 'fa-brands fa-linkedin-in', 'url' => get_frontend_settings('linkedin')),
);

$youngo_contact_cards = array(
    array('key' => 'email', 'label' => 'Email', 'icon' => 'fa-regular fa-envelope', 'value' => $youngo_contact_email, 'url' => $youngo_contact_mailto),
    array('key' => 'phone', 'label' => 'Phone', 'icon' => 'fa-solid fa-phone', 'value' => $youngo_contact_phone, 'url' => ''),
    array('key' => 'whatsapp', 'label' => 'WhatsApp', 'icon' => 'fa-brands fa-whatsapp', 'value' => $youngo_contact_whatsapp, 'url' => $youngo_contact_whatsapp_url),
    array('key' => 'address', 'label' => 'Address', 'icon' => 'fa-solid fa-location-dot', 'value' => $youngo_contact_address, 'url' => ''),
    array('key' => 'working_hours', 'label' => 'Working hours', 'icon' => 'fa-regular fa-clock', 'value' => $youngo_contact_hours, 'url' => ''),
);
?>

<section class="youngo-courses-hero">
    <div class="youngo-container">
        <nav class="youngo-breadcrumb" aria-label="<?php echo youngo_contact_e(youngo_frontend_phrase('breadcrumb', $youngo_contact_language)); ?>">
            <a href="<?php echo youngo_contact_e($youngo_contact_home_url); ?>"><?php echo youngo_contact_e(youngo_frontend_phrase('home', $youngo_contact_language)); ?></a>
            <span>/</span>
            <span><?php echo youngo_contact_e(youngo_frontend_phrase('contact_us', $youngo_contact_language)); ?></span>
        </nav>

        <div class="youngo-courses-hero__grid">
            <div>
                <p class="youngo-eyebrow"><?php echo youngo_contact_e(youngo_frontend_phrase('contact_us', $youngo_contact_language)); ?></p>
                <h1><?php echo youngo_contact_e(youngo_frontend_phrase('get_in_touch', $youngo_contact_language)); ?></h1>
                <p><?php echo youngo_contact_e(youngo_frontend_phrase('have_a_question_about_youngo_programs?_we_would_be_happy_to_hear_from_you.', $youngo_contact_language)); ?></p>
            </div>
            <div class="youngo-courses-hero__stats" aria-label="<?php echo youngo_contact_e(youngo_frontend_phrase('contact_us', $youngo_contact_language)); ?>">
                <strong><i class="fa-regular fa-message"></i></strong>
                <span><?php echo youngo_contact_e(get_settings('system_name')); ?></span>
            </div>
        </div>
    </div>
</section>

<section class="youngo-section youngo-section--white">
    <div class="youngo-container">
        <div class="youngo-placeholder__grid">
            <div>
                <div class="youngo-section__heading">
                    <p class="youngo-eyebrow"><?php echo youngo_contact_e(youngo_frontend_phrase('get_in_touch', $youngo_contact_language)); ?></p>
                    <h2><?php echo youngo_contact_e(youngo_frontend_phrase('contact_us', $youngo_contact_language)); ?></h2>
                    <p><?php echo youngo_contact_e(youngo_frontend_phrase('have_a_question_about_youngo_programs?_we_would_be_happy_to_hear_from_you.', $youngo_contact_language)); ?></p>
                </div>

                <div class="youngo-blog-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
                    <?php foreach ($youngo_contact_cards as $card): ?>
                        <?php if (trim((string) $card['value']) === '') continue; ?>
                        <article class="youngo-placeholder__panel">
                            <span><i class="<?php echo youngo_contact_e($card['icon']); ?>"></i> <?php echo youngo_contact_e(youngo_frontend_phrase($card['key'], $card['label'], $youngo_contact_language)); ?></span>
                            <?php if (!empty($card['url'])): ?>
                                <strong><a class="youngo-contact-link" href="<?php echo youngo_contact_e($card['url']); ?>"<?php echo $card['key'] === 'whatsapp' ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo nl2br(youngo_contact_e($card['value'])); ?></a></strong>
                            <?php else: ?>
                                <strong><?php echo nl2br(youngo_contact_e($card['value'])); ?></strong>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php
                $youngo_contact_active_socials = array();
                foreach ($youngo_contact_socials as $social) {
                    $social_url = trim((string) $social['url']);
                    if ($social_url !== '' && !in_array(strtolower(rtrim($social_url, '/')), array('https://facebook.com', 'http://facebook.com', 'https://twitter.com', 'http://twitter.com'), true)) {
                        $youngo_contact_active_socials[] = $social;
                    }
                }
                ?>
                <?php if (!empty($youngo_contact_active_socials)): ?>
                    <div class="youngo-section__actions" style="justify-content:flex-start;">
                        <span class="youngo-eyebrow" style="margin:0;"><?php echo youngo_contact_e(youngo_frontend_phrase('follow_us', $youngo_contact_language)); ?></span>
                        <?php foreach ($youngo_contact_active_socials as $social): ?>
                            <a class="youngo-button youngo-button--secondary youngo-button--small" href="<?php echo youngo_contact_e($social['url']); ?>" target="_blank" rel="noopener">
                                <i class="<?php echo youngo_contact_e($social['icon']); ?>"></i>
                                <?php echo youngo_contact_e($social['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="youngo-contact-panel">
                <span class="youngo-eyebrow"><?php echo youngo_contact_e(youngo_frontend_phrase('contact_us', $youngo_contact_language)); ?></span>
                <strong><?php echo youngo_contact_e(youngo_frontend_phrase('ready_to_talk_about_the_right_learning_path?', $youngo_contact_language)); ?></strong>
                <p><?php echo youngo_contact_e(youngo_frontend_phrase('send_us_an_email_and_the_youngo_team_will_help_you_choose_a_good_starting_point.', $youngo_contact_language)); ?></p>

                <div class="youngo-contact-panel__note">
                    <i class="fa-regular fa-envelope"></i>
                    <span><?php echo youngo_contact_e($youngo_contact_email_line); ?></span>
                </div>

                <?php if ($youngo_contact_email_line !== ''): ?>
                    <a class="youngo-button" href="<?php echo youngo_contact_e($youngo_contact_mailto); ?>">
                        <i class="fa-regular fa-envelope"></i>
                        <?php echo youngo_contact_e(youngo_frontend_phrase('contact_us_by_email', $youngo_contact_language)); ?>
                    </a>
                <?php else: ?>
                    <a class="youngo-button" href="<?php echo youngo_contact_e($youngo_contact_courses_url); ?>"><?php echo youngo_contact_e(youngo_frontend_phrase('browse_courses', $youngo_contact_language)); ?></a>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</section>
