<?php
$user_id = (int) $this->session->userdata('user_id');
$user_details = $this->user_model->get_all_user($user_id)->row_array();

if (!function_exists('youngo_account_e')) {
    function youngo_account_e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>

<section class="youngo-account-hero">
    <div class="youngo-container">
        <nav class="youngo-breadcrumb" aria-label="<?php echo youngo_frontend_phrase('Breadcrumb'); ?>">
            <a href="<?php echo site_url('home'); ?>"><?php echo youngo_frontend_phrase('Home'); ?></a>
            <span>/</span>
            <span><?php echo youngo_frontend_phrase('Account'); ?></span>
        </nav>

        <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('Account security'); ?></p>
        <h1><?php echo youngo_frontend_phrase('Credentials'); ?></h1>
        <p><?php echo youngo_frontend_phrase('Update password credentials through the existing Academy LMS account protection flow.'); ?></p>
    </div>
</section>

<section class="youngo-account-page">
    <div class="youngo-container">
        <div class="youngo-account-layout">
            <?php include 'profile_menus.php'; ?>

            <div class="youngo-account-panel">
                <div class="youngo-account-panel__header">
                    <div>
                        <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('Security'); ?></p>
                        <h2><?php echo youngo_frontend_phrase('account_information'); ?></h2>
                    </div>
                </div>

                <form class="youngo-account-form" action="<?php echo site_url('home/update_profile/update_credentials'); ?>" method="post">
                    <div class="youngo-form-grid">
                        <div class="youngo-form-field youngo-form-field--full">
                            <label for="youngo-email"><?php echo youngo_frontend_phrase('email'); ?></label>
                            <input type="email" id="youngo-email" name="email" value="<?php echo youngo_account_e(isset($user_details['email']) ? $user_details['email'] : ''); ?>" disabled>
                        </div>

                        <div class="youngo-form-field youngo-form-field--full">
                            <label for="current_password"><?php echo youngo_frontend_phrase('current_password'); ?></label>
                            <input type="password" id="current_password" name="current_password" placeholder="<?php echo youngo_frontend_phrase('enter_current_password'); ?>">
                        </div>

                        <div class="youngo-form-field">
                            <label for="new_password"><?php echo youngo_frontend_phrase('new_password'); ?></label>
                            <input type="password" id="new_password" name="new_password" placeholder="<?php echo youngo_frontend_phrase('enter_new_password'); ?>">
                        </div>

                        <div class="youngo-form-field">
                            <label for="confirm_password"><?php echo youngo_frontend_phrase('confirm_password'); ?></label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="<?php echo youngo_frontend_phrase('re-type_your_password'); ?>">
                        </div>
                    </div>

                    <div class="youngo-commerce-alert">
                        <strong><?php echo youngo_frontend_phrase('Password logic preserved'); ?></strong>
                        <span><?php echo youngo_frontend_phrase('YounGo uses the existing LMS password validation and update method.'); ?></span>
                    </div>

                    <div class="youngo-account-form__actions">
                        <button class="youngo-button" type="submit"><?php echo youngo_frontend_phrase('save_changes'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
