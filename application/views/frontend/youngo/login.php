<?php if (get_frontend_settings('recaptcha_status') || get_frontend_settings('recaptcha_status_v3')): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
<?php
$youngo_login_language = function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english';
$youngo_login_home_url = function_exists('youngo_frontend_home_url') ? youngo_frontend_home_url($youngo_login_language) : site_url('home');
$youngo_login_forgot_url = function_exists('youngo_frontend_forgot_password_url') ? youngo_frontend_forgot_password_url($youngo_login_language) : site_url('login/forgot_password_request');
$youngo_login_sign_up_url = function_exists('youngo_frontend_sign_up_url') ? youngo_frontend_sign_up_url($youngo_login_language) : site_url('sign_up');
?>

<section class="youngo-auth youngo-auth--doorway youngo-auth--login">
    <div class="youngo-container">
        <div class="youngo-auth-layout youngo-auth-layout--login">
            <aside class="youngo-auth-story" aria-label="<?php echo youngo_frontend_phrase_e('safe_learning_doorway'); ?>">
                <a class="youngo-auth-brandline youngo-auth-brandline--story" href="<?php echo $youngo_login_home_url; ?>" aria-label="<?php echo get_settings('system_name'); ?>">
                    <img src="<?php echo base_url('assets/frontend/youngo/images/logo_small_c.png'); ?>" alt="<?php echo get_settings('system_name'); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                    <span class="youngo-logo-fallback"><?php echo get_settings('system_name'); ?></span>
                </a>

                <div class="youngo-auth-story__copy">
                    <span class="youngo-auth-kicker"><?php echo youngo_frontend_phrase_e('a_safe_place_to_keep_learning'); ?></span>
                    <h2><?php echo youngo_frontend_phrase_e('pick_up_the_next_lesson_with_confidence.'); ?></h2>
                    <p><?php echo youngo_frontend_phrase_e('youngo_keeps_guided_lessons,_creative_projects,_and_progress_moments_together_for_curious_kids_and_the_families_cheering_them_on.'); ?></p>
                </div>

                <div class="youngo-auth-world">
                    <img src="<?php echo base_url('assets/frontend/youngo/images/demo-family-project.jpg'); ?>" alt="" aria-hidden="true">
                    <div class="youngo-auth-world__card youngo-auth-world__card--top">
                        <span><?php echo youngo_frontend_phrase_e('today'); ?></span>
                        <strong><?php echo youngo_frontend_phrase_e('creative_project_ready'); ?></strong>
                    </div>
                    <div class="youngo-auth-world__card youngo-auth-world__card--bottom">
                        <span><?php echo youngo_frontend_phrase_e('parent_view'); ?></span>
                        <strong><?php echo youngo_frontend_phrase_e('progress_feels_clear'); ?></strong>
                    </div>
                </div>

                <div class="youngo-auth-proof">
                    <div>
                        <strong><?php echo youngo_frontend_phrase_e('guided'); ?></strong>
                        <span><?php echo youngo_frontend_phrase_e('lessons_with_structure'); ?></span>
                    </div>
                    <div>
                        <strong><?php echo youngo_frontend_phrase_e('creative'); ?></strong>
                        <span><?php echo youngo_frontend_phrase_e('projects_kids_remember'); ?></span>
                    </div>
                    <div>
                        <strong><?php echo youngo_frontend_phrase_e('calm'); ?></strong>
                        <span><?php echo youngo_frontend_phrase_e('space_parents_can_trust'); ?></span>
                    </div>
                </div>
            </aside>

            <div class="youngo-auth-form-panel">
                <div class="youngo-auth-panel__inner">
                    <a class="youngo-auth-brandline youngo-auth-brandline--mobile" href="<?php echo $youngo_login_home_url; ?>" aria-label="<?php echo get_settings('system_name'); ?>">
                        <img src="<?php echo base_url('assets/frontend/youngo/images/logo_small_c.png'); ?>" alt="<?php echo get_settings('system_name'); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                        <span class="youngo-logo-fallback"><?php echo get_settings('system_name'); ?></span>
                    </a>

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

                    <div class="youngo-auth-heading">
                        <span class="youngo-auth-kicker"><?php echo youngo_frontend_phrase_e('login', 'Login', $youngo_login_language); ?></span>
                        <h1><?php echo youngo_frontend_phrase_e('login_to_your_account', 'Login to your account', $youngo_login_language); ?></h1>
                        <p><?php echo youngo_frontend_phrase_e('continue_a_safe,_joyful_learning_journey_built_for_curious_kids_and_confident_parents.', 'Continue a safe, joyful learning journey built for curious kids and confident parents.', $youngo_login_language); ?></p>
                    </div>

                    <form class="youngo-auth-form" action="<?php echo site_url('login/validate_login'); ?>" method="post" id="login-form">
                        <div class="youngo-field youngo-field--icon" data-icon="@">
                            <label for="email"><?php echo youngo_frontend_phrase_e('email_or_phone_number', 'Email or phone number'); ?></label>
                            <input id="email" type="text" name="email" placeholder="<?php echo youngo_frontend_phrase_e('enter_your_email_or_phone_number', 'Enter your email or phone number'); ?>" autocomplete="username">
                        </div>

                        <div class="youngo-field youngo-field--icon youngo-field--password" data-icon="*">
                            <label for="password"><?php echo youngo_frontend_phrase_e('password'); ?></label>
                            <div class="youngo-password-control">
                                <input id="password" type="password" name="password" placeholder="........" autocomplete="current-password">
                                <button type="button" class="youngo-password-toggle" data-youngo-password-toggle="password" aria-label="<?php echo youngo_frontend_phrase_e('show_password'); ?>">
                                    <?php echo youngo_frontend_phrase_e('show'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="youngo-auth-form__meta">
                            <label class="youngo-auth-remember" for="remember_me">
                                <input id="remember_me" type="checkbox" name="remember_me" value="1">
                                <span><?php echo youngo_frontend_phrase_e('remember_me', 'Remember me', $youngo_login_language); ?></span>
                            </label>
                            <a class="youngo-auth__small-link" href="<?php echo $youngo_login_forgot_url; ?>"><?php echo youngo_frontend_phrase_e('forgot_password?'); ?></a>
                        </div>

                        <?php if (get_frontend_settings('recaptcha_status')): ?>
                            <div class="youngo-auth__recaptcha">
                                <div class="g-recaptcha" data-sitekey="<?php echo get_frontend_settings('recaptcha_sitekey'); ?>"></div>
                            </div>
                        <?php endif; ?>

                        <?php if (get_frontend_settings('recaptcha_status_v3')): ?>
                            <button class="youngo-button youngo-button--auth g-recaptcha" data-sitekey="<?php echo get_frontend_settings('recaptcha_sitekey_v3'); ?>" data-callback="onLoginSubmit" data-action="submit">
                                <?php echo youngo_frontend_phrase_e('login_continue', 'Continue', $youngo_login_language); ?>
                            </button>
                        <?php else: ?>
                            <button type="submit" class="youngo-button youngo-button--auth"><?php echo youngo_frontend_phrase_e('login_continue', 'Continue', $youngo_login_language); ?></button>
                        <?php endif; ?>
                    </form>

                    <?php if (get_settings('fb_social_login')): ?>
                        <div class="youngo-auth-divider">
                            <span><?php echo youngo_frontend_phrase_e('or_continue_with'); ?></span>
                        </div>
                        <div class="youngo-auth-social">
                            <?php include 'facebook_login.php'; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (get_settings('public_signup') == 'enable'): ?>
                        <p class="youngo-auth-switch">
                            <?php echo youngo_frontend_phrase_e('dont_have_an_account?', "Don't have an account?", $youngo_login_language); ?>
                            <a href="<?php echo $youngo_login_sign_up_url; ?>"><?php echo youngo_frontend_phrase_e('sign_up'); ?></a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    function onLoginSubmit() {
        document.getElementById('login-form').submit();
    }

    var youngoShowPasswordLabel = <?php echo json_encode(youngo_frontend_phrase('show')); ?>;
    var youngoHidePasswordLabel = <?php echo json_encode(youngo_frontend_phrase('hide')); ?>;
    var youngoShowPasswordAria = <?php echo json_encode(youngo_frontend_phrase('show_password')); ?>;
    var youngoHidePasswordAria = <?php echo json_encode(youngo_frontend_phrase('hide_password')); ?>;

    document.querySelectorAll('[data-youngo-password-toggle]').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            var input = document.getElementById(toggle.getAttribute('data-youngo-password-toggle'));
            if (!input) {
                return;
            }

            var isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');
            toggle.textContent = isPassword ? youngoHidePasswordLabel : youngoShowPasswordLabel;
            toggle.setAttribute('aria-label', isPassword ? youngoHidePasswordAria : youngoShowPasswordAria);
        });
    });
</script>
