<?php
if (!function_exists('youngo_checkout_cta_decision') && file_exists(APPPATH . 'helpers/youngo_checkout_cta_helper.php')) {
    $this->load->helper('youngo_checkout_cta');
}

$course_id = (int) $course['id'];
$youngo_card_language = isset($youngo_frontend_language) ? $youngo_frontend_language : 'english';
$GLOBALS['youngo_card_language'] = $youngo_card_language;
$youngo_card_title = isset($course['title']) ? $course['title'] : '';
$youngo_card_description = isset($course['short_description']) && $course['short_description'] !== '' ? $course['short_description'] : (isset($course['description']) ? $course['description'] : '');
if ($youngo_card_language === 'arabic') {
    $youngo_card_content_translations = array(
        'Basic Drawing Course' => array(
            'title' => 'دورة الرسم الأساسية',
            'description' => 'دورة مناسبة للمبتدئين لتعلم أساسيات الرسم.',
        ),
    );
    if (isset($youngo_card_content_translations[$youngo_card_title])) {
        $youngo_card_title = $youngo_card_content_translations[$youngo_card_title]['title'];
        if ($youngo_card_description === '' || stripos($youngo_card_description, 'beginner-friendly') !== false) {
            $youngo_card_description = $youngo_card_content_translations[$course['title']]['description'];
        }
    }
}
if (!function_exists('youngo_course_card_phrase')) {
    function youngo_course_card_phrase($key, $fallback = '') {
        global $youngo_card_language;
        if ($youngo_card_language === 'arabic') {
            $arabic = array(
                'subscription_access' => 'وصول الاشتراك',
                'free' => 'مجاني',
                'active_access' => 'وصول نشط',
                'subscription' => 'اشتراك',
                'purchased' => 'تم الشراء',
                'granted' => 'ممنوح',
                'admin' => 'مسؤول',
                'instructor' => 'مدرّس',
                'access' => 'وصول',
                'access_ending_soon' => 'أوشك الوصول على الانتهاء',
                'expired' => 'منتهي',
                'revoked' => 'ملغى',
                'locked' => 'مقفل',
                'new' => 'جديد',
                'lessons' => 'دروس',
                'start_now' => 'ابدأ الآن',
                'view_subscription_plans' => 'عرض خطط الاشتراك',
                'access_locked' => 'الوصول مقفل',
                'view_details' => 'عرض التفاصيل',
                'subscribe_to_unlock_this_course' => 'اشترك لفتح هذه الدورة',
            );
            if (isset($arabic[$key])) return $arabic[$key];
        }
        return function_exists('youngo_frontend_phrase') ? youngo_frontend_phrase($key, $fallback, $youngo_card_language) : $fallback;
    }
}
$course_url = function_exists('youngo_frontend_course_detail_url') ? youngo_frontend_course_detail_url($course, isset($youngo_frontend_language) ? $youngo_frontend_language : null) : site_url('home/course/' . rawurlencode(slugify($course['title'])) . '/' . $course_id);
$youngo_course_player_title = isset($course['youngo_canonical_title']) ? $course['youngo_canonical_title'] : $course['title'];
$lesson_count = $this->crud_model->get_lessons('course', $course_id)->num_rows();
$course_duration = youngo_courses_duration_label($this->crud_model->get_total_duration_of_lesson_by_course_id($course_id));
$total_rating = $this->crud_model->get_ratings('course', $course_id, true)->row()->rating;
$number_of_ratings = $this->crud_model->get_ratings('course', $course_id)->num_rows();
$average_rating = $number_of_ratings > 0 ? round($total_rating / $number_of_ratings, 1) : 0;
$creator_id = !empty($course['creator']) ? (int) $course['creator'] : (int) trim(strtok((string) $course['user_id'], ','));
$instructor = $creator_id > 0 ? $this->user_model->get_all_user($creator_id)->row_array() : array();
$instructor_name = !empty($instructor) ? trim($instructor['first_name'] . ' ' . $instructor['last_name']) : '';
$youngo_card_instructor_name = ($youngo_card_language === 'arabic' && $instructor_name === 'Client Admin') ? 'مدير العميل' : $instructor_name;
$category_id = !empty($course['sub_category_id']) ? (int) $course['sub_category_id'] : (int) $course['category_id'];
$category = $category_id > 0 ? $this->crud_model->get_category_details_by_id($category_id)->row_array() : array();
if (function_exists('youngo_frontend_translate_category_row')) {
    $category = youngo_frontend_translate_category_row($category, isset($youngo_frontend_language) ? $youngo_frontend_language : null);
}
$category_name = !empty($category['name']) ? $category['name'] : youngo_frontend_phrase('Course');
$youngo_card_access_mode = isset($course['youngo_access_mode']) ? (string) $course['youngo_access_mode'] : '';
$price_label = $youngo_card_access_mode === 'subscription_only' && empty($course['is_free_course'])
    ? youngo_course_card_phrase('subscription_access', 'Subscription access')
    : ($course['is_free_course'] ? youngo_course_card_phrase('free', 'Free') : ($course['discount_flag'] ? currency($course['discounted_price']) : currency($course['price'])));
