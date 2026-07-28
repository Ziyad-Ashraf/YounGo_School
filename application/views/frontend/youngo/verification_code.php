<?php if (get_frontend_settings('recaptcha_status') || get_frontend_settings('recaptcha_status_v3')): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
<?php
$youngo_verification_language = function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english';
$youngo_verification_login_url = function_exists('youngo_frontend_login_url') ? youngo_frontend_login_url($youngo_verification_language) : site_url('login');
?>

<section class="youngo-auth">
    <div class="youngo-container youngo-auth__grid">
        <div class="youngo-auth__intro">
            <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase_e('account_security'); ?></p>
            <h1><?php echo youngo_frontend_phrase_e('email_verification'); ?></h1>
            <p><?php echo youngo_frontend_phrase_e('enter_your_verification_code_here'); ?></p>
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

            <form class="youngo-auth-form" action="<?php echo site_url('login/verify_email_address'); ?>" method="post" id="verification-form">
                <div class="youngo-field">
                    <label for="verification_code"><?php echo youngo_frontend_phrase_e('verification_code'); ?></label>
                    <input id="verification_code" type="text" name="verification_code" placeholder="<?php echo youngo_frontend_phrase_e('enter_your_verification_code'); ?>">
                </div>

                <button type="button" class="youngo-auth__small-link youngo-auth__resend" id="resend_mail_button" onclick="resend_verification_code()">
                    <?php echo youngo_frontend_phrase_e('resend_mail'); ?>
                    <span id="resend_mail_loader"></span>
                </button>

                <?php if (get_frontend_settings('recaptcha_status')): ?>
                    <div class="youngo-auth__recaptcha">
                        <div class="g-recaptcha" data-sitekey="<?php echo get_frontend_settings('recaptcha_sitekey'); ?>"></div>
                    </div>
                <?php endif; ?>

                <button type="button" onclick="continue_verify()" class="youngo-button"><?php echo youngo_frontend_phrase_e('continue'); ?></button>
            </form>

            <p class="youngo-auth-switch">
                <a href="<?php echo $youngo_verification_login_url; ?>"><?php echo youngo_frontend_phrase_e('back_to_login'); ?></a>
            </p>
        </div>
    </div>
</section>

<script>
    function continue_verify() {
        var formData = new FormData();
        formData.append('email', <?php echo json_encode((string) $this->session->userdata('register_email')); ?>);
        formData.append('verification_code', document.getElementById('verification_code').value);

        fetch('<?php echo site_url('login/verify_email_address/'); ?>', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }).then(function (response) {
            return response.text();
        }).then(function (responseText) {
            if (responseText.trim()) {
                window.location.replace('<?php echo site_url('login'); ?>');
            } else {
                window.location.reload();
            }
        });
    }

    function resend_verification_code() {
        var loader = document.getElementById('resend_mail_loader');
        var formData = new FormData();
        formData.append('email', <?php echo json_encode((string) $this->session->userdata('register_email')); ?>);

        if (loader) {
            loader.textContent = '<?php echo youngo_frontend_phrase('sending'); ?>...';
        }

        fetch('<?php echo site_url('login/resend_verification_code/'); ?>', {
            method: 'POST',
            body: formData,
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
