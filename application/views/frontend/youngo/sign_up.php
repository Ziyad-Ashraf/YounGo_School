<?php if (get_frontend_settings('recaptcha_status') || get_frontend_settings('recaptcha_status_v3')): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
<?php
$youngo_sign_up_language = function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english';
$youngo_sign_up_home_url = function_exists('youngo_frontend_home_url') ? youngo_frontend_home_url($youngo_sign_up_language) : site_url('home');
$youngo_sign_up_login_url = function_exists('youngo_frontend_login_url') ? youngo_frontend_login_url($youngo_sign_up_language) : site_url('login');
?>

<section class="youngo-auth youngo-auth--doorway youngo-auth--signup">
    <div class="youngo-container">
        <div class="youngo-auth-layout youngo-auth-layout--signup">
            <aside class="youngo-auth-story youngo-auth-story--signup" aria-label="<?php echo youngo_frontend_phrase_e('youngo_learning_benefits'); ?>">
                <a class="youngo-auth-brandline youngo-auth-brandline--story" href="<?php echo $youngo_sign_up_home_url; ?>" aria-label="<?php echo get_settings('system_name'); ?>">
                    <img src="<?php echo base_url('assets/frontend/youngo/images/logo_small_c.png'); ?>" alt="<?php echo get_settings('system_name'); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                    <span class="youngo-logo-fallback"><?php echo get_settings('system_name'); ?></span>
                </a>

                <div class="youngo-auth-story__copy">
                    <span class="youngo-auth-kicker"><?php echo youngo_frontend_phrase_e('start_with_confidence'); ?></span>
                    <h2><?php echo youngo_frontend_phrase_e('a_joyful_learning_path_for_growing_minds.'); ?></h2>
                    <p><?php echo youngo_frontend_phrase_e('families_come_to_youngo_for_guided_lessons,_creative_practice,_and_a_calm_space_designed_around_kids_learning_well.'); ?></p>
                </div>

                <div class="youngo-auth-world youngo-auth-world--signup">
                    <img src="<?php echo base_url('assets/frontend/youngo/images/demo-course-coding.jpg'); ?>" alt="" aria-hidden="true">
                    <div class="youngo-auth-world__card youngo-auth-world__card--top">
                        <span><?php echo youngo_frontend_phrase_e('step_1'); ?></span>
                        <strong><?php echo youngo_frontend_phrase_e('choose_a_guided_lesson'); ?></strong>
                    </div>
                    <div class="youngo-auth-world__card youngo-auth-world__card--bottom">
                        <span><?php echo youngo_frontend_phrase_e('step_2'); ?></span>
                        <strong><?php echo youngo_frontend_phrase_e('build,_practice,_and_grow'); ?></strong>
                    </div>
                </div>

                <div class="youngo-auth-trust-strip">
                    <div>
                        <span aria-hidden="true">01</span>
                        <strong><?php echo youngo_frontend_phrase_e('safe_learning_space'); ?></strong>
                        <p><?php echo youngo_frontend_phrase_e('a_friendly_environment_for_young_learners.'); ?></p>
                    </div>
                    <div>
                        <span aria-hidden="true">02</span>
                        <strong><?php echo youngo_frontend_phrase_e('guided_discovery'); ?></strong>
                        <p><?php echo youngo_frontend_phrase_e('lessons_help_kids_move_with_structure.'); ?></p>
                    </div>
                    <div>
                        <span aria-hidden="true">03</span>
                        <strong><?php echo youngo_frontend_phrase_e('creative_confidence'); ?></strong>
                        <p><?php echo youngo_frontend_phrase_e('projects_turn_practice_into_progress.'); ?></p>
                    </div>
                </div>
            </aside>

            <div class="youngo-auth-form-panel">
                <div class="youngo-auth-panel__inner">
                    <a class="youngo-auth-brandline youngo-auth-brandline--mobile" href="<?php echo $youngo_sign_up_home_url; ?>" aria-label="<?php echo get_settings('system_name'); ?>">
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
                        <span class="youngo-auth-kicker"><?php echo youngo_frontend_phrase_e('create_your_account'); ?></span>
                        <h1><?php echo youngo_frontend_phrase_e('join_youngo'); ?></h1>
                        <p><?php echo youngo_frontend_phrase_e('start_with_guided_lessons,_creative_projects,_and_progress_moments_families_can_feel_good_about.'); ?></p>
                    </div>

                    <form class="youngo-auth-form" action="<?php echo site_url('login/register'); ?>" method="post" enctype="multipart/form-data" id="signup-form">
                        <div class="youngo-field-grid">
                            <div class="youngo-field">
                                <label for="first_name"><?php echo youngo_frontend_phrase_e('first_name'); ?></label>
                                <input id="first_name" type="text" name="first_name" placeholder="<?php echo youngo_frontend_phrase_e('enter_your_first_name'); ?>" autocomplete="given-name" required>
                            </div>

                            <div class="youngo-field">
                                <label for="last_name"><?php echo youngo_frontend_phrase_e('last_name'); ?></label>
                                <input id="last_name" type="text" name="last_name" placeholder="<?php echo youngo_frontend_phrase_e('enter_your_last_name'); ?>" autocomplete="family-name" required>
                            </div>
                        </div>

                        <div class="youngo-field">
                            <label for="email"><?php echo youngo_frontend_phrase_e('email_address'); ?></label>
                            <input id="email" type="email" name="email" placeholder="<?php echo youngo_frontend_phrase_e('enter_your_email'); ?>" autocomplete="email" required>
                        </div>

                        <div class="youngo-field youngo-field--password">
                            <label for="password"><?php echo youngo_frontend_phrase_e('create_password'); ?></label>
                            <div class="youngo-password-control">
                                <input id="password" type="password" name="password" placeholder="........" autocomplete="new-password" required>
                                <button type="button" class="youngo-password-toggle" data-youngo-password-toggle="password" aria-label="<?php echo youngo_frontend_phrase_e('show_password'); ?>">
                                    <?php echo youngo_frontend_phrase_e('show'); ?>
                                </button>
                            </div>
                        </div>

                        <?php if (get_settings('allow_instructor')): ?>
                            <label class="youngo-auth-checkbox" for="instructor">
                                <input id="instructor" type="checkbox" name="instructor" value="yes" <?php echo isset($_GET['instructor']) ? 'checked' : ''; ?> data-youngo-toggle-target="become-instructor-fields">
                                <span><?php echo youngo_frontend_phrase_e('apply_to_become_an_instructor'); ?></span>
                            </label>

                            <div id="become-instructor-fields" class="youngo-auth-extra <?php echo isset($_GET['instructor']) ? '' : 'is-hidden'; ?>">
                                <div class="youngo-field">
                                    <label for="phone"><?php echo youngo_frontend_phrase_e('phone'); ?></label>
                                    <input id="phone" type="phone" name="phone" placeholder="<?php echo youngo_frontend_phrase_e('enter_your_phone_number'); ?>">
                                </div>

                                <div class="youngo-field">
                                    <label for="document"><?php echo youngo_frontend_phrase_e('document'); ?> <small>(doc, docs, pdf, txt, png, jpg, jpeg)</small></label>
                                    <input id="document" type="file" name="document">
                                    <small><?php echo youngo_frontend_phrase_e('provide_some_documents_about_your_qualifications'); ?></small>
                                </div>

                                <div class="youngo-field">
                                    <label for="message"><?php echo youngo_frontend_phrase_e('message'); ?></label>
                                    <textarea id="message" name="message" rows="4"></textarea>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (get_frontend_settings('recaptcha_status')): ?>
                            <div class="youngo-auth__recaptcha">
                                <div class="g-recaptcha" data-sitekey="<?php echo get_frontend_settings('recaptcha_sitekey'); ?>"></div>
                            </div>
                        <?php endif; ?>

                        <?php if (get_frontend_settings('recaptcha_status_v3')): ?>
                            <button class="youngo-button youngo-button--auth g-recaptcha" data-sitekey="<?php echo get_frontend_settings('recaptcha_sitekey_v3'); ?>" data-callback="onSignupSubmit" data-action="submit">
                                <?php echo youngo_frontend_phrase_e('sign_up'); ?>
                            </button>
                        <?php else: ?>
                            <button type="submit" class="youngo-button youngo-button--auth"><?php echo youngo_frontend_phrase_e('sign_up'); ?></button>
                        <?php endif; ?>
                    </form>

                    <?php if (get_settings('fb_social_login')): ?>
                        <div class="youngo-auth-divider">
                            <span><?php echo youngo_frontend_phrase_e('or_sign_up_with'); ?></span>
                        </div>
                        <div class="youngo-auth-social">
                            <?php include 'facebook_login.php'; ?>
                        </div>
                    <?php endif; ?>

                    <p class="youngo-auth-switch">
                        <?php echo youngo_frontend_phrase_e('already_have_an_account?'); ?>
                        <a href="<?php echo $youngo_sign_up_login_url; ?>"><?php echo youngo_frontend_phrase_e('log_in'); ?></a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    function onSignupSubmit() {
        document.getElementById('signup-form').submit();
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

    document.querySelectorAll('[data-youngo-toggle-target]').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            var target = document.getElementById(toggle.getAttribute('data-youngo-toggle-target'));
            if (target) {
                target.classList.toggle('is-hidden', !toggle.checked);
            }
        });
    });
</script>
