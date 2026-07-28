<?php
$user_id = (int) $this->session->userdata('user_id');
$user_details = isset($user_details) && is_array($user_details) ? $user_details : array();
$course_items = isset($learner_course_access_items) && is_array($learner_course_access_items) ? $learner_course_access_items : array();
$subscription_summary = isset($learner_subscription_summary) && is_array($learner_subscription_summary) ? $learner_subscription_summary : array();
$access_counts = isset($learner_access_counts) && is_array($learner_access_counts) ? $learner_access_counts : array();
$active_subscriptions = isset($subscription_summary['active']) && is_array($subscription_summary['active']) ? $subscription_summary['active'] : array();
$latest_inactive_subscription = isset($subscription_summary['latest_inactive']) && is_array($subscription_summary['latest_inactive']) ? $subscription_summary['latest_inactive'] : null;
$youngo_my_access_language = function_exists('youngo_frontend_active_language') ? youngo_frontend_active_language() : 'english';
$youngo_my_access_home_url = function_exists('youngo_frontend_home_url') ? youngo_frontend_home_url($youngo_my_access_language) : site_url('home');
$youngo_my_access_courses_url = function_exists('youngo_frontend_courses_url') ? youngo_frontend_courses_url($youngo_my_access_language) : site_url('home/courses');
$youngo_my_access_courses_page_url = function_exists('youngo_frontend_my_courses_url') ? youngo_frontend_my_courses_url($youngo_my_access_language) : site_url('home/my_courses');

if (!function_exists('youngo_my_access_e')) {
    function youngo_my_access_e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('youngo_my_access_date')) {
    function youngo_my_access_date($timestamp) {
        $timestamp = (int) $timestamp;
        return $timestamp > 0 ? date('M d, Y', $timestamp) : '';
    }
}

$student_name = trim((isset($user_details['first_name']) ? $user_details['first_name'] : '') . ' ' . (isset($user_details['last_name']) ? $user_details['last_name'] : ''));
if ($student_name === '') {
    $student_name = youngo_frontend_phrase('YounGo learner');
}
?>

<section class="youngo-account-hero">
    <div class="youngo-container">
        <nav class="youngo-breadcrumb" aria-label="<?php echo youngo_frontend_phrase('Breadcrumb'); ?>">
            <a href="<?php echo $youngo_my_access_home_url; ?>"><?php echo youngo_frontend_phrase('Home'); ?></a>
            <span>/</span>
            <span><?php echo youngo_frontend_phrase('My Access'); ?></span>
        </nav>

        <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('Learner access'); ?></p>
        <h1><?php echo youngo_frontend_phrase('My Access'); ?></h1>
        <p><?php echo youngo_frontend_phrase('Review active school-granted access and subscription status without checkout or payment actions.'); ?></p>
    </div>
</section>

