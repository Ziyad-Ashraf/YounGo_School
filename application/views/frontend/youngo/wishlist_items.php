<?php
if (!function_exists('youngo_frontend_content_language') && file_exists(APPPATH . 'helpers/youngo_frontend_content_helper.php')) {
    $this->load->helper('youngo_frontend_content');
}
if (!function_exists('youngo_checkout_cta_decision') && file_exists(APPPATH . 'helpers/youngo_checkout_cta_helper.php')) {
    $this->load->helper('youngo_checkout_cta');
}

$youngo_frontend_language = isset($youngo_frontend_language) ? $youngo_frontend_language : (function_exists('youngo_frontend_content_language') ? youngo_frontend_content_language() : 'english');
$youngo_wishlist_items_courses_url = function_exists('youngo_frontend_courses_url') ? youngo_frontend_courses_url($youngo_frontend_language) : site_url('home/courses');
$youngo_wishlist_items_contact_url = function_exists('youngo_frontend_contact_url') ? youngo_frontend_contact_url($youngo_frontend_language) : site_url('home/contact_us');
if (!function_exists('youngo_wishlist_phrase')) {
    function youngo_wishlist_phrase($phrase_key, $fallback = '', $language_code = null)
    {
        if (function_exists('youngo_frontend_phrase')) {
            $value = youngo_frontend_phrase($phrase_key, $fallback, $language_code);
        } else {
            $value = trim((string) $fallback) !== '' ? $fallback : ucwords(str_replace('_', ' ', (string) $phrase_key));
        }

        $resolved_language = function_exists('youngo_frontend_phrase_language_code') ? youngo_frontend_phrase_language_code($language_code) : $language_code;
        if ($resolved_language === null && $fallback !== '' && function_exists('youngo_frontend_phrase_language_code') && youngo_frontend_phrase_language_code($fallback) !== null) {
            $resolved_language = youngo_frontend_phrase_language_code($fallback);
        }
        if ($resolved_language === null && function_exists('youngo_frontend_active_language')) {
            $resolved_language = youngo_frontend_active_language();
        }

        if ($resolved_language !== 'arabic' || preg_match('/\p{Arabic}/u', (string) $value)) {
            return $value;
        }

        if (function_exists('youngo_frontend_local_phrase_value')) {
            $local_value = youngo_frontend_local_phrase_value($phrase_key, 'arabic');
            if ($local_value !== null && preg_match('/\p{Arabic}/u', (string) $local_value)) {
                return $local_value;
            }
        }

        $fallbacks = array(
            'wishlist_items_are_saved_to_your_learner_account_so_they_stay_available_across_visits.' => 'يتم حفظ عناصر المفضلة في حساب المتعلم لتبقى متاحة في الزيارات القادمة.',
            'save_interesting_courses_while_browsing,_then_compare_options_before_enrolling.' => 'احفظ الكورسات التي تهمك أثناء التصفح، ثم قارن الخيارات قبل التسجيل.',
        );
        $lookup_key = strtolower(trim((string) $phrase_key));

        return isset($fallbacks[$lookup_key]) ? $fallbacks[$lookup_key] : $value;
    }
}
if (!function_exists('youngo_wishlist_course_boundary_state')) {
    function youngo_wishlist_course_boundary_state($course, $user_id)
    {
        $state = array(
            'is_managed' => false,
            'has_access' => false,
            'is_subscription_only' => false,
            'message' => '',
        );

        if (!is_array($course)) {
            return $state;
        }

        $mode = isset($course['youngo_access_mode']) ? (string) $course['youngo_access_mode'] : '';
        $state['is_managed'] = in_array($mode, array('subscription_only', 'subscription_and_purchase', 'purchase_only'), true);
        $state['is_subscription_only'] = $mode === 'subscription_only';
        if (!$state['is_managed']) {
            return $state;
        }

        $state['message'] = youngo_wishlist_phrase('subscribe_to_unlock_this_course');

        if ($user_id > 0 && file_exists(APPPATH . 'helpers/youngo_entitlement_helper.php') && file_exists(APPPATH . 'models/Youngo_entitlement_model.php')) {
            $CI =& get_instance();
            $CI->load->helper('youngo_entitlement');
            if (function_exists('youngo_get_course_access_state')) {
                try {
                    $access_state = youngo_get_course_access_state((int) $user_id, (int) $course['id'], array(
                        'allow_admin_bypass' => false,
                        'allow_instructor_bypass' => false,
                    ));
                    $state['has_access'] = !empty($access_state['has_access']);
                } catch (Throwable $exception) {
                    $state['has_access'] = false;
                }
            }
        }

        return $state;
    }
}
if (!isset($my_wishlist_items)) {
    $my_wishlist_items = array();
    if ($user_id = $this->session->userdata('user_id')) {
        $wishlist = $this->user_model->get_all_user($user_id)->row('wishlist');
        if ($wishlist != '') {
            $my_wishlist_items = json_decode($wishlist, true);
            $my_wishlist_items = is_array($my_wishlist_items) ? $my_wishlist_items : array();
        }
    }
}
?>
<?php foreach ($my_wishlist_items as $my_wishlist_item): ?>
    <?php
    $course_details = $this->crud_model->get_course_by_id($my_wishlist_item)->row_array();
    if (!$course_details) continue;
    if (function_exists('youngo_frontend_translate_course_row')) {
        $course_details = youngo_frontend_translate_course_row($course_details, $youngo_frontend_language);
    }
    $course_id = (int) $course_details['id'];
    $course_url = function_exists('youngo_frontend_course_detail_url') ? youngo_frontend_course_detail_url($course_details, $youngo_frontend_language) : site_url('home/course/' . rawurlencode(slugify($course_details['title'])) . '/' . $course_id);
    $youngo_course_player_title = isset($course_details['youngo_canonical_title']) ? $course_details['youngo_canonical_title'] : $course_details['title'];
    $category_id = !empty($course_details['sub_category_id']) ? (int) $course_details['sub_category_id'] : (int) $course_details['category_id'];
    $category = $category_id > 0 ? $this->crud_model->get_category_details_by_id($category_id)->row_array() : array();
    if (function_exists('youngo_frontend_translate_category_row')) {
        $category = youngo_frontend_translate_category_row($category, $youngo_frontend_language);
    }
    $category_name = !empty($category['name']) ? $category['name'] : youngo_wishlist_phrase('course', '', $youngo_frontend_language);
    $creator_id = !empty($course_details['creator']) ? (int) $course_details['creator'] : (int) trim(strtok((string) $course_details['user_id'], ','));
    $instructor = $creator_id > 0 ? $this->user_model->get_all_user($creator_id)->row_array() : array();
    $instructor_name = !empty($instructor) ? trim($instructor['first_name'] . ' ' . $instructor['last_name']) : '';
    $lesson_count = $this->crud_model->get_lessons('course', $course_id)->num_rows();
    $course_duration = $this->crud_model->get_total_duration_of_lesson_by_course_id($course_id);
    $wishlist_item_access_mode = isset($course_details['youngo_access_mode']) ? (string) $course_details['youngo_access_mode'] : '';
    $price_label = $wishlist_item_access_mode === 'subscription_only' && empty($course_details['is_free_course'])
        ? youngo_wishlist_phrase('subscription_access', '', $youngo_frontend_language)
        : ($course_details['is_free_course'] ? youngo_wishlist_phrase('free', '', $youngo_frontend_language) : ($course_details['discount_flag'] ? currency($course_details['discounted_price']) : currency($course_details['price'])));
    $youngo_boundary_state = youngo_wishlist_course_boundary_state($course_details, (int) $this->session->userdata('user_id'));
    $wishlist_item_checkout_cta_decision = array('show_cta' => false, 'target_url' => null, 'label_text' => '');
    if (function_exists('youngo_checkout_cta_decision')) {
        try {
            $wishlist_item_checkout_cta_decision = youngo_checkout_cta_decision($course_details, (int) $this->session->userdata('user_id'), array(
                'access_state' => array('has_access' => !empty($youngo_boundary_state['has_access'])),
            ));
        } catch (Throwable $exception) {
            $wishlist_item_checkout_cta_decision = array('show_cta' => false, 'target_url' => null, 'label_text' => '');
        }
    }
    $wishlist_item_show_checkout_cta = !empty($wishlist_item_checkout_cta_decision['show_cta']) && !empty($wishlist_item_checkout_cta_decision['target_url']);
    $wishlist_item_show_subscription_cta = $wishlist_item_access_mode === 'subscription_only' || $wishlist_item_access_mode === 'subscription_and_purchase';
    $wishlist_item_checkout_label = !empty($wishlist_item_checkout_cta_decision['label_text']) ? $wishlist_item_checkout_cta_decision['label_text'] : youngo_wishlist_phrase('continue_to_checkout', 'Continue to checkout', $youngo_frontend_language);
    ?>
    <article class="youngo-commerce-course-card">
        <a class="youngo-commerce-course-card__media" href="<?php echo $course_url; ?>">
            <img loading="lazy" src="<?php echo $this->crud_model->get_course_thumbnail_url($course_id); ?>" alt="<?php echo htmlspecialchars($course_details['title']); ?>">
            <span><?php echo $price_label; ?></span>
        </a>

        <div class="youngo-commerce-course-card__body">
            <div class="youngo-commerce-course-card__topline">
                <span><?php echo htmlspecialchars($category_name); ?></span>
                <button type="button" class="youngo-icon-button" onclick="actionTo('<?php echo site_url('home/toggleWishlistItems/' . $course_id); ?>');" aria-label="<?php echo youngo_wishlist_phrase('remove_from_wishlist', '', $youngo_frontend_language); ?>">
                    <i class="fa-solid fa-heart"></i>
                </button>
            </div>

            <h3><a href="<?php echo $course_url; ?>"><?php echo htmlspecialchars($course_details['title']); ?></a></h3>

            <div class="youngo-commerce-course-card__meta">
                <?php if ($instructor_name !== ''): ?><span><i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($instructor_name); ?></span><?php endif; ?>
                <span><i class="fa-regular fa-list-alt"></i> <?php echo $lesson_count . ' ' . youngo_wishlist_phrase('lessons', '', $youngo_frontend_language); ?></span>
                <?php if ($course_duration !== ''): ?><span><i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($course_duration); ?></span><?php endif; ?>
            </div>

            <div class="youngo-commerce-course-card__actions">
                <a class="youngo-button youngo-button--small youngo-button--secondary" href="<?php echo $course_url; ?>"><?php echo youngo_wishlist_phrase('course_details', '', $youngo_frontend_language); ?></a>
                <?php if (!empty($youngo_boundary_state['has_access'])): ?>
                    <a class="youngo-button youngo-button--small" href="<?php echo site_url('home/lesson/' . slugify($youngo_course_player_title) . '/' . $course_id); ?>"><?php echo youngo_wishlist_phrase('start_now', '', $youngo_frontend_language); ?></a>
                <?php elseif ($wishlist_item_show_checkout_cta): ?>
                    <a class="youngo-button youngo-button--small" href="<?php echo html_escape($wishlist_item_checkout_cta_decision['target_url']); ?>" data-youngo-checkout-cta="local-wishlist-item"><i class="fa-solid fa-credit-card"></i> <?php echo htmlspecialchars($wishlist_item_checkout_label, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php elseif ($wishlist_item_show_subscription_cta): ?>
                    <a class="youngo-button youngo-button--small youngo-button--subscription" href="<?php echo site_url('subscriptions'); ?>"><i class="fa-solid fa-crown"></i> <?php echo youngo_wishlist_phrase('view_subscription_plans', '', $youngo_frontend_language); ?></a>
                <?php elseif ($course_details['is_free_course']): ?>
                    <a class="youngo-button youngo-button--small" href="<?php echo site_url('home/get_enrolled_to_free_course/' . $course_id); ?>"><?php echo youngo_wishlist_phrase('enroll_now', '', $youngo_frontend_language); ?></a>
                <?php else: ?>
                    <a class="youngo-button youngo-button--small" href="<?php echo $youngo_wishlist_items_contact_url; ?>"><?php echo youngo_wishlist_phrase('contact', '', $youngo_frontend_language); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </article>
<?php endforeach; ?>
<?php if (count($my_wishlist_items) == 0): ?>
    <div class="youngo-commerce-empty">
        <div class="youngo-commerce-empty__icon"><i class="fa-regular fa-heart"></i></div>
        <p class="youngo-eyebrow"><?php echo youngo_wishlist_phrase('no_saved_courses_yet', '', $youngo_frontend_language); ?></p>
        <h2><?php echo youngo_wishlist_phrase("build_your_child's_shortlist", '', $youngo_frontend_language); ?></h2>
        <p><?php echo youngo_wishlist_phrase('save_interesting_courses_while_browsing,_then_compare_options_before_enrolling.', '', $youngo_frontend_language); ?></p>
        <a class="youngo-button" href="<?php echo $youngo_wishlist_items_courses_url; ?>"><?php echo youngo_wishlist_phrase('browse_courses', '', $youngo_frontend_language); ?></a>
    </div>
<?php endif; ?>