$is_wishlisted = in_array($course_id, $my_wishlist_items);
$description = $course['short_description'] !== '' ? $course['short_description'] : $course['description'];

if (!function_exists('youngo_course_card_access_summary')) {
    function youngo_course_card_access_summary($access_state) {
        if (!is_array($access_state)) {
            return null;
        }

        $status = isset($access_state['status']) ? $access_state['status'] : 'none';
        $source = isset($access_state['access_source']) ? $access_state['access_source'] : 'none';
        $has_access = !empty($access_state['has_access']);
        $warning_due = !empty($access_state['warning_80_percent']);

        if (!$has_access && $status === 'none') {
            return null;
        }

        $source_labels = array(
            'legacy_enrol' => 'enrolled',
            'course_purchase' => 'purchased',
            'subscription' => 'subscription',
            'manual_grant' => 'granted',
            'admin' => 'admin',
            'instructor' => 'instructor',
            'none' => 'access',
        );

        $summary = array(
            'label' => 'active_access',
            'source_label' => isset($source_labels[$source]) ? $source_labels[$source] : 'access',
            'class' => 'is-active',
        );

        if ($warning_due) {
            $summary['label'] = 'access_ending_soon';
            $summary['class'] = 'is-warning';
            return $summary;
        }

        if ($status === 'expired') {
            $summary['label'] = 'expired';
            $summary['class'] = 'is-locked';
            return $summary;
        }

        if ($status === 'locked' || $status === 'revoked') {
            $summary['label'] = $status === 'revoked' ? 'revoked' : 'locked';
            $summary['class'] = 'is-locked';
            return $summary;
        }

        return $summary;
    }
}

$youngo_card_access_summary = null;
$youngo_card_access_state = null;
$youngo_card_user_id = (int) $this->session->userdata('user_id');

if ($youngo_card_user_id > 0 && file_exists(APPPATH . 'helpers/youngo_entitlement_helper.php') && file_exists(APPPATH . 'models/Youngo_entitlement_model.php')) {
    try {
        $this->load->helper('youngo_entitlement');
        if (function_exists('youngo_get_course_access_state')) {
            $youngo_card_access_state = youngo_get_course_access_state($youngo_card_user_id, $course_id, array(
                'allow_admin_bypass' => false,
                'allow_instructor_bypass' => false,
            ));
            $youngo_card_access_summary = youngo_course_card_access_summary($youngo_card_access_state);
        }
    } catch (Throwable $exception) {
        $youngo_card_access_summary = null;
    }
}

$youngo_card_status = is_array($youngo_card_access_state) && isset($youngo_card_access_state['status']) ? $youngo_card_access_state['status'] : 'none';
$youngo_card_has_access = is_array($youngo_card_access_state) && !empty($youngo_card_access_state['has_access']);
$youngo_card_is_managed_access = in_array($youngo_card_access_mode, array('subscription_only', 'subscription_and_purchase', 'purchase_only'), true);
$youngo_card_is_subscription_only = $youngo_card_access_mode === 'subscription_only';
$youngo_card_checkout_not_ready = $youngo_card_is_subscription_only && !$youngo_card_has_access;
$youngo_card_managed_access_message = 'subscribe_to_unlock_this_course';
$youngo_card_subscription_url = site_url('subscriptions');
$youngo_card_is_locked = in_array($youngo_card_status, array('expired', 'locked', 'revoked'), true);
$youngo_card_is_purchased = is_purchased($course_id);
$youngo_card_checkout_cta_decision = array('show_cta' => false, 'target_url' => null, 'label_text' => '');
if (function_exists('youngo_checkout_cta_decision')) {
    try {
        $youngo_card_checkout_cta_decision = youngo_checkout_cta_decision($course, $youngo_card_user_id, array(
            'access_state' => is_array($youngo_card_access_state) ? $youngo_card_access_state : array('has_access' => false),
        ));
    } catch (Throwable $exception) {
        $youngo_card_checkout_cta_decision = array('show_cta' => false, 'target_url' => null, 'label_text' => '');
    }
}
$youngo_card_show_checkout_cta = !empty($youngo_card_checkout_cta_decision['show_cta']) && !empty($youngo_card_checkout_cta_decision['target_url']);
$youngo_card_show_subscription_cta = $youngo_card_is_subscription_only || $youngo_card_access_mode === 'subscription_and_purchase';
$youngo_card_checkout_label = !empty($youngo_card_checkout_cta_decision['label_text']) ? $youngo_card_checkout_cta_decision['label_text'] : youngo_course_card_phrase('continue_to_checkout', 'Continue to checkout');
?>

