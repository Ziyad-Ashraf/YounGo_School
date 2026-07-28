<?php if (get_frontend_settings('recaptcha_status') || get_frontend_settings('recaptcha_status_v3')): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
<?php
$youngo_forgot_language = function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english';
$youngo_forgot_login_url = function_exists('youngo_frontend_login_url') ? youngo_frontend_login_url($youngo_forgot_language) : site_url('login');
?>

<section class="youngo-auth">
    <div class="youngo-container youngo-auth__grid">
        <div class="youngo-auth__intro">
            <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase_e('account_help'); ?></p>
            <h1><?php echo youngo_frontend_phrase_e('forgot_password'); ?></h1>
            <p><?php echo youngo_frontend_phrase_e('enter_your_email_and_we_will_send_the_next_step_to_help_secure_your_account.'); ?></p>
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

            <h2><?php echo youngo_frontend_phrase_e('forgot_password'); ?></h2>
            <p class="youngo-auth-card__copy"><?php echo youngo_frontend_phrase_e('we_will_use_your_email_to_find_your_account.'); ?></p>

            <form class="youngo-auth-form" action="<?php echo site_url('login/forgot_password/frontend'); ?>" method="post" id="forgot-password">
                <div class="youngo-field">
                    <label for="email"><?php echo youngo_frontend_phrase_e('your_email'); ?></label>
                    <input id="email" type="email" name="email" placeholder="<?php echo youngo_frontend_phrase_e('enter_your_email'); ?>" autocomplete="email">
                </div>

                <?php if (get_frontend_settings('recaptcha_status')): ?>
                    <div class="youngo-auth__recaptcha">
                        <div class="g-recaptcha" data-sitekey="<?php echo get_frontend_settings('recaptcha_sitekey'); ?>"></div>
                    </div>
                <?php endif; ?>

                <?php if (get_frontend_settings('recaptcha_status_v3')): ?>
                    <button class="youngo-button g-recaptcha" data-sitekey="<?php echo get_frontend_settings('recaptcha_sitekey_v3'); ?>" data-callback="onForgetSubmit" data-action="submit">
                        <?php echo youngo_frontend_phrase_e('send_request'); ?>
                    </button>
                <?php else: ?>
                    <button type="submit" class="youngo-button"><?php echo youngo_frontend_phrase_e('send_request'); ?></button>
                <?php endif; ?>
            </form>

            <p class="youngo-auth-switch">
                <a href="<?php echo $youngo_forgot_login_url; ?>"><?php echo youngo_frontend_phrase_e('back_to_login'); ?></a>
            </p>
        </div>
    </div>
</section>

<script>
    function onForgetSubmit() {
        document.getElementById('forgot-password').submit();
    }
</script>
