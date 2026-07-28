<?php
    $user_details = $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array();
?>

<form class="youngo-account-disable" action="<?php echo site_url('home/account_disable'); ?>" method="post">
    <?php if ($this->session->flashdata('error_message') != ''): ?>
        <div class="youngo-auth-alert youngo-auth-alert--error">
            <?php echo $this->session->flashdata('error_message'); ?>
        </div>
    <?php endif; ?>

    <p><?php echo youngo_frontend_phrase('If you want to reactivate the account after it has been disabled, you must first authenticate your account from signup page.'); ?></p>

    <div class="youngo-field">
        <label for="email"><?php echo youngo_frontend_phrase('email'); ?></label>
        <input type="email" name="email" id="email" placeholder="<?php echo youngo_frontend_phrase('email'); ?>" value="<?php echo htmlspecialchars((string) ($user_details['email'] ?? '')); ?>" disabled>
    </div>

    <div class="youngo-field">
        <label for="account_password"><?php echo youngo_frontend_phrase('Confirm your password'); ?></label>
        <input type="password" id="account_password" name="account_password" placeholder="<?php echo youngo_frontend_phrase('enter_current_password'); ?>">
    </div>

    <button type="submit" class="youngo-button youngo-button--danger"><?php echo youngo_frontend_phrase('Confirm'); ?></button>
</form>
