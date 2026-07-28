<?php if (get_frontend_settings('recaptcha_status') || get_frontend_settings('recaptcha_status_v3')): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
<?php
$youngo_change_password_language = function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english';
$youngo_change_password_login_url = function_exists('youngo_frontend_login_url') ? youngo_frontend_login_url($youngo_change_password_language) : site_url('login');
?>

<section class="youngo-auth">
    <div class="youngo-container youngo-auth__grid">
        <div class="youngo-auth__intro">
            <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase_e('account_security'); ?></p>
            <h1><?php echo youngo_frontend_phrase_e('change_password'); ?></h1>
            <p><?php echo youngo_frontend_phrase_e('change_your_password_to_secure_your_account'); ?></p>
        </div>

        <div class="youngo-auth-card">
            <?php
                $youngo_auth_messages = array(
                    'flash_message' => 'success',
                    'error_message' => 'error',
                    'info_message' => 'info',
                );
            ?>
            <?php foreach ($youngo_auth_messages as $message_key => $message_type): ?>
                <?php if ($this->session->flashdata($message_key) != ''): ?>
                    <div class="youngo-auth-alert youngo-auth-alert--<?php echo $message_type; ?>">
                        <?php echo $this->session->flashdata($message_key); ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <h2><?php echo youngo_frontend_phrase_e('change_password'); ?></h2>

            <form class="youngo-auth-form" action="<?php echo site_url('login/change_password/' . $verification_code); ?>" method="post">
                <div class="youngo-field">
                    <label for="new_password"><?php echo youngo_frontend_phrase_e('new_password'); ?></label>
                    <input id="new_password" type="password" name="new_password" placeholder="<?php echo youngo_frontend_phrase_e('enter_a_new_password'); ?>" autocomplete="new-password">
                </div>

                <div class="youngo-field">
                    <label for="confirm_password"><?php echo youngo_frontend_phrase_e('confirm_your_new_password'); ?></label>
                    <input id="confirm_password" type="password" name="confirm_password" placeholder="<?php echo youngo_frontend_phrase_e('retype_your_new_password'); ?>" autocomplete="new-password">
                </div>

                <?php if (get_frontend_settings('recaptcha_status')): ?>
                    <div class="youngo-auth__recaptcha">
                        <div class="g-recaptcha" data-sitekey="<?php echo get_frontend_settings('recaptcha_sitekey'); ?>"></div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="youngo-button"><?php echo youngo_frontend_phrase_e('continue'); ?></button>
            </form>

            <p class="youngo-auth-switch">
                <a href="<?php echo $youngo_change_password_login_url; ?>"><?php echo youngo_frontend_phrase_e('back_to_login'); ?></a>
            </p>
        </div>
    </div>
</section>
