<?php
if (file_exists(APPPATH . 'helpers/youngo_frontend_language_helper.php')) {
    $this->load->helper('youngo_frontend_language');
}

$youngo_subscriptions_language = isset($youngo_frontend_language) ? $youngo_frontend_language : (function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english');
$youngo_subscription_plans = isset($subscription_plans) && is_array($subscription_plans) ? $subscription_plans : array();
$youngo_subscriptions_home_url = function_exists('youngo_frontend_home_url') ? youngo_frontend_home_url($youngo_subscriptions_language) : site_url('home');
$youngo_subscriptions_contact_url = function_exists('youngo_frontend_contact_url') ? youngo_frontend_contact_url($youngo_subscriptions_language) : site_url('home/contact');

if (!function_exists('youngo_subscriptions_e')) {
    function youngo_subscriptions_e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>

<section class="youngo-courses-hero youngo-subscriptions-hero">
    <div class="youngo-container">
        <nav class="youngo-breadcrumb" aria-label="<?php echo youngo_subscriptions_e(youngo_frontend_phrase('breadcrumb', $youngo_subscriptions_language)); ?>">
            <a href="<?php echo youngo_subscriptions_e($youngo_subscriptions_home_url); ?>"><?php echo youngo_subscriptions_e(youngo_frontend_phrase('home', $youngo_subscriptions_language)); ?></a>
            <span>/</span>
            <span><?php echo youngo_subscriptions_e(youngo_frontend_phrase('subscriptions', $youngo_subscriptions_language)); ?></span>
        </nav>

        <div class="youngo-courses-hero__grid">
            <div>
                <p class="youngo-eyebrow"><?php echo youngo_subscriptions_e(youngo_frontend_phrase('family_access_plans', $youngo_subscriptions_language)); ?></p>
                <h1><?php echo youngo_subscriptions_e(youngo_frontend_phrase('subscription_plans', $youngo_subscriptions_language)); ?></h1>
                <p><?php echo youngo_subscriptions_e(youngo_frontend_phrase('choose_a_learning_plan_for_consistent_youngo_access._online_access_requests_are_not_available_yet.', $youngo_subscriptions_language)); ?></p>
            </div>
            <div class="youngo-courses-hero__stats" aria-label="<?php echo youngo_subscriptions_e(youngo_frontend_phrase('plan_options', $youngo_subscriptions_language)); ?>">
                <strong><?php echo count($youngo_subscription_plans); ?></strong>
                <span><?php echo count($youngo_subscription_plans) === 1 ? youngo_subscriptions_e(youngo_frontend_phrase('plan_available', $youngo_subscriptions_language)) : youngo_subscriptions_e(youngo_frontend_phrase('plans_available', $youngo_subscriptions_language)); ?></span>
            </div>
        </div>
    </div>
</section>

<section class="youngo-subscriptions-page">
    <div class="youngo-container">
        <?php if (count($youngo_subscription_plans) > 0): ?>
            <div class="youngo-subscriptions-grid">
                <?php foreach ($youngo_subscription_plans as $plan): ?>
                    <?php
                    $plan_name = isset($plan['name']) ? $plan['name'] : '';
                    $plan_price = isset($plan['price_display']) ? $plan['price_display'] : '';
                    $plan_duration = isset($plan['duration_label']) ? $plan['duration_label'] : '';
                    $plan_description = isset($plan['short_description']) ? $plan['short_description'] : '';
                    $plan_featured = !empty($plan['featured']);
                    $plan_currency = isset($plan['currency']) ? $plan['currency'] : 'EGP';
                    $plan_currency_label = youngo_frontend_phrase(strtolower($plan_currency), $plan_currency, $youngo_subscriptions_language);
                    ?>
                    <article class="youngo-subscription-card<?php echo $plan_featured ? ' is-featured' : ''; ?>">
                        <div class="youngo-subscription-card__header">
                            <div>
                                <?php if ($plan_featured): ?>
                                    <span class="youngo-subscription-card__badge"><?php echo youngo_subscriptions_e(youngo_frontend_phrase('featured_plan', $youngo_subscriptions_language)); ?></span>
                                <?php else: ?>
                                    <span class="youngo-subscription-card__badge is-muted"><?php echo youngo_subscriptions_e(youngo_frontend_phrase('subscription_access', $youngo_subscriptions_language)); ?></span>
                                <?php endif; ?>
                                <h2><?php echo youngo_subscriptions_e($plan_name); ?></h2>
                            </div>
                            <strong><?php echo youngo_subscriptions_e($plan_price); ?></strong>
                        </div>

                        <div class="youngo-subscription-card__meta">
                            <span><i class="fa-regular fa-calendar"></i><strong><?php echo youngo_subscriptions_e(youngo_frontend_phrase('duration', 'Duration', $youngo_subscriptions_language)); ?></strong> <?php echo youngo_subscriptions_e($plan_duration); ?></span>
                            <span><i class="fa-solid fa-coins"></i><?php echo youngo_subscriptions_e($plan_currency_label); ?></span>
                        </div>

                        <?php if (trim((string) $plan_description) !== ''): ?>
                            <p><?php echo youngo_subscriptions_e($plan_description); ?></p>
                        <?php else: ?>
                            <p><?php echo youngo_subscriptions_e(youngo_frontend_phrase('contact_us_to_choose_the_right_starting_point_before_subscriptions_open_online.', $youngo_subscriptions_language)); ?></p>
                        <?php endif; ?>

                        <div class="youngo-subscription-card__actions">
                            <a class="youngo-button" href="<?php echo youngo_subscriptions_e(site_url('youngo/checkout/subscription/start/' . (int) $plan['id'])); ?>" data-youngo-checkout-cta="local-subscription-plan">
                                <i class="fa-solid fa-wallet"></i>
                                <span><?php echo youngo_subscriptions_e(youngo_frontend_phrase('subscribe', 'Subscribe', $youngo_subscriptions_language)); ?></span>
                            </a>
                            <a class="youngo-button youngo-button--secondary" href="<?php echo youngo_subscriptions_e($youngo_subscriptions_contact_url); ?>">
                                <i class="fa-regular fa-envelope"></i>
                                <span><?php echo youngo_subscriptions_e(youngo_frontend_phrase('talk_to_us_about_subscriptions', $youngo_subscriptions_language)); ?></span>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="youngo-my-courses-empty youngo-subscriptions-empty">
                <div class="youngo-my-courses-empty__icon"><i class="fa-regular fa-calendar-check"></i></div>
                <p class="youngo-eyebrow"><?php echo youngo_subscriptions_e(youngo_frontend_phrase('coming_soon', $youngo_subscriptions_language)); ?></p>
                <h2><?php echo youngo_subscriptions_e(youngo_frontend_phrase('no_subscription_plans_available_yet', $youngo_subscriptions_language)); ?></h2>
                <p><?php echo youngo_subscriptions_e(youngo_frontend_phrase('subscription_plans_will_appear_here_after_they_are_approved_and_made_purchasable.', $youngo_subscriptions_language)); ?></p>
                <a class="youngo-button" href="<?php echo youngo_subscriptions_e($youngo_subscriptions_contact_url); ?>"><?php echo youngo_subscriptions_e(youngo_frontend_phrase('contact_us', $youngo_subscriptions_language)); ?></a>
            </div>
        <?php endif; ?>
    </div>
</section>