<section class="youngo-account-page">
    <div class="youngo-container">
        <div class="youngo-account-layout">
            <?php include 'profile_menus.php'; ?>

            <div class="youngo-account-panel">
                <div class="youngo-account-panel__header">
                    <div>
                        <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('Access summary'); ?></p>
                        <h2><?php echo youngo_frontend_phrase('Learning access for'); ?> <?php echo youngo_my_access_e($student_name); ?></h2>
                    </div>
                    <a class="youngo-button youngo-button--secondary youngo-button--small" href="<?php echo $youngo_my_access_courses_url; ?>"><?php echo youngo_frontend_phrase('Browse included courses'); ?></a>
                </div>

                <div class="youngo-learning-stats" aria-label="<?php echo youngo_frontend_phrase('Access counts'); ?>">
                    <div>
                        <span><?php echo youngo_frontend_phrase('Active courses'); ?></span>
                        <strong><?php echo isset($access_counts['active_course_count']) ? (int) $access_counts['active_course_count'] : count($course_items); ?></strong>
                    </div>
                    <div>
                        <span><?php echo youngo_frontend_phrase('School grants'); ?></span>
                        <strong><?php echo isset($access_counts['manual_course_grant_count']) ? (int) $access_counts['manual_course_grant_count'] : 0; ?></strong>
                    </div>
                    <div>
                        <span><?php echo youngo_frontend_phrase('Enrolled'); ?></span>
                        <strong><?php echo isset($access_counts['legacy_enrol_count']) ? (int) $access_counts['legacy_enrol_count'] : 0; ?></strong>
                    </div>
                    <div>
                        <span><?php echo youngo_frontend_phrase('Subscriptions'); ?></span>
                        <strong><?php echo isset($subscription_summary['active_count']) ? (int) $subscription_summary['active_count'] : 0; ?></strong>
                    </div>
                </div>

                <div class="youngo-my-courses-toolbar">
                    <div>
                        <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('Subscriptions'); ?></p>
                        <h2><?php echo youngo_frontend_phrase('Subscription status'); ?></h2>
                    </div>
                </div>

                <?php if (count($active_subscriptions) > 0): ?>
                    <div class="youngo-learning-list">
                        <?php foreach ($active_subscriptions as $subscription): ?>
                            <article class="youngo-learning-card">
                                <div class="youngo-learning-card__body">
                                    <div class="youngo-learning-card__top">
                                        <span><?php echo youngo_my_access_e(isset($subscription['source_label']) ? $subscription['source_label'] : youngo_frontend_phrase('Subscription access')); ?></span>
                                        <strong><?php echo youngo_my_access_e(isset($subscription['status_label']) ? $subscription['status_label'] : youngo_frontend_phrase('Access active')); ?></strong>
                                    </div>
                                    <h3><?php echo youngo_my_access_e(isset($subscription['plan_name']) ? $subscription['plan_name'] : youngo_frontend_phrase('Subscription plan')); ?></h3>
                                    <div class="youngo-learning-meta">
                                        <?php if (!empty($subscription['duration_days'])): ?><span><i class="fa-regular fa-calendar"></i> <?php echo (int) $subscription['duration_days']; ?> <?php echo youngo_frontend_phrase('days'); ?></span><?php endif; ?>
                                        <?php if (!empty($subscription['currency'])): ?><span><i class="fa-regular fa-credit-card"></i> <?php echo youngo_my_access_e($subscription['currency']); ?> <?php echo youngo_my_access_e(isset($subscription['price_paid']) ? $subscription['price_paid'] : '0.00'); ?></span><?php endif; ?>
                                        <?php if (!empty($subscription['expiry_date'])): ?><span><i class="fa-regular fa-clock"></i> <?php echo youngo_frontend_phrase('Access until'); ?> <?php echo youngo_my_access_e(youngo_my_access_date($subscription['expiry_date'])); ?></span><?php endif; ?>
                                    </div>
                                    <?php if (!empty($subscription['message'])): ?>
                                        <p class="youngo-learning-access__message"><?php echo youngo_my_access_e($subscription['message']); ?></p>
                                    <?php endif; ?>
                                    <div class="youngo-learning-card__footer">
                                        <span class="youngo-learning-expiry"><?php echo youngo_frontend_phrase('Checkout and renewal actions are not available yet.'); ?></span>
                                        <div class="youngo-learning-actions">
                                            <a class="youngo-button youngo-button--small" href="<?php echo $youngo_my_access_courses_url; ?>"><?php echo youngo_frontend_phrase('Browse included courses'); ?></a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($latest_inactive_subscription !== null): ?>
                    <div class="youngo-commerce-empty">
                        <div class="youngo-commerce-empty__icon"><i class="fa-regular fa-clock"></i></div>
                        <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('No active subscription'); ?></p>
                        <h2><?php echo youngo_my_access_e(isset($latest_inactive_subscription['status_label']) ? $latest_inactive_subscription['status_label'] : youngo_frontend_phrase('Subscription inactive')); ?></h2>
                        <p><?php echo youngo_frontend_phrase('Your latest subscription is not active. Checkout and renewal actions are not available yet.'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="youngo-commerce-empty">
                        <div class="youngo-commerce-empty__icon"><i class="fa-regular fa-compass"></i></div>
                        <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('No subscription yet'); ?></p>
                        <h2><?php echo youngo_frontend_phrase('No active subscription access'); ?></h2>
                        <p><?php echo youngo_frontend_phrase('If your school grants subscription access later, it will appear here.'); ?></p>
                    </div>
                <?php endif; ?>

                <div class="youngo-my-courses-toolbar">
                    <div>
                        <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('Courses'); ?></p>
                        <h2><?php echo youngo_frontend_phrase('Active course access'); ?></h2>
                    </div>
                    <a class="youngo-button youngo-button--secondary youngo-button--small" href="<?php echo $youngo_my_access_courses_page_url; ?>"><?php echo youngo_frontend_phrase('Open My Courses'); ?></a>
                </div>

                <?php if (count($course_items) > 0): ?>
                    <div class="youngo-purchase-list">
                        <?php foreach ($course_items as $course): ?>
                            <?php
                            $course_id = isset($course['course_id']) ? (int) $course['course_id'] : 0;
                            $title = isset($course['title']) ? $course['title'] : youngo_frontend_phrase('Course');
                            $course_url = isset($course['course_url']) ? $course['course_url'] : (function_exists('youngo_frontend_public_url') ? youngo_frontend_public_url('home/course/' . rawurlencode(slugify($title)) . '/' . $course_id, $youngo_my_access_language) : site_url('home/course/' . rawurlencode(slugify($title)) . '/' . $course_id));
                            ?>
                            <article class="youngo-purchase-card">
                                <a class="youngo-purchase-card__media" href="<?php echo $course_url; ?>">
                                    <img loading="lazy" src="<?php echo $this->crud_model->get_course_thumbnail_url($course_id); ?>" alt="<?php echo youngo_my_access_e($title); ?>">
                                </a>
                                <div class="youngo-purchase-card__body">
                                    <div>
                                        <p class="youngo-eyebrow"><?php echo youngo_my_access_e(isset($course['access_label']) ? $course['access_label'] : youngo_frontend_phrase('Access')); ?></p>
                                        <h3><a href="<?php echo $course_url; ?>"><?php echo youngo_my_access_e($title); ?></a></h3>
                                    </div>
                                    <div class="youngo-purchase-card__meta">
                                        <span><i class="fa-regular fa-circle-check"></i> <?php echo youngo_my_access_e(isset($course['access_status_label']) ? $course['access_status_label'] : youngo_frontend_phrase('Access active')); ?></span>
                                        <?php if (!empty($course['expiry_date'])): ?><span><i class="fa-regular fa-calendar"></i> <?php echo youngo_frontend_phrase('Until'); ?> <?php echo youngo_my_access_e(youngo_my_access_date($course['expiry_date'])); ?></span><?php endif; ?>
                                    </div>
                                    <div class="youngo-purchase-card__actions">
                                        <a class="youngo-button youngo-button--small youngo-button--secondary" href="<?php echo $course_url; ?>"><?php echo youngo_frontend_phrase('Course details'); ?></a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="youngo-commerce-empty">
                        <div class="youngo-commerce-empty__icon"><i class="fa-regular fa-circle-play"></i></div>
                        <p class="youngo-eyebrow"><?php echo youngo_frontend_phrase('No active course access'); ?></p>
                        <h2><?php echo youngo_frontend_phrase('Courses will appear after access is granted'); ?></h2>
                        <p><?php echo youngo_frontend_phrase('Enrolled and school-granted courses will appear here without creating payments or checkout orders.'); ?></p>
                        <a class="youngo-button" href="<?php echo $youngo_my_access_courses_url; ?>"><?php echo youngo_frontend_phrase('Browse courses'); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
