<?php
$context = isset($youngo_paymob_return_context) && is_array($youngo_paymob_return_context)
    ? $youngo_paymob_return_context
    : array();
$code = isset($context['code']) ? (string) $context['code'] : 'paymob_return_disabled_no_write';
$message = isset($context['message']) ? (string) $context['message'] : 'Paymob return URL is pending and disabled.';
$mode = isset($context['mode']) ? (string) $context['mode'] : 'sandbox';
$currency = isset($context['currency']) ? (string) $context['currency'] : 'EGP';
?>

<section class="youngo-account-hero" data-youngo-paymob-return-disabled="true">
    <div class="youngo-container">
        <nav class="youngo-breadcrumb" aria-label="<?php echo get_phrase('Breadcrumb'); ?>">
            <a href="<?php echo site_url('home'); ?>"><?php echo get_phrase('Home'); ?></a>
            <span>/</span>
            <span><?php echo get_phrase('Payment return'); ?></span>
        </nav>

        <p class="youngo-eyebrow"><?php echo get_phrase('Sandbox pending'); ?></p>
        <h1><?php echo get_phrase('Payment confirmation is pending'); ?></h1>
        <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
</section>

<section class="youngo-account-page">
    <div class="youngo-container">
        <div class="youngo-account-panel">
            <div class="youngo-account-panel__header">
                <div>
                    <p class="youngo-eyebrow"><?php echo get_phrase('Safe return route'); ?></p>
                    <h2><?php echo get_phrase('No payment action was taken'); ?></h2>
                </div>
            </div>

            <div class="youngo-empty-state">
                <p><?php echo get_phrase('This route does not mark payments as paid, issue access, or trust return query parameters.'); ?></p>
                <p><?php echo get_phrase('Configured mode'); ?>: <?php echo htmlspecialchars($mode, ENT_QUOTES, 'UTF-8'); ?> - <?php echo get_phrase('Currency'); ?>: <?php echo htmlspecialchars($currency, ENT_QUOTES, 'UTF-8'); ?></p>
                <p><?php echo get_phrase('Reason'); ?>: <?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?></p>
                <p><a href="<?php echo site_url('home/courses'); ?>"><?php echo get_phrase('Back to courses'); ?></a></p>
            </div>
        </div>
    </div>
</section>
