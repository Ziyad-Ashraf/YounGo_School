<?php
$context = isset($youngo_checkout_disabled_context) && is_array($youngo_checkout_disabled_context)
    ? $youngo_checkout_disabled_context
    : array();
$availability = isset($context['availability']) && is_array($context['availability']) ? $context['availability'] : array();
$code = isset($availability['code']) ? (string) $availability['code'] : 'checkout_disabled';
$currency = isset($context['currency']) ? (string) $context['currency'] : 'EGP';
$mode = isset($context['mode']) ? (string) $context['mode'] : 'sandbox';
?>

<section class="youngo-account-hero">
    <div class="youngo-container">
        <nav class="youngo-breadcrumb" aria-label="<?php echo get_phrase('Breadcrumb'); ?>">
            <a href="<?php echo site_url('home'); ?>"><?php echo get_phrase('Home'); ?></a>
            <span>/</span>
            <span><?php echo get_phrase('Checkout'); ?></span>
        </nav>

        <p class="youngo-eyebrow"><?php echo get_phrase('Local sandbox only'); ?></p>
        <h1><?php echo get_phrase('Checkout is not enabled'); ?></h1>
        <p><?php echo get_phrase('YounGo checkout routes are present for local planning only. Payment actions are disabled.'); ?></p>
    </div>
</section>

<section class="youngo-account-page">
    <div class="youngo-container">
        <div class="youngo-account-panel">
            <div class="youngo-account-panel__header">
                <div>
                    <p class="youngo-eyebrow"><?php echo get_phrase('Payment status'); ?></p>
                    <h2><?php echo get_phrase('No gateway is active'); ?></h2>
                </div>
            </div>

            <div class="youngo-empty-state">
                <p><?php echo get_phrase('This local checkout skeleton does not create orders, open Paymob, issue access, or use legacy gateway rows.'); ?></p>
                <p><?php echo get_phrase('Configured mode'); ?>: <?php echo htmlspecialchars($mode, ENT_QUOTES, 'UTF-8'); ?> - <?php echo get_phrase('Currency'); ?>: <?php echo htmlspecialchars($currency, ENT_QUOTES, 'UTF-8'); ?></p>
                <p><?php echo get_phrase('Reason'); ?>: <?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?></p>
                <p><a href="<?php echo site_url('home/courses'); ?>"><?php echo get_phrase('Back to courses'); ?></a></p>
            </div>
        </div>
    </div>
</section>
