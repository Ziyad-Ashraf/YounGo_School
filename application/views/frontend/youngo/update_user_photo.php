<?php
$user_id = (int) $this->session->userdata('user_id');
$user_details = $this->user_model->get_all_user($user_id)->row_array();
$student_name = trim((isset($user_details['first_name']) ? $user_details['first_name'] : '') . ' ' . (isset($user_details['last_name']) ? $user_details['last_name'] : ''));
if ($student_name === '') {
    $student_name = youngo_frontend_phrase('YounGo learner');
}
?>

<section class="youngo-account-hero">
    <div class="youngo-container">
        <nav class="youngo-breadcrumb" aria-label="<?php echo youngo_frontend_phrase('Breadcrumb'); ?>">
            <a href="<?php echo site_url('home'); ?>"><?php echo youngo_frontend_phrase('Home'); ?></a>
            <span>/</span>
            <span><?php echo youngo_frontend_phrase('Profile photo'); ?></span>
        </nav>

        <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('Learner account'); ?></p>
        <h1><?php echo youngo_frontend_phrase('Update user photo'); ?></h1>
        <p><?php echo youngo_frontend_phrase('Refresh the learner profile image while keeping the existing LMS upload handling.'); ?></p>
    </div>
</section>

<section class="youngo-account-page">
    <div class="youngo-container">
        <div class="youngo-account-layout">
            <?php include 'profile_menus.php'; ?>

            <div class="youngo-account-panel">
                <div class="youngo-account-photo-card">
                    <img loading="lazy" src="<?php echo $this->user_model->get_user_image_url($user_id); ?>" alt="<?php echo htmlspecialchars($student_name, ENT_QUOTES, 'UTF-8'); ?>">
                    <div>
                        <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('Profile photo'); ?></p>
                        <h2><?php echo htmlspecialchars($student_name, ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p><?php echo youngo_frontend_phrase('Choose a JPG image for the learner profile. The existing LMS upload method stores and serves the image.'); ?></p>
                    </div>
                </div>

                <form class="youngo-account-form" action="<?php echo site_url('home/update_profile/update_photo'); ?>" method="post" enctype="multipart/form-data">
                    <div class="youngo-form-field youngo-form-field--full">
                        <label for="profile-photo-input"><?php echo youngo_frontend_phrase('Upload photo'); ?></label>
                        <input type="file" id="profile-photo-input" name="user_image" accept="image/*">
                    </div>

                    <div class="youngo-account-form__actions">
                        <button class="youngo-button" type="submit"><?php echo youngo_frontend_phrase('Save'); ?></button>
                        <a class="youngo-button youngo-button--secondary" href="<?php echo site_url('home/profile/user_profile'); ?>"><?php echo youngo_frontend_phrase('Back to profile'); ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
