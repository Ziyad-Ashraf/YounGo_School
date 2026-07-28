<section class="youngo-auth youngo-error-page">
    <div class="youngo-container youngo-auth__grid">
        <div class="youngo-auth__intro">
            <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('404 Not Found'); ?></p>
            <h1><?php echo youngo_frontend_phrase('The page you requested could not be found'); ?></h1>
            <p><?php echo youngo_frontend_phrase('Please try the following:'); ?></p>
            <ul class="youngo-error-list">
                <li><?php echo youngo_frontend_phrase('Check the spelling of the URL'); ?></li>
                <li><?php echo youngo_frontend_phrase('If you are still puzzled, click on the home link below'); ?></li>
            </ul>
            <a class="youngo-button" href="<?php echo site_url(); ?>"><?php echo youngo_frontend_phrase('Back to Home'); ?></a>
        </div>

        <div class="youngo-auth-card youngo-auth-card--center">
            <span class="youngo-error-code">404</span>
            <h2><?php echo youngo_frontend_phrase('Not Found'); ?></h2>
            <p class="youngo-auth-card__copy"><?php echo youngo_frontend_phrase('This page is not available, but the learning path is still open.'); ?></p>
        </div>
    </div>
</section>
