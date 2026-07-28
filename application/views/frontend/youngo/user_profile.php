<?php
$user_id = (int) $this->session->userdata('user_id');
$user_details = $this->user_model->get_all_user($user_id)->row_array();
$social_links = array('facebook' => '', 'twitter' => '', 'linkedin' => '');
if (!empty($user_details['social_links'])) {
    $decoded_social_links = json_decode($user_details['social_links'], true);
    if (is_array($decoded_social_links)) {
        $social_links = array_merge($social_links, $decoded_social_links);
    }
}

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
            <span><?php echo youngo_frontend_phrase('Profile'); ?></span>
        </nav>

        <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('Learner account'); ?></p>
        <h1><?php echo youngo_frontend_phrase('Profile'); ?></h1>
        <p><?php echo youngo_frontend_phrase('Keep learner details current while YounGo continues using the existing Academy LMS account system.'); ?></p>
    </div>
</section>

<section class="youngo-account-page">
    <div class="youngo-container">
        <div class="youngo-account-layout">
            <?php include 'profile_menus.php'; ?>

            <div class="youngo-account-panel">
                <div class="youngo-account-panel__header">
                    <div>
                        <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('Profile info'); ?></p>
                        <h2><?php echo youngo_frontend_phrase('Personal details'); ?></h2>
                    </div>
                    <a class="youngo-button youngo-button--secondary youngo-button--small" href="<?php echo site_url('home/profile/user_photo'); ?>">
                        <i class="fa-regular fa-image"></i>
                        <?php echo youngo_frontend_phrase('Update photo'); ?>
                    </a>
                </div>

                <form class="youngo-account-form" action="<?php echo site_url('home/update_profile/update_basics'); ?>" method="post">
                    <div class="youngo-form-grid">
                        <div class="youngo-form-field">
                            <label for="youngo-first-name"><?php echo youngo_frontend_phrase('first_name'); ?></label>
                            <input type="text" id="youngo-first-name" name="first_name" value="<?php echo youngo_account_e(isset($user_details['first_name']) ? $user_details['first_name'] : ''); ?>" placeholder="<?php echo youngo_frontend_phrase('first_name'); ?>">
                        </div>

                        <div class="youngo-form-field">
                            <label for="youngo-last-name"><?php echo youngo_frontend_phrase('last_name'); ?></label>
                            <input type="text" id="youngo-last-name" name="last_name" value="<?php echo youngo_account_e(isset($user_details['last_name']) ? $user_details['last_name'] : ''); ?>" placeholder="<?php echo youngo_frontend_phrase('last_name'); ?>">
                        </div>

                        <?php if (!empty($user_details['is_instructor'])): ?>
                            <div class="youngo-form-field youngo-form-field--full">
                                <label for="youngo-title"><?php echo youngo_frontend_phrase('title'); ?></label>
                                <textarea id="youngo-title" name="title" rows="3" placeholder="<?php echo youngo_frontend_phrase('short_title_about_yourself'); ?>"><?php echo youngo_account_e(isset($user_details['title']) ? $user_details['title'] : ''); ?></textarea>
                            </div>

                            <div class="youngo-form-field youngo-form-field--full">
                                <label for="youngo-skills"><?php echo youngo_frontend_phrase('your_skills'); ?></label>
                                <input type="text" id="youngo-skills" name="skills" value="<?php echo youngo_account_e(isset($user_details['skills']) ? $user_details['skills'] : ''); ?>">
                            </div>
                        <?php endif; ?>

                        <div class="youngo-form-field youngo-form-field--full">
                            <label for="youngo-biography"><?php echo youngo_frontend_phrase('biography'); ?></label>
                            <textarea id="youngo-biography" name="biography" rows="6"><?php echo youngo_account_e(isset($user_details['biography']) ? $user_details['biography'] : ''); ?></textarea>
                        </div>

                        <div class="youngo-form-field">
                            <label for="youngo-twitter"><?php echo youngo_frontend_phrase('add_your_twitter_link'); ?></label>
                            <input type="text" id="youngo-twitter" maxlength="60" name="twitter_link" value="<?php echo youngo_account_e($social_links['twitter']); ?>" placeholder="<?php echo youngo_frontend_phrase('twitter_link'); ?>">
                        </div>

                        <div class="youngo-form-field">
                            <label for="youngo-facebook"><?php echo youngo_frontend_phrase('add_your_facebook_link'); ?></label>
                            <input type="text" id="youngo-facebook" maxlength="60" name="facebook_link" value="<?php echo youngo_account_e($social_links['facebook']); ?>" placeholder="<?php echo youngo_frontend_phrase('facebook_link'); ?>">
                        </div>

                        <div class="youngo-form-field youngo-form-field--full">
                            <label for="youngo-linkedin"><?php echo youngo_frontend_phrase('add_your_linkedin_link'); ?></label>
                            <input type="text" id="youngo-linkedin" maxlength="60" name="linkedin_link" value="<?php echo youngo_account_e($social_links['linkedin']); ?>" placeholder="<?php echo youngo_frontend_phrase('linkedin_link'); ?>">
                        </div>
                    </div>

                    <div class="youngo-account-form__actions">
                        <button class="youngo-button" type="submit"><?php echo youngo_frontend_phrase('save'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
