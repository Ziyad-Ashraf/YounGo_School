<?php
$youngo_login_confirmation_language = function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english';
$youngo_login_confirmation_login_url = function_exists('youngo_frontend_login_url') ? youngo_frontend_login_url($youngo_login_confirmation_language) : site_url('login');
?>
<section class="youngo-auth">
    <div class="youngo-container youngo-auth__grid">
        <div class="youngo-auth__intro">
            <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase_e('account_security'); ?></p>
            <h1><?php echo youngo_frontend_phrase_e('login_confirmation'); ?></h1>
            <p>
                <?php echo youngo_frontend_phrase_e('let_us_know_that_this_email_address_belongs_to_you'); ?>
                <?php echo youngo_frontend_phrase_e('enter_the_code_from_the_email_sent_to'); ?>
                <strong><?php echo htmlspecialchars((string) $this->session->userdata('new_device_user_email')); ?></strong>
            </p>
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

            <h2><?php echo youngo_frontend_phrase_e('verification_code'); ?></h2>

            <form class="youngo-auth-form" action="<?php echo site_url('login/new_login_confirmation/submit'); ?>" method="post" id="email_verification">
                <div class="youngo-field">
                    <label for="new_device_verification_code"><?php echo youngo_frontend_phrase_e('verification_code'); ?></label>
                    <input type="text" placeholder="<?php echo youngo_frontend_phrase_e('enter_the_verification_code'); ?>" aria-label="<?php echo youngo_frontend_phrase_e('new_device_verification_code'); ?>" name="new_device_verification_code" id="new_device_verification_code" required>
                </div>

                <button type="button" class="youngo-auth__small-link youngo-auth__resend" id="resend_mail_button" onclick="resend_new_device_verification_code()">
                    <?php echo youngo_frontend_phrase_e('resend_verification_code'); ?>
                    <span id="resend_mail_loader"></span>
                </button>

                <button type="submit" class="youngo-button"><?php echo youngo_frontend_phrase_e('continue'); ?></button>
            </form>

            <p class="youngo-auth-switch">
                <a href="<?php echo $youngo_login_confirmation_login_url; ?>"><?php echo youngo_frontend_phrase_e('back_to_login'); ?></a>
            </p>
        </div>
    </div>
</section>

<script>
    function resend_new_device_verification_code() {
        var loader = document.getElementById('resend_mail_loader');
        if (loader) {
            loader.textContent = '<?php echo youngo_frontend_phrase('sending'); ?>...';
        }

        fetch('<?php echo site_url('login/new_login_confirmation/resend'); ?>', {
            method: 'POST',
            credentials: 'same-origin'
        }).then(function () {
            if (loader) {
                loader.textContent = '<?php echo youngo_frontend_phrase('sent'); ?>';
            }
        }).catch(function () {
            if (loader) {
                loader.textContent = '<?php echo youngo_frontend_phrase('please_try_again'); ?>';
            }
        });
    }
</script>