<article class="youngo-courses-card">
    <div class="youngo-courses-card__media">
        <a href="<?php echo $course_url; ?>" aria-label="<?php echo youngo_courses_e($youngo_card_title); ?>">
            <img loading="lazy" src="<?php echo $this->crud_model->get_course_thumbnail_url($course_id); ?>" alt="<?php echo youngo_courses_e($youngo_card_title); ?>">
        </a>
        <?php if (!empty($course['level'])): ?><span class="youngo-courses-card__level"><?php echo youngo_frontend_phrase($course['level']); ?></span><?php endif; ?>
        <span class="youngo-wishlist-toggle <?php echo $is_wishlisted ? 'red-heart' : ''; ?>" id="coursesWishlistIcon<?php echo $course_id; ?>">
            <i class="fa-solid fa-heart checkPropagation" onclick="actionTo('<?php echo site_url('home/toggleWishlistItems/' . $course_id); ?>')"></i>
        </span>
    </div>

    <div class="youngo-courses-card__body">
        <div class="youngo-courses-card__topline">
            <span><?php echo youngo_courses_e($category_name); ?></span>
            <strong><?php echo $price_label; ?></strong>
        </div>

        <h3><a href="<?php echo $course_url; ?>"><?php echo youngo_courses_e($youngo_card_title); ?></a></h3>
        <?php if ($youngo_card_access_summary !== null): ?>
            <div class="youngo-courses-card__access <?php echo youngo_courses_e($youngo_card_access_summary['class']); ?>">
                <span><?php echo youngo_courses_e(youngo_course_card_phrase($youngo_card_access_summary['label'], $youngo_card_access_summary['label'])); ?></span>
                <strong><?php echo youngo_courses_e(youngo_course_card_phrase($youngo_card_access_summary['source_label'], $youngo_card_access_summary['source_label'])); ?></strong>
            </div>
        <?php endif; ?>
        <p><?php echo youngo_courses_e(youngo_courses_short_text($youngo_card_description)); ?></p>

        <div class="youngo-courses-card__facts">
            <span><i class="fa-solid fa-star"></i> <?php echo $number_of_ratings > 0 ? $average_rating . ' (' . $number_of_ratings . ')' : youngo_frontend_phrase('New'); ?></span>
            <span><i class="fa-regular fa-list-alt"></i> <?php echo $lesson_count . ' ' . youngo_course_card_phrase('lessons', 'lessons'); ?></span>
            <?php if ($course_duration !== ''): ?><span><i class="fa-regular fa-clock"></i> <?php echo $course_duration; ?></span><?php endif; ?>
        </div>

        <div class="youngo-courses-card__footer">
            <?php if ($youngo_card_instructor_name !== ''): ?><span><?php echo youngo_courses_e($youngo_card_instructor_name); ?></span><?php endif; ?>
            <?php if ($youngo_card_is_purchased || $youngo_card_has_access): ?>
                <a class="youngo-button youngo-button--small" href="<?php echo site_url('home/lesson/' . slugify($youngo_course_player_title) . '/' . $course_id); ?>"><i class="far fa-play-circle"></i> <?php echo youngo_course_card_phrase('start_now', 'Start now'); ?></a>
            <?php elseif ($youngo_card_show_checkout_cta): ?>
                <a class="youngo-button youngo-button--small" href="<?php echo html_escape($youngo_card_checkout_cta_decision['target_url']); ?>" data-youngo-checkout-cta="local-course-card"><i class="fa-solid fa-credit-card"></i> <?php echo youngo_courses_e($youngo_card_checkout_label); ?></a>
            <?php elseif ($youngo_card_show_subscription_cta): ?>
                <a class="youngo-button youngo-button--small youngo-button--subscription" href="<?php echo $youngo_card_subscription_url; ?>"><i class="fa-solid fa-crown"></i> <?php echo youngo_course_card_phrase('view_subscription_plans', 'View subscription plans'); ?></a>
            <?php elseif ($youngo_card_is_locked): ?>
                <a class="youngo-button youngo-button--small youngo-button--secondary" href="<?php echo $course_url; ?>"><?php echo youngo_course_card_phrase('access_locked', 'Access locked'); ?></a>
            <?php else: ?>
                <a class="youngo-button youngo-button--small youngo-button--secondary" href="<?php echo $course_url; ?>"><?php echo youngo_course_card_phrase('view_details', 'View details'); ?></a>
            <?php endif; ?>
        </div>
        <?php if (($youngo_card_is_subscription_only || $youngo_card_access_mode === 'subscription_and_purchase') && !$youngo_card_show_checkout_cta && !$youngo_card_has_access && !$youngo_card_is_purchased): ?>
            <div class="youngo-courses-card__subscription-note">
                <i class="fa-solid fa-sparkles"></i>
                <span><?php echo youngo_frontend_phrase($youngo_card_managed_access_message); ?></span>
            </div>
        <?php endif; ?>
    </div>
</article>
